# AWS Lightsail Deployment Guide

Complete guide for deploying WordPress multisite to AWS Lightsail.

## Prerequisites

- AWS account with Lightsail access
- AWS CLI configured
- SSH key for Lightsail instance
- IAM permissions (see [IAM Permissions](IAM_PERMISSIONS.md))

## Step 1: Create Lightsail Instance

### Option A: Using AWS CLI

```bash
./scripts/deployment/create-and-deploy-london.sh
```

### Option B: Manual Creation

1. Go to AWS Lightsail Console
2. Create instance:
   - **Platform**: Linux/Unix
   - **Blueprint**: Ubuntu 22.04 LTS
   - **Bundle**: 1GB RAM minimum (2GB+ recommended)
   - **Region**: eu-west-2 (London)
3. Note the instance name and IP address

## Step 2: Configure IAM Permissions

Create IAM policy (see `lightsail-policy.json`):

```bash
aws iam create-policy \
  --policy-name LightsailDeploymentPolicy \
  --policy-document file://lightsail-policy.json

# Attach to your user
aws iam attach-user-policy \
  --user-name your-username \
  --policy-arn arn:aws:iam::ACCOUNT_ID:policy/LightsailDeploymentPolicy
```

## Step 3: Prepare Deployment Package

```bash
./scripts/deployment/prepare-deployment.sh
```

This creates `deployment-package.tar.gz` with all necessary files.

## Step 4: Deploy to Instance

### Using Deployment Script

```bash
./scripts/deployment/deploy-to-instance.sh \
  wordpress-multisite \
  13.40.170.117 \
  ubuntu \
  /path/to/ssh-key.pem
```

### Manual Deployment

```bash
# 1. Upload package
scp -i /path/to/key.pem deployment-package.tar.gz ubuntu@INSTANCE_IP:/tmp/

# 2. SSH to instance
ssh -i /path/to/key.pem ubuntu@INSTANCE_IP

# 3. Extract and start
cd /opt/wordpress-multisite
sudo tar -xzf /tmp/deployment-package.tar.gz
sudo docker-compose pull
sudo docker-compose up -d
```

## Step 5: Configure Environment

On the instance:

```bash
cd /opt/wordpress-multisite
nano .env
```

Update:
- Database passwords
- Domain name
- AWS settings

## Step 6: Verify Deployment

```bash
# Check containers
sudo docker-compose ps

# Check logs
sudo docker-compose logs

# Test site
curl -I http://INSTANCE_IP
```

## Step 7: Configure Domain (Optional)

1. Point DNS to Lightsail IP
2. Update `.env`: `DOMAIN_CURRENT_SITE=yourdomain.com`
3. Restart: `sudo docker-compose restart`

## Step 8: SSL Certificate

### Using Let's Encrypt

```bash
# On Lightsail instance
sudo apt-get update
sudo apt-get install certbot

# Generate certificate
sudo certbot certonly --standalone -d yourdomain.com

# Copy to nginx
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem nginx/ssl/key.pem

# Restart nginx
sudo docker-compose restart nginx
```

## Current Deployment

**Instance**: `wordpress-multisite`  
**Region**: eu-west-2 (London)  
**IP**: `13.40.170.117`  
**Status**: ✅ Running

### Container Status
- nginx: Port 80
- wordpress: PHP-FPM
- db: MariaDB 10.11

## Troubleshooting

### SSH Timeout

Use browser SSH from Lightsail console instead of terminal.

### IP Address Changed

After restart, IP may change. Get current IP:
```bash
aws lightsail get-instance \
  --instance-name wordpress-multisite \
  --region eu-west-2 \
  --query 'instance.publicIpAddress'
```

### Database Connection Issues

See [Troubleshooting Guide](../troubleshooting/TROUBLESHOOTING.md).

## Next Steps

- [Scaling Guide](../architecture/SCALING.md) - Scale your deployment
- [CloudFront Setup](CLOUDFRONT.md) - Add CDN
- [Backup Guide](../maintenance/BACKUP.md) - Set up backups
