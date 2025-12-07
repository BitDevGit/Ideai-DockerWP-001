#!/bin/bash
set -e

# Create New Lightsail Instance in London and Deploy WordPress
# Region: eu-west-2 (London)

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
INSTANCE_NAME="${AWS_LIGHTSAIL_INSTANCE_NAME:-wordpress-multisite}"
REGION="eu-west-2"  # London
BLUEPRINT_ID="ubuntu_22_04"
BUNDLE_ID="nano_2_0"  # 2GB RAM, $10/month (minimum recommended)

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Create & Deploy WordPress to London${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check AWS CLI
if ! command -v aws &> /dev/null; then
    echo -e "${RED}✗ AWS CLI not found${NC}"
    exit 1
fi

# Test credentials
echo -e "${YELLOW}Testing AWS credentials...${NC}"
if ! aws sts get-caller-identity &>/dev/null; then
    echo -e "${RED}✗ AWS credentials not configured${NC}"
    exit 1
fi
echo -e "${GREEN}✓ AWS credentials OK${NC}"

# Check if instance already exists
echo -e "${YELLOW}Checking for existing instance: $INSTANCE_NAME...${NC}"
EXISTING=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" 2>/dev/null || echo "")

if [ -n "$EXISTING" ]; then
    STATE=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" \
        --query 'instance.state.name' --output text)
    echo -e "${YELLOW}⚠ Instance '$INSTANCE_NAME' already exists (state: $STATE)${NC}"
    read -p "Delete and recreate? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Deleting existing instance...${NC}"
        aws lightsail delete-instance --instance-name "$INSTANCE_NAME" --region "$REGION" || true
        echo -e "${YELLOW}Waiting 30 seconds for cleanup...${NC}"
        sleep 30
    else
        echo -e "${YELLOW}Using existing instance${NC}"
        INSTANCE_IP=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" \
            --query 'instance.publicIpAddress' --output text)
        echo -e "${GREEN}✓ Instance IP: $INSTANCE_IP${NC}"
        echo -e "${YELLOW}Run deployment script separately to deploy${NC}"
        exit 0
    fi
fi

# Get availability zone
echo -e "${YELLOW}Getting availability zone...${NC}"
AZ=$(aws lightsail get-regions --query "regions[?name=='$REGION'].availabilityZones[0].name" --output text)
if [ -z "$AZ" ] || [ "$AZ" == "None" ]; then
    # Fallback to default
    AZ="${REGION}a"
fi
echo -e "${GREEN}✓ Availability Zone: $AZ${NC}"

# Create instance
echo ""
echo -e "${BLUE}Creating Lightsail instance:${NC}"
echo "  Name: $INSTANCE_NAME"
echo "  Region: $REGION (London)"
echo "  Blueprint: $BLUEPRINT_ID (Ubuntu 22.04)"
echo "  Bundle: $BUNDLE_ID (2GB RAM, \$10/month)"
echo "  Zone: $AZ"
echo ""

# Auto-confirm if AUTO_CONFIRM is set, otherwise ask
if [ "${AUTO_CONFIRM:-}" != "yes" ]; then
    read -p "Continue? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Cancelled${NC}"
        exit 0
    fi
else
    echo -e "${GREEN}Auto-confirming (AUTO_CONFIRM=yes)${NC}"
fi

echo -e "${YELLOW}Creating instance (this takes 2-3 minutes)...${NC}"
aws lightsail create-instances \
    --instance-names "$INSTANCE_NAME" \
    --availability-zone "$AZ" \
    --blueprint-id "$BLUEPRINT_ID" \
    --bundle-id "$BUNDLE_ID" \
    --region "$REGION" \
    --output json > /tmp/lightsail-create.json

echo -e "${GREEN}✓ Instance creation started${NC}"

# Wait for instance to be running
echo -e "${YELLOW}Waiting for instance to be running...${NC}"
MAX_WAIT=300  # 5 minutes
ELAPSED=0
while [ $ELAPSED -lt $MAX_WAIT ]; do
    STATE=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" \
        --query 'instance.state.name' --output text 2>/dev/null || echo "pending")
    
    if [ "$STATE" == "running" ]; then
        echo -e "${GREEN}✓ Instance is running!${NC}"
        break
    fi
    
    echo -n "."
    sleep 5
    ELAPSED=$((ELAPSED + 5))
done
echo ""

if [ "$STATE" != "running" ]; then
    echo -e "${RED}✗ Instance did not start in time (state: $STATE)${NC}"
    exit 1
fi

# Get instance IP
echo -e "${YELLOW}Getting instance details...${NC}"
INSTANCE_IP=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" \
    --query 'instance.publicIpAddress' --output text)

if [ -z "$INSTANCE_IP" ] || [ "$INSTANCE_IP" == "None" ]; then
    echo -e "${RED}✗ Could not get instance IP${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Instance IP: $INSTANCE_IP${NC}"

# Get SSH access details
echo -e "${YELLOW}Getting SSH access details...${NC}"
SSH_DETAILS=$(aws lightsail get-instance-access-details \
    --instance-name "$INSTANCE_NAME" \
    --region "$REGION" \
    --protocol ssh 2>/dev/null)

SSH_USER=$(echo "$SSH_DETAILS" | grep -oP '"username":\s*"\K[^"]*' || echo "ubuntu")
SSH_KEY_NAME=$(aws lightsail get-instance --instance-name "$INSTANCE_NAME" --region "$REGION" \
    --query 'instance.sshKeyName' --output text)

echo -e "${GREEN}✓ SSH User: $SSH_USER${NC}"
echo -e "${GREEN}✓ SSH Key: $SSH_KEY_NAME${NC}"

# Wait a bit more for SSH to be ready
echo -e "${YELLOW}Waiting for SSH to be ready (30 seconds)...${NC}"
sleep 30

# Display next steps
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}Instance Created Successfully!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${YELLOW}Instance Details:${NC}"
echo "  Name: $INSTANCE_NAME"
echo "  IP: $INSTANCE_IP"
echo "  Region: $REGION (London)"
echo "  SSH User: $SSH_USER"
echo "  SSH Key: $SSH_KEY_NAME"
echo ""
echo -e "${YELLOW}Next: Deploy WordPress${NC}"
echo ""
echo "1. Download SSH key from Lightsail console:"
echo "   https://lightsail.aws.amazon.com/ls/webapp/$REGION/instances/$INSTANCE_NAME/connect"
echo ""
echo "2. Or use the deployment script:"
echo "   ./scripts/deploy-to-instance.sh $INSTANCE_NAME $INSTANCE_IP $SSH_USER"
echo ""
echo -e "${GREEN}Instance is ready for deployment!${NC}"
echo ""

