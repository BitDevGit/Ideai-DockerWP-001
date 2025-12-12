# Testing Guide - Theme, Plugin & DB Migration

## Overview

This guide helps you test:
1. ✅ Test theme appears locally
2. ✅ Test plugin appears locally
3. ✅ "Hello Cursor!" message on homepage
4. ✅ Theme/plugin appear in AWS after deployment
5. ✅ Database migration with domain/URL rewrites

## Test Files Created

### Theme: `test-cursor-theme`
- **Location**: `wp-content/themes/test-cursor-theme/`
- **Features**:
  - Displays "Hello Cursor!" on homepage
  - Confirms wp-content deployment
  - Modern gradient design

### Plugin: `test-cursor-plugin`
- **Location**: `wp-content/plugins/test-cursor-plugin/`
- **Features**:
  - Admin notice when active
  - Dashboard widget
  - Footer text

## Local Testing

### 1. Start Services

```bash
# Start Docker containers
docker-compose up -d

# Check status
docker-compose ps
```

### 2. Access WordPress

1. Open: http://localhost
2. Complete WordPress installation if needed
3. Login to admin: http://localhost/wp-admin

### 3. Activate Theme

1. Go to **Appearance → Themes**
2. Find "Test Cursor Theme"
3. Click **Activate**
4. Visit homepage - you should see "Hello Cursor!" message

### 4. Activate Plugin

1. Go to **Plugins → Installed Plugins**
2. Find "Test Cursor Plugin"
3. Click **Activate**
4. You should see:
   - Admin notice: "Test Cursor Plugin is active! ✅"
   - Dashboard widget: "Test Cursor Plugin Status"
   - Footer text: "Test Cursor Plugin Active ✅"

### 5. Verify Homepage

Visit http://localhost - you should see:
- 👋 Hello Cursor! heading
- List confirming:
  - ✅ Theme is loaded from wp-content
  - ✅ Database migration worked
  - ✅ Domain rewrite successful
  - ✅ Serialized URL rewrite successful

## AWS Deployment Testing

### 1. Build Production Image

```bash
# Build with wp-content included
./scripts/build/build-with-content.sh ./wp-content

# Verify image was created
docker images | grep wordpress-multisite
```

### 2. Prepare Deployment

```bash
# Create deployment package
./scripts/deployment/prepare-deployment.sh
```

### 3. Deploy to AWS

```bash
# Deploy to Lightsail instance
./scripts/deployment/deploy-to-instance.sh \
  wordpress-multisite \
  13.40.170.117 \
  ubuntu \
  /path/to/key.pem
```

### 4. Migrate Database (if migrating from local)

```bash
# Export local database
docker-compose exec wordpress wp db export local-db.sql --allow-root

# Upload to AWS
scp -i key.pem local-db.sql ubuntu@13.40.170.117:/tmp/

# SSH to AWS
ssh -i key.pem ubuntu@13.40.170.117

# Import database
cd /opt/wordpress-multisite
docker-compose exec -T db mysql -u wordpress -p wordpress < /tmp/local-db.sql

# Run migration script
./scripts/migration/migrate-db-to-aws.sh localhost 13.40.170.117
```

### 5. Verify on AWS

1. **Check theme exists:**
   ```bash
   # SSH to instance
   docker-compose exec wordpress ls -la /var/www/html/wp-content/themes/
   # Should see: test-cursor-theme
   ```

2. **Check plugin exists:**
   ```bash
   docker-compose exec wordpress ls -la /var/www/html/wp-content/plugins/
   # Should see: test-cursor-plugin
   ```

3. **Access WordPress Admin:**
   - Go to: http://13.40.170.117/wp-admin
   - Activate theme and plugin
   - Check homepage for "Hello Cursor!" message

4. **Verify URLs:**
   ```bash
   # Check site URL
   docker-compose exec wordpress wp option get siteurl --allow-root
   # Should show: http://13.40.170.117
   ```

## Database Migration

### What Gets Migrated

The migration script updates:
- Site URL (localhost → AWS IP/domain)
- Home URL
- Post content URLs
- Post GUIDs
- Post meta URLs
- Comment URLs
- User meta URLs
- Serialized data (where possible)

### Migration Scripts

1. **migrate-db-to-aws.sh** - Main migration script
   ```bash
   ./scripts/migration/migrate-db-to-aws.sh localhost 13.40.170.117
   ```

2. **migrate-serialized-urls.php** - Advanced serialized data handling
   ```bash
   docker-compose exec wordpress wp eval-file scripts/migration/migrate-serialized-urls.php
   ```

### Migration Process

1. **Export local database:**
   ```bash
   docker-compose exec wordpress wp db export local-db.sql --allow-root
   ```

2. **Upload to AWS:**
   ```bash
   scp -i key.pem local-db.sql ubuntu@13.40.170.117:/tmp/
   ```

3. **Import on AWS:**
   ```bash
   ssh -i key.pem ubuntu@13.40.170.117
   cd /opt/wordpress-multisite
   docker-compose exec -T db mysql -u wordpress -p wordpress < /tmp/local-db.sql
   ```

4. **Run migration:**
   ```bash
   ./scripts/migration/migrate-db-to-aws.sh localhost 13.40.170.117
   ```

5. **Verify:**
   ```bash
   docker-compose exec wordpress wp option get siteurl --allow-root
   docker-compose exec wordpress wp option get home --allow-root
   ```

## Expected Results

### Local
- ✅ Theme appears in Appearance → Themes
- ✅ Plugin appears in Plugins → Installed Plugins
- ✅ "Hello Cursor!" message on homepage
- ✅ Admin notice when plugin active
- ✅ Dashboard widget visible

### AWS
- ✅ Theme included in Docker image
- ✅ Plugin included in Docker image
- ✅ Theme/plugin appear in WordPress admin
- ✅ "Hello Cursor!" message on homepage
- ✅ URLs updated from localhost to AWS IP
- ✅ Serialized data properly migrated

## Troubleshooting

### Theme/Plugin Not Appearing

**Local:**
```bash
# Check volume mount
docker-compose exec wordpress ls -la /var/www/html/wp-content/themes/
docker-compose exec wordpress ls -la /var/www/html/wp-content/plugins/

# Restart container
docker-compose restart wordpress
```

**AWS:**
```bash
# Check image includes wp-content
docker exec wordpress-multisite-wordpress-1 ls -la /var/www/html/wp-content/themes/

# Rebuild if needed
./scripts/build/build-with-content.sh ./wp-content
```

### "Hello Cursor!" Not Showing

1. Check theme is activated
2. Check homepage is set correctly
3. Clear cache: `docker-compose exec wordpress wp cache flush --allow-root`
4. Check theme files exist

### Migration Issues

1. **URLs still showing old domain:**
   ```bash
   # Re-run migration
   ./scripts/migration/migrate-db-to-aws.sh localhost 13.40.170.117
   
   # Clear cache
   docker-compose exec wordpress wp cache flush --allow-root
   ```

2. **Serialized data issues:**
   ```bash
   # Use PHP script
   docker-compose exec wordpress wp eval-file scripts/migration/migrate-serialized-urls.php
   ```

## Next Steps

After successful testing:
1. Remove test theme/plugin (or keep for reference)
2. Add your real plugins/themes
3. Deploy to production
4. Update domain when ready



