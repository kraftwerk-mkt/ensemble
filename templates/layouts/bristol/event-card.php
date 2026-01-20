<?php
/**
 * Event Card Template - Bristol Editorial Style
 * Minimal default, dramatic fullscreen hover
 */
if (!defined('ABSPATH')) exit;

$event_id = get_the_ID();
$event = es_load_event_data($event_id);
$permalink = get_permalink($event_id);

// Format date
$date_display = '';
$time_display = '';
if (!empty($event['date'])) {
    $date_display = date_i18n('j. F Y', strtotime($event['date']));
    $time_display = date_i18n('H:i', strtotime($event['date']));
}

// Category
$category = !empty($event['categories']) ? $event['categories'][0]->name : '';

// Location
$location_name = !empty($event['location']['name']) ? $event['location']['name'] : '';
?>

<article class="es-bristol-card es-bristol-event-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-bristol-card-link">
        <!-- Media -->
        <div class="es-bristol-card-media">
            <?php if (has_post_thumbnail()): ?>
                <?php the_post_thumbnail('large'); ?>
            <?php else: ?>
                <div class="es-bristol-card-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Default: Just title -->
        <div class="es-bristol-card-title-bar">
            <h3 class="es-bristol-card-title"><?php the_title(); ?></h3>
        </div>
        
        <!-- Hover: Full info panel -->
        <div class="es-bristol-card-info">
            <?php if ($category): ?>
            <span class="es-bristol-card-badge"><?php echo esc_html($category); ?></span>
            <?php endif; ?>
            
            <h3 class="es-bristol-card-info-title"><?php the_title(); ?></h3>
            
            <div class="es-bristol-card-meta-list">
                <?php if ($date_display): ?>
                <span class="es-bristol-card-meta-item">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php echo esc_html($date_display); ?>
                </span>
                <?php endif; ?>
                
                <?php if ($time_display && $time_display !== '00:00'): ?>
                <span class="es-bristol-card-meta-item">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo esc_html($time_display); ?> Uhr
                </span>
                <?php endif; ?>
                
                <?php if ($location_name): ?>
                <span class="es-bristol-card-meta-item">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?php echo esc_html($location_name); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- CTA Arrow -->
        <span class="es-bristol-card-cta">
            <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </span>
    </a>
</article>
