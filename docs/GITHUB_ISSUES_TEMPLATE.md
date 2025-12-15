# GitHub Issues Template

This document provides templates for creating GitHub issues from the task list.

---

## Issue Template: P0 - Uploads Handling

```markdown
# 🔴 P0: Critical - Uploads Handling

**Priority:** P0 (Critical)  
**Labels:** `bug`, `uploads`, `multisite`, `critical`, `p0`  
**Estimated Time:** 4-6 hours  
**Status:** 🔴 Not Working

## Problem Statement
Uploads are not correctly handled for nested sites. Files may be saved to wrong directories, URLs may be incorrect, and image processing may fail.

## Acceptance Criteria
- [ ] Files upload to correct directory: `wp-content/uploads/sites/{blog_id}/{year}/{month}/`
- [ ] Generated URLs use correct path: `/wp-content/uploads/sites/{blog_id}/{year}/{month}/`
- [ ] All thumbnails are generated in correct directory
- [ ] Media Library shows correct URLs for all sites
- [ ] Images display correctly in post content
- [ ] Images display correctly in widgets
- [ ] No files are saved to root uploads directory
- [ ] All uploads work for all nesting levels

## Subtasks

### 1. Investigate Current Upload Behavior
- [ ] Test file upload on root site (blog_id 1)
- [ ] Test file upload on parent site (e.g., blog_id 54)
- [ ] Test file upload on child site (e.g., blog_id 55)
- [ ] Test file upload on grandchild site (e.g., blog_id 56)
- [ ] Document where files are actually being saved
- [ ] Document what URLs are being generated
- [ ] Check WordPress error logs for upload errors
- [ ] Check PHP error logs for permission errors

### 2. Fix Upload Directory Detection
- [ ] Verify `upload_dir` filter is running at correct priority (9999)
- [ ] Add debug logging to `fix_upload_directory()` function
- [ ] Ensure `get_current_blog_id()` returns correct blog_id during upload
- [ ] Test that `WP_CONTENT_DIR` is correct in Docker environment
- [ ] Verify `content_url()` generates correct base URL
- [ ] Test with different file types (images, PDFs, etc.)

### 3. Fix File Upload Process
- [ ] Verify `wp_handle_upload_prefilter` hook is working
- [ ] Verify `wp_handle_upload` hook is working
- [ ] Ensure upload directory exists before file move
- [ ] Ensure correct permissions (755) and ownership (www-data)
- [ ] Test that files are moved to correct directory
- [ ] Verify file is accessible at generated URL

### 4. Fix Image Processing
- [ ] Verify `wp_image_editors` filter is working
- [ ] Verify `wp_image_editor_args` filter is working
- [ ] Ensure thumbnails are generated in correct directory
- [ ] Test image resizing/cropping
- [ ] Verify all thumbnail sizes are created correctly
- [ ] Test with different image formats (JPEG, PNG, GIF, WebP)

### 5. Fix URL Generation
- [ ] Verify `wp_get_attachment_url` filter is working
- [ ] Verify `attachment_link` filter is working
- [ ] Verify `wp_get_attachment_image_src` filter is working
- [ ] Test URLs in Media Library AJAX responses
- [ ] Test URLs in post content
- [ ] Test URLs in widgets
- [ ] Verify URLs work for all nested levels

### 6. Fix Metadata Storage
- [ ] Verify `wp_get_attachment_metadata` filter is working
- [ ] Ensure metadata stores correct file paths
- [ ] Verify thumbnail metadata is correct
- [ ] Test metadata retrieval for existing uploads

### 7. Fix Content URL Replacement
- [ ] Verify `the_content` filter is working
- [ ] Verify `widget_text` filter is working
- [ ] Test regex pattern for URL replacement
- [ ] Test with different URL formats (absolute, relative)
- [ ] Test with different quote styles (single, double)

### 8. Comprehensive Testing
- [ ] Test upload on all 4 nesting levels (root, parent, child, grandchild)
- [ ] Test upload from admin area
- [ ] Test upload from frontend (if applicable)
- [ ] Test bulk upload
- [ ] Test drag-and-drop upload
- [ ] Test upload via REST API
- [ ] Test upload via WP-CLI
- [ ] Verify all uploaded files are accessible
- [ ] Verify all thumbnails are accessible
- [ ] Test with large files (>10MB)
- [ ] Test with many files (100+)

### 9. Documentation & Cleanup
- [ ] Document upload directory structure
- [ ] Document all filters and hooks used
- [ ] Add inline code comments
- [ ] Update `docs/UPLOADS_ARCHITECTURE.md`
- [ ] Create troubleshooting guide for upload issues

## Related Files
- `wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-uploads.php`
- `wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-urls.php`
- `docs/UPLOADS_ARCHITECTURE.md`

## References
- See `docs/GITHUB_TASKS.md` for full task list
- See `docs/PROJECT_STATUS.md` for project status
```

---

## Issue Template: P1 - Admin URL Generation

```markdown
# 🟠 P1: High Priority - Admin URL Generation

**Priority:** P1 (High)  
**Labels:** `bug`, `admin`, `urls`, `high-priority`, `p1`  
**Estimated Time:** 2-3 hours  
**Status:** 🟡 Partially Working

## Problem Statement
Admin URLs for nested sites sometimes point to wrong sites or use incorrect paths.

## Acceptance Criteria
- [ ] All admin URLs use correct nested paths
- [ ] Admin URLs work for all nesting levels
- [ ] No admin URLs point to wrong sites
- [ ] Query strings and fragments are preserved

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

## Related Files
- `wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-routing.php`
- `wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-urls.php`

## References
- See `docs/GITHUB_TASKS.md` for full task list
```

---

## Quick Issue Creation Commands

### Create P0 Issue (Uploads)
```bash
gh issue create \
  --title "🔴 P0: Critical - Uploads Handling" \
  --body-file docs/GITHUB_ISSUES_TEMPLATE.md \
  --label "bug,uploads,multisite,critical,p0" \
  --assignee @me
```

### Create P1 Issue (Admin URLs)
```bash
gh issue create \
  --title "🟠 P1: High Priority - Admin URL Generation" \
  --body-file docs/GITHUB_ISSUES_TEMPLATE.md \
  --label "bug,admin,urls,high-priority,p1" \
  --assignee @me
```

---

## Label Setup

Run these commands to create all labels:

```bash
# Priority Labels
gh label create "p0" --description "Critical priority" --color "d73a4a"
gh label create "p1" --description "High priority" --color "e99695"
gh label create "p2" --description "Medium priority" --color "fbca04"
gh label create "p3" --description "Medium priority" --color "fbca04"
gh label create "p4" --description "Low priority" --color "0e8a16"
gh label create "p5" --description "Low priority" --color "0e8a16"
gh label create "p6" --description "Low priority" --color "0e8a16"
gh label create "p7" --description "Low priority" --color "0e8a16"

# Type Labels
gh label create "bug" --description "Bug fix" --color "d73a4a"
gh label create "enhancement" --description "New feature" --color "a2eeef"
gh label create "documentation" --description "Documentation" --color "0075ca"
gh label create "testing" --description "Testing" --color "c5def5"
gh label create "performance" --description "Performance" --color "bfe5bf"
gh label create "security" --description "Security" --color "b60205"

# Area Labels
gh label create "uploads" --description "Upload handling" --color "1d76db"
gh label create "admin" --description "Admin area" --color "1d76db"
gh label create "urls" --description "URL generation" --color "1d76db"
gh label create "multisite" --description "Multisite functionality" --color "1d76db"
gh label create "routing" --description "Site routing" --color "1d76db"
gh label create "ui" --description "User interface" --color "1d76db"
gh label create "api" --description "API endpoints" --color "1d76db"
gh label create "cli" --description "WP-CLI commands" --color "1d76db"

# Status Labels
gh label create "critical" --description "Critical issue" --color "b60205"
gh label create "high-priority" --description "High priority" --color "e99695"
gh label create "blocked" --description "Blocked" --color "d93f0b"
gh label create "in-progress" --description "In progress" --color "0e8a16"
gh label create "ready-for-review" --description "Ready for review" --color "fbca04"
gh label create "done" --description "Completed" --color "0e8a16"
```

---

**Note:** Replace `@me` with actual GitHub usernames when assigning issues.

