# Quick Fix via Browser SSH

SSH from terminal is timing out. Use **Browser SSH** instead:

## Steps:

1. **Open Browser SSH**:
   https://lightsail.aws.amazon.com/ls/webapp/eu-west-2/instances/wordpress-multisite/connect

2. **Click "Connect using SSH"**

3. **Run this command**:
```bash
cd /opt/wordpress-multisite && sudo docker-compose down && bash /tmp/fix-on-server.sh
```

OR if the script isn't there, run this directly:

```bash
cd /opt/wordpress-multisite
sudo docker-compose down
sudo tee docker-compose.yml > /dev/null << 'EOF'
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
EOF

sudo docker-compose pull
sudo docker-compose up -d
sleep 20
sudo docker-compose ps
```

4. **Check site**: http://18.130.255.19

