# Quick Guide: Deploy to AWS Lightsail

## Current Status
- ✅ **Local Setup**: Running at http://localhost
- ❌ **Lightsail**: Not yet deployed

## Quick Deployment Steps

### Option 1: Manual Deployment (Recommended for first time)

1. **Create Lightsail Instance**
   - Go to AWS Lightsail Console: https://lightsail.aws.amazon.com
   - Click "Create instance"
   - Choose:
     - **Platform**: Linux/Unix
     - **Blueprint**: Ubuntu 22.04 LTS
     - **Instance Plan**: $10/month (2GB RAM) or higher
     - **Instance Name**: `wordpress-multisite`

2. **Wait for instance to be running** (2-3 minutes)

3. **Get Instance IP**
   - In Lightsail console, click on your instance
   - Copy the "Public IP" address

4. **Connect to Instance**
   ```bash
   # Download SSH key from Lightsail console first
   # Then connect:
   ssh -i ~/Downloads/wordpress-multisite-key.pem ubuntu@<instance-ip>
   ```

5. **Install Docker on Instance**
   ```bash
   # Update system
   sudo apt-get update && sudo apt-get upgrade -y
   
   # Install Docker
   curl -fsSL https://get.docker.com -o get-docker.sh
   sudo sh get-docker.sh
   sudo usermod -aG docker $USER
   
   # Install Docker Compose
   sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
   sudo chmod +x /usr/local/bin/docker-compose
   
   # Install Git
   sudo apt-get install -y git
   
   # Log out and back in
   exit
   ```

6. **Deploy Application**
   ```bash
   # Connect again
   ssh -i ~/Downloads/wordpress-multisite-key.pem ubuntu@<instance-ip>
   
   # Clone repository (or upload files)
   cd /opt
   sudo mkdir -p wordpress-multisite
   cd wordpress-multisite
   
   # If you have a Git repository:
   sudo git clone <your-repo-url> .
   sudo chown -R $USER:$USER .
   
   # OR upload files manually using SCP:
   # From your local machine:
   # scp -i ~/Downloads/wordpress-multisite-key.pem -r * ubuntu@<instance-ip>:/opt/wordpress-multisite/
   ```

7. **Configure Environment**
   ```bash
   cd /opt/wordpress-multisite
   cp .env.example .env
   nano .env
   # Update with:
   # - Strong passwords
   # - Your domain name (if you have one)
   # - Production settings
   ```

8. **Start Services**
   ```bash
   docker-compose up -d
   docker-compose ps  # Check status
   ```

9. **Configure Firewall**
   - In Lightsail console, go to Networking tab
   - Add firewall rules:
     - HTTP (port 80) - Allow from Anywhere
     - HTTPS (port 443) - Allow from Anywhere
     - SSH (port 22) - Allow from Your IP

10. **Access Your Site**
    - Open browser: `http://<instance-ip>`
    - Complete WordPress installation

### Option 2: Using Deployment Script (After initial setup)

Once you have:
- AWS CLI configured
- Lightsail instance created
- SSH key set up

```bash
# Set environment variables
export AWS_LIGHTSAIL_INSTANCE_NAME=wordpress-multisite
export AWS_REGION=us-east-1

# Run deployment script
./scripts/deploy-lightsail.sh
```

## Setting Up Domain (Optional)

1. **Point Domain to Lightsail**
   - In your domain registrar, create A record:
     - Type: A
     - Name: @ (or blank)
     - Value: <your-lightsail-ip>
     - TTL: 300

2. **Set Up SSL Certificate**
   ```bash
   # On Lightsail instance
   sudo apt-get install -y certbot
   
   # Stop nginx temporarily
   docker-compose stop nginx
   
   # Generate certificate
   sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com
   
   # Copy certificates
   sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem nginx/ssl/cert.pem
   sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem nginx/ssl/key.pem
   
   # Switch to production nginx config
   mv nginx/conf.d/default.conf nginx/conf.d/local.conf
   mv nginx/conf.d/default.conf.production nginx/conf.d/default.conf
   
   # Restart services
   docker-compose start nginx
   ```

## Cost Estimate

- **Lightsail Instance**: $10/month (2GB RAM) or $20/month (4GB RAM)
- **Data Transfer**: First 1TB free, then $0.09/GB
- **Total**: ~$10-20/month for small to medium sites

## Next Steps After Deployment

1. Complete WordPress installation
2. Enable multisite network
3. Set up automated backups
4. Configure CloudFront CDN (optional)
5. Set up monitoring

## Troubleshooting

### Can't connect via SSH
- Check firewall rules in Lightsail
- Verify SSH key permissions: `chmod 400 ~/Downloads/wordpress-multisite-key.pem`
- Check instance status in Lightsail console

### Services won't start
- Check logs: `docker-compose logs`
- Verify .env file is configured correctly
- Check disk space: `df -h`

### Can't access website
- Verify firewall rules allow HTTP/HTTPS
- Check nginx logs: `docker-compose logs nginx`
- Test from instance: `curl http://localhost`


