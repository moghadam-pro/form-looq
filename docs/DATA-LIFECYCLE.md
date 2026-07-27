# Data Lifecycle

This document describes the current data behavior of the development version of Free MPRO Forms. It is not a substitute for a site owner's privacy policy.

## Permanent submissions

After a valid form submission, the plugin stores a private `fmpf_submission` post inside the same WordPress installation.

The stored record currently contains:

- The form ID.
- The configured submission type.
- A generated submission title and received time.
- Sanitized values for the configured form fields.
- An internal workflow status.

The core plugin does not currently send the submission to an external service and does not add analytics or telemetry.

## Access

Submission screens require the WordPress `manage_options` capability. They are not public, are not exposed through the REST API, and cannot be created manually from the submission admin screen.

The capability model will receive an additional review before the first release candidate so installations can eventually delegate submission access without granting full administrator privileges.

## Invalid-submission state

When server-side validation fails, values may need to be shown again so the visitor can correct the form without retyping everything.

The plugin handles this state as follows:

1. Known field values and validation messages are sanitized.
2. They are stored locally in a WordPress transient with a five-minute expiration.
3. The browser receives only a random opaque token; submitted values are never added to the URL.
4. The token is scoped to the submitted form.
5. The state is deleted when successfully read and cannot be reused through the normal flow.
6. Pages containing a state token send no-cache headers and define `DONOTCACHEPAGE` for compatible caching plugins.
7. JavaScript removes the status, form ID, and state token from the visible URL after rendering.
8. The state does not include an IP address, user-agent fingerprint, account ID, or tracking identifier.

WordPress transients are temporary storage rather than a guaranteed immediate-deletion mechanism. A transient can remain in the database or object cache until WordPress or the cache backend removes the expired record. The release documentation will disclose this behavior.

## Deactivation

Deactivation currently stops plugin behavior but preserves forms and submissions. This prevents accidental data loss during troubleshooting or temporary deactivation.

## Uninstall

The uninstall policy is not final.

Before the first stable release, the plugin must provide an explicit and documented choice between:

- Preserving forms and submissions during uninstall, or
- Permanently deleting all plugin data when the site owner has enabled a dedicated opt-in setting.

No stable release will silently delete submissions on uninstall.

## Export and erasure

WordPress personal-data exporter and eraser integration is planned for Phase 1. Because field names are configurable, the implementation must identify email-address fields carefully and must not assume that every submission belongs to a registered WordPress user.

Until that integration is complete, administrators can view and delete individual submission records through WordPress Admin. This manual workflow is not considered sufficient for the stable release privacy gate.

## Retention

There is currently no automatic retention schedule for permanent submissions. Phase 1 must add a documented retention setting or a clear manual-retention policy before stable release.

Temporary invalid-submission state is limited to five minutes and is consumed on first successful render.

## External integrations

Any future email, webhook, anti-spam, or third-party integration must:

- Be optional.
- Be disabled by default unless it is essential to the feature the user explicitly enables.
- Identify the external service and the data sent to it.
- Link to the relevant terms and privacy information.
- Avoid silent telemetry or unrelated tracking.
