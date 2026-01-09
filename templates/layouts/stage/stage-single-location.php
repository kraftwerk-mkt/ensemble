<?php
/**
 * Single Location Template - STAGE LAYOUT
 * Bold, theatrical design with sharp edges
 * 
 * @package Ensemble
 * @version 2.4.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Load styles
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-layout-stage', ENSEMBLE_PLUGIN_URL . 'templates/layouts/stage/style.css', array('ensemble-base'), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-stage-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Oswald:wght@400;500;600;700&display=swap', array(), ENSEMBLE_VERSION);

$location_id = get_the_ID();
$location = function_exists('es_load_location_data') ? es_load_location_data($location_id) : array();

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
        // Handle overnight hours (e.g., 22:00 - 04:00)
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

<div class="ensemble-single-location-wrapper es-layout-stage es-stage-layout">
    
    <?php if (function_exists('ensemble_before_location')) ensemble_before_location($location_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <article class="es-location-stage">
        
        <!-- HERO -->
        <header class="es-stage-hero" style="height: 400px;">
            <?php if (has_post_thumbnail()): ?>
            <div class="es-stage-hero-image">
                <?php the_post_thumbnail('full'); ?>
            </div>
            <?php endif; ?>
            
            <div class="es-stage-hero-content">
                <?php if (!empty($location['location_type'])): ?>
                <div class="es-stage-hero-tag"><?php echo esc_html($location['location_type']); ?></div>
                <?php endif; ?>
                
                <h1 class="es-stage-hero-title"><?php the_title(); ?></h1>
                
                <?php if (!empty($location['city'])): ?>
                <div class="es-stage-hero-date">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.8;">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                    <span><?php echo esc_html($location['city']); ?><?php if (!empty($location['country'])): ?>, <?php echo esc_html($location['country']); ?><?php endif; ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Open/Closed Status in Hero -->
                <?php if ($has_opening_hours): ?>
                <div class="es-stage-hero-status">
                    <span class="es-stage-status-pill <?php echo $is_open ? 'es-stage-status-open' : 'es-stage-status-closed'; ?>">
                        <span class="es-stage-status-dot"></span>
                        <?php echo $is_open ? __('Geöffnet', 'ensemble') : __('Geschlossen', 'ensemble'); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- CONTENT -->
        <div class="es-stage-content-wrapper">
            <div class="es-stage-main">
                
                <?php if (get_the_content()): ?>
                <section class="es-stage-section">
                    <div class="es-stage-section-header">
                        <h2><?php _e('About', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-stage-description">
                        <?php the_content(); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Gallery Section -->
                <?php if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_gallery')): ?>
                <section class="es-stage-section es-stage-gallery-section">
                    <div class="es-stage-section-header">
                        <h2><?php _e('Gallery', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-stage-gallery-wrapper">
                        <?php if (function_exists('ensemble_location_gallery')) ensemble_location_gallery($location_id); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Map Section -->
                <?php if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_map')): ?>
                <section class="es-stage-section es-stage-map-section">
                    <div class="es-stage-section-header">
                        <h2><?php _e('Location', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-stage-map-wrapper">
                        <?php if (function_exists('ensemble_location_map')) ensemble_location_map($location_id); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Upcoming Events -->
                <?php if (!empty($location['upcoming_events'])): ?>
                <section class="es-stage-section">
                    <div class="es-stage-section-header">
                        <h2><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-stage-related-grid">
                        <?php foreach ($location['upcoming_events'] as $event): ?>
                        <div class="es-stage-card es-stage-card-dark">
                            <a href="<?php echo esc_url($event['permalink']); ?>" class="es-stage-card-inner">
                                <?php if (!empty($event['image'])): ?>
                                <div class="es-stage-card-image">
                                    <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                                </div>
                                <?php endif; ?>
                                <div class="es-stage-card-content">
                                    <time class="es-stage-date">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.6;">
                                            <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/>
                                        </svg>
                                        <span><?php echo esc_html($event['date_formatted']); ?></span>
                                    </time>
                                    <h3 class="es-stage-title"><?php echo esc_html($event['title']); ?></h3>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
            </div>
            
            <!-- SIDEBAR -->
            <aside class="es-stage-sidebar">
                
                <!-- Opening Hours Card -->
                <?php if ($has_opening_hours): ?>
                <div class="es-stage-sidebar-card es-stage-hours-card">
                    <div class="es-stage-hours-header">
                        <h3><?php _e('Opening Hours', 'ensemble'); ?></h3>
                        <span class="es-stage-status-pill-small <?php echo $is_open ? 'es-stage-status-open' : 'es-stage-status-closed'; ?>">
                            <?php echo $is_open ? __('Open', 'ensemble') : __('Closed', 'ensemble'); ?>
                        </span>
                    </div>
                    <div class="es-stage-hours-grid">
                        <?php foreach ($day_names as $day_key => $day_label): ?>
                        <?php 
                        $day_data = $opening_hours[$day_key] ?? array();
                        $is_today = ($current_day === $day_key);
                        $is_closed = !empty($day_data['closed']);
                        ?>
                        <div class="es-stage-hours-row <?php echo $is_today ? 'es-stage-hours-today' : ''; ?>">
                            <span class="es-stage-hours-day"><?php echo esc_html($day_label); ?></span>
                            <span class="es-stage-hours-time">
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
                    <?php if (!empty($location['opening_note'])): ?>
                    <div class="es-stage-hours-note">
                        <?php echo esc_html($location['opening_note']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Contact Info Card -->
                <div class="es-stage-sidebar-card">
                    <div class="es-stage-meta-list">
                        
                        <?php if (!empty($location['address'])): ?>
                        <div class="es-stage-meta-row">
                            <div class="es-stage-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                            </div>
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Address', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value">
                                    <?php echo esc_html($location['address']); ?>
                                    <?php if (!empty($location['zip_code']) || !empty($location['city'])): ?>
                                    <br><?php echo esc_html(trim(($location['zip_code'] ?? '') . ' ' . ($location['city'] ?? ''))); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['phone'])): ?>
                        <div class="es-stage-meta-row">
                            <div class="es-stage-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                </svg>
                            </div>
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Phone', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value">
                                    <a href="tel:<?php echo esc_attr($location['phone']); ?>" style="color: var(--stage-primary);">
                                        <?php echo esc_html($location['phone']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['email'])): ?>
                        <div class="es-stage-meta-row">
                            <div class="es-stage-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                            </div>
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Email', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value">
                                    <a href="mailto:<?php echo esc_attr($location['email']); ?>" style="color: var(--stage-primary);">
                                        <?php echo esc_html($location['email']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['website'])): ?>
                        <div class="es-stage-meta-row">
                            <div class="es-stage-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                </svg>
                            </div>
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Website', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value">
                                    <a href="<?php echo esc_url($location['website']); ?>" target="_blank" rel="noopener" style="color: var(--stage-primary);">
                                        <?php echo esc_html(preg_replace('#^https?://#', '', $location['website'])); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['capacity'])): ?>
                        <div class="es-stage-meta-row">
                            <div class="es-stage-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                                </svg>
                            </div>
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Capacity', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value"><?php echo esc_html($location['capacity']); ?> <?php _e('Persons', 'ensemble'); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <!-- Get Directions Button -->
                    <?php if (!empty($location['address']) && !empty($location['city'])): ?>
                    <div class="es-stage-directions-btn-wrapper">
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($location['address'] . ', ' . ($location['zip_code'] ?? '') . ' ' . $location['city']); ?>" 
                           target="_blank" 
                           rel="noopener" 
                           class="es-stage-directions-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.38.39-1.01 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/>
                            </svg>
                            <?php _e('Get Directions', 'ensemble'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (function_exists('ensemble_location_sidebar')) ensemble_location_sidebar($location_id); ?>
            </aside>
        </div>
        
    </article>
    
    <?php endwhile; endif; ?>
    
    <?php if (function_exists('ensemble_after_location')) ensemble_after_location($location_id); ?>
    
</div>

<style>
/* Stage Single Location Additions */
.es-stage-hero-status {
    margin-top: 16px;
}

.es-stage-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    font-family: var(--stage-font-heading);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.es-stage-status-open {
    background: #22c55e;
    color: #fff;
}

.es-stage-status-closed {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.es-stage-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.es-stage-hours-card {
    background: var(--stage-surface);
    padding: 20px;
}

.es-stage-hours-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--stage-primary);
}

.es-stage-hours-header h3 {
    margin: 0;
    font-family: var(--stage-font-heading);
    font-size: 16px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.es-stage-status-pill-small {
    padding: 4px 10px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.es-stage-status-pill-small.es-stage-status-open {
    background: #22c55e;
    color: #fff;
}

.es-stage-status-pill-small.es-stage-status-closed {
    background: var(--stage-text-muted);
    color: #fff;
}

.es-stage-hours-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.es-stage-hours-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--stage-border);
}

.es-stage-hours-row:last-child {
    border-bottom: none;
}

.es-stage-hours-today {
    background: var(--stage-primary);
    color: var(--stage-btn-text);
    margin: 0 -20px;
    padding: 8px 20px;
}

.es-stage-hours-day {
    font-family: var(--stage-font-heading);
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.es-stage-hours-time {
    font-size: 14px;
    color: var(--stage-text-secondary);
}

.es-stage-hours-today .es-stage-hours-time {
    color: var(--stage-btn-text);
}

.es-stage-hours-note {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--stage-border);
    font-size: 13px;
    color: var(--stage-text-muted);
    font-style: italic;
}

.es-stage-map-section .es-stage-map-wrapper {
    margin-top: 16px;
    border: 2px solid var(--stage-border);
    overflow: hidden;
}

.es-stage-map-wrapper iframe {
    display: block;
    width: 100%;
    min-height: 300px;
}

.es-stage-gallery-section .es-stage-gallery-wrapper {
    margin-top: 16px;
}

.es-stage-meta-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: var(--stage-primary);
    color: var(--stage-btn-text);
    flex-shrink: 0;
}

.es-stage-meta-icon svg {
    width: 18px;
    height: 18px;
}

.es-stage-meta-row {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--stage-border);
}

.es-stage-meta-row:last-child {
    border-bottom: none;
}

.es-stage-directions-btn-wrapper {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--stage-border);
}

.es-stage-directions-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px 20px;
    background: var(--stage-primary);
    color: var(--stage-btn-text);
    font-family: var(--stage-font-heading);
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.es-stage-directions-btn:hover {
    background: var(--stage-hover);
    transform: translateY(-2px);
}

.es-stage-card-dark {
    background: var(--stage-overlay-bg);
    color: var(--stage-overlay-text);
}

.es-stage-card-dark .es-stage-date {
    color: var(--stage-overlay-text-secondary);
}

@media (max-width: 768px) {
    .es-stage-hours-today {
        margin: 0 -16px;
        padding: 8px 16px;
    }
    
    .es-stage-hours-card {
        padding: 16px;
    }
}
</style>

<?php get_footer(); ?>
