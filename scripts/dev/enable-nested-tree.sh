#!/bin/bash
# Enable nested tree feature programmatically
# Usage: ./scripts/dev/enable-nested-tree.sh

set -e

echo "🔧 Enabling Nested Tree Feature"
echo "==============================="
echo ""

# Check if WordPress is installed
INSTALLED=$(curl -s -k https://site3.localwp/wp-admin/install.php 2>&1 | grep -i "already installed" || echo "")

if [ -z "$INSTALLED" ]; then
  echo "⚠️  WordPress not installed yet"
  echo "   Please install WordPress first: https://site3.localwp/wp-admin/install.php"
  exit 1
fi

echo "✅ WordPress is installed"
echo ""

# Try to enable via database directly
echo "Attempting to enable nested tree feature via database..."

docker-compose -f docker-compose.flexible.yml exec -T db3 mysql -u wordpress -pwordpress wordpress3 <<EOF 2>&1 | tail -5
INSERT INTO wp_sitemeta (site_id, meta_key, meta_value)
VALUES (1, 'ideai_nested_tree_enabled', '1')
ON DUPLICATE KEY UPDATE meta_value = '1';

INSERT INTO wp_sitemeta (site_id, meta_key, meta_value)
VALUES (1, 'ideai_nested_tree_collision_mode', 'strict')
ON DUPLICATE KEY UPDATE meta_value = 'strict';

SELECT meta_key, meta_value FROM wp_sitemeta WHERE meta_key LIKE 'ideai_%';
EOF

if [ $? -eq 0 ]; then
  echo "✅ Nested tree feature enabled via database"
else
  echo "⚠️  Database update failed, enable manually:"
  echo "   https://site3.localwp/wp-admin/network/admin.php?page=ideai-status"
fi

echo ""
echo "✅ Process complete"

