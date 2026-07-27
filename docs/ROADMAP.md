# Product Roadmap

## Product promise

Free MPRO Forms should make common WordPress form workflows dependable, multilingual, and free without pretending to replace every mature commercial form platform on day one.

## Phase 0 — Public-release foundation

**Status:** In progress

### Goals

- Audit the uploaded Native Forms 1.0.0 prototype.
- Establish the final name, slug, namespace, prefixes, and repository structure.
- Define documentation, security, privacy, testing, and release standards.
- Create the original landing-page information architecture.
- Identify public-release blockers before importing code.

### Exit criteria

- Repository documentation is public and consistent.
- Prototype audit is recorded.
- No secrets or personal submission data are present.
- Phase 1 scope and acceptance criteria are approved.

## Phase 1 — Free Core

**Goal:** Publish a small, trustworthy, installable plugin.

### Product scope

1. **Forms**
   - Create, edit, publish, duplicate, and delete forms.
   - Support section, text, email, telephone, number, textarea, select, radio, scale, and consent/checkbox fields.
   - Provide a clear first-release field configuration interface.

2. **Embedding**
   - Provide a shortcode compatible with the block editor, Elementor, and other shortcode-capable builders.
   - Load frontend assets only when a form is rendered.

3. **Submissions**
   - Store submissions locally in WordPress.
   - Restrict access with explicit capabilities.
   - Provide list and detail views.
   - Establish export, erasure, retention, and uninstall behavior.

4. **Validation and abuse resistance**
   - Validate every field according to its declared type.
   - Reject values outside configured options.
   - Reserve internal field names.
   - Enforce sensible field and request limits.
   - Keep nonce and honeypot checks.
   - Add privacy-preserving flood control.

5. **Accessibility and localization**
   - Associate every input with a visible label.
   - Use fieldset and legend for grouped controls.
   - Provide understandable summary and field-level errors.
   - Test keyboard navigation and screen-reader announcements.
   - Ship English and Persian foundations.
   - Support LTR and RTL using semantic markup and CSS logical properties.

6. **Distribution**
   - Publish verified ZIP files and SHA-256 checksums through GitHub Releases.
   - Add an `Update URI` header to prevent accidental overwrite.
   - Document manual update behavior clearly.

### Phase 1 release blockers

- Strict server-side validation and request limits.
- Accessibility semantics and form-specific feedback.
- Privacy policy integration and data lifecycle decisions.
- Capability and submission-status review.
- Automated syntax, coding-standard, and Plugin Check results.
- Clean build with the correct `free-mpro-forms` root directory.
- English and Persian smoke tests on desktop and mobile.

## Phase 2 — Visual Builder & Workflows

**Goal:** Make building and operating forms significantly easier while preserving the free core.

### Candidate scope

- Visual drag-and-reorder field builder.
- Live preview and multi-column layout controls.
- Multi-step forms and progress indicators.
- Conditional logic.
- Email notifications and confirmations.
- Templates, duplication, import, and export.
- CSV export and improved submission states.
- Optional anti-spam integrations with explicit disclosure.
- Webhooks and richer developer hooks.
- Secure GitHub-based in-dashboard update evaluation.

### Scope control

Phase 2 features will be split into smaller releases. No feature is advertised as available until it passes implementation, compatibility, accessibility, security, and documentation checks.
