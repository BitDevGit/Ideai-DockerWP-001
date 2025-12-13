<?php
/**
 * IdeAI Platform: Network Admin UI.
 *
 * Design goal: ZERO performance impact on frontend requests.
 * - This file registers admin hooks, but all heavy work is guarded by is_admin()
 *   + is_network_admin() + capability checks.
 */

namespace Ideai\Wp\Platform\AdminUI;

use Ideai\Wp\Platform;
use Ideai\Wp\Platform\NestedTree;

if (!defined('ABSPATH')) {
	exit;
}

const MENU_SLUG_STATUS = 'ideai-platform';
const MENU_SLUG_SITES  = 'ideai-platform-sites';

function should_load_network_ui() {
	return function_exists('is_admin')
		&& is_admin()
		&& function_exists('is_network_admin')
		&& is_network_admin()
		&& function_exists('is_multisite')
		&& is_multisite();
}

function register_network_menu() {
	if (!should_load_network_ui()) {
		return;
	}
	if (!current_user_can('manage_network_options')) {
		return;
	}

	add_menu_page(
		'IdeAI',
		'IdeAI',
		'manage_network_options',
		MENU_SLUG_STATUS,
		__NAMESPACE__ . '\\render_status_page',
		'dashicons-admin-generic',
		3
	);

	add_submenu_page(
		MENU_SLUG_STATUS,
		'Status',
		'Status',
		'manage_network_options',
		MENU_SLUG_STATUS,
		__NAMESPACE__ . '\\render_status_page'
	);

	add_submenu_page(
		MENU_SLUG_STATUS,
		'Sites',
		'Sites',
		'manage_network_sites',
		MENU_SLUG_SITES,
		__NAMESPACE__ . '\\render_sites_page'
	);
}
add_action('network_admin_menu', __NAMESPACE__ . '\\register_network_menu');

function handle_save_flags() {
	if (!should_load_network_ui()) {
		wp_die('Network admin only.');
	}
	if (!current_user_can('manage_network_options')) {
		wp_die('Insufficient permissions.');
	}
	check_admin_referer('ideai_save_flags');

	// Resolve network ID: get_current_network_id() can return 0/false, so fallback to 1 (main network).
	$network_id = 0;
	if (function_exists('get_current_network_id')) {
		$network_id = (int) get_current_network_id();
	}
	if ($network_id <= 0) {
		$network_id = 1; // Main network in multisite.
	}

	// Checkbox: if present in POST and equals '1', it's enabled. If absent, it's disabled.
	$enabled = isset($_POST['ideai_nested_tree_enabled']) && (string) $_POST['ideai_nested_tree_enabled'] === '1';
	$mode = isset($_POST['ideai_nested_tree_collision_mode']) ? sanitize_text_field(wp_unslash($_POST['ideai_nested_tree_collision_mode'])) : 'strict';
	if ($mode !== 'strict') {
		$mode = 'strict';
	}

	$ok = true;
	$ok = $ok && Platform\set_flag(Platform\FLAG_NESTED_TREE_ENABLED, $enabled, $network_id);
	$ok = $ok && Platform\set_flag(Platform\FLAG_NESTED_TREE_COLLISION_MODE, $mode, $network_id);

	$base = network_admin_url('admin.php?page=' . rawurlencode(MENU_SLUG_STATUS));
	$q = $ok ? 'ideai_saved=1' : 'ideai_saved=0';
	wp_safe_redirect($base . '&' . $q);
	exit;
}
add_action('admin_post_ideai_save_flags', __NAMESPACE__ . '\\handle_save_flags');

function sanitize_child_slug($slug) {
	$slug = strtolower((string) $slug);
	$slug = trim($slug);
	$slug = preg_replace('/\s+/', '-', $slug);
	$slug = preg_replace('/[^a-z0-9-]/', '', $slug);
	$slug = preg_replace('/-+/', '-', $slug);
	$slug = trim($slug, '-');
	return $slug;
}

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

function handle_create_nested_site() {
	if (!should_load_network_ui()) {
		wp_die('Network admin only.');
	}
	if (!current_user_can('manage_network_sites')) {
		wp_die('Insufficient permissions.');
	}
	check_admin_referer('ideai_create_nested_site');

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;
	if ($network_id <= 0) {
		wp_die('Could not determine network.');
	}

	if (!Platform\nested_tree_enabled($network_id)) {
		wp_die('Nested tree is disabled for this network (enable it in IdeAI → Status).');
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

	$parent_mapped = NestedTree\get_blog_path($parent_blog_id, $network_id);
	if (!$parent_mapped) {
		$parent_site = get_site($parent_blog_id);
		$parent_mapped = $parent_site && !empty($parent_site->path) ? $parent_site->path : '/';
	}
	$parent_mapped = NestedTree\normalize_path($parent_mapped);

	$nested_path = NestedTree\normalize_path($parent_mapped . $child_slug . '/');
	$internal_path = internal_path_from_nested($nested_path);

	$net = function_exists('get_network') ? get_network($network_id) : null;
	if (!$net || empty($net->domain)) {
		wp_die('Could not load network.');
	}

	$title = 'Nested: ' . trim($nested_path, '/');
	$user_id = get_current_user_id();
	$blog_id = wpmu_create_blog((string) $net->domain, $internal_path, $title, $user_id, array(), $network_id);
	if (is_wp_error($blog_id)) {
		wp_die($blog_id);
	}

	$ok = NestedTree\upsert_blog_path((int) $blog_id, $nested_path, $network_id);
	if (!$ok) {
		if (function_exists('wpmu_delete_blog')) {
			wpmu_delete_blog((int) $blog_id, true);
		}
		wp_die('Could not register nested path (collision or mapping error).');
	}

	wp_safe_redirect(network_admin_url('site-info.php?id=' . (int) $blog_id));
	exit;
}
add_action('admin_post_ideai_create_nested_site', __NAMESPACE__ . '\\handle_create_nested_site');

function render_site_new_integration() {
	if (!should_load_network_ui()) {
		return;
	}
	if (!current_user_can('manage_network_sites')) {
		return;
	}

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;

	echo '<h2>IdeAI: Create nested child site</h2>';
	if (!Platform\nested_tree_enabled($network_id)) {
		echo '<p><em>Nested tree is currently disabled for this network. Enable it in <strong>IdeAI → Status</strong>.</em></p>';
		return;
	}

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

function render_status_page() {
	if (!should_load_network_ui()) {
		wp_die('Network admin only.');
	}
	if (!current_user_can('manage_network_options')) {
		wp_die('Insufficient permissions.');
	}

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;
	$enabled = (bool) Platform\get_flag(Platform\FLAG_NESTED_TREE_ENABLED, false, $network_id);
	$mode = (string) Platform\get_flag(Platform\FLAG_NESTED_TREE_COLLISION_MODE, 'strict', $network_id);

	echo '<div class="wrap">';
	echo '<h1>IdeAI</h1>';

	if (isset($_GET['ideai_saved'])) {
		$ok = (string) $_GET['ideai_saved'] === '1';
		echo '<div class="notice ' . esc_attr($ok ? 'notice-success' : 'notice-error') . '"><p>' . esc_html($ok ? 'Saved.' : 'Could not save.') . '</p></div>';
	}

	echo '<h2>Feature flags (Network)</h2>';

	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="max-width: 900px; margin-top: 10px">';
	wp_nonce_field('ideai_save_flags');
	echo '<input type="hidden" name="action" value="ideai_save_flags" />';

	echo '<table class="form-table" role="presentation"><tbody>';

	echo '<tr>';
	echo '<th scope="row">Nested tree multisite</th>';
	echo '<td><label><input type="checkbox" name="ideai_nested_tree_enabled" value="1" ' . checked($enabled, true, false) . ' /> Enable nested tree routing (per-network)</label></td>';
	echo '</tr>';

	echo '<tr>';
	echo '<th scope="row">Collision mode</th>';
	echo '<td>';
	echo '<select name="ideai_nested_tree_collision_mode">';
	echo '<option value="strict"' . selected($mode, 'strict', false) . '>strict (block conflicts)</option>';
	echo '</select>';
	echo '<p class="description">Strict mode prevents creating a Page whose full path conflicts with a nested site path (and vice versa).</p>';
	echo '</td>';
	echo '</tr>';

	echo '</tbody></table>';

	submit_button('Save flags');
	echo '</form>';

	echo '</div>';
}

function render_sites_page() {
	if (!should_load_network_ui()) {
		wp_die('Network admin only.');
	}
	if (!current_user_can('manage_network_sites')) {
		wp_die('Insufficient permissions.');
	}

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;

	echo '<div class="wrap">';
	echo '<h1>IdeAI → Sites</h1>';

	echo '<p>Registered nested-site paths (per-network):</p>';
	$rows = NestedTree\list_mappings($network_id);
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
	echo '<p>Use <a class="button button-primary" href="' . esc_url(network_admin_url('site-new.php')) . '">Add New Site</a> and the IdeAI block on that page.</p>';

	echo '</div>';
}


