<?php
/**
 * Calendar View Template
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$calendar = new ES_Calendar();

// Get current view and date
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'month';
$date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

// Parse date
$timestamp = strtotime($date);
$year = date('Y', $timestamp);
$month = date('n', $timestamp);
$day = date('j', $timestamp);

// Get events based on view
if ($view === 'month') {
    $events = $calendar->get_events_for_month($year, $month);
    $grid = $calendar->get_month_grid($year, $month);
} elseif ($view === 'week') {
    $events = $calendar->get_events_for_week($date);
    $grid = $calendar->get_week_grid($date);
} elseif ($view === 'agenda') {
    // Agenda: Get next 30 days of events
    $agenda_start = $date;
    $agenda_end = date('Y-m-d', strtotime('+30 days', $timestamp));
    $events = $calendar->get_events_for_range($agenda_start, $agenda_end);
    $grid = null;
} else {
    $events = $calendar->get_events_for_day($date);
    $grid = null;
}

// Group events by date
$events_by_date = array();
foreach ($events as $event) {
    $event_date = $event['date'];
    if (!isset($events_by_date[$event_date])) {
        $events_by_date[$event_date] = array();
    }
    $events_by_date[$event_date][] = $event;
}

// Navigation URLs
$prev_url = admin_url('admin.php?page=ensemble-calendar&view=' . $view);
$next_url = admin_url('admin.php?page=ensemble-calendar&view=' . $view);
$today_url = admin_url('admin.php?page=ensemble-calendar&view=' . $view);

if ($view === 'month') {
    $prev_month = $month - 1;
    $prev_year = $year;
    if ($prev_month < 1) {
        $prev_month = 12;
        $prev_year--;
    }
    $prev_url .= '&date=' . sprintf('%04d-%02d-01', $prev_year, $prev_month);
    
    $next_month = $month + 1;
    $next_year = $year;
    if ($next_month > 12) {
        $next_month = 1;
        $next_year++;
    }
    $next_url .= '&date=' . sprintf('%04d-%02d-01', $next_year, $next_month);
    
    $current_label = date('F Y', $timestamp);
} elseif ($view === 'week') {
    $prev_url .= '&date=' . date('Y-m-d', strtotime('-1 week', $timestamp));
    $next_url .= '&date=' . date('Y-m-d', strtotime('+1 week', $timestamp));
    
    $week_start = strtotime("-" . (date('N', $timestamp) - 1) . " days", $timestamp);
    $week_end = strtotime("+6 days", $week_start);
    $current_label = date('M j', $week_start) . ' - ' . date('M j, Y', $week_end);
} elseif ($view === 'agenda') {
    $prev_url .= '&date=' . date('Y-m-d', strtotime('-30 days', $timestamp));
    $next_url .= '&date=' . date('Y-m-d', strtotime('+30 days', $timestamp));
    
    $agenda_end_date = strtotime('+29 days', $timestamp);
    $current_label = date('M j', $timestamp) . ' - ' . date('M j, Y', $agenda_end_date);
} else {
    $prev_url .= '&date=' . date('Y-m-d', strtotime('-1 day', $timestamp));
    $next_url .= '&date=' . date('Y-m-d', strtotime('+1 day', $timestamp));
    $current_label = date('F j, Y', $timestamp);
}
?>

<div class="wrap es-calendar-wrap">
    <h1><?php _e('Event Calendar', 'ensemble'); ?></h1>
    
    <div class="es-calendar-container">
        
        <!-- Unified Toolbar with Navigation, Filters, Jump Date and Stats -->
        <div class="es-calendar-unified-toolbar">
            <!-- Row 1: Navigation and Views -->
            <div class="es-toolbar-row es-toolbar-nav-row">
                <div class="es-calendar-nav">
                    <a href="<?php echo esc_url($prev_url); ?>" class="button" id="es-cal-prev">‹ <?php _e('Previous', 'ensemble'); ?></a>
                    <a href="<?php echo esc_url($today_url); ?>" class="button" id="es-cal-today"><?php _e('Today', 'ensemble'); ?></a>
                    <a href="<?php echo esc_url($next_url); ?>" class="button" id="es-cal-next"><?php _e('Next', 'ensemble'); ?> ›</a>
                </div>
                
                <div class="es-calendar-title">
                    <h2><?php echo esc_html($current_label); ?></h2>
                </div>
                
                <div class="es-calendar-views">
                    <a href="<?php echo admin_url('admin.php?page=ensemble-calendar&view=month&date=' . $date); ?>" 
                       class="button <?php echo $view === 'month' ? 'button-primary' : ''; ?>"
                       data-view="month">
                        <?php _e('Month', 'ensemble'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ensemble-calendar&view=week&date=' . $date); ?>" 
                       class="button <?php echo $view === 'week' ? 'button-primary' : ''; ?>"
                       data-view="week">
                        <?php _e('Week', 'ensemble'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ensemble-calendar&view=day&date=' . $date); ?>" 
                       class="button <?php echo $view === 'day' ? 'button-primary' : ''; ?>"
                       data-view="day">
                        <?php _e('Day', 'ensemble'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ensemble-calendar&view=agenda&date=' . $date); ?>" 
                       class="button <?php echo $view === 'agenda' ? 'button-primary' : ''; ?>"
                       data-view="agenda">
                        <?php _e('Agenda', 'ensemble'); ?>
                    </a>
                    
                    <!-- Actions Dropdown -->
                    <div class="es-calendar-actions">
                        <button type="button" class="button es-actions-toggle" id="es-actions-toggle" title="<?php _e('Actions', 'ensemble'); ?>">
                            <span class="dashicons dashicons-ellipsis"></span>
                        </button>
                        <div class="es-actions-dropdown" id="es-actions-dropdown" style="display: none;">
                            <a href="<?php echo admin_url('admin.php?page=ensemble-export'); ?>" class="es-action-item">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Export Calendar', 'ensemble'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=ensemble-import'); ?>" class="es-action-item">
                                <span class="dashicons dashicons-upload"></span>
                                <?php _e('Import Events', 'ensemble'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Row 2: Filters, Search, Jump Date, Stats - all inline -->
            <div class="es-toolbar-row es-toolbar-filter-row">
                <div class="es-filter-search">
                    <input type="text" 
                           id="es-calendar-search" 
                           class="es-search-input" 
                           placeholder="<?php _e('Search events...', 'ensemble'); ?>">
                    <span class="es-search-icon"><?php ES_Icons::icon('search'); ?></span>
                </div>
                
                <select id="es-filter-category" class="es-filter-select" data-filter="category">
                    <option value=""><?php _e('All Categories', 'ensemble'); ?></option>
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'ensemble_category',
                        'hide_empty' => false,
                    ));
                    foreach ($categories as $category) {
                        echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                    }
                    ?>
                </select>
                
                <select id="es-filter-location" class="es-filter-select" data-filter="location">
                    <option value=""><?php _e('All Locations', 'ensemble'); ?></option>
                    <?php
                    $locations = get_posts(array(
                        'post_type' => 'ensemble_location',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    foreach ($locations as $location) {
                        echo '<option value="' . esc_attr($location->ID) . '">' . esc_html($location->post_title) . '</option>';
                    }
                    ?>
                </select>
                
                <select id="es-filter-artist" class="es-filter-select" data-filter="artist">
                    <option value=""><?php _e('All Artists', 'ensemble'); ?></option>
                    <?php
                    $artists = get_posts(array(
                        'post_type' => 'ensemble_artist',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    foreach ($artists as $artist) {
                        echo '<option value="' . esc_attr($artist->ID) . '">' . esc_html($artist->post_title) . '</option>';
                    }
                    ?>
                </select>
                
                <button type="button" id="es-clear-filters" class="button" style="display: none;">
                    <?php _e('Clear', 'ensemble'); ?>
                </button>
                
                <div class="es-toolbar-spacer"></div>
                
                <div class="es-quick-jump-inline">
                    <label><?php _e('Jump to', 'ensemble'); ?></label>
                    <input type="date" id="es-jump-date" value="<?php echo esc_attr($date); ?>">
                </div>
                
                <div class="es-event-count-badge">
                    <span class="es-count-number"><?php echo count($events); ?></span>
                    <span class="es-count-label"><?php _e('Events', 'ensemble'); ?></span>
                </div>
            </div>
            
            <!-- Active Filters Chips -->
            <div class="es-active-filters" id="es-active-filters" style="display: none;"></div>
            
            <!-- Category Color Legend -->
            <?php 
            $legend_categories = get_terms(array(
                'taxonomy' => 'ensemble_category',
                'hide_empty' => false,
            ));
            if (!is_wp_error($legend_categories) && !empty($legend_categories)):
            ?>
            <div class="es-category-legend">
                <?php foreach ($legend_categories as $cat): 
                    $cat_color = get_term_meta($cat->term_id, 'ensemble_category_color', true);
                    if (empty($cat_color)) $cat_color = '#3582c4';
                ?>
                <div class="es-legend-item" data-category-id="<?php echo esc_attr($cat->term_id); ?>">
                    <span class="es-legend-color" style="background: <?php echo esc_attr($cat_color); ?>"></span>
                    <span class="es-legend-name"><?php echo esc_html($cat->name); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Calendar Grid (no sidebar) -->
        <div class="es-calendar-main">
        
        <?php if ($view === 'month'): ?>
            <!-- Month View -->
            <div class="es-calendar-month">
                <div class="es-calendar-header">
                    <div class="es-calendar-day-name"><?php _e('Mon', 'ensemble'); ?></div>
                    <div class="es-calendar-day-name"><?php _e('Tue', 'ensemble'); ?></div>
                    <div class="es-calendar-day-name"><?php _e('Wed', 'ensemble'); ?></div>
                    <div class="es-calendar-day-name"><?php _e('Thu', 'ensemble'); ?></div>
                    <div class="es-calendar-day-name"><?php _e('Fri', 'ensemble'); ?></div>
                    <div class="es-calendar-day-name"><?php _e('Sat', 'ensemble'); ?></div>
                    <div class="es-calendar-day-name"><?php _e('Sun', 'ensemble'); ?></div>
                </div>
                
                <div class="es-calendar-grid">
                    <?php foreach ($grid as $cell): ?>
                        <?php
                        $cell_class = 'es-calendar-cell';
                        if (!$cell['is_current_month']) $cell_class .= ' es-other-month';
                        if ($cell['is_today']) $cell_class .= ' es-today';
                        
                        $day_events = isset($events_by_date[$cell['date']]) ? $events_by_date[$cell['date']] : array();
                        if (!empty($day_events)) $cell_class .= ' es-has-events';
                        
                        $event_count = count($day_events);
                        $max_visible = 3; // Show max 3 events initially
                        ?>
                        <div class="<?php echo esc_attr($cell_class); ?>" 
                             data-date="<?php echo esc_attr($cell['date']); ?>"
                             data-event-count="<?php echo esc_attr($event_count); ?>">
                            <div class="es-cell-date"><?php echo $cell['day']; ?></div>
                            <div class="es-cell-events" data-max-visible="<?php echo $max_visible; ?>">
                                <?php 
                                $event_index = 0;
                                foreach ($day_events as $event): 
                                    $event_index++;
                                    $is_hidden = $event_index > $max_visible;
                                ?>
                                    <?php
                                    $color = $calendar->get_event_color($event);
                                    $time = $event['time'] ? $event['time'] : '';
                                    $is_virtual = !empty($event['is_virtual']);
                                    $is_recurring = !empty($event['is_recurring']);
                                    $is_multi_day = !empty($event['is_multi_day']);
                                    $is_permanent = !empty($event['is_permanent']);
                                    $duration_type = isset($event['duration_type']) ? $event['duration_type'] : 'single';
                                    $multi_day_position = isset($event['multi_day_position']) ? $event['multi_day_position'] : null;
                                    $event_status = isset($event['event_status']) ? $event['event_status'] : 'publish';
                                    $event_class = 'es-calendar-event';
                                    if ($is_virtual) $event_class .= ' es-virtual-event';
                                    if ($is_hidden) $event_class .= ' es-hidden-event';
                                    if ($event_status !== 'publish') $event_class .= ' es-status-' . $event_status;
                                    if ($is_multi_day) {
                                        $event_class .= ' es-multi-day-event';
                                        if ($multi_day_position) {
                                            $event_class .= ' es-multi-day-' . $multi_day_position;
                                        }
                                    }
                                    if ($is_permanent) $event_class .= ' es-permanent-event';
                                    
                                    // Prepare filter data
                                    $categories_json = !empty($event['categories']) ? json_encode($event['categories']) : '[]';
                                    $artists_json = !empty($event['artists']) ? json_encode($event['artists']) : '[]';
                                    $location_id = !empty($event['location_id']) ? $event['location_id'] : '';
                                    $location_name = !empty($event['location']) ? $event['location'] : '';
                                    $description = !empty($event['description']) ? $event['description'] : '';
                                    
                                    // Get category names for tooltip
                                    $category_names = array();
                                    if (!empty($event['categories'])) {
                                        foreach ($event['categories'] as $cat_id) {
                                            $term = get_term($cat_id, 'ensemble_category');
                                            if ($term && !is_wp_error($term)) {
                                                $category_names[] = $term->name;
                                            }
                                        }
                                    }
                                    $categories_text = !empty($category_names) ? implode(', ', $category_names) : '';
                                    
                                    // Get artist names for tooltip
                                    $artist_names = array();
                                    if (!empty($event['artists'])) {
                                        foreach ($event['artists'] as $artist_id) {
                                            $artist = get_post($artist_id);
                                            if ($artist) {
                                                $artist_names[] = $artist->post_title;
                                            }
                                        }
                                    }
                                    $artists_text = !empty($artist_names) ? implode(', ', $artist_names) : '';
                                    
                                    // Multi-day date range for tooltip
                                    $multi_day_range = '';
                                    if ($is_multi_day && !empty($event['multi_day_start']) && !empty($event['multi_day_end'])) {
                                        $multi_day_range = date_i18n('j. M', strtotime($event['multi_day_start'])) . ' - ' . date_i18n('j. M Y', strtotime($event['multi_day_end']));
                                    }
                                    ?>
                                    <div class="<?php echo esc_attr($event_class); ?>" 
                                         data-event-id="<?php echo esc_attr($event['id']); ?>"
                                         data-event-date="<?php echo esc_attr($event['date']); ?>"
                                         data-event-time="<?php echo esc_attr($time); ?>"
                                         data-categories='<?php echo esc_attr($categories_json); ?>'
                                         data-location-id="<?php echo esc_attr($location_id); ?>"
                                         data-location-name="<?php echo esc_attr($location_name); ?>"
                                         data-artists='<?php echo esc_attr($artists_json); ?>'
                                         data-description="<?php echo esc_attr($description); ?>"
                                         data-category-text="<?php echo esc_attr($categories_text); ?>"
                                         data-artist-text="<?php echo esc_attr($artists_text); ?>"
                                         data-duration-type="<?php echo esc_attr($duration_type); ?>"
                                         data-multi-day-position="<?php echo esc_attr($multi_day_position); ?>"
                                         data-multi-day-range="<?php echo esc_attr($multi_day_range); ?>"
                                         draggable="true"
                                         style="border-left-color: <?php echo esc_attr($color); ?>">
                                        <div class="es-event-badges">
                                            <?php if ($event_status === 'cancelled'): ?>
                                                <span class="es-event-badge es-badge-cancelled" title="<?php _e('Cancelled', 'ensemble'); ?>">✕</span>
                                            <?php elseif ($event_status === 'postponed'): ?>
                                                <span class="es-event-badge es-badge-postponed" title="<?php _e('Postponed', 'ensemble'); ?>">⏸</span>
                                            <?php elseif ($event_status === 'draft'): ?>
                                                <span class="es-event-badge es-badge-draft" title="<?php _e('Draft', 'ensemble'); ?>">✎</span>
                                            <?php elseif ($event_status === 'preview'): ?>
                                                <span class="es-event-badge es-badge-preview" title="<?php _e('Preview', 'ensemble'); ?>">👁</span>
                                            <?php endif; ?>
                                            <?php if ($is_multi_day): ?>
                                                <span class="es-event-badge es-badge-multi-day" title="<?php echo esc_attr($multi_day_range); ?>">
                                                    <span class="dashicons dashicons-calendar-alt"></span>
                                                </span>
                                            <?php elseif ($is_permanent): ?>
                                                <span class="es-event-badge es-badge-permanent" title="<?php _e('Permanent exhibition', 'ensemble'); ?>">
                                                    <span class="dashicons dashicons-admin-home"></span>
                                                </span>
                                            <?php elseif ($is_virtual): ?>
                                                <span class="es-event-badge es-badge-virtual" title="<?php _e('Virtual recurring instance', 'ensemble'); ?>">
                                                    <?php echo ES_Icons::get('sync'); ?>
                                                </span>
                                            <?php elseif ($is_recurring): ?>
                                                <span class="es-event-badge es-badge-recurring" title="<?php _e('Recurring event', 'ensemble'); ?>">
                                                    <?php echo ES_Icons::get('calendar_recurring'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="es-event-content">
                                            <?php if (!$is_multi_day || $multi_day_position === 'start'): ?>
                                                <span class="es-event-time"><?php echo esc_html($time); ?></span>
                                            <?php endif; ?>
                                            <span class="es-event-title"><?php echo esc_html($event['title']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($event_count > $max_visible): ?>
                                <div class="es-more-events" data-hidden-count="<?php echo ($event_count - $max_visible); ?>">
                                    +<?php echo ($event_count - $max_visible); ?> <?php _e('more', 'ensemble'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        <?php elseif ($view === 'week'): ?>
            <!-- Week View -->
            <div class="es-calendar-week">
                <div class="es-week-header">
                    <?php foreach ($grid as $day): ?>
                        <?php
                        $header_class = 'es-week-day-header';
                        if ($day['is_today']) $header_class .= ' es-today';
                        ?>
                        <div class="<?php echo esc_attr($header_class); ?>">
                            <div class="es-day-name"><?php echo substr($day['day_name'], 0, 3); ?></div>
                            <div class="es-day-number"><?php echo $day['day']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="es-week-grid">
                    <?php foreach ($grid as $day): ?>
                        <?php
                        $day_events = isset($events_by_date[$day['date']]) ? $events_by_date[$day['date']] : array();
                        $cell_class = 'es-week-day-cell';
                        if ($day['is_today']) $cell_class .= ' es-today';
                        ?>
                        <div class="<?php echo esc_attr($cell_class); ?>" data-date="<?php echo esc_attr($day['date']); ?>">
                            <?php if (empty($day_events)): ?>
                                <div class="es-no-events"><?php _e('No events', 'ensemble'); ?></div>
                            <?php else: ?>
                                <?php foreach ($day_events as $event): ?>
                                    <?php
                                    $color = $calendar->get_event_color($event);
                                    $time = $event['time'] ? $event['time'] : __('All day', 'ensemble');
                                    $is_virtual = !empty($event['is_virtual']);
                                    $is_recurring = !empty($event['is_recurring']);
                                    $event_status = isset($event['event_status']) ? $event['event_status'] : 'publish';
                                    $event_class = 'es-calendar-event';
                                    if ($is_virtual) $event_class .= ' es-virtual-event';
                                    if ($event_status !== 'publish') $event_class .= ' es-status-' . $event_status;
                                    
                                    // Prepare filter data (same as month view)
                                    $categories_json = !empty($event['categories']) ? json_encode($event['categories']) : '[]';
                                    $artists_json = !empty($event['artists']) ? json_encode($event['artists']) : '[]';
                                    $location_id = !empty($event['location_id']) ? $event['location_id'] : '';
                                    $location_name = !empty($event['location']) ? $event['location'] : '';
                                    $description = !empty($event['description']) ? $event['description'] : '';
                                    
                                    // Get category names for tooltip
                                    $category_names = array();
                                    if (!empty($event['categories'])) {
                                        foreach ($event['categories'] as $cat_id) {
                                            $term = get_term($cat_id, 'ensemble_category');
                                            if ($term && !is_wp_error($term)) {
                                                $category_names[] = $term->name;
                                            }
                                        }
                                    }
                                    $categories_text = !empty($category_names) ? implode(', ', $category_names) : '';
                                    
                                    // Get artist names for tooltip
                                    $artist_names = array();
                                    if (!empty($event['artists'])) {
                                        foreach ($event['artists'] as $artist_id) {
                                            $artist = get_post($artist_id);
                                            if ($artist) {
                                                $artist_names[] = $artist->post_title;
                                            }
                                        }
                                    }
                                    $artists_text = !empty($artist_names) ? implode(', ', $artist_names) : '';
                                    ?>
                                    <div class="<?php echo esc_attr($event_class); ?>" 
                                         data-event-id="<?php echo esc_attr($event['id']); ?>"
                                         data-event-date="<?php echo esc_attr($event['date']); ?>"
                                         data-event-time="<?php echo esc_attr($time); ?>"
                                         data-categories='<?php echo esc_attr($categories_json); ?>'
                                         data-location-id="<?php echo esc_attr($location_id); ?>"
                                         data-location-name="<?php echo esc_attr($location_name); ?>"
                                         data-artists='<?php echo esc_attr($artists_json); ?>'
                                         data-description="<?php echo esc_attr($description); ?>"
                                         data-category-text="<?php echo esc_attr($categories_text); ?>"
                                         data-artist-text="<?php echo esc_attr($artists_text); ?>"
                                         draggable="true"
                                         style="background-color: <?php echo esc_attr($color); ?>15; border-left-color: <?php echo esc_attr($color); ?>">
                                        <div class="es-event-badges">
                                            <?php if ($event_status === 'cancelled'): ?>
                                                <span class="es-event-badge es-badge-cancelled" title="<?php _e('Cancelled', 'ensemble'); ?>">✕</span>
                                            <?php elseif ($event_status === 'postponed'): ?>
                                                <span class="es-event-badge es-badge-postponed" title="<?php _e('Postponed', 'ensemble'); ?>">⏸</span>
                                            <?php elseif ($event_status === 'draft'): ?>
                                                <span class="es-event-badge es-badge-draft" title="<?php _e('Draft', 'ensemble'); ?>">✎</span>
                                            <?php elseif ($event_status === 'preview'): ?>
                                                <span class="es-event-badge es-badge-preview" title="<?php _e('Preview', 'ensemble'); ?>">👁</span>
                                            <?php endif; ?>
                                            <?php if ($is_virtual): ?>
                                                <span class="es-event-badge es-badge-virtual" title="<?php _e('Virtual recurring instance', 'ensemble'); ?>">
                                                    <?php echo ES_Icons::get('sync'); ?>
                                                </span>
                                            <?php elseif ($is_recurring): ?>
                                                <span class="es-event-badge es-badge-recurring" title="<?php _e('Recurring event', 'ensemble'); ?>">
                                                    <?php echo ES_Icons::get('calendar_recurring'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="es-event-time"><?php echo esc_html($time); ?></div>
                                        <div class="es-event-title"><?php echo esc_html($event['title']); ?></div>
                                        <?php if (!empty($event['location'])): ?>
                                            <div class="es-event-location"><?php echo esc_html($event['location']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        <?php elseif ($view === 'agenda'): ?>
            <!-- Agenda View -->
            <div class="es-calendar-agenda">
                <?php if (empty($events)): ?>
                    <div class="es-empty-agenda">
                        <div class="es-empty-icon"><?php ES_Icons::icon('calendar'); ?></div>
                        <h3><?php _e('No upcoming events', 'ensemble'); ?></h3>
                        <p><?php _e('There are no events scheduled in the next 30 days.', 'ensemble'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ensemble'); ?>" class="button button-primary">
                            <?php _e('Create Event', 'ensemble'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="es-agenda-list">
                        <?php
                        // Group events by date
                        $current_date = '';
                        $today = date('Y-m-d');
                        $tomorrow = date('Y-m-d', strtotime('+1 day'));
                        
                        foreach ($events as $event):
                            $event_date = $event['date'];
                            $show_date_header = ($event_date !== $current_date);
                            $current_date = $event_date;
                            
                            // Determine date label
                            if ($event_date === $today) {
                                $date_label = __('Today', 'ensemble');
                            } elseif ($event_date === $tomorrow) {
                                $date_label = __('Tomorrow', 'ensemble');
                            } else {
                                $date_label = date_i18n('l, j. F Y', strtotime($event_date));
                            }
                            
                            $color = $calendar->get_event_color($event);
                            $time = $event['time'] ? $event['time'] : __('All day', 'ensemble');
                            $is_virtual = !empty($event['is_virtual']);
                            $is_recurring = !empty($event['is_recurring']);
                            $is_past = $event_date < $today;
                            $event_status = isset($event['event_status']) ? $event['event_status'] : 'publish';
                            
                            // Get additional data
                            $location_name = !empty($event['location']) ? $event['location'] : '';
                        ?>
                            <?php if ($show_date_header): ?>
                                <div class="es-agenda-date-header <?php echo $event_date === $today ? 'es-today' : ''; ?> <?php echo $is_past ? 'es-past' : ''; ?>">
                                    <div class="es-agenda-date-icon"><?php ES_Icons::icon('calendar'); ?></div>
                                    <div class="es-agenda-date-text"><?php echo esc_html($date_label); ?></div>
                                    <?php if ($event_date === $today): ?>
                                        <span class="es-today-badge"><?php _e('Today', 'ensemble'); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="es-agenda-event <?php echo $is_past ? 'es-past-event' : ''; ?> <?php echo ($event_status !== 'publish') ? 'es-status-' . $event_status : ''; ?>" 
                                 data-event-id="<?php echo esc_attr($event['id']); ?>"
                                 style="--event-color: <?php echo esc_attr($color); ?>">
                                <div class="es-agenda-event-time">
                                    <span class="es-time-value"><?php echo esc_html($time); ?></span>
                                    <?php if ($event_status === 'cancelled'): ?>
                                        <span class="es-event-badge es-badge-cancelled" title="<?php _e('Cancelled', 'ensemble'); ?>">✕</span>
                                    <?php elseif ($event_status === 'postponed'): ?>
                                        <span class="es-event-badge es-badge-postponed" title="<?php _e('Postponed', 'ensemble'); ?>">⏸</span>
                                    <?php elseif ($event_status === 'draft'): ?>
                                        <span class="es-event-badge es-badge-draft" title="<?php _e('Draft', 'ensemble'); ?>">✎</span>
                                    <?php elseif ($event_status === 'preview'): ?>
                                        <span class="es-event-badge es-badge-preview" title="<?php _e('Preview', 'ensemble'); ?>">👁</span>
                                    <?php endif; ?>
                                    <?php if ($is_virtual): ?>
                                        <span class="es-event-badge es-badge-virtual" title="<?php _e('Virtual', 'ensemble'); ?>">
                                            <?php ES_Icons::icon('sync'); ?>
                                        </span>
                                    <?php elseif ($is_recurring): ?>
                                        <span class="es-event-badge es-badge-recurring" title="<?php _e('Recurring', 'ensemble'); ?>">
                                            <?php ES_Icons::icon('sync'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="es-agenda-event-content">
                                    <div class="es-agenda-event-title"><?php echo esc_html($event['title']); ?></div>
                                    <div class="es-agenda-event-meta">
                                        <?php if ($location_name): ?>
                                            <span class="es-meta-item">
                                                <?php ES_Icons::icon('location'); ?>
                                                <?php echo esc_html($location_name); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="es-agenda-event-actions">
                                    <a href="<?php echo admin_url('admin.php?page=ensemble&edit=' . $event['id']); ?>" 
                                       class="es-btn-icon-only" 
                                       title="<?php _e('Edit', 'ensemble'); ?>">
                                        <?php ES_Icons::icon('edit'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- Day View -->
            <div class="es-calendar-day">
                <?php if (empty($events)): ?>
                    <div class="es-empty-day">
                        <div class="es-empty-icon"><?php ES_Icons::icon('calendar'); ?></div>
                        <h3><?php _e('No events scheduled', 'ensemble'); ?></h3>
                        <p><?php _e('There are no events on this day.', 'ensemble'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ensemble'); ?>" class="button button-primary">
                            <?php _e('Create Event', 'ensemble'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="es-day-events">
                        <?php foreach ($events as $event): ?>
                            <?php
                            $color = $calendar->get_event_color($event);
                            $time = $event['time'] ? $event['time'] : __('All day', 'ensemble');
                            $is_virtual = !empty($event['is_virtual']);
                            $event_class = 'es-day-event';
                            if ($is_virtual) $event_class .= ' es-virtual-event';
                            ?>
                            <div class="<?php echo esc_attr($event_class); ?>" 
                                 data-event-id="<?php echo esc_attr($event['id']); ?>"
                                 style="border-left-color: <?php echo esc_attr($color); ?>">
                                <div class="es-day-event-header">
                                    <div class="es-day-event-time">
                                        <?php if ($is_virtual): ?>
                                            <span class="es-virtual-badge" title="<?php _e('Virtual recurring instance', 'ensemble'); ?>">🔄</span>
                                        <?php endif; ?>
                                        <?php echo esc_html($time); ?>
                                    </div>
                                    <div class="es-day-event-actions">
                                        <a href="<?php echo admin_url('admin.php?page=ensemble&edit=' . $event['id']); ?>" class="button button-small">
                                            <?php _e('Edit', 'ensemble'); ?>
                                        </a>
                                    </div>
                                </div>
                                <h3 class="es-day-event-title"><?php echo esc_html($event['title']); ?></h3>
                                <?php if (!empty($event['description'])): ?>
                                    <p class="es-day-event-description"><?php echo esc_html($event['description']); ?></p>
                                <?php endif; ?>
                                <div class="es-day-event-meta">
                                    <?php if (!empty($event['location'])): ?>
                                        <div class="es-meta-item">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html($event['location']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($event['categories'])): ?>
                                    <div class="es-day-event-categories">
                                        <?php 
                                        foreach ($event['categories'] as $cat_id): 
                                            $term = get_term($cat_id, 'ensemble_category');
                                            if ($term && !is_wp_error($term)):
                                        ?>
                                            <span class="es-category-badge"><?php echo esc_html($term->name); ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        </div><!-- .es-calendar-main -->
        
    </div>
    
    <!-- Event Details Modal -->
    <div id="es-event-modal" class="es-modal" style="display: none;">
        <div class="es-modal-content">
            <span class="es-modal-close">&times;</span>
            <div id="es-modal-body"></div>
        </div>
    </div>
    
</div>
