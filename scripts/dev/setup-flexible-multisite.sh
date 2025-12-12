#!/bin/bash

# Setup script for flexible multi-site WordPress development
# Adds hosts entries for site1.local, site2.local and wildcard subdomains

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
    "127.0.0.1  *.site1.local"
    "127.0.0.1  *.site2.local"
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
echo "   2. Access: http://site1.local and http://site2.local"
echo "   3. Configure WordPress (normal or multisite)"
echo ""


