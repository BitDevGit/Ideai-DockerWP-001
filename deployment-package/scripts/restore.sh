#!/bin/bash
set -e

# WordPress Multisite Restore Script
# Restores database and files from backup

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

if [ -z "$1" ]; then
    echo -e "${RED}Usage: $0 <backup-timestamp>${NC}"
    echo -e "${YELLOW}Example: $0 20240101_120000${NC}"
    exit 1
fi

BACKUP_TIMESTAMP=$1
BACKUP_DIR="./backups"
BACKUP_NAME="wp-backup-$BACKUP_TIMESTAMP"

echo -e "${YELLOW}WARNING: This will restore from backup and may overwrite existing data!${NC}"
read -p "Are you sure you want to continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo -e "${RED}Restore cancelled.${NC}"
    exit 0
fi

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Check if backup files exist
if [ ! -f "$BACKUP_DIR/$BACKUP_NAME-db.sql.gz" ]; then
    echo -e "${RED}Database backup not found: $BACKUP_DIR/$BACKUP_NAME-db.sql.gz${NC}"
    exit 1
fi

if [ ! -f "$BACKUP_DIR/$BACKUP_NAME-files.tar.gz" ]; then
    echo -e "${RED}Files backup not found: $BACKUP_DIR/$BACKUP_NAME-files.tar.gz${NC}"
    exit 1
fi

# Restore database
echo -e "${YELLOW}Restoring database...${NC}"
gunzip -c "$BACKUP_DIR/$BACKUP_NAME-db.sql.gz" | \
    docker-compose exec -T db mysql \
    -u"${DB_USER:-wordpress}" \
    -p"${DB_PASSWORD}" \
    "${DB_NAME:-wordpress}"

echo -e "${GREEN}Database restored.${NC}"

# Restore files
echo -e "${YELLOW}Restoring WordPress files...${NC}"
docker-compose exec -T wordpress sh -c "cd /var/www/html && rm -rf wp-content/*"
gunzip -c "$BACKUP_DIR/$BACKUP_NAME-files.tar.gz" | \
    docker-compose exec -T wordpress tar xzf - -C /var/www/html

# Fix permissions
docker-compose exec -T wordpress chown -R www-data:www-data /var/www/html

echo -e "${GREEN}Files restored.${NC}"
echo -e "${GREEN}Restore completed successfully!${NC}"


