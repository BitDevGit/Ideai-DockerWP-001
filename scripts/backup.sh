#!/bin/bash
set -e

# WordPress Multisite Backup Script
# Backs up database and files

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="wp-backup-$TIMESTAMP"

echo -e "${GREEN}Starting WordPress Multisite backup...${NC}"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Backup database
echo -e "${YELLOW}Backing up database...${NC}"
docker-compose exec -T db mysqldump \
    -u"${DB_USER:-wordpress}" \
    -p"${DB_PASSWORD}" \
    "${DB_NAME:-wordpress}" \
    > "$BACKUP_DIR/$BACKUP_NAME-db.sql"

# Compress database backup
gzip "$BACKUP_DIR/$BACKUP_NAME-db.sql"
echo -e "${GREEN}Database backup completed: $BACKUP_DIR/$BACKUP_NAME-db.sql.gz${NC}"

# Backup WordPress files
echo -e "${YELLOW}Backing up WordPress files...${NC}"
docker-compose exec -T wordpress tar czf - /var/www/html/wp-content \
    > "$BACKUP_DIR/$BACKUP_NAME-files.tar.gz"

echo -e "${GREEN}Files backup completed: $BACKUP_DIR/$BACKUP_NAME-files.tar.gz${NC}"

# Create backup manifest
cat > "$BACKUP_DIR/$BACKUP_NAME-manifest.txt" << EOF
WordPress Multisite Backup
Timestamp: $TIMESTAMP
Database: $BACKUP_NAME-db.sql.gz
Files: $BACKUP_NAME-files.tar.gz
EOF

echo -e "${GREEN}Backup completed successfully!${NC}"
echo -e "${GREEN}Backup location: $BACKUP_DIR/$BACKUP_NAME-*${NC}"

# Optional: Upload to S3
if [ -n "$AWS_S3_BACKUP_BUCKET" ]; then
    echo -e "${YELLOW}Uploading backup to S3...${NC}"
    aws s3 cp "$BACKUP_DIR/$BACKUP_NAME-db.sql.gz" \
        "s3://$AWS_S3_BACKUP_BUCKET/backups/"
    aws s3 cp "$BACKUP_DIR/$BACKUP_NAME-files.tar.gz" \
        "s3://$AWS_S3_BACKUP_BUCKET/backups/"
    echo -e "${GREEN}Backup uploaded to S3${NC}"
fi

