<?php
/**
 * Template: Kongress Speaker Card
 * 
 * @package Ensemble
 * @version 1.1.0
 */

if (!defined('ABSPATH')) exit;

// Get artist data
$artist_id = isset($artist_id) ? $artist_id : get_the_ID();
$name = get_the_title($artist_id);
$permalink = get_permalink($artist_id);
$image = get_the_post_thumbnail_url($artist_id, 'large');

// Professional info (NEW meta keys)
$position = get_post_meta($artist_id, '_es_artist_position', true);
$company = get_post_meta($artist_id, '_es_artist_company', true);
$additional = get_post_meta($artist_id, '_es_artist_additional', true);

// Get bio/excerpt for overlay
$bio = get_post_meta($artist_id, '_artist_bio', true);
if (!$bio) {
    $bio = get_the_excerpt($artist_id);
}
if (!$bio) {
    $post_obj = get_post($artist_id);
    if ($post_obj && !empty($post_obj->post_content)) {
        $bio = wp_trim_words(strip_tags($post_obj->post_content), 30);
    }
}

// Genres/Tags
$genres = get_the_terms($artist_id, 'ensemble_genre');
?>

<a href="<?php echo esc_url($permalink); ?>" class="es-kongress-speaker-card">
    <div class="es-kongress-speaker-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        <?php else: ?>
        <div style="width: 100%; height: 100%; background: var(--ensemble-placeholder-bg); display: flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-placeholder-icon)" stroke-width="1" style="width: 48px; height: 48px;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <?php endif; ?>
        
        <!-- Hover Overlay -->
        <div class="es-kongress-speaker-overlay">
            <?php if ($bio): ?>
            <div class="es-kongress-speaker-preview"><?php echo esc_html($bio); ?></div>
            <?php endif; ?>
            <span class="es-kongress-speaker-readmore"><?php _e('Read more', 'ensemble'); ?> →</span>
        </div>
    </div>
    
    <div class="es-kongress-speaker-info">
        <h3 class="es-kongress-speaker-name"><?php echo esc_html($name); ?></h3>
        
        <?php if ($position): ?>
        <div class="es-kongress-speaker-role"><?php echo esc_html($position); ?></div>
        <?php endif; ?>
        
        <?php if ($company): ?>
        <div class="es-kongress-speaker-company"><?php echo esc_html($company); ?></div>
        <?php endif; ?>
    </div>
</a>
