#!/bin/bash
set -e

# SSL Certificate Initialization Script
# For Let's Encrypt certificates

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

DOMAIN="${1:-yourdomain.com}"
EMAIL="${2:-admin@${DOMAIN}}"

echo -e "${GREEN}Setting up SSL certificate for $DOMAIN...${NC}"

# Create SSL directory
mkdir -p nginx/ssl

# Check if certbot is installed
if ! command -v certbot &> /dev/null; then
    echo -e "${YELLOW}Certbot not found. Installing...${NC}"
    sudo apt-get update
    sudo apt-get install -y certbot
fi

# Stop nginx temporarily for standalone mode
echo -e "${YELLOW}Stopping nginx for certificate generation...${NC}"
docker-compose stop nginx || true

# Generate certificate
echo -e "${YELLOW}Generating Let's Encrypt certificate...${NC}"
sudo certbot certonly --standalone \
    -d "$DOMAIN" \
    -d "www.$DOMAIN" \
    --email "$EMAIL" \
    --agree-tos \
    --non-interactive

# Copy certificates
echo -e "${YELLOW}Copying certificates...${NC}"
sudo cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/$DOMAIN/privkey.pem nginx/ssl/key.pem
sudo chmod 644 nginx/ssl/cert.pem
sudo chmod 600 nginx/ssl/key.pem

# Restart nginx
echo -e "${YELLOW}Starting nginx...${NC}"
docker-compose start nginx

echo -e "${GREEN}SSL certificate setup completed!${NC}"
echo -e "${YELLOW}Note: Certificates expire in 90 days. Set up auto-renewal:${NC}"
echo -e "Add to crontab: 0 0 * * * certbot renew --quiet && docker-compose restart nginx"

