<?php
/**
 * Fix wp-admin URLs by updating wp_blogs.path to match nested paths
 * Run: wp eval-file fix-wp-admin-paths.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;
$nested_table = NestedTree\table_name();
$sites = $wpdb->get_results($wpdb->prepare(
    'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
    1
), ARRAY_A);

echo "Fixing wp_blogs.path to match nested paths...\n\n";

$fixed = 0;
$already_correct = 0;

foreach ($sites as $site) {
    $blog_id = $site['blog_id'];
    $nested_path = $site['path'];
    
    // Get current wp_blogs path
    $current_path = $wpdb->get_var($wpdb->prepare(
        'SELECT path FROM wp_blogs WHERE blog_id=%d',
        $blog_id
    ));
    
    if ($current_path !== $nested_path) {
        // Update wp_blogs.path to match nested path
        $wpdb->update(
            'wp_blogs',
            array('path' => $nested_path),
            array('blog_id' => $blog_id),
            array('%s'),
            array('%d')
        );
        
        echo "✅ Fixed blog_id={$blog_id}: '{$current_path}' -> '{$nested_path}'\n";
        $fixed++;
    } else {
        $already_correct++;
    }
}

echo "\n";
echo "Summary:\n";
echo "- Fixed: {$fixed} sites\n";
echo "- Already correct: {$already_correct} sites\n";
echo "- Total: " . count($sites) . " sites\n";

if ($fixed > 0) {
    echo "\n✅ All wp-admin URLs should now use correct nested paths!\n";
    echo "You may need to clear cache and re-login to admin areas.\n";
}

