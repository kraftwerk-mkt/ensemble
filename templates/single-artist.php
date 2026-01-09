<?php
/**
 * Single Artist Template (Template System Version)
 * 
 * Template for displaying a single artist with full Template System support
 * Can be overridden by copying to: /your-theme/ensemble/single-artist.php
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
    echo '<style id="ensemble-single-artist-custom-css">' . $custom_css . '</style>';
}

// Get artist data - Try multiple field name formats for compatibility
$artist_id = get_the_ID();

// Try ACF first, then meta with different prefixes
$genre = get_field('artist_genre', $artist_id) ?: 
         get_post_meta($artist_id, 'artist_genre', true) ?: 
         get_post_meta($artist_id, 'es_artist_genre', true);

$website = get_field('artist_website', $artist_id) ?: 
           get_post_meta($artist_id, 'artist_website', true) ?: 
           get_post_meta($artist_id, 'es_artist_website', true);

$facebook = get_field('artist_facebook', $artist_id) ?: 
            get_post_meta($artist_id, 'artist_facebook', true) ?: 
            get_post_meta($artist_id, 'es_artist_facebook', true);

$instagram = get_field('artist_instagram', $artist_id) ?: 
             get_post_meta($artist_id, 'artist_instagram', true) ?: 
             get_post_meta($artist_id, 'es_artist_instagram', true);

$twitter = get_field('artist_twitter', $artist_id) ?: 
           get_post_meta($artist_id, 'artist_twitter', true) ?: 
           get_post_meta($artist_id, 'es_artist_twitter', true);

$soundcloud = get_field('artist_soundcloud', $artist_id) ?: 
              get_post_meta($artist_id, 'artist_soundcloud', true) ?: 
              get_post_meta($artist_id, 'es_artist_soundcloud', true);

$spotify = get_field('artist_spotify', $artist_id) ?: 
           get_post_meta($artist_id, 'artist_spotify', true) ?: 
           get_post_meta($artist_id, 'es_artist_spotify', true);

// Get genre from taxonomy as fallback
if (empty($genre)) {
    $genres = wp_get_post_terms($artist_id, 'ensemble_genre');
    if (!is_wp_error($genres) && !empty($genres)) {
        $genre_names = array();
        foreach ($genres as $genre_term) {
            $genre_names[] = $genre_term->name;
        }
        $genre = implode(', ', $genre_names);
    }
}

// Get references/credits
$references = get_field('artist_references', $artist_id) ?: 
              get_post_meta($artist_id, 'artist_references', true);

// Get upcoming events for this artist
$upcoming_events = new WP_Query(array(
    'post_type' => ensemble_get_post_type(),
    'posts_per_page' => 10,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => 'es_event_artist',
            'value' => $artist_id,
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
    
    <article class="ensemble-single-artist">
        
        <!-- Artist Header with Image -->
        <?php if (has_post_thumbnail()): ?>
        <div class="ensemble-artist-image">
            <?php the_post_thumbnail('large'); ?>
        </div>
        <?php endif; ?>
        
        <!-- Artist Content Wrapper -->
        <div class="ensemble-artist-content-wrapper">
            
            <!-- Title Section -->
            <header class="ensemble-artist-header">
                <h1 class="ensemble-artist-title"><?php the_title(); ?></h1>
                
                <?php if ($genre): ?>
                <div class="ensemble-artist-meta">
                    <span class="ensemble-meta-badge">
                        <?php echo esc_html($genre); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if ($references): ?>
                <div class="ensemble-artist-references">
                    <span class="dashicons dashicons-awards"></span>
                    <span class="ensemble-references-text"><?php echo esc_html($references); ?></span>
                </div>
                <?php endif; ?>
            </header>
            
            <!-- Social Links -->
            <?php if ($website || $facebook || $instagram || $twitter || $soundcloud || $spotify): ?>
            <div class="ensemble-artist-social">
                <?php if ($website): ?>
                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="ensemble-social-link">
                        <span class="dashicons dashicons-admin-site"></span>
                        <span><?php _e('Website', 'ensemble'); ?></span>
                    </a>
                <?php endif; ?>
                
                <?php if ($facebook): ?>
                    <a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener" class="ensemble-social-link">
                        <span class="dashicons dashicons-facebook"></span>
                        <span><?php _e('Facebook', 'ensemble'); ?></span>
                    </a>
                <?php endif; ?>
                
                <?php if ($instagram): ?>
                    <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener" class="ensemble-social-link">
                        <span class="dashicons dashicons-instagram"></span>
                        <span><?php _e('Instagram', 'ensemble'); ?></span>
                    </a>
                <?php endif; ?>
                
                <?php if ($twitter): ?>
                    <a href="<?php echo esc_url($twitter); ?>" target="_blank" rel="noopener" class="ensemble-social-link">
                        <span class="dashicons dashicons-twitter"></span>
                        <span><?php _e('Twitter', 'ensemble'); ?></span>
                    </a>
                <?php endif; ?>
                
                <?php if ($soundcloud): ?>
                    <a href="<?php echo esc_url($soundcloud); ?>" target="_blank" rel="noopener" class="ensemble-social-link">
                        <span class="dashicons dashicons-format-audio"></span>
                        <span><?php _e('SoundCloud', 'ensemble'); ?></span>
                    </a>
                <?php endif; ?>
                
                <?php if ($spotify): ?>
                    <a href="<?php echo esc_url($spotify); ?>" target="_blank" rel="noopener" class="ensemble-social-link">
                        <span class="dashicons dashicons-playlist-audio"></span>
                        <span><?php _e('Spotify', 'ensemble'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Artist Bio/Description -->
            <?php if (get_the_content()): ?>
            <div class="ensemble-artist-description">
                <h2><?php _e('About', 'ensemble'); ?></h2>
                <div class="ensemble-content">
                    <?php the_content(); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            // ADDON HOOK: ensemble_after_artist_content
            if (class_exists('ES_Addon_Manager')) {
                ES_Addon_Manager::do_addon_hook('ensemble_after_artist_content', $artist_id);
            }
            ?>
            
            <!-- Upcoming Events -->
            <?php if ($upcoming_events->have_posts()): ?>
            <div class="ensemble-artist-events-section">
                <h2><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                <div class="ensemble-events-list">
                    <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); 
                        $event_id = get_the_ID();
                        $start_date = get_post_meta($event_id, 'es_event_start_date', true);
                        $start_time = get_post_meta($event_id, 'es_event_start_time', true);
                        $location_id = get_post_meta($event_id, 'es_event_location', true);
                        $location = $location_id ? get_post($location_id) : null;
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
                                
                                <?php if ($location): ?>
                                    <span class="ensemble-event-location">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($location->post_title); ?>
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
            // ADDON HOOK: ensemble_after_artist
            if (class_exists('ES_Addon_Manager')) {
                ES_Addon_Manager::do_addon_hook('ensemble_after_artist', $artist_id);
            }
            ?>
            
        </div>
        
    </article>
    
    <?php endwhile; endif; ?>
    
</div>

<?php get_footer(); ?>
