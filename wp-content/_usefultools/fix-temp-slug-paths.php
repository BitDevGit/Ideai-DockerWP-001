<?php
/**
 * Fix sites that still have temporary slugs (like p1c2g2) instead of nested paths.
 * 
 * Usage: wp eval-file wp-content/_usefultools/fix-temp-slug-paths.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;
$table = $wpdb->base_prefix . 'ideai_nested_sites';

echo "🔧 Fixing temporary slug paths...\n";
echo "==================================\n\n";

// Find all blogs with temporary slug patterns (p1c2g2, p1c2, etc.)
// Pattern: /p[digit]c[digit]/ or /p[digit]c[digit]g[digit]/
$temp_slug_patterns = $wpdb->get_results(
    "SELECT blog_id, path FROM {$wpdb->blogs} 
     WHERE (path REGEXP '^/p[0-9]+c[0-9]+g[0-9]+/$' OR path REGEXP '^/p[0-9]+c[0-9]+/$')
     ORDER BY path",
    ARRAY_A
);

if (empty($temp_slug_patterns)) {
    echo "✅ No temporary slug paths found.\n";
    exit(0);
}

echo "Found " . count($temp_slug_patterns) . " sites with temporary slugs:\n\n";

$fixed = 0;
$skipped = 0;

foreach ($temp_slug_patterns as $row) {
    $blog_id = (int) $row['blog_id'];
    $temp_path = $row['path'];
    
    // Parse temporary slug patterns:
    // p1c2 -> parent1/child2/
    // p1c2g2 -> parent1/child2/grandchild2/
    $slug = trim($temp_path, '/');
    $nested_path = null;
    
    if (preg_match('/^p(\d+)c(\d+)g(\d+)$/', $slug, $matches)) {
        // 3 levels: parent/child/grandchild
        $parent_num = $matches[1];
        $child_num = $matches[2];
        $grandchild_num = $matches[3];
        $nested_path = "/parent{$parent_num}/child{$child_num}/grandchild{$grandchild_num}/";
    } elseif (preg_match('/^p(\d+)c(\d+)$/', $slug, $matches)) {
        // 2 levels: parent/child
        $parent_num = $matches[1];
        $child_num = $matches[2];
        $nested_path = "/parent{$parent_num}/child{$child_num}/";
    }
    
    if ($nested_path) {
        $nested_path = NestedTree\normalize_path($nested_path);
        
        echo "[{$blog_id}] {$temp_path} → {$nested_path}\n";
        
        // Check if nested path already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->blogs} WHERE path=%s AND blog_id != %d",
            $nested_path,
            $blog_id
        ));
        
        if ($exists > 0) {
            echo "  ⚠️  Nested path already exists, skipping\n\n";
            $skipped++;
            continue;
        }
        
        // Update wp_blogs.path
        $wpdb->update(
            $wpdb->blogs,
            array('path' => $nested_path),
            array('blog_id' => $blog_id),
            array('%s'),
            array('%d')
        );
        
        // Update nested_sites mapping
        NestedTree\upsert_blog_path($blog_id, $nested_path, $network_id);
        
        // Clear cache
        if (function_exists('clean_blog_cache')) {
            clean_blog_cache($blog_id);
        }
        wp_cache_delete($blog_id, 'blog-details');
        wp_cache_delete($blog_id . 'short', 'blog-details');
        
        echo "  ✅ Updated\n\n";
        $fixed++;
    } else {
        echo "[{$blog_id}] {$temp_path} - Unknown pattern, skipping\n\n";
        $skipped++;
    }
}

echo "✅ Fixed {$fixed} sites, skipped {$skipped} sites.\n";

