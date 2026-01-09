<?php
/**
 * Single Location Template - CLUB LAYOUT
 * 
 * Dark nightlife style with bold typography
 * High contrast with vibrant accents
 *
 * @package Ensemble
 * @version 2.2.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue styles
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-layout-club', ENSEMBLE_PLUGIN_URL . 'templates/layouts/club/style.css', array('ensemble-base'), ENSEMBLE_VERSION);

$location_id = get_the_ID();
$location = es_load_location_data($location_id);
$upcoming_events = es_get_upcoming_events_at_location($location_id);

// Address data
$address_data = array(
    'address' => !empty($location['address']) ? $location['address'] : '',
    'city' => !empty($location['city']) ? $location['city'] : '',
    'state' => !empty($location['state']) ? $location['state'] : '',
    'zip' => !empty($location['zip']) ? $location['zip'] : '',
    'country' => !empty($location['country']) ? $location['country'] : '',
);

// Contact data
$contact_data = array(
    'phone' => !empty($location['phone']) ? $location['phone'] : '',
    'email' => !empty($location['email']) ? $location['email'] : '',
    'website' => !empty($location['website']) ? $location['website'] : '',
);

// Opening hours
$opening_hours = !empty($location['opening_hours']) ? $location['opening_hours'] : array();
$opening_note = !empty($location['opening_note']) ? $location['opening_note'] : '';

// Check if currently open
$is_open = false;
$current_day = strtolower(date('l'));
$current_time = date('H:i');

if (!empty($opening_hours[$current_day]) && empty($opening_hours[$current_day]['closed'])) {
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
    'monday' => __('MO', 'ensemble'),
    'tuesday' => __('DI', 'ensemble'),
    'wednesday' => __('MI', 'ensemble'),
    'thursday' => __('DO', 'ensemble'),
    'friday' => __('FR', 'ensemble'),
    'saturday' => __('SA', 'ensemble'),
    'sunday' => __('SO', 'ensemble'),
);
?>

<div class="es-club-single es-club-location-single">
    
    <?php if (function_exists('ensemble_before_location')) ensemble_before_location($location_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- HERO SECTION -->
    <header class="es-club-hero es-club-hero-location">
        <div class="es-club-hero-bg">
            <?php if (has_post_thumbnail()): ?>
                <?php the_post_thumbnail('full'); ?>
            <?php else: ?>
                <div class="es-club-placeholder"></div>
            <?php endif; ?>
            <div class="es-club-hero-overlay"></div>
        </div>
        
        <div class="es-club-hero-content">
            <?php if (!empty($location['city'])): ?>
            <span class="es-club-hero-tag">
                <?php echo esc_html($location['city']); ?>
                <?php if (!empty($location['country'])) echo ' · ' . esc_html($location['country']); ?>
            </span>
            <?php endif; ?>
            
            <h1 class="es-club-hero-title"><?php the_title(); ?></h1>
            
            <div class="es-club-hero-stats">
                <?php if (!empty($location['capacity'])): ?>
                <span class="es-club-stat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <?php echo number_format_i18n($location['capacity']); ?>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($opening_hours)): ?>
                <span class="es-club-stat es-club-stat-<?php echo $is_open ? 'open' : 'closed'; ?>">
                    <span class="es-club-status-dot"></span>
                    <?php echo $is_open ? __('OPEN', 'ensemble') : __('CLOSED', 'ensemble'); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <?php if (function_exists('ensemble_after_location_title')) ensemble_after_location_title($location_id); ?>
        </div>
    </header>
    
    <!-- CONTENT -->
    <div class="es-club-container">
        <div class="es-club-location-grid">
            
            <!-- Main Content -->
            <main class="es-club-main">
                
                <!-- Description -->
                <?php if (get_the_content()): ?>
                <section class="es-club-section">
                    <?php if (function_exists('ensemble_before_location_content')) ensemble_before_location_content($location_id); ?>
                    <h2 class="es-club-section-title"><?php _e('ABOUT', 'ensemble'); ?></h2>
                    <div class="es-club-prose">
                        <?php the_content(); ?>
                    </div>
                    <?php if (function_exists('ensemble_after_location_description')) ensemble_after_location_description($location_id); ?>
                </section>
                <?php endif; ?>
                
                <!-- Map (Addon Hook) -->
                <?php 
                if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_map')) {
                    echo '<section class="es-club-section es-club-map-section">';
                    echo '<h2 class="es-club-section-title">' . __('LOCATION', 'ensemble') . '</h2>';
                    echo '<div class="es-club-map-wrapper">';
                    if (function_exists('ensemble_location_map')) ensemble_location_map($location_id, $address_data);
                    echo '</div>';
                    echo '</section>';
                }
                ?>
                
                <!-- Gallery (Addon Hook) -->
                <?php 
                if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_gallery')) {
                    echo '<section class="es-club-section es-club-gallery">';
                    echo '<h2 class="es-club-section-title">' . __('GALLERY', 'ensemble') . '</h2>';
                    if (function_exists('ensemble_location_gallery')) ensemble_location_gallery($location_id);
                    echo '</section>';
                }
                ?>
                
                <!-- Upcoming Events -->
                <?php if ($upcoming_events && $upcoming_events->have_posts()): ?>
                <section class="es-club-section es-club-events-section">
                    <h2 class="es-club-section-title"><?php _e('UPCOMING EVENTS', 'ensemble'); ?></h2>
                    
                    <div class="es-club-events-grid">
                        <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); 
                            $evt = es_load_event_data(get_the_ID());
                            $evt_timestamp = !empty($evt['date']) ? strtotime($evt['date']) : false;
                        ?>
                        <a href="<?php echo esc_url($evt['permalink']); ?>" class="es-club-event-card">
                            <div class="es-club-event-card-date">
                                <?php if ($evt_timestamp): ?>
                                <span class="es-club-card-day"><?php echo date_i18n('d', $evt_timestamp); ?></span>
                                <span class="es-club-card-month"><?php echo date_i18n('M', $evt_timestamp); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="es-club-event-card-content">
                                <h4 class="es-club-event-card-title"><?php echo esc_html($evt['title']); ?></h4>
                                <?php if (!empty($evt['formatted_time'])): ?>
                                <span class="es-club-event-card-time"><?php echo esc_html($evt['formatted_time']); ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="es-club-event-card-arrow">→</span>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </section>
                <?php endif; wp_reset_postdata(); ?>
                
            </main>
            
            <!-- Sidebar -->
            <aside class="es-club-sidebar">
                
                <!-- Contact Info -->
                <div class="es-club-info-card">
                    <h3 class="es-club-card-title"><?php _e('INFO', 'ensemble'); ?></h3>
                    
                    <?php if (!empty($location['address']) || !empty($location['city'])): ?>
                    <div class="es-club-info-item">
                        <span class="es-club-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </span>
                        <div class="es-club-info-text">
                            <?php if (!empty($location['address'])): ?>
                                <?php echo esc_html($location['address']); ?><br>
                            <?php endif; ?>
                            <?php echo esc_html(trim(($location['zip'] ?? '') . ' ' . ($location['city'] ?? ''))); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['phone'])): ?>
                    <div class="es-club-info-item">
                        <span class="es-club-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </span>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $location['phone'])); ?>" class="es-club-info-link">
                            <?php echo esc_html($location['phone']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['email'])): ?>
                    <div class="es-club-info-item">
                        <span class="es-club-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <a href="mailto:<?php echo esc_attr($location['email']); ?>" class="es-club-info-link">
                            <?php echo esc_html($location['email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['website'])): ?>
                    <a href="<?php echo esc_url($location['website']); ?>" class="es-club-btn" target="_blank" rel="noopener">
                        <?php _e('WEBSITE', 'ensemble'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15 3 21 3 21 9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Opening Hours -->
                <?php if (!empty($opening_hours)): ?>
                <div class="es-club-info-card es-club-hours-card">
                    <h3 class="es-club-card-title">
                        <?php _e('HOURS', 'ensemble'); ?>
                        <span class="es-club-status-pill <?php echo $is_open ? 'es-club-pill-open' : 'es-club-pill-closed'; ?>">
                            <?php echo $is_open ? __('OPEN', 'ensemble') : __('CLOSED', 'ensemble'); ?>
                        </span>
                    </h3>
                    
                    <div class="es-club-hours-grid">
                        <?php foreach ($day_names as $day_key => $day_label): 
                            $day_data = !empty($opening_hours[$day_key]) ? $opening_hours[$day_key] : null;
                            $is_today = ($day_key === $current_day);
                            $is_closed = empty($day_data) || !empty($day_data['closed']);
                        ?>
                        <div class="es-club-hours-row <?php echo $is_today ? 'es-club-hours-today' : ''; ?>">
                            <span class="es-club-hours-day"><?php echo esc_html($day_label); ?></span>
                            <span class="es-club-hours-time <?php echo $is_closed ? 'es-club-hours-closed' : ''; ?>">
                                <?php if ($is_closed): ?>
                                    –
                                <?php else: ?>
                                    <?php echo esc_html($day_data['open'] ?? ''); ?>–<?php echo esc_html($day_data['close'] ?? ''); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($opening_note)): ?>
                    <div class="es-club-hours-note">
                        <?php echo esc_html($opening_note); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            </aside>
            
        </div>
        
        <?php if (function_exists('ensemble_location_footer')) ensemble_location_footer($location_id); ?>
        
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php if (function_exists('ensemble_after_location_hook')) ensemble_after_location_hook($location_id, $location); ?>
    
</div>

<!-- Club Single Location Styles -->
<style>
.es-club-location-single {
    background: var(--club-bg, #0a0a0a);
    color: var(--club-text, #ffffff);
    min-height: 100vh;
}

.es-club-hero-location {
    min-height: 50vh;
}

.es-club-hero-stats {
    display: flex;
    gap: 24px;
    margin-top: 20px;
}

.es-club-stat {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--club-text-secondary, #999);
}

.es-club-stat svg {
    color: var(--club-primary, #ff3366);
}

.es-club-stat-open {
    color: #22cc66;
}

.es-club-stat-closed {
    color: var(--club-text-secondary, #999);
}

.es-club-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
}

/* Location Grid */
.es-club-location-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 48px;
}

/* Map */
.es-club-map-wrapper {
    background: var(--club-card-bg, #111);
    border: 1px solid var(--club-card-border, #222);
    overflow: hidden;
}

.es-club-map-wrapper iframe,
.es-club-map-wrapper .es-map-embed {
    display: block;
    width: 100%;
    min-height: 300px;
}

/* Events Grid */
.es-club-events-grid {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.es-club-event-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 16px 20px;
    background: var(--club-card-bg, #111);
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.es-club-event-card:hover {
    background: var(--club-surface-hover, #1a1a1a);
    transform: translateX(8px);
}

.es-club-event-card-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 50px;
    padding: 10px;
    background: var(--club-primary, #ff3366);
}

.es-club-card-day {
    font-size: 22px;
    font-weight: 800;
    line-height: 1;
}

.es-club-card-month {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    opacity: 0.8;
}

.es-club-event-card-content {
    flex: 1;
}

.es-club-event-card-title {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 4px;
    color: var(--club-text, #fff);
}

.es-club-event-card-time {
    font-size: 13px;
    color: var(--club-text-secondary, #999);
}

.es-club-event-card-arrow {
    font-size: 20px;
    color: var(--club-text-secondary, #666);
    transition: all 0.3s ease;
}

.es-club-event-card:hover .es-club-event-card-arrow {
    color: var(--club-primary, #ff3366);
    transform: translateX(4px);
}

/* Info Card */
.es-club-info-card {
    background: var(--club-card-bg, #111);
    border: 1px solid var(--club-card-border, #222);
    padding: 24px;
    margin-bottom: 24px;
}

.es-club-info-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--club-card-border, #222);
}

.es-club-info-item:last-of-type {
    border-bottom: none;
}

.es-club-info-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    color: var(--club-primary, #ff3366);
}

.es-club-info-icon svg {
    width: 100%;
    height: 100%;
}

.es-club-info-text {
    font-size: 14px;
    line-height: 1.5;
    color: var(--club-text-secondary, #999);
}

.es-club-info-link {
    font-size: 14px;
    color: var(--club-text, #fff);
    text-decoration: none;
    transition: color 0.3s ease;
}

.es-club-info-link:hover {
    color: var(--club-primary, #ff3366);
}

.es-club-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    margin-top: 16px;
    padding: 14px 24px;
    background: var(--club-primary, #ff3366);
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.3s ease;
}

.es-club-btn:hover {
    background: var(--club-hover, #ff4d7a);
    transform: translateY(-2px);
}

/* Hours Card */
.es-club-hours-card .es-club-card-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.es-club-status-pill {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.1em;
    padding: 4px 10px;
}

.es-club-pill-open {
    background: rgba(34, 204, 102, 0.2);
    color: #22cc66;
}

.es-club-pill-closed {
    background: rgba(153, 153, 153, 0.2);
    color: var(--club-text-secondary, #999);
}

.es-club-hours-grid {
    display: flex;
    flex-direction: column;
}

.es-club-hours-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--club-card-border, #222);
}

.es-club-hours-row:last-child {
    border-bottom: none;
}

.es-club-hours-today {
    background: rgba(255, 51, 102, 0.1);
    margin: 0 -24px;
    padding: 8px 24px;
}

.es-club-hours-day {
    font-size: 13px;
    font-weight: 700;
    color: var(--club-text, #fff);
}

.es-club-hours-time {
    font-size: 13px;
    font-weight: 600;
    color: var(--club-primary, #ff3366);
}

.es-club-hours-closed {
    color: var(--club-text-muted, #666);
}

.es-club-hours-note {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--club-card-border, #222);
    font-size: 12px;
    font-style: italic;
    color: var(--club-text-secondary, #999);
}

/* Responsive */
@media (max-width: 900px) {
    .es-club-location-grid {
        grid-template-columns: 1fr;
    }
    
    .es-club-sidebar {
        order: -1;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    
    .es-club-info-card {
        margin-bottom: 0;
    }
}

@media (max-width: 640px) {
    .es-club-container {
        padding: 40px 16px;
    }
    
    .es-club-sidebar {
        grid-template-columns: 1fr;
    }
    
    .es-club-hours-today {
        margin: 0 -24px;
        padding: 8px 24px;
    }
}
</style>

<?php get_footer(); ?>
