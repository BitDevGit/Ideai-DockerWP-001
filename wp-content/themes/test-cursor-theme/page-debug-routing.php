<?php
/**
 * Template Name: Debug Routing
 * 
 * Shows all WordPress routing diagnostic information on the page
 */

get_header();

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';

use Ideai\Wp\Platform\NestedTree;

global $wpdb;

$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$test_path = $_SERVER['REQUEST_URI'];
$test_domain = $_SERVER['HTTP_HOST'];

?>
<style>
.debug-container { max-width: 1200px; margin: 20px auto; padding: 20px; font-family: monospace; font-size: 12px; }
.debug-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
.debug-section h2 { margin-top: 0; color: #0073aa; }
.debug-success { color: #00a32a; }
.debug-error { color: #d63638; }
.debug-warning { color: #dba617; }
.debug-info { color: #2271b1; }
pre { background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
table th, table td { padding: 8px; text-align: left; border: 1px solid #ddd; }
table th { background: #0073aa; color: white; }
</style>

<div class="debug-container">
    <h1>🔍 WordPress Routing Diagnostic</h1>
    <p><strong>Current URL:</strong> <?php echo esc_html($current_url); ?></p>
    <p><strong>Request Path:</strong> <?php echo esc_html($test_path); ?></p>
    <p><strong>Domain:</strong> <?php echo esc_html($test_domain); ?></p>

    <?php
    // ============================================================================
    // STEP 1: Current Site Info
    // ============================================================================
    ?>
    <div class="debug-section">
        <h2>1. Current Site Information</h2>
        <?php
        $current_blog_id = get_current_blog_id();
        $current_site = get_site();
        $current_blog_details = get_blog_details();
        
        echo "<p><strong>Current Blog ID:</strong> {$current_blog_id}</p>";
        echo "<p><strong>Site Name:</strong> " . get_option('blogname', 'N/A') . "</p>";
        echo "<p><strong>Site URL:</strong> " . get_option('siteurl', 'N/A') . "</p>";
        echo "<p><strong>Home URL:</strong> " . get_option('home', 'N/A') . "</p>";
        if ($current_site) {
            echo "<p><strong>Site Path (wp_blogs):</strong> {$current_site->path}</p>";
            echo "<p><strong>Site Domain:</strong> {$current_site->domain}</p>";
        }
        ?>
    </div>

    <?php
    // ============================================================================
    // STEP 2: Multisite Environment
    // ============================================================================
    ?>
    <div class="debug-section">
        <h2>2. Multisite Environment</h2>
        <?php
        $is_multisite = is_multisite();
        echo "<p>is_multisite(): <span class='" . ($is_multisite ? "debug-success" : "debug-error") . "'>" . ($is_multisite ? "TRUE ✅" : "FALSE ❌") . "</span></p>";
        
        if ($is_multisite) {
            $is_subdomain = is_subdomain_install();
            echo "<p>is_subdomain_install(): <span class='" . (!$is_subdomain ? "debug-success" : "debug-error") . "'>" . ($is_subdomain ? "TRUE (subdomain)" : "FALSE (subdirectory) ✅") . "</span></p>";
            
            if (function_exists('get_current_network_id')) {
                $network_id = get_current_network_id();
                echo "<p>get_current_network_id(): <span class='debug-info'>{$network_id}</span></p>";
            }
        }
        ?>
    </div>

    <?php
    // ============================================================================
    // STEP 3: Network Resolution
    // ============================================================================
    ?>
    <div class="debug-section">
        <h2>3. Network Resolution</h2>
        <?php
        if (function_exists('get_network_by_path')) {
            $network = get_network_by_path($test_domain, $test_path);
            if ($network) {
                echo "<p class='debug-success'>✅ Network found: ID={$network->id}, domain={$network->domain}, path={$network->path}</p>";
                $network_id = (int) $network->id;
            } else {
                echo "<p class='debug-error'>❌ No network found</p>";
                $network_id = 1;
            }
        } else {
            echo "<p class='debug-warning'>⚠️ get_network_by_path() not available</p>";
            $network_id = 1;
        }
        ?>
    </div>

    <?php
    // ============================================================================
    // STEP 4: Database - wp_blogs
    // ============================================================================
    ?>
    <div class="debug-section">
        <h2>4. Database: wp_blogs Table</h2>
        <?php
        $normalized_test = NestedTree\normalize_path($test_path);
        $blogs_query = $wpdb->prepare(
            "SELECT blog_id, domain, path, site_id 
             FROM {$wpdb->blogs} 
             WHERE path LIKE %s 
             ORDER BY LENGTH(path) DESC, path ASC",
            '%' . $wpdb->esc_like($normalized_test) . '%'
        );
        $blogs = $wpdb->get_results($blogs_query, ARRAY_A);
        
        if ($blogs) {
            echo "<table>";
            echo "<tr><th>Blog ID</th><th>Domain</th><th>Path</th><th>Site Name</th><th>Matches?</th></tr>";
            foreach ($blogs as $blog) {
                $matches = (strpos($normalized_test, $blog['path']) === 0) ? '✅' : '';
                switch_to_blog((int) $blog['blog_id']);
                $site_name = get_option('blogname', 'N/A');
                restore_current_blog();
                $is_current = ((int) $blog['blog_id'] === $current_blog_id) ? ' <strong>(CURRENT)</strong>' : '';
                echo "<tr>";
                echo "<td>{$blog['blog_id']}{$is_current}</td>";
                echo "<td>{$blog['domain']}</td>";
                echo "<td>{$blog['path']}</td>";
                echo "<td>{$site_name}</td>";
                echo "<td>{$matches}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='debug-warning'>⚠️ No sites found matching path</p>";
        }
        ?>
    </div>

    <?php
    // ============================================================================
    // STEP 5: Database - ideai_nested_sites
    // ============================================================================
    ?>
    <div class="debug-section">
        <h2>5. Database: ideai_nested_sites Table</h2>
        <?php
        $nested_table = $wpdb->base_prefix . 'ideai_nested_sites';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$nested_table}'") === $nested_table;
        
        if ($table_exists) {
            echo "<p class='debug-success'>✅ Table exists</p>";
            
            $mappings_query = $wpdb->prepare(
                "SELECT blog_id, path, network_id 
                 FROM {$nested_table} 
                 WHERE network_id=%d AND path LIKE %s 
                 ORDER BY LENGTH(path) DESC, path ASC",
                $network_id,
                '%' . $wpdb->esc_like($normalized_test) . '%'
            );
            $mappings = $wpdb->get_results($mappings_query, ARRAY_A);
            
            if ($mappings) {
                echo "<table>";
                echo "<tr><th>Blog ID</th><th>Path</th><th>Network ID</th><th>Is Prefix?</th></tr>";
                foreach ($mappings as $mapping) {
                    $normalized_mapping = NestedTree\normalize_path($mapping['path']);
                    $is_prefix = strpos($normalized_test, $normalized_mapping) === 0;
                    $prefix_class = $is_prefix ? 'debug-success' : '';
                    echo "<tr>";
                    echo "<td>{$mapping['blog_id']}</td>";
                    echo "<td>{$mapping['path']}</td>";
                    echo "<td>{$mapping['network_id']}</td>";
                    echo "<td class='{$prefix_class}'>" . ($is_prefix ? '✅ YES' : '❌ NO') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='debug-error'>❌ No mappings found for this path</p>";
            }
            
            // Check specifically for exact match
            $exact_match = $wpdb->get_row($wpdb->prepare(
                "SELECT blog_id, path FROM {$nested_table} WHERE network_id=%d AND path=%s",
                $network_id,
                $normalized_test
            ), ARRAY_A);
            
            if ($exact_match) {
                echo "<p class='debug-success'>✅ Exact match found: blog_id={$exact_match['blog_id']}, path={$exact_match['path']}</p>";
            } else {
                echo "<p class='debug-error'>❌ No exact match for: {$normalized_test}</p>";
            }
        } else {
            echo "<p class='debug-error'>❌ Table does not exist!</p>";
        }
        ?>
    </div>

    <?php
    // ============================================================================
    // STEP 6: Nested Tree Resolution
    // ============================================================================
    ?>
    <div class="debug-section">
        <h2>6. Nested Tree Resolution Function</h2>
        <?php
        $resolved = NestedTree\resolve_blog_for_request_path($normalized_test, $network_id);
        
        if ($resolved) {
            echo "<p class='debug-success'>✅ Resolved: blog_id={$resolved['blog_id']}, path={$resolved['path']}</p>";
            
            $resolved_site = get_site($resolved['blog_id']);
            if ($resolved_site) {
                switch_to_blog($resolved['blog_id']);
                $resolved_name = get_option('blogname', 'N/A');
                restore_current_blog();
                echo "<p><strong>Resolved Site Name:</strong> {$resolved_name}</p>";
                echo "<p><strong>Resolved Site Path (wp_blogs):</strong> {$resolved_site->path}</p>";
                
                if ($resolved['path'] !== $normalized_test) {
                    echo "<p class='debug-warning'>⚠️ WARNING: Resolved path doesn't match request path!</p>";
                    echo "<p>Requested: {$normalized_test}</p>";
                    echo "<p>Resolved: {$resolved['path']}</p>";
                }
            }
        } else {
            echo "<p class='debug-error'>❌ NOT resolved by nested tree function</p>";
        }
        ?>
    </div>

    <?php
    // ============================================================================
    // STEP 7: WordPress Core Resolution
    // ============================================================================
    ?>
    <div class="debug-section">
        <h2>7. WordPress Core Resolution</h2>
        <?php
        if (function_exists('get_site_by_path')) {
            $core_site = get_site_by_path($test_domain, $test_path);
            
            if ($core_site) {
                switch_to_blog($core_site->blog_id);
                $core_name = get_option('blogname', 'N/A');
                restore_current_blog();
                
                echo "<p class='debug-info'>Core resolved: blog_id={$core_site->blog_id}, path={$core_site->path}</p>";
                echo "<p><strong>Core Site Name:</strong> {$core_name}</p>";
                
                if ($core_site->path !== $normalized_test) {
                    echo "<p class='debug-warning'>⚠️ WARNING: Core resolved to different path!</p>";
                    echo "<p>Requested: {$normalized_test}</p>";
                    echo "<p>Core resolved: {$core_site->path}</p>";
                }
                
                if ($core_site->blog_id !== $current_blog_id) {
                    echo "<p class='debug-error'>❌ MISMATCH: Core resolved to blog_id {$core_site->blog_id}, but current blog is {$current_blog_id}</p>";
                }
            } else {
                echo "<p class='debug-warning'>⚠️ Core couldn't resolve</p>";
            }
        } else {
            echo "<p class='debug-warning'>⚠️ get_site_by_path() not available</p>";
        }
        ?>
    </div>

    <?php
    // ============================================================================
    // STEP 8: Filter Hooks
    // ============================================================================
    ?>
    <div class="debug-section">
        <h2>8. Filter Hooks</h2>
        <?php
        $filters = $GLOBALS['wp_filter']['pre_get_site_by_path'] ?? null;
        if ($filters) {
            echo "<p class='debug-success'>✅ Filter 'pre_get_site_by_path' is registered</p>";
            echo "<table>";
            echo "<tr><th>Priority</th><th>Function</th></tr>";
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
                    echo "<tr><td>{$priority}</td><td>{$function_name}</td></tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<p class='debug-error'>❌ Filter 'pre_get_site_by_path' NOT registered!</p>";
        }
        ?>
    </div>

    <?php
    // ============================================================================
    // SUMMARY
    // ============================================================================
    ?>
    <div class="debug-section" style="background: #fff3cd; border-left-color: #dba617;">
        <h2>📊 Summary</h2>
        <?php
        $grandchild_exists = isset($exact_match) && $exact_match;
        $grandchild_resolved = isset($resolved) && $resolved && $resolved['path'] === $normalized_test;
        $filter_registered = isset($filters) && $filters;
        
        echo "<p>1. Exact match exists in nested_sites: " . ($grandchild_exists ? "<span class='debug-success'>✅ YES</span>" : "<span class='debug-error'>❌ NO</span>") . "</p>";
        echo "<p>2. resolve_blog_for_request_path finds match: " . ($grandchild_resolved ? "<span class='debug-success'>✅ YES</span>" : "<span class='debug-error'>❌ NO</span>") . "</p>";
        echo "<p>3. Filter hook is registered: " . ($filter_registered ? "<span class='debug-success'>✅ YES</span>" : "<span class='debug-error'>❌ NO</span>") . "</p>";
        echo "<p>4. Current blog matches resolved: " . (($resolved && $resolved['blog_id'] === $current_blog_id) ? "<span class='debug-success'>✅ YES</span>" : "<span class='debug-error'>❌ NO</span>") . "</p>";
        
        if (!$grandchild_exists) {
            echo "<p class='debug-error'><strong>🔧 FIX NEEDED:</strong> Site is missing from ideai_nested_sites table!</p>";
        } elseif (!$grandchild_resolved) {
            echo "<p class='debug-error'><strong>🔧 FIX NEEDED:</strong> resolve_blog_for_request_path is not finding the site!</p>";
        } elseif (!$filter_registered) {
            echo "<p class='debug-error'><strong>🔧 FIX NEEDED:</strong> Filter hook is not registered!</p>";
        } elseif ($resolved && $resolved['blog_id'] !== $current_blog_id) {
            echo "<p class='debug-error'><strong>🔧 FIX NEEDED:</strong> Filter is not overriding WordPress resolution!</p>";
        } else {
            echo "<p class='debug-success'><strong>✅ All checks passed - routing should work correctly!</strong></p>";
        }
        ?>
    </div>
</div>

<?php
get_footer();

