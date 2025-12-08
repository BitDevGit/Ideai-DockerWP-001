# Deployment Guide

Complete deployment guide for WordPress multisite.

## Overview

This guide covers deploying WordPress multisite to various environments:
- Local development
- AWS Lightsail
- Production servers

## Local Development

See [Quick Start Guide](QUICKSTART.md) for local setup.

## AWS Lightsail Deployment

See [Lightsail Deployment Guide](LIGHTSAIL.md) for detailed AWS Lightsail instructions.

## Production Deployment

### Prerequisites

- Server with Docker and Docker Compose
- Domain name configured
- SSL certificate
- 2GB+ RAM recommended

### Steps

1. **Prepare server:**
   ```bash
   sudo apt-get update
   sudo apt-get install docker.io docker-compose
   sudo usermod -aG docker $USER
   ```

2. **Deploy files:**
   ```bash
   scp deployment-package.tar.gz user@server:/opt/wordpress-multisite/
   ssh user@server
   cd /opt/wordpress-multisite
   tar -xzf deployment-package.tar.gz
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   nano .env
   ```

4. **Start services:**
   ```bash
   docker-compose pull
   docker-compose up -d
   ```

5. **Verify:**
   ```bash
   docker-compose ps
   curl -I http://localhost
   ```

## CI/CD Deployment

### GitHub Actions

The project includes a GitHub Actions workflow (`.github/workflows/deploy.yml`).

**Setup:**

1. Add secrets to GitHub repository:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `LIGHTSAIL_HOST`
   - `LIGHTSAIL_USER`
   - `LIGHTSAIL_SSH_KEY`

2. Push to `main` branch to trigger deployment

### Manual Trigger

Deploy manually from GitHub Actions tab.

## Post-Deployment

### 1. Complete WordPress Installation

1. Open site URL in browser
2. Complete installation wizard
3. Create admin account

### 2. Enable Multisite

1. Go to **Tools → Network Setup**
2. Choose installation type
3. Follow instructions to update files

### 3. Configure SSL

See SSL setup in [Lightsail Guide](LIGHTSAIL.md).

### 4. Set Up Backups

```bash
# Add to crontab
0 2 * * * /opt/wordpress-multisite/scripts/backup/backup.sh
```

## Monitoring

### Health Checks

```bash
# Container status
docker-compose ps

# Service health
./scripts/maintenance/health-check.sh
```

### Logs

```bash
# All logs
docker-compose logs -f

# Specific service
docker-compose logs -f wordpress
```

## Maintenance

### Updates

```bash
# Update WordPress
docker-compose exec wordpress wp core update --allow-root

# Update images
docker-compose pull
docker-compose up -d
```

### Backups

See backup scripts in `scripts/backup/`.

## Troubleshooting

See [Troubleshooting Guide](../troubleshooting/TROUBLESHOOTING.md).
