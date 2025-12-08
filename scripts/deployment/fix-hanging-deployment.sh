#!/bin/bash
# Fix hanging deployment on server

SSH_KEY="${1:-/Users/sv/_MYUI/Dev/LightsailDefaultKey-eu-west-2.pem}"
INSTANCE_IP="${2:-18.130.255.19}"

echo "Fixing hanging deployment..."

# Upload fix script
cat > /tmp/fix-deploy.sh << 'FIXSCRIPT'
#!/bin/bash
set -e
cd /opt/wordpress-multisite

echo "Stopping hanging containers..."
sudo docker-compose down 2>/dev/null || true
sudo docker stop $(sudo docker ps -aq) 2>/dev/null || true

echo "Removing build requirement..."
# Use simple compose without builds
cat > docker-compose.yml << 'EOF'
services:
  nginx:
    image: nginx:alpine
    container_name: wp-nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./nginx/conf.d:/etc/nginx/conf.d:ro
      - wp_data:/var/www/html:ro
    depends_on:
      - wordpress
    restart: unless-stopped
    networks:
      - wp-network

  wordpress:
    image: wordpress:6.4-php8.2-fpm
    container_name: wp-wordpress
    volumes:
      - wp_data:/var/www/html
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: ${DB_USER:-wordpress}
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_DB_NAME: ${DB_NAME:-wordpress}
      WORDPRESS_TABLE_PREFIX: ${TABLE_PREFIX:-wp_}
      WORDPRESS_DEBUG: ${WP_DEBUG:-0}
    depends_on:
      - db
    restart: unless-stopped
    networks:
      - wp-network

  db:
    image: mysql:8.0
    container_name: wp-db
    volumes:
      - db_data:/var/lib/mysql
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_NAME:-wordpress}
      MYSQL_USER: ${DB_USER:-wordpress}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    command: --default-authentication-plugin=mysql_native_password
    restart: unless-stopped
    networks:
      - wp-network

volumes:
  wp_data:
  db_data:

networks:
  wp-network:
    driver: bridge
EOF

echo "Starting with pre-built images only..."
sudo docker-compose pull
sudo docker-compose up -d

echo "Waiting 20 seconds..."
sleep 20

echo "=== Status ==="
sudo docker-compose ps

echo ""
echo "=== WordPress Test ==="
curl -I http://localhost 2>&1 | head -3
FIXSCRIPT

scp -i "$SSH_KEY" -o StrictHostKeyChecking=no /tmp/fix-deploy.sh ubuntu@$INSTANCE_IP:/tmp/ 2>&1 || echo "Upload failed, trying direct command..."

ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no -o ConnectTimeout=15 ubuntu@$INSTANCE_IP << 'ENDSSH'
    cd /opt/wordpress-multisite
    sudo docker-compose down 2>/dev/null || true
    
    # Create simple compose
    sudo tee docker-compose.yml > /dev/null << 'COMPOSE'
services:
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./nginx/conf.d:/etc/nginx/conf.d:ro
      - wp_data:/var/www/html:ro
    depends_on:
      - wordpress
    restart: unless-stopped
    networks:
      - wp-network

  wordpress:
    image: wordpress:6.4-php8.2-fpm
    volumes:
      - wp_data:/var/www/html
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: $(grep DB_PASSWORD .env | cut -d= -f2)
      WORDPRESS_DB_NAME: wordpress
    depends_on:
      - db
    restart: unless-stopped
    networks:
      - wp-network

  db:
    image: mysql:8.0
    volumes:
      - db_data:/var/lib/mysql
    environment:
      MYSQL_ROOT_PASSWORD: $(grep DB_ROOT_PASSWORD .env | cut -d= -f2)
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: $(grep DB_PASSWORD .env | cut -d= -f2)
    command: --default-authentication-plugin=mysql_native_password
    restart: unless-stopped
    networks:
      - wp-network

volumes:
  wp_data:
  db_data:

networks:
  wp-network:
COMPOSE

    sudo docker-compose pull
    sudo docker-compose up -d
    sleep 15
    sudo docker-compose ps
ENDSSH

echo ""
echo "✓ Fixed! Check: http://$INSTANCE_IP"

