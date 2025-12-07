# Scaling Guide - WordPress Multisite

This guide covers scaling strategies for your WordPress multisite deployment on AWS Lightsail.

## Scaling Strategies

### 1. Vertical Scaling (Scale Up)

Increase the size of your Lightsail instance to handle more traffic.

#### When to Scale Up
- CPU consistently above 70%
- Memory usage consistently above 80%
- Database queries taking longer than 2 seconds
- High traffic during peak hours

#### How to Scale Up

1. **In Lightsail Console:**
   - Go to your instance
   - Click "Stop" (wait for it to stop)
   - Click "Change plan"
   - Select larger instance size
   - Click "Start"

2. **Recommended Instance Sizes:**
   - Small sites: 2GB RAM, 1 vCPU
   - Medium sites: 4GB RAM, 2 vCPU
   - Large sites: 8GB RAM, 2 vCPU
   - Enterprise: 16GB+ RAM, 4+ vCPU

### 2. Horizontal Scaling (Scale Out)

Add multiple instances behind a load balancer.

#### Architecture

```
                    ┌─────────────┐
                    │ CloudFront  │
                    │    (CDN)    │
                    └──────┬──────┘
                           │
                    ┌──────▼──────┐
                    │  Load       │
                    │  Balancer   │
                    └──┬───────┬──┘
                       │       │
            ┌──────────▼─┐   ┌─▼──────────┐
            │ Instance 1 │   │ Instance 2 │
            │ WordPress  │   │ WordPress  │
            └──────┬─────┘   └─────┬──────┘
                   │               │
            ┌──────▼───────────────▼──────┐
            │      RDS MySQL              │
            │   (Shared Database)         │
            └─────────────────────────────┘
            
            ┌─────────────────────────────┐
            │   ElastiCache Redis         │
            │   (Shared Cache)            │
            └─────────────────────────────┘
```

#### Setup Steps

1. **Create RDS MySQL Instance:**
   ```bash
   # In AWS Console or CLI
   aws rds create-db-instance \
     --db-instance-identifier wordpress-db \
     --db-instance-class db.t3.medium \
     --engine mysql \
     --master-username wordpress \
     --master-user-password <strong-password> \
     --allocated-storage 100
   ```

2. **Create ElastiCache Redis Cluster:**
   ```bash
   aws elasticache create-cache-cluster \
     --cache-cluster-id wordpress-redis \
     --cache-node-type cache.t3.medium \
     --engine redis \
     --num-cache-nodes 1
   ```

3. **Update docker-compose.yml:**
   ```yaml
   services:
     wordpress:
       environment:
         WORDPRESS_DB_HOST: <rds-endpoint>:3306
         REDIS_HOST: <elasticache-endpoint>
     db:
       # Remove or comment out local db service
   ```

4. **Set Up Application Load Balancer:**
   - Create ALB in AWS Console
   - Add target groups for each Lightsail instance
   - Configure health checks
   - Update CloudFront origin to point to ALB

5. **Create Multiple Lightsail Instances:**
   - Deploy same application to 2+ instances
   - Use shared RDS and ElastiCache
   - Configure session storage in Redis

### 3. Database Scaling

#### Read Replicas

For read-heavy workloads:

```bash
# Create read replica
aws rds create-db-instance-read-replica \
  --db-instance-identifier wordpress-db-replica \
  --source-db-instance-identifier wordpress-db
```

Update WordPress to use read replicas for queries (requires plugin or custom code).

#### Database Optimization

```bash
# Optimize database
docker-compose exec wordpress wp db optimize --allow-root

# Analyze tables
docker-compose exec wordpress wp db query "ANALYZE TABLE wp_posts" --allow-root
```

### 4. Caching Strategy

#### Object Cache (Redis)

Already configured in this setup. Monitor Redis usage:

```bash
docker-compose exec redis redis-cli INFO stats
```

#### Page Caching

Consider adding:
- **WP Super Cache** or **W3 Total Cache** plugins
- **Varnish** or **Nginx FastCGI Cache** (already configured in nginx)

#### CDN Caching (CloudFront)

- Static assets cached at edge
- Configure cache behaviors for different content types
- Set appropriate TTL values

### 5. File Storage Scaling

#### S3 for Media Files

Move WordPress uploads to S3:

1. Install **WP Offload Media** plugin
2. Configure S3 bucket
3. Set up CloudFront for S3 bucket
4. Update WordPress to use S3 URLs

#### Benefits:
- Reduced server storage
- Faster content delivery
- Better scalability

### 6. Auto-Scaling Setup

#### Using AWS Auto Scaling

1. **Create Launch Template:**
   - Based on your Lightsail instance
   - Include user data script for automatic setup

2. **Create Auto Scaling Group:**
   ```bash
   aws autoscaling create-auto-scaling-group \
     --auto-scaling-group-name wordpress-asg \
     --min-size 2 \
     --max-size 5 \
     --desired-capacity 2 \
     --target-group-arns <target-group-arn>
   ```

3. **Set Up Scaling Policies:**
   - Scale up when CPU > 70%
   - Scale down when CPU < 30%
   - Scale based on request count

### 7. Monitoring and Metrics

#### Key Metrics to Monitor

- **CPU Utilization**: Should stay below 70%
- **Memory Usage**: Should stay below 80%
- **Database Connections**: Monitor max connections
- **Response Time**: Should be < 200ms for cached pages
- **Request Rate**: Requests per second
- **Error Rate**: Should be < 1%

#### CloudWatch Alarms

```bash
# CPU alarm
aws cloudwatch put-metric-alarm \
  --alarm-name high-cpu \
  --alarm-description "Alert when CPU exceeds 70%" \
  --metric-name CPUUtilization \
  --namespace AWS/Lightsail \
  --statistic Average \
  --period 300 \
  --threshold 70 \
  --comparison-operator GreaterThanThreshold
```

### 8. Performance Optimization

#### WordPress Optimization

```bash
# Install optimization plugins
docker-compose exec wordpress wp plugin install wp-super-cache --activate --allow-root
docker-compose exec wordpress wp plugin install autoptimize --activate --allow-root

# Enable object cache
docker-compose exec wordpress wp config set WP_CACHE true --allow-root
```

#### Database Optimization

```bash
# Regular maintenance
docker-compose exec wordpress wp db optimize --allow-root
docker-compose exec wordpress wp db repair --allow-root

# Clean up
docker-compose exec wordpress wp db query "DELETE FROM wp_options WHERE option_name LIKE '_transient_%'" --allow-root
```

#### Nginx Optimization

Already configured:
- Gzip compression
- Static file caching
- FastCGI caching (optional)

### 9. Cost Optimization

#### Lightsail Pricing Tiers

- **$3.50/month**: 512MB RAM (not recommended)
- **$5/month**: 1GB RAM (development only)
- **$10/month**: 2GB RAM (small sites)
- **$20/month**: 4GB RAM (medium sites)
- **$40/month**: 8GB RAM (large sites)
- **$80/month**: 16GB RAM (enterprise)

#### Cost-Saving Tips

1. Use CloudFront for static assets (reduces bandwidth costs)
2. Enable compression (reduces bandwidth)
3. Use S3 for media files (cheaper than instance storage)
4. Schedule non-essential instances to stop during off-hours
5. Use reserved instances for predictable workloads

### 10. Disaster Recovery

#### Multi-Region Setup

1. Deploy to multiple AWS regions
2. Use Route 53 for DNS failover
3. Replicate database across regions
4. Sync files via S3 cross-region replication

#### Backup Strategy

- Daily database backups to S3
- Weekly full site backups
- Monthly disaster recovery tests
- Keep backups for 30-90 days

## Scaling Checklist

- [ ] Monitor current resource usage
- [ ] Identify bottlenecks (CPU, memory, database, network)
- [ ] Choose scaling strategy (vertical vs horizontal)
- [ ] Set up shared database (RDS) if scaling horizontally
- [ ] Set up shared cache (ElastiCache) if scaling horizontally
- [ ] Configure load balancer
- [ ] Set up CloudFront CDN
- [ ] Move media files to S3
- [ ] Configure auto-scaling policies
- [ ] Set up monitoring and alerts
- [ ] Test failover scenarios
- [ ] Document scaling procedures

## Recommended Scaling Path

1. **Start**: Single Lightsail instance (4GB RAM)
2. **Growth**: Add CloudFront CDN
3. **More Growth**: Move to RDS + ElastiCache
4. **High Traffic**: Add second instance + Load Balancer
5. **Enterprise**: Multiple instances + Auto-scaling + Multi-region

## Support

For scaling questions or issues, refer to:
- AWS Lightsail Documentation
- WordPress Multisite Best Practices
- This project's README.md

