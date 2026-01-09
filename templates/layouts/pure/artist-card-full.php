<?php
/**
 * Template: Pure Artist Card Full
 * Image + name + bio excerpt + ghost button
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

$artist_id = isset($artist['id']) ? $artist['id'] : 0;
$name = isset($artist['name']) ? $artist['name'] : (isset($artist['title']) ? $artist['title'] : '');
$permalink = isset($artist['permalink']) ? $artist['permalink'] : '#';
$image = isset($artist['image']) ? $artist['image'] : (isset($artist['featured_image']) ? $artist['featured_image'] : '');
$genre = isset($artist['genre']) ? $artist['genre'] : '';
$bio = isset($artist['bio']) ? $artist['bio'] : (isset($artist['excerpt']) ? $artist['excerpt'] : '');
?>

<article class="es-pure-card es-pure-artist-card es-pure-card-full">
    
    <!-- Image -->
    <a href="<?php echo esc_url($permalink); ?>" class="es-pure-card-image">
        <?php if ($image): ?>
            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        <?php else: ?>
            <div class="es-pure-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        <?php endif; ?>
    </a>
    
    <!-- Body -->
    <div class="es-pure-card-body">
        <h3 class="es-pure-card-name"><?php echo esc_html($name); ?></h3>
        
        <?php if ($genre): ?>
        <div class="es-pure-card-meta"><?php echo esc_html($genre); ?></div>
        <?php endif; ?>
        
        <?php if ($bio): ?>
        <p class="es-pure-card-excerpt es-pure-text-secondary" style="margin-top: 12px; font-size: var(--pure-small);">
            <?php echo esc_html(wp_trim_words($bio, 15, '...')); ?>
        </p>
        <?php endif; ?>
        
        <a href="<?php echo esc_url($permalink); ?>" class="es-pure-btn es-pure-btn-ghost">
            <?php _e('View Profile', 'ensemble'); ?>
        </a>
    </div>
    
</article>
