#!/bin/bash
set -e

# Fast deployment using pre-built images only (no custom builds)

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

INSTANCE_IP="${1:-18.130.255.19}"
SSH_KEY="${2:-/Users/sv/_MYUI/Dev/LightsailDefaultKey-eu-west-2.pem}"
SSH_USER="${3:-ubuntu}"

echo -e "${BLUE}Fast Deployment (No Builds)${NC}"
echo ""

# Test SSH
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$SSH_USER@$INSTANCE_IP" "echo 'SSH OK'" || {
    echo -e "${RED}SSH failed${NC}"
    exit 1
}

# Deploy using simple compose file
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_USER@$INSTANCE_IP" << 'ENDSSH'
    set -e
    cd /opt/wordpress-multisite
    
    echo "Stopping any existing containers..."
    sudo docker-compose down 2>/dev/null || true
    
    echo "Using simple docker-compose (no builds)..."
    # Use standard images only
    sudo docker-compose -f docker-compose.yml up -d --no-build 2>&1 || {
        echo "Trying with pull first..."
        sudo docker-compose pull
        sudo docker-compose up -d
    }
    
    echo "Waiting 15 seconds for services..."
    sleep 15
    
    echo "=== Service Status ==="
    sudo docker-compose ps
    
    echo ""
    echo "=== Testing WordPress ==="
    curl -I http://localhost 2>&1 | head -3 || echo "WordPress starting..."
ENDSSH

echo ""
echo -e "${GREEN}✓ Deployment complete!${NC}"
echo -e "${BLUE}Site: http://$INSTANCE_IP${NC}"

