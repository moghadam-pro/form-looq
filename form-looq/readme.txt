=== Form LOOQ ===
Contributors: moghadam
Tags: forms, contact form, form builder, rtl, submissions
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build WordPress forms with a drag-and-drop builder, keep every entry on your own site, and get first-class LTR and RTL layouts.

== Description ==

Form LOOQ (formerly MPRO Forms) is an open-source WordPress form plugin focused on straightforward form creation, local entry storage, responsive layouts, and LTR/RTL support. No license key, no external account, no mandatory cloud service.

**Building forms**

* Start from a blank form or one of four templates: simple contact, complete contact, product order, and event registration.
* Arrange fields with a drag-and-drop builder that needs no page reload.
* Ten field types: single-line text, paragraph text, email, phone, number, dropdown, radio buttons, checkbox, rating scale, and section breaks.
* Per-field label, machine name, placeholder, help text, required flag, and half or full width.
* Per-form layout, submit label, success message, redirect URL, honeypot, and IP-storage settings.

**Collecting entries**

* Entries are stored in the plugin's own database tables — never sent anywhere else.
* An inbox screen with a form selector, search, status filter, and the first three fields as columns.
* Read/unread status, single and bulk selection, bulk delete and bulk status changes.
* A single-entry view showing every answer plus submission time, hashed IP, referer, and user agent.
* A private admin note on every entry, for the reviewer's own context.

**Managing the plugin**

* A forms list showing status, ID, entry count, views, and conversion rate.
* Complete CSV and XML export of any form's entries.
* A system status report covering the plugin, database, WordPress, server, and timezone.
* A dashboard widget with form and entry totals.
* An Elementor widget so forms can be placed anywhere on an Elementor canvas.
* An optional add-on catalogue and help screen backed by the project site, off by default, with bundled fallbacks when it's off or unreachable.
* A bundled Persian (fa_IR) translation.

**Privacy and safety**

* Strict server-side validation for every field type, including verification against configured options.
* Field-specific error messages with values preserved after a validation error.
* Form-scoped rate limiting that stores hashed counters rather than raw IP addresses.
* WordPress personal-data export and erasure integration.
* Configurable entry retention with a daily cleanup job.
* Data is kept when the plugin is deleted unless deletion is explicitly enabled.

== External services ==

The Add-ons and Help screens can optionally load their content from the project website at https://formlooq.ir — specifically `https://formlooq.ir/addons.json` and `https://formlooq.ir/docs.json`.

This is **off by default**. No request is made until an administrator explicitly turns it on under Settings → Add-ons, where this same disclosure is shown before the setting can be enabled. When turned on, each request sends only the standard HTTP headers plus a user agent identifying the plugin version and the site's own URL, so the project can tell which releases are in use — no form content or entry data is transmitted. Responses are cached locally, and the plugin falls back to bundled content when the setting is off or the site cannot be reached.

Terms of use: https://formlooq.ir/terms
Privacy policy: https://formlooq.ir/privacy

== Installation ==

1. Upload the `form-looq` folder to the `/wp-content/plugins/` directory, or install the plugin ZIP through the WordPress Plugins screen.
2. Activate Form LOOQ.
3. Open Forms in the WordPress admin area — it sits directly below Media.
4. Create a form from a template or from scratch.
5. Copy its shortcode from the Embed tab into a page, post, or page builder.

== Frequently Asked Questions ==

= Are entries sent to an external service? =

No. Entries are stored in this site's own database tables and are never transmitted anywhere.

= Where exactly is my data stored? =

In two dedicated tables, `wp_looq_forms` and `wp_looq_entries`, using your site's own table prefix.

= Does deleting the plugin delete my forms? =

No. Because the plugin owns its tables, deleting and reinstalling the plugin — or replacing the folder during an update — leaves every form and entry intact. Deletion happens only if you explicitly enable it under Settings → General before removing the plugin.

= Does it support RTL websites? =

Yes. Both the admin screens and the frontend form use CSS logical properties, and the frontend direction follows the site unless a shortcode attribute overrides it.

= Does it include flood protection? =

Yes. Valid submission attempts are limited per form over a short window and an hourly window. Visitor identifiers are hashed with the WordPress salt before temporary counters are stored.

= Does it work with Elementor? =

Yes. When Elementor is active, a Form LOOQ widget appears in the editor with a form selector. Spacing and typography are governed by Elementor's own controls.

= Can I import entries? =

Not in this release. Export is complete; import is deliberately held back until the export format has been validated against real sites.

== Screenshots ==

1. The forms list with entry counts, views, and conversion rate.
2. The drag-and-drop form builder.
3. The inbox, showing entries for a selected form.
4. A single entry with its admin note.
5. The system status report.

== Changelog ==

= 0.4.1 =

* Fixed: the external redirect after a form submission used `wp_redirect()` directly to work around `wp_safe_redirect()`'s host allow-list. It now adds the admin-configured destination's own host to `allowed_redirect_hosts` for that one redirect and calls `wp_safe_redirect()`, so the safe-redirect check is honoured rather than bypassed.
* Fixed: the Settings → Add-ons disclosure and this readme both said the opt-in catalogue request sends "no personal data" — overstated, since the site's own URL is part of what's sent. The wording now only says what is and isn't sent, without characterising it either way.
* Fixed: a catalogue endpoint that returns its own homepage with a 200 status for a missing path (instead of a 404) was treated as a valid response. The fetch now also checks the response's Content-Type before treating the body as JSON.
* Changed: `Terms of use` and `Privacy policy` in the External services section now point to dedicated pages (`formlooq.ir/terms`, `formlooq.ir/privacy`) instead of both pointing at the homepage.
* Changed: the project's CI workflows (`.github/workflows/*.yml`) still referenced the pre-rename `mpro-forms` paths, plugin slug, database table and option names, and a hardcoded `0.2.0` version check left over from before the 0.2.0 storage rewrite — none of it matched the 0.4.0 codebase, so the whole quality-gate suite (Plugin Check, WPCS, real WordPress integration and browser tests) had been silently not running since the rename. All of it now points at `form-looq`, and the version check reads the actual packaged version instead of a hardcoded one.
* Changed: **Build or Draft Release** now requires the full quality-gate suite to pass in the same run before it will build or publish a release, instead of only building and uploading a ZIP on its own.
* Changed: narrowed the WordPress Plugin Check ignore list — `DONOTCACHEPAGE` (a cross-plugin caching convention that must stay unprefixed) now carries its own inline suppression comment instead of being excluded repo-wide.

= 0.4.0 =

* Renamed the plugin from MPRO Forms to Form LOOQ, and moved its site from `sayid.ir/mpro-forms` to its own domain, `formlooq.ir`. Every identifier was resynchronised: the slug and text domain are now `form-looq`, the shortcode is `[looq_form]`, hooks are `form_looq_*`, the capability is `looq_manage_forms`, and the tables are `{prefix}looq_forms` and `{prefix}looq_entries`. Installs from before the rename are migrated automatically, and `[mpro_form]` (and the older `[free_mpro_form]`) keep rendering as aliases, so existing content never needs editing.
* Added back the Add-ons and Help screens' catalogue fetch, removed in 0.3.3 over an undisclosed-request concern. It returns as an explicit opt-in this time: off by default, disclosed in Settings → Add-ons before it can be turned on, and pointed at the plugin's own new domain.

= 0.3.3 =

* Removed: the plugin no longer contacts the project website automatically. The Add-ons and Help screens previously fetched a catalogue from it on every visit, sending the site's own URL along in the request — that behaviour is gone, and the Help screen now ships its content with the plugin. The Add-ons screen is removed entirely, since nothing on it was ever installable.
* Removed: settings and screens that stored data but implemented nothing — the REST API and SMS/OTP tabs (including the unused SMS API key field), the License tab, the Automatic updates checkbox, and the Import panel on the Export screen.
* Fixed: a visitor could submit a value starting with `=`, `+`, `-`, or `@` that spreadsheet software reads as a formula when an admin opens an exported CSV (CSV/formula injection). Such values are now prefixed with an apostrophe before being written out.
* Fixed: exporting more than 100,000 entries silently stopped instead of exporting everything, even though the screen advertised a complete export. The cap is removed.
* Fixed: entries were timestamped in the site's local time but compared against a UTC cutoff for retention, and displayed by parsing that local time as if it were UTC and converting it again — on a non-UTC site both the retention window and the displayed submission time could be off by the site's UTC offset. Entries are now timestamped in UTC and converted to site time only for display.
* Fixed: the privacy exporter left out the entry's referring page, browser user agent, and admin note, and the eraser left the visitor's user account attached to an otherwise-anonymised entry. Both now cover the full set of personal data a submission can carry.
* Fixed: the daily retention cleanup job was scheduled on every install even when retention was left at its default of "keep forever," so it ran daily and did nothing. It is now scheduled only while a retention window is actually configured.
* Fixed: a right-to-left site whose language isn't Persian — Arabic, Hebrew, Urdu — saw the frontend "Select" and "Yes" defaults in Persian, because the fallback checked text direction instead of the site's language. It now always comes from the plugin's own translation.
* Fixed: an admin-configured post-submission redirect to an external domain was silently replaced with the home page, because the safe-redirect helper only allows configured hosts. The URL is validated instead and honoured as configured.
* Fixed: deleting a form removed its entries before the form row itself, so a failed delete could leave a form with no entries. The form row is now deleted first, and entries are only removed once that succeeds.
* Fixed: uninstalling with data deletion turned off skipped removing the plugin's capability from the administrator role, leaving it there permanently. Capability cleanup now always runs; only the destructive table and option removal stays behind the opt-in.
* Changed: the system status report no longer includes the server's document root or the absolute uploads-folder path, and the plain-text report now carries an explicit warning before it is copied, since it still contains other server details.
* Changed: `readme.txt` no longer describes an add-on catalogue fetch as an "External service" — there is no longer an automatic outbound request to describe.

= 0.3.2 =

* Fixed: the dashboard widget rendered with no styling because its stylesheet was never loaded on the WordPress Dashboard screen. Redesigned it at the same time: bordered stat cards, the unread count in the brand color, and an inline bar next to each conversion rate.

= 0.3.1 =

* Applied the plugin's brand color (#C61531) across admin and frontend styles, replacing the placeholder blue.
* The admin menu now shows the actual brand icon instead of a placeholder, and the header bar and welcome screen use the finished logo artwork.

= 0.3.0 =

* Renamed the plugin from Free MPRO Forms to MPRO Forms. "Free" was dropped because the WordPress.org slug is permanent and a paid Pro edition is planned.
* The slug and text domain are now `mpro-forms`, the shortcode is `[mpro_form]`, hooks are `mpro_forms_*`, and the tables are `{prefix}mpro_forms` and `{prefix}mpro_entries`.
* The plugin name and description are translated on the Plugins screen, so a Persian admin sees the Persian product name.
* Fixed: the frontend form forced a Vazir/Tahoma stack on right-to-left sites, overriding the theme. It now inherits the theme font, with `--mpro-font` available as an override.
* Fixed: admin screens now inherit the wp-admin typeface on form controls, so a site-wide admin font plugin also restyles this plugin.
* Installs from before the rename are migrated automatically: tables renamed, options copied, old cron event cleared, old capability removed.

= 0.2.0 =

* Moved forms and entries from custom post types to dedicated database tables, so plugin deletion and reinstallation no longer risks the data.
* Added a drag-and-drop form builder with ten field types, per-field width, placeholder, and help text.
* Added a two-step new-form flow with four starter templates.
* Added a dedicated admin menu below Media with forms list, new form, inbox, settings, import/export, add-ons, system status, and help.
* Added a header bar with the plugin logo, name, and version to every plugin screen.
* Added a forms list with status, ID, entry count, views, and conversion rate, plus edit, duplicate, settings, entries, and delete actions.
* Added an inbox with form selection, search, status filter, dynamic field columns, and bulk actions.
* Added a single-entry view with submission metadata and a private admin note.
* Added a tabbed settings screen: editor, add-ons, license, general, widgets, REST API, and SMS.
* Added complete CSV and XML export for any form.
* Added an add-on catalogue and a help screen backed by the project site, with bundled fallbacks.
* Added a system status report and a plain-text version for support requests.
* Added a dashboard widget and an Elementor widget.
* Added a first-run welcome screen.
* Added a bundled Persian (fa_IR) translation.
* Added no-conflict mode, which removes third-party assets from plugin screens.

= 0.1.0 =

* Initial development foundation.
* Added form fields and local submission storage.
* Added validation, accessible error states, privacy tools, retention settings, rate limiting, and automated checks.

== Upgrade Notice ==

= 0.4.1 =

Fixes the external-redirect safety bypass, an overstated privacy claim in the Add-ons disclosure, and CI workflows that had silently stopped running the quality-gate suite since the 0.4.0 rename.

= 0.4.0 =

The plugin is now Form LOOQ at formlooq.ir. The shortcode is now [looq_form] — [mpro_form] and [free_mpro_form] keep working as aliases. Forms, entries, and settings migrate automatically on the first request after updating. The Add-ons catalogue fetch is back, off by default; review Settings → Add-ons if you want it.

= 0.3.3 =

Removes the outbound Add-ons/Help catalogue fetch and every setting that stored data without a working feature behind it (REST, SMS, License, Automatic updates, Import). Fixes a CSV export formula-injection issue, a timezone bug in retention and displayed submission times, and an incomplete privacy export/eraser. If you had those settings configured, review Settings after updating — the removed ones are dropped from storage the next time settings are saved.

= 0.3.0 =

The plugin was renamed to MPRO Forms. The shortcode is now [mpro_form] — update any hardcoded `[free_mpro_form]` in theme templates. Forms, entries, and settings are migrated automatically on the first request after updating.

= 0.2.0 =

This release replaces the custom-post-type storage used in 0.1.0 with dedicated database tables. Forms and entries created under 0.1.0 are not carried over. Because 0.1.0 was a pre-release development version, no migration is provided.

= 0.1.0 =

Initial development release. Review the project documentation before using it on a production website.
