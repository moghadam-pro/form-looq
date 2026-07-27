=== Free MPRO Forms ===
Contributors: moghadam
Tags: forms, contact form, rtl, privacy, submissions
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create lightweight WordPress forms with local submissions and first-class LTR and RTL support.

== Description ==

Free MPRO Forms is an open-source WordPress form plugin focused on straightforward form creation, local submission storage, responsive layouts, and LTR/RTL support.

The current development version includes:

* Text, email, telephone, number, textarea, select, radio, scale, checkbox, and section fields.
* Server-side validation and configured-option verification.
* Field-specific error messages and value preservation after validation errors.
* Local submission storage inside WordPress.
* WordPress personal-data export and erasure integration.
* Configurable submission retention.
* Explicit opt-in data deletion during uninstall.

This plugin is under active development and has not reached its first stable release.

== Installation ==

1. Upload the `free-mpro-forms` folder to the `/wp-content/plugins/` directory, or install the plugin ZIP through the WordPress Plugins screen.
2. Activate Free MPRO Forms.
3. Open Forms in the WordPress admin area.
4. Create and publish a form.
5. Copy its shortcode into a page, post, or shortcode-compatible page builder.

== Frequently Asked Questions ==

= Are submissions sent to an external service? =

No. The current version stores submissions locally in the WordPress database.

= Does it support RTL websites? =

Yes. The frontend form layout supports both LTR and RTL directions.

= Does uninstalling delete my forms and submissions? =

Not by default. Data deletion during uninstall must be explicitly enabled in the plugin settings.

== Screenshots ==

Screenshots will be added before the first stable release.

== Changelog ==

= 0.1.0 =

* Initial development foundation.
* Added form fields and local submission storage.
* Added validation, accessible error states, privacy tools, retention settings, and automated checks.

== Upgrade Notice ==

= 0.1.0 =

Initial development release. Review the project documentation before using it on a production website.
