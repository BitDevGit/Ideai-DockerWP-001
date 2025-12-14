# Diagnostic Plan: Nested Site Routing Issue

## Problem
`https://site3.localwp/parent1/child2/grandchild2/` shows "Parent 1 (Level 1)" instead of the correct grandchild site.

## Root Cause Hypothesis
1. Routing filter not intercepting correctly
2. Database mappings incorrect
3. WordPress core resolving before our filter
4. Cache issues

## Diagnostic Steps

### Step 1: Check Database State
- Verify `wp_blogs.path` for all sites
- Verify `ideai_nested_sites` mappings
- Check if grandchild2 site exists and has correct path

### Step 2: Test Routing Resolution
- Test `resolve_blog_for_request_path()` with `/parent1/child2/grandchild2/`
- Check what blog_id it returns
- Verify it's not returning parent site

### Step 3: Test WordPress Core Resolution
- Test what `get_site_by_path()` returns without our filter
- See if core is resolving to parent before our filter runs

### Step 4: Test Filter Execution
- Add debug logging to see if filter is called
- Check filter priority and execution order
- Verify filter is overriding core resolution

### Step 5: Check Site Data
- Verify site name (blogname) for each site
- Check if sites have correct paths in database
- Ensure each site is sovereign

## Fix Strategy
Based on diagnostic results, either:
1. Fix routing filter to run earlier/override correctly
2. Fix database mappings
3. Use Nginx rewrites as fallback
4. Combination approach


