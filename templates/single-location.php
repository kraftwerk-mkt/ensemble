<?php
/**
 * Single Location Template (Template System Version)
 * 
 * Template for displaying a single location with full Template System support
 * Can be overridden by copying to: /your-theme/ensemble/single-location.php
 *
 * @package Ensemble
 * @version 1.9.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Enqueue frontend CSS if not already loaded
if (!wp_style_is('ensemble-frontend-css', 'enqueued')) {
    wp_enqueue_style(
        'ensemble-frontend-css',
        ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css',
        array(),
        ENSEMBLE_VERSION
    );
}

// Load custom template CSS
if (class_exists('ES_CSS_Generator')) {
    $custom_css = ES_CSS_Generator::generate();
    echo '<style id="ensemble-single-location-custom-css">' . $custom_css . '</style>';
}

// Get location data - Try multiple field name formats for compatibility
$location_id = get_the_ID();

$address = get_field('location_address', $location_id) ?: 
           get_post_meta($location_id, 'location_address', true) ?: 
           get_post_meta($location_id, 'es_location_address', true);

$city = get_field('location_city', $location_id) ?: 
        get_post_meta($location_id, 'location_city', true) ?: 
        get_post_meta($location_id, 'es_location_city', true);

$state = get_field('location_state', $location_id) ?: 
         get_post_meta($location_id, 'location_state', true) ?: 
         get_post_meta($location_id, 'es_location_state', true);

$zip = get_field('location_zip', $location_id) ?: 
       get_post_meta($location_id, 'location_zip', true) ?: 
       get_post_meta($location_id, 'es_location_zip', true);

$country = get_field('location_country', $location_id) ?: 
           get_post_meta($location_id, 'location_country', true) ?: 
           get_post_meta($location_id, 'es_location_country', true);

$phone = get_field('location_phone', $location_id) ?: 
         get_post_meta($location_id, 'location_phone', true) ?: 
         get_post_meta($location_id, 'es_location_phone', true);

$email = get_field('location_email', $location_id) ?: 
         get_post_meta($location_id, 'location_email', true) ?: 
         get_post_meta($location_id, 'es_location_email', true);

$website = get_field('location_website', $location_id) ?: 
           get_post_meta($location_id, 'location_website', true) ?: 
           get_post_meta($location_id, 'es_location_website', true);

$capacity = get_field('location_capacity', $location_id) ?: 
            get_post_meta($location_id, 'location_capacity', true) ?: 
            get_post_meta($location_id, 'es_location_capacity', true);

// Get upcoming events at this location
$upcoming_events = new WP_Query(array(
    'post_type' => ensemble_get_post_type(),
    'posts_per_page' => 10,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => 'es_event_location',
            'value' => $location_id,
            'compare' => '='
        )
    ),
    'orderby' => 'meta_value',
    'meta_key' => 'es_event_start_date',
    'order' => 'ASC'
));
?>

<div class="ensemble-container">
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <article class="ensemble-single-location">
        
        <!-- Location Header with Image -->
        <?php if (has_post_thumbnail()): ?>
        <div class="ensemble-location-image">
            <?php the_post_thumbnail('large'); ?>
        </div>
        <?php endif; ?>
        
        <!-- Location Content Wrapper -->
        <div class="ensemble-location-content-wrapper">
            
            <!-- Title Section -->
            <header class="ensemble-location-header">
                <h1 class="ensemble-location-title"><?php the_title(); ?></h1>
            </header>
            
            <!-- Location Meta Grid -->
            <div class="ensemble-meta-grid">
                
                <!-- Address -->
                <?php if ($address || $city || $state || $zip || $country): ?>
                <div class="ensemble-meta-item">
                    <span class="ensemble-meta-icon dashicons dashicons-location"></span>
                    <div class="ensemble-meta-content">
                        <div class="ensemble-meta-label"><?php _e('Address', 'ensemble'); ?></div>
                        <div class="ensemble-meta-value">
                            <?php 
                            $address_parts = array_filter(array($address, $city, $state, $zip, $country));
                            echo esc_html(implode(', ', $address_parts));
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Phone -->
                <?php if ($phone): ?>
                <div class="ensemble-meta-item">
                    <span class="ensemble-meta-icon dashicons dashicons-phone"></span>
                    <div class="ensemble-meta-content">
                        <div class="ensemble-meta-label"><?php _e('Phone', 'ensemble'); ?></div>
                        <div class="ensemble-meta-value">
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>">
                                <?php echo esc_html($phone); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Email -->
                <?php if ($email): ?>
                <div class="ensemble-meta-item">
                    <span class="ensemble-meta-icon dashicons dashicons-email"></span>
                    <div class="ensemble-meta-content">
                        <div class="ensemble-meta-label"><?php _e('Email', 'ensemble'); ?></div>
                        <div class="ensemble-meta-value">
                            <a href="mailto:<?php echo esc_attr($email); ?>">
                                <?php echo esc_html($email); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Website -->
                <?php if ($website): ?>
                <div class="ensemble-meta-item">
                    <span class="ensemble-meta-icon dashicons dashicons-admin-site"></span>
                    <div class="ensemble-meta-content">
                        <div class="ensemble-meta-label"><?php _e('Website', 'ensemble'); ?></div>
                        <div class="ensemble-meta-value">
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener">
                                <?php _e('Visit Website', 'ensemble'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Capacity -->
                <?php if ($capacity): ?>
                <div class="ensemble-meta-item">
                    <span class="ensemble-meta-icon dashicons dashicons-groups"></span>
                    <div class="ensemble-meta-content">
                        <div class="ensemble-meta-label"><?php _e('Capacity', 'ensemble'); ?></div>
                        <div class="ensemble-meta-value">
                            <?php echo esc_html(number_format_i18n($capacity)); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Opening Hours -->
            <?php 
            $has_opening_hours = get_post_meta($location_id, 'has_opening_hours', true);
            if ($has_opening_hours && function_exists('es_render_opening_hours')): 
                $opening_hours_html = es_render_opening_hours($location_id, array('show_badge' => true));
                if ($opening_hours_html):
            ?>
            <div class="ensemble-location-opening-hours">
                <h2><?php _e('Opening Hours', 'ensemble'); ?></h2>
                <?php echo $opening_hours_html; ?>
            </div>
            <?php 
                endif;
            endif; 
            ?>
            
            <!-- Location Description -->
            <?php if (get_the_content()): ?>
            <div class="ensemble-location-description">
                <h2><?php _e('About this Location', 'ensemble'); ?></h2>
                <div class="ensemble-content">
                    <?php the_content(); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            // ADDON HOOK: ensemble_after_location_content
            if (class_exists('ES_Addon_Manager')) {
                ES_Addon_Manager::do_addon_hook('ensemble_after_location_content', $location_id);
            }
            ?>
            
            <!-- Upcoming Events -->
            <?php if ($upcoming_events->have_posts()): ?>
            <div class="ensemble-location-events-section">
                <h2><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                <div class="ensemble-events-list">
                    <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); 
                        $event_id = get_the_ID();
                        $start_date = get_post_meta($event_id, 'es_event_start_date', true);
                        $start_time = get_post_meta($event_id, 'es_event_start_time', true);
                        $artist_id = get_post_meta($event_id, 'es_event_artist', true);
                        $artist = $artist_id ? get_post($artist_id) : null;
                    ?>
                    
                    <div class="ensemble-event-list-item">
                        <div class="ensemble-event-date">
                            <?php if ($start_date): ?>
                                <span class="ensemble-date-badge">
                                    <span class="date-day"><?php echo date_i18n('j', strtotime($start_date)); ?></span>
                                    <span class="date-month"><?php echo date_i18n('M', strtotime($start_date)); ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ensemble-event-info">
                            <h3 class="ensemble-event-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            
                            <div class="ensemble-event-details">
                                <?php if ($start_time): ?>
                                    <span class="ensemble-event-time">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo date_i18n(get_option('time_format'), strtotime($start_time)); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($artist): ?>
                                    <span class="ensemble-event-artist">
                                        <span class="dashicons dashicons-megaphone"></span>
                                        <?php echo esc_html($artist->post_title); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
            
            <?php 
            // ADDON HOOK: ensemble_after_location
            if (class_exists('ES_Addon_Manager')) {
                ES_Addon_Manager::do_addon_hook('ensemble_after_location', $location_id);
            }
            ?>
            
        </div>
        
    </article>
    
    <?php endwhile; endif; ?>
    
</div>

<?php get_footer(); ?>
