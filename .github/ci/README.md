# Controlled CI trigger

The consolidated quality workflow runs automatically only when `.github/ci/run-quality` changes. All other commits leave the expensive release gates idle. The workflow can also be started manually from GitHub Actions.
