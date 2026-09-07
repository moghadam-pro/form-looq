# Development

## Requirements

The plugin itself requires:

- WordPress 6.5+
- PHP 8.1+

Development tooling also uses Composer, Node.js/npm, and either Bash or PowerShell for packaging.

## Working copy

```bash
git clone https://github.com/moghadam-pro/form-looq.git
cd form-looq
```

Copy or symlink the installable source directory into a WordPress installation:

```bash
ln -s "$PWD/form-looq" /path/to/wordpress/wp-content/plugins/form-looq
```

## Local checks

Fast validator check:

```bash
php tests/validator-test.php
```

Coding standards:

```bash
composer install
vendor/bin/phpcs --standard=phpcs.xml.dist
```

Browser tests:

```bash
npm install
npm run test:e2e
```

Build an installable package on macOS/Linux:

```bash
bash scripts/build-plugin.sh
```

On Windows PowerShell:

```powershell
./scripts/build-plugin.ps1
```

Both packaging scripts verify that the plugin header version matches the WordPress `Stable tag` and produce:

```text
dist/form-looq.zip
```

## Git workflow

Form LOOQ uses a lightweight GitHub Flow model.

```text
main
├── feat/*
├── fix/*
├── docs/*
├── refactor/*
├── test/*
└── chore/*
```

Rules:

- `main` is the source of truth.
- Work happens on short-lived branches.
- Permanent `docs`, `develop`, agent, or backup branches are not part of the normal workflow.
- Open a PR, review the diff and checks, then prefer squash merge for one logical change.
- Do not force-push `main`.
- Preserve a recovery point before risky migrations or release-engineering changes.

Recommended commit style follows Conventional Commits, for example:

```text
feat(forms): add rating field
fix(rtl): correct admin alignment
docs(architecture): document storage migration
chore(release): prepare 0.4.2
```

## Documentation rule

Documentation that describes the current code belongs in `docs/` on `main` and should be updated in the same PR as the behavior it documents.

Historical documents that are useful for traceability but no longer describe the product belong in `docs/archive/` and must be clearly marked non-canonical.

## Release work

Do not commit a ZIP as the product source. Build the artifact from the reviewed source and attach it to a GitHub Release.

See [RELEASE.md](RELEASE.md).
