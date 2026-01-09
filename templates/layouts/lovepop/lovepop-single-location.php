<?php
/**
 * Single Location Template - LOVEPOP LAYOUT
 * Vibrant gradient design with bold typography
 * 
 * @package Ensemble
 * @version 2.4.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Load styles
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-layout-lovepop', ENSEMBLE_PLUGIN_URL . 'templates/layouts/lovepop/style.css', array('ensemble-base'), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-lovepop-font', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap', array(), ENSEMBLE_VERSION);

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
?>

<div class="ensemble-single-location-wrapper es-layout-lovepop es-lovepop-layout">
    
    <?php if (function_exists('ensemble_before_location')) ensemble_before_location($location_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <article class="es-location-lovepop">
        
        <!-- HERO -->
        <header class="es-lovepop-hero" style="height: 400px;">
            <?php if (has_post_thumbnail()): ?>
            <div class="es-lovepop-hero-image">
                <?php the_post_thumbnail('full'); ?>
            </div>
            <?php endif; ?>
            
            <div class="es-lovepop-hero-content">
                <?php if (!empty($location['location_type'])): ?>
                <div class="es-lovepop-hero-tag"><?php echo esc_html($location['location_type']); ?></div>
                <?php endif; ?>
                
                <h1 class="es-lovepop-hero-title"><?php the_title(); ?></h1>
                
                <?php if (!empty($location['city'])): ?>
                <div class="es-lovepop-hero-meta">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                    <span><?php echo esc_html($location['city']); ?><?php if (!empty($location['country'])): ?>, <?php echo esc_html($location['country']); ?><?php endif; ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Open/Closed Status -->
                <?php if ($has_opening_hours): ?>
                <div class="es-lovepop-hero-status">
                    <span class="es-lovepop-status-pill <?php echo $is_open ? 'es-lovepop-status-open' : 'es-lovepop-status-closed'; ?>">
                        <span class="es-lovepop-status-dot"></span>
                        <?php echo $is_open ? __('Geöffnet', 'ensemble') : __('Geschlossen', 'ensemble'); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- CONTENT -->
        <div class="es-lovepop-content-wrapper">
            <div class="es-lovepop-main">
                
                <?php if (get_the_content()): ?>
                <section class="es-lovepop-section">
                    <div class="es-lovepop-section-header">
                        <h2><?php _e('About', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-lovepop-description">
                        <?php the_content(); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Gallery -->
                <?php if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_gallery')): ?>
                <section class="es-lovepop-section es-lovepop-gallery">
                    <div class="es-lovepop-section-header">
                        <h2><?php _e('Gallery', 'ensemble'); ?></h2>
                    </div>
                    <?php if (function_exists('ensemble_location_gallery')) ensemble_location_gallery($location_id); ?>
                </section>
                <?php endif; ?>
                
                <!-- Map -->
                <?php if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_map')): ?>
                <section class="es-lovepop-section es-lovepop-map">
                    <div class="es-lovepop-section-header">
                        <h2><?php _e('Location', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-lovepop-map-wrapper">
                        <?php if (function_exists('ensemble_location_map')) ensemble_location_map($location_id); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Upcoming Events -->
                <?php if (!empty($upcoming_events)): ?>
                <section class="es-lovepop-section">
                    <div class="es-lovepop-section-header">
                        <h2><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-lovepop-related-grid">
                        <?php foreach ($upcoming_events as $event): ?>
                        <div class="es-lovepop-card">
                            <a href="<?php echo esc_url($event['permalink']); ?>" class="es-lovepop-card-inner">
                                <?php if (!empty($event['image'])): ?>
                                <div class="es-lovepop-card-image">
                                    <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                                </div>
                                <?php endif; ?>
                                <div class="es-lovepop-card-content">
                                    <time class="es-lovepop-date">
                                        <span><?php echo esc_html($event['date_formatted']); ?></span>
                                    </time>
                                    <h3 class="es-lovepop-title"><?php echo esc_html($event['title']); ?></h3>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
            </div>
            
            <!-- SIDEBAR -->
            <aside class="es-lovepop-sidebar">
                
                <!-- Opening Hours Card -->
                <?php if ($has_opening_hours): ?>
                <div class="es-lovepop-sidebar-card es-lovepop-hours-card">
                    <div class="es-lovepop-hours-header">
                        <h3><?php _e('Opening Hours', 'ensemble'); ?></h3>
                        <span class="es-lovepop-status-small <?php echo $is_open ? 'es-lovepop-status-open' : 'es-lovepop-status-closed'; ?>">
                            <?php echo $is_open ? __('Open', 'ensemble') : __('Closed', 'ensemble'); ?>
                        </span>
                    </div>
                    <div class="es-lovepop-hours-grid">
                        <?php foreach ($day_names as $day_key => $day_label): ?>
                        <?php 
                        $day_data = $opening_hours[$day_key] ?? array();
                        $is_today = ($current_day === $day_key);
                        $is_closed = !empty($day_data['closed']);
                        ?>
                        <div class="es-lovepop-hours-row <?php echo $is_today ? 'es-lovepop-hours-today' : ''; ?>">
                            <span class="es-lovepop-hours-day"><?php echo esc_html($day_label); ?></span>
                            <span class="es-lovepop-hours-time">
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
                
                <!-- Contact Card -->
                <div class="es-lovepop-sidebar-card">
                    <div class="es-lovepop-meta-list">
                        
                        <?php if (!empty($location['address'])): ?>
                        <div class="es-lovepop-meta-row">
                            <div class="es-lovepop-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                            </div>
                            <div class="es-lovepop-meta-content">
                                <div class="es-lovepop-meta-label"><?php _e('Address', 'ensemble'); ?></div>
                                <div class="es-lovepop-meta-value">
                                    <?php echo esc_html($location['address']); ?>
                                    <?php if (!empty($location['zip']) || !empty($location['city'])): ?>
                                    <br><?php echo esc_html(trim(($location['zip'] ?? '') . ' ' . ($location['city'] ?? ''))); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['phone'])): ?>
                        <div class="es-lovepop-meta-row">
                            <div class="es-lovepop-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                </svg>
                            </div>
                            <div class="es-lovepop-meta-content">
                                <div class="es-lovepop-meta-label"><?php _e('Phone', 'ensemble'); ?></div>
                                <div class="es-lovepop-meta-value">
                                    <a href="tel:<?php echo esc_attr($location['phone']); ?>"><?php echo esc_html($location['phone']); ?></a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['email'])): ?>
                        <div class="es-lovepop-meta-row">
                            <div class="es-lovepop-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                            </div>
                            <div class="es-lovepop-meta-content">
                                <div class="es-lovepop-meta-label"><?php _e('Email', 'ensemble'); ?></div>
                                <div class="es-lovepop-meta-value">
                                    <a href="mailto:<?php echo esc_attr($location['email']); ?>"><?php echo esc_html($location['email']); ?></a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['website'])): ?>
                        <div class="es-lovepop-meta-row">
                            <div class="es-lovepop-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                </svg>
                            </div>
                            <div class="es-lovepop-meta-content">
                                <div class="es-lovepop-meta-label"><?php _e('Website', 'ensemble'); ?></div>
                                <div class="es-lovepop-meta-value">
                                    <a href="<?php echo esc_url($location['website']); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html(preg_replace('#^https?://#', '', $location['website'])); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['capacity'])): ?>
                        <div class="es-lovepop-meta-row">
                            <div class="es-lovepop-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                                </svg>
                            </div>
                            <div class="es-lovepop-meta-content">
                                <div class="es-lovepop-meta-label"><?php _e('Capacity', 'ensemble'); ?></div>
                                <div class="es-lovepop-meta-value"><?php echo esc_html($location['capacity']); ?> <?php _e('Persons', 'ensemble'); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <!-- Get Directions Button -->
                    <?php if (!empty($location['address']) && !empty($location['city'])): ?>
                    <div class="es-lovepop-directions-wrapper">
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($location['address'] . ', ' . ($location['zip'] ?? '') . ' ' . $location['city']); ?>" 
                           target="_blank" 
                           rel="noopener" 
                           class="es-lovepop-directions-btn">
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
/* Lovepop Single Location Additions */
.es-lovepop-hero-tag {
    display: inline-block;
    padding: 6px 16px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    font-size: var(--lp-xs-size);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #fff;
    margin-bottom: 12px;
}

.es-lovepop-hero-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    font-size: var(--lp-small-size);
    color: rgba(255, 255, 255, 0.8);
}

.es-lovepop-hero-status {
    margin-top: 16px;
}

.es-lovepop-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: var(--lp-xs-size);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.es-lovepop-status-open {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
}

.es-lovepop-status-closed {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.es-lovepop-status-dot {
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

.es-lovepop-hours-card {
    background: var(--lp-card-bg);
    border: 1px solid var(--lp-card-border);
    border-radius: var(--lp-radius);
    padding: 20px;
}

.es-lovepop-hours-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--lp-card-border);
}

.es-lovepop-hours-header h3 {
    margin: 0;
    font-family: var(--lp-font-heading);
    font-size: var(--lp-body-size);
    font-weight: var(--lp-heading-weight);
    color: var(--lp-text);
}

.es-lovepop-status-small {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.es-lovepop-hours-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.es-lovepop-hours-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: var(--lp-small-size);
}

.es-lovepop-hours-today {
    background: linear-gradient(135deg, rgba(var(--ensemble-primary-rgb, 233, 30, 140), 0.15), rgba(var(--ensemble-primary-rgb, 233, 30, 140), 0.08));
    margin: 0 -20px;
    padding: 8px 20px;
    border-radius: var(--lp-radius);
}

.es-lovepop-hours-day {
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--lp-text);
}

.es-lovepop-hours-time {
    color: var(--lp-text-secondary);
}

.es-lovepop-meta-row {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--lp-card-border);
}

.es-lovepop-meta-row:last-child {
    border-bottom: none;
}

.es-lovepop-meta-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--lp-primary), var(--lp-hover));
    border-radius: 50%;
    color: #fff;
    flex-shrink: 0;
}

.es-lovepop-meta-icon svg {
    width: 18px;
    height: 18px;
}

.es-lovepop-meta-content {
    flex: 1;
}

.es-lovepop-meta-label {
    font-size: var(--lp-xs-size);
    color: var(--lp-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.es-lovepop-meta-value {
    font-size: var(--lp-body-size);
    color: var(--lp-text);
}

.es-lovepop-meta-value a {
    color: var(--lp-primary);
    text-decoration: none;
}

.es-lovepop-meta-value a:hover {
    text-decoration: underline;
}

.es-lovepop-directions-wrapper {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--lp-card-border);
}

.es-lovepop-directions-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--lp-primary), var(--lp-hover));
    border-radius: 30px;
    color: #fff;
    font-family: var(--lp-font-heading);
    font-size: var(--lp-small-size);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.es-lovepop-directions-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(var(--ensemble-primary-rgb, 233, 30, 140), 0.4);
}

.es-lovepop-map-wrapper {
    border-radius: var(--lp-radius);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.es-lovepop-map-wrapper iframe {
    display: block;
    width: 100%;
    min-height: 300px;
}

.es-lovepop-card-image {
    position: relative;
    overflow: hidden;
    border-radius: var(--lp-radius) var(--lp-radius) 0 0;
}

.es-lovepop-card-image img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.es-lovepop-card:hover .es-lovepop-card-image img {
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .es-lovepop-hours-today {
        margin: 0 -16px;
        padding: 8px 16px;
    }
    
    .es-lovepop-hours-card {
        padding: 16px;
    }
}
</style>

<?php get_footer(); ?>
