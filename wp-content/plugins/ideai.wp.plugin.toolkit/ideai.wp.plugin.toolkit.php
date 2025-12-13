<?php
/**
 * Plugin Name: IdeAI Toolkit
 * Description: IdeAI admin UI + tasks for local dev factory and network management. Works with or without IdeAI Platform MU-plugin.
 * Version: 0.1.0
 * Author: IdeAI
 */

namespace Ideai\Wp\Toolkit;

if (!defined('ABSPATH')) {
	exit;
}

const VERSION = '0.1.0';
const SLUG    = 'ideai-wp-toolkit';
const SLUG_SITES = 'ideai-wp-toolkit-sites';

function platform_available() {
	return function_exists('\\Ideai\\Wp\\Platform\\is_loaded');
}

function get_platform_flag($key, $default = null) {
	if (!platform_available()) {
		return $default;
	}
	return \Ideai\Wp\Platform\get_flag($key, $default);
}

function set_platform_flag($key, $value) {
	if (!platform_available()) {
		return false;
	}
	return (bool) \Ideai\Wp\Platform\set_flag($key, $value);
}

function maybe_handle_network_post() {
	if (!is_multisite() || !is_network_admin()) {
		return;
	}
	if (!current_user_can('manage_network_options')) {
		return;
	}
	if (empty($_POST['ideai_action']) || (string) $_POST['ideai_action'] !== 'save_flags') {
		return;
	}
	check_admin_referer('ideai_save_flags');

	$enabled = !empty($_POST['ideai_nested_tree_enabled']);
	$mode = isset($_POST['ideai_nested_tree_collision_mode']) ? sanitize_text_field(wp_unslash($_POST['ideai_nested_tree_collision_mode'])) : 'strict';
	if ($mode !== 'strict') {
		$mode = 'strict';
	}

	$ok = true;
	$ok = $ok && set_platform_flag('ideai_nested_tree_enabled', $enabled);
	$ok = $ok && set_platform_flag('ideai_nested_tree_collision_mode', $mode);

	$base = network_admin_url('admin.php?page=' . rawurlencode(SLUG));
	$q = $ok ? 'ideai_saved=1' : 'ideai_saved=0';
	wp_safe_redirect($base . '&' . $q);
	exit;
}

function register_network_menu() {
	if (!is_multisite()) {
		return;
	}
	if (!current_user_can('manage_network_options')) {
		return;
	}

	add_menu_page(
		'IdeAI',
		'IdeAI',
		'manage_network_options',
		SLUG,
		__NAMESPACE__ . '\\render_status_page',
		'dashicons-admin-generic',
		3
	);

	add_submenu_page(
		SLUG,
		'Status',
		'Status',
		'manage_network_options',
		SLUG,
		__NAMESPACE__ . '\\render_status_page'
	);

	add_submenu_page(
		SLUG,
		'Sites',
		'Sites',
		'manage_network_sites',
		SLUG_SITES,
		__NAMESPACE__ . '\\render_sites_page'
	);
}
add_action('network_admin_menu', __NAMESPACE__ . '\\register_network_menu');
add_action('network_admin_init', __NAMESPACE__ . '\\maybe_handle_network_post');

function internal_path_from_nested($nested_path) {
	$nested_path = (string) $nested_path;
	$nested_path = trim($nested_path, '/');
	if ($nested_path === '') {
		return '/';
	}
	$segments = preg_split('#/+#', $nested_path);
	$segments = array_filter($segments, function ($s) { return $s !== ''; });
	$flat = implode('--', $segments);
	return '/' . $flat . '/';
}

function sanitize_child_slug($slug) {
	$slug = strtolower((string) $slug);
	$slug = trim($slug);
	$slug = preg_replace('/\s+/', '-', $slug);
	$slug = preg_replace('/[^a-z0-9-]/', '', $slug);
	$slug = preg_replace('/-+/', '-', $slug);
	$slug = trim($slug, '-');
	return $slug;
}

function handle_create_nested_site() {
	if (!is_multisite() || !is_network_admin()) {
		wp_die('Multisite network admin only.');
	}
	if (!current_user_can('manage_network_sites')) {
		wp_die('Insufficient permissions.');
	}
	check_admin_referer('ideai_create_nested_site');

	if (!platform_available()) {
		wp_die('IdeAI Platform MU-plugin is required for nested tree sites.');
	}

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;
	if ($network_id <= 0) {
		wp_die('Could not determine network.');
	}

	$parent_blog_id = isset($_POST['ideai_parent_blog_id']) ? (int) $_POST['ideai_parent_blog_id'] : 0;
	$child_slug_raw = isset($_POST['ideai_child_slug']) ? wp_unslash($_POST['ideai_child_slug']) : '';
	$child_slug = sanitize_child_slug($child_slug_raw);
	if ($parent_blog_id <= 0) {
		wp_die('Missing parent site.');
	}
	if ($child_slug === '') {
		wp_die('Missing child slug.');
	}

	$parent_mapped = \Ideai\Wp\Platform\NestedTree\get_blog_path($parent_blog_id, $network_id);
	if (!$parent_mapped) {
		$parent_site = get_site($parent_blog_id);
		$parent_mapped = $parent_site && !empty($parent_site->path) ? $parent_site->path : '/';
	}
	$parent_mapped = \Ideai\Wp\Platform\NestedTree\normalize_path($parent_mapped);

	$nested_path = \Ideai\Wp\Platform\NestedTree\normalize_path($parent_mapped . $child_slug . '/');
	$internal_path = internal_path_from_nested($nested_path);

	$net = function_exists('get_network') ? get_network($network_id) : null;
	if (!$net || empty($net->domain)) {
		wp_die('Could not load network.');
	}
	$domain = (string) $net->domain;

	$title = 'Nested: ' . trim($nested_path, '/');
	$user_id = get_current_user_id();

	$blog_id = wpmu_create_blog($domain, $internal_path, $title, $user_id, array(), $network_id);
	if (is_wp_error($blog_id)) {
		wp_die($blog_id);
	}

	$ok = \Ideai\Wp\Platform\NestedTree\upsert_blog_path((int) $blog_id, $nested_path, $network_id);
	if (!$ok) {
		if (function_exists('wpmu_delete_blog')) {
			wpmu_delete_blog((int) $blog_id, true);
		}
		wp_die('Could not register nested path (collision or mapping error).');
	}

	$edit = network_admin_url('site-info.php?id=' . (int) $blog_id);
	wp_safe_redirect($edit);
	exit;
}
add_action('admin_post_ideai_create_nested_site', __NAMESPACE__ . '\\handle_create_nested_site');

function render_site_new_integration() {
	if (!current_user_can('manage_network_sites')) {
		return;
	}

	echo '<h2>IdeAI: Create nested child site</h2>';
	if (!platform_available()) {
		echo '<p><em>Install MU-plugin <code>ideai.wp.plugin.platform</code> to enable nested tree sites.</em></p>';
		return;
	}

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;
	$sites = function_exists('get_sites') ? get_sites(array('network_id' => $network_id, 'number' => 2000)) : array();

	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin: 10px 0 20px 0; padding: 12px; border: 1px solid #ddd; background: #fff">';
	wp_nonce_field('ideai_create_nested_site');
	echo '<input type="hidden" name="action" value="ideai_create_nested_site" />';

	echo '<p><label><strong>Parent site</strong><br />';
	echo '<select name="ideai_parent_blog_id">';
	foreach ($sites as $s) {
		$bid = (int) $s->blog_id;
		$label = $s->domain . $s->path . ' (ID ' . $bid . ')';
		echo '<option value="' . esc_attr($bid) . '">' . esc_html($label) . '</option>';
	}
	echo '</select></label></p>';

	echo '<p><label><strong>Child slug</strong><br />';
	echo '<input type="text" name="ideai_child_slug" value="" placeholder="subsub1" style="width: 240px" />';
	echo '</label><br /><span class="description">Lowercase letters, numbers, hyphens. Creates a nested site under the chosen parent.</span></p>';

	submit_button('Create nested site', 'primary', 'submit', false);
	echo '</form>';
}
add_action('network_site_new_form', __NAMESPACE__ . '\\render_site_new_integration');

function register_site_menu() {
	// Optional: provide a minimal Status page in non-network wp-admin too.
	if (is_multisite() && is_network_admin()) {
		return;
	}
	if (!current_user_can('manage_options')) {
		return;
	}

	add_menu_page(
		'IdeAI',
		'IdeAI',
		'manage_options',
		SLUG,
		__NAMESPACE__ . '\\render_status_page',
		'dashicons-admin-generic',
		3
	);
}
add_action('admin_menu', __NAMESPACE__ . '\\register_site_menu');

function render_status_page() {
	$is_ms = is_multisite();

	$flags = array(
		'ideai_nested_tree_enabled'         => get_platform_flag('ideai_nested_tree_enabled', false),
		'ideai_nested_tree_collision_mode'  => get_platform_flag('ideai_nested_tree_collision_mode', 'strict'),
	);

	echo '<div class="wrap">';
	echo '<h1>IdeAI</h1>';

	echo '<h2>Status</h2>';
	echo '<table class="widefat striped" style="max-width: 900px">';
	echo '<tbody>';

	echo '<tr><th style="width:260px">Toolkit version</th><td>' . esc_html(VERSION) . '</td></tr>';
	echo '<tr><th>Multisite</th><td>' . esc_html($is_ms ? 'yes' : 'no') . '</td></tr>';
	echo '<tr><th>Platform (MU-plugin)</th><td>' . esc_html(platform_available() ? 'present' : 'missing') . '</td></tr>';

	if (platform_available() && $is_ms) {
		foreach ($flags as $k => $v) {
			echo '<tr><th>' . esc_html($k) . '</th><td><code>' . esc_html(is_bool($v) ? ($v ? 'true' : 'false') : (string) $v) . '</code></td></tr>';
		}
	} else {
		echo '<tr><th>Feature flags</th><td><em>Not available (requires multisite + platform MU-plugin)</em></td></tr>';
	}

	echo '</tbody></table>';

	if ($is_ms && is_network_admin()) {
		if (isset($_GET['ideai_saved'])) {
			$ok = (string) $_GET['ideai_saved'] === '1';
			echo '<div class="notice ' . esc_attr($ok ? 'notice-success' : 'notice-error') . '"><p>' . esc_html($ok ? 'Saved.' : 'Could not save (platform MU-plugin missing?)') . '</p></div>';
		}

		echo '<h2>Feature flags (Network)</h2>';
		if (!platform_available()) {
			echo '<p><em>Install the MU-plugin <code>ideai.wp.plugin.platform</code> to enable feature flags.</em></p>';
		} else {
			$action = network_admin_url('edit.php?action=' . rawurlencode(SLUG));
			echo '<form method="post" action="' . esc_url($action) . '" style="max-width: 900px; margin-top: 10px">';
			wp_nonce_field('ideai_save_flags');
			echo '<input type="hidden" name="ideai_action" value="save_flags" />';

			echo '<table class="form-table" role="presentation"><tbody>';

			echo '<tr>';
			echo '<th scope="row">Nested tree multisite</th>';
			echo '<td><label><input type="checkbox" name="ideai_nested_tree_enabled" value="1" ' . checked(!empty($flags['ideai_nested_tree_enabled']), true, false) . ' /> Enable nested tree routing (per-network)</label></td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th scope="row">Collision mode</th>';
			echo '<td>';
			echo '<select name="ideai_nested_tree_collision_mode">';
			echo '<option value="strict"' . selected((string) $flags['ideai_nested_tree_collision_mode'], 'strict', false) . '>strict (block conflicts)</option>';
			echo '</select>';
			echo '<p class="description">Strict mode prevents creating a Page whose full path conflicts with a nested site path (and vice versa).</p>';
			echo '</td>';
			echo '</tr>';

			echo '</tbody></table>';

			submit_button('Save flags');
			echo '</form>';
		}
	}

	if (!platform_available()) {
		echo '<p><strong>Note:</strong> Advanced routing features (nested tree multisite) require the MU-plugin <code>ideai.wp.plugin.platform</code>. Toolkit remains safe without it.</p>';
	}

	echo '</div>';
}

function render_sites_page() {
	if (!is_multisite() || !is_network_admin()) {
		wp_die('Network admin only.');
	}
	if (!current_user_can('manage_network_sites')) {
		wp_die('Insufficient permissions.');
	}

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;

	echo '<div class="wrap">';
	echo '<h1>IdeAI → Sites</h1>';

	if (!platform_available()) {
		echo '<p><strong>Platform MU-plugin missing.</strong> Install <code>ideai.wp.plugin.platform</code> to use nested tree features.</p>';
		echo '</div>';
		return;
	}

	echo '<p>Registered nested-site paths (per-network):</p>';
	$rows = \Ideai\Wp\Platform\NestedTree\list_mappings($network_id);
	if (empty($rows)) {
		echo '<p><em>No nested sites registered yet.</em></p>';
	} else {
		echo '<table class="widefat striped" style="max-width: 900px"><thead><tr><th>Path</th><th>Blog</th></tr></thead><tbody>';
		foreach ($rows as $r) {
			$bid = (int) $r['blog_id'];
			$path = (string) $r['path'];
			$link = network_admin_url('site-info.php?id=' . $bid);
			echo '<tr><td><code>' . esc_html($path) . '</code></td><td><a href="' . esc_url($link) . '">Blog ID ' . esc_html((string) $bid) . '</a></td></tr>';
		}
		echo '</tbody></table>';
	}

	echo '<h2 style="margin-top: 24px">Create nested child site</h2>';
	echo '<p>This creates a new multisite site with an internal safe slug and registers a pretty nested path for routing.</p>';
	echo '<p><a class="button button-primary" href="' . esc_url(network_admin_url('site-new.php')) . '">Open Add New Site</a></p>';

	echo '</div>';
}


