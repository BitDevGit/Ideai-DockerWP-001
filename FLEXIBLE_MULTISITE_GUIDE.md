# Flexible Multi-Site WordPress Development Guide

## 🎯 What This Supports

This environment runs **three separate WordPress installs** (each with its own DB) and a **shared `wp-content/`** folder.

Recommended local domains (no `sudo`, no `/etc/hosts` edits needed):

1. **Site 1 (single site example)**: `site1.localhost`
2. **Site 2 (multisite subdomains example)**: `site2.localhost` (+ `sub1.site2.localhost`, `sub2.site2.localhost`, …)
3. **Site 3 (multisite subdirectories example)**: `site3.localhost` (+ `/sub1`, `/sub2`, …)

Optional aliases (requires `/etc/hosts`): `site1.local`, `site2.local`, `site3.local`

---

## 🔧 Modes Supported (per site)

Each top-level domain can be configured as:

1. **Normal WordPress Site**
   - Single site: `site1.localhost`

2. **Subdomain Multisite**
   - Main: `site2.localhost`
   - Subs: `sub1.site2.localhost`, `sub2.site2.localhost`
   - One database (shared tables)

3. **Subdirectory Multisite**
   - Main: `site3.localhost`
   - Subs: `site3.localhost/sub1`, `site3.localhost/sub2`
   - One database (shared tables)

**All share:** wp-content (themes, plugins)  
**Separate:** One database per top-level domain

## 🚀 Quick Start

### 1. Setup Hosts
```bash
./scripts/dev/setup-flexible-multisite.sh
```

### 2. Start Environment
```bash
docker-compose -f docker-compose.flexible.yml up -d
```

### 3. Access Sites
- Dashboard: `http://localhost`
- Site 1: `http://site1.localhost`
- Site 2: `http://site2.localhost`
- Site 3: `http://site3.localhost`

## 📋 Configuration

### Normal WordPress Site
1. Visit `http://site1.localhost`
2. Complete WordPress installation
3. Done - single site

### Enable Subdomain Multisite
1. Install WordPress normally
2. Edit wp-config.php (in container):
   ```bash
   docker-compose -f docker-compose.flexible.yml exec wordpress1 bash
   # Edit /var/www/html/wp-config.php
   ```
3. Add before "That's all":
   ```php
   define('WP_ALLOW_MULTISITE', true);
   ```
4. Go to Tools → Network Setup
5. Choose "Sub-domains"
6. Follow WordPress instructions

### Enable Subdirectory Multisite
Same as above, but choose "Sub-directories" in Network Setup

## 📁 Structure

- **One database per top-level domain**
- **Shared wp-content** (themes, plugins)
- **Separate uploads** per top-level domain
- **Wildcard subdomain support** in Nginx

## ✅ Ready to Use!

See `docs/architecture/FLEXIBLE_MULTISITE_PLAN.md` for details.


