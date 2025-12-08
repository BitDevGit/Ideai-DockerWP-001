# Quick Start Guide

Get your WordPress multisite running in 5 minutes.

## Prerequisites

- Docker and Docker Compose installed
- 2GB+ RAM available
- Ports 80, 443 available

## Step 1: Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your settings:
- Database passwords
- Domain name (for local: `localhost`)
- Redis password

## Step 2: Start Services

```bash
# Using Make
make up

# Or using Docker Compose directly
docker-compose up -d
```

## Step 3: Access WordPress

Open your browser:
- **Local**: http://localhost
- **Network**: http://your-ip-address

## Step 4: Complete Installation

1. Select your language
2. Enter site details:
   - Site Title
   - Username
   - Password
   - Email
3. Click "Install WordPress"

## Step 5: Enable Multisite (Optional)

After installation:

1. Go to **Tools → Network Setup**
2. Choose subdomain or subdirectory installation
3. Follow the instructions to update `wp-config.php` and `.htaccess`

## Verify Installation

```bash
# Check containers
docker-compose ps

# View logs
docker-compose logs -f

# Test database connection
docker-compose exec wordpress wp db check --allow-root
```

## Next Steps

- [Deployment Guide](DEPLOYMENT.md) - Deploy to production
- [Lightsail Deployment](LIGHTSAIL.md) - Deploy to AWS Lightsail
- [Troubleshooting](../troubleshooting/TROUBLESHOOTING.md) - Common issues
