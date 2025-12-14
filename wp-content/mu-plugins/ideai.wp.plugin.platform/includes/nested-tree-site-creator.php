<?php
/**
 * Nested Tree Site Creator UI
 * 
 * Provides a user-friendly interface for creating nested sites
 */

namespace Ideai\Wp\Platform\NestedTreeSiteCreator;

use Ideai\Wp\Platform;
use Ideai\Wp\Platform\NestedTree;

if (!defined('ABSPATH')) {
	exit;
}

const MENU_SLUG = 'ideai-nested-site-creator';
const PAGE_TITLE = 'Create Nested Site';
const MENU_TITLE = 'Create Nested Site';

/**
 * Check if subdirectory multisite
 */
function is_subdirectory_multisite() {
	if (!function_exists('is_multisite') || !is_multisite()) {
		return false;
	}
	if (function_exists('is_subdomain_install')) {
		return !is_subdomain_install();
	}
	return !defined('SUBDOMAIN_INSTALL') || !SUBDOMAIN_INSTALL;
}

/**
 * Initialize the admin UI
 */
function init() {
	if (!is_multisite() || !is_subdirectory_multisite()) {
		return;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	add_action('network_admin_menu', __NAMESPACE__ . '\\add_menu_page', 20);
	add_action('admin_post_ideai_create_nested_site', __NAMESPACE__ . '\\handle_create_site');
}

/**
 * Add admin menu page to IdeAI menu
 */
function add_menu_page() {
	add_submenu_page(
		\Ideai\Wp\Platform\AdminUI\MENU_SLUG_STATUS,
		PAGE_TITLE,
		MENU_TITLE,
		'manage_sites',
		MENU_SLUG,
		__NAMESPACE__ . '\\render_page'
	);
}

/**
 * Handle site creation form submission
 */
function handle_create_site() {
	check_admin_referer('ideai_create_nested_site');
	
	if (!current_user_can('manage_sites')) {
		wp_die('Unauthorized');
	}
	
	$parent_path = isset($_POST['parent_path']) ? sanitize_text_field($_POST['parent_path']) : '';
	$site_name = isset($_POST['site_name']) ? sanitize_text_field($_POST['site_name']) : '';
	$level = isset($_POST['level']) ? (int) $_POST['level'] : 1;
	
	if (empty($site_name)) {
		wp_redirect(add_query_arg(array(
			'page' => MENU_SLUG,
			'error' => 'site_name_required'
		), network_admin_url('admin.php')));
		exit;
	}
	
	$network_id = get_current_network_id();
	
	// Build nested path
	$nested_path = build_nested_path($parent_path, $site_name, $level);
	
	// Check if site already exists
	$existing = NestedTree\resolve_blog_for_request_path($nested_path, $network_id);
	if ($existing && !empty($existing['blog_id'])) {
		wp_redirect(add_query_arg(array(
			'page' => MENU_SLUG,
			'error' => 'site_exists',
			'path' => urlencode($nested_path)
		), network_admin_url('admin.php')));
		exit;
	}
	
	// Create site
	$result = wp_insert_site(array(
		'domain' => get_network()->domain,
		'path' => $nested_path,
		'title' => $site_name,
		'user_id' => get_current_user_id(),
		'network_id' => $network_id,
		'public' => 1,
	));
	
	if (is_wp_error($result)) {
		wp_redirect(add_query_arg(array(
			'page' => MENU_SLUG,
			'error' => 'create_failed',
			'message' => urlencode($result->get_error_message())
		), network_admin_url('admin.php')));
		exit;
	}
	
	$blog_id = $result;
	
	// Register nested path
	global $wpdb;
	$nested_table = NestedTree\table_name();
	$wpdb->replace(
		$nested_table,
		array(
			'blog_id' => $blog_id,
			'network_id' => $network_id,
			'path' => $nested_path,
		),
		array('%d', '%d', '%s')
	);
	
	// Set up site
	switch_to_blog($blog_id);
	
	// Update site name
	$level_name = get_level_name($level);
	update_option('blogname', $site_name . " ($level_name)");
	
	// Set up homepage
	require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree-homepage.php';
	Ideai\Wp\Platform\NestedTree\setup_homepage_with_level($blog_id);
	
	// Create sample post
	wp_insert_post(array(
		'post_title' => $site_name . ' - Sample Post',
		'post_content' => "This is a sample post for {$site_name}.\n\nThis post confirms that the database is saving content correctly for this blog.\n\nBlog ID: $blog_id\nPath: $nested_path\nLevel: $level",
		'post_status' => 'publish',
		'post_type' => 'post',
		'post_author' => get_current_user_id(),
	));
	
	// Update siteurl and home
	$site_obj = get_site($blog_id);
	$domain = $site_obj->domain;
	$scheme = is_ssl() ? 'https' : 'http';
	$correct_url = $scheme . '://' . $domain . $nested_path;
	update_option('siteurl', $correct_url);
	update_option('home', $correct_url);
	
	restore_current_blog();
	
	wp_redirect(add_query_arg(array(
		'page' => MENU_SLUG,
		'success' => '1',
		'blog_id' => $blog_id,
		'path' => urlencode($nested_path)
	), network_admin_url('admin.php')));
	exit;
}

/**
 * Build nested path from parent and site name
 */
function build_nested_path($parent_path, $site_name, $level) {
	$parent_path = NestedTree\normalize_path($parent_path);
	$site_slug = sanitize_title($site_name);
	
	if ($level === 1) {
		return '/' . $site_slug . '/';
	} else {
		return rtrim($parent_path, '/') . '/' . $site_slug . '/';
	}
}

/**
 * Get level name
 */
function get_level_name($level) {
	$names = array(
		1 => 'Level 1',
		2 => 'Level 2',
		3 => 'Level 3',
		4 => 'Level 4',
	);
	return $names[$level] ?? "Level $level";
}

/**
 * Render the admin page
 */
function render_page() {
	if (!current_user_can('manage_sites')) {
		wp_die('Unauthorized');
	}
	
	$network_id = get_current_network_id();
	
	// Get existing sites for parent selection
	global $wpdb;
	$nested_table = NestedTree\table_name();
	$existing_sites = $wpdb->get_results($wpdb->prepare(
		'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
		$network_id
	), ARRAY_A);
	
	// Show success/error messages
	$success = isset($_GET['success']) && $_GET['success'] === '1';
	$error = isset($_GET['error']) ? $_GET['error'] : '';
	$error_message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
	
	?>
	<div class="wrap">
		<h1><?php echo esc_html(PAGE_TITLE); ?></h1>
		
		<?php if ($success) : ?>
			<div class="notice notice-success is-dismissible">
				<p><strong>✅ Site created successfully!</strong></p>
				<?php if (isset($_GET['blog_id']) && isset($_GET['path'])) : ?>
					<p>Blog ID: <?php echo esc_html($_GET['blog_id']); ?> | Path: <code><?php echo esc_html(urldecode($_GET['path'])); ?></code></p>
					<p><a href="<?php echo esc_url(network_admin_url('site-info.php?id=' . (int) $_GET['blog_id'])); ?>">View Site Details</a></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		
		<?php if ($error) : ?>
			<div class="notice notice-error is-dismissible">
				<p><strong>❌ Error:</strong>
				<?php
				switch ($error) {
					case 'site_name_required':
						echo 'Site name is required.';
						break;
					case 'site_exists':
						echo 'A site already exists at this path: <code>' . esc_html(urldecode($_GET['path'] ?? '')) . '</code>';
						break;
					case 'create_failed':
						echo 'Failed to create site: ' . esc_html($error_message);
						break;
					default:
						echo 'An error occurred.';
				}
				?>
				</p>
			</div>
		<?php endif; ?>
		
		<div class="card" style="max-width: 800px;">
			<h2>Create New Nested Site</h2>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<?php wp_nonce_field('ideai_create_nested_site'); ?>
				<input type="hidden" name="action" value="ideai_create_nested_site">
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="level">Site Level</label>
						</th>
						<td>
							<select name="level" id="level" required>
								<option value="1">Level 1 - Parent Site</option>
								<option value="2">Level 2 - Child Site</option>
								<option value="3">Level 3 - Grandchild Site</option>
								<option value="4">Level 4 - Great-Grandchild Site</option>
							</select>
							<p class="description">Select the hierarchy level for this site.</p>
						</td>
					</tr>
					
					<tr id="parent-row" style="display: none;">
						<th scope="row">
							<label for="parent_path">Parent Site</label>
						</th>
						<td>
							<select name="parent_path" id="parent_path">
								<option value="">-- Select Parent --</option>
								<?php foreach ($existing_sites as $site) : 
									$depth = NestedTree\get_site_depth($site['path']);
									if ($depth < 4) : // Only show sites that can have children
								?>
									<option value="<?php echo esc_attr($site['path']); ?>">
										<?php 
										switch_to_blog($site['blog_id']);
										echo esc_html(get_option('blogname')) . ' (' . esc_html($site['path']) . ')';
										restore_current_blog();
										?>
									</option>
								<?php 
									endif;
								endforeach; ?>
							</select>
							<p class="description">Select the parent site for this nested site.</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="site_name">Site Name</label>
						</th>
						<td>
							<input type="text" name="site_name" id="site_name" class="regular-text" required placeholder="e.g., My New Site">
							<p class="description">The name of the site. This will be used to generate the URL slug.</p>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="Create Site">
				</p>
			</form>
		</div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Existing Nested Sites</h2>
			<?php if (empty($existing_sites)) : ?>
				<p>No nested sites found.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Path</th>
							<th>Blog ID</th>
							<th>Site Name</th>
							<th>Level</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($existing_sites as $site) : 
							switch_to_blog($site['blog_id']);
							$site_name = get_option('blogname');
							$depth = NestedTree\get_site_depth($site['path']);
							restore_current_blog();
						?>
							<tr>
								<td><code><?php echo esc_html($site['path']); ?></code></td>
								<td><?php echo esc_html($site['blog_id']); ?></td>
								<td><?php echo esc_html($site_name); ?></td>
								<td><?php echo esc_html($depth); ?></td>
								<td>
									<a href="<?php echo esc_url(network_admin_url('site-info.php?id=' . $site['blog_id'])); ?>">Edit</a> |
									<a href="<?php echo esc_url(get_site_url($site['blog_id'])); ?>" target="_blank">View</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		$('#level').on('change', function() {
			if ($(this).val() === '1') {
				$('#parent-row').hide();
				$('#parent_path').prop('required', false);
			} else {
				$('#parent-row').show();
				$('#parent_path').prop('required', true);
			}
		});
		
		// Trigger on page load
		$('#level').trigger('change');
	});
	</script>
	
	<style>
	.form-table th {
		width: 200px;
	}
	.wp-list-table code {
		background: #f0f0f0;
		padding: 2px 6px;
		border-radius: 3px;
	}
	</style>
	<?php
}

// Initialize
add_action('plugins_loaded', __NAMESPACE__ . '\\init');

