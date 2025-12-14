<?php
/**
 * Clean and Rebuild Network Structure
 * 
 * 1. Removes all existing nested sites
 * 2. Creates clean structure: 2 parents → 2 kids → 2 grandkids → 2 great-grandkids
 * 3. Names them properly in order
 */

// This script is run via: wp eval-file wp-content/_usefultools/clean-and-rebuild-network.php
// WordPress is already loaded by wp-cli, so we just need to include the platform files
require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/ideai.wp.plugin.platform.php';

use Ideai\Wp\Platform;
use Ideai\Wp\Platform\NestedTree;

if (!is_multisite()) {
	die("This script only works in multisite mode.\n");
}

$network_id = get_current_network_id();
if (!Platform\nested_tree_enabled($network_id)) {
	die("Nested tree is not enabled for this network. Enable it in IdeAI → Status first.\n");
}

echo "🧹 Cleaning existing nested sites...\n";

// Get all nested sites
global $wpdb;
$nested_table = NestedTree\table_name();
$existing_sites = $wpdb->get_results($wpdb->prepare(
	'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d AND path != %s',
	$network_id,
	'/'
), ARRAY_A);

// Delete all nested sites (except root)
foreach ($existing_sites as $site) {
	$blog_id = (int) $site['blog_id'];
	if ($blog_id === 1) {
		continue; // Don't delete root site
	}
	
	echo "  Deleting site ID {$blog_id} ({$site['path']})...\n";
	
	// Delete from nested table
	$wpdb->delete($nested_table, array('blog_id' => $blog_id, 'network_id' => $network_id), array('%d', '%d'));
	
	// Delete WordPress site
	if (function_exists('wpmu_delete_blog')) {
		wpmu_delete_blog($blog_id, true); // true = drop tables
	}
}

echo "✅ All nested sites deleted.\n\n";

echo "🏗️  Creating new network structure...\n";

// Structure: 2 parents → 2 kids → 2 grandkids → 2 great-grandkids
$structure = array();

// Create parents
for ($p = 1; $p <= 2; $p++) {
	$parent_path = "/parent{$p}/";
	$parent_name = "Parent {$p}";
	
	// Create parent site
	echo "  Creating {$parent_name}...\n";
	$parent_blog_id = wp_insert_site(array(
		'domain' => get_current_site()->domain,
		'path' => $parent_path,
		'title' => $parent_name,
		'user_id' => 1,
		'network_id' => $network_id,
	));
	
	if (is_wp_error($parent_blog_id)) {
		echo "    ERROR: " . $parent_blog_id->get_error_message() . "\n";
		continue;
	}
	
	$parent_blog_id = (int) $parent_blog_id;
	NestedTree\upsert_blog_path($parent_blog_id, $parent_path, $network_id);
	
	// Setup parent homepage
	switch_to_blog($parent_blog_id);
	update_option('blogname', $parent_name);
	$homepage_id = wp_insert_post(array(
		'post_title' => $parent_name . ' (Level 1)',
		'post_content' => "<h1>{$parent_name}</h1><p>This is a Level 1 parent site.</p><p>Path: {$parent_path}</p>",
		'post_status' => 'publish',
		'post_type' => 'page',
	));
	update_option('show_on_front', 'page');
	update_option('page_on_front', $homepage_id);
	restore_current_blog();
	
	// Create children for this parent
	for ($c = 1; $c <= 2; $c++) {
		$child_path = "/parent{$p}/child{$c}/";
		$child_name = "Parent {$p} → Child {$c}";
		
		echo "    Creating {$child_name}...\n";
		$child_blog_id = wp_insert_site(array(
			'domain' => get_current_site()->domain,
			'path' => $child_path,
			'title' => $child_name,
			'user_id' => 1,
			'network_id' => $network_id,
		));
		
		if (is_wp_error($child_blog_id)) {
			echo "      ERROR: " . $child_blog_id->get_error_message() . "\n";
			continue;
		}
		
		$child_blog_id = (int) $child_blog_id;
		NestedTree\upsert_blog_path($child_blog_id, $child_path, $network_id);
		
		// Setup child homepage
		switch_to_blog($child_blog_id);
		update_option('blogname', $child_name);
		$homepage_id = wp_insert_post(array(
			'post_title' => $child_name . ' (Level 2)',
			'post_content' => "<h1>{$child_name}</h1><p>This is a Level 2 child site.</p><p>Path: {$child_path}</p>",
			'post_status' => 'publish',
			'post_type' => 'page',
		));
		update_option('show_on_front', 'page');
		update_option('page_on_front', $homepage_id);
		restore_current_blog();
		
		// Create grandchildren for this child
		for ($g = 1; $g <= 2; $g++) {
			$grandchild_path = "/parent{$p}/child{$c}/grandchild{$g}/";
			$grandchild_name = "Parent {$p} → Child {$c} → Grandchild {$g}";
			
			echo "      Creating {$grandchild_name}...\n";
			$grandchild_blog_id = wp_insert_site(array(
				'domain' => get_current_site()->domain,
				'path' => $grandchild_path,
				'title' => $grandchild_name,
				'user_id' => 1,
				'network_id' => $network_id,
			));
			
			if (is_wp_error($grandchild_blog_id)) {
				echo "        ERROR: " . $grandchild_blog_id->get_error_message() . "\n";
				continue;
			}
			
			$grandchild_blog_id = (int) $grandchild_blog_id;
			NestedTree\upsert_blog_path($grandchild_blog_id, $grandchild_path, $network_id);
			
			// Setup grandchild homepage
			switch_to_blog($grandchild_blog_id);
			update_option('blogname', $grandchild_name);
			$homepage_id = wp_insert_post(array(
				'post_title' => $grandchild_name . ' (Level 3)',
				'post_content' => "<h1>{$grandchild_name}</h1><p>This is a Level 3 grandchild site.</p><p>Path: {$grandchild_path}</p>",
				'post_status' => 'publish',
				'post_type' => 'page',
			));
			update_option('show_on_front', 'page');
			update_option('page_on_front', $homepage_id);
			restore_current_blog();
			
			// Create great-grandchildren for this grandchild
			for ($gg = 1; $gg <= 2; $gg++) {
				$greatgrandchild_path = "/parent{$p}/child{$c}/grandchild{$g}/greatgrandchild{$gg}/";
				$greatgrandchild_name = "Parent {$p} → Child {$c} → Grandchild {$g} → Great-Grandchild {$gg}";
				
				echo "        Creating {$greatgrandchild_name}...\n";
				$greatgrandchild_blog_id = wp_insert_site(array(
					'domain' => get_current_site()->domain,
					'path' => $greatgrandchild_path,
					'title' => $greatgrandchild_name,
					'user_id' => 1,
					'network_id' => $network_id,
				));
				
				if (is_wp_error($greatgrandchild_blog_id)) {
					echo "          ERROR: " . $greatgrandchild_blog_id->get_error_message() . "\n";
					continue;
				}
				
				$greatgrandchild_blog_id = (int) $greatgrandchild_blog_id;
				NestedTree\upsert_blog_path($greatgrandchild_blog_id, $greatgrandchild_path, $network_id);
				
				// Setup great-grandchild homepage
				switch_to_blog($greatgrandchild_blog_id);
				update_option('blogname', $greatgrandchild_name);
				$homepage_id = wp_insert_post(array(
					'post_title' => $greatgrandchild_name . ' (Level 4)',
					'post_content' => "<h1>{$greatgrandchild_name}</h1><p>This is a Level 4 great-grandchild site.</p><p>Path: {$greatgrandchild_path}</p>",
					'post_status' => 'publish',
					'post_type' => 'page',
				));
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage_id);
				restore_current_blog();
			}
		}
	}
}

echo "\n✅ Network structure created!\n";
echo "📊 Summary:\n";
echo "  - 2 Parents\n";
echo "  - 4 Children (2 per parent)\n";
echo "  - 8 Grandchildren (2 per child)\n";
echo "  - 16 Great-Grandchildren (2 per grandchild)\n";
echo "  - Total: 30 nested sites + 1 root = 31 sites\n\n";

// Fix all site URLs
echo "🔧 Fixing site URLs...\n";
$all_sites = $wpdb->get_results($wpdb->prepare(
	'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
	$network_id
), ARRAY_A);

foreach ($all_sites as $site) {
	switch_to_blog($site['blog_id']);
	$site_url = get_site_url($site['blog_id']);
	$home_url = get_home_url($site['blog_id']);
	update_option('siteurl', $site_url);
	update_option('home', $home_url);
	restore_current_blog();
}

echo "✅ All site URLs fixed.\n";
echo "\n🎉 Network rebuild complete!\n";

