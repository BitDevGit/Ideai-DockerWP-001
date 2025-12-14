<?php
/**
 * Quick test script to verify nested paths are stored correctly
 * Run: docker-compose -f docker-compose.flexible.yml exec wordpress3 wp eval-file tests/test-nested-paths.php
 */

if (!defined('ABSPATH')) {
	require_once __DIR__ . '/../wp-load.php';
}

if (!is_multisite()) {
	echo "Not a multisite installation.\n";
	exit(1);
}

global $wpdb;

echo "Checking nested site paths in wp_blogs table:\n\n";

$sites = $wpdb->get_results("SELECT blog_id, domain, path FROM {$wpdb->blogs} WHERE site_id = 1 ORDER BY blog_id");

foreach ($sites as $site) {
	$path = $site->path;
	$has_dash = strpos($path, '--') !== false;
	$status = $has_dash ? '❌ HAS --' : '✅ OK';
	
	echo sprintf("Blog ID %d: %s%s %s\n", 
		$site->blog_id,
		$site->domain,
		$path,
		$status
	);
	
	if ($has_dash) {
		echo "  ⚠️  This site has -- in path and needs fixing!\n";
	}
}

echo "\nDone.\n";


