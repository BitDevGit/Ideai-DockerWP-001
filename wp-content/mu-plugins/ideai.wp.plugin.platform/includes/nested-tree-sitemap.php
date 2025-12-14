<?php
/**
 * Nested Tree Sitemap UI
 * 
 * Simple hierarchical list view of all nested sites
 */

namespace Ideai\Wp\Platform\NestedTreeSitemap;

use Ideai\Wp\Platform;
use Ideai\Wp\Platform\NestedTree;

if (!defined('ABSPATH')) {
	exit;
}

const MENU_SLUG = 'ideai-nested-sitemap';
const PAGE_TITLE = 'Sites Sitemap';
const MENU_TITLE = 'Sitemap';

/**
 * Initialize the admin UI
 */
function init() {
	if (!is_multisite()) {
		return;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	add_action('network_admin_menu', __NAMESPACE__ . '\\add_menu_page', 25);
}

/**
 * Add admin menu page to IdeAI menu
 */
function add_menu_page() {
	add_submenu_page(
		\Ideai\Wp\Platform\AdminUI\MENU_SLUG_STATUS,
		PAGE_TITLE,
		MENU_TITLE,
		'manage_sites',
		MENU_SLUG,
		__NAMESPACE__ . '\\render_page'
	);
}

/**
 * Render the admin page
 */
function render_page() {
	if (!current_user_can('manage_sites')) {
		wp_die('Unauthorized');
	}
	
	$network_id = get_current_network_id();
	
	// Get all nested sites
	global $wpdb;
	$nested_table = NestedTree\table_name();
	$sites_db = $wpdb->get_results($wpdb->prepare(
		'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
		$network_id
	), ARRAY_A);

	// Add the root multisite itself
	$root_site_id = get_main_site_id($network_id);
	$root_site_path = '/';
	$sites = array(
		array('blog_id' => $root_site_id, 'path' => $root_site_path)
	);
	foreach ($sites_db as $site_item) {
		$sites[] = $site_item;
	}

	// Get site details and build tree
	$site_details = array();
	$tree = array();
	
	foreach ($sites as $site) {
		switch_to_blog($site['blog_id']);
		$site_details[$site['path']] = array(
			'blog_id' => $site['blog_id'],
			'path' => $site['path'],
			'name' => get_option('blogname'),
			'url' => get_option('home'),
			'admin_url' => admin_url(),
			'depth' => count(array_filter(explode('/', trim($site['path'], '/')))),
		);
		restore_current_blog();
	}
	
	// Build hierarchical tree
	$tree = build_hierarchical_tree($sites, $site_details);

	?>
	<div class="wrap">
		<h1><?php echo esc_html(PAGE_TITLE); ?></h1>
		<p class="description">Hierarchical list of all nested sites showing parent-to-child relationships.</p>

		<?php if (empty($sites_db)) : ?>
			<div class="notice notice-info">
				<p>No nested sites found. <a href="<?php echo esc_url(network_admin_url('admin.php?page=ideai-nested-site-creator')); ?>">Create your first nested site</a></p>
			</div>
		<?php else : ?>
			<div style="margin: 20px 0; padding: 15px; background: #f0f0f1; border-radius: 4px;">
				<?php render_tree_list($tree, $site_details); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Build hierarchical tree structure
 */
function build_hierarchical_tree($sites, $site_details) {
	// Sort by path length (parents before children)
	usort($sites, function($a, $b) {
		$len_a = strlen($a['path']);
		$len_b = strlen($b['path']);
		if ($len_a !== $len_b) {
			return $len_a - $len_b;
		}
		return strcmp($a['path'], $b['path']);
	});
	
	$tree = array();
	$path_map = array();
	
	foreach ($sites as $site) {
		$path = $site['path'];
		$segments = array_filter(explode('/', trim($path, '/')));
		$depth = count($segments);
		
		$node = array(
			'blog_id' => $site['blog_id'],
			'path' => $path,
			'depth' => $depth,
			'children' => array(),
		);
		
		if ($depth === 0 || $path === '/') {
			$tree[$site['blog_id']] = $node;
			$path_map[$path] = &$tree[$site['blog_id']];
		} else {
			// Calculate parent path
			$parent_segments = $segments;
			array_pop($parent_segments);
			$parent_path = '/';
			if (!empty($parent_segments)) {
				$parent_path = '/' . implode('/', $parent_segments) . '/';
			}
			$parent_path = NestedTree\normalize_path($parent_path);
			
			if (isset($path_map[$parent_path])) {
				$path_map[$parent_path]['children'][$site['blog_id']] = $node;
				$path_map[$path] = &$path_map[$parent_path]['children'][$site['blog_id']];
			} else {
				// Orphan - add to root
				$tree[$site['blog_id']] = $node;
				$path_map[$path] = &$tree[$site['blog_id']];
			}
		}
	}
	
	return $tree;
}

/**
 * Render tree as nested list
 */
function render_tree_list($tree, $site_details, $level = 0) {
	if (empty($tree)) {
		return;
	}
	
	// Sort by path
	uasort($tree, function($a, $b) {
		return strcmp($a['path'], $b['path']);
	});
	
	echo '<ul style="list-style: none; margin: 0; padding-left: ' . ($level * 30) . 'px;">';
	
	foreach ($tree as $node) {
		$site = $site_details[$node['path']];
		$indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
		$connector = $level > 0 ? '└─ ' : '';
		
		// Color based on depth
		$colors = array(
			0 => array('bg' => '#00507a', 'text' => '#fff', 'border' => '#003f60'),
			1 => array('bg' => '#0073aa', 'text' => '#fff', 'border' => '#00507a'),
			2 => array('bg' => '#00a32a', 'text' => '#fff', 'border' => '#007a20'),
			3 => array('bg' => '#d63638', 'text' => '#fff', 'border' => '#a32a2c'),
		);
		$color = isset($colors[$site['depth']]) ? $colors[$site['depth']] : array('bg' => '#dba617', 'text' => '#fff', 'border' => '#a37a10');
		
		echo '<li style="margin: 8px 0;">';
		echo '<div style="display: inline-block; padding: 10px 15px; background: ' . esc_attr($color['bg']) . '; color: ' . esc_attr($color['text']) . '; border: 2px solid ' . esc_attr($color['border']) . '; border-radius: 4px; min-width: 300px;">';
		echo '<strong>' . esc_html($connector . $site['name']) . '</strong><br>';
		echo '<small style="opacity: 0.9;">' . esc_html($site['path']) . ' (Level ' . $site['depth'] . ')</small><br>';
		echo '<a href="' . esc_url($site['url']) . '" target="_blank" style="color: ' . esc_attr($color['text']) . '; text-decoration: underline; margin-right: 10px;">Visit</a>';
		echo '<a href="' . esc_url($site['admin_url']) . '" target="_blank" style="color: ' . esc_attr($color['text']) . '; text-decoration: underline;">Admin</a>';
		echo '</div>';
		
		if (!empty($node['children'])) {
			render_tree_list($node['children'], $site_details, $level + 1);
		}
		
		echo '</li>';
	}
	
	echo '</ul>';
}

// Initialize
add_action('plugins_loaded', __NAMESPACE__ . '\\init');
