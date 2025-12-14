<?php
/**
 * Nested Tree Sitemap UI
 * 
 * Visual diagrammatic view of all nested sites with interconnected connectors
 */

namespace Ideai\Wp\Platform\NestedTreeSitemap;

use Ideai\Wp\Platform;
use Ideai\Wp\Platform\NestedTree;

if (!defined('ABSPATH')) {
	exit;
}

const MENU_SLUG = 'ideai-nested-sitemap';
const PAGE_TITLE = 'Sites Sitemap';
const MENU_TITLE = 'Sitemap';

/**
 * Initialize the admin UI
 */
function init() {
	if (!is_multisite()) {
		return;
	}
	
	$network_id = get_current_network_id();
	if (!Platform\nested_tree_enabled($network_id)) {
		return;
	}
	
	add_action('network_admin_menu', __NAMESPACE__ . '\\add_menu_page', 25);
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
 * Build hierarchical tree structure for visualization
 */
function build_tree_structure($sites) {
	$tree = array();
	
	foreach ($sites as $site) {
		$path = $site['path'];
		$segments = array_filter(explode('/', trim($path, '/')));
		
		$current = &$tree;
		$current_path = '/';
		
		foreach ($segments as $segment) {
			$current_path = '/' . implode('/', array_slice($segments, 0, array_search($segment, $segments) + 1)) . '/';
			
			if (!isset($current['children'][$current_path])) {
				$current['children'][$current_path] = array(
					'path' => $current_path,
					'site' => null,
					'children' => array(),
				);
			}
			
			// If this is the actual site path, store the site data
			if ($current_path === $path) {
				$current['children'][$current_path]['site'] = $site;
			}
			
			$current = &$current['children'][$current_path];
		}
	}
	
	return $tree;
}

/**
 * Calculate positions for tree layout (hierarchical)
 */
function calculate_positions($node, $level = 0, $x_offset = 0, $y_spacing = 150, $x_spacing = 350) {
	$positions = array();
	
	if (empty($node['children'])) {
		return $positions;
	}
	
	$children = $node['children'];
	$count = count($children);
	$total_width = ($count - 1) * $x_spacing;
	$start_x = $x_offset - ($total_width / 2);
	
	$index = 0;
	foreach ($children as $path => $child_node) {
		$x_pos = $start_x + ($index * $x_spacing);
		$y_pos = $level * $y_spacing + 100;
		
		$pos = array(
			'x' => $x_pos,
			'y' => $y_pos,
			'site' => $child_node['site'],
			'path' => $path,
			'children' => array(),
		);
		
		// Recursively calculate children positions
		if (!empty($child_node['children'])) {
			$child_positions = calculate_positions($child_node, $level + 1, $x_pos, $y_spacing, $x_spacing);
			$pos['children'] = $child_positions;
		}
		
		$positions[$path] = $pos;
		$index++;
	}
	
	return $positions;
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
	
	// Build tree structure properly
	$tree = array('path' => '/', 'children' => array());
	
	foreach ($sites as $site) {
		$path = $site['path'];
		$segments = array_filter(explode('/', trim($path, '/')));
		
		$current = &$tree;
		$built_path = '/';
		
		foreach ($segments as $index => $segment) {
			// Build path up to current segment
			$built_path = '/' . implode('/', array_slice($segments, 0, $index + 1)) . '/';
			
			// Navigate/create tree structure
			if (!isset($current['children'][$built_path])) {
				$current['children'][$built_path] = array(
					'path' => $built_path,
					'site' => null,
					'children' => array(),
				);
			}
			
			// If this is the actual site path, store the site data
			if ($built_path === $path) {
				$current['children'][$built_path]['site'] = $site;
			}
			
			// Move to next level
			$current = &$current['children'][$built_path];
		}
	}
	
	// Calculate positions (centered at x=0)
	$positions = calculate_positions($tree, 0, 0);
	
	// Flatten positions for rendering
	$flat_positions = array();
	function flatten_positions($pos_array, &$flat) {
		foreach ($pos_array as $path => $data) {
			$flat[$path] = $data;
			if (!empty($data['children'])) {
				flatten_positions($data['children'], $flat);
			}
		}
	}
	flatten_positions($positions, $flat_positions);
	
	// Find center and adjust all positions to be centered
	$min_x = 0;
	$max_x = 0;
	$min_y = 0;
	$max_y = 0;
	foreach ($flat_positions as $pos) {
		$min_x = min($min_x, $pos['x']);
		$max_x = max($max_x, $pos['x']);
		$min_y = min($min_y, $pos['y']);
		$max_y = max($max_y, $pos['y']);
	}
	
	$center_x = ($min_x + $max_x) / 2;
	$offset_x = -$center_x + 200; // Center with 200px margin
	
	// Adjust all positions (including nested children)
	function adjust_positions_recursive($pos_array, $offset_x) {
		foreach ($pos_array as $path => &$pos) {
			$pos['x'] += $offset_x;
			if (!empty($pos['children'])) {
				adjust_positions_recursive($pos['children'], $offset_x);
			}
		}
		return $pos_array;
	}
	$positions = adjust_positions_recursive($positions, $offset_x);
	
	// Re-flatten after adjustment
	$flat_positions = array();
	flatten_positions($positions, $flat_positions);
	
	// Calculate SVG dimensions
	$max_x = 0;
	$max_y = 0;
	foreach ($flat_positions as $pos) {
		$max_x = max($max_x, $pos['x']);
		$max_y = max($max_y, $pos['y']);
	}
	
	$svg_width = max(1400, $max_x + 400);
	$svg_height = max(900, $max_y + 250);
	
	?>
	<div class="wrap">
		<h1><?php echo esc_html(PAGE_TITLE); ?></h1>
		<p class="description">Visual diagram of all nested sites showing parent-to-child relationships.</p>
		
		<?php if (empty($sites)) : ?>
			<div class="notice notice-info">
				<p>No nested sites found. <a href="<?php echo esc_url(network_admin_url('admin.php?page=ideai-nested-site-creator')); ?>">Create your first nested site</a></p>
			</div>
		<?php else : ?>
			<div style="margin: 20px 0; padding: 15px; background: #f0f0f1; border-radius: 4px;">
				<strong>Controls:</strong> Click and drag to pan | Scroll to zoom | Click site cards to visit
			</div>
			
			<div id="sitemap-container" style="position: relative; width: 100%; height: 90vh; border: 2px solid #ddd; border-radius: 8px; background: #fafafa; overflow: hidden;">
				<svg id="sitemap-svg" width="<?php echo $svg_width; ?>" height="<?php echo $svg_height; ?>" style="display: block;">
					<defs>
						<marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
							<polygon points="0 0, 10 3, 0 6" fill="#666" />
						</marker>
					</defs>
					
					<!-- Draw connectors -->
					<g id="connectors">
						<?php
						// Draw connectors by checking actual parent-child relationships
						// Build a map of path to position for quick lookup
						$path_to_pos = array();
						foreach ($flat_positions as $path => $pos) {
							if ($pos['site']) {
								$path_to_pos[$path] = $pos;
							}
						}
						
						// For each site, find and connect to its direct children
						foreach ($path_to_pos as $parent_path => $parent_pos) {
							$parent_segments = array_filter(explode('/', trim($parent_path, '/')));
							$parent_depth = count($parent_segments);
							
							// Look for direct children (exactly one level deeper)
							foreach ($path_to_pos as $child_path => $child_pos) {
								if ($child_path === $parent_path) {
									continue; // Skip self
								}
								
								$child_segments = array_filter(explode('/', trim($child_path, '/')));
								$child_depth = count($child_segments);
								
								// Must be exactly one level deeper
								if ($child_depth !== $parent_depth + 1) {
									continue;
								}
								
								// CRITICAL: Check if child's parent path exactly matches parent_path
								// Get the parent path of the child by removing the last segment
								$child_parent_segments = array_slice($child_segments, 0, -1);
								$child_parent_path = '/' . implode('/', $child_parent_segments) . '/';
								
								// Only connect if the child's parent path exactly matches this parent
								if ($child_parent_path === $parent_path) {
									// This is a direct child - draw connector
									$x1 = $parent_pos['x'] + 150; // Right edge of parent card
									$y1 = $parent_pos['y'] + 40; // Center of parent card
									$x2 = $child_pos['x'] + 150; // Right edge of child card
									$y2 = $child_pos['y'] + 40; // Center of child card
									
									// Create curved path
									$mid_x = ($x1 + $x2) / 2;
									$control_y = $y1 + 40;
									
									// Color by parent level
									$parent_site = $site_details[$parent_path];
									$parent_level = $parent_site['depth'];
									$colors = array(
										1 => '#0073aa',
										2 => '#00a32a',
										3 => '#d63638',
									);
									$stroke_color = isset($colors[$parent_level]) ? $colors[$parent_level] : '#666';
									
									echo '<path d="M ' . $x1 . ' ' . $y1 . ' Q ' . $mid_x . ' ' . $control_y . ' ' . $x2 . ' ' . $y2 . '" stroke="' . $stroke_color . '" stroke-width="2.5" fill="none" marker-end="url(#arrowhead)" opacity="0.7" />';
								}
							}
						}
						?>
					</g>
					
					<!-- Draw site cards -->
					<g id="site-cards">
						<?php
						foreach ($flat_positions as $path => $pos) {
							if (!$pos['site']) {
								continue;
							}
							
							$site = $site_details[$path];
							$depth = $site['depth'];
							
							// Color by depth
							$colors = array(
								1 => array('bg' => '#0073aa', 'text' => '#fff', 'border' => '#005177'),
								2 => array('bg' => '#00a32a', 'text' => '#fff', 'border' => '#007a20'),
								3 => array('bg' => '#d63638', 'text' => '#fff', 'border' => '#b32d2e'),
								4 => array('bg' => '#dba617', 'text' => '#fff', 'border' => '#b8860b'),
							);
							$color = isset($colors[$depth]) ? $colors[$depth] : array('bg' => '#666', 'text' => '#fff', 'border' => '#444');
							
							$x = $pos['x'];
							$y = $pos['y'];
							
							// Card background
							echo '<rect x="' . $x . '" y="' . $y . '" width="300" height="80" rx="8" fill="' . $color['bg'] . '" stroke="' . $color['border'] . '" stroke-width="2" class="site-card" data-path="' . esc_attr($path) . '" style="cursor: pointer;" />';
							
							// Site name
							echo '<text x="' . ($x + 150) . '" y="' . ($y + 30) . '" text-anchor="middle" fill="' . $color['text'] . '" font-size="14" font-weight="600" class="site-name">' . esc_html($site['name']) . '</text>';
							
							// Path
							echo '<text x="' . ($x + 150) . '" y="' . ($y + 50) . '" text-anchor="middle" fill="' . $color['text'] . '" font-size="11" opacity="0.9" class="site-path">' . esc_html($path) . '</text>';
							
							// Level badge
							echo '<text x="' . ($x + 280) . '" y="' . ($y + 25) . '" text-anchor="middle" fill="' . $color['text'] . '" font-size="10" font-weight="600" opacity="0.8">L' . $depth . '</text>';
						}
						?>
					</g>
				</svg>
			</div>
			
			<div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
				<h3>Legend</h3>
				<div style="display: flex; gap: 20px; flex-wrap: wrap;">
					<div><span style="display: inline-block; width: 20px; height: 20px; background: #0073aa; border-radius: 4px; vertical-align: middle; margin-right: 5px;"></span> Level 1 - Parents</div>
					<div><span style="display: inline-block; width: 20px; height: 20px; background: #00a32a; border-radius: 4px; vertical-align: middle; margin-right: 5px;"></span> Level 2 - Children</div>
					<div><span style="display: inline-block; width: 20px; height: 20px; background: #d63638; border-radius: 4px; vertical-align: middle; margin-right: 5px;"></span> Level 3 - Grandchildren</div>
					<div><span style="display: inline-block; width: 20px; height: 20px; background: #dba617; border-radius: 4px; vertical-align: middle; margin-right: 5px;"></span> Level 4+</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
	
	<style>
	#sitemap-container {
		position: relative;
	}
	
	.site-card:hover {
		filter: brightness(1.1);
		stroke-width: 3 !important;
	}
	
	.site-name {
		pointer-events: none;
	}
	
	.site-path {
		pointer-events: none;
	}
	</style>
	
	<script>
	(function() {
		const container = document.getElementById('sitemap-container');
		const svg = document.getElementById('sitemap-svg');
		const siteCards = document.querySelectorAll('.site-card');
		
		// Pan and zoom functionality
		let isPanning = false;
		let startPoint = { x: 0, y: 0 };
		let viewBox = { x: 0, y: 0, width: container.offsetWidth, height: container.offsetHeight };
		
		svg.setAttribute('viewBox', `0 0 ${viewBox.width} ${viewBox.height}`);
		svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
		
		// Pan
		container.addEventListener('mousedown', function(e) {
			if (e.target.classList.contains('site-card')) return;
			isPanning = true;
			startPoint = { x: e.clientX - viewBox.x, y: e.clientY - viewBox.y };
		});
		
		container.addEventListener('mousemove', function(e) {
			if (!isPanning) return;
			viewBox.x = e.clientX - startPoint.x;
			viewBox.y = e.clientY - startPoint.y;
			svg.setAttribute('viewBox', `${viewBox.x} ${viewBox.y} ${viewBox.width} ${viewBox.height}`);
		});
		
		container.addEventListener('mouseup', function() {
			isPanning = false;
		});
		
		container.addEventListener('mouseleave', function() {
			isPanning = false;
		});
		
		// Zoom
		container.addEventListener('wheel', function(e) {
			e.preventDefault();
			const delta = e.deltaY > 0 ? 1.1 : 0.9;
			viewBox.width *= delta;
			viewBox.height *= delta;
			svg.setAttribute('viewBox', `${viewBox.x} ${viewBox.y} ${viewBox.width} ${viewBox.height}`);
		});
		
		// Click site cards
		siteCards.forEach(function(card) {
			card.addEventListener('click', function() {
				const path = this.getAttribute('data-path');
				const siteData = <?php echo json_encode($site_details); ?>;
				if (siteData[path]) {
					window.open(siteData[path].url, '_blank');
				}
			});
		});
		
		// Reset view button
		const resetBtn = document.createElement('button');
		resetBtn.textContent = 'Reset View';
		resetBtn.className = 'button';
		resetBtn.style.position = 'absolute';
		resetBtn.style.top = '10px';
		resetBtn.style.right = '10px';
		resetBtn.style.zIndex = '1000';
		resetBtn.addEventListener('click', function() {
			viewBox = { x: 0, y: 0, width: container.offsetWidth, height: container.offsetHeight };
			svg.setAttribute('viewBox', `0 0 ${viewBox.width} ${viewBox.height}`);
		});
		container.style.position = 'relative';
		container.parentElement.insertBefore(resetBtn, container);
	})();
	</script>
	<?php
}

// Initialize
add_action('plugins_loaded', __NAMESPACE__ . '\\init');

