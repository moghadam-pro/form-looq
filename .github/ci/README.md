# Controlled CI

Expensive release gates do not run on ordinary commits.

To run the complete suite, either:

1. Start **Controlled Release Gates** manually from GitHub Actions; or
2. Change `.github/ci/run-quality` in the pull request.

The complete suite covers PHP and JavaScript syntax, parser and submission validation, focused WordPress security standards, Plugin Check, real WordPress privacy/retention/uninstall/rate-limit integration tests, and creation of an installable plugin ZIP.

The separate **Build or Draft Release** workflow is manual. It verifies that the requested version matches both the plugin header and WordPress.org stable tag, builds the ZIP, uploads it as an artifact, and can optionally create a draft GitHub release. It never publishes a release unless the operator explicitly enables that input.
