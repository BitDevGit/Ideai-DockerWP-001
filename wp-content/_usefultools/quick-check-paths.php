<?php
/**
 * Quick check for all paths containing p1c2g2 or similar patterns.
 * 
 * Usage: wp eval-file wp-content/_usefultools/quick-check-paths.php
 */

global $wpdb;

echo "🔍 Quick Path Check\n";
echo "==================\n\n";

// Check wp_blogs for p1c2g2
echo "1. Paths in wp_blogs containing 'p1c2g2':\n";
$results = $wpdb->get_results(
    "SELECT blog_id, path, domain FROM {$wpdb->blogs} WHERE path LIKE '%p1c2g2%' ORDER BY path",
    ARRAY_A
);

if ($results) {
    foreach ($results as $row) {
        echo "   blog_id={$row['blog_id']}, path={$row['path']}, domain={$row['domain']}\n";
    }
} else {
    echo "   ❌ No paths found\n";
}
echo "\n";

// Check nested_sites table
echo "2. Mappings in ideai_nested_sites containing 'p1c2g2':\n";
$table = $wpdb->base_prefix . 'ideai_nested_sites';
$nested_results = $wpdb->get_results(
    "SELECT blog_id, path FROM {$table} WHERE path LIKE '%p1c2g2%' ORDER BY path",
    ARRAY_A
);

if ($nested_results) {
    foreach ($nested_results as $row) {
        echo "   blog_id={$row['blog_id']}, path={$row['path']}\n";
    }
} else {
    echo "   ❌ No mappings found\n";
}
echo "\n";

// Check for expected nested path
echo "3. Expected nested path '/parent1/child2/grandchild2/':\n";
$expected = $wpdb->get_row(
    $wpdb->prepare("SELECT blog_id, path FROM {$wpdb->blogs} WHERE path=%s", '/parent1/child2/grandchild2/'),
    ARRAY_A
);

if ($expected) {
    echo "   ✅ Found: blog_id={$expected['blog_id']}, path={$expected['path']}\n";
} else {
    echo "   ❌ Not found\n";
}
echo "\n";

// List all temp slug patterns
echo "4. All temporary slug patterns (p[digit]c[digit]...):\n";
$temp_patterns = $wpdb->get_results(
    "SELECT blog_id, path FROM {$wpdb->blogs} 
     WHERE (path REGEXP '^/p[0-9]+c[0-9]+g[0-9]+/$' OR path REGEXP '^/p[0-9]+c[0-9]+/$')
     ORDER BY path",
    ARRAY_A
);

if ($temp_patterns) {
    foreach ($temp_patterns as $row) {
        echo "   blog_id={$row['blog_id']}, path={$row['path']}\n";
    }
} else {
    echo "   ✅ No temporary slug patterns found\n";
}


