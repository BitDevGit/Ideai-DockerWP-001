#!/bin/bash
# Test vanilla WordPress wp-admin access
# Usage: ./scripts/test/vanilla-wp-test.sh

set -e

echo "🧪 Testing Vanilla WordPress wp-admin"
echo "====================================="
echo ""

# Test wp-admin redirect
echo "Test 1: Checking wp-admin redirect..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -L http://vanilla.localwp:8080/wp-admin/ 2>&1 || echo "000")
echo "  HTTP Code: $HTTP_CODE"

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
  echo "  ✅ wp-admin accessible (code: $HTTP_CODE)"
else
  echo "  ❌ wp-admin not accessible (code: $HTTP_CODE)"
fi

echo ""
echo "Test 2: Checking redirect chain..."
REDIRECTS=$(curl -s -o /dev/null -w "%{redirect_url}" -L http://vanilla.localwp:8080/wp-admin/ 2>&1 | head -5)
echo "  Redirects: $REDIRECTS"

echo ""
echo "Test 3: Checking WordPress configuration..."
echo "  Site URL:"
docker-compose -f docker-compose.vanilla-test.yml exec -T wordpress-vanilla wp --allow-root option get siteurl 2>&1 | grep -v "Warning" | tail -1 || true
echo "  Home URL:"
docker-compose -f docker-compose.vanilla-test.yml exec -T wordpress-vanilla wp --allow-root option get home 2>&1 | grep -v "Warning" | tail -1 || true

echo ""
echo "Test 4: Checking if multisite is enabled..."
if docker-compose -f docker-compose.vanilla-test.yml exec -T wordpress-vanilla wp --allow-root core is-installed --network 2>/dev/null; then
  echo "  ✅ Multisite is enabled"
else
  echo "  ⚠️  Multisite is NOT enabled"
fi

echo ""
echo "✅ Test complete!"

