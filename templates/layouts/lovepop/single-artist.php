<?php
/**
 * Single Artist Template - LOVEPOP LAYOUT
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Load styles
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-layout-lovepop', ENSEMBLE_PLUGIN_URL . 'templates/layouts/lovepop/style.css', array('ensemble-base'), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-lovepop-font', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap', array(), ENSEMBLE_VERSION);

$artist_id = get_the_ID();
$artist = function_exists('es_load_artist_data') ? es_load_artist_data($artist_id) : array();
?>

<div class="ensemble-container es-lovepop-layout">
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <article class="es-artist-lovepop">
        
        <!-- HERO -->
        <header class="es-lovepop-hero" style="height: 400px;">
            <?php if (has_post_thumbnail()): ?>
            <div class="es-lovepop-hero-image">
                <?php the_post_thumbnail('full'); ?>
            </div>
            <?php endif; ?>
            
            <div class="es-lovepop-hero-content">
                <h1 class="es-lovepop-hero-title"><?php the_title(); ?></h1>
                <?php if (!empty($artist['genre'])): ?>
                <div class="es-lovepop-hero-date">
                    
                    <span><?php echo esc_html($artist['genre']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- CONTENT -->
        <div class="es-lovepop-content-wrapper" style="grid-template-columns: 1fr;">
            <div class="es-lovepop-main">
                
                <?php if (get_the_content()): ?>
                <section class="es-lovepop-section">
                    <div class="es-lovepop-section-header">
                        
                        <h2><?php _e('About', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-lovepop-description">
                        <?php the_content(); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <?php if (!empty($artist['upcoming_events'])): ?>
                <section class="es-lovepop-section">
                    <div class="es-lovepop-section-header">
                        
                        <h2><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-lovepop-related-grid">
                        <?php foreach ($artist['upcoming_events'] as $event): ?>
                        <div class="es-lovepop-card">
                            <a href="<?php echo esc_url($event['permalink']); ?>" class="es-lovepop-card-inner">
                                <div class="es-lovepop-card-content">
                                    <time class="es-lovepop-date">
                                        
                                        <span><?php echo esc_html($event['date_formatted']); ?></span>
                                    </time>
                                    <h3 class="es-lovepop-title"><?php echo esc_html($event['title']); ?></h3>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
            </div>
        </div>
        
    </article>
    
    <?php endwhile; endif; ?>
    
</div>

<?php get_footer(); ?>
