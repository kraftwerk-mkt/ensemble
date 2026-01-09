<?php
/**
 * Single Artist Featured Wrapper Template
 * 
 * This template outputs the featured shortcode for direct artist links
 * when the artist has "featured" layout selected.
 * Slider is automatically shown when multiple artists exist.
 *
 * @package Ensemble
 * @version 2.9.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $post;

get_header();
?>

<main id="primary" class="et-site-main">
    <article id="post-<?php the_ID(); ?>" <?php post_class('et-page'); ?>>
        <div class="et-container">
            <div class="et-content">
                <div class="et-page-content">
                    <?php 
                    // Use the shortcode to render - slider is auto-detected
                    echo do_shortcode('[ensemble_artist id="' . $post->ID . '" layout="featured"]');
                    ?>
                </div>
            </div>
        </div>
    </article>
</main>

<?php
get_footer();
