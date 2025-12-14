<?php
/**
 * Check and fix routing - run this via wp eval-file
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;
$test_path = '/parent1/child2/grandchild2/';
$normalized = NestedTree\normalize_path($test_path);

echo "🔍 CHECKING ROUTING ISSUE\n";
echo str_repeat("=", 70) . "\n\n";

// Check wp_blogs
echo "1. Checking wp_blogs for grandchild2:\n";
$blogs = $wpdb->get_results($wpdb->prepare(
    "SELECT blog_id, path FROM {$wpdb->blogs} WHERE path LIKE %s ORDER BY LENGTH(path) DESC",
    '%parent1%child2%grandchild2%'
), ARRAY_A);

if ($blogs) {
    foreach ($blogs as $blog) {
        echo "   blog_id={$blog['blog_id']}, path={$blog['path']}\n";
    }
} else {
    echo "   ❌ No sites found in wp_blogs\n";
}

// Check nested_sites
echo "\n2. Checking ideai_nested_sites for grandchild2:\n";
$nested_table = $wpdb->base_prefix . 'ideai_nested_sites';
$mappings = $wpdb->get_results($wpdb->prepare(
    "SELECT blog_id, path FROM {$nested_table} WHERE network_id=%d AND path LIKE %s ORDER BY LENGTH(path) DESC",
    $network_id,
    '%parent1%child2%grandchild2%'
), ARRAY_A);

if ($mappings) {
    foreach ($mappings as $m) {
        echo "   blog_id={$m['blog_id']}, path={$m['path']}\n";
    }
} else {
    echo "   ❌ No mappings found\n";
}

// Check exact match
echo "\n3. Checking for exact match: {$normalized}\n";
$exact = $wpdb->get_row($wpdb->prepare(
    "SELECT blog_id, path FROM {$nested_table} WHERE network_id=%d AND path=%s",
    $network_id,
    $normalized
), ARRAY_A);

if ($exact) {
    echo "   ✅ Found: blog_id={$exact['blog_id']}, path={$exact['path']}\n";
} else {
    echo "   ❌ NOT FOUND - This is the problem!\n";
    
    // Find parent sites to understand structure
    echo "\n4. Finding parent sites:\n";
    $parents = $wpdb->get_results($wpdb->prepare(
        "SELECT blog_id, path FROM {$nested_table} WHERE network_id=%d AND path LIKE %s ORDER BY LENGTH(path) ASC",
        $network_id,
        '%parent1%'
    ), ARRAY_A);
    
    foreach ($parents as $p) {
        echo "   blog_id={$p['blog_id']}, path={$p['path']}\n";
    }
    
    // Try to find blog_id that should have this path
    echo "\n5. Checking if blog exists with temp slug:\n";
    $temp_slugs = $wpdb->get_results($wpdb->prepare(
        "SELECT blog_id, path FROM {$wpdb->blogs} WHERE path LIKE %s",
        '%p1c2g2%'
    ), ARRAY_A);
    
    if ($temp_slugs) {
        foreach ($temp_slugs as $ts) {
            echo "   Found temp slug: blog_id={$ts['blog_id']}, path={$ts['path']}\n";
            echo "   🔧 FIX: Need to create mapping for blog_id {$ts['blog_id']} -> {$normalized}\n";
            
            // FIX IT!
            $result = NestedTree\upsert_blog_path((int) $ts['blog_id'], $normalized, $network_id);
            if ($result) {
                echo "   ✅ FIXED: Created mapping!\n";
            } else {
                echo "   ❌ FAILED: Could not create mapping\n";
            }
        }
    } else {
        echo "   No temp slug found\n";
    }
}

// Test resolution
echo "\n6. Testing resolution:\n";
$resolved = NestedTree\resolve_blog_for_request_path($normalized, $network_id);
if ($resolved) {
    echo "   ✅ Resolved: blog_id={$resolved['blog_id']}, path={$resolved['path']}\n";
} else {
    echo "   ❌ NOT resolved\n";
}

echo "\n✅ Check complete!\n";

