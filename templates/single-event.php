<?php
/**
 * Single Event Template
 * 
 * Robustes Template das alle Event-Daten anzeigt
 * Unterstützt: Status, Timeline, Multivenue, Artist-Details
 *
 * @package Ensemble
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Enqueue frontend CSS
wp_enqueue_style(
    'ensemble-frontend-css',
    ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css',
    array(),
    ENSEMBLE_VERSION
);

// Load dashicons for icons
wp_enqueue_style('dashicons');

// Get event ID
$event_id = get_the_ID();

// ============================================================
// HELPER FUNCTION - Robustes Laden von Feldern
// ============================================================
function es_get_event_field($event_id, $field_name) {
    $value = null;
    
    // 1. Versuche ensemble_get_field (mit Mapping-Support)
    if (function_exists('ensemble_get_field')) {
        $value = ensemble_get_field($field_name, $event_id);
        if (!empty($value)) return $value;
    }
    
    // 2. Versuche ACF get_field
    if (function_exists('get_field')) {
        $value = get_field($field_name, $event_id);
        if (!empty($value)) return $value;
    }
    
    // 3. Versuche direktes post_meta (verschiedene Prefixe)
    $prefixes = array('', 'event_', 'es_', '_');
    foreach ($prefixes as $prefix) {
        $value = get_post_meta($event_id, $prefix . $field_name, true);
        if (!empty($value)) return $value;
    }
    
    return $value;
}

// ============================================================
// DATEN LADEN
// ============================================================

// Basic Fields
$event_date = es_get_event_field($event_id, 'event_date');
if (empty($event_date)) $event_date = es_get_event_field($event_id, 'date');

$event_time = es_get_event_field($event_id, 'event_time');
if (empty($event_time)) $event_time = es_get_event_field($event_id, 'time');

$event_time_end = es_get_event_field($event_id, 'event_time_end');
if (empty($event_time_end)) $event_time_end = es_get_event_field($event_id, 'time_end');

$price = es_get_event_field($event_id, 'event_price');
if (empty($price)) $price = es_get_event_field($event_id, 'price');

$ticket_url = es_get_event_field($event_id, 'event_ticket_url');
if (empty($ticket_url)) $ticket_url = es_get_event_field($event_id, 'ticket_url');

$button_text = es_get_event_field($event_id, 'event_button_text');
if (empty($button_text)) $button_text = es_get_event_field($event_id, 'button_text');
if (empty($button_text)) $button_text = __('Get Tickets', 'ensemble');

$description = es_get_event_field($event_id, 'event_description');
if (empty($description)) $description = es_get_event_field($event_id, 'description');

// Additional Info (directions, entry requirements, etc.)
$additional_info = es_get_event_field($event_id, 'event_additional_info');
if (empty($additional_info)) $additional_info = get_post_meta($event_id, 'event_additional_info', true);

// External Link
$external_url = es_get_event_field($event_id, 'event_external_url');
if (empty($external_url)) $external_url = get_post_meta($event_id, 'event_external_url', true);

$external_text = es_get_event_field($event_id, 'event_external_text');
if (empty($external_text)) $external_text = get_post_meta($event_id, 'event_external_text', true);
if (empty($external_text)) $external_text = __('More Info', 'ensemble');

// Event Status
$event_status = get_post_meta($event_id, '_event_status', true);
if (empty($event_status)) {
    $event_status = get_post_status($event_id) === 'draft' ? 'draft' : 'publish';
}

// Main Venue (Room/Stage name)
$main_venue = es_get_event_field($event_id, 'event_venue');
if (empty($main_venue)) $main_venue = es_get_event_field($event_id, 'venue');

// ============================================================
// LOCATION LADEN
// ============================================================
$location_id = es_get_event_field($event_id, 'event_location');
if (empty($location_id)) $location_id = es_get_event_field($event_id, 'location');

// Wenn Location ein Array ist (ACF Relationship), erste ID nehmen
if (is_array($location_id)) {
    $location_id = !empty($location_id[0]) ? $location_id[0] : null;
    if (is_object($location_id)) $location_id = $location_id->ID;
}
$location_id = intval($location_id);

$location = null;
$location_data = array();

if ($location_id > 0) {
    $location = get_post($location_id);
    if ($location) {
        $location_data = array(
            'id' => $location_id,
            'name' => $location->post_title,
            'url' => get_permalink($location_id),
            'address' => get_post_meta($location_id, 'location_address', true),
            'city' => get_post_meta($location_id, 'location_city', true),
            'country' => get_post_meta($location_id, 'location_country', true),
            'website' => get_post_meta($location_id, 'location_website', true),
            'image' => get_the_post_thumbnail_url($location_id, 'medium'),
            'description' => $location->post_content,
        );
        
        // ACF Fallbacks
        if (function_exists('get_field')) {
            if (empty($location_data['address'])) $location_data['address'] = get_field('address', $location_id);
            if (empty($location_data['city'])) $location_data['city'] = get_field('city', $location_id);
        }
        
        // Add room to display name if selected
        if (!empty($main_venue)) {
            $location_data['room'] = $main_venue;
            $location_data['display_name'] = $location_data['name'] . ' – ' . $main_venue;
        } else {
            $location_data['room'] = '';
            $location_data['display_name'] = $location_data['name'];
        }
    }
}

// ============================================================
// ARTISTS LADEN (mit vollen Details)
// ============================================================
$artist_data = es_get_event_field($event_id, 'event_artist');
if (empty($artist_data)) $artist_data = es_get_event_field($event_id, 'artist');

$artist_ids = array();

// Artist IDs extrahieren
if (!empty($artist_data)) {
    if (is_string($artist_data)) {
        $artist_data = maybe_unserialize($artist_data);
    }
    
    if (is_array($artist_data)) {
        foreach ($artist_data as $item) {
            if (is_object($item) && isset($item->ID)) {
                $artist_ids[] = $item->ID;
            } elseif (is_numeric($item)) {
                $artist_ids[] = intval($item);
            }
        }
    } elseif (is_numeric($artist_data)) {
        $artist_ids[] = intval($artist_data);
    }
}

// Consider Artist Order
$artist_order = get_post_meta($event_id, '_artist_order', true);
if (!empty($artist_order)) {
    $order_array = array_map('intval', explode(',', $artist_order));
    $ordered_ids = array();
    foreach ($order_array as $oid) {
        if (in_array($oid, $artist_ids)) {
            $ordered_ids[] = $oid;
        }
    }
    foreach ($artist_ids as $aid) {
        if (!in_array($aid, $ordered_ids)) {
            $ordered_ids[] = $aid;
        }
    }
    $artist_ids = $ordered_ids;
}

// Artist Times und Venues laden
$artist_times = get_post_meta($event_id, 'artist_times', true);
if (!is_array($artist_times)) $artist_times = array();

$artist_venues = get_post_meta($event_id, 'artist_venues', true);
if (!is_array($artist_venues)) $artist_venues = array();

// DEBUG: Show raw data - aktiviert für Troubleshooting
echo '<!-- DEBUG artist_times: ' . print_r($artist_times, true) . ' -->';
echo '<!-- DEBUG artist_venues: ' . print_r($artist_venues, true) . ' -->';
echo '<!-- DEBUG artist_ids: ' . print_r($artist_ids, true) . ' -->';

// Normalize array keys to handle string/int mismatch from JSON
$artist_times_normalized = array();
foreach ($artist_times as $key => $value) {
    $artist_times_normalized[intval($key)] = $value;
    $artist_times_normalized[strval($key)] = $value;
}

$artist_venues_normalized = array();
foreach ($artist_venues as $key => $value) {
    $artist_venues_normalized[intval($key)] = $value;
    $artist_venues_normalized[strval($key)] = $value;
}

// Load complete artist data
$artists = array();
foreach ($artist_ids as $artist_id) {
    $artist_post = get_post($artist_id);
    if (!$artist_post) continue;
    
    // Check for venue with both int and string key
    $artist_venue = '';
    if (isset($artist_venues_normalized[$artist_id])) {
        $artist_venue = $artist_venues_normalized[$artist_id];
    } elseif (isset($artist_venues_normalized[strval($artist_id)])) {
        $artist_venue = $artist_venues_normalized[strval($artist_id)];
    } elseif (isset($artist_venues_normalized[intval($artist_id)])) {
        $artist_venue = $artist_venues_normalized[intval($artist_id)];
    }
    
    // Check for time with both int and string key
    $artist_time = '';
    if (isset($artist_times_normalized[$artist_id])) {
        $artist_time = $artist_times_normalized[$artist_id];
    } elseif (isset($artist_times_normalized[strval($artist_id)])) {
        $artist_time = $artist_times_normalized[strval($artist_id)];
    } elseif (isset($artist_times_normalized[intval($artist_id)])) {
        $artist_time = $artist_times_normalized[intval($artist_id)];
    }
    
    $artist = array(
        'id' => $artist_id,
        'name' => $artist_post->post_title,
        'url' => get_permalink($artist_id),
        'image' => get_the_post_thumbnail_url($artist_id, 'medium'),
        'thumbnail' => get_the_post_thumbnail_url($artist_id, 'thumbnail'),
        'description' => $artist_post->post_content,
        'excerpt' => $artist_post->post_excerpt,
        'time' => $artist_time,
        'venue' => $artist_venue, // Don't fallback to main_venue yet
    );
    
    // Format time
    if (!empty($artist['time'])) {
        $artist['time_formatted'] = date_i18n(get_option('time_format'), strtotime($artist['time']));
    } else {
        $artist['time_formatted'] = '';
    }
    
    // Additional artist meta
    $artist['website'] = get_post_meta($artist_id, 'artist_website', true);
    $artist['spotify'] = get_post_meta($artist_id, 'artist_spotify', true);
    $artist['soundcloud'] = get_post_meta($artist_id, 'artist_soundcloud', true);
    $artist['instagram'] = get_post_meta($artist_id, 'artist_instagram', true);
    $artist['facebook'] = get_post_meta($artist_id, 'artist_facebook', true);
    
    // Artist Genres
    $artist_genres = get_the_terms($artist_id, 'ensemble_genre');
    $artist['genres'] = array();
    if ($artist_genres && !is_wp_error($artist_genres)) {
        foreach ($artist_genres as $genre) {
            $artist['genres'][] = $genre->name;
        }
    }
    
    $artists[] = $artist;
}

// Check flags
$has_timeline = false;
$has_multivenue = false;
$has_venue_display = false;  // NEW: Show venue header even for single venue
$venues_used = array();

foreach ($artists as $artist) {
    if (!empty($artist['time'])) $has_timeline = true;
    if (!empty($artist['venue'])) {
        $venues_used[$artist['venue']] = true;
    }
}

// Multivenue if we have 2+ different venue names assigned to artists
$has_multivenue = count($venues_used) > 1;

// Show venue display if: main_venue is set OR at least one artist has a venue
$has_venue_display = !empty($main_venue) || count($venues_used) >= 1;

// DEBUG: Show computed flags
echo '<!-- DEBUG has_timeline: ' . ($has_timeline ? 'true' : 'false') . ' -->';
echo '<!-- DEBUG has_multivenue: ' . ($has_multivenue ? 'true' : 'false') . ' -->';
echo '<!-- DEBUG has_venue_display: ' . ($has_venue_display ? 'true' : 'false') . ' -->';
echo '<!-- DEBUG main_venue: ' . $main_venue . ' -->';
echo '<!-- DEBUG venues_used: ' . print_r($venues_used, true) . ' -->';
echo '<!-- DEBUG artists count: ' . count($artists) . ' -->';
foreach ($artists as $a) {
    echo '<!-- DEBUG artist ' . $a['id'] . ' (' . $a['name'] . '): time=' . $a['time'] . ', venue=' . $a['venue'] . ' -->';
}

// ============================================================
// CATEGORIES & GENRES
// ============================================================
$categories = get_the_terms($event_id, 'ensemble_category');
$genres = get_the_terms($event_id, 'ensemble_genre');

// ============================================================
// FORMAT DATES
// ============================================================
$formatted_date = '';
$formatted_time = '';
$formatted_time_end = '';

if ($event_date) {
    $timestamp = strtotime($event_date);
    if ($timestamp) {
        $formatted_date = date_i18n(get_option('date_format'), $timestamp);
    }
}

if ($event_time) {
    $time_ts = strtotime($event_time);
    if ($time_ts) {
        $formatted_time = date_i18n(get_option('time_format'), $time_ts);
    }
}

if ($event_time_end) {
    $time_end_ts = strtotime($event_time_end);
    if ($time_end_ts) {
        $formatted_time_end = date_i18n(get_option('time_format'), $time_end_ts);
    }
}

// Recurring Event Info
$is_recurring = get_post_meta($event_id, '_es_is_recurring', true);

// ============================================================
// GALLERY LADEN
// ============================================================
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

?>

<div class="ensemble-single-event-wrapper">
    
    <?php 
    /**
     * ADDON HOOK: ensemble_before_event
     * Position: Ganz oben, vor allem Content
     * Verwendet von: Countdown-Addon, Announcement-Banner
     */
    if (class_exists('ES_Addon_Manager')) {
        ES_Addon_Manager::do_addon_hook('ensemble_before_event', $event_id, array(
            'event_date' => $event_date,
            'event_time' => $event_time,
            'event_status' => $event_status,
        ));
    }
    ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <article class="ensemble-single-event <?php echo ($event_status === 'cancelled') ? 'es-event-cancelled' : ''; ?>">
        
        <!-- STATUS BANNER -->
        <?php if ($event_status && $event_status !== 'publish'): ?>
        <div class="es-status-banner es-status-<?php echo esc_attr($event_status); ?>">
            <?php 
            switch ($event_status) {
                case 'cancelled':
                    echo '<span class="es-status-icon">✕</span>';
                    echo '<span class="es-status-text">' . __('This event has been cancelled', 'ensemble') . '</span>';
                    break;
                case 'postponed':
                    echo '<span class="es-status-icon">⏸</span>';
                    echo '<span class="es-status-text">' . __('This event has been postponed', 'ensemble') . '</span>';
                    break;
                case 'draft':
                    echo '<span class="es-status-icon">✎</span>';
                    echo '<span class="es-status-text">' . __('Preview - Not yet published', 'ensemble') . '</span>';
                    break;
            }
            ?>
        </div>
        <?php endif; ?>
        
        <!-- HERO IMAGE -->
        <?php if (has_post_thumbnail()): ?>
        <div class="es-event-hero">
            <?php the_post_thumbnail('full'); ?>
            
            <?php if ($event_date): ?>
            <div class="es-hero-date-badge">
                <span class="es-date-day"><?php echo date_i18n('j', strtotime($event_date)); ?></span>
                <span class="es-date-month"><?php echo date_i18n('M', strtotime($event_date)); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- MAIN CONTENT -->
        <div class="es-event-content">
            
            <!-- HEADER -->
            <header class="es-event-header">
                <h1 class="es-event-title <?php echo ($event_status === 'cancelled') ? 'es-cancelled' : ''; ?>">
                    <?php the_title(); ?>
                </h1>
                
                <?php if ($categories && !is_wp_error($categories)): ?>
                <div class="es-event-categories">
                    <?php foreach ($categories as $cat): ?>
                        <span class="es-category-badge"><?php echo esc_html($cat->name); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($genres && !is_wp_error($genres)): ?>
                <div class="es-event-genres">
                    <?php foreach ($genres as $genre): ?>
                        <span class="es-genre-tag"><?php echo esc_html($genre->name); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </header>
            
            <?php 
            /**
             * ADDON HOOK: ensemble_after_title
             * Position: Direkt nach dem Header/Titel
             * Verwendet von: Share-Addon, Rating-Addon
             */
            if (class_exists('ES_Addon_Manager')) {
                ES_Addon_Manager::do_addon_hook('ensemble_after_title', $event_id, array(
                    'title' => get_the_title(),
                    'permalink' => get_permalink($event_id),
                ));
            }
            ?>
            
            <!-- META INFO GRID -->
            <?php if (!function_exists('ensemble_show_section') || ensemble_show_section('meta')): ?>
            <div class="es-meta-grid">
                
                <!-- Date & Time -->
                <?php if ($formatted_date && (!function_exists('ensemble_show_meta') || ensemble_show_meta('date'))): ?>
                <div class="es-meta-card">
                    <div class="es-meta-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="es-meta-info">
                        <span class="es-meta-label"><?php _e('Date & Time', 'ensemble'); ?></span>
                        <span class="es-meta-value"><?php echo esc_html($formatted_date); ?></span>
                        <?php if ($formatted_time && (!function_exists('ensemble_show_meta') || ensemble_show_meta('time'))): ?>
                        <span class="es-meta-secondary">
                            <?php 
                            echo esc_html($formatted_time);
                            if ($formatted_time_end) {
                                echo ' – ' . esc_html($formatted_time_end);
                            }
                            ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Location -->
<?php if (!empty($location_data)): 
    // Only show location name without room/venue suffix
    $loc_display = $location_data['name'];
?>
                <div class="es-meta-card">
                    <div class="es-meta-icon">
                        <span class="dashicons dashicons-location"></span>
                    </div>
                    <div class="es-meta-info">
                        <span class="es-meta-label"><?php _e('Location', 'ensemble'); ?></span>
                        <a href="<?php echo esc_url($location_data['url']); ?>" class="es-meta-value es-meta-link">
                            <?php echo esc_html($loc_display); ?>
                        </a>
                        <?php if ($location_data['address'] || $location_data['city']): ?>
                        <span class="es-meta-secondary">
                            <?php 
                            echo esc_html($location_data['address']);
                            if ($location_data['address'] && $location_data['city']) echo ', ';
                            echo esc_html($location_data['city']);
                            ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Price -->
                <?php if ($price && (!function_exists('ensemble_show_meta') || ensemble_show_meta('price'))): ?>
                <div class="es-meta-card">
                    <div class="es-meta-icon">
                        <span class="dashicons dashicons-tickets-alt"></span>
                    </div>
                    <div class="es-meta-info">
                        <span class="es-meta-label"><?php _e('Price', 'ensemble'); ?></span>
                        <span class="es-meta-value"><?php echo esc_html($price); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Venue/Stage - No longer needed as room is now integrated into location display -->
                
            </div>
            <?php endif; ?>
            
            <?php 
            /**
             * ADDON HOOK: ensemble_ticket_area
             * Position: Im Ticket-/Buchungsbereich
             * Verwendet von: Tickets-Addon, Reservieren-Addon
             */
            if (class_exists('ES_Addon_Manager')) {
                ES_Addon_Manager::do_addon_hook('ensemble_ticket_area', $event_id, array(
                    'price' => $price,
                    'ticket_url' => $ticket_url,
                    'event_status' => $event_status,
                ));
            }
            ?>
            
            <!-- TICKET BUTTON -->
            <?php if ($ticket_url && $event_status !== 'cancelled'): ?>
            <div class="es-ticket-section">
                <a href="<?php echo esc_url($ticket_url); ?>" 
                   class="es-ticket-button" 
                   target="_blank" 
                   rel="noopener noreferrer">
                    <span class="dashicons dashicons-tickets-alt"></span>
                    <?php echo esc_html($button_text); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <!-- EXTERNAL LINK BUTTON -->
            <?php if (!empty($external_url)): ?>
            <div class="es-external-link-section">
                <a href="<?php echo esc_url($external_url); ?>" 
                   class="es-external-link-button" 
                   target="_blank" 
                   rel="noopener noreferrer">
                    <span class="dashicons dashicons-external"></span>
                    <?php echo esc_html($external_text); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <?php 
            // Add-on Hook: After Tickets (Reservations, etc.)
            do_action('ensemble_after_tickets', $event_id);
            ?>
            
            <!-- DESCRIPTION -->
            <?php if ($description || get_the_content()): ?>
            <div class="es-section es-description-section">
                <h2 class="es-section-title">
                    <span class="dashicons dashicons-editor-alignleft"></span>
                    <?php _e('About this Event', 'ensemble'); ?>
                </h2>
                <div class="es-description-content">
                    <?php 
                    if (!empty($description)) {
                        echo wpautop($description);
                    } else {
                        the_content();
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            /**
             * ADDON HOOK: ensemble_after_description
             * Position: Nach der Beschreibung
             * Verwendet von: Speisekarte-Addon, Agenda Download-Addon
             */
            if (class_exists('ES_Addon_Manager')) {
                ES_Addon_Manager::do_addon_hook('ensemble_after_description', $event_id, array(
                    'description' => $description,
                ));
            }
            ?>
            
            <!-- LINEUP / ARTISTS SECTION -->
            <?php if (!empty($artists) && (!function_exists('ensemble_show_section') || ensemble_show_section('artists'))): ?>
            <div class="es-section es-lineup-section">
                <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('artists')): ?>
                <h2 class="es-section-title">
                    <span class="dashicons dashicons-groups"></span>
                    <?php 
                    if ($has_timeline) {
                        _e('Lineup & Schedule', 'ensemble');
                    } else {
                        echo count($artists) > 1 ? __('Artists', 'ensemble') : __('Artist', 'ensemble');
                    }
                    ?>
                </h2>
                <?php endif; ?>
                
                <?php 
                // Group artists by venue if venue display is enabled
                if ($has_venue_display) {
                    $artists_by_venue = array();
                    foreach ($artists as $artist) {
                        // Use artist venue, or fall back to main event venue
                        $venue_name = !empty($artist['venue']) ? $artist['venue'] : (!empty($main_venue) ? $main_venue : __('Main Stage', 'ensemble'));
                        if (!isset($artists_by_venue[$venue_name])) {
                            $artists_by_venue[$venue_name] = array();
                        }
                        $artists_by_venue[$venue_name][] = $artist;
                    }
                } else {
                    // No venue - all artists in one group without label
                    $artists_by_venue = array('' => $artists);
                }
                ?>
                
                <?php if ($has_venue_display): ?>
                <!-- MULTIVENUE VIEW - Artists grouped by Stage/Room -->
                <div class="es-multivenue-lineup">
                    <?php foreach ($artists_by_venue as $venue_name => $venue_artists): ?>
                    <div class="es-venue-group">
                        <h3 class="es-venue-title">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($venue_name); ?>
                            <span class="es-venue-count"><?php echo count($venue_artists); ?> <?php echo count($venue_artists) > 1 ? __('Artists', 'ensemble') : __('Artist', 'ensemble'); ?></span>
                        </h3>
                        
                        <?php if ($has_timeline): ?>
                        <!-- Timeline within venue -->
                        <div class="es-timeline es-timeline-in-venue">
                            <?php foreach ($venue_artists as $artist): 
                                $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                            ?>
                            <div class="es-timeline-item">
                                <div class="es-timeline-time">
                                    <?php if ($artist['time_formatted']): ?>
                                        <span class="es-time-value"><?php echo esc_html($artist['time_formatted']); ?></span>
                                    <?php else: ?>
                                        <span class="es-time-tba"><?php _e('TBA', 'ensemble'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="es-timeline-line">
                                    <div class="es-timeline-dot"></div>
                                </div>
                                <div class="es-timeline-content">
                                    <div class="es-artist-card-horizontal">
                                        <?php if ($artist['image']): ?>
                                        <div class="es-artist-image">
                                            <?php if ($artist_has_link): ?>
                                            <a href="<?php echo esc_url($artist['link_url']); ?>">
                                                <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                            </a>
                                            <?php else: ?>
                                            <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="es-artist-info">
                                            <h4 class="es-artist-name">
                                                <?php if ($artist_has_link): ?>
                                                <a href="<?php echo esc_url($artist['link_url']); ?>">
                                                    <?php echo esc_html($artist['name']); ?>
                                                </a>
                                                <?php else: ?>
                                                <?php echo esc_html($artist['name']); ?>
                                                <?php endif; ?>
                                            </h4>
                                            <?php if (!empty($artist['genres'])): ?>
                                            <div class="es-artist-genres">
                                                <?php echo esc_html(implode(', ', $artist['genres'])); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php else: ?>
                        <!-- Artist cards within venue -->
                        <div class="es-artist-grid es-artist-grid-in-venue">
                            <?php foreach ($venue_artists as $artist): 
                                $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                            ?>
                            <div class="es-artist-card es-artist-card-compact">
                                <?php if ($artist['image']): ?>
                                <div class="es-artist-card-image">
                                    <?php if ($artist_has_link): ?>
                                    <a href="<?php echo esc_url($artist['link_url']); ?>">
                                        <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                    </a>
                                    <?php else: ?>
                                    <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="es-artist-card-content">
                                    <h4 class="es-artist-card-name">
                                        <?php if ($artist_has_link): ?>
                                        <a href="<?php echo esc_url($artist['link_url']); ?>">
                                            <?php echo esc_html($artist['name']); ?>
                                        </a>
                                        <?php else: ?>
                                        <?php echo esc_html($artist['name']); ?>
                                        <?php endif; ?>
                                    </h4>
                                    <?php if (!empty($artist['genres'])): ?>
                                    <div class="es-artist-card-genres">
                                        <?php foreach ($artist['genres'] as $genre): ?>
                                            <span class="es-genre-tag-small"><?php echo esc_html($genre); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php elseif ($has_timeline): ?>
                <!-- SINGLE VENUE - TIMELINE VIEW -->
                <div class="es-timeline">
                    <?php foreach ($artists as $artist): 
                        $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                    ?>
                    <div class="es-timeline-item">
                        <div class="es-timeline-time">
                            <?php if ($artist['time_formatted']): ?>
                                <span class="es-time-value"><?php echo esc_html($artist['time_formatted']); ?></span>
                            <?php else: ?>
                                <span class="es-time-tba"><?php _e('TBA', 'ensemble'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="es-timeline-line">
                            <div class="es-timeline-dot"></div>
                        </div>
                        <div class="es-timeline-content">
                            <div class="es-artist-card-horizontal">
                                <?php if ($artist['image']): ?>
                                <div class="es-artist-image">
                                    <?php if ($artist_has_link): ?>
                                    <a href="<?php echo esc_url($artist['link_url']); ?>">
                                        <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                    </a>
                                    <?php else: ?>
                                    <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="es-artist-info">
                                    <h3 class="es-artist-name">
                                        <?php if ($artist_has_link): ?>
                                        <a href="<?php echo esc_url($artist['link_url']); ?>">
                                            <?php echo esc_html($artist['name']); ?>
                                        </a>
                                        <?php else: ?>
                                        <?php echo esc_html($artist['name']); ?>
                                        <?php endif; ?>
                                    </h3>
                                    <?php if (!empty($artist['genres'])): ?>
                                    <div class="es-artist-genres">
                                        <?php echo esc_html(implode(', ', $artist['genres'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php else: ?>
                <!-- SINGLE VENUE - ARTIST CARDS VIEW -->
                <div class="es-artist-grid">
                    <?php foreach ($artists as $artist): 
                        $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                    ?>
                    <div class="es-artist-card">
                        <?php if ($artist['image']): ?>
                        <div class="es-artist-card-image">
                            <?php if ($artist_has_link): ?>
                            <a href="<?php echo esc_url($artist['link_url']); ?>">
                                <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                            </a>
                            <?php else: ?>
                            <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="es-artist-card-content">
                            <h3 class="es-artist-card-name">
                                <?php if ($artist_has_link): ?>
                                <a href="<?php echo esc_url($artist['link_url']); ?>">
                                    <?php echo esc_html($artist['name']); ?>
                                </a>
                                <?php else: ?>
                                <?php echo esc_html($artist['name']); ?>
                                <?php endif; ?>
                            </h3>
                            
                            <?php if (!empty($artist['genres'])): ?>
                            <div class="es-artist-card-genres">
                                <?php foreach ($artist['genres'] as $genre): ?>
                                    <span class="es-genre-tag-small"><?php echo esc_html($genre); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($artist['excerpt'])): ?>
                            <p class="es-artist-card-excerpt">
                                <?php echo esc_html(wp_trim_words($artist['excerpt'], 20)); ?>
                            </p>
                            <?php elseif (!empty($artist['description'])): ?>
                            <p class="es-artist-card-excerpt">
                                <?php echo esc_html(wp_trim_words(strip_tags($artist['description']), 20)); ?>
                            </p>
                            <?php endif; ?>
                            
                            <!-- Social Links -->
                            <?php 
                            $has_social = !empty($artist['website']) || !empty($artist['spotify']) || 
                                          !empty($artist['soundcloud']) || !empty($artist['instagram']) || 
                                          !empty($artist['facebook']);
                            ?>
                            <?php if ($has_social): ?>
                            <div class="es-artist-social">
                                <?php if ($artist['website']): ?>
                                <a href="<?php echo esc_url($artist['website']); ?>" target="_blank" rel="noopener" title="Website">
                                    <span class="dashicons dashicons-admin-site"></span>
                                </a>
                                <?php endif; ?>
                                <?php if ($artist['spotify']): ?>
                                <a href="<?php echo esc_url($artist['spotify']); ?>" target="_blank" rel="noopener" title="Spotify">
                                    <span class="dashicons dashicons-format-audio"></span>
                                </a>
                                <?php endif; ?>
                                <?php if ($artist['soundcloud']): ?>
                                <a href="<?php echo esc_url($artist['soundcloud']); ?>" target="_blank" rel="noopener" title="SoundCloud">
                                    <span class="dashicons dashicons-cloud"></span>
                                </a>
                                <?php endif; ?>
                                <?php if ($artist['instagram']): ?>
                                <a href="<?php echo esc_url($artist['instagram']); ?>" target="_blank" rel="noopener" title="Instagram">
                                    <span class="dashicons dashicons-instagram"></span>
                                </a>
                                <?php endif; ?>
                                <?php if ($artist['facebook']): ?>
                                <a href="<?php echo esc_url($artist['facebook']); ?>" target="_blank" rel="noopener" title="Facebook">
                                    <span class="dashicons dashicons-facebook"></span>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($artist_has_link): ?>
                            <a href="<?php echo esc_url($artist['link_url']); ?>" class="es-artist-card-link">
                                <?php _e('View Profile', 'ensemble'); ?> →
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- LOCATION DETAILS -->
            <?php if (!empty($location_data) && (!empty($location_data['description']) || !empty($location_data['image'])) && (!function_exists('ensemble_show_section') || ensemble_show_section('location'))): 
                $loc_main_formatted = function_exists('ensemble_format_location_address') 
                    ? ensemble_format_location_address($location_data) 
                    : array('name' => $location_data['name'], 'address_line' => $location_data['city']);
            ?>
            <div class="es-section es-location-section">
                <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('location')): ?>
                <h2 class="es-section-title">
                    <span class="dashicons dashicons-location"></span>
                    <?php _e('Venue', 'ensemble'); ?>
                </h2>
                <?php endif; ?>
                <div class="es-location-card-full">
                    <?php if ($location_data['image']): ?>
                    <div class="es-location-card-image">
                        <a href="<?php echo esc_url($location_data['url']); ?>">
                            <img src="<?php echo esc_url($location_data['image']); ?>" alt="<?php echo esc_attr($loc_main_formatted['name']); ?>">
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="es-location-card-content">
                        <h3 class="es-location-card-name">
                            <a href="<?php echo esc_url($location_data['url']); ?>">
                                <?php echo esc_html($loc_main_formatted['name']); ?>
                            </a>
                        </h3>
                        <?php if (!empty($loc_main_formatted['address_line'])): ?>
                        <p class="es-location-card-address">
                            <?php echo esc_html($loc_main_formatted['address_line']); ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($location_data['description'])): ?>
                        <div class="es-location-card-description">
                            <?php echo wpautop(wp_trim_words($location_data['description'], 50)); ?>
                        </div>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($location_data['url']); ?>" class="es-location-card-link">
                            <?php _e('View Location', 'ensemble'); ?> →
                        </a>
                    </div>
                </div>
                
                <?php 
                /**
                 * ADDON HOOK: ensemble_after_location
                 * Position: Nach der Venue-Card, für Karten etc.
                 * Verwendet von: Maps-Addon, Maps Pro-Addon (Anfahrt)
                 */
                if (class_exists('ES_Addon_Manager')) {
                    ES_Addon_Manager::do_addon_hook('ensemble_after_location', $event_id, $location_data);
                }
                ?>
                
            </div>
            <?php endif; ?>
            
            <!-- RECURRING EVENT INFO -->
            <?php if ($is_recurring): ?>
            <div class="es-info-box es-recurring-info">
                <span class="dashicons dashicons-update"></span>
                <div>
                    <strong><?php _e('Recurring Event', 'ensemble'); ?></strong>
                    <p><?php _e('This event repeats on a regular schedule.', 'ensemble'); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            /**
             * ADDON HOOK: ensemble_gallery_area
             * Position: Galerie-Bereich
             * Verwendet von: Gallery-Addon (überschreibt Standard-Gallery)
             */
            if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::has_hook('ensemble_gallery_area')) {
                ES_Addon_Manager::do_addon_hook('ensemble_gallery_area', $event_id, $gallery);
            } elseif ($has_gallery) {
                // Standard-Gallery (wenn kein Addon aktiv)
                ?>
                <div class="es-section es-gallery-section">
                    <h2 class="es-section-title">
                        <span class="dashicons dashicons-format-gallery"></span>
                        <?php _e('Gallery', 'ensemble'); ?>
                    </h2>
                    <div class="es-gallery-grid">
                        <?php foreach ($gallery as $image): ?>
                        <a href="<?php echo esc_url($image['url']); ?>" 
                           class="es-gallery-item"
                           data-lightbox="event-gallery"
                           data-title="<?php echo esc_attr($image['caption']); ?>">
                            <img src="<?php echo esc_url($image['medium']); ?>" 
                                 alt="<?php echo esc_attr($image['alt']); ?>">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
            }
            ?>
            
            <!-- ADDITIONAL INFORMATION -->
            <?php if (!empty($additional_info)): ?>
            <div class="es-section es-additional-info-section">
                <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('additional_info')): ?>
                <h2 class="es-section-title">
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php _e('Additional Information', 'ensemble'); ?>
                </h2>
                <?php endif; ?>
                <div class="es-additional-info-content">
                    <?php echo wpautop($additional_info); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            /**
             * ADDON HOOK: ensemble_related_events
             * Position: Vor dem Ende, für verwandte Events
             * Verwendet von: Related Events-Addon
             */
            if (class_exists('ES_Addon_Manager')) {
                ES_Addon_Manager::do_addon_hook('ensemble_related_events', $event_id, array(
                    'categories' => $categories,
                    'location_id' => $location_data['id'] ?? 0,
                    'artist_ids' => array_column($artists, 'id'),
                ));
            }
            ?>
            
        </div>
        
        <?php 
        /**
         * ADDON HOOK: ensemble_after_event
         * Position: Ganz am Ende des Event-Containers
         * Verwendet von: Share-Addon (Sticky), Comments-Addon
         */
        if (class_exists('ES_Addon_Manager')) {
            ES_Addon_Manager::do_addon_hook('ensemble_after_event', $event_id, array(
                'title' => get_the_title(),
                'permalink' => get_permalink($event_id),
                'featured_image' => get_the_post_thumbnail_url($event_id, 'large'),
            ));
        }
        ?>
        
    </article>
    
    <?php endwhile; endif; ?>
    
</div>

<?php get_footer(); ?>
