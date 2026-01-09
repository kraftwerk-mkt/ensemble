<?php
/**
 * Single Event Template - KINKY LAYOUT
 * 
 * Sensual dark design with sidebar layout
 * Based on Modern layout structure
 *
 * @package Ensemble
 * @version 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue base CSS
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-shortcodes', ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css', array('ensemble-base'), ENSEMBLE_VERSION);

// Enqueue Kinky Layout CSS
wp_enqueue_style('ensemble-layout-kinky', ENSEMBLE_PLUGIN_URL . 'templates/layouts/kinky/style.css', array('ensemble-base', 'ensemble-shortcodes'), ENSEMBLE_VERSION);

// Load Kinky fonts - Cinzel for elegant headlines, Lato for body
wp_enqueue_style('ensemble-kinky-fonts', 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Lato:wght@300;400;600;700&display=swap', array(), ENSEMBLE_VERSION);

$event_id = get_the_ID();
$event = es_load_event_data($event_id);
$is_cancelled = ($event['status'] === 'cancelled');
?>

<div class="es-kinky-single es-kinky-event">
    
    <?php ensemble_before_event($event_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- Hero -->
    <header class="es-kinky-hero">
        <?php if (has_post_thumbnail()): ?>
        <div class="es-kinky-hero-media">
            <?php the_post_thumbnail('full'); ?>
            <div class="es-kinky-hero-overlay"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-kinky-hero-content">
            
            <?php if ($event['status'] && $event['status'] !== 'publish'): ?>
            <span class="es-kinky-hero-status es-status-<?php echo esc_attr($event['status']); ?>">
                <?php echo esc_html(ensemble_get_status_label($event['status'])); ?>
            </span>
            <?php endif; ?>
            
            <?php if (!empty($event['badge_label'])): ?>
            <span class="es-kinky-hero-badge es-badge-<?php echo esc_attr($event['badge_raw'] ?: 'custom'); ?>">
                <?php echo esc_html($event['badge_label']); ?>
            </span>
            <?php endif; ?>
            
            <h1 class="es-kinky-hero-title"><?php the_title(); ?></h1>
            
            <?php if ($event['date']): ?>
            <time class="es-kinky-hero-date" datetime="<?php echo esc_attr($event['date']); ?>">
                <?php echo date_i18n('l, j. F Y', strtotime($event['date'])); ?>
                <?php if ($event['formatted_time']): ?>
                    <span class="es-kinky-hero-time"><?php echo esc_html($event['formatted_time']); ?></span>
                <?php endif; ?>
            </time>
            <?php endif; ?>
            
            <?php ensemble_after_title($event_id); ?>
        </div>
        
        <?php ensemble_event_header($event_id); ?>
    </header>
    
    <!-- Content -->
    <div class="es-kinky-body">
        <div class="es-kinky-container">
            
            <div class="es-kinky-layout">
                
                <!-- Main -->
                <main class="es-kinky-main">
                    
                    <?php ensemble_before_content($event_id); ?>
                    
                    <?php if (($event['description'] || get_the_content()) && (!function_exists('ensemble_show_section') || ensemble_show_section('description'))): ?>
                    <section class="es-kinky-section es-kinky-description">
                        <?php if (function_exists('ensemble_show_header') && ensemble_show_header('description')): ?>
                        <h2 class="es-kinky-section-title"><?php _e('About', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        <div class="es-kinky-prose">
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
                    
                    <!-- Mobile Ticket Card -->
                    <?php if ($event['ticket_url'] && !$is_cancelled): ?>
                    <div class="es-kinky-ticket-mobile">
                        <?php if ($event['price']): ?>
                        <span class="es-kinky-ticket-price"><?php echo esc_html($event['price']); ?></span>
                        <?php if (!empty($event['price_note'])): ?>
                        <span class="es-kinky-ticket-note"><?php echo esc_html($event['price_note']); ?></span>
                        <?php endif; ?>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-kinky-btn" target="_blank" rel="noopener">
                            <?php echo esc_html($event['button_text'] ?: __('Tickets', 'ensemble')); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php ensemble_after_description($event_id); ?>
                    
                    <!-- Artists -->
                    <?php if (!empty($event['artists']) && (!function_exists('ensemble_show_section') || ensemble_show_section('artists'))): ?>
                    <section class="es-kinky-section es-kinky-artists">
                        <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('artists')): ?>
                        <h2 class="es-kinky-section-title"><?php _e('Line-Up', 'ensemble'); ?></h2>
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
                        <div class="es-kinky-genre-meta">
                            <?php foreach ($event['genres'] as $genre): ?>
                            <span class="es-kinky-genre-tag"><?php echo esc_html(is_object($genre) ? $genre->name : $genre); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($artist_groups['has_venues']): ?>
                        <!-- Artists grouped by Room/Stage -->
                        <?php foreach ($artist_groups['groups'] as $venue_name => $venue_artists): 
                            // Get custom venue name, description and genres from config
                            $display_venue_name = $venue_name;
                            $venue_description = '';
                            $venue_genres = array();
                            if (isset($venue_config[$venue_name])) {
                                if (!empty($venue_config[$venue_name]['customName'])) {
                                    $display_venue_name = $venue_config[$venue_name]['customName'];
                                }
                                if (!empty($venue_config[$venue_name]['description'])) {
                                    $venue_description = $venue_config[$venue_name]['description'];
                                }
                                if (!empty($venue_config[$venue_name]['genres'])) {
                                    // Resolve genre IDs to names
                                    foreach ($venue_config[$venue_name]['genres'] as $genre_id) {
                                        $genre_term = get_term($genre_id, 'ensemble_genre');
                                        if ($genre_term && !is_wp_error($genre_term)) {
                                            $venue_genres[] = $genre_term->name;
                                        }
                                    }
                                }
                            }
                        ?>
                        <div class="es-kinky-venue-group">
                            <div class="es-kinky-venue-header">
                                <h3 class="es-kinky-venue-title"><?php echo esc_html($display_venue_name); ?></h3>
                                <?php if (!empty($venue_genres)): ?>
                                <div class="es-kinky-venue-genres">
                                    <?php foreach ($venue_genres as $genre): ?>
                                    <span class="es-kinky-genre-tag"><?php echo esc_html($genre); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($venue_description)): ?>
                            <div class="es-kinky-venue-description">
                                <?php echo wpautop(esc_html($venue_description)); ?>
                            </div>
                            <?php endif; ?>
                            <div class="es-kinky-artists-grid">
                                <?php foreach ($venue_artists as $artist): 
                                    $artist_name = $artist['name'] ?? '';
                                    $artist_image = $artist['image'] ?? '';
                                    $artist_role = $artist['role'] ?? '';
                                    $artist_references = $artist['references'] ?? '';
                                    $artist_genre = $artist['genre'] ?? '';
                                    $artist_time = $artist['time_formatted'] ?? ($artist['time'] ?? '');
                                    $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                                    $artist_link_url = $artist['link_url'] ?? '';
                                    $artist_link_external = !empty($artist['link_external']) && !empty($artist['link_new_tab']);
                                    $artist_target = $artist_link_external ? ' target="_blank" rel="noopener noreferrer"' : '';
                                    if (empty($artist_name)) continue;
                                ?>
                                <div class="es-kinky-artist-card">
                                    <?php if ($artist_image && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image'))): ?>
                                    <div class="es-kinky-artist-image">
                                        <?php if ($artist_has_link): ?>
                                        <a href="<?php echo esc_url($artist_link_url); ?>"<?php echo $artist_target; ?>>
                                            <img src="<?php echo esc_url($artist_image); ?>" alt="<?php echo esc_attr($artist_name); ?>">
                                        </a>
                                        <?php else: ?>
                                        <img src="<?php echo esc_url($artist_image); ?>" alt="<?php echo esc_attr($artist_name); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="es-kinky-artist-info">
                                        <?php if ($artist_has_link): ?>
                                        <a href="<?php echo esc_url($artist_link_url); ?>" class="es-kinky-artist-name"<?php echo $artist_target; ?>><?php echo esc_html($artist_name); ?></a>
                                        <?php else: ?>
                                        <span class="es-kinky-artist-name"><?php echo esc_html($artist_name); ?></span>
                                        <?php endif; ?>
                                        <?php if ($artist_references && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_references'))): ?>
                                        <span class="es-kinky-artist-references"><?php echo esc_html($artist_references); ?></span>
                                        <?php endif; ?>
                                        <?php if ($artist_genre && !empty($event['show_artist_genres'])): ?>
                                        <span class="es-kinky-artist-genre"><?php echo esc_html($artist_genre); ?></span>
                                        <?php endif; ?>
                                        <?php if ($artist_role): ?>
                                        <span class="es-kinky-artist-role"><?php echo esc_html($artist_role); ?></span>
                                        <?php endif; ?>
                                        <?php if ($artist_time && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                                        <span class="es-kinky-artist-time"><?php echo esc_html($artist_time); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php else: ?>
                        <!-- Artists without venue grouping -->
                        <div class="es-kinky-artists-grid">
                            <?php foreach ($event['artists'] as $artist): 
                                $artist_name = $artist['name'] ?? '';
                                $artist_image = $artist['image'] ?? '';
                                $artist_role = $artist['role'] ?? '';
                                $artist_references = $artist['references'] ?? '';
                                $artist_genre = $artist['genre'] ?? '';
                                $artist_time = $artist['time_formatted'] ?? ($artist['time'] ?? '');
                                $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                                $artist_link_url = $artist['link_url'] ?? '';
                                $artist_link_external = !empty($artist['link_external']) && !empty($artist['link_new_tab']);
                                $artist_target = $artist_link_external ? ' target="_blank" rel="noopener noreferrer"' : '';
                                if (empty($artist_name)) continue;
                            ?>
                            <div class="es-kinky-artist-card">
                                <?php if ($artist_image && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image'))): ?>
                                <div class="es-kinky-artist-image">
                                    <?php if ($artist_has_link): ?>
                                    <a href="<?php echo esc_url($artist_link_url); ?>"<?php echo $artist_target; ?>>
                                        <img src="<?php echo esc_url($artist_image); ?>" alt="<?php echo esc_attr($artist_name); ?>">
                                    </a>
                                    <?php else: ?>
                                    <img src="<?php echo esc_url($artist_image); ?>" alt="<?php echo esc_attr($artist_name); ?>">
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="es-kinky-artist-info">
                                    <?php if ($artist_has_link): ?>
                                    <a href="<?php echo esc_url($artist_link_url); ?>" class="es-kinky-artist-name"<?php echo $artist_target; ?>><?php echo esc_html($artist_name); ?></a>
                                    <?php else: ?>
                                    <span class="es-kinky-artist-name"><?php echo esc_html($artist_name); ?></span>
                                    <?php endif; ?>
                                    <?php if ($artist_references && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_references'))): ?>
                                    <span class="es-kinky-artist-references"><?php echo esc_html($artist_references); ?></span>
                                    <?php endif; ?>
                                    <?php if ($artist_genre && !empty($event['show_artist_genres'])): ?>
                                    <span class="es-kinky-artist-genre"><?php echo esc_html($artist_genre); ?></span>
                                    <?php endif; ?>
                                    <?php if ($artist_role): ?>
                                    <span class="es-kinky-artist-role"><?php echo esc_html($artist_role); ?></span>
                                    <?php endif; ?>
                                    <?php if ($artist_time && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                                    <span class="es-kinky-artist-time"><?php echo esc_html($artist_time); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                    </section>
                    <?php endif; ?>
                    
                    <!-- Catalog -->
                    <?php 
                    if (ensemble_has_addon_hook('event_catalog')) {
                        echo '<section class="es-kinky-section es-kinky-catalog">';
                        ensemble_event_catalog($event_id, $event['location_id']);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Additional Information -->
                    <?php if (!empty($event['additional_info'])): ?>
                    <section class="es-kinky-section es-kinky-additional-info">
                        <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('additional_info')): ?>
                        <h2 class="es-kinky-section-title"><?php _e('Additional Information', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        <div class="es-kinky-prose">
                            <?php echo wpautop($event['additional_info']); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Gallery -->
                    <?php 
                    if ((ensemble_has_addon_hook('gallery_area') || !empty($event['gallery'])) && (!function_exists('ensemble_show_addon') || ensemble_show_addon('gallery'))) {
                        echo '<section class="es-kinky-section es-kinky-gallery">';
                        if (!function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('gallery')) {
                            echo '<h2 class="es-kinky-section-title">' . __('Gallery', 'ensemble') . '</h2>';
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
                <aside class="es-kinky-aside">
                    
                    <div class="es-kinky-info-card">
                        
                        <!-- Ticket Section -->
                        <?php if ($event['price'] || $event['ticket_url']): ?>
                        <div class="es-kinky-ticket-section">
                            <?php if ($event['price'] && (!function_exists('ensemble_show_meta') || ensemble_show_meta('price'))): ?>
                            <div class="es-kinky-info-row">
                                <span class="es-kinky-info-label"><?php _e('Eintritt', 'ensemble'); ?></span>
                                <span class="es-kinky-info-value es-kinky-info-price"><?php echo esc_html($event['price']); ?></span>
                                <?php if (!empty($event['price_note'])): ?>
                                <span class="es-kinky-info-sub"><?php echo esc_html($event['price_note']); ?></span>
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
                            <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-kinky-btn es-kinky-ticket-desktop" target="_blank" rel="noopener">
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
                        <div class="es-kinky-info-row">
                            <span class="es-kinky-info-label"><?php _e('Location', 'ensemble'); ?></span>
                            <?php if ($loc_has_link): ?>
                            <a href="<?php echo esc_url($loc['link_url']); ?>" class="es-kinky-info-value es-kinky-info-link"<?php echo $loc_target; ?>>
                                <?php echo esc_html($loc_formatted['name']); ?>
                            </a>
                            <?php else: ?>
                            <span class="es-kinky-info-value"><?php echo esc_html($loc_formatted['name']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($loc_formatted['address_line'])): ?>
                            <span class="es-kinky-info-sub"><?php echo esc_html($loc_formatted['address_line']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($loc['additional_info'])): ?>
                            <span class="es-kinky-info-hint"><?php echo esc_html($loc['additional_info']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php ensemble_after_location($event_id, $event['location'] ?: array()); ?>
                        
                        <?php ensemble_event_meta($event_id, $event); ?>
                        
                        <?php if (!empty($event['external_url'])): ?>
                        <a href="<?php echo esc_url($event['external_url']); ?>" class="es-kinky-btn es-kinky-btn-secondary" target="_blank" rel="noopener">
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
