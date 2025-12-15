---
name: 🔴 P0: Uploads Management System Architecture
about: Design and implement flexible uploads management for single sites, multisites, and nested sites
title: '[P0] Uploads Management System - Architecture & Implementation'
labels: 'enhancement,uploads,multisite,architecture,p0'
assignees: ''
---

## 🔴 Priority: P0 (Critical Foundation)

**Status:** 🟡 Planning  
**Estimated Time:** 20-30 hours  
**Area:** Architecture, Uploads, Multisite, CI/CD

---

## Problem Statement

We need a flexible, performant uploads management system that:
- Works seamlessly for **ALL site types**: single sites, subdomain multisite, subdirectory multisite
- Supports **nested sites** in both subdomain and subdirectory multisite
- Supports independent uploads repositories (separate from code)
- Enables nested site content to be organized hierarchically
- Works identically in local development and AWS production
- Supports CI/CD workflows for deployments

### Current Limitations
- Uploads are in Docker volumes (hard to manage independently)
- No easy way to sync uploads between local and AWS
- No hierarchical structure for nested sites
- No CI/CD integration
- Difficult to backup/restore uploads
- System must work for both subdomain AND subdirectory multisite (nested or not)

---

## Requirements

### 1. Universal Site Management
- [ ] Unified configuration system for ALL types:
  - [ ] Single sites
  - [ ] Subdomain multisite (nested or not)
  - [ ] Subdirectory multisite (nested or not)
- [ ] Same commands/tools work for all types
- [ ] Clear distinction in config file (`multisite_type: subdomain|subdirectory`)
- [ ] Support for nested sites in both multisite types

### 2. Independent Uploads Repositories
- [ ] Uploads in separate directories outside main codebase
- [ ] Easy to backup/restore independently
- [ ] Easy to sync between environments
- [ ] Support for separate Git repos or Git LFS

### 3. Flexible Configuration System
- [ ] YAML config file (`config/uploads-config.yml`)
- [ ] Well-commented and easy to understand
- [ ] Environment-specific overrides
- [ ] Supports local and AWS configurations

### 4. Nested Site Content Hierarchy
- [ ] Parent sites contain children as subfolders
- [ ] Logical file system matches site hierarchy
- [ ] Works for nested sites in **both** subdomain and subdirectory multisite
- [ ] WordPress still sees correct `sites/{blog_id}/` structure
- [ ] URL generation remains correct for both multisite types

### 5. Easy Folder-to-Site Marriage
- [ ] Connect existing folder to site
- [ ] Import uploads from backup
- [ ] Sync from production to local
- [ ] Connect from external source

### 6. CI/CD Support
- [ ] Sync uploads during deployment
- [ ] Pull from S3 on deploy
- [ ] Push to S3 after changes
- [ ] Environment-specific workflows

### 7. Local & AWS Parity
- [ ] Same config works in both environments
- [ ] Same commands work in both
- [ ] Automatic environment detection
- [ ] Secure credential management

---

## Proposed Architecture

### Directory Structure
```
project-root/
├── config/
│   └── uploads-config.yml
├── uploads/                        # Local uploads (gitignored)
│   ├── site1.localwp/
│   ├── site2.localwp/
│   └── site3.localwp/
│       └── sites/
│           ├── 1/                  # Root
│           ├── 54/                  # Parent 1
│           │   └── children/
│           │       ├── 55/          # Child 1
│           │       └── 57/          # Child 2
│           └── 58/                  # Parent 2
└── scripts/uploads/
    ├── connect-uploads.sh
    ├── sync-from-s3.sh
    ├── sync-to-s3.sh
    ├── backup.sh
    └── restore.sh
```

### Configuration File
See `docs/UPLOADS_MANAGEMENT_ARCHITECTURE.md` for full config example.

---

## Subtasks

### Phase 1: Planning & Design
- [ ] Review and refine architecture document
- [ ] Answer open questions (see architecture doc)
- [ ] Design configuration file structure
- [ ] Design directory structure
- [ ] Design migration path
- [ ] Create detailed technical specification

### Phase 2: Local Structure Implementation
- [ ] Create new directory structure
- [ ] Create configuration file (`config/uploads-config.yml`)
- [ ] Create configuration parser/loader
- [ ] Update Docker Compose to use new structure
- [ ] Create migration script for existing uploads
- [ ] Test with single site
- [ ] Test with multisite
- [ ] Test with nested multisite

### Phase 3: Nested Hierarchy
- [ ] Design nested folder structure
- [ ] Implement path mapping (logical → WordPress)
- [ ] Update `nested-tree-uploads.php` to use new paths
- [ ] Update URL generation filters
- [ ] Test with subdirectory nested multisite (current site3)
- [ ] Test with subdomain nested multisite (if applicable)
- [ ] Test with non-nested multisite (both types)
- [ ] Migrate existing nested sites to new structure
- [ ] Verify all uploads work correctly for all types

### Phase 4: Management Scripts
- [ ] Create `connect-uploads.sh` script
- [ ] Create `sync-from-s3.sh` script
- [ ] Create `sync-to-s3.sh` script
- [ ] Create `backup.sh` script
- [ ] Create `restore.sh` script
- [ ] Create `migrate-nested.sh` script
- [ ] Add error handling and logging
- [ ] Add progress indicators
- [ ] Test all scripts

### Phase 5: AWS Integration
- [ ] Set up S3 buckets for each site
- [ ] Configure IAM roles and permissions
- [ ] Implement S3 sync functionality
- [ ] Test sync from local to S3
- [ ] Test sync from S3 to local
- [ ] Test incremental sync
- [ ] Add S3 versioning
- [ ] Add S3 lifecycle policies

### Phase 6: CI/CD Integration
- [ ] Create GitHub Actions workflow
- [ ] Add uploads sync step to deployment
- [ ] Configure environment variables
- [ ] Test deployment workflow
- [ ] Add rollback capability
- [ ] Document CI/CD process

### Phase 7: WordPress Integration
- [ ] Update `nested-tree-uploads.php` MU-plugin
- [ ] Add `get_uploads_base_path()` function
- [ ] Add `get_nested_uploads_path($blog_id)` function
- [ ] Add `sync_uploads_to_s3($blog_id)` function
- [ ] Add `restore_uploads_from_backup($backup_path)` function
- [ ] Update all upload filters to use new paths
- [ ] Test all upload scenarios

### Phase 8: Testing & Documentation
- [ ] Test with single site
- [ ] Test with subdomain multisite (non-nested)
- [ ] Test with subdomain multisite (nested)
- [ ] Test with subdirectory multisite (non-nested)
- [ ] Test with subdirectory multisite (nested, 4 levels)
- [ ] Test backup/restore for all types
- [ ] Test sync workflows for all types
- [ ] Test CI/CD deployment
- [ ] Performance testing
- [ ] Update `docs/UPLOADS_ARCHITECTURE.md`
- [ ] Create user guide (all site types)
- [ ] Create developer guide
- [ ] Create troubleshooting guide

---

## Acceptance Criteria

- [ ] Configuration file is easy to understand and well-commented
- [ ] Single sites work correctly with new structure
- [ ] Subdomain multisite (non-nested) works correctly
- [ ] Subdomain multisite (nested) works correctly
- [ ] Subdirectory multisite (non-nested) works correctly
- [ ] Subdirectory multisite (nested) works correctly
- [ ] Nested sites use hierarchical folder structure (both types)
- [ ] All uploads save to correct directories (all types)
- [ ] All URLs generate correctly (all types)
- [ ] Scripts work for connecting folders to sites (all types)
- [ ] S3 sync works in both directions (all types)
- [ ] CI/CD integration works
- [ ] Same config works locally and on AWS
- [ ] Performance is acceptable (no degradation)
- [ ] Documentation is complete (covers all site types)

---

## Related Files

- `docs/UPLOADS_MANAGEMENT_ARCHITECTURE.md` - Architecture discussion
- `docs/UPLOADS_ARCHITECTURE.md` - Current architecture
- `docker-compose.flexible.yml` - Docker configuration
- `wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-uploads.php` - Current uploads handling

---

## Open Questions

See `docs/UPLOADS_MANAGEMENT_ARCHITECTURE.md` for detailed open questions.

Key questions:
1. Nested structure format: `sites/54/children/55/` vs `sites/54/child1/`?
2. S3 structure: Flat vs nested vs hybrid?
3. Performance: Bind mount vs Docker volume? S3 vs EFS vs EBS?
4. Backup strategy: Full vs incremental? Frequency?
5. CI/CD: Push on deploy? Pull on deploy? Conflict resolution?

---

## References

- See `docs/UPLOADS_MANAGEMENT_ARCHITECTURE.md` for full discussion
- See `docs/GITHUB_TASKS.md` for task list
- See `docs/PROJECT_STATUS.md` for project status

