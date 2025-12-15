# Uploads Architecture

## Overview

The WordPress uploads directory is separated from the code repository to keep user-generated content out of version control.

## Directory Structure

### In Repository (Version Controlled)
- `wp-content/themes/` - Theme files
- `wp-content/plugins/` - Plugin files  
- `wp-content/mu-plugins/` - Must-use plugins
- `wp-content/languages/` - Translation files

### In Docker Volumes (Not in Repository)
- `wp-content/uploads/` - User-uploaded media files
  - `sites/{blog_id}/` - Site-specific uploads for multisite
  - Organized by year/month: `sites/{blog_id}/{year}/{month}/`

## Docker Volume Configuration

Each WordPress site has its own uploads volume:
- `wp1_uploads` - Site 1 uploads
- `wp2_uploads` - Site 2 uploads  
- `wp3_uploads` - Site 3 uploads

These volumes are mounted at `/var/www/html/wp-content/uploads` and are **not** part of the git repository.

## Benefits

1. **Clean Repository** - No user-generated content in version control
2. **Faster Git Operations** - Smaller repository size
3. **Better Performance** - Uploads in dedicated volumes
4. **Easier Backups** - Can backup uploads separately from code
5. **Multisite Support** - Each site's uploads are isolated

## Multisite Uploads

For nested multisite (site3.localwp), uploads are organized as:
```
wp-content/uploads/
  sites/
    54/              # Blog ID 54 (Parent 1)
      2025/
        12/
          image.jpg
    55/              # Blog ID 55 (Parent 1 → Child 1)
      2025/
        12/
          image.jpg
```

## Migration Notes

When migrating from the old setup (where uploads were in the repo):
1. Uploads were moved to Docker volumes
2. Existing uploads in `./wp-content/uploads/` should be migrated to volumes
3. The `./wp-content/uploads/` directory in the repo is now ignored by git

