#!/bin/bash
set -e

# Deploy WordPress to Lightsail Instance

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
    echo "Example: $0 wordpress-multisite 18.130.255.19 ubuntu ~/Downloads/key.pem"
    exit 1
fi

echo -e "${BLUE}Deploying WordPress to Lightsail Instance${NC}"
echo "Instance: $INSTANCE_NAME"
echo "IP: $INSTANCE_IP"
echo "User: $SSH_USER"
echo ""

# Check deployment package
if [ ! -f "deployment-package.tar.gz" ]; then
    echo -e "${YELLOW}Creating deployment package...${NC}"
    ./scripts/prepare-deployment.sh
fi

# Get SSH key path
if [ -z "$SSH_KEY_PATH" ]; then
    SSH_KEY_PATH="${HOME}/Downloads/LightsailDefaultKeyPair-${INSTANCE_NAME}.pem"
    if [ ! -f "$SSH_KEY_PATH" ]; then
        # Try common locations
        for loc in "${HOME}/Downloads/LightsailDefaultKeyPair.pem" "${HOME}/Downloads/${INSTANCE_NAME}.pem" "/tmp/lightsail-key.pem"; do
            if [ -f "$loc" ]; then
                SSH_KEY_PATH="$loc"
                break
            fi
        done
    fi
fi

if [ ! -f "$SSH_KEY_PATH" ]; then
    echo -e "${YELLOW}SSH key not found${NC}"
    echo -e "${YELLOW}Please download it from Lightsail console:${NC}"
    echo "https://lightsail.aws.amazon.com/ls/webapp/eu-west-2/instances/$INSTANCE_NAME/connect"
    echo ""
    read -p "Enter path to SSH key: " SSH_KEY_PATH
fi

if [ ! -f "$SSH_KEY_PATH" ]; then
    echo -e "${RED}SSH key not found: $SSH_KEY_PATH${NC}"
    exit 1
fi

chmod 400 "$SSH_KEY_PATH"

echo -e "${YELLOW}Testing SSH connection...${NC}"
if ! ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$SSH_USER@$INSTANCE_IP" "echo 'SSH OK'" &>/dev/null; then
    echo -e "${RED}✗ Cannot connect via SSH. Is the instance ready?${NC}"
    exit 1
fi
echo -e "${GREEN}✓ SSH connection OK${NC}"

echo -e "${YELLOW}Uploading deployment package...${NC}"
scp -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no deployment-package.tar.gz "$SSH_USER@$INSTANCE_IP:/tmp/" || {
    echo -e "${RED}✗ Upload failed${NC}"
    exit 1
}
echo -e "${GREEN}✓ Upload complete${NC}"

echo -e "${YELLOW}Deploying on remote instance...${NC}"
ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no "$SSH_USER@$INSTANCE_IP" << 'ENDSSH'
    set -e
    
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
    
    echo "Setting up application directory..."
    cd /opt
    sudo mkdir -p wordpress-multisite
    cd wordpress-multisite
    
    echo "Extracting deployment package..."
    sudo tar -xzf /tmp/deployment-package.tar.gz
    sudo chown -R $USER:$USER .
    
    echo "Configuring environment..."
    if [ ! -f .env ]; then
        cp .env.example .env
        # Generate secure passwords
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
    
    echo "Starting services..."
    docker-compose up -d --build
    
    echo "Waiting for services to start..."
    sleep 15
    
    echo "Checking service status..."
    docker-compose ps
    
    echo "Deployment complete!"
ENDSSH

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}Your WordPress site should be available at:${NC}"
echo -e "${BLUE}http://$INSTANCE_IP${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Open http://$INSTANCE_IP in your browser"
echo "2. Complete WordPress installation"
echo "3. Enable multisite network"
echo "4. Configure firewall in Lightsail console (ports 80, 443)"
echo ""

