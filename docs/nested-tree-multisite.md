# Nested Tree Multisite (Subdirectory) — IdeAI Platform

This adds support for **hierarchical “sites within sites”** on WordPress **subdirectory multisite** networks, e.g.:

- `/sub1/` is a site
- `/sub1/subsub1/` is a different site
- `/sub1/subsub1/subsubsub1/` also works

## Components

- **MU-plugin**: `ideai.wp.plugin.platform`
  - feature flags (per-network)
  - nested path mapping + resolver (deepest prefix wins)
  - outbound URL rewriting (internal flat ⇄ external nested)
  - canonical redirect policy (avoid flattening / loops)
  - collision prevention (nested site paths vs Pages, strict mode)

- **Plugin**: `ideai.wp.plugin.toolkit`
  - IdeAI wp-admin menu
  - Network Admin UI to create nested child sites
  - Integrates into `wp-admin/network/site-new.php`

Toolkit works without the MU-plugin; advanced nested-tree features require the MU-plugin.

## Enable (per-network)

In **Network Admin**:

- **IdeAI → Status**
  - Enable **Nested tree multisite**
  - Collision mode: **strict**

These are stored as **per-network** options (in the multisite meta table).

## Create nested child sites (UI)

Go to:
- `wp-admin/network/site-new.php`

Use:
- **IdeAI: Create nested child site**
  - Select a **Parent site**
  - Enter **Child slug** (single segment)

This creates:
- an internal WordPress site using a safe flat path (segments joined by `--`)
- a mapping entry for the pretty nested path used for routing and URL generation

## Collision rules (strict)

Strict mode prevents ambiguous URLs:

- If a nested site exists at `/a/b/`, you cannot publish a Page whose permalink path is exactly `/a/b/`.
- If a Page already exists at `/a/b/` on the network’s main site, creating a nested site at `/a/b/` is blocked.

## Nginx requirements

Nginx must rewrite multisite subdirectory core paths for **any depth**:

See:
- `docs/nginx-nested-tree-subdir-multisite.md`

## Smoke test

Run:

- `scripts/dev/nested-tree-smoke.sh`

It enables flags, creates a sample tree, and curls key URLs using `--resolve` (no hosts/DNS required).

## Rollback / Disable safely

To disable nested-tree behavior for a network:

- In **Network Admin → IdeAI → Status**
  - Uncheck **Nested tree multisite**

What happens:
- Routing returns to normal WordPress multisite behavior.
- The mapping table remains but is ignored while disabled.
- No content is deleted.

Optional cleanup (advanced):
- You can remove the mapping rows / drop the mapping table, but only do this if you are sure you won’t re-enable nested-tree.


