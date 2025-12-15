# Uploads Management Architecture - Discussion & Requirements

**Status:** 🟡 Planning  
**Priority:** P0 (Critical Foundation)  
**Last Updated:** 2025-12-15

---

## 🎯 Vision

Create a flexible, performant, and maintainable uploads management system that:
- Works seamlessly for both single sites and multisites
- Supports independent uploads repositories (separate from code)
- Enables nested site content to be organized hierarchically
- Works identically in local development and AWS production
- Supports CI/CD workflows for deployments

---

## 📋 Requirements

### 1. Universal Site Management (Single, Multisite, Nested)
**Requirement:** Manage uploads for ALL site types with the same system:
- ✅ Single sites
- ✅ Subdomain multisite (nested or not)
- ✅ Subdirectory multisite (nested or not)

**Current State:**
- Single sites: `wp-content/uploads/`
- Multisite (subdomain or subdirectory): `wp-content/uploads/sites/{blog_id}/`
- Each top-level site has its own Docker volume

**Desired State:**
- Unified configuration system for ALL types
- Same commands/tools work for all
- Clear distinction between site types in config
- Support for nested sites in both subdomain and subdirectory multisite

---

### 2. Independent Uploads Repositories
**Requirement:** Uploads folder completely independent from plugins/themes (wp-content).

**Current State:**
- Code: `./wp-content/themes`, `./wp-content/plugins` (in repo, read-only mounts)
- Uploads: Docker volumes (`wp1_uploads`, `wp2_uploads`, `wp3_uploads`)

**Desired State:**
- Uploads in separate directories/repos outside of main codebase
- Easy to backup/restore uploads independently
- Easy to sync uploads between environments
- Support for Git LFS or separate Git repos for uploads

**Proposed Structure:**
```
project-root/
├── wp-content/              # Code (themes, plugins, mu-plugins)
│   ├── themes/
│   ├── plugins/
│   └── mu-plugins/
├── uploads/                  # Uploads (separate, not in main repo)
│   ├── site1.localwp/        # Single site
│   │   └── sites/
│   │       └── 1/            # Single site uploads
│   ├── site2.localwp/        # Subdomain multisite (can be nested)
│   │   └── sites/
│   │       └── 1/            # Root site (site2.localwp)
│   │       └── 2/            # Subdomain 1 (sub1.site2.localwp)
│   │       └── 3/            # Subdomain 2 (sub2.site2.localwp)
│   │       └── 54/           # Nested parent (if nested enabled)
│   │           └── children/
│   │               └── 55/    # Nested child
│   └── site3.localwp/        # Subdirectory multisite (nested)
│       └── sites/
│           └── 1/            # Root site
│           └── 54/            # Parent 1 (/parent1/)
│               └── children/
│                   └── 55/     # Child 1 (/parent1/child1/)
│                       └── children/
│                           └── 56/  # Grandchild 1
```

---

### 3. Flexible Configuration System
**Requirement:** Easy-to-understand, well-commented configuration.

**Proposed Config File:** `config/uploads-config.yml`

```yaml
# Uploads Management Configuration
# This file defines how uploads are managed for each site
# Supports: Single sites, Subdomain multisite, Subdirectory multisite (nested or not)

sites:
  # Single Site Example
  site1.localwp:
    type: single
    uploads_path: ./uploads/site1.localwp
    docker_volume: wp1_uploads
    aws_s3_bucket: site1-uploads-prod
    aws_s3_prefix: uploads/
    
  # Subdomain Multisite Example (non-nested)
  site2.localwp:
    type: multisite
    multisite_type: subdomain  # subdomain or subdirectory
    nested: false
    uploads_path: ./uploads/site2.localwp
    docker_volume: wp2_uploads
    aws_s3_bucket: site2-uploads-prod
    aws_s3_prefix: uploads/
    
  # Subdomain Multisite with Nested Sites
  site2-nested.localwp:
    type: multisite
    multisite_type: subdomain
    nested: true
    uploads_path: ./uploads/site2-nested.localwp
    docker_volume: wp2_nested_uploads
    aws_s3_bucket: site2-nested-uploads-prod
    aws_s3_prefix: uploads/
    nested_structure:
      enabled: true
      parent_includes_children: true  # Children are subfolders of parent
    
  # Subdirectory Multisite (non-nested)
  site3-simple.localwp:
    type: multisite
    multisite_type: subdirectory
    nested: false
    uploads_path: ./uploads/site3-simple.localwp
    docker_volume: wp3_simple_uploads
    aws_s3_bucket: site3-simple-uploads-prod
    aws_s3_prefix: uploads/
    
  # Subdirectory Multisite with Nested Sites (Current site3)
  site3.localwp:
    type: multisite
    multisite_type: subdirectory
    nested: true
    uploads_path: ./uploads/site3.localwp
    docker_volume: wp3_uploads
    aws_s3_bucket: site3-uploads-prod
    aws_s3_prefix: uploads/
    # Nested sites inherit parent's upload structure
    nested_structure:
      enabled: true
      parent_includes_children: true  # Children are subfolders of parent

# Global Settings
global:
  # Local Development
  local:
    base_path: ./uploads
    use_docker_volumes: true
    sync_method: bind-mount  # or volume, or symlink
    
  # AWS Production
  aws:
    region: us-east-1
    sync_method: s3  # or efs, or ebs
    backup_enabled: true
    backup_retention_days: 30
    
  # CI/CD
  cicd:
    sync_on_deploy: true
    sync_direction: pull  # pull from S3, or push to S3
    exclude_patterns:
      - "*.tmp"
      - ".DS_Store"
```

---

### 4. Nested Site Content Hierarchy
**Requirement:** Nested sites' content organized as subfolders of parent.
**Applies to:** Both subdomain and subdirectory multisite when nested tree is enabled.

**Current State:**
- All sites at same level: `sites/54/`, `sites/55/`, `sites/56/`
- No hierarchical relationship in file system
- Works for both subdomain and subdirectory multisite

**Desired State:**
- Parent site: `sites/54/`
- Child site: `sites/54/children/55/` (or `sites/54/child1/`)
- Grandchild: `sites/54/children/55/children/56/`
- Works identically for subdomain and subdirectory nested multisite

**Benefits:**
- Clear parent-child relationship in file system
- Easy to backup entire parent tree
- Easy to move/delete parent and all children
- Matches logical site hierarchy
- Same structure works for both multisite types

**Challenges:**
- WordPress expects `sites/{blog_id}/` structure
- Need to map logical structure to WordPress structure
- URL generation must remain correct
- Must work for both subdomain and subdirectory multisite

---

### 5. Easy Folder-to-Site Marriage
**Requirement:** Easily connect a folder of uploads to a site (locally, stably, performantly).

**Use Cases:**
- Import existing uploads from another site
- Restore uploads from backup
- Sync uploads from production to local
- Connect uploads from external source

**Proposed Commands:**
```bash
# Connect existing folder to site
./scripts/uploads/connect-uploads.sh site3.localwp ./backups/site3-uploads-2025-12-15

# Sync uploads from S3 to local
./scripts/uploads/sync-from-s3.sh site3.localwp

# Sync uploads from local to S3
./scripts/uploads/sync-to-s3.sh site3.localwp

# Restore uploads from backup
./scripts/uploads/restore.sh site3.localwp ./backups/site3-uploads-2025-12-15.tar.gz
```

---

### 6. CI/CD Support
**Requirement:** Support CI/CD workflows for deployments.

**Workflow:**
1. **Local Development:**
   - Uploads in local folders or Docker volumes
   - Easy to test and develop

2. **Staging/Production:**
   - Uploads in S3 (or EFS/EBS)
   - Synced during deployment
   - Backed up automatically

3. **Deployment Process:**
   ```yaml
   # .github/workflows/deploy.yml
   - name: Sync Uploads
     run: |
       ./scripts/uploads/sync-to-s3.sh site3.localwp
       # Or pull from S3 if deploying to new environment
       ./scripts/uploads/sync-from-s3.sh site3.localwp
   ```

**Requirements:**
- Same config works locally and on AWS
- Environment-specific overrides
- Secure credential management
- Efficient sync (only changed files)

---

### 7. Local & AWS Parity
**Requirement:** Same setup works locally and on AWS.

**Local:**
- Docker volumes or bind mounts
- Fast file access
- Easy to backup/restore

**AWS:**
- S3 for storage (or EFS/EBS)
- CloudFront for CDN
- Automatic backups
- Versioning enabled

**Abstraction Layer:**
- Same commands work in both environments
- Config file handles differences
- Scripts detect environment automatically

---

## 🏗️ Proposed Architecture

### Directory Structure

```
project-root/
├── config/
│   └── uploads-config.yml          # Main configuration
├── uploads/                        # Local uploads (gitignored)
│   ├── site1.localwp/
│   ├── site2.localwp/
│   └── site3.localwp/
│       └── sites/
│           ├── 1/                  # Root site
│           ├── 54/                  # Parent 1
│           │   └── children/
│           │       ├── 55/          # Child 1
│           │       │   └── children/
│           │       │       └── 56/   # Grandchild 1
│           │       └── 57/          # Child 2
│           └── 58/                  # Parent 2
├── scripts/
│   └── uploads/
│       ├── connect-uploads.sh      # Connect folder to site
│       ├── sync-from-s3.sh         # Pull from S3
│       ├── sync-to-s3.sh           # Push to S3
│       ├── backup.sh               # Backup uploads
│       ├── restore.sh               # Restore uploads
│       └── migrate-nested.sh       # Migrate to nested structure
└── docker-compose.flexible.yml     # Updated with new mounts
```

### WordPress Integration

**MU-Plugin:** `nested-tree-uploads.php` (already exists, needs enhancement)

**New Functions:**
- `get_uploads_base_path()` - Get base path for uploads (local or S3)
- `get_nested_uploads_path($blog_id)` - Get path for nested site
- `sync_uploads_to_s3($blog_id)` - Sync specific site to S3
- `restore_uploads_from_backup($backup_path)` - Restore from backup

---

## 🔄 Migration Path

### Phase 1: Local Structure
1. Create new directory structure
2. Migrate existing uploads to new structure
3. Update Docker Compose config
4. Test locally

### Phase 2: Nested Hierarchy
1. Implement nested folder structure
2. Update WordPress filters to use new paths
3. Test with nested sites
4. Migrate existing nested sites

### Phase 3: AWS Integration
1. Set up S3 buckets
2. Create sync scripts
3. Test sync workflows
4. Integrate with CI/CD

### Phase 4: Advanced Features
1. Automatic backups
2. Versioning
3. CDN integration
4. Performance optimization

---

## ❓ Open Questions

1. **Nested Structure:**
   - Should children be in `sites/54/children/55/` or `sites/54/child1/`?
   - How to handle deep nesting (4+ levels)?

2. **S3 Structure:**
   - Flat: `s3://bucket/site3/sites/54/...`
   - Nested: `s3://bucket/site3/sites/54/children/55/...`
   - Hybrid: Logical nested, but WordPress still sees flat?

3. **Performance:**
   - Local: Bind mount vs Docker volume?
   - AWS: S3 vs EFS vs EBS?
   - Caching strategy?

4. **Backup Strategy:**
   - Full backup vs incremental?
   - How often?
   - Retention policy?

5. **CI/CD:**
   - Push to S3 on every deploy?
   - Pull from S3 on deploy?
   - How to handle conflicts?

---

## 📝 Next Steps

1. **Discuss & Refine:** Review this document, answer open questions
2. **Create Tasks:** Break down into actionable tasks
3. **Prototype:** Build proof of concept
4. **Test:** Test with real sites
5. **Document:** Update documentation
6. **Deploy:** Roll out gradually

---

**See Also:**
- `docs/UPLOADS_ARCHITECTURE.md` - Current architecture
- `docs/GITHUB_TASKS.md` - Task list
- `.github/ISSUE_TEMPLATE/` - Issue templates

