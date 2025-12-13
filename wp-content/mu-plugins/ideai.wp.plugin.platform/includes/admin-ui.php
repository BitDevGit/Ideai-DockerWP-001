<?php
/**
 * IdeAI Platform: Network Admin UI.
 *
 * Design goal: ZERO performance impact on frontend requests.
 * - This file registers admin hooks, but all heavy work is guarded by is_admin()
 *   + is_network_admin() + capability checks.
 */

namespace Ideai\Wp\Platform\AdminUI;

use Ideai\Wp\Platform;
use Ideai\Wp\Platform\NestedTree;

if (!defined('ABSPATH')) {
	exit;
}

const MENU_SLUG_STATUS = 'ideai-platform';
const MENU_SLUG_SITES  = 'ideai-platform-sites';

function should_load_network_ui() {
	return function_exists('is_admin')
		&& is_admin()
		&& function_exists('is_network_admin')
		&& is_network_admin()
		&& function_exists('is_multisite')
		&& is_multisite();
}

function register_network_menu() {
	if (!should_load_network_ui()) {
		return;
	}
	if (!current_user_can('manage_network_options')) {
		return;
	}

	add_menu_page(
		'IdeAI',
		'IdeAI',
		'manage_network_options',
		MENU_SLUG_STATUS,
		__NAMESPACE__ . '\\render_status_page',
		'dashicons-admin-generic',
		3
	);

	add_submenu_page(
		MENU_SLUG_STATUS,
		'Status',
		'Status',
		'manage_network_options',
		MENU_SLUG_STATUS,
		__NAMESPACE__ . '\\render_status_page'
	);

	add_submenu_page(
		MENU_SLUG_STATUS,
		'Sites',
		'Sites',
		'manage_network_sites',
		MENU_SLUG_SITES,
		__NAMESPACE__ . '\\render_sites_page'
	);
}
add_action('network_admin_menu', __NAMESPACE__ . '\\register_network_menu');

function handle_save_flags() {
	// admin-post.php doesn't have full admin context, so check multisite + capability directly.
	if (!function_exists('is_multisite') || !is_multisite()) {
		wp_die('Multisite only.');
	}
	if (!current_user_can('manage_network_options')) {
		wp_die('Insufficient permissions.');
	}
	check_admin_referer('ideai_save_flags');

	// Resolve network ID: get_current_network_id() can return 0/false, so fallback to 1 (main network).
	$network_id = 0;
	if (function_exists('get_current_network_id')) {
		$network_id = (int) get_current_network_id();
	}
	if ($network_id <= 0) {
		$network_id = 1; // Main network in multisite.
	}

	// Checkbox: if present in POST and equals '1', it's enabled. If absent, it's disabled.
	$enabled = isset($_POST['ideai_nested_tree_enabled']) && (string) $_POST['ideai_nested_tree_enabled'] === '1';
	$mode = isset($_POST['ideai_nested_tree_collision_mode']) ? sanitize_text_field(wp_unslash($_POST['ideai_nested_tree_collision_mode'])) : 'strict';
	if ($mode !== 'strict') {
		$mode = 'strict';
	}

	$ok1 = Platform\set_flag(Platform\FLAG_NESTED_TREE_ENABLED, $enabled, $network_id);
	$ok2 = Platform\set_flag(Platform\FLAG_NESTED_TREE_COLLISION_MODE, $mode, $network_id);
	$ok = $ok1 && $ok2;

	$base = network_admin_url('admin.php?page=' . rawurlencode(MENU_SLUG_STATUS));
	$q = $ok ? 'ideai_saved=1' : 'ideai_saved=0';
	wp_safe_redirect($base . '&' . $q);
	exit;
}
add_action('admin_post_ideai_save_flags', __NAMESPACE__ . '\\handle_save_flags');

function sanitize_child_slug($slug) {
	$slug = strtolower((string) $slug);
	$slug = trim($slug);
	$slug = preg_replace('/\s+/', '-', $slug);
	$slug = preg_replace('/[^a-z0-9-]/', '', $slug);
	$slug = preg_replace('/-+/', '-', $slug);
	$slug = trim($slug, '-');
	return $slug;
}


function handle_create_nested_site() {
	if (!should_load_network_ui()) {
		wp_die('Network admin only.');
	}
	if (!current_user_can('manage_network_sites')) {
		wp_die('Insufficient permissions.');
	}
	check_admin_referer('ideai_create_nested_site');

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;
	if ($network_id <= 0) {
		wp_die('Could not determine network.');
	}

	if (!Platform\nested_tree_enabled($network_id)) {
		wp_die('Nested tree is disabled for this network (enable it in IdeAI → Status).');
	}

	$parent_blog_id = isset($_POST['ideai_parent_blog_id']) ? (int) $_POST['ideai_parent_blog_id'] : 0;
	$child_slug_raw = isset($_POST['ideai_child_slug']) ? wp_unslash($_POST['ideai_child_slug']) : '';
	$child_slug = sanitize_child_slug($child_slug_raw);
	if ($parent_blog_id <= 0) {
		wp_die('Missing parent site.');
	}
	if ($child_slug === '') {
		wp_die('Missing child slug.');
	}

	$parent_mapped = NestedTree\get_blog_path($parent_blog_id, $network_id);
	if (!$parent_mapped) {
		$parent_site = get_site($parent_blog_id);
		$parent_mapped = $parent_site && !empty($parent_site->path) ? $parent_site->path : '/';
	}
	$parent_mapped = NestedTree\normalize_path($parent_mapped);

	$nested_path = NestedTree\normalize_path($parent_mapped . $child_slug . '/');
	
	// Create site with temporary slug (last segment only) - WordPress will accept this
	// We'll update to nested path immediately after creation
	$temp_slug = $child_slug; // Use child slug as temporary path
	
	$net = function_exists('get_network') ? get_network($network_id) : null;
	if (!$net || empty($net->domain)) {
		wp_die('Could not load network.');
	}

	$title = 'Nested: ' . trim($nested_path, '/');
	$user_id = get_current_user_id();
	
	// Create site with temporary path (just the child slug)
	$blog_id = wpmu_create_blog((string) $net->domain, '/' . $temp_slug . '/', $title, $user_id, array(), $network_id);
	if (is_wp_error($blog_id)) {
		wp_die($blog_id);
	}
	
	// CRITICAL: Immediately update wp_blogs.path to nested path
	// WordPress uses this for all URL generation
	if (function_exists('update_blog_details')) {
		update_blog_details((int) $blog_id, array('path' => $nested_path));
	}
	// Direct DB update as backup
	global $wpdb;
	$wpdb->update(
		$wpdb->blogs,
		array('path' => $nested_path),
		array('blog_id' => (int) $blog_id),
		array('%s'),
		array('%d')
	);
	
	// Clear cache
	if (function_exists('clean_blog_cache')) {
		clean_blog_cache((int) $blog_id);
	}
	wp_cache_delete((int) $blog_id, 'blog-details');
	wp_cache_delete((int) $blog_id . 'short', 'blog-details');

	// Save nested path mapping (for routing lookup)
	$ok = NestedTree\upsert_blog_path((int) $blog_id, $nested_path, $network_id);
	if (!$ok) {
		if (function_exists('wpmu_delete_blog')) {
			wpmu_delete_blog((int) $blog_id, true);
		}
		wp_die('Could not register nested path (collision or mapping error).');
	}

	wp_safe_redirect(network_admin_url('site-info.php?id=' . (int) $blog_id));
	exit;
}
// Hook into WordPress standard site creation to set nested path if nested site was created
function handle_wpmu_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta) {
	// Only process if nested tree is enabled
	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;
	if (!$network_id || !Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	// Check if this was created via our nested site form (standard WordPress "Add Site" form)
	$parent_blog_id = isset($_POST['ideai_parent_blog_id']) ? (int) $_POST['ideai_parent_blog_id'] : 0;
	$child_slug_raw = isset($_POST['ideai_child_slug']) ? wp_unslash($_POST['ideai_child_slug']) : '';
	
	// Only process if nested site fields were used
	if ($parent_blog_id <= 0 || empty($child_slug_raw)) {
		return; // Standard WordPress site, not nested
	}
	
	$child_slug = sanitize_child_slug($child_slug_raw);
	if (empty($child_slug)) {
		return;
	}
	
	// Get parent path
	$parent_mapped = NestedTree\get_blog_path($parent_blog_id, $network_id);
	if (!$parent_mapped) {
		$parent_site = get_site($parent_blog_id);
		$parent_mapped = $parent_site && !empty($parent_site->path) ? $parent_site->path : '/';
	}
	$parent_mapped = NestedTree\normalize_path($parent_mapped);
	
	// Calculate nested path
	$nested_path = NestedTree\normalize_path($parent_mapped . $child_slug . '/');
	
	// CRITICAL: Update wp_blogs.path to nested path immediately
	if (function_exists('update_blog_details')) {
		update_blog_details((int) $blog_id, array('path' => $nested_path));
	}
	// Direct DB update as backup
	global $wpdb;
	$wpdb->update(
		$wpdb->blogs,
		array('path' => $nested_path),
		array('blog_id' => (int) $blog_id),
		array('%s'),
		array('%d')
	);
	
	// Clear cache
	if (function_exists('clean_blog_cache')) {
		clean_blog_cache((int) $blog_id);
	}
	wp_cache_delete((int) $blog_id, 'blog-details');
	wp_cache_delete((int) $blog_id . 'short', 'blog-details');
	
	// Save nested path mapping (for routing)
	NestedTree\upsert_blog_path((int) $blog_id, $nested_path, $network_id);
}
add_action('wpmu_new_blog', __NAMESPACE__ . '\\handle_wpmu_new_blog', 5, 6);

function render_site_new_integration() {
	if (!should_load_network_ui()) {
		return;
	}
	if (!current_user_can('manage_network_sites')) {
		return;
	}

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;

	if (!Platform\nested_tree_enabled($network_id)) {
		return; // Silent: don't show block if disabled
	}
	
	// Add fields to the EXISTING WordPress form (not a separate form)
	// These will be submitted with the standard WordPress "Add Site" form

	$sites = function_exists('get_sites') ? get_sites(array('network_id' => $network_id, 'number' => 2000)) : array();
	
	// Build site list with nested paths
	$site_options = array();
	$site_paths = array(); // For JavaScript: blog_id => nested_path
	
	// Add network root as default (blog_id 1)
	$root_blog_id = 1;
	$root_path = '/';
	$site_options[] = array(
		'blog_id' => $root_blog_id,
		'label' => 'Network root (' . $root_path . ')',
		'path' => $root_path,
	);
	$site_paths[$root_blog_id] = $root_path;
	
	// Get network domain for JavaScript
	$network_domain = '';
	if (function_exists('get_network')) {
		$net = get_network($network_id);
		if ($net && !empty($net->domain)) {
			$network_domain = $net->domain;
		}
	}
	// Fallback to current site domain
	if (empty($network_domain) && function_exists('get_current_site')) {
		$current_site = get_current_site();
		if ($current_site && !empty($current_site->domain)) {
			$network_domain = $current_site->domain;
		}
	}
	
	// Add all other sites
	foreach ($sites as $s) {
		$bid = (int) $s->blog_id;
		if ($bid === $root_blog_id) {
			continue; // Skip root, already added
		}
		
		// Get nested path (or fallback to WordPress path)
		$nested_path = NestedTree\get_blog_path($bid, $network_id);
		if (!$nested_path) {
			$nested_path = !empty($s->path) ? $s->path : '/';
		}
		$nested_path = NestedTree\normalize_path($nested_path);
		
		$label = $s->domain . $nested_path . ' (ID ' . $bid . ')';
		$site_options[] = array(
			'blog_id' => $bid,
			'label' => $label,
			'path' => $nested_path,
		);
		$site_paths[$bid] = $nested_path;
	}

	// Add fields to the EXISTING WordPress form
	// These will be moved to the top via JavaScript
	echo '<div id="ideai_nested_site_fields" style="display: none;">';
	echo '<h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #c3c4c7;">IdeAI: Nested Site Options</h2>';
	echo '<p class="description" style="margin-bottom: 20px;">Create a nested site at any depth, or leave parent as "Not a nested site" for standard sites.</p>';
	
	echo '<table class="form-table" role="presentation"><tbody>';
	
	// Parent dropdown
	echo '<tr>';
	echo '<th scope="row"><label for="ideai_parent_blog_id">Parent site</label></th>';
	echo '<td>';
	echo '<select name="ideai_parent_blog_id" id="ideai_parent_blog_id" style="width: 100%; max-width: 600px">';
	echo '<option value="0">-- Not a nested site (standard WordPress site) --</option>';
	foreach ($site_options as $opt) {
		$selected = ($opt['blog_id'] === $root_blog_id) ? ' selected="selected"' : '';
		echo '<option value="' . esc_attr($opt['blog_id']) . '" data-path="' . esc_attr($opt['path']) . '"' . $selected . '>' . esc_html($opt['label']) . '</option>';
	}
	echo '</select>';
	echo '<p class="description">Select a parent site to create a nested child site.</p>';
	echo '</td>';
	echo '</tr>';
	
	// Site Address (URL) - nested version
	echo '<tr id="ideai_nested_row" style="display: none;">';
	echo '<th scope="row"><label for="ideai_child_slug">Site Address (URL)</label></th>';
	echo '<td>';
	echo '<div style="display: flex; align-items: center; gap: 4px; max-width: 600px">';
	echo '<span id="ideai_path_prefix" style="padding: 6px 8px; background: #f0f0f1; color: #646970; border: 1px solid #8c8f94; border-right: none; border-radius: 4px 0 0 4px; font-family: monospace; white-space: nowrap; user-select: none;">/</span>';
	echo '<input type="text" name="ideai_child_slug" id="ideai_child_slug" value="" placeholder="subsub1" style="flex: 1; padding: 6px 8px; border: 1px solid #8c8f94; border-left: none; border-radius: 0 4px 4px 0; font-family: monospace;" class="regular-text" />';
	echo '</div>';
	echo '<p class="description">Enter the child slug. Full URL: <code id="ideai_full_path_preview" style="font-size: 13px; color: #2271b1;">/</code></p>';
	echo '</td>';
	echo '</tr>';
	
	echo '</tbody></table>';
	echo '</div>';

	// JavaScript: move fields to top, show/hide nested fields, update prefix/slug, and populate WordPress Site Address field
	$js_paths = wp_json_encode($site_paths);
	$js_domain = wp_json_encode($network_domain);
	echo '<script>
(function() {
	var paths = ' . $js_paths . ';
	var networkDomain = ' . $js_domain . ';
	var ideaiFieldsContainer = document.getElementById("ideai_nested_site_fields");
	var parentSelect = document.getElementById("ideai_parent_blog_id");
	var nestedRow = document.getElementById("ideai_nested_row");
	var prefixSpan = document.getElementById("ideai_path_prefix");
	var childInput = document.getElementById("ideai_child_slug");
	var previewSpan = document.getElementById("ideai_full_path_preview");
	
	// Find WordPress Site Address field and its row (to hide it)
	var wpSiteAddress = document.getElementById("site-address") || document.querySelector("input[name=\\"blog[domain]\\"]") || document.querySelector("input[name=\\"site-address\\"]");
	var wpSiteAddressRow = wpSiteAddress ? wpSiteAddress.closest("tr") : null;
	var wpSiteAddressLabel = wpSiteAddressRow ? wpSiteAddressRow.querySelector("th label, td label") : null;
	
	// Move IdeAI fields to the top of the form
	if (ideaiFieldsContainer) {
		var form = ideaiFieldsContainer.closest("form");
		if (form) {
			// Find the first form-table or h2, insert before it
			var firstElement = form.querySelector("h2, .form-table");
			if (firstElement) {
				form.insertBefore(ideaiFieldsContainer, firstElement);
			}
			ideaiFieldsContainer.style.display = "";
		}
	}
	
	if (!parentSelect || !nestedRow || !prefixSpan || !childInput || !previewSpan) return;
	
	function getPathDepth(path) {
		if (!path || path === "/") return 0;
		var segments = path.split("/").filter(function(s) { return s !== ""; });
		return segments.length;
	}
	
	function getSuggestedSlug(depth) {
		var prefix = "";
		for (var i = 0; i <= depth; i++) {
			prefix += "sub";
		}
		return prefix + "site";
	}
	
	var previousSuggestedSlug = "";
	
	// normalizePath: safety net for old sites that might have -- in paths
	// New sites should never have --, but this handles legacy data
	function normalizePath(path) {
		if (path && path.indexOf("--") !== -1) {
			var segments = path.split("/").filter(function(s) { return s !== ""; });
			var normalized = [];
			for (var i = 0; i < segments.length; i++) {
				if (segments[i].indexOf("--") !== -1) {
					var subSegments = segments[i].split("--").filter(function(s) { return s !== ""; });
					normalized = normalized.concat(subSegments);
				} else {
					normalized.push(segments[i]);
				}
			}
			return "/" + normalized.join("/") + "/";
		}
		return path || "/";
	}
	
	function updateNestedFields() {
		var blogId = parseInt(parentSelect.value, 10);
		if (blogId > 0) {
			nestedRow.style.display = "";
			var path = paths[blogId] || "/";
			path = normalizePath(path);
			paths[blogId] = path; // Update stored path to normalized version
			prefixSpan.textContent = path;
			
			var depth = getPathDepth(path);
			var suggestedSlug = getSuggestedSlug(depth);
			
			var currentValue = childInput.value.trim();
			if (currentValue === "" || currentValue === previousSuggestedSlug) {
				childInput.value = suggestedSlug;
				previousSuggestedSlug = suggestedSlug;
			} else {
				previousSuggestedSlug = suggestedSlug;
			}
			updatePreview();
			updateWordPressSiteAddress();
			
			// Hide WordPress native Site Address field
			if (wpSiteAddressRow) {
				wpSiteAddressRow.style.display = "none";
			}
		} else {
			nestedRow.style.display = "none";
			childInput.value = "";
			updatePreview();
			updateWordPressSiteAddress();
			
			// Show WordPress native Site Address field
			if (wpSiteAddressRow) {
				wpSiteAddressRow.style.display = "";
			}
		}
	}
	
	function updatePreview() {
		var blogId = parseInt(parentSelect.value, 10);
		if (blogId > 0 && childInput.value.trim()) {
			var path = paths[blogId] || "/";
			path = normalizePath(path);
			var fullPath = path + childInput.value.trim() + "/";
			
			// Get domain: use networkDomain from PHP, or fallback to current page host
			var domain = networkDomain || window.location.host;
			
			// Construct full URL
			var protocol = window.location.protocol;
			var fullUrl = protocol + "//" + domain + fullPath;
			previewSpan.textContent = fullUrl;
		} else {
			previewSpan.textContent = "/";
		}
	}
	
	function updateWordPressSiteAddress() {
		if (!wpSiteAddress) return;
		
		var blogId = parseInt(parentSelect.value, 10);
		if (blogId > 0 && childInput.value.trim()) {
			// Calculate nested path
			var parentPath = paths[blogId] || "/";
			var childSlug = childInput.value.trim();
			var nestedPath = parentPath + childSlug + "/";
			
			// Use just the child slug for WordPress field (we'll update to nested path after creation)
			// WordPress will create site with this, then we update to full nested path
			wpSiteAddress.value = childSlug;
		}
	}
	
	parentSelect.addEventListener("change", updateNestedFields);
	childInput.addEventListener("input", function() {
		updatePreview();
		updateWordPressSiteAddress();
	});
	updateNestedFields();
})();
</script>';
}
add_action('network_site_new_form', __NAMESPACE__ . '\\render_site_new_integration');

function render_status_page() {
	if (!should_load_network_ui()) {
		wp_die('Network admin only.');
	}
	if (!current_user_can('manage_network_options')) {
		wp_die('Insufficient permissions.');
	}

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;
	$enabled = (bool) Platform\get_flag(Platform\FLAG_NESTED_TREE_ENABLED, false, $network_id);

	echo '<div class="wrap">';
	echo '<h1>IdeAI</h1>';

	if (isset($_GET['ideai_saved'])) {
		$ok = (string) $_GET['ideai_saved'] === '1';
		echo '<div class="notice ' . esc_attr($ok ? 'notice-success' : 'notice-error') . '"><p>' . esc_html($ok ? 'Saved.' : 'Could not save.') . '</p></div>';
	}

	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" id="ideai-status-form">';
	wp_nonce_field('ideai_save_flags');
	echo '<input type="hidden" name="action" value="ideai_save_flags" />';
	// Collision mode is always 'strict' (no UI needed since it's the only option)
	echo '<input type="hidden" name="ideai_nested_tree_collision_mode" value="strict" />';

	// Descriptive card for Nested Tree Multisite
	echo '<div style="max-width: 900px; margin: 20px 0; padding: 20px; border: 1px solid #c3c4c7; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
	
	echo '<div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px;">';
	echo '<div style="flex: 1;">';
	echo '<h2 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600;">Nested Tree Multisite</h2>';
	echo '<p style="margin: 0 0 12px 0; color: #646970; line-height: 1.6;">';
	echo 'Enable hierarchical nested sites for subdirectory multisite networks. ';
	echo 'This allows you to create child sites at any depth (e.g., <code>/sub1/subsub1/subsubsub1/</code>) ';
	echo 'beyond WordPress\'s standard single-level subdirectory multisite. ';
	echo 'Each nested site is a fully independent WordPress site with its own admin, content, and settings.';
	echo '</p>';
	echo '<p style="margin: 0; color: #646970; line-height: 1.6; font-size: 13px;">';
	echo '<strong>Note:</strong> This feature applies to the entire network and requires proper Nginx rewrite rules. ';
	echo 'When enabled, strict collision prevention ensures Pages and nested sites cannot occupy the same path.';
	echo '</p>';
	echo '</div>';
	
	// Toggle switch
	echo '<div style="margin-left: 24px; flex-shrink: 0;">';
	echo '<label style="display: inline-flex; align-items: center; cursor: pointer;">';
	echo '<input type="checkbox" name="ideai_nested_tree_enabled" value="1" id="ideai_nested_tree_toggle" ' . checked($enabled, true, false) . ' style="display: none;" />';
	echo '<span id="ideai_toggle_switch" style="';
	echo 'display: inline-block; ';
	echo 'width: 50px; ';
	echo 'height: 26px; ';
	echo 'background: ' . ($enabled ? '#2271b1' : '#c3c4c7') . '; ';
	echo 'border-radius: 13px; ';
	echo 'position: relative; ';
	echo 'transition: background 0.2s; ';
	echo 'box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);';
	echo '">';
	echo '<span style="';
	echo 'display: block; ';
	echo 'width: 22px; ';
	echo 'height: 22px; ';
	echo 'background: #fff; ';
	echo 'border-radius: 50%; ';
	echo 'position: absolute; ';
	echo 'top: 2px; ';
	echo 'left: ' . ($enabled ? '26px' : '2px') . '; ';
	echo 'transition: left 0.2s; ';
	echo 'box-shadow: 0 1px 3px rgba(0,0,0,0.3);';
	echo '"></span>';
	echo '</span>';
	echo '<span id="ideai_toggle_label" style="margin-left: 12px; font-weight: 600; color: ' . ($enabled ? '#2271b1' : '#646970') . ';">';
	echo $enabled ? 'Enabled' : 'Disabled';
	echo '</span>';
	echo '</label>';
	echo '</div>';
	
	echo '</div>'; // End card

	echo '<div style="margin-top: 20px;">';
	submit_button('Save settings', 'primary', 'submit', false);
	echo '</div>';
	echo '</form>';

	// JavaScript for toggle switch
	echo '<script>
(function() {
	var toggle = document.getElementById("ideai_nested_tree_toggle");
	var toggleSwitch = document.getElementById("ideai_toggle_switch");
	var toggleLabel = document.getElementById("ideai_toggle_label");
	
	if (!toggle || !toggleSwitch || !toggleLabel) return;
	
	function updateToggle(checked) {
		toggle.checked = checked;
		toggleSwitch.style.background = checked ? "#2271b1" : "#c3c4c7";
		var thumb = toggleSwitch.querySelector("span");
		if (thumb) {
			thumb.style.left = checked ? "26px" : "2px";
		}
		toggleLabel.textContent = checked ? "Enabled" : "Disabled";
		toggleLabel.style.color = checked ? "#2271b1" : "#646970";
	}
	
	toggleSwitch.addEventListener("click", function(e) {
		e.preventDefault();
		updateToggle(!toggle.checked);
	});
	
	toggleLabel.addEventListener("click", function(e) {
		e.preventDefault();
		updateToggle(!toggle.checked);
	});
})();
</script>';

	echo '</div>';
}

function render_sites_page() {
	if (!should_load_network_ui()) {
		wp_die('Network admin only.');
	}
	if (!current_user_can('manage_network_sites')) {
		wp_die('Insufficient permissions.');
	}

	$network_id = function_exists('get_current_network_id') ? (int) get_current_network_id() : 0;

	echo '<div class="wrap">';
	echo '<h1>IdeAI → Sites</h1>';

	echo '<p>Registered nested-site paths (per-network):</p>';
	$rows = NestedTree\list_mappings($network_id);
	if (empty($rows)) {
		echo '<p><em>No nested sites registered yet.</em></p>';
	} else {
		echo '<table class="widefat striped" style="max-width: 900px"><thead><tr><th>Path</th><th>Blog</th></tr></thead><tbody>';
		foreach ($rows as $r) {
			$bid = (int) $r['blog_id'];
			$path = (string) $r['path'];
			$link = network_admin_url('site-info.php?id=' . $bid);
			echo '<tr><td><code>' . esc_html($path) . '</code></td><td><a href="' . esc_url($link) . '">Blog ID ' . esc_html((string) $bid) . '</a></td></tr>';
		}
		echo '</tbody></table>';
	}

	echo '<h2 style="margin-top: 24px">Create nested child site</h2>';
	echo '<p>Use <a class="button button-primary" href="' . esc_url(network_admin_url('site-new.php')) . '">Add New Site</a> and the IdeAI block on that page.</p>';

	echo '</div>';
}


