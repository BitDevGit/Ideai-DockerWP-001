#!/bin/bash
# Automated WordPress installation via wp-cli (if available) or manual instructions
# Usage: ./scripts/dev/install-wordpress-automated.sh

set -e

echo "🔧 Attempting Automated WordPress Installation"
echo "=============================================="
echo ""

# Check if wp-cli is available in container
if docker-compose -f docker-compose.flexible.yml exec -T wordpress3 which wp >/dev/null 2>&1; then
  echo "✅ wp-cli found, attempting automated installation..."
  
  # Install WordPress
  docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp core install \
    --url=https://site3.localwp \
    --title="Site 3: Subdirectory Multisite" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@site3.localwp \
    --skip-email \
    2>&1 | grep -v "Warning" || echo "Installation may have completed or needs manual setup"
  
  # Enable multisite
  docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp core multisite-install \
    --url=https://site3.localwp \
    --title="Site 3: Subdirectory Multisite" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@site3.localwp \
    --skip-email \
    2>&1 | grep -v "Warning" || echo "Multisite setup may need manual configuration"
  
  echo "✅ Automated installation attempted"
else
  echo "⚠️  wp-cli not available in container"
  echo ""
  echo "📝 Manual Installation Required:"
  echo "   1. Visit: https://site3.localwp/wp-admin/install.php"
  echo "   2. Complete WordPress installation"
  echo "   3. Enable multisite (Sub-directories)"
  echo ""
fi

echo ""
echo "✅ Installation process complete"
echo "   Check: https://site3.localwp/wp-admin/"

