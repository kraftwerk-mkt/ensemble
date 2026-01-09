<?php
/**
 * Sponsors Display Template
 * 
 * Handles: carousel, grid, bar, marquee styles
 *
 * @package Ensemble
 * @subpackage Addons/Sponsors
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables from shortcode/render function
$style = isset($atts['style']) ? sanitize_key($atts['style']) : 'carousel';
$columns = isset($atts['columns']) ? absint($atts['columns']) : 4;
$title = isset($atts['title']) ? sanitize_text_field($atts['title']) : '';
$extra_class = isset($atts['class']) ? sanitize_html_class($atts['class']) : '';
$height = isset($height) ? absint($height) : 60;
$grayscale = isset($grayscale) ? (bool) $grayscale : false;
$grayscale_hover = isset($settings['grayscale_hover']) ? (bool) $settings['grayscale_hover'] : true;

// Wrapper classes
$wrapper_class = 'es-sponsors';
$wrapper_class .= ' es-sponsors--' . $style;
if ($grayscale) {
    $wrapper_class .= ' es-sponsors--grayscale';
}
if ($grayscale && $grayscale_hover) {
    $wrapper_class .= ' es-sponsors--grayscale-hover';
}
if ($extra_class) {
    $wrapper_class .= ' ' . $extra_class;
}

// Style variables
$style_vars = '--es-sponsor-height: ' . $height . 'px;';
$style_vars .= '--es-sponsor-columns: ' . $columns . ';';
?>

<div class="<?php echo esc_attr($wrapper_class); ?>" style="<?php echo esc_attr($style_vars); ?>">
    
    <?php if (!empty($title)): ?>
    <h3 class="es-sponsors__title"><?php echo esc_html($title); ?></h3>
    <?php endif; ?>
    
    <?php if ($style === 'carousel'): ?>
    <!-- Carousel Style -->
    <div class="es-sponsors__carousel" data-autoplay="<?php echo esc_attr($settings['carousel_autoplay'] ? 'true' : 'false'); ?>" data-speed="<?php echo esc_attr($settings['carousel_speed']); ?>">
        <div class="es-sponsors__track">
            <?php foreach ($sponsors as $sponsor): ?>
            <div class="es-sponsors__slide">
                <?php $this->render_sponsor_item($sponsor, $height); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="es-sponsors__nav es-sponsors__nav--prev" aria-label="<?php _e('Previous', 'ensemble'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </button>
        <button class="es-sponsors__nav es-sponsors__nav--next" aria-label="<?php _e('Next', 'ensemble'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m9 18 6-6-6-6"/>
            </svg>
        </button>
    </div>
    
    <?php elseif ($style === 'grid'): ?>
    <!-- Grid Style -->
    <div class="es-sponsors__grid">
        <?php foreach ($sponsors as $sponsor): ?>
        <div class="es-sponsors__item">
            <?php $this->render_sponsor_item($sponsor, $height); ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php elseif ($style === 'bar'): ?>
    <!-- Bar Style (horizontal logo strip) -->
    <div class="es-sponsors__bar">
        <?php foreach ($sponsors as $sponsor): ?>
        <div class="es-sponsors__item">
            <?php $this->render_sponsor_item($sponsor, $height); ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php elseif ($style === 'marquee'): ?>
    <!-- Marquee Style (infinite scroll) -->
    <div class="es-sponsors__marquee">
        <div class="es-sponsors__marquee-track">
            <?php 
            // Duplicate for seamless loop
            for ($i = 0; $i < 2; $i++):
                foreach ($sponsors as $sponsor): 
            ?>
            <div class="es-sponsors__item">
                <?php $this->render_sponsor_item($sponsor, $height); ?>
            </div>
            <?php 
                endforeach;
            endfor; 
            ?>
        </div>
    </div>
    
    <?php endif; ?>
    
</div>

<?php
/**
 * Render single sponsor item (logo with link)
 */
if (!function_exists('es_sponsors_render_item')):
    // This is handled by the method below
endif;
?>
