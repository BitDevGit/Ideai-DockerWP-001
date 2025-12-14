<?php
/**
 * Complete diagnostic for routing issue.
 * Tests everything step by step.
 * 
 * Usage: wp eval-file wp-content/_usefultools/diagnose-routing-complete.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;
$test_path = '/parent1/child2/grandchild2/';
$normalized = NestedTree\normalize_path($test_path);

echo "🔍 COMPLETE ROUTING DIAGNOSTIC\n";
echo "================================\n\n";

// STEP 1: Check Database State
echo "STEP 1: Database State\n";
echo "----------------------\n";

// Check wp_blogs for all parent1 related sites
echo "1.1 Sites in wp_blogs containing 'parent1':\n";
$blogs = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT blog_id, path, domain FROM {$wpdb->blogs} WHERE path LIKE %s ORDER BY LENGTH(path) DESC",
		'%parent1%'
	),
	ARRAY_A
);

if ($blogs) {
	foreach ($blogs as $blog) {
		$matches = ($blog['path'] === $normalized || strpos($normalized, $blog['path']) === 0) ? '✅' : '';
		echo "   blog_id={$blog['blog_id']}, path={$blog['path']}, domain={$blog['domain']} {$matches}\n";
	}
} else {
	echo "   ❌ No sites found\n";
}
echo "\n";

// Check nested_sites mappings
echo "1.2 Mappings in ideai_nested_sites:\n";
$table = $wpdb->base_prefix . 'ideai_nested_sites';
$mappings = $wpdb->get_results($wpdb->prepare(
	"SELECT blog_id, path FROM {$table} WHERE network_id=%d AND path LIKE %s ORDER BY LENGTH(path) DESC",
	$network_id,
	'%parent1%'
), ARRAY_A);

if ($mappings) {
	foreach ($mappings as $m) {
		$matches = ($m['path'] === $normalized || strpos($normalized, $m['path']) === 0) ? '✅' : '';
		echo "   blog_id={$m['blog_id']}, path={$m['path']} {$matches}\n";
	}
} else {
	echo "   ❌ No mappings found\n";
}
echo "\n";

// STEP 2: Test Routing Resolution
echo "STEP 2: Routing Resolution\n";
echo "--------------------------\n";

echo "2.1 Testing resolve_blog_for_request_path('{$normalized}'):\n";
$resolved = NestedTree\resolve_blog_for_request_path($normalized, $network_id);
if ($resolved) {
	echo "   ✅ Resolved: blog_id={$resolved['blog_id']}, path={$resolved['path']}\n";
	
	// Get site details
	$site = get_site($resolved['blog_id']);
	if ($site) {
		$site_name = get_blog_option($resolved['blog_id'], 'blogname', 'N/A');
		echo "   Site name: {$site_name}\n";
		echo "   Site path in DB: {$site->path}\n";
		
		if ($resolved['path'] !== $normalized) {
			echo "   ⚠️  WARNING: Resolved path doesn't match request path!\n";
			echo "      Requested: {$normalized}\n";
			echo "      Resolved:  {$resolved['path']}\n";
		}
	}
} else {
	echo "   ❌ NOT resolved\n";
}
echo "\n";

// STEP 3: Test WordPress Core Resolution
echo "STEP 3: WordPress Core Resolution\n";
echo "----------------------------------\n";

echo "3.1 Testing get_site_by_path() (WordPress core):\n";
$core_site = get_site_by_path('site3.localwp', $test_path);
if ($core_site) {
	echo "   blog_id={$core_site->blog_id}, path={$core_site->path}\n";
	$core_name = get_blog_option($core_site->blog_id, 'blogname', 'N/A');
	echo "   Site name: {$core_name}\n";
	
	if ($core_site->path !== $normalized) {
		echo "   ⚠️  WARNING: Core resolved to different path!\n";
		echo "      Requested: {$normalized}\n";
		echo "      Core resolved: {$core_site->path}\n";
	}
} else {
	echo "   ❌ Core couldn't resolve\n";
}
echo "\n";

// STEP 4: Test All Path Segments
echo "STEP 4: Testing Path Segments\n";
echo "-----------------------------\n";

$test_segments = array(
	'/parent1/',
	'/parent1/child2/',
	'/parent1/child2/grandchild2/',
);

foreach ($test_segments as $segment) {
	$seg_normalized = NestedTree\normalize_path($segment);
	echo "Testing: {$segment}\n";
	
	// Our resolution
	$our_resolved = NestedTree\resolve_blog_for_request_path($seg_normalized, $network_id);
	if ($our_resolved) {
		echo "  Our filter: blog_id={$our_resolved['blog_id']}, path={$our_resolved['path']}\n";
	} else {
		echo "  Our filter: ❌ NOT resolved\n";
	}
	
	// Core resolution
	$core_resolved = get_site_by_path('site3.localwp', $segment);
	if ($core_resolved) {
		echo "  Core: blog_id={$core_resolved->blog_id}, path={$core_resolved->path}\n";
	} else {
		echo "  Core: ❌ NOT resolved\n";
	}
	
	// Check if they match
	if ($our_resolved && $core_resolved) {
		if ($our_resolved['blog_id'] === $core_resolved->blog_id) {
			echo "  ✅ Match\n";
		} else {
			echo "  ⚠️  MISMATCH - Our filter and core disagree!\n";
		}
	}
	echo "\n";
}

// STEP 5: Check Site Names
echo "STEP 5: Site Names (blogname)\n";
echo "-----------------------------\n";

$all_sites = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT blog_id, path FROM {$wpdb->blogs} WHERE path LIKE %s ORDER BY LENGTH(path) DESC",
		'%parent1%'
	),
	ARRAY_A
);

foreach ($all_sites as $site_row) {
	$blog_id = (int) $site_row['blog_id'];
	switch_to_blog($blog_id);
	$site_name = get_option('blogname', 'N/A');
	restore_current_blog();
	
	echo "blog_id={$blog_id}, path={$site_row['path']}, name={$site_name}\n";
}

echo "\n";
echo "✅ Diagnostic complete!\n";


