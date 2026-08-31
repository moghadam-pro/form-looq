=== MPRO Forms ===
Contributors: moghadam
Tags: forms, contact form, form builder, rtl, submissions
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build WordPress forms with a drag-and-drop builder, keep every entry on your own site, and get first-class LTR and RTL layouts.

== Description ==

MPRO Forms is an open-source WordPress form plugin focused on straightforward form creation, local entry storage, responsive layouts, and LTR/RTL support. No license key, no external account, no mandatory cloud service.

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
* A bundled Persian (fa_IR) translation.

**Privacy and safety**

* Strict server-side validation for every field type, including verification against configured options.
* Field-specific error messages with values preserved after a validation error.
* Form-scoped rate limiting that stores hashed counters rather than raw IP addresses.
* WordPress personal-data export and erasure integration.
* Configurable entry retention with a daily cleanup job.
* Data is kept when the plugin is deleted unless deletion is explicitly enabled.

== External services ==

The Add-ons and Help screens can load their content from the project website at https://sayid.ir/mpro-forms — specifically `https://sayid.ir/mpro-forms/addons.json` and `https://sayid.ir/mpro-forms/docs.json`.

These requests send only the standard HTTP headers plus a user agent identifying the plugin version and the site URL, so the project can tell which releases are in use. No form content, entry data, or personal data is transmitted. Responses are cached locally and the plugin falls back to a bundled catalogue when the site cannot be reached.

This behaviour can be turned off entirely under Settings → Add-ons, after which the plugin makes no external requests at all.

Site terms and privacy policy: https://sayid.ir/mpro-forms

== Installation ==

1. Upload the `mpro-forms` folder to the `/wp-content/plugins/` directory, or install the plugin ZIP through the WordPress Plugins screen.
2. Activate MPRO Forms.
3. Open Forms in the WordPress admin area — it sits directly below Media.
4. Create a form from a template or from scratch.
5. Copy its shortcode from the Embed tab into a page, post, or page builder.

== Frequently Asked Questions ==

= Are entries sent to an external service? =

No. Entries are stored in this site's own database tables and are never transmitted anywhere.

= Where exactly is my data stored? =

In two dedicated tables, `wp_mpro_forms` and `wp_mpro_entries`, using your site's own table prefix.

= Does deleting the plugin delete my forms? =

No. Because the plugin owns its tables, deleting and reinstalling the plugin — or replacing the folder during an update — leaves every form and entry intact. Deletion happens only if you explicitly enable it under Settings → General before removing the plugin.

= Does it support RTL websites? =

Yes. Both the admin screens and the frontend form use CSS logical properties, and the frontend direction follows the site unless a shortcode attribute overrides it.

= Does it include flood protection? =

Yes. Valid submission attempts are limited per form over a short window and an hourly window. Visitor identifiers are hashed with the WordPress salt before temporary counters are stored.

= Does it work with Elementor? =

Yes. When Elementor is active, a MPRO Forms widget appears in the editor with a form selector. Spacing and typography are governed by Elementor's own controls.

= Can I import entries? =

Not in this release. Export is complete; import is deliberately held back until the export format has been validated against real sites.

== Screenshots ==

1. The forms list with entry counts, views, and conversion rate.
2. The drag-and-drop form builder.
3. The inbox, showing entries for a selected form.
4. A single entry with its admin note.
5. The system status report.

== Changelog ==

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

= 0.3.0 =

The plugin was renamed to MPRO Forms. The shortcode is now [mpro_form] — update any hardcoded `[free_mpro_form]` in theme templates. Forms, entries, and settings are migrated automatically on the first request after updating.

= 0.2.0 =

This release replaces the custom-post-type storage used in 0.1.0 with dedicated database tables. Forms and entries created under 0.1.0 are not carried over. Because 0.1.0 was a pre-release development version, no migration is provided.

= 0.1.0 =

Initial development release. Review the project documentation before using it on a production website.
