<?php
/**
 * Artist Card Template - Bristol Editorial Style
 * Minimal default, dramatic fullscreen hover
 */
if (!defined('ABSPATH')) exit;

$artist_id = get_the_ID();
$artist = es_load_artist_data($artist_id);
$permalink = get_permalink($artist_id);

// Image handling
$has_image = has_post_thumbnail();
$image_url = '';
if (!$has_image && !empty($artist['featured_image'])) {
    $image_url = $artist['featured_image'];
    $has_image = true;
}

// Genre & Origin
$genre = !empty($artist['genre']) ? $artist['genre'] : '';
$origin = !empty($artist['origin']) ? $artist['origin'] : '';
?>

<article class="es-bristol-card es-bristol-artist-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-bristol-card-link">
        <!-- Media -->
        <div class="es-bristol-card-media">
            <?php if (has_post_thumbnail()): ?>
                <?php the_post_thumbnail('large'); ?>
            <?php elseif ($image_url): ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title_attribute(); ?>">
            <?php else: ?>
                <div class="es-bristol-card-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Default: Just title -->
        <div class="es-bristol-card-title-bar">
            <h3 class="es-bristol-card-title"><?php the_title(); ?></h3>
        </div>
        
        <!-- Hover: Full info panel -->
        <div class="es-bristol-card-info">
            <?php if ($genre): ?>
            <span class="es-bristol-card-badge"><?php echo esc_html($genre); ?></span>
            <?php endif; ?>
            
            <h3 class="es-bristol-card-info-title"><?php the_title(); ?></h3>
            
            <div class="es-bristol-card-meta-list">
                <?php if ($origin): ?>
                <span class="es-bristol-card-meta-item">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <?php echo esc_html($origin); ?>
                </span>
                <?php endif; ?>
                

            </div>
        </div>
        
        <!-- CTA Arrow -->
        <span class="es-bristol-card-cta">
            <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </span>
    </a>
</article>
