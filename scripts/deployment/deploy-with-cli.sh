#!/bin/bash
set -e

# Automated Lightsail Deployment Script
# Requires: AWS CLI configured with Lightsail permissions

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
INSTANCE_NAME="${AWS_LIGHTSAIL_INSTANCE_NAME:-wordpress-multisite}"
REGION="${AWS_REGION:-us-east-1}"
BLUEPRINT_ID="ubuntu_22_04"
BUNDLE_ID="nano_2_0"  # 2GB RAM, $10/month

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}WordPress Multisite Lightsail Deployment${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check AWS CLI
if ! command -v aws &> /dev/null; then
    echo -e "${RED}✗ AWS CLI not found. Please install it first.${NC}"
    exit 1
fi

# Test AWS credentials
echo -e "${YELLOW}Testing AWS credentials...${NC}"
if ! aws sts get-caller-identity &>/dev/null; then
    echo -e "${RED}✗ AWS credentials not configured. Run 'aws configure'${NC}"
    exit 1
fi
echo -e "${GREEN}✓ AWS credentials OK${NC}"

# Test Lightsail access
echo -e "${YELLOW}Testing Lightsail access...${NC}"
if ! aws lightsail get-regions --region "$REGION" &>/dev/null; then
    echo -e "${RED}✗ No Lightsail access. Please add IAM permissions.${NC}"
    echo -e "${YELLOW}See IAM_PERMISSIONS_NEEDED.md for instructions${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Lightsail access OK${NC}"

# Check if instance exists
echo -e "${YELLOW}Checking for existing instance: $INSTANCE_NAME...${NC}"
INSTANCE_EXISTS=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" 2>/dev/null || echo "")

if [ -z "$INSTANCE_EXISTS" ]; then
    echo -e "${YELLOW}Instance not found. Creating new instance...${NC}"
    
    # Get availability zone
    AZ=$(aws lightsail get-regions --region "$REGION" --query 'regions[0].availabilityZones[0].name' --output text)
    
    echo -e "${BLUE}Creating Lightsail instance:${NC}"
    echo "  Name: $INSTANCE_NAME"
    echo "  Blueprint: $BLUEPRINT_ID"
    echo "  Bundle: $BUNDLE_ID (2GB RAM, \$10/month)"
    echo "  Region: $REGION"
    echo ""
    
    read -p "Continue? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Deployment cancelled.${NC}"
        exit 0
    fi
    
    # Create instance
    aws lightsail create-instances \
        --instance-names "$INSTANCE_NAME" \
        --availability-zone "$AZ" \
        --blueprint-id "$BLUEPRINT_ID" \
        --bundle-id "$BUNDLE_ID" \
        --region "$REGION" \
        --output json
    
    echo -e "${GREEN}✓ Instance creation started${NC}"
    echo -e "${YELLOW}Waiting for instance to be running (this may take 2-3 minutes)...${NC}"
    
    # Wait for instance to be running
    while true; do
        STATE=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" \
            --query 'instance.state.name' --output text 2>/dev/null || echo "pending")
        
        if [ "$STATE" == "running" ]; then
            echo -e "${GREEN}✓ Instance is running!${NC}"
            break
        fi
        
        echo -n "."
        sleep 5
    done
    echo ""
else
    echo -e "${GREEN}✓ Instance found${NC}"
    
    # Check if running
    STATE=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" \
        --query 'instance.state.name' --output text)
    
    if [ "$STATE" != "running" ]; then
        echo -e "${YELLOW}Instance is $STATE. Starting...${NC}"
        aws lightsail start-instance --instance-name "$INSTANCE_NAME" --region "$REGION"
        sleep 10
    fi
fi

# Get instance details
echo -e "${YELLOW}Getting instance details...${NC}"
INSTANCE_IP=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" \
    --query 'instance.publicIpAddress' --output text)

if [ -z "$INSTANCE_IP" ] || [ "$INSTANCE_IP" == "None" ]; then
    echo -e "${RED}✗ Could not get instance IP address${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Instance IP: $INSTANCE_IP${NC}"

# Get SSH key info
echo -e "${YELLOW}Getting SSH access details...${NC}"
SSH_KEY_NAME=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" \
    --query 'instance.sshKeyName' --output text)

echo -e "${GREEN}✓ SSH Key: $SSH_KEY_NAME${NC}"

# Check if we can get access details
echo -e "${YELLOW}Getting SSH connection info...${NC}"
ACCESS_DETAILS=$(aws lightsail get-instance-access-details \
    --instance-name "$INSTANCE_NAME" \
    --region "$REGION" \
    --protocol ssh 2>/dev/null || echo "")

if [ -z "$ACCESS_DETAILS" ]; then
    echo -e "${YELLOW}⚠ Could not get automatic SSH details${NC}"
    echo -e "${YELLOW}You'll need to download the SSH key from Lightsail console${NC}"
    SSH_USER="ubuntu"
else
    SSH_USER=$(echo "$ACCESS_DETAILS" | grep -oP '"username":\s*"\K[^"]*' || echo "ubuntu")
    echo -e "${GREEN}✓ SSH User: $SSH_USER${NC}"
fi

# Prepare deployment package
echo -e "${YELLOW}Preparing deployment package...${NC}"
if [ -f "deployment-package.tar.gz" ]; then
    echo -e "${GREEN}✓ Using existing deployment package${NC}"
else
    if [ -f "scripts/prepare-deployment.sh" ]; then
        ./scripts/prepare-deployment.sh
    else
        echo -e "${RED}✗ Deployment package not found. Run prepare-deployment.sh first${NC}"
        exit 1
    fi
fi

# Display next steps
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}Deployment Ready!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${YELLOW}Instance Details:${NC}"
echo "  Name: $INSTANCE_NAME"
echo "  IP: $INSTANCE_IP"
echo "  SSH User: $SSH_USER"
echo "  SSH Key: $SSH_KEY_NAME"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo ""
echo "1. Download SSH key from Lightsail console:"
echo "   https://lightsail.aws.amazon.com"
echo "   → Click on instance → Connect → Download default key"
echo ""
echo "2. Upload deployment package:"
echo "   scp -i ~/Downloads/$SSH_KEY_NAME.pem deployment-package.tar.gz $SSH_USER@$INSTANCE_IP:/tmp/"
echo ""
echo "3. SSH into instance:"
echo "   ssh -i ~/Downloads/$SSH_KEY_NAME.pem $SSH_USER@$INSTANCE_IP"
echo ""
echo "4. On the instance, run:"
echo "   cd /opt"
echo "   sudo mkdir -p wordpress-multisite"
echo "   cd wordpress-multisite"
echo "   sudo tar -xzf /tmp/deployment-package.tar.gz"
echo "   sudo chown -R \$USER:\$USER ."
echo "   cp .env.example .env"
echo "   nano .env  # Configure your settings"
echo "   docker-compose up -d"
echo ""
echo -e "${GREEN}After deployment, access your site at:${NC}"
echo -e "${BLUE}http://$INSTANCE_IP${NC}"
echo ""

