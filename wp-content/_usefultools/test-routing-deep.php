<?php
/**
 * Test routing for deeply nested paths.
 * 
 * Usage: wp eval-file wp-content/_usefultools/test-routing-deep.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;
$test_paths = array(
	'/parent1/',
	'/parent1/child2/',
	'/parent1/child2/grandchild2/',
);

echo "🧪 Testing routing for nested paths\n";
echo "====================================\n\n";

foreach ($test_paths as $test_path) {
	$normalized = NestedTree\normalize_path($test_path);
	echo "Testing: {$test_path}\n";
	
	// Test resolution
	$resolved = NestedTree\resolve_blog_for_request_path($normalized, $network_id);
	if ($resolved) {
		echo "  ✅ Resolved: blog_id={$resolved['blog_id']}, path={$resolved['path']}\n";
		
		// Get site details
		$site = get_site($resolved['blog_id']);
		if ($site) {
			echo "  Site name: " . get_blog_option($resolved['blog_id'], 'blogname', 'N/A') . "\n";
			echo "  Site path in DB: {$site->path}\n";
		}
	} else {
		echo "  ❌ NOT resolved\n";
	}
	echo "\n";
}

// Check all mappings
echo "All registered mappings:\n";
$table = $wpdb->base_prefix . 'ideai_nested_sites';
$mappings = $wpdb->get_results($wpdb->prepare(
	"SELECT blog_id, path FROM {$table} WHERE network_id=%d ORDER BY LENGTH(path) DESC",
	$network_id
), ARRAY_A);

foreach ($mappings as $m) {
	echo "  blog_id={$m['blog_id']}, path={$m['path']}\n";
}


