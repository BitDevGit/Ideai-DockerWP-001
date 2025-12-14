<?php
/**
 * API endpoint to get all nested sites for a network
 * 
 * Usage: /wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-sites-api.php?network_id=1
 */

// Load WordPress
$wp_load = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
if (file_exists($wp_load)) {
    require_once $wp_load;
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'WordPress not found'));
    exit;
}

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$network_id = isset($_GET['network_id']) ? (int) $_GET['network_id'] : 1;

if (!is_multisite()) {
    http_response_code(400);
    echo json_encode(array('error' => 'Not a multisite installation'));
    exit;
}

global $wpdb;
$nested_table = NestedTree\table_name();
$sites = $wpdb->get_results($wpdb->prepare(
    'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
    $network_id
), ARRAY_A);

$output = array();

foreach ($sites as $site) {
    switch_to_blog($site['blog_id']);
    $name = get_option('blogname');
    $depth = NestedTree\get_site_depth($site['path']);
    $url = get_option('home');
    
    $output[] = array(
        'blog_id' => (int) $site['blog_id'],
        'path' => $site['path'],
        'name' => $name,
        'level' => $depth,
        'url' => $url
    );
    
    restore_current_blog();
}

echo json_encode($output, JSON_PRETTY_PRINT);

