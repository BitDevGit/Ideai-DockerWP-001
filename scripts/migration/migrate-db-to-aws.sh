#!/bin/bash
# Database Migration Script for AWS Deployment
# Handles domain rewrite and serialized URL rewrite

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

OLD_DOMAIN="${1:-localhost}"
NEW_DOMAIN="${2:-13.40.170.117}"
DB_NAME="${DB_NAME:-wordpress}"
DB_USER="${DB_USER:-wordpress}"
DB_PASSWORD="${DB_PASSWORD}"
DB_HOST="${DB_HOST:-db:3306}"

echo -e "${BLUE}WordPress Database Migration${NC}"
echo "  From: $OLD_DOMAIN"
echo "  To: $NEW_DOMAIN"
echo ""

# Check if wp-cli is available
if ! command -v wp &> /dev/null; then
    echo -e "${YELLOW}wp-cli not found, using direct MySQL...${NC}"
    USE_WPCLI=0
else
    USE_WPCLI=1
fi

if [ "$USE_WPCLI" = "1" ]; then
    echo -e "${YELLOW}Using wp-cli for migration...${NC}"
    
    # Search and replace using wp-cli
    wp search-replace "$OLD_DOMAIN" "$NEW_DOMAIN" --allow-root --all-tables || {
        echo -e "${RED}wp-cli search-replace failed${NC}"
        exit 1
    }
    
    # Handle serialized data
    wp search-replace "s:$(echo -n "$OLD_DOMAIN" | wc -c):\"$OLD_DOMAIN\"" "s:$(echo -n "$NEW_DOMAIN" | wc -c):\"$NEW_DOMAIN\"" --allow-root --all-tables || {
        echo -e "${YELLOW}Serialized replace may have failed (this is often OK)${NC}"
    }
    
    echo -e "${GREEN}✓ Migration complete using wp-cli${NC}"
else
    echo -e "${YELLOW}Using direct MySQL for migration...${NC}"
    
    # Create SQL script
    SQL_FILE=$(mktemp)
    cat > "$SQL_FILE" << EOF
-- WordPress Database Migration
-- Replace domain in all tables

USE ${DB_NAME};

-- Update siteurl and home options
UPDATE wp_options SET option_value = REPLACE(option_value, '${OLD_DOMAIN}', '${NEW_DOMAIN}') WHERE option_name IN ('siteurl', 'home');

-- Update posts content
UPDATE wp_posts SET post_content = REPLACE(post_content, '${OLD_DOMAIN}', '${NEW_DOMAIN}');
UPDATE wp_posts SET guid = REPLACE(guid, '${OLD_DOMAIN}', '${NEW_DOMAIN}');

-- Update postmeta
UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, '${OLD_DOMAIN}', '${NEW_DOMAIN}');

-- Update comments
UPDATE wp_comments SET comment_content = REPLACE(comment_content, '${OLD_DOMAIN}', '${NEW_DOMAIN}');
UPDATE wp_comments SET comment_author_url = REPLACE(comment_author_url, '${OLD_DOMAIN}', '${NEW_DOMAIN}');

-- Update usermeta
UPDATE wp_usermeta SET meta_value = REPLACE(meta_value, '${OLD_DOMAIN}', '${NEW_DOMAIN}');

-- Handle serialized data (basic approach)
-- Note: This is a simplified approach. For complex serialized data, use wp-cli or specialized tools
UPDATE wp_options SET option_value = REPLACE(option_value, 's:$(echo -n "$OLD_DOMAIN" | wc -c):\"${OLD_DOMAIN}\"', 's:$(echo -n "$NEW_DOMAIN" | wc -c):\"${NEW_DOMAIN}\"') WHERE option_value LIKE '%${OLD_DOMAIN}%';

-- For multisite, update site tables
UPDATE wp_blogs SET domain = '${NEW_DOMAIN}' WHERE domain = '${OLD_DOMAIN}';
UPDATE wp_site SET domain = '${NEW_DOMAIN}' WHERE domain = '${OLD_DOMAIN}';

SELECT 'Migration complete!' AS status;
EOF

    # Execute SQL
    docker-compose exec -T db mysql -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$SQL_FILE" || {
        echo -e "${RED}MySQL migration failed${NC}"
        rm -f "$SQL_FILE"
        exit 1
    }
    
    rm -f "$SQL_FILE"
    echo -e "${GREEN}✓ Migration complete using MySQL${NC}"
fi

echo ""
echo -e "${GREEN}✓ Database migration complete!${NC}"
echo ""
echo "Updated:"
echo "  - Site URL: $OLD_DOMAIN → $NEW_DOMAIN"
echo "  - Home URL: $OLD_DOMAIN → $NEW_DOMAIN"
echo "  - Post content URLs"
echo "  - Serialized data (where possible)"
echo ""
echo -e "${YELLOW}Note:${NC} You may need to:"
echo "  1. Clear any caching"
echo "  2. Update .htaccess if using permalinks"
echo "  3. Verify serialized data manually if issues occur"

