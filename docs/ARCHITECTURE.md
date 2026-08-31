# Architecture

This document describes how Free MPRO Forms 0.2.0 is put together and why. It is
the reference for anyone extending the plugin or reviewing a change.

## Guiding constraints

Four constraints shaped almost every decision below.

1. **Data outlives the plugin.** A site owner must be able to delete the plugin,
   reinstall it, or replace the folder during an update without losing forms or
   entries. This is why storage moved to plugin-owned tables in 0.2.0.
2. **No build step.** The repository ships what runs. There is no bundler,
   transpiler, or minifier, so a reviewer reads exactly what a site executes.
3. **No mandatory network calls.** Every remote request is cacheable, has a
   bundled fallback, and can be switched off in one place.
4. **RTL and LTR are equal.** Layouts use CSS logical properties throughout
   rather than a separate right-to-left stylesheet.

## Directory layout

```
free-mpro-forms/
├── free-mpro-forms.php          Bootstrap: constants, includes, hook wiring
├── uninstall.php                Opt-in data removal
├── readme.txt                   WordPress.org readme
├── assets/
│   ├── css/admin.css            Admin chrome, builder, cards
│   ├── css/forms.css            Frontend form
│   ├── js/admin.js              Copy buttons, confirms, unsaved guard
│   ├── js/builder.js            Drag-and-drop builder
│   ├── js/forms.js              Frontend error focus management
│   └── images/logo.svg          Plugin logo
├── includes/
│   ├── class-plugin.php         Identity, capability, URLs, embed snippets
│   ├── class-db.php             Table names, JSON encode/decode
│   ├── class-install.php        Schema creation and upgrades
│   ├── class-settings.php       Option storage and sanitization
│   ├── class-form-repository.php
│   ├── class-entry-repository.php
│   ├── class-field-validator.php
│   ├── class-submission-validator.php
│   ├── class-submission-state.php
│   ├── class-templates.php      Starter templates
│   ├── class-rate-limiter.php
│   ├── class-frontend-form.php  Shortcode and submission handling
│   ├── class-privacy-manager.php
│   ├── class-privacy-policy.php
│   ├── class-remote-content.php Add-on catalogue and docs index
│   ├── class-dashboard-widget.php
│   ├── class-elementor.php
│   ├── class-elementor-widget.php
│   └── admin/                   One class per admin screen
└── languages/                   fa_IR translation, .po and .mo
```

## Loading strategy

`free-mpro-forms.php` requires the core class files eagerly and the `admin/`
classes only when `is_admin()`. The whole core set is small, and any request that
renders a form needs most of it, so lazy autoloading would add indirection for no
measurable gain.

Every subsystem is wired on `plugins_loaded` through
`free_mpro_forms_bootstrap()`. Nothing runs at file-include time except constant
definitions, which keeps the plugin safe to include from tests and WP-CLI.

## Data model

Two tables, both prefixed with the site's own `$wpdb->prefix` followed by
`fmpf_`.

### `{prefix}fmpf_forms`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `bigint unsigned` | Primary key |
| `title` | `varchar(191)` | Indexed length is safe under `utf8mb4` |
| `description` | `text` | Optional |
| `status` | `varchar(20)` | `active`, `inactive`, or `draft` |
| `fields` | `longtext` | JSON array of field definitions |
| `settings` | `longtext` | JSON object of per-form settings |
| `views` | `bigint unsigned` | Incremented on render |
| `entries_count` | `bigint unsigned` | Denormalised, recomputed on write |
| `author_id` | `bigint unsigned` | Creator |
| `created_at`, `updated_at` | `datetime` | Site time |

Indexes: `status`, `author_id`.

### `{prefix}fmpf_entries`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `bigint unsigned` | Primary key |
| `form_id` | `bigint unsigned` | Parent form |
| `status` | `varchar(20)` | `unread` or `read` |
| `data` | `longtext` | JSON map of field name to value |
| `note` | `text` | Private admin note |
| `ip_hash` | `varchar(64)` | HMAC-SHA256 of the IP, salted; empty when disabled |
| `user_agent` | `varchar(255)` | Truncated |
| `referer` | `varchar(255)` | Truncated |
| `user_id` | `bigint unsigned` | Zero for anonymous submissions |
| `created_at` | `datetime` | Site time |

Indexes: `(form_id, status)`, `created_at`, `user_id`.

### Why JSON columns rather than a field table

A third table of field definitions and a fourth of per-field entry values would
normalise the model, but every read path in the plugin wants the whole form or
the whole entry at once. Storing `fields` and `data` as JSON keeps those reads to
a single row and makes duplication and export trivial. The cost is that entry
search is a `LIKE` over the JSON blob, which is acceptable at the scale a free
forms plugin serves. If per-field querying becomes necessary, an index table can
be added alongside without changing the existing columns.

### Denormalised `entries_count`

The forms list shows an entry count for every row. Computing it with a subquery
per row is fine for twenty rows but is the kind of thing that quietly becomes a
problem. `entries_count` is recomputed by `Form_Repository::recount_entries()`
after every entry insert or delete, and by `Entry_Repository::recount_all()`
after a retention purge.

### Schema versioning

`Install::SCHEMA_VERSION` is compared against the `fmpf_schema_version` option on
every `plugins_loaded`. A mismatch re-runs `dbDelta`, which is additive and safe
to call repeatedly. This means a site that updates by replacing the plugin folder
— never firing the activation hook — still gets its schema brought current.

## Request flows

### Rendering a form

1. `Frontend_Form::shortcode()` resolves the form through `Form_Repository::get()`.
2. A form that is missing, not `active`, or has no fields renders nothing — or a
   short admin-only hint for users who can manage forms.
3. `Form_Repository::record_view()` increments the view counter.
4. Assets are enqueued only at this point, so pages without a form load nothing.
5. Fields render into semantic HTML with per-field help and error IDs wired
   through `aria-describedby`.

### Submitting a form

1. `Rate_Limiter::guard_submission()` runs first on `admin_post_fmpf_submit` at
   priority 5. It repeats the nonce, honeypot, and timing checks so that a
   request rejected by the limiter never reaches the handler.
2. `Frontend_Form::handle_submission()` re-resolves the form, verifies the nonce,
   checks the honeypot, and rejects submissions completed in under two seconds or
   more than a day after the form was rendered.
3. `Submission_Validator::validate()` checks every field against its declared
   type and, for choice fields, against the configured options. It also rejects
   requests carrying keys the form did not define.
4. On failure, values and errors go into a short-lived transient keyed by an
   opaque token, and the visitor is redirected back with that token. This keeps
   the error round-trip working on cached pages without ever putting submitted
   values in a URL.
5. On success, `Entry_Repository::create()` stores the entry and the visitor is
   redirected to the form's configured URL, or back with a success flag.

### Saving a form in the builder

The builder holds its state as a JavaScript array and serialises it into a hidden
input on submit. `Page_Builder::handle_save()` decodes that JSON and passes it
through `Field_Validator::sanitize_fields()`, which applies exactly the same
rules the server would apply to any other source: a type allowlist, reserved and
duplicate name rejection, length caps, and a 50-field limit. The client is never
trusted; it is only a convenient editor.

## Validation

`Field_Validator` owns what a field may be. It exposes two entry points:

- `parse_definition()` — the original line-based format, retained because it is
  covered by tests and remains a compact way to define a form in code.
- `sanitize_fields()` — the structured format the builder and templates use.

Both produce the same shape, so `Submission_Validator` has one code path.

Reserved names (`action`, `form_id`, `fmpf_nonce`, `fmpf_started_at`, `website`,
`submit`) are rejected because they collide with the request keys the submission
handler relies on.

## Capability model

One capability, `fmpf_manage_forms`, gates every screen. It is granted to
`administrator` on activation and on every schema upgrade. `Plugin::current_user_can()`
also accepts `manage_options`, so a site that manages capabilities externally
does not lock its own administrators out. The capability name is filterable
through `free_mpro_forms_capability`.

## Privacy posture

- Entries never leave the site. There is no telemetry and no submission relay.
- IP addresses are stored only as an HMAC-SHA256 digest salted with
  `wp_salt( 'nonce' )`, and only when the form enables it. The digest is not
  reversible and is not comparable across sites.
- The rate limiter stores hashed counters in transients, never raw addresses.
- WordPress personal-data export and erasure are both implemented. Erasure
  blanks the stored values, IP hash, user agent, and referer rather than deleting
  the row, so entry numbering stays stable.
- Retention is off by default; when set, a daily cron deletes older entries.

## Remote content

The Add-ons and Help screens read JSON from the project site. The contract is
documented in [REMOTE-CONTENT.md](REMOTE-CONTENT.md). Three properties matter:

- Responses are cached in a transient for a configurable number of hours.
- A failed or malformed response caches an empty result briefly, so a broken
  endpoint never slows down repeated page loads.
- Every screen falls back to a catalogue bundled in the plugin, and the whole
  mechanism can be switched off under Settings → Add-ons.

## Frontend assets

`forms.css` is a two-column CSS grid. Fields span both columns unless the form
opts into the two-column layout *and* the field is marked half width — so a
narrow theme never gets a cramped row it did not ask for. Logical properties
(`inset-inline-start`, `padding-inline`, `border-block-end`) mean the same
stylesheet serves both directions.

`builder.js` uses the native HTML5 drag-and-drop API. Cards are also reorderable
with keyboard-accessible move buttons, so drag is an enhancement rather than the
only way to arrange a form.

## Extension points

| Hook | Type | Fires / filters |
| --- | --- | --- |
| `free_mpro_forms_form_created` | action | After a form row is inserted |
| `free_mpro_forms_form_updated` | action | After a form row is updated |
| `free_mpro_forms_form_deleted` | action | After a form and its entries are removed |
| `free_mpro_forms_entry_created` | action | After an entry is stored |
| `free_mpro_forms_entry_deleted` | action | After an entry is removed |
| `free_mpro_forms_capability` | filter | Capability gating every screen |
| `free_mpro_forms_templates` | filter | Starter template definitions |
| `free_mpro_forms_track_views` | filter | Whether to count a form view |
| `free_mpro_forms_sms_providers` | filter | Selectable SMS gateways |
| `free_mpro_forms_rate_limit_enabled` | filter | Disable limiting per form |
| `free_mpro_forms_rate_limit_windows` | filter | Limit and window sizes |
| `free_mpro_forms_rate_limit_identity` | filter | Visitor identity source |

## Known limits in 0.2.0

- Entry search is a substring match over the JSON `data` column.
- Import is not implemented; see [DECISIONS.md](DECISIONS.md) for why.
- The REST namespace is reserved but registers no routes.
- SMS settings are stored but no message is sent.
- Conditional logic, multi-step forms, and file uploads are not implemented.
