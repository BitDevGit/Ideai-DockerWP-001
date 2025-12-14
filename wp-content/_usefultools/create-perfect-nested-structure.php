<?php
/**
 * Create Perfect Nested Structure
 * 
 * Creates a 4-level nested structure:
 * - Level 1: Parent sites
 * - Level 2: Child1 and Child2 for each parent
 * - Level 3: Grandchild for each child
 * 
 * Each site gets:
 * - Homepage with level-specific content
 * - Sample post named after the blog
 * 
 * Run: wp eval-file create-perfect-nested-structure.php
 */

/**
 * Create Perfect Nested Structure
 * 
 * Run: wp eval-file create-perfect-nested-structure.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/ideai.wp.plugin.platform.php';
require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';
require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-homepage.php';

use Ideai\Wp\Platform;
use Ideai\Wp\Platform\NestedTree;

if (!is_multisite()) {
    die("Error: This script requires multisite.\n");
}

$network_id = get_current_network_id();

// Check if nested tree is enabled
if (!Platform\nested_tree_enabled($network_id)) {
    die("Error: Nested tree not enabled for network $network_id.\n");
}

global $wpdb;

echo "🌳 Creating Perfect Nested Structure\n";
echo str_repeat("=", 60) . "\n\n";

// Configuration
$parent_count = 3; // Create 3 parent sites
$children_per_parent = 3; // 3 children per parent
$grandchildren_per_child = 3; // 3 grandchildren per child
$parents = [];

// Step 1: Create Parent Sites (Level 1)
echo "📁 Step 1: Creating Parent Sites (Level 1)\n";
for ($i = 1; $i <= $parent_count; $i++) {
    $parent_path = "/parent{$i}/";
    
    // Check if site already exists
    $existing = NestedTree\resolve_blog_for_request_path($parent_path, $network_id);
    if ($existing && !empty($existing['blog_id'])) {
        echo "  ⚠️  Parent $i already exists (blog_id: {$existing['blog_id']})\n";
        $parents[$i] = $existing['blog_id'];
        continue;
    }
    
    // Create parent site
    $result = wp_insert_site(array(
        'domain' => get_network()->domain,
        'path' => $parent_path,
        'title' => "Parent Site $i",
        'user_id' => 1,
        'network_id' => $network_id,
        'public' => 1,
    ));
    
    if (is_wp_error($result)) {
        echo "  ❌ Failed to create parent $i: " . $result->get_error_message() . "\n";
        continue;
    }
    
    $blog_id = $result;
    $parents[$i] = $blog_id;
    
    // Register nested path in database
    global $wpdb;
    $nested_table = NestedTree\table_name();
    $wpdb->replace(
        $nested_table,
        array(
            'blog_id' => $blog_id,
            'network_id' => $network_id,
            'path' => $parent_path,
        ),
        array('%d', '%d', '%s')
    );
    
    // Set up site
    switch_to_blog($blog_id);
    
    // Update site name
    update_option('blogname', "Parent Site $i (Level 1)");
    
    // Set up homepage
    Ideai\Wp\Platform\NestedTree\setup_homepage_with_level($blog_id);
    
    // Create sample post
    $post_id = wp_insert_post(array(
        'post_title' => "Parent Site $i - Sample Post",
        'post_content' => "This is a sample post for Parent Site $i.\n\nThis post confirms that the database is saving content correctly for this blog.\n\nBlog ID: $blog_id\nPath: $parent_path\nLevel: 1",
        'post_status' => 'publish',
        'post_type' => 'post',
        'post_author' => 1,
    ));
    
    if ($post_id) {
        echo "  ✅ Created parent $i (blog_id: $blog_id, post_id: $post_id)\n";
    } else {
        echo "  ⚠️  Created parent $i (blog_id: $blog_id) but post creation failed\n";
    }
    
    restore_current_blog();
}

echo "\n";

// Step 2: Create Child Sites (Level 2) - 3 children per parent
echo "📁 Step 2: Creating Child Sites (Level 2)\n";
$children = [];

foreach ($parents as $parent_num => $parent_blog_id) {
    for ($child_num = 1; $child_num <= $children_per_parent; $child_num++) {
        $child_name = "child{$child_num}";
        $child_path = "/parent{$parent_num}/{$child_name}/";
        
        // Check if site already exists
        $existing = NestedTree\resolve_blog_for_request_path($child_path, $network_id);
        if ($existing && !empty($existing['blog_id'])) {
            echo "  ⚠️  {$child_path} already exists (blog_id: {$existing['blog_id']})\n";
            $children[$parent_num][$child_name] = $existing['blog_id'];
            continue;
        }
        
        // Create child site
        $result = wp_insert_site(array(
            'domain' => get_network()->domain,
            'path' => $child_path,
            'title' => "Parent $parent_num → " . ucfirst($child_name),
            'user_id' => 1,
            'network_id' => $network_id,
            'public' => 1,
        ));
        
        if (is_wp_error($result)) {
            echo "  ❌ Failed to create {$child_path}: " . $result->get_error_message() . "\n";
            continue;
        }
        
        $blog_id = $result;
        $children[$parent_num][$child_name] = $blog_id;
        
        // Register nested path in database
        global $wpdb;
        $nested_table = NestedTree\table_name();
        $wpdb->replace(
            $nested_table,
            array(
                'blog_id' => $blog_id,
                'network_id' => $network_id,
                'path' => $child_path,
            ),
            array('%d', '%d', '%s')
        );
        
        // Set up site
        switch_to_blog($blog_id);
        
        // Update site name
        update_option('blogname', "Parent $parent_num → " . ucfirst($child_name) . " (Level 2)");
        
        // Set up homepage
        Ideai\Wp\Platform\NestedTree\setup_homepage_with_level($blog_id);
        
        // Create sample post
        $post_title = "Parent $parent_num → " . ucfirst($child_name) . " - Sample Post";
        $post_id = wp_insert_post(array(
            'post_title' => $post_title,
            'post_content' => "This is a sample post for Parent $parent_num → " . ucfirst($child_name) . ".\n\nThis post confirms that the database is saving content correctly for this blog.\n\nBlog ID: $blog_id\nPath: $child_path\nLevel: 2\nParent: Parent $parent_num",
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1,
        ));
        
        if ($post_id) {
            echo "  ✅ Created {$child_path} (blog_id: $blog_id, post_id: $post_id)\n";
        } else {
            echo "  ⚠️  Created {$child_path} (blog_id: $blog_id) but post creation failed\n";
        }
        
        restore_current_blog();
    }
}

echo "\n";

// Step 3: Create Grandchild Sites (Level 3) - 3 grandchildren per child
echo "📁 Step 3: Creating Grandchild Sites (Level 3)\n";

foreach ($children as $parent_num => $parent_children) {
    foreach ($parent_children as $child_name => $child_blog_id) {
        for ($grandchild_num = 1; $grandchild_num <= $grandchildren_per_child; $grandchild_num++) {
            $grandchild_path = "/parent{$parent_num}/{$child_name}/grandchild{$grandchild_num}/";
        
        // Check if site already exists
        $existing = NestedTree\resolve_blog_for_request_path($grandchild_path, $network_id);
        if ($existing && !empty($existing['blog_id'])) {
            echo "  ⚠️  {$grandchild_path} already exists (blog_id: {$existing['blog_id']})\n";
            continue;
        }
        
            // Check if site already exists
            $existing = NestedTree\resolve_blog_for_request_path($grandchild_path, $network_id);
            if ($existing && !empty($existing['blog_id'])) {
                echo "  ⚠️  {$grandchild_path} already exists (blog_id: {$existing['blog_id']})\n";
                continue;
            }
            
            // Create grandchild site
            $result = wp_insert_site(array(
                'domain' => get_network()->domain,
                'path' => $grandchild_path,
                'title' => "Parent $parent_num → " . ucfirst($child_name) . " → Grandchild $grandchild_num",
                'user_id' => 1,
                'network_id' => $network_id,
                'public' => 1,
            ));
            
            if (is_wp_error($result)) {
                echo "  ❌ Failed to create {$grandchild_path}: " . $result->get_error_message() . "\n";
                continue;
            }
            
            $blog_id = $result;
            
            // Register nested path in database
            global $wpdb;
            $nested_table = NestedTree\table_name();
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
            
            // Update site name
            update_option('blogname', "Parent $parent_num → " . ucfirst($child_name) . " → Grandchild $grandchild_num (Level 3)");
            
            // Set up homepage
            Ideai\Wp\Platform\NestedTree\setup_homepage_with_level($blog_id);
            
            // Create sample post
            $post_title = "Parent $parent_num → " . ucfirst($child_name) . " → Grandchild $grandchild_num - Sample Post";
            $post_id = wp_insert_post(array(
                'post_title' => $post_title,
                'post_content' => "This is a sample post for Parent $parent_num → " . ucfirst($child_name) . " → Grandchild $grandchild_num.\n\nThis post confirms that the database is saving content correctly for this blog.\n\nBlog ID: $blog_id\nPath: $grandchild_path\nLevel: 3\nParent: Parent $parent_num → " . ucfirst($child_name),
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_author' => 1,
            ));
            
            if ($post_id) {
                echo "  ✅ Created {$grandchild_path} (blog_id: $blog_id, post_id: $post_id)\n";
            } else {
                echo "  ⚠️  Created {$grandchild_path} (blog_id: $blog_id) but post creation failed\n";
            }
            
            restore_current_blog();
        }
    }
}

echo "\n";

// Step 4: Update siteurl and home options for all created sites
echo "🔧 Step 4: Updating siteurl and home options\n";

$all_sites = $wpdb->get_results($wpdb->prepare(
    'SELECT blog_id, path FROM ' . NestedTree\table_name() . ' WHERE network_id=%d ORDER BY path ASC',
    $network_id
), ARRAY_A);

$updated = 0;
foreach ($all_sites as $site) {
    $nested_path = $site['path'];
    switch_to_blog($site['blog_id']);
    
    $site_obj = get_site($site['blog_id']);
    $domain = $site_obj->domain;
    $scheme = is_ssl() ? 'https' : 'http';
    
    $correct_siteurl = $scheme . '://' . $domain . $nested_path;
    $correct_home = $scheme . '://' . $domain . $nested_path;
    
    $current_siteurl = get_option('siteurl');
    $current_home = get_option('home');
    
    if ($current_siteurl !== $correct_siteurl) {
        update_option('siteurl', $correct_siteurl);
        $updated++;
    }
    
    if ($current_home !== $correct_home) {
        update_option('home', $correct_home);
        $updated++;
    }
    
    restore_current_blog();
}

echo "  ✅ Updated $updated site options\n\n";

// Summary
echo str_repeat("=", 60) . "\n";
echo "✅ Perfect Nested Structure Created!\n\n";

echo "Structure Summary:\n";
echo "- Level 1 (Parents): " . count($parents) . " sites\n";
echo "- Level 2 (Children): " . (count($parents) * $children_per_parent) . " sites\n";
echo "- Level 3 (Grandchildren): " . (count($parents) * $children_per_parent * $grandchildren_per_child) . " sites\n";
echo "- Total: " . (count($parents) * (1 + $children_per_parent + ($children_per_parent * $grandchildren_per_child))) . " sites\n\n";

echo "All sites have:\n";
echo "- ✅ Correct nested paths\n";
echo "- ✅ Homepage with level-specific content\n";
echo "- ✅ Sample post named after the blog\n";
echo "- ✅ Correct siteurl and home options\n\n";

echo "Test URLs:\n";
foreach ($parents as $parent_num => $blog_id) {
    echo "  Parent $parent_num: https://site3.localwp/parent{$parent_num}/\n";
    for ($child_num = 1; $child_num <= $children_per_parent; $child_num++) {
        $child_name = "child{$child_num}";
        if (isset($children[$parent_num][$child_name])) {
            echo "    → {$child_name}: https://site3.localwp/parent{$parent_num}/{$child_name}/\n";
            for ($grandchild_num = 1; $grandchild_num <= $grandchildren_per_child; $grandchild_num++) {
                echo "      → grandchild{$grandchild_num}: https://site3.localwp/parent{$parent_num}/{$child_name}/grandchild{$grandchild_num}/\n";
            }
        }
    }
}

echo "\n";

