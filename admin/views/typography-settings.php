<?php
/**
 * Typography Settings View
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) {
    exit;
}

$category_labels = array(
    'system' => __('System Fonts', 'ensemble'),
    'sans-serif' => __('Sans-Serif', 'ensemble'),
    'serif' => __('Serif', 'ensemble'),
    'display' => __('Display', 'ensemble'),
    'monospace' => __('Monospace', 'ensemble'),
    'custom' => __('Custom Fonts', 'ensemble'),
);

$weight_labels = array(
    '300' => __('Light (300)', 'ensemble'),
    '400' => __('Regular (400)', 'ensemble'),
    '500' => __('Medium (500)', 'ensemble'),
    '600' => __('Semi-Bold (600)', 'ensemble'),
    '700' => __('Bold (700)', 'ensemble'),
);
?>

<div class="es-typography-settings">
    
    <div class="es-settings-intro">
        <h3><?php _e('Typography', 'ensemble'); ?></h3>
        <p class="description">
            <?php _e('Choose fonts for your event pages. The selected fonts will be automatically loaded from Google Fonts.', 'ensemble'); ?>
        </p>
    </div>
    
    <div class="es-font-settings-grid">
        
        <!-- Heading Font -->
        <div class="es-font-setting-card">
            <div class="es-font-card-header">
                <span class="dashicons dashicons-heading"></span>
                <h4><?php _e('Heading Font', 'ensemble'); ?></h4>
            </div>
            
            <div class="es-font-preview" id="heading-preview" style="font-family: <?php echo esc_attr($settings['heading_font']); ?>; font-weight: <?php echo esc_attr($settings['heading_weight']); ?>;">
                Ensemble Events
            </div>
            
            <div class="es-font-selector">
                <label><?php _e('Font Family', 'ensemble'); ?></label>
                <div class="es-font-dropdown-wrapper">
                    <button type="button" class="es-font-dropdown-trigger" data-target="heading">
                        <span class="es-selected-font"><?php echo esc_html($settings['heading_font']); ?></span>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <input type="hidden" name="typography[heading_font]" id="heading_font" value="<?php echo esc_attr($settings['heading_font']); ?>">
                </div>
            </div>
            
            <div class="es-font-weight-selector">
                <label><?php _e('Font Weight', 'ensemble'); ?></label>
                <select name="typography[heading_weight]" id="heading_weight">
                    <?php foreach ($weight_labels as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['heading_weight'], $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Body Font -->
        <div class="es-font-setting-card">
            <div class="es-font-card-header">
                <span class="dashicons dashicons-text"></span>
                <h4><?php _e('Body Font', 'ensemble'); ?></h4>
            </div>
            
            <div class="es-font-preview es-font-preview-body" id="body-preview" style="font-family: <?php echo esc_attr($settings['body_font']); ?>; font-weight: <?php echo esc_attr($settings['body_weight']); ?>;">
                The quick brown fox jumps over the lazy dog. Pack my box with five dozen liquor jugs.
            </div>
            
            <div class="es-font-selector">
                <label><?php _e('Font Family', 'ensemble'); ?></label>
                <div class="es-font-dropdown-wrapper">
                    <button type="button" class="es-font-dropdown-trigger" data-target="body">
                        <span class="es-selected-font"><?php echo esc_html($settings['body_font']); ?></span>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <input type="hidden" name="typography[body_font]" id="body_font" value="<?php echo esc_attr($settings['body_font']); ?>">
                </div>
            </div>
            
            <div class="es-font-weight-selector">
                <label><?php _e('Font Weight', 'ensemble'); ?></label>
                <select name="typography[body_weight]" id="body_weight">
                    <?php foreach ($weight_labels as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['body_weight'], $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
    </div>
    
    <!-- Custom Fonts (Pro) -->
    <div class="es-custom-fonts-section <?php echo !$is_pro ? 'es-pro-locked' : ''; ?>">
        <div class="es-font-card-header">
            <span class="dashicons dashicons-upload"></span>
            <h4>
                <?php _e('Custom Fonts', 'ensemble'); ?>
                <?php if (!$is_pro): ?>
                <span class="es-pro-badge">PRO</span>
                <?php endif; ?>
            </h4>
        </div>
        
        <?php if ($is_pro): ?>
        <p class="description">
            <?php _e('Upload your own fonts (.woff2, .woff, .ttf, .otf). They will appear in the font selector above.', 'ensemble'); ?>
        </p>
        
        <div class="es-custom-fonts-list">
            <?php if (!empty($custom)): ?>
                <?php foreach ($custom as $index => $font): ?>
                <div class="es-custom-font-item">
                    <span class="es-custom-font-name"><?php echo esc_html($font['name']); ?></span>
                    <span class="es-custom-font-weight"><?php echo esc_html($font['weight']); ?></span>
                    <button type="button" class="es-remove-font" data-index="<?php echo $index; ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="es-no-custom-fonts"><?php _e('No custom fonts uploaded yet.', 'ensemble'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="es-upload-font-form">
            <input type="file" id="es-font-file" accept=".woff,.woff2,.ttf,.otf" style="display: none;">
            <button type="button" class="button" id="es-upload-font-btn">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Upload Font', 'ensemble'); ?>
            </button>
        </div>
        
        <?php else: ?>
        <div class="es-pro-feature-info">
            <p><?php _e('Upload your own brand fonts with Ensemble Pro.', 'ensemble'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=license'); ?>" class="button">
                <?php _e('Upgrade to Pro', 'ensemble'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- All Google Fonts (Pro) -->
    <?php if (!$is_pro): ?>
    <div class="es-all-fonts-promo">
        <div class="es-promo-content">
            <span class="dashicons dashicons-editor-textcolor"></span>
            <div>
                <h4><?php _e('Access All 1500+ Google Fonts', 'ensemble'); ?></h4>
                <p><?php _e('Upgrade to Pro to unlock the complete Google Fonts library with search and categories.', 'ensemble'); ?></p>
            </div>
            <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=license'); ?>" class="button button-primary">
                <?php _e('Unlock All Fonts', 'ensemble'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<!-- Font Picker Modal -->
<div class="es-font-picker-modal" id="es-font-picker-modal" style="display: none;">
    <div class="es-font-picker-overlay"></div>
    <div class="es-font-picker-content">
        <div class="es-font-picker-header">
            <h3><?php _e('Select Font', 'ensemble'); ?></h3>
            <button type="button" class="es-font-picker-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        
        <?php if ($is_pro): ?>
        <div class="es-font-picker-search">
            <span class="dashicons dashicons-search"></span>
            <input type="text" id="es-font-search" placeholder="<?php esc_attr_e('Search fonts...', 'ensemble'); ?>">
            <select id="es-font-category-filter">
                <option value=""><?php _e('All Categories', 'ensemble'); ?></option>
                <?php foreach ($category_labels as $cat => $label): ?>
                <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <div class="es-font-picker-list">
            <?php foreach ($by_category as $category => $fonts): ?>
                <?php if (empty($fonts)) continue; ?>
                <div class="es-font-category">
                    <h4 class="es-font-category-title"><?php echo esc_html($category_labels[$category] ?? $category); ?></h4>
                    <div class="es-font-category-items">
                        <?php foreach ($fonts as $name => $data): ?>
                        <button type="button" class="es-font-option" data-font="<?php echo esc_attr($name); ?>" style="font-family: '<?php echo esc_attr($name); ?>', sans-serif;">
                            <span class="es-font-option-name"><?php echo esc_html($name); ?></span>
                            <span class="es-font-option-preview">Aa</span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (!empty($custom)): ?>
            <div class="es-font-category">
                <h4 class="es-font-category-title"><?php echo esc_html($category_labels['custom']); ?></h4>
                <div class="es-font-category-items">
                    <?php foreach ($custom as $font): ?>
                    <button type="button" class="es-font-option" data-font="<?php echo esc_attr($font['name']); ?>" style="font-family: '<?php echo esc_attr($font['name']); ?>', sans-serif;">
                        <span class="es-font-option-name"><?php echo esc_html($font['name']); ?></span>
                        <span class="es-font-option-preview">Aa</span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Typography Settings Styles */
.es-typography-settings {
    max-width: 900px;
}

.es-settings-intro {
    margin-bottom: 30px;
}

.es-settings-intro h3 {
    margin: 0 0 10px;
    font-size: 1.3rem;
}

.es-font-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.es-font-setting-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 24px;
}

.es-font-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.es-font-card-header .dashicons {
    color: #e94560;
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.es-font-card-header h4 {
    margin: 0;
    font-size: 1rem;
}

.es-font-preview {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 20px;
    font-size: 28px;
    margin-bottom: 20px;
    text-align: center;
    min-height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.es-font-preview-body {
    font-size: 16px;
    line-height: 1.6;
    text-align: left;
}

.es-font-selector,
.es-font-weight-selector {
    margin-bottom: 15px;
}

.es-font-selector label,
.es-font-weight-selector label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
    margin-bottom: 6px;
}

.es-font-dropdown-wrapper {
    position: relative;
}

.es-font-dropdown-trigger {
    width: 100%;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-align: left;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    transition: border-color 0.2s;
}

.es-font-dropdown-trigger:hover {
    border-color: #e94560;
}

.es-font-weight-selector select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

/* Font Picker Modal */
.es-font-picker-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
}

.es-font-picker-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
}

.es-font-picker-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 12px;
    width: 600px;
    max-width: 90vw;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.es-font-picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #eee;
}

.es-font-picker-header h3 {
    margin: 0;
}

.es-font-picker-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    color: #666;
}

.es-font-picker-close:hover {
    color: #e94560;
}

.es-font-picker-search {
    display: flex;
    gap: 10px;
    padding: 15px 24px;
    border-bottom: 1px solid #eee;
    align-items: center;
}

.es-font-picker-search .dashicons {
    color: #999;
}

.es-font-picker-search input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.es-font-picker-search select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.es-font-picker-list {
    overflow-y: auto;
    padding: 15px 24px;
    flex: 1;
}

.es-font-category {
    margin-bottom: 24px;
}

.es-font-category-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #999;
    margin: 0 0 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
}

.es-font-category-items {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.es-font-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #f8f9fa;
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
}

.es-font-option:hover {
    background: #fff;
    border-color: #e94560;
}

.es-font-option-name {
    font-size: 14px;
}

.es-font-option-preview {
    font-size: 24px;
    opacity: 0.6;
}

/* Custom Fonts Section */
.es-custom-fonts-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 30px;
}

.es-custom-fonts-section.es-pro-locked {
    opacity: 0.7;
}

.es-custom-fonts-list {
    margin: 15px 0;
}

.es-custom-font-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
}

.es-custom-font-name {
    flex: 1;
    font-weight: 500;
}

.es-custom-font-weight {
    color: #666;
    font-size: 13px;
}

.es-remove-font {
    background: none;
    border: none;
    color: #e94560;
    cursor: pointer;
    padding: 2px;
}

.es-no-custom-fonts {
    color: #999;
    font-style: italic;
}

.es-upload-font-form {
    margin-top: 15px;
}

.es-pro-feature-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.es-pro-feature-info p {
    margin: 0 0 15px;
}

.es-pro-feature-info .button {
    background: #fff;
    color: #667eea;
    border: none;
}

/* Pro Promo */
.es-all-fonts-promo {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 12px;
    padding: 30px;
}

.es-promo-content {
    display: flex;
    align-items: center;
    gap: 20px;
    color: #fff;
}

.es-promo-content .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #e94560;
}

.es-promo-content h4 {
    margin: 0 0 5px;
    font-size: 1.1rem;
}

.es-promo-content p {
    margin: 0;
    opacity: 0.8;
}

.es-promo-content .button-primary {
    background: #e94560;
    border-color: #e94560;
    margin-left: auto;
    flex-shrink: 0;
}

/* Pro Badge */
.es-pro-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    font-size: 9px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: 8px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var currentTarget = null;
    
    // Open font picker
    $('.es-font-dropdown-trigger').on('click', function() {
        currentTarget = $(this).data('target');
        $('#es-font-picker-modal').show();
    });
    
    // Close font picker
    $('.es-font-picker-close, .es-font-picker-overlay').on('click', function() {
        $('#es-font-picker-modal').hide();
    });
    
    // Select font
    $('.es-font-option').on('click', function() {
        var fontName = $(this).data('font');
        
        // Update hidden input
        $('#' + currentTarget + '_font').val(fontName);
        
        // Update trigger text
        $('[data-target="' + currentTarget + '"] .es-selected-font').text(fontName);
        
        // Update preview
        $('#' + currentTarget + '-preview').css('font-family', '"' + fontName + '", sans-serif');
        
        // Load font for preview
        if (!fontName.startsWith('System')) {
            var link = document.createElement('link');
            link.href = 'https://fonts.googleapis.com/css2?family=' + fontName.replace(/ /g, '+') + ':wght@400;700&display=swap';
            link.rel = 'stylesheet';
            document.head.appendChild(link);
        }
        
        // Close picker
        $('#es-font-picker-modal').hide();
    });
    
    // Update preview on weight change
    $('#heading_weight').on('change', function() {
        $('#heading-preview').css('font-weight', $(this).val());
    });
    
    $('#body_weight').on('change', function() {
        $('#body-preview').css('font-weight', $(this).val());
    });
    
    // Search fonts (Pro)
    var searchTimeout;
    $('#es-font-search').on('input', function() {
        clearTimeout(searchTimeout);
        var search = $(this).val().toLowerCase();
        
        searchTimeout = setTimeout(function() {
            $('.es-font-option').each(function() {
                var name = $(this).data('font').toLowerCase();
                $(this).toggle(name.indexOf(search) !== -1);
            });
            
            // Hide empty categories
            $('.es-font-category').each(function() {
                var hasVisible = $(this).find('.es-font-option:visible').length > 0;
                $(this).toggle(hasVisible);
            });
        }, 200);
    });
    
    // Filter by category
    $('#es-font-category-filter').on('change', function() {
        var category = $(this).val();
        
        if (!category) {
            $('.es-font-category').show();
            $('.es-font-option').show();
        } else {
            $('.es-font-category').hide();
            $('.es-font-category').each(function() {
                var catTitle = $(this).find('.es-font-category-title').text().toLowerCase();
                if (catTitle.indexOf(category) !== -1 || category === 'system' && catTitle.indexOf('system') !== -1) {
                    $(this).show();
                }
            });
        }
    });
    
    // Upload custom font (Pro)
    $('#es-upload-font-btn').on('click', function() {
        $('#es-font-file').click();
    });
    
    $('#es-font-file').on('change', function() {
        var file = this.files[0];
        if (!file) return;
        
        var fontName = prompt('<?php _e("Enter font name:", "ensemble"); ?>', file.name.replace(/\.[^/.]+$/, ""));
        if (!fontName) return;
        
        var formData = new FormData();
        formData.append('action', 'es_upload_custom_font');
        formData.append('nonce', '<?php echo wp_create_nonce("ensemble_admin"); ?>');
        formData.append('font_file', file);
        formData.append('font_name', fontName);
        formData.append('font_weight', '400');
        formData.append('font_style', 'normal');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || '<?php _e("Upload failed", "ensemble"); ?>');
                }
            }
        });
    });
});
</script>
