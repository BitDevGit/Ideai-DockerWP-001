# WordPress Content Workflow

Complete guide for working with wp-content in this project.

## Overview

This project uses a hybrid approach for wp-content:
- **Local Development**: Volume mount for instant changes
- **Production**: wp-content included in Docker image
- **Repository**: Can use shared wp-content repository

## Local Development

### Setup

1. **Create local wp-content** (if not using shared repo):
   ```bash
   mkdir -p wp-content/{plugins,themes,uploads}
   ```

2. **Or symlink to shared repository**:
   ```bash
   ./scripts/dev/setup-wp-content.sh /path/to/wp-content-repo
   ```

3. **Start services**:
   ```bash
   docker-compose up -d
   ```

### Development Workflow

- **Edit plugins/themes**: Changes appear instantly (volume mount)
- **Uploads**: Stored in Docker volume (`wp_uploads`)
- **No rebuilds needed**: Volume mount provides live updates

### File Structure

```
wp-content/
├── plugins/          # Your custom plugins
├── themes/           # Your custom themes
└── uploads/          # User uploads (Docker volume)
```

## Production Build

### Building Image with wp-content

**Option 1: Use local wp-content**
```bash
./scripts/build/build-with-content.sh ./wp-content
```

**Option 2: Fetch from repository**
```bash
./scripts/build/build-with-content.sh "" https://github.com/user/wp-content-repo.git
```

**Option 3: Manual build**
```bash
docker build \
  --build-arg WP_CONTENT_SOURCE=./wp-content \
  -f wordpress/Dockerfile.production \
  -t wordpress-multisite:latest \
  .
```

### Using Built Image

Update `docker-compose.yml`:
```yaml
wordpress:
  image: wordpress-multisite:latest  # Your built image
  volumes:
    - wp_uploads:/var/www/html/wp-content/uploads  # Only uploads
```

## Deployment

### Prepare Deployment Package

```bash
# Builds image and creates deployment package
./scripts/deployment/prepare-deployment.sh
```

This will:
1. Build WordPress image with wp-content
2. Create deployment package
3. Include all necessary files

### Deploy to AWS Lightsail

```bash
./scripts/deployment/deploy-to-instance.sh \
  wordpress-multisite \
  13.40.170.117 \
  ubuntu \
  /path/to/key.pem
```

## Shared Repository Setup

### Create wp-content Repository

1. Create new repository:
   ```bash
   mkdir wp-content-repo
   cd wp-content-repo
   git init
   mkdir plugins themes
   git add .
   git commit -m "Initial wp-content structure"
   ```

2. Add plugins and themes:
   ```bash
   # Add your plugins
   cp -r /path/to/plugin wp-content-repo/plugins/
   
   # Add your themes
   cp -r /path/to/theme wp-content-repo/themes/
   
   git add .
   git commit -m "Add plugins and themes"
   ```

### Use in Projects

**Local development:**
```bash
./scripts/dev/setup-wp-content.sh ../wp-content-repo
```

**Production build:**
```bash
./scripts/build/build-with-content.sh "" https://github.com/user/wp-content-repo.git
```

## Updating wp-content

### Local Development

1. Edit files in `wp-content/plugins/` or `wp-content/themes/`
2. Changes appear immediately (volume mount)
3. No rebuild needed

### Production

1. Update wp-content repository (if using shared repo)
2. Rebuild image:
   ```bash
   ./scripts/build/build-with-content.sh "" https://github.com/user/wp-content-repo.git
   ```
3. Redeploy:
   ```bash
   ./scripts/deployment/deploy-to-instance.sh ...
   ```

## Best Practices

1. **Version Control**:
   - Keep wp-content in separate repository
   - Use git tags for versioning
   - Document plugin/theme versions

2. **Local Development**:
   - Use volume mounts for speed
   - Symlink to shared repo for consistency
   - Keep uploads in Docker volume

3. **Production**:
   - Include wp-content in image
   - Use separate volume for uploads
   - Document build process

4. **Testing**:
   - Test locally with volume mounts
   - Build production image before deployment
   - Verify plugins/themes in production

## Troubleshooting

### wp-content not appearing

**Local:**
- Check volume mount in `docker-compose.override.yml`
- Verify `wp-content/` directory exists
- Check Docker volume: `docker volume ls`

**Production:**
- Verify image includes wp-content: `docker inspect <image>`
- Check build logs for copy errors
- Verify Dockerfile.production is used

### Changes not appearing

**Local:**
- Restart container: `docker-compose restart wordpress`
- Check volume mount: `docker-compose exec wordpress ls -la /var/www/html/wp-content`

**Production:**
- Rebuild image with updated wp-content
- Redeploy to instance

### Permission issues

```bash
# Fix permissions
docker-compose exec wordpress chown -R www-data:www-data /var/www/html/wp-content
docker-compose exec wordpress chmod -R 755 /var/www/html/wp-content
```

## Environment Variables

```bash
# Local development
WP_CONTENT_DIR=/var/www/html/wp-content
WP_DEBUG=1

# Production
WP_CONTENT_DIR=/var/www/html/wp-content
WP_DEBUG=0
```

## File Structure Reference

```
project-root/
├── wp-content/                    # Local (can be symlink)
│   ├── plugins/
│   ├── themes/
│   └── uploads/                  # Docker volume
├── docker-compose.yml
├── docker-compose.override.yml    # Local volume mounts
├── wordpress/
│   ├── Dockerfile                # Base
│   └── Dockerfile.production     # With wp-content
└── scripts/
    ├── dev/
    │   └── setup-wp-content.sh
    └── build/
        └── build-with-content.sh
```

## Next Steps

- [Quick Start Guide](QUICKSTART.md)
- [Deployment Guide](DEPLOYMENT.md)
- [Troubleshooting](../troubleshooting/TROUBLESHOOTING.md)

