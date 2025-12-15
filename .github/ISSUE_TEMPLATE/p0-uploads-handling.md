---
name: 🔴 P0: Critical - Uploads Handling
about: Fix uploads handling for nested multisite
title: '[P0] Uploads Handling - Files not saving to correct directories'
labels: 'bug,uploads,multisite,critical,p0'
assignees: ''
---

## 🔴 Priority: P0 (Critical)

**Status:** 🔴 Not Working  
**Estimated Time:** 4-6 hours  
**Area:** Uploads, Multisite

---

## Problem Statement

Uploads are not correctly handled for nested sites. Files may be saved to wrong directories, URLs may be incorrect, and image processing may fail.

### Current Behavior
- Files may be saved to root uploads directory instead of site-specific directory
- Generated URLs may point to wrong paths
- Thumbnails may not be generated correctly
- Media Library may show incorrect URLs

### Expected Behavior
- Files should save to: `wp-content/uploads/sites/{blog_id}/{year}/{month}/`
- URLs should use: `/wp-content/uploads/sites/{blog_id}/{year}/{month}/`
- All thumbnails should be generated in correct directory
- Media Library should show correct URLs for all sites

---

## Acceptance Criteria

- [ ] Files upload to correct directory: `wp-content/uploads/sites/{blog_id}/{year}/{month}/`
- [ ] Generated URLs use correct path: `/wp-content/uploads/sites/{blog_id}/{year}/{month}/`
- [ ] All thumbnails are generated in correct directory
- [ ] Media Library shows correct URLs for all sites
- [ ] Images display correctly in post content
- [ ] Images display correctly in widgets
- [ ] No files are saved to root uploads directory
- [ ] All uploads work for all nesting levels (root, parent, child, grandchild)

---

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

---

## Related Files

- `wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-uploads.php`
- `wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-urls.php`
- `docs/UPLOADS_ARCHITECTURE.md`
- `docker-compose.flexible.yml` (uploads volume configuration)

---

## Testing Checklist

Before marking as complete, test:

- [ ] Upload image on root site → saves to `sites/1/`
- [ ] Upload image on parent site → saves to `sites/54/`
- [ ] Upload image on child site → saves to `sites/55/`
- [ ] Upload image on grandchild site → saves to `sites/56/`
- [ ] All thumbnails generated in correct directories
- [ ] Media Library shows correct URLs
- [ ] Images display in post content
- [ ] Images display in widgets
- [ ] No files in root `uploads/` directory (except for root site)

---

## Notes

- Current upload directories exist: `/var/www/html/wp-content/uploads/sites/{blog_id}/`
- Permissions should be: `755` (drwxr-xr-x)
- Ownership should be: `www-data:www-data`
- PHP upload limits: `upload_max_filesize=100M`, `post_max_size=100M`

---

## References

- See `docs/GITHUB_TASKS.md` for full task list
- See `docs/PROJECT_STATUS.md` for project status
- See `docs/UPLOADS_ARCHITECTURE.md` for architecture details

