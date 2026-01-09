<?php
/**
 * Downloads Grid Template
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

$columns = isset($atts['columns']) ? intval($atts['columns']) : 3;
$custom_class = isset($atts['class']) ? esc_attr($atts['class']) : '';
$show_size = $addon->get_setting('show_file_size', true);
$show_count = $addon->get_setting('show_download_count', false);
$show_type = $addon->get_setting('show_file_type', true);
?>

<div class="es-downloads es-downloads--grid es-downloads--cols-<?php echo $columns; ?> <?php echo $custom_class; ?>">
    <?php if (!empty($atts['title'])): ?>
        <h3 class="es-downloads__title"><?php echo esc_html($atts['title']); ?></h3>
    <?php endif; ?>
    
    <?php if (empty($downloads)): ?>
        <p class="es-downloads__empty"><?php _e('Keine Downloads verfügbar.', 'ensemble'); ?></p>
    <?php else: ?>
        <div class="es-downloads__grid">
            <?php foreach ($downloads as $download): 
                $type_info = $addon->get_type_info($download['type_slug']);
                $icon_class = $addon->get_file_icon($download['file_extension']);
                $type_slug = esc_attr($download['type_slug'] ?: 'other');
            ?>
                <div class="es-download-card" data-download-id="<?php echo esc_attr($download['id']); ?>">
                    <div class="es-download-card__icon es-download-card__icon--<?php echo $type_slug; ?>">
                        <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                    </div>
                    
                    <div class="es-download-card__content">
                        <h4 class="es-download-card__title"><?php echo esc_html($download['title']); ?></h4>
                        
                        <?php if (!empty($download['description'])): ?>
                            <p class="es-download-card__description"><?php echo esc_html(wp_trim_words($download['description'], 15)); ?></p>
                        <?php endif; ?>
                        
                        <div class="es-download-card__meta">
                            <?php if ($show_type && $download['type']): ?>
                                <span class="es-download-card__type es-download-card__type--<?php echo $type_slug; ?>">
                                    <?php echo esc_html($download['type']['name']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($download['file_extension']): ?>
                                <span class="es-download-card__extension"><?php echo esc_html(strtoupper($download['file_extension'])); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($show_size && $download['file_size']): ?>
                                <span class="es-download-card__size"><?php echo esc_html($addon->format_file_size($download['file_size'])); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($show_count && $download['download_count'] > 0): ?>
                                <span class="es-download-card__count">
                                    <?php echo esc_html(sprintf(_n('%d Download', '%d Downloads', $download['download_count'], 'ensemble'), $download['download_count'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($download['artists'])): ?>
                            <div class="es-download-card__speakers">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php 
                                $artist_names = array_map(function($a) { return $a['title']; }, $download['artists']);
                                echo esc_html(implode(', ', $artist_names));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?php echo esc_url($download['download_url']); ?>" 
                       class="es-download-card__button"
                       data-download-id="<?php echo esc_attr($download['id']); ?>"
                       <?php if ($download['require_login'] && !is_user_logged_in()): ?>
                           data-require-login="true"
                       <?php endif; ?>>
                        <span class="dashicons dashicons-download"></span>
                        <span class="es-download-card__button-text"><?php _e('Download', 'ensemble'); ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
