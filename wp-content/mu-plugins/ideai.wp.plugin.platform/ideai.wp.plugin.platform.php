<?php
/**
 * IdeAI WP Platform (MU-plugin) core.
 *
 * Keep this file side-effect-light: define helpers and register hooks, but do not
 * change WordPress behavior unless feature flags are enabled.
 */

namespace Ideai\Wp\Platform;

if (!defined('ABSPATH')) {
	exit;
}

const VERSION = '0.1.0';

/**
 * Feature flags (per-network).
 *
 * IMPORTANT: Keep defaults safe (disabled).
 */
const FLAG_NESTED_TREE_ENABLED = 'ideai_nested_tree_enabled';
const FLAG_NESTED_TREE_COLLISION_MODE = 'ideai_nested_tree_collision_mode';

function flag_defaults() {
	return array(
		FLAG_NESTED_TREE_ENABLED => false,
		FLAG_NESTED_TREE_COLLISION_MODE => 'strict',
	);
}

require_once __DIR__ . '/includes/nested-tree.php';

/**
 * Returns true when debug logging is enabled for the platform layer.
 *
 * Enable by setting:
 * - define('IDEAI_WP_PLATFORM_DEBUG', true); or
 * - IDEAI_WP_PLATFORM_DEBUG=1 env var
 */
function is_debug_enabled() {
	if (defined('IDEAI_WP_PLATFORM_DEBUG')) {
		return (bool) IDEAI_WP_PLATFORM_DEBUG;
	}
	$env = getenv('IDEAI_WP_PLATFORM_DEBUG');
	return $env === '1' || $env === 'true';
}

/**
 * Lightweight logger (error_log) to avoid dependencies.
 *
 * @param string $message
 * @param array  $context
 */
function log_msg($message, array $context = array()) {
	if (!is_debug_enabled()) {
		return;
	}
	$line = '[ideai.platform] ' . (string) $message;
	if (!empty($context)) {
		$line .= ' ' . wp_json_encode($context);
	}
	error_log($line);
}

/**
 * Get a per-network option with a sane fallback for non-multisite.
 *
 * @param string   $key
 * @param mixed    $default
 * @param int|null $network_id
 * @return mixed
 */
function get_flag($key, $default = null, $network_id = null) {
	$defaults = flag_defaults();
	if ($default === null && array_key_exists($key, $defaults)) {
		$default = $defaults[$key];
	}
	if (function_exists('is_multisite') && is_multisite()) {
		if ($network_id === null && function_exists('get_current_network_id')) {
			$network_id = get_current_network_id();
		}
		// get_network_option handles null network id in modern WP, but we keep it explicit.
		return get_network_option($network_id, $key, $default);
	}
	return get_option($key, $default);
}

/**
 * Set a per-network option with a sane fallback for non-multisite.
 *
 * @param string   $key
 * @param mixed    $value
 * @param int|null $network_id
 * @return bool
 */
function set_flag($key, $value, $network_id = null) {
	if (function_exists('is_multisite') && is_multisite()) {
		if ($network_id === null && function_exists('get_current_network_id')) {
			$network_id = get_current_network_id();
		}
		return (bool) update_network_option($network_id, $key, $value);
	}
	return (bool) update_option($key, $value);
}

function nested_tree_enabled($network_id = null) {
	return (bool) get_flag(FLAG_NESTED_TREE_ENABLED, false, $network_id);
}


/**
 * True if the platform MU-plugin is loaded.
 *
 * Tooling plugins can call this to safely detect platform availability.
 */
function is_loaded() {
	return true;
}

/**
 * Boot hook: currently no behavior changes; just emits a debug line.
 */
function bootstrap() {
	log_msg('loaded', array('version' => VERSION));
}
add_action('muplugins_loaded', __NAMESPACE__ . '\\bootstrap', 1);


