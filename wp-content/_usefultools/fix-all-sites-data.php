<?php
/**
 * Fix all sites to have correct titles, names, and homepages.
 * Each site is sovereign and should have its own identity.
 * 
 * Usage: wp eval-file wp-content/_usefultools/fix-all-sites-data.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';
require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-homepage.php';

use Ideai\Wp\Platform\NestedTree;

if (!is_multisite()) {
	echo "❌ This script requires multisite.\n";
	exit(1);
}

$network_id = 1;
$sites = get_sites(array('network_id' => $network_id, 'number' => 1000));

echo "🔧 Fixing all sites to be sovereign with correct data...\n";
echo "======================================================\n\n";

$fixed = 0;
$skipped = 0;

foreach ($sites as $site) {
	$blog_id = (int) $site->blog_id;
	switch_to_blog($blog_id);
	
	$path = $site->path ?? '/';
	$depth = NestedTree\get_site_depth($path);
	
	// Generate site name based on path
	$site_name = generate_site_name_from_path($path, $depth);
	$site_title = $site_name; // Title and name should match
	
	echo "[{$blog_id}] Path: {$path}, Depth: {$depth}\n";
	echo "   Name: {$site_name}\n";
	
	// Update site name (blogname option)
	update_option('blogname', $site_name);
	
	// Update site title if different
	$current_name = get_option('blogname');
	if ($current_name !== $site_name) {
		update_option('blogname', $site_name);
		echo "   ✅ Updated blogname\n";
	}
	
	// Setup homepage with level information
	NestedTree\setup_homepage_with_level($blog_id);
	echo "   ✅ Homepage setup\n";
	
	restore_current_blog();
	$fixed++;
	
	echo "\n";
}

echo "✅ Fixed {$fixed} sites!\n";
echo "\n";
echo "🧪 Test sites:\n";
echo "   Root: https://site3.localwp/\n";
echo "   Level 1: https://site3.localwp/parent1/\n";
echo "   Level 2: https://site3.localwp/parent1/child2/\n";
echo "   Level 3: https://site3.localwp/parent1/child2/grandchild2/\n";

/**
 * Generate a site name from its path.
 * 
 * @param string $path Site path
 * @param int    $depth Site depth
 * @return string Site name
 */
function generate_site_name_from_path($path, $depth) {
	if ($path === '/' || $depth === 0) {
		return 'Site 3: Subdirectory Multisite';
	}
	
	// Remove leading/trailing slashes and split
	$segments = array_filter(explode('/', trim($path, '/')));
	
	if (empty($segments)) {
		return 'Site 3: Subdirectory Multisite';
	}
	
	// Capitalize each segment and join
	$name_parts = array();
	foreach ($segments as $segment) {
		// Convert parent1 -> Parent 1, child2 -> Child 2, etc.
		if (preg_match('/^([a-z]+)(\d+)$/', $segment, $matches)) {
			$word = ucfirst($matches[1]);
			$num = $matches[2];
			$name_parts[] = "{$word} {$num}";
		} else {
			$name_parts[] = ucfirst($segment);
		}
	}
	
	$site_name = implode(' → ', $name_parts);
	
	// Add level indicator
	$level_label = $depth === 0 ? 'Root' : "Level {$depth}";
	
	return "{$site_name} ({$level_label})";
}


