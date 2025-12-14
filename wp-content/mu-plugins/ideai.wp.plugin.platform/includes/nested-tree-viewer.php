<?php
/**
 * Nested Tree Viewer UI
 * 
 * Displays all nested sites in a hierarchical tree/pyramid structure
 */

namespace Ideai\Wp\Platform\NestedTreeViewer;

use Ideai\Wp\Platform;
use Ideai\Wp\Platform\NestedTree;

if (!defined('ABSPATH')) {
	exit;
}

const MENU_SLUG = 'ideai-nested-tree-viewer';
const PAGE_TITLE = 'Nested Sites Tree';
const MENU_TITLE = 'Sites Tree';

/**
 * Initialize the admin UI
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

function init() {
	if (!is_multisite() || !is_subdirectory_multisite()) {
		return;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	add_action('network_admin_menu', __NAMESPACE__ . '\\add_menu_page', 20);
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
 * Build hierarchical tree structure organized by parent
 */
function build_tree($sites) {
	$tree = array();
	$by_parent = array(); // Group by parent path
	
	foreach ($sites as $site) {
		$path = $site['path'];
		$segments = array_filter(explode('/', trim($path, '/')));
		$depth = count($segments);
		
		if ($depth === 1) {
			// Parent site
			$parent_key = $path;
			if (!isset($by_parent[$parent_key])) {
				$by_parent[$parent_key] = array(
					'parent' => $site,
					'children' => array(),
					'grandchildren' => array(),
				);
			}
		} elseif ($depth === 2) {
			// Child site
			$parent_path = '/' . $segments[0] . '/';
			if (!isset($by_parent[$parent_path])) {
				$by_parent[$parent_path] = array(
					'parent' => null,
					'children' => array(),
					'grandchildren' => array(),
				);
			}
			$by_parent[$parent_path]['children'][$path] = $site;
		} elseif ($depth === 3) {
			// Grandchild site
			$parent_path = '/' . $segments[0] . '/';
			$child_path = '/' . $segments[0] . '/' . $segments[1] . '/';
			if (!isset($by_parent[$parent_path])) {
				$by_parent[$parent_path] = array(
					'parent' => null,
					'children' => array(),
					'grandchildren' => array(),
				);
			}
			if (!isset($by_parent[$parent_path]['grandchildren'][$child_path])) {
				$by_parent[$parent_path]['grandchildren'][$child_path] = array();
			}
			$by_parent[$parent_path]['grandchildren'][$child_path][$path] = $site;
		}
	}
	
	// Sort children and grandchildren
	foreach ($by_parent as $parent_key => &$data) {
		ksort($data['children']);
		foreach ($data['grandchildren'] as $child_key => &$grandchildren) {
			ksort($grandchildren);
		}
	}
	
	return $by_parent;
}

/**
 * Get parent path for a given path
 */
function get_parent_path($path) {
	$segments = array_filter(explode('/', trim($path, '/')));
	if (count($segments) <= 1) {
		return '/';
	}
	array_pop($segments);
	return '/' . implode('/', $segments) . '/';
}

/**
 * Render the admin page
 */
function render_page() {
	if (!current_user_can('manage_sites')) {
		wp_die('Unauthorized');
	}
	
	$network_id = get_current_network_id();
	
	// Get all nested sites
	global $wpdb;
	$nested_table = NestedTree\table_name();
	$sites = $wpdb->get_results($wpdb->prepare(
		'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
		$network_id
	), ARRAY_A);
	
	// Build tree structure
	$tree = build_tree($sites);
	
	// Get site details
	$site_details = array();
	foreach ($sites as $site) {
		switch_to_blog($site['blog_id']);
		$site_details[$site['path']] = array(
			'blog_id' => $site['blog_id'],
			'path' => $site['path'],
			'name' => get_option('blogname'),
			'url' => get_option('home'),
			'admin_url' => admin_url(),
			'depth' => count(array_filter(explode('/', trim($site['path'], '/')))),
		);
		restore_current_blog();
	}
	
	?>
	<div class="wrap">
		<h1><?php echo esc_html(PAGE_TITLE); ?></h1>
		<p class="description">Visual hierarchy of all nested sites. Click any site card to view or edit.</p>
		
		<?php if (empty($sites)) : ?>
			<div class="notice notice-info">
				<p>No nested sites found. <a href="<?php echo esc_url(network_admin_url('admin.php?page=ideai-nested-site-creator')); ?>">Create your first nested site</a></p>
			</div>
		<?php else : ?>
			<div class="nested-tree-container" style="margin-top: 30px;">
				<div class="parents-grid" style="display: grid; grid-template-columns: repeat(<?php echo count($tree); ?>, 1fr); gap: 30px; align-items: start;">
					<?php foreach ($tree as $parent_path => $parent_data) : 
						$parent_site = $parent_data['parent'];
						if (!$parent_site) {
							continue;
						}
						$parent_details = $site_details[$parent_path];
					?>
						<div class="parent-column" style="background: white; border: 2px solid #0073aa; border-radius: 8px; padding: 20px; min-height: 100%;">
							<!-- Parent Site -->
							<div class="parent-card site-card" style="background: linear-gradient(135deg, #0073aa 0%, #005177 100%); color: white; border: none; margin-bottom: 30px;">
								<h2 style="margin: 0 0 10px 0; font-size: 18px; color: white;">
									<?php echo esc_html($parent_details['name']); ?>
								</h2>
								<div style="font-size: 12px; opacity: 0.9; margin-bottom: 12px;">
									<div><strong>Path:</strong> <code style="background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 3px; color: white;"><?php echo esc_html($parent_details['path']); ?></code></div>
									<div style="margin-top: 4px;"><strong>Blog ID:</strong> <?php echo esc_html($parent_details['blog_id']); ?></div>
								</div>
								<div style="display: flex; gap: 8px; flex-wrap: wrap;">
									<a href="<?php echo esc_url($parent_details['url']); ?>" target="_blank" class="button button-small" style="background: white; color: #0073aa; border: none; text-decoration: none;">
										View
									</a>
									<a href="<?php echo esc_url($parent_details['admin_url']); ?>" target="_blank" class="button button-small" style="background: white; color: #0073aa; border: none; text-decoration: none;">
										Admin
									</a>
								</div>
							</div>
							
							<!-- Children -->
							<?php if (!empty($parent_data['children'])) : ?>
								<div class="children-container" style="margin-bottom: 20px;">
									<?php foreach ($parent_data['children'] as $child_path => $child_site) : 
										$child_details = $site_details[$child_path];
									?>
										<div class="child-card site-card" style="background: #e8f5e9; border: 2px solid #00a32a; margin-bottom: 20px; margin-left: 20px;">
											<h3 style="margin: 0 0 8px 0; font-size: 15px; color: #00a32a;">
												<?php echo esc_html($child_details['name']); ?>
											</h3>
											<div style="font-size: 11px; color: #666; margin-bottom: 10px;">
												<div><code><?php echo esc_html($child_details['path']); ?></code></div>
												<div style="margin-top: 2px;">ID: <?php echo esc_html($child_details['blog_id']); ?></div>
											</div>
											<div style="display: flex; gap: 6px; flex-wrap: wrap;">
												<a href="<?php echo esc_url($child_details['url']); ?>" target="_blank" class="button button-small" style="text-decoration: none; font-size: 11px; padding: 3px 6px;">
													View
												</a>
												<a href="<?php echo esc_url($child_details['admin_url']); ?>" target="_blank" class="button button-small" style="text-decoration: none; font-size: 11px; padding: 3px 6px;">
													Admin
												</a>
											</div>
											
											<!-- Grandchildren -->
											<?php if (isset($parent_data['grandchildren'][$child_path]) && !empty($parent_data['grandchildren'][$child_path])) : ?>
												<div class="grandchildren-container" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #00a32a;">
													<?php foreach ($parent_data['grandchildren'][$child_path] as $grandchild_path => $grandchild_site) : 
														$grandchild_details = $site_details[$grandchild_path];
													?>
														<div class="grandchild-card site-card" style="background: #fff3e0; border: 1px solid #d63638; margin-bottom: 10px; margin-left: 20px; padding: 12px;">
															<div style="font-size: 13px; font-weight: 600; color: #d63638; margin-bottom: 6px;">
																<?php echo esc_html($grandchild_details['name']); ?>
															</div>
															<div style="font-size: 10px; color: #666; margin-bottom: 8px;">
																<code style="font-size: 9px;"><?php echo esc_html($grandchild_details['path']); ?></code>
																<span style="margin-left: 8px;">ID: <?php echo esc_html($grandchild_details['blog_id']); ?></span>
															</div>
															<div style="display: flex; gap: 4px;">
																<a href="<?php echo esc_url($grandchild_details['url']); ?>" target="_blank" class="button button-small" style="text-decoration: none; font-size: 10px; padding: 2px 5px; line-height: 1.3;">
																	View
																</a>
																<a href="<?php echo esc_url($grandchild_details['admin_url']); ?>" target="_blank" class="button button-small" style="text-decoration: none; font-size: 10px; padding: 2px 5px; line-height: 1.3;">
																	Admin
																</a>
															</div>
														</div>
													<?php endforeach; ?>
												</div>
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			
			<div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
				<h3>Summary</h3>
				<ul style="list-style: none; padding: 0;">
					<?php
					$total = count($sites);
					$by_level = array();
					foreach ($site_details as $details) {
						$level = $details['depth'];
						if (!isset($by_level[$level])) {
							$by_level[$level] = 0;
						}
						$by_level[$level]++;
					}
					ksort($by_level);
					foreach ($by_level as $level => $count) {
						$level_label = $level === 1 ? 'Parent' : ($level === 2 ? 'Child' : ($level === 3 ? 'Grandchild' : "Level $level"));
						echo '<li style="padding: 5px 0;">' . esc_html($level_label) . ' sites: <strong>' . $count . '</strong></li>';
					}
					?>
					<li style="padding: 5px 0; margin-top: 10px; border-top: 1px solid #ddd; padding-top: 10px;">
						<strong>Total: <?php echo $total; ?> sites</strong>
					</li>
				</ul>
			</div>
		<?php endif; ?>
	</div>
	
	<style>
	.nested-tree-container {
		background: #f8f9fa;
		padding: 30px;
		border-radius: 8px;
	}
	
	.parents-grid {
		animation: fadeIn 0.4s ease-in;
	}
	
	.parent-column {
		display: flex;
		flex-direction: column;
		min-height: 100%;
	}
	
	.parent-card {
		position: sticky;
		top: 20px;
		z-index: 10;
	}
	
	.site-card {
		border-radius: 6px;
		box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		transition: all 0.2s;
		position: relative;
	}
	
	.site-card:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
	}
	
	.parent-card:hover {
		box-shadow: 0 6px 16px rgba(0,115,170,0.3) !important;
	}
	
	.child-card:hover {
		border-color: #00a32a !important;
		box-shadow: 0 4px 8px rgba(0,163,42,0.2) !important;
	}
	
	.grandchild-card:hover {
		border-color: #d63638 !important;
		box-shadow: 0 3px 6px rgba(214,54,56,0.2) !important;
	}
	
	@keyframes fadeIn {
		from {
			opacity: 0;
			transform: translateY(15px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}
	
	.site-card code {
		word-break: break-all;
		font-size: 10px;
		background: rgba(0,0,0,0.05);
		padding: 2px 4px;
		border-radius: 3px;
	}
	
	.button-small {
		padding: 4px 8px;
		font-size: 11px;
		height: auto;
		line-height: 1.4;
	}
	
	@media (max-width: 1400px) {
		.parents-grid {
			grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)) !important;
		}
	}
	</style>
	<?php
}

// Initialize
add_action('plugins_loaded', __NAMESPACE__ . '\\init');

