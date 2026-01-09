<?php
/**
 * Template: Stage Artist Card - Full
 * Card with image, name, genre badge, and button
 * Used for layout="cards"
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// Support both $artist array and direct variables
$artist_id = isset($artist['id']) ? $artist['id'] : ($artist_id ?? 0);
$name = isset($artist['name']) ? $artist['name'] : (isset($artist['title']) ? $artist['title'] : '');
$image = isset($artist['image']) ? $artist['image'] : (isset($artist['featured_image']) ? $artist['featured_image'] : '');
$genre = isset($artist['genre']) ? $artist['genre'] : '';
$permalink = isset($artist['permalink']) ? $artist['permalink'] : '#';
$link_target = isset($artist['link_target']) ? $artist['link_target'] : '_self';

// Shortcode attributes
$show_link = isset($shortcode_atts['show_link']) ? filter_var($shortcode_atts['show_link'], FILTER_VALIDATE_BOOLEAN) : true;
$link_text = isset($shortcode_atts['link_text']) ? $shortcode_atts['link_text'] : __('View Profile', 'ensemble');
?>

<article class="ensemble-artist-card es-stage-card es-stage-card--full">
    
    <!-- Image -->
    <a href="<?php echo esc_url($permalink); ?>" class="es-stage-card-image-link" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
        <div class="es-stage-card-image">
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
        </div>
    </a>
    
    <!-- Content -->
    <div class="es-stage-card-body">
        
        <!-- Name -->
        <h3 class="es-stage-card-title">
            <a href="<?php echo esc_url($permalink); ?>" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
                <?php echo esc_html($name); ?>
            </a>
        </h3>
        
        <!-- Genre (klein) -->
        <?php if ($genre): ?>
        <span class="es-stage-card-meta"><?php echo esc_html($genre); ?></span>
        <?php endif; ?>
        
        <!-- Button -->
        <?php if ($show_link): ?>
        <a href="<?php echo esc_url($permalink); ?>" 
           class="es-stage-btn es-stage-btn-outline"
           <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
            <?php echo esc_html($link_text); ?>
        </a>
        <?php endif; ?>
        
    </div>
    
</article>
