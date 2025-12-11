# Quick Start Guide

## 🚀 Step 1: Start Docker Desktop

**On macOS:**
```bash
# Option 1: Open from Applications
open -a Docker

# Option 2: Use Spotlight
# Press Cmd+Space, type "Docker", press Enter
```

**Wait for Docker to start:**
- Look for Docker icon in menu bar (whale icon)
- Wait until it shows "Docker Desktop is running"
- This takes 30-60 seconds

**Verify Docker is running:**
```bash
docker info
# Should show Docker system information (not an error)
```

---

## 🏃 Step 2: Start WordPress Stack

```bash
cd /Users/sv/_MYUI/_LocalDev/Ideai-DockerWP-001
docker-compose up -d
```

**What this does:**
1. Creates Docker network: `wp-network`
2. Creates volumes: `wp_data`, `db_data`
3. Starts database container
4. Starts WordPress container
5. Starts Nginx container

**Expected output:**
```
Creating network "wordpress-multisite_wp-network" ... done
Creating volume "wordpress-multisite_db_data" ... done
Creating volume "wordpress-multisite_wp_data" ... done
Creating wordpress-multisite-db-1 ... done
Creating wordpress-multisite-wordpress-1 ... done
Creating wordpress-multisite-nginx-1 ... done
```

---

## ✅ Step 3: Verify Everything is Running

```bash
docker-compose ps
```

**Expected output:**
```
NAME                           STATUS          PORTS
wordpress-multisite-db-1       Up (healthy)    
wordpress-multisite-wordpress-1 Up             
wordpress-multisite-nginx-1   Up              0.0.0.0:80->80/tcp
```

All containers should show "Up" status.

---

## 🌐 Step 4: Access WordPress

**Open in browser:**
- **Site:** http://localhost
- **Admin:** http://localhost/wp-admin

**First time setup:**
1. Choose language
2. Enter site title, username, password, email
3. Click "Install WordPress"

---

## 📊 Step 5: Monitor Logs (Optional)

```bash
# View all logs
docker-compose logs -f

# View specific service
docker-compose logs -f nginx
docker-compose logs -f wordpress
docker-compose logs -f db
```

**Press Ctrl+C to stop following logs**

---

## 🛑 Stop Everything

```bash
docker-compose down
```

**To stop and remove volumes (deletes database!):**
```bash
docker-compose down -v
```

---

## 🔍 Troubleshooting

### Docker won't start
- Check Docker Desktop is installed
- Restart Docker Desktop
- Check system resources (Docker needs ~2GB RAM)

### Containers won't start
```bash
# Check logs
docker-compose logs

# Restart containers
docker-compose restart

# Rebuild if needed
docker-compose up -d --build
```

### Port 80 already in use
```bash
# Find what's using port 80
lsof -i :80

# Or change port in docker-compose.yml:
# ports:
#   - "8080:80"  # Use port 8080 instead
```

### Can't access http://localhost
1. Check containers are running: `docker-compose ps`
2. Check Nginx logs: `docker-compose logs nginx`
3. Check WordPress logs: `docker-compose logs wordpress`
4. Try: `curl http://localhost` (should return HTML)

---

## 📚 Next Steps

After WordPress is running:
1. **Read:** `DOCKER_WALKTHROUGH.md` - Understand the architecture
2. **Test:** Activate test theme and plugin
3. **Explore:** Check logs, inspect containers
4. **Deploy:** When ready, deploy to AWS Lightsail

---

## 🎯 Quick Commands Reference

```bash
# Start
docker-compose up -d

# Stop
docker-compose down

# Restart
docker-compose restart

# View status
docker-compose ps

# View logs
docker-compose logs -f

# Access WordPress container
docker-compose exec wordpress bash

# Access database
docker-compose exec db mysql -u wordpress -p

# Check network
docker network ls
docker network inspect wordpress-multisite_wp-network

# Check volumes
docker volume ls
```

---

**Ready? Let's start Docker and get WordPress running!** 🚀

