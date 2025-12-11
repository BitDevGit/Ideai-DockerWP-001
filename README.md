# WordPress Multisite on Docker - AWS Lightsail

A production-ready, secure, and performant WordPress multisite setup using Docker, designed for easy deployment to AWS Lightsail with CDN support, scaling capabilities, and CI/CD integration.

## 🚀 Quick Start

```bash
# 1. Start Docker Desktop
open -a Docker  # macOS
# Wait for Docker to be running

# 2. Start services
docker-compose up -d

# 3. Access WordPress
# http://localhost
```

**First time setup:**
1. Visit http://localhost
2. Complete WordPress installation
3. Log in at http://localhost/wp-admin

## ✨ Features

- ✅ **WordPress Multisite** - Fully configured for subdomain or subdirectory installations
- ✅ **Docker Compose** - Multi-container setup (Nginx, WordPress FPM, MariaDB)
- ✅ **Local Development** - Volume mounts for instant theme/plugin changes
- ✅ **Production Builds** - Docker images with wp-content included
- ✅ **Security Hardened** - SSL/TLS ready, security headers, rate limiting
- ✅ **Performance Optimized** - OPcache, Gzip compression, static file caching
- ✅ **AWS Lightsail Ready** - Automated deployment scripts
- ✅ **Test Theme/Plugin** - Included for verification

## 📋 Current Status

**Local:** ✅ Running at http://localhost  
**AWS:** ✅ Instance `wordpress-multisite` (London - eu-west-2) at http://13.40.170.117

### Container Status
- ✅ **nginx**: Web server (port 80)
- ✅ **wordpress**: PHP-FPM application (port 9000)
- ✅ **db**: MariaDB database (port 3306)

## 📁 Project Structure

```
.
├── README.md                      # This file
├── QUICK_START.md                 # Quick start guide
├── DOCKER_WALKTHROUGH.md          # Detailed Docker architecture
├── STATUS.md                      # Current project status
├── TESTING_GUIDE.md               # Testing theme/plugin/migration
├── URLS.md                        # Quick URL reference
│
├── docker-compose.yml             # Main Docker Compose config
├── docker-compose.override.yml    # Local dev overrides (volume mounts)
├── .env                           # Environment variables (create from .env.example)
│
├── docs/                          # Documentation
│   ├── deployment/                # Deployment guides
│   │   ├── QUICKSTART.md          # Quick deployment guide
│   │   ├── DEPLOYMENT.md          # Full deployment guide
│   │   ├── LIGHTSAIL.md          # AWS Lightsail setup
│   │   ├── IAM_PERMISSIONS.md     # AWS IAM permissions
│   │   └── WP_CONTENT_WORKFLOW.md # wp-content workflow
│   ├── architecture/              # Architecture docs
│   │   ├── SCALING.md             # Scaling strategies
│   │   ├── WP_CONTENT_STRATEGY.md # wp-content strategy
│   │   └── WP_CONTENT_IMPLEMENTATION.md # wp-content implementation
│   └── troubleshooting/           # Troubleshooting
│       └── TROUBLESHOOTING.md     # Common issues
│
├── nginx/                         # Nginx configuration
│   ├── nginx.conf                 # Main Nginx config
│   └── conf.d/
│       ├── default.conf           # Local development config
│       └── default.conf.production # Production config
│
├── wordpress/                     # WordPress configuration
│   ├── Dockerfile                 # Base WordPress image
│   ├── Dockerfile.production      # Production build with wp-content
│   ├── php.ini                    # PHP configuration
│   └── uploads.ini                # Upload settings
│
├── wp-content/                    # WordPress content (themes, plugins)
│   ├── themes/
│   │   └── test-cursor-theme/     # Test theme with "Hello Cursor!"
│   ├── plugins/
│   │   └── test-cursor-plugin/     # Test plugin
│   └── uploads/                   # User uploads (volume mounted)
│
└── scripts/                        # Automation scripts
    ├── build/
    │   └── build-with-content.sh  # Build production image
    ├── deployment/                 # Deployment scripts
    ├── dev/
    │   ├── setup-wp-content.sh     # Setup wp-content for local dev
    │   └── explain-docker.sh       # Docker explanation script
    ├── migration/                  # Database migration
    │   ├── migrate-db-to-aws.sh   # Domain/URL migration
    │   └── migrate-serialized-urls.php # Serialized data migration
    ├── backup/                     # Backup/restore
    └── maintenance/                # Health checks, SSL
```

## 🏗️ Architecture

```
Browser → Nginx (Port 80) → WordPress PHP-FPM (Port 9000) → MariaDB (Port 3306)
                ↓
         (Static files served directly)
```

**Why this architecture?**
- **Nginx**: Fast static file serving, reverse proxy
- **PHP-FPM**: Efficient PHP processing, separate from web server
- **MariaDB**: Lightweight database, optimized for small instances
- **Volumes**: Persistent storage for database and uploads
- **Network**: Isolated Docker network for container communication

See [DOCKER_WALKTHROUGH.md](DOCKER_WALKTHROUGH.md) for detailed explanation.

## 📚 Documentation

### Quick References
- **[QUICK_START.md](QUICK_START.md)** - Get started in 5 minutes
- **[URLS.md](URLS.md)** - Quick URL reference
- **[STATUS.md](STATUS.md)** - Current project status

### Detailed Guides
- **[DOCKER_WALKTHROUGH.md](DOCKER_WALKTHROUGH.md)** - Complete Docker architecture walkthrough
- **[TESTING_GUIDE.md](TESTING_GUIDE.md)** - Testing theme, plugin, and DB migration

### Deployment
- **[docs/deployment/QUICKSTART.md](docs/deployment/QUICKSTART.md)** - Quick deployment guide
- **[docs/deployment/DEPLOYMENT.md](docs/deployment/DEPLOYMENT.md)** - Full deployment instructions
- **[docs/deployment/LIGHTSAIL.md](docs/deployment/LIGHTSAIL.md)** - AWS Lightsail setup
- **[docs/deployment/IAM_PERMISSIONS.md](docs/deployment/IAM_PERMISSIONS.md)** - Required AWS permissions
- **[docs/deployment/WP_CONTENT_WORKFLOW.md](docs/deployment/WP_CONTENT_WORKFLOW.md)** - wp-content workflow

### Architecture
- **[docs/architecture/SCALING.md](docs/architecture/SCALING.md)** - Scaling strategies
- **[docs/architecture/WP_CONTENT_STRATEGY.md](docs/architecture/WP_CONTENT_STRATEGY.md)** - wp-content strategy
- **[docs/architecture/WP_CONTENT_IMPLEMENTATION.md](docs/architecture/WP_CONTENT_IMPLEMENTATION.md)** - wp-content implementation

### Troubleshooting
- **[docs/troubleshooting/TROUBLESHOOTING.md](docs/troubleshooting/TROUBLESHOOTING.md)** - Common issues and solutions

## 🛠️ Development

### Local Development

**Start services:**
```bash
docker-compose up -d
```

**Access:**
- Site: http://localhost
- Admin: http://localhost/wp-admin

**wp-content:**
- Volume mounted for instant changes
- Edit themes/plugins directly in `wp-content/`
- Changes appear immediately (no rebuild needed)

### WordPress Content Setup

```bash
# Setup wp-content for local development
./scripts/dev/setup-wp-content.sh

# Or symlink to shared repository
./scripts/dev/setup-wp-content.sh /path/to/wp-content-repo
```

**Local Development:**
- wp-content is volume mounted (instant changes)
- No rebuilds needed during development
- Uploads use separate Docker volume

See [docs/deployment/WP_CONTENT_WORKFLOW.md](docs/deployment/WP_CONTENT_WORKFLOW.md) for details.

### Make Commands

```bash
make up          # Start all services
make down        # Stop all services
make logs        # View logs
make restart     # Restart services
make clean       # Remove containers and volumes
make backup      # Create backup
```

### Manual Commands

```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f

# Access WordPress container
docker-compose exec wordpress bash

# Access database
docker-compose exec db mysql -u wordpress -p
```

## 🚢 Deployment

### AWS Lightsail Deployment

```bash
# Deploy to existing instance
./scripts/deployment/deploy-to-instance.sh \
  wordpress-multisite \
  13.40.170.117 \
  ubuntu \
  /path/to/ssh-key.pem
```

See [docs/deployment/DEPLOYMENT.md](docs/deployment/DEPLOYMENT.md) for detailed instructions.

### Production Build

```bash
# Build with wp-content included
./scripts/build/build-with-content.sh ./wp-content
```

## 🔒 Security

- SSL/TLS encryption (TLS 1.2+)
- Security headers (HSTS, X-Frame-Options, etc.)
- Rate limiting on Nginx (10 req/s)
- Database password protection
- File access restrictions

## 📦 Environment Variables

Create `.env` from `.env.example`:

```bash
cp .env.example .env
```

Key variables:
- `DB_PASSWORD` - Database password
- `DB_ROOT_PASSWORD` - Database root password
- `DB_NAME` - Database name (default: wordpress)
- `DB_USER` - Database user (default: wordpress)
- `WP_DEBUG` - Enable WordPress debug mode (0 or 1)

## 🔄 Backup & Restore

### Create Backup
```bash
./scripts/backup/backup.sh
```

### Restore from Backup
```bash
./scripts/backup/restore.sh 20240101_120000
```

## 📈 Monitoring

### Health Checks
```bash
# Check container status
docker-compose ps

# Check logs
docker-compose logs wordpress
docker-compose logs nginx
docker-compose logs db

# Database health
docker-compose exec db mysqladmin ping -h localhost
```

## 🐛 Troubleshooting

See [docs/troubleshooting/TROUBLESHOOTING.md](docs/troubleshooting/TROUBLESHOOTING.md) for common issues.

**Quick fixes:**
```bash
# Restart services
docker-compose restart

# Rebuild containers
docker-compose up -d --build

# Check logs
docker-compose logs --tail=50
```

## 🧪 Testing

### Test Theme & Plugin
- Theme: `test-cursor-theme` - Shows "Hello Cursor!" on homepage
- Plugin: `test-cursor-plugin` - Admin notices and dashboard widget

See [TESTING_GUIDE.md](TESTING_GUIDE.md) for complete testing instructions.

### Database Migration
Scripts for migrating from local to AWS with domain/URL rewrites:
- `scripts/migration/migrate-db-to-aws.sh`
- `scripts/migration/migrate-serialized-urls.php`

## 📝 License

This project is provided as-is for your use. WordPress is licensed under GPL v2 or later.

## 🔗 Additional Resources

- [WordPress Multisite Documentation](https://wordpress.org/support/article/create-a-network/)
- [Docker Documentation](https://docs.docker.com/)
- [AWS Lightsail Documentation](https://docs.aws.amazon.com/lightsail/)
- [CloudFront Documentation](https://docs.aws.amazon.com/cloudfront/)
