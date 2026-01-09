<?php
/**
 * Template: Lovepop Artist Card - Full
 * Card style with image, name, genre, bio and button
 * Used for layout="cards"
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// Support both $artist array (from shortcode) and direct variables
$artist_id = isset($artist['id']) ? $artist['id'] : ($artist_id ?? 0);
$name = isset($artist['name']) ? $artist['name'] : (isset($artist['title']) ? $artist['title'] : '');
$image = isset($artist['image']) ? $artist['image'] : (isset($artist['featured_image']) ? $artist['featured_image'] : '');
$genre = isset($artist['genre']) ? $artist['genre'] : '';
$excerpt = isset($artist['excerpt']) ? $artist['excerpt'] : '';
$permalink = isset($artist['permalink']) ? $artist['permalink'] : '#';
$link_target = isset($artist['link_target']) ? $artist['link_target'] : '_self';

// Shortcode attributes
$show_bio = isset($shortcode_atts['show_bio']) ? filter_var($shortcode_atts['show_bio'], FILTER_VALIDATE_BOOLEAN) : true;
$show_link = isset($shortcode_atts['show_link']) ? filter_var($shortcode_atts['show_link'], FILTER_VALIDATE_BOOLEAN) : true;
$link_text = isset($shortcode_atts['link_text']) ? $shortcode_atts['link_text'] : __('View Profile', 'ensemble');
?>

<article class="ensemble-artist-card es-lovepop-card es-lovepop-artist-card-full">
    
    <!-- Image Header -->
    <a href="<?php echo esc_url($permalink); ?>" class="es-lovepop-card-image-link" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
        <div class="es-lovepop-card-image">
            <?php if ($image): ?>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-lovepop-placeholder-bg es-lovepop-placeholder-artist">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
    </a>
    
    <!-- Content -->
    <div class="es-lovepop-card-content">
        
        <!-- Genre Badge -->
        <?php if ($genre): ?>
        <span class="es-lovepop-card-badge"><?php echo esc_html($genre); ?></span>
        <?php endif; ?>
        
        <!-- Name -->
        <h3 class="es-lovepop-card-title">
            <a href="<?php echo esc_url($permalink); ?>" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
                <?php echo esc_html($name); ?>
            </a>
        </h3>
        
        <!-- Bio -->
        <?php if ($show_bio && $excerpt): ?>
        <p class="es-lovepop-card-excerpt"><?php echo esc_html(wp_trim_words($excerpt, 15)); ?></p>
        <?php endif; ?>
        
        <!-- Button -->
        <?php if ($show_link): ?>
        <a href="<?php echo esc_url($permalink); ?>" 
           class="es-lovepop-btn es-lovepop-btn-outline"
           <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
            <?php echo esc_html($link_text); ?>
        </a>
        <?php endif; ?>
        
    </div>
    
</article>
