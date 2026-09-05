# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Nothing yet.

## [0.4.0] — 2026-09-05

Renamed the plugin from MPRO Forms to Form LOOQ and moved it to its own domain,
`formlooq.ir` (previously `sayid.ir/mpro-forms`).

### Changed

- **Every identifier resynchronised with the new name**, the same way the
  0.3.0 rename synchronised MPRO Forms's own identifiers:
  - Slug and text domain: `mpro-forms` → `form-looq`. The plugin directory and
    main file are renamed to match.
  - Namespace: `MPROForms` → `FormLooq`; constants `MPRO_FORMS_*` → `FORM_LOOQ_*`.
  - Shortcode: `[mpro_form]` → `[looq_form]`.
  - Hooks: `mpro_forms_*` → `form_looq_*`.
  - Database tables: `{prefix}mpro_forms` / `{prefix}mpro_entries` →
    `{prefix}looq_forms` / `{prefix}looq_entries`.
  - Options, transients, cron hook, and capability moved from the `mpro_`
    prefix to `form_looq_` (or `looq_` for the capability and request keys).
  - CSS classes and asset handles moved from `mpro-` to `looq-`.
  - The plugin name and description on the Plugins screen show "Form LOOQ" /
    "فرم لوک" for Persian admins.
- **`Plugin::HOME_URL`** moved from `https://sayid.ir/mpro-forms` to
  `https://formlooq.ir`.

### Added

- **The Add-ons and Help screens' remote catalogue fetch is back**, removed in
  0.3.3 because it ran by default and was not disclosed as sending the site's
  URL to the project's server. This time it ships **off by default**: nothing
  is requested until an admin explicitly enables it under Settings → Add-ons,
  where the exact data sent is disclosed before the setting can be turned on.
  The endpoints are the plugin's own new domain,
  `https://formlooq.ir/addons.json` and `https://formlooq.ir/docs.json`.

### Migration

Installs updated from 0.3.x are migrated automatically on the first request
after updating: the `mpro_` tables are renamed, `mpro_forms_*` options are
copied to their `form_looq_*` equivalents, the old cron event is cleared, and
the old `mpro_manage_forms` capability is removed from the administrator role
— the same two-step chain (`fmpf_` → `mpro_` → `looq_`) `migrate_legacy_names()`
already ran for the 0.3.0 rename, extended by one more generation. The
shortcode aliases `[mpro_form]` and `[free_mpro_form]` keep rendering
unchanged, so no existing page or post needs editing.

## [0.3.3] — 2026-09-05

Fixes from a pre-submission review against the WordPress.org plugin guidelines,
covering an outbound request the review flagged as undisclosed telemetry, a set
of settings that stored data behind no working feature, and several correctness
and privacy bugs.

### Removed

- **The outbound catalogue fetch.** The Add-ons and Help screens called
  `sayid.ir` on every visit, sending the site's own `home_url()` in the
  user agent so usage could be tracked — opt-out only, and undisclosed as such
  in the privacy policy text. `Remote_Content`, the `addons.json`/`docs.json`
  contract, and the Add-ons screen are gone; Help now ships its content with
  the plugin, and the only remaining outbound link is a plain click to the
  project site.
- **Settings and screens with no working feature behind them**: the REST API
  and SMS/OTP tabs (an unused SMS API key was being stored and autoloaded),
  the License tab, the Automatic updates checkbox, and the Import panel on
  the Export screen. Shipping controls for features that do not exist yet
  produced a misleading admin experience and, for the SMS key, an unnecessary
  place to leak a credential.

### Fixed

- **CSV/formula injection in entry exports.** A visitor-supplied value
  starting with `=`, `+`, `-`, `@`, a tab, or a carriage return is now
  prefixed with an apostrophe before `fputcsv()` writes it, so spreadsheet
  software renders it as text instead of evaluating it as a formula when an
  admin opens the export.
- **The "complete" export silently truncated at 100,000 entries.** The
  500-batch cap in `Exporter::batches()` is removed; export now runs until
  every entry is written, matching what the UI and readme already claimed.
- **Timezone handling for entries.** `created_at` was written in the site's
  local time but compared against a UTC cutoff during retention cleanup, and
  the admin screens re-parsed that local-time string as if it were UTC before
  converting it again for display — on a non-UTC site, both the retention
  window and the displayed submission time could be off by the site's offset.
  Entries are now timestamped in UTC (`current_time( 'mysql', true )`); the
  existing display and cutoff logic is already written to expect that and is
  unchanged.
- **Incomplete privacy export and erasure.** The exporter left out an entry's
  referring page, browser user agent, and admin note; the eraser cleared the
  submitted values but left the visitor's WordPress account still linked to
  the entry. Both now cover the full set of personal data an entry can carry,
  and the privacy policy text lists it explicitly instead of only the
  submitted field values.
- **The retention cleanup cron ran daily for every site, including ones that
  never configured a retention window.** `retention_days` defaults to 0
  ("keep forever"), so the job had nothing to do on a fresh install but was
  scheduled anyway. Activation now calls `Settings::sync_cron()`, which only
  schedules the job while a retention window is actually set. An update that
  replaces the plugin folder without a deactivate/reactivate cycle — the
  common path for a manual ZIP upload — also reconciles it automatically on
  the first request after updating, so a site already carrying the old
  unconditional schedule from 0.3.2 does not have to touch Settings to clear
  it.
- **RTL forms defaulted to Persian regardless of the site's actual language.**
  The "Select" and "Yes" fallback text checked `is_rtl()` and used a literal
  Persian string, so an Arabic, Hebrew, or Urdu site — also RTL — saw Persian
  wording. It now always comes from the plugin's own translation for the
  current locale.
- **An admin-configured external redirect URL was silently dropped.**
  `wp_safe_redirect()` only allows the current site's own host, so a
  post-submission redirect to another domain — set deliberately by a
  capability-holding form administrator, not by the visitor — fell back to
  the home page. The URL is now validated with `wp_http_validate_url()` and
  honoured as configured.
- **Form deletion was not safely ordered.** Entries were deleted before the
  form row; if the form delete then failed, the form was left with no
  entries. The form row is now deleted first, and entries are only removed
  once that succeeds.
- **Uninstall skipped capability cleanup when data deletion was off.** The
  routine returned early before removing the `mpro_manage_forms` capability
  from the administrator role, unless the site owner had also opted into
  deleting all data. Capability cleanup now always runs on uninstall; only
  dropping the tables and options stays behind the explicit opt-in. The table
  name is also passed through `$wpdb->prepare()`'s `%i` identifier
  placeholder instead of being interpolated directly.

### Changed

- **The system status report** no longer includes the server's document root
  or the absolute uploads-folder path, and the plain-text version now carries
  an explicit warning to review it before sharing it outside a support
  request, since it still includes other server and environment details.

## [0.3.2] — 2026-08-31

### Fixed

- **The dashboard widget rendered with no styling at all.** `Admin::enqueue()`
  only loads the plugin stylesheet on the plugin's own admin screens, and the
  widget lives on `index.php` (the WordPress Dashboard), which was never in
  that list — so every list, table, and heading fell back to bare browser
  defaults. `Dashboard_Widget` now enqueues the stylesheet itself, scoped to
  `index.php`.
- Redesigned the widget while fixing it: the four stats are now bordered cards
  rather than a plain list, with the unread count picked out in the brand
  color as the one number that calls for action; the recent-forms table has a
  proper heading, tabular-number alignment, and a small inline bar next to
  each conversion rate instead of a bare percentage.

## [0.3.1] — 2026-08-31

### Changed

- **Brand color.** `--mpro-primary` / `--mpro-accent` in both stylesheets, and
  the README version badge, move from placeholder blues to the plugin's own
  color, `#C61531`, with a darker shade of it for hover and focus states.
- **Menu icon.** The WordPress admin sidebar now shows the actual brand mark
  (a hexagon enclosing a stylised "P") loaded from its own SVG file, instead
  of a monochrome bar chart built inline in PHP. WordPress renders a custom
  menu icon at reduced opacity and brings it to full opacity on hover or when
  the menu is current — it does not force a silhouette — so the colored icon
  renders as designed rather than as a placeholder.
- **Header bar and welcome screen logo.** Both now use the supplied app-icon-style
  JPG instead of the hand-drawn SVG, which is removed as nothing references it
  anymore.

## [0.3.0] — 2026-08-31

Renamed the plugin from Free MPRO Forms to MPRO Forms and synchronised every
identifier with the new name. "Free" was dropped deliberately: the WordPress.org
slug is permanent once approved, and a paid Pro edition is planned, so shipping
a free core called "Free" would leave the wrong name in the URL forever.

### Changed

- **Slug and text domain** are now `mpro-forms`. The plugin directory and main
  file were renamed to match.
- **Namespace** `FreeMPROForms` is now `MPROForms`; constants `FREE_MPRO_FORMS_*`
  are now `MPRO_FORMS_*`.
- **Shortcode** `[free_mpro_form]` is now `[mpro_form]`.
- **Hooks** `free_mpro_forms_*` are now `mpro_forms_*`.
- **Database tables** are `{prefix}mpro_forms` and `{prefix}mpro_entries`.
- **Options, transients, cron hook, and capability** moved from the `fmpf_`
  prefix to `mpro_forms_` (or `mpro_` for the capability and request keys).
- **CSS classes and asset handles** moved from `fmpf-` to `mpro-`.
- The plugin name and description on the Plugins screen are now translated, so a
  Persian admin sees "فرم‌ساز ام‌پرو".
- The admin header bar and dashboard widget show the full product name; the menu
  entry stays "فرم‌ها".

### Fixed

- **The frontend form no longer forces its own typeface.** An RTL rule hardcoded
  a Vazir/Tahoma stack, overriding the theme on every right-to-left site. The
  form now inherits the surrounding font, and `--mpro-font` overrides it when a
  site actually wants a different one.
- Admin screens re-establish font inheritance on inputs, selects, textareas, and
  buttons, which do not inherit it by default. A site-wide admin font plugin now
  restyles the plugin's screens along with the rest of wp-admin.

### Migration

Installs that predate the rename are migrated automatically on the first request
after updating: the `fmpf_` tables are renamed, options are copied to their new
keys, the old cron event is cleared, and the old capability is removed. Nothing
was ever publicly released under the old name, so this routine can be dropped
once no install predates 0.3.0.

## [0.2.0] — 2026-08-31

The plugin was rebuilt around dedicated database tables and gained its full admin
experience. See [DECISIONS.md](https://github.com/moghadam-pro/form-looq/blob/docs/docs/DECISIONS.md)
on the `docs` branch for the reasoning behind the larger changes.

### Changed

- **Storage moved from custom post types to dedicated tables.** Forms and entries
  now live in `{prefix}mpro_forms` and `{prefix}mpro_entries`. Because the plugin
  owns these tables and never drops them on its own, deleting and reinstalling
  the plugin — or replacing the folder during an update — no longer risks the
  data. This is a breaking change; see *Removed* below.
- `uninstall.php` drops the plugin tables only when data deletion is explicitly
  enabled, and now also removes the plugin capability and cached remote content.
- Settings consolidated into a single `mpro_forms_settings` option instead of scattered
  keys.
- The frontend stylesheet now keeps forms in a single column unless the form opts
  into the two-column layout, so a narrow theme is never given a cramped row.

### Added

**Admin**

- A top-level menu at position 11, directly below Media, using the plugin logo as
  its icon and showing a Persian label on Persian admins.
- Sub-pages: forms list, new form, inbox, settings, import/export, add-ons,
  system status, and help.
- A header bar with the logo, plugin name, and version on every plugin screen.
- A forms list built on `WP_List_Table` with title, status, ID, entry count,
  views, and conversion rate, plus edit, entries, settings, duplicate, and delete
  row actions and bulk delete.
- A two-step new-form flow: choose one of four templates or a blank form, then
  name the form and open the builder.
- A drag-and-drop form builder with ten field types and per-field machine name,
  placeholder, help text, required flag, and half or full width. Written in plain
  JavaScript with no build step.
- Four starter templates: simple contact, complete contact, product order, and
  event registration.
- An inbox with a form selector, search, status filter, the first three form
  fields as dynamic columns, and bulk delete and read/unread actions.
- A single-entry view showing every answer alongside submission time, hashed IP,
  referer, and user agent.
- A private admin note on every entry.
- A tabbed settings screen: editor, add-ons, license, general, widgets, REST API,
  and SMS.
- Complete CSV and XML export of any form's entries, streamed in batches. CSV
  carries a UTF-8 BOM so Excel opens Persian text correctly.
- A system status report covering the plugin, database, WordPress, server, and
  timezone, with a copyable plain-text version.
- An add-on catalogue and a help screen backed by JSON from the project site,
  with transient caching, a bundled fallback, and a setting to disable the
  requests entirely.
- A first-run welcome screen mirroring the public landing page.
- No-conflict mode, which dequeues third-party assets on plugin screens.

**Elsewhere**

- A dashboard widget with form and entry totals and the top forms by entry count.
- An Elementor widget with a form selector and a direction control, registered
  only when Elementor is active.
- A bundled Persian (fa_IR) translation covering 262 strings.
- A single `mpro_manage_forms` capability, granted to administrators on
  activation and on schema upgrades.
- `Field_Validator::sanitize_fields()` for structured field definitions, applying
  the same type allowlist and reserved and duplicate name rules as the existing
  line-based parser.
- Actions `mpro_forms_form_created`, `mpro_forms_form_updated`,
  `mpro_forms_form_deleted`, `mpro_forms_entry_created`, and
  `mpro_forms_entry_deleted`.
- Filters `mpro_forms_capability`, `mpro_forms_templates`, and
  `mpro_forms_track_views`.
- Test coverage for structured field sanitization and for validating a submission
  against builder-produced fields.

### Removed

- The `mpro_form` and `mpro_submission` custom post types and their meta.
- Forms and entries created under 0.1.0 are **not** carried over. No migration is
  provided because 0.1.0 was never publicly released.

### Fixed

- Settings checkboxes now persist when unticked. Each is paired with a hidden
  input, since an unchecked box is not posted at all.

## [0.1.0] — 2026-08-30

Initial development foundation, never publicly released.

### Added

- Form and submission management through custom post types.
- Text, email, telephone, number, textarea, select, radio, scale, checkbox, and
  section fields.
- Server-side validation with configured-option verification.
- Field-specific error messages with values preserved after a validation error.
- Local submission storage.
- Privacy-conscious, form-scoped submission rate limiting using hashed counters.
- WordPress personal-data export and erasure integration.
- Configurable submission retention.
- Opt-in data deletion during uninstall.
- Shortcode embedding, responsive layouts, and automatic LTR/RTL direction.

[Unreleased]: https://github.com/moghadam-pro/form-looq/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/moghadam-pro/form-looq/releases/tag/v0.4.0
[0.3.3]: https://github.com/moghadam-pro/form-looq/releases/tag/v0.3.3
[0.3.2]: https://github.com/moghadam-pro/form-looq/releases/tag/v0.3.2
[0.3.1]: https://github.com/moghadam-pro/form-looq/releases/tag/v0.3.1
[0.3.0]: https://github.com/moghadam-pro/form-looq/releases/tag/v0.3.0
[0.2.0]: https://github.com/moghadam-pro/form-looq/releases/tag/v0.2.0
[0.1.0]: https://github.com/moghadam-pro/form-looq/releases/tag/v0.1.0
