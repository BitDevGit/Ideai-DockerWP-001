# Deployment Guide - AWS Lightsail

This guide walks you through deploying the WordPress multisite to AWS Lightsail.

## Prerequisites

1. AWS Account with Lightsail access
2. Domain name with DNS control
3. AWS CLI installed and configured
4. SSH key pair for Lightsail access

## Step 1: Create Lightsail Instance

1. Log in to AWS Lightsail Console
2. Create a new instance:
   - **Platform**: Linux/Unix
   - **Blueprint**: Ubuntu 22.04 LTS
   - **Instance Plan**: Choose based on your needs (minimum 2GB RAM recommended)
   - **Instance Name**: `wordpress-multisite` (or your preferred name)

3. Wait for instance to be running

## Step 2: Configure Instance

### Connect to Instance

```bash
# Get instance IP from Lightsail console
ssh -i ~/.ssh/lightsail-key.pem ubuntu@<instance-ip>
```

### Install Docker and Docker Compose

```bash
# Update system
sudo apt-get update
sudo apt-get upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Log out and back in for group changes to take effect
exit
```

### Install Additional Tools

```bash
# Install Git
sudo apt-get install -y git

# Install Certbot for SSL
sudo apt-get install -y certbot
```

## Step 3: Deploy Application

### Option A: Using Git (Recommended)

```bash
# Clone your repository
cd /opt
sudo git clone <your-repo-url> wordpress-multisite
cd wordpress-multisite
sudo chown -R $USER:$USER .

# Copy environment file
cp .env.example .env
nano .env  # Edit with your configuration
```

### Option B: Using Deployment Script

From your local machine:

```bash
# Set up SSH key
chmod 400 ~/.ssh/lightsail-key.pem

# Run deployment script
./scripts/deploy-lightsail.sh
```

## Step 4: Configure Environment

Edit `.env` file with your settings:

```bash
nano .env
```

Key settings:
- `DB_PASSWORD`: Strong password for database
- `DB_ROOT_PASSWORD`: Strong password for MySQL root
- `REDIS_PASSWORD`: Strong password for Redis
- `DOMAIN_CURRENT_SITE`: Your domain name
- `AWS_LIGHTSAIL_INSTANCE_NAME`: Your instance name

## Step 5: Set Up SSL Certificate

### Generate Let's Encrypt Certificate

```bash
# Stop nginx temporarily
docker-compose stop nginx

# Generate certificate
sudo certbot certonly --standalone \
    -d yourdomain.com \
    -d www.yourdomain.com \
    --email admin@yourdomain.com \
    --agree-tos

# Copy certificates
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem nginx/ssl/key.pem
sudo chmod 644 nginx/ssl/cert.pem
sudo chmod 600 nginx/ssl/key.pem

# Start services
docker-compose up -d
```

### Set Up Auto-Renewal

```bash
# Add to crontab
sudo crontab -e

# Add this line (runs daily at 2 AM)
0 2 * * * certbot renew --quiet && cd /opt/wordpress-multisite && docker-compose restart nginx
```

## Step 6: Configure DNS

1. Go to your domain registrar's DNS settings
2. Create an A record pointing to your Lightsail instance IP:
   - Type: A
   - Name: @ (or blank)
   - Value: <your-lightsail-ip>
   - TTL: 300

3. Create CNAME for www:
   - Type: CNAME
   - Name: www
   - Value: yourdomain.com
   - TTL: 300

## Step 7: Start Services

```bash
# Start all services
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f
```

## Step 8: Complete WordPress Setup

1. Access your site: `https://yourdomain.com`
2. Complete WordPress installation
3. Enable multisite network:
   - Go to Tools → Network Setup
   - Follow the instructions
   - Update `wp-config.php` and `.htaccess` as shown

## Step 9: Set Up CloudFront CDN (Optional)

```bash
# Run CloudFront setup script
./scripts/setup-cloudfront.sh

# Follow the instructions to create distribution
# Update DNS to point CDN subdomain to CloudFront
```

## Step 10: Configure Firewall

In Lightsail console:

1. Go to Networking tab
2. Add firewall rules:
   - HTTP (port 80) - Allow from Anywhere
   - HTTPS (port 443) - Allow from Anywhere
   - SSH (port 22) - Allow from Your IP only

## Step 11: Set Up Monitoring

### CloudWatch Alarms

1. Go to AWS CloudWatch
2. Create alarms for:
   - CPU utilization > 80%
   - Memory utilization > 80%
   - Disk space < 20%

### Automated Backups

```bash
# Add to crontab for daily backups at 3 AM
0 3 * * * cd /opt/wordpress-multisite && ./scripts/backup.sh
```

## Troubleshooting

### Can't connect to instance

- Check security group/firewall rules
- Verify SSH key permissions: `chmod 400 ~/.ssh/lightsail-key.pem`
- Check instance status in Lightsail console

### Services won't start

```bash
# Check logs
docker-compose logs

# Rebuild containers
docker-compose up -d --build --force-recreate
```

### SSL certificate issues

- Verify domain DNS is pointing to instance
- Check certificate files exist: `ls -la nginx/ssl/`
- Test certificate: `openssl x509 -in nginx/ssl/cert.pem -text -noout`

### Database connection errors

- Verify `.env` file has correct credentials
- Check database container: `docker-compose ps db`
- Test connection: `docker-compose exec db mysql -u wordpress -p`

## Scaling

### Vertical Scaling (Increase Instance Size)

1. Go to Lightsail console
2. Stop instance
3. Change instance plan to larger size
4. Start instance

### Horizontal Scaling (Multiple Instances)

1. Create additional Lightsail instances
2. Set up Application Load Balancer
3. Configure shared database (RDS or external MySQL)
4. Use ElastiCache for shared Redis
5. Update CloudFront origin to point to load balancer

## Maintenance

### Update WordPress

```bash
docker-compose exec wordpress wp core update --allow-root
docker-compose exec wordpress wp plugin update --all --allow-root
```

### Update Docker Images

```bash
docker-compose pull
docker-compose up -d
```

### Database Backup

```bash
./scripts/backup.sh
```

## Security Checklist

- [ ] Strong passwords in `.env` file
- [ ] SSL certificate installed and auto-renewal configured
- [ ] Firewall rules configured (only necessary ports open)
- [ ] SSH access restricted to your IP
- [ ] Regular backups scheduled
- [ ] WordPress security plugins installed
- [ ] Regular updates scheduled
- [ ] Monitoring and alerts configured

## Next Steps

1. Install WordPress security plugins (Wordfence, Sucuri)
2. Set up monitoring and alerting
3. Configure automated backups to S3
4. Set up staging environment
5. Configure CI/CD pipeline (see `.github/workflows/deploy.yml`)

