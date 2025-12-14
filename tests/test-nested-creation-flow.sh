#!/bin/bash
# Complete test flow for nested site creation
# This simulates the browser testing workflow

set -e

echo "🧪 Nested Site Creation Test Flow"
echo "=================================="
echo ""

# Step 1: Verify environment
echo "Step 1: Verifying environment..."
if ! curl -s -k -o /dev/null -w "%{http_code}" https://site3.localwp/ | grep -q "200\|302\|301"; then
  echo "❌ site3.localwp is not accessible"
  echo "   Please ensure containers are running: docker-compose -f docker-compose.flexible.yml up -d"
  exit 1
fi
echo "✅ site3.localwp is accessible"
echo ""

# Step 2: Check if WordPress is installed
echo "Step 2: Checking WordPress installation..."
INSTALL_PAGE=$(curl -s -k https://site3.localwp/wp-admin/install.php 2>&1 | grep -i "already installed\|multisite" || echo "")
if [ -n "$INSTALL_PAGE" ]; then
  echo "✅ WordPress appears to be installed"
else
  echo "⚠️  WordPress may need setup"
  echo "   Visit: https://site3.localwp/wp-admin/install.php"
fi
echo ""

# Step 3: Check nested tree feature
echo "Step 3: Checking nested tree feature..."
# Try to check via database or API
echo "   Feature should be enabled at: https://site3.localwp/wp-admin/network/admin.php?page=ideai-status"
echo ""

# Step 4: Test site creation page
echo "Step 4: Testing site creation page..."
SITE_NEW=$(curl -s -k -L https://site3.localwp/wp-admin/network/site-new.php 2>&1 | grep -i "add site\|nested\|ideai" || echo "")
if [ -n "$SITE_NEW" ]; then
  echo "✅ Site creation page is accessible"
  echo "   Found nested/ideai references: $(echo "$SITE_NEW" | wc -l | tr -d ' ') lines"
else
  echo "⚠️  Site creation page may need authentication"
  echo "   Visit: https://site3.localwp/wp-admin/network/site-new.php"
fi
echo ""

# Step 5: Document manual testing steps
echo "Step 5: Manual Browser Testing Steps"
echo "--------------------------------------"
echo ""
echo "1. Navigate to: https://site3.localwp/wp-admin/network/site-new.php"
echo "2. Log in if prompted (admin/admin if using test setup)"
echo "3. Look for 'IdeAI: Nested Site Options' section"
echo "4. Verify 'Parent site' dropdown is visible"
echo "5. Select a parent (e.g., 'Network root (/)')"
echo "6. Verify nested fields appear:"
echo "   - Grayed-out prefix showing parent path"
echo "   - Child slug input field"
echo "   - Full URL preview"
echo "7. Enter child slug: 'browsertest123'"
echo "8. Verify full URL preview shows: https://site3.localwp/browsertest123/"
echo "9. Click 'Add Site' button"
echo "10. Verify site is created"
echo "11. Navigate to: https://site3.localwp/browsertest123/wp-admin/"
echo "12. Verify all URLs use correct path (no --)"
echo ""

# Step 6: Verification commands
echo "Step 6: Verification Commands"
echo "------------------------------"
echo ""
echo "# Check if site was created"
echo "curl -k -I https://site3.localwp/browsertest123/wp-admin/"
echo ""
echo "# Check database path (if you have DB access)"
echo "# Should show: /browsertest123/ (not /browsertest123--something/)"
echo ""
echo "# Test URL generation"
echo "curl -k https://site3.localwp/browsertest123/ | grep -i 'wp-admin\|site-url'"
echo ""

echo "✅ Test flow documented!"
echo ""
echo "📝 Next Steps:"
echo "   1. Use browser MCP to navigate and test UI"
echo "   2. Create a nested site via UI"
echo "   3. Verify URLs are correct"
echo "   4. Run automated tests: ./tests/test-all.sh"
echo ""


