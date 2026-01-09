<?php
/**
 * Single Event Template - CLUB LAYOUT
 * 
 * Dark, bold nightclub style
 * Single column layout - NO sidebar
 * With venue grouping like Lovepop
 *
 * @package Ensemble
 * @version 2.2.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue base CSS
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-shortcodes', ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css', array('ensemble-base'), ENSEMBLE_VERSION);

// Enqueue Club Layout CSS
wp_enqueue_style('ensemble-layout-club', ENSEMBLE_PLUGIN_URL . 'templates/layouts/club/style.css', array('ensemble-base', 'ensemble-shortcodes'), ENSEMBLE_VERSION);

// Load Inter font
wp_enqueue_style('ensemble-club-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap', array(), ENSEMBLE_VERSION);

$event_id = get_the_ID();
$event = es_load_event_data($event_id);
$is_cancelled = ($event['status'] === 'cancelled');

// Format date
$timestamp = $event['date'] ? strtotime($event['date']) : false;
?>

<div class="es-club-single es-club-event">
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <div class="es-club-container">
        
        <!-- 1. HEADER IMAGE with Date Badge -->
        <header class="es-club-header">
            <div class="es-club-header-image">
                <?php if (has_post_thumbnail()): ?>
                    <?php the_post_thumbnail('full'); ?>
                <?php else: ?>
                    <div class="es-club-placeholder"></div>
                <?php endif; ?>
                
                <!-- Date Badge -->
                <?php if ($timestamp): ?>
                <div class="es-club-date-badge">
                    <span class="es-club-date-day"><?php echo date_i18n('d', $timestamp); ?></span>
                    <span class="es-club-date-month"><?php echo date_i18n('M', $timestamp); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Status Badge -->
                <?php if ($event['status'] && $event['status'] !== 'publish'): ?>
                <div class="es-club-status es-club-status-<?php echo esc_attr($event['status']); ?>">
                    <?php echo esc_html(function_exists('ensemble_get_status_label') ? ensemble_get_status_label($event['status']) : ucfirst($event['status'])); ?>
                </div>
                <?php endif; ?>
                
                <!-- Badge -->
                <?php if (!empty($event['badge_label'])): ?>
                <div class="es-club-badge es-badge-<?php echo esc_attr($event['badge_raw'] ?: 'custom'); ?>">
                    <?php echo esc_html($event['badge_label']); ?>
                </div>
                <?php endif; ?>
                
                <!-- Countdown (bottom of header image) -->
                <div class="es-club-countdown-wrapper">
                    <?php ensemble_before_event($event_id); ?>
                </div>
            </div>
            
            <?php ensemble_event_header($event_id); ?>
        </header>
        
        <!-- 2. TITLE BAR -->
        <div class="es-club-title-bar">
            <div class="es-club-title-left">
                <h1 class="es-club-title"><?php the_title(); ?></h1>
                
                <div class="es-club-meta-row">
                    <?php if ($event['formatted_time']): ?>
                    <span class="es-club-meta-item es-club-time">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                        <?php echo esc_html($event['formatted_time']); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($event['location']): 
                        $loc = $event['location'];
                        $loc_name = !empty($loc['display_name']) ? $loc['display_name'] : (!empty($loc['name']) ? $loc['name'] : '');
                    ?>
                    <span class="es-club-meta-item es-club-venue">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php echo esc_html($loc_name); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($event['date']): ?>
                    <span class="es-club-meta-item es-club-date-text">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <?php echo date_i18n('l, j. F Y', $timestamp); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <?php ensemble_after_title($event_id); ?>
            </div>
            
            <?php if (!$is_cancelled): ?>
            <div class="es-club-title-right">
                <!-- Action Buttons Group (Ticket + Reservation nebeneinander) -->
                <div class="es-club-action-buttons">
                    <?php if (!empty($event['ticket_url'])): ?>
                    <!-- Normal Ticket Button (from Wizard) -->
                    <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-club-btn-ticket" target="_blank" rel="noopener">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7v2a3 3 0 1 1 0 6v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2a3 3 0 1 1 0-6V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2Z"/>
                        </svg>
                        <?php echo esc_html($event['button_text'] ?: __('Tickets kaufen', 'ensemble')); ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Reservations (next to ticket button) -->
                    <?php ensemble_after_tickets($event_id); ?>
                </div>
                
                <!-- Price Info (below buttons) -->
                <?php if (!empty($event['ticket_url']) && $event['price']): ?>
                <div class="es-club-ticket-info">
                    <span class="es-club-price"><?php echo esc_html($event['price']); ?></span>
                    <?php if (!empty($event['price_note'])): ?>
                    <span class="es-club-price-note"><?php echo esc_html($event['price_note']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($event['price']): ?>
            <div class="es-club-title-right">
                <div class="es-club-ticket-info">
                    <span class="es-club-price-label"><?php _e('Eintritt', 'ensemble'); ?></span>
                    <span class="es-club-price"><?php echo esc_html($event['price']); ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php ensemble_before_content($event_id); ?>
        
        <!-- 3. DESCRIPTION -->
        <?php if (($event['description'] || get_the_content()) && (!function_exists('ensemble_show_section') || ensemble_show_section('description'))): ?>
        <section class="es-club-section es-club-description">
            <?php if (function_exists('ensemble_show_header') && ensemble_show_header('description')): ?>
            <h2 class="es-club-section-title"><?php _e('About', 'ensemble'); ?></h2>
            <?php endif; ?>
            <div class="es-club-prose">
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
        
        <?php ensemble_after_description($event_id); ?>
        
        <!-- 4 & 5. ARTISTS with Venue/Room Grouping (like Lovepop) -->
        <?php if (!empty($event['artists']) && (!function_exists('ensemble_show_section') || ensemble_show_section('artists'))): ?>
        <section class="es-club-section es-club-lineup">
            <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('artists')): ?>
            <h2 class="es-club-section-title"><?php _e('Line-Up', 'ensemble'); ?></h2>
            <?php endif; ?>
            
            <?php 
            // Group artists by venue (like Lovepop)
            $artist_groups = function_exists('es_group_artists_by_venue') ? es_group_artists_by_venue($event['artists']) : array('has_venues' => false, 'groups' => array());
            $venue_config = !empty($event['venue_config']) ? $event['venue_config'] : array();
            ?>
            
            <?php 
            // Show event genres as meta if no venues are used
            if (!$artist_groups['has_venues'] && !empty($event['genres'])): 
            ?>
            <div class="es-club-genre-meta">
                <?php foreach ($event['genres'] as $genre): ?>
                <span class="es-club-genre-tag"><?php echo esc_html(is_object($genre) ? $genre->name : $genre); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($artist_groups['has_venues']): ?>
            <!-- Artists grouped by Room/Stage -->
            <?php foreach ($artist_groups['groups'] as $venue_name => $venue_artists): 
                // Get custom venue name from config if available
                $display_venue_name = $venue_name;
                $venue_genres = array();
                if (isset($venue_config[$venue_name])) {
                    if (!empty($venue_config[$venue_name]['customName'])) {
                        $display_venue_name = $venue_config[$venue_name]['customName'];
                    }
                    if (!empty($venue_config[$venue_name]['genres'])) {
                        // Get genre names from IDs
                        foreach ($venue_config[$venue_name]['genres'] as $genre_id) {
                            $genre_term = get_term($genre_id, 'ensemble_genre');
                            if ($genre_term && !is_wp_error($genre_term)) {
                                $venue_genres[] = $genre_term->name;
                            }
                        }
                    }
                }
            ?>
            <div class="es-club-venue-group">
                <div class="es-club-venue-header">
                    <h3 class="es-club-venue-title"><?php echo esc_html($display_venue_name); ?></h3>
                    <?php if (!empty($venue_genres)): ?>
                    <div class="es-club-venue-genres">
                        <?php foreach ($venue_genres as $genre): ?>
                        <span class="es-club-venue-genre"><?php echo esc_html($genre); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="es-club-artist-grid">
                    <?php foreach ($venue_artists as $artist): 
                        $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                        $artist_target = (!empty($artist['link_external']) && !empty($artist['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                        $artist_tag = $artist_has_link ? 'a' : 'div';
                        $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' . $artist_target : '';
                    ?>
                    <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-club-artist-item">
                        <div class="es-club-artist-img">
                            <?php if (!empty($artist['image'])): ?>
                                <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                            <?php else: ?>
                                <div class="es-club-artist-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="es-club-artist-info">
                            <h4 class="es-club-artist-name"><?php echo esc_html($artist['name']); ?></h4>
                            <?php if (!empty($artist['references'])): ?>
                            <span class="es-club-artist-references"><?php echo esc_html($artist['references']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($artist['genre']) && !empty($event['show_artist_genres'])): ?>
                            <span class="es-club-artist-genre"><?php echo esc_html($artist['genre']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                            <span class="es-club-artist-time"><?php echo esc_html($artist['time_formatted']); ?></span>
                            <?php endif; ?>
                        </div>
                    </<?php echo $artist_tag; ?>>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php else: ?>
            <!-- Simple artist grid (no venues) -->
            <div class="es-club-artist-grid">
                <?php foreach ($event['artists'] as $artist): 
                    $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                    $artist_target = (!empty($artist['link_external']) && !empty($artist['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                    $artist_tag = $artist_has_link ? 'a' : 'div';
                    $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' . $artist_target : '';
                ?>
                <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-club-artist-item">
                    <div class="es-club-artist-img">
                        <?php if (!empty($artist['image'])): ?>
                            <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                        <?php else: ?>
                            <div class="es-club-artist-placeholder">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="es-club-artist-info">
                        <h4 class="es-club-artist-name"><?php echo esc_html($artist['name']); ?></h4>
                        <?php if (!empty($artist['references'])): ?>
                        <span class="es-club-artist-references"><?php echo esc_html($artist['references']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($artist['genre']) && !empty($event['show_artist_genres'])): ?>
                        <span class="es-club-artist-genre"><?php echo esc_html($artist['genre']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                        <span class="es-club-artist-time"><?php echo esc_html($artist['time_formatted']); ?></span>
                        <?php endif; ?>
                    </div>
                </<?php echo $artist_tag; ?>>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php ensemble_artist_section($event_id, $event['artists']); ?>
        </section>
        <?php endif; ?>
        
        <!-- 6. CATALOG -->
        <?php 
        if (ensemble_has_addon_hook('event_catalog')) {
            ob_start();
            ensemble_event_catalog($event_id, $event['location_id'] ?? null);
            $catalog_content = ob_get_clean();
            if (!empty(trim($catalog_content))):
        ?>
            <section class="es-club-section es-club-catalog">
                <?php echo $catalog_content; ?>
            </section>
        <?php 
            endif;
        }
        ?>
        
        <!-- 6b. TICKETS ADD-ON (under catalog) -->
        <?php if (ensemble_has_addon_hook('ticket_area')): ?>
        <section class="es-club-section es-club-tickets-section">
            <?php 
            ensemble_ticket_area($event_id, array(
                'price' => $event['price'],
                'ticket_url' => $event['ticket_url'],
                'event_status' => $event['status'],
            ));
            ?>
        </section>
        <?php endif; ?>
        
        <!-- 7. ADDITIONAL INFORMATION -->
        <?php if (!empty($event['additional_info'])): ?>
        <section class="es-club-section es-club-additional-info">
            <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('additional_info')): ?>
            <h2 class="es-club-section-title"><?php _e('Weitere Informationen', 'ensemble'); ?></h2>
            <?php endif; ?>
            <div class="es-club-prose">
                <?php echo wpautop($event['additional_info']); ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- 8. GALLERY -->
        <?php 
        if ((ensemble_has_addon_hook('gallery_area') || !empty($event['gallery'])) && (!function_exists('ensemble_show_addon') || ensemble_show_addon('gallery'))) {
            echo '<section class="es-club-section es-club-gallery">';
            if (!function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('gallery')) {
                echo '<h2 class="es-club-section-title">' . __('Galerie', 'ensemble') . '</h2>';
            }
            ensemble_gallery_area($event_id, $event['gallery'] ?: array());
            echo '</section>';
        }
        ?>
        
        <!-- 9. LOCATION -->
        <?php if ($event['location'] && (!function_exists('ensemble_show_section') || ensemble_show_section('location'))): 
            $loc = $event['location'];
            $loc_name = !empty($loc['display_name']) ? $loc['display_name'] : (!empty($loc['name']) ? $loc['name'] : '');
            $loc_image = '';
            if (!empty($loc['id'])) {
                $loc_image = get_the_post_thumbnail_url($loc['id'], 'medium');
            }
        ?>
        <section class="es-club-section es-club-location-section">
            <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('location')): ?>
            <h2 class="es-club-section-title"><?php _e('Location', 'ensemble'); ?></h2>
            <?php endif; ?>
            <div class="es-club-location-row">
                <div class="es-club-location-image">
                    <?php if ($loc_image): ?>
                    <img src="<?php echo esc_url($loc_image); ?>" alt="<?php echo esc_attr($loc_name); ?>" loading="lazy">
                    <?php else: ?>
                    <div class="es-club-location-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="es-club-location-content">
                    <h3 class="es-club-location-name">
                        <?php if (!empty($loc['link_url'])): ?>
                        <a href="<?php echo esc_url($loc['link_url']); ?>"><?php echo esc_html($loc_name); ?></a>
                        <?php else: ?>
                        <?php echo esc_html($loc_name); ?>
                        <?php endif; ?>
                    </h3>
                    
                    <?php 
                    $address_parts = array();
                    if (!empty($loc['street'])) $address_parts[] = $loc['street'];
                    if (!empty($loc['zip']) || !empty($loc['city'])) {
                        $address_parts[] = trim(($loc['zip'] ?? '') . ' ' . ($loc['city'] ?? ''));
                    }
                    if (!empty($address_parts)):
                    ?>
                    <p class="es-club-location-address"><?php echo esc_html(implode(', ', array_filter($address_parts))); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($loc['additional_info'])): ?>
                    <p class="es-club-location-hint"><?php echo esc_html($loc['additional_info']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($loc['link_url'])): ?>
                    <a href="<?php echo esc_url($loc['link_url']); ?>" class="es-club-location-link">
                        <?php _e('Zur Location', 'ensemble'); ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php ensemble_after_location($event_id, $event['location'] ?: array()); ?>
        </section>
        <?php endif; ?>
        
        <!-- 10. MAPS -->
        <?php 
        if (ensemble_has_addon_hook('location_map') && !empty($event['location'])) {
            echo '<section class="es-club-section es-club-map">';
            ensemble_location_map($event['location_id'] ?? 0, $event['location']);
            echo '</section>';
        }
        ?>
        
        <?php ensemble_event_footer($event_id); ?>
        
    </div><!-- .es-club-container -->
    
    <?php endwhile; endif; ?>
    
    <!-- 11. RELATED EVENTS -->
    <?php 
    ensemble_related_events($event_id, array(
        'layout' => 'club',
        'heading_tag' => 'h2',
        'heading_class' => 'es-club-section-title',
        'container_class' => 'es-club-section es-club-related',
        'grid_class' => 'es-club-related-grid',
    ));
    ?>
    
    <!-- 12. SOCIAL SHARE -->
    <div class="es-club-container">
        <?php 
        ensemble_social_share($event_id, array(
            'title' => $event['title'],
            'permalink' => $event['permalink'],
            'featured_image' => $event['featured_image'],
        ));
        ?>
    </div>
    
    <?php 
    ensemble_after_event($event_id, array(
        'layout' => 'club'
    ));
    ?>
    
</div>

<?php get_footer(); ?>
