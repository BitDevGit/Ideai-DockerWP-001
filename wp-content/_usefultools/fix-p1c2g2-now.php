<?php
/**
 * Quick fix for /p1c2g2/ routing issue.
 * Checks database and fixes if needed.
 * 
 * Usage: wp eval-file wp-content/_usefultools/fix-p1c2g2-now.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;
$temp_path = '/p1c2g2/';
$expected_path = '/parent1/child2/grandchild2/';

echo "🔧 Fixing /p1c2g2/ routing issue\n";
echo "==================================\n\n";

// 1. Check if temp path exists
echo "1. Checking for {$temp_path}...\n";
$blog = $wpdb->get_row($wpdb->prepare(
    "SELECT blog_id, path FROM {$wpdb->blogs} WHERE path=%s",
    $temp_path
), ARRAY_A);

if (!$blog) {
    echo "   ❌ Path not found in wp_blogs\n";
    echo "   Checking for expected path instead...\n";
    
    $expected_blog = $wpdb->get_row($wpdb->prepare(
        "SELECT blog_id, path FROM {$wpdb->blogs} WHERE path=%s",
        $expected_path
    ), ARRAY_A);
    
    if ($expected_blog) {
        echo "   ✅ Expected path {$expected_path} exists: blog_id={$expected_blog['blog_id']}\n";
        echo "   → Site is already fixed! Check nested_sites mapping...\n\n";
        
        // Check mapping
        $table = $wpdb->base_prefix . 'ideai_nested_sites';
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT blog_id, path FROM {$table} WHERE network_id=%d AND blog_id=%d",
            $network_id,
            $expected_blog['blog_id']
        ), ARRAY_A);
        
        if ($mapping) {
            echo "   ✅ Mapping exists: path={$mapping['path']}\n";
            echo "   → Routing should work. Try: https://site3.localwp{$expected_path}\n";
        } else {
            echo "   ❌ Mapping missing! Creating it...\n";
            NestedTree\upsert_blog_path($expected_blog['blog_id'], $expected_path, $network_id);
            echo "   ✅ Mapping created!\n";
        }
        exit(0);
    } else {
        echo "   ❌ Expected path also not found\n";
        echo "   → Site may not exist. Check if it was created.\n";
        exit(1);
    }
}

$blog_id = (int) $blog['blog_id'];
echo "   ✅ Found: blog_id={$blog_id}, path={$blog['path']}\n\n";

// 2. Check if expected path already exists
echo "2. Checking if expected path {$expected_path} exists...\n";
$exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->blogs} WHERE path=%s AND blog_id != %d",
    $expected_path,
    $blog_id
));

if ($exists > 0) {
    echo "   ⚠️  Expected path already exists for another site\n";
    echo "   → Cannot convert. Manual intervention needed.\n";
    exit(1);
}
echo "   ✅ Path is available\n\n";

// 3. Convert path
echo "3. Converting path...\n";
$updated = $wpdb->update(
    $wpdb->blogs,
    array('path' => $expected_path),
    array('blog_id' => $blog_id),
    array('%s'),
    array('%d')
);

if ($updated === false) {
    echo "   ❌ Failed to update wp_blogs\n";
    exit(1);
}
echo "   ✅ Updated wp_blogs.path: {$temp_path} → {$expected_path}\n\n";

// 4. Update mapping
echo "4. Updating nested_sites mapping...\n";
$ok = NestedTree\upsert_blog_path($blog_id, $expected_path, $network_id);
if (!$ok) {
    echo "   ❌ Failed to update mapping\n";
    exit(1);
}
echo "   ✅ Mapping updated\n\n";

// 5. Clear cache
echo "5. Clearing caches...\n";
if (function_exists('clean_blog_cache')) {
    clean_blog_cache($blog_id);
}
wp_cache_delete($blog_id, 'blog-details');
wp_cache_delete($blog_id . 'short', 'blog-details');
echo "   ✅ Cache cleared\n\n";

echo "✅ Successfully fixed!\n";
echo "\n";
echo "🧪 Test: https://site3.localwp{$expected_path}\n";
echo "   (Old path {$temp_path} should now redirect or 404)\n";


