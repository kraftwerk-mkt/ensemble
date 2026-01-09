<?php
/**
 * Template: Stage Artist Card (Grid/Slider)
 * Image only, name + next event appears on hover
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// Support both $artist array and direct variables
$artist_id = isset($artist['id']) ? $artist['id'] : ($artist_id ?? 0);
$name = isset($artist['name']) ? $artist['name'] : (isset($artist['title']) ? $artist['title'] : '');
$image = isset($artist['image']) ? $artist['image'] : (isset($artist['featured_image']) ? $artist['featured_image'] : '');
$permalink = isset($artist['permalink']) ? $artist['permalink'] : '#';
$link_target = isset($artist['link_target']) ? $artist['link_target'] : '_self';

// Next event data (if available)
$next_event = isset($artist['next_event']) ? $artist['next_event'] : null;
$next_event_title = '';
$next_event_date = '';

if ($next_event) {
    $next_event_title = is_array($next_event) ? ($next_event['title'] ?? '') : '';
    $next_event_date = is_array($next_event) ? ($next_event['date_formatted'] ?? '') : '';
}
?>

<article class="ensemble-artist-card es-stage-card es-stage-card--minimal">
    <a href="<?php echo esc_url($permalink); ?>" class="es-stage-card-link" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
        
        <!-- Background Image -->
        <div class="es-stage-card-bg">
            <?php if ($image): ?>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-stage-placeholder-bg es-stage-placeholder-artist">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="es-stage-card-gradient es-stage-gradient-hover"></div>
        </div>
        
        <!-- Content (hidden, appears on hover) -->
        <div class="es-stage-card-overlay es-stage-overlay-hover">
            <h3 class="es-stage-card-name"><?php echo esc_html($name); ?></h3>
            
            <?php if ($next_event_title || $next_event_date): ?>
            <div class="es-stage-card-next-event">
                <?php if ($next_event_date): ?>
                <span class="es-stage-next-date"><?php echo esc_html($next_event_date); ?></span>
                <?php endif; ?>
                <?php if ($next_event_title): ?>
                <span class="es-stage-next-title"><?php echo esc_html($next_event_title); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
    </a>
</article>
