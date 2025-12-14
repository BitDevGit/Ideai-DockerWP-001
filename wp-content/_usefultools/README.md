# Useful Tools

This folder contains utility scripts for managing nested multisite installations.

## Scripts

### Site Creation
- **`create-intermediate-sites.php`** - Create missing intermediate level sites (children) for nested tree
- **`create-nested-site.sh`** - Shell script wrapper for creating nested sites

### Site Management
- **`update-all-homepages.php`** - Update all existing sites' homepages to show level information
- **`check-site-path.php`** - Diagnostic tool to check site path routing (usage: `wp eval-file wp-content/_usefultools/check-site-path.php [path]`)
- **`diagnose-p1c2g2.php`** - Diagnose routing issues for a specific path (e.g., `/p1c2g2/`)
- **`fix-temp-slug-paths.php`** - Fix all sites that still have temporary slugs (e.g., `p1c2g2`) instead of nested paths (e.g., `/parent1/child2/grandchild2/`)
- **`fix-single-temp-slug.php`** - Fix a single site by blog_id (usage: `wp eval-file wp-content/_usefultools/fix-single-temp-slug.php <blog_id>`)

## Usage

All PHP scripts can be run via `wp-cli`:

```bash
# From host
docker-compose -f docker-compose.flexible.yml exec wordpress3 wp --allow-root eval-file /var/www/html/wp-content/_usefultools/script-name.php

# Or copy to wp-content first (since scripts/ isn't mounted)
cp wp-content/_usefultools/script-name.php wp-content/script-name.php
docker-compose -f docker-compose.flexible.yml exec wordpress3 wp --allow-root eval-file /var/www/html/wp-content/script-name.php
rm wp-content/script-name.php
```

Shell scripts can be run directly from the host.

