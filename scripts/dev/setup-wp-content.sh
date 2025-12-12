#!/bin/bash
# Setup wp-content for local development
# Can create directory or symlink to shared repository

set -e

WP_CONTENT_REPO="${1:-}"
WP_CONTENT_DIR="./wp-content"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}WordPress Content Setup${NC}"
echo ""

# Check if wp-content is already a symlink
if [ -L "$WP_CONTENT_DIR" ]; then
    CURRENT_LINK=$(readlink "$WP_CONTENT_DIR")
    echo -e "${YELLOW}wp-content is already a symlink to:${NC} $CURRENT_LINK"
    echo ""
    read -p "Replace with new symlink? (y/N): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm "$WP_CONTENT_DIR"
    else
        echo "Keeping existing symlink"
        exit 0
    fi
fi

# If no repo provided, create local directory
if [ -z "$WP_CONTENT_REPO" ]; then
    echo -e "${YELLOW}No repository provided. Creating local wp-content directory...${NC}"
    mkdir -p "$WP_CONTENT_DIR"/{plugins,themes,uploads}
    touch "$WP_CONTENT_DIR"/.gitkeep
    touch "$WP_CONTENT_DIR"/plugins/.gitkeep
    touch "$WP_CONTENT_DIR"/themes/.gitkeep
    echo -e "${GREEN}✓ Created local wp-content directory${NC}"
    echo ""
    echo "To use a shared repository, run:"
    echo "  $0 /path/to/wp-content-repo"
    exit 0
fi

# Check if repo exists
if [ ! -d "$WP_CONTENT_REPO" ]; then
    echo -e "${RED}Error: Repository directory not found: $WP_CONTENT_REPO${NC}"
    echo ""
    echo "Usage:"
    echo "  $0                    # Create local directory"
    echo "  $0 /path/to/repo      # Symlink to repository"
    exit 1
fi

# Create symlink
echo -e "${YELLOW}Creating symlink to shared repository...${NC}"
if [ -d "$WP_CONTENT_DIR" ] && [ ! -L "$WP_CONTENT_DIR" ]; then
    echo -e "${YELLOW}Existing wp-content directory found. Backing up...${NC}"
    mv "$WP_CONTENT_DIR" "${WP_CONTENT_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
fi

ln -s "$WP_CONTENT_REPO" "$WP_CONTENT_DIR"
echo -e "${GREEN}✓ Linked wp-content to: $WP_CONTENT_REPO${NC}"
echo ""
echo "Note: Uploads will still use Docker volume (wp_uploads)"
echo "      to avoid conflicts with the symlinked directory"



