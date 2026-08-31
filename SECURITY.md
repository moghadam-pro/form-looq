# Security policy

## Supported versions

| Version | Supported |
| --- | --- |
| 0.2.x | Yes |
| 0.1.x | No — pre-release, superseded |

Until the first stable release, only the latest version receives fixes.

## Reporting a vulnerability

Please report security issues privately rather than opening a public issue.

- **Email:** security@sayid.ir
- **GitHub:** open a [private security advisory](https://github.com/moghadam-pro/free-forms-wp-plugin/security/advisories/new)

Useful details to include:

- The plugin version, WordPress version, and PHP version.
- What an attacker can do, and what access they need to do it.
- Steps to reproduce, ideally against a clean install.
- Any proof-of-concept you have.

You can expect an acknowledgement within 72 hours and an assessment within seven
days. If the report is confirmed, you will be kept updated through the fix and
credited in the release notes unless you prefer otherwise.

Please give a reasonable window for a fix before disclosing publicly.

## Scope

**In scope**

- Anything in the `free-mpro-forms/` directory.
- Privilege escalation, SQL injection, XSS, CSRF, and unauthorised data access.
- Bypassing the capability checks that gate plugin screens.
- Bypassing submission validation or rate limiting.

**Out of scope**

- Vulnerabilities in WordPress core, other plugins, or themes.
- Issues that require an administrator account to exploit, unless they cross a
  boundary an administrator is not meant to cross.
- Missing security headers on the site as a whole.
- Findings from automated scanners without a working proof of concept.
- Social engineering.

## Security design

Points that are deliberate rather than accidental, and worth understanding before
reporting:

- **Capability gating.** Every admin screen begins with `Admin::guard()`, which
  checks `fmpf_manage_forms` or `manage_options`.
- **Nonces.** Every state-changing request — row actions, bulk actions, settings
  saves, form saves, exports, note saves — is nonce-verified.
- **Client input is untrusted.** The builder posts field definitions as JSON.
  `Field_Validator::sanitize_fields()` re-applies the full ruleset server side:
  type allowlist, reserved and duplicate name rejection, length caps, and a
  50-field limit.
- **Submission validation.** Values are checked against the field's declared type
  and, for choice fields, against the configured options. A request carrying keys
  the form did not define is rejected outright.
- **Database access.** All queries use `$wpdb->prepare()` or the `$wpdb` helper
  methods with explicit format arrays. Order-by and column names come from
  allowlists, never from request data.
- **Output escaping.** All output is escaped at the point of output.
- **IP handling.** IP addresses are stored only as a salted HMAC-SHA256 digest,
  and only when the form enables it. The rate limiter hashes identities before
  storing counters.
- **Remote content.** Responses from the project site are sanitized field by field
  before rendering, so a compromised endpoint cannot inject markup. The requests
  can be disabled entirely.

If you find a place where one of these does not hold, that is a valid report.
