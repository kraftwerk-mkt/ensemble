<?php
/**
 * Template: Kongress Location Card
 * 
 * Respects all shortcode display options independently.
 * Designer-ready with CSS variables.
 * 
 * @package Ensemble
 * @version 2.0.0 - Full display options support
 */

if (!defined('ABSPATH')) exit;

// Get location data
$location_id = isset($location_id) ? $location_id : get_the_ID();
$name = get_the_title($location_id);
$permalink = get_permalink($location_id);
$image = get_the_post_thumbnail_url($location_id, 'large');

// Parse shortcode attributes - each option is INDEPENDENT
$show_image       = !isset($shortcode_atts['show_image']) || filter_var($shortcode_atts['show_image'], FILTER_VALIDATE_BOOLEAN);
$show_name        = !isset($shortcode_atts['show_name']) || filter_var($shortcode_atts['show_name'], FILTER_VALIDATE_BOOLEAN);
$show_type        = !isset($shortcode_atts['show_type']) || filter_var($shortcode_atts['show_type'], FILTER_VALIDATE_BOOLEAN);
$show_address     = !isset($shortcode_atts['show_address']) || filter_var($shortcode_atts['show_address'], FILTER_VALIDATE_BOOLEAN);
$show_capacity    = isset($shortcode_atts['show_capacity']) && filter_var($shortcode_atts['show_capacity'], FILTER_VALIDATE_BOOLEAN);
$show_events      = isset($shortcode_atts['show_events']) && filter_var($shortcode_atts['show_events'], FILTER_VALIDATE_BOOLEAN);
$show_description = isset($shortcode_atts['show_description']) && filter_var($shortcode_atts['show_description'], FILTER_VALIDATE_BOOLEAN);
$show_social      = isset($shortcode_atts['show_social']) && filter_var($shortcode_atts['show_social'], FILTER_VALIDATE_BOOLEAN);
$show_link        = !isset($shortcode_atts['show_link']) || filter_var($shortcode_atts['show_link'], FILTER_VALIDATE_BOOLEAN);
$link_text        = isset($shortcode_atts['link_text']) ? $shortcode_atts['link_text'] : __('View Location', 'ensemble');

// Address & City
$address = '';
if (function_exists('get_field')) {
    $address = get_field('location_address', $location_id);
}
if (empty($address)) {
    $address = get_post_meta($location_id, 'location_address', true);
}

$city = '';
if (function_exists('get_field')) {
    $city = get_field('location_city', $location_id);
}
if (empty($city)) {
    $city = get_post_meta($location_id, 'location_city', true);
}

// Capacity
$capacity = '';
if (function_exists('get_field')) {
    $capacity = get_field('location_capacity', $location_id);
}
if (empty($capacity)) {
    $capacity = get_post_meta($location_id, 'location_capacity', true);
}

// Location Types (Taxonomy)
$type_text = '';
$location_types = get_the_terms($location_id, 'ensemble_location_type');
if ($location_types && !is_wp_error($location_types)) {
    $type_text = $location_types[0]->name;
}

// Description/Excerpt
$description = '';
$post_obj = get_post($location_id);
if ($post_obj && !empty($post_obj->post_excerpt)) {
    $description = $post_obj->post_excerpt;
} elseif ($post_obj && !empty($post_obj->post_content)) {
    $description = wp_trim_words(strip_tags($post_obj->post_content), 20);
}

// Event Count
$events_count = 0;
if (function_exists('ensemble_get_location_event_count')) {
    $events_count = ensemble_get_location_event_count($location_id, true);
}

// =============================================
// SOCIAL LINKS
// =============================================
$social_data = array();

// Website
$website = '';
if (function_exists('get_field')) {
    $website = get_field('location_website', $location_id);
}
if (empty($website)) {
    $website = get_post_meta($location_id, 'location_website', true);
}
if (!empty($website)) {
    $social_data['website'] = $website;
}

// YouTube
$youtube = '';
if (function_exists('get_field')) {
    $youtube = get_field('location_youtube', $location_id);
}
if (empty($youtube)) {
    $youtube = get_post_meta($location_id, 'location_youtube', true);
}
if (!empty($youtube)) {
    $social_data['youtube'] = $youtube;
}

// Vimeo
$vimeo = '';
if (function_exists('get_field')) {
    $vimeo = get_field('location_vimeo', $location_id);
}
if (empty($vimeo)) {
    $vimeo = get_post_meta($location_id, 'location_vimeo', true);
}
if (!empty($vimeo)) {
    $social_data['vimeo'] = $vimeo;
}

// Social Links Array
$social_links_array = array();
if (function_exists('get_field')) {
    $social_links_array = get_field('location_social_links', $location_id);
}
if (empty($social_links_array) || !is_array($social_links_array)) {
    $social_links_array = get_post_meta($location_id, 'location_social_links', true);
}

if (is_array($social_links_array) && !empty($social_links_array)) {
    foreach ($social_links_array as $url) {
        if (empty($url)) continue;
        $url = trim($url);
        
        if (stripos($url, 'facebook.com') !== false) {
            $social_data['facebook'] = $url;
        } elseif (stripos($url, 'instagram.com') !== false) {
            $social_data['instagram'] = $url;
        } elseif (stripos($url, 'twitter.com') !== false || stripos($url, 'x.com') !== false) {
            $social_data['twitter'] = $url;
        } elseif (stripos($url, 'linkedin.com') !== false) {
            $social_data['linkedin'] = $url;
        } elseif (stripos($url, 'tiktok.com') !== false) {
            $social_data['tiktok'] = $url;
        } elseif ((stripos($url, 'youtube.com') !== false || stripos($url, 'youtu.be') !== false) && empty($social_data['youtube'])) {
            $social_data['youtube'] = $url;
        } elseif (stripos($url, 'vimeo.com') !== false && empty($social_data['vimeo'])) {
            $social_data['vimeo'] = $url;
        }
    }
}

$has_social = !empty($social_data);

/**
 * Get social icon SVG - with fallback
 * Only declare once
 */
if (!function_exists('ensemble_get_location_social_icon')) {
    function ensemble_get_location_social_icon($platform) {
        // Try ES_Icons first
        if (class_exists('ES_Icons')) {
            $icon = ES_Icons::get($platform);
            if (!empty($icon)) {
                return $icon;
            }
        }
        
        // Fallback SVG icons
        $icons = array(
            'website' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
            'facebook' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
            'instagram' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
            'twitter' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            'linkedin' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
            'youtube' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
            'tiktok' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
            'vimeo' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M23.977 6.416c-.105 2.338-1.739 5.543-4.894 9.609-3.268 4.247-6.026 6.37-8.29 6.37-1.409 0-2.578-1.294-3.553-3.881L5.322 11.4C4.603 8.816 3.834 7.522 3.01 7.522c-.179 0-.806.378-1.881 1.132L0 7.197c1.185-1.044 2.351-2.084 3.501-3.128C5.08 2.701 6.266 1.984 7.055 1.91c1.867-.18 3.016 1.1 3.447 3.838.465 2.953.789 4.789.971 5.507.539 2.45 1.131 3.674 1.776 3.674.502 0 1.256-.796 2.265-2.385 1.004-1.589 1.54-2.797 1.612-3.628.144-1.371-.395-2.061-1.614-2.061-.574 0-1.167.121-1.777.391 1.186-3.868 3.434-5.757 6.762-5.637 2.473.06 3.628 1.664 3.493 4.797l-.013.01z"/></svg>',
        );
        
        return isset($icons[$platform]) ? $icons[$platform] : '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
    }
}
?>

<?php if ($show_link): ?>
<a href="<?php echo esc_url($permalink); ?>" class="es-kongress-location-card">
<?php else: ?>
<div class="es-kongress-location-card">
<?php endif; ?>
    
    <?php if ($show_image): ?>
    <div class="es-kongress-location-card-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        <?php else: ?>
        <div class="es-kongress-location-placeholder">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 48px; height: 48px;">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="es-kongress-location-card-content">
        <?php if ($show_type && $type_text): ?>
        <div class="es-kongress-location-type"><?php echo esc_html($type_text); ?></div>
        <?php endif; ?>
        
        <?php if ($show_name): ?>
        <h3 class="es-kongress-location-name"><?php echo esc_html($name); ?></h3>
        <?php endif; ?>
        
        <?php if ($show_address && ($address || $city)): ?>
        <div class="es-kongress-location-address">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px; flex-shrink: 0;">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            <span>
                <?php 
                if ($address) echo esc_html($address);
                if ($address && $city) echo ', ';
                if ($city) echo esc_html($city);
                ?>
            </span>
        </div>
        <?php endif; ?>
        
        <?php if ($show_capacity && $capacity): ?>
        <div class="es-kongress-location-capacity">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px; flex-shrink: 0;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span><?php printf(__('Capacity: %s', 'ensemble'), esc_html($capacity)); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($show_events && $events_count > 0): ?>
        <div class="es-kongress-location-events">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px; flex-shrink: 0;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span><?php printf(_n('%d Event', '%d Events', $events_count, 'ensemble'), $events_count); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($show_description && $description): ?>
        <div class="es-kongress-location-description">
            <?php echo esc_html($description); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($show_social && $has_social): ?>
        <div class="es-kongress-location-social" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
            <?php foreach ($social_data as $platform => $url): ?>
            <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:var(--ensemble-surface,#f0f0f0);color:var(--ensemble-text,#333);cursor:pointer;" onclick="event.stopPropagation();event.preventDefault();window.open('<?php echo esc_js($url); ?>','_blank');">
                <?php echo ensemble_get_location_social_icon($platform); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
<?php if ($show_link): ?>
</a>
<?php else: ?>
</div>
<?php endif; ?>
