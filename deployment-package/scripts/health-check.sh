#!/bin/bash
set -e

# Health Check Script for WordPress Multisite
# Returns 0 if healthy, 1 if unhealthy

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

HEALTHY=0

echo "Running health checks..."

# Check if containers are running
echo -n "Checking containers... "
if ! docker-compose ps | grep -q "Up"; then
    echo -e "${RED}FAILED${NC}"
    echo "Some containers are not running"
    HEALTHY=1
else
    echo -e "${GREEN}OK${NC}"
fi

# Check WordPress
echo -n "Checking WordPress... "
if docker-compose exec -T wordpress php -r "exit(file_exists('/var/www/html/wp-config.php') ? 0 : 1);" 2>/dev/null; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    HEALTHY=1
fi

# Check Database
echo -n "Checking Database... "
if docker-compose exec -T db mysqladmin ping -h localhost -u root -p"${DB_ROOT_PASSWORD:-changeme}" --silent 2>/dev/null; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    HEALTHY=1
fi

# Check Redis
echo -n "Checking Redis... "
if docker-compose exec -T redis redis-cli -a "${REDIS_PASSWORD:-changeme}" ping 2>/dev/null | grep -q "PONG"; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    HEALTHY=1
fi

# Check Nginx
echo -n "Checking Nginx... "
if docker-compose exec -T nginx nginx -t 2>/dev/null | grep -q "successful"; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    HEALTHY=1
fi

# Check HTTP response (if domain is configured)
if [ -n "$DOMAIN_CURRENT_SITE" ] && [ "$DOMAIN_CURRENT_SITE" != "localhost" ]; then
    echo -n "Checking HTTP response... "
    if curl -s -o /dev/null -w "%{http_code}" "https://${DOMAIN_CURRENT_SITE}" | grep -q "200\|301\|302"; then
        echo -e "${GREEN}OK${NC}"
    else
        echo -e "${YELLOW}WARNING${NC} (may be normal if SSL not configured)"
    fi
fi

if [ $HEALTHY -eq 0 ]; then
    echo -e "\n${GREEN}All health checks passed!${NC}"
    exit 0
else
    echo -e "\n${RED}Some health checks failed!${NC}"
    exit 1
fi


