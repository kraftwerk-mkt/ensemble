<?php
/**
 * Single Location Template - Bristol City Festival
 * Urban festival style with all addon hooks
 *
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);

$location_id = get_the_ID();
$location = es_load_location_data($location_id);
$upcoming_events = es_get_upcoming_events_at_location($location_id);

$address_data = array(
    'address' => $location['address'],
    'city' => $location['city'],
    'state' => $location['state'],
    'zip' => $location['zip'],
    'country' => $location['country'],
);

$contact_data = array(
    'phone' => $location['phone'],
    'email' => $location['email'],
    'website' => $location['website'],
);
?>

<div class="es-bristol es-bristol-single es-bristol-location">
    
    <?php ensemble_before_location($location_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- Hero -->
    <header class="es-bristol-single-hero">
        <?php if (has_post_thumbnail()): ?>
        <div class="es-bristol-single-hero-media">
            <?php the_post_thumbnail('full'); ?>
            <div class="es-bristol-single-hero-overlay"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-bristol-single-hero-content">
            
            <?php if ($location['city']): ?>
            <span class="es-bristol-single-hero-genre">
                <?php echo esc_html($location['city']); ?>
                <?php if ($location['country']) echo ', ' . esc_html($location['country']); ?>
            </span>
            <?php endif; ?>
            
            <h1 class="es-bristol-single-hero-title"><?php the_title(); ?></h1>
            
            <?php if ($location['capacity']): ?>
            <div class="es-bristol-single-hero-meta">
                <?php echo number_format_i18n($location['capacity']); ?> <?php _e('Capacity', 'ensemble'); ?>
            </div>
            <?php endif; ?>
            
            <?php ensemble_after_location_title($location_id); ?>
        </div>
        
        <?php ensemble_location_header($location_id); ?>
    </header>
    
    <!-- Content -->
    <div class="es-bristol-body">
        <div class="es-bristol-container">
            
            <div class="es-bristol-layout">
                
                <!-- Main -->
                <main class="es-bristol-main">
                    
                    <!-- Description -->
                    <?php if (get_the_content()): ?>
                    <section class="es-bristol-section es-bristol-description">
                        <?php ensemble_before_location_content($location_id); ?>
                        <h2 class="es-bristol-section-title"><?php _e('About This Venue', 'ensemble'); ?></h2>
                        <div class="es-bristol-prose">
                            <?php the_content(); ?>
                        </div>
                        <?php ensemble_after_location_description($location_id); ?>
                    </section>
                    <?php endif; ?>
                    
                    <?php ensemble_location_meta($location_id, $location); ?>
                    
                    <!-- Map Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('location_map')) {
                        echo '<section class="es-bristol-section es-bristol-map">';
                        echo '<h2 class="es-bristol-section-title">' . __('Location', 'ensemble') . '</h2>';
                        ensemble_location_map($location_id, $address_data);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Floor Plan Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('location_floorplan')) {
                        echo '<section class="es-bristol-section es-bristol-floorplan">';
                        echo '<h2 class="es-bristol-section-title">' . __('Floor Plan', 'ensemble') . '</h2>';
                        ensemble_location_floorplan($location_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Catalog Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('location_catalog')) {
                        echo '<section class="es-bristol-section es-bristol-catalog">';
                        ensemble_location_catalog($location_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Gallery Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('location_gallery')) {
                        echo '<section class="es-bristol-section es-bristol-gallery">';
                        echo '<h2 class="es-bristol-section-title">' . __('Gallery', 'ensemble') . '</h2>';
                        ensemble_location_gallery($location_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Events -->
                    <?php if ($upcoming_events->have_posts()): ?>
                    <section class="es-bristol-section es-bristol-events">
                        <h2 class="es-bristol-section-title"><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                        
                        <?php ensemble_location_events($location_id); ?>
                        
                        <div class="es-bristol-event-list">
                            <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); 
                                $evt = es_load_event_data(get_the_ID());
                                $evt_timestamp = strtotime($evt['date']);
                            ?>
                            <a href="<?php echo esc_url($evt['permalink']); ?>" class="es-bristol-event-row">
                                <time class="es-bristol-event-date">
                                    <span class="es-bristol-event-day"><?php echo date_i18n('d', $evt_timestamp); ?></span>
                                    <span class="es-bristol-event-month"><?php echo date_i18n('M', $evt_timestamp); ?></span>
                                </time>
                                <div class="es-bristol-event-info">
                                    <h4><?php echo esc_html($evt['title']); ?></h4>
                                    <?php if ($evt['formatted_time']): ?>
                                        <span><?php echo esc_html($evt['formatted_time']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="es-bristol-event-arrow">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </a>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php ensemble_after_location_events($location_id, $upcoming_events); ?>
                    </section>
                    <?php endif; wp_reset_postdata(); ?>
                    
                    <?php ensemble_location_footer($location_id); ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="es-bristol-aside">
                    
                    <div class="es-bristol-info-card">
                        
                        <!-- Address -->
                        <?php if ($location['address'] || $location['city']): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Address', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value">
                                <?php 
                                $parts = array_filter(array($location['address'], $location['zip'] . ' ' . $location['city']));
                                echo esc_html(implode(', ', $parts));
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Capacity -->
                        <?php if ($location['capacity']): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Capacity', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo number_format_i18n($location['capacity']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Phone -->
                        <?php if ($location['phone']): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Phone', 'ensemble'); ?></span>
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $location['phone'])); ?>" class="es-bristol-info-value es-bristol-info-link">
                                <?php echo esc_html($location['phone']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Email -->
                        <?php if ($location['email']): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Email', 'ensemble'); ?></span>
                            <a href="mailto:<?php echo esc_attr($location['email']); ?>" class="es-bristol-info-value es-bristol-info-link">
                                <?php echo esc_html($location['email']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Website -->
                        <?php if ($location['website']): ?>
                        <div class="es-bristol-info-row">
                            <a href="<?php echo esc_url($location['website']); ?>" class="es-bristol-btn es-bristol-btn-primary" target="_blank" rel="noopener">
                                <?php _e('Visit Website', 'ensemble'); ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                    <polyline points="15 3 21 3 21 9"/>
                                    <line x1="10" y1="14" x2="21" y2="3"/>
                                </svg>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <!-- Contact Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('location_contact')) {
                        ensemble_location_contact($location_id, $contact_data);
                    }
                    ?>
                    
                    <!-- Booking Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('location_booking')) {
                        echo '<div class="es-bristol-booking">';
                        ensemble_location_booking($location_id);
                        echo '</div>';
                    }
                    ?>
                    
                    <?php 
                    if (ensemble_has_addon_hook('location_sidebar')) {
                        ensemble_location_sidebar($location_id);
                    }
                    ?>
                    
                </aside>
                
            </div>
            
        </div>
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php ensemble_after_location_hook($location_id, $location); ?>
    
</div>

<!-- Theme Toggle -->
<button class="es-bristol-theme-toggle" onclick="toggleBristolTheme()" aria-label="Toggle theme">
    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="5"/>
        <line x1="12" y1="1" x2="12" y2="3"/>
        <line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1" y1="12" x2="3" y2="12"/>
        <line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
    </svg>
    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>
</button>

<script>
function toggleBristolTheme() {
    const root = document.querySelector('.es-bristol');
    if (root) {
        root.classList.toggle('es-mode-light');
        localStorage.setItem('es-bristol-theme', root.classList.contains('es-mode-light') ? 'light' : 'dark');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('es-bristol-theme');
    if (saved === 'light') {
        document.querySelector('.es-bristol')?.classList.add('es-mode-light');
    }
});
</script>

<?php get_footer(); ?>
