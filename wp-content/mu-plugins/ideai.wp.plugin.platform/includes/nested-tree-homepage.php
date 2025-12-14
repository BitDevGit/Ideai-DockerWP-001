<?php
/**
 * Nested tree multisite: homepage customization.
 *
 * Creates a homepage that displays the site's level/depth for testing.
 * Updates page title to show the level.
 */

namespace Ideai\Wp\Platform\NestedTree;

use function Ideai\Wp\Platform\nested_tree_enabled;
use function Ideai\Wp\Platform\log_msg;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get the depth/level of a site based on its path.
 *
 * @param string $path Site path (e.g., "/parent1/child1/")
 * @return int Depth level (0 = root, 1 = first level, 2 = second level, etc.)
 */
function get_site_depth($path) {
	if (empty($path) || $path === '/') {
		return 0;
	}
	$segments = array_filter(explode('/', trim($path, '/')));
	return count($segments);
}

/**
 * Get the current site's depth.
 *
 * @return int
 */
function get_current_site_depth() {
	$path = get_blog_details()->path ?? '/';
	return get_site_depth($path);
}

/**
 * Create or update homepage content with level information.
 *
 * @param int $blog_id
 */
function setup_homepage_with_level($blog_id) {
	if (!nested_tree_enabled()) {
		return;
	}

	switch_to_blog($blog_id);

	$site = get_blog_details($blog_id);
	$path = $site->path ?? '/';
	$depth = get_site_depth($path);
	
	// Generate site name from path
	$site_name = generate_site_name_from_path($path, $depth);

	// Get or create the homepage
	$homepage_id = get_option('page_on_front');
	if (!$homepage_id) {
		// Create a new page for the homepage
		$homepage_id = wp_insert_post(array(
			'post_title' => 'Home',
			'post_content' => generate_homepage_content($path, $depth, $site_name),
			'post_status' => 'publish',
			'post_type' => 'page',
		));
		if ($homepage_id && !is_wp_error($homepage_id)) {
			update_option('show_on_front', 'page');
			update_option('page_on_front', $homepage_id);
		}
	} else {
		// Update existing homepage
		wp_update_post(array(
			'ID' => $homepage_id,
			'post_content' => generate_homepage_content($path, $depth, $site_name),
		));
	}
	
	// Ensure site name is correct
	$current_name = get_option('blogname');
	if ($current_name !== $site_name) {
		update_option('blogname', $site_name);
	}

	restore_current_blog();
}

/**
 * Generate site name from path.
 * 
 * @param string $path Site path
 * @param int    $depth Site depth
 * @return string Site name
 */
function generate_site_name_from_path($path, $depth) {
	if ($path === '/' || $depth === 0) {
		return 'Site 3: Subdirectory Multisite';
	}
	
	// Remove leading/trailing slashes and split
	$segments = array_filter(explode('/', trim($path, '/')));
	
	if (empty($segments)) {
		return 'Site 3: Subdirectory Multisite';
	}
	
	// Capitalize each segment and join
	$name_parts = array();
	foreach ($segments as $segment) {
		// Convert parent1 -> Parent 1, child2 -> Child 2, etc.
		if (preg_match('/^([a-z]+)(\d+)$/', $segment, $matches)) {
			$word = ucfirst($matches[1]);
			$num = $matches[2];
			$name_parts[] = "{$word} {$num}";
		} else {
			$name_parts[] = ucfirst($segment);
		}
	}
	
	$site_name = implode(' → ', $name_parts);
	
	// Add level indicator
	$level_label = $depth === 0 ? 'Root' : "Level {$depth}";
	
	return "{$site_name} ({$level_label})";
}

/**
 * Generate homepage content showing the site's level.
 *
 * @param string $path Site path
 * @param int    $depth Site depth
 * @param string $site_name Site name
 * @return string HTML content
 */
function generate_homepage_content($path, $depth, $site_name = null) {
	if ($site_name === null) {
		$site_name = generate_site_name_from_path($path, $depth);
	}
	
	$level_label = $depth === 0 ? 'Root' : "Level {$depth}";
	$path_display = $path === '/' ? '/' : rtrim($path, '/');

	$content = <<<HTML
<div style="max-width: 800px; margin: 40px auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">
	<h1 style="font-size: 2.5em; margin-bottom: 0.5em; color: #333;">
		🌳 {$site_name}
	</h1>
	
	<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
		<h2 style="margin-top: 0; color: #555;">Site Information</h2>
		<ul style="list-style: none; padding: 0; margin: 0;">
			<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
				<strong>Path:</strong> <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">{$path_display}</code>
			</li>
			<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
				<strong>Depth:</strong> <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">{$depth}</code>
			</li>
			<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
				<strong>Level:</strong> <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">{$level_label}</code>
			</li>
			<li style="padding: 8px 0;">
				<strong>Site ID:</strong> <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">{get_current_blog_id()}</code>
			</li>
		</ul>
	</div>
	
	<div style="background: #e8f4f8; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #0073aa;">
		<p style="margin: 0; color: #555;">
			<strong>ℹ️ Testing Note:</strong> This homepage displays the site's level in the nested tree structure. 
			The page title also reflects the level for easy identification during testing.
		</p>
	</div>
	
	<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
		<p style="color: #666; font-size: 0.9em;">
			This is a sovereign site with its own content, admin, and community. 
			It can create child sites that will be nested under this path.
		</p>
	</div>
</div>
HTML;

	return $content;
}

/**
 * Filter page title to include site level.
 *
 * @param string $title Current title
 * @return string Modified title
 */
function filter_page_title($title) {
	if (!nested_tree_enabled()) {
		return $title;
	}

	// Only modify on frontend homepage
	if (!is_front_page() || is_admin()) {
		return $title;
	}

	$depth = get_current_site_depth();
	$level_label = $depth === 0 ? 'Root' : "Level {$depth}";

	// Append level to title
	$site_name = get_bloginfo('name');
	return "{$site_name} ({$level_label})";
}

/**
 * Filter document title to include site level.
 *
 * @param array $title_parts Title parts
 * @return array Modified title parts
 */
function filter_document_title_parts($title_parts) {
	if (!nested_tree_enabled()) {
		return $title_parts;
	}

	// Only modify on frontend homepage
	if (!is_front_page() || is_admin()) {
		return $title_parts;
	}

	$depth = get_current_site_depth();
	$level_label = $depth === 0 ? 'Root' : "Level {$depth}";

	// Add level to title
	if (isset($title_parts['title'])) {
		$title_parts['title'] .= " ({$level_label})";
	}

	return $title_parts;
}

// Hook into site creation to setup homepage
add_action('wpmu_new_blog', function ($blog_id, $user_id, $domain, $path, $site_id, $meta) {
	// Delay slightly to ensure site is fully created
	wp_schedule_single_event(time() + 2, 'ideai_setup_homepage', array($blog_id));
}, 10, 6);

// Handle the scheduled event
add_action('ideai_setup_homepage', function ($blog_id) {
	setup_homepage_with_level($blog_id);
});

// Filter page title
add_filter('wp_title', __NAMESPACE__ . '\\filter_page_title', 20);
add_filter('document_title_parts', __NAMESPACE__ . '\\filter_document_title_parts', 20);

