<?php
/**
 * Downloads List Template
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
$show_count = $addon->get_setting('show_download_count', false);
$show_type = $addon->get_setting('show_file_type', true);
$show_date = $addon->get_setting('show_date', true);
?>

<div class="es-downloads es-downloads--list <?php echo $custom_class; ?>">
    <?php if (!empty($atts['title'])): ?>
        <h3 class="es-downloads__title"><?php echo esc_html($atts['title']); ?></h3>
    <?php endif; ?>
    
    <?php if (empty($downloads)): ?>
        <p class="es-downloads__empty"><?php _e('Keine Downloads verfügbar.', 'ensemble'); ?></p>
    <?php else: ?>
        <div class="es-downloads__list">
            <?php foreach ($downloads as $download): 
                $type_info = $addon->get_type_info($download['type_slug']);
                $icon_class = $addon->get_file_icon($download['file_extension']);
                $type_slug = esc_attr($download['type_slug'] ?: 'other');
            ?>
                <div class="es-download-item" data-download-id="<?php echo esc_attr($download['id']); ?>">
                    <div class="es-download-item__icon es-download-item__icon--<?php echo $type_slug; ?>">
                        <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                    </div>
                    
                    <div class="es-download-item__content">
                        <div class="es-download-item__header">
                            <h4 class="es-download-item__title"><?php echo esc_html($download['title']); ?></h4>
                            
                            <?php if ($show_type && $download['type']): ?>
                                <span class="es-download-item__type es-download-item__type--<?php echo $type_slug; ?>">
                                    <?php echo esc_html($download['type']['name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($download['description'])): ?>
                            <p class="es-download-item__description"><?php echo esc_html($download['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="es-download-item__meta">
                            <?php if ($download['file_extension']): ?>
                                <span class="es-download-item__extension">
                                    <span class="dashicons dashicons-media-default"></span>
                                    <?php echo esc_html(strtoupper($download['file_extension'])); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($show_size && $download['file_size']): ?>
                                <span class="es-download-item__size">
                                    <span class="dashicons dashicons-database"></span>
                                    <?php echo esc_html($addon->format_file_size($download['file_size'])); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($show_date): ?>
                                <span class="es-download-item__date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($download['created']))); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($show_count && $download['download_count'] > 0): ?>
                                <span class="es-download-item__count">
                                    <span class="dashicons dashicons-chart-bar"></span>
                                    <?php echo esc_html(sprintf(_n('%d Download', '%d Downloads', $download['download_count'], 'ensemble'), $download['download_count'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($download['artists']) || !empty($download['events'])): ?>
                            <div class="es-download-item__links">
                                <?php if (!empty($download['artists'])): ?>
                                    <span class="es-download-item__speakers">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <?php 
                                        $links = array();
                                        foreach ($download['artists'] as $artist) {
                                            $links[] = '<a href="' . esc_url($artist['url']) . '">' . esc_html($artist['title']) . '</a>';
                                        }
                                        echo implode(', ', $links);
                                        ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($download['events'])): ?>
                                    <span class="es-download-item__events">
                                        <span class="dashicons dashicons-calendar"></span>
                                        <?php 
                                        $links = array();
                                        foreach ($download['events'] as $event) {
                                            $links[] = '<a href="' . esc_url($event['url']) . '">' . esc_html($event['title']) . '</a>';
                                        }
                                        echo implode(', ', $links);
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?php echo esc_url($download['download_url']); ?>" 
                       class="es-download-item__button"
                       data-download-id="<?php echo esc_attr($download['id']); ?>"
                       <?php if ($download['require_login'] && !is_user_logged_in()): ?>
                           data-require-login="true"
                       <?php endif; ?>>
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Download', 'ensemble'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
