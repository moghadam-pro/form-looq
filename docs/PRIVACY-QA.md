# Privacy and Retention QA

These checks must pass in a real WordPress test installation before the privacy lifecycle is considered release-ready.

## Personal-data exporter

1. Create a form with a configured email field and submit the same address more than once.
   - WordPress **Tools → Export Personal Data** includes every matching submission.
   - Field labels are human-readable.
   - Form, received time, type, status, and configured values are included.

2. Create submissions with a different email address.
   - They are not included in the requested export.

3. Delete the original form but retain its submissions.
   - Export can still match a clearly email-named stored field.
   - Unknown field labels fall back to readable versions of their stored keys.

4. Use uppercase and lowercase variations of the same email address.
   - Matching remains case-insensitive after sanitization.

5. Create more than 50 submissions.
   - Pagination returns every matching record exactly once.
   - The exporter eventually reports `done: true`.

## Personal-data eraser

6. Request erasure for a matching email address.
   - All submitted field values are removed.
   - The record shell remains available for operational counts.
   - Internal status changes to `erased`.

7. Export the same email address after erasure.
   - The erased submission no longer matches or exports personal field values.

8. Process more than 50 submissions.
   - Pagination does not skip records.
   - Each matching submission is erased once.

9. Request erasure for an invalid or absent email address.
   - No records are changed.
   - The eraser completes without warnings.

## Retention

10. Leave retention at the default setting.
    - Daily cleanup does not delete submissions.

11. Select each supported retention period.
    - Only submissions older than the selected threshold are permanently deleted.
    - Newer submissions remain.

12. Provide an unsupported retention value by modifying the request.
    - The setting is normalized to the safe default of zero days.

13. Simulate a deletion failure during a 100-item cleanup batch.
    - Cleanup exits rather than entering an infinite loop.

14. Deactivate and reactivate the plugin.
    - Deactivation clears the scheduled event without deleting data.
    - Reactivation restores one daily event without duplicates.

## Uninstall

15. Uninstall with the deletion option disabled.
    - Forms, submissions, and settings remain in the database.
    - The scheduled retention hook is removed.

16. Reinstall after preserved uninstall data.
    - Existing forms and submissions remain available after compatibility checks.

17. Enable permanent uninstall cleanup and uninstall the plugin.
    - Forms, submissions, plugin settings, and temporary validation transients are permanently removed.
    - The cleanup completes in bounded batches.

18. Verify the irreversible warning and checkbox behavior.
    - The checkbox is disabled by default.
    - Unchecking a previously enabled option saves zero correctly.

## Suggested privacy policy content

19. Open the WordPress privacy-policy guide.
    - A Free MPRO Forms section appears.
    - It describes local storage, temporary error state, no core telemetry, retention, privacy tools, and uninstall behavior.

20. Review all text in English and Persian WordPress installations.
    - User-facing strings are translatable.
    - No statement promises behavior that the code does not provide.
