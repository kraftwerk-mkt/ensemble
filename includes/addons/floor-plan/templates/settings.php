<?php
/**
 * Floor Plan Pro - Settings Template
 * 
 * Rendered in the addon settings modal
 * 
 * @package Ensemble
 * @subpackage Addons/FloorPlan
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Label options
$label_options = array(
    'floor_plan'    => __('Floor Plan', 'ensemble'),
    'seating_plan'  => __('Seating Plan', 'ensemble'),
    'venue_map'     => __('Venue Map', 'ensemble'),
    'room_layout'   => __('Room Layout', 'ensemble'),
    'table_plan'    => __('Table Plan', 'ensemble'),
    'area_overview' => __('Area Overview', 'ensemble'),
    'custom'        => __('Custom', 'ensemble'),
);

$current_style = $settings['label_style'] ?? 'floor_plan';
$custom_label = $settings['custom_label'] ?? '';
?>

<div class="es-floor-plan-settings">
    
    <!-- Label Style -->
    <div class="es-settings-section">
        <h3><?php _e('Display Labels', 'ensemble'); ?></h3>
        <p class="es-section-desc"><?php _e('Choose how the floor plan section is labeled on event pages.', 'ensemble'); ?></p>
        
        <div class="es-field">
            <label><?php _e('Label Style', 'ensemble'); ?></label>
            <div class="es-label-options">
                <?php foreach ($label_options as $key => $label): ?>
                <label class="es-label-option <?php echo $current_style === $key ? 'active' : ''; ?>">
                    <input type="radio" name="label_style" value="<?php echo esc_attr($key); ?>" <?php checked($current_style, $key); ?>>
                    <span class="es-label-text"><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="es-field es-custom-label-field" style="<?php echo $current_style !== 'custom' ? 'display:none;' : ''; ?>">
            <label><?php _e('Custom Label', 'ensemble'); ?></label>
            <input type="text" 
                   name="custom_label" 
                   value="<?php echo esc_attr($custom_label); ?>" 
                   placeholder="<?php esc_attr_e('e.g., Table Overview', 'ensemble'); ?>"
                   class="es-input-wide">
            <p class="es-hint"><?php _e('Enter your own label text for the floor plan section.', 'ensemble'); ?></p>
        </div>
    </div>
    
    <!-- Preview -->
    <div class="es-settings-section es-settings-section--last">
        <h3><?php _e('Preview', 'ensemble'); ?></h3>
        <div class="es-preview-box">
            <span class="es-preview-label"><?php _e('Section title will display as:', 'ensemble'); ?></span>
            <strong class="es-preview-value">
                <?php 
                if ($current_style === 'custom' && !empty($custom_label)) {
                    echo esc_html($custom_label);
                } else {
                    echo esc_html($label_options[$current_style] ?? $label_options['floor_plan']);
                }
                ?>
            </strong>
        </div>
    </div>
    
</div>

<style>
.es-floor-plan-settings {
    padding: 0;
}

.es-settings-section {
    margin-bottom: var(--es-spacing-lg, 24px);
}

.es-settings-section--last {
    margin-bottom: 0;
}

.es-settings-section h3 {
    margin: 0 0 var(--es-spacing-sm, 8px) 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-section-desc {
    margin: 0 0 var(--es-spacing-md, 16px) 0;
    font-size: 13px;
    color: var(--es-text-secondary, #a0a0a0);
}

.es-field {
    margin-bottom: var(--es-spacing-md, 16px);
}

.es-field > label {
    display: block;
    margin-bottom: var(--es-spacing-sm, 8px);
    font-size: 13px;
    font-weight: 500;
    color: var(--es-text, #e0e0e0);
}

/* Label Options Grid */
.es-label-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--es-spacing-sm, 8px);
}

.es-label-option {
    display: flex;
    align-items: center;
    padding: 12px 14px;
    background: var(--es-surface, #2c2c2c);
    border: 2px solid transparent;
    border-radius: var(--es-radius-lg, 8px);
    cursor: pointer;
    transition: var(--es-transition, all 0.2s ease);
}

.es-label-option:hover {
    background: var(--es-surface-hover, #404040);
}

.es-label-option.active {
    border-color: var(--es-primary, #3582c4);
    background: var(--es-primary-light, rgba(53, 130, 196, 0.1));
}

.es-label-option input {
    display: none;
}

.es-label-text {
    font-size: 13px;
    color: var(--es-text, #e0e0e0);
}

/* Input */
.es-input-wide {
    width: 100%;
    padding: 10px 12px;
    background: var(--es-background, #1e1e1e);
    border: 1px solid var(--es-border, #404040);
    border-radius: var(--es-radius, 6px);
    color: var(--es-text, #e0e0e0);
    font-size: 14px;
}

.es-input-wide:focus {
    border-color: var(--es-primary, #3582c4);
    outline: none;
}

.es-hint {
    margin: 6px 0 0;
    font-size: 12px;
    color: var(--es-text-secondary, #a0a0a0);
}

/* Preview Box */
.es-preview-box {
    padding: var(--es-spacing-md, 16px);
    background: var(--es-surface, #2c2c2c);
    border-radius: var(--es-radius-lg, 8px);
    text-align: center;
}

.es-preview-label {
    display: block;
    font-size: 12px;
    color: var(--es-text-secondary, #a0a0a0);
    margin-bottom: var(--es-spacing-sm, 8px);
}

.es-preview-value {
    font-size: 16px;
    color: var(--es-text, #e0e0e0);
}

/* Responsive */
@media (max-width: 500px) {
    .es-label-options {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var $container = $('.es-floor-plan-settings');
    var $customField = $container.find('.es-custom-label-field');
    var $previewValue = $container.find('.es-preview-value');
    var $customInput = $container.find('input[name="custom_label"]');
    
    var labels = {
        'floor_plan': '<?php echo esc_js(__('Floor Plan', 'ensemble')); ?>',
        'seating_plan': '<?php echo esc_js(__('Seating Plan', 'ensemble')); ?>',
        'venue_map': '<?php echo esc_js(__('Venue Map', 'ensemble')); ?>',
        'room_layout': '<?php echo esc_js(__('Room Layout', 'ensemble')); ?>',
        'table_plan': '<?php echo esc_js(__('Table Plan', 'ensemble')); ?>',
        'area_overview': '<?php echo esc_js(__('Area Overview', 'ensemble')); ?>',
        'custom': '<?php echo esc_js(__('Custom', 'ensemble')); ?>'
    };
    
    $container.on('change', 'input[name="label_style"]', function() {
        var value = $(this).val();
        
        $container.find('.es-label-option').removeClass('active');
        $(this).closest('.es-label-option').addClass('active');
        
        if (value === 'custom') {
            $customField.slideDown(200);
            updatePreview($customInput.val() || labels['custom']);
        } else {
            $customField.slideUp(200);
            updatePreview(labels[value] || labels['floor_plan']);
        }
    });
    
    $customInput.on('input', function() {
        updatePreview($(this).val() || labels['custom']);
    });
    
    function updatePreview(text) {
        $previewValue.text(text);
    }
});
</script>
