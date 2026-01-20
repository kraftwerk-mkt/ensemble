<?php
/**
 * Template: Kongress Event Card
 * 
 * Supports card styles: default, compact, minimal, overlay, featured
 * 
 * @package Ensemble
 * @version 1.3.0 - Added show_title support
 */

if (!defined('ABSPATH')) exit;

// Get event data
$event_id = isset($event_id) ? $event_id : get_the_ID();
$title = get_the_title($event_id);
$permalink = get_permalink($event_id);
$image = get_the_post_thumbnail_url($event_id, 'medium_large');
$excerpt = get_the_excerpt($event_id);

// Parse shortcode attributes with defaults
$show_image    = isset($shortcode_atts['show_image']) ? $shortcode_atts['show_image'] : true;
$show_title    = isset($shortcode_atts['show_title']) ? $shortcode_atts['show_title'] : true;
$show_date     = isset($shortcode_atts['show_date']) ? $shortcode_atts['show_date'] : true;
$show_time     = isset($shortcode_atts['show_time']) ? $shortcode_atts['show_time'] : true;
$show_location = isset($shortcode_atts['show_location']) ? $shortcode_atts['show_location'] : true;
$show_category = isset($shortcode_atts['show_category']) ? $shortcode_atts['show_category'] : true;
$show_price    = isset($shortcode_atts['show_price']) ? $shortcode_atts['show_price'] : true;

// Card style - available from parent scope or shortcode_atts
// Styles: default, compact, minimal, overlay, featured
$card_style = isset($style) ? $style : 'default';
if (isset($shortcode_atts['style'])) {
    $card_style = $shortcode_atts['style'];
}

// Date & Time
$event_date = ensemble_get_field('event_date', $event_id);
$formatted_date = $event_date ? date_i18n('j. F Y', strtotime($event_date)) : '';
$short_date = $event_date ? date_i18n('j. M', strtotime($event_date)) : '';

$event_time = ensemble_get_field('event_time', $event_id);
if (!$event_time) {
    $event_time = ensemble_get_field('event_start_time', $event_id);
}

// Location
$location_id = ensemble_get_field('event_location', $event_id);
$location_name = '';
if ($location_id) {
    $location_post = get_post($location_id);
    if ($location_post) {
        $location_name = $location_post->post_title;
    }
}

// Price
$price = ensemble_get_field('event_price', $event_id);
if (!$price) {
    $price = get_post_meta($event_id, '_event_price', true);
}

// Badge
$badge_label = '';
$badge_raw = get_post_meta($event_id, 'event_badge', true);
if ($badge_raw && function_exists('ensemble_get_badge_label')) {
    $badge_label = ensemble_get_badge_label($badge_raw, $event_id);
}

// Categories
$categories = get_the_terms($event_id, 'ensemble_category');
$category_name = ($categories && !is_wp_error($categories)) ? $categories[0]->name : '';

// Card classes
$card_classes = array(
    'es-kongress-event-card',
    'es-card-style-' . esc_attr($card_style)
);
?>

<?php 
// =============================================================================
// STYLE: MINIMAL - Very reduced, just title, date and maybe location
// =============================================================================
if ($card_style === 'minimal'): ?>

<a href="<?php echo esc_url($permalink); ?>" class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <div class="es-kongress-event-content">
        <?php if ($show_date && $formatted_date): ?>
        <span class="es-kongress-minimal-date"><?php echo esc_html($short_date); ?></span>
        <?php endif; ?>
        
        <?php if ($show_title): ?>
        <h3 class="es-kongress-event-title"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>
        
        <?php if ($show_location && $location_name): ?>
        <span class="es-kongress-minimal-location"><?php echo esc_html($location_name); ?></span>
        <?php endif; ?>
    </div>
</a>

<?php 
// =============================================================================
// STYLE: OVERLAY - Text overlaid on the image
// =============================================================================
elseif ($card_style === 'overlay'): ?>

<a href="<?php echo esc_url($permalink); ?>" class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <div class="es-kongress-overlay-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
        <?php else: ?>
        <div class="es-kongress-overlay-placeholder"></div>
        <?php endif; ?>
        
        <div class="es-kongress-overlay-gradient"></div>
        
        <div class="es-kongress-overlay-content">
            <?php if ($show_category && $category_name): ?>
            <div class="es-kongress-event-category"><?php echo esc_html($category_name); ?></div>
            <?php endif; ?>
            
            <?php if ($show_title): ?>
            <h3 class="es-kongress-event-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            
            <div class="es-kongress-event-meta">
                <?php if ($show_date && $formatted_date): ?>
                <span class="es-kongress-event-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <?php echo esc_html($formatted_date); ?>
                </span>
                <?php endif; ?>
                
                <?php if ($show_location && $location_name): ?>
                <span class="es-kongress-event-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php echo esc_html($location_name); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($badge_label): ?>
    <span class="es-kongress-event-badge"><?php echo esc_html($badge_label); ?></span>
    <?php endif; ?>
</a>

<?php 
// =============================================================================
// STYLE: COMPACT - Smaller image, horizontal layout on larger screens
// =============================================================================
elseif ($card_style === 'compact'): ?>

<a href="<?php echo esc_url($permalink); ?>" class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <?php if ($show_image): ?>
    <div class="es-kongress-compact-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
        <?php else: ?>
        <div class="es-kongress-compact-placeholder">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 32px; height: 32px;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <?php endif; ?>
        
        <?php if ($badge_label): ?>
        <span class="es-kongress-event-badge es-badge-small"><?php echo esc_html($badge_label); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="es-kongress-compact-content">
        <?php if ($show_date && $short_date): ?>
        <span class="es-kongress-compact-date"><?php echo esc_html($short_date); ?></span>
        <?php endif; ?>
        
        <?php if ($show_title): ?>
        <h3 class="es-kongress-event-title"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>
        
        <?php if ($show_location && $location_name): ?>
        <span class="es-kongress-compact-location">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 12px; height: 12px;">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            <?php echo esc_html($location_name); ?>
        </span>
        <?php endif; ?>
    </div>
</a>

<?php 
// =============================================================================
// STYLE: FEATURED - Larger, more prominent
// =============================================================================
elseif ($card_style === 'featured'): ?>

<a href="<?php echo esc_url($permalink); ?>" class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <?php if ($show_image): ?>
    <div class="es-kongress-featured-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
        <?php else: ?>
        <div class="es-kongress-featured-placeholder">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 64px; height: 64px;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <?php endif; ?>
        
        <?php if ($badge_label): ?>
        <span class="es-kongress-event-badge es-badge-large"><?php echo esc_html($badge_label); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="es-kongress-featured-content">
        <?php if ($show_category && $category_name): ?>
        <div class="es-kongress-event-category"><?php echo esc_html($category_name); ?></div>
        <?php endif; ?>
        
        <?php if ($show_title): ?>
        <h3 class="es-kongress-event-title es-title-large"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>
        
        <?php if ($excerpt): ?>
        <p class="es-kongress-featured-excerpt"><?php echo esc_html(wp_trim_words($excerpt, 20)); ?></p>
        <?php endif; ?>
        
        <div class="es-kongress-event-meta">
            <?php if ($show_date && $formatted_date): ?>
            <span class="es-kongress-event-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 16px; height: 16px;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <?php echo esc_html($formatted_date); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($show_time && $event_time): ?>
            <span class="es-kongress-event-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 16px; height: 16px;">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <?php echo esc_html($event_time); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($show_location && $location_name): ?>
            <span class="es-kongress-event-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 16px; height: 16px;">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <?php echo esc_html($location_name); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($show_price && $price): ?>
            <span class="es-kongress-event-meta-item es-meta-price">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 16px; height: 16px;">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                <?php echo esc_html($price); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</a>

<?php 
// =============================================================================
// STYLE: DEFAULT - Standard card layout
// =============================================================================
else: ?>

<a href="<?php echo esc_url($permalink); ?>" class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <?php if ($show_image): ?>
    <div class="es-kongress-event-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
        <?php else: ?>
        <div style="width: 100%; height: 100%; background: var(--ensemble-placeholder-bg); display: flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-placeholder-icon)" stroke-width="1" style="width: 48px; height: 48px;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <?php endif; ?>
        
        <?php if ($badge_label): ?>
        <span class="es-kongress-event-badge"><?php echo esc_html($badge_label); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="es-kongress-event-content">
        <?php if ($show_category && $category_name): ?>
        <div class="es-kongress-event-category"><?php echo esc_html($category_name); ?></div>
        <?php endif; ?>
        
        <?php if ($show_title): ?>
        <h3 class="es-kongress-event-title"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>
        
        <?php if (($show_date && $formatted_date) || ($show_time && $event_time) || ($show_location && $location_name) || ($show_price && $price)): ?>
        <div class="es-kongress-event-meta">
            <?php if ($show_date && $formatted_date): ?>
            <span class="es-kongress-event-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <?php echo esc_html($formatted_date); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($show_time && $event_time): ?>
            <span class="es-kongress-event-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <?php echo esc_html($event_time); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($show_location && $location_name): ?>
            <span class="es-kongress-event-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <?php echo esc_html($location_name); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($show_price && $price): ?>
            <span class="es-kongress-event-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                <?php echo esc_html($price); ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</a>

<?php endif; ?>

<style>
/* =============================================================================
   BASE STYLES (shared across all card styles)
   ============================================================================= */
.es-kongress-event-card {
    display: block;
    background: var(--ensemble-card-bg);
    border: var(--ensemble-card-border-width) solid var(--ensemble-card-border);
    border-radius: var(--ensemble-card-radius);
    overflow: hidden;
    box-shadow: var(--ensemble-card-shadow);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.es-kongress-event-card:hover {
    transform: var(--ensemble-card-hover-transform);
    box-shadow: var(--ensemble-card-hover-shadow);
}

.es-kongress-event-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 12px;
    background: var(--ensemble-secondary);
    color: #fff;
    font-size: var(--ensemble-xs-size);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 4px;
    z-index: 2;
}

.es-kongress-event-category {
    font-size: var(--ensemble-xs-size);
    color: var(--ensemble-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 8px;
}

.es-kongress-event-title {
    font-family: var(--ensemble-font-heading);
    font-size: var(--ensemble-lg-size);
    font-weight: var(--ensemble-heading-weight);
    color: var(--ensemble-text);
    margin: 0 0 12px 0;
    line-height: var(--ensemble-line-height-heading);
}

.es-kongress-event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: var(--ensemble-small-size);
    color: var(--ensemble-text-secondary);
}

.es-kongress-event-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.es-kongress-event-meta-item svg {
    color: var(--ensemble-primary);
    flex-shrink: 0;
}

/* =============================================================================
   STYLE: DEFAULT
   ============================================================================= */
.es-card-style-default .es-kongress-event-image {
    position: relative;
    height: var(--ensemble-card-image-height, 200px);
    overflow: hidden;
}

.es-card-style-default .es-kongress-event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.es-card-style-default:hover .es-kongress-event-image img {
    transform: scale(1.05);
}

.es-card-style-default .es-kongress-event-content {
    padding: var(--ensemble-card-padding, 20px);
}

/* =============================================================================
   STYLE: MINIMAL
   ============================================================================= */
.es-card-style-minimal {
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 12px 0;
    border-bottom: 1px solid var(--ensemble-card-border);
    border-radius: 0;
}

.es-card-style-minimal:hover {
    transform: none;
    box-shadow: none;
    background: var(--ensemble-card-hover-bg, rgba(0,0,0,0.02));
}

.es-card-style-minimal .es-kongress-event-content {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.es-card-style-minimal .es-kongress-minimal-date {
    font-weight: 700;
    color: var(--ensemble-primary);
    font-size: var(--ensemble-base-size);
    min-width: 60px;
}

.es-card-style-minimal .es-kongress-event-title {
    margin: 0;
    font-size: var(--ensemble-base-size);
    flex: 1;
}

.es-card-style-minimal .es-kongress-minimal-location {
    font-size: var(--ensemble-small-size);
    color: var(--ensemble-text-secondary);
}

/* =============================================================================
   STYLE: OVERLAY
   ============================================================================= */
.es-card-style-overlay {
    position: relative;
    min-height: 280px;
}

.es-card-style-overlay .es-kongress-overlay-image {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.es-card-style-overlay .es-kongress-overlay-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.es-card-style-overlay:hover .es-kongress-overlay-image img {
    transform: scale(1.05);
}

.es-card-style-overlay .es-kongress-overlay-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--ensemble-primary) 0%, var(--ensemble-secondary) 100%);
}

.es-card-style-overlay .es-kongress-overlay-gradient {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.1) 100%);
    z-index: 1;
}

.es-card-style-overlay .es-kongress-overlay-content {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: var(--ensemble-card-padding, 20px);
    z-index: 2;
    color: #fff;
}

.es-card-style-overlay .es-kongress-event-category {
    color: var(--ensemble-primary-light, #fff);
    opacity: 0.9;
}

.es-card-style-overlay .es-kongress-event-title {
    color: #fff;
}

.es-card-style-overlay .es-kongress-event-meta {
    color: rgba(255,255,255,0.8);
}

.es-card-style-overlay .es-kongress-event-meta-item svg {
    color: var(--ensemble-primary-light, #fff);
}

/* =============================================================================
   STYLE: COMPACT
   ============================================================================= */
.es-card-style-compact {
    display: flex;
    flex-direction: row;
    align-items: stretch;
}

.es-card-style-compact .es-kongress-compact-image {
    position: relative;
    width: 120px;
    min-height: 100px;
    flex-shrink: 0;
    overflow: hidden;
}

.es-card-style-compact .es-kongress-compact-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.es-card-style-compact .es-kongress-compact-placeholder {
    width: 100%;
    height: 100%;
    background: var(--ensemble-placeholder-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ensemble-placeholder-icon);
}

.es-card-style-compact .es-kongress-compact-content {
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    flex: 1;
    min-width: 0;
}

.es-card-style-compact .es-kongress-compact-date {
    font-size: var(--ensemble-xs-size);
    font-weight: 700;
    color: var(--ensemble-primary);
    margin-bottom: 4px;
}

.es-card-style-compact .es-kongress-event-title {
    font-size: var(--ensemble-base-size);
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.es-card-style-compact .es-kongress-compact-location {
    font-size: var(--ensemble-xs-size);
    color: var(--ensemble-text-secondary);
    display: flex;
    align-items: center;
    gap: 4px;
}

.es-card-style-compact .es-badge-small {
    top: 6px;
    left: 6px;
    padding: 2px 8px;
    font-size: 10px;
}

/* =============================================================================
   STYLE: FEATURED
   ============================================================================= */
.es-card-style-featured .es-kongress-featured-image {
    position: relative;
    height: var(--ensemble-card-image-height-featured, 280px);
    overflow: hidden;
}

.es-card-style-featured .es-kongress-featured-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.es-card-style-featured:hover .es-kongress-featured-image img {
    transform: scale(1.05);
}

.es-card-style-featured .es-kongress-featured-placeholder {
    width: 100%;
    height: 100%;
    background: var(--ensemble-placeholder-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ensemble-placeholder-icon);
}

.es-card-style-featured .es-kongress-featured-content {
    padding: var(--ensemble-card-padding-featured, 24px);
}

.es-card-style-featured .es-title-large {
    font-size: var(--ensemble-xl-size, 1.5rem);
}

.es-card-style-featured .es-kongress-featured-excerpt {
    color: var(--ensemble-text-secondary);
    font-size: var(--ensemble-small-size);
    line-height: 1.6;
    margin: 0 0 16px 0;
}

.es-card-style-featured .es-badge-large {
    padding: 6px 16px;
    font-size: var(--ensemble-small-size);
}

.es-card-style-featured .es-meta-price {
    font-weight: 600;
    color: var(--ensemble-primary);
}

/* =============================================================================
   RESPONSIVE
   ============================================================================= */
@media (max-width: 600px) {
    .es-card-style-compact {
        flex-direction: column;
    }
    
    .es-card-style-compact .es-kongress-compact-image {
        width: 100%;
        height: 150px;
    }
    
    .es-card-style-overlay {
        min-height: 220px;
    }
    
    .es-card-style-minimal .es-kongress-event-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>
