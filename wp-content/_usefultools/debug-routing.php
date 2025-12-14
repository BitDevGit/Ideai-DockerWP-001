<?php
/**
 * Debug routing to see what's happening.
 * 
 * Usage: wp eval-file wp-content/_usefultools/debug-routing.php [path]
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;
$test_path = isset($argv[1]) ? $argv[1] : '/parent1/child2/grandchild2/';
$test_path = NestedTree\normalize_path($test_path);

echo "🔍 Debugging routing for: {$test_path}\n";
echo "=====================================\n\n";

// 1. Check all mappings
echo "1. All nested site mappings:\n";
$table = $wpdb->base_prefix . 'ideai_nested_sites';
$mappings = $wpdb->get_results($wpdb->prepare(
    "SELECT blog_id, path FROM {$table} WHERE network_id=%d ORDER BY LENGTH(path) DESC",
    $network_id
), ARRAY_A);

if ($mappings) {
    foreach ($mappings as $m) {
        $matches = (strpos($test_path, $m['path']) === 0) ? '✅ MATCHES' : '';
        echo "   blog_id={$m['blog_id']}, path={$m['path']} {$matches}\n";
    }
} else {
    echo "   ❌ No mappings found\n";
}
echo "\n";

// 2. Test resolution
echo "2. Testing resolve_blog_for_request_path:\n";
$resolved = NestedTree\resolve_blog_for_request_path($test_path, $network_id);
if ($resolved) {
    echo "   ✅ Resolved to: blog_id={$resolved['blog_id']}, path={$resolved['path']}\n";
} else {
    echo "   ❌ NOT resolved\n";
}
echo "\n";

// 3. Check what WordPress core would resolve
echo "3. What WordPress core would resolve:\n";
$core_site = get_site_by_path('site3.localwp', $test_path);
if ($core_site) {
    echo "   blog_id={$core_site->blog_id}, path={$core_site->path}\n";
} else {
    echo "   ❌ Core couldn't resolve\n";
}


