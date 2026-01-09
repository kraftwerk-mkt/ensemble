<?php
/**
 * Single Location Template - STAGE LAYOUT
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Load styles
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-layout-stage', ENSEMBLE_PLUGIN_URL . 'templates/layouts/stage/style.css', array('ensemble-base'), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-stage-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Oswald:wght@400;500;600;700&display=swap', array(), ENSEMBLE_VERSION);

$location_id = get_the_ID();
$location = function_exists('es_load_location_data') ? es_load_location_data($location_id) : array();
?>

<div class="ensemble-single-location-wrapper es-layout-stage es-stage-layout">
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <article class="es-location-stage">
        
        <!-- HERO -->
        <header class="es-stage-hero" style="height: 400px;">
            <?php if (has_post_thumbnail()): ?>
            <div class="es-stage-hero-image">
                <?php the_post_thumbnail('full'); ?>
            </div>
            <?php endif; ?>
            
            <div class="es-stage-hero-content">
                <h1 class="es-stage-hero-title"><?php the_title(); ?></h1>
                <?php if (!empty($location['city'])): ?>
                <div class="es-stage-hero-date">
                    
                    <span><?php echo esc_html($location['city']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- CONTENT -->
        <div class="es-stage-content-wrapper">
            <div class="es-stage-main">
                
                <?php if (get_the_content()): ?>
                <section class="es-stage-section">
                    <div class="es-stage-section-header">
                        
                        <h2><?php _e('About', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-stage-description">
                        <?php the_content(); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <?php if (!empty($location['upcoming_events'])): ?>
                <section class="es-stage-section">
                    <div class="es-stage-section-header">
                        
                        <h2><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-stage-related-grid">
                        <?php foreach ($location['upcoming_events'] as $event): ?>
                        <div class="es-stage-card">
                            <a href="<?php echo esc_url($event['permalink']); ?>" class="es-stage-card-inner">
                                <div class="es-stage-card-content">
                                    <time class="es-stage-date">
                                        
                                        <span><?php echo esc_html($event['date_formatted']); ?></span>
                                    </time>
                                    <h3 class="es-stage-title"><?php echo esc_html($event['title']); ?></h3>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
            </div>
            
            <!-- SIDEBAR -->
            <aside class="es-stage-sidebar">
                <div class="es-stage-sidebar-card">
                    <div class="es-stage-meta-list">
                        <?php if (!empty($location['address'])): ?>
                        <div class="es-stage-meta-row">
                            
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Address', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value"><?php echo esc_html($location['address']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['city'])): ?>
                        <div class="es-stage-meta-row">
                            
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('City', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value">
                                    <?php echo esc_html($location['zip_code'] ?? ''); ?> <?php echo esc_html($location['city']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['website'])): ?>
                        <div class="es-stage-meta-row">
                            
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Website', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value">
                                    <a href="<?php echo esc_url($location['website']); ?>" target="_blank" rel="noopener" style="color: var(--lp-primary);">
                                        <?php echo esc_html(preg_replace('#^https?://#', '', $location['website'])); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
        
    </article>
    
    <?php endwhile; endif; ?>
    
</div>

<?php get_footer(); ?>
