<?php
/**
 * Nested tree multisite: request routing integration.
 *
 * We hook into WordPress multisite site resolution to allow "deepest prefix wins"
 * based on the ideai nested-site mapping table.
 *
 * This is feature-flagged per-network and only intended for subdirectory multisite.
 */

namespace Ideai\Wp\Platform\NestedTreeRouting;

use Ideai\Wp\Platform;
use Ideai\Wp\Platform\NestedTree;

if (!defined('ABSPATH')) {
	exit;
}

function is_subdirectory_multisite() {
	if (!function_exists('is_multisite') || !is_multisite()) {
		return false;
	}
	if (function_exists('is_subdomain_install')) {
		return !is_subdomain_install();
	}
	// Fallback.
	return !defined('SUBDOMAIN_INSTALL') || !SUBDOMAIN_INSTALL;
}

/**
 * Resolve the current network id using core if available.
 *
 * @return int|null
 */
function resolve_network_id($domain, $path) {
	if (!function_exists('get_network_by_path')) {
		return null;
	}
	$net = get_network_by_path($domain, $path);
	if ($net && isset($net->id)) {
		return (int) $net->id;
	}
	return null;
}

/**
 * Filter: pre_get_site_by_path
 *
 * Core WordPress hook for site resolution. Runs early (priority 1) to ensure
 * we intercept before WordPress's default resolution.
 *
 * @param mixed  $site    null|false|WP_Site
 * @param string $domain
 * @param string $path
 * @param array  $segments
 * @return mixed
 */
function pre_get_site_by_path($site, $domain, $path, $segments) {
	// Early bailouts for performance
	if (!is_subdirectory_multisite()) {
		return $site;
	}

	$network_id = resolve_network_id($domain, $path);
	if (!$network_id) {
		return $site;
	}

	if (!Platform\nested_tree_enabled($network_id)) {
		return $site;
	}

	// Normalize path for matching
	$normalized_path = NestedTree\normalize_path($path);
	
	// CRITICAL: Always check our nested table first, regardless of what WordPress resolved.
	// This ensures we find the deepest matching nested site, not just what's in wp_blogs.
	$resolved = NestedTree\resolve_blog_for_request_path($normalized_path, $network_id);
	
	if ($resolved && !empty($resolved['blog_id'])) {
		$blog_id = (int) $resolved['blog_id'];
		$wp_site = function_exists('get_site') ? get_site($blog_id) : null;
		
		if ($wp_site) {
			// Log override if WordPress resolved to different site
			if ($site && is_object($site) && isset($site->blog_id) && (int) $site->blog_id !== $blog_id) {
				error_log(sprintf(
					'[NESTED_TREE] Overriding: wp=%d -> nested=%d for %s',
					(int) $site->blog_id,
					$blog_id,
					$normalized_path
				));
			}
			
			// ALWAYS return our resolved site
			return $wp_site;
		}
	}
	
	// No nested site found - return whatever WordPress resolved (or null)
	return $site;
}

// Priority 1 ensures we run VERY early, before WordPress core resolution
add_filter('pre_get_site_by_path', __NAMESPACE__ . '\\pre_get_site_by_path', 1, 4);

/**
 * Allow iframe embedding for nested sites (for dashboard)
 */
function allow_iframe_embedding() {
	if (!is_multisite() || !is_subdirectory_multisite()) {
		return;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	// Remove X-Frame-Options header to allow iframe embedding
	remove_action('admin_init', 'send_frame_options_header');
	remove_action('login_init', 'send_frame_options_header');
	
	// Also remove via filter
	add_filter('send_frame_options_header', '__return_false', 10, 0);
}
add_action('init', __NAMESPACE__ . '\\allow_iframe_embedding', 1);

/**
 * Fix admin URLs to use nested paths instead of wp_blogs.path
 * This ensures wp-admin URLs match the nested site structure
 * CRITICAL: Must use the provided $blog_id, not get_current_blog_id()
 * 
 * WordPress admin_url() signature: admin_url($path = '', $scheme = 'admin', $blog_id = null)
 * When $blog_id is null, WordPress uses get_current_blog_id()
 * When $blog_id is provided, WordPress uses that specific blog
 * 
 * The filter receives: ($url, $path, $blog_id)
 * - $url: The generated URL
 * - $path: The path passed to admin_url()
 * - $blog_id: The blog_id used (null if not provided, then WordPress uses current)
 */
function fix_admin_url($url, $path, $blog_id) {
	if (!is_multisite() || !is_subdirectory_multisite()) {
		return $url;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return $url;
	}
	
	// CRITICAL: Determine the target blog_id
	// If blog_id is provided in the filter, use it
	// Otherwise, WordPress used get_current_blog_id() when generating the URL
	$target_blog_id = $blog_id ? (int) $blog_id : get_current_blog_id();
	
	// Only fix URLs for nested sites
	$nested_path = NestedTree\get_blog_path($target_blog_id, $network_id);
	if (!$nested_path) {
		return $url;
	}
	
	// Get the site's domain
	$site = get_site($target_blog_id);
	if (!$site) {
		return $url;
	}
	
	// Parse the current URL to see what WordPress generated
	$parsed = wp_parse_url($url);
	if (!$parsed || !isset($parsed['path'])) {
		return $url;
	}
	
	// Handle WordPress 'admin' scheme - convert to actual scheme
	$scheme = isset($parsed['scheme']) ? $parsed['scheme'] : '';
	if ($scheme === 'admin') {
		$scheme = is_ssl() ? 'https' : 'http';
	} elseif (!$scheme) {
		$scheme = is_ssl() ? 'https' : 'http';
	}
	
	// Check if URL already has correct nested path
	$current_path = $parsed['path'];
	if (strpos($current_path, $nested_path) === 0) {
		// Path is correct, but may need scheme fix
		if ($parsed['scheme'] === 'admin') {
			// Rebuild with correct scheme
			$fixed_url = $scheme . '://' . $site->domain . $current_path;
			if (isset($parsed['query'])) {
				$fixed_url .= '?' . $parsed['query'];
			}
			if (isset($parsed['fragment'])) {
				$fixed_url .= '#' . $parsed['fragment'];
			}
			return $fixed_url;
		}
		// Already correct
		return $url;
	}
	
	// Reconstruct URL using nested path
	$admin_path = $nested_path . 'wp-admin/';
	if ($path && $path !== '/') {
		$admin_path .= ltrim($path, '/');
	}
	
	$fixed_url = $scheme . '://' . $site->domain . $admin_path;
	if (isset($parsed['query'])) {
		$fixed_url .= '?' . $parsed['query'];
	}
	if (isset($parsed['fragment'])) {
		$fixed_url .= '#' . $parsed['fragment'];
	}
	
	return $fixed_url;
}
// Priority 1 to run VERY early, before any other URL filters
add_filter('admin_url', __NAMESPACE__ . '\\fix_admin_url', 1, 3);

/**
 * Fix site_url to use nested paths
 * CRITICAL: Must use the provided $blog_id parameter
 */
function fix_site_url($url, $path, $scheme, $blog_id) {
	if (!is_multisite() || !is_subdirectory_multisite()) {
		return $url;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return $url;
	}
	
	// CRITICAL: Use the provided blog_id, not current blog
	$target_blog_id = $blog_id ? (int) $blog_id : get_current_blog_id();
	
	// Only fix URLs for nested sites
	$nested_path = NestedTree\get_blog_path($target_blog_id, $network_id);
	if (!$nested_path) {
		return $url;
	}
	
	// Get the site's domain
	$site = get_site($target_blog_id);
	if (!$site) {
		return $url;
	}
	
	// Reconstruct URL using nested path
	$url_scheme = $scheme ? $scheme : (is_ssl() ? 'https' : 'http');
	$fixed_path = $nested_path;
	if ($path && $path !== '/') {
		$fixed_path .= ltrim($path, '/');
	}
	
	$fixed_url = $url_scheme . '://' . $site->domain . $fixed_path;
	
	return $fixed_url;
}
// Priority 20 to run after WordPress core
add_filter('site_url', __NAMESPACE__ . '\\fix_site_url', 20, 4);

/**
 * Fix home_url to use nested paths
 * CRITICAL: Must use the provided $blog_id parameter
 */
function fix_home_url($url, $path, $scheme, $blog_id) {
	if (!is_multisite() || !is_subdirectory_multisite()) {
		return $url;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return $url;
	}
	
	// CRITICAL: Use the provided blog_id, not current blog
	$target_blog_id = $blog_id ? (int) $blog_id : get_current_blog_id();
	
	// Only fix URLs for nested sites
	$nested_path = NestedTree\get_blog_path($target_blog_id, $network_id);
	if (!$nested_path) {
		return $url;
	}
	
	// Get the site's domain
	$site = get_site($target_blog_id);
	if (!$site) {
		return $url;
	}
	
	// Reconstruct URL using nested path
	$url_scheme = $scheme ? $scheme : (is_ssl() ? 'https' : 'http');
	$fixed_path = $nested_path;
	if ($path && $path !== '/') {
		$fixed_path .= ltrim($path, '/');
	}
	
	$fixed_url = $url_scheme . '://' . $site->domain . $fixed_path;
	
	return $fixed_url;
}
// Priority 20 to run after WordPress core
add_filter('home_url', __NAMESPACE__ . '\\fix_home_url', 20, 4);

/**
 * Force correct blog context after site resolution.
 * This ensures we're in the right blog even if something switches it.
 * CRITICAL: Also runs on admin_init to ensure admin pages have correct context.
 */
function force_correct_blog() {
	if (!is_multisite() || !is_subdirectory_multisite()) {
		return;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	// Get current request path
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	$parsed = parse_url($request_uri);
	$path = $parsed['path'] ?? '/';
	$normalized = NestedTree\normalize_path($path);
	
	// Resolve using nested table
	$resolved = NestedTree\resolve_blog_for_request_path($normalized, $network_id);
	
	if ($resolved && !empty($resolved['blog_id'])) {
		$target_blog_id = (int) $resolved['blog_id'];
		$current_blog_id = get_current_blog_id();
		
		if ($target_blog_id !== $current_blog_id) {
			error_log(sprintf(
				'[NESTED_TREE] Force switching: current=%d -> target=%d for %s',
				$current_blog_id,
				$target_blog_id,
				$normalized
			));
			switch_to_blog($target_blog_id);
		}
	}
}
// Run very early, before template loading
add_action('template_redirect', __NAMESPACE__ . '\\force_correct_blog', 1);
// ALSO run on admin_init to ensure admin pages have correct context
add_action('admin_init', __NAMESPACE__ . '\\force_correct_blog', 1);

/**
 * Fix front page detection for nested sites.
 * WordPress doesn't recognize nested paths as front pages, so we need to help it.
 */
function fix_front_page_detection($query_vars) {
	if (!is_multisite() || !is_subdirectory_multisite()) {
		return $query_vars;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return $query_vars;
	}
	
	// Get current request path
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	$parsed = parse_url($request_uri);
	$path = $parsed['path'] ?? '/';
	$normalized = NestedTree\normalize_path($path);
	
	// Get current site's nested path
	$current_blog_id = get_current_blog_id();
	$current_nested_path = NestedTree\get_blog_path($current_blog_id, $network_id);
	
	// If we're at the exact nested path (no additional segments), it's the front page
	if ($current_nested_path && $normalized === $current_nested_path) {
		// Force WordPress to treat this as the front page
		$query_vars['pagename'] = '';
		$query_vars['page_id'] = '';
		$query_vars['name'] = '';
		$query_vars['error'] = '';
	}
	
	return $query_vars;
}
add_filter('request', __NAMESPACE__ . '\\fix_front_page_detection', 1);

/**
 * Force front page query vars after parse_query.
 */
function force_front_page_query($query) {
	if (!is_multisite() || !is_subdirectory_multisite() || is_admin()) {
		return;
	}
	
	if (!$query->is_main_query()) {
		return;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	// Get current request path
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	$parsed = parse_url($request_uri);
	$path = $parsed['path'] ?? '/';
	$normalized = NestedTree\normalize_path($path);
	
	// Get current site's nested path
	$current_blog_id = get_current_blog_id();
	$current_nested_path = NestedTree\get_blog_path($current_blog_id, $network_id);
	
	// If we're at the exact nested path, force front page
	if ($current_nested_path && $normalized === $current_nested_path) {
		$query->is_home = true;
		$query->is_front_page = true;
		$query->is_page = false;
		$query->is_singular = false;
		$query->is_404 = false;
		$query->set('page_id', '');
		$query->set('pagename', '');
		
		// Set the homepage page ID if configured
		$homepage_id = get_option('page_on_front');
		if ($homepage_id && get_option('show_on_front') === 'page') {
			$query->set('page_id', $homepage_id);
			$query->is_page = true;
			$query->is_singular = true;
			$query->is_home = false;
		}
	}
}
add_action('parse_query', __NAMESPACE__ . '\\force_front_page_query', 20);

/**
 * Force front-page.php template for nested site roots.
 */
function force_front_page_template($template) {
	if (!is_multisite() || !is_subdirectory_multisite() || is_admin()) {
		return $template;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return $template;
	}
	
	// Get current request path
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	$parsed = parse_url($request_uri);
	$path = $parsed['path'] ?? '/';
	$normalized = NestedTree\normalize_path($path);
	
	// Get current site's nested path
	$current_blog_id = get_current_blog_id();
	$current_nested_path = NestedTree\get_blog_path($current_blog_id, $network_id);
	
	// If we're at the exact nested path, force front-page.php
	if ($current_nested_path && $normalized === $current_nested_path) {
		$front_page_template = locate_template(array('front-page.php'));
		if ($front_page_template) {
			// Also fix the query to prevent 404
			global $wp_query;
			if ($wp_query) {
				$wp_query->is_404 = false;
				$wp_query->is_home = true;
				$wp_query->is_front_page = true;
			}
			return $front_page_template;
		}
	}
	
	return $template;
}
add_filter('template_include', __NAMESPACE__ . '\\force_front_page_template', 99);

/**
 * Prevent 404 for nested site roots - run very early.
 */
function prevent_404_for_nested_roots() {
	if (!is_multisite() || !is_subdirectory_multisite() || is_admin()) {
		return;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	// Get current request path
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	$parsed = parse_url($request_uri);
	$path = $parsed['path'] ?? '/';
	$normalized = NestedTree\normalize_path($path);
	
	// Get current site's nested path
	$current_blog_id = get_current_blog_id();
	$current_nested_path = NestedTree\get_blog_path($current_blog_id, $network_id);
	
	// If we're at the exact nested path, force homepage query
	if ($current_nested_path && $normalized === $current_nested_path) {
		global $wp_query, $wp;
		
		// Clear any error state
		$wp_query->is_404 = false;
		$wp_query->is_home = true;
		$wp_query->is_front_page = true;
		$wp_query->is_page = false;
		$wp_query->is_singular = false;
		$wp_query->set('error', '');
		$wp_query->set('pagename', '');
		$wp_query->set('name', '');
		
		// If homepage is set to a page, load that page
		$homepage_id = get_option('page_on_front');
		if ($homepage_id && get_option('show_on_front') === 'page') {
			$homepage = get_post($homepage_id);
			if ($homepage) {
				$wp_query->queried_object = $homepage;
				$wp_query->queried_object_id = $homepage_id;
				$wp_query->is_page = true;
				$wp_query->is_singular = true;
				$wp_query->is_home = false;
				$wp_query->set('page_id', $homepage_id);
			}
		}
		
		// Prevent redirect_canonical from redirecting
		remove_action('template_redirect', 'redirect_canonical');
	}
}
add_action('parse_request', __NAMESPACE__ . '\\prevent_404_for_nested_roots', 1);
