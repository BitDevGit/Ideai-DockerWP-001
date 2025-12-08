# Troubleshooting Guide

Common issues and their solutions.

## Database Connection Errors

### Error: "Error establishing a database connection"

**Symptoms:**
- WordPress shows database connection error
- Database container restarting

**Solutions:**

1. **Check database container status:**
   ```bash
   docker-compose ps db
   ```

2. **Wait for database initialization:**
   ```bash
   # Database needs 30-60 seconds to initialize
   docker-compose logs db | tail -20
   ```

3. **Verify environment variables:**
   ```bash
   cat .env | grep DB_
   ```

4. **Test database connection:**
   ```bash
   docker-compose exec db mysql -u wordpress -p
   ```

5. **If using small instance (512MB RAM):**
   - Switch to MariaDB (already configured)
   - Reduce buffer pool size in docker-compose.yml

## Container Won't Start

### Error: Container keeps restarting

**Check logs:**
```bash
docker-compose logs <service-name>
```

**Common causes:**
- Port conflict (80, 443 already in use)
- Insufficient memory
- Configuration error

**Solutions:**
```bash
# Check what's using port 80
sudo lsof -i :80

# Free up memory
docker system prune

# Rebuild containers
docker-compose up -d --build --force-recreate
```

## Nginx Configuration Errors

### Error: "duplicate upstream"

**Solution:**
```bash
# Remove duplicate config files
rm nginx/conf.d/local.conf
rm nginx/conf.d/._*.conf

# Restart nginx
docker-compose restart nginx
```

## Memory Issues

### Error: Container killed (exit code 137)

**Symptoms:**
- Containers restarting frequently
- "Killed" messages in logs

**Solutions:**

1. **Check available memory:**
   ```bash
   free -h
   ```

2. **Reduce database memory:**
   - Already optimized for small instances
   - MariaDB uses 64M buffer pool

3. **Upgrade instance:**
   - Minimum: 1GB RAM
   - Recommended: 2GB+ RAM

## SSH Connection Timeouts

### Error: "Connection timed out"

**When deploying to Lightsail:**

1. **Check instance status:**
   ```bash
   aws lightsail get-instance --instance-name wordpress-multisite --region eu-west-2
   ```

2. **Verify port 22 is open:**
   ```bash
   aws lightsail get-instance-port-states --instance-name wordpress-multisite --region eu-west-2
   ```

3. **Use browser SSH:**
   - Go to Lightsail console
   - Click "Connect using SSH"
   - More reliable than terminal SSH

4. **Check IP address:**
   - IP may change after restart
   - Get current IP: `aws lightsail get-instance ... --query 'instance.publicIpAddress'`

## WordPress Installation Issues

### Error: 500 Internal Server Error

**Before installation:**
- This is normal - WordPress needs to be installed
- Open the URL in browser to start installation

**After installation:**
```bash
# Check WordPress logs
docker-compose logs wordpress

# Check file permissions
docker-compose exec wordpress ls -la /var/www/html

# Verify database connection
docker-compose exec wordpress wp db check --allow-root
```

## Docker Build Timeouts

### Error: "TLS handshake timeout"

**When building on Lightsail:**

**Solution:** Use pre-built images instead of building:
```bash
# In docker-compose.yml, use:
image: wordpress:6.4-php8.2-fpm
# Instead of:
build: ./wordpress
```

## Performance Issues

### Slow page loads

**Check:**
```bash
# Container resource usage
docker stats

# Database queries
docker-compose exec wordpress wp db query "SHOW PROCESSLIST;" --allow-root

# Nginx access logs
docker-compose exec nginx tail -f /var/log/nginx/access.log
```

**Optimizations:**
- Enable OPcache (already configured)
- Use Redis caching (optional)
- Enable CloudFront CDN
- Optimize images

## Getting Help

1. Check logs: `docker-compose logs`
2. Review [Deployment Guide](../deployment/DEPLOYMENT.md)
3. Check [Architecture Docs](../architecture/SCALING.md)
4. Open an issue on GitHub

