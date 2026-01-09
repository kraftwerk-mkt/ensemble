<?php
/**
 * Single Event Template - Noir Elegance
 * Full-width dark mode, minimal and refined
 *
 * @package Ensemble
 * @version 3.1.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue base CSS
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-shortcodes', ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css', array('ensemble-base'), ENSEMBLE_VERSION);

// Enqueue Modern Layout CSS
wp_enqueue_style('ensemble-layout-modern', ENSEMBLE_PLUGIN_URL . 'templates/layouts/modern/style.css', array('ensemble-base', 'ensemble-shortcodes'), ENSEMBLE_VERSION);

$event_id = get_the_ID();
$event = es_load_event_data($event_id);
$is_cancelled = ($event['status'] === 'cancelled');
?>

<div class="es-noir-single es-noir-event">
    
    <?php ensemble_before_event($event_id); ?>
    
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
            
            <?php if ($event['status'] && $event['status'] !== 'publish'): ?>
            <span class="es-noir-hero-status es-status-<?php echo esc_attr($event['status']); ?>">
                <?php echo esc_html(ensemble_get_status_label($event['status'])); ?>
            </span>
            <?php endif; ?>
            
            <?php if (!empty($event['badge_label'])): ?>
            <span class="es-noir-hero-badge es-badge-<?php echo esc_attr($event['badge_raw'] ?: 'custom'); ?>">
                <?php echo esc_html($event['badge_label']); ?>
            </span>
            <?php endif; ?>
            
            <?php if (!empty($event['categories'])): ?>
            <div class="es-noir-hero-cats">
                <?php foreach ($event['categories'] as $cat): ?>
                    <span><?php echo esc_html($cat->name); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <h1 class="es-noir-hero-title"><?php the_title(); ?></h1>
            
            <?php if ($event['date']): ?>
            <time class="es-noir-hero-date" datetime="<?php echo esc_attr($event['date']); ?>">
                <?php echo date_i18n('l, j. F Y', strtotime($event['date'])); ?>
                <?php if ($event['formatted_time']): ?>
                    <span class="es-noir-hero-time"><?php echo esc_html($event['formatted_time']); ?></span>
                <?php endif; ?>
            </time>
            <?php endif; ?>
            
            <?php ensemble_after_title($event_id); ?>
        </div>
        
        <?php ensemble_event_header($event_id); ?>
    </header>
    
    <!-- Content -->
    <div class="es-noir-body">
        <div class="es-noir-container">
            
            <div class="es-noir-layout">
                
                <!-- Main -->
                <main class="es-noir-main">
                    
                    <?php ensemble_before_content($event_id); ?>
                    
                    <?php if (($event['description'] || get_the_content()) && (!function_exists('ensemble_show_section') || ensemble_show_section('description'))): ?>
                    <section class="es-noir-section es-noir-description">
                        <?php if (function_exists('ensemble_show_header') && ensemble_show_header('description')): ?>
                        <h2 class="es-noir-section-title"><?php _e('About', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        <div class="es-noir-prose">
                            <?php 
                            if (!empty($event['description'])) {
                                echo wpautop($event['description']);
                            } else {
                                the_content();
                            }
                            ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Mobile Ticket Card (shown only on mobile, after description) -->
                    <?php if ($event['ticket_url'] && !$is_cancelled): ?>
                    <div class="es-noir-ticket-mobile">
                        <?php if ($event['price']): ?>
                        <span class="es-noir-ticket-price"><?php echo esc_html($event['price']); ?></span>
                        <?php if (!empty($event['price_note'])): ?>
                        <span class="es-noir-ticket-note"><?php echo esc_html($event['price_note']); ?></span>
                        <?php endif; ?>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-noir-btn" target="_blank" rel="noopener">
                            <?php echo esc_html($event['button_text'] ?: __('Tickets', 'ensemble')); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php ensemble_after_description($event_id); ?>
                    
                    <!-- Artists -->
                    <?php if (!empty($event['artists']) && (!function_exists('ensemble_show_section') || ensemble_show_section('artists'))): ?>
                    <section class="es-noir-section es-noir-artists">
                        <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('artists')): ?>
                        <h2 class="es-noir-section-title"><?php _e('Line-Up', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        
                        <?php ensemble_artist_section($event_id, $event['artists']); ?>
                        
                        <?php 
                        // Group artists by venue
                        $artist_groups = es_group_artists_by_venue($event['artists']);
                        $venue_config = !empty($event['venue_config']) ? $event['venue_config'] : array();
                        ?>
                        
                        <?php 
                        // Show event genres as meta if no venues are used
                        if (!$artist_groups['has_venues'] && !empty($event['genres'])): 
                        ?>
                        <div class="es-noir-genre-meta">
                            <?php foreach ($event['genres'] as $genre): ?>
                            <span class="es-noir-genre-tag"><?php echo esc_html(is_object($genre) ? $genre->name : $genre); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($artist_groups['has_venues']): ?>
                        <!-- Artists grouped by Room/Stage -->
                        <?php foreach ($artist_groups['groups'] as $venue_name => $venue_artists): 
                            // Get custom venue name and genres from config
                            $display_venue_name = $venue_name;
                            $venue_genres = array();
                            if (isset($venue_config[$venue_name])) {
                                if (!empty($venue_config[$venue_name]['customName'])) {
                                    $display_venue_name = $venue_config[$venue_name]['customName'];
                                }
                                if (!empty($venue_config[$venue_name]['genres'])) {
                                    foreach ($venue_config[$venue_name]['genres'] as $genre_id) {
                                        $genre_term = get_term($genre_id, 'ensemble_genre');
                                        if ($genre_term && !is_wp_error($genre_term)) {
                                            $venue_genres[] = $genre_term->name;
                                        }
                                    }
                                }
                            }
                        ?>
                        <div class="es-noir-venue-group">
                            <div class="es-noir-venue-header">
                                <h3 class="es-noir-venue-title">
                                    <?php echo esc_html($display_venue_name); ?>
                                </h3>
                                <?php if (!empty($venue_genres)): ?>
                                <div class="es-noir-venue-genres">
                                    <?php foreach ($venue_genres as $genre): ?>
                                    <span class="es-noir-venue-genre"><?php echo esc_html($genre); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="es-noir-artist-grid">
                                <?php foreach ($venue_artists as $artist): 
                                    $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                                    $artist_tag = $artist_has_link ? 'a' : 'div';
                                    $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' : '';
                                ?>
                                <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-noir-artist-item">
                                    <?php if (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image')): ?>
                                    <div class="es-noir-artist-img">
                                        <?php if ($artist['image']): ?>
                                            <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="es-noir-artist-info">
                                        <h4><?php echo esc_html($artist['name']); ?></h4>
                                        <?php if (!empty($artist['references']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_references'))): ?>
                                            <span class="es-noir-artist-references"><?php echo esc_html($artist['references']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($artist['genre']) && !empty($event['show_artist_genres'])): ?>
                                            <span class="es-noir-artist-genre"><?php echo esc_html($artist['genre']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                                            <span class="es-noir-artist-time"><?php echo esc_html($artist['time_formatted']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </<?php echo $artist_tag; ?>>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php else: ?>
                        <!-- Simple artist list (no venues assigned) -->
                        <div class="es-noir-artist-grid">
                            <?php foreach ($event['artists'] as $artist): 
                                $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                                $artist_tag = $artist_has_link ? 'a' : 'div';
                                $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' : '';
                            ?>
                            <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-noir-artist-item">
                                <?php if (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image')): ?>
                                <div class="es-noir-artist-img">
                                    <?php if ($artist['image']): ?>
                                        <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="es-noir-artist-info">
                                    <h4><?php echo esc_html($artist['name']); ?></h4>
                                    <?php if (!empty($artist['references']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_references'))): ?>
                                        <span class="es-noir-artist-references"><?php echo esc_html($artist['references']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($artist['genre']) && !empty($event['show_artist_genres'])): ?>
                                        <span class="es-noir-artist-genre"><?php echo esc_html($artist['genre']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                                        <span class="es-noir-artist-time"><?php echo esc_html($artist['time_formatted']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </<?php echo $artist_tag; ?>>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Catalog (after Lineup) -->
                    <?php 
                    if (ensemble_has_addon_hook('event_catalog')) {
                        echo '<section class="es-noir-section es-noir-catalog">';
                        ensemble_event_catalog($event_id, $event['location_id']);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Gallery moved to Sidebar for Modern Layout -->
                    
                    <!-- Additional Information -->
                    <?php if (!empty($event['additional_info'])): ?>
                    <section class="es-noir-section es-noir-additional-info">
                        <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('additional_info')): ?>
                        <h2 class="es-noir-section-title"><?php _e('Additional Information', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        <div class="es-noir-prose es-noir-info-content">
                            <?php echo wpautop($event['additional_info']); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Gallery -->
                    <?php 
                    if ((ensemble_has_addon_hook('gallery_area') || !empty($event['gallery'])) && (!function_exists('ensemble_show_addon') || ensemble_show_addon('gallery'))) {
                        echo '<section class="es-noir-section es-noir-gallery">';
                        if (!function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('gallery')) {
                            echo '<h2 class="es-noir-section-title">' . __('Gallery', 'ensemble') . '</h2>';
                        }
                        ensemble_gallery_area($event_id, $event['gallery'] ?: array());
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Share -->
                    <?php 
                    ensemble_social_share($event_id, array(
                        'title' => $event['title'],
                        'permalink' => $event['permalink'],
                        'featured_image' => $event['featured_image'],
                    ));
                    ?>
                    
                    <?php ensemble_event_footer($event_id); ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="es-noir-aside">
                    
                    <div class="es-noir-info-card">
                        
                        <!-- Ticket Section (Price + Button) -->
                        <?php if ($event['price'] || $event['ticket_url']): ?>
                        <div class="es-noir-ticket-section">
                            <?php if ($event['price'] && (!function_exists('ensemble_show_meta') || ensemble_show_meta('price'))): ?>
                            <div class="es-noir-info-row">
                                <span class="es-noir-info-label"><?php _e('Eintritt', 'ensemble'); ?></span>
                                <span class="es-noir-info-value es-noir-info-price"><?php echo esc_html($event['price']); ?></span>
                                <?php if (!empty($event['price_note'])): ?>
                                <span class="es-noir-info-sub"><?php echo esc_html($event['price_note']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php 
                            ensemble_ticket_area($event_id, array(
                                'price' => $event['price'],
                                'ticket_url' => $event['ticket_url'],
                                'event_status' => $event['status'],
                            ));
                            ?>
                            
                            <!-- Ticket Button - Desktop only -->
                            <?php if ($event['ticket_url'] && !$is_cancelled): ?>
                            <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-noir-btn es-noir-ticket-desktop" target="_blank" rel="noopener">
                                <?php echo esc_html($event['button_text'] ?: __('Tickets', 'ensemble')); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Location -->
                        <?php if ($event['location']): 
                            $loc = $event['location'];
                            $loc_has_link = !empty($loc['link_enabled']) && !empty($loc['link_url']);
                            $loc_target = (!empty($loc['link_external']) && !empty($loc['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                            $loc_formatted = function_exists('ensemble_format_location_address') 
                                ? ensemble_format_location_address($loc) 
                                : array('name' => (!empty($loc['display_name']) ? $loc['display_name'] : $loc['name']), 'address_line' => $loc['city']);
                        ?>
                        <div class="es-noir-info-row">
                            <span class="es-noir-info-label"><?php _e('Location', 'ensemble'); ?></span>
                            <?php if ($loc_has_link): ?>
                            <a href="<?php echo esc_url($loc['link_url']); ?>" class="es-noir-info-value es-noir-info-link"<?php echo $loc_target; ?>>
                                <?php echo esc_html($loc_formatted['name']); ?>
                            </a>
                            <?php else: ?>
                            <span class="es-noir-info-value"><?php echo esc_html($loc_formatted['name']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($loc_formatted['address_line'])): ?>
                            <span class="es-noir-info-sub"><?php echo esc_html($loc_formatted['address_line']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($loc['additional_info'])): ?>
                            <span class="es-noir-info-hint"><?php echo esc_html($loc['additional_info']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php ensemble_after_location($event_id, $event['location'] ?: array()); ?>
                        
                        <?php ensemble_event_meta($event_id, $event); ?>
                        
                        <?php if (!empty($event['external_url'])): ?>
                        <a href="<?php echo esc_url($event['external_url']); ?>" class="es-noir-btn es-noir-btn-secondary" target="_blank" rel="noopener">
                            <?php echo esc_html($event['external_text']); ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php ensemble_after_tickets($event_id); ?>
                        
                    </div>
                    
                    <?php ensemble_event_sidebar($event_id); ?>
                    
                </aside>
                
            </div>
            
            <!-- Related Events -->
            <?php 
            ensemble_related_events($event_id, array(
                'categories' => $event['categories'],
                'location_id' => $event['location_id'],
                'artist_ids' => array_column($event['artists'], 'id'),
            ));
            ?>
            
        </div>
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php 
    ensemble_after_event($event_id, array(
        'title' => $event['title'],
        'permalink' => $event['permalink'],
        'featured_image' => $event['featured_image'],
    ));
    ?>
    
</div>

<?php get_footer(); ?>