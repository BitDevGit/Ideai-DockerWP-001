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
 * @param mixed  $site    null|false|WP_Site
 * @param string $domain
 * @param string $path
 * @param array  $segments
 * @return mixed
 */
function pre_get_site_by_path($site, $domain, $path, $segments) {
	// Never run in admin context - this is for frontend request routing only
	if (function_exists('is_admin') && is_admin()) {
		return $site;
	}
	
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

	$resolved = NestedTree\resolve_blog_for_request_path($path, $network_id);
	if (!$resolved || empty($resolved['blog_id'])) {
		return $site;
	}

	$blog_id = (int) $resolved['blog_id'];
	$wp_site = function_exists('get_site') ? get_site($blog_id) : null;
	if ($wp_site) {
		Platform\log_msg('nested_tree routed', array(
			'network_id' => $network_id,
			'domain' => $domain,
			'request_path' => $path,
			'matched_path' => $resolved['path'],
			'blog_id' => $blog_id,
		));
		return $wp_site;
	}

	return $site;
}

add_filter('pre_get_site_by_path', __NAMESPACE__ . '\\pre_get_site_by_path', 10, 4);


