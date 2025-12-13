# Local Development (Flexible Stack)

This repo runs a local WordPress dev environment with:

- **Three top-level sites** (separate WP core + separate DB per site)
- **Shared `wp-content/`** (themes + plugins)
- **Separate uploads** per site
- **HTTPS-only** (HTTP redirects to HTTPS)
- A dashboard at `https://localhost`

## Sites

- **Site 1 (single site)**: `https://site1.localwp`
- **Site 2 (multisite, subdomains demo)**: `https://site2.localwp`
  - sub-sites: `https://sub1.site2.localwp`, `https://sub2.site2.localwp`, …
- **Site 3 (multisite, subdirectories demo)**: `https://site3.localwp`
  - sub-sites: `https://site3.localwp/sub1/`, `https://site3.localwp/sub2/`, …

All three share:
- `./wp-content/themes`
- `./wp-content/plugins`

## One-time setup

### 1) Hosts entries (.localwp)

`.localwp` requires `/etc/hosts` entries:

```bash
./scripts/dev/setup-flexible-multisite.sh
```

### 2) Trusted local HTTPS (mkcert)

```bash
./scripts/dev/setup-https-mkcert.sh
```

## Start / stop

```bash
docker compose -f docker-compose.flexible.yml up -d
docker compose -f docker-compose.flexible.yml down
```

## Dashboard

Open `https://localhost` for:
- links to all sites
- health/status checks
- host-file helper commands

## Subdomain multisite note (Site 2)

`/etc/hosts` does **not** support wildcard subdomains.

That means each subdomain you want (e.g. `sub1.site2.localwp`) must exist in `/etc/hosts` (the dashboard helper generates copy/paste commands).

## Subdirectory multisite + nested “sub-sub sites” (Site 3)

Standard subdirectory multisite supports:
- `/sub1/`

This repo optionally supports deeper nested sites like:
- `/sub1/subsub1/`
- `/sub1/subsub1/subsubsub1/`

Enable it per-network in **Network Admin → IdeAI → Status**.

Docs:
- `docs/nested-tree-multisite.md`


