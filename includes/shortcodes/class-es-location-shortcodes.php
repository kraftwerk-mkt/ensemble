<?php
/**
 * Location Shortcodes Class
 * 
 * Handles all location-related shortcodes with full display options.
 * Uses Layout-Set templates when available.
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Location_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Shortcodes werden über register_shortcodes() registriert
        // wenn von ES_Shortcodes aufgerufen
        
        // Ensure Slider Renderer is initialized for slider layout
        if (class_exists('ES_Slider_Renderer')) {
            ES_Slider_Renderer::init();
        }
    }
    
    /**
     * Register shortcodes
     * Called by ES_Shortcodes main class
     */
    public function register_shortcodes() {
        add_shortcode('ensemble_locations', array($this, 'locations_list_shortcode'));
        add_shortcode('ensemble_location', array($this, 'single_location_shortcode'));
    }
    
    /**
     * Locations List Shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function locations_list_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('locations');
        }
        
        // Parse attributes with ALL display options
        $atts = shortcode_atts(array(
            // Layout
            'layout'           => 'grid',
            'columns'          => '3',
            'limit'            => '12',
            'orderby'          => 'title',
            'order'            => 'ASC',
            'type'             => '',
            
            // Display Options - ALL from Location Manager
            'show_image'       => 'true',
            'show_name'        => 'true',
            'show_type'        => 'true',
            'show_address'     => 'true',
            'show_capacity'    => 'false',
            'show_events'      => 'false',
            'show_description' => 'false',
            'show_social'      => 'false',
            'show_link'        => 'true',
            'link_text'        => __('View Location', 'ensemble'),
            
            // Slider options
            'autoplay'         => 'false',
            'autoplay_speed'   => '5000',
            'loop'             => 'false',
            'dots'             => 'true',
            'arrows'           => 'true',
            'gap'              => '24',
        ), $atts, 'ensemble_locations');
        
        // Sanitize
        $layout = sanitize_key($atts['layout']);
        $columns = absint($atts['columns']);
        $limit = absint($atts['limit']);
        $orderby = sanitize_key($atts['orderby']);
        $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';
        $type = sanitize_text_field($atts['type']);
        
        // Slider options
        $autoplay = filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN);
        $autoplay_speed = absint($atts['autoplay_speed']);
        $loop = filter_var($atts['loop'], FILTER_VALIDATE_BOOLEAN);
        $show_dots = filter_var($atts['dots'], FILTER_VALIDATE_BOOLEAN);
        $show_arrows = filter_var($atts['arrows'], FILTER_VALIDATE_BOOLEAN);
        $gap = absint($atts['gap']);
        $is_slider_layout = in_array($layout, array('slider', 'carousel'));
        
        // Validate columns
        if (!in_array($columns, array(2, 3, 4))) {
            $columns = 3;
        }
        
        // Query args
        $args = array(
            'post_type'      => 'ensemble_location',
            'posts_per_page' => $limit,
            'orderby'        => $orderby,
            'order'          => $order,
            'post_status'    => 'publish',
        );
        
        // Type filter
        if ($type) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_location_type',
                    'field'    => 'slug',
                    'terms'    => explode(',', $type),
                ),
            );
        }
        
        // Get locations
        $locations_query = new WP_Query($args);
        
        if (!$locations_query->have_posts()) {
            return '<div class="ensemble-no-results">' . __('No locations found.', 'ensemble') . '</div>';
        }
        
        // Build output
        ob_start();
        
        // ========================================
        // SLIDER LAYOUT
        // ========================================
        if ($is_slider_layout && class_exists('ES_Slider_Renderer')):
            
            // Ensure slider assets are loaded
            // If we're past wp_head, enqueue for footer
            $plugin_url = defined('ENSEMBLE_PLUGIN_URL') ? ENSEMBLE_PLUGIN_URL : plugins_url('/', dirname(__FILE__));
            $version = defined('ENSEMBLE_VERSION') ? ENSEMBLE_VERSION : '2.9.0';
            
            // Register if not yet registered
            if (!wp_style_is('ensemble-slider', 'registered')) {
                wp_register_style('ensemble-slider', $plugin_url . 'assets/css/ensemble-slider.css', array(), $version);
                wp_register_script('ensemble-slider', $plugin_url . 'assets/js/ensemble-slider.js', array(), $version, true);
            }
            
            // Enqueue - if wp_head already done, these will go to footer
            wp_enqueue_style('ensemble-slider');
            wp_enqueue_script('ensemble-slider');
            
            // Fallback: If styles weren't loaded in head, add them inline
            if (did_action('wp_head') && !wp_style_is('ensemble-slider', 'done')) {
                add_action('wp_footer', function() use ($plugin_url, $version) {
                    if (!wp_style_is('ensemble-slider', 'done')) {
                        echo '<link rel="stylesheet" href="' . esc_url($plugin_url . 'assets/css/ensemble-slider.css?ver=' . $version) . '" />';
                        echo '<script src="' . esc_url($plugin_url . 'assets/js/ensemble-slider.js?ver=' . $version) . '"></script>';
                    }
                }, 5);
            }
            
            $slider_options = array(
                'slides_to_show'   => $columns,
                'slides_to_scroll' => 1,
                'autoplay'         => $autoplay,
                'autoplay_speed'   => $autoplay_speed,
                'loop'             => $loop,
                'dots'             => $show_dots,
                'arrows'           => $show_arrows,
                'gap'              => $gap,
            );
            
            echo ES_Slider_Renderer::render_wrapper_start('slider', $slider_options, 'locations');
            
            $slide_index = 0;
            while ($locations_query->have_posts()) {
                $locations_query->the_post();
                $location_id = get_the_ID();
                
                echo ES_Slider_Renderer::render_slide_start($slide_index);
                $this->render_location_grid_item($location_id, $atts);
                echo ES_Slider_Renderer::render_slide_end();
                
                $slide_index++;
            }
            
            echo ES_Slider_Renderer::render_wrapper_end();
        
        // ========================================
        // SLIDER FALLBACK (CSS-only horizontal scroll)
        // ========================================
        elseif ($is_slider_layout):
            
            $slider_id = 'ensemble-location-slider-' . uniqid();
            ?>
            <div id="<?php echo esc_attr($slider_id); ?>" class="ensemble-locations-slider-fallback" style="position: relative;">
                <div class="ensemble-slider-track" style="display: flex; gap: <?php echo esc_attr($gap); ?>px; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; padding-bottom: 10px;">
                    <?php
                    while ($locations_query->have_posts()) {
                        $locations_query->the_post();
                        $location_id = get_the_ID();
                        ?>
                        <div class="ensemble-slider-slide" style="flex: 0 0 calc(<?php echo (100 / $columns); ?>% - <?php echo ($gap * ($columns - 1) / $columns); ?>px); scroll-snap-align: start; min-width: 280px;">
                            <?php $this->render_location_grid_item($location_id, $atts); ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php if ($show_arrows): ?>
                <button class="ensemble-slider-prev" onclick="document.querySelector('#<?php echo esc_js($slider_id); ?> .ensemble-slider-track').scrollBy({left: -300, behavior: 'smooth'})" style="position: absolute; left: -20px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border-radius: 50%; background: var(--ensemble-card-bg, #fff); border: 1px solid var(--ensemble-card-border, #e0e0e0); box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <button class="ensemble-slider-next" onclick="document.querySelector('#<?php echo esc_js($slider_id); ?> .ensemble-slider-track').scrollBy({left: 300, behavior: 'smooth'})" style="position: absolute; right: -20px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border-radius: 50%; background: var(--ensemble-card-bg, #fff); border: 1px solid var(--ensemble-card-border, #e0e0e0); box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </button>
                <?php endif; ?>
            </div>
            <style>
                .ensemble-locations-slider-fallback .ensemble-slider-track::-webkit-scrollbar { height: 6px; }
                .ensemble-locations-slider-fallback .ensemble-slider-track::-webkit-scrollbar-track { background: var(--ensemble-surface, #f0f0f0); border-radius: 3px; }
                .ensemble-locations-slider-fallback .ensemble-slider-track::-webkit-scrollbar-thumb { background: var(--ensemble-primary, #333); border-radius: 3px; }
                @media (max-width: 768px) {
                    .ensemble-locations-slider-fallback .ensemble-slider-prev,
                    .ensemble-locations-slider-fallback .ensemble-slider-next { display: none !important; }
                }
            </style>
            <?php
            
        else:
        // ========================================
        // STANDARD GRID / LIST / CARDS LAYOUT
        // ========================================
        
        // Check if using Kongress layout set for correct CSS class
        $active_set = '';
        if (class_exists('ES_Layout_Sets')) {
            $active_set = ES_Layout_Sets::get_active_set();
        }
        
        // Use Kongress-specific grid class if that layout is active
        if ($active_set === 'kongress' && $layout === 'grid') {
            $container_class = 'es-kongress-locations-grid';
        } else {
            $container_class = 'ensemble-locations-list ensemble-layout-' . $layout;
            if (in_array($layout, array('grid', 'cards'))) {
                $container_class .= ' ensemble-columns-' . $columns;
            }
        }
        
        echo '<div class="' . esc_attr($container_class) . '">';
        
        while ($locations_query->have_posts()) {
            $locations_query->the_post();
            $location_id = get_the_ID();
            
            if ($layout === 'list') {
                $this->render_location_list_item($location_id, $atts);
            } elseif ($layout === 'cards') {
                $this->render_location_card_item($location_id, $atts);
            } else {
                $this->render_location_grid_item($location_id, $atts);
            }
        }
        
        echo '</div>';
        
        endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Render Location Grid Item
     * Uses template from Layout-Set if available
     */
    private function render_location_grid_item($location_id, $atts) {
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('location-card.php', $active_set);
            
            if ($template_path && file_exists($template_path)) {
                $shortcode_atts = $atts;
                include $template_path;
                return;
            }
        }
        
        // Fallback: Default template with Designer variables
        $this->render_location_fallback($location_id, $atts);
    }
    
    /**
     * Render Location List Item
     */
    private function render_location_list_item($location_id, $atts) {
        // Parse display options
        $show_image       = !isset($atts['show_image']) || filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_name        = !isset($atts['show_name']) || filter_var($atts['show_name'], FILTER_VALIDATE_BOOLEAN);
        $show_type        = !isset($atts['show_type']) || filter_var($atts['show_type'], FILTER_VALIDATE_BOOLEAN);
        $show_address     = !isset($atts['show_address']) || filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_capacity    = isset($atts['show_capacity']) && filter_var($atts['show_capacity'], FILTER_VALIDATE_BOOLEAN);
        $show_events      = isset($atts['show_events']) && filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_description = isset($atts['show_description']) && filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_link        = !isset($atts['show_link']) || filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text        = isset($atts['link_text']) ? $atts['link_text'] : __('View Location', 'ensemble');
        
        $location_post = get_post($location_id);
        $permalink = get_permalink($location_id);
        
        // Get address & city
        $address = get_post_meta($location_id, 'location_address', true);
        $city = get_post_meta($location_id, 'location_city', true);
        $capacity = get_post_meta($location_id, 'location_capacity', true);
        
        // Location types
        $type_text = '';
        $location_types = get_the_terms($location_id, 'ensemble_location_type');
        if ($location_types && !is_wp_error($location_types)) {
            $type_text = $location_types[0]->name;
        }
        
        // Event count
        $events_count = 0;
        if (function_exists('ensemble_get_location_event_count')) {
            $events_count = ensemble_get_location_event_count($location_id, true);
        }
        ?>
        <div class="ensemble-location-list-item" style="display: flex; gap: 20px; padding: 20px; background: var(--ensemble-card-bg); border: var(--ensemble-card-border-width, 1px) solid var(--ensemble-card-border); border-radius: var(--ensemble-card-radius, 8px); margin-bottom: 16px;">
            <?php if ($show_image && has_post_thumbnail($location_id)): ?>
            <div class="ensemble-location-list-image" style="flex-shrink: 0; width: 120px; height: 120px; border-radius: var(--ensemble-card-radius, 8px); overflow: hidden;">
                <img src="<?php echo get_the_post_thumbnail_url($location_id, 'medium'); ?>" alt="<?php echo esc_attr($location_post->post_title); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <?php endif; ?>
            
            <div class="ensemble-location-list-content" style="flex: 1; min-width: 0;">
                <?php if ($show_type && $type_text): ?>
                <div style="font-size: var(--ensemble-xs-size, 11px); color: var(--ensemble-secondary); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 4px;">
                    <?php echo esc_html($type_text); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_name): ?>
                <h3 style="font-family: var(--ensemble-font-heading); font-size: var(--ensemble-lg-size, 18px); font-weight: var(--ensemble-heading-weight, 700); margin: 0 0 8px 0; color: var(--ensemble-text);">
                    <?php echo esc_html($location_post->post_title); ?>
                </h3>
                <?php endif; ?>
                
                <?php if ($show_address && ($address || $city)): ?>
                <div style="display: flex; align-items: center; gap: 6px; font-size: var(--ensemble-small-size, 14px); color: var(--ensemble-text-secondary); margin-bottom: 8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px; flex-shrink: 0;">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span><?php echo esc_html(trim($address . ($address && $city ? ', ' : '') . $city)); ?></span>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
                    <?php if ($show_capacity && $capacity): ?>
                    <span style="font-size: var(--ensemble-small-size, 14px); color: var(--ensemble-text-secondary);">
                        <?php printf(__('Capacity: %s', 'ensemble'), esc_html($capacity)); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($show_events && $events_count > 0): ?>
                    <span style="font-size: var(--ensemble-small-size, 14px); color: var(--ensemble-text-secondary);">
                        <?php printf(_n('%d Event', '%d Events', $events_count, 'ensemble'), $events_count); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($show_description && $location_post->post_excerpt): ?>
                <div style="font-size: var(--ensemble-small-size, 14px); color: var(--ensemble-text-secondary); margin-top: 8px;">
                    <?php echo esc_html(wp_trim_words($location_post->post_excerpt, 20)); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($show_link): ?>
            <div style="flex-shrink: 0; display: flex; align-items: center;">
                <a href="<?php echo esc_url($permalink); ?>" style="display: inline-block; padding: var(--ensemble-button-padding-v, 10px) var(--ensemble-button-padding-h, 20px); background: var(--ensemble-button-bg); color: var(--ensemble-button-text); font-size: var(--ensemble-button-font-size, 14px); font-weight: var(--ensemble-button-weight, 600); border-radius: var(--ensemble-button-radius, 4px); text-decoration: none; transition: all 0.3s ease;">
                    <?php echo esc_html($link_text); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render Location Card Item (Large)
     */
    private function render_location_card_item($location_id, $atts) {
        $show_image = !isset($atts['show_image']) || filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_link = !isset($atts['show_link']) || filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = isset($atts['link_text']) ? $atts['link_text'] : __('View Location', 'ensemble');
        
        $location_post = get_post($location_id);
        $permalink = get_permalink($location_id);
        $address = get_post_meta($location_id, 'location_address', true);
        $city = get_post_meta($location_id, 'location_city', true);
        ?>
        <div class="ensemble-location-card ensemble-location-card--large" style="background: var(--ensemble-card-bg); border: var(--ensemble-card-border-width, 1px) solid var(--ensemble-card-border); border-radius: var(--ensemble-card-radius, 8px); overflow: hidden; box-shadow: var(--ensemble-card-shadow); transition: all 0.3s ease;">
            <?php if ($show_image): ?>
            <div class="ensemble-location-portrait" style="aspect-ratio: 16/9; overflow: hidden;">
                <?php if (has_post_thumbnail($location_id)): ?>
                    <a href="<?php echo esc_url($permalink); ?>">
                        <img src="<?php echo get_the_post_thumbnail_url($location_id, 'large'); ?>" alt="<?php echo esc_attr($location_post->post_title); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                    </a>
                <?php else: ?>
                    <div style="width: 100%; height: 100%; background: var(--ensemble-placeholder-bg, #f0f0f0); display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-placeholder-icon, #999)" stroke-width="1" style="width: 64px; height: 64px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-location-info" style="padding: var(--ensemble-card-padding, 20px);">
                <h3 style="font-family: var(--ensemble-font-heading); font-size: var(--ensemble-lg-size, 18px); font-weight: var(--ensemble-heading-weight, 700); margin: 0 0 8px 0;">
                    <a href="<?php echo esc_url($permalink); ?>" style="color: var(--ensemble-text); text-decoration: none;">
                        <?php echo esc_html($location_post->post_title); ?>
                    </a>
                </h3>
                
                <?php if ($address || $city): ?>
                <div style="font-size: var(--ensemble-small-size, 14px); color: var(--ensemble-text-secondary); margin-bottom: 16px;">
                    <?php echo esc_html(trim($address . ($address && $city ? ', ' : '') . $city)); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <a href="<?php echo esc_url($permalink); ?>" style="display: inline-block; padding: var(--ensemble-button-padding-v, 10px) var(--ensemble-button-padding-h, 20px); background: var(--ensemble-button-bg); color: var(--ensemble-button-text); font-size: var(--ensemble-button-font-size, 14px); font-weight: var(--ensemble-button-weight, 600); border-radius: var(--ensemble-button-radius, 4px); text-decoration: none;">
                    <?php echo esc_html($link_text); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Fallback Render for Grid Item
     */
    private function render_location_fallback($location_id, $atts) {
        $show_image       = !isset($atts['show_image']) || filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_name        = !isset($atts['show_name']) || filter_var($atts['show_name'], FILTER_VALIDATE_BOOLEAN);
        $show_type        = !isset($atts['show_type']) || filter_var($atts['show_type'], FILTER_VALIDATE_BOOLEAN);
        $show_address     = !isset($atts['show_address']) || filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_link        = !isset($atts['show_link']) || filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        
        $location_post = get_post($location_id);
        $permalink = get_permalink($location_id);
        $address = get_post_meta($location_id, 'location_address', true);
        $city = get_post_meta($location_id, 'location_city', true);
        
        $type_text = '';
        $location_types = get_the_terms($location_id, 'ensemble_location_type');
        if ($location_types && !is_wp_error($location_types)) {
            $type_text = $location_types[0]->name;
        }
        ?>
        <div class="ensemble-location-card" style="background: var(--ensemble-card-bg); border: var(--ensemble-card-border-width, 1px) solid var(--ensemble-card-border); border-radius: var(--ensemble-card-radius, 8px); overflow: hidden; box-shadow: var(--ensemble-card-shadow); transition: all 0.3s ease;">
            <?php if ($show_image): ?>
            <div style="height: 180px; overflow: hidden;">
                <?php if (has_post_thumbnail($location_id)): ?>
                    <?php if ($show_link): ?><a href="<?php echo esc_url($permalink); ?>"><?php endif; ?>
                        <img src="<?php echo get_the_post_thumbnail_url($location_id, 'medium'); ?>" alt="<?php echo esc_attr($location_post->post_title); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php if ($show_link): ?></a><?php endif; ?>
                <?php else: ?>
                    <div style="width: 100%; height: 100%; background: var(--ensemble-placeholder-bg, #f0f0f0); display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-placeholder-icon, #999)" stroke-width="1" style="width: 48px; height: 48px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div style="padding: var(--ensemble-card-padding, 16px);">
                <?php if ($show_type && $type_text): ?>
                <div style="font-size: var(--ensemble-xs-size, 11px); color: var(--ensemble-secondary); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 8px;">
                    <?php echo esc_html($type_text); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_name): ?>
                <h3 style="font-family: var(--ensemble-font-heading); font-size: var(--ensemble-lg-size, 18px); font-weight: var(--ensemble-heading-weight, 700); margin: 0 0 12px 0;">
                    <?php if ($show_link): ?><a href="<?php echo esc_url($permalink); ?>" style="color: var(--ensemble-text); text-decoration: none;"><?php endif; ?>
                        <?php echo esc_html($location_post->post_title); ?>
                    <?php if ($show_link): ?></a><?php endif; ?>
                </h3>
                <?php endif; ?>
                
                <?php if ($show_address && ($address || $city)): ?>
                <div style="display: flex; align-items: flex-start; gap: 8px; font-size: var(--ensemble-small-size, 14px); color: var(--ensemble-text-secondary); line-height: 1.4;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px; flex-shrink: 0; margin-top: 2px; color: var(--ensemble-primary);">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span><?php echo esc_html(trim($address . ($address && $city ? ', ' : '') . $city)); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Single Location Shortcode
     */
    public function single_location_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('locations');
        }
        
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'ensemble_location');
        
        $location_id = absint($atts['id']);
        
        if (!$location_id) {
            return '<div class="ensemble-error">' . __('Please specify a location ID.', 'ensemble') . '</div>';
        }
        
        $location = get_post($location_id);
        if (!$location || $location->post_type !== 'ensemble_location') {
            return '<div class="ensemble-error">' . __('Location not found.', 'ensemble') . '</div>';
        }
        
        ob_start();
        $this->render_location_grid_item($location_id, array(
            'show_image'       => 'true',
            'show_name'        => 'true',
            'show_type'        => 'true',
            'show_address'     => 'true',
            'show_capacity'    => 'true',
            'show_events'      => 'true',
            'show_description' => 'true',
            'show_social'      => 'true',
            'show_link'        => 'true',
        ));
        return ob_get_clean();
    }
}
