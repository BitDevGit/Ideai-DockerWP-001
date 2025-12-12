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

	if (!platform_available()) {
		echo '<p><strong>Note:</strong> Advanced routing features (nested tree multisite) require the MU-plugin <code>ideai.wp.plugin.platform</code>. Toolkit remains safe without it.</p>';
	}

	echo '</div>';
}


