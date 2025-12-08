#!/bin/bash
set -e

# Deploy WordPress to Lightsail Instance (Background/No Timeout Version)

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

INSTANCE_NAME="${1:-wordpress-multisite}"
INSTANCE_IP="${2}"
SSH_USER="${3:-ubuntu}"
SSH_KEY_PATH="${4}"

if [ -z "$INSTANCE_IP" ]; then
    echo -e "${RED}Usage: $0 <instance-name> <instance-ip> [ssh-user] [ssh-key-path]${NC}"
    exit 1
fi

if [ -z "$SSH_KEY_PATH" ]; then
    SSH_KEY_PATH="/Users/sv/_MYUI/Dev/LightsailDefaultKey-eu-west-2.pem"
fi

echo -e "${BLUE}Deploying WordPress (No Timeout Version)${NC}"
echo "Instance: $INSTANCE_NAME"
echo "IP: $INSTANCE_IP"
echo ""

# Upload deployment script to run on server
cat > /tmp/deploy-remote.sh << 'REMOTE_SCRIPT'
#!/bin/bash
set -e
cd /opt/wordpress-multisite

echo "=== Starting Deployment ==="
echo "Installing Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
fi

echo "Installing Docker Compose..."
if ! command -v docker-compose &> /dev/null; then
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
fi

echo "Setting up directory..."
sudo mkdir -p /opt/wordpress-multisite
cd /opt/wordpress-multisite
sudo tar -xzf /tmp/deployment-package.tar.gz
sudo chown -R $USER:$USER .

echo "Configuring environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    DB_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    DB_ROOT_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    REDIS_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
    sed -i "s/DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=$DB_ROOT_PASS/" .env
    sed -i "s/REDIS_PASSWORD=.*/REDIS_PASSWORD=$REDIS_PASS/" .env
    sed -i "s/DOMAIN_CURRENT_SITE=.*/DOMAIN_CURRENT_SITE=$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4)/" .env
    sed -i "s/ENVIRONMENT=.*/ENVIRONMENT=production/" .env
    sed -i "s/WP_DEBUG=.*/WP_DEBUG=0/" .env
fi

echo "Starting services (this may take 10-15 minutes)..."
sudo docker-compose up -d --build

echo "Waiting for services..."
sleep 20

echo "Checking status..."
sudo docker-compose ps

echo "=== Deployment Complete ==="
echo "WordPress should be available at: http://$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4)"
REMOTE_SCRIPT

# Upload files
echo -e "${YELLOW}Uploading files...${NC}"
scp -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no deployment-package.tar.gz "$SSH_USER@$INSTANCE_IP:/tmp/" || {
    echo -e "${RED}Upload failed${NC}"
    exit 1
}

scp -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no /tmp/deploy-remote.sh "$SSH_USER@$INSTANCE_IP:/tmp/" || {
    echo -e "${RED}Script upload failed${NC}"
    exit 1
}

echo -e "${GREEN}✓ Files uploaded${NC}"

# Run deployment in background with nohup
echo -e "${YELLOW}Starting deployment in background (no timeout)...${NC}"
ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no -o ServerAliveInterval=60 -o ServerAliveCountMax=10 "$SSH_USER@$INSTANCE_IP" << 'ENDSSH'
    chmod +x /tmp/deploy-remote.sh
    nohup /tmp/deploy-remote.sh > /tmp/deploy.log 2>&1 &
    echo $! > /tmp/deploy.pid
    echo "Deployment started in background (PID: $(cat /tmp/deploy.pid))"
    echo "Monitor progress with: tail -f /tmp/deploy.log"
ENDSSH

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Deployment Started in Background!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}To check progress:${NC}"
echo "  ssh -i $SSH_KEY_PATH $SSH_USER@$INSTANCE_IP 'tail -f /tmp/deploy.log'"
echo ""
echo -e "${YELLOW}To check status:${NC}"
echo "  ssh -i $SSH_KEY_PATH $SSH_USER@$INSTANCE_IP 'cd /opt/wordpress-multisite && sudo docker-compose ps'"
echo ""
echo -e "${YELLOW}Your site will be at:${NC}"
echo -e "${BLUE}http://$INSTANCE_IP${NC}"
echo ""
echo -e "${YELLOW}Deployment typically takes 10-15 minutes${NC}"

