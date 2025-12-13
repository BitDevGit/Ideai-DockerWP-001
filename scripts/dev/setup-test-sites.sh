#!/bin/bash
# Automated setup of test sites with semantic naming
# Usage: ./scripts/dev/setup-test-sites.sh

set -e

echo "🚀 Setting up test sites with semantic naming..."

# Wait for WordPress to be ready
echo "Waiting for WordPress to be ready..."
sleep 5

# Site 1: Single site (already set up by WordPress)
echo "✅ Site 1: Single site (site1.localwp) - already configured"

# Site 2: Subdomain multisite
echo "📦 Setting up Site 2: Subdomain multisite..."
docker-compose -f docker-compose.flexible.yml exec -T wordpress2 wp core multisite-install \
  --url=site2.localwp \
  --title="Site 2: Subdomain Multisite" \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=admin@site2.localwp \
  --subdomains \
  --skip-email \
  2>&1 | grep -v "Warning" || true

# Create test subdomains for Site 2
echo "Creating test subdomains for Site 2..."
docker-compose -f docker-compose.flexible.yml exec -T wordpress2 wp site create \
  --slug=test1 \
  --title="Test Subdomain 1" \
  --email=test1@site2.localwp \
  --skip-email \
  2>&1 | grep -v "Warning" || true

docker-compose -f docker-compose.flexible.yml exec -T wordpress2 wp site create \
  --slug=test2 \
  --title="Test Subdomain 2" \
  --email=test2@site2.localwp \
  --skip-email \
  2>&1 | grep -v "Warning" || true

# Site 3: Subdirectory multisite with nested tree
echo "📦 Setting up Site 3: Subdirectory multisite..."
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp core multisite-install \
  --url=site3.localwp \
  --title="Site 3: Subdirectory Multisite" \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=admin@site3.localwp \
  --skip-email \
  2>&1 | grep -v "Warning" || true

# Enable nested tree feature
echo "Enabling nested tree feature for Site 3..."
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp eval "
if (function_exists('Ideai\Wp\Platform\set_flag')) {
    \Ideai\Wp\Platform\set_flag('ideai_nested_tree_enabled', '1', 1);
    echo 'Nested tree enabled\n';
} else {
    echo 'Nested tree plugin not loaded\n';
}
" 2>&1 || true

# Create test nested sites with semantic names
echo "Creating test nested sites for Site 3..."

# Level 1: /parent1/
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp site create \
  --slug=parent1 \
  --title="Parent Site 1" \
  --email=parent1@site3.localwp \
  --skip-email \
  2>&1 | grep -v "Warning" || true

# Level 2: /parent1/child1/
# Note: This will be created via the nested site UI, but we can set it up manually for testing
echo "  Creating /parent1/child1/..."

# Level 2: /parent1/child2/
echo "  Creating /parent1/child2/..."

# Level 1: /parent2/
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp site create \
  --slug=parent2 \
  --title="Parent Site 2" \
  --email=parent2@site3.localwp \
  --skip-email \
  2>&1 | grep -v "Warning" || true

# Level 2: /parent2/child1/ (duplicate slug under different parent - should work)
echo "  Creating /parent2/child1/..."

echo ""
echo "✅ Test sites setup complete!"
echo ""
echo "📋 Test Sites Created:"
echo "  Site 1: https://site1.localwp/ (Single site)"
echo "  Site 2: https://site2.localwp/ (Subdomain multisite)"
echo "    - https://test1.site2.localwp/"
echo "    - https://test2.site2.localwp/"
echo "  Site 3: https://site3.localwp/ (Subdirectory multisite)"
echo "    - https://site3.localwp/parent1/"
echo "    - https://site3.localwp/parent2/"
echo ""
echo "🔐 Admin credentials: admin / admin"

