#!/bin/bash
# Create a nested site via wp-cli
# Usage: ./create-nested-site.sh <parent_path> <child_slug> <title>

set -e

PARENT_PATH="${1:-/}"
CHILD_SLUG="${2}"
TITLE="${3:-Nested Site}"

if [ -z "$CHILD_SLUG" ]; then
    echo "Usage: $0 <parent_path> <child_slug> <title>"
    echo "Example: $0 /parent1/ mysite \"My Site\""
    exit 1
fi

# Normalize parent path
PARENT_PATH=$(echo "$PARENT_PATH" | sed 's|/*$|/|' | sed 's|^/*|/|')

# Calculate nested path
NESTED_PATH="${PARENT_PATH}${CHILD_SLUG}/"

echo "Creating nested site..."
echo "  Parent: $PARENT_PATH"
echo "  Child slug: $CHILD_SLUG"
echo "  Nested path: $NESTED_PATH"
echo "  Title: $TITLE"

# Create with temporary slug, then update to nested path
TEMP_SLUG="temp-$(date +%s)"
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp --allow-root site create \
    --slug="$TEMP_SLUG" \
    --title="$TITLE" \
    --email="admin@site3.localwp" \
    --skip-email

# Get the blog_id (would need to query or use a PHP script for this)
echo "⚠️  Note: This script creates the site but path update should be done via PHP"
echo "   Use the WordPress admin UI or a PHP script to set the nested path"


