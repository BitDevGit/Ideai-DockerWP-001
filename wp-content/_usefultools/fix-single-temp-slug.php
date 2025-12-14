<?php
/**
 * Fix a single site with temporary slug by converting to nested path.
 * 
 * Usage: wp eval-file wp-content/_usefultools/fix-single-temp-slug.php [blog_id]
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;

if (empty($argv[1])) {
    echo "Usage: wp eval-file wp-content/_usefultools/fix-single-temp-slug.php <blog_id>\n";
    exit(1);
}

$blog_id = (int) $argv[1];
$site = get_site($blog_id);

if (!$site) {
    echo "❌ Site with blog_id {$blog_id} not found.\n";
    exit(1);
}

$temp_path = $site->path;
echo "🔧 Fixing blog_id {$blog_id}\n";
echo "   Current path: {$temp_path}\n\n";

// Parse temporary slug
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
} else {
    echo "❌ Path '{$slug}' doesn't match expected pattern (p1c2 or p1c2g2)\n";
    exit(1);
}

$nested_path = NestedTree\normalize_path($nested_path);
echo "   Target path: {$nested_path}\n\n";

// Check if nested path already exists for a different blog
$exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->blogs} WHERE path=%s AND blog_id != %d",
    $nested_path,
    $blog_id
));

if ($exists > 0) {
    echo "⚠️  Nested path already exists for another site. Cannot convert.\n";
    exit(1);
}

// Update wp_blogs.path
echo "1. Updating wp_blogs.path...\n";
$updated = $wpdb->update(
    $wpdb->blogs,
    array('path' => $nested_path),
    array('blog_id' => $blog_id),
    array('%s'),
    array('%d')
);

if ($updated === false) {
    echo "   ❌ Failed to update wp_blogs\n";
    exit(1);
}
echo "   ✅ Updated wp_blogs.path\n\n";

// Update nested_sites mapping
echo "2. Updating nested_sites mapping...\n";
$ok = NestedTree\upsert_blog_path($blog_id, $nested_path, $network_id);
if (!$ok) {
    echo "   ❌ Failed to update nested_sites mapping\n";
    exit(1);
}
echo "   ✅ Updated nested_sites mapping\n\n";

// Clear cache
echo "3. Clearing caches...\n";
if (function_exists('clean_blog_cache')) {
    clean_blog_cache($blog_id);
}
wp_cache_delete($blog_id, 'blog-details');
wp_cache_delete($blog_id . 'short', 'blog-details');
echo "   ✅ Cache cleared\n\n";

echo "✅ Successfully converted:\n";
echo "   {$temp_path} → {$nested_path}\n";
echo "\n";
echo "🧪 Test: https://site3.localwp{$nested_path}\n";


