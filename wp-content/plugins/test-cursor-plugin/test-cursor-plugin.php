<?php
/**
 * Plugin Name: Test Cursor Plugin
 * Plugin URI: https://example.com
 * Description: Test plugin to verify wp-content deployment. Adds admin notice and footer text.
 * Version: 1.0.0
 * Author: Cursor Test
 * Author URI: https://example.com
 * License: GPL v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin notice
function test_cursor_plugin_admin_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong>Test Cursor Plugin</strong> is active! ✅</p>
        <p>This confirms wp-content deployment is working.</p>
    </div>
    <?php
}
add_action('admin_notices', 'test_cursor_plugin_admin_notice');

// Add footer text
function test_cursor_plugin_footer_text($text) {
    return $text . ' | Test Cursor Plugin Active ✅';
}
add_filter('admin_footer_text', 'test_cursor_plugin_footer_text');

// Add dashboard widget
function test_cursor_plugin_dashboard_widget() {
    wp_add_dashboard_widget(
        'test_cursor_widget',
        'Test Cursor Plugin Status',
        'test_cursor_plugin_widget_content'
    );
}
add_action('wp_dashboard_setup', 'test_cursor_plugin_dashboard_widget');

function test_cursor_plugin_widget_content() {
    ?>
    <div style="padding: 10px;">
        <h3>✅ Plugin Active</h3>
        <p>This widget confirms that:</p>
        <ul>
            <li>Plugin is loaded from wp-content/plugins</li>
            <li>WordPress can access wp-content directory</li>
            <li>Deployment was successful</li>
        </ul>
        <p><strong>Status:</strong> <span style="color: green;">Working!</span></p>
    </div>
    <?php
}

// Log activation
register_activation_hook(__FILE__, 'test_cursor_plugin_activate');
function test_cursor_plugin_activate() {
    error_log('Test Cursor Plugin activated at ' . date('Y-m-d H:i:s'));
}



