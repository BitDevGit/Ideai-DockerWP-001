#!/bin/bash
set -e

# Prepare Deployment Package for Lightsail
# This script prepares everything needed for manual deployment

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}Preparing deployment package for AWS Lightsail...${NC}"

# Create deployment directory
DEPLOY_DIR="deployment-package"
rm -rf "$DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

echo -e "${YELLOW}Copying files...${NC}"

# Copy essential files
cp docker-compose.yml "$DEPLOY_DIR/"
cp .env.example "$DEPLOY_DIR/.env.example"
cp -r nginx "$DEPLOY_DIR/"
cp -r wordpress "$DEPLOY_DIR/"
cp -r scripts "$DEPLOY_DIR/"
cp Makefile "$DEPLOY_DIR/" 2>/dev/null || true
cp README.md "$DEPLOY_DIR/" 2>/dev/null || true
cp DEPLOYMENT.md "$DEPLOY_DIR/" 2>/dev/null || true

# Create deployment instructions
cat > "$DEPLOY_DIR/DEPLOY_INSTRUCTIONS.txt" << 'EOF'
# Quick Deployment Instructions

## On Your Lightsail Instance:

1. Upload this entire folder to /opt/wordpress-multisite on your Lightsail instance:
   scp -i ~/Downloads/your-key.pem -r deployment-package/* ubuntu@<instance-ip>:/opt/wordpress-multisite/

2. SSH into your instance:
   ssh -i ~/Downloads/your-key.pem ubuntu@<instance-ip>

3. Navigate to the directory:
   cd /opt/wordpress-multisite

4. Configure environment:
   cp .env.example .env
   nano .env
   # Update with your settings:
   # - Strong passwords
   # - Your domain (if you have one)
   # - Production settings

5. Start services:
   docker-compose up -d

6. Check status:
   docker-compose ps

7. Access your site:
   http://<your-instance-ip>
EOF

# Create a tarball
echo -e "${YELLOW}Creating deployment archive...${NC}"
tar -czf deployment-package.tar.gz -C "$DEPLOY_DIR" .

echo -e "${GREEN}✓ Deployment package created: deployment-package.tar.gz${NC}"
echo -e "${GREEN}✓ Deployment directory: $DEPLOY_DIR/${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Upload deployment-package.tar.gz to your Lightsail instance"
echo "2. Extract: tar -xzf deployment-package.tar.gz"
echo "3. Follow instructions in DEPLOY_INSTRUCTIONS.txt"

