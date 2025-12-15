<?php
/**
 * Fix Upload Directories for All Nested Sites
 * 
 * Creates upload directories for all nested sites that don't have them.
 * Run: wp eval-file wp-content/_usefultools/fix-all-uploads-directories.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/ideai.wp.plugin.platform.php';

use Ideai\Wp\Platform\NestedTree;

if (!is_multisite()) {
	die("This script only works in multisite mode.\n");
}

$network_id = get_current_network_id();

echo "🔧 Fixing upload directories for all nested sites...\n\n";

// Get all nested sites
global $wpdb;
$nested_table = NestedTree\table_name();
$sites = $wpdb->get_results($wpdb->prepare(
	'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
	$network_id
), ARRAY_A);

// Add root site
$root_site_id = get_main_site_id($network_id);
$sites[] = array('blog_id' => $root_site_id, 'path' => '/');

$fixed = 0;
$skipped = 0;

foreach ($sites as $site) {
	$blog_id = (int) $site['blog_id'];
	
	switch_to_blog($blog_id);
	
	// Get upload directory info
	$upload_dir = wp_upload_dir();
	$basedir = $upload_dir['basedir'];
	
	// Check if base directory exists
	if (!file_exists($basedir)) {
		echo "  Creating upload directory for blog_id {$blog_id} ({$site['path']})...\n";
		wp_mkdir_p($basedir);
		$fixed++;
	} else {
		$skipped++;
	}
	
	// Ensure year/month directories exist
	$year = date('Y');
	$month = date('m');
	$year_dir = $basedir . '/' . $year;
	$month_dir = $year_dir . '/' . $month;
	
	if (!file_exists($year_dir)) {
		wp_mkdir_p($year_dir);
		echo "    Created year directory: {$year_dir}\n";
		$fixed++;
	}
	
	if (!file_exists($month_dir)) {
		wp_mkdir_p($month_dir);
		echo "    Created month directory: {$month_dir}\n";
		$fixed++;
	}
	
	// Set proper permissions
	if (file_exists($basedir)) {
		chmod($basedir, 0755);
		if (file_exists($year_dir)) {
			chmod($year_dir, 0755);
		}
		if (file_exists($month_dir)) {
			chmod($month_dir, 0755);
		}
	}
	
	restore_current_blog();
}

echo "\n✅ Upload directories fixed!\n";
echo "  - Fixed: {$fixed} directories\n";
echo "  - Already existed: {$skipped} sites\n";

