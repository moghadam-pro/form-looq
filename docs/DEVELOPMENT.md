# Development guide

## Requirements

- PHP 8.1 or newer
- WordPress 6.5 or newer
- Composer (for PHPCS only)
- Node 18+ (for Playwright end-to-end tests only)
- `msgfmt` from GNU gettext (for compiling translations)

There is no build step for the plugin itself. What is in the repository is what
runs.

## Getting a working copy

```bash
git clone https://github.com/moghadam-pro/free-forms-wp-plugin.git
cd free-forms-wp-plugin
```

Symlink or copy `free-mpro-forms/` into a WordPress install:

```bash
ln -s "$PWD/free-mpro-forms" /path/to/wordpress/wp-content/plugins/free-mpro-forms
```

Activate it from the Plugins screen. Activation creates the two tables, grants
the capability to `administrator`, and schedules the retention cron.

## Running the checks

Unit-style validator tests run standalone against a small WordPress function
stub in `tests/bootstrap.php` — no WordPress install required:

```bash
php tests/validator-test.php
```

Coding standards:

```bash
composer install
vendor/bin/phpcs
```

Integration tests need a real WordPress environment and are run by the workflows
in `.github/workflows/`. End-to-end tests use Playwright:

```bash
npm install
npm run test:e2e
```

Syntax-check everything before committing:

```bash
find free-mpro-forms -name '*.php' -exec php -l {} \;
```

## Building a release package

```bash
scripts/build-plugin.sh
```

This copies the plugin directory to `dist/`, refuses to continue if the version
in the plugin header does not match `Stable tag` in `readme.txt`, checks that no
development-only paths leaked in, and produces `dist/free-mpro-forms.zip`.

## Translations

The Persian translation lives in `free-mpro-forms/languages/`. After editing the
`.po` file, recompile the `.mo`:

```bash
cd free-mpro-forms/languages
msgfmt --check-format -o free-mpro-forms-fa_IR.mo free-mpro-forms-fa_IR.po
```

Both files are committed. `msgfmt --statistics` reports how many strings are
translated.

The top-level menu label has a hard-coded Persian fallback in
`Plugin::menu_label()`. It is used only when the locale starts with `fa` and the
translation has not loaded — the menu is the plugin's most visible surface and
should not appear in English on a Persian admin because of a load-order accident.

## Adding a field type

1. Add the type to `Field_Validator::ALLOWED_TYPES`.
2. Add a palette entry in `Field_Validator::types()` with a label, a Dashicon
   name, and whether it carries options.
3. Handle rendering in `Frontend_Form::render_field()`.
4. Handle validation in `Submission_Validator::validate_value()`.
5. Add a case to `tests/validator-test.php`.

The builder needs no change: it reads the palette from `Field_Validator::types()`
through `Page_Builder::type_config()`.

## Adding an admin screen

1. Create `includes/admin/class-page-<name>.php` in the `FreeMPROForms\Admin`
   namespace with a static `render()`.
2. Register it in `Admin::pages()`. Add `'hidden' => true` to keep it routable
   but out of the sidebar.
3. Add the file to the admin include list in `free-mpro-forms.php`.
4. If it handles form submissions, give it a static `init()` that registers an
   `admin_post_*` action, and call that from `free_mpro_forms_bootstrap()`.

Start every `render()` with `Admin::guard()` and `Admin::header()`.

## Adding a setting

1. Add the key and its default to `Settings::defaults()`.
2. Add sanitization to `Settings::sanitize()` — booleans go in the `$booleans`
   array, everything else needs an explicit branch.
3. Render the control in the matching `Page_Settings::render_<tab>_tab()`.

Every checkbox must be preceded by a hidden input with the same name and value
`0`. Unchecked boxes are not posted, so without the pair, unticking a box would
never persist.

## Conventions

- WordPress coding standards, enforced by `phpcs.xml.dist`.
- Yoda conditions, tabs for indentation, `array()` over `[]`.
- Every direct database call carries a `phpcs:ignore` comment naming the sniff.
- Escape at output, sanitize at input. No exceptions.
- Prefix everything with `fmpf_` / `FMPF_` / `FreeMPROForms\`.
- Comments explain why, not what.

## Versioning and releases

Semantic versioning. A release touches four places, all of which must agree:

1. `Version:` in the plugin header
2. `FREE_MPRO_FORMS_VERSION`
3. `Stable tag:` in `readme.txt`
4. The `== Changelog ==` section in `readme.txt`

`scripts/build-plugin.sh` fails the build if 1 and 3 disagree. Bump
`Install::SCHEMA_VERSION` whenever the table structure changes.

## Branches

- `main` — the plugin only, plus a complete `README.md`. Nothing else.
- `docs` — development documentation, decision log, landing page, and a short
  `README.md`.
