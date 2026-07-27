# Data Lifecycle

This document describes the current data behavior of the development version of Free MPRO Forms. It is not a substitute for a site owner's privacy policy.

## Permanent submissions

After a valid form submission, the plugin stores a private `fmpf_submission` post inside the same WordPress installation.

The stored record contains:

- The form ID.
- The configured submission type.
- A generated submission title and received time.
- Sanitized values for the configured form fields.
- An internal workflow status.

The core plugin does not send submissions to an external service and does not add analytics or telemetry.

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

## Retention

The default retention setting is **keep submissions until manually deleted**. An administrator can instead select automatic deletion after 30, 60, 90, 180, 365, 730, or 1,825 days.

A daily WordPress cron task permanently deletes submissions older than the selected period in bounded batches. If no retention period is selected, the cleanup task exits without deleting data.

Temporary invalid-submission state remains independent of permanent retention and expires after five minutes.

## WordPress personal-data export

Free MPRO Forms registers an exporter with WordPress **Tools → Export Personal Data**.

For each submission, the exporter:

- Identifies fields configured with the `email` field type.
- Matches the requested address case-insensitively after email sanitization.
- Exports the form name, received time, submission type, workflow status, and stored field values.
- Uses stored field labels when the original form still exists.
- Falls back to email-like field names only when the original form definition is unavailable.

A form submission is not assumed to belong to a registered WordPress account; matching is based on the submitted email address.

## WordPress personal-data erasure

Free MPRO Forms registers an eraser with WordPress **Tools → Erase Personal Data**.

For a matching submission, the eraser:

- Removes all submitted field values.
- Keeps the non-personal record shell, received time, form relationship, and submission type for operational counting and audit continuity.
- Changes the internal status to `erased`.

The eraser intentionally redacts the values instead of deleting the post during paginated privacy processing. Administrators can still permanently delete individual records through WordPress Admin, automatic retention, or the explicit uninstall setting.

## Deactivation

Deactivation clears the plugin's scheduled retention event but preserves forms, submissions, settings, and temporary state. This prevents accidental data loss during troubleshooting or temporary deactivation.

When the plugin is activated again, the daily retention schedule is restored automatically.

## Uninstall

The default uninstall behavior preserves forms, submissions, and settings. The scheduled retention event is cleared because the plugin code will no longer be available.

A dedicated setting allows an administrator to opt in to permanent uninstall cleanup. When enabled, uninstall deletes:

- All Free MPRO Forms submissions.
- All Free MPRO Forms form definitions.
- Retention and uninstall settings.
- Temporary validation transients stored in the WordPress options table.

The option is disabled by default, displays an irreversible-action warning, and does not affect ordinary deactivation.

## External integrations

Any future email, webhook, anti-spam, or third-party integration must:

- Be optional.
- Be disabled by default unless it is essential to the feature the user explicitly enables.
- Identify the external service and the data sent to it.
- Link to the relevant terms and privacy information.
- Avoid silent telemetry or unrelated tracking.
