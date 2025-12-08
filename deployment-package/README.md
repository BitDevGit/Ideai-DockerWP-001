# WordPress Multisite on Docker - AWS Lightsail Deployment

A production-ready, secure, and performant WordPress multisite setup using Docker, designed for easy deployment to AWS Lightsail with CDN support, scaling capabilities, and CI/CD integration.

## Features

- ✅ **WordPress Multisite** - Fully configured for subdomain or subdirectory installations
- ✅ **Docker Compose** - Multi-container setup with WordPress, MySQL, Redis, and Nginx
- ✅ **Security Hardened** - SSL/TLS, security headers, rate limiting, and best practices
- ✅ **Performance Optimized** - Redis caching, OPcache, Gzip compression, static file caching
- ✅ **AWS Lightsail Ready** - Deployment scripts and configurations
- ✅ **CDN Support** - CloudFront integration scripts
- ✅ **CI/CD Pipeline** - GitHub Actions for automated deployment
- ✅ **Backup & Restore** - Automated backup scripts with S3 support
- ✅ **Scalable Architecture** - Designed for horizontal scaling

## Architecture

```
┌─────────────┐
│   CloudFront│  (CDN)
└──────┬──────┘
       │
┌──────▼──────┐
│    Nginx    │  (Reverse Proxy & SSL)
└──────┬──────┘
       │
┌──────▼──────┐
│  WordPress  │  (PHP-FPM)
└──────┬──────┘
       │
┌──────▼──────┐     ┌──────────┐
│    MySQL    │     │  Redis   │
└─────────────┘     └──────────┘
```

## Prerequisites

- Docker and Docker Compose installed
- AWS CLI configured (for Lightsail deployment)
- Domain name with DNS access
- SSL certificate (Let's Encrypt recommended)

## Quick Start

### 1. Clone and Configure

```bash
git clone <your-repo-url>
cd Ideai-DockerWP-001
cp .env.example .env
```

Edit `.env` with your configuration:
- Database passwords
- Domain name
- Redis password
- AWS settings

### 2. Local Development

```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f

# Access WordPress
# http://localhost (will redirect to HTTPS if configured)
```

### 3. Initial WordPress Setup

1. Access WordPress admin: `https://yourdomain.com/wp-admin`
2. Complete the WordPress installation
3. Enable multisite network:
   - Go to Tools → Network Setup
   - Follow the instructions to enable multisite
   - Update `wp-config.php` and `.htaccess` as instructed

### 4. Production Deployment to AWS Lightsail

#### Option A: Using Deployment Script

```bash
chmod +x scripts/deploy-lightsail.sh
./scripts/deploy-lightsail.sh
```

#### Option B: Manual Deployment

1. Create a Lightsail instance (Ubuntu 22.04 LTS recommended)
2. Install Docker and Docker Compose on the instance
3. Copy project files to `/opt/wordpress-multisite`
4. Configure `.env` file
5. Run `docker-compose up -d`

### 5. SSL Certificate Setup

For production, use Let's Encrypt:

```bash
# On your Lightsail instance
sudo apt-get update
sudo apt-get install certbot

# Generate certificate
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Copy certificates to nginx/ssl/
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem nginx/ssl/key.pem
```

### 6. CDN Setup (CloudFront)

```bash
chmod +x scripts/setup-cloudfront.sh
export ORIGIN_DOMAIN=yourdomain.com
export CDN_DOMAIN=cdn.yourdomain.com
./scripts/setup-cloudfront.sh
```

Follow the instructions to create the CloudFront distribution and update DNS.

## CI/CD Setup

### GitHub Actions Configuration

1. Add the following secrets to your GitHub repository:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `LIGHTSAIL_HOST` (your instance IP or domain)
   - `LIGHTSAIL_USER` (usually `bitnami` or `ubuntu`)
   - `LIGHTSAIL_SSH_KEY` (your SSH private key)

2. Push to `main` or `production` branch to trigger deployment

### Manual CI/CD Trigger

The workflow can also be triggered manually from GitHub Actions tab.

## Backup & Restore

### Create Backup

```bash
chmod +x scripts/backup.sh
./scripts/backup.sh
```

Backups are stored in `./backups/` directory.

### Restore from Backup

```bash
chmod +x scripts/restore.sh
./scripts/restore.sh 20240101_120000
```

### Automated Backups

Add to crontab for daily backups:

```bash
0 2 * * * /path/to/scripts/backup.sh
```

## Scaling

### Horizontal Scaling

1. **Load Balancer**: Use AWS Application Load Balancer in front of multiple Lightsail instances
2. **Database**: Consider RDS for MySQL or use read replicas
3. **Redis**: Use ElastiCache for Redis cluster
4. **CDN**: CloudFront handles static asset distribution

### Vertical Scaling

Increase Lightsail instance size as needed:
- 2GB RAM minimum for small sites
- 4GB+ recommended for production
- 8GB+ for high traffic

## Security Best Practices

- ✅ SSL/TLS encryption (TLS 1.2+)
- ✅ Security headers (HSTS, X-Frame-Options, etc.)
- ✅ Rate limiting on Nginx
- ✅ Database password protection
- ✅ Redis password authentication
- ✅ Regular security updates
- ✅ Firewall configuration (only allow 80, 443, SSH)
- ✅ Regular backups
- ✅ WordPress security plugins recommended

## Monitoring

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

### Performance Monitoring

Consider integrating:
- CloudWatch for AWS resources
- New Relic or Datadog for application monitoring
- Uptime monitoring (UptimeRobot, Pingdom)

## Maintenance

### Update WordPress

```bash
docker-compose exec wordpress wp core update --allow-root
docker-compose exec wordpress wp plugin update --all --allow-root
docker-compose exec wordpress wp theme update --all --allow-root
```

### Update Docker Images

```bash
docker-compose pull
docker-compose up -d
```

### Database Maintenance

```bash
# Optimize database
docker-compose exec wordpress wp db optimize --allow-root

# Repair database
docker-compose exec wordpress wp db repair --allow-root
```

## Troubleshooting

### Container won't start

```bash
# Check logs
docker-compose logs

# Rebuild containers
docker-compose up -d --build --force-recreate
```

### Database connection issues

- Verify `.env` file has correct database credentials
- Check database container is running: `docker-compose ps db`
- Test connection: `docker-compose exec db mysql -u wordpress -p`

### SSL certificate issues

- Verify certificate files exist in `nginx/ssl/`
- Check certificate expiration: `openssl x509 -in nginx/ssl/cert.pem -noout -dates`
- Renew Let's Encrypt certificates before expiration

## File Structure

```
.
├── docker-compose.yml          # Main Docker Compose configuration
├── .env.example                # Environment variables template
├── .gitignore                  # Git ignore rules
├── nginx/
│   ├── nginx.conf              # Main Nginx configuration
│   └── conf.d/
│       └── default.conf        # Site configuration
├── wordpress/
│   ├── Dockerfile              # Custom WordPress image
│   ├── php.ini                 # PHP configuration
│   ├── uploads.ini             # Upload settings
│   └── configure-multisite.sh  # Multisite setup script
├── scripts/
│   ├── deploy-lightsail.sh     # AWS Lightsail deployment
│   ├── setup-cloudfront.sh     # CloudFront CDN setup
│   ├── backup.sh               # Backup script
│   └── restore.sh              # Restore script
├── .github/
│   └── workflows/
│       └── deploy.yml          # CI/CD pipeline
└── README.md                   # This file
```

## Environment Variables

Key environment variables (see `.env.example` for full list):

- `DB_PASSWORD` - MySQL database password
- `DB_ROOT_PASSWORD` - MySQL root password
- `REDIS_PASSWORD` - Redis password
- `DOMAIN_CURRENT_SITE` - Primary domain for multisite
- `SUBDOMAIN_INSTALL` - Use subdomains (1) or subdirectories (0)
- `WP_DEBUG` - Enable WordPress debug mode (0 or 1)

## Support & Contributing

For issues, questions, or contributions, please open an issue or pull request on GitHub.

## License

This project is provided as-is for your use. WordPress is licensed under GPL v2 or later.

## Additional Resources

- [WordPress Multisite Documentation](https://wordpress.org/support/article/create-a-network/)
- [Docker Documentation](https://docs.docker.com/)
- [AWS Lightsail Documentation](https://docs.aws.amazon.com/lightsail/)
- [CloudFront Documentation](https://docs.aws.amazon.com/cloudfront/)


