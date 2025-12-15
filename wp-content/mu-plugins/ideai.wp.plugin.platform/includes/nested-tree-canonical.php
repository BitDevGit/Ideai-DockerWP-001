<?php
/**
 * Nested tree multisite: canonical redirect policy.
 *
 * Prevent redirects that would canonicalize to the internal/flat blog path when
 * nested-tree is enabled, and instead canonicalize toward the mapped nested path.
 */

namespace Ideai\Wp\Platform\NestedTreeCanonical;

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

function rewrite_internal_to_mapped($url, $internal_prefix, $mapped_prefix) {
	$p = wp_parse_url($url);
	if (!is_array($p)) {
		return $url;
	}
	$path = $p['path'] ?? '';
	if (strpos($path, $internal_prefix) !== 0) {
		return $url;
	}
	$p['path'] = $mapped_prefix . substr($path, strlen($internal_prefix));
	return rebuild_url($p);
}

/**
 * Filter canonical redirects to prefer mapped nested path.
 *
 * @param string|false $redirect_url
 * @param string       $requested_url
 * @return string|false
 */
function filter_redirect_canonical($redirect_url, $requested_url) {
	if ($redirect_url === false) {
		return false;
	}
	if (!is_subdirectory_multisite()) {
		return $redirect_url;
	}
	if (!function_exists('get_current_network_id') || !function_exists('get_current_blog_id') || !function_exists('get_site')) {
		return $redirect_url;
	}

	$network_id = (int) get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return $redirect_url;
	}

	$blog_id = (int) get_current_blog_id();
	$mapped = NestedTree\get_blog_path($blog_id, $network_id);
	if (!$mapped || $mapped === '/') {
		return $redirect_url;
	}

	$site = get_site($blog_id);
	if (!$site || empty($site->path)) {
		return $redirect_url;
	}

	$internal = NestedTree\normalize_path($site->path);
	$mapped = NestedTree\normalize_path($mapped);
	if ($internal === $mapped) {
		return $redirect_url;
	}

	// If canonical redirect points at internal path, rewrite it to the mapped path.
	$redirect_parts = wp_parse_url($redirect_url);
	$requested_parts = wp_parse_url($requested_url);
	if (!is_array($redirect_parts) || !is_array($requested_parts)) {
		return $redirect_url;
	}

	// Only touch redirects for the same host.
	if (isset($redirect_parts['host'], $requested_parts['host']) && $redirect_parts['host'] !== $requested_parts['host']) {
		return $redirect_url;
	}

	$redirect_path = $redirect_parts['path'] ?? '';
	if (strpos($redirect_path, $internal) === 0) {
		$new = rewrite_internal_to_mapped($redirect_url, $internal, $mapped);
		if ($new !== $redirect_url) {
			Platform\log_msg('nested_tree canonical rewrite', array(
				'blog_id' => $blog_id,
				'internal' => $internal,
				'mapped' => $mapped,
				'from' => $redirect_url,
				'to' => $new,
			));
		}
		return $new;
	}

	return $redirect_url;
}

add_filter('redirect_canonical', __NAMESPACE__ . '\\filter_redirect_canonical', 20, 2);



