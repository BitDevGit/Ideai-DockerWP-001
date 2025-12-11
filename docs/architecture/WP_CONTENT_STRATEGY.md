# WordPress Content Strategy

## Overview

Strategy for managing shared `wp-content` (plugins, themes) across multiple Docker WordPress instances, enabling:
- Local development with live reload
- Production builds with bundled content
- Reusable wp-content repository
- Docker-aligned architecture

## Requirements

1. **Local Development**: Fast iteration, no rebuilds needed
2. **Production**: Self-contained Docker images with wp-content included
3. **Reusability**: Same wp-content across multiple WordPress projects
4. **Version Control**: Separate wp-content repository
5. **AWS Deployment**: wp-content included in deployed image

## Proposed Architecture

### Phase 1: Local Development (Docker Volume Mount)

**Structure:**
```
project-root/
├── wp-content/              # Shared wp-content (symlink or volume)
│   ├── plugins/
│   ├── themes/
│   └── uploads/             # Excluded from git
├── docker-compose.yml       # Volume mount for local dev
└── wordpress/
    └── Dockerfile           # Production build
```

**Local docker-compose.yml:**
```yaml
wordpress:
  volumes:
    - ./wp-content:/var/www/html/wp-content  # Live mount
```

**Benefits:**
- ✅ Instant changes (no rebuild)
- ✅ Fast development cycle
- ✅ Can use symlink to shared repo

### Phase 2: Production Build (Copy into Image)

**Dockerfile Strategy:**
```dockerfile
FROM wordpress:6.4-php8.2-fpm

# Copy wp-content into image
COPY wp-content/ /var/www/html/wp-content/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/wp-content
```

**Benefits:**
- ✅ Self-contained production image
- ✅ No external dependencies
- ✅ Fast deployment

### Phase 3: Separate wp-content Repository

**Option A: Git Submodule**
```
wordpress-project/
├── .gitmodules
└── wp-content/  (submodule)
    └── -> ../wp-content-repo/
```

**Option B: Build-time Copy**
```
Build process:
1. Clone wp-content repo
2. Copy into Docker build context
3. Build image with wp-content included
```

**Option C: Docker Build Arg**
```dockerfile
ARG WP_CONTENT_SOURCE
COPY ${WP_CONTENT_SOURCE} /var/www/html/wp-content/
```

## Recommended Approach (Phased)

### Phase 1: Local Development Setup ✅

**Implementation:**
1. Create `wp-content/` directory structure
2. Configure docker-compose.yml for volume mount
3. Use symlink to shared repo (optional)

**Files:**
- `docker-compose.override.yml` - Local volume mounts
- `wp-content/` - Local development content

### Phase 2: Production Build Integration ✅

**Implementation:**
1. Update Dockerfile to copy wp-content
2. Create build script that includes wp-content
3. Update deployment process

**Files:**
- `wordpress/Dockerfile` - Updated with wp-content copy
- `scripts/build/build-with-content.sh` - Build script

### Phase 3: Shared Repository Integration ✅

**Implementation:**
1. Create separate wp-content repository
2. Integrate as git submodule OR build-time dependency
3. Update CI/CD to include wp-content in builds

**Files:**
- `wp-content/` - Git submodule or build-time copy
- `.gitmodules` - If using submodule
- `scripts/build/fetch-content.sh` - Fetch wp-content for build

## Detailed Implementation Plan

### Phase 1: Local Development (Week 1)

**Goal:** Fast local development with live wp-content

**Steps:**
1. Create `wp-content/` directory
2. Configure docker-compose.override.yml
3. Test volume mounting
4. Document local workflow

**Deliverables:**
- ✅ Working local setup
- ✅ Documentation
- ✅ Example wp-content structure

### Phase 2: Production Build (Week 2)

**Goal:** Include wp-content in production images

**Steps:**
1. Update Dockerfile
2. Create build scripts
3. Test production build
4. Update deployment process

**Deliverables:**
- ✅ Production Dockerfile
- ✅ Build scripts
- ✅ Updated deployment docs

### Phase 3: Shared Repository (Week 3)

**Goal:** Reusable wp-content across projects

**Steps:**
1. Create wp-content repository
2. Choose integration method (submodule vs build-time)
3. Update CI/CD
4. Document workflow

**Deliverables:**
- ✅ wp-content repository
- ✅ Integration method
- ✅ CI/CD updates
- ✅ Complete documentation

## File Structure (Proposed)

```
project-root/
├── wp-content/                    # Local dev (symlink or real)
│   ├── plugins/
│   ├── themes/
│   ├── uploads/                  # .gitignored
│   └── .gitkeep
├── docker-compose.yml            # Base config
├── docker-compose.override.yml   # Local dev overrides
├── wordpress/
│   └── Dockerfile                # Production build
├── scripts/
│   └── build/
│       ├── build-with-content.sh
│       └── fetch-content.sh
└── docs/
    └── architecture/
        └── WP_CONTENT_STRATEGY.md
```

## Questions for Review

1. **Repository Strategy**: Submodule vs build-time copy?
2. **Local Development**: Symlink vs real directory?
3. **Uploads Handling**: Volume mount for uploads in production?
4. **Version Control**: How to version wp-content separately?
5. **Build Process**: When to fetch/update wp-content?

## Next Steps

1. **Review this document** ✅
2. **Choose repository strategy** (submodule vs build-time)
3. **Approve Phase 1 implementation**
4. **Begin Phase 1 development**

---

**Status**: 📋 Awaiting Review  
**Next Action**: Review and approve approach


