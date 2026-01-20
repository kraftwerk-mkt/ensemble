<?php
/**
 * Single Event Template - Bristol City Festival
 * Urban festival style with all addon hooks
 *
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);

$event_id = get_the_ID();
$event = es_load_event_data($event_id);

// Artists
$artists = $event['artists'] ?? array();

// Build address string
$address_parts = array_filter(array(
    $event['location']['address'] ?? '',
    ($event['location']['zip'] ?? '') . ' ' . ($event['location']['city'] ?? ''),
));
$full_address = implode(', ', $address_parts);
?>

<div class="es-bristol es-bristol-single es-bristol-event">
    
    <?php ensemble_before_event($event_id); ?>
    
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
            
            <?php if (!empty($event['categories'])): ?>
            <span class="es-bristol-single-hero-category">
                <?php 
                $cat = is_array($event['categories']) ? $event['categories'][0] : $event['categories'];
                echo esc_html(is_object($cat) ? $cat->name : $cat); 
                ?>
            </span>
            <?php endif; ?>
            
            <h1 class="es-bristol-single-hero-title"><?php the_title(); ?></h1>
            
            <div class="es-bristol-single-hero-meta">
                <?php if ($event['date']): ?>
                    <span><?php echo esc_html(date_i18n('l, j. F Y', strtotime($event['date']))); ?></span>
                <?php endif; ?>
                <?php if ($event['formatted_time']): ?>
                    <span> • <?php echo esc_html($event['formatted_time']); ?></span>
                <?php endif; ?>
                <?php if ($event['location']['name'] ?? false): ?>
                    <span> • <?php echo esc_html($event['location']['name']); ?></span>
                <?php endif; ?>
            </div>
            
            <?php ensemble_after_event_title($event_id); ?>
        </div>
        
        <?php ensemble_event_header($event_id); ?>
    </header>
    
    <!-- Content -->
    <div class="es-bristol-body">
        <div class="es-bristol-container">
            
            <div class="es-bristol-layout">
                
                <!-- Main -->
                <main class="es-bristol-main">
                    
                    <?php ensemble_event_actions($event_id); ?>
                    
                    <!-- Description -->
                    <?php if (get_the_content()): ?>
                    <section class="es-bristol-section es-bristol-description">
                        <?php ensemble_before_event_content($event_id); ?>
                        <h2 class="es-bristol-section-title"><?php _e('About This Event', 'ensemble'); ?></h2>
                        <div class="es-bristol-prose">
                            <?php the_content(); ?>
                        </div>
                        <?php ensemble_after_event_description($event_id); ?>
                    </section>
                    <?php endif; ?>
                    
                    <?php ensemble_event_meta($event_id, $event); ?>
                    
                    <!-- Artists -->
                    <?php if (!empty($artists)): ?>
                    <section class="es-bristol-section es-bristol-artists">
                        <h2 class="es-bristol-section-title"><?php echo esc_html(ensemble_label('artist', true)); ?></h2>
                        <?php ensemble_event_artists($event_id, $artists); ?>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Timetable Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('event_timetable')) {
                        echo '<section class="es-bristol-section es-bristol-timetable">';
                        echo '<h2 class="es-bristol-section-title">' . __('Schedule', 'ensemble') . '</h2>';
                        ensemble_event_timetable($event_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Floor Plan Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('event_floorplan')) {
                        echo '<section class="es-bristol-section es-bristol-floorplan">';
                        echo '<h2 class="es-bristol-section-title">' . __('Floor Plan', 'ensemble') . '</h2>';
                        ensemble_event_floorplan($event_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Gallery Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('event_gallery')) {
                        echo '<section class="es-bristol-section es-bristol-gallery">';
                        echo '<h2 class="es-bristol-section-title">' . __('Gallery', 'ensemble') . '</h2>';
                        ensemble_event_gallery($event_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Map Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('event_map') && !empty($event['location'])) {
                        echo '<section class="es-bristol-section es-bristol-map">';
                        echo '<h2 class="es-bristol-section-title">' . __('Location', 'ensemble') . '</h2>';
                        ensemble_event_map($event_id, $event['location']);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Downloads Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('event_downloads')) {
                        echo '<section class="es-bristol-section es-bristol-downloads">';
                        echo '<h2 class="es-bristol-section-title">' . __('Downloads', 'ensemble') . '</h2>';
                        ensemble_event_downloads($event_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- FAQ Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('event_faq')) {
                        echo '<section class="es-bristol-section es-bristol-faq">';
                        echo '<h2 class="es-bristol-section-title">' . __('FAQ', 'ensemble') . '</h2>';
                        ensemble_event_faq($event_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <!-- Sponsors Hook -->
                    <?php 
                    if (ensemble_has_addon_hook('event_sponsors')) {
                        echo '<section class="es-bristol-section es-bristol-sponsors">';
                        echo '<h2 class="es-bristol-section-title">' . __('Sponsors', 'ensemble') . '</h2>';
                        ensemble_event_sponsors($event_id);
                        echo '</section>';
                    }
                    ?>
                    
                    <?php ensemble_event_footer($event_id); ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="es-bristol-aside">
                    
                    <!-- Tickets / Booking -->
                    <?php 
                    if (ensemble_has_addon_hook('event_tickets')) {
                        echo '<div class="es-bristol-tickets">';
                        ensemble_event_tickets($event_id);
                        echo '</div>';
                    }
                    
                    if (ensemble_has_addon_hook('event_booking')) {
                        echo '<div class="es-bristol-booking">';
                        ensemble_event_booking($event_id);
                        echo '</div>';
                    }
                    ?>
                    
                    <!-- Event Info Card -->
                    <div class="es-bristol-info-card">
                        
                        <!-- Date & Time -->
                        <?php if ($event['date']): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Date', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value">
                                <?php echo esc_html(date_i18n('l, j. F Y', strtotime($event['date']))); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($event['formatted_time']): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Time', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($event['formatted_time']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Location -->
                        <?php if ($event['location']['name'] ?? false): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php echo esc_html(ensemble_label('location')); ?></span>
                            <span class="es-bristol-info-value">
                                <?php if ($event['location']['id']): ?>
                                <a href="<?php echo get_permalink($event['location']['id']); ?>" class="es-bristol-info-link">
                                    <?php echo esc_html($event['location']['name']); ?>
                                </a>
                                <?php else: ?>
                                    <?php echo esc_html($event['location']['name']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Address -->
                        <?php if ($full_address): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Address', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($full_address); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Price -->
                        <?php if ($event['price']): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Price', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($event['price']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Ticket Link (wenn keine Ticket Addon) -->
                        <?php if ($event['ticket_url'] && !ensemble_has_addon_hook('event_tickets')): ?>
                        <div class="es-bristol-info-row">
                            <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-bristol-btn es-bristol-btn-primary" target="_blank" rel="noopener">
                                <?php echo esc_html($event['button_text'] ?: __('Get Tickets', 'ensemble')); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <!-- Additional Sidebar Hooks -->
                    <?php 
                    if (ensemble_has_addon_hook('event_sidebar')) {
                        ensemble_event_sidebar($event_id);
                    }
                    ?>
                    
                    <!-- Related Events -->
                    <?php 
                    if (ensemble_has_addon_hook('event_related')) {
                        ensemble_event_related($event_id);
                    }
                    ?>
                    
                </aside>
                
            </div>
            
        </div>
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php ensemble_after_event($event_id, $event); ?>
    
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
// Init on load
document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('es-bristol-theme');
    if (saved === 'light') {
        document.querySelector('.es-bristol')?.classList.add('es-mode-light');
    }
});
</script>

<?php get_footer(); ?>
