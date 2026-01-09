<?php
/**
 * Single Event Template - STAGE LAYOUT
 * 
 * Single-Column Layout:
 * - Hero (Fullbleed) mit Datum links, Badge rechts
 * - Title-Row: Titel links, Tickets-Button rechts
 * - Description → Genres → Line-Up → Additional Info → Katalog → Tickets → Galerie → Facebook → Share
 *
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue styles
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-shortcodes', ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css', array('ensemble-base'), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-layout-stage', ENSEMBLE_PLUGIN_URL . 'templates/layouts/stage/style.css', array('ensemble-base', 'ensemble-shortcodes'), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-stage-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Oswald:wght@400;500;600;700&display=swap', array(), ENSEMBLE_VERSION);

$event_id = get_the_ID();
$event = es_load_event_data($event_id);
$is_cancelled = ($event['status'] === 'cancelled');
$timestamp = $event['date'] ? strtotime($event['date']) : false;
?>

<div class="ensemble-single-event-wrapper es-layout-stage es-stage-single">
    
    <?php ensemble_before_event($event_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- ========================================
         HERO - Fullbleed Image
         Datum links, Badge rechts
         ======================================== -->
    <header class="es-stage-hero">
        <?php if (has_post_thumbnail()): ?>
        <div class="es-stage-hero-bg">
            <?php the_post_thumbnail('full'); ?>
            <div class="es-stage-hero-overlay"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-stage-hero-inner">
            <!-- Datum Badge (links) -->
            <?php if ($timestamp): ?>
            <div class="es-stage-hero-date-badge">
                <span class="es-stage-date-day"><?php echo date_i18n('j', $timestamp); ?></span>
                <span class="es-stage-date-month"><?php echo date_i18n('M', $timestamp); ?></span>
                <span class="es-stage-date-year"><?php echo date_i18n('Y', $timestamp); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Status/Badge (rechts) -->
            <div class="es-stage-hero-badges">
                <?php if ($event['status'] && $event['status'] !== 'publish'): ?>
                <span class="es-stage-badge es-stage-badge-status es-status-<?php echo esc_attr($event['status']); ?>">
                    <?php echo esc_html(function_exists('ensemble_get_status_label') ? ensemble_get_status_label($event['status']) : ucfirst($event['status'])); ?>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($event['badge_label'])): ?>
                <span class="es-stage-badge es-stage-badge-custom">
                    <?php echo esc_html($event['badge_label']); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php ensemble_event_header($event_id); ?>
    </header>
    
    <!-- ========================================
         TITLE ROW
         Titel links, Tickets-Button rechts
         ======================================== -->
    <div class="es-stage-title-row">
        <div class="es-stage-container">
            <div class="es-stage-title-inner">
                <div class="es-stage-title-left">
                    <h1 class="es-stage-event-title"><?php the_title(); ?></h1>
                    
                    <?php if ($event['formatted_time'] || !empty($event['location'])): ?>
                    <div class="es-stage-title-meta">
                        <?php if ($event['formatted_time']): ?>
                        <span class="es-stage-meta-time">
                            <?php echo esc_html($event['formatted_time']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($event['location'])): 
                            $loc = $event['location'];
                            $loc_name = !empty($loc['display_name']) ? $loc['display_name'] : $loc['name'];
                        ?>
                        <span class="es-stage-meta-location">
                            <?php echo esc_html($loc_name); ?>
                            <?php if (!empty($loc['city'])): ?>
                                <span class="es-stage-meta-city"><?php echo esc_html($loc['city']); ?></span>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php ensemble_after_title($event_id); ?>
                </div>
                
                <div class="es-stage-title-right">
                    <?php if ($event['ticket_url'] && !$is_cancelled): ?>
                    <div class="es-stage-title-ticket">
                        <?php if ($event['price']): ?>
                        <span class="es-stage-ticket-price"><?php echo esc_html($event['price']); ?></span>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-stage-btn es-stage-btn-primary" target="_blank" rel="noopener">
                            <?php echo esc_html($event['button_text'] ?: __('Tickets', 'ensemble')); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ========================================
         CONTENT - Single Column
         ======================================== -->
    <div class="es-stage-content">
        <div class="es-stage-container">
            
            <?php ensemble_before_content($event_id); ?>
            
            <!-- DESCRIPTION -->
            <?php if (($event['description'] || get_the_content()) && (!function_exists('ensemble_show_section') || ensemble_show_section('description'))): ?>
            <section class="es-stage-section es-stage-description">
                <?php if (function_exists('ensemble_show_header') && ensemble_show_header('description')): ?>
                <h2 class="es-stage-section-title"><?php _e('About', 'ensemble'); ?></h2>
                <?php endif; ?>
                <div class="es-stage-prose">
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
            
            <?php 
            // Calculate artist groups once for both genres and lineup sections
            $artist_groups = !empty($event['artists']) ? es_group_artists_by_venue($event['artists']) : array('has_venues' => false, 'groups' => array());
            $venue_config = !empty($event['venue_config']) ? $event['venue_config'] : array();
            ?>
            
            <!-- GENRES (nur wenn KEINE Venues/Räume vorhanden) -->
            <?php if (!empty($event['genres']) && empty($artist_groups['has_venues'])): ?>
            <section class="es-stage-section es-stage-genres">
                <div class="es-stage-genre-list">
                    <?php foreach ($event['genres'] as $genre): ?>
                    <span class="es-stage-genre-pill"><?php echo esc_html(is_object($genre) ? $genre->name : $genre); ?></span>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- LINE-UP -->
            <?php if (!empty($event['artists']) && (!function_exists('ensemble_show_section') || ensemble_show_section('artists'))): ?>
            <section class="es-stage-section es-stage-lineup">
                <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('artists')): ?>
                <h2 class="es-stage-section-title"><?php _e('Line-Up', 'ensemble'); ?></h2>
                <?php endif; ?>
                
                <?php if ($artist_groups['has_venues']): ?>
                <!-- Artists grouped by Room/Stage -->
                <?php foreach ($artist_groups['groups'] as $venue_name => $venue_artists): 
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
                <div class="es-stage-venue-group">
                    <div class="es-stage-venue-header">
                        <h3 class="es-stage-venue-name"><?php echo esc_html($display_venue_name); ?></h3>
                        <?php if (!empty($venue_genres)): ?>
                        <div class="es-stage-venue-genres">
                            <?php foreach ($venue_genres as $genre): ?>
                            <span class="es-stage-venue-genre"><?php echo esc_html($genre); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="es-stage-lineup-list">
                        <?php foreach ($venue_artists as $artist): 
                            $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                            $artist_target = (!empty($artist['link_external']) && !empty($artist['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                            $artist_tag = $artist_has_link ? 'a' : 'div';
                            $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' . $artist_target : '';
                        ?>
                        <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-stage-lineup-item">
                            <?php if (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image')): ?>
                            <div class="es-stage-lineup-img">
                                <?php if (!empty($artist['image'])): ?>
                                    <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                <?php else: ?>
                                    <div class="es-stage-lineup-placeholder">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div class="es-stage-lineup-info">
                                <h4 class="es-stage-lineup-name"><?php echo esc_html($artist['name']); ?></h4>
                                <?php 
                                // Collect meta items
                                $meta_items = array();
                                if (!empty($artist['references'])) {
                                    $meta_items[] = $artist['references'];
                                }
                                if (!empty($artist['genre']) && !empty($event['show_artist_genres'])) {
                                    $meta_items[] = $artist['genre'];
                                }
                                if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))) {
                                    $meta_items[] = $artist['time_formatted'];
                                }
                                if (!empty($meta_items)):
                                ?>
                                <span class="es-stage-lineup-meta"><?php echo esc_html(implode(' | ', $meta_items)); ?></span>
                                <?php endif; ?>
                            </div>
                        </<?php echo $artist_tag; ?>>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php else: ?>
                <!-- Simple artist list -->
                <div class="es-stage-lineup-list">
                    <?php foreach ($event['artists'] as $artist): 
                        $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                        $artist_target = (!empty($artist['link_external']) && !empty($artist['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                        $artist_tag = $artist_has_link ? 'a' : 'div';
                        $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' . $artist_target : '';
                    ?>
                    <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-stage-lineup-item">
                        <?php if (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image')): ?>
                        <div class="es-stage-lineup-img">
                            <?php if (!empty($artist['image'])): ?>
                                <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                            <?php else: ?>
                                <div class="es-stage-lineup-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="es-stage-lineup-info">
                            <h4 class="es-stage-lineup-name"><?php echo esc_html($artist['name']); ?></h4>
                            <?php 
                            $meta_items = array();
                            if (!empty($artist['references'])) {
                                $meta_items[] = $artist['references'];
                            }
                            if (!empty($artist['genre']) && !empty($event['show_artist_genres'])) {
                                $meta_items[] = $artist['genre'];
                            }
                            if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))) {
                                $meta_items[] = $artist['time_formatted'];
                            }
                            if (!empty($meta_items)):
                            ?>
                            <span class="es-stage-lineup-meta"><?php echo esc_html(implode(' | ', $meta_items)); ?></span>
                            <?php endif; ?>
                        </div>
                    </<?php echo $artist_tag; ?>>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php ensemble_artist_section($event_id, $event['artists']); ?>
            </section>
            <?php endif; ?>
            
            <?php ensemble_after_description($event_id); ?>
            
            <!-- ADDITIONAL INFORMATION -->
            <?php if (!empty($event['additional_info'])): ?>
            <section class="es-stage-section es-stage-additional">
                <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('additional_info')): ?>
                <h2 class="es-stage-section-title"><?php _e('Additional Information', 'ensemble'); ?></h2>
                <?php endif; ?>
                <div class="es-stage-prose">
                    <?php echo wpautop($event['additional_info']); ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- CATALOG (Add-on) -->
            <?php 
            if (ensemble_has_addon_hook('event_catalog')) {
                ob_start();
                ensemble_event_catalog($event_id, $event['location_id']);
                $catalog_content = ob_get_clean();
                if (!empty(trim($catalog_content))):
            ?>
            <section class="es-stage-section es-stage-catalog">
                <h2 class="es-stage-section-title"><?php _e('Catalog', 'ensemble'); ?></h2>
                <?php echo $catalog_content; ?>
            </section>
            <?php 
                endif;
            }
            ?>
            
            <!-- TICKETS ADD-ON -->
            <?php if (ensemble_has_addon_hook('ticket_area') && !$is_cancelled): ?>
            <section class="es-stage-section es-stage-tickets-addon">
                <?php 
                ensemble_ticket_area($event_id, array(
                    'price' => $event['price'],
                    'ticket_url' => $event['ticket_url'],
                    'event_status' => $event['status'],
                ));
                ?>
            </section>
            <?php endif; ?>
            
            <!-- GALLERY -->
            <?php 
            if ((ensemble_has_addon_hook('gallery_area') || !empty($event['gallery'])) && (!function_exists('ensemble_show_addon') || ensemble_show_addon('gallery'))) {
                echo '<section class="es-stage-section es-stage-gallery">';
                if (!function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('gallery')) {
                    echo '<h2 class="es-stage-section-title">' . __('Gallery', 'ensemble') . '</h2>';
                }
                ensemble_gallery_area($event_id, $event['gallery'] ?: array());
                echo '</section>';
            }
            ?>
            
            <!-- LOCATION INFO -->
            <?php if (!empty($event['location'])): 
                $loc = $event['location'];
                $loc_has_link = !empty($loc['link_enabled']) && !empty($loc['link_url']);
                $loc_target = (!empty($loc['link_external']) && !empty($loc['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
            ?>
            <section class="es-stage-section es-stage-location-info">
                <h2 class="es-stage-section-title"><?php _e('Location', 'ensemble'); ?></h2>
                <div class="es-stage-location-card">
                    <div class="es-stage-location-details">
                        <h3 class="es-stage-location-name">
                            <?php if ($loc_has_link): ?>
                            <a href="<?php echo esc_url($loc['link_url']); ?>"<?php echo $loc_target; ?>>
                                <?php echo esc_html(!empty($loc['display_name']) ? $loc['display_name'] : $loc['name']); ?>
                            </a>
                            <?php else: ?>
                                <?php echo esc_html(!empty($loc['display_name']) ? $loc['display_name'] : $loc['name']); ?>
                            <?php endif; ?>
                        </h3>
                        <?php if (!empty($loc['address']) || !empty($loc['city'])): ?>
                        <p class="es-stage-location-address">
                            <?php 
                            $address_parts = array();
                            if (!empty($loc['address'])) $address_parts[] = $loc['address'];
                            if (!empty($loc['zip_code']) || !empty($loc['city'])) {
                                $address_parts[] = trim(($loc['zip_code'] ?? '') . ' ' . ($loc['city'] ?? ''));
                            }
                            echo esc_html(implode(', ', $address_parts));
                            ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($loc['additional_info'])): ?>
                        <p class="es-stage-location-hint"><?php echo esc_html($loc['additional_info']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php ensemble_after_location($event_id, $loc); ?>
            </section>
            <?php endif; ?>
            
            <!-- FACEBOOK EVENT -->
            <?php if (!empty($event['facebook_url'])): ?>
            <section class="es-stage-section es-stage-facebook">
                <a href="<?php echo esc_url($event['facebook_url']); ?>" class="es-stage-facebook-link" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <?php _e('Facebook Event', 'ensemble'); ?>
                </a>
            </section>
            <?php endif; ?>
            
            <!-- EXTERNAL LINK -->
            <?php if (!empty($event['external_url'])): ?>
            <section class="es-stage-section es-stage-external">
                <a href="<?php echo esc_url($event['external_url']); ?>" class="es-stage-btn es-stage-btn-secondary" target="_blank" rel="noopener">
                    <?php echo esc_html($event['external_text'] ?: __('More Info', 'ensemble')); ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                </a>
            </section>
            <?php endif; ?>
            
            <!-- SHARE -->
            <?php 
            ensemble_social_share($event_id, array(
                'title' => $event['title'],
                'permalink' => $event['permalink'],
                'featured_image' => $event['featured_image'],
            ));
            ?>
            
            <?php ensemble_event_footer($event_id); ?>
            <?php ensemble_event_meta($event_id, $event); ?>
            <?php ensemble_after_tickets($event_id); ?>
            
            <?php 
            // Main Sponsor Hook - displays main sponsor in sidebar
            do_action('ensemble_main_sponsor_sidebar', $event_id);
            ?>
            
            <?php ensemble_event_sidebar($event_id); ?>
            
        </div>
    </div>
    
    <!-- RELATED EVENTS -->
    <?php 
    ensemble_related_events($event_id, array(
        'categories' => $event['categories'],
        'location_id' => $event['location_id'],
        'artist_ids' => !empty($event['artists']) ? array_column($event['artists'], 'id') : array(),
    ));
    ?>
    
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
