<?php
/**
 * ============================================================================
 * ENSEMBLE - BLANK TEMPLATE
 * ============================================================================
 * 
 * Copy this template and build your own HTML around it.
 * All available variables are documented below and ready to use.
 * 
 * Storage location for custom templates:
 * - /wp-content/themes/YOUR-THEME/ensemble/single-event.php
 * - Or create a new layout set under: /templates/layouts/YOUR-SET/
 * 
 * @package Ensemble
 * @version 3.0
 * ============================================================================
 * 
 * ADDON HOOKS - Available positions for addons:
 * ===============================================
 * 
 * ensemble_before_event      - At the top (Countdown, Banner)
 * ensemble_after_title       - After title (Share, Rating)
 * ensemble_after_location    - After location (Maps, Directions)
 * ensemble_ticket_area       - Ticket area (Tickets, Reservations)
 * ensemble_after_description - After description (Menu, Agenda)
 * ensemble_gallery_area      - Gallery area (Gallery-Addon)
 * ensemble_related_events    - Related events (Related Events)
 * ensemble_after_event       - At the bottom (Share Sticky, Comments)
 * 
 * Usage in custom addons:
 * ES_Addon_Manager::register_hook('ensemble_after_title', 'my-addon', 'callback_fn', 10);
 * 
 * ============================================================================
 */

if (!defined('ABSPATH')) exit;

get_header();

// Load Ensemble Styles (optional - you can also use your own)
wp_enqueue_style('ensemble-frontend-css', ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('dashicons');

$event_id = get_the_ID();

/**
 * ============================================================================
 * HELPER FUNCTION
 * ============================================================================
 * This function loads fields robustly - tries different storage locations
 */
if (!function_exists('es_get_field')) {
    function es_get_field($event_id, $field) {
        // 1. Ensemble Helper (mit Field-Mapping)
        if (function_exists('ensemble_get_field')) {
            $val = ensemble_get_field($field, $event_id);
            if (!empty($val)) return $val;
        }
        // 2. ACF
        if (function_exists('get_field')) {
            $val = get_field($field, $event_id);
            if (!empty($val)) return $val;
        }
        // 3. Post Meta (verschiedene Prefixe)
        foreach (array('', 'event_', 'es_', '_') as $prefix) {
            $val = get_post_meta($event_id, $prefix . $field, true);
            if (!empty($val)) return $val;
        }
        return null;
    }
}


/**
 * ============================================================================
 * AVAILABLE VARIABLES - OVERVIEW
 * ============================================================================
 * 
 * BASIC EVENT DATA:
 * -----------------
 * $event_id          - Post ID of the event
 * $event_title       - Event title
 * $event_date        - Date (Y-m-d format, e.g. "2025-12-31")
 * $event_time        - Start time (H:i format, e.g. "22:00")
 * $event_time_end    - End time
 * $price             - Price (free text, e.g. "15€" or "Free")
 * $ticket_url        - Link to ticket shop
 * $button_text       - Button text (default: "Get Tickets")
 * $description       - Event description
 * $event_status      - Status: "publish", "cancelled", "postponed", "draft"
 * $main_venue        - Room/Stage name (for Multivenue)
 * 
 * FORMATTED DATA:
 * ------------------
 * $formatted_date    - Date in WordPress format (e.g. "December 31, 2025")
 * $formatted_time    - Time in WordPress format (e.g. "22:00")
 * $formatted_time_end - End time formatted
 * 
 * FEATURED IMAGE:
 * ---------------
 * has_post_thumbnail()           - Checks if image exists
 * the_post_thumbnail('large')    - Outputs image
 * get_the_post_thumbnail_url()   - URL of the image
 * 
 * LOCATION (Array):
 * -----------------
 * $location['id']           - Location Post ID
 * $location['name']         - Location Name
 * $location['url']          - Permalink to location page (always internal)
 * $location['link_url']     - URL to use for linking (respects settings, may be external website)
 * $location['link_enabled'] - Boolean: should location be linked?
 * $location['link_external']- Boolean: is link pointing to external website?
 * $location['link_new_tab'] - Boolean: should external links open in new tab?
 * $location['address']      - Street & house number
 * $location['city']         - City
 * $location['country']      - Country
 * $location['website']      - Website URL
 * $location['image']        - Featured Image URL
 * $location['description']  - Description
 * 
 * ARTISTS (Array of Arrays):
 * ---------------------------
 * Loop: foreach ($artists as $artist)
 * 
 * $artist['id']             - Artist Post ID
 * $artist['name']           - Artist Name
 * $artist['url']            - Permalink to artist page (always internal)
 * $artist['link_url']       - URL to use for linking (null if linking disabled)
 * $artist['link_enabled']   - Boolean: should artist be linked?
 * $artist['image']          - Featured Image URL (medium)
 * $artist['thumbnail']      - Thumbnail URL
 * $artist['description']    - Full description (HTML)
 * $artist['excerpt']        - Short description
 * $artist['time']           - Performance time (if Timeline active)
 * $artist['time_formatted'] - Performance time formatted
 * $artist['venue']          - Stage/Room (if Multivenue active)
 * $artist['genres']         - Array of genre names
 * $artist['website']        - Website URL
 * $artist['spotify']        - Spotify URL
 * $artist['soundcloud']     - SoundCloud URL
 * $artist['instagram']      - Instagram URL
 * $artist['facebook']       - Facebook URL
 * 
 * TAXONOMIES:
 * -----------
 * $categories - Array of WP_Term objects (ensemble_category)
 * $genres     - Array of WP_Term objects (ensemble_genre)
 * 
 * GALLERY (for Gallery-Addon):
 * ----------------------------
 * $gallery - Array of gallery images
 * Loop: foreach ($gallery as $image)
 * 
 * $image['id']        - Attachment ID
 * $image['url']       - Full image URL
 * $image['thumbnail'] - Thumbnail URL
 * $image['medium']    - Medium URL
 * $image['large']     - Large URL
 * $image['alt']       - Alt text
 * $image['caption']   - Image caption
 * 
 * FLAGS:
 * ------
 * $has_timeline    - true if artist times exist
 * $has_multivenue  - true if multiple venues/rooms
 * $is_recurring    - true if recurring event
 * $has_gallery     - true if gallery images exist
 * 
 * ============================================================================
 */


// ============================================================================
// LOAD DATA - Copy this block to your template
// ============================================================================

// Basic Fields
$event_title    = get_the_title();
$event_date     = es_get_field($event_id, 'event_date');
$event_time     = es_get_field($event_id, 'event_time');
$event_time_end = es_get_field($event_id, 'event_time_end');
$price          = es_get_field($event_id, 'event_price');
$ticket_url     = es_get_field($event_id, 'event_ticket_url');
$button_text    = es_get_field($event_id, 'event_button_text') ?: __('Get Tickets', 'ensemble');
$description    = es_get_field($event_id, 'event_description');
$main_venue     = es_get_field($event_id, 'event_venue');

// Event Status
$event_status = get_post_meta($event_id, '_event_status', true);
if (empty($event_status)) {
    $event_status = (get_post_status() === 'draft') ? 'draft' : 'publish';
}

// Formatierte Daten
$formatted_date     = $event_date ? date_i18n(get_option('date_format'), strtotime($event_date)) : '';
$formatted_time     = $event_time ? date_i18n(get_option('time_format'), strtotime($event_time)) : '';
$formatted_time_end = $event_time_end ? date_i18n(get_option('time_format'), strtotime($event_time_end)) : '';

// Location laden
$location_id = es_get_field($event_id, 'event_location');
if (is_array($location_id)) $location_id = $location_id[0] ?? null;
if (is_object($location_id)) $location_id = $location_id->ID;
$location_id = intval($location_id);

$location = array();
if ($location_id > 0 && ($loc_post = get_post($location_id))) {
    $location = array(
        'id'          => $location_id,
        'name'        => $loc_post->post_title,
        'url'         => get_permalink($location_id),
        'address'     => get_post_meta($location_id, 'location_address', true),
        'city'        => get_post_meta($location_id, 'location_city', true),
        'country'     => get_post_meta($location_id, 'location_country', true),
        'website'     => get_post_meta($location_id, 'location_website', true),
        'image'       => get_the_post_thumbnail_url($location_id, 'medium'),
        'description' => $loc_post->post_content,
    );
}

// Artists laden
$artist_data = es_get_field($event_id, 'event_artist');
$artist_ids = array();

if (!empty($artist_data)) {
    if (is_string($artist_data)) $artist_data = maybe_unserialize($artist_data);
    if (is_array($artist_data)) {
        foreach ($artist_data as $item) {
            if (is_object($item)) $artist_ids[] = $item->ID;
            elseif (is_numeric($item)) $artist_ids[] = intval($item);
        }
    } elseif (is_numeric($artist_data)) {
        $artist_ids[] = intval($artist_data);
    }
}

// Artist Order
$artist_order = get_post_meta($event_id, '_artist_order', true);
if (!empty($artist_order)) {
    $order_arr = array_map('intval', explode(',', $artist_order));
    $sorted = array();
    foreach ($order_arr as $id) if (in_array($id, $artist_ids)) $sorted[] = $id;
    foreach ($artist_ids as $id) if (!in_array($id, $sorted)) $sorted[] = $id;
    $artist_ids = $sorted;
}

// Artist Times & Venues
$artist_times  = get_post_meta($event_id, 'artist_times', true) ?: array();
$artist_venues = get_post_meta($event_id, 'artist_venues', true) ?: array();

// Artists mit allen Daten
$artists = array();
foreach ($artist_ids as $aid) {
    $artist_post = get_post($aid);
    if (!$artist_post) continue;
    
    $artists[] = array(
        'id'             => $aid,
        'name'           => $artist_post->post_title,
        'url'            => get_permalink($aid),
        'image'          => get_the_post_thumbnail_url($aid, 'medium'),
        'thumbnail'      => get_the_post_thumbnail_url($aid, 'thumbnail'),
        'description'    => $artist_post->post_content,
        'excerpt'        => $artist_post->post_excerpt,
        'time'           => $artist_times[$aid] ?? '',
        'time_formatted' => isset($artist_times[$aid]) ? date_i18n(get_option('time_format'), strtotime($artist_times[$aid])) : '',
        'venue'          => $artist_venues[$aid] ?? $main_venue,
        'genres'         => array_map(function($t) { return $t->name; }, get_the_terms($aid, 'ensemble_genre') ?: array()),
        'website'        => get_post_meta($aid, 'artist_website', true),
        'spotify'        => get_post_meta($aid, 'artist_spotify', true),
        'soundcloud'     => get_post_meta($aid, 'artist_soundcloud', true),
        'instagram'      => get_post_meta($aid, 'artist_instagram', true),
        'facebook'       => get_post_meta($aid, 'artist_facebook', true),
    );
}

// Flags
$has_timeline   = !empty(array_filter(array_column($artists, 'time')));
$has_multivenue = count(array_unique(array_filter(array_column($artists, 'venue')))) > 1;
$is_recurring   = (bool) get_post_meta($event_id, '_es_is_recurring', true);

// Taxonomien
$categories = get_the_terms($event_id, 'ensemble_category') ?: array();
$genres     = get_the_terms($event_id, 'ensemble_genre') ?: array();

// Gallery laden
$gallery_ids = get_post_meta($event_id, '_event_gallery', true);
$gallery = array();
if (!empty($gallery_ids) && is_array($gallery_ids)) {
    foreach ($gallery_ids as $attachment_id) {
        $attachment = get_post($attachment_id);
        if (!$attachment) continue;
        
        $gallery[] = array(
            'id'        => $attachment_id,
            'url'       => wp_get_attachment_url($attachment_id),
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
            'medium'    => wp_get_attachment_image_url($attachment_id, 'medium'),
            'large'     => wp_get_attachment_image_url($attachment_id, 'large'),
            'alt'       => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'caption'   => $attachment->post_excerpt,
        );
    }
}
$has_gallery = !empty($gallery);


// ============================================================================
// AB HIER DEIN HTML
// ============================================================================
?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<!-- 
================================================================================
BEISPIEL-STRUKTUR - Ersetze dies mit deinem eigenen HTML
================================================================================
-->

<div class="mein-event-container">
    
    <?php 
    /**
     * ================================================================
     * ADDON HOOK: ensemble_before_event
     * Position: At the top, before all content
     * Used by: Countdown-Addon, Announcement-Banner
     * ================================================================
     */
    ES_Addon_Manager::do_addon_hook('ensemble_before_event', $event_id, array(
        'event_date' => $event_date,
        'event_time' => $event_time,
        'event_status' => $event_status,
    ));
    ?>
    
    <!-- STATUS (wenn abgesagt/verschoben) -->
    <?php if ($event_status === 'cancelled'): ?>
        <div class="mein-status-banner cancelled">⚠️ This event has been cancelled</div>
    <?php elseif ($event_status === 'postponed'): ?>
        <div class="mein-status-banner postponed">⏸ This event has been postponed</div>
    <?php endif; ?>
    
    <!-- FEATURED IMAGE -->
    <?php if (has_post_thumbnail()): ?>
        <div class="mein-event-bild">
            <?php the_post_thumbnail('full'); ?>
        </div>
    <?php endif; ?>
    
    <!-- TITEL -->
    <h1 class="mein-event-titel"><?php echo esc_html($event_title); ?></h1>
    
    <?php 
    /**
     * ================================================================
     * ADDON HOOK: ensemble_after_title
     * Position: Directly after the title
     * Used by: Share-Addon, Rating-Addon
     * ================================================================
     */
    ES_Addon_Manager::do_addon_hook('ensemble_after_title', $event_id, array(
        'title' => $event_title,
        'permalink' => get_permalink($event_id),
    ));
    ?>
    
    <!-- KATEGORIEN -->
    <?php if (!empty($categories)): ?>
        <div class="mein-kategorien">
            <?php foreach ($categories as $cat): ?>
                <span class="kategorie"><?php echo esc_html($cat->name); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- GENRES -->
    <?php if (!empty($genres)): ?>
        <div class="mein-genres">
            <?php foreach ($genres as $genre): ?>
                <span class="genre"><?php echo esc_html($genre->name); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- DATUM & ZEIT -->
    <?php if ($formatted_date): ?>
        <div class="mein-datum">
            <strong>Date:</strong> <?php echo esc_html($formatted_date); ?>
            <?php if ($formatted_time): ?>
                | <strong>Time:</strong> <?php echo esc_html($formatted_time); ?>
                <?php if ($formatted_time_end): ?>
                    - <?php echo esc_html($formatted_time_end); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- LOCATION -->
    <?php if (!empty($location)): ?>
        <div class="mein-location">
            <strong>Location:</strong> 
            <?php if (!empty($location['link_enabled']) && !empty($location['link_url'])): ?>
                <a href="<?php echo esc_url($location['link_url']); ?>"
                   <?php if (!empty($location['link_external']) && !empty($location['link_new_tab'])): ?>
                       target="_blank" rel="noopener noreferrer"
                   <?php endif; ?>>
                    <?php echo esc_html($location['name']); ?>
                </a>
            <?php else: ?>
                <?php echo esc_html($location['name']); ?>
            <?php endif; ?>
            <?php if ($location['address'] || $location['city']): ?>
                <br>
                <?php echo esc_html($location['address']); ?>
                <?php if ($location['city']): ?>, <?php echo esc_html($location['city']); ?><?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php 
        /**
         * ================================================================
         * ADDON HOOK: ensemble_after_location
         * Position: Directly after location info
         * Used by: Maps-Addon, Maps Pro-Addon (Directions)
         * ================================================================
         */
        ES_Addon_Manager::do_addon_hook('ensemble_after_location', $event_id, $location);
        ?>
    <?php endif; ?>
    
    <!-- ROOM/STAGE (if only one) -->
    <?php if ($main_venue && !$has_multivenue): ?>
        <div class="mein-venue">
            <strong>Stage:</strong> <?php echo esc_html($main_venue); ?>
        </div>
    <?php endif; ?>
    
    <!-- PREIS -->
    <?php if ($price): ?>
        <div class="mein-preis">
            <strong>Eintritt:</strong> <?php echo esc_html($price); ?>
        </div>
    <?php endif; ?>
    
    <?php 
    /**
     * ================================================================
     * ADDON HOOK: ensemble_ticket_area
     * Position: In the ticket/booking area
     * Used by: Tickets-Addon, Reservations-Addon
     * ================================================================
     */
    ES_Addon_Manager::do_addon_hook('ensemble_ticket_area', $event_id, array(
        'price' => $price,
        'ticket_url' => $ticket_url,
        'event_status' => $event_status,
    ));
    ?>
    
    <!-- TICKET BUTTON -->
    <?php if ($ticket_url && $event_status !== 'cancelled'): ?>
        <div class="mein-ticket-button">
            <a href="<?php echo esc_url($ticket_url); ?>" target="_blank" rel="noopener">
                <?php echo esc_html($button_text); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <!-- BESCHREIBUNG -->
    <?php if ($description): ?>
        <div class="mein-beschreibung">
            <h2>About the Event</h2>
            <?php echo wpautop($description); ?>
        </div>
    <?php elseif (get_the_content()): ?>
        <div class="mein-beschreibung">
            <h2>About the Event</h2>
            <?php the_content(); ?>
        </div>
    <?php endif; ?>
    
    <?php 
    /**
     * ================================================================
     * ADDON HOOK: ensemble_after_description
     * Position: After the description
     * Used by: Menu-Addon, Agenda Download-Addon
     * ================================================================
     */
    ES_Addon_Manager::do_addon_hook('ensemble_after_description', $event_id, array(
        'description' => $description,
    ));
    ?>
    
    
    <!-- ================================================================
         ARTISTS SECTION
         ================================================================ -->
    <?php if (!empty($artists)): ?>
        <div class="mein-artists">
            <h2>
                <?php if ($has_timeline): ?>
                    Lineup & Timetable
                <?php else: ?>
                    <?php echo count($artists) > 1 ? 'Artists' : 'Artist'; ?>
                <?php endif; ?>
            </h2>
            
            <?php foreach ($artists as $artist): ?>
                <div class="mein-artist-card">
                    
                    <!-- Artist Bild -->
                    <?php if ($artist['image']): ?>
                        <div class="artist-bild">
                            <?php if (!empty($artist['link_enabled']) && !empty($artist['link_url'])): ?>
                                <a href="<?php echo esc_url($artist['link_url']); ?>">
                                    <img src="<?php echo esc_url($artist['image']); ?>" 
                                         alt="<?php echo esc_attr($artist['name']); ?>">
                                </a>
                            <?php else: ?>
                                <img src="<?php echo esc_url($artist['image']); ?>" 
                                     alt="<?php echo esc_attr($artist['name']); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="artist-info">
                        <!-- Artist Name -->
                        <h3 class="artist-name">
                            <?php if (!empty($artist['link_enabled']) && !empty($artist['link_url'])): ?>
                                <a href="<?php echo esc_url($artist['link_url']); ?>">
                                    <?php echo esc_html($artist['name']); ?>
                                </a>
                            <?php else: ?>
                                <?php echo esc_html($artist['name']); ?>
                            <?php endif; ?>
                        </h3>
                        
                        <!-- Auftrittszeit (wenn Timeline) -->
                        <?php if ($has_timeline && $artist['time_formatted']): ?>
                            <div class="artist-zeit">
                                🕐 <?php echo esc_html($artist['time_formatted']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Stage (if Multivenue) -->
                        <?php if ($has_multivenue && $artist['venue']): ?>
                            <div class="artist-venue">
                                📍 <?php echo esc_html($artist['venue']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Genres -->
                        <?php if (!empty($artist['genres'])): ?>
                            <div class="artist-genres">
                                <?php echo esc_html(implode(', ', $artist['genres'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Bio/Excerpt -->
                        <?php if ($artist['excerpt']): ?>
                            <p class="artist-bio">
                                <?php echo esc_html($artist['excerpt']); ?>
                            </p>
                        <?php elseif ($artist['description']): ?>
                            <p class="artist-bio">
                                <?php echo esc_html(wp_trim_words(strip_tags($artist['description']), 30)); ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Social Links -->
                        <div class="artist-social">
                            <?php if ($artist['website']): ?>
                                <a href="<?php echo esc_url($artist['website']); ?>" target="_blank">🌐 Website</a>
                            <?php endif; ?>
                            <?php if ($artist['spotify']): ?>
                                <a href="<?php echo esc_url($artist['spotify']); ?>" target="_blank">🎵 Spotify</a>
                            <?php endif; ?>
                            <?php if ($artist['soundcloud']): ?>
                                <a href="<?php echo esc_url($artist['soundcloud']); ?>" target="_blank">☁️ SoundCloud</a>
                            <?php endif; ?>
                            <?php if ($artist['instagram']): ?>
                                <a href="<?php echo esc_url($artist['instagram']); ?>" target="_blank">📸 Instagram</a>
                            <?php endif; ?>
                            <?php if ($artist['facebook']): ?>
                                <a href="<?php echo esc_url($artist['facebook']); ?>" target="_blank">👥 Facebook</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($artist['link_enabled']) && !empty($artist['link_url'])): ?>
                        <a href="<?php echo esc_url($artist['link_url']); ?>" class="artist-link">
                            Zum Profil →
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    
    <!-- ================================================================
         LOCATION DETAILS (optional)
         ================================================================ -->
    <?php if (!empty($location) && $location['description']): ?>
        <div class="mein-location-details">
            <h2>About the Venue</h2>
            
            <?php if ($location['image']): ?>
                <img src="<?php echo esc_url($location['image']); ?>" 
                     alt="<?php echo esc_attr($location['name']); ?>">
            <?php endif; ?>
            
            <h3><?php echo esc_html($location['name']); ?></h3>
            
            <?php if ($location['address']): ?>
                <p><?php echo esc_html($location['address']); ?>, <?php echo esc_html($location['city']); ?></p>
            <?php endif; ?>
            
            <div class="location-beschreibung">
                <?php echo wpautop(wp_trim_words($location['description'], 50)); ?>
            </div>
            
            <?php if (!empty($location['link_enabled']) && !empty($location['link_url'])): ?>
            <a href="<?php echo esc_url($location['link_url']); ?>"<?php if (!empty($location['link_external']) && !empty($location['link_new_tab'])): ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>>Mehr zur Location →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    
    <!-- ================================================================
         RECURRING INFO
         ================================================================ -->
    <?php if ($is_recurring): ?>
        <div class="mein-recurring-info">
            🔄 <strong>Recurring Event</strong>
            <p>This event takes place regularly.</p>
        </div>
    <?php endif; ?>
    
    
    <?php 
    /**
     * ================================================================
     * ADDON HOOK: ensemble_gallery_area
     * Position: Gallery area
     * Used by: Gallery-Addon (overrides default gallery)
     * ================================================================
     */
    if (ES_Addon_Manager::has_hook('ensemble_gallery_area')) {
        ES_Addon_Manager::do_addon_hook('ensemble_gallery_area', $event_id, $gallery);
    } else {
        // Default gallery (if no addon active)
        if ($has_gallery): ?>
            <!-- ================================================================
                 GALLERY (Default - overridden by Gallery-Addon)
                 ================================================================ -->
            <div class="mein-gallery">
                <h2>Gallery</h2>
                <div class="gallery-grid">
                    <?php foreach ($gallery as $image): ?>
                        <a href="<?php echo esc_url($image['url']); ?>" 
                           class="gallery-item"
                           data-lightbox="event-gallery">
                            <img src="<?php echo esc_url($image['medium']); ?>" 
                                 alt="<?php echo esc_attr($image['alt']); ?>">
                            <?php if ($image['caption']): ?>
                                <span class="gallery-caption"><?php echo esc_html($image['caption']); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif;
    }
    ?>
    
    <?php 
    /**
     * ================================================================
     * ADDON HOOK: ensemble_related_events
     * Position: Before the end, for related events
     * Used by: Related Events-Addon
     * ================================================================
     */
    ES_Addon_Manager::do_addon_hook('ensemble_related_events', $event_id, array(
        'categories' => $categories,
        'location_id' => $location['id'] ?? 0,
        'artist_ids' => array_column($artists, 'id'),
    ));
    ?>
    
    <?php 
    /**
     * ================================================================
     * ADDON HOOK: ensemble_after_event
     * Position: At the very end of the event container
     * Used by: Share-Addon (Sticky), Comments-Addon
     * ================================================================
     */
    ES_Addon_Manager::do_addon_hook('ensemble_after_event', $event_id, array(
        'title' => $event_title,
        'permalink' => get_permalink($event_id),
        'featured_image' => get_the_post_thumbnail_url($event_id, 'large'),
    ));
    ?>
    
</div>

<?php endwhile; endif; ?>

<?php get_footer(); ?>
