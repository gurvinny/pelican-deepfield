# Security Policy

## Supported versions

Deepfield is a theme plugin for [Pelican Panel](https://pelican.dev). Only the
latest release receives fixes.

| Version | Supported |
|---|---|
| 1.3.x | yes |
| < 1.3 | no |

## Reporting a vulnerability

**Please do not open a public issue for a security problem.**

Use GitHub's private vulnerability reporting instead:
[**Report a vulnerability**](https://github.com/gurvinny/pelican-deepfield/security/advisories/new).
That opens a private advisory visible only to the maintainer, so a fix can ship
before the details are public.

Expect an acknowledgement within a few days. If a fix is warranted it will be
released with credit to you, unless you would rather stay anonymous.

## Scope

This plugin ships CSS, Blade view hooks and a small amount of JavaScript that
run inside an authenticated panel. The things worth reporting:

- Stored or reflected XSS reachable through the plugin's settings fields or its
  rendered views
- A plugin setting that escapes its intended scope and affects panel behaviour
  beyond theming
- Anything in the release archive that does not correspond to the tagged source

Out of scope: vulnerabilities in Pelican Panel, Filament or Laravel themselves.
Report those to their own maintainers.

## Verifying a release

Release archives are built by GitHub Actions from a tagged commit, and the
workflow refuses to publish when the tag, `plugin.json` and `update.json`
disagree on the version. Prefer the published archive over a hand-built one.
