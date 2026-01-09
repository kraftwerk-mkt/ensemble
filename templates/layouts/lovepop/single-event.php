<?php
/**
 * Single Event Template - LOVEPOP LAYOUT
 * 
 * Dark gradient background with magenta accents
 * Bold, impactful design
 *
 * @package Ensemble
 * @version 1.2.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue base CSS
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-shortcodes', ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css', array('ensemble-base'), ENSEMBLE_VERSION);

// Enqueue Lovepop Layout CSS
wp_enqueue_style('ensemble-layout-lovepop', ENSEMBLE_PLUGIN_URL . 'templates/layouts/lovepop/style.css', array('ensemble-base', 'ensemble-shortcodes'), ENSEMBLE_VERSION);

// Load Montserrat font
wp_enqueue_style('ensemble-lovepop-font', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap', array(), ENSEMBLE_VERSION);

$event_id = get_the_ID();
$event = es_load_event_data($event_id);
$is_cancelled = ($event['status'] === 'cancelled');
?>

<div class="es-lovepop-single es-lovepop-event">
    
    <?php ensemble_before_event($event_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- Hero -->
    <header class="es-lovepop-hero">
        <?php if (has_post_thumbnail()): ?>
        <div class="es-lovepop-hero-media">
            <?php the_post_thumbnail('full'); ?>
            <div class="es-lovepop-hero-overlay"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-lovepop-hero-content">
            
            <?php if ($event['status'] && $event['status'] !== 'publish'): ?>
            <span class="es-lovepop-hero-status es-status-<?php echo esc_attr($event['status']); ?>">
                <?php echo esc_html(function_exists('ensemble_get_status_label') ? ensemble_get_status_label($event['status']) : ucfirst($event['status'])); ?>
            </span>
            <?php endif; ?>
            
            <?php if (!empty($event['badge_label'])): ?>
            <span class="es-lovepop-hero-badge es-badge-<?php echo esc_attr($event['badge_raw'] ?: 'custom'); ?>">
                <?php echo esc_html($event['badge_label']); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($event['date']): ?>
            <time class="es-lovepop-hero-date" datetime="<?php echo esc_attr($event['date']); ?>">
                <?php echo date_i18n('l, j. F Y', strtotime($event['date'])); ?>
                <?php if ($event['formatted_time']): ?>
                    <span class="es-lovepop-hero-time"><?php echo esc_html($event['formatted_time']); ?></span>
                <?php endif; ?>
            </time>
            <?php endif; ?>
            
            <h1 class="es-lovepop-hero-title"><?php the_title(); ?></h1>
            
            <?php ensemble_after_title($event_id); ?>
        </div>
        
        <?php ensemble_event_header($event_id); ?>
    </header>
    
    <!-- Content -->
    <div class="es-lovepop-body">
        <div class="es-lovepop-container">
            
            <div class="es-lovepop-layout-grid">
                
                <!-- Main -->
                <main class="es-lovepop-main">
                    
                    <?php ensemble_before_content($event_id); ?>
                    
                    <!-- Description -->
                    <?php if (($event['description'] || get_the_content()) && (!function_exists('ensemble_show_section') || ensemble_show_section('description'))): ?>
                    <section class="es-lovepop-section es-lovepop-description">
                        <?php if (function_exists('ensemble_show_header') && ensemble_show_header('description')): ?>
                        <h2 class="es-lovepop-section-title"><?php _e('About', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        <div class="es-lovepop-prose">
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
                    <div class="es-lovepop-ticket-card es-lovepop-ticket-mobile">
                        <?php if ($event['price']): ?>
                        <div class="es-lovepop-ticket-price"><?php echo esc_html($event['price']); ?></div>
                        <?php if (!empty($event['price_note'])): ?>
                        <div class="es-lovepop-ticket-note"><?php echo esc_html($event['price_note']); ?></div>
                        <?php endif; ?>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-lovepop-btn es-lovepop-btn-ticket" target="_blank" rel="noopener">
                            <?php echo esc_html($event['button_text'] ?: __('Tickets kaufen', 'ensemble')); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Mobile Tickets Add-on -->
                    <?php if (ensemble_has_addon_hook('ticket_area') && !$is_cancelled): ?>
                    <div class="es-lovepop-tickets-addon es-lovepop-ticket-mobile">
                        <?php 
                        ensemble_ticket_area($event_id, array(
                            'price' => $event['price'],
                            'ticket_url' => $event['ticket_url'],
                            'event_status' => $event['status'],
                        ));
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Artists -->
                    <?php if (!empty($event['artists']) && (!function_exists('ensemble_show_section') || ensemble_show_section('artists'))): ?>
                    <section class="es-lovepop-section es-lovepop-artists">
                        <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('artists')): ?>
                        <h2 class="es-lovepop-section-title"><?php _e('Line-Up', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        
                        <?php 
                        // Group artists by venue
                        $artist_groups = es_group_artists_by_venue($event['artists']);
                        $venue_config = !empty($event['venue_config']) ? $event['venue_config'] : array();
                        ?>
                        
                        <?php 
                        // Show event genres as meta if no venues are used
                        if (!$artist_groups['has_venues'] && !empty($event['genres'])): 
                        ?>
                        <div class="es-lovepop-genre-meta">
                            <?php foreach ($event['genres'] as $genre): ?>
                            <span class="es-lovepop-genre-tag"><?php echo esc_html(is_object($genre) ? $genre->name : $genre); ?></span>
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
                        <div class="es-lovepop-venue-group">
                            <div class="es-lovepop-venue-header">
                                <h3 class="es-lovepop-venue-title">
                                    <?php echo esc_html($display_venue_name); ?>
                                </h3>
                                <?php if (!empty($venue_genres)): ?>
                                <div class="es-lovepop-venue-genres">
                                    <?php foreach ($venue_genres as $genre): ?>
                                    <span class="es-lovepop-venue-genre"><?php echo esc_html($genre); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="es-lovepop-artist-grid">
                                <?php foreach ($venue_artists as $artist): 
                                    $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                                    $artist_target = (!empty($artist['link_external']) && !empty($artist['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                                    $artist_tag = $artist_has_link ? 'a' : 'div';
                                    $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' . $artist_target : '';
                                ?>
                                <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-lovepop-artist-item">
                                    <?php if (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image')): ?>
                                    <div class="es-lovepop-artist-img">
                                        <?php if (!empty($artist['image'])): ?>
                                            <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="es-lovepop-artist-info">
                                        <h4><?php echo esc_html($artist['name']); ?></h4>
                                        <?php if (!empty($artist['genre']) && !empty($event['show_artist_genres'])): ?>
                                            <span class="es-lovepop-artist-genre"><?php echo esc_html($artist['genre']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($artist['references'])): ?>
                                            <span class="es-lovepop-artist-references"><?php echo esc_html($artist['references']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                                            <span class="es-lovepop-artist-time"><?php echo esc_html($artist['time_formatted']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </<?php echo $artist_tag; ?>>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php else: ?>
                        <!-- Simple artist grid -->
                        <div class="es-lovepop-artist-grid">
                            <?php foreach ($event['artists'] as $artist): 
                                $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                                $artist_target = (!empty($artist['link_external']) && !empty($artist['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                                $artist_tag = $artist_has_link ? 'a' : 'div';
                                $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' . $artist_target : '';
                            ?>
                            <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-lovepop-artist-item">
                                <?php if (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image')): ?>
                                <div class="es-lovepop-artist-img">
                                    <?php if (!empty($artist['image'])): ?>
                                        <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="es-lovepop-artist-info">
                                    <h4><?php echo esc_html($artist['name']); ?></h4>
                                    <?php if (!empty($artist['genre']) && !empty($event['show_artist_genres'])): ?>
                                        <span class="es-lovepop-artist-genre"><?php echo esc_html($artist['genre']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($artist['references'])): ?>
                                        <span class="es-lovepop-artist-references"><?php echo esc_html($artist['references']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                                        <span class="es-lovepop-artist-time"><?php echo esc_html($artist['time_formatted']); ?></span>
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
                    
                    <!-- Catalog -->
                    <?php 
                    if (ensemble_has_addon_hook('event_catalog')) {
                        ob_start();
                        ensemble_event_catalog($event_id, $event['location_id']);
                        $catalog_content = ob_get_clean();
                        if (!empty(trim($catalog_content))):
                    ?>
                        <section class="es-lovepop-section es-lovepop-catalog">
                            <?php echo $catalog_content; ?>
                        </section>
                    <?php 
                        endif;
                    }
                    ?>
                    
                    <!-- Additional Information -->
                    <?php if (!empty($event['additional_info'])): ?>
                    <section class="es-lovepop-section es-lovepop-additional-info">
                        <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('additional_info')): ?>
                        <h2 class="es-lovepop-section-title"><?php _e('Additional Information', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        <div class="es-lovepop-prose es-lovepop-info-content">
                            <?php echo wpautop($event['additional_info']); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Gallery -->
                    <?php 
                    if ((ensemble_has_addon_hook('gallery_area') || !empty($event['gallery'])) && (!function_exists('ensemble_show_addon') || ensemble_show_addon('gallery'))) {
                        echo '<section class="es-lovepop-section es-lovepop-gallery">';
                        if (!function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('gallery')) {
                            echo '<h2 class="es-lovepop-section-title">' . __('Gallery', 'ensemble') . '</h2>';
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
                <aside class="es-lovepop-aside">
                    
                    <?php 
                    // Main Sponsor Hook - displays main sponsor in sidebar
                    do_action('ensemble_main_sponsor_sidebar', $event_id);
                    ?>
                    
                    <!-- Ticket Button (TOP) - Desktop only (Normal Wizard Button) -->
                    <?php if ($event['ticket_url'] && !$is_cancelled): ?>
                    <div class="es-lovepop-ticket-card es-lovepop-ticket-desktop">
                        <?php if ($event['price']): ?>
                        <div class="es-lovepop-ticket-price"><?php echo esc_html($event['price']); ?></div>
                        <?php if (!empty($event['price_note'])): ?>
                        <div class="es-lovepop-ticket-note"><?php echo esc_html($event['price_note']); ?></div>
                        <?php endif; ?>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-lovepop-btn es-lovepop-btn-ticket" target="_blank" rel="noopener">
                            <?php echo esc_html($event['button_text'] ?: __('Tickets kaufen', 'ensemble')); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tickets Add-on (separate from normal button) -->
                    <?php if (ensemble_has_addon_hook('ticket_area') && !$is_cancelled): ?>
                    <div class="es-lovepop-tickets-addon es-lovepop-ticket-desktop">
                        <?php 
                        ensemble_ticket_area($event_id, array(
                            'price' => $event['price'],
                            'ticket_url' => $event['ticket_url'],
                            'event_status' => $event['status'],
                        ));
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- External Link Button -->
                    <?php if (!empty($event['external_url'])): ?>
                    <div class="es-lovepop-external-link">
                        <a href="<?php echo esc_url($event['external_url']); ?>" class="es-lovepop-btn es-lovepop-btn-secondary" target="_blank" rel="noopener">
                            <span class="dashicons dashicons-external"></span>
                            <?php echo esc_html($event['external_text']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Reservation Area (eigene Card) -->
                    <?php 
                    ob_start();
                    ensemble_event_sidebar($event_id);
                    $reservation_content = ob_get_clean();
                    if (!empty(trim($reservation_content))):
                    ?>
                    <div class="es-lovepop-reservation-card">
                        <?php echo $reservation_content; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Info Card -->
                    <div class="es-lovepop-info-card">
                        
                        <!-- Location -->
                        <?php if ($event['location']): 
                            $loc = $event['location'];
                            $loc_has_link = !empty($loc['link_enabled']) && !empty($loc['link_url']);
                            $loc_target = (!empty($loc['link_external']) && !empty($loc['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                            $loc_formatted = function_exists('ensemble_format_location_address') 
                                ? ensemble_format_location_address($loc) 
                                : array('name' => (!empty($loc['display_name']) ? $loc['display_name'] : $loc['name']), 'address_line' => $loc['city'] ?? '');
                        ?>
                        <div class="es-lovepop-info-row">
                            <span class="es-lovepop-info-label"><?php _e('Location', 'ensemble'); ?></span>
                            <?php if ($loc_has_link): ?>
                            <a href="<?php echo esc_url($loc['link_url']); ?>" class="es-lovepop-info-value es-lovepop-info-link"<?php echo $loc_target; ?>>
                                <?php echo esc_html($loc_formatted['name']); ?>
                            </a>
                            <?php else: ?>
                            <span class="es-lovepop-info-value"><?php echo esc_html($loc_formatted['name']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($loc_formatted['address_line'])): ?>
                            <span class="es-lovepop-info-sub"><?php echo esc_html($loc_formatted['address_line']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($loc['additional_info'])): ?>
                            <span class="es-lovepop-info-hint"><?php echo esc_html($loc['additional_info']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php ensemble_after_location($event_id, $event['location'] ?: array()); ?>
                        
                        <!-- Price (nur wenn kein Ticket Button oben) -->
                        <?php if ($event['price'] && !$event['ticket_url'] && (!function_exists('ensemble_show_meta') || ensemble_show_meta('price'))): ?>
                        <div class="es-lovepop-info-row">
                            <span class="es-lovepop-info-label"><?php _e('Eintritt', 'ensemble'); ?></span>
                            <span class="es-lovepop-info-value es-lovepop-info-price"><?php echo esc_html($event['price']); ?></span>
                            <?php if (!empty($event['price_note'])): ?>
                            <span class="es-lovepop-info-sub"><?php echo esc_html($event['price_note']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Facebook Event -->
                        <?php if (!empty($event['facebook_url'])): ?>
                        <div class="es-lovepop-info-row es-lovepop-social-row">
                            <a href="<?php echo esc_url($event['facebook_url']); ?>" class="es-lovepop-facebook-link" target="_blank" rel="noopener noreferrer">
                                <span class="dashicons dashicons-facebook-alt"></span>
                                <?php _e('Facebook Event', 'ensemble'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php ensemble_event_meta($event_id, $event); ?>
                        
                        <?php ensemble_after_tickets($event_id); ?>
                        
                    </div>
                    
                </aside>
                
            </div>
            
            <!-- Related Events -->
            <?php 
            ensemble_related_events($event_id, array(
                'categories' => $event['categories'],
                'location_id' => $event['location_id'],
                'artist_ids' => !empty($event['artists']) ? array_column($event['artists'], 'id') : array(),
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
