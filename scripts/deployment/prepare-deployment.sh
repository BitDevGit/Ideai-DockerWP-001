#!/bin/bash
set -e

# Prepare Deployment Package for Lightsail
# This script prepares everything needed for manual deployment
# Includes wp-content in the WordPress image

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}Preparing deployment package for AWS Lightsail...${NC}"

# Check for wp-content
WP_CONTENT_SOURCE="${WP_CONTENT_SOURCE:-./wp-content}"
if [ ! -d "$WP_CONTENT_SOURCE" ]; then
    echo -e "${YELLOW}Warning: wp-content directory not found: $WP_CONTENT_SOURCE${NC}"
    echo "  Creating empty wp-content structure..."
    mkdir -p "$WP_CONTENT_SOURCE"/{plugins,themes,uploads}
fi

# Build WordPress image with wp-content (optional - can skip if using pre-built)
BUILD_IMAGE="${BUILD_IMAGE:-1}"
if [ "$BUILD_IMAGE" = "1" ]; then
    echo -e "${BLUE}Building WordPress image with wp-content...${NC}"
    if [ -f "scripts/build/build-with-content.sh" ]; then
        ./scripts/build/build-with-content.sh "$WP_CONTENT_SOURCE" || {
            echo -e "${YELLOW}Build failed, continuing without custom image...${NC}"
        }
    else
        echo -e "${YELLOW}Build script not found, skipping image build...${NC}"
    fi
fi

# Create deployment directory
DEPLOY_DIR="deployment-package"
rm -rf "$DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

echo -e "${YELLOW}Copying files...${NC}"

# Copy essential files
cp docker-compose.yml "$DEPLOY_DIR/"
cp .env.example "$DEPLOY_DIR/.env.example"
cp -r nginx "$DEPLOY_DIR/"
cp -r wordpress "$DEPLOY_DIR/"  # Includes Dockerfile.production
cp -r scripts "$DEPLOY_DIR/"
cp Makefile "$DEPLOY_DIR/" 2>/dev/null || true
cp README.md "$DEPLOY_DIR/" 2>/dev/null || true

# Copy wp-content structure (for reference, not included in image)
if [ -d "$WP_CONTENT_SOURCE" ]; then
    echo -e "${YELLOW}Including wp-content structure...${NC}"
    mkdir -p "$DEPLOY_DIR/wp-content"
    # Copy structure but not uploads
    rsync -a --exclude='uploads/*' --exclude='cache/*' \
        "$WP_CONTENT_SOURCE/" "$DEPLOY_DIR/wp-content/" 2>/dev/null || \
        cp -r "$WP_CONTENT_SOURCE" "$DEPLOY_DIR/" 2>/dev/null || true
fi

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

5. Build WordPress image with wp-content (if using Dockerfile.production):
   docker build -f wordpress/Dockerfile.production -t wordpress-multisite:latest .
   # OR use pre-built image and update docker-compose.yml

6. Start services:
   docker-compose up -d

7. Check status:
   docker-compose ps

8. Access your site:
   http://<your-instance-ip>

## Note on wp-content:
- Plugins and themes are included in the Docker image
- Uploads use a separate Docker volume
- To update plugins/themes, rebuild the image
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


