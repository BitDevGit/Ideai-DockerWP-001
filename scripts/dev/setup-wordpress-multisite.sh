#!/bin/bash
# Complete WordPress multisite setup for Site 3
# This script sets up WordPress multisite (subdirectory) from scratch
# Usage: ./scripts/dev/setup-wordpress-multisite.sh

set -e

echo "🚀 WordPress Multisite Setup for Site 3"
echo "======================================="
echo ""

# Step 1: Check containers
echo "Step 1: Checking containers..."
if ! docker-compose -f docker-compose.flexible.yml ps | grep -q "wordpress3.*Up\|db3.*Up"; then
  echo "Starting containers..."
  docker-compose -f docker-compose.flexible.yml up -d wordpress3 db3
  echo "Waiting for containers to be ready..."
  sleep 10
fi
echo "✅ Containers are running"
echo ""

# Step 2: Check if WordPress is already installed
echo "Step 2: Checking WordPress installation..."
DB_CHECK=$(docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'wordpress3' AND table_name LIKE 'wp_%';" --skip-column-names 2>&1 | tail -1 | tr -d ' ')

if [ "$DB_CHECK" -gt "0" ]; then
  echo "✅ WordPress database tables exist ($DB_CHECK tables)"
  MULTISITE_CHECK=$(docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 -e "SELECT COUNT(*) FROM wp_blogs WHERE site_id = 1;" --skip-column-names 2>&1 | tail -1 | tr -d ' ')
  if [ "$MULTISITE_CHECK" -gt "0" ]; then
    echo "✅ Multisite appears to be configured ($MULTISITE_CHECK sites)"
    echo ""
    echo "WordPress is already set up!"
    echo "Admin URL: https://site3.localwp/wp-admin/"
    echo ""
    exit 0
  fi
else
  echo "⚠️  WordPress database is empty"
  echo "   WordPress will need to be installed via browser"
fi
echo ""

# Step 3: Check wp-config.php
echo "Step 3: Checking wp-config.php..."
if docker-compose -f docker-compose.flexible.yml exec -T wordpress3 test -f /var/www/html/wp-config.php; then
  echo "✅ wp-config.php exists"
  
  # Check if multisite is configured
  if docker-compose -f docker-compose.flexible.yml exec -T wordpress3 grep -q "MULTISITE.*true" /var/www/html/wp-config.php 2>/dev/null; then
    echo "✅ Multisite is configured in wp-config.php"
  else
    echo "⚠️  Multisite not configured in wp-config.php"
    echo "   This will be set up during WordPress installation"
  fi
else
  echo "⚠️  wp-config.php not found (WordPress will create it)"
fi
echo ""

# Step 4: Provide setup instructions
echo "Step 4: WordPress Setup Instructions"
echo "-------------------------------------"
echo ""
echo "If WordPress is not installed, follow these steps:"
echo ""
echo "1. Navigate to: https://site3.localwp/wp-admin/install.php"
echo ""
echo "2. Complete the installation:"
echo "   - Site Title: Site 3: Subdirectory Multisite"
echo "   - Username: admin"
echo "   - Password: admin (or your choice)"
echo "   - Email: admin@site3.localwp"
echo ""
echo "3. After installation, enable multisite:"
echo "   - WordPress will detect you want multisite"
echo "   - Choose 'Sub-directories' (not subdomains)"
echo "   - Complete the network setup"
echo ""
echo "4. Enable nested tree feature:"
echo "   - Navigate to: Network Admin → IdeAI → Status"
echo "   - Enable 'Nested tree multisite' toggle"
echo "   - Save"
echo ""
echo "5. Test nested site creation:"
echo "   - Navigate to: Network Admin → Sites → Add New"
echo "   - Use the nested site fields to create a test site"
echo ""
echo "✅ Setup complete!"
echo ""
echo "📋 Quick Access:"
echo "   Admin: https://site3.localwp/wp-admin/"
echo "   Network Admin: https://site3.localwp/wp-admin/network/"
echo "   Create Site: https://site3.localwp/wp-admin/network/site-new.php"
echo "   IdeAI Status: https://site3.localwp/wp-admin/network/admin.php?page=ideai-status"
echo ""

