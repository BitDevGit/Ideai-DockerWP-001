# Nested Tree Multisite - Clean Refactor Plan

## Current Problem
- Sites are being created with `--` in paths (e.g., `/sub1--subsub1/`) instead of `/` (e.g., `/sub1/subsub1/`)
- Complex conversion logic that's failing
- URL rewriting trying to fix what should be correct from the start

## WordPress Multisite Basics
- WordPress stores site path in `wp_blogs.path` column
- WordPress uses this path directly for URL generation via `admin_url()`, `home_url()`, etc.
- Standard multisite: `/sub1/` works perfectly - WordPress generates URLs using this path
- **Key insight**: If `wp_blogs.path` is correct, WordPress generates correct URLs automatically

## Solution: Store Nested Paths Directly in Database

### Core Principle
**Store the nested path directly in `wp_blogs.path` - no `--`, no conversion, no rewriting needed.**

### Implementation Plan

#### 1. Site Creation (admin-ui.php)
**Current**: Creates site with `--` path, then tries to convert it
**New**: Create site with nested path directly (if WordPress allows `/` after creation)

**Option A (Preferred)**: 
- Create site with temporary slug (e.g., `temp123`)
- Immediately update `wp_blogs.path` to nested path (e.g., `/sub1/subsub1/`)
- WordPress allows updating path after creation

**Option B (If A doesn't work)**:
- Create site with flat slug (e.g., `sub1-subsub1`)
- Immediately update `wp_blogs.path` to nested path (e.g., `/sub1/subsub1/`)
- WordPress will use the updated path for all URL generation

#### 2. Routing (nested-tree-routing.php)
**Keep**: `pre_get_site_by_path` filter - this is correct and minimal
- WordPress calls this to determine which site handles a request
- We return the correct WP_Site object based on nested path matching
- This is the ONLY routing code needed

#### 3. URL Generation (nested-tree-urls.php)
**Remove**: All URL rewriting logic
**Why**: If `wp_blogs.path` is correct, WordPress generates correct URLs automatically
- `admin_url()` uses `get_site($blog_id)->path` 
- If path is `/sub1/subsub1/`, WordPress generates `/sub1/subsub1/wp-admin/`
- No rewriting needed!

#### 4. Database Storage (nested-tree.php)
**Keep**: Custom table for nested path mapping (for routing lookup)
**Update**: `upsert_blog_path()` should:
- Save to custom table (for routing)
- **ALWAYS** update `wp_blogs.path` to match (WordPress uses this)
- Clear cache

#### 5. Remove All `--` Logic
- Remove `handle_wpmu_new_blog` conversion logic
- Remove any `--` to `/` conversion code
- Sites should NEVER have `--` in their paths

### Files to Modify

1. **admin-ui.php**
   - Remove `handle_wpmu_new_blog` hook (or simplify to just ensure path is correct)
   - Update site creation to set nested path directly in database immediately

2. **nested-tree-urls.php**
   - **DELETE THIS FILE** - URL rewriting not needed if DB paths are correct
   - OR keep minimal version that only handles edge cases

3. **nested-tree.php**
   - Keep: `upsert_blog_path()` - but ensure it ALWAYS updates `wp_blogs.path`
   - Keep: `resolve_blog_for_request_path()` - needed for routing
   - Keep: Custom table - needed for routing lookup

4. **nested-tree-routing.php**
   - Keep as-is - this is correct and minimal

### Testing Checklist
- [ ] Create nested site at `/sub1/subsub1/`
- [ ] Verify `wp_blogs.path` = `/sub1/subsub1/` (not `--`)
- [ ] Verify admin URLs are correct (no rewriting needed)
- [ ] Verify routing works (correct site loads)
- [ ] Verify no `--` appears anywhere

### Code Size Target
- Routing: ~50 lines (already done)
- Database sync: ~20 lines (ensure wp_blogs.path matches)
- Site creation: ~30 lines (set path correctly)
- **Total: ~100 lines** (vs current ~500+ lines)

### Key Insight
**WordPress multisite works perfectly when `wp_blogs.path` is correct. We just need to:**
1. Store nested paths in `wp_blogs.path` (not `--`)
2. Route requests correctly (already done)
3. That's it - WordPress handles the rest!


