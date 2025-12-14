<?php
/**
 * Front Page Template with Full Debugging
 * 
 * This template shows all WordPress routing and site information
 * for debugging nested multisite routing.
 */

get_header();

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$test_path = $_SERVER['REQUEST_URI'];
$test_domain = $_SERVER['HTTP_HOST'];
$current_blog_id = get_current_blog_id();
$current_site = get_site();
$current_blog_details = get_blog_details();

?>
<style>
.debug-container { max-width: 1400px; margin: 20px auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.debug-section { background: #f8f9fa; padding: 20px; margin: 15px 0; border-left: 5px solid #0073aa; border-radius: 4px; }
.debug-section h2 { margin-top: 0; color: #0073aa; font-size: 1.5em; }
.debug-success { color: #00a32a; font-weight: bold; }
.debug-error { color: #d63638; font-weight: bold; }
.debug-warning { color: #dba617; font-weight: bold; }
.debug-info { color: #2271b1; font-weight: bold; }
pre { background: #fff; padding: 15px; border: 1px solid #ddd; overflow-x: auto; border-radius: 4px; font-size: 12px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; background: white; }
table th, table td { padding: 12px; text-align: left; border: 1px solid #ddd; }
table th { background: #0073aa; color: white; font-weight: bold; }
table tr:nth-child(even) { background: #f9f9f9; }
.code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
.homepage-content { background: #e6f7ff; padding: 30px; margin: 20px 0; border-radius: 8px; border: 2px solid #3399ff; }
.homepage-content h1 { color: #333; margin-top: 0; }
</style>

<div class="debug-container">
    <div style="background: #00a32a; color: white; padding: 20px; margin: 20px 0; border-radius: 8px;">
        <h1 style="margin: 0; color: white;">✅ FRONT-PAGE.PHP TEMPLATE LOADED!</h1>
        <p style="margin: 10px 0 0 0;">This confirms the debug template is working.</p>
    </div>
    
    <div class="homepage-content">
        <h1>🌳 <?php echo esc_html(get_option('blogname', 'Site')); ?></h1>
        <?php
        // Force load homepage content regardless of WordPress query
        $homepage_id = get_option('page_on_front');
        if ($homepage_id) {
            $homepage = get_post($homepage_id);
            if ($homepage && $homepage->post_status === 'publish') {
                echo '<div style="margin-top: 20px; font-size: 1.1em; line-height: 1.6;">';
                echo apply_filters('the_content', $homepage->post_content);
                echo '</div>';
            } else {
                echo '<p style="color: #d63638;">⚠️ Homepage page (ID: ' . $homepage_id . ') not found or not published.</p>';
            }
        } else {
            echo '<p style="color: #666;">Homepage content will be displayed here once configured.</p>';
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>📍 Current Site Information</h2>
        <table>
            <tr><th>Property</th><th>Value</th></tr>
            <tr><td>Current Blog ID</td><td><span class="code"><?php echo $current_blog_id; ?></span></td></tr>
            <tr><td>Site Name (blogname)</td><td><strong><?php echo esc_html(get_option('blogname', 'N/A')); ?></strong></td></tr>
            <tr><td>Site URL</td><td><?php echo esc_html(get_option('siteurl', 'N/A')); ?></td></tr>
            <tr><td>Home URL</td><td><?php echo esc_html(get_option('home', 'N/A')); ?></td></tr>
            <tr><td>Site Path (wp_blogs)</td><td><span class="code"><?php echo esc_html($current_site->path ?? '/'); ?></span></td></tr>
            <tr><td>Site Domain</td><td><?php echo esc_html($current_site->domain ?? 'N/A'); ?></td></tr>
            <tr><td>Current URL</td><td><?php echo esc_html($current_url); ?></td></tr>
            <tr><td>Request Path</td><td><span class="code"><?php echo esc_html($test_path); ?></span></td></tr>
        </table>
    </div>

    <?php
    // Multisite Environment
    ?>
    <div class="debug-section">
        <h2>🌐 Multisite Environment</h2>
        <table>
            <tr><th>Check</th><th>Result</th></tr>
            <tr>
                <td>is_multisite()</td>
                <td><span class="<?php echo is_multisite() ? 'debug-success' : 'debug-error'; ?>">
                    <?php echo is_multisite() ? '✅ TRUE' : '❌ FALSE'; ?>
                </span></td>
            </tr>
            <tr>
                <td>is_subdomain_install()</td>
                <td><span class="<?php echo !is_subdomain_install() ? 'debug-success' : 'debug-error'; ?>">
                    <?php echo is_subdomain_install() ? 'Subdomain' : '✅ Subdirectory'; ?>
                </span></td>
            </tr>
            <?php if (function_exists('get_current_network_id')): ?>
            <tr>
                <td>get_current_network_id()</td>
                <td><span class="code"><?php echo get_current_network_id(); ?></span></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <?php
    // Network Resolution
    $network_id = 1;
    if (function_exists('get_network_by_path')) {
        $network = get_network_by_path($test_domain, $test_path);
        if ($network) {
            $network_id = (int) $network->id;
        }
    }
    ?>

    <?php
    // Nested Sites Table
    ?>
    <div class="debug-section">
        <h2>🗺️ Nested Sites Mapping</h2>
        <?php
        $nested_table = $wpdb->base_prefix . 'ideai_nested_sites';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$nested_table}'") === $nested_table;
        
        if ($table_exists) {
            $normalized_test = NestedTree\normalize_path($test_path);
            
            // Get current site's nested path
            $current_nested_path = NestedTree\get_blog_path($current_blog_id, $network_id);
            
            echo '<p><strong>Current Site Nested Path:</strong> ';
            if ($current_nested_path) {
                echo '<span class="code debug-success">' . esc_html($current_nested_path) . ' ✅</span>';
            } else {
                echo '<span class="debug-error">NOT SET ❌</span>';
            }
            echo '</p>';
            
            // Get all related mappings
            $mappings = $wpdb->get_results($wpdb->prepare(
                "SELECT blog_id, path FROM {$nested_table} 
                 WHERE network_id=%d AND path LIKE %s 
                 ORDER BY LENGTH(path) DESC",
                $network_id,
                '%' . $wpdb->esc_like($normalized_test) . '%'
            ), ARRAY_A);
            
            if ($mappings) {
                echo '<table>';
                echo '<tr><th>Blog ID</th><th>Nested Path</th><th>Site Name</th><th>Is Current?</th></tr>';
                foreach ($mappings as $mapping) {
                    $is_current = ((int) $mapping['blog_id'] === $current_blog_id);
                    switch_to_blog((int) $mapping['blog_id']);
                    $site_name = get_option('blogname', 'N/A');
                    restore_current_blog();
                    echo '<tr>';
                    echo '<td>' . $mapping['blog_id'] . ($is_current ? ' <strong>(CURRENT)</strong>' : '') . '</td>';
                    echo '<td><span class="code">' . esc_html($mapping['path']) . '</span></td>';
                    echo '<td>' . esc_html($site_name) . '</td>';
                    echo '<td>' . ($is_current ? '<span class="debug-success">✅ YES</span>' : '❌') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="debug-warning">⚠️ No nested mappings found for this path</p>';
            }
        } else {
            echo '<p class="debug-error">❌ Nested sites table does not exist!</p>';
        }
        ?>
    </div>

    <?php
    // Resolution Test
    ?>
    <div class="debug-section">
        <h2>🔍 Resolution Test</h2>
        <?php
        $normalized_test = NestedTree\normalize_path($test_path);
        $resolved = NestedTree\resolve_blog_for_request_path($normalized_test, $network_id);
        
        echo '<table>';
        echo '<tr><th>Test</th><th>Result</th></tr>';
        echo '<tr><td>Request Path (normalized)</td><td><span class="code">' . esc_html($normalized_test) . '</span></td></tr>';
        
        if ($resolved) {
            echo '<tr><td>resolve_blog_for_request_path()</td><td>';
            echo '<span class="debug-success">✅ Resolved: blog_id=' . $resolved['blog_id'] . ', path=' . esc_html($resolved['path']) . '</span>';
            echo '</td></tr>';
            
            if ($resolved['blog_id'] === $current_blog_id) {
                echo '<tr><td>Matches Current Blog</td><td><span class="debug-success">✅ YES - Correct!</span></td></tr>';
            } else {
                echo '<tr><td>Matches Current Blog</td><td><span class="debug-error">❌ NO - Should be blog_id ' . $resolved['blog_id'] . '</span></td></tr>';
            }
        } else {
            echo '<tr><td>resolve_blog_for_request_path()</td><td><span class="debug-error">❌ NOT RESOLVED</span></td></tr>';
        }
        
        // WordPress core resolution
        if (function_exists('get_site_by_path')) {
            $core_site = get_site_by_path($test_domain, $test_path);
            if ($core_site) {
                echo '<tr><td>WordPress Core Resolution</td><td>';
                echo 'blog_id=' . $core_site->blog_id . ', path=' . esc_html($core_site->path);
                if ($core_site->blog_id === $current_blog_id) {
                    echo ' <span class="debug-success">✅</span>';
                } else {
                    echo ' <span class="debug-error">❌ MISMATCH</span>';
                }
                echo '</td></tr>';
            }
        }
        
        echo '</table>';
        ?>
    </div>

    <?php
    // Filter Hooks
    ?>
    <div class="debug-section">
        <h2>🔗 Filter Hooks</h2>
        <?php
        $filters = $GLOBALS['wp_filter']['pre_get_site_by_path'] ?? null;
        if ($filters) {
            echo '<p class="debug-success">✅ Filter "pre_get_site_by_path" is registered</p>';
            echo '<table>';
            echo '<tr><th>Priority</th><th>Function</th></tr>';
            foreach ($filters->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $function_name = 'unknown';
                    if (is_array($callback['function'])) {
                        $function_name = (is_object($callback['function'][0]) 
                            ? get_class($callback['function'][0]) 
                            : $callback['function'][0]) . '::' . $callback['function'][1];
                    } elseif (is_string($callback['function'])) {
                        $function_name = $callback['function'];
                    } elseif (is_object($callback['function'])) {
                        $function_name = 'Closure';
                    }
                    $is_ours = strpos($function_name, 'NestedTreeRouting') !== false;
                    echo '<tr>';
                    echo '<td>' . $priority . '</td>';
                    echo '<td>' . ($is_ours ? '<strong>' : '') . esc_html($function_name) . ($is_ours ? '</strong> ✅' : '') . '</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
        } else {
            echo '<p class="debug-error">❌ Filter "pre_get_site_by_path" NOT registered!</p>';
        }
        ?>
    </div>

    <?php
    // All Related Sites
    ?>
    <div class="debug-section">
        <h2>📋 All Related Sites (wp_blogs)</h2>
        <?php
        $normalized_test = NestedTree\normalize_path($test_path);
        $blogs = $wpdb->get_results($wpdb->prepare(
            "SELECT blog_id, domain, path FROM {$wpdb->blogs} 
             WHERE path LIKE %s 
             ORDER BY LENGTH(path) DESC",
            '%' . $wpdb->esc_like($normalized_test) . '%'
        ), ARRAY_A);
        
        if ($blogs) {
            echo '<table>';
            echo '<tr><th>Blog ID</th><th>Domain</th><th>Path</th><th>Site Name</th><th>Is Current?</th></tr>';
            foreach ($blogs as $blog) {
                $is_current = ((int) $blog['blog_id'] === $current_blog_id);
                switch_to_blog((int) $blog['blog_id']);
                $site_name = get_option('blogname', 'N/A');
                restore_current_blog();
                echo '<tr>';
                echo '<td>' . $blog['blog_id'] . ($is_current ? ' <strong>(CURRENT)</strong>' : '') . '</td>';
                echo '<td>' . esc_html($blog['domain']) . '</td>';
                echo '<td><span class="code">' . esc_html($blog['path']) . '</span></td>';
                echo '<td>' . esc_html($site_name) . '</td>';
                echo '<td>' . ($is_current ? '<span class="debug-success">✅ YES</span>' : '❌') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="debug-warning">⚠️ No sites found</p>';
        }
        ?>
    </div>

    <?php
    // Summary
    ?>
    <div class="debug-section" style="background: #fff3cd; border-left-color: #dba617;">
        <h2>📊 Summary</h2>
        <?php
        $current_nested_path = NestedTree\get_blog_path($current_blog_id, $network_id);
        $resolved = NestedTree\resolve_blog_for_request_path($normalized_test, $network_id);
        $filter_registered = isset($filters) && $filters;
        $correct_blog = $resolved && $resolved['blog_id'] === $current_blog_id;
        
        echo '<table>';
        echo '<tr><th>Check</th><th>Status</th></tr>';
        echo '<tr><td>Nested path set for current site</td><td>' . 
            ($current_nested_path ? '<span class="debug-success">✅ YES (' . esc_html($current_nested_path) . ')</span>' : '<span class="debug-error">❌ NO</span>') . 
            '</td></tr>';
        echo '<tr><td>resolve_blog_for_request_path finds match</td><td>' . 
            ($resolved ? '<span class="debug-success">✅ YES (blog_id=' . $resolved['blog_id'] . ')</span>' : '<span class="debug-error">❌ NO</span>') . 
            '</td></tr>';
        echo '<tr><td>Filter hook registered</td><td>' . 
            ($filter_registered ? '<span class="debug-success">✅ YES</span>' : '<span class="debug-error">❌ NO</span>') . 
            '</td></tr>';
        echo '<tr><td>Current blog matches resolved</td><td>' . 
            ($correct_blog ? '<span class="debug-success">✅ YES - Routing Working!</span>' : '<span class="debug-error">❌ NO - Routing Issue!</span>') . 
            '</td></tr>';
        echo '</table>';
        
        if (!$current_nested_path) {
            echo '<p class="debug-error"><strong>🔧 FIX NEEDED:</strong> Current site missing from nested_sites table!</p>';
        } elseif (!$resolved) {
            echo '<p class="debug-error"><strong>🔧 FIX NEEDED:</strong> resolve_blog_for_request_path not finding site!</p>';
        } elseif (!$filter_registered) {
            echo '<p class="debug-error"><strong>🔧 FIX NEEDED:</strong> Filter hook not registered!</p>';
        } elseif (!$correct_blog) {
            echo '<p class="debug-error"><strong>🔧 FIX NEEDED:</strong> Filter not overriding WordPress resolution!</p>';
        } else {
            echo '<p class="debug-success"><strong>✅ All checks passed - Routing is working correctly!</strong></p>';
        }
        ?>
    </div>
</div>

<?php
get_footer();

