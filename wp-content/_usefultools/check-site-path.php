<?php
/**
 * Check and fix site path routing issues.
 * 
 * Usage: wp eval-file wp-content/_usefultools/check-site-path.php [path]
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;
$check_path = isset($argv[1]) ? $argv[1] : '/p1c2g2/';
$check_path = NestedTree\normalize_path($check_path);

echo "🔍 Checking path: {$check_path}\n";
echo "================================\n\n";

// Check wp_blogs
$blog = $wpdb->get_row($wpdb->prepare(
    "SELECT blog_id, path, domain FROM {$wpdb->blogs} WHERE path=%s",
    $check_path
), ARRAY_A);

if ($blog) {
    echo "✅ Found in wp_blogs:\n";
    echo "   blog_id: {$blog['blog_id']}\n";
    echo "   path: {$blog['path']}\n";
    echo "   domain: {$blog['domain']}\n\n";
} else {
    echo "❌ NOT found in wp_blogs\n\n";
}

// Check nested_sites table
$table = $wpdb->base_prefix . 'ideai_nested_sites';
$nested = $wpdb->get_row($wpdb->prepare(
    "SELECT blog_id, path FROM {$table} WHERE network_id=%d AND path=%s",
    $network_id,
    $check_path
), ARRAY_A);

if ($nested) {
    echo "✅ Found in nested_sites:\n";
    echo "   blog_id: {$nested['blog_id']}\n";
    echo "   path: {$nested['path']}\n\n";
} else {
    echo "❌ NOT found in nested_sites\n\n";
}

// Try to resolve using routing
$resolved = NestedTree\resolve_blog_for_request_path($check_path, $network_id);
if ($resolved) {
    echo "✅ Routing resolves to:\n";
    echo "   blog_id: {$resolved['blog_id']}\n";
    echo "   path: {$resolved['path']}\n\n";
} else {
    echo "❌ Routing does NOT resolve this path\n\n";
}

// Check for similar paths
echo "🔍 Searching for paths containing 'p1c2':\n";
$similar = $wpdb->get_results($wpdb->prepare(
    "SELECT blog_id, path FROM {$wpdb->blogs} WHERE path LIKE %s ORDER BY path",
    '%p1c2%'
), ARRAY_A);

if ($similar) {
    foreach ($similar as $row) {
        echo "   blog_id={$row['blog_id']}, path={$row['path']}\n";
    }
} else {
    echo "   No paths found\n";
}

echo "\n";

// Check what the correct nested path should be
$expected_path = '/parent1/child2/grandchild2/';
echo "🔍 Expected nested path: {$expected_path}\n";
$expected_blog = $wpdb->get_row($wpdb->prepare(
    "SELECT blog_id, path FROM {$wpdb->blogs} WHERE path=%s",
    $expected_path
), ARRAY_A);

if ($expected_blog) {
    echo "✅ Found expected path in wp_blogs:\n";
    echo "   blog_id: {$expected_blog['blog_id']}\n";
    echo "   path: {$expected_blog['path']}\n";
} else {
    echo "❌ Expected path NOT found\n";
}


