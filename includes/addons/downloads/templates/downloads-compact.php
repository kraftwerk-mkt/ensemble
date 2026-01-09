<?php
/**
 * Downloads Compact Template
 * 
 * Minimal style for embedding in sidebars or small spaces
 * 
 * @package Ensemble
 * @subpackage Addons/Downloads
 * @var array $downloads
 * @var array $atts
 * @var ES_Downloads_Addon $addon
 */

if (!defined('ABSPATH')) {
    exit;
}

$custom_class = isset($atts['class']) ? esc_attr($atts['class']) : '';
$show_size = $addon->get_setting('show_file_size', true);
?>

<div class="es-downloads es-downloads--compact <?php echo $custom_class; ?>">
    <?php if (!empty($atts['title'])): ?>
        <h4 class="es-downloads__title"><?php echo esc_html($atts['title']); ?></h4>
    <?php endif; ?>
    
    <?php if (empty($downloads)): ?>
        <p class="es-downloads__empty"><?php _e('Keine Downloads verfügbar.', 'ensemble'); ?></p>
    <?php else: ?>
        <ul class="es-downloads__compact-list">
            <?php foreach ($downloads as $download): 
                $type_info = $addon->get_type_info($download['type_slug']);
                $icon_class = $addon->get_file_icon($download['file_extension']);
                $type_slug = esc_attr($download['type_slug'] ?: 'other');
            ?>
                <li class="es-download-compact" data-download-id="<?php echo esc_attr($download['id']); ?>">
                    <a href="<?php echo esc_url($download['download_url']); ?>" 
                       class="es-download-compact__link"
                       data-download-id="<?php echo esc_attr($download['id']); ?>"
                       <?php if ($download['require_login'] && !is_user_logged_in()): ?>
                           data-require-login="true"
                       <?php endif; ?>>
                        
                        <span class="es-download-compact__icon es-download-compact__icon--<?php echo $type_slug; ?>">
                            <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                        </span>
                        
                        <span class="es-download-compact__info">
                            <span class="es-download-compact__title"><?php echo esc_html($download['title']); ?></span>
                            <span class="es-download-compact__meta">
                                <?php if ($download['file_extension']): ?>
                                    <span class="es-download-compact__ext"><?php echo esc_html(strtoupper($download['file_extension'])); ?></span>
                                <?php endif; ?>
                                <?php if ($show_size && $download['file_size']): ?>
                                    <span class="es-download-compact__size"><?php echo esc_html($addon->format_file_size($download['file_size'])); ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                        
                        <span class="es-download-compact__arrow dashicons dashicons-download"></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
