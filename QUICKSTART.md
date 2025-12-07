# Quick Start Guide

Get your WordPress multisite up and running in 5 minutes!

## Prerequisites

- Docker and Docker Compose installed
- Basic terminal knowledge

## Local Development Setup

### 1. Clone and Configure

```bash
# Clone the repository
git clone <your-repo-url>
cd Ideai-DockerWP-001

# Copy environment file
cp .env.example .env

# Edit .env with your settings (at minimum, change passwords)
nano .env
```

### 2. Start Services

```bash
# Using Make (recommended)
make up

# Or using docker-compose directly
docker-compose up -d
```

### 3. Access WordPress

- **WordPress Site**: http://localhost
- **phpMyAdmin**: http://localhost:8080
- **WordPress Admin**: http://localhost/wp-admin

### 4. Complete WordPress Setup

1. Open http://localhost in your browser
2. Follow the WordPress installation wizard
3. Create your admin account
4. Log in to WordPress admin

### 5. Enable Multisite

1. Go to **Tools → Network Setup** in WordPress admin
2. Choose **Subdomains** or **Subdirectories**
3. Follow the instructions to:
   - Update `wp-config.php` (already done automatically)
   - Update `.htaccess` (if needed)
4. Log out and log back in
5. You'll see **Network Admin** in the admin bar

## Common Commands

```bash
# View logs
make logs

# Stop services
make down

# Restart services
make restart

# Check status
make status

# Run health checks
make health

# Create backup
make backup

# Update WordPress
make update-wp
```

## Production Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed production deployment instructions.

## Troubleshooting

### Services won't start

```bash
# Check logs
docker-compose logs

# Rebuild containers
docker-compose up -d --build --force-recreate
```

### Can't access WordPress

1. Check if containers are running: `docker-compose ps`
2. Check logs: `docker-compose logs wordpress`
3. Verify port 80 is not in use: `lsof -i :80`

### Database connection error

1. Verify `.env` file has correct database credentials
2. Check database container: `docker-compose ps db`
3. Restart database: `docker-compose restart db`

## Next Steps

- Read [README.md](README.md) for full documentation
- Read [DEPLOYMENT.md](DEPLOYMENT.md) for production setup
- Read [SCALING.md](SCALING.md) for scaling strategies
- Set up SSL certificates for production
- Configure backups
- Set up monitoring

## Need Help?

- Check the main [README.md](README.md)
- Review [DEPLOYMENT.md](DEPLOYMENT.md) for production issues
- Check Docker logs: `docker-compose logs -f`

