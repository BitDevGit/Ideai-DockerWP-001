#!/bin/bash
set -e

WP_CONFIG="/var/www/html/wp-config.php"

if [ ! -f "$WP_CONFIG" ]; then
    echo "wp-config.php not found. Waiting for WordPress installation..."
    exit 0
fi

# Check if multisite is already configured
if grep -q "MULTISITE" "$WP_CONFIG"; then
    echo "Multisite is already configured."
    exit 0
fi

# Enable multisite in wp-config.php
if [ "$WORDPRESS_MULTISITE" = "1" ]; then
    echo "Configuring WordPress Multisite..."
    
    # Add multisite constants before "That's all, stop editing!"
    sed -i "/That's all, stop editing!/i\\
/* Multisite */\\
define( 'WP_ALLOW_MULTISITE', true );\\
" "$WP_CONFIG"
    
    # If subdomain install
    if [ "$WORDPRESS_SUBDOMAIN_INSTALL" = "1" ]; then
        sed -i "/WP_ALLOW_MULTISITE/a\\
define( 'SUBDOMAIN_INSTALL', true );\\
define( 'DOMAIN_CURRENT_SITE', '$WORDPRESS_DOMAIN_CURRENT_SITE' );\\
define( 'PATH_CURRENT_SITE', '/' );\\
define( 'SITE_ID_CURRENT_SITE', 1 );\\
define( 'BLOG_ID_CURRENT_SITE', 1 );\\
" "$WP_CONFIG"
    fi
    
    echo "Multisite configuration added to wp-config.php"
    echo "Please complete the network setup via WordPress admin."
fi



