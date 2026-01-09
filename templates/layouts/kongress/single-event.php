<?php
/**
 * Template: Kongress Single Event
 * 
 * Professional conference/congress event page
 * Features: Hero, Stats, Agenda/Timeline, Speaker Grid, Catalog
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Load event data
$event_id = get_the_ID();
$event = function_exists('es_load_event_data') ? es_load_event_data($event_id) : array();

// Basic data
$title = !empty($event['title']) ? $event['title'] : get_the_title();
$description = !empty($event['description']) ? $event['description'] : '';
$content = !empty($description) ? $description : get_the_content(); // Use wizard description first
$excerpt = !empty($event['excerpt']) ? $event['excerpt'] : get_the_excerpt();
$featured_image = get_the_post_thumbnail_url($event_id, 'full');
$permalink = get_permalink();

// Date & Time
$start_date = !empty($event['start_date']) ? $event['start_date'] : '';
$end_date = !empty($event['end_date']) ? $event['end_date'] : '';
$start_time = !empty($event['start_time']) ? $event['start_time'] : '';
$end_time = !empty($event['end_time']) ? $event['end_time'] : '';
$formatted_date = !empty($event['formatted_date']) ? $event['formatted_date'] : '';

// Format date if not provided
if (empty($formatted_date) && $start_date) {
    $timestamp = strtotime($start_date);
    $formatted_date = date_i18n('j. F Y', $timestamp);
    if ($end_date && $end_date !== $start_date) {
        $end_timestamp = strtotime($end_date);
        $formatted_date = date_i18n('j.', $timestamp) . ' - ' . date_i18n('j. F Y', $end_timestamp);
    }
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
$external_text = !empty($event['external_text']) ? $event['external_text'] : __('Learn More', 'ensemble');

// Status
$status = !empty($event['status']) ? $event['status'] : '';
$is_cancelled = $status === 'cancelled';
$is_soldout = $status === 'soldout';

// Artists/Speakers
$artists = !empty($event['artists']) ? $event['artists'] : array();

// Get artist times for agenda
$artist_times = get_post_meta($event_id, 'artist_times', true);
if (!is_array($artist_times)) $artist_times = array();

$artist_venues = get_post_meta($event_id, 'artist_venues', true);
if (!is_array($artist_venues)) $artist_venues = array();

// Get agenda breaks
$agenda_breaks = get_post_meta($event_id, '_agenda_breaks', true);
if (!is_array($agenda_breaks)) $agenda_breaks = array();

// Get merged agenda from artists + breaks + session titles
// This combines: selected artists with times, breaks, and custom session titles
$agenda_items = function_exists('ensemble_get_merged_agenda') 
    ? ensemble_get_merged_agenda($event_id) 
    : array();

// Categories
$categories = get_the_terms($event_id, 'ensemble_category');

// Child Events (for multi-day)
$child_events = array();
$has_children = !empty($event['has_children']);
if ($has_children && !empty($event['child_event_ids'])) {
    foreach ($event['child_event_ids'] as $child_id) {
        $child_data = function_exists('es_load_event_data') ? es_load_event_data($child_id) : array();
        if ($child_data) {
            $child_events[] = $child_data;
        }
    }
}

// Stats
$speaker_count = count($artists);
// Count only actual sessions (type 'session'), not breaks (type 'break')
$session_count = 0;
foreach ($agenda_items as $item) {
    $type = $item['type'] ?? 'session';
    if ($type !== 'break') {
        $session_count++;
    }
}
// Duration in days
$duration_days = 1;
if ($start_date && $end_date && $end_date !== $start_date) {
    $duration_days = floor((strtotime($end_date) - strtotime($start_date)) / 86400) + 1;
}

// Badge
$badge_label = !empty($event['badge_label']) ? $event['badge_label'] : '';
?>

<article class="es-kongress-single es-layout-kongress" id="es-event-<?php echo esc_attr($event_id); ?>">

    <?php 
    // Hook: Before Event
    do_action('ensemble_before_single_event', $event_id, $event);
    ?>

    <!-- HERO SECTION -->
    <section class="es-kongress-hero">
        <?php if ($featured_image): ?>
        <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>" class="es-kongress-hero-bg">
        <?php endif; ?>
        <div class="es-kongress-hero-overlay"></div>
        
        <div class="es-kongress-hero-content">
            <!-- Meta Info -->
            <div class="es-kongress-hero-meta">
                <?php if ($formatted_date): ?>
                <div class="es-kongress-hero-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span><?php echo esc_html($formatted_date); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($location_name): ?>
                <div class="es-kongress-hero-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span><?php echo esc_html($location_name); ?><?php if ($location_city) echo ', ' . esc_html($location_city); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Badge -->
            <?php if ($badge_label): ?>
            <span class="es-kongress-hero-badge"><?php echo esc_html($badge_label); ?></span>
            <?php endif; ?>
            
            <!-- Title -->
            <h1 class="es-kongress-hero-title"><?php echo esc_html($title); ?></h1>
            
            <!-- Subtitle/Excerpt -->
            <?php if ($excerpt): ?>
            <p class="es-kongress-hero-subtitle"><?php echo esc_html($excerpt); ?></p>
            <?php endif; ?>
            
            <!-- Status Badge -->
            <?php if ($is_cancelled): ?>
            <span class="es-kongress-hero-badge" style="background: var(--ensemble-status-cancelled);">
                <?php _e('Cancelled', 'ensemble'); ?>
            </span>
            <?php elseif ($is_soldout): ?>
            <span class="es-kongress-hero-badge" style="background: var(--ensemble-status-soldout);">
                <?php _e('Sold Out', 'ensemble'); ?>
            </span>
            <?php endif; ?>
        </div>
    </section>

    <!-- STATS BAR -->
    <?php if ($speaker_count > 0 || $duration_days > 1): ?>
    <section class="es-kongress-stats">
        <?php if ($duration_days > 1): ?>
        <div class="es-kongress-stat es-kongress-animate">
            <div class="es-kongress-stat-number" data-count="<?php echo esc_attr($duration_days); ?>">0</div>
            <div class="es-kongress-stat-label">Days</div>
        </div>
        <?php endif; ?>
        
        <?php if ($speaker_count > 0): ?>
        <div class="es-kongress-stat es-kongress-animate es-kongress-animate-delay-1">
            <div class="es-kongress-stat-number" data-count="<?php echo esc_attr($speaker_count); ?>">0</div>
            <div class="es-kongress-stat-label">Speakers</div>
        </div>
        <?php endif; ?>
        
        <?php if ($session_count > 0): ?>
        <div class="es-kongress-stat es-kongress-animate es-kongress-animate-delay-2">
            <div class="es-kongress-stat-number" data-count="<?php echo esc_attr($session_count); ?>">0</div>
            <div class="es-kongress-stat-label">Sessions</div>
        </div>
        <?php endif; ?>
        
        <?php 
        // Allow addons to add stats
        do_action('ensemble_kongress_stats', $event_id, $event); 
        ?>
    </section>
    <?php endif; ?>

    <!-- DESCRIPTION SECTION (above tabs) -->
    <?php if ($content): ?>
    <section class="es-kongress-description-section">
        <div class="es-kongress-content-wrapper">
            <div class="es-kongress-description-content">
                <?php echo wp_kses_post($content); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- MAIN CONTENT -->
    <div class="es-kongress-content-wrapper">
        <div class="es-kongress-grid">
            
            <!-- MAIN COLUMN -->
            <main class="es-kongress-main">
                
                <?php
                // Check for ticket info (simple fields OR Ticket Addon including global tickets)
                $has_addon_tickets = function_exists('ensemble_has_tickets') && ensemble_has_tickets($event_id);
                $has_tickets = ($ticket_url || $price || $has_addon_tickets);
                
                // Check for catalogs assigned to this event
                $event_catalogs = get_posts(array(
                    'post_type' => 'es_catalog',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_catalog_event',
                            'value' => $event_id,
                            'compare' => '=',
                        ),
                    ),
                ));
                $has_catalog = !empty($event_catalogs);
                
                // Get catalog type info for tab naming
                $catalog_tab_name = __('Catalog', 'ensemble');
                $catalog_tab_icon = 'book'; // default
                
                if ($has_catalog) {
                    // Catalog type definitions
                    $catalog_type_names = array(
                        'menu' => array('name' => __('Menu', 'ensemble'), 'icon' => 'utensils'),
                        'drinks' => array('name' => __('Drinks', 'ensemble'), 'icon' => 'glass'),
                        'merchandise' => array('name' => __('Merch', 'ensemble'), 'icon' => 'shirt'),
                        'services' => array('name' => __('Services', 'ensemble'), 'icon' => 'package'),
                        'equipment' => array('name' => __('Equipment', 'ensemble'), 'icon' => 'tool'),
                        'rooms' => array('name' => __('Rooms', 'ensemble'), 'icon' => 'door'),
                        'custom' => array('name' => __('Catalog', 'ensemble'), 'icon' => 'book'),
                    );
                    
                    // Get types from all catalogs
                    $catalog_types_found = array();
                    foreach ($event_catalogs as $cat) {
                        $type = get_post_meta($cat->ID, '_catalog_type', true);
                        if ($type && !in_array($type, $catalog_types_found)) {
                            $catalog_types_found[] = $type;
                        }
                    }
                    
                    // Set tab name based on catalog type(s)
                    if (count($catalog_types_found) === 1) {
                        // Single type - use its name
                        $type = $catalog_types_found[0];
                        if (isset($catalog_type_names[$type])) {
                            $catalog_tab_name = $catalog_type_names[$type]['name'];
                            $catalog_tab_icon = $catalog_type_names[$type]['icon'];
                        }
                    } elseif (count($catalog_types_found) > 1) {
                        // Multiple types - combine names (max 2)
                        $names = array();
                        foreach (array_slice($catalog_types_found, 0, 2) as $type) {
                            if (isset($catalog_type_names[$type])) {
                                $names[] = $catalog_type_names[$type]['name'];
                            }
                        }
                        $catalog_tab_name = implode(' & ', $names);
                        // Use first type's icon
                        if (isset($catalog_type_names[$catalog_types_found[0]])) {
                            $catalog_tab_icon = $catalog_type_names[$catalog_types_found[0]]['icon'];
                        }
                    }
                }
                
                // Multi-Day Event Detection
                $is_multiday = $has_children && !empty($child_events);
                
                // Analyze child event dates for smart labeling
                $child_dates = array();
                $date_counts = array();
                $unique_dates = array();
                
                if ($is_multiday) {
                    foreach ($child_events as $child) {
                        // Normalize date format to Y-m-d for comparison
                        $raw_date = !empty($child['start_date']) ? $child['start_date'] : '';
                        $date = '';
                        if ($raw_date) {
                            $timestamp = strtotime($raw_date);
                            if ($timestamp) {
                                $date = date('Y-m-d', $timestamp); // Normalize format
                            }
                        }
                        $child_dates[] = $date;
                        if ($date) {
                            $date_counts[$date] = isset($date_counts[$date]) ? $date_counts[$date] + 1 : 1;
                        }
                    }
                    $unique_dates = array_unique(array_filter($child_dates));
                }
                
                // Determine labeling mode:
                // - 'event': All same date (or no dates) → "Event 1", "Event 2"
                // - 'day': All different dates → "Mon 5. Jan", "Tue 6. Jan"  
                // - 'mixed': Multiple events on some days → "Mon 5. Jan #1"
                $label_mode = 'day';
                if ($is_multiday) {
                    $unique_count = count($unique_dates);
                    $child_count = count($child_events);
                    
                    if ($unique_count === 0) {
                        // No dates set - use Event numbering
                        $label_mode = 'event';
                    } elseif ($unique_count === 1) {
                        // All same date - use Event numbering
                        $label_mode = 'event';
                    } elseif ($unique_count < $child_count) {
                        // Fewer unique dates than events = some days have multiple events
                        $label_mode = 'mixed';
                    }
                    // else: all different dates = 'day' mode (default)
                }
                
                // Track event counters per date for mixed mode
                $date_event_counters = array();
                ?>
                
                <!-- TAB NAVIGATION -->
                <nav class="es-kongress-tabs es-kongress-tabs-centered" role="tablist">
                    
                    <?php if ($is_multiday): ?>
                        <!-- MULTI-DAY/MULTI-EVENT: Show tabs for each child -->
                        <?php foreach ($child_events as $day_index => $child_event): 
                            $day_number = $day_index + 1;
                            $child_date_raw = !empty($child_event['start_date']) ? $child_event['start_date'] : '';
                            $child_date_normalized = $child_date_raw ? date('Y-m-d', strtotime($child_date_raw)) : '';
                            $is_first = ($day_index === 0);
                            
                            // Generate smart label based on mode
                            if ($label_mode === 'event') {
                                // All same date or no dates: Event 1, Event 2, Event 3
                                $tab_label = sprintf(__('Event %d', 'ensemble'), $day_number);
                            } elseif ($label_mode === 'day') {
                                // All different dates: Show date
                                $tab_label = $child_date_raw ? date_i18n('D j. M', strtotime($child_date_raw)) : sprintf(__('Day %d', 'ensemble'), $day_number);
                            } else {
                                // Mixed: Date + Event number for days with multiple events
                                if (!isset($date_event_counters[$child_date_normalized])) {
                                    $date_event_counters[$child_date_normalized] = 0;
                                }
                                $date_event_counters[$child_date_normalized]++;
                                
                                if ($child_date_normalized && isset($date_counts[$child_date_normalized]) && $date_counts[$child_date_normalized] > 1) {
                                    // Multiple events on this date
                                    $tab_label = date_i18n('D j.', strtotime($child_date_raw)) . ' #' . $date_event_counters[$child_date_normalized];
                                } elseif ($child_date_raw) {
                                    // Single event on this date
                                    $tab_label = date_i18n('D j. M', strtotime($child_date_raw));
                                } else {
                                    // No date - fallback
                                    $tab_label = sprintf(__('Event %d', 'ensemble'), $day_number);
                                }
                            }
                        ?>
                        <button class="es-kongress-tab <?php echo $is_first ? 'is-active' : ''; ?>" 
                                data-tab="day-<?php echo $day_number; ?>" 
                                role="tab" 
                                aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>" 
                                aria-controls="tab-day-<?php echo $day_number; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span><?php echo esc_html($tab_label); ?></span>
                        </button>
                        <?php endforeach; ?>
                        
                    <?php else: ?>
                        <!-- SINGLE EVENT: Show Program tab -->
                        <button class="es-kongress-tab is-active" data-tab="agenda" role="tab" aria-selected="true" aria-controls="tab-agenda">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span>Program</span>
                        </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($artists)): ?>
                    <button class="es-kongress-tab" data-tab="speakers" role="tab" aria-selected="false" aria-controls="tab-speakers">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span>Speakers</span>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($has_catalog): ?>
                    <button class="es-kongress-tab" data-tab="catalog" role="tab" aria-selected="false" aria-controls="tab-catalog">
                        <?php 
                        // Dynamic icon based on catalog type
                        switch ($catalog_tab_icon):
                            case 'utensils': ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                                    <path d="M7 2v20"/>
                                    <path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                                </svg>
                            <?php break;
                            case 'glass': ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M8 22h8"/>
                                    <path d="M12 11v11"/>
                                    <path d="m19 3-7 8-7-8Z"/>
                                </svg>
                            <?php break;
                            case 'shirt': ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>
                                </svg>
                            <?php break;
                            case 'package': ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="m7.5 4.27 9 5.15"/>
                                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                    <path d="m3.3 7 8.7 5 8.7-5"/>
                                    <path d="M12 22V12"/>
                                </svg>
                            <?php break;
                            case 'tool': ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                </svg>
                            <?php break;
                            case 'door': ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M18 20V6a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v14"/>
                                    <path d="M2 20h20"/>
                                    <path d="M14 12v.01"/>
                                </svg>
                            <?php break;
                            default: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                    <line x1="8" y1="6" x2="16" y2="6"/>
                                    <line x1="8" y1="10" x2="16" y2="10"/>
                                    <line x1="8" y1="14" x2="12" y2="14"/>
                                </svg>
                        <?php endswitch; ?>
                        <span><?php echo esc_html($catalog_tab_name); ?></span>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($has_tickets): ?>
                    <button class="es-kongress-tab" data-tab="tickets" role="tab" aria-selected="false" aria-controls="tab-tickets">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M2 9a3 3 0 0 1 3 3v1a3 3 0 0 1-3 3V9z"/>
                            <path d="M22 9a3 3 0 0 0-3 3v1a3 3 0 0 0 3 3V9z"/>
                            <rect x="4" y="6" width="16" height="12" rx="2"/>
                        </svg>
                        <span>Tickets</span>
                    </button>
                    <?php endif; ?>
                </nav>
                
                <!-- TAB PANELS -->
                <div class="es-kongress-tab-panels">
                    
                    <?php if ($is_multiday): ?>
                    <!-- MULTI-DAY: Tab panels for each day -->
                    <?php foreach ($child_events as $day_index => $child_event): 
                        $day_number = $day_index + 1;
                        $is_first = ($day_index === 0);
                        
                        // Get child event ID
                        $child_id = !empty($child_event['id']) ? $child_event['id'] : 0;
                        
                        // Get child event data
                        $child_title = !empty($child_event['title']) ? $child_event['title'] : '';
                        $child_date = !empty($child_event['start_date']) ? $child_event['start_date'] : '';
                        $child_time = !empty($child_event['start_time']) ? $child_event['start_time'] : '';
                        $child_description = !empty($child_event['description']) ? $child_event['description'] : '';
                        $child_additional_info = !empty($child_event['additional_info']) ? $child_event['additional_info'] : '';
                        $child_location = !empty($child_event['location']) ? $child_event['location'] : null;
                        $child_permalink = !empty($child_event['permalink']) ? $child_event['permalink'] : '';
                        
                        // Load agenda/timetable directly for this child event
                        $child_agenda = array();
                        if ($child_id && function_exists('ensemble_get_merged_agenda')) {
                            $child_agenda = ensemble_get_merged_agenda($child_id);
                        }
                        
                        // Get artists for this child
                        $child_artists = !empty($child_event['artists']) ? $child_event['artists'] : array();
                    ?>
                    <div class="es-kongress-tab-panel <?php echo $is_first ? 'is-active' : ''; ?>" id="tab-day-<?php echo $day_number; ?>" role="tabpanel">
                        
                        <!-- Day Header -->
                        <div class="es-kongress-day-header es-kongress-animate">
                            <h2 class="es-kongress-day-title"><?php echo esc_html($child_title); ?></h2>
                            <div class="es-kongress-day-meta">
                                <?php if ($child_date): ?>
                                <span class="es-kongress-day-date">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <?php echo date_i18n('l, j. F Y', strtotime($child_date)); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($child_time): ?>
                                <span class="es-kongress-day-time">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <?php echo esc_html($child_time); ?> Uhr
                                </span>
                                <?php endif; ?>
                                <?php if ($child_location && is_array($child_location) && !empty($child_location['name'])): ?>
                                <span class="es-kongress-day-location">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <?php echo esc_html($child_location['name']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($child_permalink): ?>
                                <a href="<?php echo esc_url($child_permalink); ?>" class="es-kongress-day-link">
                                    <?php _e('Read more', 'ensemble'); ?> →
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($child_additional_info): ?>
                        <div class="es-kongress-intro es-kongress-animate">
                            <?php echo wp_kses_post($child_additional_info); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- TIMETABLE / ARTISTS & BREAKS for this day -->
                        <?php if (!empty($child_agenda)): ?>
                        <div class="es-kongress-agenda es-kongress-animate">
                            <div class="es-kongress-timeline">
                                <?php foreach ($child_agenda as $item): 
                                    // Get item type
                                    $type = $item['type'] ?? 'session';
                                    $is_break = ($type === 'break');
                                    
                                    // Time
                                    $time_start = $item['time'] ?? '';
                                    
                                    // Title
                                    if ($is_break) {
                                        $item_title = !empty($item['title']) ? $item['title'] : 'Break';
                                    } else {
                                        $item_title = !empty($item['session_title']) ? $item['session_title'] : ($item['artist_name'] ?? '');
                                    }
                                    
                                    // Room/Venue
                                    $room = $item['venue'] ?? '';
                                    
                                    // Icon for breaks
                                    $icon = $item['icon'] ?? 'pause';
                                    
                                    // Duration for breaks
                                    $duration = $item['duration'] ?? '';
                                    
                                    // Build speakers array
                                    $speakers = array();
                                    if (!$is_break && !empty($item['artist_id'])) {
                                        $speakers[] = array(
                                            'id' => $item['artist_id'],
                                            'name' => $item['artist_name'] ?? '',
                                            'image' => $item['artist_image'] ?? '',
                                            'role_title' => $item['artist_role'] ?? '',
                                        );
                                    }
                                ?>
                                <div class="es-kongress-timeline-item <?php echo $is_break ? 'is-break' : 'is-session'; ?>">
                                    <div class="es-kongress-timeline-time">
                                        <span class="time-start"><?php echo esc_html($time_start); ?></span>
                                    </div>
                                    <div class="es-kongress-timeline-dot"></div>
                                    
                                    <?php if ($is_break): ?>
                                    <div class="es-kongress-break">
                                        <div class="es-kongress-break-icon">
                                            <?php echo ensemble_get_agenda_icon($icon); ?>
                                        </div>
                                        <div class="es-kongress-break-content">
                                            <div class="es-kongress-break-title"><?php echo esc_html($item_title); ?></div>
                                            <?php if ($duration): ?>
                                            <div class="es-kongress-break-duration"><?php echo esc_html($duration); ?> min</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="es-kongress-session">
                                        <div class="es-kongress-session-header">
                                            <h3 class="es-kongress-session-title"><?php echo esc_html($item_title); ?></h3>
                                            <?php if ($room): ?>
                                            <span class="es-kongress-session-venue"><?php echo esc_html($room); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($speakers)): ?>
                                        <div class="es-kongress-session-speakers">
                                            <?php foreach ($speakers as $speaker): ?>
                                            <a href="<?php echo $speaker['id'] ? esc_url(get_permalink($speaker['id'])) : '#'; ?>" class="es-kongress-session-speaker">
                                                <?php if (!empty($speaker['image'])): ?>
                                                <img src="<?php echo esc_url($speaker['image']); ?>" alt="<?php echo esc_attr($speaker['name']); ?>" class="es-kongress-session-speaker-image">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="es-kongress-session-speaker-name"><?php echo esc_html($speaker['name']); ?></div>
                                                    <?php if (!empty($speaker['role_title'])): ?>
                                                    <div class="es-kongress-session-speaker-title"><?php echo esc_html($speaker['role_title']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="es-kongress-empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <p><?php _e('Program for this day coming soon.', 'ensemble'); ?></p>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    <?php endforeach; ?>
                    
                    <?php else: ?>
                    <!-- SINGLE EVENT: Normal Agenda Tab -->
                    <div class="es-kongress-tab-panel is-active" id="tab-agenda" role="tabpanel">
                        
                        <?php 
                        // Additional Info (Programm-Details)
                        $additional_info = !empty($event['additional_info']) ? $event['additional_info'] : '';
                        if ($additional_info): 
                        ?>
                        <div class="es-kongress-intro es-kongress-animate">
                            <?php echo wp_kses_post($additional_info); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- AGENDA / TIMELINE -->
                        <?php if (!empty($agenda_items)): ?>
                        <div class="es-kongress-agenda es-kongress-animate">
                            <div class="es-kongress-timeline">
                                <?php foreach ($agenda_items as $item): 
                                    // Get item type
                                    $type = $item['type'] ?? 'session';
                                    $is_break = ($type === 'break');
                                    
                                    // Time
                                    $time_start = $item['time'] ?? '';
                                    
                                    // Title: For breaks use 'title', for sessions use 'session_title' or 'artist_name'
                                    if ($is_break) {
                                        $title = !empty($item['title']) ? $item['title'] : 'Break';
                                    } else {
                                        $title = !empty($item['session_title']) ? $item['session_title'] : ($item['artist_name'] ?? '');
                                    }
                                    
                                    // Room/Venue
                                    $room = $item['venue'] ?? '';
                                    
                                    // Icon for breaks
                                    $icon = $item['icon'] ?? 'pause';
                                    
                                    // Duration for breaks
                                    $duration = $item['duration'] ?? '';
                                    
                                    // Build speakers array for sessions
                                    $speakers = array();
                                    if (!$is_break && !empty($item['artist_id'])) {
                                        $speakers[] = array(
                                            'id' => $item['artist_id'],
                                            'name' => $item['artist_name'] ?? '',
                                            'image' => $item['artist_image'] ?? '',
                                            'role_title' => $item['artist_role'] ?? '',
                                        );
                                    }
                                ?>
                                <div class="es-kongress-timeline-item <?php echo $is_break ? 'is-break' : 'is-session'; ?>">
                                    <!-- Time Display -->
                                    <div class="es-kongress-timeline-time">
                                        <span class="time-start"><?php echo esc_html($time_start); ?></span>
                                    </div>
                                    
                                    <!-- Timeline Dot -->
                                    <div class="es-kongress-timeline-dot"></div>
                                    
                                    <?php if ($is_break): ?>
                                    <!-- Break/Pause -->
                                    <div class="es-kongress-break">
                                        <div class="es-kongress-break-icon">
                                            <?php echo ensemble_get_agenda_icon($icon); ?>
                                        </div>
                                        <div class="es-kongress-break-content">
                                            <div class="es-kongress-break-title"><?php echo esc_html($title); ?></div>
                                            <?php if ($duration): ?>
                                            <div class="es-kongress-break-duration"><?php echo esc_html($duration); ?> min</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php else: ?>
                                    <!-- Speaker Session -->
                                    <div class="es-kongress-session">
                                        <div class="es-kongress-session-header">
                                            <h3 class="es-kongress-session-title"><?php echo esc_html($title); ?></h3>
                                            <?php if ($room): ?>
                                            <span class="es-kongress-session-venue"><?php echo esc_html($room); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($speakers)): ?>
                                        <div class="es-kongress-session-speakers">
                                            <?php foreach ($speakers as $speaker): 
                                                $speaker_id = $speaker['id'] ?? 0;
                                                $speaker_name = $speaker['name'] ?? '';
                                                $speaker_image = $speaker['image'] ?? '';
                                                $speaker_role = $speaker['role_title'] ?? '';
                                            ?>
                                            <a href="<?php echo $speaker_id ? esc_url(get_permalink($speaker_id)) : '#'; ?>" class="es-kongress-session-speaker">
                                                <?php if ($speaker_image): ?>
                                                <img src="<?php echo esc_url($speaker_image); ?>" alt="<?php echo esc_attr($speaker_name); ?>" class="es-kongress-session-speaker-image">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="es-kongress-session-speaker-name"><?php echo esc_html($speaker_name); ?></div>
                                                    <?php if ($speaker_role): ?>
                                                    <div class="es-kongress-session-speaker-title"><?php echo esc_html($speaker_role); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="es-kongress-empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <p><?php _e('Program coming soon.', 'ensemble'); ?></p>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    <?php endif; ?>
                    
                    <!-- TAB 2: SPEAKERS -->
                    <?php if (!empty($artists)): ?>
                    <div class="es-kongress-tab-panel" id="tab-speakers" role="tabpanel">
                        <div class="es-kongress-speakers-grid">
                            <?php foreach ($artists as $index => $artist): 
                                $artist_id = is_array($artist) ? ($artist['id'] ?? 0) : (is_object($artist) ? $artist->ID : $artist);
                                if (!$artist_id) continue;
                                
                                $artist_post = get_post($artist_id);
                                if (!$artist_post) continue;
                                
                                $artist_name = $artist_post->post_title;
                                $artist_image = get_the_post_thumbnail_url($artist_id, 'large');
                                $artist_role = get_post_meta($artist_id, 'artist_role', true);
                                $artist_company = get_post_meta($artist_id, 'artist_company', true);
                                $artist_permalink = get_permalink($artist_id);
                                
                                // Get artist description (first 80 words)
                                $artist_content = $artist_post->post_content;
                                $artist_excerpt = wp_trim_words(wp_strip_all_tags($artist_content), 80, '...');
                            ?>
                            <a href="<?php echo esc_url($artist_permalink); ?>" class="es-kongress-speaker-card es-kongress-animate es-kongress-animate-delay-<?php echo ($index % 3) + 1; ?>">
                                <div class="es-kongress-speaker-image">
                                    <?php if ($artist_image): ?>
                                    <img src="<?php echo esc_url($artist_image); ?>" alt="<?php echo esc_attr($artist_name); ?>">
                                    <?php else: ?>
                                    <div class="es-kongress-speaker-placeholder">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Hover Overlay with Preview -->
                                    <?php if ($artist_excerpt): ?>
                                    <div class="es-kongress-speaker-overlay">
                                        <div class="es-kongress-speaker-preview">
                                            <?php echo esc_html($artist_excerpt); ?>
                                        </div>
                                        <span class="es-kongress-speaker-readmore">Read more →</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="es-kongress-speaker-info">
                                    <h3 class="es-kongress-speaker-name"><?php echo esc_html($artist_name); ?></h3>
                                    <?php if ($artist_role): ?>
                                    <div class="es-kongress-speaker-role"><?php echo esc_html($artist_role); ?></div>
                                    <?php endif; ?>
                                    <?php if ($artist_company): ?>
                                    <div class="es-kongress-speaker-company"><?php echo esc_html($artist_company); ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- TAB: CATALOG -->
                    <?php if ($has_catalog): ?>
                    <div class="es-kongress-tab-panel" id="tab-catalog" role="tabpanel">
                        <div class="es-kongress-catalog-content">
                            <?php 
                            // Render catalogs via the addon hook
                            if (function_exists('ensemble_event_catalog')) {
                                ensemble_event_catalog($event_id, $data['location_id'] ?? 0);
                            } else {
                                // Fallback: render catalogs directly
                                foreach ($event_catalogs as $catalog) {
                                    $catalog_type = get_post_meta($catalog->ID, '_catalog_type', true);
                                    $catalog_title = $catalog->post_title;
                                    ?>
                                    <div class="es-kongress-catalog-section">
                                        <h3 class="es-kongress-catalog-title"><?php echo esc_html($catalog_title); ?></h3>
                                        <?php
                                        // Try to get items
                                        $items = get_posts(array(
                                            'post_type' => 'es_catalog_item',
                                            'posts_per_page' => -1,
                                            'meta_query' => array(
                                                array(
                                                    'key' => '_item_catalog',
                                                    'value' => $catalog->ID,
                                                ),
                                            ),
                                            'orderby' => 'menu_order',
                                            'order' => 'ASC',
                                        ));
                                        
                                        if ($items): ?>
                                        <div class="es-kongress-catalog-items">
                                            <?php foreach ($items as $item): 
                                                $item_price = get_post_meta($item->ID, '_item_price', true);
                                                $item_desc = get_post_meta($item->ID, '_item_description', true);
                                            ?>
                                            <div class="es-kongress-catalog-item">
                                                <div class="es-kongress-catalog-item-name"><?php echo esc_html($item->post_title); ?></div>
                                                <?php if ($item_desc): ?>
                                                <div class="es-kongress-catalog-item-desc"><?php echo esc_html($item_desc); ?></div>
                                                <?php endif; ?>
                                                <?php if ($item_price): ?>
                                                <div class="es-kongress-catalog-item-price"><?php echo esc_html($item_price); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- TAB 3: TICKETS -->
                    <?php if ($has_tickets): ?>
                    <div class="es-kongress-tab-panel" id="tab-tickets" role="tabpanel">
                        <div class="es-kongress-tickets-content">
                            <?php if ($price): ?>
                            <div class="es-kongress-ticket-price-display">
                                <span class="price-label">Price</span>
                                <span class="price-value"><?php echo esc_html($price); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($price_info): ?>
                            <div class="es-kongress-ticket-info-text">
                                <?php echo esc_html($price_info); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($ticket_url && !$is_cancelled && !$is_soldout): ?>
                            <a href="<?php echo esc_url($ticket_url); ?>" class="es-kongress-btn es-kongress-btn-primary es-kongress-btn-large" target="_blank" rel="noopener">
                                <?php echo esc_html($button_text); ?>
                            </a>
                            <?php elseif ($is_soldout): ?>
                            <span class="es-kongress-btn es-kongress-btn-disabled es-kongress-btn-large">
                                Sold Out
                            </span>
                            <?php endif; ?>
                            
                            <?php 
                            // Hook: Ticket Area (Ticket Addon)
                            if (function_exists('ensemble_ticket_area')) {
                                ensemble_ticket_area($event_id);
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- LOCATION (außerhalb Tabs) -->
                <?php if ($location_name): ?>
                <section class="es-kongress-section es-kongress-animate" style="margin-top: 48px;">
                    <div class="es-kongress-section-header">
                        <div class="es-kongress-section-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <h2 class="es-kongress-section-title"><?php _e('Venue', 'ensemble'); ?></h2>
                    </div>
                    
                    <a href="<?php echo $location_permalink ? esc_url($location_permalink) : '#'; ?>" class="es-kongress-location-block">
                        <?php if ($location_image): ?>
                        <div class="es-kongress-location-image">
                            <img src="<?php echo esc_url($location_image); ?>" alt="<?php echo esc_attr($location_name); ?>">
                        </div>
                        <?php endif; ?>
                        <div class="es-kongress-location-info">
                            <h3><?php echo esc_html($location_name); ?></h3>
                            <?php if ($location_address || $location_city): ?>
                            <div class="es-kongress-location-address">
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
                // Hook: After Location (Maps etc.)
                if (function_exists('ensemble_after_location') && $location) {
                    ensemble_after_location($event_id, $location);
                }
                ?>
                
                <?php 
                // CONTACTS SECTION (Staff Addon)
                $contact_ids = get_post_meta($event_id, '_es_event_contacts', true);
                
                if (!empty($contact_ids) && is_array($contact_ids)):
                    // Get staff data - try multiple methods
                    $contacts = array();
                    
                    // Method 1: Via Addon Manager
                    if (class_exists('ES_Addon_Manager')) {
                        $staff_addon = ES_Addon_Manager::get_active_addon('staff');
                        if ($staff_addon && method_exists($staff_addon, 'get_staff_manager')) {
                            $staff_manager = $staff_addon->get_staff_manager();
                            foreach ($contact_ids as $contact_id) {
                                $person = $staff_manager->get_staff($contact_id);
                                if ($person) {
                                    $contacts[] = $person;
                                }
                            }
                        }
                    }
                    
                    // Method 2: Direct post query fallback
                    if (empty($contacts)) {
                        foreach ($contact_ids as $contact_id) {
                            $post = get_post($contact_id);
                            // Accept any post that exists and is published
                            if ($post && $post->post_status === 'publish') {
                                $contacts[] = array(
                                    'id' => $post->ID,
                                    'name' => $post->post_title,
                                    'position' => get_post_meta($post->ID, '_es_staff_position', true),
                                    'email' => get_post_meta($post->ID, '_es_staff_email', true),
                                    'phone_numbers' => get_post_meta($post->ID, '_es_staff_phone_numbers', true),
                                    'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium'),
                                );
                            }
                        }
                    }
                    
                    if (!empty($contacts)):
                        $contact_label = function_exists('ensemble_label') ? ensemble_label('staff', true) : __('Contact Persons', 'ensemble');
                ?>
                <section class="es-kongress-section es-kongress-animate" style="margin-top: 48px;">
                    <div class="es-kongress-section-header">
                        <div class="es-kongress-section-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <h2 class="es-kongress-section-title"><?php echo esc_html($contact_label); ?></h2>
                    </div>
                    
                    <div class="es-kongress-contacts-grid">
                        <?php foreach ($contacts as $person): ?>
                        <div class="es-kongress-contact-card">
                            <?php if (!empty($person['featured_image'])): ?>
                            <div class="es-kongress-contact-image">
                                <img src="<?php echo esc_url($person['featured_image']); ?>" alt="<?php echo esc_attr($person['name']); ?>">
                            </div>
                            <?php else: ?>
                            <div class="es-kongress-contact-image es-kongress-contact-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <?php endif; ?>
                            <div class="es-kongress-contact-info">
                                <h4 class="es-kongress-contact-name"><?php echo esc_html($person['name']); ?></h4>
                                
                                <?php if (!empty($person['position'])): ?>
                                <p class="es-kongress-contact-position"><?php echo esc_html($person['position']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($person['department'])): ?>
                                <p class="es-kongress-contact-department"><?php echo esc_html($person['department']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($person['responsibility'])): ?>
                                <p class="es-kongress-contact-responsibility"><?php echo esc_html($person['responsibility']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($person['office_hours'])): ?>
                                <p class="es-kongress-contact-hours">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12,6 12,12 16,14"/>
                                    </svg>
                                    <?php echo esc_html($person['office_hours']); ?>
                                </p>
                                <?php endif; ?>
                                
                                <div class="es-kongress-contact-links">
                                    <?php if (!empty($person['email'])): ?>
                                    <a href="mailto:<?php echo esc_attr($person['email']); ?>" class="es-kongress-contact-email" title="<?php echo esc_attr($person['email']); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                            <polyline points="22,6 12,13 2,6"/>
                                        </svg>
                                        <span><?php echo esc_html($person['email']); ?></span>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Phone numbers - support both 'phones' array and legacy 'phone_numbers'
                                    $phones = !empty($person['phones']) ? $person['phones'] : array();
                                    if (empty($phones) && !empty($person['phone_numbers'])) {
                                        // Convert legacy format
                                        foreach ((array)$person['phone_numbers'] as $num) {
                                            $phones[] = array('type' => 'office', 'number' => $num);
                                        }
                                    }
                                    if (!empty($phones) && is_array($phones)):
                                        foreach ($phones as $phone):
                                            if (empty($phone['number'])) continue;
                                            $phone_type = isset($phone['type']) ? $phone['type'] : 'office';
                                            $type_labels = array(
                                                'office' => __('Office', 'ensemble'),
                                                'mobile' => __('Mobile', 'ensemble'),
                                                'fax' => __('Fax', 'ensemble')
                                            );
                                            $type_label = isset($type_labels[$phone_type]) ? $type_labels[$phone_type] : $type_labels['office'];
                                    ?>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone['number'])); ?>" class="es-kongress-contact-phone" title="<?php echo esc_attr($type_label); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                        </svg>
                                        <span><?php echo esc_html($phone['number']); ?><?php if ($phone_type !== 'office'): ?> <small>(<?php echo esc_html($type_label); ?>)</small><?php endif; ?></span>
                                    </a>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </div>
                                
                                <?php 
                                // Social Links
                                $has_social = !empty($person['website']) || !empty($person['linkedin']) || !empty($person['twitter']);
                                if ($has_social): 
                                ?>
                                <div class="es-kongress-contact-social">
                                    <?php if (!empty($person['website'])): ?>
                                    <a href="<?php echo esc_url($person['website']); ?>" target="_blank" rel="noopener noreferrer" title="<?php esc_attr_e('Website', 'ensemble'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 16px; height: 16px;">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="2" y1="12" x2="22" y2="12"/>
                                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($person['linkedin'])): ?>
                                    <a href="<?php echo esc_url($person['linkedin']); ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn">
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 16px; height: 16px;">
                                            <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($person['twitter'])): ?>
                                    <a href="<?php echo esc_url($person['twitter']); ?>" target="_blank" rel="noopener noreferrer" title="Twitter / X">
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 16px; height: 16px;">
                                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php 
                    endif;
                endif;
                ?>
                
                <?php 
                // ADDON HOOK: Downloads, Materials, etc.
                if (class_exists('ES_Addon_Manager')) {
                    ES_Addon_Manager::do_addon_hook('ensemble_after_description', $event_id, array(
                        'event' => $event,
                        'layout' => 'kongress'
                    ));
                }
                ?>
                
            </main>
            
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="es-kongress-footer">
        <div class="es-kongress-content-wrapper">
            <?php 
            // Hook: Sponsors
            do_action('ensemble_sponsors_display', $event_id);
            
            // Hook: Social Share
            if (function_exists('ensemble_social_share')) {
                ensemble_social_share($event_id);
            }
            
            // Hook: Related Events
            if (function_exists('ensemble_related_events')) {
                ensemble_related_events($event_id);
            }
            
            // Hook: After Event
            do_action('ensemble_after_single_event', $event_id, $event);
            ?>
        </div>
    </footer>

</article>

<!-- Scroll Animation & Counter & Tabs Script -->
<script>
(function() {
    // =====================
    // TAB SWITCHING
    // =====================
    var tabs = document.querySelectorAll('.es-kongress-tab');
    var panels = document.querySelectorAll('.es-kongress-tab-panel');
    
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var targetId = 'tab-' + this.getAttribute('data-tab');
            
            // Update tabs
            tabs.forEach(function(t) {
                t.classList.remove('is-active');
                t.setAttribute('aria-selected', 'false');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-selected', 'true');
            
            // Update panels with fade
            panels.forEach(function(panel) {
                if (panel.id === targetId) {
                    panel.classList.add('is-active');
                    // Trigger animations in newly visible panel
                    panel.querySelectorAll('.es-kongress-animate').forEach(function(el) {
                        el.classList.add('is-visible');
                    });
                    // Check for description reveal
                    if (targetId === 'tab-description') {
                        initDescriptionReveal();
                    }
                } else {
                    panel.classList.remove('is-active');
                }
            });
        });
    });
    
    // =====================
    // DESCRIPTION SCROLL REVEAL
    // =====================
    function initDescriptionReveal() {
        var wrapper = document.querySelector('.es-kongress-description-wrapper');
        var content = document.querySelector('.es-kongress-description-content');
        var fade = document.querySelector('.es-kongress-description-fade');
        
        if (!wrapper || !content) return;
        
        function updateReveal() {
            var wrapperRect = wrapper.getBoundingClientRect();
            var contentHeight = content.scrollHeight;
            var visibleHeight = wrapper.offsetHeight;
            var scrollTop = wrapper.scrollTop;
            var scrollPercent = scrollTop / (contentHeight - visibleHeight);
            
            // Fade out the overlay as user scrolls
            if (fade) {
                var fadeOpacity = Math.max(0, 1 - (scrollPercent * 2));
                fade.style.opacity = fadeOpacity;
            }
            
            // Reveal text paragraphs
            var paragraphs = content.querySelectorAll('p, h2, h3, h4, ul, ol, blockquote');
            paragraphs.forEach(function(p, index) {
                var pRect = p.getBoundingClientRect();
                var pTop = pRect.top - wrapperRect.top;
                
                if (pTop < visibleHeight * 0.8) {
                    p.classList.add('is-revealed');
                }
            });
        }
        
        // Initial check
        setTimeout(updateReveal, 100);
        
        // On scroll within wrapper
        wrapper.addEventListener('scroll', updateReveal);
    }
    
    // =====================
    // SCROLL ANIMATION OBSERVER
    // =====================
    var animateElements = document.querySelectorAll('.es-kongress-animate');
    
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    
    animateElements.forEach(function(el) {
        // Only observe elements in the active tab initially
        var panel = el.closest('.es-kongress-tab-panel');
        if (!panel || panel.classList.contains('is-active')) {
            observer.observe(el);
        }
    });
    
    // =====================
    // COUNTER ANIMATION
    // =====================
    var counters = document.querySelectorAll('.es-kongress-stat-number[data-count]');
    var counterObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                entry.target.classList.add('counted');
                var target = parseInt(entry.target.getAttribute('data-count'));
                var duration = 1500;
                var start = 0;
                var startTime = null;
                
                function animate(currentTime) {
                    if (!startTime) startTime = currentTime;
                    var progress = Math.min((currentTime - startTime) / duration, 1);
                    var easeProgress = 1 - Math.pow(1 - progress, 3);
                    entry.target.textContent = Math.floor(easeProgress * target);
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        entry.target.textContent = target;
                    }
                }
                requestAnimationFrame(animate);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(function(counter) {
        counterObserver.observe(counter);
    });
})();
</script>

<?php get_footer(); ?>
