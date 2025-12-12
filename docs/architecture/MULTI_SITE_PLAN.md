# Multi-Site Local Development Environment Plan

## 🎯 Goal

Create a local WordPress development environment with:
- **2 separate WordPress sites**
- **Each site has its own database**
- **Both sites share the same wp-content folder** (themes, plugins)
- **Easy to add more sites later**

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Nginx (Port 80)                      │
│  ┌──────────────────┐  ┌──────────────────┐            │
│  │  site1.local     │  │  site2.local     │            │
│  │  (Port 80)       │  │  (Port 80)       │            │
│  └────────┬─────────┘  └────────┬─────────┘            │
└───────────┼─────────────────────┼──────────────────────┘
            │                       │
            │ FastCGI               │ FastCGI
            ▼                       ▼
┌──────────────────┐      ┌──────────────────┐
│  WordPress Site1 │      │  WordPress Site2 │
│  (PHP-FPM :9001) │      │  (PHP-FPM :9002) │
└────────┬─────────┘      └────────┬─────────┘
         │                          │
         │ SQL                      │ SQL
         ▼                          ▼
┌──────────────────┐      ┌──────────────────┐
│   Database 1     │      │   Database 2     │
│   (Port 3307)    │      │   (Port 3308)    │
└──────────────────┘      └──────────────────┘
         │                          │
         └──────────┬───────────────┘
                    │
                    ▼
         ┌──────────────────┐
         │  Shared          │
         │  wp-content/     │
         │  (Volume Mount)  │
         └──────────────────┘
```

## 📋 Configuration Options

### Option 1: Subdomain Routing (Recommended)
- `site1.local` → WordPress Site 1
- `site2.local` → WordPress Site 2
- Both on port 80
- Requires `/etc/hosts` entries

### Option 2: Port-Based Routing
- `localhost:8081` → WordPress Site 1
- `localhost:8082` → WordPress Site 2
- No hosts file changes needed

### Option 3: Path-Based Routing
- `localhost/site1` → WordPress Site 1
- `localhost/site2` → WordPress Site 2
- More complex Nginx config

**Recommendation:** Option 1 (Subdomain) - Cleanest, most realistic

## 🗂️ File Structure

```
.
├── docker-compose.yml              # Current single-site setup
├── docker-compose.multi.yml        # Multi-site setup
│
├── nginx/
│   └── conf.d/
│       ├── default.conf            # Current (single site)
│       └── multi-site.conf        # Multi-site Nginx config
│
├── wp-content/                     # Shared (already exists)
│   ├── themes/
│   ├── plugins/
│   └── uploads/
│       ├── site1/                 # Site-specific uploads
│       └── site2/                 # Site-specific uploads
│
└── sites/                          # Site-specific configs
    ├── site1/
    │   └── wp-config.php          # Site 1 config
    └── site2/
        └── wp-config.php          # Site 2 config
```

## 🔧 Implementation Plan

### Phase 1: Docker Compose Configuration
1. Create `docker-compose.multi.yml`
2. Define 2 WordPress containers (different ports)
3. Define 2 database containers (different ports)
4. Shared wp-content volume mount
5. Nginx configuration for routing

### Phase 2: Nginx Configuration
1. Create multi-site Nginx config
2. Route subdomains to correct PHP-FPM
3. Handle static files from shared wp-content

### Phase 3: WordPress Configuration
1. Separate wp-config.php for each site
2. Different database connections
3. Shared wp-content path

### Phase 4: Setup Scripts
1. Script to add hosts entries
2. Script to initialize databases
3. Script to start/stop sites

### Phase 5: Documentation
1. Setup guide
2. Usage guide
3. Troubleshooting

## 📝 Docker Compose Structure

```yaml
services:
  nginx:
    # Routes to both sites
    
  wordpress1:
    # Site 1 PHP-FPM
    ports: ["9001:9000"]
    
  wordpress2:
    # Site 2 PHP-FPM
    ports: ["9002:9000"]
    
  db1:
    # Site 1 database
    ports: ["3307:3306"]
    
  db2:
    # Site 2 database
    ports: ["3308:3306"]
    
volumes:
  wp_content_shared:
    # Shared wp-content
  db1_data:
  db2_data:
  wp1_uploads:
  wp2_uploads:
```

## 🚀 Usage

```bash
# Start multi-site environment
docker-compose -f docker-compose.multi.yml up -d

# Access sites
# http://site1.local
# http://site2.local

# Stop
docker-compose -f docker-compose.multi.yml down
```

## ✅ Benefits

1. **Isolated Databases** - Test different data/configs
2. **Shared Content** - Same themes/plugins, easy updates
3. **Realistic** - Mimics production multisite setup
4. **Scalable** - Easy to add more sites
5. **Fast Development** - Volume mounts for instant changes

## ⚠️ Considerations

1. **Hosts File** - Need to add entries for subdomains
2. **Port Conflicts** - Ensure ports don't conflict
3. **Resource Usage** - 2x containers = more memory
4. **Uploads** - Need separate uploads directories per site
5. **wp-config.php** - Each site needs its own config

## 🎯 Next Steps

1. Create `docker-compose.multi.yml`
2. Create Nginx multi-site config
3. Create setup scripts
4. Test both sites
5. Document usage


