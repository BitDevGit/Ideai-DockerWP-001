#!/bin/bash
# Build WordPress image with wp-content included
# Usage:
#   ./build-with-content.sh [wp-content-source] [wp-content-repo-url]
#   ./build-with-content.sh ./wp-content
#   ./build-with-content.sh "" https://github.com/user/wp-content-repo.git

set -e

WP_CONTENT_SOURCE="${1:-./wp-content}"
WP_CONTENT_REPO="${2:-}"
IMAGE_NAME="${IMAGE_NAME:-wordpress-multisite}"
IMAGE_TAG="${IMAGE_TAG:-latest}"
BUILD_CONTEXT="${BUILD_CONTEXT:-.}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Building WordPress Image with wp-content${NC}"
echo ""

# If repo URL provided, clone it
if [ -n "$WP_CONTENT_REPO" ]; then
    echo -e "${YELLOW}Fetching wp-content from repository...${NC}"
    TEMP_DIR=$(mktemp -d)
    trap "rm -rf $TEMP_DIR" EXIT
    
    git clone "$WP_CONTENT_REPO" "$TEMP_DIR/wp-content" || {
        echo -e "${RED}Error: Failed to clone repository${NC}"
        exit 1
    }
    
    WP_CONTENT_SOURCE="$TEMP_DIR/wp-content"
    echo -e "${GREEN}✓ Cloned wp-content from repository${NC}"
fi

# Verify wp-content exists
if [ ! -d "$WP_CONTENT_SOURCE" ]; then
    echo -e "${RED}Error: wp-content directory not found: $WP_CONTENT_SOURCE${NC}"
    echo ""
    echo "Usage:"
    echo "  $0 [local-path]                    # Use local wp-content"
    echo "  $0 \"\" [repo-url]                   # Clone from repository"
    exit 1
fi

# Verify required directories exist
if [ ! -d "$WP_CONTENT_SOURCE/plugins" ] || [ ! -d "$WP_CONTENT_SOURCE/themes" ]; then
    echo -e "${YELLOW}Warning: plugins or themes directory missing${NC}"
    echo "Creating missing directories..."
    mkdir -p "$WP_CONTENT_SOURCE"/{plugins,themes}
fi

# Count plugins and themes
PLUGIN_COUNT=$(find "$WP_CONTENT_SOURCE/plugins" -mindepth 1 -maxdepth 1 -type d 2>/dev/null | wc -l | tr -d ' ')
THEME_COUNT=$(find "$WP_CONTENT_SOURCE/themes" -mindepth 1 -maxdepth 1 -type d 2>/dev/null | wc -l | tr -d ' ')

echo -e "${BLUE}wp-content summary:${NC}"
echo "  Plugins: $PLUGIN_COUNT"
echo "  Themes: $THEME_COUNT"
echo ""

# Build image
echo -e "${YELLOW}Building Docker image...${NC}"
echo "  Image: ${IMAGE_NAME}:${IMAGE_TAG}"
echo "  wp-content source: $WP_CONTENT_SOURCE"
echo ""

# Use absolute path for build context
WP_CONTENT_ABS=$(cd "$WP_CONTENT_SOURCE" && pwd)

docker build \
    --build-arg WP_CONTENT_SOURCE="$WP_CONTENT_ABS" \
    -f wordpress/Dockerfile.production \
    -t "${IMAGE_NAME}:${IMAGE_TAG}" \
    "$BUILD_CONTEXT" || {
    echo -e "${RED}Build failed${NC}"
    exit 1
}

echo ""
echo -e "${GREEN}✓ Build complete: ${IMAGE_NAME}:${IMAGE_TAG}${NC}"
echo ""
echo "To use this image, update docker-compose.yml:"
echo "  wordpress:"
echo "    image: ${IMAGE_NAME}:${IMAGE_TAG}"



