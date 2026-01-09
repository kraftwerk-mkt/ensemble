<?php
/**
 * Download Items Partial - Grid Style
 * 
 * @package Ensemble
 * @subpackage Addons/Downloads
 * 
 * Available variables:
 * - $downloads: Array of downloads
 * - $addon: ES_Downloads_Addon instance
 */

defined('ABSPATH') || exit;

if (empty($downloads)): ?>
    <p class="es-downloads-empty"><?php _e('No downloads available.', 'ensemble'); ?></p>
<?php else: ?>
    <?php foreach ($downloads as $download): 
        $type_info = $addon->get_type_info($download['type_slug']);
    ?>
        <a href="<?php echo esc_url($download['download_url']); ?>" class="es-download-card" target="_blank">
            <div class="es-download-card__icon" style="background-color: <?php echo esc_attr($type_info['color']); ?>20; color: <?php echo esc_attr($type_info['color']); ?>">
                <span class="dashicons <?php echo esc_attr($type_info['icon']); ?>"></span>
            </div>
            <div class="es-download-card__content">
                <h5 class="es-download-card__title"><?php echo esc_html($download['title']); ?></h5>
                <div class="es-download-card__meta">
                    <span class="es-download-card__type"><?php echo esc_html($type_info['label']); ?></span>
                    <?php if ($download['file_size']): ?>
                        <span class="es-download-card__size"><?php echo esc_html(size_format($download['file_size'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="es-download-card__action">
                <span class="dashicons dashicons-download"></span>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>
