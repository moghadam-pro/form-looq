# Validation and Accessibility Baseline

## Status

Implemented on the Phase 0 identity branch as an initial public-release baseline. This document does not claim full WCAG conformance.

## Definition limits

- Maximum stored definition length: 50,000 characters.
- Maximum parsed fields: 50.
- Maximum field-name length: 64 characters.
- Maximum label length: 160 characters.
- Maximum help-text length: 300 characters.
- Maximum options per field: 100.
- Maximum option length: 200 characters.
- Duplicate and reserved field names are rejected by the parser.
- Select, radio, and scale fields require configured options.

## Submission validation

- Required fields are enforced on the server.
- Arrays and objects are rejected for scalar fields.
- Email values must pass WordPress email validation.
- Number values must be numeric.
- Telephone values accept Unicode digits and common telephone punctuation.
- Select, radio, and scale values must exactly match configured options.
- Checkbox values are normalized to `1` or an empty string.
- Text values are limited to 500 characters.
- Textarea values are limited to 5,000 characters.
- Excess request keys are rejected.
- Nonce, honeypot, published-form status, and submission-time checks are enforced before storage.
- Redirect targets are validated before use.

## Accessibility baseline

- Text, email, telephone, number, textarea, and select controls have unique IDs and explicit labels.
- Radio and scale controls use `fieldset` and `legend`.
- Help text is connected using `aria-describedby`.
- Required state is communicated in text and through native required attributes.
- Success messages use `role="status"`.
- Error messages use `role="alert"`.
- Notices can receive programmatic focus.
- Keyboard focus indicators are visible.
- Layout remains responsive in LTR and RTL directions.

## Still required before a stable release

- Preserve valid field values after a failed submission.
- Return field-specific error messages instead of only a general error.
- Move focus to the error summary after redirect.
- Test with keyboard-only navigation and screen readers.
- Test high zoom, forced colors, reduced motion, and browser autofill.
- Run WordPress Coding Standards and Plugin Check.
- Add automated tests for parser and validator edge cases.
- Verify localized number behavior and international telephone examples.

## Manual test cases

1. Submit every supported field type with valid values.
2. Omit each required field one at a time.
3. Submit an invalid email address.
4. Submit a nonnumeric value to a number field.
5. Submit an option that is not configured in a select or radio field.
6. Submit an array for a scalar field.
7. Submit more request keys than expected.
8. Submit before the minimum completion time.
9. Submit after the timestamp expires.
10. Fill the honeypot field.
11. Repeat tests in English LTR and Persian RTL layouts.
12. Navigate and submit using only the keyboard.
