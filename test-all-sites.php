<?php
/**
 * Test all nested sites on site3
 * Run: wp eval-file test-all-sites.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';
require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-homepage.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;
$nested_table = NestedTree\table_name();
$sites = $wpdb->get_results($wpdb->prepare(
    'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
    1
), ARRAY_A);

echo "Testing all " . count($sites) . " nested sites on site3.localwp:\n";
echo str_repeat("=", 80) . "\n\n";

$passed = 0;
$failed = 0;
$failed_sites = array();

foreach ($sites as $site) {
    $path = $site['path'];
    $blog_id = $site['blog_id'];
    $normalized = NestedTree\normalize_path($path);
    $resolved = NestedTree\resolve_blog_for_request_path($normalized, 1);
    
    if ($resolved && $resolved['blog_id'] == $blog_id && $resolved['path'] === $normalized) {
        switch_to_blog($blog_id);
        $name = get_option('blogname');
        $theme = get_option('stylesheet');
        $homepage = get_option('page_on_front');
        $homepage_exists = $homepage && get_post($homepage);
        
        if ($theme === 'test-cursor-theme' && $homepage_exists) {
            echo "✅ " . str_pad($path, 40) . " -> blog_id=" . str_pad($blog_id, 3) . " | " . $name . "\n";
            $passed++;
        } else {
            echo "⚠️  " . str_pad($path, 40) . " -> blog_id=" . str_pad($blog_id, 3) . " | theme=" . $theme . ", homepage=" . ($homepage_exists ? 'YES' : 'NO') . "\n";
            $failed++;
            $failed_sites[] = $path;
        }
        restore_current_blog();
    } else {
        echo "❌ " . str_pad($path, 40) . " -> blog_id=" . str_pad($blog_id, 3) . " | ROUTING FAILED\n";
        $failed++;
        $failed_sites[] = $path;
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY: " . $passed . " passed, " . $failed . " failed out of " . count($sites) . " total sites\n";

if ($failed > 0) {
    echo "\nFailed sites:\n";
    foreach ($failed_sites as $failed_path) {
        echo "  - " . $failed_path . "\n";
    }
} else {
    echo "\n🎉 ALL SITES PASSED!\n";
}

