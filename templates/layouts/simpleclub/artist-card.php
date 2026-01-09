<?php
/**
 * Template: Simple Club Artist Card
 * Fullscreen overlay style - Name visible by default, Genre on hover
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Support both $artist array (from shortcode) and direct variables
$artist_id = isset($artist['id']) ? $artist['id'] : ($artist_id ?? 0);
$name = isset($artist['name']) ? $artist['name'] : (isset($artist['title']) ? $artist['title'] : '');
$image = isset($artist['image']) ? $artist['image'] : (isset($artist['featured_image']) ? $artist['featured_image'] : '');
$genre = isset($artist['genre']) ? $artist['genre'] : '';
$permalink = isset($artist['permalink']) ? $artist['permalink'] : '#';
$link_target = isset($artist['link_target']) ? $artist['link_target'] : '_self';
?>

<article class="ensemble-artist-card es-simpleclub-card es-simpleclub-card--overlay es-simpleclub-artist-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-simpleclub-card-link" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
        
        <!-- Background Image -->
        <div class="es-simpleclub-card-bg">
            <?php if ($image): ?>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-simpleclub-placeholder-bg es-simpleclub-placeholder-artist">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="es-simpleclub-card-gradient"></div>
        </div>
        
        <!-- Content Overlay -->
        <div class="es-simpleclub-card-overlay es-simpleclub-artist-overlay">
            
            <!-- Name (visible by default) -->
            <div class="es-simpleclub-card-date es-simpleclub-artist-name-wrap">
                <span class="es-simpleclub-artist-name"><?php echo esc_html($name); ?></span>
            </div>
            
            <!-- Genres (hidden by default, appears on hover) -->
            <?php if ($genre): ?>
            <div class="es-simpleclub-card-artists es-simpleclub-artist-genres">
                <?php echo esc_html($genre); ?>
            </div>
            <?php endif; ?>
            
        </div>
        
    </a>
</article>
