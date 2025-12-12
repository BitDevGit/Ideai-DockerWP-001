#!/bin/bash
# This script runs ON THE SERVER to fix hanging containers
# Upload and execute: bash /tmp/fix-on-server.sh

set -e
cd /opt/wordpress-multisite

echo "=== Stopping hanging containers ==="
sudo docker-compose down 2>/dev/null || true
sudo docker stop $(sudo docker ps -q) 2>/dev/null || true

echo "=== Using simple compose (no builds) ==="
sudo tee docker-compose.yml > /dev/null << 'EOF'
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
      - ./wordpress/php.ini:/usr/local/etc/php/php.ini:ro
      - ./wordpress/uploads.ini:/usr/local/etc/php/conf.d/uploads.ini:ro
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: ${DB_USER:-wordpress}
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_DB_NAME: ${DB_NAME:-wordpress}
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
EOF

echo "=== Pulling images ==="
sudo docker-compose pull

echo "=== Starting services (no build) ==="
sudo docker-compose up -d

echo "=== Waiting 20 seconds ==="
sleep 20

echo "=== Status ==="
sudo docker-compose ps

echo ""
echo "=== Testing ==="
curl -I http://localhost 2>&1 | head -3 || echo "Still starting..."

echo ""
echo "✓ Done! Site should be at: http://$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || echo '18.130.255.19')"



