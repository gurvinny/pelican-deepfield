# Contributing

Thanks for taking a look. Deepfield is a small plugin, so the process is short.

## Before you start

Open an issue first for anything beyond a fix. The theme is opinionated and a
change that fits your panel may not fit the design direction, and it is better
to find that out before you write it.

## Working on it

- **Panel target:** Pelican Panel 1.0.0-beta38+, Filament v5, PHP 8.3+.
- **Both palettes matter.** The stylesheet ships a dark *deep cosmic* and a
  light *cool observatory* palette driven by the same rule set. Colours are
  `--df-rgb-*` channel triples used as `rgb(var(--df-rgb-x) / a)`; add a colour
  by defining a triple, not a literal, or it will only work in one mode.
- **Do not hide or override the panel's theme switcher.** Deepfield sets its
  preferred mode with `$panel->defaultThemeMode(...)` and otherwise leaves the
  user's choice alone. Writing `localStorage.theme` fights Filament's own script
  and breaks the switcher for everyone.
- Keep `plugin.json` and `update.json` in agreement on the version. CI checks
  this on every pull request, and the release workflow refuses to publish when
  they disagree.

## Testing

There is no automated visual test. Run the plugin against a real panel — the
repository's own notes describe a Docker rig — and check both light and dark
mode before opening a PR.

CI will syntax-check the PHP and JavaScript and validate both manifests.

## Reporting bugs

Include the panel version, the Deepfield version, the browser, and whether it
reproduces in both colour modes. A screenshot helps more than a description for
anything visual.

Security problems go through [SECURITY.md](SECURITY.md), not the issue tracker.
