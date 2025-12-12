#!/bin/bash

# Setup script for flexible multi-site WordPress development
# Adds optional hosts entries for site1.local, site2.local, site3.local
# Note: `.localhost` domains (e.g. site1.localhost, sub1.site2.localhost) resolve automatically without /etc/hosts edits.

set -e

echo "🚀 Flexible Multi-Site WordPress Setup"
echo ""

if [[ "$OSTYPE" != "darwin"* ]]; then
    echo "❌ This script is for macOS. Add hosts entries manually."
    exit 1
fi

HOSTS_FILE="/etc/hosts"
ENTRIES=(
    "127.0.0.1  site1.local"
    "127.0.0.1  site2.local"
    "127.0.0.1  site3.local"
)

echo "📝 Adding hosts entries..."

for entry in "${ENTRIES[@]}"; do
    domain=$(echo "$entry" | awk '{print $2}')
    if grep -q "$domain" "$HOSTS_FILE" 2>/dev/null; then
        echo "   ⚠️  $domain already exists"
    else
        echo "$entry" | sudo tee -a "$HOSTS_FILE" > /dev/null
        echo "   ✅ Added $domain"
    fi
done

echo ""
echo "✅ Setup complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Start: docker-compose -f docker-compose.flexible.yml up -d"
echo "   2. Access (recommended, no hosts needed):"
echo "      - http://site1.localhost"
echo "      - http://site2.localhost"
echo "      - http://site3.localhost"
echo "   3. Access (optional, requires hosts):"
echo "      - http://site1.local"
echo "      - http://site2.local"
echo "      - http://site3.local"
echo "   3. Configure WordPress (normal or multisite)"
echo ""


