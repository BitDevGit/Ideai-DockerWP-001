# Database Migration Scripts

Scripts for migrating WordPress database from local to AWS, including domain and serialized URL rewrites.

## Scripts

### migrate-db-to-aws.sh

Main migration script that handles:
- Domain replacement (localhost → AWS IP/domain)
- Serialized URL rewrites
- All WordPress tables

**Usage:**
```bash
# From local environment
./scripts/migration/migrate-db-to-aws.sh localhost 13.40.170.117

# Or with domain
./scripts/migration/migrate-db-to-aws.sh localhost yourdomain.com
```

**On AWS instance:**
```bash
# SSH to instance
ssh -i key.pem ubuntu@13.40.170.117

# Run migration
cd /opt/wordpress-multisite
./scripts/migration/migrate-db-to-aws.sh localhost 13.40.170.117
```

### migrate-serialized-urls.php

Advanced script for handling serialized data properly.

**Usage:**
```bash
# Via wp-cli
docker-compose exec wordpress wp eval-file scripts/migration/migrate-serialized-urls.php

# Or set environment variables
OLD_DOMAIN=localhost NEW_DOMAIN=13.40.170.117 \
  docker-compose exec wordpress wp eval-file scripts/migration/migrate-serialized-urls.php
```

## Migration Process

### 1. Export Local Database

```bash
# Export database
docker-compose exec db mysqldump -u wordpress -p wordpress > local-db.sql

# Or using wp-cli
docker-compose exec wordpress wp db export local-db.sql --allow-root
```

### 2. Import to AWS

```bash
# Upload to AWS
scp -i key.pem local-db.sql ubuntu@13.40.170.117:/tmp/

# SSH and import
ssh -i key.pem ubuntu@13.40.170.117
cd /opt/wordpress-multisite
docker-compose exec -T db mysql -u wordpress -p wordpress < /tmp/local-db.sql
```

### 3. Run Migration Script

```bash
# On AWS instance
cd /opt/wordpress-multisite
./scripts/migration/migrate-db-to-aws.sh localhost 13.40.170.117
```

### 4. Verify

```bash
# Check site URL
docker-compose exec wordpress wp option get siteurl --allow-root

# Check home URL
docker-compose exec wordpress wp option get home --allow-root
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
   docker-compose exec wordpress wp cache flush --allow-root
   ```

2. Check .htaccess:
   ```bash
   # May need to update rewrite rules
   ```

3. Verify database:
   ```bash
   docker-compose exec wordpress wp option get siteurl --allow-root
   ```

### Serialized data issues

Use the PHP script for proper handling:
```bash
docker-compose exec wordpress wp eval-file scripts/migration/migrate-serialized-urls.php
```

## Best Practices

1. **Backup first**: Always backup before migration
2. **Test locally**: Test migration script on local copy first
3. **Verify**: Check a few URLs after migration
4. **Clear cache**: Clear all caches after migration
5. **Check serialized**: Use PHP script for complex serialized data


