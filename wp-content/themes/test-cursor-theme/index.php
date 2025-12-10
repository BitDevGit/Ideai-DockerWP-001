<?php
/**
 * Main template file
 * 
 * @package Test_Cursor_Theme
 */

get_header();
?>

<main id="main" class="site-main">
    <?php
    if (have_posts()) {
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

