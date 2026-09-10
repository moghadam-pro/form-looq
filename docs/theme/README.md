# Form LOOQ Theme

`formlooq-theme/` is the source of the standalone WordPress theme used by formlooq.ir. It is intentionally independent from the plugin package and has no required plugin dependency.

## Product principles

- Design-first, lightweight, and RTL/LTR-native.
- Honest current-release content; roadmap features are labelled as future work.
- Local assets only—no Google Fonts, icon CDN, or frontend framework.
- Separate canonical Persian and English URLs with matching `hreflang` metadata.
- Semantic HTML, keyboard navigation, visible focus, and reduced-motion support.
- WordPress content remains supported through the standard `page.php`; product routes are controlled by the site-specific theme.

## Brand assets

- `assets/images/logo-mark.svg`: shared Form LOOQ mark.
- `assets/images/logo-en.svg`: mark plus English `Form LOOQ` logotype.
- `assets/images/logo-fa.svg`: mark plus Persian `فرم‌لوک` logotype.

The header and footer select the correct lockup from the URL language.

The WordPress theme card uses the approved 1200×900 Form LOOQ product cover at
`screenshot.png`.

## Plugin catalogue endpoints

The theme serves `/addons.json` and `/docs.json` with the exact version-1 contracts consumed by the plugin's optional remote-content feature. Plain `/addons`, `/docs`, `/privacy`, and `/terms` URLs redirect to their canonical English equivalents so existing plugin links remain valid.

## Local development

Copy or symlink `formlooq-theme/` to `wp-content/themes/formlooq-theme`, activate it, and use pretty permalinks. Activation flushes rewrite rules once.

Run the lightweight checks from the repository root:

```bash
bash scripts/validate-theme.sh
bash scripts/build-theme.sh
```

The build creates `dist/formlooq-theme.zip`. It never modifies the plugin package.

## Content source

Product content is centralized in `inc/content-data.php`. English and Persian entries share the same route keys so the language switcher always points to an equivalent canonical page.

Current capabilities were checked against Form LOOQ 0.4.2. Do not present roadmap features such as email notifications, conditional fields, multi-step forms, file uploads, SMS delivery, or add-on activation as shipped functionality.

## Public feedback form

The homepage and Support page render Form LOOQ form ID `5` for bug reports and
feature requests. If the plugin or form is unavailable, visitors receive a
GitHub issue link instead of a broken shortcode. The production form ID can be
changed without editing templates:

```php
add_filter( 'formlooq_theme_support_form_id', static fn() => 12 );
```

## Deployment

1. Build `dist/formlooq-theme.zip`.
2. Take a database and `wp-content` backup.
3. Install or update the theme on staging.
4. Confirm `/en/`, `/fa/`, all footer pages, the GitHub download, mobile navigation, and 404 behavior.
5. Deploy the same tested ZIP to production.
