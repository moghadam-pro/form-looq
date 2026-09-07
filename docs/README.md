# Form LOOQ documentation

This directory is the canonical development documentation for the source on `main`.

**Current documented version:** 0.4.1

## Documentation map

- [ARCHITECTURE.md](ARCHITECTURE.md) — runtime structure, storage, request flows, migrations, privacy, and extension points.
- [DEVELOPMENT.md](DEVELOPMENT.md) — local setup, checks, branch/PR workflow, and contribution conventions.
- [RELEASE.md](RELEASE.md) — versioning, quality gates, packaging, GitHub Releases, and rollback.
- [archive/](archive/) — historical MPRO-era documents kept for traceability. They are **not** current product documentation.

## Source-of-truth policy

- `main` contains source, tests, CI, release tooling, and the documentation that describes them.
- Documentation changes use short-lived branches such as `docs/*` and are merged back into `main`.
- A permanent documentation branch is not used.
- Release history is represented by Git tags and GitHub Releases, not backup branches.
- Generated packages are release artifacts; the repository source archive is not the installable WordPress package.

When code and documentation disagree, treat the code and automated checks as evidence of current behavior and update the documentation in the same change.
