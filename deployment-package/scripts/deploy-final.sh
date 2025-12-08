#!/bin/bash
# Final deployment - simple and reliable

INSTANCE_IP="18.130.255.19"
SSH_KEY="/Users/sv/_MYUI/Dev/LightsailDefaultKey-eu-west-2.pem"
SSH_USER="ubuntu"

echo "=== Final Deployment ==="

# Upload and deploy in one go with timeout protection
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no -o ConnectTimeout=20 -o ServerAliveInterval=30 "$SSH_USER@$INSTANCE_IP" bash << 'REMOTE'
set -e
cd /opt/wordpress-multisite

echo "Extracting updated files..."
sudo tar -xzf /tmp/deployment-package.tar.gz -C . --overwrite
sudo chown -R $USER:$USER .

echo "Using simple docker-compose (no build)..."
sudo docker-compose down 2>/dev/null || true

echo "Pulling images..."
sudo docker-compose pull nginx mysql redis

echo "Starting services..."
sudo docker-compose up -d

echo "Waiting 30 seconds..."
sleep 30

echo "Status:"
sudo docker-compose ps

echo ""
echo "✓ Done! Check: http://$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4)"
REMOTE

echo ""
echo "✓ Deployment complete!"
echo "Site: http://$INSTANCE_IP"

