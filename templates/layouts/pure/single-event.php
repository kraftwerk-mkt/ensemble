<?php
/**
 * Template: Pure Single Event
 * Ultra-minimal design with clean typography
 * Dark/Light mode support with toggle button
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Load event data
$event_id = get_the_ID();
$event = function_exists('es_load_event_data') ? es_load_event_data($event_id) : array();

// Basic data with fallbacks
$title = !empty($event['title']) ? $event['title'] : get_the_title();
$content = !empty($event['content']) ? $event['content'] : get_the_content();
$excerpt = !empty($event['excerpt']) ? $event['excerpt'] : get_the_excerpt();
$featured_image = get_the_post_thumbnail_url($event_id, 'full');
$permalink = get_permalink();

// Date & Time
$start_date = !empty($event['start_date']) ? $event['start_date'] : '';
$start_time = !empty($event['start_time']) ? $event['start_time'] : '';
$end_date = !empty($event['end_date']) ? $event['end_date'] : '';
$end_time = !empty($event['end_time']) ? $event['end_time'] : '';
$formatted_date = !empty($event['formatted_date']) ? $event['formatted_date'] : '';

// Format date if not provided
if (empty($formatted_date) && $start_date) {
    $timestamp = strtotime($start_date);
    $formatted_date = date_i18n('l, j F Y', $timestamp);
}

// Location
$location = !empty($event['location']) ? $event['location'] : null;
$location_name = '';
$location_address = '';
$location_city = '';
$location_image = '';
$location_permalink = '';

if (is_array($location)) {
    $location_name = $location['name'] ?? $location['title'] ?? '';
    $location_address = $location['address'] ?? '';
    $location_city = $location['city'] ?? '';
    $location_image = $location['image'] ?? $location['featured_image'] ?? '';
    $location_permalink = $location['permalink'] ?? '';
} elseif (is_object($location)) {
    $location_name = $location->post_title ?? '';
    $location_permalink = get_permalink($location->ID);
}

// Ticket & Price
$ticket_url = !empty($event['ticket_url']) ? $event['ticket_url'] : '';
$button_text = !empty($event['button_text']) ? $event['button_text'] : __('Get Tickets', 'ensemble');
$price = !empty($event['price']) ? $event['price'] : '';
$price_info = !empty($event['price_info']) ? $event['price_info'] : '';

// External Link
$external_url = !empty($event['external_url']) ? $event['external_url'] : '';
$external_text = !empty($event['external_text']) ? $event['external_text'] : __('More Info', 'ensemble');

// Status
$status = !empty($event['status']) ? $event['status'] : '';
$is_cancelled = $status === 'cancelled';
$is_soldout = $status === 'soldout';
$is_postponed = $status === 'postponed';

// Artists
$artists = !empty($event['artists']) ? $event['artists'] : array();

// Gallery
$gallery = !empty($event['gallery']) ? $event['gallery'] : array();

// Get current mode
$current_mode = 'light';
if (class_exists('ES_Layout_Sets')) {
    $current_mode = ES_Layout_Sets::get_active_mode();
}

// Categories/Genres
$categories = get_the_terms($event_id, 'ensemble_category');
?>

<article class="es-pure-single-event es-layout-pure <?php echo 'es-mode-' . esc_attr($current_mode); ?>" id="es-event-<?php echo esc_attr($event_id); ?>">

    <?php 
    // Hook: Before Event
    if (function_exists('ensemble_before_event')) {
        ensemble_before_event($event_id);
    }
    do_action('ensemble_before_single_event', $event_id, $event);
    ?>

    <!-- HERO IMAGE -->
    <?php if ($featured_image): ?>
    <div class="es-pure-hero">
        <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>">
    </div>
    <?php endif; ?>

    <!-- HEADER -->
    <header class="es-pure-header">
        <div class="es-pure-header-inner">
            
            <!-- Meta (Date, Time, Location) -->
            <div class="es-pure-header-meta">
                <?php if ($formatted_date): ?>
                <div class="es-pure-header-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span><?php echo esc_html($formatted_date); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($start_time): ?>
                <div class="es-pure-header-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span><?php echo esc_html($start_time); ?><?php if ($end_time) echo ' – ' . esc_html($end_time); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($location_name): ?>
                <div class="es-pure-header-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span><?php echo esc_html($location_name); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($categories && !is_wp_error($categories)): ?>
                <div class="es-pure-header-meta-item">
                    <span><?php echo esc_html(implode(', ', wp_list_pluck($categories, 'name'))); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Title with Badge -->
            <div class="es-pure-title-wrapper">
                <h1 class="es-pure-title"><?php echo esc_html($title); ?></h1>
                <?php 
                // Event Badge
                $badge_label = !empty($event['badge_label']) ? $event['badge_label'] : '';
                $badge_raw = !empty($event['badge_raw']) ? $event['badge_raw'] : '';
                if ($badge_label): 
                ?>
                <span class="es-pure-event-badge es-badge-<?php echo esc_attr($badge_raw ?: 'custom'); ?>">
                    <?php echo esc_html($badge_label); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Status Badge -->
            <?php if ($is_cancelled || $is_soldout || $is_postponed): ?>
            <div class="es-pure-status-badge" style="margin-bottom: 24px;">
                <span class="es-pure-btn es-pure-btn-ghost" style="pointer-events: none; opacity: 0.7;">
                    <?php 
                    if ($is_cancelled) _e('Event Cancelled', 'ensemble');
                    elseif ($is_soldout) _e('Sold Out', 'ensemble');
                    elseif ($is_postponed) _e('Postponed', 'ensemble');
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
        </div>
    </header>

    <!-- CONTENT AREA -->
    <div class="es-pure-content-wrapper">
        <div class="es-pure-layout">
            
            <!-- MAIN CONTENT -->
            <main class="es-pure-main">
                
                <?php 
                // Hook: Event Meta
                if (function_exists('ensemble_event_meta')) {
                    ensemble_event_meta($event_id);
                }
                ?>
                
                <!-- Description -->
                <?php if (!empty($content) && (!function_exists('ensemble_show_section') || ensemble_show_section('description'))): ?>
                <section class="es-pure-section">
                    <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('description')): ?>
                    <h2 class="es-pure-section-title"><?php _e('About', 'ensemble'); ?></h2>
                    <?php endif; ?>
                    <div class="es-pure-prose">
                        <?php echo wp_kses_post(wpautop($content)); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Artists / Line-Up -->
                <?php if (!empty($artists) && (!function_exists('ensemble_show_section') || ensemble_show_section('artists'))): ?>
                <section class="es-pure-section">
                    <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('artists')): ?>
                    <h2 class="es-pure-section-title"><?php _e('Line-Up', 'ensemble'); ?></h2>
                    <?php endif; ?>
                    
                    <?php 
                    // Group artists by venue
                    $artist_groups = function_exists('es_group_artists_by_venue') ? es_group_artists_by_venue($artists) : array('has_venues' => false, 'groups' => array('' => $artists));
                    $venue_config = !empty($event['venue_config']) ? $event['venue_config'] : array();
                    ?>
                    
                    <?php 
                    // Show event genres as meta if no venues are used
                    $event_genres = !empty($event['genres']) ? $event['genres'] : array();
                    if (!$artist_groups['has_venues'] && !empty($event_genres)): 
                    ?>
                    <div class="es-pure-genre-meta">
                        <?php foreach ($event_genres as $genre): ?>
                        <span class="es-pure-genre-tag"><?php echo esc_html(is_object($genre) ? $genre->name : $genre); ?></span>
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
                    <div class="es-pure-venue-group">
                        <div class="es-pure-venue-header">
                            <h3 class="es-pure-venue-title"><?php echo esc_html($display_venue_name); ?></h3>
                            <?php if (!empty($venue_genres)): ?>
                            <div class="es-pure-venue-genres">
                                <?php foreach ($venue_genres as $vg): ?>
                                <span class="es-pure-venue-genre"><?php echo esc_html($vg); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="es-pure-lineup">
                            <?php foreach ($venue_artists as $artist): 
                                $a_name = '';
                                $a_image = '';
                                $a_genre = '';
                                $a_permalink = '#';
                                $a_time = '';
                                
                                if (is_array($artist)) {
                                    $a_name = $artist['name'] ?? $artist['title'] ?? '';
                                    $a_image = $artist['image'] ?? $artist['featured_image'] ?? '';
                                    $a_genre = $artist['genre'] ?? '';
                                    $a_permalink = $artist['permalink'] ?? '#';
                                    $a_time = $artist['set_time'] ?? $artist['time'] ?? '';
                                } elseif (is_object($artist)) {
                                    $a_name = $artist->post_title ?? '';
                                    $a_permalink = get_permalink($artist->ID);
                                    $a_image = get_the_post_thumbnail_url($artist->ID, 'thumbnail');
                                }
                            ?>
                            <a href="<?php echo esc_url($a_permalink); ?>" class="es-pure-lineup-item">
                                <div class="es-pure-lineup-image">
                                    <?php if ($a_image): ?>
                                        <img src="<?php echo esc_url($a_image); ?>" alt="<?php echo esc_attr($a_name); ?>">
                                    <?php else: ?>
                                        <div class="es-pure-placeholder">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 20px; height: 20px;">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                                <circle cx="12" cy="7" r="4"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="es-pure-lineup-info">
                                    <div class="es-pure-lineup-name"><?php echo esc_html($a_name); ?></div>
                                    <?php if ($a_genre): ?>
                                    <div class="es-pure-lineup-meta"><?php echo esc_html($a_genre); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($a_time): ?>
                                <div class="es-pure-lineup-time"><?php echo esc_html($a_time); ?></div>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php else: ?>
                    <!-- Artists without venue grouping -->
                    <div class="es-pure-lineup">
                        <?php foreach ($artists as $artist): 
                            $a_name = '';
                            $a_image = '';
                            $a_genre = '';
                            $a_permalink = '#';
                            $a_time = '';
                            
                            if (is_array($artist)) {
                                $a_name = $artist['name'] ?? $artist['title'] ?? '';
                                $a_image = $artist['image'] ?? $artist['featured_image'] ?? '';
                                $a_genre = $artist['genre'] ?? '';
                                $a_permalink = $artist['permalink'] ?? '#';
                                $a_time = $artist['set_time'] ?? $artist['time'] ?? '';
                            } elseif (is_object($artist)) {
                                $a_name = $artist->post_title ?? '';
                                $a_permalink = get_permalink($artist->ID);
                                $a_image = get_the_post_thumbnail_url($artist->ID, 'thumbnail');
                            }
                        ?>
                        <a href="<?php echo esc_url($a_permalink); ?>" class="es-pure-lineup-item">
                            <div class="es-pure-lineup-image">
                                <?php if ($a_image): ?>
                                    <img src="<?php echo esc_url($a_image); ?>" alt="<?php echo esc_attr($a_name); ?>">
                                <?php else: ?>
                                    <div class="es-pure-placeholder">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 20px; height: 20px;">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="es-pure-lineup-info">
                                <div class="es-pure-lineup-name"><?php echo esc_html($a_name); ?></div>
                                <?php if ($a_genre): ?>
                                <div class="es-pure-lineup-meta"><?php echo esc_html($a_genre); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if ($a_time): ?>
                            <div class="es-pure-lineup-time"><?php echo esc_html($a_time); ?></div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
                
                <!-- Location -->
                <?php if ($location_name && (!function_exists('ensemble_show_section') || ensemble_show_section('location'))): ?>
                <section class="es-pure-section">
                    <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('location')): ?>
                    <h2 class="es-pure-section-title"><?php _e('Location', 'ensemble'); ?></h2>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($location_permalink); ?>" class="es-pure-location-block">
                        <div class="es-pure-location-block-image">
                            <?php if ($location_image): ?>
                                <img src="<?php echo esc_url($location_image); ?>" alt="<?php echo esc_attr($location_name); ?>">
                            <?php else: ?>
                                <div class="es-pure-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="es-pure-location-block-info">
                            <div class="es-pure-location-block-name"><?php echo esc_html($location_name); ?></div>
                            <?php if ($location_address || $location_city): ?>
                            <div class="es-pure-location-block-address">
                                <?php 
                                if ($location_address) echo esc_html($location_address);
                                if ($location_address && $location_city) echo '<br>';
                                if ($location_city) echo esc_html($location_city);
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </section>
                <?php endif; ?>
                
                <?php 
                // Hook: Event Catalog
                if (function_exists('ensemble_event_catalog')) {
                    ensemble_event_catalog($event_id);
                }
                ?>
                
            </main>
            
            <!-- SIDEBAR -->
            <aside class="es-pure-sidebar">
                
                <?php 
                // Main Sponsor Hook - displays main sponsor in sidebar
                do_action('ensemble_main_sponsor_sidebar', $event_id);
                ?>
                
                <!-- Ticket Box -->
                <?php if ($ticket_url || $price): ?>
                <div class="es-pure-sidebar-block es-pure-ticket-box">
                    <?php if ($price): ?>
                    <div class="es-pure-ticket-price"><?php echo esc_html($price); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($price_info): ?>
                    <p class="es-pure-text-secondary es-pure-text-small" style="margin-bottom: 16px;">
                        <?php echo esc_html($price_info); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($ticket_url && !$is_cancelled && !$is_soldout): ?>
                    <a href="<?php echo esc_url($ticket_url); ?>" class="es-pure-btn es-pure-btn-ghost" target="_blank" rel="noopener">
                        <?php echo esc_html($button_text); ?>
                    </a>
                    <?php elseif ($is_soldout): ?>
                    <span class="es-pure-btn es-pure-btn-ghost" style="opacity: 0.5; pointer-events: none;">
                        <?php _e('Sold Out', 'ensemble'); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php 
                // Hook: Ticket Area
                if (function_exists('ensemble_ticket_area')) {
                    ensemble_ticket_area($event_id);
                }
                ?>
                
                <!-- External Link Button -->
                <?php if (!empty($external_url)): ?>
                <div class="es-pure-sidebar-block">
                    <a href="<?php echo esc_url($external_url); ?>" class="es-pure-btn es-pure-btn-ghost" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 8px;">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15 3 21 3 21 9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                        <?php echo esc_html($external_text); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php 
                // Hook: Gallery Area
                if (function_exists('ensemble_gallery_area')) {
                    ensemble_gallery_area($event_id);
                }
                ?>
                
                <!-- Gallery -->
                <?php if (!empty($gallery)): ?>
                <div class="es-pure-sidebar-block" style="padding: 0; border: none;">
                    <h3 class="es-pure-sidebar-title" style="padding: 0 0 12px 0;"><?php _e('Gallery', 'ensemble'); ?></h3>
                    <div class="es-pure-gallery">
                        <?php foreach (array_slice($gallery, 0, 6) as $image): 
                            // Handle both array format and ID format
                            if (is_array($image)) {
                                $img_url = $image['url'] ?? '';
                                if (empty($img_url) && !empty($image['id'])) {
                                    $img_url = wp_get_attachment_image_url($image['id'], 'medium');
                                }
                            } elseif (is_numeric($image)) {
                                // It's an attachment ID
                                $img_url = wp_get_attachment_image_url($image, 'medium');
                            } else {
                                // It's a URL string
                                $img_url = $image;
                            }
                            if ($img_url):
                        ?>
                        <div class="es-pure-gallery-item">
                            <img src="<?php echo esc_url($img_url); ?>" alt="" loading="lazy">
                        </div>
                        <?php 
                            endif;
                        endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php 
                // Hook: After Location (for Maps addon etc.)
                if (function_exists('ensemble_after_location')) {
                    ensemble_after_location($event_id);
                }
                ?>
                
            </aside>
            
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="es-pure-footer">
        <div class="es-pure-content-wrapper">
            
            <?php 
            // Hook: Social Share (vom Addon)
            if (function_exists('ensemble_social_share')) {
                ensemble_social_share($event_id);
            }
            
            // Hook: Related Events
            if (function_exists('ensemble_related_events')) {
                ensemble_related_events($event_id);
            }
            
            // Hook: Event Footer
            if (function_exists('ensemble_event_footer')) {
                ensemble_event_footer($event_id);
            }
            
            // Hook: After Event
            if (function_exists('ensemble_after_event')) {
                ensemble_after_event($event_id, $event);
            }
            do_action('ensemble_after_single_event', $event_id, $event);
            ?>
            
        </div>
    </footer>

</article>

<?php 
// Include Pure mode script (once per page)
if (!defined('ES_PURE_MODE_SCRIPT_LOADED')) {
    define('ES_PURE_MODE_SCRIPT_LOADED', true);
    ?>
    <script id="es-pure-mode-script">
    (function(){var k='ensemble_pure_mode';function g(){try{return localStorage.getItem(k)||'light'}catch(e){return'light'}}function s(m){try{localStorage.setItem(k,m)}catch(e){}}function a(m){document.body.classList.remove('es-mode-light','es-mode-dark');document.body.classList.add('es-mode-'+m);document.querySelectorAll('.es-layout-pure,.es-pure-single-event,.es-pure-single-artist,.es-pure-single-location').forEach(function(el){el.classList.remove('es-mode-light','es-mode-dark');el.classList.add('es-mode-'+m)});document.querySelectorAll('.es-mode-toggle').forEach(function(t){var sun=t.querySelector('.es-icon-sun'),moon=t.querySelector('.es-icon-moon');if(sun&&moon){sun.style.display=m==='dark'?'block':'none';moon.style.display=m==='dark'?'none':'block'}})}function t(){var c=g(),n=c==='dark'?'light':'dark';s(n);a(n)}function c(){var b=document.createElement('button');b.className='es-mode-toggle';b.innerHTML='<svg class="es-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg><svg class="es-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';b.onclick=t;return b}function i(){if(!document.querySelector('.es-layout-pure,.es-pure-single-event,.es-pure-single-artist,.es-pure-single-location'))return;a(g());if(!document.querySelector('.es-mode-toggle'))document.body.appendChild(c())}document.documentElement.classList.add('es-mode-'+g());if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',i);else i();window.togglePureMode=t})();
    </script>
    <?php
}
?>

<?php get_footer(); ?>
