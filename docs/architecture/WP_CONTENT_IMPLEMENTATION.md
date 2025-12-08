# WordPress Content Implementation Details

## Implementation Options Comparison

### Option 1: Docker Volume Mount (Local) + Copy (Production)

**Local:**
```yaml
volumes:
  - ./wp-content:/var/www/html/wp-content
```

**Production Dockerfile:**
```dockerfile
COPY wp-content/ /var/www/html/wp-content/
```

**Pros:**
- ✅ Fast local development
- ✅ Simple production build
- ✅ Clear separation

**Cons:**
- ⚠️ Need to rebuild on wp-content changes
- ⚠️ wp-content must be in build context

### Option 2: Git Submodule

**Structure:**
```
project/
├── .gitmodules
└── wp-content/  (submodule -> ../wp-content-repo)
```

**Build:**
```dockerfile
COPY wp-content/ /var/www/html/wp-content/
```

**Pros:**
- ✅ Version controlled separately
- ✅ Reusable across projects
- ✅ Clear dependency

**Cons:**
- ⚠️ Submodule complexity
- ⚠️ Team needs to understand submodules

### Option 3: Build-time Fetch

**Build Script:**
```bash
# Fetch wp-content during build
git clone https://github.com/user/wp-content-repo.git wp-content
docker build --build-arg WP_CONTENT=./wp-content .
```

**Dockerfile:**
```dockerfile
ARG WP_CONTENT
COPY ${WP_CONTENT} /var/www/html/wp-content/
```

**Pros:**
- ✅ Flexible source location
- ✅ Can use different repos per project
- ✅ No submodule complexity

**Cons:**
- ⚠️ Requires build script
- ⚠️ Network dependency during build

### Option 4: Multi-stage with External Source

**Dockerfile:**
```dockerfile
FROM wordpress:6.4-php8.2-fpm as base

# Stage 1: Fetch wp-content
FROM alpine/git as content
RUN git clone https://github.com/user/wp-content-repo.git /content

# Stage 2: Combine
FROM base
COPY --from=content /content /var/www/html/wp-content
```

**Pros:**
- ✅ Self-contained build
- ✅ No external dependencies at runtime
- ✅ Can cache wp-content layer

**Cons:**
- ⚠️ More complex Dockerfile
- ⚠️ Network required during build

## Recommended: Hybrid Approach

### Local Development
- **Volume mount** for instant changes
- **Symlink** to shared wp-content repo (optional)

### Production Build
- **Copy from local** or **fetch during build**
- **Include in image** for self-contained deployment

### Repository Management
- **Separate repo** for wp-content
- **Build-time integration** (simpler than submodule)
- **Version pinning** via build args or tags

## Detailed Implementation

### Phase 1: Local Development Setup

**File: `docker-compose.override.yml`**
```yaml
version: '3.8'

services:
  wordpress:
    volumes:
      # Mount wp-content for live development
      - ./wp-content:/var/www/html/wp-content
      # Exclude uploads from mount (use volume)
      - wp_uploads:/var/www/html/wp-content/uploads
    environment:
      - WP_CONTENT_DIR=/var/www/html/wp-content

volumes:
  wp_uploads:
```

**File: `wp-content/.gitkeep`**
```
# This directory contains WordPress plugins and themes
# For local development, this can be a symlink to a shared repository
```

**File: `scripts/dev/setup-wp-content.sh`**
```bash
#!/bin/bash
# Setup wp-content for local development

WP_CONTENT_REPO="${1:-../wp-content-repo}"
WP_CONTENT_DIR="./wp-content"

if [ -L "$WP_CONTENT_DIR" ]; then
    echo "wp-content is already a symlink"
    exit 0
fi

if [ ! -d "$WP_CONTENT_REPO" ]; then
    echo "Creating wp-content directory"
    mkdir -p "$WP_CONTENT_DIR"/{plugins,themes,uploads}
    echo "To use shared repo, run:"
    echo "  rm -rf wp-content && ln -s $WP_CONTENT_REPO wp-content"
else
    echo "Creating symlink to shared repo"
    rm -rf "$WP_CONTENT_DIR"
    ln -s "$WP_CONTENT_REPO" "$WP_CONTENT_DIR"
    echo "✓ Linked to $WP_CONTENT_REPO"
fi
```

### Phase 2: Production Build

**File: `wordpress/Dockerfile.production`**
```dockerfile
FROM wordpress:6.4-php8.2-fpm

# Install wp-content
# Option A: Copy from build context
ARG WP_CONTENT_SOURCE=../wp-content
COPY ${WP_CONTENT_SOURCE}/ /var/www/html/wp-content/

# Option B: Fetch during build (uncomment to use)
# RUN apt-get update && apt-get install -y git && \
#     git clone https://github.com/user/wp-content-repo.git /tmp/wp-content && \
#     cp -r /tmp/wp-content/* /var/www/html/wp-content/ && \
#     rm -rf /tmp/wp-content && \
#     apt-get remove -y git && apt-get autoremove -y

# Set permissions
RUN chown -R www-data:www-data /var/www/html/wp-content && \
    find /var/www/html/wp-content -type d -exec chmod 755 {} \; && \
    find /var/www/html/wp-content -type f -exec chmod 644 {} \;

# Exclude uploads from image (use volume in production)
RUN rm -rf /var/www/html/wp-content/uploads/* || true
```

**File: `scripts/build/build-with-content.sh`**
```bash
#!/bin/bash
# Build WordPress image with wp-content included

set -e

WP_CONTENT_SOURCE="${1:-./wp-content}"
WP_CONTENT_REPO="${2:-}"

echo "Building WordPress image with wp-content..."

# If repo URL provided, clone it
if [ -n "$WP_CONTENT_REPO" ]; then
    echo "Fetching wp-content from repository..."
    rm -rf /tmp/wp-content-build
    git clone "$WP_CONTENT_REPO" /tmp/wp-content-build
    WP_CONTENT_SOURCE=/tmp/wp-content-build
fi

# Verify wp-content exists
if [ ! -d "$WP_CONTENT_SOURCE" ]; then
    echo "Error: wp-content directory not found: $WP_CONTENT_SOURCE"
    exit 1
fi

# Build image
docker build \
    --build-arg WP_CONTENT_SOURCE="$WP_CONTENT_SOURCE" \
    -f wordpress/Dockerfile.production \
    -t wordpress-multisite:latest \
    .

echo "✓ Build complete"
```

### Phase 3: Deployment Integration

**File: `scripts/deployment/prepare-deployment.sh` (Updated)**
```bash
#!/bin/bash
# Prepare deployment package with wp-content

WP_CONTENT_SOURCE="${WP_CONTENT_SOURCE:-./wp-content}"
WP_CONTENT_REPO="${WP_CONTENT_REPO:-}"

# Fetch wp-content if repo provided
if [ -n "$WP_CONTENT_REPO" ]; then
    echo "Fetching wp-content from repository..."
    git clone "$WP_CONTENT_REPO" ./wp-content-temp
    WP_CONTENT_SOURCE=./wp-content-temp
fi

# Build image with wp-content
./scripts/build/build-with-content.sh "$WP_CONTENT_SOURCE"

# Continue with existing deployment process...
```

## Usage Examples

### Local Development

```bash
# Option 1: Use local wp-content directory
mkdir -p wp-content/{plugins,themes,uploads}

# Option 2: Symlink to shared repo
./scripts/dev/setup-wp-content.sh ../wp-content-repo

# Start services
docker-compose up -d
```

### Production Build

```bash
# Build with local wp-content
./scripts/build/build-with-content.sh ./wp-content

# Build with remote repo
./scripts/build/build-with-content.sh "" https://github.com/user/wp-content-repo.git

# Deploy
./scripts/deployment/deploy-to-instance.sh ...
```

## File Structure (Final)

```
project-root/
├── wp-content/                    # Local dev (can be symlink)
│   ├── plugins/
│   ├── themes/
│   └── uploads/                  # Volume mounted
├── docker-compose.yml
├── docker-compose.override.yml   # Local volume mounts
├── wordpress/
│   ├── Dockerfile                # Base (no wp-content)
│   └── Dockerfile.production     # With wp-content
├── scripts/
│   ├── dev/
│   │   └── setup-wp-content.sh
│   ├── build/
│   │   └── build-with-content.sh
│   └── deployment/
│       └── prepare-deployment.sh  # Updated
└── docs/
    └── architecture/
        ├── WP_CONTENT_STRATEGY.md
        └── WP_CONTENT_IMPLEMENTATION.md
```

## Decision Matrix

| Feature | Volume Mount | Submodule | Build-time Fetch |
|---------|-------------|-----------|------------------|
| Local Dev Speed | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| Production Simplicity | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| Reusability | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Team Complexity | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |
| Build Time | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |

## Recommendation

**Use Hybrid Approach:**
1. **Local**: Volume mount (fastest development)
2. **Production**: Build-time copy (self-contained)
3. **Repository**: Separate repo with build-time fetch (flexible)

This gives best of all worlds:
- ✅ Fast local development
- ✅ Self-contained production images
- ✅ Reusable wp-content repository
- ✅ Simple team workflow

