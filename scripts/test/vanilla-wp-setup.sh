#!/bin/bash
# Setup vanilla WordPress multisite for testing
# Usage: ./scripts/test/vanilla-wp-setup.sh

set -e

echo "🧪 Setting up Vanilla WordPress Test Environment"
echo "=================================================="
echo ""

# Start containers
echo "Step 1: Starting containers..."
docker-compose -f docker-compose.vanilla-test.yml up -d

echo ""
echo "Step 2: Waiting for database..."
sleep 10

# Install WP-CLI in container
echo ""
echo "Step 3: Installing WP-CLI..."
docker-compose -f docker-compose.vanilla-test.yml exec -T wordpress-vanilla bash -c "
  apt-get update -qq && \
  apt-get install -y -qq default-mysql-client >/dev/null 2>&1 && \
  cd /tmp && \
  curl -s -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
  chmod +x wp-cli.phar && \
  mv wp-cli.phar /usr/local/bin/wp 2>/dev/null || true
" 2>&1 | grep -v "Warning" || true

echo ""
echo "Step 4: Waiting for WordPress to be ready..."
sleep 5

echo ""
echo "Step 5: Installing WordPress..."
# Check if WordPress is already installed
if docker-compose -f docker-compose.vanilla-test.yml exec -T wordpress-vanilla wp --allow-root core is-installed 2>/dev/null; then
  echo "  WordPress already installed, skipping..."
else
  docker-compose -f docker-compose.vanilla-test.yml exec -T wordpress-vanilla wp --allow-root core install \
    --url=http://vanilla.localwp:8080 \
    --title="Vanilla WordPress Test" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@vanilla.localwp \
    --skip-email \
    2>&1 | grep -v "Warning" || echo "Installation may need manual setup"
fi

echo ""
echo "Step 6: Checking multisite status..."
if docker-compose -f docker-compose.vanilla-test.yml exec -T wordpress-vanilla wp --allow-root core is-installed --network 2>/dev/null; then
  echo "  Multisite already enabled, skipping..."
else
  echo "  Enabling multisite (subdirectory)..."
  docker-compose -f docker-compose.vanilla-test.yml exec -T wordpress-vanilla wp --allow-root core multisite-install \
    --url=http://vanilla.localwp:8080 \
    --title="Vanilla WordPress Multisite" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@vanilla.localwp \
    --skip-email \
    --subdomains=false \
    2>&1 | grep -v "Warning" || echo "Multisite setup may need manual configuration"
fi

echo ""
echo "✅ Vanilla WordPress setup complete!"
echo ""
echo "📋 Access:"
echo "  Frontend: http://vanilla.localwp:8080"
echo "  Admin: http://vanilla.localwp:8080/wp-admin/ (admin/admin)"
echo ""
echo "🧪 Test wp-admin:"
echo "  curl -I http://vanilla.localwp:8080/wp-admin/"
echo ""

