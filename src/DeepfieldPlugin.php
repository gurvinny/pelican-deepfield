<?php

namespace Gurvinny\Deepfield;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;

class DeepfieldPlugin implements Plugin, HasPluginSettings
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'deepfield';
    }

    public function register(Panel $panel): void
    {
        $this->publishAssets();

        $panel
            ->colors([
                'primary' => Color::Violet,
                'gray'    => Color::Slate,
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info'    => Color::Cyan,
            ])
            ->renderHook('panels::head.end', fn () => $this->renderHead())
            ->renderHook('panels::body.start', fn () => $this->renderBody());
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

    protected function settings(): array
    {
        return [
            'star_density'   => config('deepfield.star_density', 'medium'),
            'nebula_enabled' => (bool) config('deepfield.nebula_enabled', true),
            'nebula_hue'     => config('deepfield.nebula_hue', 'violet'),
            'crt_bloom'      => (bool) config('deepfield.crt_bloom', true),
            'reduce_motion'  => (bool) config('deepfield.reduce_motion', false),
        ];
    }

    protected function renderHead(): string
    {
        $settings = htmlspecialchars(json_encode($this->settings()), ENT_QUOTES, 'UTF-8');
        $cssHref = asset('plugins/deepfield/deepfield.css');
        $spaceGrotesk = asset('plugins/deepfield/fonts/SpaceGrotesk-Variable.woff2');
        $jetbrains    = asset('plugins/deepfield/fonts/JetBrainsMono-Variable.woff2');
        $orbitron     = asset('plugins/deepfield/fonts/Orbitron-Variable.woff2');

        return <<<HTML
<meta name="df-settings" content="{$settings}">
<script>
(function(){
  try{document.documentElement.classList.add('dark');localStorage.setItem('theme','dark');}catch(e){}

  /* Deepfield xterm.js palette shim
     Pelican bakes the terminal palette into inline JS in server-console.blade.php
     and renders via WebGL canvas, which CSS cannot color. We intercept
     window.Xterm the moment Vite assigns it and monkey-patch the Terminal
     constructor to merge our theme into every instance. */
  var DF_XTERM = {
    background: '#050614',
    foreground: '#e8ecff',
    cursor: '#38e1ff',
    cursorAccent: '#050614',
    selectionBackground: 'rgba(94, 234, 212, 0.35)',
    selectionForeground: '#050614',
    black: '#0b0a1e', red: '#f87171', green: '#5eead4', yellow: '#facc15',
    blue: '#7c5cf0', magenta: '#f472b6', cyan: '#38e1ff', white: '#c8cff0',
    brightBlack: '#5b608a', brightRed: '#fca5a5', brightGreen: '#a7f3d0',
    brightYellow: '#fde68a', brightBlue: '#a78bfa', brightMagenta: '#fbcfe8',
    brightCyan: '#7dd3fc', brightWhite: '#ffffff'
  };
  function patchXterm(x){
    if(!x || !x.Terminal || x.__dfPatched) return;
    var Orig = x.Terminal;
    var Patched = function(opts){
      opts = opts || {};
      opts.theme = Object.assign({}, opts.theme, DF_XTERM);
      opts.allowTransparency = true;
      return new Orig(opts);
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
<link rel="preload" href="{$orbitron}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{$cssHref}">
HTML;
    }

    protected function renderBody(): string
    {
        $jsSrc = asset('plugins/deepfield/deepfield.js');

        return <<<HTML
<canvas id="df-nebula" aria-hidden="true"></canvas>
<canvas id="df-starfield" aria-hidden="true"></canvas>
<script defer src="{$jsSrc}"></script>
HTML;
    }

    // HasPluginSettings ---------------------------------------------------

    public function getSettingsForm(): array
    {
        return [
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
                ->inline(false),

            Select::make('nebula_hue')
                ->label(trans('deepfield::strings.nebula_hue'))
                ->options([
                    'violet' => trans('deepfield::strings.hue_violet'),
                    'teal'   => trans('deepfield::strings.hue_teal'),
                    'rose'   => trans('deepfield::strings.hue_rose'),
                ])
                ->native(false)
                ->required(),

            Toggle::make('crt_bloom')
                ->label(trans('deepfield::strings.crt_bloom'))
                ->helperText(trans('deepfield::strings.crt_bloom_help'))
                ->inline(false),

            Toggle::make('reduce_motion')
                ->label(trans('deepfield::strings.reduce_motion'))
                ->helperText(trans('deepfield::strings.reduce_motion_help'))
                ->inline(false),
        ];
    }

    public function getSettingsFormData(): array
    {
        return $this->settings();
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'DEEPFIELD_STAR_DENSITY'   => $data['star_density'] ?? 'medium',
            'DEEPFIELD_NEBULA_ENABLED' => ($data['nebula_enabled'] ?? true) ? 'true' : 'false',
            'DEEPFIELD_NEBULA_HUE'     => $data['nebula_hue'] ?? 'violet',
            'DEEPFIELD_CRT_BLOOM'      => ($data['crt_bloom'] ?? true) ? 'true' : 'false',
            'DEEPFIELD_REDUCE_MOTION'  => ($data['reduce_motion'] ?? false) ? 'true' : 'false',
        ]);

        Notification::make()
            ->title(trans('deepfield::strings.saved'))
            ->success()
            ->send();
    }
}
