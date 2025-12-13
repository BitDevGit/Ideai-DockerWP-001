#!/bin/bash
# Quick reset: flush DBs, setup test sites, run tests
# Usage: ./scripts/dev/quick-reset.sh

set -e

echo "🔄 Quick Reset: Flush → Setup → Test"
echo "====================================="
echo ""

# Step 1: Reset databases
echo "Step 1: Resetting databases..."
./scripts/dev/reset-databases.sh

# Step 2: Setup test sites
echo ""
echo "Step 2: Setting up test sites..."
./scripts/dev/setup-test-sites.sh

# Step 3: Run tests
echo ""
echo "Step 3: Running tests..."
./tests/test-all.sh

echo ""
echo "✅ Quick reset complete!"
echo ""
echo "📋 Quick Access:"
echo "  Site 1: https://site1.localwp/wp-admin/ (admin/admin)"
echo "  Site 2: https://site2.localwp/wp-admin/ (admin/admin)"
echo "  Site 3: https://site3.localwp/wp-admin/ (admin/admin)"
echo ""
echo "🧪 To create a nested site:"
echo "  ./scripts/dev/create-nested-site.sh /parent1/ child1 \"Child Site 1\""

