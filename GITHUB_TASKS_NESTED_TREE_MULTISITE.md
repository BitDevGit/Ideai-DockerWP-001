# Nested Tree Multisite (Subdirectory) — GitHub Task Breakdown

Goal: Support **hierarchical nested multisite sites** (e.g. `/a/`, `/a/b/`, `/a/b/c/`) on **any** WordPress **subdirectory multisite network**, using:

- MU plugin: `ideai.wp.plugin.platform` (safe, always-on platform behavior **including Network Admin UI**; feature-flagged per-network)

Constraints:
- **Per-network** feature flags (stored via `get_network_option()` / `update_network_option()`).
- **No breaking behavior** when feature flags are off.
- Must remain compatible with existing flat subdirectory multisite.
- Enforce collisions: if a nested site exists at a path, parent site cannot create content occupying that same path (and vice versa), efficiently (no full scans per request).

---

## Semantic labels (recommended)

Create these labels in GitHub (see script below):

### Area
- **area:platform**: MU-plugin (`ideai.wp.plugin.platform`) changes (includes IdeAI Network Admin UI)
- **area:nginx**: nginx/mkcert/dev routing changes
- **area:docs**: documentation-only
- **area:tests**: scripts/tests/harness

### Type
- **type:feature**: net-new feature work
- **type:fix**: bugfix
- **type:chore**: maintenance/refactor (no behavior change)

### Risk
- **risk:low**
- **risk:medium**
- **risk:high**: routing/canonical/cookies sensitive

### Status
- **status:ready**
- **status:blocked**
- **status:needs-review**

### Scope markers (optional)
- **scope:network-admin**
- **scope:routing**
- **scope:canonical**
- **scope:collisions**

---

## Tight issue headers (copy/paste template)

Use this structure in every issue body:

**Summary**

**Scope**
- In:
- Out:

**Acceptance**
- [ ]

**Risk / notes**

**Links**

---

## Commit tracking rules (one commit per task)

- **One issue = one commit** (no mixed changes).
- Commit message format:
  - `task(#<issue>): <short summary>`
- Include a trailer in the commit body:
  - `Refs #<issue>`
- PR title mirrors the commit message.
- PR body links the issue and restates acceptance checklist.

Example:

```
task(#123): add per-network feature flag API

Refs #123
```

---

## How to create these as GitHub Issues quickly (gh)

If you use GitHub CLI (`gh`) in the repo root:

```bash
# Example:
# gh issue create --title "..." --body-file - <<'EOF'
# ...
# EOF
```

I can generate a concrete `gh` script once you confirm labels/milestones you want.

---

## Issue List (each issue = one commit)

### 1) Add MU plugin skeleton: `ideai.wp.plugin.platform`
- **Deliverable**: `wp-content/mu-plugins/ideai.wp.plugin.platform/` with:
  - loader file
  - minimal logging helper (toggle via env/constant)
  - per-network feature flag reader utility
  - no behavior changes yet
- **Acceptance**:
  - MU plugin loads without errors on single site + multisite
  - No routing changes when flags are absent/off

### 2) Define + implement per-network feature flags API + Network Admin UI
- **Deliverable**:
  - Flag keys + defaults:
    - `ideai_nested_tree_enabled` (default false)
    - `ideai_nested_tree_collision_mode` (default "strict")
  - IdeAI Network Admin UI to toggle flag(s)
  - Platform reads them (feature-flagged)
- **Acceptance**:
  - Flags persist across requests
  - Flags are network-scoped, not site-scoped

### 4) Mapping data model (nested path ⇄ blog)
- **Deliverable**:
  - Decide storage:
    - Option A: custom table `wp_ideai_nested_sites`
    - Option B: network meta storing a compact mapping
  - Implement CRUD API in platform:
    - register nested path for blog_id
    - resolve deepest match for a request path
    - reverse lookup blog_id → nested path
- **Acceptance**:
  - Resolves deepest-prefix match correctly (supports depth ≥ 3)
  - Efficient lookup (indexed table or cached structure)

### 5) Request routing: “deepest registered prefix wins” (feature-flagged)
- **Deliverable**:
  - Platform hooks into multisite bootstrap to:
    - parse request path
    - resolve blog via mapping
    - set current blog correctly
  - Only active when `ideai_nested_tree_enabled=true`
- **Acceptance**:
  - When off: default WP routing untouched
  - When on: `/a/b/` resolves to blog mapped to `/a/b/` even if `/a/` also exists

### 6) Outbound URL rewriting (flat ⇄ nested)
- **Deliverable**:
  - Platform filters to ensure generated URLs use the nested path:
    - home/site URLs
    - admin URLs
    - login URLs
  - Only active when flag enabled
- **Acceptance**:
  - Frontend links and admin links remain consistent with nested paths

### 7) Canonical redirect policy (prevent flattening / loops)
- **Deliverable**:
  - Platform hooks `redirect_canonical` (and other needed points) to:
    - preserve nested paths
    - avoid redirect loops
  - Documented redirect rules
- **Acceptance**:
  - No loops on:
    - `/a/b/wp-admin/`
    - `/a/b/wp-login.php?redirect_to=...`

### 8) Collision prevention (reserved site paths vs page paths) — strict mode
- **Deliverable**:
  - Maintain a reserved-path index from nested sites
  - Maintain a page-path index (only pages initially)
  - Enforce:
    - creating a nested site fails if page exists at that path
    - creating/updating a page fails if a nested site reserves that path
- **Acceptance**:
  - O(1)/O(log N) checks, no full scans per request

### 9) Network Admin “Tree Editor” UI (Platform)
- **Deliverable**:
  - IdeAI → Sites page (Network Admin):
    - shows tree of nested sites
    - create child site under selected parent (single slug)
    - delete node (with confirmation)
  - Integrate into `wp-admin/network/site-new.php`:
    - add “Create child site under…” UI
    - uses internal safe slug, stores mapping
- **Acceptance**:
  - Creates nested sites without typing `/` into core “Site Address”
  - Duplicates allowed under different parents (`/a/blog/` and `/b/blog/`)

### 10) Nginx rules: standard multisite + deep-path admin support (docs + templates)
- **Deliverable**:
  - Document the nginx rules that support:
    - multi-level `/.../wp-admin/`
    - `/.../wp-content/`, `/.../wp-includes/`, and `*.php`
  - Ensure local mkcert nginx template remains correct
- **Acceptance**:
  - `/a/b/wp-admin/` loads login/admin correctly (no downloads)

### 11) Test harness (CLI) + smoke tests
- **Deliverable**:
  - A `scripts/dev/nested-tree-smoke.sh` that:
    - enables flag
    - creates sample tree via wp-cli/toolkit hooks
    - curls key URLs to verify routing + admin
- **Acceptance**:
  - One-command verification for CI/local

### 12) Documentation & release notes
- **Deliverable**:
  - `docs/nested-tree-multisite.md`:
    - how it works
    - limitations
    - migration strategy
    - safety/rollback
- **Acceptance**:
  - Clear “disable flag to revert to normal multisite” instructions

---

## Commit policy (as requested)
- Exactly **one commit per issue** above.
- Commit messages: `task(<issue-id>): <summary>`
- No mixed changes across issues (keeps history clean).


