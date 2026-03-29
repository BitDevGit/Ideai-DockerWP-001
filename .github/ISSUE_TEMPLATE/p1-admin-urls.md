---
name: 🟠 P1: High Priority - Admin URL Generation
about: Fix admin URL generation for nested multisite
title: '[P1] Admin URLs - Incorrect paths for nested sites'
labels: 'bug,admin,urls,high-priority,p1'
assignees: ''
---

## 🟠 Priority: P1 (High)

**Status:** 🟡 Partially Working  
**Estimated Time:** 2-3 hours  
**Area:** Admin, URLs

---

## Problem Statement

Admin URLs for nested sites sometimes point to wrong sites or use incorrect paths.

### Current Behavior
- Admin URLs may point to parent sites instead of current site
- Admin URLs may use root paths instead of nested paths
- Query strings and fragments may be lost

### Expected Behavior
- Admin URLs should use correct nested paths: `/parent/child/wp-admin/`
- Admin URLs should work for all nesting levels
- Query strings and fragments should be preserved

---

## Acceptance Criteria

- [ ] All admin URLs use correct nested paths
- [ ] Admin URLs work for all nesting levels
- [ ] No admin URLs point to wrong sites
- [ ] Query strings and fragments are preserved

---

## Subtasks

### 1. Investigate Admin URL Issues
- [ ] Test admin URLs for all nesting levels
- [ ] Document which URLs are incorrect
- [ ] Check `fix_admin_url` filter execution
- [ ] Verify blog context is correct during URL generation

### 2. Fix Admin URL Filter
- [ ] Ensure `fix_admin_url` runs at correct priority (1)
- [ ] Add blog context validation
- [ ] Fix scheme conversion (admin → http/https)
- [ ] Preserve query strings and fragments
- [ ] Add debug logging

### 3. Test All Admin URLs
- [ ] Test `/wp-admin/` for all sites
- [ ] Test `/wp-admin/edit.php` for all sites
- [ ] Test `/wp-admin/post.php?post=X&action=edit` for all sites
- [ ] Test `/wp-admin/upload.php` for all sites
- [ ] Test `/wp-admin/options-general.php` for all sites
- [ ] Verify redirects work correctly

### 4. Edge Cases
- [ ] Test with root site (blog_id 1)
- [ ] Test with deeply nested sites (4+ levels)
- [ ] Test with special characters in paths
- [ ] Test with query parameters
- [ ] Test with fragments

---

## Related Files

- `wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-routing.php`
- `wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-urls.php`

---

## Testing Checklist

Before marking as complete, test:

- [ ] Root site: `https://site3.localwp/wp-admin/` → works
- [ ] Parent site: `https://site3.localwp/parent1/wp-admin/` → works
- [ ] Child site: `https://site3.localwp/parent1/child1/wp-admin/` → works
- [ ] Grandchild: `https://site3.localwp/parent1/child1/grandchild1/wp-admin/` → works
- [ ] Edit post on child site → opens post on same child site (not parent)
- [ ] Upload page on child site → shows child site's uploads (not parent's)

---

## References

- See `docs/GITHUB_TASKS.md` for full task list
- See `docs/PROJECT_STATUS.md` for project status


