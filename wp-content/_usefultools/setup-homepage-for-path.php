<?php
/**
 * Setup homepage for a site by path.
 * 
 * Usage: wp eval-file wp-content/_usefultools/setup-homepage-for-path.php [path]
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-homepage.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$target_path = isset($argv[1]) ? $argv[1] : '/parent1/child2/grandchild2/';
$target_path = NestedTree\normalize_path($target_path);

echo "🏠 Setting up homepage for: {$target_path}\n";
echo "==========================================\n\n";

// Find the blog_id by path
$blog = $wpdb->get_row($wpdb->prepare(
    "SELECT blog_id, path FROM {$wpdb->blogs} WHERE path=%s",
    $target_path
), ARRAY_A);

if (!$blog) {
    echo "❌ Site not found for path: {$target_path}\n";
    exit(1);
}

$blog_id = (int) $blog['blog_id'];
echo "✅ Found site: blog_id={$blog_id}, path={$blog['path']}\n\n";

// Setup homepage
echo "Setting up homepage...\n";
NestedTree\setup_homepage_with_level($blog_id);

echo "✅ Homepage setup complete!\n";
echo "\n";
echo "🧪 Test: https://site3.localwp{$target_path}\n";


