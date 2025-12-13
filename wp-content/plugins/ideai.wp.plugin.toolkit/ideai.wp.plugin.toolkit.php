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
}
add_action('network_admin_menu', __NAMESPACE__ . '\\register_network_menu');
add_action('network_admin_init', __NAMESPACE__ . '\\maybe_handle_network_post');

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


