<?php
/**
 * Ensemble CSS Generator
 * 
 * Generates custom CSS from design settings
 * 
 * @package Ensemble
 * @since 1.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_CSS_Generator {
    
    /**
     * Generate custom CSS from current settings
     * 
     * @return string Generated CSS
     */
    public static function generate() {
        // Get Light Mode settings (default)
        $settings = ES_Design_Settings::get_mode_settings('light');
        // Get Dark Mode settings
        $dark_settings = ES_Design_Settings::get_mode_settings('dark');
        
        ob_start();
        ?>
        
/* ========================================
   Ensemble Event Management - Custom Design
   Generated: <?php echo date('Y-m-d H:i:s'); ?>
   Template: <?php echo ES_Design_Settings::get_active_template(); ?>
   ======================================== */

/* ========================================
   LIGHT MODE (Default)
   ======================================== */

:root {
    /* Colors - Light Mode */
    --ensemble-primary: <?php echo esc_attr($settings['primary_color']); ?>;
    --ensemble-primary-rgb: <?php echo self::hex_to_rgb($settings['primary_color']); ?>;
    --ensemble-secondary: <?php echo esc_attr($settings['secondary_color']); ?>;
    --ensemble-secondary-rgb: <?php echo self::hex_to_rgb($settings['secondary_color']); ?>;
    --ensemble-bg: <?php echo esc_attr($settings['background_color']); ?>;
    --ensemble-text: <?php echo esc_attr($settings['text_color']); ?>;
    --ensemble-text-secondary: <?php echo esc_attr($settings['text_secondary']); ?>;
    --ensemble-text-muted: <?php echo esc_attr($settings['text_muted'] ?? '#a0aec0'); ?>;
    --ensemble-card-bg: <?php echo esc_attr($settings['card_background']); ?>;
    --ensemble-card-border: <?php echo esc_attr($settings['card_border']); ?>;
    --ensemble-hover: <?php echo esc_attr($settings['hover_color']); ?>;
    --ensemble-link: <?php echo esc_attr($settings['link_color'] ?? $settings['primary_color']); ?>;
    
    /* Surface & Dividers - Light Mode */
    --ensemble-surface: <?php echo esc_attr($settings['surface_color'] ?? $settings['card_background']); ?>;
    --ensemble-divider: <?php echo esc_attr($settings['divider_color'] ?? $settings['card_border']); ?>;
    
    /* Overlay - Light Mode (Text über Bildern) */
    --ensemble-overlay-bg: <?php echo esc_attr($settings['overlay_bg'] ?? 'rgba(0, 0, 0, 0.7)'); ?>;
    --ensemble-overlay-text: <?php echo esc_attr($settings['overlay_text'] ?? '#ffffff'); ?>;
    --ensemble-overlay-text-secondary: <?php echo esc_attr($settings['overlay_text_secondary'] ?? 'rgba(255, 255, 255, 0.8)'); ?>;
    --ensemble-overlay-text-muted: <?php echo esc_attr($settings['overlay_text_muted'] ?? 'rgba(255, 255, 255, 0.6)'); ?>;
    --ensemble-overlay-border: <?php echo esc_attr($settings['overlay_border'] ?? 'rgba(255, 255, 255, 0.2)'); ?>;
    
    /* Placeholder - Light Mode */
    --ensemble-placeholder-bg: <?php echo esc_attr($settings['placeholder_bg'] ?? '#e2e8f0'); ?>;
    --ensemble-placeholder-icon: <?php echo esc_attr($settings['placeholder_icon'] ?? '#a0aec0'); ?>;
    
    /* Status Colors */
    --ensemble-status-cancelled: <?php echo esc_attr($settings['status_cancelled'] ?? '#dc2626'); ?>;
    --ensemble-status-soldout: <?php echo esc_attr($settings['status_soldout'] ?? '#1a202c'); ?>;
    --ensemble-status-postponed: <?php echo esc_attr($settings['status_postponed'] ?? '#d97706'); ?>;
    
    /* Gradients */
    --ensemble-gradient-start: <?php echo esc_attr($settings['gradient_start'] ?? 'rgba(0, 0, 0, 0.8)'); ?>;
    --ensemble-gradient-mid: <?php echo esc_attr($settings['gradient_mid'] ?? 'rgba(0, 0, 0, 0.4)'); ?>;
    --ensemble-gradient-end: <?php echo esc_attr($settings['gradient_end'] ?? 'transparent'); ?>;
    
    /* Social */
    --ensemble-facebook-color: <?php echo esc_attr($settings['facebook_color'] ?? '#1877f2'); ?>;
    
    /* Typography - Fonts */
    --ensemble-font-heading: '<?php echo esc_attr($settings['heading_font']); ?>', sans-serif;
    --ensemble-font-body: '<?php echo esc_attr($settings['body_font']); ?>', sans-serif;
    
    /* Typography - Base Sizes (Designer Settings) */
    --ensemble-h1-size: <?php echo esc_attr($settings['h1_size']); ?>px;
    --ensemble-h2-size: <?php echo esc_attr($settings['h2_size']); ?>px;
    --ensemble-h3-size: <?php echo esc_attr($settings['h3_size']); ?>px;
    --ensemble-body-size: <?php echo esc_attr($settings['body_size']); ?>px;
    --ensemble-small-size: <?php echo esc_attr($settings['small_size']); ?>px;
    
    /* Typography - Extended Sizes (Semantic naming for specific use cases) */
    --ensemble-xs-size: <?php echo esc_attr($settings['xs_size'] ?? 12); ?>px;
    --ensemble-meta-size: <?php echo esc_attr($settings['meta_size'] ?? 14); ?>px;
    --ensemble-lg-size: <?php echo esc_attr($settings['lg_size'] ?? 18); ?>px;
    --ensemble-xl-size: <?php echo esc_attr($settings['xl_size'] ?? 20); ?>px;
    --ensemble-price-size: <?php echo esc_attr($settings['price_size'] ?? 32); ?>px;
    --ensemble-hero-size: <?php echo esc_attr($settings['hero_size'] ?? 72); ?>px;
    
    /* Typography - Weights */
    --ensemble-heading-weight: <?php echo esc_attr($settings['heading_weight']); ?>;
    --ensemble-body-weight: <?php echo esc_attr($settings['body_weight']); ?>;
    
    /* Typography - Line Heights */
    --ensemble-line-height-heading: <?php echo esc_attr($settings['line_height_heading']); ?>;
    --ensemble-line-height-body: <?php echo esc_attr($settings['line_height_body']); ?>;
    
    /* Buttons - Light Mode */
    --ensemble-button-bg: <?php echo esc_attr($settings['button_bg']); ?>;
    --ensemble-button-text: <?php echo esc_attr($settings['button_text']); ?>;
    --ensemble-button-hover-bg: <?php echo esc_attr($settings['button_hover_bg']); ?>;
    --ensemble-button-hover-text: <?php echo esc_attr($settings['button_hover_text']); ?>;
    --ensemble-button-radius: <?php echo esc_attr($settings['button_radius']); ?>px;
    --ensemble-button-padding-v: <?php echo esc_attr($settings['button_padding_v']); ?>px;
    --ensemble-button-padding-h: <?php echo esc_attr($settings['button_padding_h']); ?>px;
    --ensemble-button-weight: <?php echo esc_attr($settings['button_weight']); ?>;
    
    /* Cards */
    --ensemble-card-radius: <?php echo esc_attr($settings['card_radius']); ?>px;
    --ensemble-card-padding: <?php echo esc_attr($settings['card_padding']); ?>px;
    --ensemble-card-image-height: <?php echo esc_attr($settings['card_image_height']); ?>px;
    --ensemble-card-border-width: <?php echo esc_attr($settings['card_border_width']); ?>px;
    --ensemble-card-shadow: <?php 
        $shadow = $settings['card_shadow'] ?? 'medium';
        switch ($shadow) {
            case 'none': echo 'none'; break;
            case 'light': echo '0 1px 3px rgba(0, 0, 0, 0.08)'; break;
            case 'heavy': echo '0 10px 30px rgba(0, 0, 0, 0.15)'; break;
            default: echo '0 4px 6px rgba(0, 0, 0, 0.1)'; // medium
        }
    ?>;
    --ensemble-card-hover-transform: <?php 
        $hover = $settings['card_hover'] ?? 'lift';
        echo ($hover === 'lift' || $hover === 'glow') ? 'translateY(-5px)' : 'none';
    ?>;
    --ensemble-card-hover-shadow: <?php 
        $hover = $settings['card_hover'] ?? 'lift';
        switch ($hover) {
            case 'lift': echo '0 10px 40px rgba(0, 0, 0, 0.2)'; break;
            case 'glow': echo '0 0 30px var(--ensemble-primary)'; break;
            default: echo 'var(--ensemble-card-shadow)';
        }
    ?>;
    
    /* Button Style */
    --ensemble-button-style: <?php echo esc_attr($settings['button_style'] ?? 'solid'); ?>;
    --ensemble-button-font-size: <?php echo esc_attr($settings['button_font_size'] ?? 16); ?>px;
    --ensemble-button-border-width: <?php echo esc_attr($settings['button_border_width'] ?? 2); ?>px;
    --ensemble-button-border: <?php echo esc_attr($settings['button_bg']); ?>;
    
    /* Line Height (generisch) */
    --ensemble-line-height: <?php echo esc_attr($settings['line_height_body']); ?>;
    
    /* Layout */
    --ensemble-container-width: <?php echo esc_attr($settings['container_width']); ?>px;
    --ensemble-grid-columns: <?php echo esc_attr($settings['grid_columns']); ?>;
    --ensemble-card-gap: <?php echo esc_attr($settings['grid_gap']); ?>px;
    --ensemble-grid-gap: <?php echo esc_attr($settings['grid_gap']); ?>px;
    --ensemble-section-spacing: <?php echo esc_attr($settings['section_spacing']); ?>px;
    
    /* Calendar - Light Mode */
    --ensemble-cal-header-bg: <?php echo esc_attr($settings['calendar_header_bg']); ?>;
    --ensemble-cal-header-text: <?php echo esc_attr($settings['calendar_header_text']); ?>;
    --ensemble-cal-cell-bg: <?php echo esc_attr($settings['calendar_cell_bg']); ?>;
    --ensemble-cal-cell-hover: <?php echo esc_attr($settings['calendar_cell_hover']); ?>;
    --ensemble-cal-today-bg: <?php echo esc_attr($settings['calendar_today_bg']); ?>;
    --ensemble-cal-today-text: <?php echo esc_attr($settings['calendar_today_text']); ?>;
    --ensemble-cal-event-bg: <?php echo esc_attr($settings['calendar_event_bg']); ?>;
    
    /* Filters - Light Mode */
    --ensemble-filter-bg: <?php echo esc_attr($settings['filter_bg']); ?>;
    
    /* Dark Mode Variables - Available globally for dark layouts like Lovepop */
    --ensemble-dark-primary: <?php echo esc_attr($dark_settings['primary_color']); ?>;
    --ensemble-dark-bg: <?php echo esc_attr($dark_settings['background_color']); ?>;
    --ensemble-dark-text: <?php echo esc_attr($dark_settings['text_color']); ?>;
    --ensemble-dark-text-secondary: <?php echo esc_attr($dark_settings['text_secondary']); ?>;
    --ensemble-dark-text-muted: <?php echo esc_attr($dark_settings['text_muted'] ?? '#666666'); ?>;
    --ensemble-dark-card-bg: <?php echo esc_attr($dark_settings['card_background']); ?>;
    --ensemble-dark-card-border: <?php echo esc_attr($dark_settings['card_border']); ?>;
    --ensemble-dark-hover: <?php echo esc_attr($dark_settings['hover_color']); ?>;
    --ensemble-dark-link: <?php echo esc_attr($dark_settings['link_color'] ?? $dark_settings['primary_color']); ?>;
    --ensemble-dark-button-bg: <?php echo esc_attr($dark_settings['button_bg']); ?>;
    --ensemble-dark-button-text: <?php echo esc_attr($dark_settings['button_text']); ?>;
    --ensemble-dark-button-hover-bg: <?php echo esc_attr($dark_settings['button_hover_bg']); ?>;
    --ensemble-dark-button-hover-text: <?php echo esc_attr($dark_settings['button_hover_text']); ?>;
    
    /* Dark Surface & Overlay */
    --ensemble-dark-surface: <?php echo esc_attr($dark_settings['surface_color'] ?? '#111111'); ?>;
    --ensemble-dark-divider: <?php echo esc_attr($dark_settings['divider_color'] ?? '#333333'); ?>;
    --ensemble-dark-overlay-bg: <?php echo esc_attr($dark_settings['overlay_bg'] ?? 'rgba(255, 255, 255, 0.9)'); ?>;
    --ensemble-dark-overlay-text: <?php echo esc_attr($dark_settings['overlay_text'] ?? '#111111'); ?>;
    --ensemble-dark-placeholder-bg: <?php echo esc_attr($dark_settings['placeholder_bg'] ?? '#2a2a2a'); ?>;
    --ensemble-dark-placeholder-icon: <?php echo esc_attr($dark_settings['placeholder_icon'] ?? 'rgba(255, 255, 255, 0.3)'); ?>;
    
    /* Aliase für alternative Namenskonventionen (manche Layouts verwenden *-dark statt dark-*) */
    --ensemble-bg-dark: var(--ensemble-dark-bg);
    --ensemble-text-dark: var(--ensemble-dark-text);
    --ensemble-text-secondary-dark: var(--ensemble-dark-text-secondary);
    --ensemble-text-muted-dark: var(--ensemble-dark-text-muted);
    --ensemble-primary-dark: var(--ensemble-dark-primary);
    --ensemble-hover-dark: var(--ensemble-dark-hover);
    --ensemble-link-dark: var(--ensemble-dark-link);
    --ensemble-card-bg-dark: var(--ensemble-dark-card-bg);
    --ensemble-card-border-dark: var(--ensemble-dark-card-border);
    --ensemble-surface-dark: var(--ensemble-dark-surface);
    --ensemble-divider-dark: var(--ensemble-dark-divider);
    --ensemble-button-text-dark: var(--ensemble-dark-button-text);
    --ensemble-button-border-dark: var(--ensemble-dark-button-text);
    --ensemble-button-hover-bg-dark: var(--ensemble-dark-button-hover-bg);
    --ensemble-button-hover-text-dark: var(--ensemble-dark-button-hover-text);
    --ensemble-overlay-bg-dark: var(--ensemble-dark-overlay-bg);
    --ensemble-overlay-text-dark: var(--ensemble-dark-overlay-text);
    --ensemble-placeholder-bg-dark: var(--ensemble-dark-placeholder-bg);
    --ensemble-placeholder-icon-dark: var(--ensemble-dark-placeholder-icon);
    
    /* Card Hover */
    --ensemble-card-bg-hover: <?php 
        $card_bg = $settings['card_background'];
        // Slightly darker/lighter version
        echo esc_attr($settings['card_bg_hover'] ?? '#f8f9fa'); 
    ?>;
}

/* ========================================
   DARK MODE
   Applied when .es-mode-dark class is present
   ======================================== */

.es-mode-dark,
body.es-mode-dark,
.et-dark-mode {
    /* Colors - Dark Mode */
    --ensemble-primary: <?php echo esc_attr($dark_settings['primary_color']); ?>;
    --ensemble-primary-rgb: <?php echo self::hex_to_rgb($dark_settings['primary_color']); ?>;
    --ensemble-secondary: <?php echo esc_attr($dark_settings['secondary_color']); ?>;
    --ensemble-secondary-rgb: <?php echo self::hex_to_rgb($dark_settings['secondary_color']); ?>;
    --ensemble-bg: <?php echo esc_attr($dark_settings['background_color']); ?>;
    --ensemble-text: <?php echo esc_attr($dark_settings['text_color']); ?>;
    --ensemble-text-secondary: <?php echo esc_attr($dark_settings['text_secondary']); ?>;
    --ensemble-text-muted: <?php echo esc_attr($dark_settings['text_muted'] ?? '#666666'); ?>;
    --ensemble-card-bg: <?php echo esc_attr($dark_settings['card_background']); ?>;
    --ensemble-card-border: <?php echo esc_attr($dark_settings['card_border']); ?>;
    --ensemble-hover: <?php echo esc_attr($dark_settings['hover_color']); ?>;
    --ensemble-link: <?php echo esc_attr($dark_settings['link_color'] ?? $dark_settings['primary_color']); ?>;
    
    /* Surface & Dividers - Dark Mode */
    --ensemble-surface: <?php echo esc_attr($dark_settings['surface_color'] ?? '#111111'); ?>;
    --ensemble-divider: <?php echo esc_attr($dark_settings['divider_color'] ?? '#333333'); ?>;
    
    /* Overlay - Dark Mode */
    --ensemble-overlay-bg: <?php echo esc_attr($dark_settings['overlay_bg'] ?? 'rgba(255, 255, 255, 0.9)'); ?>;
    --ensemble-overlay-text: <?php echo esc_attr($dark_settings['overlay_text'] ?? '#111111'); ?>;
    --ensemble-overlay-text-secondary: <?php echo esc_attr($dark_settings['overlay_text_secondary'] ?? 'rgba(0, 0, 0, 0.7)'); ?>;
    --ensemble-overlay-text-muted: <?php echo esc_attr($dark_settings['overlay_text_muted'] ?? 'rgba(0, 0, 0, 0.5)'); ?>;
    --ensemble-overlay-border: <?php echo esc_attr($dark_settings['overlay_border'] ?? 'rgba(0, 0, 0, 0.2)'); ?>;
    
    /* Placeholder - Dark Mode */
    --ensemble-placeholder-bg: <?php echo esc_attr($dark_settings['placeholder_bg'] ?? '#2a2a2a'); ?>;
    --ensemble-placeholder-icon: <?php echo esc_attr($dark_settings['placeholder_icon'] ?? 'rgba(255, 255, 255, 0.3)'); ?>;
    
    /* Buttons - Dark Mode */
    --ensemble-button-bg: <?php echo esc_attr($dark_settings['button_bg']); ?>;
    --ensemble-button-text: <?php echo esc_attr($dark_settings['button_text']); ?>;
    --ensemble-button-hover-bg: <?php echo esc_attr($dark_settings['button_hover_bg']); ?>;
    --ensemble-button-hover-text: <?php echo esc_attr($dark_settings['button_hover_text']); ?>;
    --ensemble-button-border: <?php echo esc_attr($dark_settings['button_text']); ?>;
    
    /* Calendar - Dark Mode */
    --ensemble-cal-header-bg: <?php echo esc_attr($dark_settings['calendar_header_bg']); ?>;
    --ensemble-cal-header-text: <?php echo esc_attr($dark_settings['calendar_header_text']); ?>;
    --ensemble-cal-cell-bg: <?php echo esc_attr($dark_settings['calendar_cell_bg']); ?>;
    --ensemble-cal-cell-hover: <?php echo esc_attr($dark_settings['calendar_cell_hover']); ?>;
    --ensemble-cal-today-bg: <?php echo esc_attr($dark_settings['calendar_today_bg']); ?>;
    --ensemble-cal-today-text: <?php echo esc_attr($dark_settings['calendar_today_text']); ?>;
    --ensemble-cal-event-bg: <?php echo esc_attr($dark_settings['calendar_event_bg']); ?>;
    
    /* Filters - Dark Mode */
    --ensemble-filter-bg: <?php echo esc_attr($dark_settings['filter_bg']); ?>;
    
    /* Card Hover - Dark Mode */
    --ensemble-card-bg-hover: <?php echo esc_attr($dark_settings['card_bg_hover'] ?? '#252525'); ?>;
}

/* ========================================
   Container & Layout
   ======================================== */

.ensemble-container {
    max-width: var(--ensemble-container-width);
    margin: 0 auto;
    padding: 0 20px;
}

.ensemble-events-grid {
    display: grid;
    grid-template-columns: repeat(var(--ensemble-grid-columns), 1fr);
    gap: var(--ensemble-card-gap);
    margin: var(--ensemble-section-spacing) 0;
}

@media (max-width: 1024px) {
    .ensemble-events-grid {
        grid-template-columns: repeat(<?php echo esc_attr($settings['grid_columns_tablet']); ?>, 1fr);
    }
}

@media (max-width: 640px) {
    .ensemble-events-grid {
        grid-template-columns: repeat(<?php echo esc_attr($settings['grid_columns_mobile']); ?>, 1fr);
    }
}

/* ========================================
   Typography
   ======================================== */

.ensemble-container h1,
.ensemble-event-title {
    font-family: var(--ensemble-font-heading);
    font-size: var(--ensemble-h1-size);
    font-weight: var(--ensemble-heading-weight);
    line-height: var(--ensemble-line-height-heading);
    color: var(--ensemble-text);
    margin: 0 0 1em 0;
}

.ensemble-container h2 {
    font-family: var(--ensemble-font-heading);
    font-size: var(--ensemble-h2-size);
    font-weight: var(--ensemble-heading-weight);
    line-height: var(--ensemble-line-height-heading);
    color: var(--ensemble-text);
    margin: 0 0 0.75em 0;
}

.ensemble-container h3 {
    font-family: var(--ensemble-font-heading);
    font-size: var(--ensemble-h3-size);
    font-weight: var(--ensemble-heading-weight);
    line-height: var(--ensemble-line-height-heading);
    color: var(--ensemble-text);
    margin: 0 0 0.5em 0;
}

.ensemble-container p,
.ensemble-event-description {
    font-family: var(--ensemble-font-body);
    font-size: var(--ensemble-body-size);
    font-weight: var(--ensemble-body-weight);
    line-height: var(--ensemble-line-height-body);
    color: var(--ensemble-text);
    margin: 0 0 1em 0;
}

.ensemble-event-meta,
.ensemble-event-date,
.ensemble-event-location {
    font-family: var(--ensemble-font-body);
    font-size: var(--ensemble-small-size);
    color: var(--ensemble-text-secondary);
    line-height: var(--ensemble-line-height-body);
}

/* ========================================
   Event Cards
   ======================================== */

.ensemble-event-card {
    background: var(--ensemble-card-bg);
    border: 1px solid var(--ensemble-card-border);
    border-radius: var(--ensemble-card-radius);
    padding: var(--ensemble-card-padding);
    overflow: hidden;
    transition: all 0.3s ease;
    <?php echo self::generate_card_shadow($settings['card_shadow']); ?>
}

<?php if ($settings['card_hover'] === 'lift'): ?>
.ensemble-event-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}
<?php endif; ?>

<?php if ($settings['card_hover'] === 'glow'): ?>
.ensemble-event-card:hover {
    box-shadow: 0 0 30px rgba(102, 126, 234, 0.4);
    border-color: var(--ensemble-primary);
}
<?php endif; ?>

<?php if ($settings['card_hover'] === 'border'): ?>
.ensemble-event-card:hover {
    border-color: var(--ensemble-primary);
    border-width: 2px;
}
<?php endif; ?>

.ensemble-event-image {
    width: 100%;
    height: var(--ensemble-card-image-height);
    object-fit: <?php echo esc_attr($settings['card_image_fit']); ?>;
    border-radius: calc(var(--ensemble-card-radius) - 4px);
    margin-bottom: 20px;
}

.ensemble-event-card-title {
    font-family: var(--ensemble-font-heading);
    font-size: var(--ensemble-h3-size);
    font-weight: var(--ensemble-heading-weight);
    color: var(--ensemble-text);
    margin: 0 0 12px 0;
}

.ensemble-event-card-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
}

.ensemble-event-card-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: var(--ensemble-small-size);
    color: var(--ensemble-text-secondary);
}

/* ========================================
   Buttons
   ======================================== */

.ensemble-button,
.ensemble-event-button {
    <?php echo self::generate_button_style($settings); ?>
}

<?php if ($settings['button_hover_effect'] === 'scale'): ?>
.ensemble-button:hover,
.ensemble-event-button:hover {
    transform: scale(1.05);
}
<?php endif; ?>

<?php if ($settings['button_hover_effect'] === 'shadow'): ?>
.ensemble-button:hover,
.ensemble-event-button:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}
<?php endif; ?>

/* ========================================
   Filters
   ======================================== */

<?php if ($settings['filter_position'] === 'above'): ?>
.ensemble-filters {
    background: var(--ensemble-filter-bg);
    padding: 24px;
    border-radius: var(--ensemble-card-radius);
    margin-bottom: var(--ensemble-card-gap);
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}
<?php endif; ?>

.ensemble-filter-item {
    flex: 1;
    min-width: 200px;
}

.ensemble-filter-item label {
    display: block;
    font-size: var(--ensemble-small-size);
    font-weight: 600;
    color: var(--ensemble-text);
    margin-bottom: 6px;
}

.ensemble-filter-item input,
.ensemble-filter-item select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--ensemble-card-border);
    border-radius: 6px;
    font-family: var(--ensemble-font-body);
    font-size: var(--ensemble-body-size);
    background: var(--ensemble-card-bg);
    color: var(--ensemble-text);
}

.ensemble-filter-item input:focus,
.ensemble-filter-item select:focus {
    outline: none;
    border-color: var(--ensemble-primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* ========================================
   Calendar Styles
   ======================================== */

.ensemble-calendar {
    background: var(--ensemble-card-bg);
    border: 1px solid var(--ensemble-card-border);
    border-radius: var(--ensemble-card-radius);
    overflow: hidden;
}

.ensemble-calendar-header {
    background: var(--ensemble-cal-header-bg);
    color: var(--ensemble-cal-header-text);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ensemble-calendar-cell {
    background: var(--ensemble-cal-cell-bg);
    border: 1px solid var(--ensemble-card-border);
    padding: 10px;
    min-height: 80px;
    transition: background-color 0.2s ease;
}

.ensemble-calendar-cell:hover {
    background: var(--ensemble-cal-cell-hover);
}

.ensemble-calendar-cell.today {
    background: var(--ensemble-cal-today-bg);
    color: var(--ensemble-cal-today-text);
}

.ensemble-calendar-event {
    background: var(--ensemble-cal-event-bg);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: var(--ensemble-small-size);
    margin-top: 4px;
}

/* ========================================
   Responsive Utilities
   ======================================== */

@media (max-width: 768px) {
    :root {
        --ensemble-h1-size: calc(<?php echo esc_attr($settings['h1_size']); ?>px * 0.8);
        --ensemble-h2-size: calc(<?php echo esc_attr($settings['h2_size']); ?>px * 0.85);
        --ensemble-h3-size: calc(<?php echo esc_attr($settings['h3_size']); ?>px * 0.9);
    }
    
    .ensemble-filters {
        flex-direction: column;
    }
    
    .ensemble-filter-item {
        width: 100%;
    }
}

/* ========================================
   Loading States
   ======================================== */

.ensemble-loading {
    text-align: center;
    padding: 40px;
}

.ensemble-spinner {
    border: 3px solid var(--ensemble-card-border);
    border-top-color: var(--ensemble-primary);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: ensemble-spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes ensemble-spin {
    to { transform: rotate(360deg); }
}

/* ========================================
   No Results Message
   ======================================== */

.ensemble-no-results {
    text-align: center;
    padding: 60px 20px;
    background: var(--ensemble-filter-bg);
    border-radius: var(--ensemble-card-radius);
    margin: var(--ensemble-card-gap) 0;
}

.ensemble-no-results h3 {
    color: var(--ensemble-text);
    margin-bottom: 12px;
}

.ensemble-no-results p {
    color: var(--ensemble-text-secondary);
}

        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate card shadow CSS
     */
    private static function generate_card_shadow($shadow) {
        switch ($shadow) {
            case 'none':
                return 'box-shadow: none;';
            case 'light':
                return 'box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);';
            case 'medium':
                return 'box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);';
            case 'heavy':
                return 'box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);';
            default:
                return 'box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);';
        }
    }
    
    /**
     * Generate button style CSS
     */
    private static function generate_button_style($settings) {
        $css = '';
        
        $css .= 'display: inline-block;' . "\n    ";
        $css .= 'padding: var(--ensemble-button-padding-v) var(--ensemble-button-padding-h);' . "\n    ";
        $css .= 'border-radius: var(--ensemble-button-radius);' . "\n    ";
        $css .= 'font-family: var(--ensemble-font-body);' . "\n    ";
        $css .= 'font-size: var(--ensemble-body-size);' . "\n    ";
        $css .= 'font-weight: var(--ensemble-button-weight);' . "\n    ";
        $css .= 'text-decoration: none;' . "\n    ";
        $css .= 'text-align: center;' . "\n    ";
        $css .= 'cursor: pointer;' . "\n    ";
        $css .= 'transition: all 0.3s ease;' . "\n    ";
        $css .= 'border: 2px solid transparent;' . "\n    ";
        
        switch ($settings['button_style']) {
            case 'solid':
                $css .= 'background: var(--ensemble-button-bg);' . "\n    ";
                $css .= 'color: var(--ensemble-button-text);' . "\n    ";
                $css .= 'border-color: var(--ensemble-button-bg);';
                break;
                
            case 'outline':
                $css .= 'background: transparent;' . "\n    ";
                $css .= 'color: var(--ensemble-button-bg);' . "\n    ";
                $css .= 'border-color: var(--ensemble-button-bg);';
                break;
                
            case 'ghost':
                $css .= 'background: transparent;' . "\n    ";
                $css .= 'color: var(--ensemble-button-bg);' . "\n    ";
                $css .= 'border-color: transparent;';
                break;
                
            case 'gradient':
                $css .= 'background: linear-gradient(135deg, var(--ensemble-primary), var(--ensemble-secondary));' . "\n    ";
                $css .= 'color: var(--ensemble-button-text);' . "\n    ";
                $css .= 'border: none;';
                break;
        }
        
        return $css;
    }
    
    /**
     * Convert hex color to RGB values
     * 
     * @param string $hex Hex color (with or without #)
     * @return string RGB values as "r, g, b"
     */
    private static function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        if (strlen($hex) !== 6) {
            return '0, 0, 0';
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "{$r}, {$g}, {$b}";
    }
    
    /**
     * Enqueue custom CSS
     */
    public static function enqueue_custom_css() {
        // Generate CSS based on effective settings (respects design mode)
        // All layouts can now be customized via Designer
        $css = self::generate();
        
        // Add inline style to ensemble-base or ensemble-shortcodes
        // These are the main frontend stylesheets
        if (wp_style_is('ensemble-base', 'enqueued')) {
            wp_add_inline_style('ensemble-base', $css);
        } elseif (wp_style_is('ensemble-shortcodes', 'enqueued')) {
            wp_add_inline_style('ensemble-shortcodes', $css);
        } else {
            // Fallback: Register our own style handle
            wp_register_style('ensemble-custom-design', false);
            wp_enqueue_style('ensemble-custom-design');
            wp_add_inline_style('ensemble-custom-design', $css);
        }
    }
}

// Enqueue custom CSS on frontend with lower priority to ensure base styles are loaded first
add_action('wp_enqueue_scripts', array('ES_CSS_Generator', 'enqueue_custom_css'), 99);
