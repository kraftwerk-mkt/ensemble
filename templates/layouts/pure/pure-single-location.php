<?php
/**
 * Template: Pure Single Location
 * Ultra-minimal location profile
 * 
 * @package Ensemble
 * @version 2.4.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Styles
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-layout-pure', ENSEMBLE_PLUGIN_URL . 'templates/layouts/pure/style.css', array('ensemble-base'), ENSEMBLE_VERSION);

$location_id = get_the_ID();
$location = function_exists('es_load_location_data') ? es_load_location_data($location_id) : array();
$upcoming_events = function_exists('es_get_upcoming_events_by_location') ? es_get_upcoming_events_by_location($location_id) : array();

// Opening hours logic
$opening_hours = !empty($location['opening_hours']) ? $location['opening_hours'] : array();
$has_opening_hours = !empty(array_filter($opening_hours, function($day) {
    return !empty($day['open']) || !empty($day['closed']);
}));

// Check if currently open
$is_open = false;
$current_day = strtolower(date('l'));
$current_time = date('H:i');

if ($has_opening_hours && !empty($opening_hours[$current_day]) && empty($opening_hours[$current_day]['closed'])) {
    $open_time = $opening_hours[$current_day]['open'] ?? '';
    $close_time = $opening_hours[$current_day]['close'] ?? '';
    if ($open_time && $close_time) {
        if ($close_time < $open_time) {
            $is_open = ($current_time >= $open_time || $current_time <= $close_time);
        } else {
            $is_open = ($current_time >= $open_time && $current_time <= $close_time);
        }
    }
}

$day_names = array(
    'monday' => __('Mo', 'ensemble'),
    'tuesday' => __('Di', 'ensemble'),
    'wednesday' => __('Mi', 'ensemble'),
    'thursday' => __('Do', 'ensemble'),
    'friday' => __('Fr', 'ensemble'),
    'saturday' => __('Sa', 'ensemble'),
    'sunday' => __('So', 'ensemble'),
);

// Get current mode
$current_mode = 'light';
if (class_exists('ES_Layout_Sets')) {
    $current_mode = ES_Layout_Sets::get_active_mode();
}
?>

<div class="ensemble-single-location-wrapper es-layout-pure">
<article class="es-pure-single-location es-layout-pure <?php echo 'es-mode-' . esc_attr($current_mode); ?>">

    <?php if (function_exists('ensemble_before_location')) ensemble_before_location($location_id); ?>

    <!-- HERO IMAGE -->
    <?php if (has_post_thumbnail()): ?>
    <div class="es-pure-hero">
        <?php the_post_thumbnail('full'); ?>
    </div>
    <?php endif; ?>

    <!-- HEADER -->
    <header class="es-pure-header">
        <div class="es-pure-header-inner">
            
            <?php if (!empty($location['city']) || !empty($location['country'])): ?>
            <div class="es-pure-header-meta">
                <div class="es-pure-header-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span>
                        <?php 
                        echo esc_html($location['city'] ?? '');
                        if (!empty($location['city']) && !empty($location['country'])) echo ', ';
                        echo esc_html($location['country'] ?? '');
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <h1 class="es-pure-title"><?php the_title(); ?></h1>
            
            <!-- Open/Closed Status -->
            <?php if ($has_opening_hours): ?>
            <div class="es-pure-status-wrapper">
                <span class="es-pure-status <?php echo $is_open ? 'es-pure-status-open' : 'es-pure-status-closed'; ?>">
                    <span class="es-pure-status-dot"></span>
                    <?php echo $is_open ? __('Geöffnet', 'ensemble') : __('Geschlossen', 'ensemble'); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="es-pure-header-actions">
                <?php if (!empty($location['website'])): ?>
                <a href="<?php echo esc_url($location['website']); ?>" class="es-pure-btn es-pure-btn-ghost" target="_blank" rel="noopener">
                    <?php _e('Website', 'ensemble'); ?>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($location['address'])): ?>
                <a href="https://maps.google.com/?q=<?php echo urlencode($location['address'] . ', ' . ($location['city'] ?? '')); ?>" class="es-pure-btn es-pure-btn-ghost" target="_blank" rel="noopener">
                    <?php _e('Directions', 'ensemble'); ?>
                </a>
                <?php endif; ?>
            </div>
            
        </div>
    </header>

    <!-- CONTENT -->
    <div class="es-pure-content-wrapper">
        <div class="es-pure-layout">
            
            <main class="es-pure-main">
                
                <!-- Description -->
                <?php if (get_the_content()): ?>
                <section class="es-pure-section">
                    <h2 class="es-pure-section-title"><?php _e('About', 'ensemble'); ?></h2>
                    <div class="es-pure-prose">
                        <?php the_content(); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Gallery -->
                <?php if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_gallery')): ?>
                <section class="es-pure-section es-pure-gallery">
                    <h2 class="es-pure-section-title"><?php _e('Gallery', 'ensemble'); ?></h2>
                    <?php if (function_exists('ensemble_location_gallery')) ensemble_location_gallery($location_id); ?>
                </section>
                <?php endif; ?>
                
                <!-- Map -->
                <?php if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_map')): ?>
                <section class="es-pure-section es-pure-map">
                    <h2 class="es-pure-section-title"><?php _e('Location', 'ensemble'); ?></h2>
                    <div class="es-pure-map-wrapper">
                        <?php if (function_exists('ensemble_location_map')) ensemble_location_map($location_id); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Upcoming Events -->
                <?php if (!empty($upcoming_events)): ?>
                <section class="es-pure-section">
                    <h2 class="es-pure-section-title"><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                    <div class="es-pure-events-list">
                        <?php foreach ($upcoming_events as $event): ?>
                        <a href="<?php echo esc_url($event['permalink']); ?>" class="es-pure-event-item">
                            <span class="es-pure-event-date"><?php echo esc_html($event['date_formatted']); ?></span>
                            <span class="es-pure-event-title"><?php echo esc_html($event['title']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
            </main>
            
            <!-- SIDEBAR -->
            <aside class="es-pure-sidebar">
                
                <!-- Opening Hours -->
                <?php if ($has_opening_hours): ?>
                <div class="es-pure-sidebar-block es-pure-hours-block">
                    <div class="es-pure-hours-header">
                        <h3 class="es-pure-sidebar-title"><?php _e('Opening Hours', 'ensemble'); ?></h3>
                        <span class="es-pure-status-small <?php echo $is_open ? 'es-pure-status-open' : 'es-pure-status-closed'; ?>">
                            <?php echo $is_open ? __('Open', 'ensemble') : __('Closed', 'ensemble'); ?>
                        </span>
                    </div>
                    <div class="es-pure-hours-grid">
                        <?php foreach ($day_names as $day_key => $day_label): ?>
                        <?php 
                        $day_data = $opening_hours[$day_key] ?? array();
                        $is_today = ($current_day === $day_key);
                        $is_closed = !empty($day_data['closed']);
                        ?>
                        <div class="es-pure-hours-row <?php echo $is_today ? 'es-pure-hours-today' : ''; ?>">
                            <span class="es-pure-hours-day"><?php echo esc_html($day_label); ?></span>
                            <span class="es-pure-hours-time">
                                <?php if ($is_closed): ?>
                                    <?php _e('Closed', 'ensemble'); ?>
                                <?php elseif (!empty($day_data['open']) && !empty($day_data['close'])): ?>
                                    <?php echo esc_html($day_data['open']); ?> – <?php echo esc_html($day_data['close']); ?>
                                <?php else: ?>
                                    –
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Location Details -->
                <div class="es-pure-sidebar-block">
                    <h3 class="es-pure-sidebar-title"><?php _e('Details', 'ensemble'); ?></h3>
                    
                    <div class="es-pure-details-list">
                        <?php if (!empty($location['address'])): ?>
                        <div class="es-pure-detail-item">
                            <div class="es-pure-detail-label"><?php _e('Address', 'ensemble'); ?></div>
                            <div class="es-pure-detail-value">
                                <?php echo esc_html($location['address']); ?>
                                <?php if (!empty($location['zip']) || !empty($location['city'])): ?>
                                <br><?php echo esc_html(trim(($location['zip'] ?? '') . ' ' . ($location['city'] ?? ''))); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['phone'])): ?>
                        <div class="es-pure-detail-item">
                            <div class="es-pure-detail-label"><?php _e('Phone', 'ensemble'); ?></div>
                            <div class="es-pure-detail-value">
                                <a href="tel:<?php echo esc_attr($location['phone']); ?>"><?php echo esc_html($location['phone']); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['email'])): ?>
                        <div class="es-pure-detail-item">
                            <div class="es-pure-detail-label"><?php _e('Email', 'ensemble'); ?></div>
                            <div class="es-pure-detail-value">
                                <a href="mailto:<?php echo esc_attr($location['email']); ?>"><?php echo esc_html($location['email']); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['capacity'])): ?>
                        <div class="es-pure-detail-item">
                            <div class="es-pure-detail-label"><?php _e('Capacity', 'ensemble'); ?></div>
                            <div class="es-pure-detail-value"><?php echo esc_html($location['capacity']); ?> <?php _e('Persons', 'ensemble'); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (function_exists('ensemble_location_sidebar')) ensemble_location_sidebar($location_id); ?>
                
            </aside>
            
        </div>
    </div>
    
    <?php if (function_exists('ensemble_after_location')) ensemble_after_location($location_id); ?>

</article>
</div>

<style>
/* Pure Single Location Additions */
.es-pure-status-wrapper {
    margin-top: 12px;
}

.es-pure-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border: 1px solid var(--pure-border);
    font-size: var(--pure-xs);
    font-weight: var(--pure-weight-medium);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.es-pure-status-open {
    border-color: #22c55e;
    color: #22c55e;
}

.es-pure-status-closed {
    border-color: var(--pure-text-muted);
    color: var(--pure-text-muted);
}

.es-pure-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.es-pure-hours-block {
    border: 1px solid var(--pure-border);
    padding: 20px;
}

.es-mode-dark .es-pure-hours-block {
    border-color: var(--pure-border);
}

.es-pure-hours-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--pure-border);
}

.es-pure-hours-header .es-pure-sidebar-title {
    margin: 0;
}

.es-pure-status-small {
    padding: 3px 8px;
    font-size: 10px;
    font-weight: var(--pure-weight-medium);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid currentColor;
}

.es-pure-hours-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.es-pure-hours-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: var(--pure-small);
}

.es-pure-hours-today {
    background: var(--pure-primary);
    color: var(--pure-bg);
    margin: 0 -20px;
    padding: 6px 20px;
}

.es-pure-hours-day {
    font-weight: var(--pure-weight-medium);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.es-pure-hours-time {
    color: var(--pure-text-secondary);
}

.es-pure-hours-today .es-pure-hours-time {
    color: var(--pure-bg);
}

.es-pure-details-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.es-pure-detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.es-pure-detail-label {
    font-size: var(--pure-xs);
    color: var(--pure-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.es-pure-detail-value {
    font-size: var(--pure-body);
    color: var(--pure-text);
}

.es-pure-detail-value a {
    color: var(--pure-primary);
    text-decoration: none;
}

.es-pure-detail-value a:hover {
    text-decoration: underline;
}

.es-pure-map-wrapper {
    border: 1px solid var(--pure-border);
    overflow: hidden;
}

.es-pure-map-wrapper iframe {
    display: block;
    width: 100%;
    min-height: 300px;
}

.es-pure-events-list {
    display: flex;
    flex-direction: column;
}

.es-pure-event-item {
    display: flex;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid var(--pure-border);
    text-decoration: none;
    color: var(--pure-text);
    transition: var(--pure-transition);
}

.es-pure-event-item:hover {
    background: var(--pure-surface);
    margin: 0 -16px;
    padding: 16px;
}

.es-pure-event-item:last-child {
    border-bottom: none;
}

.es-pure-event-date {
    flex-shrink: 0;
    width: 100px;
    font-size: var(--pure-small);
    color: var(--pure-text-muted);
}

.es-pure-event-title {
    font-weight: var(--pure-weight-medium);
}

@media (max-width: 768px) {
    .es-pure-hours-today {
        margin: 0 -16px;
        padding: 6px 16px;
    }
    
    .es-pure-hours-block {
        padding: 16px;
    }
}
</style>

<?php get_footer(); ?>
