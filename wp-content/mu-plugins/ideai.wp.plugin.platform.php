<?php
/**
 * Plugin Name: IdeAI Platform (MU)
 * Description: IdeAI must-use platform layer (feature flags, routing hooks, etc.).
 * Version: 0.1.0
 * Author: IdeAI
 *
 * This is a loader file. WordPress MU-plugins only auto-load PHP files placed
 * directly in wp-content/mu-plugins/. We keep the real code in a subdirectory.
 */

if (!defined('ABSPATH')) {
	exit;
}

$entry = __DIR__ . '/ideai.wp.plugin.platform/ideai.wp.plugin.platform.php';
if (is_readable($entry)) {
	require_once $entry;
}


