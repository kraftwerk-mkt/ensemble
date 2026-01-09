<?php
/**
 * Single Location Template - Noir Elegance
 * Full-width dark mode, minimal and refined
 *
 * @package Ensemble
 * @version 3.1.0
 */

if (!defined('ABSPATH')) exit;

get_header();

wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);

$location_id = get_the_ID();
$location = es_load_location_data($location_id);
$upcoming_events = es_get_upcoming_events_at_location($location_id);

$address_data = array(
    'address' => $location['address'],
    'city' => $location['city'],
    'state' => $location['state'],
    'zip' => $location['zip'],
    'country' => $location['country'],
);

$contact_data = array(
    'phone' => $location['phone'],
    'email' => $location['email'],
    'website' => $location['website'],
);
?>

<div class="es-noir-single es-noir-location">
    
    <?php ensemble_before_location($location_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- Hero -->
    <header class="es-noir-hero">
        <?php if (has_post_thumbnail()): ?>
        <div class="es-noir-hero-media">
            <?php the_post_thumbnail('full'); ?>
            <div class="es-noir-hero-overlay"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-noir-hero-content">
            
            <?php if ($location['city']): ?>
            <span class="es-noir-hero-location">
                <?php echo esc_html($location['city']); ?>
                <?php if ($location['country']) echo ', ' . esc_html($location['country']); ?>
            </span>
            <?php endif; ?>
            
            <h1 class="es-noir-hero-title"><?php the_title(); ?></h1>
            
            <?php if ($location['capacity']): ?>
            <span class="es-noir-hero-capacity">
                <?php echo number_format_i18n($location['capacity']); ?> <?php _e('Capacity', 'ensemble'); ?>
            </span>
            <?php endif; ?>
            
            <?php ensemble_after_location_title($location_id); ?>
        </div>
        
        <?php ensemble_location_header($location_id); ?>
    </header>
    
    <!-- Content -->
    <div class="es-noir-body">
        <div class="es-noir-container">
            
            <div class="es-noir-layout">
                
                <!-- Main -->
                <main class="es-noir-main">
                    
                    <!-- Description -->
                    <?php if (get_the_content()): ?>
                    <section class="es-noir-section es-noir-description">
                        <?php ensemble_before_location_content($location_id); ?>
                        <h2 class="es-noir-section-title"><?php _e('About Us', 'ensemble'); ?></h2>
                        <div class="es-noir-prose">
                            <?php the_content(); ?>
                        </div>
                        <?php ensemble_after_location_description($location_id); ?>
                    </section>
                    <?php endif; ?>
                    
                    <?php ensemble_location_meta($location_id, $location); ?>
                    
                    <!-- Map -->
                    <?php 
                    if (ensemble_has_addon_hook('location_map')) {
                        echo '<section class="es-noir-section es-noir-map">';
                        ensemble_location_map($location_id, $address_data);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Catalog -->
                    <?php 
                    if (ensemble_has_addon_hook('location_catalog')) {
                        echo '<section class="es-noir-section es-noir-catalog">';
                        ensemble_location_catalog($location_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Gallery -->
                    <?php 
                    if (ensemble_has_addon_hook('location_gallery')) {
                        echo '<section class="es-noir-section es-noir-gallery">';
                        echo '<h2 class="es-noir-section-title">' . __('Gallery', 'ensemble') . '</h2>';
                        ensemble_location_gallery($location_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Events -->
                    <?php if ($upcoming_events->have_posts()): ?>
                    <section class="es-noir-section es-noir-events">
                        <h2 class="es-noir-section-title"><?php _e('Kommende Events', 'ensemble'); ?></h2>
                        
                        <?php ensemble_location_events($location_id); ?>
                        
                        <div class="es-noir-event-list">
                            <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); 
                                $evt = es_load_event_data(get_the_ID());
                                $evt_timestamp = strtotime($evt['date']);
                            ?>
                            <a href="<?php echo esc_url($evt['permalink']); ?>" class="es-noir-event-row">
                                <time class="es-noir-event-date">
                                    <span class="es-noir-event-day"><?php echo date_i18n('d', $evt_timestamp); ?></span>
                                    <span class="es-noir-event-month"><?php echo date_i18n('M', $evt_timestamp); ?></span>
                                </time>
                                <div class="es-noir-event-info">
                                    <h4><?php echo esc_html($evt['title']); ?></h4>
                                    <?php if ($evt['formatted_time']): ?>
                                        <span><?php echo esc_html($evt['formatted_time']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="es-noir-event-arrow">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </a>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php ensemble_after_location_events($location_id, $upcoming_events); ?>
                    </section>
                    <?php endif; wp_reset_postdata(); ?>
                    
                    <?php ensemble_location_footer($location_id); ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="es-noir-aside">
                    
                    <div class="es-noir-info-card">
                        
                        <!-- Address -->
                        <?php if ($location['address'] || $location['city']): ?>
                        <div class="es-noir-info-row">
                            <span class="es-noir-info-label"><?php _e('Adresse', 'ensemble'); ?></span>
                            <span class="es-noir-info-value">
                                <?php 
                                $parts = array_filter(array($location['address'], $location['zip'] . ' ' . $location['city']));
                                echo esc_html(implode(', ', $parts));
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Phone -->
                        <?php if ($location['phone']): ?>
                        <div class="es-noir-info-row">
                            <span class="es-noir-info-label"><?php _e('Telefon', 'ensemble'); ?></span>
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $location['phone'])); ?>" class="es-noir-info-value es-noir-info-link">
                                <?php echo esc_html($location['phone']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Email -->
                        <?php if ($location['email']): ?>
                        <div class="es-noir-info-row">
                            <span class="es-noir-info-label"><?php _e('E-Mail', 'ensemble'); ?></span>
                            <a href="mailto:<?php echo esc_attr($location['email']); ?>" class="es-noir-info-value es-noir-info-link">
                                <?php echo esc_html($location['email']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Website -->
                        <?php if ($location['website']): ?>
                        <div class="es-noir-info-row">
                            <a href="<?php echo esc_url($location['website']); ?>" class="es-noir-btn es-noir-btn-outline" target="_blank" rel="noopener">
                                <?php _e('Website besuchen', 'ensemble'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <!-- Contact -->
                    <?php 
                    if (ensemble_has_addon_hook('location_contact')) {
                        ensemble_location_contact($location_id, $contact_data);
                    }
                    ?>
                    
                    <?php 
                    if (ensemble_has_addon_hook('location_sidebar')) {
                        ensemble_location_sidebar($location_id);
                    }
                    ?>
                    
                </aside>
                
            </div>
            
        </div>
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php ensemble_after_location_hook($location_id, $location); ?>
    
</div>

<?php get_footer(); ?>
