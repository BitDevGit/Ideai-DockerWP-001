# Flexible Multi-Site WordPress Development Guide

## 🎯 What This Supports

This environment runs **three separate WordPress installs** (each with its own DB) and a **shared `wp-content/`** folder.

Local domains (aligned with production-style domains):

1. **Site 1 (single site example)**: `site1.localwp`
2. **Site 2 (multisite subdomains example)**: `site2.localwp` (+ `sub1.site2.localwp`, `sub2.site2.localwp`, …)
3. **Site 3 (multisite subdirectories example)**: `site3.localwp` (+ `/sub1`, `/sub2`, …)

---

## 🔧 Modes Supported (per site)

Each top-level domain can be configured as:

1. **Normal WordPress Site**
   - Single site: `site1.localwp`

2. **Subdomain Multisite**
   - Main: `site2.localwp`
   - Subs: `sub1.site2.localwp`, `sub2.site2.localwp`
   - One database (shared tables)

3. **Subdirectory Multisite**
   - Main: `site3.localwp`
   - Subs: `site3.localwp/sub1`, `site3.localwp/sub2`
   - One database (shared tables)

**All share:** wp-content (themes, plugins)  
**Separate:** One database per top-level domain

## 🚀 Quick Start

## 🔐 Optional: Enable HTTPS (recommended)

This repo supports **trusted local HTTPS** via `mkcert` (best UX; matches production behavior more closely).

```bash
./scripts/dev/setup-https-mkcert.sh
```

Once enabled, **HTTP (port 80) redirects to HTTPS (port 443)** so local behavior matches production best practice.

## 🩺 Troubleshooting (start here)

Run:

```bash
./scripts/dev/doctor.sh
```

It will tell you exactly whether Docker is running, whether ports are listening, and whether your `/etc/hosts` entries are missing (with a copy/paste fix).

### 1. Setup Hosts
```bash
./scripts/dev/setup-flexible-multisite.sh
```

### 2. Start Environment
```bash
docker-compose -f docker-compose.flexible.yml up -d
```

### 3. Access Sites
- Dashboard: `https://localhost` (HTTP redirects to HTTPS)
- Site 1: `https://site1.localwp`
- Site 2: `https://site2.localwp`
- Site 3: `https://site3.localwp`

## 📋 Configuration

### Normal WordPress Site
1. Visit `https://site1.localwp`
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

## ⚠️ Subdomain Multisite on `.localwp` (important)

If you enable **multisite with subdomains** (e.g. `sub1.site2.localwp`), note:

- **`/etc/hosts` does NOT support wildcards** (so `*.site2.local` won’t work there).
- **`/etc/hosts` does NOT support wildcards** (so `*.site2.localwp` won’t work there).
- That means you must either:
  - **Add each subdomain you plan to use** to `/etc/hosts` (recommended for “no-bloat” setups), or
  - Set up a **local DNS resolver** (e.g. `dnsmasq`) that can wildcard `*.site2.local` → `127.0.0.1` (more moving parts).

Tip: the local dashboard includes a helper to generate the exact `/etc/hosts` lines + a copy/paste `sudo` command for any subdomains you want to use.

See `docs/architecture/FLEXIBLE_MULTISITE_PLAN.md` for details.


