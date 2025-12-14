<?php
/**
 * Update all existing sites' homepages to show level information.
 * 
 * Usage: wp eval-file wp-content/_usefultools/update-all-homepages.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-homepage.php';

use Ideai\Wp\Platform\NestedTree;

if (!is_multisite()) {
	echo "❌ This script requires multisite.\n";
	exit(1);
}

$network_id = 1;
$sites = get_sites(array('network_id' => $network_id, 'number' => 1000));

echo "🔄 Updating homepages for " . count($sites) . " sites...\n\n";

$updated = 0;
$skipped = 0;

foreach ($sites as $site) {
	$blog_id = (int) $site->blog_id;
	
	echo "[{$blog_id}] ";
	
	// Get site path
	$site_obj = get_blog_details($blog_id);
	$path = $site_obj->path ?? '/';
	$depth = NestedTree\get_site_depth($path);
	
	echo "Path: {$path}, Depth: {$depth} ... ";
	
	// Setup homepage
	NestedTree\setup_homepage_with_level($blog_id);
	
	echo "✅ Updated\n";
	$updated++;
}

echo "\n✅ Updated {$updated} homepages!\n";


