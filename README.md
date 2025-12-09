# WordPress Multisite on Docker - AWS Lightsail

A production-ready, secure, and performant WordPress multisite setup using Docker, designed for easy deployment to AWS Lightsail with CDN support, scaling capabilities, and CI/CD integration.

## 🚀 Quick Start

```bash
# 1. Clone and configure
git clone <your-repo-url>
cd Ideai-DockerWP-001
cp .env.example .env
# Edit .env with your settings

# 2. Start locally
make up
# or
docker-compose up -d

# 3. Access WordPress
# http://localhost
```

## ✨ Features

- ✅ **WordPress Multisite** - Fully configured for subdomain or subdirectory installations
- ✅ **Docker Compose** - Multi-container setup with WordPress, MariaDB, and Nginx
- ✅ **Shared wp-content** - Reusable plugins/themes across projects (local volume mount + production image)
- ✅ **Security Hardened** - SSL/TLS, security headers, rate limiting
- ✅ **Performance Optimized** - OPcache, Gzip compression, static file caching
- ✅ **AWS Lightsail Ready** - Automated deployment scripts
- ✅ **CDN Support** - CloudFront integration scripts
- ✅ **CI/CD Pipeline** - GitHub Actions for automated deployment
- ✅ **Backup & Restore** - Automated backup scripts
- ✅ **Scalable Architecture** - Designed for horizontal scaling

## 📋 Current Deployment Status

**Instance**: `wordpress-multisite` (London - eu-west-2)  
**IP**: `13.40.170.117`  
**Status**: ✅ All containers running  
**URL**: http://13.40.170.117

### Container Status
- ✅ **nginx**: Running (port 80)
- ✅ **wordpress**: Running (PHP-FPM)
- ✅ **db**: Running (MariaDB 10.11 - healthy)

## 📁 Project Structure

```
.
├── README.md                      # This file - main documentation
├── docker-compose.yml             # Main Docker Compose configuration
├── .env.example                   # Environment variables template
├── Makefile                       # Common development commands
│
├── docs/                          # Documentation
│   ├── deployment/                # Deployment guides
│   ├── troubleshooting/          # Troubleshooting guides
│   └── architecture/              # Architecture documentation
│
├── nginx/                         # Nginx configuration
│   ├── nginx.conf                 # Main Nginx config
│   └── conf.d/
│       └── default.conf           # Site configuration
│
├── wordpress/                     # WordPress configuration
│   ├── Dockerfile                 # Custom WordPress image (optional)
│   ├── php.ini                    # PHP configuration
│   ├── uploads.ini                # Upload settings
│   └── configure-multisite.sh     # Multisite setup script
│
├── scripts/                       # Automation scripts
│   ├── deployment/                # Deployment scripts
│   ├── maintenance/               # Maintenance scripts
│   └── backup/                    # Backup/restore scripts
│
└── .github/
    └── workflows/
        └── deploy.yml             # CI/CD pipeline
```

## 🏗️ Architecture

```
┌─────────────┐
│  CloudFront │  (CDN - Optional)
└──────┬──────┘
       │
┌──────▼──────┐
│    Nginx    │  (Reverse Proxy & SSL)
└──────┬──────┘
       │
┌──────▼──────┐
│  WordPress  │  (PHP-FPM 8.2)
└──────┬──────┘
       │
┌──────▼──────┐
│   MariaDB   │  (Database 10.11)
└─────────────┘
```

## 📚 Documentation

- **[Quick Start Guide](docs/deployment/QUICKSTART.md)** - Get started in 5 minutes
- **[Deployment Guide](docs/deployment/DEPLOYMENT.md)** - Full deployment instructions
- **[AWS Lightsail Deployment](docs/deployment/LIGHTSAIL.md)** - Step-by-step Lightsail setup
- **[WP Content Workflow](docs/deployment/WP_CONTENT_WORKFLOW.md)** - Working with plugins/themes
- **[Troubleshooting](docs/troubleshooting/TROUBLESHOOTING.md)** - Common issues and solutions
- **[Scaling Guide](docs/architecture/SCALING.md)** - Scaling strategies
- **[IAM Permissions](docs/deployment/IAM_PERMISSIONS.md)** - Required AWS permissions

## 🛠️ Development

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

See [WP Content Workflow](docs/deployment/WP_CONTENT_WORKFLOW.md) for details.

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
# Using deployment script
./scripts/deployment/deploy-to-instance.sh \
  wordpress-multisite \
  13.40.170.117 \
  ubuntu \
  /path/to/ssh-key.pem
```

See [Deployment Guide](docs/deployment/DEPLOYMENT.md) for detailed instructions.

## 🔒 Security

- SSL/TLS encryption (TLS 1.2+)
- Security headers (HSTS, X-Frame-Options, etc.)
- Rate limiting on Nginx
- Database password protection
- Regular security updates recommended

## 📦 Environment Variables

Key variables (see `.env.example` for full list):

- `DB_PASSWORD` - Database password
- `DB_ROOT_PASSWORD` - Database root password
- `DB_NAME` - Database name (default: wordpress)
- `DB_USER` - Database user (default: wordpress)
- `DOMAIN_CURRENT_SITE` - Primary domain for multisite
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

See [Troubleshooting Guide](docs/troubleshooting/TROUBLESHOOTING.md) for common issues.

Quick fixes:

```bash
# Restart services
docker-compose restart

# Rebuild containers
docker-compose up -d --build

# Check logs
docker-compose logs --tail=50
```

## 📝 License

This project is provided as-is for your use. WordPress is licensed under GPL v2 or later.

## 🔗 Additional Resources

- [WordPress Multisite Documentation](https://wordpress.org/support/article/create-a-network/)
- [Docker Documentation](https://docs.docker.com/)
- [AWS Lightsail Documentation](https://docs.aws.amazon.com/lightsail/)
- [CloudFront Documentation](https://docs.aws.amazon.com/cloudfront/)
