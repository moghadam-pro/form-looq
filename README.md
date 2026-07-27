# Free MPRO Forms

> An open-source, privacy-first WordPress form plugin with responsive layouts and first-class RTL/LTR support.

**Project status:** Pre-release / Phase 0 audit

Free MPRO Forms is being built for people who need practical WordPress forms without a license key, external account, or mandatory cloud service. The first public release will focus on a small, dependable core: form creation, local submission storage, responsive rendering, multilingual content, and clear extension points.

The project is independent and is not affiliated with Gravity Forms, Rocketgenius, or any other commercial form product. It does not currently claim feature parity with mature form builders.

## Why this project exists

- **Free by default** — no paid license is required for the core plugin.
- **Local by default** — submissions remain inside the site’s WordPress database unless the site owner explicitly adds an integration.
- **Bilingual by design** — LTR and RTL layouts are treated as core product requirements.
- **Open product development** — roadmap, release notes, testing status, security guidance, and verified technical documentation are public.

## Current audited prototype

The uploaded `Native Forms 1.0.0` prototype currently includes:

- Form and submission management screens in WordPress Admin.
- Text, email, telephone, number, textarea, select, radio, scale, checkbox, and section fields.
- Shortcode embedding for the block editor, Elementor, and other shortcode-compatible builders.
- Local submission storage without mandatory external services.
- Unicode content support for Persian, Arabic, and other languages.
- Responsive two-column and single-column layouts.
- Automatic LTR/RTL frontend direction.
- Nonce checks, basic honeypot protection, sanitization, and escaped output.
- A developer action fired after a submission is stored.

The prototype is **not yet the public Free MPRO Forms package**. It must first be renamed, hardened, tested, documented, and rebuilt under the final plugin slug.

## Planned Phase 1 — Free Core

- Final `free-mpro-forms` package, namespace, prefixes, and text domain.
- Dependable form and submission management.
- Strict server-side validation for every supported field type.
- Responsive, accessible frontend markup.
- First-class LTR and RTL layouts using CSS logical properties.
- English interface and Persian translation foundation.
- Local submission storage with privacy documentation.
- Submission retention, export, and deletion foundations.
- Verified GitHub Release packages.
- WordPress.org submission readiness and release workflow.
- Public documentation, changelog, security policy, and release checksums.

## Planned Phase 2 — Visual Builder & Workflows

- Visual field builder with drag, reorder, and live preview.
- Multi-step forms.
- Conditional field visibility.
- Email notifications and confirmations.
- Import/export tools and reusable templates.
- Stronger anti-spam controls and optional integrations.
- CSV export and improved submission workflows.
- Developer API, filters, actions, and webhook foundations.

The roadmap is directional. Features are only considered released after they are implemented, tested, and documented.

## Product websites and demos

| Language | Landing page | Demo |
|---|---|---|
| English | https://moghadam.pro/free-mpro-forms | https://moghadam.pro/free-mpro-forms/demo |
| Persian | https://sayid.ir/free-mpro-forms | https://sayid.ir/free-mpro-forms/demo |

The English and Persian websites are maintained separately and link to each other.

## Installation

There is no stable public package yet. When the first verified release is available:

1. Download the verified plugin ZIP from GitHub Releases or install it from WordPress.org when the directory listing becomes available.
2. Verify the published SHA-256 checksum for GitHub packages.
3. Upload and activate **Free MPRO Forms**.
4. Create a form, publish it, and embed its shortcode on a page.

Do not use GitHub’s automatically generated source archive as the installable plugin unless the release notes explicitly say it is supported.

## Distribution model

GitHub remains the source of truth for development, code review, issues, and tagged source releases.

The WordPress.org Plugin Directory is planned as an additional official distribution channel. If accepted, WordPress.org SVN will be used only for approved release packages, plugin assets, and stable tags. Development will not move to SVN.

See [WordPress.org publishing](docs/WORDPRESS-ORG.md) for prerequisites, review rules, costs, and the planned release flow.

## Privacy

The current prototype stores submissions in the local WordPress database and does not require an external account. The public release will document what is collected, where it is stored, who can access it, export/erasure behavior, retention, uninstall behavior, and all optional external integrations.

No telemetry, tracking, remote assets, or external submission processing will be introduced silently.

## Security

Please do not report vulnerabilities in public issues. Follow [SECURITY.md](SECURITY.md) for responsible disclosure.

The public release process will include syntax checks, WordPress Coding Standards, Plugin Check, security review, accessibility review, and clean-install testing.

## Requirements

The audited prototype currently declares:

- WordPress 6.5 or newer.
- PHP 8.1 or newer.

The final compatibility range will be confirmed before the first stable release.

## Documentation

- [Roadmap](docs/ROADMAP.md)
- [WordPress.org publishing](docs/WORDPRESS-ORG.md)
- [Contributing](CONTRIBUTING.md)
- [Changelog](CHANGELOG.md)

## License

Free MPRO Forms is licensed under the GNU General Public License v2.0 or later. See [LICENSE](LICENSE).

## Author

Created and maintained by [Sayid Moghadam](https://moghadam.pro/).
