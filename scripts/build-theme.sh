#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
theme_dir="$repo_root/formlooq-theme"
dist_dir="$repo_root/dist"
archive="$dist_dir/formlooq-theme.zip"

"$repo_root/scripts/validate-theme.sh"
mkdir -p "$dist_dir"
rm -f "$archive"
(cd "$repo_root" && zip -qr "$archive" formlooq-theme -x '*.DS_Store' '*/.git/*')
unzip -t "$archive" >/dev/null
echo "Built $archive"
