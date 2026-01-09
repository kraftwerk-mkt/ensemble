<?php
/**
 * Ensemble Location Shortcodes
 *
 * Handles all location-related shortcodes including location lists, grids,
 * and single location displays.
 *
 * @package Ensemble
 * @subpackage Shortcodes
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Location Shortcodes class.
 *
 * @since 3.0.0
 */
class ES_Location_Shortcodes extends ES_Shortcode_Base {

	/**
	 * Register location-related shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'ensemble_locations', array( $this, 'locations_list_shortcode' ) );
		add_shortcode( 'ensemble_location', array( $this, 'single_location_shortcode' ) );
	}

    public function locations_list_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'layout' => 'grid',           // grid, list, slider, cards
            'columns' => '3',             // 2, 3, 4 (nur für grid)
            'limit' => '12',              // Anzahl Locations
            'orderby' => 'title',         // title, date, menu_order
            'order' => 'ASC',             // ASC, DESC
            'type' => '',                 // Location Type slug
            'show_image' => 'true',       // Bild anzeigen
            'show_address' => 'true',     // Adresse anzeigen
            'show_description' => 'true', // Description anzeigen
            'show_events' => 'false',     // Kommende Events anzeigen
            'show_link' => 'true',        // Link zur Location
            'link_text' => 'View Location', // Link Text
            // Slider options
            'autoplay' => 'false',
            'autoplay_speed' => '5000',
            'loop' => 'false',
            'dots' => 'true',
            'arrows' => 'true',
            'gap' => '24',
        ), $atts, 'ensemble_locations');
        
        // Sanitize
        $layout = sanitize_key($atts['layout']);
        $columns = absint($atts['columns']);
        $limit = absint($atts['limit']);
        $orderby = sanitize_key($atts['orderby']);
        $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';
        $type = sanitize_text_field($atts['type']);
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_address = filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        
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
            'post_type' => 'ensemble_location',
            'posts_per_page' => $limit,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
        );
        
        // Type filter
        if ($type) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'location_type',
                    'field' => 'slug',
                    'terms' => $type,
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
            
            $slider_options = array(
                'slides_to_show' => $columns,
                'slides_to_scroll' => 1,
                'autoplay' => $autoplay,
                'autoplay_speed' => $autoplay_speed,
                'loop' => $loop,
                'dots' => $show_dots,
                'arrows' => $show_arrows,
                'gap' => $gap,
            );
            
            echo ES_Slider_Renderer::render_wrapper_start('slider', $slider_options, 'locations');
            
            $slide_index = 0;
            while ($locations_query->have_posts()) {
                $locations_query->the_post();
                $location_id = get_the_ID();
                
                echo ES_Slider_Renderer::render_slide_start($slide_index);
                
                if ($layout === 'cards') {
                    $this->render_location_card_item($location_id, $atts);
                } else {
                    $this->render_location_grid_item($location_id, $atts);
                }
                
                echo ES_Slider_Renderer::render_slide_end();
                $slide_index++;
            }
            
            echo ES_Slider_Renderer::render_wrapper_end();
            
        else:
        // ========================================
        // STANDARD GRID / LIST / CARDS LAYOUT
        // ========================================
        
        $container_class = 'ensemble-locations-list ensemble-layout-' . $layout;
        if (in_array($layout, array('grid', 'cards'))) {
            $container_class .= ' ensemble-columns-' . $columns;
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
     */
    private function render_location_grid_item($location_id, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_address = filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        
        $location_post = get_post($location_id);
        
        // Get address (try ACF first, then meta)
        $address = '';
        if (function_exists('get_field')) {
            $address = get_field('location_address', $location_id);
        }
        if (empty($address)) {
            $address = get_post_meta($location_id, 'location_address', true);
        }
        
        // Get city (try ACF first, then meta)
        $city = '';
        if (function_exists('get_field')) {
            $city = get_field('location_city', $location_id);
        }
        if (empty($city)) {
            $city = get_post_meta($location_id, 'location_city', true);
        }
        
        $type_terms = get_the_terms($location_id, 'location_type');
        
        // Get external link (try ACF first, then meta)
        $external_url = '';
        if (function_exists('get_field')) {
            $external_url = get_field('location_website', $location_id);
        }
        if (empty($external_url)) {
            $external_url = get_post_meta($location_id, 'location_website', true);
        }
        $location_permalink = !empty($external_url) ? $external_url : get_permalink($location_id);
        $link_target = !empty($external_url) ? '_blank' : '_self';
        
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('location-card.php', $active_set);
            
            if ($template_path && file_exists($template_path)) {
                // Prepare location data for template
                $location = array(
                    'id' => $location_id,
                    'name' => $location_post->post_title,
                    'title' => $location_post->post_title,
                    'permalink' => $location_permalink,
                    'link_target' => $link_target,
                    'image' => get_the_post_thumbnail_url($location_id, 'large'),
                    'featured_image' => get_the_post_thumbnail_url($location_id, 'large'),
                    'address' => $address,
                    'city' => $city,
                    'type' => ($type_terms && !is_wp_error($type_terms)) ? $type_terms[0]->name : '',
                    'excerpt' => $location_post->post_excerpt,
                );
                
                $shortcode_atts = $atts;
                include $template_path;
                return;
            }
        }
        
        // Fallback: Default template
        ?>
        <div class="ensemble-location-card">
            
            <?php if ($show_image): ?>
            <div class="ensemble-location-image">
                <?php if (has_post_thumbnail($location_id)): ?>
                    <a href="<?php echo get_permalink($location_id); ?>">
                        <?php echo get_the_post_thumbnail($location_id, 'medium'); ?>
                    </a>
                <?php else: ?>
                    <div class="ensemble-location-placeholder">
                        <span class="dashicons dashicons-location"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-location-content">
                
                <h3 class="ensemble-location-title">
                    <a href="<?php echo get_permalink($location_id); ?>">
                        <?php echo esc_html($location_post->post_title); ?>
                    </a>
                </h3>
                
                <?php if ($type_terms && !is_wp_error($type_terms)): ?>
                <div class="ensemble-location-type">
                    <?php echo esc_html($type_terms[0]->name); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_address && ($address || $city)): ?>
                <div class="ensemble-location-address">
                    <span class="dashicons dashicons-location-alt"></span>
                    <?php if ($address): ?>
                        <span><?php echo esc_html($address); ?></span>
                    <?php endif; ?>
                    <?php if ($city): ?>
                        <span><?php echo esc_html($city); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_description && $location_post->post_excerpt): ?>
                <div class="ensemble-location-description">
                    <?php echo wpautop(wp_trim_words($location_post->post_excerpt, 20)); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_events): ?>
                    <?php $this->render_location_upcoming_events($location_id); ?>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <div class="ensemble-location-actions">
                    <a href="<?php echo get_permalink($location_id); ?>" 
                       class="ensemble-btn ensemble-btn-secondary">
                        <?php echo esc_html($link_text); ?>
                    </a>
                </div>
                <?php endif; ?>
                
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Render Location List Item
     */
    private function render_location_list_item($location_id, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_address = filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        
        $location = get_post($location_id);
        $address = get_post_meta($location_id, 'es_location_address', true);
        $city = get_post_meta($location_id, 'es_location_city', true);
        $type_terms = get_the_terms($location_id, 'location_type');
        ?>
        <div class="ensemble-location-list-item">
            
            <?php if ($show_image): ?>
            <div class="ensemble-location-thumb">
                <?php if (has_post_thumbnail($location_id)): ?>
                    <a href="<?php echo get_permalink($location_id); ?>">
                        <?php echo get_the_post_thumbnail($location_id, 'thumbnail'); ?>
                    </a>
                <?php else: ?>
                    <div class="ensemble-location-placeholder-small">
                        <span class="dashicons dashicons-location"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-location-list-content">
                
                <div class="ensemble-location-header">
                    <h4 class="ensemble-location-title">
                        <a href="<?php echo get_permalink($location_id); ?>">
                            <?php echo esc_html($location->post_title); ?>
                        </a>
                    </h4>
                    
                    <?php if ($type_terms && !is_wp_error($type_terms)): ?>
                    <span class="ensemble-location-type"><?php echo esc_html($type_terms[0]->name); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($show_address && ($address || $city)): ?>
                <div class="ensemble-location-address-compact">
                    <span class="dashicons dashicons-location-alt"></span>
                    <?php 
                    $addr_parts = array_filter(array($address, $city));
                    echo esc_html(implode(', ', $addr_parts));
                    ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_description && $location->post_excerpt): ?>
                <div class="ensemble-location-description">
                    <?php echo wpautop(wp_trim_words($location->post_excerpt, 30)); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_events): ?>
                    <?php $this->render_location_upcoming_events($location_id); ?>
                <?php endif; ?>
                
            </div>
            
            <?php if ($show_link): ?>
            <div class="ensemble-location-list-action">
                <a href="<?php echo get_permalink($location_id); ?>" 
                   class="ensemble-btn ensemble-btn-secondary">
                    <?php echo esc_html($link_text); ?>
                </a>
            </div>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render Location Card Item (larger cards with more details)
     */
    private function render_location_card_item($location_id, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_address = filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        
        $location = get_post($location_id);
        $address = get_post_meta($location_id, 'es_location_address', true);
        $city = get_post_meta($location_id, 'es_location_city', true);
        $type_terms = get_the_terms($location_id, 'location_type');
        $capacity = get_post_meta($location_id, 'es_location_capacity', true);
        ?>
        <div class="ensemble-location-card ensemble-location-card--large">
            <?php if ($show_image): ?>
            <div class="ensemble-location-image-large">
                <?php if (has_post_thumbnail($location_id)): ?>
                    <a href="<?php echo get_permalink($location_id); ?>">
                        <?php echo get_the_post_thumbnail($location_id, 'large'); ?>
                    </a>
                    <?php if ($type_terms && !is_wp_error($type_terms)): ?>
                    <span class="ensemble-location-type-badge"><?php echo esc_html($type_terms[0]->name); ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="ensemble-location-placeholder-large">
                        <span class="dashicons dashicons-location"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-location-card-content">
                <h3 class="ensemble-location-title">
                    <a href="<?php echo get_permalink($location_id); ?>"><?php echo esc_html($location->post_title); ?></a>
                </h3>
                
                <?php if ($show_address && ($address || $city)): ?>
                <div class="ensemble-location-address">
                    <span class="dashicons dashicons-location-alt"></span>
                    <span><?php echo esc_html(implode(', ', array_filter(array($address, $city)))); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($capacity): ?>
                <div class="ensemble-location-capacity">
                    <span class="dashicons dashicons-groups"></span>
                    <span><?php printf(__('Capacity: %s', 'ensemble'), esc_html($capacity)); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($show_description && $location->post_excerpt): ?>
                <div class="ensemble-location-description"><?php echo wp_trim_words($location->post_excerpt, 20, '...'); ?></div>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <a href="<?php echo get_permalink($location_id); ?>" class="ensemble-btn ensemble-btn-primary">
                    <?php echo esc_html($link_text); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
        // Query upcoming events for this location
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'es_event_location',
                    'value' => $location_id,
                    'compare' => '=',
                ),
                array(
                    'key' => 'es_event_start_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
            ),
            'meta_key' => 'es_event_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        );
        
        $events_query = new WP_Query($args);
        
        if (!$events_query->have_posts()) {
            return;
        }
        
        echo '<div class="ensemble-location-events">';
        echo '<h5>' . __('Upcoming Events', 'ensemble') . '</h5>';
        echo '<ul class="ensemble-events-mini-list">';
        
        while ($events_query->have_posts()) {
            $events_query->the_post();
            $event_id = get_the_ID();
            $start_date = $this->get_event_meta($event_id, 'start_date');
            
            echo '<li>';
            echo '<a href="' . get_permalink($event_id) . '">';
            echo '<span class="ensemble-event-date">' . date_i18n('M j', strtotime($start_date)) . '</span>';
            echo '<span class="ensemble-event-name">' . get_the_title() . '</span>';
            echo '</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
        
        wp_reset_postdata();
    }
    
    // =========================================
    // GALLERY SHORTCODE
    // =========================================
    
    /**
     * Gallery Shortcode
     * 
     * Displays a gallery with images
     * 
     * Usage: 
     * [ensemble_gallery id="123"]
     * [ensemble_gallery id="123" columns="4" layout="masonry"]
     * [ensemble_gallery event="456"]
     * [ensemble_gallery artist="789"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function single_location_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 0,                      // Location Post ID (required)
            'layout' => 'card',             // card, compact, full
            'show_image' => 'true',         // Show featured image
            'show_address' => 'true',       // Show address
            'show_description' => 'true',   // Show description
            'show_events' => 'true',        // Show upcoming events
            'show_link' => 'true',          // Show "View Location" link
            'link_text' => 'View Location', // Link button text
            'template' => '',               // Template name
        ), $atts, 'ensemble_location');
        
        // Sanitize
        $location_id = absint($atts['id']);
        $layout = sanitize_key($atts['layout']);
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_address = filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        $template = $this->get_effective_template($atts['template']);
        
        // Apply template if specified
        $this->apply_shortcode_template($template);
        
        // Validate location ID
        if (!$location_id) {
            return '<div class="ensemble-error">Location ID is required</div>';
        }
        
        // Get location post
        $location = get_post($location_id);
        
        if (!$location || $location->post_type !== 'ensemble_location') {
            return '<div class="ensemble-error">Location not found</div>';
        }
        
        // Get location meta
        $location_data = array(
            'address' => get_post_meta($location_id, 'es_location_address', true),
            'city' => get_post_meta($location_id, 'es_location_city', true),
            'zip' => get_post_meta($location_id, 'es_location_zip', true),
            'country' => get_post_meta($location_id, 'es_location_country', true),
            'phone' => get_post_meta($location_id, 'es_location_phone', true),
            'email' => get_post_meta($location_id, 'es_location_email', true),
            'website' => get_post_meta($location_id, 'es_location_website', true),
        );
        
        // Build HTML based on layout
        ob_start();
        
        switch ($layout) {
            case 'compact':
                $this->render_single_location_compact($location, $location_data, $atts);
                break;
                
            case 'full':
                $this->render_single_location_full($location, $location_data, $atts);
                break;
                
            case 'card':
            default:
                $this->render_single_location_card($location, $location_data, $atts);
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render Single Location Card Layout
     */
    private function render_single_location_card($location_post, $data, $atts) {
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('location-card.php', $active_set);
            
            if ($template_path && file_exists($template_path)) {
                // Convert shortcode data to template format
                $location = array(
                    'id' => $location_post->ID,
                    'title' => $location_post->post_title,
                    'permalink' => get_permalink($location_post->ID),
                    'featured_image' => get_the_post_thumbnail_url($location_post->ID, 'large'),
                    'address' => $data['address'] ?? '',
                    'city' => $data['city'] ?? '',
                    'zip' => $data['zip'] ?? '',
                    'country' => $data['country'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'website' => $data['website'] ?? '',
                    'capacity' => $data['capacity'] ?? '',
                    'excerpt' => $location_post->post_excerpt,
                    'event_count' => 0, // TODO: Calculate if needed
                );
                
                // Also pass shortcode attributes for template flexibility
                $shortcode_atts = $atts;
                
                // Load the template
                include $template_path;
                return;
            }
        }
        
        // Fallback: Hardcoded layout (old system)
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_address = filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        
        $type_terms = get_the_terms($location_post->ID, 'location_type');
        ?>
        <div class="ensemble-single-location ensemble-layout-card">
            
            <?php if ($show_image): ?>
            <div class="ensemble-location-image">
                <?php if (has_post_thumbnail($location_post->ID)): ?>
                    <?php echo get_the_post_thumbnail($location_post->ID, 'large'); ?>
                <?php else: ?>
                    <div class="ensemble-location-placeholder">
                        <span class="dashicons dashicons-location"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-location-content">
                
                <h3 class="ensemble-location-title">
                    <?php echo esc_html($location_post->post_title); ?>
                </h3>
                
                <?php if ($type_terms && !is_wp_error($type_terms)): ?>
                <div class="ensemble-location-type">
                    <?php echo esc_html($type_terms[0]->name); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_address && ($data['address'] || $data['city'])): ?>
                <div class="ensemble-location-info">
                    <div class="ensemble-info-item">
                        <span class="dashicons dashicons-location-alt"></span>
                        <div>
                            <?php if ($data['address']): ?>
                                <div><?php echo esc_html($data['address']); ?></div>
                            <?php endif; ?>
                            <?php if ($data['city'] || $data['zip']): ?>
                                <div>
                                    <?php echo esc_html($data['zip'] . ' ' . $data['city']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($data['country']): ?>
                                <div><?php echo esc_html($data['country']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($data['phone']): ?>
                    <div class="ensemble-info-item">
                        <span class="dashicons dashicons-phone"></span>
                        <a href="tel:<?php echo esc_attr($data['phone']); ?>">
                            <?php echo esc_html($data['phone']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($data['website']): ?>
                    <div class="ensemble-info-item">
                        <span class="dashicons dashicons-admin-site"></span>
                        <a href="<?php echo esc_url($data['website']); ?>" target="_blank" rel="noopener">
                            <?php _e('Visit Website', 'ensemble'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_description && $location_post->post_excerpt): ?>
                <div class="ensemble-location-description">
                    <?php echo wpautop($location_post->post_excerpt); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_events): ?>
                    <?php $this->render_location_upcoming_events($location->ID); ?>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <div class="ensemble-location-actions">
                    <a href="<?php echo get_permalink($location->ID); ?>" 
                       class="ensemble-btn ensemble-btn-primary">
                        <?php echo esc_html($atts['link_text']); ?>
                    </a>
                </div>
                <?php endif; ?>
                
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Render Single Location Compact Layout
     */
    private function render_single_location_compact($location, $data, $atts) {
        $show_address = filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        ?>
        <div class="ensemble-single-location ensemble-layout-compact">
            
            <div class="ensemble-compact-header">
                <h4 class="ensemble-location-title">
                    <?php echo esc_html($location->post_title); ?>
                </h4>
                
                <?php if ($show_address && ($data['address'] || $data['city'])): ?>
                <div class="ensemble-compact-address">
                    <span class="dashicons dashicons-location-alt"></span>
                    <?php 
                    $addr_parts = array_filter(array($data['address'], $data['city']));
                    echo esc_html(implode(', ', $addr_parts));
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($show_link): ?>
            <a href="<?php echo get_permalink($location->ID); ?>" class="ensemble-compact-link">
                <?php echo esc_html($atts['link_text']); ?> →
            </a>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render Single Location Full Layout
     */
    private function render_single_location_full($location, $data, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_address = filter_var($atts['show_address'], FILTER_VALIDATE_BOOLEAN);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        
        $type_terms = get_the_terms($location->ID, 'location_type');
        ?>
        <div class="ensemble-single-location ensemble-layout-full">
            
            <?php if ($show_image && has_post_thumbnail($location->ID)): ?>
            <div class="ensemble-location-header-image">
                <?php echo get_the_post_thumbnail($location->ID, 'full'); ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-location-full-content">
                
                <h2 class="ensemble-location-title">
                    <?php echo esc_html($location->post_title); ?>
                </h2>
                
                <?php if ($type_terms && !is_wp_error($type_terms)): ?>
                <div class="ensemble-location-type-large">
                    <?php echo esc_html($type_terms[0]->name); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_address): ?>
                <div class="ensemble-location-info-grid">
                    
                    <?php if ($data['address'] || $data['city']): ?>
                    <div class="ensemble-info-box">
                        <div class="ensemble-info-icon">
                            <span class="dashicons dashicons-location-alt"></span>
                        </div>
                        <div class="ensemble-info-content">
                            <label><?php _e('Address', 'ensemble'); ?></label>
                            <div>
                                <?php if ($data['address']): ?>
                                    <div><?php echo esc_html($data['address']); ?></div>
                                <?php endif; ?>
                                <?php if ($data['zip'] || $data['city']): ?>
                                    <div><?php echo esc_html(trim($data['zip'] . ' ' . $data['city'])); ?></div>
                                <?php endif; ?>
                                <?php if ($data['country']): ?>
                                    <div><?php echo esc_html($data['country']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($data['phone']): ?>
                    <div class="ensemble-info-box">
                        <div class="ensemble-info-icon">
                            <span class="dashicons dashicons-phone"></span>
                        </div>
                        <div class="ensemble-info-content">
                            <label><?php _e('Phone', 'ensemble'); ?></label>
                            <strong><a href="tel:<?php echo esc_attr($data['phone']); ?>"><?php echo esc_html($data['phone']); ?></a></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($data['email']): ?>
                    <div class="ensemble-info-box">
                        <div class="ensemble-info-icon">
                            <span class="dashicons dashicons-email"></span>
                        </div>
                        <div class="ensemble-info-content">
                            <label><?php _e('Email', 'ensemble'); ?></label>
                            <strong><a href="mailto:<?php echo esc_attr($data['email']); ?>"><?php echo esc_html($data['email']); ?></a></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($data['website']): ?>
                    <div class="ensemble-info-box">
                        <div class="ensemble-info-icon">
                            <span class="dashicons dashicons-admin-site"></span>
                        </div>
                        <div class="ensemble-info-content">
                            <label><?php _e('Website', 'ensemble'); ?></label>
                            <strong><a href="<?php echo esc_url($data['website']); ?>" target="_blank" rel="noopener"><?php _e('Visit Website', 'ensemble'); ?></a></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                <?php endif; ?>
                
                <?php if ($show_description): ?>
                <div class="ensemble-location-description-full">
                    <?php echo wpautop($location->post_content); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_events): ?>
                <div class="ensemble-location-events-full">
                    <?php $this->render_location_upcoming_events($location->ID); ?>
                </div>
                <?php endif; ?>
                
            </div>
            
        </div>
        <?php
    }
    
}
