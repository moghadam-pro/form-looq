# Release Engineering

Form LOOQ separates **source**, **artifact**, and **installed deployment**.

```text
Source repository (main)
        ↓ quality gates + package build
dist/form-looq.zip
        ↓ GitHub Release / WordPress installation
wp-content/plugins/form-looq/
```

GitHub's automatically generated "Source code" archives contain the whole repository and are not the installable WordPress package.

## Version sources

Before a release, keep these synchronized:

1. `Version:` in `form-looq/form-looq.php`
2. `FORM_LOOQ_VERSION`
3. `Stable tag:` in `form-looq/readme.txt`
4. release/changelog notes appropriate to the version

The packaging and release workflows fail when the plugin header and Stable tag disagree.

## Recommended release preparation

Create a short-lived branch such as:

```text
chore/release-0.4.2
```

Use the PR to:

- finish the version bump,
- update changelog/release notes,
- review release-impacting changes,
- run CI,
- merge to `main`.

Create a recovery point before changes that alter storage, migrations, packaging, or release automation.

## Quality gates

`.github/workflows/quality.yml` currently includes:

- PHP syntax and validator checks across PHP 8.1–8.3,
- JavaScript syntax,
- focused WordPress Coding Standards checks,
- WordPress Plugin Check,
- WordPress integration tests,
- Chromium LTR/RTL and keyboard validation,
- package structure verification,
- clean-install release-candidate testing,
- uninstall/reinstall data-preservation verification.

The release workflow invokes the full quality workflow as a required dependency.

## Building locally

macOS/Linux:

```bash
bash scripts/build-plugin.sh
```

Windows PowerShell:

```powershell
./scripts/build-plugin.ps1
```

Expected artifact:

```text
dist/form-looq.zip
└── form-looq/
    ├── form-looq.php
    ├── assets/
    ├── includes/
    ├── languages/
    ├── readme.txt
    └── uninstall.php
```

Development-only repository files are outside the packaged plugin directory.

## Publishing on GitHub

The current release workflow is intentionally manual:

```text
.github/workflows/release.yml
→ workflow_dispatch
```

Inputs:

- version without the `v` prefix
- whether to create the GitHub Release as a draft

After the quality gates pass, the workflow:

1. validates the requested version against the plugin header and Stable tag,
2. builds `dist/form-looq.zip`,
3. verifies the ZIP root and packaged version,
4. uploads a workflow artifact,
5. creates `v<version>` as a GitHub Release targeting the workflow commit,
6. attaches `form-looq.zip`.

The durable user-facing download is the GitHub Release asset. The Actions artifact is CI output, not the long-term distribution location.

## Rollback and traceability

Every published release should be traceable to one reviewed `main` commit.

For risky repository changes:

1. record the current `main` SHA,
2. create a temporary recovery branch or an appropriate tag,
3. make the change on a short-lived branch,
4. verify after merge,
5. keep the recovery point until the change is proven stable,
6. convert long-lived historical checkpoints to tags rather than permanent backup branches.

For a bad product release, do not rewrite history. Fix forward with a new patch release when possible. If an immediate repository rollback is required, revert the offending commit/PR while retaining the original release tag and audit trail.
