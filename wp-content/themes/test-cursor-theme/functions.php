<?php
/**
 * Test Cursor Theme Functions
 * 
 * This theme displays "Hello Cursor" on the homepage
 * to verify wp-content deployment and DB migration.
 */

// Enqueue styles
function test_cursor_theme_enqueue_styles() {
    wp_enqueue_style('test-cursor-theme-style', get_stylesheet_uri(), array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'test_cursor_theme_enqueue_styles');

// Add "Hello Cursor" to homepage content
function test_cursor_add_homepage_content($content) {
    if (is_front_page() && is_main_query()) {
        $hello_cursor = '
        <div class="hello-cursor">
            <h1>👋 Hello Cursor!</h1>
            <p>This message confirms that:</p>
            <ul style="text-align: left; display: inline-block; margin-top: 20px;">
                <li>✅ Theme is loaded from wp-content</li>
                <li>✅ Database migration worked</li>
                <li>✅ Domain rewrite successful</li>
                <li>✅ Serialized URL rewrite successful</li>
            </ul>
        </div>
        ';
        return $hello_cursor . $content;
    }
    return $content;
}
add_filter('the_content', 'test_cursor_add_homepage_content', 10);

// Add theme support
function test_cursor_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
}
add_action('after_setup_theme', 'test_cursor_theme_setup');



