# Marketing artwork

Generated composites built from the captures in `../screenshots/`, the plugin's own
colour tokens and its self-hosted fonts.

| File | Size | Use |
|---|---|---|
| `social-preview.png` | 1280 × 640 | GitHub → Settings → Social preview |
| `hub/01-hero.png` | 2520 × 1080 (21:9) | Pelican Hub — first screenshot (primary background image) |
| `hub/02-console.png` | 2520 × 1080 | Pelican Hub |
| `hub/03-admin.png` | 2520 × 1080 | Pelican Hub |
| `hub/04-servers.png` | 2520 × 1080 | Pelican Hub |
| `hub/05-settings.png` | 2520 × 1080 | Pelican Hub |
| `hub/06-login.png` | 2520 × 1080 | Pelican Hub |

## Rebuilding

```bash
node marketing/build.js            # uses a local Chrome/Chromium
CHROME=/usr/bin/chromium node marketing/build.js
```

Copy, captions and layout live in `build.js`. Re-run it after replacing any file in
`../screenshots/` so the artwork stays in step with the current release.
