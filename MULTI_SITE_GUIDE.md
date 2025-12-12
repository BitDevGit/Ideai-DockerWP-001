# Multi-Site WordPress Development Environment

## 🎯 Overview

Run **2 separate WordPress sites** locally, each with its own database, sharing the same `wp-content` folder (themes, plugins).

## 🏗️ Architecture

- **Site 1**: `site1.localwp` → WordPress 1 → Database 1
- **Site 2**: `site2.localwp` → WordPress 2 → Database 2
- **Shared**: `wp-content/` (themes, plugins) - volume mounted for instant changes

## 🚀 Quick Start

### 1. Setup Hosts Entries

```bash
# Run setup script (requires sudo)
./scripts/dev/setup-multi-site.sh

# Or manually add to /etc/hosts:
# 127.0.0.1  site1.localwp
# 127.0.0.1  site2.localwp
```

### 2. Start Multi-Site Environment

```bash
docker-compose -f docker-compose.multi.yml up -d
```

### 3. Access Sites

- **Site 1**: https://site1.localwp
- **Site 2**: https://site2.localwp

### 4. Complete WordPress Installation

Visit each site and complete the WordPress installation wizard.

## 📋 What's Included

### Containers
- **nginx**: Routes `site1.localwp` and `site2.localwp` to respective WordPress instances
- **wordpress1**: PHP-FPM for Site 1
- **wordpress2**: PHP-FPM for Site 2
- **db1**: Database for Site 1
- **db2**: Database for Site 2

### Volumes
- **wp_content_shared**: Shared wp-content (themes, plugins)
- **wp1_data**: Site 1 WordPress core files
- **wp2_data**: Site 2 WordPress core files
- **wp1_uploads**: Site 1 uploads (separate from shared content)
- **wp2_uploads**: Site 2 uploads (separate from shared content)
- **db1_data**: Site 1 database
- **db2_data**: Site 2 database

## 🔧 Configuration

### Environment Variables

Create `.env.multi` (optional) or use defaults:

```bash
# Site 1 Database
DB1_NAME=wordpress1
DB1_USER=wordpress1
DB1_PASSWORD=site1_password
DB1_ROOT_PASSWORD=root_site1_password

# Site 2 Database
DB2_NAME=wordpress2
DB2_USER=wordpress2
DB2_PASSWORD=site2_password
DB2_ROOT_PASSWORD=root_site2_password
```

### Local Development Override

The `docker-compose.multi.override.yml` automatically:
- Mounts `./wp-content` for instant theme/plugin changes
- Separates uploads per site
- Enables WP_DEBUG

## 📁 File Structure

```
.
├── docker-compose.multi.yml           # Multi-site configuration
├── docker-compose.multi.override.yml  # Local dev overrides
├── nginx/
│   └── conf.d/
│       └── multi-site.conf            # Nginx routing config
├── wp-content/                        # Shared (themes, plugins)
│   ├── themes/
│   ├── plugins/
│   └── uploads/                       # Per-site uploads (volumes)
└── scripts/dev/
    └── setup-multi-site.sh            # Setup script
```

## 🎯 Use Cases

1. **Theme Development**: Test theme on Site 1, verify on Site 2
2. **Plugin Testing**: Test plugin with different configurations
3. **Multi-Client Development**: Different sites for different clients
4. **A/B Testing**: Compare different setups side-by-side
5. **Migration Testing**: Test migrations between sites

## 🔄 Commands

### Start
```bash
docker-compose -f docker-compose.multi.yml up -d
```

### Stop
```bash
docker-compose -f docker-compose.multi.yml down
```

### View Logs
```bash
# All logs
docker-compose -f docker-compose.multi.yml logs -f

# Specific service
docker-compose -f docker-compose.multi.yml logs -f wordpress1
docker-compose -f docker-compose.multi.yml logs -f nginx
```

### Access Containers
```bash
# Site 1 WordPress
docker-compose -f docker-compose.multi.yml exec wordpress1 bash

# Site 2 WordPress
docker-compose -f docker-compose.multi.yml exec wordpress2 bash

# Site 1 Database
docker-compose -f docker-compose.multi.yml exec db1 mysql -u wordpress1 -p

# Site 2 Database
docker-compose -f docker-compose.multi.yml exec db2 mysql -u wordpress2 -p
```

### Check Status
```bash
docker-compose -f docker-compose.multi.yml ps
```

## ✅ Benefits

1. **Isolated Databases**: Each site has its own data
2. **Shared Content**: Same themes/plugins, easy updates
3. **Fast Development**: Volume mounts for instant changes
4. **Realistic Setup**: Mimics production multisite
5. **Easy Scaling**: Add more sites by duplicating services

## ⚠️ Notes

- **Hosts File**: Requires `/etc/hosts` entries (setup script handles this)
- **Port 80**: Both sites use port 80 (Nginx routes by domain)
- **Resource Usage**: 2x containers = more memory (~400-600 MB total)
- **Uploads**: Separated per site to avoid conflicts
- **wp-content**: Shared for themes/plugins, but uploads are separate

## 🐛 Troubleshooting

### Sites not accessible

1. **Check hosts entries:**
   ```bash
   cat /etc/hosts | grep site
   ```

2. **Check containers are running:**
   ```bash
   docker-compose -f docker-compose.multi.yml ps
   ```

3. **Check Nginx logs:**
   ```bash
   docker-compose -f docker-compose.multi.yml logs nginx
   ```

### Database connection errors

1. **Check database containers:**
   ```bash
   docker-compose -f docker-compose.multi.yml logs db1
   docker-compose -f docker-compose.multi.yml logs db2
   ```

2. **Verify environment variables** match in docker-compose.multi.yml

### wp-content not updating

1. **Check volume mount:**
   ```bash
   docker-compose -f docker-compose.multi.yml exec wordpress1 ls -la /var/www/html/wp-content/themes/
   ```

2. **Restart containers:**
   ```bash
   docker-compose -f docker-compose.multi.yml restart wordpress1 wordpress2
   ```

## 📚 Related Documentation

- **[MULTI_SITE_PLAN.md](docs/architecture/MULTI_SITE_PLAN.md)** - Detailed architecture plan
- **[DOCKER_WALKTHROUGH.md](DOCKER_WALKTHROUGH.md)** - Docker architecture explanation
- **[README.md](README.md)** - Main project documentation

## 🚀 Next Steps

1. Run setup script to add hosts entries
2. Start multi-site environment
3. Complete WordPress installation on both sites
4. Activate test theme/plugin on both sites
5. Start developing!

---

**Ready to use!** 🎉


