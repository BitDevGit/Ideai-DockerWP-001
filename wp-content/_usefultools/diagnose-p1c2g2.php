<?php
/**
 * Diagnose the /p1c2g2/ routing issue.
 * 
 * Usage: wp eval-file wp-content/_usefultools/diagnose-p1c2g2.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;
$check_path = '/p1c2g2/';
$normalized = NestedTree\normalize_path($check_path);

echo "🔍 Diagnosing: {$check_path}\n";
echo "================================\n\n";

// 1. Check wp_blogs
echo "1. Checking wp_blogs table:\n";
$blog = $wpdb->get_row($wpdb->prepare(
    "SELECT blog_id, path, domain FROM {$wpdb->blogs} WHERE path=%s",
    $check_path
), ARRAY_A);

if ($blog) {
    echo "   ✅ Found: blog_id={$blog['blog_id']}, path={$blog['path']}, domain={$blog['domain']}\n\n";
    $blog_id = (int) $blog['blog_id'];
} else {
    echo "   ❌ NOT found in wp_blogs\n\n";
    
    // Check for similar paths
    echo "   Searching for paths containing 'p1c2g2':\n";
    $similar = $wpdb->get_results($wpdb->prepare(
        "SELECT blog_id, path FROM {$wpdb->blogs} WHERE path LIKE %s",
        '%p1c2g2%'
    ), ARRAY_A);
    
    if ($similar) {
        foreach ($similar as $row) {
            echo "      blog_id={$row['blog_id']}, path={$row['path']}\n";
        }
    } else {
        echo "      No paths found\n";
    }
    echo "\n";
    exit(1);
}

// 2. Check nested_sites mapping
echo "2. Checking ideai_nested_sites mapping:\n";
$table = $wpdb->base_prefix . 'ideai_nested_sites';
$nested = $wpdb->get_row($wpdb->prepare(
    "SELECT blog_id, path FROM {$table} WHERE network_id=%d AND blog_id=%d",
    $network_id,
    $blog_id
), ARRAY_A);

if ($nested) {
    echo "   ✅ Found mapping: path={$nested['path']}\n\n";
} else {
    echo "   ❌ NOT found in nested_sites table\n";
    echo "   This is why routing fails!\n\n";
}

// 3. Try routing resolution
echo "3. Testing routing resolution:\n";
$resolved = NestedTree\resolve_blog_for_request_path($normalized, $network_id);
if ($resolved) {
    echo "   ✅ Routing resolves to: blog_id={$resolved['blog_id']}, path={$resolved['path']}\n\n";
} else {
    echo "   ❌ Routing does NOT resolve this path\n\n";
}

// 4. Check what the correct nested path should be
echo "4. Expected nested path conversion:\n";
$slug = trim($check_path, '/');
if (preg_match('/^p(\d+)c(\d+)g(\d+)$/', $slug, $matches)) {
    $parent_num = $matches[1];
    $child_num = $matches[2];
    $grandchild_num = $matches[3];
    $expected_path = "/parent{$parent_num}/child{$child_num}/grandchild{$grandchild_num}/";
    $expected_path = NestedTree\normalize_path($expected_path);
    echo "   Pattern: p{$parent_num}c{$child_num}g{$grandchild_num}\n";
    echo "   Expected: {$expected_path}\n\n";
    
    // Check if expected path exists
    $expected_blog = $wpdb->get_row($wpdb->prepare(
        "SELECT blog_id, path FROM {$wpdb->blogs} WHERE path=%s",
        $expected_path
    ), ARRAY_A);
    
    if ($expected_blog) {
        echo "   ✅ Expected path EXISTS in wp_blogs: blog_id={$expected_blog['blog_id']}\n";
    } else {
        echo "   ❌ Expected path does NOT exist in wp_blogs\n";
        echo "   → Need to convert {$check_path} to {$expected_path}\n\n";
        
        // Offer to fix it
        echo "5. Fix suggestion:\n";
        echo "   Run: wp eval-file wp-content/_usefultools/fix-single-temp-slug.php {$blog_id}\n";
    }
} else {
    echo "   ⚠️  Path doesn't match expected pattern (p1c2g2)\n\n";
}

// 5. Check if site is accessible
echo "5. Site details:\n";
$site = get_site($blog_id);
if ($site) {
    echo "   blog_id: {$site->blog_id}\n";
    echo "   domain: {$site->domain}\n";
    echo "   path: {$site->path}\n";
    echo "   registered: {$site->registered}\n";
    echo "   last_updated: {$site->last_updated}\n";
} else {
    echo "   ❌ Could not get site object\n";
}


