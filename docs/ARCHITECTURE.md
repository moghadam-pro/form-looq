# Architecture

This document describes the Form LOOQ 0.4.1 codebase on `main`.

## Guiding constraints

1. **Local data ownership.** Forms and entries are stored in the site's own WordPress database.
2. **Data survives normal uninstall/reinstall.** Plugin-owned tables are preserved unless the administrator explicitly opts into data deletion.
3. **No mandatory remote service.** Remote Add-ons/Help content is off by default, cached, validated, and has bundled fallback behavior.
4. **RTL/LTR parity.** The same frontend implementation supports both directions.
5. **Reviewable distribution.** The installable ZIP is generated from the `form-looq/` directory and is validated before release.

## Repository layout

```text
.github/workflows/       CI and release automation
form-looq/               installable WordPress plugin source
  form-looq.php          plugin bootstrap and public constants
  assets/                admin/frontend CSS, JS, and brand assets
  includes/              core runtime classes
    admin/               admin-screen classes
  languages/             bundled translations
  readme.txt             WordPress.org package metadata
  uninstall.php          opt-in uninstall cleanup
scripts/                 packaging scripts for Bash and PowerShell
tests/                   validator, integration, and browser tests
docs/                    canonical engineering documentation
```

Only `form-looq/` is packaged for WordPress installation.

## Bootstrap and loading

`form-looq/form-looq.php` defines:

- `FORM_LOOQ_VERSION`
- `FORM_LOOQ_FILE`
- `FORM_LOOQ_DIR`

Core classes are loaded eagerly because the runtime set is small. Admin-only classes load only when `is_admin()`.

`form_looq_bootstrap()` runs on `plugins_loaded` and initializes schema upgrades, settings, rate limiting, frontend rendering/submission, privacy integrations, dashboard/Elementor integration, and admin screens.

Namespace: `FormLooq\`.

## Storage model

Form LOOQ owns two tables using `DB::PREFIX = 'looq_'`:

- `{prefix}looq_forms`
- `{prefix}looq_entries`

Forms keep field definitions and settings as JSON in the form row. Entries keep submitted field values as JSON in the entry row.

The schema version is currently `Install::SCHEMA_VERSION = 3`.

### Upgrade compatibility

The installer contains explicit migration support for both previous product identities:

```text
fmpf_*        Free MPRO Forms
   ↓
mpro_*        MPRO Forms
   ↓
looq_* / form_looq_*   Form LOOQ
```

Legacy tables, options, cron hooks, and administrator capabilities are migrated or cleaned during upgrade. Existing content using `[mpro_form]` or `[free_mpro_form]` continues to render through compatibility aliases.

## Capability model

The primary plugin capability is:

```text
looq_manage_forms
```

It is granted to administrators during activation/upgrade. Runtime access is filterable through `form_looq_capability`, and `manage_options` remains an administrator fallback.

## Frontend rendering

The canonical shortcode is:

```text
[looq_form id="12"]
```

A form render:

1. resolves an active form,
2. records a view,
3. enqueues frontend assets only when needed,
4. renders semantic fields with validation/accessibility metadata,
5. follows explicit `rtl`/`ltr` direction or the WordPress locale direction.

## Submission flow

Frontend submissions use `admin_post_looq_submit` / `admin_post_nopriv_looq_submit`.

The flow includes:

1. nonce, honeypot, and elapsed-time checks,
2. form-scoped rate limiting,
3. server-side field validation,
4. short-lived opaque submission-state storage for validation errors,
5. local entry creation,
6. safe redirect handling.

Configured external redirects are passed through `wp_safe_redirect()`; the configured destination host is added only for that redirect rather than bypassing WordPress safe-redirect validation.

## Privacy and lifecycle

- Submission data is stored locally.
- Optional IP storage uses a non-reversible hash rather than a raw IP.
- WordPress personal-data export and erasure integrations are implemented.
- Retention cleanup is scheduled only when a retention window is configured.
- Deactivation clears the scheduled cleanup hook.
- Normal uninstall preserves the plugin tables.
- Data is removed only when the administrator explicitly enables the uninstall cleanup setting.

The release-candidate CI verifies the preserve-on-uninstall and opt-in-delete behaviors against a real WordPress installation.

## Remote content

`Remote_Content` can retrieve:

- `https://formlooq.ir/addons.json`
- `https://formlooq.ir/docs.json`

Remote fetching is disabled by default. When enabled it:

- uses GET with `Accept: application/json`,
- identifies the plugin version and site URL in the user agent,
- requires HTTP 200 and a JSON content type,
- normalizes all returned fields before display,
- caches responses,
- falls back to bundled content when unavailable.

No form or entry content is sent by this feature.

## Extension points

Current public hooks include:

- `form_looq_form_created`
- `form_looq_form_updated`
- `form_looq_form_deleted`
- `form_looq_entry_created`
- `form_looq_entry_deleted`
- `form_looq_capability`
- `form_looq_templates`
- `form_looq_track_views`
- `form_looq_rate_limit_enabled`
- `form_looq_rate_limit_windows`
- `form_looq_rate_limit_identity`

## Verification architecture

The controlled quality workflow covers:

- PHP syntax on PHP 8.1, 8.2, and 8.3,
- validator tests,
- JavaScript syntax,
- WordPress Coding Standards,
- WordPress Plugin Check,
- WordPress integration tests,
- Chromium LTR/RTL and keyboard browser tests,
- installable ZIP verification,
- packaged clean-install release-candidate tests.

See [DEVELOPMENT.md](DEVELOPMENT.md) and [RELEASE.md](RELEASE.md).
