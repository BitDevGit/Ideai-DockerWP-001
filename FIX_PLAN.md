# Fix Plan: Restore wp-admin and Prevent Root Site Interference

## Problem
- wp-admin has redirect loops
- Nested tree code is running on root site (blog_id 1, path '/')
- Root site should work exactly like vanilla WordPress multisite

## Root Cause
Nested tree logic is being applied to the root site when it should ONLY run for nested child sites (blog_id > 1, path !== '/').

## Solution: Skip Root Site in All Nested Tree Code

### Rule: Only apply nested tree logic when:
1. `blog_id > 1` (not root site)
2. `nested_path !== '/'` (not root path)
3. `nested_path` exists and is not empty

### Files to Fix

#### 1. `nested-tree-routing.php`
- `fix_admin_url()`: Skip if `nested_path === '/'` or `blog_id === 1`
- `fix_site_url()`: Skip if `nested_path === '/'` or `blog_id === 1`
- `fix_home_url()`: Skip if `nested_path === '/'` or `blog_id === 1`
- `force_correct_blog()`: Already skips admin paths - good
- `prevent_admin_redirect_loops()`: Remove - this is causing issues
- `disable_canonical_redirect_for_admin()`: Remove - this is causing issues

#### 2. `nested-tree-canonical.php`
- `filter_redirect_canonical()`: Skip if `blog_id === 1` or `mapped === '/'`
- `disable_canonical_for_admin()`: Remove - redundant

#### 3. `nested-tree-urls.php`
- `filter_admin_url()`: Skip if `nested_path === '/'` or `blog_id === 1`
- `maybe_rewrite_for_blog()`: Skip if `mapped === '/'` or `blog_id === 1`

## Implementation Steps

1. **Remove problematic redirect prevention code** (causing loops)
2. **Add root site checks** to all URL filters
3. **Test with root site** - should work like vanilla WP
4. **Test with nested sites** - should still work

## Testing
- Root site wp-admin: Should work normally
- Root site frontend: Should work normally  
- Nested site wp-admin: Should work with nested paths
- Nested site frontend: Should work with nested paths

## Progress

### ✅ Completed
1. Added root site checks (`blog_id === 1`) to:
   - `fix_admin_url()` in nested-tree-routing.php
   - `fix_site_url()` in nested-tree-routing.php  
   - `fix_home_url()` in nested-tree-routing.php
   - `filter_redirect_canonical()` in nested-tree-canonical.php
   - `maybe_rewrite_for_blog()` in nested-tree-urls.php
   - `filter_admin_url()` in nested-tree-urls.php

2. Removed problematic redirect prevention code:
   - Removed `prevent_admin_redirect_loops()` 
   - Removed `disable_canonical_redirect_for_admin()`
   - Removed `disable_canonical_for_admin()` filter

3. Added `disable_canonical_for_root_admin()` to completely disable redirect_canonical for root site admin

### ❌ Still Broken
- wp-admin redirect loop persists
- Redirect appears to be coming from WordPress core, not our filters
- May need to investigate:
  - WordPress core admin redirect logic
  - Nginx configuration
  - Site URL/Home URL mismatch
  - Other plugins/themes

## Next Steps
1. Test with vanilla WordPress to confirm it's not a core issue
2. Check if redirect happens before our filters run
3. Investigate WordPress core admin redirect mechanisms
4. Consider temporarily disabling nested tree entirely for root site to isolate issue

