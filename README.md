# IdeAI Docker WordPress - Local Development Environment

A powerful local WordPress development environment supporting all types of WordPress installations, including advanced nested multisite structures.

## Features

- **3 Separate WordPress Installations** with independent databases
- **Shared `wp-content/`** (themes + plugins) across all sites
- **Separate uploads** per top-level site
- **HTTPS-only locally** (HTTP redirects to HTTPS)
- **Local dashboard** at `https://localhost`
- **Nested Tree Multisite** - Support for deeply nested subdirectory multisite sites (e.g., `/parent/child/grandchild/`)

## Quick Start

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
- **Site 2 (subdomain multisite)**: `https://site2.localwp`
- **Site 3 (subdirectory multisite with nested tree)**: `https://site3.localwp`

If anything is off, run:
```bash
./scripts/dev/doctor.sh
```

## Nested Tree Multisite

Site 3 supports **nested subdirectory multisite** - allowing sites to be nested within other sites:

- `/parent1/` - Level 1 (Parent)
- `/parent1/child1/` - Level 2 (Child)
- `/parent1/child1/grandchild1/` - Level 3 (Grandchild)
- And deeper levels as needed...

### Features

- **Unlimited nesting depth** - Create parent → child → grandchild → great-grandchild structures
- **Sovereign sites** - Each nested site has its own content, admin, and identity
- **Canonical URLs** - Proper routing and URL generation for all nested levels
- **Visual tree viewer** - Network Admin → IdeAI → Sites Tree shows the complete hierarchy
- **Site creation UI** - Network Admin → IdeAI → Create Nested Site

### Enabling Nested Tree

1. Go to **Network Admin → IdeAI → Status**
2. Enable "Nested Tree" feature flag for the network
3. Start creating nested sites!

### Documentation

- `docs/nested-tree-multisite.md` - Complete nested tree documentation
- `docs/nginx-nested-tree-subdir-multisite.md` - Nginx configuration details

## Architecture

### Sites

- **Site 1**: Single WordPress site
- **Site 2**: Subdomain multisite (e.g., `sub1.site2.localwp`)
- **Site 3**: Subdirectory multisite with nested tree support (e.g., `/parent1/child1/`)

### Docker Containers

- `wordpress1`, `wordpress2`, `wordpress3` - WordPress containers
- `mariadb1`, `mariadb2`, `mariadb3` - Database containers
- `nginx` - Reverse proxy with HTTPS
- `redis` - Object cache (optional)

### File Structure

```
wp-content/
├── mu-plugins/
│   └── ideai.wp.plugin.platform/  # Nested tree multisite platform
├── plugins/                       # Shared plugins
├── themes/                        # Shared themes
└── _usefultools/                  # Utility scripts for site management
```

## Network Admin Features

Access via **Network Admin → IdeAI**:

- **Status** - Platform status and feature flags
- **Sites** - Complete site tree with expand/collapse
- **Sites Tree** - Visual pyramid view of nested sites
- **Create Nested Site** - UI for creating new nested sites

## Development

### Creating Nested Sites

**Via UI:**
1. Network Admin → IdeAI → Create Nested Site
2. Enter parent path, site name, and options
3. Site is created with homepage and sample content

**Via Script:**
```bash
docker-compose -f docker-compose.flexible.yml exec wordpress3 wp --allow-root eval-file /var/www/html/wp-content/_usefultools/create-perfect-nested-structure.php
```

### Utility Scripts

Located in `wp-content/_usefultools/`:

- `create-perfect-nested-structure.php` - Create 3x3x3 nested structure
- `create-missing-grandchildren.php` - Ensure all children have 3 grandchildren
- `fix-site-options-urls.php` - Fix siteurl and home options
- `update-all-homepages.php` - Update all site homepages

See `wp-content/_usefultools/README.md` for details.

## Task Management

**📋 Task List:** See `docs/GITHUB_TASKS.md` for comprehensive task list with priorities and semantic tags.

**🐛 GitHub Issues:** Use `docs/GITHUB_ISSUES_TEMPLATE.md` to create GitHub issues from tasks.

**📊 Project Status:** See `docs/PROJECT_STATUS.md` for current project status and completed features.

## Troubleshooting

```bash
# Check system health
./scripts/dev/doctor.sh

# Reset databases
./scripts/dev/reset-databases.sh

# Quick reset
./scripts/dev/quick-reset.sh
```

See `docs/troubleshooting/TROUBLESHOOTING.md` for more help.

## License

This is a development environment for IdeAI WordPress projects.
