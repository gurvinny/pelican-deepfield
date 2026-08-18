#!/usr/bin/env bash
# Build the distributable plugin archive.
#
# The archive is uploaded through the panel's admin UI, which routes the file
# through PHP's upload handler. The official Pelican Docker image ships PHP
# defaults (upload_max_filesize = 2M), so anything larger is rejected before the
# application ever sees it and Livewire answers 422. Only runtime files belong
# in here: screenshots, dev tooling and CI config stay in the repository.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${1:-$ROOT/deepfield.zip}"
# zip runs from inside the staging dir, so the destination must be absolute.
case "$OUT" in
    /*) ;;
    *) OUT="$PWD/$OUT" ;;
esac
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

# Everything the plugin needs at runtime, plus licensing.
PAYLOAD=(
    plugin.json
    update.json
    LICENSE
    README.md
    config
    css
    fonts
    js
    lang
    src
)

# Hard ceiling, well under upload_max_filesize so there is room to grow.
MAX_BYTES=$((1536 * 1024))

mkdir -p "$STAGE/deepfield"
for item in "${PAYLOAD[@]}"; do
    if [ ! -e "$ROOT/$item" ]; then
        echo "missing payload entry: $item" >&2
        exit 1
    fi
    cp -r "$ROOT/$item" "$STAGE/deepfield/"
done

rm -f "$OUT"
(cd "$STAGE" && zip -rq "$OUT" deepfield -x '*.DS_Store' '*/.gitkeep')

SIZE=$(stat -c%s "$OUT")
echo "built $OUT ($(numfmt --to=iec --suffix=B "$SIZE"))"

if [ "$SIZE" -gt "$MAX_BYTES" ]; then
    echo "archive is $SIZE bytes, over the $MAX_BYTES byte ceiling; panel uploads will fail with 422" >&2
    exit 1
fi
