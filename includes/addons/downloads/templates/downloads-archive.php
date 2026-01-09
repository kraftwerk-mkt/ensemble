<?php
/**
 * Downloads Archive Template - Grouped Display
 * 
 * @package Ensemble
 * @subpackage Addons/Downloads
 * 
 * Available variables:
 * - $groups: Array of download groups
 * - $atts: Shortcode attributes
 * - $addon: ES_Downloads_Addon instance
 */

defined('ABSPATH') || exit;

$style = $atts['style'];
$expanded = $atts['expanded'];
$show_count = $atts['show_count'] === 'yes';
$columns = absint($atts['columns']);
$custom_class = sanitize_html_class($atts['class']);

$wrapper_class = 'es-downloads-archive';
$wrapper_class .= ' es-downloads-archive--' . $style;
if ($custom_class) {
    $wrapper_class .= ' ' . $custom_class;
}
?>
<div class="<?php echo esc_attr($wrapper_class); ?>">
    
    <?php if (!empty($atts['title'])): ?>
        <h3 class="es-downloads-archive__title"><?php echo esc_html($atts['title']); ?></h3>
    <?php endif; ?>
    
    <?php if ($style === 'tabs'): ?>
        <!-- Tabs Navigation -->
        <div class="es-downloads-tabs">
            <div class="es-downloads-tabs__nav" role="tablist">
                <?php $tab_index = 0; foreach ($groups as $key => $group): ?>
                    <button 
                        class="es-downloads-tabs__tab <?php echo $tab_index === 0 ? 'is-active' : ''; ?>"
                        role="tab"
                        aria-selected="<?php echo $tab_index === 0 ? 'true' : 'false'; ?>"
                        aria-controls="tab-panel-<?php echo esc_attr($key); ?>"
                        data-tab="<?php echo esc_attr($key); ?>"
                    >
                        <?php if (!empty($group['icon'])): ?>
                            <span class="dashicons <?php echo esc_attr($group['icon']); ?>"></span>
                        <?php endif; ?>
                        <span class="es-downloads-tabs__tab-title"><?php echo esc_html($group['title']); ?></span>
                        <?php if ($show_count): ?>
                            <span class="es-downloads-tabs__count"><?php echo count($group['downloads']); ?></span>
                        <?php endif; ?>
                    </button>
                <?php $tab_index++; endforeach; ?>
            </div>
            
            <div class="es-downloads-tabs__panels">
                <?php $tab_index = 0; foreach ($groups as $key => $group): ?>
                    <div 
                        class="es-downloads-tabs__panel <?php echo $tab_index === 0 ? 'is-active' : ''; ?>"
                        role="tabpanel"
                        id="tab-panel-<?php echo esc_attr($key); ?>"
                        <?php echo $tab_index !== 0 ? 'hidden' : ''; ?>
                    >
                        <?php include __DIR__ . '/partials/download-items.php'; ?>
                    </div>
                <?php $tab_index++; endforeach; ?>
            </div>
        </div>
        
    <?php elseif ($style === 'accordion'): ?>
        <!-- Accordion -->
        <div class="es-downloads-accordion">
            <?php 
            $accordion_index = 0; 
            foreach ($groups as $key => $group): 
                $is_expanded = ($expanded === 'all') || 
                               ($expanded === 'first' && $accordion_index === 0);
            ?>
                <div class="es-downloads-accordion__item <?php echo $is_expanded ? 'is-open' : ''; ?>">
                    <button 
                        class="es-downloads-accordion__header"
                        aria-expanded="<?php echo $is_expanded ? 'true' : 'false'; ?>"
                        aria-controls="accordion-panel-<?php echo esc_attr($key); ?>"
                    >
                        <span class="es-downloads-accordion__header-content">
                            <?php if (!empty($group['thumbnail'])): ?>
                                <img src="<?php echo esc_url($group['thumbnail']); ?>" alt="" class="es-downloads-accordion__thumb">
                            <?php elseif (!empty($group['icon'])): ?>
                                <span class="es-downloads-accordion__icon dashicons <?php echo esc_attr($group['icon']); ?>" 
                                      <?php if (!empty($group['color'])): ?>style="color: <?php echo esc_attr($group['color']); ?>"<?php endif; ?>></span>
                            <?php endif; ?>
                            
                            <span class="es-downloads-accordion__title">
                                <?php echo esc_html($group['title']); ?>
                            </span>
                            
                            <?php if ($show_count): ?>
                                <span class="es-downloads-accordion__count">
                                    <?php printf(_n('%d download', '%d downloads', count($group['downloads']), 'ensemble'), count($group['downloads'])); ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        
                        <span class="es-downloads-accordion__toggle dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    
                    <div 
                        class="es-downloads-accordion__panel"
                        id="accordion-panel-<?php echo esc_attr($key); ?>"
                        <?php echo !$is_expanded ? 'hidden' : ''; ?>
                    >
                        <div class="es-downloads-accordion__content">
                            <?php include __DIR__ . '/partials/download-items.php'; ?>
                        </div>
                    </div>
                </div>
            <?php $accordion_index++; endforeach; ?>
        </div>
        
    <?php elseif ($style === 'list'): ?>
        <!-- Grouped List -->
        <div class="es-downloads-grouped-list">
            <?php foreach ($groups as $key => $group): ?>
                <div class="es-downloads-group">
                    <div class="es-downloads-group__header">
                        <?php if (!empty($group['thumbnail'])): ?>
                            <img src="<?php echo esc_url($group['thumbnail']); ?>" alt="" class="es-downloads-group__thumb">
                        <?php elseif (!empty($group['icon'])): ?>
                            <span class="es-downloads-group__icon dashicons <?php echo esc_attr($group['icon']); ?>"
                                  <?php if (!empty($group['color'])): ?>style="color: <?php echo esc_attr($group['color']); ?>"<?php endif; ?>></span>
                        <?php endif; ?>
                        
                        <h4 class="es-downloads-group__title">
                            <?php if (!empty($group['permalink'])): ?>
                                <a href="<?php echo esc_url($group['permalink']); ?>"><?php echo esc_html($group['title']); ?></a>
                            <?php else: ?>
                                <?php echo esc_html($group['title']); ?>
                            <?php endif; ?>
                        </h4>
                        
                        <?php if ($show_count): ?>
                            <span class="es-downloads-group__count"><?php echo count($group['downloads']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="es-downloads-group__items">
                        <?php include __DIR__ . '/partials/download-items.php'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        <!-- Grid (default) -->
        <div class="es-downloads-grouped-grid">
            <?php foreach ($groups as $key => $group): ?>
                <div class="es-downloads-group">
                    <div class="es-downloads-group__header">
                        <?php if (!empty($group['icon'])): ?>
                            <span class="es-downloads-group__icon dashicons <?php echo esc_attr($group['icon']); ?>"
                                  <?php if (!empty($group['color'])): ?>style="color: <?php echo esc_attr($group['color']); ?>"<?php endif; ?>></span>
                        <?php endif; ?>
                        
                        <h4 class="es-downloads-group__title">
                            <?php if (!empty($group['permalink'])): ?>
                                <a href="<?php echo esc_url($group['permalink']); ?>"><?php echo esc_html($group['title']); ?></a>
                            <?php else: ?>
                                <?php echo esc_html($group['title']); ?>
                            <?php endif; ?>
                        </h4>
                        
                        <?php if ($show_count): ?>
                            <span class="es-downloads-group__count"><?php echo count($group['downloads']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="es-downloads-group__grid" style="--columns: <?php echo $columns; ?>">
                        <?php 
                        $downloads = $group['downloads'];
                        include __DIR__ . '/partials/download-items-grid.php'; 
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
</div>
