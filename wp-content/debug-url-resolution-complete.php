<?php
/**
 * COMPREHENSIVE URL RESOLUTION DIAGNOSTIC
 * 
 * Uses every WordPress function available to debug nested site routing.
 * Tests the actual URL resolution process step by step.
 * 
 * Usage: wp eval-file wp-content/_usefultools/debug-url-resolution-complete.php
 */

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    require_once dirname(__FILE__) . '/../../../../wp-load.php';
}

require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';
require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-routing.php';

use Ideai\Wp\Platform\NestedTree;
use Ideai\Wp\Platform\NestedTreeRouting;

global $wpdb;

$test_url = 'https://site3.localwp/parent1/child2/grandchild2/';
$test_domain = 'site3.localwp';
$test_path = '/parent1/child2/grandchild2/';

echo "🔍 COMPREHENSIVE URL RESOLUTION DIAGNOSTIC\n";
echo str_repeat("=", 70) . "\n\n";

// ============================================================================
// STEP 1: WordPress Environment Check
// ============================================================================
echo "STEP 1: WordPress Environment\n";
echo str_repeat("-", 70) . "\n";

echo "1.1 Multisite Check:\n";
$is_multisite = is_multisite();
echo "   is_multisite() = " . ($is_multisite ? "TRUE ✅" : "FALSE ❌") . "\n";

if ($is_multisite) {
    echo "1.2 Subdirectory Install:\n";
    $is_subdomain = is_subdomain_install();
    echo "   is_subdomain_install() = " . ($is_subdomain ? "TRUE (subdomain)" : "FALSE (subdirectory) ✅") . "\n";
    
    echo "1.3 Current Network:\n";
    if (function_exists('get_current_network_id')) {
        $network_id = get_current_network_id();
        echo "   get_current_network_id() = {$network_id}\n";
    }
    
    echo "1.4 Current Site:\n";
    if (function_exists('get_current_site')) {
        $current_site = get_current_site();
        if ($current_site) {
            echo "   domain = {$current_site->domain}\n";
            echo "   path = {$current_site->path}\n";
        }
    }
    
    if (function_exists('get_current_blog_id')) {
        $current_blog_id = get_current_blog_id();
        echo "   get_current_blog_id() = {$current_blog_id}\n";
    }
}

echo "\n";

// ============================================================================
// STEP 2: Network Resolution
// ============================================================================
echo "STEP 2: Network Resolution\n";
echo str_repeat("-", 70) . "\n";

if (function_exists('get_network_by_path')) {
    echo "2.1 get_network_by_path('{$test_domain}', '{$test_path}'):\n";
    $network = get_network_by_path($test_domain, $test_path);
    if ($network) {
        echo "   ✅ Network found: ID={$network->id}, domain={$network->domain}, path={$network->path}\n";
        $network_id = (int) $network->id;
    } else {
        echo "   ❌ No network found\n";
        $network_id = 1; // Fallback
    }
} else {
    echo "   ⚠️  get_network_by_path() not available\n";
    $network_id = 1;
}

echo "\n";

// ============================================================================
// STEP 3: Database State - wp_blogs
// ============================================================================
echo "STEP 3: Database State (wp_blogs)\n";
echo str_repeat("-", 70) . "\n";

echo "3.1 All sites with 'parent1' in path:\n";
$blogs_query = $wpdb->prepare(
    "SELECT blog_id, domain, path, site_id 
     FROM {$wpdb->blogs} 
     WHERE path LIKE %s 
     ORDER BY LENGTH(path) DESC, path ASC",
    '%parent1%'
);
$blogs = $wpdb->get_results($blogs_query, ARRAY_A);

if ($blogs) {
    foreach ($blogs as $blog) {
        $matches = (strpos($test_path, $blog['path']) === 0) ? '✅ MATCHES' : '';
        echo "   blog_id={$blog['blog_id']}, path={$blog['path']}, domain={$blog['domain']} {$matches}\n";
        
        // Get site name
        switch_to_blog((int) $blog['blog_id']);
        $site_name = get_option('blogname', 'N/A');
        restore_current_blog();
        echo "      → Site name: {$site_name}\n";
    }
} else {
    echo "   ❌ No sites found\n";
}

echo "\n";

// ============================================================================
// STEP 4: Database State - ideai_nested_sites
// ============================================================================
echo "STEP 4: Database State (ideai_nested_sites)\n";
echo str_repeat("-", 70) . "\n";

$nested_table = $wpdb->base_prefix . 'ideai_nested_sites';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$nested_table}'") === $nested_table;

if ($table_exists) {
    echo "4.1 Table exists ✅\n";
    
    echo "4.2 All mappings with 'parent1' in path:\n";
    $mappings_query = $wpdb->prepare(
        "SELECT blog_id, path, network_id 
         FROM {$nested_table} 
         WHERE network_id=%d AND path LIKE %s 
         ORDER BY LENGTH(path) DESC, path ASC",
        $network_id,
        '%parent1%'
    );
    $mappings = $wpdb->get_results($mappings_query, ARRAY_A);
    
    if ($mappings) {
        foreach ($mappings as $mapping) {
            $normalized_test = NestedTree\normalize_path($test_path);
            $normalized_mapping = NestedTree\normalize_path($mapping['path']);
            $is_prefix = strpos($normalized_test, $normalized_mapping) === 0;
            $matches = $is_prefix ? '✅ PREFIX MATCH' : '';
            echo "   blog_id={$mapping['blog_id']}, path={$mapping['path']} {$matches}\n";
        }
    } else {
        echo "   ❌ No mappings found\n";
    }
    
    // Check specifically for grandchild2
    echo "\n4.3 Specific check for grandchild2:\n";
    $grandchild_path = NestedTree\normalize_path('/parent1/child2/grandchild2/');
    $grandchild_check = $wpdb->get_row($wpdb->prepare(
        "SELECT blog_id, path FROM {$nested_table} WHERE network_id=%d AND path=%s",
        $network_id,
        $grandchild_path
    ), ARRAY_A);
    
    if ($grandchild_check) {
        echo "   ✅ Grandchild2 EXISTS: blog_id={$grandchild_check['blog_id']}, path={$grandchild_check['path']}\n";
    } else {
        echo "   ❌ Grandchild2 DOES NOT EXIST in nested_sites table!\n";
    }
} else {
    echo "4.1 ❌ Table does not exist!\n";
}

echo "\n";

// ============================================================================
// STEP 5: Nested Tree Resolution Function
// ============================================================================
echo "STEP 5: Nested Tree Resolution Function\n";
echo str_repeat("-", 70) . "\n";

$normalized_path = NestedTree\normalize_path($test_path);
echo "5.1 Normalized path: {$normalized_path}\n";

echo "5.2 resolve_blog_for_request_path('{$normalized_path}', {$network_id}):\n";
$resolved = NestedTree\resolve_blog_for_request_path($normalized_path, $network_id);

if ($resolved) {
    echo "   ✅ Resolved: blog_id={$resolved['blog_id']}, path={$resolved['path']}\n";
    
    // Verify the resolved site
    $resolved_site = get_site($resolved['blog_id']);
    if ($resolved_site) {
        echo "   Site details:\n";
        echo "      - blog_id: {$resolved_site->blog_id}\n";
        echo "      - domain: {$resolved_site->domain}\n";
        echo "      - path: {$resolved_site->path}\n";
        
        switch_to_blog($resolved['blog_id']);
        $resolved_name = get_option('blogname', 'N/A');
        restore_current_blog();
        echo "      - name: {$resolved_name}\n";
        
        if ($resolved['path'] !== $normalized_path) {
            echo "   ⚠️  WARNING: Resolved path doesn't match request path!\n";
            echo "      Requested: {$normalized_path}\n";
            echo "      Resolved:  {$resolved['path']}\n";
        }
    }
} else {
    echo "   ❌ NOT resolved by nested tree function\n";
}

echo "\n";

// ============================================================================
// STEP 6: WordPress Core Site Resolution
// ============================================================================
echo "STEP 6: WordPress Core Site Resolution\n";
echo str_repeat("-", 70) . "\n";

if (function_exists('get_site_by_path')) {
    echo "6.1 get_site_by_path('{$test_domain}', '{$test_path}'):\n";
    
    // Temporarily remove our filter to see what core does
    $core_site = get_site_by_path($test_domain, $test_path);
    
    if ($core_site) {
        echo "   ✅ Core resolved: blog_id={$core_site->blog_id}, path={$core_site->path}\n";
        
        switch_to_blog($core_site->blog_id);
        $core_name = get_option('blogname', 'N/A');
        restore_current_blog();
        echo "   Site name: {$core_name}\n";
        
        if ($core_site->path !== $normalized_path) {
            echo "   ⚠️  WARNING: Core resolved to different path!\n";
            echo "      Requested: {$normalized_path}\n";
            echo "      Core resolved: {$core_site->path}\n";
        }
    } else {
        echo "   ❌ Core couldn't resolve\n";
    }
} else {
    echo "   ⚠️  get_site_by_path() not available\n";
}

echo "\n";

// ============================================================================
// STEP 7: Filter Hook Check
// ============================================================================
echo "STEP 7: Filter Hook Check\n";
echo str_repeat("-", 70) . "\n";

echo "7.1 Checking if pre_get_site_by_path filter is registered:\n";
$filters = $GLOBALS['wp_filter']['pre_get_site_by_path'] ?? null;
if ($filters) {
    echo "   ✅ Filter exists\n";
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
            echo "      Priority {$priority}: {$function_name}\n";
        }
    }
} else {
    echo "   ❌ Filter NOT registered!\n";
}

echo "\n";

// ============================================================================
// STEP 8: Test All Path Segments
// ============================================================================
echo "STEP 8: Testing All Path Segments\n";
echo str_repeat("-", 70) . "\n";

$test_segments = array(
    '/parent1/',
    '/parent1/child2/',
    '/parent1/child2/grandchild2/',
);

foreach ($test_segments as $segment) {
    $seg_normalized = NestedTree\normalize_path($segment);
    echo "Testing: {$segment}\n";
    echo "  Normalized: {$seg_normalized}\n";
    
    // Our resolution
    $our_resolved = NestedTree\resolve_blog_for_request_path($seg_normalized, $network_id);
    if ($our_resolved) {
        switch_to_blog($our_resolved['blog_id']);
        $our_name = get_option('blogname', 'N/A');
        restore_current_blog();
        echo "  Our filter: blog_id={$our_resolved['blog_id']}, path={$our_resolved['path']}, name={$our_name}\n";
    } else {
        echo "  Our filter: ❌ NOT resolved\n";
    }
    
    // Core resolution
    if (function_exists('get_site_by_path')) {
        $core_resolved = get_site_by_path($test_domain, $segment);
        if ($core_resolved) {
            switch_to_blog($core_resolved->blog_id);
            $core_name = get_option('blogname', 'N/A');
            restore_current_blog();
            echo "  Core: blog_id={$core_resolved->blog_id}, path={$core_resolved->path}, name={$core_name}\n";
        } else {
            echo "  Core: ❌ NOT resolved\n";
        }
        
        // Check if they match
        if ($our_resolved && $core_resolved) {
            if ($our_resolved['blog_id'] === $core_resolved->blog_id) {
                echo "  ✅ Match\n";
            } else {
                echo "  ⚠️  MISMATCH - Our filter and core disagree!\n";
            }
        }
    }
    
    echo "\n";
}

// ============================================================================
// STEP 9: URL Parsing
// ============================================================================
echo "STEP 9: URL Parsing\n";
echo str_repeat("-", 70) . "\n";

$parsed = parse_url($test_url);
echo "9.1 parse_url() result:\n";
print_r($parsed);

if (isset($parsed['path'])) {
    echo "\n9.2 Path segments:\n";
    $segments = array_filter(explode('/', trim($parsed['path'], '/')));
    foreach ($segments as $i => $seg) {
        echo "   Segment {$i}: {$seg}\n";
    }
}

echo "\n";

// ============================================================================
// STEP 10: Check Site Details for All Related Sites
// ============================================================================
echo "STEP 10: Site Details for All Related Sites\n";
echo str_repeat("-", 70) . "\n";

if ($blogs) {
    foreach ($blogs as $blog) {
        $blog_id = (int) $blog['blog_id'];
        echo "Blog ID {$blog_id} ({$blog['path']}):\n";
        
        switch_to_blog($blog_id);
        
        $details = get_blog_details($blog_id);
        if ($details) {
            echo "   - blogname: " . get_option('blogname', 'N/A') . "\n";
            echo "   - siteurl: " . get_option('siteurl', 'N/A') . "\n";
            echo "   - home: " . get_option('home', 'N/A') . "\n";
            echo "   - path (from details): {$details->path}\n";
        }
        
        // Check nested path mapping
        $nested_path = NestedTree\get_blog_path($blog_id, $network_id);
        if ($nested_path) {
            echo "   - nested_path: {$nested_path} ✅\n";
        } else {
            echo "   - nested_path: NOT SET ❌\n";
        }
        
        restore_current_blog();
        echo "\n";
    }
}

// ============================================================================
// SUMMARY
// ============================================================================
echo str_repeat("=", 70) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 70) . "\n";

$grandchild_exists = isset($grandchild_check) && $grandchild_check;
$grandchild_resolved = isset($resolved) && $resolved && $resolved['path'] === $normalized_path;

echo "1. Grandchild site exists in nested_sites table: " . ($grandchild_exists ? "✅ YES" : "❌ NO") . "\n";
echo "2. resolve_blog_for_request_path finds grandchild: " . ($grandchild_resolved ? "✅ YES" : "❌ NO") . "\n";
echo "3. Filter hook is registered: " . (isset($filters) && $filters ? "✅ YES" : "❌ NO") . "\n";

if (!$grandchild_exists) {
    echo "\n🔧 FIX NEEDED: Grandchild site is missing from ideai_nested_sites table!\n";
    echo "   Need to create mapping for blog_id -> /parent1/child2/grandchild2/\n";
} elseif (!$grandchild_resolved) {
    echo "\n🔧 FIX NEEDED: resolve_blog_for_request_path is not finding the grandchild!\n";
    echo "   Check SQL query in resolve_blog_for_request_path()\n";
} elseif (!isset($filters) || !$filters) {
    echo "\n🔧 FIX NEEDED: Filter hook is not registered!\n";
    echo "   Check nested-tree-routing.php is being loaded\n";
} else {
    echo "\n✅ All checks passed - routing should work. Issue may be in filter logic.\n";
}

echo "\n✅ Diagnostic complete!\n";

