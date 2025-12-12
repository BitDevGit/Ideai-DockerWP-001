# Flexible Multi-Site Development Environment Plan

## 🎯 Requirements

Support for each top-level domain (site1.localwp, site2.localwp):

1. **Normal WordPress Site**
   - Single site installation
   - Example: `site1.localwp`

2. **Subdomain Multisite**
   - One WordPress multisite installation
   - Main site: `site2.localwp`
   - Sub-sites: `sub1.site2.localwp`, `sub2.site2.localwp`
   - One database per top-level domain

3. **Subdirectory Multisite**
   - One WordPress multisite installation
   - Main site: `site3.localwp`
   - Sub-sites: `site3.localwp/sub1`, `site3.localwp/sub2`
   - One database per top-level domain

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Nginx (Port 80)                      │
│  ┌──────────────────┐  ┌──────────────────┐            │
│  │  site1.localwp   │  │  site2.localwp   │            │
│  │  (Normal/MS)     │  │  (Normal/MS)     │            │
│  │                   │  │                   │            │
│  │  *.site1.localwp  │  │  *.site2.localwp  │            │
│  │  (Subdomain MS)   │  │  (Subdomain MS)  │            │
│  └────────┬──────────┘  └────────┬──────────┘            │
└───────────┼──────────────────────┼──────────────────────┘
            │                       │
            │ FastCGI               │ FastCGI
            ▼                       ▼
┌──────────────────┐      ┌──────────────────┐
│  WordPress Site1 │      │  WordPress Site2 │
│  (PHP-FPM)       │      │  (PHP-FPM)       │
└────────┬─────────┘      └────────┬─────────┘
         │                          │
         │ SQL                      │ SQL
         ▼                          ▼
┌──────────────────┐      ┌──────────────────┐
│   Database 1     │      │   Database 2     │
│  (site1.localwp) │      │  (site2.localwp) │
└──────────────────┘      └──────────────────┘
         │                          │
         └──────────┬───────────────┘
                    │
                    ▼
         ┌──────────────────┐
         │  Shared          │
         │  wp-content/     │
         │  (Volume Mount)   │
         └──────────────────┘
```

## 📋 Configuration Strategy

### Per Top-Level Domain:
- **One WordPress container** (can be normal or multisite)
- **One database** (shared tables if multisite)
- **Shared wp-content** (themes, plugins)
- **Separate uploads** per top-level domain

### WordPress Multisite Configuration:
- Configured via `wp-config.php`
- Subdomain: `define('SUBDOMAIN_INSTALL', true);`
- Subdirectory: `define('SUBDOMAIN_INSTALL', false);`
- Wildcard subdomain support in Nginx

## 🔧 Implementation

### Docker Compose Structure

```yaml
services:
  nginx:
    # Routes all domains and subdomains
    
  wordpress1:
    # Site1 (normal or multisite)
    
  wordpress2:
    # Site2 (normal or multisite)
    
  db1:
    # Database for site1.localwp
    
  db2:
    # Database for site2.localwp
    
volumes:
  wp_content_shared:  # Shared themes/plugins
  wp1_data:           # Site1 WordPress core
  wp2_data:           # Site2 WordPress core
  wp1_uploads:        # Site1 uploads
  wp2_uploads:        # Site2 uploads
  db1_data:           # Site1 database
  db2_data:           # Site2 database
```

### Nginx Configuration

- Main domains: `site1.localwp`, `site2.localwp`
- Wildcard subdomains: `*.site1.localwp`, `*.site2.localwp`
- All route to respective WordPress container

### WordPress Configuration

- Each site can be configured as:
  - Normal: Standard wp-config.php
  - Multisite (Subdomain): Add multisite constants
  - Multisite (Subdirectory): Add multisite constants

## 🚀 Usage

### Start Environment
```bash
docker-compose -f docker-compose.flexible.yml up -d
```

### Configure Site 1
- Normal: Use default WordPress installation
- Multisite: Enable multisite via wp-config.php

### Configure Site 2
- Normal: Use default WordPress installation
- Multisite: Enable multisite via wp-config.php

## ✅ Benefits

1. **Flexible**: Each site can be normal or multisite
2. **Isolated**: Separate databases per top-level domain
3. **Shared Content**: Same themes/plugins for all
4. **Realistic**: Mimics production setups
5. **Scalable**: Easy to add more top-level domains


