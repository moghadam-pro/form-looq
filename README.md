# MPRO Forms

> A free, privacy-first WordPress form plugin with a drag-and-drop builder, local
> entry storage, and first-class RTL/LTR support.

[![Version](https://img.shields.io/badge/version-0.3.0-2271b1)](https://github.com/moghadam-pro/mpro-forms-wp-plugin/releases)
[![WordPress](https://img.shields.io/badge/wordpress-6.5%2B-21759b)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/php-8.1%2B-777bb4)](https://www.php.net)
[![License](https://img.shields.io/badge/license-GPLv2%2B-green)](LICENSE)

MPRO Forms is for people who need practical WordPress forms without a
license key, an external account, or a mandatory cloud service. Every entry stays
in your own database, the whole plugin ships without a build step, and nothing is
gated behind a paywall.

**Project status:** pre-release. 0.2.0 is feature-complete for its scope and
under active testing ahead of the first public stable release.

The project is independent and is not affiliated with Gravity Forms,
Rocketgenius, or any other commercial form product.

---

## Why this exists

- **Free by default** — no paid license for the core plugin, on any number of sites.
- **Local by default** — entries live in your database and are never transmitted.
- **Bilingual by design** — LTR and RTL are both first-class, not an afterthought.
- **Light by design** — no framework, no bundler, and assets load only on pages
  that actually render a form.
- **Open** — roadmap, decisions, testing status, and security guidance are public.

---

## Features

### Building forms

- Drag-and-drop builder with ten field types: single-line text, paragraph text,
  email, phone, number, dropdown, radio buttons, checkbox, rating scale, and
  section breaks.
- Four starter templates plus a blank form: simple contact, complete contact,
  product order, and event registration.
- Per field: label, machine name, placeholder, help text, required flag, and half
  or full width.
- Per form: status, layout, submit label, success message, redirect URL,
  honeypot, and whether to store a hashed visitor IP.
- Embed code always one click away as a shortcode, a block, or PHP.

### Collecting entries

- An inbox with a form selector, search, status filter, and the first three form
  fields as columns.
- Read/unread status, single and bulk selection, bulk delete and bulk status changes.
- A single-entry view with every answer plus submission time, hashed IP, referer,
  and user agent.
- A private admin note on every entry.

### Managing the plugin

- A forms list showing status, ID, entry count, views, and conversion rate.
- Complete CSV and XML export of any form's entries.
- A system status report, with a plain-text version for support requests.
- A dashboard widget with form and entry totals.
- An Elementor widget with a form selector.
- A bundled Persian (fa_IR) translation.

### Privacy and safety

- Strict server-side validation for every field type, including verification
  against configured options.
- Field-specific error messages, with values preserved after a validation error.
- Form-scoped rate limiting using hashed counters, never raw IP addresses.
- WordPress personal-data export and erasure integration.
- Configurable entry retention with a daily cleanup job.

---

## Installation

### From a release

1. Download `mpro-forms.zip` from the
   [releases page](https://github.com/moghadam-pro/mpro-forms-wp-plugin/releases).
2. Upload it through **Plugins → Add New → Upload Plugin**.
3. Activate it.
4. Open **Forms** — it sits directly below Media in the admin menu.

### From source

```bash
git clone https://github.com/moghadam-pro/mpro-forms-wp-plugin.git
cp -r mpro-forms-wp-plugin/mpro-forms /path/to/wp-content/plugins/
```

**Requirements:** WordPress 6.5+, PHP 8.1+.

---

## Usage

Create a form, then copy its shortcode from the **Embed** tab:

```
[mpro_form id="12"]
```

In a theme template:

```php
<?php echo do_shortcode( '[mpro_form id="12"]' ); ?>
```

The shortcode accepts a few optional attributes:

| Attribute | Default | Purpose |
| --- | --- | --- |
| `id` | — | Form ID. Required. |
| `dir` | `auto` | `rtl`, `ltr`, or `auto` to follow the site. |
| `button` | Form setting | Override the submit button label. |
| `sent` | Form setting | Override the success message. |
| `error` | Built-in | Override the validation summary heading. |

---

## Where your data lives

Forms and entries are stored in two tables the plugin owns:

| Table | Contents |
| --- | --- |
| `{prefix}mpro_forms` | Form definitions, settings, view and entry counts |
| `{prefix}mpro_entries` | Submitted values, status, admin note, and metadata |

Because the plugin owns these tables and never drops them on its own, **deleting
and reinstalling the plugin — or replacing the folder during an update — leaves
your forms and entries intact.** Data is removed only if you tick *Delete all
forms, entries, and plugin tables* under **Settings → General** before removing
the plugin.

---

## Privacy

- Entries never leave your site. There is no telemetry and no submission relay.
- IP addresses are stored only as a salted HMAC-SHA256 digest, and only when the
  form enables it. The digest is not reversible.
- The rate limiter stores hashed counters in transients, never raw addresses.
- WordPress export and erasure requests are both handled.

### External requests

The **Add-ons** and **Help** screens can load their content from
`https://sayid.ir/mpro-forms`. These requests send only standard HTTP
headers plus a user agent identifying the plugin version and site URL — no form
content, entry data, or personal data. Responses are cached, a bundled fallback
is used when the site is unreachable, and the whole mechanism can be switched off
under **Settings → Add-ons**, after which the plugin makes no external requests
at all.

---

## For developers

### Actions

| Hook | Arguments |
| --- | --- |
| `mpro_forms_form_created` | `int $form_id` |
| `mpro_forms_form_updated` | `int $form_id` |
| `mpro_forms_form_deleted` | `int $form_id` |
| `mpro_forms_entry_created` | `int $entry_id, int $form_id` |
| `mpro_forms_entry_deleted` | `int $entry_id` |

```php
add_action(
	'mpro_forms_entry_created',
	function ( $entry_id, $form_id ) {
		$entry = \MPROForms\Entry_Repository::get( $entry_id );
		// your logic here
	},
	10,
	2
);
```

### Filters

| Hook | Filters |
| --- | --- |
| `mpro_forms_capability` | The capability gating every plugin screen |
| `mpro_forms_templates` | Starter template definitions |
| `mpro_forms_track_views` | Whether a form view is counted |
| `mpro_forms_sms_providers` | Selectable SMS gateways |
| `mpro_forms_rate_limit_enabled` | Whether rate limiting applies to a form |
| `mpro_forms_rate_limit_windows` | Limit and window sizes |
| `mpro_forms_rate_limit_identity` | The visitor identity used for limiting |

### Storing an entry programmatically

```php
mpro_forms_store_submission(
	12,
	array(
		'full_name'     => 'Sayid',
		'email_address' => 'sayid@example.com',
		'message'       => 'Hello.',
	)
);
```

---

## Roadmap

Directional, not a commitment. A feature is released only once it is implemented,
tested, and documented.

**Next**

- Entry import, once the export format has been validated against real sites.
- Email notifications and confirmations.
- Conditional field visibility and multi-step forms.
- Working add-on activation.

**Later**

- File upload fields.
- REST API routes on the reserved namespace.
- SMS delivery and phone verification through the configured gateway.
- Per-role capabilities.

---

## Contributing

Issues and pull requests are welcome. Before opening a PR:

```bash
php tests/validator-test.php
composer install && vendor/bin/phpcs
```

Development documentation, the architecture reference, and the decision log live
on the [`docs` branch](https://github.com/moghadam-pro/mpro-forms-wp-plugin/tree/docs).

---

## Security

Report vulnerabilities privately rather than in a public issue. See
[SECURITY.md](SECURITY.md).

---

## License

GPLv2 or later. See [LICENSE](LICENSE).
