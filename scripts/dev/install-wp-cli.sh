#!/bin/bash
# Install wp-cli in WordPress container
# Usage: ./scripts/dev/install-wp-cli.sh

set -e

echo "📦 Installing wp-cli in WordPress container..."
echo "=============================================="
echo ""

# Download and install wp-cli
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 bash -c "
cd /tmp && \
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
chmod +x wp-cli.phar && \
mv wp-cli.phar /usr/local/bin/wp && \
wp --info
" 2>&1 | tail -10

echo ""
echo "✅ wp-cli installation attempted"
echo "   Test: docker-compose -f docker-compose.flexible.yml exec wordpress3 wp --info"


