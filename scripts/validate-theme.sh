#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
theme_dir="$repo_root/formlooq-theme"

required=(style.css functions.php header.php footer.php front-page.php index.php page.php 404.php readme.txt screenshot.png assets/js/theme.js assets/images/logo-mark.svg assets/images/logo-en.svg assets/images/logo-fa.svg assets/fonts/inter/inter-400.woff2 assets/fonts/inter/inter-600.woff2 assets/fonts/inter/inter-700.woff2 assets/fonts/inter/inter-800.woff2 assets/fonts/vazirmatn/vazirmatn-400.woff2 assets/fonts/vazirmatn/vazirmatn-500.woff2 assets/fonts/vazirmatn/vazirmatn-700.woff2 assets/fonts/vazirmatn/vazirmatn-800.woff2 inc/content.php inc/content-data.php inc/remote-content.php)
for file in "${required[@]}"; do
  test -f "$theme_dir/$file" || { echo "Missing: $file" >&2; exit 1; }
done

if command -v php >/dev/null 2>&1; then
  while IFS= read -r -d '' file; do php -l "$file" >/dev/null; done < <(find "$theme_dir" -name '*.php' -print0)
elif [[ "${1:-}" == "--require-php" ]]; then
  echo "PHP is required for this validation run." >&2
  exit 1
else
  echo "PHP is unavailable; PHP lint deferred to CI."
fi

node --check "$theme_dir/assets/js/theme.js"

if rg -n 'fonts\.googleapis|cdnjs\.cloudflare|\[cite:' "$theme_dir"; then
  echo "External font/icon dependency or leaked citation marker found." >&2
  exit 1
fi

for lang in en fa; do
  for route in features demos docs addons download changelog support privacy terms about; do
    rg -q "'$route' =>" "$theme_dir/inc/content-data.php" || { echo "Missing $lang/$route content" >&2; exit 1; }
  done
done

echo "Form LOOQ theme validation passed."
