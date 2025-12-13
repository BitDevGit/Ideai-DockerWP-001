#!/bin/bash
# Ensure WordPress is ready for testing
# Usage: ./scripts/dev/ensure-wordpress-ready.sh

set -e

echo "🔍 Checking WordPress setup..."

# Wait for containers
echo "Waiting for containers to be ready..."
docker-compose -f docker-compose.flexible.yml up -d wordpress3 db3
sleep 5

# Check if WordPress needs installation
echo "Checking WordPress installation status..."
INSTALLED=$(curl -s -k https://site3.localwp/wp-admin/install.php 2>&1 | grep -i "already installed" || echo "")

if [ -z "$INSTALLED" ]; then
  echo "⚠️  WordPress may need setup. Checking database..."
  
  # Check if we can connect to database
  DB_CHECK=$(docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 -e "SELECT 1;" 2>&1 | grep -i "error\|denied" || echo "ok")
  
  if [ "$DB_CHECK" != "ok" ]; then
    echo "❌ Database connection issue. Please check docker-compose configuration."
    exit 1
  fi
  
  echo "✅ Database is accessible"
  echo "📝 WordPress may need manual setup via browser"
  echo "   Navigate to: https://site3.localwp/wp-admin/install.php"
else
  echo "✅ WordPress appears to be installed"
fi

# Check if nested tree feature is enabled
echo ""
echo "Checking nested tree feature..."
FEATURE_ENABLED=$(docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 -e "SELECT meta_value FROM wp_sitemeta WHERE meta_key = 'ideai_nested_tree_enabled' LIMIT 1;" --skip-column-names 2>&1 | tail -1 | tr -d ' ')

if [ "$FEATURE_ENABLED" = "1" ]; then
  echo "✅ Nested tree feature is enabled"
else
  echo "⚠️  Nested tree feature not enabled"
  echo "   Enable it at: https://site3.localwp/wp-admin/network/admin.php?page=ideai-status"
fi

echo ""
echo "✅ Ready for testing!"
echo "   Admin: https://site3.localwp/wp-admin/"
echo "   Create Site: https://site3.localwp/wp-admin/network/site-new.php"

