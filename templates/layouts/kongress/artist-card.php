<?php
/**
 * Template: Kongress Speaker Card
 * 
 * Supports card styles: default, compact, minimal, overlay, circle
 * Uses Designer variables for full customization
 * 
 * @package Ensemble
 * @version 1.2.0 - Added card style support
 */

if (!defined('ABSPATH')) exit;

// Get artist data
$artist_id = isset($artist_id) ? $artist_id : get_the_ID();
$name = get_the_title($artist_id);
$permalink = get_permalink($artist_id);
$image = get_the_post_thumbnail_url($artist_id, 'large');

// Parse shortcode attributes with defaults - ALL display options
$show_image    = !isset($shortcode_atts['show_image']) || filter_var($shortcode_atts['show_image'], FILTER_VALIDATE_BOOLEAN);
$show_name     = !isset($shortcode_atts['show_name']) || filter_var($shortcode_atts['show_name'], FILTER_VALIDATE_BOOLEAN);
$show_position = !isset($shortcode_atts['show_position']) || filter_var($shortcode_atts['show_position'], FILTER_VALIDATE_BOOLEAN);
$show_company  = !isset($shortcode_atts['show_company']) || filter_var($shortcode_atts['show_company'], FILTER_VALIDATE_BOOLEAN);
$show_genre    = isset($shortcode_atts['show_genre']) && filter_var($shortcode_atts['show_genre'], FILTER_VALIDATE_BOOLEAN);
$show_type     = isset($shortcode_atts['show_type']) && filter_var($shortcode_atts['show_type'], FILTER_VALIDATE_BOOLEAN);
$show_bio      = !isset($shortcode_atts['show_bio']) || filter_var($shortcode_atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
$show_events   = isset($shortcode_atts['show_events']) && filter_var($shortcode_atts['show_events'], FILTER_VALIDATE_BOOLEAN);
$show_social   = isset($shortcode_atts['show_social']) && filter_var($shortcode_atts['show_social'], FILTER_VALIDATE_BOOLEAN);
$show_link     = !isset($shortcode_atts['show_link']) || filter_var($shortcode_atts['show_link'], FILTER_VALIDATE_BOOLEAN);
$link_text     = isset($shortcode_atts['link_text']) ? $shortcode_atts['link_text'] : __('Read more', 'ensemble');

// Card style - available from parent scope or shortcode_atts
// Styles: default, compact, minimal, overlay, circle
$card_style = isset($style) ? $style : 'default';
if (isset($shortcode_atts['style'])) {
    $card_style = $shortcode_atts['style'];
}

// Professional info
$position = get_post_meta($artist_id, '_es_artist_position', true);
$company = get_post_meta($artist_id, '_es_artist_company', true);

// Get bio/excerpt
$bio = '';
if ($show_bio) {
    $bio = get_post_meta($artist_id, '_artist_bio', true);
    if (!$bio) {
        $bio = get_the_excerpt($artist_id);
    }
    if (!$bio) {
        $post_obj = get_post($artist_id);
        if ($post_obj && !empty($post_obj->post_content)) {
            $bio = wp_trim_words(strip_tags($post_obj->post_content), 25);
        }
    }
}

// Genres/Tags - Try taxonomy first, then ACF, then meta
$genres = get_the_terms($artist_id, 'ensemble_genre');
$genre_name = ($genres && !is_wp_error($genres)) ? $genres[0]->name : '';

// Fallback to ACF or meta field if no taxonomy
if (empty($genre_name)) {
    if (function_exists('get_field')) {
        $genre_name = get_field('artist_genre', $artist_id);
    }
    if (empty($genre_name)) {
        $genre_name = get_post_meta($artist_id, 'artist_genre', true);
    }
    if (empty($genre_name)) {
        $genre_name = get_post_meta($artist_id, 'es_artist_genre', true);
    }
}

// Artist Type
$artist_types = get_the_terms($artist_id, 'ensemble_artist_type');
$type_name = ($artist_types && !is_wp_error($artist_types)) ? $artist_types[0]->name : '';

// Social Links - Load from array (same format as single-artist.php)
$social_links = array();
if ($show_social) {
    // Primary method: Array of URLs that get parsed by platform
    $social_links_array = get_post_meta($artist_id, 'artist_social_links', true);
    if (is_array($social_links_array) && !empty($social_links_array)) {
        foreach ($social_links_array as $url) {
            if (empty($url)) continue;
            $url = esc_url($url);
            
            if (stripos($url, 'linkedin.com') !== false) {
                $social_links['linkedin'] = $url;
            } elseif (stripos($url, 'twitter.com') !== false || stripos($url, 'x.com') !== false) {
                $social_links['twitter'] = $url;
            } elseif (stripos($url, 'facebook.com') !== false) {
                $social_links['facebook'] = $url;
            } elseif (stripos($url, 'instagram.com') !== false) {
                $social_links['instagram'] = $url;
            } elseif (stripos($url, 'xing.com') !== false) {
                $social_links['xing'] = $url;
            } elseif (stripos($url, 'youtube.com') !== false) {
                $social_links['youtube'] = $url;
            } elseif (stripos($url, 'github.com') !== false) {
                $social_links['github'] = $url;
            }
        }
    }
    
    // Fallback: Try individual meta fields
    if (empty($social_links)) {
        $social_platforms = array(
            'website'   => array('artist_website', 'es_artist_website', '_es_artist_website'),
            'twitter'   => array('artist_twitter', 'es_artist_twitter', '_es_artist_twitter'),
            'linkedin'  => array('artist_linkedin', 'es_artist_linkedin', '_es_artist_linkedin'),
            'instagram' => array('artist_instagram', 'es_artist_instagram', '_es_artist_instagram'),
            'facebook'  => array('artist_facebook', 'es_artist_facebook', '_es_artist_facebook'),
        );
        
        foreach ($social_platforms as $platform => $meta_keys) {
            $url = '';
            if (function_exists('get_field')) {
                $url = get_field($meta_keys[0], $artist_id);
            }
            if (empty($url)) {
                foreach ($meta_keys as $meta_key) {
                    $url = get_post_meta($artist_id, $meta_key, true);
                    if (!empty($url)) break;
                }
            }
            if (!empty($url)) {
                $social_links[$platform] = $url;
            }
        }
    }
    
    // Also check for standalone website field
    if (empty($social_links['website'])) {
        $website = get_post_meta($artist_id, 'artist_website', true);
        if (!empty($website)) {
            $social_links['website'] = $website;
        }
    }
    
    // Also check for email field
    if (empty($social_links['email'])) {
        $email = get_post_meta($artist_id, 'artist_email', true);
        if (empty($email)) {
            $email = get_post_meta($artist_id, 'es_artist_email', true);
        }
        if (empty($email)) {
            $email = get_post_meta($artist_id, '_es_artist_email', true);
        }
        if (!empty($email) && is_email($email)) {
            $social_links['email'] = 'mailto:' . $email;
        }
    }
}

// Upcoming events count
$upcoming_events_count = 0;
if ($show_events && function_exists('ensemble_get_artist_event_count')) {
    $upcoming_events_count = ensemble_get_artist_event_count($artist_id, true);
}

// Card classes
$card_classes = array(
    'es-kongress-speaker-card',
    'es-card-style-' . esc_attr($card_style)
);

// IMPORTANT: If social links are shown, card must be div (no nested <a> tags!)
// The name/image will be wrapped in a link instead
$has_social = $show_social && !empty($social_links);
$card_tag = ($show_link && !$has_social) ? 'a' : 'div';
$card_href = ($card_tag === 'a') ? ' href="' . esc_url($permalink) . '"' : '';

// Aliases for other card styles that don't have social links
$tag = $show_link ? 'a' : 'div';
$href = $show_link ? ' href="' . esc_url($permalink) . '"' : '';
?>

<?php 
// =============================================================================
// STYLE: MINIMAL - Very reduced, just name and role
// =============================================================================
if ($card_style === 'minimal'): ?>

<<?php echo $tag . $href; ?> class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <div class="es-kongress-speaker-content">
        <?php if ($show_name): ?>
        <h3 class="es-kongress-speaker-name"><?php echo esc_html($name); ?></h3>
        <?php endif; ?>
        
        <?php 
        $role_parts = array();
        if ($show_position && $position) $role_parts[] = $position;
        if ($show_company && $company) $role_parts[] = $company;
        if (!empty($role_parts)): 
        ?>
        <div class="es-kongress-speaker-role">
            <?php echo esc_html(implode(' · ', $role_parts)); ?>
        </div>
        <?php endif; ?>
    </div>
</<?php echo $tag; ?>>

<?php 
// =============================================================================
// STYLE: OVERLAY - Full image with text overlay at bottom
// =============================================================================
elseif ($card_style === 'overlay'): ?>

<<?php echo $tag . $href; ?> class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <div class="es-kongress-overlay-image">
        <?php if ($show_image && $image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        <?php else: ?>
        <div class="es-kongress-overlay-placeholder"></div>
        <?php endif; ?>
        
        <div class="es-kongress-overlay-gradient"></div>
        
        <div class="es-kongress-overlay-content">
            <?php if ($show_genre && $genre_name): ?>
            <div class="es-kongress-speaker-genre"><?php echo esc_html($genre_name); ?></div>
            <?php endif; ?>
            
            <?php if ($show_name): ?>
            <h3 class="es-kongress-speaker-name"><?php echo esc_html($name); ?></h3>
            <?php endif; ?>
            
            <?php if ($show_position && $position): ?>
            <div class="es-kongress-speaker-role"><?php echo esc_html($position); ?></div>
            <?php endif; ?>
        </div>
    </div>
</<?php echo $tag; ?>>

<?php 
// =============================================================================
// STYLE: COMPACT - Small horizontal card
// =============================================================================
elseif ($card_style === 'compact'): ?>

<<?php echo $tag . $href; ?> class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <?php if ($show_image): ?>
    <div class="es-kongress-compact-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        <?php else: ?>
        <div class="es-kongress-compact-placeholder">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 24px; height: 24px;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="es-kongress-compact-content">
        <?php if ($show_name): ?>
        <h3 class="es-kongress-speaker-name"><?php echo esc_html($name); ?></h3>
        <?php endif; ?>
        <?php if ($show_position && $position): ?>
        <div class="es-kongress-speaker-role"><?php echo esc_html($position); ?></div>
        <?php endif; ?>
    </div>
</<?php echo $tag; ?>>

<?php 
// =============================================================================
// STYLE: CIRCLE - Circular image, centered text
// =============================================================================
elseif ($card_style === 'circle'): ?>

<<?php echo $tag . $href; ?> class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <?php if ($show_image): ?>
    <div class="es-kongress-circle-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        <?php else: ?>
        <div class="es-kongress-circle-placeholder">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 48px; height: 48px;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="es-kongress-speaker-info">
        <?php if ($show_name): ?>
        <h3 class="es-kongress-speaker-name"><?php echo esc_html($name); ?></h3>
        <?php endif; ?>
        
        <?php if ($show_position && $position): ?>
        <div class="es-kongress-speaker-role"><?php echo esc_html($position); ?></div>
        <?php endif; ?>
        
        <?php if ($show_company && $company): ?>
        <div class="es-kongress-speaker-company"><?php echo esc_html($company); ?></div>
        <?php endif; ?>
    </div>
</<?php echo $tag; ?>>

<?php 
// =============================================================================
// STYLE: DEFAULT - Professional card with hover overlay
// =============================================================================
else: ?>

<div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
    <?php if ($show_image): ?>
    <div class="es-kongress-speaker-image">
        <?php if ($show_link): ?><a href="<?php echo esc_url($permalink); ?>"><?php endif; ?>
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        <?php else: ?>
        <div class="es-kongress-speaker-placeholder">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 48px; height: 48px;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <?php endif; ?>
        <?php if ($show_link): ?></a><?php endif; ?>
        
        <!-- Hover Overlay -->
        <?php if ($show_bio && $bio || $show_link): ?>
        <div class="es-kongress-speaker-overlay">
            <?php if ($show_bio && $bio): ?>
            <div class="es-kongress-speaker-preview"><?php echo esc_html($bio); ?></div>
            <?php endif; ?>
            <?php if ($show_link): ?>
            <a href="<?php echo esc_url($permalink); ?>" class="es-kongress-speaker-readmore"><?php echo esc_html($link_text); ?> →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="es-kongress-speaker-info">
        <?php if ($show_name): ?>
        <h3 class="es-kongress-speaker-name">
            <?php if ($show_link): ?><a href="<?php echo esc_url($permalink); ?>"><?php endif; ?>
            <?php echo esc_html($name); ?>
            <?php if ($show_link): ?></a><?php endif; ?>
        </h3>
        <?php endif; ?>
        
        <?php if ($show_position && $position): ?>
        <div class="es-kongress-speaker-role"><?php echo esc_html($position); ?></div>
        <?php endif; ?>
        
        <?php if ($show_company && $company): ?>
        <div class="es-kongress-speaker-company"><?php echo esc_html($company); ?></div>
        <?php endif; ?>
        
        <?php if ($show_genre && $genre_name): ?>
        <div class="es-kongress-speaker-genre"><?php echo esc_html($genre_name); ?></div>
        <?php endif; ?>
        
        <?php if ($show_type && $type_name): ?>
        <div class="es-kongress-speaker-type"><?php echo esc_html($type_name); ?></div>
        <?php endif; ?>
        
        <?php if ($show_events && $upcoming_events_count > 0): ?>
        <div class="es-kongress-speaker-sessions">
            <?php printf(_n('%d Session', '%d Sessions', $upcoming_events_count, 'ensemble'), $upcoming_events_count); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($show_social && !empty($social_links)): ?>
        <div class="es-kongress-speaker-social">
            <?php foreach ($social_links as $platform => $url): ?>
            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="es-social-link es-social-<?php echo esc_attr($platform); ?>" title="<?php echo esc_attr(ucfirst($platform)); ?>">
                <?php if ($platform === 'website'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <?php elseif ($platform === 'twitter'): ?>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                <?php elseif ($platform === 'linkedin'): ?>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                <?php elseif ($platform === 'instagram'): ?>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                <?php elseif ($platform === 'facebook'): ?>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <?php elseif ($platform === 'xing'): ?>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.188 0c-.517 0-.741.325-.927.66 0 0-7.455 13.224-7.702 13.657.015.024 4.919 9.023 4.919 9.023.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916c-.004-.006-.004-.016 0-.022L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zM3.648 4.74c-.211 0-.385.074-.473.216-.09.149-.078.339.02.531l2.34 4.05c.004.01.004.016 0 .021L1.86 16.051c-.099.188-.093.381 0 .529.085.142.239.234.45.234h3.461c.518 0 .766-.348.945-.667l3.734-6.609-2.378-4.155c-.172-.315-.434-.659-.962-.659H3.648v.016z"/></svg>
                <?php elseif ($platform === 'youtube'): ?>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                <?php elseif ($platform === 'github'): ?>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                <?php elseif ($platform === 'email'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php 
// Only output styles ONCE per page load (not for every card)
static $es_speaker_card_styles_output = false;
if (!$es_speaker_card_styles_output): 
$es_speaker_card_styles_output = true;
?>
<style>
/* =============================================================================
   SPEAKER CARD - BASE STYLES
   ============================================================================= */
.es-kongress-speaker-card {
    display: flex !important;
    flex-direction: column !important;
    background: var(--ensemble-card-bg);
    border: var(--ensemble-card-border-width) solid var(--ensemble-card-border);
    border-radius: var(--ensemble-card-radius);
    overflow: hidden !important;
    box-shadow: var(--ensemble-card-shadow);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    height: 100%; /* Gleiche Höhe in Grid */
    width: 100%;
    box-sizing: border-box;
    min-width: 0; /* Prevent flexbox overflow */
}

.es-kongress-speaker-card:hover {
    transform: var(--ensemble-card-hover-transform);
    box-shadow: var(--ensemble-card-hover-shadow);
}

/* Internal links within card */
.es-kongress-speaker-card a {
    text-decoration: none;
    color: inherit;
}

.es-kongress-speaker-name a {
    color: var(--ensemble-text);
    transition: color 0.3s ease;
}

.es-kongress-speaker-name a:hover {
    color: var(--ensemble-secondary, #B87333);
}

/* Image link wrapper */
.es-kongress-speaker-image > a {
    display: block;
    width: 100%;
    height: 100%;
}

/* Image Container - Fixed Aspect Ratio */
.es-kongress-speaker-image {
    position: relative;
    aspect-ratio: 1 / 1; /* Quadratisches Bild */
    overflow: hidden;
    flex-shrink: 0;
}

.es-kongress-speaker-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.es-kongress-speaker-card:hover .es-kongress-speaker-image img {
    transform: scale(1.05);
}

.es-kongress-speaker-placeholder {
    width: 100%;
    height: 100%;
    background: var(--ensemble-placeholder-bg, #f0f0f0);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ensemble-placeholder-icon, #999);
}

/* Info Section - Grows to fill remaining space */
.es-kongress-speaker-card .es-kongress-speaker-info {
    padding: var(--ensemble-card-padding, 20px);
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-width: 0; /* Prevent overflow */
    overflow: hidden;
}

.es-kongress-speaker-name {
    font-family: var(--ensemble-font-heading);
    font-size: var(--ensemble-lg-size);
    font-weight: var(--ensemble-heading-weight);
    color: var(--ensemble-text);
    margin: 0 0 4px 0;
    line-height: var(--ensemble-line-height-heading);
}

.es-kongress-speaker-role {
    font-size: var(--ensemble-small-size);
    color: var(--ensemble-secondary);
}

.es-kongress-speaker-company {
    font-size: var(--ensemble-small-size);
    color: var(--ensemble-text-secondary);
}

.es-kongress-speaker-genre,
.es-kongress-speaker-type {
    font-size: var(--ensemble-xs-size);
    color: var(--ensemble-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-top: 8px;
}

.es-kongress-speaker-sessions {
    margin-top: 8px;
    font-size: var(--ensemble-xs-size);
    color: var(--ensemble-text-secondary);
}

/* Social Links - Fixed positioning inside card */
.es-kongress-speaker-card .es-kongress-speaker-social {
    display: flex !important;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: auto;
    padding-top: 12px;
    width: 100%;
    box-sizing: border-box;
}

.es-kongress-speaker-card .es-kongress-speaker-social .es-social-link {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    min-width: 32px;
    min-height: 32px;
    border-radius: 50%;
    background: var(--ensemble-surface, #f5f5f5);
    color: var(--ensemble-text-secondary);
    transition: all 0.3s ease;
    text-decoration: none;
    flex-shrink: 0;
}

.es-kongress-speaker-card .es-kongress-speaker-social .es-social-link:hover {
    background: var(--ensemble-secondary, #B87333);
    color: #fff;
}

.es-kongress-speaker-card .es-kongress-speaker-social .es-social-link svg {
    width: 16px;
    height: 16px;
    max-width: 16px;
    max-height: 16px;
    flex-shrink: 0;
}

/* =============================================================================
   STYLE: DEFAULT - Professional card with hover overlay
   ============================================================================= */
.es-card-style-default .es-kongress-speaker-image {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
}

.es-card-style-default .es-kongress-speaker-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.es-card-style-default:hover .es-kongress-speaker-image img {
    transform: scale(1.05);
}

.es-card-style-default .es-kongress-speaker-placeholder {
    width: 100%;
    height: 100%;
    background: var(--ensemble-placeholder-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ensemble-placeholder-icon);
}

.es-card-style-default .es-kongress-speaker-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.6) 50%, rgba(0,0,0,0.2) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: var(--ensemble-card-padding);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.es-card-style-default:hover .es-kongress-speaker-overlay {
    opacity: 1;
}

.es-card-style-default .es-kongress-speaker-preview {
    color: rgba(255,255,255,0.9);
    font-size: var(--ensemble-small-size);
    line-height: 1.5;
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.es-card-style-default .es-kongress-speaker-readmore {
    color: #fff;
    font-size: var(--ensemble-small-size);
    font-weight: 600;
}

.es-card-style-default .es-kongress-speaker-info {
    padding: var(--ensemble-card-padding);
}

/* =============================================================================
   STYLE: MINIMAL
   ============================================================================= */
.es-card-style-minimal {
    background: transparent;
    border: none;
    box-shadow: none;
    border-bottom: 1px solid var(--ensemble-divider);
    border-radius: 0;
}

.es-card-style-minimal:hover {
    transform: none;
    box-shadow: none;
    background: var(--ensemble-surface);
}

.es-card-style-minimal .es-kongress-speaker-content {
    padding: 16px 0;
}

.es-card-style-minimal .es-kongress-speaker-name {
    font-size: var(--ensemble-base-size);
    margin-bottom: 2px;
}

.es-card-style-minimal .es-kongress-speaker-role {
    font-size: var(--ensemble-small-size);
    color: var(--ensemble-text-secondary);
}

/* =============================================================================
   STYLE: OVERLAY
   ============================================================================= */
.es-card-style-overlay {
    position: relative;
    aspect-ratio: 3/4;
}

.es-card-style-overlay .es-kongress-overlay-image {
    position: absolute;
    inset: 0;
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
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);
    z-index: 1;
}

.es-card-style-overlay .es-kongress-overlay-content {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: var(--ensemble-card-padding);
    z-index: 2;
    color: #fff;
}

.es-card-style-overlay .es-kongress-speaker-name {
    color: #fff;
}

.es-card-style-overlay .es-kongress-speaker-role {
    color: rgba(255,255,255,0.8);
}

.es-card-style-overlay .es-kongress-speaker-genre {
    color: var(--ensemble-primary-light, #fff);
    opacity: 0.9;
}

/* =============================================================================
   STYLE: COMPACT
   ============================================================================= */
.es-card-style-compact {
    display: flex;
    flex-direction: row;
    align-items: center;
}

.es-card-style-compact .es-kongress-compact-image {
    width: 64px;
    height: 64px;
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
    flex: 1;
    min-width: 0;
}

.es-card-style-compact .es-kongress-speaker-name {
    font-size: var(--ensemble-base-size);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.es-card-style-compact .es-kongress-speaker-role {
    font-size: var(--ensemble-xs-size);
}

/* =============================================================================
   STYLE: CIRCLE
   ============================================================================= */
.es-card-style-circle {
    text-align: center;
    background: transparent;
    border: none;
    box-shadow: none;
}

.es-card-style-circle:hover {
    transform: none;
    box-shadow: none;
}

.es-card-style-circle .es-kongress-circle-image {
    width: 140px;
    height: 140px;
    margin: 0 auto 16px;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: var(--ensemble-card-shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.es-card-style-circle:hover .es-kongress-circle-image {
    transform: scale(1.05);
    box-shadow: var(--ensemble-card-hover-shadow);
}

.es-card-style-circle .es-kongress-circle-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.es-card-style-circle .es-kongress-circle-placeholder {
    width: 100%;
    height: 100%;
    background: var(--ensemble-placeholder-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ensemble-placeholder-icon);
}

.es-card-style-circle .es-kongress-speaker-info {
    padding: 0;
}

/* =============================================================================
   RESPONSIVE
   ============================================================================= */
@media (max-width: 600px) {
    .es-card-style-default .es-kongress-speaker-overlay {
        opacity: 1;
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);
    }
    
    .es-card-style-default .es-kongress-speaker-preview {
        display: none;
    }
    
    .es-card-style-circle .es-kongress-circle-image {
        width: 100px;
        height: 100px;
    }
    
    .es-card-style-compact {
        flex-direction: column;
    }
    
    .es-card-style-compact .es-kongress-compact-image {
        width: 100%;
        height: 120px;
    }
}

/* =============================================================================
   GRID INTEGRATION FIX
   Ensures cards work correctly within the parent grid
   ============================================================================= */
.ensemble-artists-list .es-kongress-speaker-card,
.ensemble-artists-grid .es-kongress-speaker-card,
.es-slider__slide .es-kongress-speaker-card {
    display: flex !important;
    flex-direction: column !important;
    height: 100% !important;
    width: 100% !important;
}

/* Ensure social links stay inside card */
.es-kongress-speaker-card * {
    box-sizing: border-box;
}

.es-kongress-speaker-card .es-kongress-speaker-social a {
    position: static !important;
    float: none !important;
}
</style>
<?php endif; // End of one-time styles output ?>
