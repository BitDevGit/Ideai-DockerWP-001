# IdeAI Docker WordPress - Task List

**Repository:** Ideai-DockerWP-001  
**Status:** Active Development  
**Last Updated:** 2025-12-14

> **📝 Note:** Use GitHub issue templates in `.github/ISSUE_TEMPLATE/` to create issues from these tasks. See `docs/GITHUB_WORKFLOW.md` for workflow guide.

---

## 🎯 Priority Tasks

### 🔴 P0: Uploads Management System Architecture

**Status:** 🟡 Planning  
**Tags:** `enhancement` `uploads` `multisite` `architecture` `p0`  
**Assignee:** TBD  
**Estimated Time:** 20-30 hours

#### Problem Statement
Design and implement a flexible, performant uploads management system that works for single sites, multisites, and nested sites, with support for local development and AWS production, including CI/CD integration.

#### Key Requirements
- **Universal support:** Single sites, subdomain multisite, subdirectory multisite (all nested or not)
- Independent uploads repositories (separate from code)
- Nested site content hierarchy (children as subfolders of parent) - works for both multisite types
- Easy folder-to-site marriage (connect uploads to sites)
- CI/CD support (sync during deployments)
- Local & AWS parity (same config, same commands)

#### Subtasks
See `.github/ISSUE_TEMPLATE/p0-uploads-management-system.md` for complete task breakdown.

#### Acceptance Criteria
- [ ] Configuration file is easy to understand
- [ ] Works for single sites, multisites, and nested sites
- [ ] Uploads are independent from code
- [ ] Nested sites use hierarchical structure
- [ ] S3 sync works in both directions
- [ ] CI/CD integration works
- [ ] Same config works locally and on AWS

---

### 🔴 P0: Critical - Uploads Handling (Current Issues)

**Status:** 🔴 Not Working  
**Tags:** `bug` `uploads` `multisite` `critical` `p0`  
**Assignee:** TBD  
**Estimated Time:** 4-6 hours

#### Problem Statement
Uploads are not correctly handled for nested sites. Files may be saved to wrong directories, URLs may be incorrect, and image processing may fail.

#### Subtasks

1. **Investigate Current Upload Behavior**
   - [ ] Test file upload on root site (blog_id 1)
   - [ ] Test file upload on parent site (e.g., blog_id 54)
   - [ ] Test file upload on child site (e.g., blog_id 55)
   - [ ] Test file upload on grandchild site (e.g., blog_id 56)
   - [ ] Document where files are actually being saved
   - [ ] Document what URLs are being generated
   - [ ] Check WordPress error logs for upload errors
   - [ ] Check PHP error logs for permission errors

2. **Fix Upload Directory Detection**
   - [ ] Verify `upload_dir` filter is running at correct priority (9999)
   - [ ] Add debug logging to `fix_upload_directory()` function
   - [ ] Ensure `get_current_blog_id()` returns correct blog_id during upload
   - [ ] Test that `WP_CONTENT_DIR` is correct in Docker environment
   - [ ] Verify `content_url()` generates correct base URL
   - [ ] Test with different file types (images, PDFs, etc.)

3. **Fix File Upload Process**
   - [ ] Verify `wp_handle_upload_prefilter` hook is working
   - [ ] Verify `wp_handle_upload` hook is working
   - [ ] Ensure upload directory exists before file move
   - [ ] Ensure correct permissions (755) and ownership (www-data)
   - [ ] Test that files are moved to correct directory
   - [ ] Verify file is accessible at generated URL

4. **Fix Image Processing**
   - [ ] Verify `wp_image_editors` filter is working
   - [ ] Verify `wp_image_editor_args` filter is working
   - [ ] Ensure thumbnails are generated in correct directory
   - [ ] Test image resizing/cropping
   - [ ] Verify all thumbnail sizes are created correctly
   - [ ] Test with different image formats (JPEG, PNG, GIF, WebP)

5. **Fix URL Generation**
   - [ ] Verify `wp_get_attachment_url` filter is working
   - [ ] Verify `attachment_link` filter is working
   - [ ] Verify `wp_get_attachment_image_src` filter is working
   - [ ] Test URLs in Media Library AJAX responses
   - [ ] Test URLs in post content
   - [ ] Test URLs in widgets
   - [ ] Verify URLs work for all nested levels

6. **Fix Metadata Storage**
   - [ ] Verify `wp_get_attachment_metadata` filter is working
   - [ ] Ensure metadata stores correct file paths
   - [ ] Verify thumbnail metadata is correct
   - [ ] Test metadata retrieval for existing uploads

7. **Fix Content URL Replacement**
   - [ ] Verify `the_content` filter is working
   - [ ] Verify `widget_text` filter is working
   - [ ] Test regex pattern for URL replacement
   - [ ] Test with different URL formats (absolute, relative)
   - [ ] Test with different quote styles (single, double)

8. **Comprehensive Testing**
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

9. **Documentation & Cleanup**
   - [ ] Document upload directory structure
   - [ ] Document all filters and hooks used
   - [ ] Add inline code comments
   - [ ] Update `docs/UPLOADS_ARCHITECTURE.md`
   - [ ] Create troubleshooting guide for upload issues

#### Acceptance Criteria
- [ ] Files upload to correct directory: `wp-content/uploads/sites/{blog_id}/{year}/{month}/`
- [ ] Generated URLs use correct path: `/wp-content/uploads/sites/{blog_id}/{year}/{month}/`
- [ ] All thumbnails are generated in correct directory
- [ ] Media Library shows correct URLs for all sites
- [ ] Images display correctly in post content
- [ ] Images display correctly in widgets
- [ ] No files are saved to root uploads directory
- [ ] All uploads work for all nesting levels

---

### 🟠 P1: High Priority - Admin URL Generation

**Status:** 🟡 Partially Working  
**Tags:** `bug` `admin` `urls` `high-priority` `p1`  
**Assignee:** TBD  
**Estimated Time:** 2-3 hours

#### Problem Statement
Admin URLs for nested sites sometimes point to wrong sites or use incorrect paths.

#### Subtasks

1. **Investigate Admin URL Issues**
   - [ ] Test admin URLs for all nesting levels
   - [ ] Document which URLs are incorrect
   - [ ] Check `fix_admin_url` filter execution
   - [ ] Verify blog context is correct during URL generation

2. **Fix Admin URL Filter**
   - [ ] Ensure `fix_admin_url` runs at correct priority (1)
   - [ ] Add blog context validation
   - [ ] Fix scheme conversion (admin → http/https)
   - [ ] Preserve query strings and fragments
   - [ ] Add debug logging

3. **Test All Admin URLs**
   - [ ] Test `/wp-admin/` for all sites
   - [ ] Test `/wp-admin/edit.php` for all sites
   - [ ] Test `/wp-admin/post.php?post=X&action=edit` for all sites
   - [ ] Test `/wp-admin/upload.php` for all sites
   - [ ] Test `/wp-admin/options-general.php` for all sites
   - [ ] Verify redirects work correctly

4. **Edge Cases**
   - [ ] Test with root site (blog_id 1)
   - [ ] Test with deeply nested sites (4+ levels)
   - [ ] Test with special characters in paths
   - [ ] Test with query parameters
   - [ ] Test with fragments

#### Acceptance Criteria
- [ ] All admin URLs use correct nested paths
- [ ] Admin URLs work for all nesting levels
- [ ] No admin URLs point to wrong sites
- [ ] Query strings and fragments are preserved

---

### 🟡 P2: Medium Priority - Error Handling & Validation

**Status:** 🟡 Not Started  
**Tags:** `enhancement` `error-handling` `validation` `p2`  
**Assignee:** TBD  
**Estimated Time:** 3-4 hours

#### Subtasks

1. **Site Creation Validation**
   - [ ] Add path validation (no special characters, no conflicts)
   - [ ] Add name validation (required, max length)
   - [ ] Add parent site validation (must exist, must be valid)
   - [ ] Add collision detection UI feedback
   - [ ] Add user-friendly error messages

2. **Graceful Degradation**
   - [ ] Handle nested tree disabled mid-operation
   - [ ] Handle missing database tables
   - [ ] Handle missing upload directories
   - [ ] Add fallback behavior for edge cases

3. **Error Logging**
   - [ ] Add structured error logging
   - [ ] Add error context (blog_id, path, etc.)
   - [ ] Add error severity levels
   - [ ] Create error dashboard (optional)

#### Acceptance Criteria
- [ ] All user actions show clear error messages
- [ ] System handles errors gracefully
- [ ] Errors are logged with context
- [ ] No fatal errors in production

---

### 🟡 P3: Medium Priority - Site Management UI

**Status:** 🟡 Not Started  
**Tags:** `enhancement` `ui` `admin` `p3`  
**Assignee:** TBD  
**Estimated Time:** 4-5 hours

#### Subtasks

1. **Site Deletion UI**
   - [ ] Add delete button to site list
   - [ ] Add confirmation dialog
   - [ ] Handle cascading deletes (children, grandchildren)
   - [ ] Clean up database entries
   - [ ] Clean up upload directories (optional)
   - [ ] Add undo functionality (optional)

2. **Site Editing UI**
   - [ ] Add edit button to site list
   - [ ] Add form to edit site name
   - [ ] Add form to edit site path (with validation)
   - [ ] Handle path changes (update database, redirects)
   - [ ] Add preview of changes

3. **Bulk Operations**
   - [ ] Add checkbox selection to site list
   - [ ] Add bulk delete
   - [ ] Add bulk edit (name, path)
   - [ ] Add bulk export (optional)

#### Acceptance Criteria
- [ ] Users can delete sites via UI
- [ ] Users can edit site name via UI
- [ ] Users can edit site path via UI (with validation)
- [ ] Bulk operations work correctly

---

### 🟢 P4: Low Priority - Performance Optimization

**Status:** 🟢 Not Started  
**Tags:** `enhancement` `performance` `optimization` `p4`  
**Assignee:** TBD  
**Estimated Time:** 3-4 hours

#### Subtasks

1. **Path Resolution Caching**
   - [ ] Add caching for `resolve_blog_for_request_path()`
   - [ ] Use WordPress transients or object cache
   - [ ] Invalidate cache on site creation/deletion
   - [ ] Add cache warming on site creation

2. **Query Optimization**
   - [ ] Optimize `ideai_nested_sites` table queries
   - [ ] Add database indexes if needed
   - [ ] Optimize site tree building
   - [ ] Add query result caching

3. **Lazy Loading**
   - [ ] Implement lazy loading for site tree UI
   - [ ] Load sites on demand (pagination)
   - [ ] Add loading indicators

#### Acceptance Criteria
- [ ] Path resolution is cached
- [ ] Database queries are optimized
- [ ] Site tree UI loads quickly (<2s for 100+ sites)
- [ ] No performance degradation with many sites

---

### 🟢 P5: Low Priority - Testing & Quality Assurance

**Status:** 🟢 Not Started  
**Tags:** `testing` `qa` `automation` `p5`  
**Assignee:** TBD  
**Estimated Time:** 6-8 hours

#### Subtasks

1. **Unit Tests**
   - [ ] Test `get_blog_path()` function
   - [ ] Test `upsert_blog_path()` function
   - [ ] Test `resolve_blog_for_request_path()` function
   - [ ] Test URL rewriting functions
   - [ ] Test upload directory functions

2. **Integration Tests**
   - [ ] Test site creation flow
   - [ ] Test site routing
   - [ ] Test URL generation
   - [ ] Test upload handling
   - [ ] Test admin URL generation

3. **Smoke Tests**
   - [ ] Update `nested-tree-smoke.sh`
   - [ ] Test all nesting levels
   - [ ] Test all admin URLs
   - [ ] Test all upload scenarios
   - [ ] Add to CI/CD pipeline

4. **Browser Testing**
   - [ ] Test in Chrome
   - [ ] Test in Firefox
   - [ ] Test in Safari
   - [ ] Test in Edge
   - [ ] Test responsive design

#### Acceptance Criteria
- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] Smoke tests run automatically
- [ ] Cross-browser compatibility verified

---

### 🟢 P6: Low Priority - Documentation

**Status:** 🟢 Partially Complete  
**Tags:** `documentation` `docs` `p6`  
**Assignee:** TBD  
**Estimated Time:** 2-3 hours

#### Subtasks

1. **API Documentation**
   - [ ] Add PHPDoc to all functions
   - [ ] Document all filters and hooks
   - [ ] Document all database tables
   - [ ] Create API reference guide

2. **Architecture Documentation**
   - [ ] Create system architecture diagram
   - [ ] Document data flow
   - [ ] Document component interactions
   - [ ] Create sequence diagrams

3. **User Documentation**
   - [ ] Create user guide for site creation
   - [ ] Create troubleshooting guide
   - [ ] Create FAQ
   - [ ] Create video tutorials (optional)

4. **Developer Documentation**
   - [ ] Create developer setup guide
   - [ ] Document code structure
   - [ ] Document testing procedures
   - [ ] Create contribution guide

#### Acceptance Criteria
- [ ] All functions have PHPDoc
- [ ] Architecture is documented
- [ ] User guide is complete
- [ ] Developer guide is complete

---

### 🟢 P7: Low Priority - Advanced Features

**Status:** 🟢 Not Started  
**Tags:** `enhancement` `feature` `advanced` `p7`  
**Assignee:** TBD  
**Estimated Time:** 8-10 hours

#### Subtasks

1. **WP-CLI Commands**
   - [ ] `wp nested-site create` command
   - [ ] `wp nested-site list` command
   - [ ] `wp nested-site delete` command
   - [ ] `wp nested-site update` command
   - [ ] `wp nested-site tree` command

2. **REST API**
   - [ ] Endpoint: `GET /wp-json/ideai/v1/nested-sites`
   - [ ] Endpoint: `POST /wp-json/ideai/v1/nested-sites`
   - [ ] Endpoint: `PUT /wp-json/ideai/v1/nested-sites/{id}`
   - [ ] Endpoint: `DELETE /wp-json/ideai/v1/nested-sites/{id}`
   - [ ] Add authentication
   - [ ] Add permissions

3. **Site Migration**
   - [ ] Tool to move site between levels
   - [ ] Tool to change parent site
   - [ ] Handle URL updates
   - [ ] Handle database updates
   - [ ] Handle upload directory moves

4. **Export/Import**
   - [ ] Export site structure to JSON
   - [ ] Import site structure from JSON
   - [ ] Export site content
   - [ ] Import site content

#### Acceptance Criteria
- [ ] WP-CLI commands work correctly
- [ ] REST API endpoints work correctly
- [ ] Site migration works correctly
- [ ] Export/import works correctly

---

## 📋 Task Summary

| Priority | Count | Status |
|----------|-------|--------|
| P0 (Critical) | 1 | 🔴 Not Working |
| P1 (High) | 1 | 🟡 Partially Working |
| P2 (Medium) | 1 | 🟡 Not Started |
| P3 (Medium) | 1 | 🟡 Not Started |
| P4 (Low) | 1 | 🟢 Not Started |
| P5 (Low) | 1 | 🟢 Not Started |
| P6 (Low) | 1 | 🟢 Partially Complete |
| P7 (Low) | 1 | 🟢 Not Started |

**Total Tasks:** 8 major tasks  
**Total Subtasks:** ~80+ subtasks  
**Estimated Total Time:** 30-40 hours

---

## 🏷️ Semantic Tags Reference

### Priority Tags
- `p0` - Critical (must fix immediately)
- `p1` - High priority (fix soon)
- `p2` - Medium priority (fix when possible)
- `p3` - Medium priority (nice to have)
- `p4` - Low priority (optimization)
- `p5` - Low priority (testing)
- `p6` - Low priority (documentation)
- `p7` - Low priority (advanced features)

### Type Tags
- `bug` - Bug fix
- `enhancement` - New feature or improvement
- `documentation` - Documentation update
- `testing` - Testing related
- `performance` - Performance optimization
- `security` - Security related

### Area Tags
- `uploads` - Upload handling
- `admin` - Admin area
- `urls` - URL generation/rewriting
- `multisite` - Multisite functionality
- `routing` - Site routing
- `ui` - User interface
- `api` - API endpoints
- `cli` - WP-CLI commands

### Status Tags
- `critical` - Critical issue
- `high-priority` - High priority issue
- `blocked` - Blocked by another task
- `in-progress` - Currently being worked on
- `ready-for-review` - Ready for code review
- `done` - Completed

---

## 📝 Notes

- Tasks are organized by priority (P0 = highest, P7 = lowest)
- Each task has semantic tags for easy filtering
- Subtasks are checkboxes for tracking progress
- Estimated time is per task (not per subtask)
- Assignees should be set when work begins
- Status should be updated as work progresses

---

## 🔄 How to Use This Document

1. **For Project Managers:**
   - Use tags to filter tasks by priority, type, or area
   - Assign tasks to team members
   - Track progress via checkbox completion
   - Update status as work progresses

2. **For Developers:**
   - Pick a task based on priority and skills
   - Check off subtasks as you complete them
   - Update status when starting/finishing
   - Add notes if blocked or need clarification

3. **For GitHub:**
   - Create issues from tasks (one issue per major task)
   - Use tags in issue labels
   - Link subtasks as checklists in issue descriptions
   - Reference this document in issue descriptions

---

**Last Updated:** 2025-12-14  
**Next Review:** Weekly

