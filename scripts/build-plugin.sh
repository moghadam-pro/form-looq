#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="$ROOT_DIR/mpro-forms"
DIST_DIR="$ROOT_DIR/dist"
BUILD_DIR="$DIST_DIR/mpro-forms"
ZIP_FILE="$DIST_DIR/mpro-forms.zip"

rm -rf "$DIST_DIR"
mkdir -p "$BUILD_DIR"

cp -R "$PLUGIN_DIR"/. "$BUILD_DIR"/
find "$BUILD_DIR" -type f \( -name '.DS_Store' -o -name '*.log' -o -name '*.map' \) -delete

HEADER_VERSION="$(sed -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*//p' "$BUILD_DIR/mpro-forms.php" | head -n 1 | tr -d '\r')"
STABLE_TAG="$(sed -n 's/^Stable tag:[[:space:]]*//p' "$BUILD_DIR/readme.txt" | head -n 1 | tr -d '\r')"

if [[ -z "$HEADER_VERSION" || "$HEADER_VERSION" != "$STABLE_TAG" ]]; then
  echo "Version mismatch: plugin header='$HEADER_VERSION', readme stable tag='$STABLE_TAG'" >&2
  exit 1
fi

if grep -RIlE '(vendor/|node_modules/|tests/|\.git/)' "$BUILD_DIR" >/dev/null 2>&1; then
  echo 'Unexpected development-only path reference found in release package.' >&2
  exit 1
fi

(
  cd "$DIST_DIR"
  zip -qr "$(basename "$ZIP_FILE")" "$(basename "$BUILD_DIR")"
)

unzip -tq "$ZIP_FILE" >/dev/null
printf 'Built %s (version %s)\n' "$ZIP_FILE" "$HEADER_VERSION"
