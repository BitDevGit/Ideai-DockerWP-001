# Database Migration (Domain/URL rewrite)

These scripts help you migrate a WordPress database between environments by rewriting domains/URLs (including many serialized values).

This repo’s **primary local setup** is the flexible stack (`docker-compose.flexible.yml`) with sites:
- `site1.localwp` (service: `wordpress1`, db: `db1`)
- `site2.localwp` (service: `wordpress2`, db: `db2`)
- `site3.localwp` (service: `wordpress3`, db: `db3`)

## Scripts

### `migrate-db-to-aws.sh`

Main migration script that handles:
- Domain replacement (localhost → AWS IP/domain)
- Serialized URL rewrites
- All WordPress tables

**Usage (generic):**
```bash
./scripts/migration/migrate-db-to-aws.sh <old-domain> <new-domain>
```

**On AWS instance:**
```bash
# SSH to instance
ssh -i key.pem ubuntu@13.40.170.117

# Run migration
cd /opt/wordpress-multisite
./scripts/migration/migrate-db-to-aws.sh localhost 13.40.170.117
```

### `migrate-serialized-urls.php`

Advanced script for handling serialized data properly.

**Usage:**
```bash
# Via wp-cli (choose the WP container for the site you’re migrating)
docker compose -f docker-compose.flexible.yml exec wordpress1 wp eval-file scripts/migration/migrate-serialized-urls.php --allow-root

# Or set environment variables
OLD_DOMAIN=site1.localwp NEW_DOMAIN=example.com \
  docker compose -f docker-compose.flexible.yml exec wordpress1 wp eval-file scripts/migration/migrate-serialized-urls.php --allow-root
```

## Migration Process

### 1) Export database (local)

```bash
# Using wp-cli (recommended)
docker compose -f docker-compose.flexible.yml exec wordpress1 wp db export /tmp/local-db.sql --allow-root

# Copy it out of the container
docker cp "$(docker compose -f docker-compose.flexible.yml ps -q wordpress1):/tmp/local-db.sql" ./local-db.sql
```

### 2) Import database (target environment)

Import steps depend on your target (AWS/Lightsail/EC2/etc). The essential part is:
- import SQL into the target DB for that site
- then run the domain rewrite scripts with `<old-domain> -> <new-domain>`

### 3) Run migration script

```bash
./scripts/migration/migrate-db-to-aws.sh <old-domain> <new-domain>
```

### 4) Verify

```bash
# Check site URL
docker compose -f docker-compose.flexible.yml exec wordpress1 wp option get siteurl --allow-root

# Check home URL
docker compose -f docker-compose.flexible.yml exec wordpress1 wp option get home --allow-root
```

## What Gets Updated

- `wp_options` (siteurl, home, and all options)
- `wp_posts` (content, guid)
- `wp_postmeta` (meta values)
- `wp_comments` (content, author URLs)
- `wp_usermeta` (meta values)
- `wp_blogs` (for multisite)
- `wp_site` (for multisite)

## Serialized Data

WordPress stores some data in serialized PHP format. The migration script handles:
- Basic serialized strings
- Serialized arrays
- Serialized objects

For complex cases, use `migrate-serialized-urls.php` which properly unserializes, updates, and re-serializes data.

## Troubleshooting

### URLs still showing old domain

1. Clear cache:
   ```bash
   docker compose -f docker-compose.flexible.yml exec wordpress1 wp cache flush --allow-root
   ```

2. Check .htaccess:
   ```bash
   # May need to update rewrite rules
   ```

3. Verify database:
   ```bash
   docker compose -f docker-compose.flexible.yml exec wordpress1 wp option get siteurl --allow-root
   ```

### Serialized data issues

Use the PHP script for proper handling:
```bash
docker compose -f docker-compose.flexible.yml exec wordpress1 wp eval-file scripts/migration/migrate-serialized-urls.php --allow-root
```

## Best Practices

1. **Backup first**: Always backup before migration
2. **Test locally**: Test migration script on local copy first
3. **Verify**: Check a few URLs after migration
4. **Clear cache**: Clear all caches after migration
5. **Check serialized**: Use PHP script for complex serialized data



