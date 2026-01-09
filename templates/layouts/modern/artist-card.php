<?php
/**
 * Template: Artist Card - Noir Elegance
 * Full-width dark mode, content overlay on image
 * 
 * @package Ensemble
 * @version 3.1.0
 */

if (!defined('ABSPATH')) exit;

$artist_id = $artist['id'];
$title = $artist['title'];
$permalink = $artist['permalink'];
$featured_image = $artist['featured_image'];
$genre = $artist['genre'];
$event_count = $artist['event_count'];

// Parse genres - could be array, comma-separated string, or single value
$genres = array();
if (!empty($genre)) {
    if (is_array($genre)) {
        $genres = $genre;
    } else {
        $genres = array_map('trim', explode(',', $genre));
    }
}
?>

<article class="es-noir-card es-noir-card-artist">
    <a href="<?php echo esc_url($permalink); ?>" class="es-noir-card-inner">
        
        <div class="es-noir-card-media">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-noir-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="es-noir-card-gradient"></div>
        </div>
        
        <div class="es-noir-card-content">
            <h3 class="es-noir-title"><?php echo esc_html($title); ?></h3>
            
            <?php if (!empty($genres)): ?>
            <div class="es-noir-genre-tags">
                <?php foreach ($genres as $g): ?>
                <span class="es-noir-genre-tag"><?php echo esc_html($g); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($event_count): ?>
            <div class="es-noir-meta">
                <span><?php printf(_n('%s Event', '%s Events', $event_count, 'ensemble'), number_format_i18n($event_count)); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <span class="es-noir-arrow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </span>
        
    </a>
</article>
