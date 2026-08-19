<div align="center">

# Deepfield

**A deep-cosmic theme plugin for [Pelican Panel](https://pelican.dev).**
*Run your servers from the edge of the observable universe.*

![Version](https://img.shields.io/badge/version-1.1.0-a78bfa?style=for-the-badge&labelColor=0b0a1e)
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

- **Canvas starfield** — three parallax depth layers, mouse-driven drift, pooled meteors with tapered trails.
- **Nebula fog** — slow-drifting cloud layer beneath the stars. Hue-swappable (violet · teal · rose).
- **Aurora accents** — cyan → teal → violet on primary buttons, active nav, focus rings, status badges.
- **Readable glass** — sidebar and topbar float as translucent slabs with heavy tint; modals stay near-opaque.
- **Terminal-first console** — CRT top bar, optional phosphor bloom and scanlines, four xterm palettes including a 1:1 Minecraft vanilla `§` match.
- **Self-hosted type** — Orbitron display, Space Grotesk UI, JetBrains Mono code. No CDN, no Google Fonts.
- **Filament-native settings** — every effect toggleable in-panel, persisted to `.env`. No migrations.
- **Responsive & motion-safe** — sidebar collapses on narrow viewports; `prefers-reduced-motion` always honored.

Skins all three Filament panels: **admin**, **app**, and **server**. Dark-only by design.

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

Then hard-refresh the panel (`Ctrl+Shift+R`).

## Settings

Admin → Plugins → **Deepfield** → Settings. Values are written to `.env` as `DEEPFIELD_*`, so they survive updates.

| Setting | Default | Options |
|---|---|---|
| Starfield density | Medium | `off` / `low` ~250 / `medium` ~600 / `high` ~1200 |
| Nebula fog | On | on / off |
| Nebula hue | Violet | violet · teal · rose |
| CRT bloom | On | Phosphor glow + scanlines on the server console |
| Terminal palette | Cosmic | cosmic · Minecraft Vanilla · Solarized Aurora · Nord Aurora |
| Reduce motion | Off | Disables parallax + meteors |

## Screenshots

<div align="center">

![Login](https://raw.githubusercontent.com/gurvinny/pelican-deepfield/main/screenshots/00-login.png)
*Login — aurora wordmark, glass form card*

![Server list](https://raw.githubusercontent.com/gurvinny/pelican-deepfield/main/screenshots/02-server-list.png)
*Server list — fluid card grid on a cosmic backdrop*

</div>

## Performance

- One `requestAnimationFrame` loop for stars, a second at ~10fps for the nebula. Both pause when the tab is hidden.
- Pointer parallax is passive and eased — no synchronous layout thrash.
- DPR-aware, capped at `2` so 4K displays don't melt.
- `high` density holds 60fps on a mid-range 2019 laptop. Drop to `medium` if you hear fans.

## Compatibility

Pelican Panel ≥ 1.0 · Filament v5.7 · PHP ≥ 8.3 · any evergreen browser with `backdrop-filter` (Safari 15.4+).

If Pelican bumps to a new major Filament version, open an issue if the compat patch is slow to land.

## License

MIT — see [`LICENSE`](LICENSE). Bundled fonts are OFL 1.1 (`fonts/OFL.*.txt`): **Space Grotesk**, **JetBrains Mono**, **Orbitron**.

Made by [@gurvinny](https://github.com/gurvinny).
