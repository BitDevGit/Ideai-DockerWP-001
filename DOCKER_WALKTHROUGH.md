# Docker Architecture Walkthrough

## 🏗️ Overview: How Everything Works Together

```
┌─────────────────────────────────────────────────────────┐
│                    Your Browser                          │
│              http://localhost                            │
└────────────────────┬────────────────────────────────────┘
                     │
                     │ HTTP Request
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Nginx Container (Port 80)                              │
│  ┌──────────────────────────────────────────────────┐   │
│  │ • Receives HTTP requests                         │   │
│  │ • Serves static files (CSS, JS, images)          │   │
│  │ • Forwards PHP requests to WordPress container   │   │
│  │ • Handles SSL/TLS (in production)               │   │
│  │ • Rate limiting & security headers               │   │
│  └──────────────────────────────────────────────────┘   │
└────────────────────┬────────────────────────────────────┘
                     │
                     │ FastCGI (PHP requests)
                     ▼
┌─────────────────────────────────────────────────────────┐
│  WordPress Container (PHP-FPM :9000)                    │
│  ┌──────────────────────────────────────────────────┐   │
│  │ • Runs WordPress PHP code                        │   │
│  │ • Processes themes, plugins                      │   │
│  │ • Handles wp-content (themes/plugins)            │   │
│  │ • Connects to database for data                  │   │
│  │ • Uses Redis for caching (optional)              │   │
│  └──────────────────────────────────────────────────┘   │
└────────────────────┬────────────────────────────────────┘
                     │
                     │ SQL Queries
                     ▼
┌─────────────────────────────────────────────────────────┐
│  MariaDB Container (Port 3306)                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │ • Stores all WordPress data                      │   │
│  │ • Posts, pages, users, settings                  │   │
│  │ • Plugin data, theme settings                    │   │
│  │ • Persistent storage (Docker volume)             │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

---

## 📦 Container Breakdown

### 1. **Nginx Container** (`nginx`)

**What it does:**
- Acts as a reverse proxy and web server
- Receives all HTTP requests from your browser
- Serves static files directly (faster than PHP)
- Forwards PHP requests to WordPress container

**Why Nginx?**
- **Performance**: Much faster than Apache for static files
- **Efficiency**: Lower memory usage
- **Scalability**: Handles many concurrent connections
- **Security**: Better rate limiting and security features

**Configuration:**
```yaml
nginx:
  image: nginx:alpine          # Lightweight Alpine Linux
  ports:
    - "80:80"                  # Maps host port 80 → container port 80
  volumes:
    - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro    # Main config
    - ./nginx/conf.d:/etc/nginx/conf.d:ro            # Site configs
    - wp_data:/var/www/html:ro                       # WordPress files (read-only)
  depends_on:
    - wordpress                # Waits for WordPress to start
```

**Key Nginx Features:**
- **Gzip compression**: Reduces file sizes by 70-90%
- **Static file caching**: Images/CSS/JS cached for 1 year
- **Rate limiting**: Prevents DDoS attacks (10 requests/second)
- **Security headers**: XSS protection, frame options, etc.

---

### 2. **WordPress Container** (`wordpress`)

**What it does:**
- Runs WordPress PHP code
- Processes themes and plugins
- Handles all WordPress logic
- Connects to database for data

**Why PHP-FPM?**
- **Separation**: Nginx handles HTTP, PHP-FPM handles PHP
- **Performance**: Better than mod_php (Apache)
- **Scalability**: Can run multiple PHP workers
- **Resource isolation**: PHP crashes don't affect web server

**Configuration:**
```yaml
wordpress:
  image: wordpress:6.4-php8.2-fpm    # Official WordPress with PHP 8.2 FPM
  volumes:
    - wp_data:/var/www/html          # WordPress core files
    # Local dev override adds:
    - ./wp-content:/var/www/html/wp-content  # Your themes/plugins (live)
  environment:
    WORDPRESS_DB_HOST: db:3306       # Database connection
    WORDPRESS_DB_USER: wordpress
    WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
    WORDPRESS_DB_NAME: wordpress
  depends_on:
    - db                             # Waits for database
```

**How PHP-FPM Works:**
1. Nginx receives PHP request (e.g., `/index.php`)
2. Nginx forwards to `wordpress:9000` (FastCGI)
3. PHP-FPM processes the PHP code
4. PHP-FPM queries database if needed
5. PHP-FPM returns HTML to Nginx
6. Nginx sends HTML to browser

**Local Development Override:**
```yaml
# docker-compose.override.yml (auto-loaded)
wordpress:
  volumes:
    - ./wp-content:/var/www/html/wp-content  # Live editing!
    - wp_uploads:/var/www/html/wp-content/uploads  # Separate uploads
  environment:
    - WP_DEBUG=1                    # Show errors locally
```

**Why volume mount for wp-content?**
- ✅ Edit themes/plugins instantly (no rebuild needed)
- ✅ See changes immediately
- ✅ Use your favorite editor
- ✅ Git version control

---

### 3. **Database Container** (`db`)

**What it does:**
- Stores all WordPress data
- Handles SQL queries
- Provides persistent storage

**Why MariaDB over MySQL?**
- **Lighter**: Better for small instances (Lightsail)
- **Compatible**: Drop-in MySQL replacement
- **Performance**: Optimized for WordPress
- **Open source**: Fully open source

**Configuration:**
```yaml
db:
  image: mysql:8.0                  # (We use MariaDB in production)
  volumes:
    - db_data:/var/lib/mysql        # Persistent storage
  environment:
    MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    MYSQL_DATABASE: wordpress
    MYSQL_USER: wordpress
    MYSQL_PASSWORD: ${DB_PASSWORD}
  command: --default-authentication-plugin=mysql_native_password
```

**Why Docker Volume?**
- **Persistence**: Data survives container restarts
- **Backup**: Easy to backup entire volume
- **Performance**: Faster than bind mounts for DB
- **Isolation**: Database files separate from code

**What's stored in database?**
- Posts, pages, comments
- Users and permissions
- Plugin settings
- Theme settings
- WordPress options
- Multisite network data

---

## 🔄 Request Flow Example

**User visits: `http://localhost/`**

1. **Browser** → Sends HTTP GET request to `localhost:80`
2. **Nginx** → Receives request, checks if it's a static file
3. **Nginx** → Sees it's `/` (root), forwards to `index.php`
4. **Nginx** → Sends FastCGI request to `wordpress:9000`
5. **WordPress PHP-FPM** → Executes `index.php`
6. **WordPress** → Queries database: `SELECT * FROM wp_posts...`
7. **Database** → Returns post data
8. **WordPress** → Processes theme, generates HTML
9. **WordPress** → Returns HTML to Nginx
10. **Nginx** → Sends HTML to browser
11. **Browser** → Displays page

**User visits: `http://localhost/wp-content/themes/test-cursor-theme/style.css`**

1. **Browser** → Sends HTTP GET request
2. **Nginx** → Sees it's a `.css` file (static)
3. **Nginx** → Serves file directly from `wp_data` volume
4. **Nginx** → Adds cache headers (1 year)
5. **Browser** → Caches file, displays page

---

## 📁 Volume Strategy

### Shared Volume: `wp_data`
```
wp_data:/var/www/html
```
- **Contains**: WordPress core files
- **Shared by**: Nginx (read-only) + WordPress (read-write)
- **Why**: Nginx needs access to serve static files
- **Persistence**: Survives container restarts

### Database Volume: `db_data`
```
db_data:/var/lib/mysql
```
- **Contains**: All database files
- **Used by**: MariaDB container only
- **Why**: Persistent storage for database
- **Backup**: Can backup entire volume

### Local Development: `./wp-content`
```
./wp-content:/var/www/html/wp-content
```
- **Contains**: Your themes and plugins
- **Why**: Live editing without rebuilds
- **Only in**: Local development (override file)
- **Production**: Copied into Docker image

### Uploads Volume: `wp_uploads`
```
wp_uploads:/var/www/html/wp-content/uploads
```
- **Contains**: User-uploaded files
- **Why**: Separate from code (easier backup)
- **Prevents**: Upload conflicts with git

---

## 🌐 Network: `wp-network`

**Why a custom network?**
- **Isolation**: Containers can't see other Docker networks
- **DNS**: Containers can use service names (`wordpress`, `db`)
- **Security**: Only containers on this network can communicate

**How containers communicate:**
```yaml
networks:
  - wp-network
```

**Service names as hostnames:**
- `wordpress` → WordPress container IP
- `db` → Database container IP
- `nginx` → Nginx container IP

**Example:**
```php
// WordPress connects to database using:
WORDPRESS_DB_HOST: db:3306
// "db" resolves to database container IP automatically
```

---

## 🔒 Security Features

### 1. **Nginx Security Headers**
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
```
- Prevents clickjacking
- Prevents MIME type sniffing
- Prevents XSS attacks

### 2. **Rate Limiting**
```nginx
limit_req_zone $binary_remote_addr zone=wp_limit:10m rate=10r/s;
```
- Limits: 10 requests per second per IP
- Prevents: DDoS attacks, brute force

### 3. **File Access Restrictions**
```nginx
location ~ wp-config.php {
    deny all;  # Blocks access to config file
}
```
- Protects sensitive files
- Hides WordPress version info

### 4. **Read-Only Mounts**
```yaml
volumes:
  - wp_data:/var/www/html:ro  # Nginx can't modify files
```
- Nginx only reads files (can't be hacked to modify)
- WordPress container has write access

---

## ⚡ Performance Optimizations

### 1. **Gzip Compression**
```nginx
gzip on;
gzip_comp_level 6;
```
- **Reduces**: File sizes by 70-90%
- **Faster**: Less data to transfer
- **Saves**: Bandwidth and load time

### 2. **Static File Caching**
```nginx
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```
- **Browser caches**: Images, CSS, JS for 1 year
- **Faster**: Subsequent page loads
- **Reduces**: Server load

### 3. **OPcache** (PHP)
```ini
opcache.enable=1
opcache.memory_consumption=128
```
- **Caches**: Compiled PHP code
- **Faster**: PHP execution
- **Reduces**: CPU usage

### 4. **Redis Caching** (Optional)
- **Caches**: Database queries
- **Faster**: Page generation
- **Reduces**: Database load

---

## 🚀 Starting the Stack

### Step-by-Step Process:

1. **Docker Compose reads `docker-compose.yml`**
   - Creates network: `wp-network`
   - Creates volumes: `wp_data`, `db_data`

2. **Starts Database Container**
   - Initializes MariaDB
   - Creates database: `wordpress`
   - Creates user: `wordpress`
   - Waits for readiness

3. **Starts WordPress Container**
   - Downloads WordPress files (if needed)
   - Waits for database
   - Connects to database
   - Initializes WordPress (first run)

4. **Starts Nginx Container**
   - Loads configuration
   - Waits for WordPress
   - Starts listening on port 80

5. **Ready!**
   - All containers running
   - Network connected
   - WordPress accessible at `http://localhost`

---

## 🔍 Debugging & Monitoring

### Check Container Status
```bash
docker-compose ps
```

### View Logs
```bash
docker-compose logs nginx      # Nginx logs
docker-compose logs wordpress  # WordPress logs
docker-compose logs db         # Database logs
docker-compose logs -f         # Follow all logs
```

### Access Containers
```bash
docker-compose exec wordpress bash  # Shell into WordPress
docker-compose exec db mysql -u wordpress -p  # Access database
docker-compose exec nginx sh        # Shell into Nginx
```

### Check Network
```bash
docker network inspect wordpress-multisite_wp-network
```

### Check Volumes
```bash
docker volume ls
docker volume inspect wordpress-multisite_wp_data
```

---

## 📊 Resource Usage

**Typical Memory Usage:**
- Nginx: ~10-20 MB
- WordPress: ~50-100 MB
- MariaDB: ~100-200 MB
- **Total**: ~200-400 MB

**Why so efficient?**
- Alpine Linux (minimal base)
- PHP-FPM (shared memory)
- MariaDB (optimized settings)
- No unnecessary services

---

## 🎯 Key Takeaways

1. **Nginx** = Web server + reverse proxy
2. **WordPress** = PHP application server
3. **MariaDB** = Data storage
4. **Volumes** = Persistent storage
5. **Network** = Container communication
6. **Override file** = Local development tweaks

**Why this architecture?**
- ✅ Separation of concerns
- ✅ Scalability (can add more WordPress containers)
- ✅ Security (each service isolated)
- ✅ Performance (Nginx serves static files fast)
- ✅ Development (volume mounts for live editing)
- ✅ Production (Docker images for deployment)

---

## 🚦 Next Steps

1. Start Docker Desktop
2. Run: `docker-compose up -d`
3. Check: `docker-compose ps`
4. Visit: `http://localhost`
5. Explore: Check logs, inspect containers

**Ready to start? Let's do it!** 🚀


