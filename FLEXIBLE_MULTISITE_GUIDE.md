# Flexible Multi-Site WordPress Development Guide

## 🎯 What This Supports

For each top-level domain (site1.local, site2.local):

1. **Normal WordPress Site**
   - Single site: `site1.local`

2. **Subdomain Multisite**
   - Main: `site1.local`
   - Subs: `subdomain1.site1.local`, `subdomain2.site1.local`
   - One database (shared tables)

3. **Subdirectory Multisite**
   - Main: `site1.local`
   - Subs: `site1.local/subdirectory1`, `site1.local/subdirectory2`
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
- http://site1.local
- http://site2.local

## 📋 Configuration

### Normal WordPress Site
1. Visit http://site1.local
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


