<div align="center">

# Deepfield

**A deep-cosmic theme plugin for [Pelican Panel](https://pelican.dev).**
*Run your servers from the edge of the observable universe.*

![Version](https://img.shields.io/badge/version-1.3.0-a78bfa?style=for-the-badge&labelColor=0b0a1e)
![License](https://img.shields.io/badge/license-MIT-38e1ff?style=for-the-badge&labelColor=0b0a1e)
![Panel](https://img.shields.io/badge/pelican_panel-%5E1.0-5eead4?style=for-the-badge&labelColor=0b0a1e)
![Filament](https://img.shields.io/badge/filament-v5.7-f472b6?style=for-the-badge&labelColor=0b0a1e)
![PHP](https://img.shields.io/badge/php-%E2%89%A5_8.3-818cf8?style=for-the-badge&labelColor=0b0a1e)

</div>

---

<div align="center">

![Server console](https://raw.githubusercontent.com/gurvinny/pelican-deepfield/main/screenshots/03-server-console.png)
*Server console — CRT chrome, live logs, aurora power buttons*

</div>

## Features

- **Two palettes, one theme** — *deep cosmic* dark and *cool observatory white* light, driven off a single set of colour tokens. The panel's own theme switcher picks between them and the choice is the user's, per account.
- **Canvas backdrop** — three parallax depth layers, mouse-driven drift, pooled meteors with tapered trails. In light mode the metaphor inverts: a daylight sky with slow drifting motes and a soft aurora haze.
- **Nebula fog** — slow-drifting cloud layer beneath the stars. Hue-swappable (violet · teal · rose).
- **Aurora accents** — cyan → teal → violet on primary buttons, active nav, focus rings, status badges; walked down in lightness for light mode so they stay legible on white.
- **Readable glass** — sidebar and topbar float as translucent slabs with heavy tint; modals stay near-opaque.
- **Terminal-first console** — CRT top bar, optional phosphor bloom and scanlines, four xterm palettes including a 1:1 Minecraft vanilla `§` match. The screen stays dark in light mode by default, so those palettes stay valid.
- **Self-hosted type** — Orbitron display (dark mode), Space Grotesk UI and headings, JetBrains Mono code. No CDN, no Google Fonts.
- **Filament-native settings** — every effect toggleable in-panel, persisted to `.env`. No migrations.
- **Responsive & motion-safe** — sidebar collapses on narrow viewports; `prefers-reduced-motion` always honored.

Skins all three Filament panels: **admin**, **app**, and **server**.

## Install

**Via panel** — Admin → Plugins → Install from ZIP → upload `deepfield.zip` from [releases](https://github.com/gurvinny/pelican-deepfield/releases).

**Manual**

```bash
cd /var/www/pelican/plugins
git clone https://github.com/gurvinny/pelican-deepfield.git deepfield
cd /var/www/pelican
php artisan p:plugin:install   # select "deepfield"
```

> The folder **must** be named `deepfield` to match the `id` in `plugin.json`. Cloning without the trailing argument leaves it as `pelican-deepfield` and the panel rejects it with a plugin-id mismatch — just rename it.

> **Docker:** the official image roots the panel at `/var/www/html`, so the plugin path is `/var/www/html/plugins/deepfield`. Run the artisan command inside the container (`docker compose exec panel php artisan p:plugin:install`), or just install from ZIP through the panel UI, which works the same on both.

Then hard-refresh the panel (`Ctrl+Shift+R`).

## Settings

Admin → Plugins → **Deepfield** → Settings. Values are written to `.env` as `DEEPFIELD_*`, so they survive updates. The page is grouped into five sections; settings that depend on another one are hidden when their parent is off.

**Theme Mode & Colors**

| Setting | Default | Options |
|---|---|---|
| Default theme mode | Dark | `dark` / `light` / `system` — the fallback for a user who has not chosen yet, **not** a lock. The theme switcher stays available and each user's choice is remembered |
| Apply Deepfield's component colors | On | Sets Filament's primary / gray / danger / success / warning / info roles. Turn off to hand those to another plugin such as Theme Customizer |

**Background & Atmosphere**

| Setting | Default | Options |
|---|---|---|
| Starfield density | Medium | `off` / `low` ~250 / `medium` ~600 / `high` ~1200 |
| Nebula fog | On | on / off |
| Nebula hue | Violet | violet · teal · rose |

**Server Console**

| Setting | Default | Options |
|---|---|---|
| Terminal palette | Cosmic | cosmic · Minecraft Vanilla · Solarized Aurora · Nord Aurora |
| CRT bloom | On | Phosphor glow around the console frame |
| CRT scanline density | Normal | `off` / `fine` / `normal` / `heavy` — independent of bloom |
| Keep the console dark in light mode | On | Terminal screen keeps the dark palette while the page chrome goes light. Turn off to let the console follow the light theme |

**Motion & Accessibility**

| Setting | Default | Options |
|---|---|---|
| Reduce motion | Off | Disables parallax, meteors and the scanline drift. `prefers-reduced-motion` is honored regardless |

**Interface Chrome**

| Setting | Default | Options |
|---|---|---|
| Audio cues | Off | Short tone when a server changes state |
| Tab title suffix | On | Appends ` · Deepfield` to the browser tab title |

## Screenshots

<div align="center">

![Login](https://raw.githubusercontent.com/gurvinny/pelican-deepfield/main/screenshots/00-login.png)
*Login — aurora wordmark, glass form card*

![Server list](https://raw.githubusercontent.com/gurvinny/pelican-deepfield/main/screenshots/02-server-list.png)
*Server list — fluid card grid on a cosmic backdrop*

![Plugin settings](https://raw.githubusercontent.com/gurvinny/pelican-deepfield/main/screenshots/04-plugin-settings.png)
*Settings — grouped sections, dependent fields hide themselves*

</div>

## Performance

- One `requestAnimationFrame` loop for stars, a second at ~10fps for the nebula. Both pause when the tab is hidden.
- Pointer parallax is passive and eased — no synchronous layout thrash.
- DPR-aware, capped at `2` so 4K displays don't melt.
- `high` density holds 60fps on a mid-range 2019 laptop. Drop to `medium` if you hear fans.

## Compatibility

Pelican Panel ≥ 1.0 · Filament v5.7 · PHP ≥ 8.3 · any evergreen browser with `backdrop-filter` (Safari 15.4+).

If Pelican bumps to a new major Filament version, open an issue if the compat patch is slow to land.

### Running Deepfield with other plugins

**Theme switcher.** Deepfield does not touch it. It sets the panel's *default* mode and nothing more; each user's own choice is stored by Filament and is left alone. Versions before 1.3.0 overwrote that stored choice on every page load and hid the switcher outright — if the switcher looks stuck or missing, that is the version to upgrade from.

**Theme Customizer.** Fine together. Both plugins want Filament's colour roles, and the last one to register wins, so pick an owner: leave *Apply Deepfield's component colors* on for Deepfield's palette, or turn it off to hand the roles to Theme Customizer. Its font and theme-mode settings work either way.

**Other full themes** (Nord, Neobrutalism, Pterodactyl, Fluffy). **Enable one at a time.** Deepfield restyles the whole panel, and so do they; two of them at once leaves each theme showing through wherever the other has no rule. This is not a bug either plugin can fix — it is what stacking two complete stylesheets does.

## Changelog

See [releases](https://github.com/gurvinny/pelican-deepfield/releases) for what changed in each version, including upgrade notes.

## License

MIT — see [`LICENSE`](LICENSE).

Bundled fonts are **SIL OFL 1.1**, not MIT: **Space Grotesk**, **JetBrains Mono** and **Orbitron**. Their license texts ship alongside them in `fonts/OFL.*.txt`, and the attribution is recorded in [`NOTICE`](NOTICE).

Made by [@gurvinny](https://github.com/gurvinny).
