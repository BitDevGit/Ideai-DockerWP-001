# Complete Setup Guide: Nested Tree Multisite

This guide walks through the complete setup process step-by-step, so you can learn, replicate, and iterate.

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Initial Setup](#initial-setup)
3. [WordPress Multisite Installation](#wordpress-multisite-installation)
4. [Enable Nested Tree Feature](#enable-nested-tree-feature)
5. [Create Your First Nested Site](#create-your-first-nested-site)
6. [Testing & Verification](#testing--verification)
7. [Troubleshooting](#troubleshooting)
8. [Iteration Workflow](#iteration-workflow)

---

## Prerequisites

### What You Need
- Docker and Docker Compose installed
- `mkcert` installed (for HTTPS certificates)
- Access to `/etc/hosts` (for `.localwp` domains)

### Verify Prerequisites
```bash
# Check Docker
docker --version
docker-compose --version

# Check mkcert
mkcert --version

# Check hosts file access
ls -la /etc/hosts
```

---

## Initial Setup

### Step 1: Clone/Setup Repository
```bash
cd /path/to/Ideai-DockerWP-001
```

### Step 2: Set Up HTTPS Certificates
```bash
./scripts/dev/setup-https-mkcert.sh
```
**What this does:**
- Generates trusted local SSL certificates
- Creates Nginx HTTPS configuration
- Enables HTTPS for all sites

### Step 3: Add Local Domains to /etc/hosts
```bash
./scripts/dev/setup-flexible-multisite.sh
```
**What this does:**
- Adds `site1.localwp`, `site2.localwp`, `site3.localwp` to `/etc/hosts`
- Flushes DNS cache

### Step 4: Start Containers
```bash
docker-compose -f docker-compose.flexible.yml up -d
```
**What this does:**
- Starts all WordPress, database, and Nginx containers
- Creates Docker volumes for persistent data

### Step 5: Verify Containers
```bash
docker-compose -f docker-compose.flexible.yml ps
```
**Expected output:** All containers should show "Up" status

---

## WordPress Multisite Installation

### Step 1: Check Current State
```bash
./scripts/dev/setup-wordpress-multisite.sh
```
This script checks if WordPress is already installed and provides instructions.

### Step 2: Install WordPress (if needed)

**Option A: Via Browser (Recommended)**
1. Navigate to: `https://site3.localwp/wp-admin/install.php`
2. Fill in:
   - **Site Title:** Site 3: Subdirectory Multisite
   - **Username:** admin
   - **Password:** admin (or your choice)
   - **Email:** admin@site3.localwp
3. Click "Install WordPress"

**Option B: Check if Already Installed**
```bash
curl -k https://site3.localwp/wp-admin/install.php | grep -i "already installed"
```

### Step 3: Enable Multisite

After WordPress installation:

1. **Add to wp-config.php:**
   ```php
   define('WP_ALLOW_MULTISITE', true);
   ```

2. **Or WordPress will prompt you:**
   - Go to Tools → Network Setup
   - Choose "Sub-directories" (not subdomains)
   - Follow the instructions to update `.htaccess` and `wp-config.php`

3. **Verify Multisite:**
   ```bash
   docker-compose -f docker-compose.flexible.yml exec -T wordpress3 grep -i "MULTISITE.*true" /var/www/html/wp-config.php
   ```

### Step 4: Verify Installation
```bash
# Check database
docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 \
  -e "SELECT blog_id, domain, path FROM wp_blogs WHERE site_id = 1;"
```

**Expected:** At least one site (blog_id = 1) with path = `/`

---

## Enable Nested Tree Feature

### Step 1: Navigate to IdeAI Status Page
```
https://site3.localwp/wp-admin/network/admin.php?page=ideai-status
```

### Step 2: Enable Feature
1. Find "Nested Tree Multisite" card
2. Toggle the switch to "Enabled"
3. Click "Save"
4. Verify you see "Saved." message

### Step 3: Verify Feature is Enabled
```bash
docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 \
  -e "SELECT meta_value FROM wp_sitemeta WHERE meta_key = 'ideai_nested_tree_enabled';"
```

**Expected:** `1` (enabled)

---

## Create Your First Nested Site

### Step 1: Navigate to Site Creation Page
```
https://site3.localwp/wp-admin/network/site-new.php
```

### Step 2: Use Nested Site Fields

You should see "IdeAI: Nested Site Options" section at the top:

1. **Parent site dropdown:**
   - Default: "Not a nested site (standard WordPress site)"
   - Select: "Network root (/)" or an existing site

2. **When parent is selected:**
   - Grayed-out prefix appears showing parent path
   - Child slug input field appears
   - Full URL preview updates in real-time

3. **Enter child slug:**
   - Example: `test123`
   - Preview shows: `https://site3.localwp/test123/`

4. **Click "Add Site"**

### Step 3: Verify Site Creation

**Check database:**
```bash
docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 \
  -e "SELECT blog_id, domain, path FROM wp_blogs WHERE path LIKE '%test123%';"
```

**Expected:** Path should be `/test123/` (not `/test123--something/`)

**Check nested tree mapping:**
```bash
docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 \
  -e "SELECT blog_id, path FROM wp_ideai_nested_tree_paths WHERE path LIKE '%test123%';"
```

**Expected:** Same path `/test123/`

**Test accessibility:**
```bash
curl -k -I https://site3.localwp/test123/wp-admin/
```

**Expected:** HTTP 200 or 302 (redirect to login)

---

## Testing & Verification

### Automated Tests

**Run all tests:**
```bash
./tests/test-all.sh
```

**Individual tests:**
```bash
# Test URL generation
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 php tests/test-nested-urls.php

# Test database paths
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 php tests/test-nested-paths.php
```

### Manual Verification Checklist

- [ ] Site accessible at nested path
- [ ] Admin URL uses correct nested path
- [ ] No `--` in any URLs
- [ ] All links within admin point to correct site
- [ ] Database path matches nested path
- [ ] Nested tree mapping exists

### Browser MCP Testing

Use Cursor's browser MCP to:
1. Navigate to site creation page
2. Interact with nested site fields
3. Create a test site
4. Verify URLs are correct

See `TESTING_WORKFLOW.md` for detailed browser testing steps.

---

## Troubleshooting

### Issue: Site not accessible
```bash
# Check containers
docker-compose -f docker-compose.flexible.yml ps

# Check Nginx
docker-compose -f docker-compose.flexible.yml logs nginx | tail -20

# Check WordPress
docker-compose -f docker-compose.flexible.yml logs wordpress3 | tail -20
```

### Issue: WordPress not installed
```bash
# Check database
docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 \
  -e "SHOW TABLES;"

# If empty, visit: https://site3.localwp/wp-admin/install.php
```

### Issue: Nested tree feature not saving
```bash
# Check network ID
docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 \
  -e "SELECT * FROM wp_sitemeta WHERE meta_key LIKE 'ideai_%';"

# Check PHP errors
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 tail -50 /var/log/php-fpm/error.log
```

### Issue: URLs contain `--`
```bash
# Check database paths
docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 \
  -e "SELECT blog_id, path FROM wp_blogs WHERE path LIKE '%--%';"

# If found, paths need to be updated to use `/` instead
```

### Issue: Browser MCP not working
- Ensure WordPress is accessible: `curl -k https://site3.localwp/`
- Check SSL certificates: `ls -la nginx/certs/`
- Verify `/etc/hosts` entries: `cat /etc/hosts | grep localwp`

---

## Iteration Workflow

### Quick Reset
```bash
./scripts/dev/quick-reset.sh
```
This does:
1. Reset databases
2. Setup test sites
3. Run tests

### Make Code Changes

1. **Edit code:**
   ```bash
   # Edit MU-plugin files
   vim wp-content/mu-plugins/ideai.wp.plugin.platform/includes/admin-ui.php
   ```

2. **Test changes:**
   ```bash
   # Check syntax
   docker-compose -f docker-compose.flexible.yml exec -T wordpress3 \
     php -l /var/www/html/wp-content/mu-plugins/ideai.wp.plugin.platform/includes/admin-ui.php
   ```

3. **Verify in browser:**
   - Navigate to site creation page
   - Test nested site creation
   - Verify URLs are correct

4. **Run automated tests:**
   ```bash
   ./tests/test-all.sh
   ```

### Semantic Naming for Test Sites

Use clear, hierarchical names:
- **Level 1:** `/parent1/`, `/parent2/`
- **Level 2:** `/parent1/child1/`, `/parent1/child2/`
- **Level 3:** `/parent1/child1/grandchild1/`

This makes testing and debugging easier.

---

## Key Files Reference

### Configuration
- `docker-compose.flexible.yml` - Container definitions
- `nginx/conf.d/flexible-multisite.conf` - Nginx routing
- `wp-config.php` - WordPress configuration (auto-generated)

### Code
- `wp-content/mu-plugins/ideai.wp.plugin.platform/` - Main MU-plugin
  - `includes/admin-ui.php` - UI and site creation
  - `includes/nested-tree.php` - Database and mapping
  - `includes/nested-tree-routing.php` - Request routing
  - `includes/nested-tree-urls.php` - URL rewriting

### Scripts
- `scripts/dev/reset-databases.sh` - Clean reset
- `scripts/dev/setup-test-sites.sh` - Auto setup
- `scripts/dev/create-nested-site.sh` - CLI site creation
- `scripts/dev/quick-reset.sh` - Full reset → test

### Tests
- `tests/test-all.sh` - Run all tests
- `tests/test-nested-urls.php` - URL generation test
- `tests/test-nested-paths.php` - Path verification

---

## Success Criteria

When everything is working correctly:

✅ **Database:**
- All paths use `/` (never `--`)
- Nested tree mappings exist for nested sites
- `wp_blogs.path` matches nested path

✅ **URLs:**
- `home_url()`, `admin_url()`, `site_url()` generate correct paths
- No `--` in any generated URLs
- All links point to correct nested site

✅ **UI:**
- Nested site fields appear when feature enabled
- Parent dropdown works correctly
- Full URL preview updates in real-time
- Site creation works seamlessly

✅ **Accessibility:**
- Sites accessible at nested paths
- Admin URLs work correctly
- No 404 errors for nested sites

---

## Next Steps

1. **Create test sites** with semantic naming
2. **Test deeply nested sites** (3+ levels)
3. **Verify collision prevention** (Pages vs nested sites)
4. **Test URL rewriting** in various scenarios
5. **Iterate and improve** based on testing

---

## Learning Resources

- `TESTING_WORKFLOW.md` - Detailed testing workflow
- `TESTING_STATUS.md` - Current status and summary
- `tests/README.md` - Test suite documentation
- `REFACTOR_PLAN.md` - Architecture and design decisions

---

**Remember:** This is a learning and iteration process. Document what works, what doesn't, and how you fix issues. This makes future iterations faster and more reliable.

