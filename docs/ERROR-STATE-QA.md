# Error-State QA

These checks must pass in both English LTR and Persian RTL layouts before the validation and error-state work can be considered complete.

## Field validation

1. Submit every required field empty.
   - An error summary appears.
   - Every required field has a specific error.
   - The first invalid field receives focus.

2. Enter an invalid email address.
   - The email value remains visible.
   - The email field is marked with `aria-invalid="true"`.
   - The field error is connected through `aria-describedby`.

3. Enter a non-numeric value through a modified request for a number field.
   - The server rejects it even if browser validation is bypassed.

4. Submit an option that is not configured for select, radio, or scale.
   - The server rejects the value.
   - The forged value is not reflected into the form.

5. Submit an array for a scalar field.
   - The request fails safely without a PHP warning or stored value.

6. Submit an unexpected request key.
   - A form-level error is displayed.
   - Known values can still be corrected without exposing the unexpected value.

## Value preservation

7. Make one field invalid while other fields are valid.
   - Text, email, telephone, number, textarea, select, radio, scale, and checkbox values are restored appropriately.

8. Refresh after the error page has rendered.
   - The one-time state cannot be reused.
   - Sensitive values do not remain in URL parameters.

9. Wait longer than five minutes before opening an unused error-state URL.
   - The expired state is unavailable.
   - A generic error remains understandable.

## Multiple forms

10. Place two different forms on one page and submit the second form with an error.
    - Only the submitted form displays the status and errors.
    - The first form does not display a false success or error state.

## Accessibility

11. Navigate the error summary links using only the keyboard.
    - Each link reaches the related control or fieldset.

12. Test with a screen reader.
    - The summary is announced.
    - Labels, required state, help text, and field errors have understandable relationships.

13. Test invalid radio and scale groups.
    - The `fieldset` can receive focus.
    - The `legend` and related error are announced.

14. Disable JavaScript.
    - Server-side validation and visible error messages still work.
    - The opaque state token may remain in the URL, but no submitted values appear there.

## Privacy and caching

15. Inspect the redirected URL.
    - It contains only status, form ID, and an opaque random token.

16. Inspect response headers and a supported caching plugin.
    - The error-state response is not cached.

17. Inspect stored temporary state.
    - It contains only sanitized known field values, validation errors, form ID, and creation time.
    - It contains no IP address, user agent, account ID, or analytics identifier.

## Success flow

18. Submit a valid form.
    - One permanent submission is created.
    - No validation transient is created.
    - Only the submitted form displays the success message.
    - Status parameters are removed from the visible URL after rendering when JavaScript is available.
