<?php
/**
 * Single Location Template - KINKY LAYOUT
 * 
 * Dark fiery design with elegant italic typography
 * Fire-red/orange accents on near-black background
 *
 * @package Ensemble
 * @version 2.2.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue styles
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-layout-kinky', ENSEMBLE_PLUGIN_URL . 'templates/layouts/kinky/style.css', array('ensemble-base'), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-kinky-fonts', 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Lato:wght@300;400;600;700&display=swap', array(), ENSEMBLE_VERSION);

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
            // Handles midnight crossing (e.g., 22:00 - 03:00)
            $is_open = ($current_time >= $open_time || $current_time <= $close_time);
        } else {
            $is_open = ($current_time >= $open_time && $current_time <= $close_time);
        }
    }
}

$day_names = array(
    'monday' => __('Montag', 'ensemble'),
    'tuesday' => __('Dienstag', 'ensemble'),
    'wednesday' => __('Mittwoch', 'ensemble'),
    'thursday' => __('Donnerstag', 'ensemble'),
    'friday' => __('Freitag', 'ensemble'),
    'saturday' => __('Samstag', 'ensemble'),
    'sunday' => __('Sonntag', 'ensemble'),
);

// Social links
$social_links = array(
    'website' => !empty($location['website']) ? $location['website'] : '',
    'instagram' => !empty($location['instagram']) ? $location['instagram'] : '',
    'facebook' => !empty($location['facebook']) ? $location['facebook'] : '',
);
$has_social = !empty(array_filter($social_links));
?>

<div class="es-kinky-single es-kinky-location-single">
    
    <?php if (function_exists('ensemble_before_location')) ensemble_before_location($location_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- HERO SECTION -->
    <header class="es-kinky-hero es-kinky-hero-location">
        <div class="es-kinky-hero-bg">
            <?php if (has_post_thumbnail()): ?>
                <?php the_post_thumbnail('full'); ?>
            <?php else: ?>
                <div class="es-kinky-placeholder"></div>
            <?php endif; ?>
        </div>
        
        <div class="es-kinky-hero-content">
            <?php if (!empty($location['city'])): ?>
            <span class="es-kinky-hero-city">
                <?php echo esc_html($location['city']); ?>
                <?php if (!empty($location['country'])) echo ' · ' . esc_html($location['country']); ?>
            </span>
            <?php endif; ?>
            
            <h1 class="es-kinky-hero-title"><?php the_title(); ?></h1>
            
            <?php if (!empty($location['capacity'])): ?>
            <span class="es-kinky-hero-capacity">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <?php echo number_format_i18n($location['capacity']); ?> <?php _e('Kapazität', 'ensemble'); ?>
            </span>
            <?php endif; ?>
            
            <!-- Open/Closed Status -->
            <?php if (!empty($opening_hours)): ?>
            <div class="es-kinky-hero-status <?php echo $is_open ? 'es-kinky-status-open' : 'es-kinky-status-closed'; ?>">
                <span class="es-kinky-status-dot"></span>
                <?php echo $is_open ? __('Jetzt geöffnet', 'ensemble') : __('Geschlossen', 'ensemble'); ?>
            </div>
            <?php endif; ?>
            
            <?php if (function_exists('ensemble_after_location_title')) ensemble_after_location_title($location_id); ?>
        </div>
    </header>
    
    <!-- CONTENT -->
    <div class="es-kinky-container es-kinky-location-container">
        <div class="es-kinky-location-layout">
            
            <!-- Main Content -->
            <main class="es-kinky-main">
                
                <!-- Description -->
                <?php if (get_the_content()): ?>
                <section class="es-kinky-section es-kinky-description">
                    <?php if (function_exists('ensemble_before_location_content')) ensemble_before_location_content($location_id); ?>
                    <h2 class="es-kinky-section-title"><?php _e('Über uns', 'ensemble'); ?></h2>
                    <div class="es-kinky-divider"></div>
                    <div class="es-kinky-prose">
                        <?php the_content(); ?>
                    </div>
                    <?php if (function_exists('ensemble_after_location_description')) ensemble_after_location_description($location_id); ?>
                </section>
                <?php endif; ?>
                
                <!-- Map (Addon Hook) -->
                <?php 
                if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_map')) {
                    echo '<section class="es-kinky-section es-kinky-map-section">';
                    echo '<h2 class="es-kinky-section-title">' . __('Anfahrt', 'ensemble') . '</h2>';
                    echo '<div class="es-kinky-divider"></div>';
                    echo '<div class="es-kinky-map-wrapper">';
                    if (function_exists('ensemble_location_map')) ensemble_location_map($location_id, $address_data);
                    echo '</div>';
                    echo '</section>';
                }
                ?>
                
                <!-- Gallery (Addon Hook) -->
                <?php 
                if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_gallery')) {
                    echo '<section class="es-kinky-section es-kinky-gallery">';
                    echo '<h2 class="es-kinky-section-title">' . __('Gallery', 'ensemble') . '</h2>';
                    echo '<div class="es-kinky-divider"></div>';
                    if (function_exists('ensemble_location_gallery')) ensemble_location_gallery($location_id);
                    echo '</section>';
                }
                ?>
                
                <!-- Upcoming Events -->
                <?php if ($upcoming_events && $upcoming_events->have_posts()): ?>
                <section class="es-kinky-section es-kinky-events">
                    <h2 class="es-kinky-section-title"><?php _e('Kommende Events', 'ensemble'); ?></h2>
                    <div class="es-kinky-divider"></div>
                    
                    <?php if (function_exists('ensemble_location_events')) ensemble_location_events($location_id); ?>
                    
                    <div class="es-kinky-event-list">
                        <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); 
                            $evt = es_load_event_data(get_the_ID());
                            $evt_timestamp = !empty($evt['date']) ? strtotime($evt['date']) : false;
                        ?>
                        <a href="<?php echo esc_url($evt['permalink']); ?>" class="es-kinky-event-row">
                            <?php if ($evt_timestamp): ?>
                            <time class="es-kinky-event-date">
                                <span class="es-kinky-event-day"><?php echo date_i18n('d', $evt_timestamp); ?></span>
                                <span class="es-kinky-event-month"><?php echo date_i18n('M', $evt_timestamp); ?></span>
                            </time>
                            <?php endif; ?>
                            <div class="es-kinky-event-info">
                                <h4 class="es-kinky-event-name"><?php echo esc_html($evt['title']); ?></h4>
                                <?php if (!empty($evt['formatted_time'])): ?>
                                    <span class="es-kinky-event-time"><?php echo esc_html($evt['formatted_time']); ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="es-kinky-event-arrow">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </span>
                        </a>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if (function_exists('ensemble_after_location_events')) ensemble_after_location_events($location_id, $upcoming_events); ?>
                </section>
                <?php endif; wp_reset_postdata(); ?>
                
                <?php if (function_exists('ensemble_location_footer')) ensemble_location_footer($location_id); ?>
                
            </main>
            
            <!-- Sidebar -->
            <aside class="es-kinky-sidebar">
                
                <!-- Info Card -->
                <div class="es-kinky-info-card">
                    <h3 class="es-kinky-info-card-title"><?php _e('Kontakt & Info', 'ensemble'); ?></h3>
                    
                    <!-- Address -->
                    <?php if (!empty($location['address']) || !empty($location['city'])): ?>
                    <div class="es-kinky-info-row">
                        <span class="es-kinky-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </span>
                        <div class="es-kinky-info-content">
                            <span class="es-kinky-info-label"><?php _e('Adresse', 'ensemble'); ?></span>
                            <span class="es-kinky-info-value">
                                <?php if (!empty($location['address'])): ?>
                                    <?php echo esc_html($location['address']); ?><br>
                                <?php endif; ?>
                                <?php 
                                $city_line = array_filter(array(
                                    !empty($location['zip']) ? $location['zip'] : '',
                                    !empty($location['city']) ? $location['city'] : ''
                                ));
                                if ($city_line) echo esc_html(implode(' ', $city_line));
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Phone -->
                    <?php if (!empty($location['phone'])): ?>
                    <div class="es-kinky-info-row">
                        <span class="es-kinky-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </span>
                        <div class="es-kinky-info-content">
                            <span class="es-kinky-info-label"><?php _e('Telefon', 'ensemble'); ?></span>
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $location['phone'])); ?>" class="es-kinky-info-value es-kinky-info-link">
                                <?php echo esc_html($location['phone']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Email -->
                    <?php if (!empty($location['email'])): ?>
                    <div class="es-kinky-info-row">
                        <span class="es-kinky-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <div class="es-kinky-info-content">
                            <span class="es-kinky-info-label"><?php _e('E-Mail', 'ensemble'); ?></span>
                            <a href="mailto:<?php echo esc_attr($location['email']); ?>" class="es-kinky-info-value es-kinky-info-link">
                                <?php echo esc_html($location['email']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Website -->
                    <?php if (!empty($location['website'])): ?>
                    <div class="es-kinky-info-row es-kinky-info-row-button">
                        <a href="<?php echo esc_url($location['website']); ?>" class="es-kinky-button es-kinky-button-ghost" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                            <?php _e('Website besuchen', 'ensemble'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Opening Hours -->
                <?php if (!empty($opening_hours)): ?>
                <div class="es-kinky-info-card es-kinky-hours-card">
                    <h3 class="es-kinky-info-card-title">
                        <?php _e('Öffnungszeiten', 'ensemble'); ?>
                        <?php if (!empty($opening_hours)): ?>
                        <span class="es-kinky-status-badge <?php echo $is_open ? 'es-kinky-badge-open' : 'es-kinky-badge-closed'; ?>">
                            <?php echo $is_open ? __('Geöffnet', 'ensemble') : __('Geschlossen', 'ensemble'); ?>
                        </span>
                        <?php endif; ?>
                    </h3>
                    
                    <div class="es-kinky-hours-list">
                        <?php foreach ($day_names as $day_key => $day_label): 
                            $day_data = !empty($opening_hours[$day_key]) ? $opening_hours[$day_key] : null;
                            $is_today = ($day_key === $current_day);
                            $is_closed = empty($day_data) || !empty($day_data['closed']);
                        ?>
                        <div class="es-kinky-hours-row <?php echo $is_today ? 'es-kinky-hours-today' : ''; ?>">
                            <span class="es-kinky-hours-day"><?php echo esc_html($day_label); ?></span>
                            <span class="es-kinky-hours-time <?php echo $is_closed ? 'es-kinky-hours-closed' : ''; ?>">
                                <?php if ($is_closed): ?>
                                    <?php _e('Geschlossen', 'ensemble'); ?>
                                <?php else: ?>
                                    <?php echo esc_html($day_data['open'] ?? ''); ?> – <?php echo esc_html($day_data['close'] ?? ''); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($opening_note)): ?>
                    <div class="es-kinky-hours-note">
                        <?php echo esc_html($opening_note); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Social Links -->
                <?php if ($has_social): ?>
                <div class="es-kinky-social-card">
                    <?php if (!empty($social_links['instagram'])): ?>
                    <a href="<?php echo esc_url($social_links['instagram']); ?>" target="_blank" rel="noopener" class="es-kinky-social-link" title="Instagram">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($social_links['facebook'])): ?>
                    <a href="<?php echo esc_url($social_links['facebook']); ?>" target="_blank" rel="noopener" class="es-kinky-social-link" title="Facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php 
                if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('location_sidebar')) {
                    if (function_exists('ensemble_location_sidebar')) ensemble_location_sidebar($location_id);
                }
                ?>
                
            </aside>
            
        </div>
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php if (function_exists('ensemble_after_location_hook')) ensemble_after_location_hook($location_id, $location); ?>
    
</div>

<!-- Kinky Single Location Styles -->
<style>
/* Location Single Specific Styles */
.es-kinky-location-single {
    background: var(--kinky-bg, #0a0a0f);
    background-image: var(--kinky-bg-gradient);
    color: var(--kinky-text, #ffffff);
    min-height: 100vh;
}

.es-kinky-hero-location {
    min-height: 60vh;
}

.es-kinky-hero-city {
    display: inline-block;
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--kinky-text-muted, #8a8a9a);
    margin-bottom: 16px;
}

.es-kinky-hero-capacity {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 14px;
    font-weight: 400;
    color: var(--kinky-text-muted, #8a8a9a);
    margin-top: 16px;
}

.es-kinky-hero-capacity svg {
    color: var(--kinky-primary, #cc2222);
}

/* Open/Closed Status in Hero */
.es-kinky-hero-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-top: 20px;
    padding: 10px 20px;
    border: 1px solid;
}

.es-kinky-status-open {
    color: #22cc66;
    border-color: #22cc66;
    background: rgba(34, 204, 102, 0.1);
}

.es-kinky-status-closed {
    color: var(--kinky-text-muted, #8a8a9a);
    border-color: var(--kinky-card-border, #1a1a22);
    background: rgba(26, 26, 34, 0.5);
}

.es-kinky-status-dot {
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

/* Location Layout */
.es-kinky-location-container {
    max-width: 1100px;
}

.es-kinky-location-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 48px;
}

.es-kinky-main {
    display: flex;
    flex-direction: column;
    gap: 48px;
}

/* Sidebar */
.es-kinky-sidebar {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Info Card */
.es-kinky-info-card {
    background: var(--kinky-card-bg, #0f0f14);
    border: 1px solid var(--kinky-card-border, #1a1a22);
    padding: 24px;
}

.es-kinky-info-card-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: var(--kinky-font-heading, 'Cinzel', serif);
    font-size: 18px;
    font-weight: 400;
    
    color: var(--kinky-text, #ffffff);
    margin: 0 0 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--kinky-card-border, #1a1a22);
}

.es-kinky-info-row {
    display: flex;
    gap: 16px;
    padding: 12px 0;
    border-bottom: 1px solid var(--kinky-card-border, #1a1a22);
}

.es-kinky-info-row:last-child {
    border-bottom: none;
}

.es-kinky-info-row-button {
    padding-top: 20px;
    border-bottom: none;
}

.es-kinky-info-row-button .es-kinky-button {
    width: 100%;
    justify-content: center;
}

.es-kinky-info-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    color: var(--kinky-primary, #cc2222);
}

.es-kinky-info-icon svg {
    width: 100%;
    height: 100%;
}

.es-kinky-info-content {
    flex: 1;
}

.es-kinky-info-label {
    display: block;
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--kinky-text-muted, #8a8a9a);
    margin-bottom: 4px;
}

.es-kinky-info-value {
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 14px;
    font-weight: 400;
    color: var(--kinky-text, #ffffff);
    line-height: 1.5;
}

.es-kinky-info-link {
    text-decoration: none;
    transition: color 0.3s ease;
}

.es-kinky-info-link:hover {
    color: var(--kinky-secondary, #ff6633);
}

/* Opening Hours */
.es-kinky-hours-list {
    display: flex;
    flex-direction: column;
}

.es-kinky-hours-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--kinky-card-border, #1a1a22);
}

.es-kinky-hours-row:last-child {
    border-bottom: none;
}

.es-kinky-hours-today {
    background: rgba(204, 34, 34, 0.1);
    margin: 0 -24px;
    padding: 10px 24px;
}

.es-kinky-hours-day {
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 14px;
    font-weight: 400;
    color: var(--kinky-text, #ffffff);
}

.es-kinky-hours-time {
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 14px;
    font-weight: 600;
    color: var(--kinky-secondary, #ff6633);
}

.es-kinky-hours-closed {
    color: var(--kinky-text-muted, #8a8a9a);
    font-weight: 400;
}

.es-kinky-hours-note {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--kinky-card-border, #1a1a22);
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 13px;
    
    color: var(--kinky-text-muted, #8a8a9a);
}

/* Status Badge */
.es-kinky-status-badge {
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 10px;
    font-weight: 700;
    font-style: normal;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 4px 10px;
}

.es-kinky-badge-open {
    background: rgba(34, 204, 102, 0.2);
    color: #22cc66;
}

.es-kinky-badge-closed {
    background: rgba(138, 138, 154, 0.2);
    color: var(--kinky-text-muted, #8a8a9a);
}

/* Social Card */
.es-kinky-social-card {
    display: flex;
    justify-content: center;
    gap: 12px;
    padding: 20px;
    background: var(--kinky-card-bg, #0f0f14);
    border: 1px solid var(--kinky-card-border, #1a1a22);
}

/* Map Wrapper */
.es-kinky-map-wrapper {
    border: 1px solid var(--kinky-card-border, #1a1a22);
    overflow: hidden;
}

.es-kinky-map-wrapper iframe,
.es-kinky-map-wrapper .es-map-embed {
    display: block;
    width: 100%;
    min-height: 300px;
}

/* Responsive */
@media (max-width: 900px) {
    .es-kinky-location-layout {
        grid-template-columns: 1fr;
    }
    
    .es-kinky-sidebar {
        order: -1;
    }
}

@media (max-width: 640px) {
    .es-kinky-container {
        padding: 40px 16px;
    }
    
    .es-kinky-info-card {
        padding: 20px;
    }
    
    .es-kinky-hours-today {
        margin: 0 -20px;
        padding: 10px 20px;
    }
}
</style>

<?php get_footer(); ?>
