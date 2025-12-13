<?php
/**
 * Nested tree multisite: mapping + resolver helpers.
 *
 * Storage: custom table (network-scoped), optimized for "deepest prefix wins".
 */

namespace Ideai\Wp\Platform\NestedTree;

use function Ideai\Wp\Platform\log_msg;

if (!defined('ABSPATH')) {
	exit;
}

const SCHEMA_VERSION = 1;

function table_name() {
	global $wpdb;
	// base_prefix is the network-wide prefix in multisite.
	return $wpdb->base_prefix . 'ideai_nested_sites';
}

function schema_option_key() {
	return 'ideai_nested_tree_schema_version';
}

function normalize_path($path) {
	$path = (string) $path;
	// Strip query/fragments defensively.
	$path = preg_replace('/[?#].*$/', '', $path);
	$path = trim($path);

	if ($path === '') {
		return '/';
	}
	if ($path[0] !== '/') {
		$path = '/' . $path;
	}
	// Collapse multiple slashes.
	$path = preg_replace('#/+#', '/', $path);
	// Ensure trailing slash for stable prefix matching.
	if (substr($path, -1) !== '/') {
		$path .= '/';
	}
	return $path;
}

function ensure_schema($network_id = null) {
	// Schema is network-wide; store schema version at network scope.
	if (!is_multisite()) {
		return false;
	}
	if ($network_id === null && function_exists('get_current_network_id')) {
		$network_id = get_current_network_id();
	}

	$installed = (int) get_network_option($network_id, schema_option_key(), 0);
	if ($installed >= SCHEMA_VERSION) {
		return true;
	}

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table = table_name();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		network_id bigint(20) unsigned NOT NULL,
		blog_id bigint(20) unsigned NOT NULL,
		path varchar(255) NOT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY network_path (network_id, path),
		UNIQUE KEY network_blog (network_id, blog_id),
		KEY network_id (network_id),
		KEY path (path)
	) {$charset_collate};";

	dbDelta($sql);

	update_network_option($network_id, schema_option_key(), SCHEMA_VERSION);
	log_msg('nested_tree schema ensured', array('table' => $table, 'schema' => SCHEMA_VERSION));
	return true;
}

function cache_group() {
	return 'ideai_nested_tree';
}

function cache_key_blog_path($network_id, $blog_id) {
	return 'n' . (int) $network_id . ':b' . (int) $blog_id . ':path';
}

function cache_key_resolve($network_id, $request_path) {
	return 'n' . (int) $network_id . ':resolve:' . md5((string) $request_path);
}

/**
 * Register or update mapping: blog_id -> nested path.
 *
 * @param int         $blog_id
 * @param string      $path
 * @param int|null    $network_id
 * @return bool
 */
function upsert_blog_path($blog_id, $path, $network_id = null) {
	if (!is_multisite()) {
		return false;
	}
	if ($network_id === null && function_exists('get_current_network_id')) {
		$network_id = get_current_network_id();
	}
	if (!$network_id) {
		return false;
	}

	ensure_schema($network_id);

	global $wpdb;
	$table = table_name();
	$blog_id = (int) $blog_id;
	$network_id = (int) $network_id;
	$path = normalize_path($path);

	// Strict collision prevention: do not allow creating a nested site at a path that
	// already exists as a Page path in the network's main site.
	$collision_mode = \Ideai\Wp\Platform\get_flag(\Ideai\Wp\Platform\FLAG_NESTED_TREE_COLLISION_MODE, 'strict', $network_id);
	if ($collision_mode === 'strict' && function_exists('get_network')) {
		$net = get_network($network_id);
		if ($net && !empty($net->site_id) && function_exists('switch_to_blog') && function_exists('get_page_by_path')) {
			$main_blog_id = (int) $net->site_id;
			$relative = trim($path, '/');
			// If relative is empty, it's the root; ignore.
			if ($relative !== '') {
				switch_to_blog($main_blog_id);
				$page = get_page_by_path($relative, OBJECT, 'page');
				restore_current_blog();
				if ($page && !empty($page->ID)) {
					log_msg('nested_tree collision: page exists', array(
						'network_id' => $network_id,
						'path' => $path,
						'page_id' => (int) $page->ID,
					));
					return false;
				}
			}
		}
	}

	// Upsert via delete+insert to keep compatibility across DB versions.
	$wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE network_id=%d AND blog_id=%d", $network_id, $blog_id));
	$wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE network_id=%d AND path=%s", $network_id, $path));

	$ok = (bool) $wpdb->insert(
		$table,
		array(
			'network_id' => $network_id,
			'blog_id' => $blog_id,
			'path' => $path,
		),
		array('%d', '%d', '%s')
	);

	// CRITICAL: Always update wp_blogs.path to match the nested path
	// WordPress uses this stored path for URL generation
	if ($ok) {
		if (function_exists('update_blog_details')) {
			update_blog_details($blog_id, array('path' => $path));
		}
		// Direct database update as backup to ensure it's saved
		global $wpdb;
		$wpdb->update(
			$wpdb->blogs,
			array('path' => $path),
			array('blog_id' => $blog_id),
			array('%s'),
			array('%d')
		);
		// Clear cache
		if (function_exists('clean_blog_cache')) {
			clean_blog_cache($blog_id);
		}
		wp_cache_delete($blog_id, 'blog-details');
		wp_cache_delete($blog_id . 'short', 'blog-details');
	}

	wp_cache_delete(cache_key_blog_path($network_id, $blog_id), cache_group());
	// Resolver cache is path-dependent; we avoid global flush and just let short TTL expire.

	return $ok;
}

/**
 * Get nested path for blog_id (if registered).
 *
 * @return string|null Normalized path or null
 */
function get_blog_path($blog_id, $network_id = null) {
	if (!is_multisite()) {
		return null;
	}
	if ($network_id === null && function_exists('get_current_network_id')) {
		$network_id = get_current_network_id();
	}
	if (!$network_id) {
		return null;
	}

	$blog_id = (int) $blog_id;
	$network_id = (int) $network_id;

	$ck = cache_key_blog_path($network_id, $blog_id);
	$cached = wp_cache_get($ck, cache_group());
	if ($cached !== false) {
		return $cached === null ? null : (string) $cached;
	}

	ensure_schema($network_id);

	global $wpdb;
	$table = table_name();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$path = $wpdb->get_var($wpdb->prepare("SELECT path FROM {$table} WHERE network_id=%d AND blog_id=%d", $network_id, $blog_id));
	$path = $path ? normalize_path($path) : null;

	wp_cache_set($ck, $path, cache_group(), 300);
	return $path;
}

/**
 * Resolve blog_id by request path using "deepest prefix wins".
 *
 * @param string   $request_path
 * @param int|null $network_id
 * @return array{blog_id:int,path:string}|null
 */
function resolve_blog_for_request_path($request_path, $network_id = null) {
	if (!is_multisite()) {
		return null;
	}
	if ($network_id === null && function_exists('get_current_network_id')) {
		$network_id = get_current_network_id();
	}
	if (!$network_id) {
		return null;
	}

	$network_id = (int) $network_id;
	$request_path = normalize_path($request_path);

	$ck = cache_key_resolve($network_id, $request_path);
	$cached = wp_cache_get($ck, cache_group());
	if ($cached !== false) {
		return $cached === null ? null : $cached;
	}

	ensure_schema($network_id);

	global $wpdb;
	$table = table_name();

	// Find the longest registered path that prefixes the request path.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT blog_id, path
			 FROM {$table}
			 WHERE network_id=%d
			   AND %s LIKE CONCAT(path, '%%')
			 ORDER BY LENGTH(path) DESC
			 LIMIT 1",
			$network_id,
			$request_path
		),
		ARRAY_A
	);

	$out = null;
	if ($row && !empty($row['blog_id']) && !empty($row['path'])) {
		$out = array(
			'blog_id' => (int) $row['blog_id'],
			'path' => normalize_path($row['path']),
		);
	}

	wp_cache_set($ck, $out, cache_group(), 60);
	return $out;
}

/**
 * List registered mappings for a network.
 *
 * @return array<int,array{blog_id:int,path:string}>
 */
function list_mappings($network_id = null) {
	if (!is_multisite()) {
		return array();
	}
	if ($network_id === null && function_exists('get_current_network_id')) {
		$network_id = get_current_network_id();
	}
	if (!$network_id) {
		return array();
	}

	$network_id = (int) $network_id;
	ensure_schema($network_id);

	global $wpdb;
	$table = table_name();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $wpdb->get_results($wpdb->prepare("SELECT blog_id, path FROM {$table} WHERE network_id=%d ORDER BY LENGTH(path) ASC, path ASC", $network_id), ARRAY_A);
	if (!is_array($rows)) {
		return array();
	}

	$out = array();
	foreach ($rows as $r) {
		if (empty($r['blog_id']) || empty($r['path'])) {
			continue;
		}
		$out[] = array(
			'blog_id' => (int) $r['blog_id'],
			'path' => normalize_path($r['path']),
		);
	}
	return $out;
}

/**
 * Sync all nested site paths to wp_blogs table.
 * Ensures database path always matches nested path mapping.
 *
 * @param int|null $network_id
 * @return int Number of sites synced
 */
function sync_all_blog_paths($network_id = null) {
	if (!is_multisite()) {
		return 0;
	}
	if ($network_id === null && function_exists('get_current_network_id')) {
		$network_id = get_current_network_id();
	}
	if (!$network_id) {
		return 0;
	}

	$network_id = (int) $network_id;
	$mappings = list_mappings($network_id);
	$synced = 0;

	foreach ($mappings as $mapping) {
		$blog_id = (int) $mapping['blog_id'];
		$nested_path = normalize_path($mapping['path']);
		
		if ($blog_id > 0 && $nested_path !== '/') {
			$site = function_exists('get_site') ? get_site($blog_id) : null;
			if ($site && (!empty($site->path) && normalize_path($site->path) !== $nested_path)) {
				if (function_exists('update_blog_details')) {
					update_blog_details($blog_id, array('path' => $nested_path));
					$synced++;
				}
			}
		}
	}

	return $synced;
}


