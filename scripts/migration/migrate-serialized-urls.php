<?php
/**
 * Serialized URL Migration Script
 * 
 * Properly handles WordPress serialized data when migrating domains
 * This script should be run via wp-cli or included in WordPress
 * 
 * Usage: wp eval-file scripts/migration/migrate-serialized-urls.php
 */

if (!defined('ABSPATH')) {
    // If running standalone, define these
    define('ABSPATH', dirname(__FILE__) . '/../../../');
    require_once ABSPATH . 'wp-load.php';
}

// Configuration
$old_domain = getenv('OLD_DOMAIN') ?: 'localhost';
$new_domain = getenv('NEW_DOMAIN') ?: '13.40.170.117';

/**
 * Recursively search and replace in arrays/objects
 */
function migrate_serialized_data($data, $old_domain, $new_domain) {
    if (is_string($data)) {
        return str_replace($old_domain, $new_domain, $data);
    } elseif (is_array($data)) {
        return array_map(function($item) use ($old_domain, $new_domain) {
            return migrate_serialized_data($item, $old_domain, $new_domain);
        }, $data);
    } elseif (is_object($data)) {
        $new_object = new stdClass();
        foreach ($data as $key => $value) {
            $new_key = str_replace($old_domain, $new_domain, $key);
            $new_object->$new_key = migrate_serialized_data($value, $old_domain, $new_domain);
        }
        return $new_object;
    }
    return $data;
}

/**
 * Update option with serialized data
 */
function update_serialized_option($option_name, $old_domain, $new_domain) {
    $option_value = get_option($option_name);
    
    if (is_serialized($option_value)) {
        $unserialized = unserialize($option_value);
        $migrated = migrate_serialized_data($unserialized, $old_domain, $new_domain);
        update_option($option_name, $migrated);
        return true;
    } elseif (is_string($option_value) && strpos($option_value, $old_domain) !== false) {
        update_option($option_name, str_replace($old_domain, $new_domain, $option_value));
        return true;
    }
    return false;
}

// Main migration
global $wpdb;

echo "Starting serialized URL migration...\n";
echo "From: $old_domain\n";
echo "To: $new_domain\n\n";

$updated = 0;

// Update all options
$options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_value LIKE '%{$old_domain}%'");

foreach ($options as $option) {
    if (update_serialized_option($option->option_name, $old_domain, $new_domain)) {
        $updated++;
        echo "Updated option: {$option->option_name}\n";
    }
}

// Update postmeta
$postmeta = $wpdb->get_results($wpdb->prepare(
    "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
    '%' . $wpdb->esc_like($old_domain) . '%'
));

foreach ($postmeta as $meta) {
    if (is_serialized($meta->meta_value)) {
        $unserialized = unserialize($meta->meta_value);
        $migrated = migrate_serialized_data($unserialized, $old_domain, $new_domain);
        $wpdb->update(
            $wpdb->postmeta,
            array('meta_value' => serialize($migrated)),
            array('meta_id' => $meta->meta_id)
        );
        $updated++;
        echo "Updated postmeta ID: {$meta->meta_id}\n";
    }
}

// Update usermeta
$usermeta = $wpdb->get_results($wpdb->prepare(
    "SELECT umeta_id, meta_value FROM {$wpdb->usermeta} WHERE meta_value LIKE %s",
    '%' . $wpdb->esc_like($old_domain) . '%'
));

foreach ($usermeta as $meta) {
    if (is_serialized($meta->meta_value)) {
        $unserialized = unserialize($meta->meta_value);
        $migrated = migrate_serialized_data($unserialized, $old_domain, $new_domain);
        $wpdb->update(
            $wpdb->usermeta,
            array('meta_value' => serialize($migrated)),
            array('umeta_id' => $meta->umeta_id)
        );
        $updated++;
        echo "Updated usermeta ID: {$meta->umeta_id}\n";
    }
}

echo "\n✓ Migration complete! Updated $updated records.\n";

