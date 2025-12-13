#!/bin/bash
# Create a nested site via wp-cli (for testing)
# Usage: ./scripts/dev/create-nested-site.sh <parent_path> <child_slug> [title]
# Example: ./scripts/dev/create-nested-site.sh /parent1/ child1 "Child Site 1"

set -e

PARENT_PATH="${1:-/}"
CHILD_SLUG="${2:-test}"
TITLE="${3:-Nested Site}"

if [ "$PARENT_PATH" = "/" ]; then
  FULL_PATH="/${CHILD_SLUG}/"
else
  FULL_PATH="${PARENT_PATH}${CHILD_SLUG}/"
fi

echo "Creating nested site: ${FULL_PATH}"

# Create site with temporary slug (WordPress will accept this)
TEMP_SLUG="${CHILD_SLUG}"

docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp site create \
  --slug="${TEMP_SLUG}" \
  --title="${TITLE}" \
  --email="${CHILD_SLUG}@site3.localwp" \
  --skip-email \
  2>&1 | grep -v "Warning" || true

# Get the blog_id of the newly created site
BLOG_ID=$(docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp db query \
  "SELECT blog_id FROM wp_blogs WHERE path LIKE '%/${TEMP_SLUG}/%' ORDER BY blog_id DESC LIMIT 1" \
  --skip-column-names 2>/dev/null | tail -1 | tr -d ' ')

if [ -z "$BLOG_ID" ] || [ "$BLOG_ID" = "NULL" ]; then
  echo "❌ Failed to find created site"
  exit 1
fi

echo "Found blog_id: ${BLOG_ID}"

# Update path in database
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp db query \
  "UPDATE wp_blogs SET path = '${FULL_PATH}' WHERE blog_id = ${BLOG_ID}" 2>/dev/null || true

# Update nested tree mapping
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp eval "
require_once '/var/www/html/wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';
if (function_exists('Ideai\Wp\Platform\NestedTree\upsert_blog_path')) {
    \Ideai\Wp\Platform\NestedTree\upsert_blog_path(${BLOG_ID}, '${FULL_PATH}', 1);
    echo 'Nested path mapping created\n';
} else {
    echo 'Nested tree functions not available\n';
}
" 2>&1 || true

# Clear cache
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp cache flush 2>/dev/null || true

echo "✅ Created nested site: https://site3.localwp${FULL_PATH}"
echo "   Blog ID: ${BLOG_ID}"
echo "   Path: ${FULL_PATH}"

