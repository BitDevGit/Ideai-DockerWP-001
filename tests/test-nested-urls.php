<?php
/**
 * Test nested URL generation and rewriting
 * Run: docker-compose -f docker-compose.flexible.yml exec wordpress3 php tests/test-nested-urls.php
 */

if (!defined('ABSPATH')) {
	require_once __DIR__ . '/../wp-load.php';
}

if (!is_multisite()) {
	echo "❌ Not a multisite installation.\n";
	exit(1);
}

echo "🧪 Testing Nested URL Generation\n";
echo str_repeat("=", 60) . "\n\n";

// Get all nested sites
global $wpdb;
$sites = $wpdb->get_results("
	SELECT b.blog_id, b.domain, b.path, n.path as nested_path
	FROM {$wpdb->blogs} b
	LEFT JOIN {$wpdb->prefix}ideai_nested_tree_paths n ON b.blog_id = n.blog_id AND n.network_id = 1
	WHERE b.site_id = 1 AND b.blog_id > 1
	ORDER BY b.blog_id
");

if (empty($sites)) {
	echo "⚠️  No nested sites found. Create some test sites first.\n";
	exit(0);
}

$passed = 0;
$failed = 0;

foreach ($sites as $site) {
	$blog_id = (int) $site->blog_id;
	$db_path = $site->path;
	$nested_path = $site->nested_path ?: $db_path;
	
	echo "Blog ID {$blog_id}:\n";
	echo "  DB Path: {$db_path}\n";
	echo "  Nested Path: " . ($nested_path ?: 'none') . "\n";
	
	// Switch to site
	switch_to_blog($blog_id);
	
	// Test URL generation
	$home_url = home_url('/');
	$admin_url = admin_url();
	$site_url = site_url('/');
	
	echo "  Generated URLs:\n";
	echo "    home_url(): {$home_url}\n";
	echo "    admin_url(): {$admin_url}\n";
	echo "    site_url(): {$site_url}\n";
	
	// Check if URLs contain the nested path
	$expected_path = rtrim($nested_path, '/');
	$urls_correct = true;
	
	if (strpos($home_url, $expected_path) === false && $expected_path !== '/') {
		echo "    ❌ home_url() missing nested path\n";
		$urls_correct = false;
	}
	if (strpos($admin_url, $expected_path) === false && $expected_path !== '/') {
		echo "    ❌ admin_url() missing nested path\n";
		$urls_correct = false;
	}
	if (strpos($site_url, $expected_path) === false && $expected_path !== '/') {
		echo "    ❌ site_url() missing nested path\n";
		$urls_correct = false;
	}
	
	// Check for -- in URLs (should never appear)
	if (strpos($home_url, '--') !== false || strpos($admin_url, '--') !== false) {
		echo "    ❌ URLs contain -- (should use /)\n";
		$urls_correct = false;
	}
	
	if ($urls_correct) {
		echo "    ✅ All URLs correct\n";
		$passed++;
	} else {
		$failed++;
	}
	
	restore_current_blog();
	echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";

exit($failed > 0 ? 1 : 0);

