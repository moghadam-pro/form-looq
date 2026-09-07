# Native Forms to MPRO Forms Migration Strategy

## Decision

The first public release will use the final `mpro-forms` identity and will not silently reuse the prototype identifiers.

Because the prototype has not been publicly distributed as MPRO Forms, automatic migration is not required for the initial public release. A guarded one-time migration path may be added for the maintainer's existing test installation before the first release candidate.

## Legacy identifiers under review

- Post types used by the Native Forms prototype.
- Form field and submission meta keys.
- Shortcode names.
- Submission-created action names.
- Option names, CSS selectors, and admin slugs.

## Migration requirements

Any migration implementation must:

1. Run only for an authenticated administrator with an explicit capability check.
2. Require a nonce-protected confirmation action.
3. Detect legacy data before offering migration.
4. Never delete legacy records automatically.
5. Create a backup/export recommendation before changes.
6. Be idempotent and safe to run more than once.
7. Record only a non-sensitive completion flag.
8. Provide a dry-run summary of affected forms and submissions.
9. Avoid copying real submission values into logs.
10. Include rollback instructions.

## Public release rule

No legacy migration code will ship merely for convenience. It will be included only if a verified legacy installation needs it and the path is tested against a copy of that installation.

## Current outcome

- New public installations use only the `mpro_` identifiers.
- The maintainer's original prototype data will be tested separately.
- The migration decision remains a release-candidate gate, not a blocker for validation and accessibility development.
