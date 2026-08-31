# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Nothing yet.

## [0.2.0] — 2026-08-31

The plugin was rebuilt around dedicated database tables and gained its full admin
experience. See [DECISIONS.md](https://github.com/moghadam-pro/free-forms-wp-plugin/blob/docs/docs/DECISIONS.md)
on the `docs` branch for the reasoning behind the larger changes.

### Changed

- **Storage moved from custom post types to dedicated tables.** Forms and entries
  now live in `{prefix}fmpf_forms` and `{prefix}fmpf_entries`. Because the plugin
  owns these tables and never drops them on its own, deleting and reinstalling
  the plugin — or replacing the folder during an update — no longer risks the
  data. This is a breaking change; see *Removed* below.
- `uninstall.php` drops the plugin tables only when data deletion is explicitly
  enabled, and now also removes the plugin capability and cached remote content.
- Settings consolidated into a single `fmpf_settings` option instead of scattered
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
- A single `fmpf_manage_forms` capability, granted to administrators on
  activation and on schema upgrades.
- `Field_Validator::sanitize_fields()` for structured field definitions, applying
  the same type allowlist and reserved and duplicate name rules as the existing
  line-based parser.
- Actions `free_mpro_forms_form_created`, `free_mpro_forms_form_updated`,
  `free_mpro_forms_form_deleted`, `free_mpro_forms_entry_created`, and
  `free_mpro_forms_entry_deleted`.
- Filters `free_mpro_forms_capability`, `free_mpro_forms_templates`, and
  `free_mpro_forms_track_views`.
- Test coverage for structured field sanitization and for validating a submission
  against builder-produced fields.

### Removed

- The `fmpf_form` and `fmpf_submission` custom post types and their meta.
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

[Unreleased]: https://github.com/moghadam-pro/free-forms-wp-plugin/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/moghadam-pro/free-forms-wp-plugin/releases/tag/v0.2.0
[0.1.0]: https://github.com/moghadam-pro/free-forms-wp-plugin/releases/tag/v0.1.0
