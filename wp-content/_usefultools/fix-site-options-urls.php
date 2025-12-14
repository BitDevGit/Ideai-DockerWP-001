<?php
/**
 * Fix siteurl and home options to use nested paths
 * Run: wp eval-file fix-site-options-urls.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;
$nested_table = NestedTree\table_name();
$sites = $wpdb->get_results($wpdb->prepare(
    'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
    1
), ARRAY_A);

echo "Fixing siteurl and home options to use nested paths...\n\n";

$fixed = 0;
$already_correct = 0;

foreach ($sites as $site) {
    $blog_id = $site['blog_id'];
    $nested_path = $site['path'];
    
    switch_to_blog($blog_id);
    
    // Get site domain
    $site_obj = get_site($blog_id);
    $domain = $site_obj->domain;
    $scheme = is_ssl() ? 'https' : 'http';
    
    // Build correct URLs
    $correct_siteurl = $scheme . '://' . $domain . $nested_path;
    $correct_home = $scheme . '://' . $domain . $nested_path;
    
    // Get current options
    $current_siteurl = get_option('siteurl');
    $current_home = get_option('home');
    
    $needs_fix = false;
    
    if ($current_siteurl !== $correct_siteurl) {
        update_option('siteurl', $correct_siteurl);
        echo "✅ Blog {$blog_id}: Fixed siteurl: '{$current_siteurl}' -> '{$correct_siteurl}'\n";
        $needs_fix = true;
    }
    
    if ($current_home !== $correct_home) {
        update_option('home', $correct_home);
        echo "✅ Blog {$blog_id}: Fixed home: '{$current_home}' -> '{$correct_home}'\n";
        $needs_fix = true;
    }
    
    if ($needs_fix) {
        $fixed++;
    } else {
        $already_correct++;
    }
    
    restore_current_blog();
}

echo "\n";
echo "Summary:\n";
echo "- Fixed: {$fixed} sites\n";
echo "- Already correct: {$already_correct} sites\n";
echo "- Total: " . count($sites) . " sites\n";

if ($fixed > 0) {
    echo "\n✅ All site URLs should now use correct nested paths!\n";
}

