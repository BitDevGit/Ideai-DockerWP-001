#!/bin/bash
# Run all tests
# Usage: ./tests/test-all.sh

set -e

echo "🧪 Running All Tests"
echo "===================="
echo ""

# Test 1: Check database paths
echo "Test 1: Checking database paths..."
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp db query \
  "SELECT blog_id, domain, path FROM wp_blogs WHERE site_id = 1 ORDER BY blog_id" \
  2>&1 | grep -v "Warning" || true
echo ""

# Test 2: Check nested tree mappings
echo "Test 2: Checking nested tree mappings..."
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp db query \
  "SELECT blog_id, path, network_id FROM wp_ideai_nested_tree_paths ORDER BY blog_id" \
  2>&1 | grep -v "Warning" || true
echo ""

# Test 3: Check for -- in paths (should be none)
echo "Test 3: Checking for -- in paths (should find none)..."
DASH_COUNT=$(docker-compose -f docker-compose.flexible.yml exec -T wordpress3 wp db query \
  "SELECT COUNT(*) FROM wp_blogs WHERE path LIKE '%--%'" \
  --skip-column-names 2>&1 | tail -1 | tr -d ' ')

if [ "$DASH_COUNT" = "0" ]; then
  echo "✅ No -- found in paths"
else
  echo "❌ Found {$DASH_COUNT} paths with --"
fi
echo ""

# Test 4: PHP URL generation test
echo "Test 4: Testing URL generation..."
docker-compose -f docker-compose.flexible.yml exec -T wordpress3 php tests/test-nested-urls.php 2>&1 || true
echo ""

# Test 5: HTTP accessibility test
echo "Test 5: Testing HTTP accessibility..."
echo "  Testing site3.localwp..."
if curl -s -k -o /dev/null -w "%{http_code}" https://site3.localwp/ | grep -q "200\|302"; then
  echo "  ✅ site3.localwp accessible"
else
  echo "  ❌ site3.localwp not accessible"
fi

echo ""
echo "✅ All tests complete!"


