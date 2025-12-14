# Useful Tools

Utility scripts for managing nested multisite installations.

## Site Creation

- **`create-perfect-nested-structure.php`** - Create a perfect 3x3x3 nested structure (3 parents, each with 3 children, each with 3 grandchildren)
- **`create-missing-grandchildren.php`** - Ensure all children have exactly 3 grandchildren
- **`create-nested-site.sh`** - Shell script wrapper for creating nested sites

## Site Management

- **`update-all-homepages.php`** - Update all existing sites' homepages to show level information
- **`fix-site-options-urls.php`** - Fix `siteurl` and `home` options for all sites to use correct nested paths
- **`setup-homepage-for-path.php`** - Set up homepage for a specific site path
- **`check-site-path.php`** - Diagnostic tool to check site path routing

## Usage

All PHP scripts can be run via `wp-cli`:

```bash
# From host
docker-compose -f docker-compose.flexible.yml exec wordpress3 wp --allow-root eval-file /var/www/html/wp-content/_usefultools/script-name.php
```

Shell scripts can be run directly from the host.

## Examples

### Create Perfect Structure
```bash
docker-compose -f docker-compose.flexible.yml exec wordpress3 wp --allow-root eval-file /var/www/html/wp-content/_usefultools/create-perfect-nested-structure.php
```

### Fix All Site URLs
```bash
docker-compose -f docker-compose.flexible.yml exec wordpress3 wp --allow-root eval-file /var/www/html/wp-content/_usefultools/fix-site-options-urls.php
```

### Check Site Path
```bash
docker-compose -f docker-compose.flexible.yml exec wordpress3 wp --allow-root eval-file /var/www/html/wp-content/_usefultools/check-site-path.php /parent1/child1/
```
