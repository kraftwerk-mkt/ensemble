<?php
/**
 * FAQ Shortcode Template
 * 
 * @package Ensemble
 * @subpackage Addons/FAQ
 * @version 1.0.0
 * 
 * Available variables:
 * @var array $faqs FAQ items
 * @var array $categories FAQ categories
 * @var array $atts Shortcode attributes
 * @var array $settings Addon settings
 * @var string $current_layout Current Ensemble layout
 */

if (!defined('ABSPATH')) {
    exit;
}

// Build wrapper classes
$wrapper_classes = array(
    'es-faq-wrapper',
    'es-faq-layout-' . esc_attr($atts['layout']),
    'es-faq-icon-' . esc_attr($atts['icon_position']),
    'es-faq-icon-' . esc_attr($atts['icon_type']),
);

if (!empty($atts['class'])) {
    $wrapper_classes[] = esc_attr($atts['class']);
}

// Data attributes
$data_attrs = array(
    'allow-multiple' => $settings['allow_multiple_open'] ? 'true' : 'false',
);
?>

<div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
     data-allow-multiple="<?php echo esc_attr($data_attrs['allow-multiple']); ?>">
    
    <?php if ($atts['show_search'] || $atts['show_filter']) : ?>
    <div class="es-faq-header">
        
        <?php if ($atts['show_search']) : ?>
        <div class="es-faq-search-wrap">
            <svg class="es-faq-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" 
                   class="es-faq-search" 
                   placeholder="<?php esc_attr_e('FAQs durchsuchen...', 'ensemble'); ?>"
                   aria-label="<?php esc_attr_e('FAQs durchsuchen', 'ensemble'); ?>">
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_filter'] && !empty($categories)) : ?>
        <div class="es-faq-filter" role="tablist" aria-label="<?php esc_attr_e('FAQ Kategorien', 'ensemble'); ?>">
            <button type="button" 
                    class="es-faq-filter-btn active" 
                    data-category="all"
                    role="tab"
                    aria-selected="true">
                <?php _e('Alle', 'ensemble'); ?>
                <span class="es-count"><?php echo count($faqs); ?></span>
            </button>
            
            <?php foreach ($categories as $category) : 
                // Count FAQs in this category
                $cat_count = 0;
                foreach ($faqs as $faq) {
                    if (in_array($category->slug, $faq['categories'])) {
                        $cat_count++;
                    }
                }
            ?>
            <button type="button" 
                    class="es-faq-filter-btn" 
                    data-category="<?php echo esc_attr($category->slug); ?>"
                    role="tab"
                    aria-selected="false">
                <?php echo esc_html($category->name); ?>
                <span class="es-count"><?php echo $cat_count; ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
    </div>
    <?php endif; ?>
    
    <div class="es-faq-list" role="list">
        <?php 
        $index = 0;
        foreach ($faqs as $faq) : 
            $is_expanded = $faq['expanded'] || ($atts['expand_first'] && $index === 0);
            $item_classes = array('es-faq-item');
            if ($is_expanded) {
                $item_classes[] = 'active';
            }
            
            $categories_string = implode(' ', $faq['categories']);
        ?>
        <div class="<?php echo esc_attr(implode(' ', $item_classes)); ?>"
             data-categories="<?php echo esc_attr($categories_string); ?>"
             role="listitem">
            
            <button type="button" 
                    class="es-faq-question"
                    aria-expanded="<?php echo $is_expanded ? 'true' : 'false'; ?>"
                    aria-controls="es-faq-answer-<?php echo esc_attr($faq['id']); ?>">
                
                <?php if ($settings['show_icon'] && !empty($faq['icon'])) : ?>
                <span class="es-faq-icon">
                    <span class="<?php echo esc_attr($faq['icon']); ?>"></span>
                </span>
                <?php endif; ?>
                
                <span class="es-faq-question-text">
                    <?php echo esc_html($faq['question']); ?>
                </span>
                
                <span class="es-faq-toggle" aria-hidden="true">
                    <?php if ($atts['icon_type'] === 'chevron') : ?>
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                    <?php elseif ($atts['icon_type'] === 'arrow') : ?>
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14"/>
                        <path d="M12 5l7 7-7 7"/>
                    </svg>
                    <?php endif; ?>
                </span>
            </button>
            
            <div class="es-faq-answer" 
                 id="es-faq-answer-<?php echo esc_attr($faq['id']); ?>"
                 role="region"
                 aria-labelledby="es-faq-question-<?php echo esc_attr($faq['id']); ?>">
                <div class="es-faq-answer-inner">
                    <?php echo $faq['answer']; ?>
                </div>
            </div>
            
        </div>
        <?php 
            $index++;
        endforeach; 
        ?>
    </div>
    
    <!-- No Results Message (hidden by default) -->
    <div class="es-faq-no-results" style="display: none;">
        <svg class="es-faq-no-results-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 8v4"/>
            <path d="M12 16h.01"/>
        </svg>
        <p><?php _e('Keine FAQs gefunden', 'ensemble'); ?></p>
        <button type="button" class="es-faq-reset-search" onclick="esFaqResetSearch(this)">
            <?php _e('Filter zurücksetzen', 'ensemble'); ?>
        </button>
    </div>
    
</div>

<?php 
// Schema Markup for SEO
if ($settings['schema_markup']) {
    // Get the addon instance from active addons or create schema inline
    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array(),
    );
    
    foreach ($faqs as $faq) {
        $schema['mainEntity'][] = array(
            '@type'          => 'Question',
            'name'           => strip_tags($faq['question']),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => strip_tags($faq['answer']),
            ),
        );
    }
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
?>
