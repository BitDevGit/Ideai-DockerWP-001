#!/bin/bash
set -e

# Simple, reliable deployment that won't hang

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

INSTANCE_IP="${1:-18.130.255.19}"
SSH_KEY="${2:-/Users/sv/_MYUI/Dev/LightsailDefaultKey-eu-west-2.pem}"
SSH_USER="${3:-ubuntu}"

echo -e "${BLUE}Simple Deployment (No Hanging)${NC}"
echo "IP: $INSTANCE_IP"
echo ""

# Test SSH
echo -e "${YELLOW}Testing SSH...${NC}"
if ! ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$SSH_USER@$INSTANCE_IP" "echo OK" &>/dev/null; then
    echo -e "${RED}SSH not working${NC}"
    exit 1
fi
echo -e "${GREEN}✓ SSH OK${NC}"

# Upload fixed package
echo -e "${YELLOW}Uploading fixed package...${NC}"
scp -i "$SSH_KEY" -o StrictHostKeyChecking=no deployment-package.tar.gz "$SSH_USER@$INSTANCE_IP:/tmp/" || exit 1

# Deploy with timeout protection
echo -e "${YELLOW}Deploying (with timeout protection)...${NC}"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no -o ServerAliveInterval=30 "$SSH_USER@$INSTANCE_IP" << 'ENDSSH'
    set -e
    cd /opt/wordpress-multisite
    
    # Extract
    sudo tar -xzf /tmp/deployment-package.tar.gz
    sudo chown -R $USER:$USER .
    
    # Use pre-built images instead of building
    echo "Pulling pre-built images..."
    sudo docker-compose pull || true
    
    # Start services (will build if needed, but with timeout)
    echo "Starting services..."
    timeout 1800 sudo docker-compose up -d --build || {
        echo "Build timed out, trying with pre-built images..."
        sudo docker-compose up -d
    }
    
    sleep 10
    sudo docker-compose ps
ENDSSH

echo ""
echo -e "${GREEN}✓ Deployment complete!${NC}"
echo -e "${BLUE}Site: http://$INSTANCE_IP${NC}"
