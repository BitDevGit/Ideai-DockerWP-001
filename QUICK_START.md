# Quick Start Guide

## 🚀 First Time Setup (5 minutes)

```bash
# 1. Setup HTTPS
./scripts/dev/setup-https-mkcert.sh

# 2. Add domains to /etc/hosts
./scripts/dev/setup-flexible-multisite.sh

# 3. Start containers
docker-compose -f docker-compose.flexible.yml up -d

# 4. Check WordPress setup
./scripts/dev/setup-wordpress-multisite.sh
```

## 📝 WordPress Installation

1. Visit: `https://site3.localwp/wp-admin/install.php`
2. Install WordPress (admin/admin)
3. Enable multisite (Sub-directories)
4. Enable nested tree: `Network Admin → IdeAI → Status`

## ✅ Verify Everything Works

```bash
# Run all tests
./tests/test-all.sh

# Or quick reset (flush → setup → test)
./scripts/dev/quick-reset.sh
```

## 🔄 Daily Workflow

```bash
# Make code changes
vim wp-content/mu-plugins/ideai.wp.plugin.platform/includes/admin-ui.php

# Test changes
./tests/test-all.sh

# Create test site
./scripts/dev/create-nested-site.sh /parent1/ test123 "Test Site"
```

## 📚 Full Documentation

- **Complete Setup:** `docs/COMPLETE_SETUP_GUIDE.md`
- **Testing Workflow:** `TESTING_WORKFLOW.md`
- **Troubleshooting:** `docs/troubleshooting/TROUBLESHOOTING.md`

## 🎯 Key URLs

- Admin: `https://site3.localwp/wp-admin/`
- Create Site: `https://site3.localwp/wp-admin/network/site-new.php`
- IdeAI Status: `https://site3.localwp/wp-admin/network/admin.php?page=ideai-status`

## 🐛 Quick Fixes

```bash
# Reset everything
./scripts/dev/reset-databases.sh

# Check containers
docker-compose -f docker-compose.flexible.yml ps

# Check logs
docker-compose -f docker-compose.flexible.yml logs wordpress3 | tail -20
```


