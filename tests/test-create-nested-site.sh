#!/bin/bash
# Test script to verify nested site creation works correctly
# Usage: ./tests/test-create-nested-site.sh

set -e

echo "Testing nested site creation..."
echo ""

# Check if site3 is running
if ! curl -s -k https://site3.localwp/ > /dev/null; then
    echo "❌ site3.localwp is not accessible"
    exit 1
fi

echo "✅ site3.localwp is accessible"
echo ""

# Test: Create a nested site and verify path
echo "To test manually:"
echo "1. Go to https://site3.localwp/wp-admin/network/site-new.php"
echo "2. Select a parent site"
echo "3. Enter a child slug (e.g., 'test123')"
echo "4. Click 'Add Site'"
echo "5. Verify the site URL is correct (e.g., /sub1/test123/ not /sub1--test123/)"
echo ""
echo "Or check database directly:"
echo "docker-compose -f docker-compose.flexible.yml exec db3 mysql -u wordpress -pwordpress wordpress3 -e \"SELECT blog_id, domain, path FROM wp_blogs WHERE path LIKE '%/%' ORDER BY blog_id;\""

