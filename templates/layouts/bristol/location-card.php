<?php
/**
 * Location Card Template - Bristol Editorial Style
 * Minimal default, dramatic fullscreen hover
 */
if (!defined('ABSPATH')) exit;

$location_id = get_the_ID();
$location = es_load_location_data($location_id);
$permalink = get_permalink($location_id);

// Image handling
$has_image = has_post_thumbnail();
$image_url = '';
if (!$has_image && !empty($location['featured_image'])) {
    $image_url = $location['featured_image'];
    $has_image = true;
}

// Category
$location_cats = get_the_terms($location_id, 'location_category');
$category = ($location_cats && !is_wp_error($location_cats)) ? $location_cats[0]->name : '';

// Address
$address = !empty($location['address']) ? $location['address'] : '';
$city = !empty($location['city']) ? $location['city'] : '';
?>

<article class="es-bristol-card es-bristol-location-card">
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
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
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
            <?php if ($category): ?>
            <span class="es-bristol-card-badge"><?php echo esc_html($category); ?></span>
            <?php endif; ?>
            
            <h3 class="es-bristol-card-info-title"><?php the_title(); ?></h3>
            
            <div class="es-bristol-card-meta-list">
                <?php if ($address): ?>
                <span class="es-bristol-card-meta-item">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?php echo esc_html($address); ?>
                </span>
                <?php endif; ?>
                
                <?php if ($city): ?>
                <span class="es-bristol-card-meta-item">
                    <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <?php echo esc_html($city); ?>
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
