<?php
/**
 * Ensemble Event Shortcodes
 *
 * Handles all event-related shortcodes including single events, event grids,
 * upcoming events, lineup, featured events, and preview events.
 *
 * @package Ensemble
 * @subpackage Shortcodes
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event Shortcodes class.
 *
 * @since 3.0.0
 */
class ES_Event_Shortcodes extends ES_Shortcode_Base {

	/**
	 * Register event-related shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'ensemble_event', array( $this, 'single_event_shortcode' ) );
		add_shortcode( 'ensemble_upcoming_events', array( $this, 'upcoming_events_shortcode' ) );
		add_shortcode( 'ensemble_lineup', array( $this, 'lineup_shortcode' ) );
		add_shortcode( 'ensemble_featured_events', array( $this, 'featured_events_shortcode' ) );
		add_shortcode( 'ensemble_events_grid', array( $this, 'events_grid_shortcode' ) );
		add_shortcode( 'ensemble_events', array( $this, 'events_grid_shortcode' ) ); // Alias
		add_shortcode( 'ensemble_preview_events', array( $this, 'preview_events_shortcode' ) );
	}

    /**
     * Get event meta data with fallback support for different meta key formats
     * 
     * @param int $event_id Event post ID
     * @param string $field Field name (e.g. 'start_date', 'location', 'artist')
     * @return mixed Meta value or empty string
     */
    protected function get_event_meta($event_id, $field) {
        // Use centralized helper function if available
        if (function_exists('ensemble_get_event_meta')) {
            return ensemble_get_event_meta($event_id, $field);
        }
        
        // Try multiple meta key formats
        $possible_keys = array(
            'es_event_' . $field,      // New format: es_event_start_date
            'event_' . $field,          // Standard: event_start_date
            '_event_' . $field,         // Private: _event_start_date
            '_es_event_' . $field,      // Private new: _es_event_start_date
        );
        
        foreach ($possible_keys as $key) {
            $value = get_post_meta($event_id, $key, true);
            if (!empty($value)) {
                return $value;
            }
        }
        
        // Special handling for certain fields
        if ($field === 'start_date' || $field === 'date') {
            // Try ACF field
            if (function_exists('get_field')) {
                $value = get_field('event_date', $event_id);
                if ($value) return $value;
            }
        }
        
        if ($field === 'location') {
            // Try ACF relationship field
            if (function_exists('get_field')) {
                $value = get_field('event_location', $event_id);
                if ($value) {
                    return is_array($value) ? $value[0] : $value;
                }
            }
        }
        
        if ($field === 'artist') {
            // Try ACF relationship field
            if (function_exists('get_field')) {
                $value = get_field('event_artist', $event_id);
                if ($value) {
                    return is_array($value) ? $value[0] : $value;
                }
            }
        }
        
        return '';
    }
    
    /**
     * Get all event data as array
     * Uses the same methods as the templates for consistency
     * 
     * @param int $event_id Event post ID
     * @return array Event data array
     */
    protected function get_event_data($event_id) {
        // Get event status
        $event_status = get_post_meta($event_id, '_event_status', true);
        if (empty($event_status)) {
            $event_status = get_post_status($event_id) === 'draft' ? 'draft' : 'publish';
        }
        
        // Use ensemble_get_field if available (same as templates use)
        if (function_exists('ensemble_get_field')) {
            $start_date = ensemble_get_field('event_date', $event_id);
            if (!$start_date) {
                $start_date = ensemble_get_field('event_start_date', $event_id);
            }
            
            $start_time = ensemble_get_field('event_time', $event_id);
            if (!$start_time) {
                $start_time = ensemble_get_field('event_start_time', $event_id);
            }
            
            $end_date = ensemble_get_field('event_end_date', $event_id);
            $end_time = ensemble_get_field('event_end_time', $event_id);
            
            $location_id = ensemble_get_field('event_location', $event_id);
            $artist_id = ensemble_get_field('event_artist', $event_id);
            $price = ensemble_get_field('event_price', $event_id);
            $ticket_url = ensemble_get_field('event_ticket_url', $event_id);
            
            return array(
                'start_date'  => $start_date,
                'start_time'  => $start_time,
                'end_date'    => $end_date,
                'end_time'    => $end_time,
                'location_id' => $location_id,
                'artist_id'   => $artist_id,
                'price'       => $price,
                'ticket_url'  => $ticket_url,
                'status'      => $event_status,
            );
        }
        
        // Fallback to get_event_meta
        return array(
            'start_date'  => $this->get_event_meta($event_id, 'start_date'),
            'start_time'  => $this->get_event_meta($event_id, 'start_time'),
            'end_date'    => $this->get_event_meta($event_id, 'end_date'),
            'end_time'    => $this->get_event_meta($event_id, 'end_time'),
            'location_id' => $this->get_event_meta($event_id, 'location'),
            'artist_id'   => $this->get_event_meta($event_id, 'artist'),
            'price'       => $this->get_event_meta($event_id, 'price'),
            'ticket_url'  => $this->get_event_meta($event_id, 'ticket_url'),
            'status'      => $event_status,
        );
    }
    
    /**
     * Get the correct meta key for a field (for use in WP_Query)
     * 
     * @param string $field Field name (e.g. 'location', 'artist')
     * @return string The meta key to use in queries
     */
    protected function get_meta_key_for_field($field) {
        // Use centralized meta key management if available
        if (class_exists('ES_Meta_Keys')) {
            return ES_Meta_Keys::get($field);
        }
        
        // Try to detect which meta key format is used
        global $wpdb;
        
        $possible_keys = array(
            'es_event_' . $field,
            'event_' . $field,
            '_event_' . $field,
        );
        
        foreach ($possible_keys as $key) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1",
                $key
            ));
            if ($exists) {
                return $key;
            }
        }
        
        // Fallback
        return 'event_' . $field;
    }
    
    /**
     * Get the date meta key used in the database
     * 
     * @return string Date meta key
     */
    protected function get_date_meta_key() {
        // Check cache first
        $cached = get_transient('ensemble_date_meta_key');
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        
        $possible_keys = array(
            'es_event_start_date',
            'event_date',
            'event_start_date',
            '_event_date',
            '_event_start_date',
        );
        
        foreach ($possible_keys as $key) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1",
                $key
            ));
            if ($exists) {
                set_transient('ensemble_date_meta_key', $key, HOUR_IN_SECONDS);
                return $key;
            }
        }
        
        // Fallback
        return 'event_date';
    }
    
    /**
     * Format date for display
     * 
     * @param string $start_date Start date
     * @param string $end_date End date (optional)
     * @return string Formatted date string
     */
    protected function format_date($start_date, $end_date = '') {
        if (empty($start_date)) {
            return '';
        }
        
        $start_formatted = date_i18n(get_option('date_format'), strtotime($start_date));
        
        if (!empty($end_date) && $end_date !== $start_date) {
            $end_formatted = date_i18n(get_option('date_format'), strtotime($end_date));
            return $start_formatted . ' – ' . $end_formatted;
        }
        
        return $start_formatted;
    }
    
    /**
     * Format date short version
     * 
     * @param string $date Date string
     * @return string Short formatted date
     */
    protected function format_date_short($date) {
        if (empty($date)) {
            return '';
        }
        return date_i18n('j. M', strtotime($date));
    }
    
    /**
     * Format time for display
     * 
     * @param string $start_time Start time
     * @param string $end_time End time (optional)
     * @return string Formatted time string
     */
    protected function format_time($start_time, $end_time = '') {
        if (empty($start_time)) {
            return '';
        }
        
        if (!empty($end_time) && $end_time !== $start_time) {
            return esc_html($start_time) . ' – ' . esc_html($end_time);
        }
        
        return esc_html($start_time);
    }
    
    /**
     * Apply template if specified in shortcode
     * 
     * @param string $template Template name from shortcode attribute
     * @return void
     */
    protected function apply_shortcode_template($template) {
        // Check if parent has this method
        if (method_exists(get_parent_class($this), 'apply_shortcode_template')) {
            return parent::apply_shortcode_template($template);
        }
        
        if (empty($template) || !class_exists('ES_Design_Settings')) {
            return;
        }
        
        $current_template = ES_Design_Settings::get_active_template();
        
        // Only load if different from current
        if ($template !== $current_template) {
            ES_Design_Settings::load_template($template);
            
            // Regenerate CSS inline for this page
            if (class_exists('ES_CSS_Generator')) {
                $custom_css = ES_CSS_Generator::generate();
                echo '<style id="ensemble-template-' . esc_attr($template) . '">' . $custom_css . '</style>';
            }
        }
    }
    
    /**
     * Get effective template from shortcode attribute or URL parameter
     * 
     * @param string $shortcode_template Template from shortcode attribute
     * @return string Effective template to use
     */
    protected function get_effective_template($shortcode_template = '') {
        // Check if parent has this method
        if (method_exists(get_parent_class($this), 'get_effective_template')) {
            return parent::get_effective_template($shortcode_template);
        }
        
        // If explicit template in shortcode, use it
        if (!empty($shortcode_template)) {
            return sanitize_key($shortcode_template);
        }
        
        // Check URL parameter
        if (isset($_GET['es_layout']) && !empty($_GET['es_layout'])) {
            return sanitize_key($_GET['es_layout']);
        }
        
        return '';
    }

    public function single_event_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('events');
        }
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 0,                      // Event Post ID (required)
            'layout' => 'card',             // card, compact, full
            'template' => '',               // Design template to apply
            'show_image' => 'true',         // Show featured image
            'show_date' => 'true',          // Show date
            'show_time' => 'true',          // Show time
            'show_location' => 'true',      // Show location
            'show_artist' => 'true',        // Show artist/performer
            'show_excerpt' => 'true',       // Show excerpt
            'show_link' => 'true',          // Show "View Event" link
            'link_text' => 'View Event',    // Link button text
        ), $atts, 'ensemble_event');
        
        // Sanitize
        $event_id = absint($atts['id']);
        $layout = sanitize_key($atts['layout']);
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        $show_time = filter_var($atts['show_time'], FILTER_VALIDATE_BOOLEAN);
        $show_location = filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN);
        $show_artist = filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN);
        $show_excerpt = filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        $template = $this->get_effective_template($atts['template']);
        
        // Apply template if specified
        $this->apply_shortcode_template($template);
        
        // Validate event ID
        if (!$event_id) {
            return '<div class="ensemble-error">Event ID is required</div>';
        }
        
        // Get event post
        $event = get_post($event_id);
        
        if (!$event || $event->post_type !== ensemble_get_post_type()) {
            return '<div class="ensemble-error">Event not found</div>';
        }
        
        // Get event meta
        $event_data = $this->get_event_data($event_id);
        
        // DEBUG OUTPUT - Shows in frontend when WP_DEBUG is true
        // Remove or comment out after debugging
        if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['ensemble_debug'])) {
            echo '<div style="background:#ffe0e0;padding:10px;margin:10px 0;font-size:12px;font-family:monospace;">';
            echo '<strong>Ensemble Single Event Debug:</strong><br>';
            echo 'Event ID: ' . esc_html($event_id) . '<br>';
            echo 'Post Type: ' . esc_html($event->post_type) . '<br>';
            echo 'Title: ' . esc_html($event->post_title) . '<br>';
            echo 'ensemble_get_field exists: ' . (function_exists('ensemble_get_field') ? 'YES' : 'NO') . '<br>';
            echo 'ES_Layout_Sets exists: ' . (class_exists('ES_Layout_Sets') ? 'YES' : 'NO') . '<br>';
            if (class_exists('ES_Layout_Sets')) {
                echo 'Active Layout Set: ' . esc_html(ES_Layout_Sets::get_active_set()) . '<br>';
            }
            echo '<strong>Event Data:</strong><pre>' . esc_html(print_r($event_data, true)) . '</pre>';
            echo '</div>';
        }
        
        // Build HTML based on layout
        ob_start();
        
        switch ($layout) {
            case 'compact':
                $this->render_compact_layout($event, $event_data, $atts);
                break;
                
            case 'full':
                $this->render_full_layout($event, $event_data, $atts);
                break;
                
            case 'card':
            default:
                $this->render_card_layout($event, $event_data, $atts);
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render Card Layout
     */
    private function render_card_layout($event, $data, $atts) {
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('event-card.php', $active_set);
            
            // DEBUG: Uncomment to see template loading
            // error_log('Ensemble - Active Set: ' . $active_set);
            // error_log('Ensemble - Template Path: ' . ($template_path ?: 'NOT FOUND'));
            
            if ($template_path && file_exists($template_path)) {
                // WICHTIG: $event_id setzen für das Template!
                $event_id = $event->ID;
                
                // WICHTIG: Setup post data für ACF und andere Funktionen die globale Post-Daten erwarten
                global $post;
                $original_post = $post;
                $post = $event;
                setup_postdata($event);
                
                // Shortcode Attribute als Booleans konvertieren für Template
                $shortcode_atts = array(
                    'show_image'    => filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN),
                    'show_date'     => filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN),
                    'show_time'     => filter_var($atts['show_time'], FILTER_VALIDATE_BOOLEAN),
                    'show_location' => filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN),
                    'show_artist'   => filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN),
                    'show_excerpt'  => filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN),
                    'show_link'     => filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN),
                    'show_category' => true,
                    'show_price'    => true,
                    'link_text'     => $atts['link_text'] ?? __('View Event', 'ensemble'),
                    'style'         => 'default',
                );
                
                // Load the template
                include $template_path;
                
                // Reset post data
                $post = $original_post;
                wp_reset_postdata();
                return;
            }
        }
        
        // Fallback: Hardcoded layout (old system)
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        $show_time = filter_var($atts['show_time'], FILTER_VALIDATE_BOOLEAN);
        $show_location = filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN);
        $show_artist = filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN);
        $show_excerpt = filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        ?>
        <div class="ensemble-single-event ensemble-layout-card">
            
            <?php if ($show_image && has_post_thumbnail($event->ID)): ?>
            <div class="ensemble-event-image">
                <?php echo get_the_post_thumbnail($event->ID, 'large'); ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-event-content">
                
                <h3 class="ensemble-event-title">
                    <?php echo esc_html($event->post_title); ?>
                </h3>
                
                <div class="ensemble-event-meta">
                    
                    <?php if ($show_date && !empty($data['start_date'])): ?>
                    <div class="ensemble-meta-item ensemble-date">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php echo $this->format_date($data['start_date'], $data['end_date'] ?? ''); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_time && !empty($data['start_time'])): ?>
                    <div class="ensemble-meta-item ensemble-time">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php echo $this->format_time($data['start_time'], $data['end_time'] ?? ''); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_location && !empty($data['location_id'])): 
                        $location = get_post($data['location_id']);
                        if ($location):
                    ?>
                    <div class="ensemble-meta-item ensemble-location">
                        <span class="dashicons dashicons-location"></span>
                        <span><?php echo esc_html($location->post_title); ?></span>
                    </div>
                    <?php 
                        endif;
                    endif; 
                    ?>
                    
                    <?php if ($show_artist && !empty($data['artist_id'])): 
                        $artist = get_post($data['artist_id']);
                        if ($artist):
                    ?>
                    <div class="ensemble-meta-item ensemble-artist">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span><?php echo esc_html($artist->post_title); ?></span>
                    </div>
                    <?php 
                        endif;
                    endif; 
                    ?>
                    
                    <?php if (!empty($data['price'])): ?>
                    <div class="ensemble-meta-item ensemble-price">
                        <span class="dashicons dashicons-tickets-alt"></span>
                        <span><?php echo esc_html($data['price']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php if ($show_excerpt && !empty($event->post_excerpt)): ?>
                <div class="ensemble-event-excerpt">
                    <?php echo wpautop(esc_html($event->post_excerpt)); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <div class="ensemble-event-actions">
                    <?php if (!empty($data['ticket_url'])): ?>
                    <a href="<?php echo esc_url($data['ticket_url']); ?>" 
                       class="ensemble-btn ensemble-btn-tickets" 
                       target="_blank" 
                       rel="noopener">
                        <?php _e('Get Tickets', 'ensemble'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo get_permalink($event->ID); ?>" 
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
     * Render Compact Layout
     */
    private function render_compact_layout($event, $data, $atts) {
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('event-card.php', $active_set);
            
            if ($template_path && file_exists($template_path)) {
                // WICHTIG: $event_id setzen für das Template!
                $event_id = $event->ID;
                
                // WICHTIG: Setup post data für ACF und andere Funktionen
                global $post;
                $original_post = $post;
                $post = $event;
                setup_postdata($event);
                
                // Shortcode Attribute als Booleans + compact style
                $shortcode_atts = array(
                    'show_image'    => filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN),
                    'show_date'     => filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN),
                    'show_time'     => filter_var($atts['show_time'], FILTER_VALIDATE_BOOLEAN),
                    'show_location' => filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN),
                    'show_artist'   => filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN),
                    'show_excerpt'  => false,
                    'show_link'     => filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN),
                    'show_category' => false,
                    'show_price'    => false,
                    'link_text'     => $atts['link_text'] ?? __('View Event', 'ensemble'),
                    'style'         => 'compact',
                );
                
                // Load the template
                include $template_path;
                
                // Reset post data
                $post = $original_post;
                wp_reset_postdata();
                return;
            }
        }
        
        // Fallback: Hardcoded layout
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        $show_time = filter_var($atts['show_time'], FILTER_VALIDATE_BOOLEAN);
        $show_location = filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        ?>
        <div class="ensemble-single-event ensemble-layout-compact">
            
            <div class="ensemble-compact-header">
                <h4 class="ensemble-event-title">
                    <?php echo esc_html($event->post_title); ?>
                </h4>
                
                <div class="ensemble-compact-meta">
                    <?php if ($show_date && !empty($data['start_date'])): ?>
                        <span class="ensemble-date"><?php echo $this->format_date_short($data['start_date']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($show_time && !empty($data['start_time'])): ?>
                        <span class="ensemble-time"><?php echo esc_html($data['start_time']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($show_location && !empty($data['location_id'])): 
                        $location = get_post($data['location_id']);
                        if ($location):
                    ?>
                        <span class="ensemble-location"><?php echo esc_html($location->post_title); ?></span>
                    <?php 
                        endif;
                    endif; 
                    ?>
                </div>
            </div>
            
            <?php if ($show_link): ?>
            <a href="<?php echo get_permalink($event->ID); ?>" class="ensemble-compact-link">
                <?php echo esc_html($atts['link_text']); ?> →
            </a>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render Full Layout
     */
    private function render_full_layout($event, $data, $atts) {
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('event-card.php', $active_set);
            
            if ($template_path && file_exists($template_path)) {
                // WICHTIG: $event_id setzen für das Template!
                $event_id = $event->ID;
                
                // WICHTIG: Setup post data für ACF und andere Funktionen
                global $post;
                $original_post = $post;
                $post = $event;
                setup_postdata($event);
                
                // Shortcode Attribute als Booleans + featured style
                $shortcode_atts = array(
                    'show_image'    => filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN),
                    'show_date'     => filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN),
                    'show_time'     => filter_var($atts['show_time'], FILTER_VALIDATE_BOOLEAN),
                    'show_location' => filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN),
                    'show_artist'   => filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN),
                    'show_excerpt'  => filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN),
                    'show_link'     => filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN),
                    'show_category' => true,
                    'show_price'    => true,
                    'link_text'     => $atts['link_text'] ?? __('View Event', 'ensemble'),
                    'style'         => 'featured',
                );
                
                // Load the template
                include $template_path;
                
                // Reset post data
                $post = $original_post;
                wp_reset_postdata();
                return;
            }
        }
        
        // Fallback: Hardcoded layout
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        $show_time = filter_var($atts['show_time'], FILTER_VALIDATE_BOOLEAN);
        $show_location = filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN);
        $show_artist = filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        ?>
        <div class="ensemble-single-event ensemble-layout-full">
            
            <?php if ($show_image && has_post_thumbnail($event->ID)): ?>
            <div class="ensemble-event-header-image">
                <?php echo get_the_post_thumbnail($event->ID, 'full'); ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-event-full-content">
                
                <h2 class="ensemble-event-title">
                    <?php echo esc_html($event->post_title); ?>
                </h2>
                
                <div class="ensemble-event-meta-grid">
                    
                    <?php if ($show_date && !empty($data['start_date'])): ?>
                    <div class="ensemble-meta-box">
                        <div class="ensemble-meta-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="ensemble-meta-content">
                            <label><?php _e('Date', 'ensemble'); ?></label>
                            <strong><?php echo $this->format_date($data['start_date'], $data['end_date'] ?? ''); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_time && !empty($data['start_time'])): ?>
                    <div class="ensemble-meta-box">
                        <div class="ensemble-meta-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="ensemble-meta-content">
                            <label><?php _e('Time', 'ensemble'); ?></label>
                            <strong><?php echo $this->format_time($data['start_time'], $data['end_time'] ?? ''); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_location && !empty($data['location_id'])): 
                        $location = get_post($data['location_id']);
                        if ($location):
                    ?>
                    <div class="ensemble-meta-box">
                        <div class="ensemble-meta-icon">
                            <span class="dashicons dashicons-location"></span>
                        </div>
                        <div class="ensemble-meta-content">
                            <label><?php _e('Location', 'ensemble'); ?></label>
                            <strong><?php echo esc_html($location->post_title); ?></strong>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endif; 
                    ?>
                    
                    <?php if ($show_artist && !empty($data['artist_id'])): 
                        $artist = get_post($data['artist_id']);
                        if ($artist):
                    ?>
                    <div class="ensemble-meta-box">
                        <div class="ensemble-meta-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                        <div class="ensemble-meta-content">
                            <label><?php _e('Artist', 'ensemble'); ?></label>
                            <strong><?php echo esc_html($artist->post_title); ?></strong>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endif; 
                    ?>
                    
                    <?php if (!empty($data['price'])): ?>
                    <div class="ensemble-meta-box">
                        <div class="ensemble-meta-icon">
                            <span class="dashicons dashicons-tickets-alt"></span>
                        </div>
                        <div class="ensemble-meta-content">
                            <label><?php _e('Price', 'ensemble'); ?></label>
                            <strong><?php echo esc_html($data['price']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <div class="ensemble-event-description">
                    <?php echo wpautop($event->post_content); ?>
                </div>
                
                <?php if ($show_link): ?>
                <div class="ensemble-event-actions-full">
                    <?php if (!empty($data['ticket_url'])): ?>
                    <a href="<?php echo esc_url($data['ticket_url']); ?>" 
                       class="ensemble-btn ensemble-btn-tickets ensemble-btn-large" 
                       target="_blank" 
                       rel="noopener">
                        <span class="dashicons dashicons-tickets-alt"></span>
                        <?php _e('Get Tickets', 'ensemble'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Format date range
     */
    public function upcoming_events_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('events');
        }
        
        $atts = shortcode_atts(array(
            'limit' => '5',
            'show_countdown' => 'false',
            'show_image' => 'true',
            'show_location' => 'true',
            'show_artist' => 'true',
        ), $atts, 'ensemble_upcoming_events');
        
        $limit = absint($atts['limit']);
        $show_countdown = filter_var($atts['show_countdown'], FILTER_VALIDATE_BOOLEAN);
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_location = filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN);
        $show_artist = filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN);
        
        // Query upcoming events
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // Get the correct date meta key (considers Field Mapping)
        $date_key = $this->get_date_meta_key();
        
        // Check if events with meta exist
        $test_query = new WP_Query(array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => 1,
            'meta_key' => $date_key,
        ));
        
        if ($test_query->have_posts()) {
            // Use meta-based filtering
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key' => $date_key,
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
                // Exclude preview/cancelled/postponed events - only show published
                array(
                    'relation' => 'OR',
                    array(
                        'key' => '_event_status',
                        'value' => array('preview', 'cancelled', 'postponed'),
                        'compare' => 'NOT IN',
                    ),
                    array(
                        'key' => '_event_status',
                        'value' => 'publish',
                        'compare' => '=',
                    ),
                    array(
                        'key' => '_event_status',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            );
            $args['meta_key'] = $date_key;
            $args['orderby'] = 'meta_value';
            $args['order'] = 'ASC';
        }
        
        wp_reset_postdata();
        $events = new WP_Query($args);
        
        if (!$events->have_posts()) {
            return '<div class="ensemble-no-upcoming">' . __('No upcoming events.', 'ensemble') . '</div>';
        }
        
        ob_start();
        ?>
        
        <div class="ensemble-upcoming-events">
            <?php while ($events->have_posts()): $events->the_post(); 
                $event_id = get_the_ID();
                $start_date = $this->get_event_meta($event_id, 'start_date');
                $start_time = $this->get_event_meta($event_id, 'start_time');
                $location_id = $this->get_event_meta($event_id, 'location');
                $artist_id = $this->get_event_meta($event_id, 'artist');
                
                $location = $location_id ? get_post($location_id) : null;
                $artist = $artist_id ? get_post($artist_id) : null;
            ?>
            
            <div class="ensemble-upcoming-item">
                
                <?php if ($show_image && has_post_thumbnail()): ?>
                <div class="ensemble-upcoming-thumb">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('thumbnail'); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="ensemble-upcoming-content">
                    
                    <div class="ensemble-upcoming-date">
                        <?php if ($start_date): ?>
                            <span class="ensemble-date-badge">
                                <span class="date-day"><?php echo date_i18n('j', strtotime($start_date)); ?></span>
                                <span class="date-month"><?php echo date_i18n('M', strtotime($start_date)); ?></span>
                            </span>
                            
                            <?php if ($show_countdown && $start_date): 
                                $diff = strtotime($start_date) - time();
                                $days = floor($diff / (60 * 60 * 24));
                                if ($days >= 0):
                            ?>
                            <span class="ensemble-countdown">
                                <?php 
                                if ($days == 0) {
                                    echo __('Today!', 'ensemble');
                                } elseif ($days == 1) {
                                    echo __('Tomorrow!', 'ensemble');
                                } else {
                                    printf(__('in %d days', 'ensemble'), $days);
                                }
                                ?>
                            </span>
                            <?php endif; endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="ensemble-upcoming-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h4>
                    
                    <div class="ensemble-upcoming-meta">
                        <?php if ($start_time): ?>
                            <span class="meta-time">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($start_time); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($show_location && $location): ?>
                            <span class="meta-location">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($location->post_title); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($show_artist && $artist): ?>
                            <span class="meta-artist">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo esc_html($artist->post_title); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                </div>
                
            </div>
            
            <?php endwhile; ?>
        </div>
        
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Event Lineup Shortcode
     * Shows all artists/performers for a specific event
     * 
     * Usage: [ensemble_lineup event_id="123" show_times="true"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function lineup_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('lineup');
        }
        
        $atts = shortcode_atts(array(
            'event_id' => 0,
            'show_times' => 'false',
            'show_genre' => 'true',
            'show_bio' => 'false',
            'layout' => 'list', // list, grid
        ), $atts, 'ensemble_lineup');
        
        $event_id = absint($atts['event_id']);
        $show_times = filter_var($atts['show_times'], FILTER_VALIDATE_BOOLEAN);
        $show_genre = filter_var($atts['show_genre'], FILTER_VALIDATE_BOOLEAN);
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $layout = sanitize_key($atts['layout']);
        
        if (!$event_id) {
            return '<div class="ensemble-error">Event ID is required</div>';
        }
        
        // Get event
        $event = get_post($event_id);
        if (!$event || $event->post_type !== ensemble_get_post_type()) {
            return '<div class="ensemble-error">Event not found</div>';
        }
        
        // Get lineup data - assuming you have a meta field for multiple artists
        // For now, we'll use the single artist field as example
        $artist_id = $this->get_event_meta($event_id, 'artist');
        
        if (!$artist_id) {
            return '<div class="ensemble-no-lineup">' . __('No lineup information available.', 'ensemble') . '</div>';
        }
        
        $artist = get_post($artist_id);
        if (!$artist) {
            return '<div class="ensemble-no-lineup">' . __('No lineup information available.', 'ensemble') . '</div>';
        }
        
        ob_start();
        ?>
        
        <div class="ensemble-lineup ensemble-layout-<?php echo esc_attr($layout); ?>">
            
            <div class="ensemble-lineup-header">
                <h3><?php printf(__('Lineup for %s', 'ensemble'), esc_html($event->post_title)); ?></h3>
            </div>
            
            <div class="ensemble-lineup-item">
                
                <?php if (has_post_thumbnail($artist->ID)): ?>
                <div class="ensemble-lineup-image">
                    <a href="<?php echo get_permalink($artist->ID); ?>">
                        <?php echo get_the_post_thumbnail($artist->ID, 'medium'); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="ensemble-lineup-content">
                    
                    <h4 class="ensemble-lineup-name">
                        <a href="<?php echo get_permalink($artist->ID); ?>">
                            <?php echo esc_html($artist->post_title); ?>
                        </a>
                    </h4>
                    
                    <?php 
                    $genre = get_post_meta($artist->ID, 'es_artist_genre', true);
                    if ($show_genre && $genre): 
                    ?>
                    <div class="ensemble-lineup-genre">
                        <?php echo esc_html($genre); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_times): ?>
                    <div class="ensemble-lineup-time">
                        <span class="dashicons dashicons-clock"></span>
                        <?php 
                        $start_time = $this->get_event_meta($event_id, 'start_time');
                        echo $start_time ? esc_html($start_time) : __('Time TBA', 'ensemble');
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_bio && $artist->post_excerpt): ?>
                    <div class="ensemble-lineup-bio">
                        <?php echo wpautop(wp_trim_words($artist->post_excerpt, 30)); ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Featured Events Shortcode
     * Shows only featured/highlighted events
     * 
     * Usage: [ensemble_featured_events limit="3" layout="grid"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function featured_events_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('events');
        }
        
        $atts = shortcode_atts(array(
            'limit' => '3',
            'layout' => 'grid', // grid, list, slider
            'columns' => '3',
            'show_excerpt' => 'true',
        ), $atts, 'ensemble_featured_events');
        
        $limit = absint($atts['limit']);
        $layout = sanitize_key($atts['layout']);
        $columns = absint($atts['columns']);
        $show_excerpt = filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN);
        
        // Query featured events
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'es_event_featured',
                    'value' => '1',
                    'compare' => '=',
                ),
                array(
                    'key' => 'es_event_start_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
                // Exclude preview/cancelled/postponed events - only show published
                array(
                    'relation' => 'OR',
                    array(
                        'key' => '_event_status',
                        'value' => array('preview', 'cancelled', 'postponed'),
                        'compare' => 'NOT IN',
                    ),
                    array(
                        'key' => '_event_status',
                        'value' => 'publish',
                        'compare' => '=',
                    ),
                    array(
                        'key' => '_event_status',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            ),
            'meta_key' => 'es_event_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        );
        
        $events = new WP_Query($args);
        
        if (!$events->have_posts()) {
            return '<div class="ensemble-no-featured">' . __('No featured events at the moment.', 'ensemble') . '</div>';
        }
        
        ob_start();
        ?>
        
        <div class="ensemble-featured-events ensemble-layout-<?php echo esc_attr($layout); ?> ensemble-columns-<?php echo esc_attr($columns); ?>">
            
            <?php while ($events->have_posts()): $events->the_post(); 
                $event_id = get_the_ID();
                $start_date = $this->get_event_meta($event_id, 'start_date');
                $start_time = $this->get_event_meta($event_id, 'start_time');
                $location_id = $this->get_event_meta($event_id, 'location');
                $artist_id = $this->get_event_meta($event_id, 'artist');
                
                $location = $location_id ? get_post($location_id) : null;
                $artist = $artist_id ? get_post($artist_id) : null;
            ?>
            
            <div class="ensemble-featured-card">
                
                <div class="ensemble-featured-badge">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php _e('Featured', 'ensemble'); ?>
                </div>
                
                <div class="ensemble-featured-image">
                    <?php if (has_post_thumbnail()): ?>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('large'); ?>
                        </a>
                    <?php else: ?>
                        <div class="ensemble-featured-placeholder">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="ensemble-featured-content">
                    
                    <div class="ensemble-featured-date">
                        <?php if ($start_date): ?>
                            <?php echo date_i18n('F j, Y', strtotime($start_date)); ?>
                            <?php if ($start_time): ?>
                                <span class="featured-time">• <?php echo esc_html($start_time); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="ensemble-featured-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    
                    <?php if ($show_excerpt && get_the_excerpt()): ?>
                    <div class="ensemble-featured-excerpt">
                        <?php echo wpautop(wp_trim_words(get_the_excerpt(), 25)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ensemble-featured-meta">
                        <?php if ($location): ?>
                            <span class="meta-location">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($location->post_title); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($artist): ?>
                            <span class="meta-artist">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo esc_html($artist->post_title); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?php the_permalink(); ?>" class="ensemble-featured-btn">
                        <?php _e('View Event', 'ensemble'); ?> →
                    </a>
                    
                </div>
                
            </div>
            
            <?php endwhile; ?>
            
        </div>
        
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Filterable Events Grid Shortcode
     * Advanced event grid with AJAX filtering
     * 
     * Usage: [ensemble_events_grid show_filters="true"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function events_grid_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('events');
        }
        
        $atts = shortcode_atts(array(
            // Layout
            'layout' => 'grid',           // grid, list, masonry, slider, hero, carousel
            'columns' => '3',             // 1-6 (also used as slides_to_show for slider)
            'gap' => '20',                // Gap in px
            'style' => 'default',         // default, compact, minimal, overlay, featured
            
            // Slider-specific options
            'autoplay' => 'false',        // Auto-advance slides
            'autoplay_speed' => '5000',   // Autoplay interval in ms
            'loop' => 'false',            // Loop back to start
            'dots' => 'true',             // Show dot navigation
            'arrows' => 'true',           // Show arrow navigation
            'fullscreen' => 'false',      // Hero: 100vh fullscreen
            
            // Query
            'limit' => '12',
            'offset' => '0',
            'orderby' => 'date',          // date, title, menu_order, rand
            'order' => 'ASC',
            'show' => 'upcoming',         // upcoming, past, all
            'featured' => '',             // 1 = only featured
            
            // Filters
            'category' => '',             // Category slug(s), comma-separated
            'location' => '',             // Location ID(s), comma-separated
            'artist' => '',               // Artist ID(s), comma-separated
            
            // UI Elements
            'show_filters' => 'true',
            'show_filter' => '',              // Alias for show_filters (Block compatibility)
            'show_search' => 'true',
            
            // Display Options
            'show_image' => '1',
            'show_title' => '1',
            'show_date' => '1',
            'show_time' => '1',
            'show_location' => '1',
            'show_category' => '1',
            'show_price' => '1',
            'show_description' => '0',
            'show_artists' => '0',
            'excerpt_length' => '100',
            
            // Empty state
            'empty_message' => '',
            
            // Template
            'template' => '',
        ), $atts, 'ensemble_events_grid');
        
        // Parse all attributes
        $layout = sanitize_key($atts['layout']);
        $columns = absint($atts['columns']);
        $gap = absint($atts['gap']);
        $style = sanitize_key($atts['style']);
        $limit = absint($atts['limit']);
        $offset = absint($atts['offset']);
        $orderby = sanitize_key($atts['orderby']);
        $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';
        $show = sanitize_key($atts['show']);
        $featured_only = filter_var($atts['featured'], FILTER_VALIDATE_BOOLEAN);
        
        // Slider options
        $autoplay = filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN);
        $autoplay_speed = absint($atts['autoplay_speed']);
        $loop = filter_var($atts['loop'], FILTER_VALIDATE_BOOLEAN);
        $show_dots = filter_var($atts['dots'], FILTER_VALIDATE_BOOLEAN);
        $show_arrows = filter_var($atts['arrows'], FILTER_VALIDATE_BOOLEAN);
        $fullscreen = filter_var($atts['fullscreen'], FILTER_VALIDATE_BOOLEAN);
        $is_slider_layout = in_array($layout, array('slider', 'hero', 'carousel'));
        
        // Filters
        $category_filter = sanitize_text_field($atts['category']);
        $location_filter = sanitize_text_field($atts['location']);
        $artist_filter = sanitize_text_field($atts['artist']);
        
        // UI - Disable filters automatically for slider layouts (they don't make sense there)
        // Support both show_filters and show_filter (Block uses singular)
        $show_filters_attr = !empty($atts['show_filter']) ? $atts['show_filter'] : $atts['show_filters'];
        $show_filters = filter_var($show_filters_attr, FILTER_VALIDATE_BOOLEAN);
        $show_search = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
        
        if ($is_slider_layout) {
            $show_filters = false;
            $show_search = false;
        }
        
        // Display options
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_title = filter_var($atts['show_title'], FILTER_VALIDATE_BOOLEAN);
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        $show_time = filter_var($atts['show_time'], FILTER_VALIDATE_BOOLEAN);
        $show_location_meta = filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN);
        $show_category = filter_var($atts['show_category'], FILTER_VALIDATE_BOOLEAN);
        $show_price = filter_var($atts['show_price'], FILTER_VALIDATE_BOOLEAN);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_artists = filter_var($atts['show_artists'], FILTER_VALIDATE_BOOLEAN);
        $excerpt_length = absint($atts['excerpt_length']);
        $empty_message = sanitize_text_field($atts['empty_message']);
        
        $template = $this->get_effective_template($atts['template']);
        
        // Apply template if specified
        $this->apply_shortcode_template($template);
        
        // Get filter data for UI
        $categories = get_terms(array(
            'taxonomy' => 'ensemble_category',
            'hide_empty' => true,
        ));
        if (is_wp_error($categories)) {
            $categories = array();
        }
        
        // Get all artists for filter dropdown
        $artists = get_posts(array(
            'post_type' => 'ensemble_artist',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        // Get all locations for filter dropdown
        $locations = get_posts(array(
            'post_type' => 'ensemble_location',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        wp_reset_postdata();
        
        // Build events query with all filters
        $events_args = array(
            'post_type' => ensemble_get_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => $offset,
        );
        
        // Get the correct date meta key (considers Field Mapping)
        $date_key = $this->get_date_meta_key();
        
        // Date filtering (upcoming/past/all)
        $meta_query = array();
        
        // Exclude preview, cancelled, postponed events from normal grids (only show published)
        $meta_query['status_filter'] = array(
            'relation' => 'OR',
            array(
                'key' => '_event_status',
                'value' => array('preview', 'cancelled', 'postponed'),
                'compare' => 'NOT IN',
            ),
            array(
                'key' => '_event_status',
                'value' => 'publish',
                'compare' => '=',
            ),
            array(
                'key' => '_event_status',
                'compare' => 'NOT EXISTS',
            ),
        );
        
        if ($show === 'upcoming') {
            $meta_query[] = array(
                'key' => $date_key,
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE',
            );
        } elseif ($show === 'past') {
            $meta_query[] = array(
                'key' => $date_key,
                'value' => date('Y-m-d'),
                'compare' => '<',
                'type' => 'DATE',
            );
        }
        
        // Apply sorting - respect user selection
        if ($orderby === 'date') {
            $events_args['meta_key'] = $date_key;
            $events_args['orderby'] = 'meta_value';
            $events_args['order'] = $order; // ASC or DESC from user
        } else {
            $events_args['orderby'] = $orderby;
            $events_args['order'] = $order;
        }
        
        // Featured filter
        if ($featured_only) {
            $meta_query[] = array(
                'key' => 'es_event_featured',
                'value' => '1',
                'compare' => '=',
            );
        }
        
        // Location filter - check multiple possible meta keys
        if (!empty($location_filter)) {
            $location_ids = array_map('absint', explode(',', $location_filter));
            
            // Get the correct meta key for location
            $location_meta_key = $this->get_meta_key_for_field('location');
            
            $meta_query[] = array(
                'key' => $location_meta_key,
                'value' => $location_ids,
                'compare' => 'IN',
            );
        }
        
        // Artist filter - handle serialized arrays (ACF stores artists as serialized)
        if (!empty($artist_filter)) {
            $artist_ids = array_map('absint', explode(',', $artist_filter));
            
            // Get the correct meta key for artist
            $artist_meta_key = $this->get_meta_key_for_field('artist');
            
            // Build meta query for serialized arrays
            $artist_meta_queries = array('relation' => 'OR');
            
            foreach ($artist_ids as $artist_id) {
                // Match serialized integer: i:123;
                $artist_meta_queries[] = array(
                    'key' => $artist_meta_key,
                    'value' => 'i:' . $artist_id . ';',
                    'compare' => 'LIKE',
                );
                // Match serialized string: s:3:"123";
                $artist_meta_queries[] = array(
                    'key' => $artist_meta_key,
                    'value' => 's:' . strlen($artist_id) . ':"' . $artist_id . '";',
                    'compare' => 'LIKE',
                );
                // Match plain value (non-serialized)
                $artist_meta_queries[] = array(
                    'key' => $artist_meta_key,
                    'value' => $artist_id,
                    'compare' => '=',
                );
            }
            
            $meta_query[] = $artist_meta_queries;
        }
        
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $events_args['meta_query'] = $meta_query;
        }
        
        // Category filter (taxonomy)
        if (!empty($category_filter)) {
            $events_args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'field' => 'slug',
                    'terms' => array_map('sanitize_title', explode(',', $category_filter)),
                ),
            );
        }
        
        $events = new WP_Query($events_args);
        
        // Generate custom gap style if not default
        $custom_style = '';
        if ($gap != 20) {
            $custom_style = ' style="gap: ' . esc_attr($gap) . 'px;"';
        }
        
        // Get active layout set for styling
        $active_layout_set = '';
        $active_mode = 'light';
        if (class_exists('ES_Layout_Sets')) {
            $active_layout_set = ES_Layout_Sets::get_active_set();
            $active_mode = ES_Layout_Sets::get_active_mode();
        }
        
        ob_start();
        
        // Build wrapper classes
        $wrapper_classes = array(
            'ensemble-events-grid-wrapper',
            'es-layout-' . esc_attr($active_layout_set),
        );
        
        // Add mode class for Pure layout
        if ($active_layout_set === 'pure') {
            $wrapper_classes[] = 'es-mode-' . esc_attr($active_mode);
        }
        
        if ($is_slider_layout) {
            $wrapper_classes[] = 'es-is-slider';
        }
        ?>
        
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
            
            <?php if (!$is_slider_layout && ($show_filters || $show_search)): ?>
            <div class="ensemble-grid-filters">
                
                <?php if ($show_search): ?>
                <div class="ensemble-search-box">
                    <input type="text" 
                           class="ensemble-search-input" 
                           placeholder="<?php _e('Search events...', 'ensemble'); ?>">
                </div>
                <?php endif; ?>
                
                <?php if ($show_filters): ?>
                <div class="ensemble-filter-row">
                    
                    <!-- Time Filter -->
                    <select class="ensemble-filter" data-filter="time">
                        <option value="upcoming"><?php _e('Upcoming', 'ensemble'); ?></option>
                        <option value="all"><?php _e('All Events', 'ensemble'); ?></option>
                        <option value="today"><?php _e('Today', 'ensemble'); ?></option>
                        <option value="this-week"><?php _e('This Week', 'ensemble'); ?></option>
                        <option value="past"><?php _e('Past Events', 'ensemble'); ?></option>
                    </select>
                    
                    <!-- Category Filter -->
                    <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                    <select class="ensemble-filter" data-filter="category">
                        <option value=""><?php _e('All Categories', 'ensemble'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat->term_id); ?>">
                                <?php echo esc_html($cat->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    
                    <!-- Artist Filter -->
                    <?php if (!empty($artists)): ?>
                    <select class="ensemble-filter" data-filter="artist">
                        <option value=""><?php _e('All Artists', 'ensemble'); ?></option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?php echo esc_attr($artist->ID); ?>">
                                <?php echo esc_html($artist->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    
                    <!-- Location Filter -->
                    <?php if (!empty($locations)): ?>
                    <select class="ensemble-filter" data-filter="location">
                        <option value=""><?php _e('All Locations', 'ensemble'); ?></option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo esc_attr($location->ID); ?>">
                                <?php echo esc_html($location->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    
                </div>
                <?php endif; ?>
                
            </div>
            <?php endif; ?>
            
            <?php 
            // ========================================
            // SLIDER / HERO LAYOUT
            // ========================================
            if ($is_slider_layout && class_exists('ES_Slider_Renderer')):
                
                // Slider options
                $slider_options = array(
                    'slides_to_show' => ($layout === 'hero') ? 1 : $columns,
                    'slides_to_scroll' => ($layout === 'hero') ? 1 : 1,
                    'autoplay' => ($layout === 'hero') ? true : $autoplay,
                    'autoplay_speed' => $autoplay_speed,
                    'loop' => ($layout === 'hero') ? true : $loop,
                    'dots' => $show_dots,
                    'arrows' => $show_arrows,
                    'gap' => ($layout === 'hero') ? 0 : $gap,
                    'fullscreen' => $fullscreen,
                );
                
                echo ES_Slider_Renderer::render_wrapper_start($layout, $slider_options, 'events');
                
                if ($events->have_posts()):
                    $slide_index = 0;
                    
                    while ($events->have_posts()): $events->the_post();
                        $event_id = get_the_ID();
                        
                        // Prepare event data
                        $start_date = $this->get_event_meta($event_id, 'start_date');
                        $start_time = $this->get_event_meta($event_id, 'start_time');
                        $location_id = $this->get_event_meta($event_id, 'location');
                        $artist_id = $this->get_event_meta($event_id, 'artist');
                        $price = $this->get_event_meta($event_id, 'price');
                        $ticket_url = $this->get_event_meta($event_id, 'ticket_url');
                        $event_categories = get_the_terms($event_id, 'ensemble_category');
                        $location = $location_id ? get_post($location_id) : null;
                        
                        // Load artists for this event
                        $event_artists = array();
                        if (function_exists('es_load_event_artists')) {
                            $artists_data = es_load_event_artists($event_id);
                            if (!empty($artists_data)) {
                                foreach ($artists_data as $a) {
                                    $event_artists[] = $a['name'];
                                }
                            }
                        }
                        
                        $event_data = array(
                            'id' => $event_id,
                            'title' => get_the_title(),
                            'permalink' => get_permalink(),
                            'featured_image' => get_the_post_thumbnail_url($event_id, 'full'),
                            'excerpt' => get_the_excerpt(),
                            'start_date' => $start_date,
                            'start_time' => $start_time,
                            'location' => $location ? $location->post_title : '',
                            'location_id' => $location_id,
                            'artists' => $event_artists,
                            'price' => $price,
                            'ticket_url' => $ticket_url,
                            'categories' => $event_categories,
                            'badge' => get_post_meta($event_id, 'event_badge', true),
                            'badge_custom' => get_post_meta($event_id, 'event_badge_custom', true),
                            // Hero Video
                            'hero_video_url' => get_post_meta($event_id, '_hero_video_url', true),
                            'hero_video_autoplay' => get_post_meta($event_id, '_hero_video_autoplay', true) == '1',
                            'hero_video_loop' => get_post_meta($event_id, '_hero_video_loop', true) == '1',
                            'hero_video_controls' => get_post_meta($event_id, '_hero_video_controls', true) == '1',
                        );
                        
                        echo ES_Slider_Renderer::render_slide_start($slide_index);
                        
                        if ($layout === 'hero') {
                            // Hero layout - full-width slide with overlay
                            echo ES_Slider_Renderer::render_hero_event_slide($event_data, array(
                                'show_category' => $show_category,
                                'show_date' => $show_date,
                                'show_time' => $show_time,
                                'show_location' => $show_location_meta,
                                'show_excerpt' => $show_description,
                            ));
                        } else {
                            // Slider/Carousel - use card template
                            $card_template = null;
                            if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
                                $active_set = ES_Layout_Sets::get_active_set();
                                $card_template = ES_Template_Loader::locate_template('event-card.php', $active_set);
                            }
                            
                            if ($card_template && file_exists($card_template)) {
                                $event = $event_data;
                                $shortcode_atts = array(
                                    'show_image' => $show_image,
                                    'show_title' => $show_title,
                                    'show_date' => $show_date,
                                    'show_time' => $show_time,
                                    'show_location' => $show_location_meta,
                                    'show_price' => $show_price,
                                    'show_category' => $show_category,
                                    'show_description' => $show_description,
                                    'show_artists' => $show_artists,
                                );
                                $card_data_attributes = '';
                                $card_index = $slide_index;
                                include $card_template;
                            } else {
                                // Fallback card
                                $this->render_slider_event_card($event_data, array(
                                    'show_image' => $show_image,
                                    'show_title' => $show_title,
                                    'show_date' => $show_date,
                                    'show_time' => $show_time,
                                    'show_location' => $show_location_meta,
                                    'show_price' => $show_price,
                                    'show_category' => $show_category,
                                    'style' => $style,
                                ));
                            }
                        }
                        
                        echo ES_Slider_Renderer::render_slide_end();
                        $slide_index++;
                    endwhile;
                else:
                    ?>
                    <div class="ensemble-no-results">
                        <?php echo $empty_message ? esc_html($empty_message) : __('No events found.', 'ensemble'); ?>
                    </div>
                    <?php
                endif;
                
                echo ES_Slider_Renderer::render_wrapper_end();
                
            else:
            // ========================================
            // STANDARD GRID / LIST LAYOUT
            // ========================================
            ?>
            
            <!-- Events Container -->
            <div class="ensemble-events-grid ensemble-layout-<?php echo esc_attr($layout); ?> ensemble-columns-<?php echo esc_attr($columns); ?> ensemble-style-<?php echo esc_attr($style); ?>"<?php echo $custom_style; ?>>
                
                <?php if ($events->have_posts()): ?>
                    <?php 
                    // Get template path for event cards
                    $card_template = null;
                    if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
                        $active_set = ES_Layout_Sets::get_active_set();
                        $card_template = ES_Template_Loader::locate_template('event-card.php', $active_set);
                    }
                    
                    // Card index for layouts that need position info (e.g., Magazine hero)
                    $card_index = 0;
                    
                    while ($events->have_posts()): $events->the_post(); 
                        $event_id = get_the_ID();
                        
                        // Prepare event data (used by all templates)
                        $start_date = $this->get_event_meta($event_id, 'start_date');
                        $start_time = $this->get_event_meta($event_id, 'start_time');
                        $location_id = $this->get_event_meta($event_id, 'location');
                        $artist_id = $this->get_event_meta($event_id, 'artist');
                        $price = $this->get_event_meta($event_id, 'price');
                        $event_categories = get_the_terms($event_id, 'ensemble_category');
                        $category_id = ($event_categories && !is_wp_error($event_categories)) ? $event_categories[0]->term_id : '';
                        
                        $location = $location_id ? get_post($location_id) : null;
                        $artist = $artist_id ? get_post($artist_id) : null;
                        
                        // Load artists for this event
                        $event_artists = array();
                        $artist_ids_array = array();
                        if (function_exists('es_load_event_artists')) {
                            $artists_data = es_load_event_artists($event_id);
                            if (!empty($artists_data)) {
                                foreach ($artists_data as $a) {
                                    $event_artists[] = $a['name'];
                                    // Ensure ID is integer, not array
                                    $aid = isset($a['id']) ? $a['id'] : null;
                                    if (is_array($aid)) {
                                        $aid = reset($aid);
                                    }
                                    if ($aid) {
                                        $artist_ids_array[] = intval($aid);
                                    }
                                }
                            }
                        }
                        // Fallback: if no artists loaded but we have artist_id
                        if (empty($artist_ids_array) && $artist_id) {
                            if (is_array($artist_id)) {
                                foreach ($artist_id as $aid) {
                                    if (is_array($aid)) {
                                        $aid = reset($aid);
                                    }
                                    if ($aid) {
                                        $artist_ids_array[] = intval($aid);
                                    }
                                }
                            } else {
                                $artist_ids_array[] = intval($artist_id);
                            }
                        }
                        $artist_ids_array = array_filter(array_map('intval', $artist_ids_array));
                        $artist_ids_string = implode(',', $artist_ids_array);
                        
                        // BUILD DATA ATTRIBUTES STRING (used by ALL templates)
                        $card_data_attributes = sprintf(
                            'data-date="%s" data-category-id="%s" data-location-id="%s" data-artist-id="%s" data-artist-ids="%s"',
                            esc_attr($start_date),
                            esc_attr($category_id),
                            esc_attr($location_id),
                            esc_attr(is_array($artist_id) ? ($artist_id[0] ?? '') : $artist_id),
                            esc_attr($artist_ids_string)
                        );
                        
                        // If we have a template, use it
                        if ($card_template && file_exists($card_template)) {
                            // Prepare event data for template
                            $event = array(
                                'id' => $event_id,
                                'title' => get_the_title(),
                                'permalink' => get_permalink(),
                                'featured_image' => get_the_post_thumbnail_url($event_id, 'large'),
                                'excerpt' => get_the_excerpt(),
                                'start_date' => $start_date,
                                'start_time' => $start_time,
                                'location' => $location ? $location->post_title : '',
                                'location_id' => $location_id,
                                'artist_id' => $artist_id,
                                'artists' => $event_artists,
                                'status' => get_post_meta($event_id, '_event_status', true) ?: 'publish',
                                'price' => $price,
                                'categories' => $event_categories,
                                'category_id' => $category_id,
                            );
                            
                            // Pass shortcode attributes to template
                            $shortcode_atts = array(
                                'show_image' => $show_image,
                                'show_title' => $show_title,
                                'show_date' => $show_date,
                                'show_time' => $show_time,
                                'show_location' => $show_location_meta,
                                'show_price' => $show_price,
                                'show_category' => $show_category,
                                'show_description' => $show_description,
                                'show_artists' => $show_artists,
                            );
                            
                            // $card_data_attributes and $card_index are available in template
                            include $card_template;
                            $card_index++;
                        } else {
                            // Fallback: Use default inline HTML
                            ?>
                            
                            <div class="ensemble-event-card ensemble-card-<?php echo esc_attr($style); ?>" <?php echo $card_data_attributes; ?>>
                                
                                <?php if ($show_image): ?>
                                <div class="ensemble-event-image">
                                    <?php if (has_post_thumbnail()): ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium'); ?>
                                        </a>
                                        <?php if ($show_category && $event_categories && !is_wp_error($event_categories)): ?>
                                        <span class="ensemble-event-category-badge"><?php echo esc_html($event_categories[0]->name); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="ensemble-event-placeholder">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="ensemble-event-content">
                                    
                                    <?php if ($show_date && $start_date): ?>
                                    <div class="ensemble-event-date">
                                        <span class="date-day"><?php echo date_i18n('j', strtotime($start_date)); ?></span>
                                        <span class="date-month"><?php echo date_i18n('M', strtotime($start_date)); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($show_title): ?>
                                    <h3 class="ensemble-event-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    <?php endif; ?>
                                    
                                    <?php if ($show_description): ?>
                                    <div class="ensemble-event-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), $excerpt_length / 5, '...'); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="ensemble-event-meta">
                                        <?php if ($show_time && $start_time): ?>
                                        <span class="ensemble-meta-time"><span class="dashicons dashicons-clock"></span> <?php echo esc_html($start_time); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($show_location_meta && $location): ?>
                                        <span class="ensemble-meta-location"><span class="dashicons dashicons-location"></span> <?php echo esc_html($location->post_title); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($show_artists && $artist): ?>
                                        <span class="ensemble-meta-artist"><span class="dashicons dashicons-admin-users"></span> <?php echo esc_html($artist->post_title); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($show_price && $price): ?>
                                        <span class="ensemble-meta-price"><span class="dashicons dashicons-tickets-alt"></span> <?php echo esc_html($price); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                </div>
                                
                            </div>
                            
                            <?php
                            $card_index++;
                        }
                    endwhile; ?>
                <?php else: ?>
                    <div class="ensemble-no-results">
                        <?php echo $empty_message ? esc_html($empty_message) : __('No events found.', 'ensemble'); ?>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <?php endif; // Ende: Standard Grid/List Layout ?>
            
        </div>
        
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Render slider event card (fallback)
     * 
     * @param array $event Event data
     * @param array $options Display options
     */
    private function render_slider_event_card($event, $options = array()) {
        $defaults = array(
            'show_image' => true,
            'show_title' => true,
            'show_date' => true,
            'show_time' => true,
            'show_location' => true,
            'show_price' => true,
            'show_category' => true,
            'style' => 'default',
        );
        $options = wp_parse_args($options, $defaults);
        ?>
        <div class="ensemble-event-card ensemble-card-<?php echo esc_attr($options['style']); ?>">
            
            <?php if ($options['show_image']): ?>
            <div class="ensemble-event-image">
                <?php if (!empty($event['featured_image'])): ?>
                    <a href="<?php echo esc_url($event['permalink']); ?>">
                        <img src="<?php echo esc_url($event['featured_image']); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                    </a>
                    <?php if ($options['show_category'] && !empty($event['categories']) && !is_wp_error($event['categories'])): ?>
                    <span class="ensemble-event-category-badge"><?php echo esc_html($event['categories'][0]->name); ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="ensemble-event-placeholder">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-event-content">
                
                <?php if ($options['show_date'] && !empty($event['start_date'])): ?>
                <div class="ensemble-event-date">
                    <span class="date-day"><?php echo date_i18n('j', strtotime($event['start_date'])); ?></span>
                    <span class="date-month"><?php echo date_i18n('M', strtotime($event['start_date'])); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($options['show_title']): ?>
                <h3 class="ensemble-event-title">
                    <a href="<?php echo esc_url($event['permalink']); ?>"><?php echo esc_html($event['title']); ?></a>
                </h3>
                <?php endif; ?>
                
                <div class="ensemble-event-meta">
                    <?php if ($options['show_time'] && !empty($event['start_time'])): ?>
                    <span class="ensemble-meta-time"><span class="dashicons dashicons-clock"></span> <?php echo esc_html($event['start_time']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($options['show_location'] && !empty($event['location'])): ?>
                    <span class="ensemble-meta-location"><span class="dashicons dashicons-location"></span> <?php echo esc_html($event['location']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($options['show_price'] && !empty($event['price'])): ?>
                    <span class="ensemble-meta-price"><span class="dashicons dashicons-tickets-alt"></span> <?php echo esc_html($event['price']); ?></span>
                    <?php endif; ?>
                </div>
                
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Calendar Shortcode with FullCalendar
     * Displays events in a full-featured calendar
     * 
     * Usage: [ensemble_calendar view="month"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function preview_events_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('events');
        }
        
        $atts = shortcode_atts(array(
            'limit' => '-1',              // -1 = alle
            'title' => '',                // Überschrift (leer = "Vorschau")
            'show_year' => 'false',       // Jahr im Datum anzeigen
            'template' => '',             // Layout template (für Farben)
            'collapsed' => 'true',        // Standardmäßig eingeklappt
        ), $atts, 'ensemble_preview_events');
        
        $limit = intval($atts['limit']);
        $title = sanitize_text_field($atts['title']);
        $show_year = filter_var($atts['show_year'], FILTER_VALIDATE_BOOLEAN);
        $collapsed = filter_var($atts['collapsed'], FILTER_VALIDATE_BOOLEAN);
        
        // Standard-Titel wenn keiner angegeben
        if (empty($title)) {
            $title = __('Vorschau', 'ensemble');
        }
        
        // Apply template if specified
        if (!empty($atts['template'])) {
            $this->apply_shortcode_template($atts['template']);
        }
        
        // Get the correct date meta key
        $date_key = $this->get_date_meta_key();
        
        // Query: Events mit Status "preview", sortiert nach Datum
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => $date_key,
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_event_status',
                    'value' => 'preview',
                    'compare' => '=',
                ),
                array(
                    'key' => $date_key,
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
            ),
        );
        
        $events = new WP_Query($args);
        
        if (!$events->have_posts()) {
            return '';
        }
        
        // Unique ID für mehrere Instanzen
        $unique_id = 'es-preview-' . uniqid();
        $event_count = $events->found_posts;
        
        ob_start();
        ?>
        <div class="es-preview-events <?php echo $collapsed ? 'es-preview-collapsed' : 'es-preview-expanded'; ?>" id="<?php echo esc_attr($unique_id); ?>">
            
            <button type="button" class="es-preview-toggle" aria-expanded="<?php echo $collapsed ? 'false' : 'true'; ?>" aria-controls="<?php echo esc_attr($unique_id); ?>-list">
                <span class="es-preview-toggle-text">
                    <?php echo esc_html($title); ?>
                    <span class="es-preview-count">(<?php echo esc_html($event_count); ?>)</span>
                </span>
                <span class="es-preview-toggle-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
            
            <div class="es-preview-content" id="<?php echo esc_attr($unique_id); ?>-list" <?php echo $collapsed ? 'hidden' : ''; ?>>
                <ul class="es-preview-events-list">
                    <?php while ($events->have_posts()): $events->the_post(); 
                        $event_id = get_the_ID();
                        $event_date = get_post_meta($event_id, $date_key, true);
                        
                        // Location ermitteln
                        $location_meta_key = $this->get_meta_key_for_field('location');
                        $location_id = get_post_meta($event_id, $location_meta_key, true);
                        $location_name = '';
                        
                        if (!empty($location_id)) {
                            $location_name = get_the_title($location_id);
                        }
                        
                        // Fallback: tba wenn keine Location
                        if (empty($location_name)) {
                            $location_name = __('tba', 'ensemble');
                        }
                        
                        // Event Title
                        $event_title = get_the_title();
                        
                        // Datum formatieren
                        if (!empty($event_date)) {
                            $timestamp = strtotime($event_date);
                            $day_name = date_i18n('D', $timestamp);
                            $day_name = rtrim($day_name, '. ');
                            $day = date('d', $timestamp);
                            $month = date('m', $timestamp);
                            $year = date('y', $timestamp);
                            
                            if ($show_year) {
                                $date_display = $day_name . ' ' . $day . '.' . $month . '.' . $year;
                            } else {
                                $date_display = $day_name . ' ' . $day . '.' . $month . '.';
                            }
                        } else {
                            $date_display = __('tba', 'ensemble');
                        }
                    ?>
                    <li class="es-preview-event-item">
                        <span class="es-preview-date"><?php echo esc_html($date_display); ?></span>
                        <span class="es-preview-separator">|</span>
                        <span class="es-preview-location"><?php echo esc_html($location_name); ?></span>
                        <?php if (!empty($event_title) && $event_title !== $location_name): ?>
                        <span class="es-preview-arrow">→</span>
                        <span class="es-preview-title"><?php echo esc_html($event_title); ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
        
        <style>
        .es-preview-events {
            margin: 2rem auto;
            max-width: 800px;
        }
        
        /* Toggle Button */
        .es-preview-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 1rem 1.5rem;
            background: var(--ensemble-card-bg, rgba(255,255,255,0.05));
            border: 1px solid var(--ensemble-card-border, rgba(255,255,255,0.1));
            border-radius: var(--ensemble-card-radius, 8px);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .es-preview-toggle:hover {
            background: var(--ensemble-card-hover-bg, rgba(255,255,255,0.1));
            border-color: var(--ensemble-primary, #e91e8c);
        }
        
        .es-preview-toggle-text {
            font-size: var(--ensemble-h3-size, 1.25rem);
            font-weight: var(--ensemble-heading-weight, 700);
            color: var(--ensemble-text, #ffffff);
        }
        
        .es-preview-count {
            font-weight: 500;
            color: var(--ensemble-text-secondary, rgba(255,255,255,0.7));
            margin-left: 8px;
        }
        
        .es-preview-toggle-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            color: var(--ensemble-primary, #e91e8c);
            transition: transform 0.3s ease;
        }
        
        .es-preview-toggle-icon svg {
            width: 20px;
            height: 20px;
        }
        
        /* Expanded State */
        .es-preview-expanded .es-preview-toggle-icon {
            transform: rotate(180deg);
        }
        
        .es-preview-expanded .es-preview-toggle {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            border-bottom-color: transparent;
        }
        
        /* Content Container */
        .es-preview-content {
            background: var(--ensemble-card-bg, rgba(255,255,255,0.05));
            border: 1px solid var(--ensemble-card-border, rgba(255,255,255,0.1));
            border-top: none;
            border-radius: 0 0 var(--ensemble-card-radius, 8px) var(--ensemble-card-radius, 8px);
        }
        
        .es-preview-content[hidden] {
            display: none;
        }
        
        .es-preview-events-list {
            list-style: none;
            margin: 0;
            padding: 0.5rem 1.5rem 1.5rem;
        }
        
        .es-preview-event-item {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--ensemble-card-border, rgba(255,255,255,0.1));
            font-size: 1.125rem;
            color: var(--ensemble-text, #ffffff);
        }
        
        .es-preview-event-item:last-child {
            border-bottom: none;
        }
        
        .es-preview-date {
            font-weight: 700;
            font-family: var(--ensemble-font-heading, inherit);
            color: var(--ensemble-primary, #e91e8c);
            font-size: 1.125rem;
        }
        
        .es-preview-separator {
            color: var(--ensemble-text-muted, rgba(255,255,255,0.5));
            font-size: 1.125rem;
        }
        
        .es-preview-location {
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--ensemble-text, #ffffff);
        }
        
        .es-preview-arrow {
            color: var(--ensemble-primary, #e91e8c);
            font-weight: 700;
            font-size: 1.125rem;
        }
        
        .es-preview-title {
            color: var(--ensemble-text-secondary, rgba(255,255,255,0.7));
            font-size: 1rem;
        }
        
        /* Mobile */
        @media (max-width: 600px) {
            .es-preview-toggle {
                padding: 0.875rem 1rem;
            }
            
            .es-preview-toggle-text {
                font-size: 1rem;
            }
            
            .es-preview-events-list {
                padding: 0.5rem 1rem 1rem;
            }
            
            .es-preview-event-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
                padding: 0.875rem 0;
            }
            
            .es-preview-separator {
                display: none;
            }
            
            .es-preview-date,
            .es-preview-location {
                font-size: 0.9375rem;
            }
        }
        </style>
        
        <script>
        (function() {
            var container = document.getElementById('<?php echo esc_js($unique_id); ?>');
            if (!container) return;
            
            var toggle = container.querySelector('.es-preview-toggle');
            var content = container.querySelector('.es-preview-content');
            
            if (!toggle || !content) return;
            
            toggle.addEventListener('click', function() {
                var isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                
                toggle.setAttribute('aria-expanded', !isExpanded);
                
                if (isExpanded) {
                    content.hidden = true;
                    container.classList.remove('es-preview-expanded');
                    container.classList.add('es-preview-collapsed');
                } else {
                    content.hidden = false;
                    container.classList.remove('es-preview-collapsed');
                    container.classList.add('es-preview-expanded');
                }
            });
        })();
        </script>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}
