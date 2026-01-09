<?php
/**
 * Download Items Partial - List Style
 * 
 * @package Ensemble
 * @subpackage Addons/Downloads
 * 
 * Available variables:
 * - $group: Current group data
 * - $addon: ES_Downloads_Addon instance
 */

defined('ABSPATH') || exit;

$downloads = isset($group['downloads']) ? $group['downloads'] : array();

if (empty($downloads)): ?>
    <p class="es-downloads-empty"><?php _e('No downloads available.', 'ensemble'); ?></p>
<?php else: ?>
    <ul class="es-downloads-items">
        <?php foreach ($downloads as $download): 
            $type_info = $addon->get_type_info($download['type_slug']);
        ?>
            <li class="es-downloads-item">
                <a href="<?php echo esc_url($download['download_url']); ?>" class="es-downloads-item__link" target="_blank">
                    <span class="es-downloads-item__icon dashicons <?php echo esc_attr($type_info['icon']); ?>" 
                          style="color: <?php echo esc_attr($type_info['color']); ?>"></span>
                    <span class="es-downloads-item__info">
                        <span class="es-downloads-item__title"><?php echo esc_html($download['title']); ?></span>
                        <span class="es-downloads-item__meta">
                            <?php echo esc_html($type_info['label']); ?>
                            <?php if ($download['file_size']): ?>
                                &bull; <?php echo esc_html(size_format($download['file_size'])); ?>
                            <?php endif; ?>
                            <?php if ($download['file_extension']): ?>
                                &bull; <?php echo esc_html(strtoupper($download['file_extension'])); ?>
                            <?php endif; ?>
                        </span>
                    </span>
                    <span class="es-downloads-item__action dashicons dashicons-download"></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
