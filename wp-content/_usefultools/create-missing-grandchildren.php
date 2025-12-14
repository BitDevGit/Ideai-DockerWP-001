<?php
/**
 * Create missing grandchildren to ensure each child has exactly 3 grandchildren
 * 
 * Run: wp eval-file create-missing-grandchildren.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';
require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-homepage.php';

use Ideai\Wp\Platform\NestedTree;

if (!is_multisite()) {
    die("Error: This script requires multisite.\n");
}

$network_id = get_current_network_id();

global $wpdb;
$nested_table = NestedTree\table_name();

echo "🌳 Creating Missing Grandchildren\n";
echo str_repeat("=", 60) . "\n\n";

$created = 0;
$registered = 0;

foreach ([1, 2, 3] as $parent_num) {
    for ($child_num = 1; $child_num <= 3; $child_num++) {
        $child_name = "child{$child_num}";
        
        // Check existing grandchildren for this child
        $child_path = "/parent{$parent_num}/{$child_name}/";
        $existing_grandchildren = $wpdb->get_results($wpdb->prepare(
            "SELECT path FROM {$nested_table} WHERE network_id=%d AND path LIKE %s AND path != %s ORDER BY path ASC",
            $network_id,
            $child_path . '%',
            $child_path
        ), ARRAY_A);
        
        $existing_paths = array_column($existing_grandchildren, 'path');
        
        // Create missing grandchildren (should have grandchild1, grandchild2, grandchild3)
        for ($grandchild_num = 1; $grandchild_num <= 3; $grandchild_num++) {
            $grandchild_path = "/parent{$parent_num}/{$child_name}/grandchild{$grandchild_num}/";
            
            // Check if already in nested_sites table
            $exists_in_nested = in_array($grandchild_path, $existing_paths);
            
            if ($exists_in_nested) {
                echo "  ✓ {$grandchild_path} already exists\n";
                continue;
            }
            
            // Check if exists in wp_blogs but not registered
            $blog = $wpdb->get_row($wpdb->prepare(
                "SELECT blog_id FROM wp_blogs WHERE path=%s AND site_id=%d",
                $grandchild_path,
                $network_id
            ));
            
            if ($blog) {
                // Register it
                $wpdb->replace(
                    $nested_table,
                    array(
                        'blog_id' => $blog->blog_id,
                        'network_id' => $network_id,
                        'path' => $grandchild_path,
                    ),
                    array('%d', '%d', '%s')
                );
                
                switch_to_blog($blog->blog_id);
                update_option('blogname', "Parent {$parent_num} → " . ucfirst($child_name) . " → Grandchild {$grandchild_num} (Level 3)");
                NestedTree\setup_homepage_with_level($blog->blog_id);
                restore_current_blog();
                
                echo "  ✅ Registered existing {$grandchild_path} (blog_id: {$blog->blog_id})\n";
                $registered++;
                continue;
            }
            
            // Create new site
            $result = wp_insert_site(array(
                'domain' => get_network()->domain,
                'path' => $grandchild_path,
                'title' => "Parent {$parent_num} → " . ucfirst($child_name) . " → Grandchild {$grandchild_num}",
                'user_id' => 1,
                'network_id' => $network_id,
                'public' => 1,
            ));
            
            if (is_wp_error($result)) {
                echo "  ❌ Failed to create {$grandchild_path}: " . $result->get_error_message() . "\n";
                continue;
            }
            
            $blog_id = $result;
            
            // Register nested path
            $wpdb->replace(
                $nested_table,
                array(
                    'blog_id' => $blog_id,
                    'network_id' => $network_id,
                    'path' => $grandchild_path,
                ),
                array('%d', '%d', '%s')
            );
            
            // Set up site
            switch_to_blog($blog_id);
            update_option('blogname', "Parent {$parent_num} → " . ucfirst($child_name) . " → Grandchild {$grandchild_num} (Level 3)");
            NestedTree\setup_homepage_with_level($blog_id);
            
            // Create sample post
            $post_id = wp_insert_post(array(
                'post_title' => "Parent {$parent_num} → " . ucfirst($child_name) . " → Grandchild {$grandchild_num} - Sample Post",
                'post_content' => "Sample post for {$grandchild_path}",
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_author' => 1,
            ));
            
            restore_current_blog();
            
            echo "  ✅ Created {$grandchild_path} (blog_id: {$blog_id}, post_id: {$post_id})\n";
            $created++;
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Complete!\n";
echo "  Created: {$created} grandchildren\n";
echo "  Registered: {$registered} existing grandchildren\n";
echo "  Total processed: " . ($created + $registered) . "\n\n";

// Verify final structure
$sites = $wpdb->get_results($wpdb->prepare(
    "SELECT blog_id, path FROM {$nested_table} WHERE network_id=%d AND path LIKE '/parent%' ORDER BY path ASC",
    $network_id
), ARRAY_A);

echo "Final Verification:\n";
$all_ok = true;
foreach ([1, 2, 3] as $parent_num) {
    for ($child_num = 1; $child_num <= 3; $child_num++) {
        $child_path = "/parent{$parent_num}/child{$child_num}/";
        $grandchildren = array();
        foreach ($sites as $site) {
            if (strpos($site['path'], $child_path) === 0 && $site['path'] !== $child_path) {
                $grandchildren[] = $site['path'];
            }
        }
        $count = count($grandchildren);
        $status = $count === 3 ? '✅' : '❌';
        echo "  Parent {$parent_num} → Child {$child_num}: {$count} grandchildren {$status}\n";
        if ($count !== 3) {
            $all_ok = false;
        }
    }
}

echo "\n" . ($all_ok ? "✅ All children have exactly 3 grandchildren!" : "❌ Some children are missing grandchildren") . "\n";

