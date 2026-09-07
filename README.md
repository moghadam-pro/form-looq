# Form LOOQ

> A free, privacy-first WordPress form plugin with a drag-and-drop builder, local
> entry storage, and first-class RTL/LTR support.

[![Version](https://img.shields.io/badge/version-0.4.1-C61531)](https://github.com/moghadam-pro/form-looq/releases)
[![WordPress](https://img.shields.io/badge/wordpress-6.5%2B-21759b)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/php-8.1%2B-777bb4)](https://www.php.net)
[![License](https://img.shields.io/badge/license-GPLv2%2B-green)](LICENSE)

Form LOOQ (formerly MPRO Forms) is for people who need practical WordPress forms without a
license key, an external account, or a mandatory cloud service. Every entry stays
in your own database, the whole plugin ships without a build step, and nothing is
gated behind a paywall.

**Project status:** pre-release. Version 0.4.1 is the current public development
release and is under active testing ahead of the first stable release.

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

1. Download `form-looq.zip` from the
   [releases page](https://github.com/moghadam-pro/form-looq/releases).
2. Upload it through **Plugins → Add New → Upload Plugin**.
3. Activate it.
4. Open **Forms** — it sits directly below Media in the admin menu.

### From source

```bash
git clone https://github.com/moghadam-pro/form-looq.git
cp -r form-looq/form-looq /path/to/wp-content/plugins/
```

**Requirements:** WordPress 6.5+, PHP 8.1+.

---

## Usage

Create a form, then copy its shortcode from the **Embed** tab:

```
[looq_form id="12"]
```

In a theme template:

```php
<?php echo do_shortcode( '[looq_form id="12"]' ); ?>
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
| `{prefix}looq_forms` | Form definitions, settings, view and entry counts |
| `{prefix}looq_entries` | Submitted values, status, admin note, and metadata |

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

The **Add-ons** screen can optionally load its catalogue from
`https://formlooq.ir`. This is **off by default** — no request is made until an
admin explicitly turns it on under **Settings → Add-ons**, where the exact data
sent (standard HTTP headers plus a user agent identifying the plugin version and
the site's own URL — no form content or entry data) is disclosed before the
setting is enabled. Responses are cached, and a bundled fallback catalogue is
shown when the setting is off or the site cannot be reached.

---

## For developers

### Actions

| Hook | Arguments |
| --- | --- |
| `form_looq_form_created` | `int $form_id` |
| `form_looq_form_updated` | `int $form_id` |
| `form_looq_form_deleted` | `int $form_id` |
| `form_looq_entry_created` | `int $entry_id, int $form_id` |
| `form_looq_entry_deleted` | `int $entry_id` |

```php
add_action(
	'form_looq_entry_created',
	function ( $entry_id, $form_id ) {
		$entry = \FormLooq\Entry_Repository::get( $entry_id );
		// your logic here
	},
	10,
	2
);
```

### Filters

| Hook | Filters |
| --- | --- |
| `form_looq_capability` | The capability gating every plugin screen |
| `form_looq_templates` | Starter template definitions |
| `form_looq_track_views` | Whether a form view is counted |
| `form_looq_rate_limit_enabled` | Whether rate limiting applies to a form |
| `form_looq_rate_limit_windows` | Limit and window sizes |
| `form_looq_rate_limit_identity` | The visitor identity used for limiting |

### Storing an entry programmatically

```php
form_looq_store_submission(
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

Development documentation and the architecture reference live in
[`docs/`](docs/README.md) on `main`, alongside the source they describe.

---

## Security

Report vulnerabilities privately rather than in a public issue. See
[SECURITY.md](SECURITY.md).

---

## License

GPLv2 or later. See [LICENSE](LICENSE).
