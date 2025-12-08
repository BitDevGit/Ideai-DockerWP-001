# Scaling Guide

Strategies for scaling your WordPress multisite deployment.

## Current Architecture

```
Nginx → WordPress (PHP-FPM) → MariaDB
```

## Vertical Scaling

### Increase Instance Size

**AWS Lightsail:**
1. Go to instance settings
2. Change bundle size
3. Restart instance

**Recommended sizes:**
- Small sites: 1-2GB RAM
- Medium sites: 2-4GB RAM
- Large sites: 4-8GB RAM
- Enterprise: 8GB+ RAM

### Database Optimization

Already optimized for small instances:
- MariaDB 10.11 (lighter than MySQL)
- 64M buffer pool
- Optimized for 512MB-1GB instances

## Horizontal Scaling

### Load Balancer Setup

1. **Create multiple instances:**
   ```bash
   # Deploy to multiple Lightsail instances
   ./scripts/deployment/deploy-to-instance.sh instance-1 IP1 ubuntu key.pem
   ./scripts/deployment/deploy-to-instance.sh instance-2 IP2 ubuntu key.pem
   ```

2. **Use AWS Application Load Balancer:**
   - Create ALB in front of instances
   - Configure health checks
   - Point domain to ALB

### Database Scaling

**Option 1: RDS**
- Use AWS RDS for MySQL/MariaDB
- Automatic backups
- Read replicas for scaling

**Option 2: Read Replicas**
- Set up MariaDB master-slave
- WordPress reads from replicas

### Redis Caching

Add Redis for object caching:

```yaml
# In docker-compose.yml
redis:
  image: redis:7-alpine
  volumes:
    - redis_data:/data
```

## CDN Setup

### CloudFront

See CloudFront setup script:
```bash
./scripts/deployment/setup-cloudfront.sh
```

**Benefits:**
- Global content delivery
- Reduced server load
- Better performance

## Performance Optimization

### Already Configured

- ✅ OPcache enabled
- ✅ Gzip compression
- ✅ Static file caching
- ✅ Nginx rate limiting

### Additional Optimizations

1. **Image Optimization:**
   - Use WebP format
   - Lazy loading
   - CDN for images

2. **Database:**
   - Regular optimization
   - Query caching
   - Index optimization

3. **WordPress:**
   - Use caching plugins
   - Minimize plugins
   - Optimize themes

## Monitoring

### Resource Monitoring

```bash
# Container stats
docker stats

# System resources
htop
```

### Application Monitoring

Consider:
- CloudWatch (AWS)
- New Relic
- Datadog
- Uptime monitoring

## Cost Optimization

### Lightsail Bundles

- Start small (1GB)
- Monitor usage
- Scale up as needed

### CDN Benefits

- Reduces server load
- Lower bandwidth costs
- Better performance

## Scaling Checklist

- [ ] Monitor resource usage
- [ ] Set up automated backups
- [ ] Configure CDN
- [ ] Enable caching
- [ ] Optimize database
- [ ] Set up monitoring
- [ ] Plan for load balancer
- [ ] Document scaling procedures

## Next Steps

- [Deployment Guide](../deployment/DEPLOYMENT.md)
- [Troubleshooting](../troubleshooting/TROUBLESHOOTING.md)
