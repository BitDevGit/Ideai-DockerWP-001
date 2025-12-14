<?php
/**
 * Create missing intermediate level 2 sites (children).
 * 
 * Usage: wp eval-file wp-content/_usefultools/create-intermediate-sites.php
 */

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$network_id = 1;
$table = $wpdb->base_prefix . 'ideai_nested_sites';

echo "🌳 Creating missing intermediate level sites (children)...\n";
echo "========================================================\n\n";

$count = 0;

for ($parent = 1; $parent <= 5; $parent++) {
    for ($child = 1; $child <= 2; $child++) {
        $child_path = "/parent{$parent}/child{$child}/";
        
        // Check if this path already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE network_id=%d AND path=%s",
            $network_id,
            $child_path
        ));
        
        if ($exists > 0) {
            echo "⏭️  {$child_path} already exists, skipping\n";
            continue;
        }
        
        $count++;
        echo "[{$count}] Creating: {$child_path}\n";
        
        // Create site with temporary slug
        $temp_slug = "p{$parent}c{$child}";
        $blog_id = wpmu_create_blog(
            get_network()->domain,
            "/{$temp_slug}/",
            "Child {$child} of Parent {$parent}",
            get_current_user_id(),
            array('public' => 1),
            $network_id
        );
        
        if (is_wp_error($blog_id)) {
            echo "  ❌ Failed to create site: " . $blog_id->get_error_message() . "\n";
            continue;
        }
        
        echo "  ✅ Created blog_id: {$blog_id}\n";
        
        // Update path in wp_blogs
        $wpdb->update(
            $wpdb->blogs,
            array('path' => $child_path),
            array('blog_id' => $blog_id)
        );
        echo "  ✅ Updated wp_blogs.path\n";
        
        // Update nested tree mapping
        NestedTree\upsert_blog_path($blog_id, $child_path, $network_id);
        echo "  ✅ Updated nested tree mapping\n\n";
    }
}

echo "✅ Created {$count} intermediate sites!\n";
echo "\n🧪 Verifying complete tree...\n";

$all_sites = NestedTree\list_mappings($network_id);
echo "Total nested sites: " . count($all_sites) . "\n";


