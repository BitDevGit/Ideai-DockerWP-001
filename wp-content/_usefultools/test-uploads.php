<?php
/**
 * Test Uploads for Nested Sites
 * 
 * This script tests upload directory detection and path generation
 * for all nested sites.
 * 
 * Usage:
 *   docker-compose -f docker-compose.flexible.yml exec wordpress3 php /var/www/html/wp-content/_usefultools/test-uploads.php
 */

// Load WordPress
require_once dirname(__DIR__, 3) . '/wp-load.php';

if (!is_multisite()) {
    die("ERROR: This is not a multisite installation.\n");
}

echo "=== Uploads Test for Nested Sites ===\n\n";

// Get all sites
global $wpdb;
$sites = $wpdb->get_results("SELECT blog_id, path, domain FROM {$wpdb->blogs} ORDER BY blog_id");

echo "Found " . count($sites) . " sites\n\n";

foreach ($sites as $site) {
    $blog_id = (int) $site->blog_id;
    $path = $site->path;
    
    echo "--- Site: Blog ID {$blog_id}, Path: {$path} ---\n";
    
    // Switch to site
    switch_to_blog($blog_id);
    
    // Get upload directory
    $upload_dir = wp_upload_dir(null, false);
    
    echo "  Basedir: " . $upload_dir['basedir'] . "\n";
    echo "  Baseurl: " . $upload_dir['baseurl'] . "\n";
    echo "  Path: " . $upload_dir['path'] . "\n";
    echo "  URL: " . $upload_dir['url'] . "\n";
    
    // Check if directory exists
    $exists = file_exists($upload_dir['basedir']);
    $writable = is_writable($upload_dir['basedir']);
    
    echo "  Directory exists: " . ($exists ? "YES" : "NO") . "\n";
    echo "  Directory writable: " . ($writable ? "YES" : "NO") . "\n";
    
    // Check expected path
    $expected = WP_CONTENT_DIR . '/uploads/sites/' . $blog_id;
    $correct = (strpos($upload_dir['basedir'], $expected) !== false);
    echo "  Path is correct: " . ($correct ? "YES" : "NO") . "\n";
    
    if (!$correct) {
        echo "  ⚠️  WARNING: Expected path contains '{$expected}' but got '{$upload_dir['basedir']}'\n";
    }
    
    // Check for files
    if ($exists) {
        $files = glob($upload_dir['basedir'] . '/*');
        $file_count = count($files);
        echo "  Files in directory: {$file_count}\n";
        
        // Check year/month directories
        $year = date('Y');
        $month = date('m');
        $year_dir = $upload_dir['basedir'] . '/' . $year;
        $month_dir = $year_dir . '/' . $month;
        
        if (file_exists($year_dir)) {
            echo "  Year directory ({$year}) exists: YES\n";
            if (file_exists($month_dir)) {
                echo "  Month directory ({$year}/{$month}) exists: YES\n";
                $month_files = glob($month_dir . '/*');
                echo "  Files in month directory: " . count($month_files) . "\n";
            } else {
                echo "  Month directory ({$year}/{$month}) exists: NO\n";
            }
        } else {
            echo "  Year directory ({$year}) exists: NO\n";
        }
    }
    
    restore_current_blog();
    echo "\n";
}

echo "=== Test Complete ===\n";

