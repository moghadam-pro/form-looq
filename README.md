# MPRO Forms — documentation

Development documentation for
[MPRO Forms](https://github.com/moghadam-pro/free-forms-wp-plugin), a free,
privacy-first WordPress form plugin.

**The plugin itself lives on [`main`](https://github.com/moghadam-pro/free-forms-wp-plugin/tree/main).**
This branch carries everything about *building* it.

## Contents

| Document | What it covers |
| --- | --- |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Data model, request flows, loading strategy, extension points |
| [DECISIONS.md](docs/DECISIONS.md) | Decision log with alternatives and revisit triggers |
| [DEVELOPMENT.md](docs/DEVELOPMENT.md) | Setup, checks, release process, how to extend |
| [REMOTE-CONTENT.md](docs/REMOTE-CONTENT.md) | JSON contract for the add-on catalogue and docs index |
| [ROADMAP.md](docs/ROADMAP.md) | Phased product plan |
| [DATA-LIFECYCLE.md](docs/DATA-LIFECYCLE.md) | How entry data is created, retained, and removed |
| [PRIVACY-QA.md](docs/PRIVACY-QA.md) | Privacy test matrix |
| [VALIDATION-AND-ACCESSIBILITY.md](docs/VALIDATION-AND-ACCESSIBILITY.md) | Validation rules and accessibility expectations |
| [ERROR-STATE-QA.md](docs/ERROR-STATE-QA.md) | Error-state test matrix |
| [MIGRATION-STRATEGY.md](docs/MIGRATION-STRATEGY.md) | Migration approach and constraints |
| [WORDPRESS-ORG.md](docs/WORDPRESS-ORG.md) | WordPress.org submission checklist |
| [landing/index.html](docs/landing/index.html) | Persian landing page for sayid.ir/mpro-forms |

## The landing page

`docs/landing/index.html` is a single self-contained file — no external CSS,
fonts, scripts, or images. Upload it as-is to
`https://sayid.ir/mpro-forms`.

It is also the source for the plugin's first-run welcome screen. When loaded with
`?mpro_context=1` or inside a frame, its download buttons rewrite themselves to
point at the documentation, since a visitor reading it inside wp-admin has the
plugin installed already.

## Branch layout

- **`main`** — the plugin, its README, changelog, license, security policy, and
  the tooling needed to test and package it.
- **`docs`** — this branch.
