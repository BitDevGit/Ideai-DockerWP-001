#!/bin/bash
# Minimal deployment - no builds, no hanging

INSTANCE_IP="${1:-18.130.255.19}"
SSH_KEY="${2:-/Users/sv/_MYUI/Dev/LightsailDefaultKey-eu-west-2.pem}"

echo "=== Minimal Deployment ==="
echo "IP: $INSTANCE_IP"
echo ""

# Upload
echo "Uploading minimal package..."
scp -i "$SSH_KEY" -o StrictHostKeyChecking=no deployment-package.tar.gz ubuntu@$INSTANCE_IP:/tmp/ || exit 1

# Deploy
echo "Deploying..."
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no ubuntu@$INSTANCE_IP << 'ENDSSH'
    set -e
    cd /opt/wordpress-multisite
    
    # Stop any existing
    sudo docker-compose down 2>/dev/null || true
    
    # Extract
    sudo tar -xzf /tmp/deployment-package.tar.gz
    
    # Use minimal compose
    sudo cp docker-compose.yml docker-compose.yml.backup 2>/dev/null || true
    
    # Pull images first (faster)
    echo "Pulling images..."
    sudo docker-compose pull
    
    # Start (no build)
    echo "Starting services..."
    sudo docker-compose up -d
    
    echo "Waiting 20 seconds..."
    sleep 20
    
    echo "=== Status ==="
    sudo docker-compose ps
    
    echo ""
    echo "=== Test ==="
    curl -I http://localhost 2>&1 | head -3
ENDSSH

echo ""
echo "✓ Done! http://$INSTANCE_IP"

