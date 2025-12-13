<?php
/**
 * Nested tree multisite: collision prevention (nested sites vs Pages).
 *
 * Policy (strict):
 * - You cannot create a Page whose permalink path equals a nested-site path.
 * - You cannot create a nested site at a path where a Page already exists (handled in upsert).
 */

namespace Ideai\Wp\Platform\NestedTreeCollisions;

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

function notice_key($user_id) {
	return 'ideai_nested_tree_collision_' . (int) $user_id;
}

function add_notice_for_user($message) {
	if (!function_exists('get_current_user_id')) {
		return;
	}
	$user_id = (int) get_current_user_id();
	if ($user_id <= 0) {
		return;
	}
	set_transient(notice_key($user_id), (string) $message, 60);
}

function maybe_show_notice() {
	if (!function_exists('get_current_user_id')) {
		return;
	}
	$user_id = (int) get_current_user_id();
	if ($user_id <= 0) {
		return;
	}
	$msg = get_transient(notice_key($user_id));
	if (!$msg) {
		return;
	}
	delete_transient(notice_key($user_id));
	echo '<div class="notice notice-error"><p>' . esc_html($msg) . '</p></div>';
}
add_action('admin_notices', __NAMESPACE__ . '\\maybe_show_notice');

/**
 * If a Page is being published and it collides with a nested site path, revert to draft.
 */
function prevent_publish_collision($new_status, $old_status, $post) {
	if ($new_status !== 'publish') {
		return;
	}
	if (!$post || empty($post->ID) || empty($post->post_type) || $post->post_type !== 'page') {
		return;
	}
	if (!is_subdirectory_multisite()) {
		return;
	}
	if (!function_exists('get_current_network_id')) {
		return;
	}

	$network_id = (int) get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}

	$collision_mode = Platform\get_flag(Platform\FLAG_NESTED_TREE_COLLISION_MODE, 'strict', $network_id);
	if ($collision_mode !== 'strict') {
		return;
	}

	$permalink = get_permalink($post->ID);
	if (!$permalink) {
		return;
	}
	$path = wp_parse_url($permalink, PHP_URL_PATH);
	if (!is_string($path) || $path === '') {
		return;
	}

	$path = NestedTree\normalize_path($path);
	$hit = NestedTree\resolve_blog_for_request_path($path, $network_id);
	if (!$hit || empty($hit['path']) || $hit['path'] !== $path) {
		// Only block exact collisions.
		return;
	}

	// Revert to draft (guard against recursion).
	remove_action('transition_post_status', __NAMESPACE__ . '\\prevent_publish_collision', 10);
	wp_update_post(array('ID' => (int) $post->ID, 'post_status' => 'draft'));
	add_action('transition_post_status', __NAMESPACE__ . '\\prevent_publish_collision', 10, 3);

	add_notice_for_user('Publish blocked: a nested site already exists at ' . $path . ' (rename the page or delete/move the nested site).');
	Platform\log_msg('nested_tree collision: blocked page publish', array(
		'network_id' => $network_id,
		'page_id' => (int) $post->ID,
		'path' => $path,
		'nested_blog_id' => (int) $hit['blog_id'],
	));
}

add_action('transition_post_status', __NAMESPACE__ . '\\prevent_publish_collision', 10, 3);


