# Validation and Accessibility Baseline

This document records the implemented development baseline. It does not claim WCAG conformance or production readiness.

## Field-definition controls

- Form definitions are limited to 50 fields and 50,000 characters.
- Supported field types are explicitly allowlisted.
- Field names are sanitized, length-limited, unique, and checked against reserved request keys.
- Labels, help text, option counts, and option lengths are limited.
- Select, radio, and scale fields require configured options.
- Duplicate options are removed.

## Server-side submission validation

- Validation runs after the form, post status, nonce, honeypot, and submission-time checks.
- Scalar fields reject arrays and objects.
- Email, number, telephone, textarea, select, radio, scale, checkbox, and text values use type-specific validation.
- Select, radio, and scale values must exactly match a configured option.
- Required fields are checked on the server even when browser validation is bypassed.
- Unexpected request keys produce a form-level error.
- Value lengths are enforced independently of HTML attributes.
- Invalid values are never stored as permanent submissions.

## Error-state behavior

- Known values are sanitized and preserved after validation errors.
- Preserved values are stored locally in a five-minute WordPress transient.
- URLs contain only a random opaque token, status, and form ID; submitted values are not placed in URLs.
- Temporary state is scoped to the submitted form and consumed on first successful read.
- Error-state responses disable page caching where supported.
- Status parameters are removed from the visible URL after rendering when JavaScript is available.
- Multiple forms on one page receive independent success and error states.

## Accessible markup baseline

- Text controls use explicit `label` and `for` relationships.
- Every control receives a unique ID.
- Radio and scale groups use `fieldset` and `legend`.
- Help text and field errors are connected with `aria-describedby`.
- Invalid controls use `aria-invalid="true"`.
- Required state is expressed in visible text and with native required attributes.
- Error summaries link to the related controls.
- Success uses `role="status"`; errors use `role="alert"`.
- The first invalid control receives focus after the redirected page renders.
- Invalid radio and scale fieldsets become programmatically focusable.
- Focus styling works in LTR and RTL layouts.

## Verification performed

- PHP syntax checks passed for the plugin bootstrap and new validation, state, and frontend classes.
- JavaScript syntax validation passed for the frontend error-state script.
- A local validator smoke test confirmed that a valid submission passes while invalid email and forged option values return field-specific errors.
- Manual QA scenarios are defined in `docs/ERROR-STATE-QA.md`.

## Remaining release blockers

- Automated unit and integration tests in a real WordPress test environment.
- WordPress Coding Standards and Plugin Check.
- Browser and assistive-technology testing.
- Personal-data exporter and eraser integration.
- Final retention and uninstall settings.
- Flood-control hardening beyond the minimum completion-time check.
- Clean release packaging and reproducible builds.
