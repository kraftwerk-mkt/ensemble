<?php
/**
 * Single Location Template - SIMPLE CLUB LAYOUT
 * 
 * Clean club-style layout with:
 * - Large hero image with gradient overlay
 * - Location name and address prominently displayed
 * - Contact info and map
 * - Upcoming events at this venue
 *
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue base CSS
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);

// Enqueue Simple Club Layout CSS
wp_enqueue_style('ensemble-layout-simpleclub', ENSEMBLE_PLUGIN_URL . 'templates/layouts/simpleclub/style.css', array('ensemble-base'), ENSEMBLE_VERSION);

// Load Montserrat font
wp_enqueue_style('ensemble-simpleclub-font', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap', array(), ENSEMBLE_VERSION);

$location_id = get_the_ID();
$location = es_load_location_data($location_id);
$upcoming_events = es_get_upcoming_events_by_location($location_id);

// Build full address
$address_parts = array();
if (!empty($location['street'])) $address_parts[] = $location['street'];
if (!empty($location['city'])) $address_parts[] = $location['city'];
if (!empty($location['postal_code'])) $address_parts[] = $location['postal_code'];
if (!empty($location['country'])) $address_parts[] = $location['country'];
$full_address = implode(', ', $address_parts);

// Google Maps URL for directions
$maps_url = '';
if ($full_address) {
    $maps_url = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($full_address);
}
?>

<div class="es-simpleclub-single es-simpleclub-location-single">
    
    <?php if (function_exists('ensemble_before_location')) ensemble_before_location($location_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- Hero -->
    <header class="es-simpleclub-hero">
        <?php if (has_post_thumbnail()): ?>
        <div class="es-simpleclub-hero-media">
            <?php the_post_thumbnail('full'); ?>
            <div class="es-simpleclub-hero-overlay"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-simpleclub-hero-content">
            
            <?php if (!empty($location['type'])): ?>
            <span class="es-simpleclub-hero-badge"><?php echo esc_html($location['type']); ?></span>
            <?php endif; ?>
            
            <h1 class="es-simpleclub-hero-title"><?php the_title(); ?></h1>
            
            <?php if (!empty($location['city'])): ?>
            <div class="es-simpleclub-hero-location">
                <?php echo esc_html($location['city']); ?>
                <?php if (!empty($location['country'])): ?>
                    <span class="es-simpleclub-hero-country"><?php echo esc_html($location['country']); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (function_exists('ensemble_after_location_title')) ensemble_after_location_title($location_id); ?>
        </div>
    </header>
    
    <!-- Content -->
    <div class="es-simpleclub-body">
        <div class="es-simpleclub-container">
            
            <div class="es-simpleclub-layout-grid">
                
                <!-- Main -->
                <main class="es-simpleclub-main">
                    
                    <?php if (function_exists('ensemble_before_location_content')) ensemble_before_location_content($location_id); ?>
                    
                    <!-- Description -->
                    <?php if (get_the_content()): ?>
                    <section class="es-simpleclub-section es-simpleclub-description">
                        <h2 class="es-simpleclub-section-title"><?php _e('About', 'ensemble'); ?></h2>
                        <div class="es-simpleclub-prose">
                            <?php the_content(); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if (function_exists('ensemble_after_location_content')) ensemble_after_location_content($location_id); ?>
                    
                    <!-- Map -->
                    <?php if (!empty($location['lat']) && !empty($location['lng'])): ?>
                    <section class="es-simpleclub-section es-simpleclub-map-section">
                        <h2 class="es-simpleclub-section-title"><?php _e('Location', 'ensemble'); ?></h2>
                        <?php if (function_exists('ensemble_location_map')) {
                            ensemble_location_map($location_id, $location);
                        } else { ?>
                        <div class="es-simpleclub-map-placeholder">
                            <a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener" class="es-simpleclub-map-link">
                                <?php _e('Open in Google Maps', 'ensemble'); ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                    <polyline points="15 3 21 3 21 9"/>
                                    <line x1="10" y1="14" x2="21" y2="3"/>
                                </svg>
                            </a>
                        </div>
                        <?php } ?>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Upcoming Events at this Location -->
                    <?php if (!empty($upcoming_events)): ?>
                    <section class="es-simpleclub-section es-simpleclub-events-section">
                        <h2 class="es-simpleclub-section-title"><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                        <div class="es-simpleclub-events-grid">
                            <?php foreach (array_slice($upcoming_events, 0, 6) as $event): 
                                $event_date = strtotime($event['date']);
                            ?>
                            <a href="<?php echo esc_url($event['permalink']); ?>" class="es-simpleclub-event-card-mini">
                                <?php if (!empty($event['image'])): ?>
                                <div class="es-simpleclub-event-thumb">
                                    <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>" loading="lazy">
                                </div>
                                <?php endif; ?>
                                <div class="es-simpleclub-event-info">
                                    <time class="es-simpleclub-event-date-inline">
                                        <?php echo date_i18n('j. M Y', $event_date); ?>
                                    </time>
                                    <span class="es-simpleclub-event-title-mini"><?php echo esc_html($event['title']); ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Gallery (Addon Hook) -->
                    <?php 
                    if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_gallery')) {
                        echo '<section class="es-simpleclub-section es-simpleclub-gallery">';
                        echo '<h2 class="es-simpleclub-section-title">' . __('Gallery', 'ensemble') . '</h2>';
                        if (function_exists('ensemble_location_gallery')) ensemble_location_gallery($location_id);
                        echo '</section>';
                    }
                    ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="es-simpleclub-sidebar">
                    
                    <!-- Location Info Card -->
                    <div class="es-simpleclub-sidebar-card es-simpleclub-info-card">
                        <h3 class="es-simpleclub-card-label"><?php _e('Info', 'ensemble'); ?></h3>
                        
                        <!-- Address -->
                        <?php if ($full_address): ?>
                        <div class="es-simpleclub-info-row">
                            <span class="es-simpleclub-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </span>
                            <div class="es-simpleclub-info-content">
                                <span class="es-simpleclub-info-label"><?php _e('Address', 'ensemble'); ?></span>
                                <span class="es-simpleclub-info-value"><?php echo esc_html($full_address); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Capacity -->
                        <?php if (!empty($location['capacity'])): ?>
                        <div class="es-simpleclub-info-row">
                            <span class="es-simpleclub-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </span>
                            <div class="es-simpleclub-info-content">
                                <span class="es-simpleclub-info-label"><?php _e('Capacity', 'ensemble'); ?></span>
                                <span class="es-simpleclub-info-value"><?php echo esc_html($location['capacity']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Website -->
                        <?php if (!empty($location['website'])): ?>
                        <div class="es-simpleclub-info-row">
                            <span class="es-simpleclub-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                </svg>
                            </span>
                            <div class="es-simpleclub-info-content">
                                <span class="es-simpleclub-info-label"><?php _e('Website', 'ensemble'); ?></span>
                                <a href="<?php echo esc_url($location['website']); ?>" class="es-simpleclub-info-link" target="_blank" rel="noopener">
                                    <?php echo esc_html(preg_replace('#^https?://(www\.)?#', '', $location['website'])); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Phone -->
                        <?php if (!empty($location['phone'])): ?>
                        <div class="es-simpleclub-info-row">
                            <span class="es-simpleclub-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                            </span>
                            <div class="es-simpleclub-info-content">
                                <span class="es-simpleclub-info-label"><?php _e('Phone', 'ensemble'); ?></span>
                                <a href="tel:<?php echo esc_attr($location['phone']); ?>" class="es-simpleclub-info-link">
                                    <?php echo esc_html($location['phone']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Email -->
                        <?php if (!empty($location['email'])): ?>
                        <div class="es-simpleclub-info-row">
                            <span class="es-simpleclub-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </span>
                            <div class="es-simpleclub-info-content">
                                <span class="es-simpleclub-info-label"><?php _e('Email', 'ensemble'); ?></span>
                                <a href="mailto:<?php echo esc_attr($location['email']); ?>" class="es-simpleclub-info-link">
                                    <?php echo esc_html($location['email']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Route Button -->
                        <?php if ($maps_url): ?>
                        <div class="es-simpleclub-action-row">
                            <a href="<?php echo esc_url($maps_url); ?>" class="es-simpleclub-action-btn es-simpleclub-route-btn" target="_blank" rel="noopener">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                                </svg>
                                <?php _e('Get Directions', 'ensemble'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <!-- Social Links -->
                    <?php 
                    $social_links = array(
                        'facebook' => !empty($location['facebook']) ? $location['facebook'] : '',
                        'instagram' => !empty($location['instagram']) ? $location['instagram'] : '',
                        'twitter' => !empty($location['twitter']) ? $location['twitter'] : '',
                    );
                    $has_social = !empty(array_filter($social_links));
                    
                    if ($has_social): ?>
                    <div class="es-simpleclub-sidebar-card es-simpleclub-social-card">
                        <h3 class="es-simpleclub-card-label"><?php _e('Follow', 'ensemble'); ?></h3>
                        <div class="es-simpleclub-social-links">
                            <?php if (!empty($social_links['facebook'])): ?>
                            <a href="<?php echo esc_url($social_links['facebook']); ?>" target="_blank" rel="noopener" title="Facebook" class="es-simpleclub-social-btn">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social_links['instagram'])): ?>
                            <a href="<?php echo esc_url($social_links['instagram']); ?>" target="_blank" rel="noopener" title="Instagram" class="es-simpleclub-social-btn">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social_links['twitter'])): ?>
                            <a href="<?php echo esc_url($social_links['twitter']); ?>" target="_blank" rel="noopener" title="Twitter/X" class="es-simpleclub-social-btn">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (function_exists('ensemble_location_sidebar')) ensemble_location_sidebar($location_id); ?>
                    
                </aside>
                
            </div>
            
        </div>
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php if (function_exists('ensemble_after_location')) ensemble_after_location($location_id); ?>
    
</div>

<?php get_footer(); ?>
