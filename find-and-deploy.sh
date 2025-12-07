#!/bin/bash
# Find SSH key and deploy

cd /Users/sv/_MYUI/_LocalDev/Ideai-DockerWP-001

echo "Looking for SSH key in ~/Downloads..."
echo ""

# Try common names
KEYS=(
    "$HOME/Downloads/LightsailDefaultKeyPair-wordpress-multisite.pem"
    "$HOME/Downloads/LightsailDefaultKeyPair.pem"
    "$HOME/Downloads/wordpress-multisite.pem"
)

FOUND=""
for key in "${KEYS[@]}"; do
    if [ -f "$key" ]; then
        FOUND="$key"
        echo "✓ Found: $key"
        break
    fi
done

# If not found, list all .pem files
if [ -z "$FOUND" ]; then
    echo "Searching for .pem files..."
    find "$HOME/Downloads" -name "*.pem" -type f 2>/dev/null | while read key; do
        echo "Found: $key"
        FOUND="$key"
        break
    done
fi

if [ -z "$FOUND" ]; then
    echo "❌ No SSH key found in ~/Downloads"
    echo ""
    echo "Please provide the exact filename, or run:"
    echo "  ./scripts/deploy-to-instance.sh wordpress-multisite 18.130.255.19 ubuntu /path/to/key.pem"
    exit 1
fi

echo ""
echo "Using key: $FOUND"
chmod 400 "$FOUND"
echo ""

./scripts/deploy-to-instance.sh wordpress-multisite 18.130.255.19 ubuntu "$FOUND"

