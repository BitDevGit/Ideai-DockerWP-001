# WordPress Content Strategy - Proposal for Review

## 📋 Executive Summary

**Goal**: Share wp-content (plugins, themes) across multiple Docker WordPress projects with:
- Fast local development (no rebuilds)
- Self-contained production images
- Reusable wp-content repository
- AWS deployment integration

## 🎯 Proposed Solution: Hybrid Approach

### Local Development
- **Docker volume mount** for `wp-content/`
- **Optional symlink** to shared repository
- **Instant changes** - no rebuild needed

### Production Build
- **Copy wp-content into Docker image** during build
- **Self-contained** - no external dependencies
- **Fast deployment** to AWS

### Repository Management
- **Separate wp-content repository**
- **Build-time integration** (simpler than git submodules)
- **Version pinning** via build args

## 📊 Comparison of Options

| Approach | Local Dev | Production | Reusability | Complexity |
|----------|-----------|------------|-------------|------------|
| **Volume Mount Only** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Git Submodule** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| **Build-time Fetch** | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Hybrid (Recommended)** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |

## 🏗️ Implementation Phases

### Phase 1: Local Development Setup
**Duration**: 1-2 days  
**Deliverables**:
- `docker-compose.override.yml` with volume mounts
- `wp-content/` directory structure
- Setup script for symlink to shared repo
- Documentation

**Files to Create**:
- `docker-compose.override.yml`
- `scripts/dev/setup-wp-content.sh`
- `wp-content/.gitkeep`

### Phase 2: Production Build Integration
**Duration**: 2-3 days  
**Deliverables**:
- Production Dockerfile with wp-content copy
- Build script for including wp-content
- Updated deployment process
- Testing

**Files to Create**:
- `wordpress/Dockerfile.production`
- `scripts/build/build-with-content.sh`
- Updated `scripts/deployment/prepare-deployment.sh`

### Phase 3: Shared Repository Integration
**Duration**: 2-3 days  
**Deliverables**:
- Separate wp-content repository
- Build-time fetch integration
- CI/CD updates
- Complete documentation

**Files to Create**:
- wp-content repository (separate)
- Updated build scripts
- CI/CD configuration

## 📁 Proposed File Structure

```
project-root/
├── wp-content/                    # Local (can be symlink)
│   ├── plugins/
│   ├── themes/
│   └── uploads/                  # Volume mounted
├── docker-compose.yml            # Base config
├── docker-compose.override.yml   # Local volume mounts
├── wordpress/
│   ├── Dockerfile               # Base (current)
│   └── Dockerfile.production    # With wp-content
└── scripts/
    ├── dev/
    │   └── setup-wp-content.sh
    ├── build/
    │   └── build-with-content.sh
    └── deployment/
        └── prepare-deployment.sh  # Updated
```

## 🔄 Workflow Examples

### Local Development
```bash
# Setup wp-content (one time)
./scripts/dev/setup-wp-content.sh ../wp-content-repo

# Start services
docker-compose up -d

# Edit plugins/themes - changes appear instantly!
```

### Production Build
```bash
# Build with local wp-content
./scripts/build/build-with-content.sh ./wp-content

# OR build with remote repo
./scripts/build/build-with-content.sh "" https://github.com/user/wp-content-repo.git

# Deploy to AWS
./scripts/deployment/deploy-to-instance.sh ...
```

## ✅ Benefits

1. **Fast Local Development**
   - Volume mount = instant changes
   - No rebuilds during development
   - Can use symlink to shared repo

2. **Self-Contained Production**
   - wp-content included in image
   - No external dependencies at runtime
   - Fast AWS deployment

3. **Reusable Content**
   - Separate wp-content repository
   - Use across multiple projects
   - Version controlled separately

4. **Simple Workflow**
   - Clear separation: local vs production
   - Build scripts handle complexity
   - Team-friendly approach

## ⚠️ Considerations

1. **Uploads Handling**
   - Local: Volume mount (excluded from git)
   - Production: Separate volume (not in image)
   - Solution: Use Docker volume for uploads

2. **Build Time**
   - Including wp-content increases image size
   - Mitigation: Multi-stage builds, layer caching

3. **Version Management**
   - How to pin wp-content version?
   - Solution: Build args or git tags

4. **Team Workflow**
   - Everyone needs wp-content access
   - Solution: Shared repo with clear docs

## 📝 Questions for Review

1. **Repository Strategy**: 
   - ✅ Build-time fetch (recommended)
   - ⚠️ Git submodule (alternative)
   - Your preference?

2. **Local Setup**:
   - ✅ Volume mount + optional symlink (recommended)
   - ⚠️ Always symlink
   - Your preference?

3. **Uploads**:
   - ✅ Separate volume (recommended)
   - ⚠️ Include in image (not recommended)
   - Your preference?

4. **Version Pinning**:
   - ✅ Git tags/branches in build script
   - ⚠️ Build args
   - Your preference?

## 🚀 Next Steps

1. **Review this proposal** ✅
2. **Answer questions above**
3. **Approve approach**
4. **Begin Phase 1 implementation**

## 📚 Detailed Documentation

- **[Strategy Document](docs/architecture/WP_CONTENT_STRATEGY.md)** - Full strategy
- **[Implementation Details](docs/architecture/WP_CONTENT_IMPLEMENTATION.md)** - Technical details

---

## ✍️ Sign-off

**Status**: 📋 Awaiting Review

**Reviewer**: _________________  
**Date**: _________________  
**Approved**: ☐ Yes  ☐ No  ☐ With Changes

**Notes**:
_________________________________________________
_________________________________________________
_________________________________________________

