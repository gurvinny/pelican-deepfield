<div align="center">

# Deepfield

**A deep-cosmic theme plugin for [Pelican Panel](https://pelican.dev).**
*Run your servers from the edge of the observable universe. Aurora glow, parallax stars, terminal that hums.*

![Version](https://img.shields.io/badge/version-1.0.0-a78bfa?style=for-the-badge&labelColor=0b0a1e)
![License](https://img.shields.io/badge/license-MIT-38e1ff?style=for-the-badge&labelColor=0b0a1e)
![Panel](https://img.shields.io/badge/pelican_panel-%5E1.0-5eead4?style=for-the-badge&labelColor=0b0a1e)
![Filament](https://img.shields.io/badge/filament-v5.7-f472b6?style=for-the-badge&labelColor=0b0a1e)

</div>

---

## What it is

Deepfield replaces Pelican Panel's default look with a dark-only, cosmic-atmospheric UI:

- **Canvas-based starfield** with three parallax depth layers, mouse-driven drift, and pooled meteors with tapered gradient trails — replaces the naive DOM-`<span>` approach seen in other theme plugins.
- **Drifting nebula fog** layered beneath the stars for depth. Hue-swappable (violet · teal · rose).
- **Aurora accent gradient** (cyan → teal → violet) on primary buttons, active nav, focus rings, and status badges.
- **Glassmorphism where it earns its keep** — sidebar and topbar float as translucent slabs, but with heavy tint + strong blur so text always wins. Modals stay near-opaque. Nothing feels washed out.
- **Premium typography** — Orbitron for display H1s, Space Grotesk for the interface, JetBrains Mono for every stretch of code. All self-hosted (no CDN or Google Fonts round-trip).
- **Terminal-first server console** with optional CRT bloom and subtle scanlines. Toggle it off during incidents.
- **Filament-native settings page** — starfield density, nebula on/off, hue, CRT bloom, reduce-motion. Written straight to `.env`, no database migrations.
- **Motion respects you** — `prefers-reduced-motion` is honored automatically; there is also an explicit user toggle.

Skins **all three Filament panels**: admin, app (server list), and server (client console).

---

## Install

### Via panel frontend
Admin → Plugins → Install from ZIP → upload `deepfield.zip` from the [releases page](https://github.com/gurvinny/pelican-deepfield/releases).

### Manual
```bash
cd /var/www/pelican/plugins
git clone https://github.com/gurvinny/pelican-deepfield.git deepfield
cd /var/www/pelican
php artisan p:plugin:install
# select "deepfield"
```

> **Important:** the target folder **must** be named `deepfield` (matching the `id` in `plugin.json`). If you `git clone` without the trailing `deepfield` argument, the folder ends up as `pelican-deepfield` and the panel will refuse it with a "plugin id mismatch" error. Just rename the folder — no other changes needed.

Hard-refresh the panel (`Ctrl+Shift+R`). You should see the nebula fade in and the starfield settle behind your sidebar.

---

## Settings

Admin → Plugins → **Deepfield** → Settings.

| Setting | Default | Description |
|---|---|---|
| **Starfield density** | Medium | `off` / `low` (~250) / `medium` (~600) / `high` (~1200) — perf lever for older clients |
| **Nebula fog** | On | Soft drifting cloud layer beneath the stars |
| **Nebula hue** | Violet | `violet` · `teal` · `rose` |
| **CRT bloom on server console** | On | Phosphor glow + subtle scanlines on the in-game-server console page |
| **Reduce motion** | Off | Disables parallax + meteors. `prefers-reduced-motion` is always respected regardless of this setting |

Values are persisted to `.env` (`DEEPFIELD_*`) so they survive updates.

---

## Screenshots

*Coming soon.*

<!-- ![Dashboard](screenshots/dashboard.png) -->
<!-- ![Server list](screenshots/server-list.png) -->
<!-- ![Console with CRT bloom](screenshots/console.png) -->
<!-- ![Settings](screenshots/settings.png) -->

---

## Performance notes

- Runs a single `requestAnimationFrame` loop for stars, a second (~10 fps effective) for the nebula.
- Pauses both loops when the tab is hidden (`visibilitychange`).
- Pointer parallax is passive and eased — no synchronous layout thrash.
- The starfield is DPR-aware but caps at `devicePixelRatio: 2` to avoid melting 4K displays.
- Density `high` (~1200 stars) has been comfortable at 60fps on a mid-range 2019 laptop. If you notice fan spin, drop to `medium` or `low`.

---

## Compatibility

- **Pelican Panel:** ≥ 1.0
- **Filament:** v5.7
- **PHP:** ≥ 8.3
- **Browsers:** any evergreen browser with `backdrop-filter` support (Chrome, Edge, Firefox, Safari 15.4+).

If Pelican Panel bumps to a new major Filament version, expect a compat patch — open an issue if I'm slow.

---

## Roadmap

- [ ] Screenshots
- [ ] Optional lightweight light-mode variant (dawn cosmic — cream bg, indigo ink)
- [ ] Extra nebula presets (aurora / ember / abyss)
- [ ] Per-user preference overrides (currently server-wide via `.env`)
- [ ] Publish on the Pelican Hub marketplace

---

## Credits

Made by [@gurvinny](https://github.com/gurvinny).

Fonts: **Space Grotesk** (Florian Karsten), **JetBrains Mono** (JetBrains), **Orbitron** (Matt McInerney / The League of Moveable Type) — all SIL Open Font License 1.1.

---

## License

**MIT** for the plugin code. See [`LICENSE`](LICENSE).
Bundled fonts are OFL 1.1 — see `fonts/OFL.*.txt`.
