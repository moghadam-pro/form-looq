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

- Validation runs after the form, post status, nonce, honeypot, submission-time, and rate-limit checks.
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

## Automated browser verification

Chromium tests run against a real WordPress installation and verify:

- English LTR rendering and computed direction.
- Persian RTL rendering and computed direction.
- Explicit label and accessible-name resolution.
- `fieldset` and `legend` semantics for grouped choices.
- Keyboard order between adjacent form controls.
- Successful submission and success status announcement.
- Server-side validation when native browser validation is bypassed.
- Linked error summaries and `aria-invalid` state.
- Stable focus on the first invalid control after navigation and page scripts complete.
- Removal of temporary status and state parameters from the visible URL.

## Verification performed

- PHP syntax and parser/submission-validator tests pass on PHP 8.1, 8.2, and 8.3.
- JavaScript syntax validation passes on Node.js 22.
- Focused WordPress Coding Standards and WordPress Plugin Check gates pass.
- Privacy, retention, uninstall, rate-limit, and browser tests pass in real WordPress environments.
- Chromium LTR, RTL, keyboard, success, error-summary, and focus scenarios pass.

## Remaining release blockers

- Manual screen-reader validation with at least one desktop screen reader.
- Final clean-install Release Candidate verification.
- Final release notes, package review, and publication decision.
