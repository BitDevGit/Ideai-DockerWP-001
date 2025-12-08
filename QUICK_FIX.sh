#!/bin/bash
# Quick fix for hanging deployment - run this on the server

cd /opt/wordpress-multisite && \
sudo docker-compose down && \
sudo docker-compose -f docker-compose.yml up -d --no-build 2>&1 || \
(sudo docker-compose pull && sudo docker-compose up -d) && \
sleep 15 && \
sudo docker-compose ps && \
echo "✓ Done! Check: http://$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4)"

