<?php
/**
 * Main template file with debugging
 * 
 * @package Test_Cursor_Theme
 */

get_header();

// FORCE LOAD HOMEPAGE CONTENT - works regardless of WordPress query state
$homepage = null;
if (isset($GLOBALS['nested_site_homepage'])) {
    $homepage = $GLOBALS['nested_site_homepage'];
} else {
    // Fallback: load directly
    $homepage_id = get_option('page_on_front');
    if ($homepage_id && get_option('show_on_front') === 'page') {
        $homepage = get_post($homepage_id);
    }
}

if ($homepage && $homepage->post_status === 'publish') {
    ?>
    <main id="main" class="site-main">
        <article id="post-<?php echo $homepage->ID; ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php echo esc_html($homepage->post_title); ?></h1>
            </header>
            <div class="entry-content">
                <?php echo apply_filters('the_content', $homepage->post_content); ?>
            </div>
        </article>
    </main>
    <?php
    get_footer();
    return;
}

// If this is a nested site root, show debug info
if (file_exists(ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php')) {
    require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';
    
    use Ideai\Wp\Platform\NestedTree;
    
    global $wpdb;
    
    $current_blog_id = get_current_blog_id();
    $network_id = get_current_network_id();
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $parsed = parse_url($request_uri);
    $path = $parsed['path'] ?? '/';
    $normalized = NestedTree\normalize_path($path);
    $current_nested_path = NestedTree\get_blog_path($current_blog_id, $network_id);
    $is_nested_root = ($current_nested_path && $normalized === $current_nested_path);
    
    // If at nested root, load front-page.php content
    if ($is_nested_root) {
        $front_page_template = get_template_directory() . '/front-page.php';
        if (file_exists($front_page_template)) {
            // Clear any 404 state
            global $wp_query;
            if ($wp_query) {
                $wp_query->is_404 = false;
                $wp_query->is_home = true;
                $wp_query->is_front_page = true;
            }
            // Include the front-page template
            include $front_page_template;
            // Don't call get_footer() here - front-page.php should handle it
            return;
        }
    }
}

?>

<main id="main" class="site-main">
    <?php
    // Fallback: Try to load homepage if not already loaded
    $homepage_id = get_option('page_on_front');
    if ($homepage_id && get_option('show_on_front') === 'page') {
        $homepage = get_post($homepage_id);
        if ($homepage && $homepage->post_status === 'publish') {
            ?>
            <article id="post-<?php echo $homepage_id; ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php echo esc_html($homepage->post_title); ?></h1>
                </header>
                <div class="entry-content">
                    <?php echo apply_filters('the_content', $homepage->post_content); ?>
                </div>
            </article>
            <?php
        }
    } elseif (have_posts()) {
        while (have_posts()) {
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
            <?php
        }
    } else {
        ?>
        <div class="info-box">
            <h2>Welcome to WordPress!</h2>
            <p>This is a test theme to verify wp-content deployment.</p>
            <p>If you see "Hello Cursor!" above, everything is working! 🎉</p>
        </div>
        <?php
    }
    ?>
</main>

<?php
get_footer();




