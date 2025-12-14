# Testing Workflow with Cursor Browser MCP

## 🎯 Goal
Rapid iteration on nested site creation with automated testing and browser verification.

## 🛠️ Tools Available

### Automated Scripts
1. **`./scripts/dev/reset-databases.sh`** - Flush all databases cleanly
2. **`./scripts/dev/setup-test-sites.sh`** - Auto-create test sites
3. **`./scripts/dev/create-nested-site.sh`** - Create nested sites via CLI
4. **`./scripts/dev/quick-reset.sh`** - One command: flush → setup → test
5. **`./tests/test-all.sh`** - Run all automated tests
6. **`./tests/test-nested-urls.php`** - PHP test for URL generation

### Browser MCP Tools
- `browser_navigate` - Navigate to URLs
- `browser_snapshot` - Get page accessibility tree
- `browser_click` - Click elements
- `browser_type` - Type into inputs
- `browser_wait_for` - Wait for conditions

## 🚀 Rapid Iteration Workflow

### Step 1: Reset Environment
```bash
./scripts/dev/reset-databases.sh
./scripts/dev/setup-test-sites.sh
```

### Step 2: Make Code Changes
Edit files in `wp-content/mu-plugins/ideai.wp.plugin.platform/`

### Step 3: Test with Browser MCP
```javascript
// Navigate to site creation
browser_navigate("https://site3.localwp/wp-admin/network/site-new.php")

// Get page snapshot
browser_snapshot()

// Interact with nested site fields
browser_click("Parent site dropdown")
browser_type("Child slug input", "test123")
browser_click("Add Site button")

// Verify results
browser_navigate("https://site3.localwp/parent/test123/wp-admin/")
browser_snapshot() // Verify URLs are correct
```

### Step 4: Run Automated Tests
```bash
./tests/test-all.sh
```

### Step 5: Iterate
Repeat steps 2-4 until all tests pass.

## 📋 Test Checklist

### UI Tests (Browser MCP)
- [ ] Nested site fields appear when feature enabled
- [ ] Parent dropdown is at top of form
- [ ] Grayed-out prefix shows correct parent path
- [ ] Child slug input works correctly
- [ ] Full URL preview updates in real-time
- [ ] "Add Site" button creates site correctly

### Functional Tests (Automated)
- [ ] Database paths use `/` (never `--`)
- [ ] Nested tree mappings are created
- [ ] `home_url()`, `admin_url()`, `site_url()` generate correct paths
- [ ] No `--` in any generated URLs
- [ ] HTTP requests return 200/302 (not 404)

### Integration Tests
- [ ] Site accessible at nested path
- [ ] Admin URLs point to correct nested site
- [ ] All links within admin use correct paths
- [ ] Collision prevention works (Pages vs nested sites)

## 🎨 Semantic Naming Convention

- **Level 0**: `/` (Network root)
- **Level 1**: `/parent1/`, `/parent2/` (Top-level sites)
- **Level 2**: `/parent1/child1/`, `/parent1/child2/` (Nested children)
- **Level 3**: `/parent1/child1/grandchild1/` (Deep nesting)

## 🔍 Verification Commands

```bash
# Check database paths
docker-compose -f docker-compose.flexible.yml exec db3 mysql -u root -proot wordpress3 \
  -e "SELECT blog_id, path FROM wp_blogs ORDER BY blog_id;"

# Check nested tree mappings
docker-compose -f docker-compose.flexible.yml exec db3 mysql -u root -proot wordpress3 \
  -e "SELECT blog_id, path FROM wp_ideai_nested_tree_paths ORDER BY blog_id;"

# Test URL accessibility
curl -k -I https://site3.localwp/parent1/child1/wp-admin/

# Run PHP tests
docker-compose -f docker-compose.flexible.yml exec wordpress3 php tests/test-nested-urls.php
```

## 📝 Example Test Session

1. **Reset**: `./scripts/dev/quick-reset.sh`
2. **Navigate**: Browser to `https://site3.localwp/wp-admin/network/site-new.php`
3. **Create**: Use UI to create `/parent1/test123/`
4. **Verify**: Check database, URLs, accessibility
5. **Iterate**: Make code changes, repeat

## ✅ Success Criteria

- All automated tests pass
- Browser UI works correctly
- No `--` in any URLs
- All nested sites accessible
- URLs generate correctly
- Minimal code, maximum reliability


