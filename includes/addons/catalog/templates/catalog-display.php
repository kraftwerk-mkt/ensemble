<?php
/**
 * Catalog Display Frontend Template
 * 
 * Available variables:
 * - $catalog (WP_Post)
 * - $catalog_id (int)
 * - $type (string)
 * - $type_config (array)
 * - $categories (array of WP_Term)
 * - $items (array)
 * - $by_cat (array - items grouped by category)
 * - $atts (shortcode attributes)
 */
if (!defined('ABSPATH')) exit;

$show_prices = $atts['show_prices'] !== 'false';
$show_images = $atts['show_images'] === 'true';
$show_filter = $atts['show_filter'] === 'true';
$show_title = !isset($atts['show_title']) || $atts['show_title'] !== 'false';
$layout = $atts['layout'];
$columns = intval($atts['columns']) ?: 1;

$layout_class = 'es-catalog-layout-' . $layout;
if ($columns > 1) {
    $layout_class .= ' es-catalog-cols-' . $columns;
}
?>

<div class="es-catalog <?php echo esc_attr($layout_class); ?>" data-catalog-id="<?php echo $catalog_id; ?>" data-type="<?php echo esc_attr($type); ?>">
    
    <?php if ($show_title && $catalog): ?>
        <div class="es-catalog-header">
            <h2 class="es-catalog-title"><?php echo esc_html($catalog->post_title); ?></h2>
            <?php if ($catalog->post_excerpt): ?>
                <p class="es-catalog-subtitle"><?php echo esc_html($catalog->post_excerpt); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($show_filter && count($categories) > 1): ?>
        <div class="es-catalog-filter">
            <button class="es-filter-btn active" data-category="all">
                <?php _e('Alle', 'ensemble'); ?>
            </button>
            <?php foreach ($categories as $cat): ?>
                <button class="es-filter-btn" data-category="<?php echo $cat->term_id; ?>">
                    <?php echo esc_html($cat->name); ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="es-catalog-content">
        <?php foreach ($categories as $cat): 
            $cat_items = $by_cat[$cat->term_id] ?? array();
            if (empty($cat_items)) continue;
            
            $cat_color = get_term_meta($cat->term_id, '_category_color', true);
        ?>
            <div class="es-catalog-category" data-category-id="<?php echo $cat->term_id; ?>">
                <div class="es-category-header" <?php if ($cat_color): ?>style="border-color: <?php echo esc_attr($cat_color); ?>"<?php endif; ?>>
                    <h3 class="es-category-title"><?php echo esc_html($cat->name); ?></h3>
                    <?php if ($cat->description): ?>
                        <p class="es-category-desc"><?php echo esc_html($cat->description); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="es-catalog-items">
                    <?php foreach ($cat_items as $item): 
                        $attrs = $item['attributes'];
                        $price = isset($attrs['price']) && $attrs['price'] ? floatval($attrs['price']) : null;
                        $price_type = $attrs['price_type'] ?? '';
                    ?>
                        <div class="es-catalog-item">
                            <?php if ($show_images && $item['image']): ?>
                                <div class="es-item-image">
                                    <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="es-item-info">
                                <div class="es-item-header">
                                    <h4 class="es-item-name">
                                        <?php echo esc_html($item['title']); ?>
                                        <?php 
                                        // Badges - Text only, no emojis
                                        if (!empty($attrs['new'])): ?><span class="es-badge es-badge-new"><?php _e('Neu', 'ensemble'); ?></span><?php endif;
                                        if (!empty($attrs['highlight'])): ?><span class="es-badge es-badge-highlight"><?php _e('Empfehlung', 'ensemble'); ?></span><?php endif;
                                        if (!empty($attrs['vegan'])): ?><span class="es-badge es-badge-vegan"><?php _e('Vegan', 'ensemble'); ?></span><?php endif;
                                        if (!empty($attrs['vegetarian'])): ?><span class="es-badge es-badge-veg"><?php _e('Vegetarisch', 'ensemble'); ?></span><?php endif;
                                        if (!empty($attrs['sale'])): ?><span class="es-badge es-badge-sale"><?php _e('Sale', 'ensemble'); ?></span><?php endif;
                                        if (!empty($attrs['limited'])): ?><span class="es-badge es-badge-limited"><?php _e('Limitiert', 'ensemble'); ?></span><?php endif;
                                        if (!empty($attrs['popular'])): ?><span class="es-badge es-badge-popular"><?php _e('Beliebt', 'ensemble'); ?></span><?php endif;
                                        ?>
                                    </h4>
                                    
                                    <?php if ($show_prices && $price !== null): ?>
                                        <span class="es-item-price">
                                            <?php 
                                            if ($price_type === 'on_request') {
                                                _e('Auf Anfrage', 'ensemble');
                                            } else {
                                                echo number_format($price, 2, ',', '.') . ' €';
                                                if ($price_type === 'per_person') echo ' <small>p.P.</small>';
                                                if ($price_type === 'per_hour') echo ' <small>/Std.</small>';
                                                if ($price_type === 'per_day') echo ' <small>/Tag</small>';
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($item['description']): ?>
                                    <p class="es-item-desc"><?php echo esc_html($item['description']); ?></p>
                                <?php endif; ?>
                                
                                <?php 
                                // Type-specific details
                                $details = array();
                                
                                // Menu/Drinks
                                if (!empty($attrs['volume'])) $details[] = $attrs['volume'];
                                if (!empty($attrs['allergens'])) $details[] = '<span class="es-allergens">Allergene: ' . esc_html($attrs['allergens']) . '</span>';
                                if (!empty($attrs['portion_size'])) $details[] = $attrs['portion_size'];
                                if (!empty($attrs['spicy'])) {
                                    $spicy_icons = str_repeat('🌶️', intval($attrs['spicy']));
                                    $details[] = $spicy_icons;
                                }
                                
                                // Merchandise
                                if (!empty($attrs['sizes']) && is_array($attrs['sizes'])) {
                                    $details[] = 'Sizes: ' . implode(', ', $attrs['sizes']);
                                }
                                if (!empty($attrs['colors'])) $details[] = 'Colors: ' . $attrs['colors'];
                                
                                // Rooms
                                if (!empty($attrs['capacity_standing'])) $details[] = 'Standing: ' . $attrs['capacity_standing'] . ' Pers.';
                                if (!empty($attrs['capacity_seated'])) $details[] = 'Seated: ' . $attrs['capacity_seated'] . ' Pers.';
                                if (!empty($attrs['area'])) $details[] = $attrs['area'] . ' m²';
                                
                                // Services
                                if (!empty($attrs['duration'])) $details[] = 'Duration: ' . $attrs['duration'];
                                if (!empty($attrs['max_persons'])) $details[] = 'Max. ' . $attrs['max_persons'] . ' Pers.';
                                
                                // Courses
                                if (!empty($attrs['level'])) {
                                    $levels = array('beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Expert', 'all' => 'All Levels');
                                    $details[] = $levels[$attrs['level']] ?? $attrs['level'];
                                }
                                
                                if ($details): 
                                ?>
                                    <div class="es-item-details">
                                        <?php echo implode(' · ', $details); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($attrs['includes'])): ?>
                                    <div class="es-item-includes">
                                        <strong><?php _e('Inklusive:', 'ensemble'); ?></strong>
                                        <?php echo nl2br(esc_html($attrs['includes'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($attrs['amenities']) && is_array($attrs['amenities'])): ?>
                                    <div class="es-item-amenities">
                                        <?php 
                                        $amenity_labels = array(
                                            'projector' => 'Projector',
                                            'sound' => 'Sound',
                                            'wifi' => 'WiFi',
                                            'kitchen' => 'Kitchen',
                                            'bar' => 'Bar',
                                            'stage' => 'Stage',
                                            'parking' => 'Parking',
                                            'accessible' => 'Accessible'
                                        );
                                        foreach ($attrs['amenities'] as $amenity) {
                                            echo '<span class="es-amenity">' . ($amenity_labels[$amenity] ?? $amenity) . '</span>';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php 
        // Uncategorized items
        $uncategorized = $by_cat[0] ?? array();
        if ($uncategorized): 
        ?>
            <div class="es-catalog-category es-catalog-uncategorized">
                <div class="es-catalog-items">
                    <?php foreach ($uncategorized as $item): 
                        $attrs = $item['attributes'];
                        $price = isset($attrs['price']) && $attrs['price'] ? floatval($attrs['price']) : null;
                    ?>
                        <div class="es-catalog-item">
                            <div class="es-item-info">
                                <div class="es-item-header">
                                    <h4 class="es-item-name"><?php echo esc_html($item['title']); ?></h4>
                                    <?php if ($show_prices && $price): ?>
                                        <span class="es-item-price"><?php echo number_format($price, 2, ',', '.'); ?> €</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($item['description']): ?>
                                    <p class="es-item-desc"><?php echo esc_html($item['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
