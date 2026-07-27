# Free MPRO Forms

> An open-source, privacy-first WordPress form plugin with responsive layouts and first-class RTL/LTR support.

**Project status:** Pre-release / Phase 0 audit

Free MPRO Forms is being built for people who need practical WordPress forms without a license key, external account, or mandatory cloud service. The first public release will focus on a small, dependable core: form creation, local submission storage, responsive rendering, multilingual content, and clear extension points.

The project is independent and is not affiliated with Gravity Forms, Rocketgenius, or any other commercial form product. It does not currently claim feature parity with mature form builders.

## Why this project exists

Many WordPress sites need a reliable contact, application, survey, or intake form without introducing another subscription or sending submission data to a third-party platform. Free MPRO Forms follows four principles:

- **Free by default** — no paid license is required for the core plugin.
- **Local by default** — submissions remain inside the site’s WordPress database unless the site owner explicitly adds an integration.
- **Bilingual by design** — LTR and RTL layouts are treated as core product requirements.
- **Open by process** — roadmap, decisions, release notes, testing status, and AI-assisted work logs are documented publicly.

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

Phase 1 is the first stable public foundation:

- Final `free-mpro-forms` package, namespace, prefixes, and text domain.
- Dependable form and submission management.
- Strict server-side validation for every supported field type.
- Responsive, accessible frontend markup.
- First-class LTR and RTL layouts using CSS logical properties.
- English interface and Persian translation foundation.
- Local submission storage with privacy documentation.
- Submission retention, export, and deletion foundations.
- Manual installation and updates through verified GitHub Releases.
- Public documentation, changelog, security policy, and release checksums.

## Planned Phase 2 — Visual Builder & Workflows

Phase 2 expands the core without changing the free-first direction:

- Visual field builder with drag, reorder, and live preview.
- Multi-step forms.
- Conditional field visibility.
- Email notifications and confirmations.
- Import/export tools and reusable templates.
- Stronger anti-spam controls and optional integrations.
- CSV export and improved submission workflows.
- Developer API, filters, actions, and webhook foundations.

The roadmap is directional. Features are only considered released after they are implemented, tested, and documented.

## Installation

There is no stable public package yet.

When the first verified release is available:

1. Download the release ZIP attached to the GitHub Release.
2. Verify its published SHA-256 checksum.
3. In WordPress Admin, open **Plugins → Add New → Upload Plugin**.
4. Upload the ZIP and activate **Free MPRO Forms**.
5. Create a form, publish it, and embed its shortcode on a page.

Do not use GitHub’s automatically generated “Source code” archive as the installable plugin unless the release notes explicitly say it is supported. The verified plugin ZIP will contain the correct root folder and only runtime files.

## Update model

GitHub Releases will be the source of truth for versions, release notes, downloadable ZIP files, and checksums.

The first stable release will support **manual updates from GitHub**. WordPress does not automatically update a third-party plugin from a GitHub repository without a custom updater. A secure in-dashboard updater may be evaluated in a later version. The plugin header will use an `Update URI` to prevent an unrelated WordPress.org plugin with a similar slug from overwriting the installation.

A future WordPress.org directory release remains possible. If adopted, GitHub will remain the development repository and WordPress.org SVN will be treated only as a release repository.

## Privacy

The current prototype stores submissions in the local WordPress database and does not require an external account. The public release will document:

- What data is collected.
- Where submissions are stored.
- Who can access them.
- How data can be exported or erased.
- What happens during deactivation and uninstall.
- Whether any optional integration sends data to another service.

No telemetry, tracking, remote assets, or external submission processing will be introduced silently.

## Security

Please do not report vulnerabilities in public issues. Follow [SECURITY.md](SECURITY.md) for responsible disclosure.

The public release process will include syntax checks, WordPress Coding Standards, Plugin Check, security review, accessibility review, and installation tests before a stable tag is published.

## Requirements

The audited prototype currently declares:

- WordPress 6.5 or newer.
- PHP 8.1 or newer.

The final compatibility range will be confirmed by automated and manual tests before the first stable release.

## Documentation

- [Roadmap](docs/ROADMAP.md)
- [Release process](docs/RELEASE-PROCESS.md)
- [Landing page brief](docs/LANDING-PAGE.md)
- [Initial prototype audit](docs/AUDIT-2026-07-27.md)
- [Development log](docs/DEVELOPMENT-LOG.md)
- [AI-assisted development policy](docs/AI-ASSISTED-DEVELOPMENT.md)
- [Contributing](CONTRIBUTING.md)
- [Changelog](CHANGELOG.md)

## Contributing

The repository will accept focused bug reports, documentation improvements, translations, accessibility feedback, and reviewed pull requests. Read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a contribution.

## License

Free MPRO Forms is licensed under the GNU General Public License v2.0 or later. See [LICENSE](LICENSE).

## Author

Created and maintained by [Sayid Moghadam](https://moghadam.pro/).
