# Test Suite

Automated testing for nested tree multisite functionality.

## Quick Start

```bash
# Full reset: flush DBs → setup sites → run tests
./scripts/dev/quick-reset.sh

# Or step by step:
./scripts/dev/reset-databases.sh      # Flush all databases
./scripts/dev/setup-test-sites.sh     # Create test sites
./tests/test-all.sh                    # Run all tests
```

## Test Scripts

### `test-all.sh`
Runs all tests:
- Database path checks
- Nested tree mapping verification
- `--` detection (should find none)
- URL generation tests
- HTTP accessibility

### `test-nested-urls.php`
PHP test that verifies:
- `home_url()`, `admin_url()`, `site_url()` generate correct nested paths
- No `--` in generated URLs
- Paths match expected nested structure

## Creating Test Sites

### Standard Sites
```bash
# Create a nested site
./scripts/dev/create-nested-site.sh /parent1/ child1 "Child Site 1"

# Creates: https://site3.localwp/parent1/child1/
```

### Semantic Naming Convention
- **Level 0**: Root (`/`)
- **Level 1**: Parents (`/parent1/`, `/parent2/`)
- **Level 2**: Children (`/parent1/child1/`, `/parent1/child2/`)
- **Level 3**: Grandchildren (`/parent1/child1/grandchild1/`)

## Rapid Iteration Workflow

1. **Make code changes**
2. **Reset and test:**
   ```bash
   ./scripts/dev/quick-reset.sh
   ```
3. **Create specific test case:**
   ```bash
   ./scripts/dev/create-nested-site.sh /parent1/ test123 "Test Site"
   ```
4. **Verify URLs:**
   ```bash
   curl -k https://site3.localwp/parent1/test123/wp-admin/
   ```
5. **Run tests:**
   ```bash
   ./tests/test-all.sh
   ```

## Expected Results

✅ All database paths use `/` (never `--`)  
✅ All URLs contain correct nested paths  
✅ No `--` in any generated URLs  
✅ HTTP requests return 200/302 (not 404)  
✅ Admin URLs point to correct nested site  


