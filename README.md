# IdeAI Docker WordPress (Local Dev + AWS-aligned)

This repo is a clean starting point for a **local WordPress dev environment** that mirrors production patterns:

- **3 separate WordPress installs** + **3 separate MariaDB databases**
- **Shared `wp-content/`** (themes + plugins) across all sites
- **Separate uploads per top-level site**
- **HTTPS-only locally** (HTTP redirects to HTTPS)
- A local dashboard at **`https://localhost`**
- Optional **nested “sub-sub” multisite sites** for subdirectory multisite via the IdeAI MU-plugin

## Quick start (local)

```bash
# 1) Add /etc/hosts entries for .localwp domains (requires sudo)
./scripts/dev/setup-flexible-multisite.sh

# 2) Create trusted local HTTPS certs + nginx HTTPS vhosts (mkcert)
./scripts/dev/setup-https-mkcert.sh

# 3) Start the flexible stack
docker compose -f docker-compose.flexible.yml up -d
```

Open:
- **Dashboard**: `https://localhost`
- **Site 1 (single site)**: `https://site1.localwp`
- **Site 2 (subdomain multisite demo)**: `https://site2.localwp`
- **Site 3 (subdirectory multisite demo)**: `https://site3.localwp`

If anything is off, run:

```bash
./scripts/dev/doctor.sh
```

## Nested tree multisite (sub-sub sites)

Site 3 can run subdirectory multisite like:
- `/sub1/`
- `/sub1/subsub1/`

This is enabled per-network in **Network Admin → IdeAI → Status** (feature flag).

Docs:
- `docs/nested-tree-multisite.md`
- `docs/nginx-nested-tree-subdir-multisite.md`

## What to read next

- **Local dev guide**: `docs/LOCAL_DEV.md`
- **Troubleshooting**: `docs/troubleshooting/TROUBLESHOOTING.md`

