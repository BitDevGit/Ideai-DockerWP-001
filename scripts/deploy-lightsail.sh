#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
INSTANCE_NAME="${AWS_LIGHTSAIL_INSTANCE_NAME:-wordpress-multisite}"
REGION="${AWS_REGION:-us-east-1}"
SSH_USER="bitnami"  # Default for Lightsail WordPress instances

echo -e "${GREEN}Starting deployment to AWS Lightsail...${NC}"

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    echo -e "${RED}AWS CLI is not installed. Please install it first.${NC}"
    exit 1
fi

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker is not installed. Please install it first.${NC}"
    exit 1
fi

# Get instance IP
echo -e "${YELLOW}Getting Lightsail instance information...${NC}"
INSTANCE_INFO=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" 2>/dev/null || echo "")

if [ -z "$INSTANCE_INFO" ]; then
    echo -e "${RED}Instance $INSTANCE_NAME not found. Creating new instance...${NC}"
    echo -e "${YELLOW}Please create the instance manually or update the script.${NC}"
    exit 1
fi

INSTANCE_IP=$(echo "$INSTANCE_INFO" | grep -oP '"publicIpAddress":\s*"\K[^"]*' || echo "")

if [ -z "$INSTANCE_IP" ]; then
    echo -e "${RED}Could not retrieve instance IP address.${NC}"
    exit 1
fi

echo -e "${GREEN}Instance IP: $INSTANCE_IP${NC}"

# Create deployment package
echo -e "${YELLOW}Creating deployment package...${NC}"
tar -czf deployment.tar.gz \
    docker-compose.yml \
    .env.example \
    nginx/ \
    wordpress/ \
    scripts/ \
    --exclude='*.log' \
    --exclude='node_modules' \
    --exclude='.git'

# Copy files to instance
echo -e "${YELLOW}Copying files to Lightsail instance...${NC}"
scp -i ~/.ssh/lightsail-key.pem deployment.tar.gz $SSH_USER@$INSTANCE_IP:/tmp/

# Deploy on remote instance
echo -e "${YELLOW}Deploying on remote instance...${NC}"
ssh -i ~/.ssh/lightsail-key.pem $SSH_USER@$INSTANCE_IP << 'ENDSSH'
    set -e
    cd /opt
    sudo mkdir -p wordpress-multisite
    sudo chown $USER:$USER wordpress-multisite
    cd wordpress-multisite
    
    # Extract deployment package
    tar -xzf /tmp/deployment.tar.gz
    
    # Copy .env if it doesn't exist
    if [ ! -f .env ]; then
        cp .env.example .env
        echo "Please update .env file with your configuration"
    fi
    
    # Stop existing containers
    docker-compose down || true
    
    # Pull latest images
    docker-compose pull
    
    # Start services
    docker-compose up -d --build
    
    # Wait for services to be ready
    sleep 10
    
    # Run WordPress database updates
    docker-compose exec -T wordpress wp core update-db --allow-root || true
    
    # Clean up
    rm -f /tmp/deployment.tar.gz
    
    echo "Deployment completed successfully!"
ENDSSH

# Clean up local deployment package
rm -f deployment.tar.gz

echo -e "${GREEN}Deployment completed!${NC}"
echo -e "${GREEN}Your WordPress multisite is available at: https://$INSTANCE_IP${NC}"

