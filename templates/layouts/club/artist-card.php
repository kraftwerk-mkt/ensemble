<?php
/**
 * Template: Club Artist Card
 * Dark nightclub style artist card
 * Vertical layout like event cards: Image top, info bottom
 * 
 * @package Ensemble
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

$artist_id = get_the_ID();
$name = get_the_title();
$permalink = get_permalink();
$featured_image = get_the_post_thumbnail_url($artist_id, 'medium_large');
$origin = get_post_meta($artist_id, '_artist_origin', true);
$bio = get_post_meta($artist_id, '_artist_bio', true);
if (!$bio) {
    $bio = get_the_excerpt();
}

// Get genres from taxonomy
$genres = get_the_terms($artist_id, 'ensemble_genre');
if (is_wp_error($genres)) {
    $genres = array();
}

// Fallback to meta field if no taxonomy genres
if (empty($genres)) {
    $genre_meta = get_post_meta($artist_id, '_artist_genre', true);
    if ($genre_meta) {
        // Split by comma if multiple
        $genre_names = array_map('trim', explode(',', $genre_meta));
        $genres = array();
        foreach ($genre_names as $gname) {
            $obj = new stdClass();
            $obj->name = $gname;
            $genres[] = $obj;
        }
    }
}

// Get upcoming event count for this artist
$upcoming_count = 0;
if (function_exists('ensemble_get_artist_events')) {
    $events = ensemble_get_artist_events($artist_id, array('upcoming' => true, 'limit' => 99));
    $upcoming_count = count($events);
}
?>

<article class="ensemble-event-card es-club-card es-club-artist-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-club-link">
        
        <!-- Image - Full Width, No Padding -->
        <div class="es-club-image">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-club-placeholder"></div>
            <?php endif; ?>
            
            <!-- Event Count Badge (on image) -->
            <?php if ($upcoming_count > 0): ?>
            <div class="es-club-count-badge">
                <span class="es-club-count-number"><?php echo $upcoming_count; ?></span>
                <span class="es-club-count-label"><?php echo $upcoming_count === 1 ? __('Event', 'ensemble') : __('Events', 'ensemble'); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Content - Has Padding -->
        <div class="es-club-content">
            
            <h3 class="es-club-title"><?php echo esc_html($name); ?></h3>
            
            <?php if ($origin): ?>
            <div class="es-club-meta">
                <span class="es-club-location"><?php echo esc_html($origin); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($genres)): ?>
            <div class="es-club-genre-tags">
                <?php foreach ($genres as $genre): ?>
                <span class="es-club-genre-tag"><?php echo esc_html($genre->name); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
        </div>
        
    </a>
</article>
