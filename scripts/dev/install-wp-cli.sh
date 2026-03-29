#!/bin/bash
# Install wp-cli and mysql client in WordPress containers
# Usage: ./scripts/dev/install-wp-cli.sh [wordpress1|wordpress2|wordpress3|all]

set -e

CONTAINER=${1:-wordpress3}

if [ "$CONTAINER" = "all" ]; then
  echo "📦 Installing wp-cli in all WordPress containers..."
  echo "=================================================="
  echo ""
  for c in wordpress1 wordpress2 wordpress3; do
    echo "Installing in $c..."
    docker-compose -f docker-compose.flexible.yml exec -T $c bash -c "
      apt-get update -qq && \
      apt-get install -y -qq default-mysql-client >/dev/null 2>&1 && \
      cd /tmp && \
      curl -s -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
      chmod +x wp-cli.phar && \
      mv wp-cli.phar /usr/local/bin/wp 2>/dev/null || true
    " 2>&1 | grep -v "Warning" || true
  done
  echo ""
  echo "✅ wp-cli installation attempted for all containers"
else
  echo "📦 Installing wp-cli in WordPress container ($CONTAINER)..."
  echo "========================================================"
  echo ""

  # Install mysql client and wp-cli
  docker-compose -f docker-compose.flexible.yml exec -T $CONTAINER bash -c "
    apt-get update -qq && \
    apt-get install -y -qq default-mysql-client >/dev/null 2>&1 && \
    cd /tmp && \
    curl -s -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && \
    mv wp-cli.phar /usr/local/bin/wp 2>/dev/null || true && \
    wp --allow-root --info | head -3
  " 2>&1 | tail -10

  echo ""
  echo "✅ wp-cli installation attempted"
  echo "   Test: docker-compose -f docker-compose.flexible.yml exec $CONTAINER wp --allow-root --info"
fi



