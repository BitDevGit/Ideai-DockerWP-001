<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header id="masthead" class="site-header">
        <div class="site-branding">
            <h1 class="site-title">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <?php bloginfo('name'); ?>
                </a>
            </h1>
            <?php
            $description = get_bloginfo('description', 'display');
            if ($description || is_customize_preview()) {
                ?>
                <p class="site-description"><?php echo $description; ?></p>
                <?php
            }
            ?>
        </div>
    </header>
    
    <?php
    // FORCE LOAD HOMEPAGE CONTENT FOR NESTED SITES
    // This runs in header so it always executes
    if (!is_admin() && file_exists(ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php')) {
        require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';
        
        $current_blog_id = get_current_blog_id();
        $network_id = get_current_network_id();
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $parsed = parse_url($request_uri);
        $path = $parsed['path'] ?? '/';
        $normalized = Ideai\Wp\Platform\NestedTree\normalize_path($path);
        $current_nested_path = Ideai\Wp\Platform\NestedTree\get_blog_path($current_blog_id, $network_id);
        $is_nested_root = ($current_nested_path && $normalized === $current_nested_path);
        
        if ($is_nested_root) {
            $homepage_id = get_option('page_on_front');
            if ($homepage_id && get_option('show_on_front') === 'page') {
                $homepage = get_post($homepage_id);
                if ($homepage && $homepage->post_status === 'publish') {
                    $GLOBALS['nested_site_homepage'] = $homepage;
                    // OUTPUT CONTENT DIRECTLY - works regardless of template
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
                    exit; // Stop here - content is loaded
                }
            }
        }
    }
    ?>


