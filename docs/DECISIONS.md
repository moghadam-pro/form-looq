# Decision log

Each entry records a decision that is not obvious from reading the code, the
alternatives considered, and what would cause it to be revisited.

---

## 1. Storage moved from custom post types to dedicated tables

**Date:** 2026-08-31 · **Version:** 0.2.0 · **Status:** Accepted

### Context

0.1.0 stored forms as `fmpf_form` posts and submissions as `fmpf_submission`
posts, with field definitions and answers in post meta.

### Decision

Two plugin-owned tables, `{prefix}fmpf_forms` and `{prefix}fmpf_entries`.

### Why

The requirement that drove it: a site owner must be able to delete the plugin and
reinstall it — or replace the folder during an update — without losing data. With
custom post types that is technically true as well, but the data sits in
`wp_posts` and `wp_postmeta` alongside everything else on the site, where it is
vulnerable to any tool that sweeps orphaned post types or unregistered meta.
Owning the tables makes the guarantee explicit rather than incidental.

Two further gains followed. A forms list that needs entry counts, views, and a
conversion rate per row is a single indexed query rather than a `WP_Query` plus a
meta join per row. And an entry with twelve answers is one row instead of one
post plus twelve meta rows.

### Alternatives considered

- **Keep CPTs.** Rejected for the reasons above.
- **Tables plus a one-time CPT migrator.** Considered and rejected because 0.1.0
  was never publicly released, so there is no installed base to migrate. Shipping
  a migrator would mean maintaining and testing a path nobody travels.

### Consequences

- Forms and entries created under 0.1.0 are not carried over. The readme's
  Upgrade Notice states this plainly.
- WordPress does not manage the schema, so `Install::SCHEMA_VERSION` and the
  `plugins_loaded` check exist to keep it current even without an activation.
- `uninstall.php` must drop the tables explicitly, and only when opted in.

### Revisit if

An installed base of 0.1.0 sites appears, or per-field querying becomes common
enough to justify an entry-value index table alongside the JSON column.

---

## 2. No build step

**Date:** 2026-08-31 · **Version:** 0.2.0 · **Status:** Accepted

### Context

The builder needed drag and drop, dynamic field cards, and live editing —
normally a case for React and `@wordpress/scripts`.

### Decision

Plain ES2017 in `builder.js` and `admin.js`, with no bundler, transpiler, or
minifier. `wp-i18n` is the only script dependency.

### Why

Three reasons, in order of weight:

1. **Review.** WordPress.org requires human-readable source for anything
   minified. Shipping what runs removes an entire class of submission friction.
2. **Weight.** The builder is roughly 10 KB of JavaScript. The React equivalent
   would pull in the `wp-element` and `wp-components` bundles.
3. **Contribution.** Anyone with a text editor and a WordPress install can change
   the builder and see the result. No `npm install`, no toolchain drift.

### Alternatives considered

- **React with `@wordpress/scripts`.** Better ergonomics for the complex
  interactions a later version will want, at the cost of all three points above.

### Consequences

- State management in the builder is a plain array with a full re-render on
  change. That is fine at fifty fields and would not be at five hundred, which is
  well past the enforced limit.
- Conditional logic and multi-step forms will stress this choice. That is the
  natural point to reconsider.

### Revisit if

The builder needs nested or conditional structures that a re-render-everything
model handles badly.

---

## 3. Import is shipped disabled

**Date:** 2026-08-31 · **Version:** 0.2.0 · **Status:** Accepted

### Context

Export was straightforward. Import was scoped alongside it.

### Decision

Export ships complete in CSV and XML. Import ships as a visible but disabled
panel, with the reason stated on screen.

### Why

Import is not the inverse of export. It has to answer questions export never
faces: what happens when an incoming column matches no field on the target form;
what happens when a choice value is not among the form's configured options;
whether a re-import creates duplicates or updates in place; and what to do with
rows that partially fail. Guessing wrong writes bad data into a table the plugin
promises to protect.

Shipping a disabled panel that explains the delay is more honest than shipping an
importer that silently corrupts entries, and more useful than hiding the feature
entirely, since it tells users the file they export now will remain importable.

### Alternatives considered

- **Ship a naive importer.** Rejected: the failure mode is silent data damage.
- **Omit the panel.** Rejected: users would not know export files are forward
  compatible.

### Revisit if

The export format has been validated against real sites and a merge strategy is
settled.

---

## 4. IP addresses are stored as salted hashes

**Date:** 2026-08-31 · **Version:** 0.2.0 · **Status:** Accepted (carried from 0.1.0)

### Decision

When a form enables IP storage, the entry keeps `HMAC-SHA256(ip, wp_salt('nonce'))`
truncated to 64 characters. The raw address is never written to the database. The
rate limiter uses the same construction for its transient counters.

### Why

The operational value of a stored IP is almost entirely "were these two
submissions from the same source" — which a stable hash answers. The residual
value, identifying an individual, is exactly the part that creates GDPR exposure.
Salting with a site secret also means the digest cannot be compared across sites
or reversed with a rainbow table over the IPv4 space.

### Consequences

- Administrators cannot read the address, only compare digests. The entry screen
  says so.
- Rotating WordPress salts invalidates existing digests. This is acceptable:
  their only use is comparison, and comparison across a salt rotation is not
  meaningful anyway.

---

## 5. One capability for every screen

**Date:** 2026-08-31 · **Version:** 0.2.0 · **Status:** Accepted

### Decision

A single `fmpf_manage_forms` capability, granted to `administrator`, gating every
plugin screen. `Plugin::current_user_can()` also accepts `manage_options`.

### Why

A granular scheme — separate capabilities for editing forms, reading entries,
deleting entries, changing settings — is the right end state, but only once there
is evidence about how teams actually divide this work. Inventing five
capabilities up front means committing to names and boundaries that later have to
be migrated.

Accepting `manage_options` as an alternative is the safety valve: a site using a
role manager that strips unknown capabilities cannot lock its administrators out
of their own forms.

### Revisit if

Users ask to give an editor access to entries without access to settings — the
first real signal that the boundary matters.

---

## 6. Menu position 11

**Date:** 2026-08-31 · **Version:** 0.2.0 · **Status:** Accepted

### Decision

The top-level menu registers at position 11, directly below Media (10).

### Why

The position was specified as a product requirement. It puts forms next to the
other content-collection tool in the admin rather than at the bottom near
settings.

### Consequences

Position collisions with other plugins are possible; WordPress resolves them by
nudging one entry. The plugin does not attempt to defend the slot.

---

## 7. Views are counted on render, uncached

**Date:** 2026-08-31 · **Version:** 0.2.0 · **Status:** Accepted, with a known limit

### Decision

`Form_Repository::record_view()` runs one `UPDATE ... SET views = views + 1` each
time a form renders, filterable through `free_mpro_forms_track_views`.

### Why

The conversion-rate column needs a denominator. An atomic increment is the
cheapest correct way to get one, and it costs a single write on pages that
already ran a read.

### Known limit

On a site with full-page caching, a cached page serves without executing PHP, so
views undercount and the conversion rate reads high. This is documented rather
than worked around: the alternatives are a JavaScript beacon, which adds a
request to every page carrying a form, or a separate view-log table, which adds a
row per impression. Neither is worth it for a metric that is directional.

### Revisit if

Conversion rate is used for anything more consequential than orientation.
