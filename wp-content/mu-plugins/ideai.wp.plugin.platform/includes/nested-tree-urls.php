<?php
/**
 * Nested tree multisite: outbound URL rewriting (flat ⇄ nested).
 *
 * When enabled, WordPress may generate URLs using an internal "flat" site path.
 * We rewrite those URLs to the registered nested path for that blog.
 */

namespace Ideai\Wp\Platform\NestedTreeUrls;

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
	return !defined('SUBDOMAIN_INSTALL') || !SUBDOMAIN_INSTALL;
}

function rebuild_url(array $p) {
	$scheme   = isset($p['scheme']) ? $p['scheme'] . '://' : '';
	$user     = $p['user'] ?? '';
	$pass     = isset($p['pass']) ? ':' . $p['pass']  : '';
	$auth     = $user !== '' ? $user . $pass . '@' : '';
	$host     = $p['host'] ?? '';
	$port     = isset($p['port']) ? ':' . $p['port'] : '';
	$path     = $p['path'] ?? '';
	$query    = isset($p['query']) ? '?' . $p['query'] : '';
	$fragment = isset($p['fragment']) ? '#' . $p['fragment'] : '';
	return $scheme . $auth . $host . $port . $path . $query . $fragment;
}

function replace_path_prefix($full_path, $from_prefix, $to_prefix) {
	$full_path = (string) $full_path;
	$from_prefix = (string) $from_prefix;
	$to_prefix = (string) $to_prefix;

	if ($from_prefix === $to_prefix) {
		return $full_path;
	}
	if ($from_prefix === '' || $from_prefix === '/') {
		// Root site: only rewrite if full path begins with "/" (it will), but we avoid rewriting root.
		return $full_path;
	}
	if (strpos($full_path, $from_prefix) !== 0) {
		return $full_path;
	}
	return $to_prefix . substr($full_path, strlen($from_prefix));
}

/**
 * Rewrite a generated URL for a given blog_id, if nested-tree mapping exists.
 *
 * @param string $url
 * @param int    $blog_id
 * @return string
 */
function maybe_rewrite_for_blog($url, $blog_id) {
	if (!is_subdirectory_multisite()) {
		return $url;
	}
	if (!function_exists('get_current_network_id')) {
		return $url;
	}
	$network_id = (int) get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return $url;
	}

	$blog_id = (int) $blog_id;
	if ($blog_id <= 0 || !function_exists('get_site')) {
		return $url;
	}

	$mapped = NestedTree\get_blog_path($blog_id, $network_id);
	if (!$mapped || $mapped === '/') {
		return $url;
	}

	$site = get_site($blog_id);
	if (!$site || empty($site->path)) {
		return $url;
	}

	$internal = NestedTree\normalize_path($site->path);
	$mapped = NestedTree\normalize_path($mapped);

	$p = wp_parse_url($url);
	if (!is_array($p)) {
		return $url;
	}

	// Only rewrite when host matches the network domain.
	$net = function_exists('get_network') ? get_network($network_id) : null;
	if ($net && !empty($net->domain) && isset($p['host']) && $p['host'] !== $net->domain) {
		return $url;
	}

	$old_path = $p['path'] ?? '';
	
	// If internal and mapped are the same, no rewrite needed
	if ($internal === $mapped) {
		return $url;
	}
	
	// Standard WordPress multisite: replace internal path with mapped nested path
	$new_path = replace_path_prefix($old_path, $internal, $mapped);
	
	if ($new_path === $old_path) {
		return $url;
	}

	$p['path'] = $new_path;
	return rebuild_url($p);
}

// home_url filter provides $blog_id as 4th argument.
function filter_home_url($url, $path, $orig_scheme, $blog_id) {
	return maybe_rewrite_for_blog($url, $blog_id);
}
add_filter('home_url', __NAMESPACE__ . '\\filter_home_url', 20, 4);

// site_url filter provides $blog_id as 4th argument.
function filter_site_url($url, $path, $scheme, $blog_id) {
	return maybe_rewrite_for_blog($url, $blog_id);
}
add_filter('site_url', __NAMESPACE__ . '\\filter_site_url', 20, 4);

// admin_url: third arg is blog_id.
function filter_admin_url($url, $path, $blog_id) {
	return maybe_rewrite_for_blog($url, $blog_id);
}
add_filter('admin_url', __NAMESPACE__ . '\\filter_admin_url', 20, 3);

// wp_login_url doesn't provide blog_id; use current blog.
function filter_login_url($login_url, $redirect, $force_reauth) {
	$blog_id = function_exists('get_current_blog_id') ? get_current_blog_id() : 0;
	return maybe_rewrite_for_blog($login_url, $blog_id);
}
add_filter('login_url', __NAMESPACE__ . '\\filter_login_url', 20, 3);

// network_site_url: second arg is path, third is scheme, no blog_id - use current blog
function filter_network_site_url($url, $path, $scheme) {
	$blog_id = function_exists('get_current_blog_id') ? get_current_blog_id() : 0;
	return maybe_rewrite_for_blog($url, $blog_id);
}
add_filter('network_site_url', __NAMESPACE__ . '\\filter_network_site_url', 20, 3);

// network_home_url: second arg is path, third is scheme, no blog_id - use current blog
function filter_network_home_url($url, $path, $scheme) {
	$blog_id = function_exists('get_current_blog_id') ? get_current_blog_id() : 0;
	return maybe_rewrite_for_blog($url, $blog_id);
}
add_filter('network_home_url', __NAMESPACE__ . '\\filter_network_home_url', 20, 3);


