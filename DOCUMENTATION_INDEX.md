# Documentation Index

Complete index of all documentation and resources.

## 📚 Main Documentation

- **[README.md](README.md)** - Main project documentation and quick start
- **[PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md)** - Complete file structure and organization

## 🚀 Deployment Guides

Located in `docs/deployment/`:

- **[QUICKSTART.md](docs/deployment/QUICKSTART.md)** - Get started in 5 minutes
- **[DEPLOYMENT.md](docs/deployment/DEPLOYMENT.md)** - Complete deployment guide
- **[LIGHTSAIL.md](docs/deployment/LIGHTSAIL.md)** - AWS Lightsail deployment guide
- **[IAM_PERMISSIONS.md](docs/deployment/IAM_PERMISSIONS.md)** - Required AWS IAM permissions

## 🐛 Troubleshooting

Located in `docs/troubleshooting/`:

- **[TROUBLESHOOTING.md](docs/troubleshooting/TROUBLESHOOTING.md)** - Common issues and solutions

## 🏗️ Architecture

Located in `docs/architecture/`:

- **[SCALING.md](docs/architecture/SCALING.md)** - Scaling strategies and optimization

## 📁 Scripts

### Deployment Scripts (`scripts/deployment/`)

- `create-and-deploy-london.sh` - Create and deploy to London Lightsail instance
- `deploy-to-instance.sh` - Deploy to existing Lightsail instance
- `deploy-with-cli.sh` - Deploy using AWS CLI
- `deploy-background.sh` - Background deployment (no timeout)
- `deploy-fast.sh` - Fast deployment (no builds)
- `deploy-minimal.sh` - Minimal deployment
- `deploy-simple.sh` - Simple deployment
- `prepare-deployment.sh` - Prepare deployment package
- `setup-cloudfront.sh` - CloudFront CDN setup

### Maintenance Scripts (`scripts/maintenance/`)

- `health-check.sh` - Check service health
- `init-ssl.sh` - Initialize SSL certificates

### Backup Scripts (`scripts/backup/`)

- `backup.sh` - Create backup
- `restore.sh` - Restore from backup

## 🔧 Configuration Files

- `docker-compose.yml` - Main Docker Compose configuration
- `.env.example` - Environment variables template
- `Makefile` - Development commands
- `lightsail-policy.json` - AWS IAM policy
- `.gitignore` - Git ignore rules

## 📊 Current Status

**Deployment**: ✅ Running  
**Instance**: `wordpress-multisite` (London - eu-west-2)  
**IP**: `13.40.170.117`  
**URL**: http://13.40.170.117

### Services
- ✅ nginx: Running (port 80)
- ✅ wordpress: Running (PHP-FPM)
- ✅ db: Running (MariaDB 10.11)

## 🗂️ File Organization

### By Purpose
- **docs/**: All documentation
- **scripts/**: All automation scripts
- **nginx/**: Nginx configuration
- **wordpress/**: WordPress configuration

### By Function
- **deployment/**: Deployment-related files
- **maintenance/**: Maintenance scripts
- **backup/**: Backup/restore scripts
- **troubleshooting/**: Troubleshooting guides
- **architecture/**: Architecture documentation

## 📝 Quick Reference

### Start Services
```bash
make up
# or
docker-compose up -d
```

### Deploy to Lightsail
```bash
./scripts/deployment/deploy-to-instance.sh \
  wordpress-multisite \
  13.40.170.117 \
  ubuntu \
  /path/to/key.pem
```

### Check Status
```bash
docker-compose ps
docker-compose logs
```

### Create Backup
```bash
./scripts/backup/backup.sh
```

## 🔍 Finding Information

- **Getting Started**: See [QUICKSTART.md](docs/deployment/QUICKSTART.md)
- **Deployment Issues**: See [TROUBLESHOOTING.md](docs/troubleshooting/TROUBLESHOOTING.md)
- **Scaling**: See [SCALING.md](docs/architecture/SCALING.md)
- **AWS Setup**: See [LIGHTSAIL.md](docs/deployment/LIGHTSAIL.md)
- **File Structure**: See [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md)

## 📞 Support

For issues or questions:
1. Check [TROUBLESHOOTING.md](docs/troubleshooting/TROUBLESHOOTING.md)
2. Review relevant deployment guide
3. Check logs: `docker-compose logs`
4. Open an issue on GitHub


