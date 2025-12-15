<?php
/**
 * Nested Tree Upload Directory Fix
 * 
 * Ensures upload directories are created for nested sites and that
 * WordPress uses the correct upload path for each site.
 */

namespace Ideai\Wp\Platform\NestedTreeUploads;

use Ideai\Wp\Platform;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Initialize upload directory fixes
 */
function init() {
	if (!is_multisite()) {
		return;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	// Ensure upload directories exist when switching to a blog
	add_action('switch_blog', __NAMESPACE__ . '\\ensure_upload_directory', 10, 2);
	
	// Fix upload directory path if WordPress is using wrong path
	// Use very high priority to override any other filters
	add_filter('upload_dir', __NAMESPACE__ . '\\fix_upload_directory', 9999, 1);
	
	// Also hook into admin_init to ensure blog context is correct
	add_action('admin_init', __NAMESPACE__ . '\\ensure_admin_blog_context', 1);
	
	// Hook into image processing to ensure correct upload directory
	add_filter('wp_image_editors', __NAMESPACE__ . '\\ensure_upload_dir_before_image_processing', 10, 1);
	add_action('wp_handle_upload_prefilter', __NAMESPACE__ . '\\ensure_upload_dir_before_upload', 10, 1);
}

/**
 * Ensure upload directory exists when switching to a blog
 */
function ensure_upload_directory($new_blog_id, $prev_blog_id) {
	// Switch to the blog to get correct upload directory
	switch_to_blog($new_blog_id);
	$upload_dir = wp_upload_dir(null, false);
	$basedir = $upload_dir['basedir'];
	restore_current_blog();
	
	// Create base directory if it doesn't exist
	if (!file_exists($basedir)) {
		wp_mkdir_p($basedir);
	}
	
	// Create year/month directories
	$year = date('Y');
	$month = date('m');
	$year_dir = $basedir . '/' . $year;
	$month_dir = $year_dir . '/' . $month;
	
	if (!file_exists($year_dir)) {
		wp_mkdir_p($year_dir);
	}
	if (!file_exists($month_dir)) {
		wp_mkdir_p($month_dir);
	}
	
	// Ensure proper permissions and ownership
	if (file_exists($basedir)) {
		@chmod($basedir, 0755);
		@chown($basedir, 'www-data');
	}
	if (file_exists($year_dir)) {
		@chmod($year_dir, 0755);
		@chown($year_dir, 'www-data');
	}
	if (file_exists($month_dir)) {
		@chmod($month_dir, 0755);
		@chown($month_dir, 'www-data');
	}
}

/**
 * Fix upload directory path to ensure it uses the correct site-specific path
 */
function fix_upload_directory($uploads) {
	// Only run in multisite
	if (!is_multisite()) {
		return $uploads;
	}
	
	$blog_id = get_current_blog_id();
	
	// Only fix for non-main sites
	if ($blog_id <= 1) {
		return $uploads;
	}
	
	// Force the correct multisite upload path structure
	$expected_basedir = WP_CONTENT_DIR . '/uploads/sites/' . $blog_id;
	$expected_baseurl = content_url('uploads/sites/' . $blog_id);
	
	// Debug logging (only if debug enabled)
	if (Platform\is_debug_enabled()) {
		$original_basedir = $uploads['basedir'] ?? 'not set';
		error_log(sprintf(
			'[NESTED_TREE_UPLOADS] fix_upload_directory: blog_id=%d, original=%s, expected=%s',
			$blog_id,
			$original_basedir,
			$expected_basedir
		));
	}
	
	// Check if WordPress is using wrong path (root uploads instead of site-specific)
	$wrong_path = WP_CONTENT_DIR . '/uploads';
	if (strpos($uploads['basedir'], $wrong_path) === 0 && strpos($uploads['basedir'], '/sites/') === false) {
		// WordPress is using root uploads - force fix
		$uploads['basedir'] = $expected_basedir;
		$uploads['baseurl'] = $expected_baseurl;
	}
	
	// CRITICAL: Always force the correct path - don't let WordPress use root uploads
	// This prevents files from being saved to both locations
	$uploads['basedir'] = $expected_basedir;
	$uploads['baseurl'] = $expected_baseurl;
	
	// Rebuild path and URL with correct base
	if (!empty($uploads['subdir'])) {
		$uploads['path'] = $expected_basedir . $uploads['subdir'];
		$uploads['url'] = $expected_baseurl . $uploads['subdir'];
	} else {
		$uploads['path'] = $expected_basedir;
		$uploads['url'] = $expected_baseurl;
	}
	
	// Ensure we're not using root uploads path at all
	$root_uploads_path = WP_CONTENT_DIR . '/uploads';
	if (strpos($uploads['path'], $root_uploads_path) === 0 && strpos($uploads['path'], '/sites/') === false) {
		// Force fix even if WordPress tried to use root path
		$uploads['path'] = str_replace($root_uploads_path, $expected_basedir, $uploads['path']);
		$uploads['url'] = str_replace(content_url('uploads'), $expected_baseurl, $uploads['url']);
		
		if (Platform\is_debug_enabled()) {
			error_log(sprintf('[NESTED_TREE_UPLOADS] Forced path fix: %s -> %s', $uploads['path'], $expected_basedir));
		}
	}
	
	// Ensure directory exists with proper permissions and ownership
	if (!file_exists($uploads['basedir'])) {
		wp_mkdir_p($uploads['basedir']);
		@chmod($uploads['basedir'], 0755);
		@chown($uploads['basedir'], 'www-data');
	}
	if (!file_exists($uploads['path'])) {
		wp_mkdir_p($uploads['path']);
		@chmod($uploads['path'], 0755);
		@chown($uploads['path'], 'www-data');
	}
	
	// Also ensure parent directories are writable
	$parent_dirs = array(
		dirname($uploads['basedir']), // wp-content/uploads
		$uploads['basedir'], // wp-content/uploads/sites/54
	);
	foreach ($parent_dirs as $dir) {
		if (file_exists($dir) && !is_writable($dir)) {
			@chmod($dir, 0755);
			@chown($dir, 'www-data');
		}
	}
	
	return $uploads;
}

/**
 * Ensure correct blog context in admin
 */
function ensure_admin_blog_context() {
	// Only run in admin area
	if (!is_admin()) {
		return;
	}
	
	// Check if we're in a site admin (not network admin)
	if (is_network_admin()) {
		return;
	}
	
	// Get the current blog ID from the request
	$current_blog_id = get_current_blog_id();
	
	// If we're on a nested site, ensure we're switched to it
	if ($current_blog_id > 1) {
		// Force switch to ensure context is correct
		switch_to_blog($current_blog_id);
		// Ensure upload directory exists
		ensure_upload_directory($current_blog_id, 0);
	}
}

/**
 * Ensure upload directory is correct before image processing
 */
function ensure_upload_dir_before_image_processing($editors) {
	$blog_id = get_current_blog_id();
	if ($blog_id > 1) {
		ensure_upload_directory($blog_id, 0);
	}
	return $editors;
}

/**
 * Ensure upload directory is correct before file upload
 */
function ensure_upload_dir_before_upload($file) {
	$blog_id = get_current_blog_id();
	if ($blog_id > 1) {
		$upload_dir = wp_upload_dir();
		
		// Debug logging
		if (Platform\is_debug_enabled()) {
			error_log(sprintf(
				'[NESTED_TREE_UPLOADS] ensure_upload_dir_before_upload: blog_id=%d, path=%s, exists=%s, writable=%s',
				$blog_id,
				$upload_dir['path'],
				file_exists($upload_dir['path']) ? 'yes' : 'no',
				is_writable($upload_dir['path']) ? 'yes' : 'no'
			));
		}
		
		// Force ensure directory exists and is writable
		if (!file_exists($upload_dir['path'])) {
			wp_mkdir_p($upload_dir['path']);
			@chmod($upload_dir['path'], 0755);
			@chown($upload_dir['path'], 'www-data');
			
			if (Platform\is_debug_enabled()) {
				error_log(sprintf('[NESTED_TREE_UPLOADS] Created upload directory: %s', $upload_dir['path']));
			}
		}
		if (!is_writable($upload_dir['path'])) {
			@chmod($upload_dir['path'], 0755);
			@chown($upload_dir['path'], 'www-data');
			
			if (Platform\is_debug_enabled()) {
				error_log(sprintf('[NESTED_TREE_UPLOADS] Fixed permissions for: %s', $upload_dir['path']));
			}
		}
	}
	return $file;
}

// Initialize early to catch all upload operations
add_action('plugins_loaded', __NAMESPACE__ . '\\init', 1);

