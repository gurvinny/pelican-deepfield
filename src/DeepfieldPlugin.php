<?php

namespace Gurvinny\Deepfield;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;

class DeepfieldPlugin implements Plugin, HasPluginSettings
{
    use EnvironmentWriterTrait;

    /**
     * Every setting, its .env key, and the only values it may hold.
     *
     * Anything not listed here is rejected on both read and write. On write this
     * keeps arbitrary strings out of the .env file; on read it keeps them out of
     * the markup the render hooks emit.
     *
     * @var array<string, array{env: string, type: string, default: mixed, values?: string[]}>
     */
    public const SETTINGS = [
        'theme_mode' => [
            'env' => 'DEEPFIELD_THEME_MODE',
            'type' => 'enum',
            'default' => 'dark',
            'values' => ['dark', 'light', 'system'],
        ],
        'panel_colors' => [
            'env' => 'DEEPFIELD_PANEL_COLORS',
            'type' => 'bool',
            'default' => true,
        ],
        'console_always_dark' => [
            'env' => 'DEEPFIELD_CONSOLE_ALWAYS_DARK',
            'type' => 'bool',
            'default' => true,
        ],
        'star_density' => [
            'env' => 'DEEPFIELD_STAR_DENSITY',
            'type' => 'enum',
            'default' => 'medium',
            'values' => ['off', 'low', 'medium', 'high'],
        ],
        'nebula_enabled' => [
            'env' => 'DEEPFIELD_NEBULA_ENABLED',
            'type' => 'bool',
            'default' => true,
        ],
        'nebula_hue' => [
            'env' => 'DEEPFIELD_NEBULA_HUE',
            'type' => 'enum',
            'default' => 'violet',
            'values' => ['violet', 'teal', 'rose'],
        ],
        'crt_bloom' => [
            'env' => 'DEEPFIELD_CRT_BLOOM',
            'type' => 'bool',
            'default' => true,
        ],
        'reduce_motion' => [
            'env' => 'DEEPFIELD_REDUCE_MOTION',
            'type' => 'bool',
            'default' => false,
        ],
        'terminal_palette' => [
            'env' => 'DEEPFIELD_TERMINAL_PALETTE',
            'type' => 'enum',
            'default' => 'cosmic',
            'values' => ['cosmic', 'minecraft', 'solarized_aurora', 'nord_aurora'],
        ],
        'scanline_density' => [
            'env' => 'DEEPFIELD_SCANLINE_DENSITY',
            'type' => 'enum',
            'default' => 'normal',
            'values' => ['off', 'fine', 'normal', 'heavy'],
        ],
        'audio_cues' => [
            'env' => 'DEEPFIELD_AUDIO_CUES',
            'type' => 'bool',
            'default' => false,
        ],
        'tab_title_suffix' => [
            'env' => 'DEEPFIELD_TAB_TITLE_SUFFIX',
            'type' => 'bool',
            'default' => true,
        ],
    ];

    /**
     * Coerce one raw value to its declared type, falling back to the default.
     *
     * Accepts the string forms .env produces as well as the native types the
     * settings form submits.
     */
    public static function coerce(string $key, mixed $value): mixed
    {
        $spec = self::SETTINGS[$key] ?? null;

        if ($spec === null) {
            return null;
        }

        if ($value === null || $value === '') {
            return $spec['default'];
        }

        if ($spec['type'] === 'bool') {
            if (is_bool($value)) {
                return $value;
            }

            return match (strtolower((string) $value)) {
                '1', 'true', 'on', 'yes' => true,
                '0', 'false', 'off', 'no' => false,
                default => $spec['default'],
            };
        }

        return in_array($value, $spec['values'], true) ? $value : $spec['default'];
    }

    public function getId(): string
    {
        return 'deepfield';
    }

    public function register(Panel $panel): void
    {
        $this->publishAssets();

        $settings = $this->settings();

        /*
         * Set the panel's *default* mode rather than forcing one.
         *
         * Deepfield used to add .dark and write localStorage.theme = 'dark' from
         * a head script. Filament stores the user's own choice under that exact
         * key and the head.end hook runs after Filament's own theme script, so
         * the plugin overwrote that choice on every page load and every Livewire
         * navigation — which is what made the theme switcher, and the Theme
         * Customizer plugin, look broken. defaultThemeMode() only supplies the
         * fallback for a user who has not chosen yet.
         */
        $panel
            ->defaultThemeMode(ThemeMode::tryFrom($settings['theme_mode']) ?? ThemeMode::Dark)
            ->renderHook('panels::head.end', fn () => $this->renderHead())
            ->renderHook('panels::body.start', fn () => $this->renderBody());

        /*
         * Filament merges ->colors() per key and the last plugin to register
         * wins, so hardcoding all six roles quietly overrode whatever the admin
         * had picked in Theme Customizer — and which one won depended on plugin
         * load order. Deepfield's stylesheet does not depend on these; they only
         * tune Filament's own component colours. Turning this off hands the
         * palette to the other plugin cleanly.
         */
        if ($settings['panel_colors']) {
            $panel->colors([
                'primary' => Color::Violet,
                'gray'    => Color::Slate,
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info'    => Color::Cyan,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    protected function publishAssets(): void
    {
        $public = public_path('plugins/deepfield');

        if (! is_dir($public)) {
            @mkdir($public, 0755, true);
            @mkdir($public.'/fonts', 0755, true);
        }

        $pairs = [
            __DIR__.'/../css/deepfield.css' => $public.'/deepfield.css',
            __DIR__.'/../js/deepfield.js'   => $public.'/deepfield.js',
        ];

        foreach ($pairs as $src => $dst) {
            if (is_file($src) && (! is_file($dst) || filemtime($src) > filemtime($dst))) {
                @copy($src, $dst);
            }
        }

        $fontsDir = __DIR__.'/../fonts';
        if (is_dir($fontsDir)) {
            foreach (glob($fontsDir.'/*.woff2') ?: [] as $font) {
                $dst = $public.'/fonts/'.basename($font);
                if (! is_file($dst) || filemtime($font) > filemtime($dst)) {
                    @copy($font, $dst);
                }
            }
        }
    }

    /** @return array<string, mixed> */
    protected function settings(): array
    {
        $settings = [];

        foreach (self::SETTINGS as $key => $spec) {
            $settings[$key] = self::coerce($key, config("deepfield.{$key}", $spec['default']));
        }

        return $settings;
    }

    protected function renderHead(): string
    {
        $settings = htmlspecialchars(json_encode($this->settings()), ENT_QUOTES, 'UTF-8');
        $cssHref = $this->asset('deepfield.css');
        $spaceGrotesk = asset('plugins/deepfield/fonts/SpaceGrotesk-Variable.woff2');
        $jetbrains    = asset('plugins/deepfield/fonts/JetBrainsMono-Variable.woff2');
        $orbitron     = asset('plugins/deepfield/fonts/Orbitron-Variable.woff2');

        // Orbitron is the dark theme's display face; light mode uses Space Grotesk
        // for headings instead. Preloading it on a panel that defaults to light
        // fetches a font nothing renders, which is what the browser's
        // "preloaded but not used" warning is pointing at. It still loads on
        // demand for a user who switches to dark.
        $orbitronPreload = self::coerce('theme_mode', config('deepfield.theme_mode')) === 'light'
            ? ''
            : "<link rel=\"preload\" href=\"{$orbitron}\" as=\"font\" type=\"font/woff2\" crossorigin>";

        // SVG favicon — aurora gradient orbit.
        // Use literal `#` in the SVG; rawurlencode() does the escaping.
        // Previously had `%23` literals inside the string AND rawurlencode
        // wrapping — became `%2523` (invalid), fills fell back to black.
        $favicon = 'data:image/svg+xml;utf8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<defs><linearGradient id="a" x1="0" y1="0" x2="1" y2="1">'
            .'<stop offset="0" stop-color="#38e1ff"/>'
            .'<stop offset="0.5" stop-color="#5eead4"/>'
            .'<stop offset="1" stop-color="#a78bfa"/>'
            .'</linearGradient></defs>'
            .'<rect width="64" height="64" rx="14" fill="#050614"/>'
            .'<circle cx="32" cy="32" r="18" fill="none" stroke="url(#a)" stroke-width="3"/>'
            .'<circle cx="32" cy="32" r="4" fill="url(#a)"/>'
            .'</svg>'
        );

        return <<<HTML
<meta name="df-settings" content="{$settings}">
<link rel="icon" type="image/svg+xml" href="{$favicon}">
<script>
(function(){
  /* No theme forcing here. Filament has already resolved the mode from
     localStorage.theme (the user's choice) falling back to the panel's
     defaultThemeMode, which register() sets. Writing that key from this hook
     is what used to overwrite the choice on every page load. */
  /* Route markers — set body attributes so route-specific CSS only applies where it should.
     Body doesn't exist yet at head-time, so defer to DOMContentLoaded, and re-run on Livewire nav. */
  function markRoute(){
    try{
      if (!document.body) return;
      var p = location.pathname.toLowerCase().replace(/\/+$/,'') || '/';

      /* Auth routes */
      if (/\/login|\/register|\/password|\/reset|\/verify|\/two-factor/.test(p)) {
        document.body.setAttribute('data-df-auth','1');
      } else {
        document.body.removeAttribute('data-df-auth');
      }

      /* Server list — Pelican app-panel root. Panel routes vary, so match broadly:
         - "/"
         - anything ending in "/servers"
         - "/admin" / "/app" / "/dashboard" roots
         - Also promote if the page has a server table and the URL contains "server". */
      var isServerList = (
        p === '/' || p === '' ||
        /\/servers\/?$/.test(p) ||
        /^\/(admin|app|dashboard)\/?$/.test(p) ||
        /^\/(admin|app)\/servers\/?$/.test(p)
      );
      if (isServerList) {
        document.body.setAttribute('data-df-page','server-list');
      } else {
        document.body.removeAttribute('data-df-page');
      }
      /* Fallback promotion: if URL contains "server" and a servers table is present */
      if (!isServerList && /server/i.test(p)) {
        setTimeout(function(){
          var hasServerTable = document.querySelector('.fi-ta-table tbody tr');
          if (hasServerTable) document.body.setAttribute('data-df-page','server-list');
        }, 200);
      }
      /* Debug: log the pathname once so we can tune matching if needed */
      if (!window.__dfLogged) {
        window.__dfLogged = true;
      }
    }catch(e){}
  }
  if (document.body) markRoute();
  else document.addEventListener('DOMContentLoaded', markRoute);
  document.addEventListener('livewire:navigated', markRoute);

  /* ---------- Palette catalogue ---------- */
  var PAL = {
    cosmic: {
      background:'#050614', foreground:'#e8ecff', cursor:'#38e1ff', cursorAccent:'#050614',
      selectionBackground:'rgba(94,234,212,0.40)', selectionForeground:'#050614',
      black:'#0b0a1e', blue:'#5b8dff', green:'#22c55e', cyan:'#0ea5e9',
      red:'#ef4444', magenta:'#a855f7', yellow:'#eab308', white:'#d4d4d8',
      brightBlack:'#6b7280', brightBlue:'#60a5fa', brightGreen:'#4ade80', brightCyan:'#22d3ee',
      brightRed:'#f87171', brightMagenta:'#e879f9', brightYellow:'#fde047', brightWhite:'#ffffff'
    },
    minecraft: {
      background:'#050614', foreground:'#ffffff', cursor:'#ffffff', cursorAccent:'#050614',
      selectionBackground:'rgba(255,255,85,0.40)', selectionForeground:'#050614',
      black:'#000000', blue:'#0000aa', green:'#00aa00', cyan:'#00aaaa',
      red:'#aa0000', magenta:'#aa00aa', yellow:'#ffaa00', white:'#aaaaaa',
      brightBlack:'#555555', brightBlue:'#5555ff', brightGreen:'#55ff55', brightCyan:'#55ffff',
      brightRed:'#ff5555', brightMagenta:'#ff55ff', brightYellow:'#ffff55', brightWhite:'#ffffff'
    },
    solarized_aurora: {
      background:'#001b26', foreground:'#93a1a1', cursor:'#2aa198', cursorAccent:'#001b26',
      selectionBackground:'rgba(42,161,152,0.40)', selectionForeground:'#001b26',
      black:'#073642', blue:'#268bd2', green:'#859900', cyan:'#2aa198',
      red:'#dc322f', magenta:'#d33682', yellow:'#b58900', white:'#93a1a1',
      brightBlack:'#586e75', brightBlue:'#83a8ff', brightGreen:'#b0d34a', brightCyan:'#5eead4',
      brightRed:'#ff6b68', brightMagenta:'#ff7ebc', brightYellow:'#fde047', brightWhite:'#fdf6e3'
    },
    nord_aurora: {
      background:'#0d1420', foreground:'#e5e9f0', cursor:'#88c0d0', cursorAccent:'#0d1420',
      selectionBackground:'rgba(136,192,208,0.40)', selectionForeground:'#0d1420',
      black:'#2e3440', blue:'#5e81ac', green:'#a3be8c', cyan:'#88c0d0',
      red:'#bf616a', magenta:'#b48ead', yellow:'#ebcb8b', white:'#d8dee9',
      brightBlack:'#4c566a', brightBlue:'#81a1c1', brightGreen:'#b6d195', brightCyan:'#8fbcbb',
      brightRed:'#d08a91', brightMagenta:'#c9a8c5', brightYellow:'#f0d798', brightWhite:'#eceff4'
    }
  };

  /* The four presets above are all dark-on-dark: every one of them pairs a
     near-black background with a near-white foreground. Handing any of them a
     light backdrop gives white text on white. So when the console is asked to
     follow light mode there is one light palette rather than four, built from
     the same aurora hues walked down until each clears 4.5:1 on white. The
     background is fully transparent so #terminal's own token-driven surface
     shows through, which is what keeps it in step with the rest of the page. */
  var PAL_LIGHT = {
    background:'rgba(0,0,0,0)', foreground:'#14162b', cursor:'#0e7490', cursorAccent:'#ffffff',
    selectionBackground:'rgba(15,118,110,0.28)', selectionForeground:'#14162b',
    /* Every slot clears 4.5:1 against the light console surface (min 5.01), and
       "bright" runs darker than its base rather than lighter — the usual
       convention inverts on a light backdrop, where a lighter variant is the
       one that disappears. That is also why white/brightWhite are the two
       strongest inks here rather than the two faintest. */
    black:'#1f2430', brightBlack:'#4b5563',
    red:'#991b1b',   brightRed:'#b91c1c',
    green:'#065f46', brightGreen:'#047857',
    yellow:'#854d0e',brightYellow:'#a1520a',
    blue:'#1e40af',  brightBlue:'#1d4ed8',
    magenta:'#86198f', brightMagenta:'#a21caf',
    cyan:'#155e75',  brightCyan:'#0e7490',
    white:'#4b5563', brightWhite:'#111827'
  };

  /* Read settings from meta */
  var CFG = { terminal_palette:'cosmic', crt_bloom:true, reduce_motion:false };
  try { var m = document.querySelector('meta[name="df-settings"]'); if (m) Object.assign(CFG, JSON.parse(m.content)); } catch(e){}

  /* Resolved per terminal rather than once: the mode can change under us. */
  function activeTheme(){
    var followsLight = document.body
      && document.body.getAttribute('data-df-console') === 'auto'
      && !document.documentElement.classList.contains('dark');
    return followsLight ? PAL_LIGHT : (PAL[CFG.terminal_palette] || PAL.cosmic);
  }

  /* xterm.js reads its theme at construction and paints the scrollable element
     with an inline background, so a live theme switch has to push the new
     palette into each terminal that already exists. */
  var terminals = [];
  function repaintTerminals(){
    var theme = activeTheme();
    for (var i = terminals.length - 1; i >= 0; i--) {
      var t = terminals[i];
      if (!t || !document.getElementById('terminal')) { terminals.splice(i, 1); continue; }
      try {
        if (t.options) t.options.theme = theme;
        else if (typeof t.setOption === 'function') t.setOption('theme', theme);
      } catch(e){ terminals.splice(i, 1); }
    }
  }
  new MutationObserver(repaintTerminals).observe(document.documentElement, {
    attributes: true, attributeFilter: ['class']
  });

  /* Terminal instance hooks — installed after constructor */
  function installHooks(term){
    if (!term || term.__dfHooked) return;
    term.__dfHooked = true;
    var wrapper = document.getElementById('terminal');
    var idleTimer = null;

    /* Log severity gutter */
    var SEV = { INFO:'#4ade80', WARN:'#fde047', ERROR:'#f87171', FATAL:'#f87171', DEBUG:'#22d3ee', TRACE:'#5b608a' };
    if (typeof term.onLineFeed === 'function' && typeof term.registerMarker === 'function' && typeof term.registerDecoration === 'function') {
      term.onLineFeed(function(){
        try {
          var buf = term.buffer.active;
          var y = buf.cursorY - 1;
          if (y < 0) return;
          var line = buf.getLine(y);
          if (!line) return;
          var text = line.translateToString(true);
          var mm = text.match(/\/(INFO|WARN|ERROR|FATAL|DEBUG|TRACE)\]:/i);
          if (!mm) return;
          var color = SEV[mm[1].toUpperCase()];
          var marker = term.registerMarker(0);
          if (!marker) return;
          term.registerDecoration({ marker: marker, x: 0, width: 1, height: 1, layer: 'top', backgroundColor: color });
        } catch(e){}
      });
    }

    /* Bell → aurora border flash */
    if (typeof term.onBell === 'function') {
      term.onBell(function(){
        if (!wrapper) return;
        wrapper.classList.remove('df-bell-flash');
        void wrapper.offsetWidth;
        wrapper.classList.add('df-bell-flash');
        setTimeout(function(){ wrapper && wrapper.classList.remove('df-bell-flash'); }, 700);
      });
    }

    /* Idle border pulse — after 8s idle */
    function bumpIdle(){
      if (!wrapper) return;
      wrapper.classList.remove('df-idle');
      clearTimeout(idleTimer);
      if (CFG.reduce_motion) return;
      idleTimer = setTimeout(function(){ wrapper && wrapper.classList.add('df-idle'); }, 8000);
    }
    if (typeof term.onLineFeed === 'function') term.onLineFeed(bumpIdle);
    if (typeof term.onData === 'function') term.onData(bumpIdle);
    bumpIdle();

    /* Link provider — Minecraft-style IPv4:port with click-to-copy */
    if (typeof term.registerLinkProvider === 'function') {
      term.registerLinkProvider({
        provideLinks: function(bufferLineNumber, callback){
          try {
            var line = term.buffer.active.getLine(bufferLineNumber - 1);
            if (!line){ callback(undefined); return; }
            var text = line.translateToString();
            var links = [];
            var re = /(?:\d{1,3}\.){3}\d{1,3}(?::\d{2,5})?/g;
            var mm;
            while ((mm = re.exec(text)) !== null) {
              (function(ipVal, s, e){
                links.push({
                  range: { start: { x: s, y: bufferLineNumber }, end: { x: e, y: bufferLineNumber } },
                  text: ipVal,
                  activate: function(){
                    try { navigator.clipboard && navigator.clipboard.writeText(ipVal); } catch(_){}
                    if (wrapper){
                      wrapper.setAttribute('data-df-toast', 'Copied ' + ipVal);
                      wrapper.classList.add('df-toast');
                      setTimeout(function(){ wrapper.classList.remove('df-toast'); }, 1400);
                    }
                  },
                  hover: function(){}, leave: function(){}
                });
              })(mm[0], mm.index + 1, mm.index + mm[0].length);
            }
            callback(links);
          } catch(e){ callback(undefined); }
        }
      });
    }
  }

  /* xterm.js constructor patch */
  function patchXterm(x){
    if(!x || !x.Terminal || x.__dfPatched) return;
    var Orig = x.Terminal;
    var Patched = function(opts){
      opts = opts || {};
      opts.theme = Object.assign({}, opts.theme, activeTheme());
      opts.allowTransparency = true;
      var t = new Orig(opts);
      terminals.push(t);
      requestAnimationFrame(function(){ installHooks(t); });
      return t;
    };
    Patched.prototype = Orig.prototype;
    try { Object.setPrototypeOf(Patched, Orig); } catch(e){}
    x.Terminal = Patched;
    x.__dfPatched = true;
  }
  if (window.Xterm) { patchXterm(window.Xterm); }
  else {
    var _x;
    try {
      Object.defineProperty(window, 'Xterm', {
        configurable: true,
        get: function(){ return _x; },
        set: function(v){ _x = v; patchXterm(v); }
      });
    } catch(e){}
  }
})();
</script>
<link rel="preload" href="{$spaceGrotesk}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{$jetbrains}" as="font" type="font/woff2" crossorigin>
{$orbitronPreload}
<link rel="stylesheet" href="{$cssHref}">
HTML;
    }

    protected function renderBody(): string
    {
        $jsSrc = $this->asset('deepfield.js');

        // json_encode, not htmlspecialchars: HTML entities are not decoded inside a
        // <script> element, so escaping for HTML here would emit the literal entity.
        // coerce() has already reduced this to one of the allowlisted literals.
        $scan = json_encode(self::coerce('scanline_density', config('deepfield.scanline_density')));

        // Drives the light-mode console opt-out in the stylesheet: with this set,
        // the terminal keeps the dark palette even while the panel is light.
        $console = json_encode(
            self::coerce('console_always_dark', config('deepfield.console_always_dark')) ? 'dark' : 'auto'
        );

        // Mirrors the panel_colors setting into the stylesheet. Turning the
        // setting off has to release the primary/danger/success/warning buttons
        // too, not just Filament's registered colour roles — those button rules
        // carry !important and would otherwise keep overriding whichever plugin
        // the admin handed the palette to.
        $colors = json_encode(
            self::coerce('panel_colors', config('deepfield.panel_colors')) ? '1' : '0'
        );

        return <<<HTML
<canvas id="df-nebula" aria-hidden="true"></canvas>
<canvas id="df-starfield" aria-hidden="true"></canvas>
<script>
document.body.setAttribute('data-df-scan',{$scan});
document.body.setAttribute('data-df-console',{$console});
document.body.setAttribute('data-df-colors',{$colors});
</script>
<script defer src="{$jsSrc}"></script>
HTML;
    }

    /**
     * Asset URL with a content hash appended.
     *
     * Without this the panel serves the same URL after a plugin update and browsers
     * keep the previously cached copy, so a shipped fix looks like it did nothing.
     * A content hash rather than filemtime keeps the URL stable across deploys that
     * do not change the file, and avoids publishing server timestamps.
     */
    protected function asset(string $file): string
    {
        static $hashes = [];

        $url = asset("plugins/deepfield/{$file}");

        if (!array_key_exists($file, $hashes)) {
            $path = public_path("plugins/deepfield/{$file}");

            $hashes[$file] = is_file($path)
                ? substr(hash_file('xxh128', $path), 0, 8)
                : null;
        }

        return $hashes[$file] === null ? $url : "{$url}?v={$hashes[$file]}";
    }

    // HasPluginSettings ---------------------------------------------------

    public function getSettingsForm(): array
    {
        return [
            Section::make(trans('deepfield::strings.section_theme'))
                ->description(trans('deepfield::strings.section_theme_help'))
                ->collapsible()
                ->schema([
                    Select::make('theme_mode')
                        ->label(trans('deepfield::strings.theme_mode'))
                        ->helperText(trans('deepfield::strings.theme_mode_help'))
                        ->options([
                            'dark'   => trans('deepfield::strings.mode_dark'),
                            'light'  => trans('deepfield::strings.mode_light'),
                            'system' => trans('deepfield::strings.mode_system'),
                        ])
                        ->native(false)
                        ->required(),

                    Toggle::make('panel_colors')
                        ->label(trans('deepfield::strings.panel_colors'))
                        ->helperText(trans('deepfield::strings.panel_colors_help'))
                        ->inline(false),
                ]),

            Section::make(trans('deepfield::strings.section_background'))
                ->description(trans('deepfield::strings.section_background_help'))
                ->collapsible()
                ->schema([
                    Select::make('star_density')
                        ->label(trans('deepfield::strings.star_density'))
                        ->helperText(trans('deepfield::strings.star_density_help'))
                        ->options([
                            'off'    => trans('deepfield::strings.density_off'),
                            'low'    => trans('deepfield::strings.density_low'),
                            'medium' => trans('deepfield::strings.density_medium'),
                            'high'   => trans('deepfield::strings.density_high'),
                        ])
                        ->native(false)
                        ->required(),

                    Toggle::make('nebula_enabled')
                        ->label(trans('deepfield::strings.nebula_enabled'))
                        ->helperText(trans('deepfield::strings.nebula_enabled_help'))
                        ->live()
                        ->inline(false),

                    Select::make('nebula_hue')
                        ->label(trans('deepfield::strings.nebula_hue'))
                        ->helperText(trans('deepfield::strings.nebula_hue_help'))
                        ->options([
                            'violet' => trans('deepfield::strings.hue_violet'),
                            'teal'   => trans('deepfield::strings.hue_teal'),
                            'rose'   => trans('deepfield::strings.hue_rose'),
                        ])
                        ->native(false)
                        ->required()
                        ->visible(fn (Get $get) => (bool) $get('nebula_enabled')),
                ]),

            Section::make(trans('deepfield::strings.section_console'))
                ->description(trans('deepfield::strings.section_console_help'))
                ->collapsible()
                ->schema([
                    Select::make('terminal_palette')
                        ->label(trans('deepfield::strings.terminal_palette'))
                        ->helperText(trans('deepfield::strings.terminal_palette_help'))
                        ->options([
                            'cosmic'           => trans('deepfield::strings.palette_cosmic'),
                            'minecraft'        => trans('deepfield::strings.palette_minecraft'),
                            'solarized_aurora' => trans('deepfield::strings.palette_solarized'),
                            'nord_aurora'      => trans('deepfield::strings.palette_nord'),
                        ])
                        ->native(false)
                        ->required(),

                    Toggle::make('crt_bloom')
                        ->label(trans('deepfield::strings.crt_bloom'))
                        ->helperText(trans('deepfield::strings.crt_bloom_help'))
                        ->inline(false),

                    Select::make('scanline_density')
                        ->label(trans('deepfield::strings.scanline_density'))
                        ->helperText(trans('deepfield::strings.scanline_density_help'))
                        ->options([
                            'off'    => trans('deepfield::strings.scan_off'),
                            'fine'   => trans('deepfield::strings.scan_fine'),
                            'normal' => trans('deepfield::strings.scan_normal'),
                            'heavy'  => trans('deepfield::strings.scan_heavy'),
                        ])
                        ->native(false)
                        ->required(),

                    Toggle::make('console_always_dark')
                        ->label(trans('deepfield::strings.console_always_dark'))
                        ->helperText(trans('deepfield::strings.console_always_dark_help'))
                        ->inline(false),
                ]),

            Section::make(trans('deepfield::strings.section_motion'))
                ->description(trans('deepfield::strings.section_motion_help'))
                ->collapsible()
                ->schema([
                    Toggle::make('reduce_motion')
                        ->label(trans('deepfield::strings.reduce_motion'))
                        ->helperText(trans('deepfield::strings.reduce_motion_help'))
                        ->inline(false),
                ]),

            Section::make(trans('deepfield::strings.section_chrome'))
                ->description(trans('deepfield::strings.section_chrome_help'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Toggle::make('audio_cues')
                        ->label(trans('deepfield::strings.audio_cues'))
                        ->helperText(trans('deepfield::strings.audio_cues_help'))
                        ->inline(false),

                    Toggle::make('tab_title_suffix')
                        ->label(trans('deepfield::strings.tab_title_suffix'))
                        ->helperText(trans('deepfield::strings.tab_title_suffix_help'))
                        ->inline(false),
                ]),
        ];
    }

    public function getSettingsFormData(): array
    {
        return $this->settings();
    }

    public function saveSettings(array $data): void
    {
        // Fields hidden by ->visible() are absent from $data. Fall back to the value
        // currently in effect rather than the default, so collapsing a dependent
        // field (nebula hue while the fog is off) does not quietly reset it.
        $current = $this->settings();
        $values = [];

        foreach (self::SETTINGS as $key => $spec) {
            $raw = array_key_exists($key, $data) ? $data[$key] : $current[$key];
            $value = self::coerce($key, $raw);

            $values[$spec['env']] = $spec['type'] === 'bool'
                ? ($value ? 'true' : 'false')
                : $value;
        }

        $this->writeToEnvironment($values);

        Notification::make()
            ->title(trans('deepfield::strings.saved'))
            ->success()
            ->send();
    }
}
