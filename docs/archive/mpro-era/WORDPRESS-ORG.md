# WordPress.org Publishing Plan

MPRO Forms is planned for publication in the official WordPress.org Plugin Directory in addition to GitHub Releases.

## Cost

WordPress.org does not charge a submission, review, listing, hosting, download, or update-distribution fee for plugins in the public directory.

Possible indirect costs are outside WordPress.org itself, such as development time, testing infrastructure, a domain, professional security review, translations, or optional third-party services.

## Required accounts and ownership

- A WordPress.org user account is required.
- The submitting account becomes an initial plugin committer after approval.
- Additional committers may be added later.
- The account and project must use accurate, maintainable contact information.

## Submission prerequisites

The submission package must be a complete, installable ZIP that is ready for review. Placeholder projects, incomplete shells, and proposals should not be submitted.

Before submission, MPRO Forms must have:

- A unique and policy-compliant plugin name and slug.
- GPL-compatible code, libraries, fonts, icons, images, and other bundled assets.
- A valid main plugin header.
- A valid WordPress.org `readme.txt`.
- No encrypted, obfuscated, or intentionally unreadable code.
- No tracking or telemetry without explicit consent and documentation.
- No undisclosed calls to external services.
- No trialware behavior that locks locally available functionality behind payment.
- Secure input handling, capability checks, escaping, and nonce use where appropriate.
- Internationalized user-facing strings.
- No secrets, development files, private data, or production submissions in the ZIP.
- A stable product website and a monitored support contact.

## Review readiness gate

The plugin will not be submitted until all Phase 1 release blockers are resolved and the release candidate passes:

1. PHP syntax checks.
2. WordPress Coding Standards.
3. Plugin Check.
4. Security review.
5. Accessibility review.
6. Internationalization checks.
7. Clean installation, activation, deactivation, and uninstall tests.
8. English LTR and Persian RTL tests.
9. Supported WordPress and PHP compatibility tests.
10. Source-to-build verification.

## Submission and approval flow

1. Build a clean release ZIP with the root directory `mpro-forms`.
2. Validate the plugin header and `readme.txt`.
3. Run automated and manual release checks.
4. Submit the ZIP through the WordPress.org developer submission form.
5. Respond to review feedback and provide corrected ZIP files when requested.
6. After approval, receive access to the plugin's WordPress.org SVN repository.
7. Commit the approved plugin files to `trunk`.
8. Commit directory assets such as icons, banners, and screenshots to the SVN `assets` directory.
9. Create a version directory under `tags` and ensure `Stable tag` points to the intended release.
10. Verify the public listing, installation, translations, support forum, and update delivery.

Approval is not guaranteed. The review team may request changes, reject the chosen slug, or decline the plugin if it does not meet directory guidelines.

## GitHub and SVN responsibilities

### GitHub

GitHub remains the canonical development repository for:

- Source code and branches.
- Issues and pull requests.
- CI checks and review.
- Security policy.
- Technical and contributor documentation.
- Tagged source releases and verified release packages.

### WordPress.org SVN

SVN is used only as a release repository for:

- Approved runtime plugin files.
- Stable version tags.
- WordPress.org directory assets.

Development work should not happen directly in SVN.

## Release synchronization

Every stable release intended for both channels should use the same version number and changelog entry.

Recommended order:

1. Freeze and test the release candidate on GitHub.
2. Create the final clean ZIP and checksum.
3. Tag and publish the GitHub Release.
4. Deploy the exact reviewed runtime files to WordPress.org SVN.
5. Verify the WordPress.org listing and update package.
6. Update the English and Persian product websites and demos.

If a security release requires coordinated disclosure, publication order may be adjusted to minimize exposure.

## Directory assets to prepare

- Plugin icon: 128×128 and 256×256 PNG.
- Banner: 772×250 and 1544×500 PNG or JPG.
- Numbered screenshots matching `readme.txt` captions.
- Clean English and Persian form examples.
- Forms list, editor, submission list, and submission details screens.

Assets must be original or properly licensed and must not imitate Gravity Forms branding.

## Maintenance obligations

Publishing on WordPress.org creates an ongoing maintenance responsibility:

- Monitor support and security reports.
- Keep compatibility information accurate.
- Maintain the plugin against current WordPress releases.
- Publish clear changelogs.
- Avoid abandoned or misleading listings.
- Keep external service and privacy disclosures current.
- Follow WordPress.org guidelines after approval, not only during initial review.
