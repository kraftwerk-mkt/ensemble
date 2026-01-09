<?php
/**
 * Template: Kinky Artist Card
 * Sensual dark design with elegant typography
 * 
 * @package Ensemble
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

// Artist data
$artist_id = $artist['id'] ?? 0;
$name = $artist['name'] ?? '';
$permalink = $artist['permalink'] ?? '';
$featured_image = $artist['featured_image'] ?? '';
$role = $artist['role'] ?? '';
$short_bio = $artist['short_bio'] ?? '';

// Display settings
$show_image = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('image');
$show_role = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('role');
?>

<article class="ensemble-artist-card es-kinky-artist-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-kinky-link">
        
        <?php if ($show_image): ?>
        <div class="es-kinky-artist-image">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-kinky-placeholder"></div>
            <?php endif; ?>
            <div class="es-kinky-gradient"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-kinky-artist-content">
            <h3 class="es-kinky-artist-name"><?php echo esc_html($name); ?></h3>
            
            <?php if ($role && $show_role): ?>
            <span class="es-kinky-artist-role"><?php echo esc_html($role); ?></span>
            <?php endif; ?>
        </div>
        
    </a>
</article>
