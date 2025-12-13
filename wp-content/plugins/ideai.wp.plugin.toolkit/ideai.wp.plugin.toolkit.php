<?php
/**
 * Plugin Name: IdeAI Toolkit (Deprecated)
 * Description: Deprecated. IdeAI Network Admin UI has moved to the MU-plugin ideai.wp.plugin.platform. This plugin is now a no-op.
 * Version: 0.1.1
 * Author: IdeAI
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('admin_notices', function () {
	if (!current_user_can('manage_network_options')) {
		return;
	}
	if (!is_multisite() || !is_network_admin()) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>IdeAI Toolkit is deprecated.</strong> Use the IdeAI menu provided by the MU-plugin <code>ideai.wp.plugin.platform</code>.</p></div>';
});


