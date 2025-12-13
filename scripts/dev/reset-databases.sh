#!/bin/bash
# Reset all WordPress databases to clean state
# Usage: ./scripts/dev/reset-databases.sh

set -e

echo "🔄 Resetting WordPress databases..."

# Stop containers
echo "Stopping containers..."
docker-compose -f docker-compose.flexible.yml stop wordpress1 wordpress2 wordpress3 db1 db2 db3 2>/dev/null || true

# Remove volumes (this deletes all data)
echo "Removing database volumes..."
docker-compose -f docker-compose.flexible.yml down -v 2>/dev/null || true

# Remove specific volumes
docker volume rm ideai-dockerwp-001_db1_data 2>/dev/null || true
docker volume rm ideai-dockerwp-001_db2_data 2>/dev/null || true
docker volume rm ideai-dockerwp-001_db3_data 2>/dev/null || true

# Start containers fresh
echo "Starting containers..."
docker-compose -f docker-compose.flexible.yml up -d db1 db2 db3
sleep 5
docker-compose -f docker-compose.flexible.yml up -d wordpress1 wordpress2 wordpress3

echo "✅ Databases reset complete!"
echo "Waiting for WordPress to initialize..."
sleep 10

echo "✅ Ready for fresh setup!"

