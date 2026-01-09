<?php
/**
 * Ensemble Shortcodes Class
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes on init hook
        add_action('init', array($this, 'register_shortcodes'));
        
        // Enqueue styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // Register AJAX handlers for calendar
        add_action('wp_ajax_ensemble_get_calendar_events', array('ES_Shortcodes', 'ajax_get_calendar_events'));
        add_action('wp_ajax_nopriv_ensemble_get_calendar_events', array('ES_Shortcodes', 'ajax_get_calendar_events'));
    }
    
    /**
     * Detect which meta key format is being used and return the date meta key
     * Considers Field Mapping, es_ prefix, and legacy format
     * 
     * @return string The meta key to use for date queries
     */
    private function get_date_meta_key() {
        // Use centralized meta key management
        if (class_exists('ES_Meta_Keys')) {
            return ES_Meta_Keys::get('date');
        }
        
        // Fallback to helper function
        if (function_exists('ensemble_get_date_meta_key')) {
            return ensemble_get_date_meta_key();
        }
        
        return 'event_date';
    }
    
    /**
     * Legacy function - kept for compatibility
     * @deprecated Use get_date_meta_key() instead
     */
    private function get_meta_key_prefix() {
        $date_key = $this->get_date_meta_key();
        
        if ($date_key === 'es_event_start_date') {
            return 'es_';
        } elseif ($date_key === 'event_date') {
            return '';
        }
        
        return 'es_';
    }
    
    /**
     * Get event meta field with Field Mapping support
     * 
     * @param int $event_id Event ID
     * @param string $field Field name (e.g. 'start_date', 'location', 'artist')
     * @return mixed Meta value
     */
    private function get_event_meta($event_id, $field) {
        // Use centralized helper function
        if (function_exists('ensemble_get_event_meta')) {
            return ensemble_get_event_meta($event_id, $field);
        }
        
        // Fallback to direct meta access
        return get_post_meta($event_id, 'event_' . $field, true);
    }
    
    /**
     * Get the correct meta key for a field (for use in WP_Query)
     * 
     * @param string $field Field name (e.g. 'location', 'artist')
     * @return string The meta key to use in queries
     */
    private function get_meta_key_for_field($field) {
        // Use centralized meta key management
        if (class_exists('ES_Meta_Keys')) {
            return ES_Meta_Keys::get($field);
        }
        
        // Fallback
        return 'event_' . $field;
    }
    
    /**
     * Apply template if specified in shortcode
     * This is a helper function used by all shortcodes
     * 
     * @param string $template Template name from shortcode attribute
     * @return void
     */
    private function apply_shortcode_template($template) {
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
     * Priority:
     * 1. Explicit shortcode attribute
     * 2. URL parameter es_layout
     * 3. Empty (use default/current)
     * 
     * @param string $shortcode_template Template from shortcode attribute
     * @return string Effective template to use
     */
    private function get_effective_template($shortcode_template = '') {
        // If explicit template in shortcode, use it
        if (!empty($shortcode_template)) {
            return sanitize_key($shortcode_template);
        }
        
        // Check URL parameter for layout switcher
        if (isset($_GET['es_layout']) && !empty($_GET['es_layout'])) {
            $url_layout = sanitize_key($_GET['es_layout']);
            $available = self::get_available_layouts();
            if (isset($available[$url_layout])) {
                return $url_layout;
            }
        }
        
        // Return empty to use default
        return '';
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('ensemble_event', array($this, 'single_event_shortcode'));
        add_shortcode('ensemble_artists', array($this, 'artists_list_shortcode'));
        add_shortcode('ensemble_artist', array($this, 'single_artist_shortcode'));
        add_shortcode('ensemble_locations', array($this, 'locations_list_shortcode'));
        add_shortcode('ensemble_location', array($this, 'single_location_shortcode'));
        add_shortcode('ensemble_upcoming_events', array($this, 'upcoming_events_shortcode'));
        add_shortcode('ensemble_lineup', array($this, 'lineup_shortcode'));
        add_shortcode('ensemble_featured_events', array($this, 'featured_events_shortcode'));
        add_shortcode('ensemble_events_grid', array($this, 'events_grid_shortcode'));
        
        // Gallery shortcode
        add_shortcode('ensemble_gallery', array($this, 'gallery_shortcode'));
        
        // Preview Events - Liste für angekündigte Events
        add_shortcode('ensemble_preview_events', array($this, 'preview_events_shortcode'));
        
        // Alias für Rückwärtskompatibilität
        add_shortcode('ensemble_events', array($this, 'events_grid_shortcode'));
        add_shortcode('ensemble_calendar', array($this, 'calendar_shortcode'));
        
        // Layout Switcher für Demo-Seiten
        add_shortcode('ensemble_layout_switcher', array($this, 'layout_switcher_shortcode'));
        add_shortcode('ensemble_demo', array($this, 'demo_page_shortcode'));
    }
    
    /**
     * Enqueue frontend styles
     */
    public function enqueue_styles() {
        // Only enqueue if shortcode is used
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'ensemble_event') ||
            has_shortcode($post->post_content, 'ensemble_artists') ||
            has_shortcode($post->post_content, 'ensemble_artist') ||
            has_shortcode($post->post_content, 'ensemble_locations') ||
            has_shortcode($post->post_content, 'ensemble_location') ||
            has_shortcode($post->post_content, 'ensemble_upcoming_events') ||
            has_shortcode($post->post_content, 'ensemble_lineup') ||
            has_shortcode($post->post_content, 'ensemble_featured_events') ||
            has_shortcode($post->post_content, 'ensemble_events_grid') ||
            has_shortcode($post->post_content, 'ensemble_events') ||
            has_shortcode($post->post_content, 'ensemble_calendar') ||
            has_shortcode($post->post_content, 'ensemble_gallery') ||
            has_shortcode($post->post_content, 'ensemble_layout_switcher') ||
            has_shortcode($post->post_content, 'ensemble_demo')
        )) {
            wp_enqueue_style(
                'ensemble-shortcodes',
                plugins_url('assets/css/shortcodes.css', dirname(__FILE__)),
                array(),
                ENSEMBLE_VERSION
            );
            
            // Load gallery CSS if gallery shortcode is used
            if (has_shortcode($post->post_content, 'ensemble_gallery')) {
                wp_enqueue_style(
                    'ensemble-gallery',
                    plugins_url('assets/css/gallery.css', dirname(__FILE__)),
                    array('ensemble-shortcodes'),
                    ENSEMBLE_VERSION
                );
                
                wp_enqueue_script(
                    'ensemble-gallery-lightbox',
                    plugins_url('assets/js/gallery-lightbox.js', dirname(__FILE__)),
                    array(),
                    ENSEMBLE_VERSION,
                    true
                );
            }
            
            // Load layout-specific CSS based on active layout set
            $this->enqueue_layout_styles();
        }
    }
    
    /**
     * Enqueue layout-specific styles based on active layout set
     */
    private function enqueue_layout_styles() {
        // Get active layout (considers URL parameter)
        $active_layout = class_exists('ES_Layout_Sets') ? ES_Layout_Sets::get_active_set() : 'classic';
        
        // Check if layout has a style.css file
        $layout_css_path = ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $active_layout . '/style.css';
        $layout_css_url = ENSEMBLE_PLUGIN_URL . 'templates/layouts/' . $active_layout . '/style.css';
        
        if (file_exists($layout_css_path)) {
            wp_enqueue_style(
                'ensemble-layout-' . $active_layout,
                $layout_css_url,
                array('ensemble-shortcodes'),
                ENSEMBLE_VERSION
            );
        }
        
        // Also load base CSS if it exists
        $base_css_path = ENSEMBLE_PLUGIN_DIR . 'assets/css/layouts/ensemble-base.css';
        if (file_exists($base_css_path)) {
            wp_enqueue_style(
                'ensemble-base',
                ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css',
                array(),
                ENSEMBLE_VERSION
            );
        }
        
        // Pure layout: Add mode toggle script
        if ($active_layout === 'pure') {
            add_action('wp_footer', array($this, 'output_pure_mode_script'), 99);
        }
    }
    
    /**
     * Output Pure mode toggle script (for grid/list pages)
     */
    public function output_pure_mode_script() {
        static $output = false;
        if ($output) return;
        if (defined('ES_PURE_MODE_SCRIPT_LOADED')) return;
        $output = true;
        ?>
        <script id="es-pure-mode-script">
        (function(){var k='ensemble_pure_mode';function g(){try{return localStorage.getItem(k)||'light'}catch(e){return'light'}}function s(m){try{localStorage.setItem(k,m)}catch(e){}}function a(m){document.body.classList.remove('es-mode-light','es-mode-dark');document.body.classList.add('es-mode-'+m);document.querySelectorAll('.es-layout-pure,.es-pure-single-event,.es-pure-single-artist,.es-pure-single-location,.ensemble-events-grid-wrapper.es-layout-pure,.ensemble-artists-wrapper.es-layout-pure,.ensemble-locations-wrapper.es-layout-pure').forEach(function(el){el.classList.remove('es-mode-light','es-mode-dark');el.classList.add('es-mode-'+m)});document.querySelectorAll('.es-mode-toggle').forEach(function(t){var sun=t.querySelector('.es-icon-sun'),moon=t.querySelector('.es-icon-moon');if(sun&&moon){sun.style.display=m==='dark'?'block':'none';moon.style.display=m==='dark'?'none':'block'}})}function t(){var c=g(),n=c==='dark'?'light':'dark';s(n);a(n)}function c(){var b=document.createElement('button');b.className='es-mode-toggle';b.innerHTML='<svg class="es-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg><svg class="es-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';b.onclick=t;return b}function i(){if(!document.querySelector('.es-layout-pure,.es-pure-single-event,.es-pure-single-artist,.es-pure-single-location'))return;a(g());if(!document.querySelector('.es-mode-toggle'))document.body.appendChild(c())}document.documentElement.classList.add('es-mode-'+g());if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',i);else i();window.togglePureMode=t})();
        </script>
        <?php
    }
    
    /**
     * Single Event Shortcode
     * Embeds a single event anywhere
     * 
     * Usage: [ensemble_event id="123" layout="card"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function single_event_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 0,                      // Event Post ID (required)
            'layout' => 'card',             // card, compact, full
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
     * Get event data from meta fields
     * Supports both es_ prefixed keys (new) and non-prefixed keys (legacy)
     */
    private function get_event_data($event_id) {
        // Get event status
        $event_status = get_post_meta($event_id, '_event_status', true);
        if (empty($event_status)) {
            $event_status = get_post_status($event_id) === 'draft' ? 'draft' : 'publish';
        }
        
        return array(
            'start_date' => $this->get_event_meta($event_id, 'start_date'),
            'start_time' => $this->get_event_meta($event_id, 'start_time'),
            'end_date' => $this->get_event_meta($event_id, 'end_date'),
            'end_time' => $this->get_event_meta($event_id, 'end_time'),
            'location_id' => $this->get_event_meta($event_id, 'location'),
            'artist_id' => $this->get_event_meta($event_id, 'artist'),
            'price' => $this->get_event_meta($event_id, 'price'),
            'ticket_url' => $this->get_event_meta($event_id, 'ticket_url'),
            'status' => $event_status,
        );
    }
    
    /**
     * Render Card Layout
     */
    private function render_card_layout($event, $data, $atts) {
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('event-card.php', $active_set);
            
            if ($template_path && file_exists($template_path)) {
                // Convert shortcode data to template format
                $event = array(
                    'id' => $event->ID,
                    'title' => $event->post_title,
                    'permalink' => get_permalink($event->ID),
                    'featured_image' => get_the_post_thumbnail_url($event->ID, 'large'),
                    'start_date' => $data['start_date'] ?? '',
                    'end_date' => $data['end_date'] ?? '',
                    'start_time' => $data['start_time'] ?? '',
                    'end_time' => $data['end_time'] ?? '',
                    'location' => $data['location_id'] ? get_the_title($data['location_id']) : '',
                    'location_id' => $data['location_id'] ?? '',
                    'excerpt' => $event->post_excerpt,
                    'status' => $data['status'] ?? '',
                    'price' => $data['price'] ?? '',
                    'ticket_url' => $data['ticket_url'] ?? '',
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
                    
                    <?php if ($show_date && $data['start_date']): ?>
                    <div class="ensemble-meta-item ensemble-date">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php echo $this->format_date($data['start_date'], $data['end_date']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_time && $data['start_time']): ?>
                    <div class="ensemble-meta-item ensemble-time">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php echo $this->format_time($data['start_time'], $data['end_time']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_location && $data['location_id']): 
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
                    
                    <?php if ($show_artist && $data['artist_id']): 
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
                    
                    <?php if ($data['price']): ?>
                    <div class="ensemble-meta-item ensemble-price">
                        <span class="dashicons dashicons-tickets-alt"></span>
                        <span><?php echo esc_html($data['price']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php if ($show_excerpt && $event->post_excerpt): ?>
                <div class="ensemble-event-excerpt">
                    <?php echo wpautop(esc_html($event->post_excerpt)); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <div class="ensemble-event-actions">
                    <?php if ($data['ticket_url']): ?>
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
                    <?php if ($show_date && $data['start_date']): ?>
                        <span class="ensemble-date"><?php echo $this->format_date_short($data['start_date']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($show_time && $data['start_time']): ?>
                        <span class="ensemble-time"><?php echo esc_html($data['start_time']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($show_location && $data['location_id']): 
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
                    
                    <?php if ($show_date && $data['start_date']): ?>
                    <div class="ensemble-meta-box">
                        <div class="ensemble-meta-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="ensemble-meta-content">
                            <label><?php _e('Date', 'ensemble'); ?></label>
                            <strong><?php echo $this->format_date($data['start_date'], $data['end_date']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_time && $data['start_time']): ?>
                    <div class="ensemble-meta-box">
                        <div class="ensemble-meta-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="ensemble-meta-content">
                            <label><?php _e('Time', 'ensemble'); ?></label>
                            <strong><?php echo $this->format_time($data['start_time'], $data['end_time']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_location && $data['location_id']): 
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
                    
                    <?php if ($show_artist && $data['artist_id']): 
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
                    
                    <?php if ($data['price']): ?>
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
                    <?php if ($data['ticket_url']): ?>
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
    private function format_date($start, $end = '') {
        if (!$start) {
            return '';
        }
        
        $start_date = date_i18n('j. F Y', strtotime($start));
        
        if ($end && $end !== $start) {
            $end_date = date_i18n('j. F Y', strtotime($end));
            return $start_date . ' - ' . $end_date;
        }
        
        return $start_date;
    }
    
    /**
     * Format date short
     */
    private function format_date_short($date) {
        if (!$date) {
            return '';
        }
        return date_i18n('j. M Y', strtotime($date));
    }
    
    /**
     * Format time range
     */
    private function format_time($start, $end = '') {
        if (!$start) {
            return '';
        }
        
        if ($end && $end !== $start) {
            return $start . ' - ' . $end;
        }
        
        return $start;
    }
    
    /**
     * Artists List Shortcode
     * Displays a list or grid of artists
     * 
     * Usage: [ensemble_artists layout="grid" columns="3" limit="12"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function artists_list_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'layout' => 'grid',           // grid, list, slider, cards, compact, featured
            'columns' => '3',             // 2, 3, 4 (nur für grid)
            'limit' => '12',              // Anzahl Artists
            'orderby' => 'title',         // title, date, menu_order
            'order' => 'ASC',             // ASC, DESC
            'genre' => '',                // Genre slug(s), comma-separated
            'type' => '',                 // Artist Type slug(s), comma-separated
            'category' => '',             // Alias for type (backward compatibility)
            'show_image' => 'true',       // Bild anzeigen
            'show_bio' => 'true',         // Bio/Excerpt anzeigen
            'show_events' => 'false',     // Kommende Events anzeigen
            'show_link' => 'true',        // Link zum Artist
            'link_text' => 'View Profile', // Link Text
            // Slider options
            'autoplay' => 'false',
            'autoplay_speed' => '5000',
            'loop' => 'false',
            'dots' => 'true',
            'arrows' => 'true',
            'gap' => '24',
        ), $atts, 'ensemble_artists');
        
        // Sanitize
        $layout = sanitize_key($atts['layout']);
        $columns = absint($atts['columns']);
        $limit = absint($atts['limit']);
        $orderby = sanitize_key($atts['orderby']);
        $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';
        $genre = sanitize_text_field($atts['genre']);
        $artist_type = sanitize_text_field($atts['type'] ?: $atts['category']); // type or category alias
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
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
        
        // Query args - for featured, get all artists
        $args = array(
            'post_type' => 'ensemble_artist',
            'posts_per_page' => ($layout === 'featured') ? -1 : $limit,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
        );
        
        // Build tax_query for filtering
        $tax_query = array();
        
        // Genre filter
        if ($genre) {
            $tax_query[] = array(
                'taxonomy' => 'ensemble_genre',
                'field' => 'slug',
                'terms' => array_map('trim', explode(',', $genre)),
            );
        }
        
        // Artist Type filter
        if ($artist_type) {
            $tax_query[] = array(
                'taxonomy' => 'ensemble_artist_type',
                'field' => 'slug',
                'terms' => array_map('trim', explode(',', $artist_type)),
            );
        }
        
        // Add tax_query if any filters
        if (!empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $args['tax_query'] = $tax_query;
        }
        
        // Get artists
        $artists_query = new WP_Query($args);
        
        if (!$artists_query->have_posts()) {
            return '<div class="ensemble-no-results">' . __('No artists found.', 'ensemble') . '</div>';
        }
        
        // Build output
        ob_start();
        
        // ========================================
        // FEATURED LAYOUT - Fullscreen with slider navigation
        // ========================================
        if ($layout === 'featured'):
            $this->render_artists_featured_slider($artists_query->posts, $atts);
            wp_reset_postdata();
            return ob_get_clean();
        endif;
        
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
            
            echo ES_Slider_Renderer::render_wrapper_start('slider', $slider_options, 'artists');
            
            $slide_index = 0;
            while ($artists_query->have_posts()) {
                $artists_query->the_post();
                $artist_id = get_the_ID();
                
                echo ES_Slider_Renderer::render_slide_start($slide_index);
                $this->render_artist_grid_item($artist_id, $atts);
                echo ES_Slider_Renderer::render_slide_end();
                
                $slide_index++;
            }
            
            echo ES_Slider_Renderer::render_wrapper_end();
            
        else:
        // ========================================
        // STANDARD GRID / LIST / CARDS / COMPACT LAYOUT
        // ========================================
        
        $container_class = 'ensemble-artists-list ensemble-layout-' . $layout;
        if (in_array($layout, array('grid', 'cards', 'compact'))) {
            $container_class .= ' ensemble-columns-' . $columns;
        }
        
        echo '<div class="' . esc_attr($container_class) . '">';
        
        while ($artists_query->have_posts()) {
            $artists_query->the_post();
            $artist_id = get_the_ID();
            
            if ($layout === 'list') {
                $this->render_artist_list_item($artist_id, $atts);
            } elseif ($layout === 'compact') {
                $this->render_artist_compact_item($artist_id, $atts);
            } elseif ($layout === 'cards') {
                $this->render_artist_card_item($artist_id, $atts);
            } else {
                $this->render_artist_grid_item($artist_id, $atts);
            }
        }
        
        echo '</div>';
        
        endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Render Artist Grid Item
     */
    private function render_artist_grid_item($artist_id, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        
        $artist_post = get_post($artist_id);
        
        // Get genre from taxonomy (ensemble_genre)
        $genre = '';
        $genre_terms = get_the_terms($artist_id, 'ensemble_genre');
        if ($genre_terms && !is_wp_error($genre_terms)) {
            $genre_names = wp_list_pluck($genre_terms, 'name'); $genre = implode(', ', $genre_names);
        }
        // Fallback to ACF/meta if no taxonomy term
        if (empty($genre) && function_exists('get_field')) {
            $genre = get_field('artist_genre', $artist_id);
        }
        if (empty($genre)) {
            $genre = get_post_meta($artist_id, 'artist_genre', true);
        }
        
        // Get external link (try ACF first, then meta)
        $external_url = '';
        if (function_exists('get_field')) {
            $external_url = get_field('artist_website', $artist_id);
        }
        if (empty($external_url)) {
            $external_url = get_post_meta($artist_id, 'artist_website', true);
        }
        $artist_permalink = !empty($external_url) ? $external_url : get_permalink($artist_id);
        $link_target = !empty($external_url) ? '_blank' : '_self';
        
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('artist-card.php', $active_set);
            
            if ($template_path && file_exists($template_path)) {
                // Prepare artist data for template
                $artist = array(
                    'id' => $artist_id,
                    'name' => $artist_post->post_title,
                    'title' => $artist_post->post_title,
                    'permalink' => $artist_permalink,
                    'link_target' => $link_target,
                    'image' => get_the_post_thumbnail_url($artist_id, 'large'),
                    'featured_image' => get_the_post_thumbnail_url($artist_id, 'large'),
                    'genre' => $genre,
                    'excerpt' => $artist_post->post_excerpt,
                );
                
                $shortcode_atts = $atts;
                include $template_path;
                return;
            }
        }
        
        // Fallback: Default template
        ?>
        <div class="ensemble-artist-card">
            
            <?php if ($show_image): ?>
            <div class="ensemble-artist-image">
                <?php if (has_post_thumbnail($artist_id)): ?>
                    <a href="<?php echo get_permalink($artist_id); ?>">
                        <?php echo get_the_post_thumbnail($artist_id, 'medium'); ?>
                    </a>
                <?php else: ?>
                    <div class="ensemble-artist-placeholder">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-artist-content">
                
                <h3 class="ensemble-artist-title">
                    <a href="<?php echo get_permalink($artist_id); ?>">
                        <?php echo esc_html($artist_post->post_title); ?>
                    </a>
                </h3>
                
                <?php if ($genre): ?>
                <div class="ensemble-artist-genre">
                    <?php echo esc_html($genre); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_bio && $artist_post->post_excerpt): ?>
                <div class="ensemble-artist-bio">
                    <?php echo wpautop(wp_trim_words($artist_post->post_excerpt, 20)); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_events): ?>
                    <?php $this->render_artist_upcoming_events($artist_id); ?>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <div class="ensemble-artist-actions">
                    <a href="<?php echo get_permalink($artist_id); ?>" 
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
     * Render Artist List Item
     */
    private function render_artist_list_item($artist_id, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        
        $artist = get_post($artist_id);
        $genre = get_post_meta($artist_id, 'es_artist_genre', true);
        ?>
        <div class="ensemble-artist-list-item">
            
            <?php if ($show_image): ?>
            <div class="ensemble-artist-thumb">
                <?php if (has_post_thumbnail($artist_id)): ?>
                    <a href="<?php echo get_permalink($artist_id); ?>">
                        <?php echo get_the_post_thumbnail($artist_id, 'thumbnail'); ?>
                    </a>
                <?php else: ?>
                    <div class="ensemble-artist-placeholder-small">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-artist-list-content">
                
                <div class="ensemble-artist-header">
                    <h4 class="ensemble-artist-title">
                        <a href="<?php echo get_permalink($artist_id); ?>">
                            <?php echo esc_html($artist->post_title); ?>
                        </a>
                    </h4>
                    
                    <?php if ($genre): ?>
                    <span class="ensemble-artist-genre"><?php echo esc_html($genre); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($show_bio && $artist->post_excerpt): ?>
                <div class="ensemble-artist-bio">
                    <?php echo wpautop(wp_trim_words($artist->post_excerpt, 30)); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_events): ?>
                    <?php $this->render_artist_upcoming_events($artist_id); ?>
                <?php endif; ?>
                
            </div>
            
            <?php if ($show_link): ?>
            <div class="ensemble-artist-list-action">
                <a href="<?php echo get_permalink($artist_id); ?>" 
                   class="ensemble-btn ensemble-btn-secondary">
                    <?php echo esc_html($link_text); ?>
                </a>
            </div>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render Artist Compact Item (small avatar + name)
     */
    private function render_artist_compact_item($artist_id, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $artist = get_post($artist_id);
        $genre = get_post_meta($artist_id, 'es_artist_genre', true);
        ?>
        <a href="<?php echo get_permalink($artist_id); ?>" class="ensemble-artist-compact">
            <?php if ($show_image): ?>
            <div class="ensemble-artist-avatar">
                <?php if (has_post_thumbnail($artist_id)): ?>
                    <?php echo get_the_post_thumbnail($artist_id, 'thumbnail'); ?>
                <?php else: ?>
                    <span class="dashicons dashicons-admin-users"></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="ensemble-artist-compact-info">
                <span class="ensemble-artist-compact-name"><?php echo esc_html($artist->post_title); ?></span>
                <?php if ($genre): ?>
                <span class="ensemble-artist-compact-genre"><?php echo esc_html($genre); ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php
    }
    
    /**
     * Render Artist Card Item (large portrait cards)
     */
    private function render_artist_card_item($artist_id, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        
        $artist_post = get_post($artist_id);
        
        // Get genre from taxonomy (ensemble_genre)
        $genre = '';
        $genre_terms = get_the_terms($artist_id, 'ensemble_genre');
        if ($genre_terms && !is_wp_error($genre_terms)) {
            $genre_names = wp_list_pluck($genre_terms, 'name'); $genre = implode(', ', $genre_names);
        }
        // Fallback to ACF/meta if no taxonomy term
        if (empty($genre) && function_exists('get_field')) {
            $genre = get_field('artist_genre', $artist_id);
        }
        if (empty($genre)) {
            $genre = get_post_meta($artist_id, 'artist_genre', true);
        }
        
        // Get external link (try ACF first, then meta)
        $external_url = '';
        if (function_exists('get_field')) {
            $external_url = get_field('artist_website', $artist_id);
        }
        if (empty($external_url)) {
            $external_url = get_post_meta($artist_id, 'artist_website', true);
        }
        $artist_permalink = !empty($external_url) ? $external_url : get_permalink($artist_id);
        $link_target = !empty($external_url) ? '_blank' : '_self';
        
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('artist-card-full.php', $active_set);
            
            if ($template_path && file_exists($template_path)) {
                // Prepare artist data for template
                $artist = array(
                    'id' => $artist_id,
                    'name' => $artist_post->post_title,
                    'title' => $artist_post->post_title,
                    'permalink' => $artist_permalink,
                    'link_target' => $link_target,
                    'image' => get_the_post_thumbnail_url($artist_id, 'large'),
                    'featured_image' => get_the_post_thumbnail_url($artist_id, 'large'),
                    'genre' => $genre,
                    'excerpt' => $artist_post->post_excerpt,
                );
                
                $shortcode_atts = $atts;
                include $template_path;
                return;
            }
        }
        
        // Fallback: Default template
        ?>
        <div class="ensemble-artist-card ensemble-artist-card--large">
            <?php if ($show_image): ?>
            <div class="ensemble-artist-portrait">
                <?php if (has_post_thumbnail($artist_id)): ?>
                    <a href="<?php echo esc_url($artist_permalink); ?>" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
                        <?php echo get_the_post_thumbnail($artist_id, 'large'); ?>
                    </a>
                <?php else: ?>
                    <div class="ensemble-artist-placeholder-large">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-artist-card-content">
                <h3 class="ensemble-artist-title">
                    <a href="<?php echo esc_url($artist_permalink); ?>" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html($artist_post->post_title); ?></a>
                </h3>
                
                <?php if ($genre): ?>
                <div class="ensemble-artist-genre"><?php echo esc_html($genre); ?></div>
                <?php endif; ?>
                
                <?php if ($show_bio && $artist_post->post_excerpt): ?>
                <div class="ensemble-artist-bio"><?php echo wp_trim_words($artist_post->post_excerpt, 25, '...'); ?></div>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <a href="<?php echo esc_url($artist_permalink); ?>" class="ensemble-btn ensemble-btn-primary" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
                    <?php echo esc_html($link_text); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
        // Query upcoming events for this artist
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'es_event_artist',
                    'value' => $artist_id,
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
        
        echo '<div class="ensemble-artist-events">';
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
    
    /**
     * Single Artist Shortcode
     * Embeds a single artist profile anywhere
     * 
     * Usage: [ensemble_artist id="123" layout="card"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function single_artist_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 0,                      // Artist Post ID (required)
            'layout' => 'card',             // card, compact, full
            'show_image' => 'true',         // Show featured image
            'show_genre' => 'true',         // Show genre
            'show_bio' => 'true',           // Show bio/excerpt
            'show_events' => 'true',        // Show upcoming events
            'show_link' => 'true',          // Show "View Profile" link
            'link_text' => 'View Profile',  // Link button text
        ), $atts, 'ensemble_artist');
        
        // Sanitize
        $artist_id = absint($atts['id']);
        $layout = sanitize_key($atts['layout']);
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_genre = filter_var($atts['show_genre'], FILTER_VALIDATE_BOOLEAN);
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        $template = $this->get_effective_template($atts['template']);
        
        // Apply template if specified
        $this->apply_shortcode_template($template);
        
        // Validate artist ID
        if (!$artist_id) {
            return '<div class="ensemble-error">Artist ID is required</div>';
        }
        
        // Get artist post
        $artist = get_post($artist_id);
        
        if (!$artist || $artist->post_type !== 'ensemble_artist') {
            return '<div class="ensemble-error">Artist not found</div>';
        }
        
        // Get artist meta
        $genre = get_post_meta($artist_id, 'es_artist_genre', true);
        $website = get_post_meta($artist_id, 'es_artist_website', true);
        $social = array(
            'facebook' => get_post_meta($artist_id, 'es_artist_facebook', true),
            'instagram' => get_post_meta($artist_id, 'es_artist_instagram', true),
            'twitter' => get_post_meta($artist_id, 'es_artist_twitter', true),
            'soundcloud' => get_post_meta($artist_id, 'es_artist_soundcloud', true),
        );
        
        // Build HTML based on layout
        ob_start();
        
        switch ($layout) {
            case 'compact':
                $this->render_single_artist_compact($artist, $genre, $atts);
                break;
                
            case 'full':
                $this->render_single_artist_full($artist, $genre, $website, $social, $atts);
                break;
            
            case 'featured':
            case 'featuredslider': // Legacy support
                // Auto-detect: show slider if more than 1 artist exists
                $artist_count = wp_count_posts('ensemble_artist');
                $has_multiple_artists = ($artist_count->publish > 1);
                $this->render_single_artist_featured($artist, $genre, $website, $social, $atts, $has_multiple_artists);
                break;
                
            case 'card':
            default:
                $this->render_single_artist_card($artist, $genre, $atts);
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render Single Artist Card Layout
     */
    private function render_single_artist_card($artist_post, $genre, $atts) {
        // Try to load template from active Layout-Set
        if (class_exists('ES_Layout_Sets') && class_exists('ES_Template_Loader')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $template_path = ES_Template_Loader::locate_template('artist-card.php', $active_set);
            
            if ($template_path && file_exists($template_path)) {
                // Convert shortcode data to template format
                $artist = array(
                    'id' => $artist_post->ID,
                    'title' => $artist_post->post_title,
                    'permalink' => get_permalink($artist_post->ID),
                    'featured_image' => get_the_post_thumbnail_url($artist_post->ID, 'large'),
                    'genre' => $genre,
                    'excerpt' => $artist_post->post_excerpt,
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
        $show_genre = filter_var($atts['show_genre'], FILTER_VALIDATE_BOOLEAN);
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        ?>
        <div class="ensemble-single-artist ensemble-layout-card">
            
            <?php if ($show_image): ?>
            <div class="ensemble-artist-image">
                <?php if (has_post_thumbnail($artist_post->ID)): ?>
                    <?php echo get_the_post_thumbnail($artist_post->ID, 'large'); ?>
                <?php else: ?>
                    <div class="ensemble-artist-placeholder">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-artist-content">
                
                <h3 class="ensemble-artist-title">
                    <?php echo esc_html($artist_post->post_title); ?>
                </h3>
                
                <?php if ($show_genre && $genre): ?>
                <div class="ensemble-artist-genre">
                    <?php echo esc_html($genre); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_bio && $artist_post->post_excerpt): ?>
                <div class="ensemble-artist-bio">
                    <?php echo wpautop($artist_post->post_excerpt); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_events): ?>
                    <?php $this->render_artist_upcoming_events($artist_post->ID); ?>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <div class="ensemble-artist-actions">
                    <a href="<?php echo get_permalink($artist_post->ID); ?>" 
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
     * Render Single Artist Compact Layout
     */
    private function render_single_artist_compact($artist, $genre, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_genre = filter_var($atts['show_genre'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        ?>
        <div class="ensemble-single-artist ensemble-layout-compact">
            
            <?php if ($show_image): ?>
            <div class="ensemble-artist-thumb">
                <?php if (has_post_thumbnail($artist->ID)): ?>
                    <a href="<?php echo get_permalink($artist->ID); ?>">
                        <?php echo get_the_post_thumbnail($artist->ID, 'thumbnail'); ?>
                    </a>
                <?php else: ?>
                    <div class="ensemble-artist-placeholder-small">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-compact-header">
                <h4 class="ensemble-artist-title">
                    <?php echo esc_html($artist->post_title); ?>
                </h4>
                
                <?php if ($show_genre && $genre): ?>
                <span class="ensemble-artist-genre"><?php echo esc_html($genre); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($show_link): ?>
            <a href="<?php echo get_permalink($artist->ID); ?>" class="ensemble-compact-link">
                <?php echo esc_html($atts['link_text']); ?> →
            </a>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Render Single Artist Full Layout
     */
    private function render_single_artist_full($artist, $genre, $website, $social, $atts) {
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_genre = filter_var($atts['show_genre'], FILTER_VALIDATE_BOOLEAN);
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        ?>
        <div class="ensemble-single-artist ensemble-layout-full">
            
            <?php if ($show_image && has_post_thumbnail($artist->ID)): ?>
            <div class="ensemble-artist-header-image">
                <?php echo get_the_post_thumbnail($artist->ID, 'full'); ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-artist-full-content">
                
                <h2 class="ensemble-artist-title">
                    <?php echo esc_html($artist->post_title); ?>
                </h2>
                
                <?php if ($show_genre && $genre): ?>
                <div class="ensemble-artist-genre-large">
                    <?php echo esc_html($genre); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_bio): ?>
                <div class="ensemble-artist-description">
                    <?php echo wpautop($artist->post_content); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($website || array_filter($social)): ?>
                <div class="ensemble-artist-social">
                    <h4><?php _e('Connect', 'ensemble'); ?></h4>
                    <div class="ensemble-social-links">
                        
                        <?php if ($website): ?>
                        <a href="<?php echo esc_url($website); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="ensemble-social-link">
                            <span class="dashicons dashicons-admin-site"></span>
                            <?php _e('Website', 'ensemble'); ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($social['facebook']): ?>
                        <a href="<?php echo esc_url($social['facebook']); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="ensemble-social-link">
                            <span class="dashicons dashicons-facebook"></span>
                            Facebook
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($social['instagram']): ?>
                        <a href="<?php echo esc_url($social['instagram']); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="ensemble-social-link">
                            <span class="dashicons dashicons-instagram"></span>
                            Instagram
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($social['twitter']): ?>
                        <a href="<?php echo esc_url($social['twitter']); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="ensemble-social-link">
                            <span class="dashicons dashicons-twitter"></span>
                            Twitter
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($social['soundcloud']): ?>
                        <a href="<?php echo esc_url($social['soundcloud']); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="ensemble-social-link">
                            <span class="dashicons dashicons-format-audio"></span>
                            SoundCloud
                        </a>
                        <?php endif; ?>
                        
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($show_events): ?>
                <div class="ensemble-artist-events-full">
                    <?php $this->render_artist_upcoming_events($artist->ID); ?>
                </div>
                <?php endif; ?>
                
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Render Single Artist Featured Layout
     * True fullscreen with split layout (Name left, Info right)
     * 
     * @param WP_Post $artist Artist post object
     * @param mixed $genre Genre data
     * @param string $website Website URL
     * @param array $social Social links
     * @param array $atts Shortcode attributes
     * @param bool $with_slider Show artist slider for navigation
     */
    private function render_single_artist_featured($artist, $genre, $website, $social, $atts, $with_slider = false) {
        $artist_id = $artist->ID;
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_genre = filter_var($atts['show_genre'], FILTER_VALIDATE_BOOLEAN);
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        
        // Load artist data
        $artist_data = function_exists('es_load_artist_data') ? es_load_artist_data($artist_id) : array();
        
        // Load social links - try ACF fields first, then post_meta (same as original)
        $website = '';
        $social = array();
        
        // Try ACF first
        if (function_exists('get_field')) {
            $website = get_field('artist_website', $artist_id);
            $social['facebook'] = get_field('artist_facebook', $artist_id);
            $social['instagram'] = get_field('artist_instagram', $artist_id);
            $social['twitter'] = get_field('artist_twitter', $artist_id);
            $social['soundcloud'] = get_field('artist_soundcloud', $artist_id);
            $social['spotify'] = get_field('artist_spotify', $artist_id);
            $social['youtube'] = get_field('artist_youtube', $artist_id);
            $social['tiktok'] = get_field('artist_tiktok', $artist_id);
            $social['bandcamp'] = get_field('artist_bandcamp', $artist_id);
            $social['mixcloud'] = get_field('artist_mixcloud', $artist_id);
        }
        
        // Fallback to post_meta
        if (empty($website)) {
            $website = get_post_meta($artist_id, 'artist_website', true);
        }
        if (empty(array_filter($social))) {
            $social['facebook'] = get_post_meta($artist_id, 'artist_facebook', true);
            $social['instagram'] = get_post_meta($artist_id, 'artist_instagram', true);
            $social['twitter'] = get_post_meta($artist_id, 'artist_twitter', true);
            $social['soundcloud'] = get_post_meta($artist_id, 'artist_soundcloud', true);
            $social['spotify'] = get_post_meta($artist_id, 'artist_spotify', true);
            $social['youtube'] = get_post_meta($artist_id, 'artist_youtube', true);
            $social['tiktok'] = get_post_meta($artist_id, 'artist_tiktok', true);
            $social['bandcamp'] = get_post_meta($artist_id, 'artist_bandcamp', true);
            $social['mixcloud'] = get_post_meta($artist_id, 'artist_mixcloud', true);
        }
        
        // Filter empty social links
        $social = array_filter($social);
        
        // Hero video data
        $hero_video_url = get_post_meta($artist_id, '_artist_hero_video_url', true);
        
        // Detect video type
        $video_type = '';
        $video_embed_url = '';
        if (!empty($hero_video_url)) {
            if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $hero_video_url, $matches) || 
                preg_match('/youtu\.be\/([^?]+)/', $hero_video_url, $matches)) {
                $video_type = 'youtube';
                $video_id = $matches[1];
                $video_embed_url = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&mute=1&loop=1&playlist=' . $video_id . '&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1';
            } elseif (preg_match('/vimeo\.com\/(\d+)/', $hero_video_url, $matches)) {
                $video_type = 'vimeo';
                $video_id = $matches[1];
                $video_embed_url = 'https://player.vimeo.com/video/' . $video_id . '?autoplay=1&muted=1&loop=1&background=1&autopause=0';
            } elseif (preg_match('/\.(mp4|webm|ogg)$/i', $hero_video_url)) {
                $video_type = 'mp4';
                $video_embed_url = $hero_video_url;
            }
        }
        
        // Get layout set for styling
        $active_set = class_exists('ES_Layout_Sets') ? ES_Layout_Sets::get_active_set() : 'modern';
        $layout_config = array();
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $active_set . '/config.php')) {
            $layout_config = include ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $active_set . '/config.php';
        }
        $is_dark_mode = ($layout_config['default_mode'] ?? 'dark') === 'dark';
        $mode_class = $is_dark_mode ? 'es-featured-dark' : 'es-featured-light';
        
        // Get upcoming events - event_artist can be array, use multiple queries
        $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        $today = date('Y-m-d');
        
        // Use same query pattern as get_artist_event_count (which works!)
        global $wpdb;
        
        // Get the correct meta key for artist field
        $artist_meta_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('artist') : 'event_artist';
        
        // Get all event IDs for this artist (same pattern as working count function)
        $all_event_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = %s 
            AND p.post_type = %s
            AND p.post_status = 'publish'
            AND (
                pm.meta_value LIKE %s
                OR pm.meta_value LIKE %s
                OR pm.meta_value = %s
            )
        ", 
            $artist_meta_key,
            $event_post_type,
            '%i:' . $artist_id . ';%',
            '%s:' . strlen($artist_id) . ':"' . $artist_id . '";%',
            $artist_id
        ));
        
        $upcoming_events = null;
        $showing_past = false;
        
        if (!empty($all_event_ids)) {
            // Get all events for this artist
            $all_events_query = new WP_Query(array(
                'post_type' => $event_post_type,
                'post__in' => $all_event_ids,
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ));
            
            // Separate into upcoming and past
            $upcoming_ids = array();
            $past_ids = array();
            
            if ($all_events_query->have_posts()) {
                while ($all_events_query->have_posts()) {
                    $all_events_query->the_post();
                    $evt_id = get_the_ID();
                    
                    // Use unified helper for date retrieval
                    $event_date = function_exists('ensemble_get_event_meta') 
                        ? ensemble_get_event_meta($evt_id, 'start_date')
                        : get_post_meta($evt_id, 'event_date', true);
                    
                    if (!empty($event_date) && $event_date >= $today) {
                        $upcoming_ids[$evt_id] = $event_date;
                    } else {
                        $past_ids[$evt_id] = $event_date ?: '1970-01-01';
                    }
                }
                wp_reset_postdata();
            }
            
            // Sort and get upcoming events
            if (!empty($upcoming_ids)) {
                asort($upcoming_ids); // Sort by date ASC
                $sorted_upcoming = array_slice(array_keys($upcoming_ids), 0, 3);
                
                $upcoming_events = new WP_Query(array(
                    'post_type' => $event_post_type,
                    'post__in' => $sorted_upcoming,
                    'orderby' => 'post__in',
                    'posts_per_page' => 3,
                ));
            }
            
            // If no upcoming, show past events
            if (!$upcoming_events || !$upcoming_events->have_posts()) {
                if (!empty($past_ids)) {
                    arsort($past_ids); // Sort by date DESC
                    $sorted_past = array_slice(array_keys($past_ids), 0, 3);
                    
                    $upcoming_events = new WP_Query(array(
                        'post_type' => $event_post_type,
                        'post__in' => $sorted_past,
                        'orderby' => 'post__in',
                        'posts_per_page' => 3,
                    ));
                    $showing_past = true;
                }
            }
        }
        
        // Fallback empty query
        if (!$upcoming_events) {
            $upcoming_events = new WP_Query(array('post__in' => array(0)));
        }
        
        // Get other artists for slider
        $other_artists = array();
        if ($with_slider) {
            $artists_query = new WP_Query(array(
                'post_type' => 'ensemble_artist',
                'posts_per_page' => 20,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
            ));
            $other_artists = $artists_query->posts;
        }
        
        // Has bio content?
        $has_bio = $show_bio && ($artist->post_content || !empty($artist_data['references']));
        
        // Enqueue CSS
        wp_enqueue_style('ensemble-featured-artist', ENSEMBLE_PLUGIN_URL . 'assets/css/single-artist-featured.css', array(), ENSEMBLE_VERSION);
        
        // Unique ID for this instance
        $instance_id = 'es-featured-' . $artist_id . '-' . wp_rand(1000, 9999);
        
        // Slider class
        $slider_class = $with_slider ? 'has-slider' : '';
        ?>
        
        <div id="<?php echo esc_attr($instance_id); ?>" class="es-featured-artist <?php echo esc_attr($mode_class); ?> es-set-<?php echo esc_attr($active_set); ?> <?php echo esc_attr($slider_class); ?>" data-artist-id="<?php echo esc_attr($artist_id); ?>">
            
            <!-- FULLSCREEN HERO -->
            <section class="es-featured-hero">
                
                <!-- Background Media -->
                <div class="es-featured-hero-media">
                    <?php if ($video_type === 'mp4'): ?>
                        <video class="es-featured-hero-video" autoplay muted loop playsinline>
                            <source src="<?php echo esc_url($video_embed_url); ?>" type="video/mp4">
                        </video>
                    <?php elseif ($video_type === 'youtube' || $video_type === 'vimeo'): ?>
                        <div class="es-featured-hero-video-wrapper">
                            <iframe class="es-featured-hero-video" 
                                    src="<?php echo esc_url($video_embed_url); ?>" 
                                    frameborder="0" 
                                    allow="autoplay; fullscreen" 
                                    allowfullscreen></iframe>
                        </div>
                    <?php elseif ($show_image && has_post_thumbnail($artist_id)): ?>
                        <div class="es-featured-hero-image">
                            <?php echo get_the_post_thumbnail($artist_id, 'full'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="es-featured-hero-gradient"></div>
                </div>
                
                <!-- Hero Content: Split Layout -->
                <div class="es-featured-hero-content">
                    
                    <!-- LEFT SIDE: Name + Social -->
                    <div class="es-featured-left">
                        
                        <h1 class="es-featured-title"><?php echo esc_html($artist->post_title); ?></h1>
                        
                        <!-- Social Icons -->
                        <div class="es-featured-hero-social"<?php echo (empty($website) && empty($social)) ? ' style="display:none"' : ''; ?>>
                            <?php if (!empty($website)): ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="Website">
                                <?php echo ES_Icons::get('website'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social['facebook'])): ?>
                            <a href="<?php echo esc_url($social['facebook']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="Facebook">
                                <?php echo ES_Icons::get('facebook'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social['instagram'])): ?>
                            <a href="<?php echo esc_url($social['instagram']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="Instagram">
                                <?php echo ES_Icons::get('instagram'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social['twitter'])): ?>
                            <a href="<?php echo esc_url($social['twitter']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="X/Twitter">
                                <?php echo ES_Icons::get('twitter'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social['soundcloud'])): ?>
                            <a href="<?php echo esc_url($social['soundcloud']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="SoundCloud">
                                <?php echo ES_Icons::get('soundcloud'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social['spotify'])): ?>
                            <a href="<?php echo esc_url($social['spotify']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="Spotify">
                                <?php echo ES_Icons::get('spotify'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social['youtube'])): ?>
                            <a href="<?php echo esc_url($social['youtube']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="YouTube">
                                <?php echo ES_Icons::get('youtube'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social['tiktok'])): ?>
                            <a href="<?php echo esc_url($social['tiktok']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="TikTok">
                                <?php echo ES_Icons::get('tiktok'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social['bandcamp'])): ?>
                            <a href="<?php echo esc_url($social['bandcamp']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="Bandcamp">
                                <?php echo ES_Icons::get('bandcamp'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($social['mixcloud'])): ?>
                            <a href="<?php echo esc_url($social['mixcloud']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="Mixcloud">
                                <?php echo ES_Icons::get('mixcloud'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                    <!-- RIGHT SIDE: Info Block -->
                    <div class="es-featured-right">
                        
                        <!-- Events -->
                        <?php if ($show_events && $upcoming_events->have_posts()): ?>
                        <div class="es-featured-info-section">
                            <h3 class="es-featured-info-title">
                                <?php echo $showing_past ? __('Letzte Events', 'ensemble') : __('Nächste Events', 'ensemble'); ?>
                            </h3>
                            <div class="es-featured-events-compact">
                                <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); 
                                    $evt_id = get_the_ID();
                                    $start_date = function_exists('ensemble_get_event_meta') 
                                        ? ensemble_get_event_meta($evt_id, 'start_date')
                                        : get_post_meta($evt_id, 'event_date', true);
                                    $evt_timestamp = $start_date ? strtotime($start_date) : time();
                                ?>
                                <a href="<?php the_permalink(); ?>" class="es-featured-event-row">
                                    <div class="es-featured-event-date-small">
                                        <span class="day"><?php echo date_i18n('d', $evt_timestamp); ?></span>
                                        <span class="month"><?php echo date_i18n('M', $evt_timestamp); ?></span>
                                    </div>
                                    <h4><?php the_title(); ?></h4>
                                </a>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- More Info Button (always visible) -->
                        <button type="button" class="es-featured-more-btn">
                            <?php _e('Mehr Info', 'ensemble'); ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>
                        
                    </div>
                    
                </div>
                
            </section>
            
            <!-- Navigation Arrows (for slider variant) -->
            <?php if ($with_slider && count($other_artists) > 1): ?>
            <div class="es-featured-nav es-featured-nav-prev">
                <button type="button" class="es-featured-nav-btn" data-direction="prev" title="<?php esc_attr_e('Vorheriger', 'ensemble'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </button>
            </div>
            <div class="es-featured-nav es-featured-nav-next">
                <button type="button" class="es-featured-nav-btn" data-direction="next" title="<?php esc_attr_e('Nächster', 'ensemble'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Artist Slider (for slider variant) -->
            <?php if ($with_slider && !empty($other_artists)): ?>
            <section class="es-featured-slider-section">
                <div class="es-featured-slider">
                    <?php foreach ($other_artists as $other_artist): 
                        $is_current = ($other_artist->ID === $artist_id);
                    ?>
                    <a href="<?php echo get_permalink($other_artist->ID); ?>" 
                       class="es-featured-slider-item <?php echo $is_current ? 'is-active' : ''; ?>"
                       data-artist-id="<?php echo esc_attr($other_artist->ID); ?>"
                       title="<?php echo esc_attr($other_artist->post_title); ?>">
                        <div class="es-featured-slider-image">
                            <?php if (has_post_thumbnail($other_artist->ID)): ?>
                                <?php echo get_the_post_thumbnail($other_artist->ID, 'thumbnail'); ?>
                            <?php else: ?>
                                <div class="es-featured-slider-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Overlay (for panel) -->
            <div class="es-featured-overlay"></div>
            
            <!-- Slide-in Panel (More Info) -->
            <div class="es-featured-panel">
                <div class="es-featured-panel-header">
                    <h2 class="es-featured-panel-title"><?php echo esc_html($artist->post_title); ?></h2>
                    <button type="button" class="es-featured-panel-close">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="es-featured-panel-content">
                    <?php if (!empty($artist_data['references'])): ?>
                    <blockquote class="es-featured-references">
                        <?php echo esc_html($artist_data['references']); ?>
                    </blockquote>
                    <?php endif; ?>
                    
                    <?php if ($artist->post_content): ?>
                    <div class="es-featured-description">
                        <?php echo wpautop($artist->post_content); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_genre && !empty($genre)): ?>
                    <div class="es-featured-panel-genres">
                        <h3 class="es-featured-gallery-title"><?php _e('Genres', 'ensemble'); ?></h3>
                        <div class="es-featured-genres-compact">
                            <?php 
                            $genres = is_array($genre) ? $genre : explode(',', $genre);
                            foreach ($genres as $g): 
                                $g = is_object($g) ? $g->name : trim($g);
                                if (!empty($g)):
                            ?>
                            <span class="es-featured-genre-tag"><?php echo esc_html($g); ?></span>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($website) || !empty($social)): ?>
                    <div class="es-featured-panel-social-section">
                        <h3 class="es-featured-gallery-title"><?php _e('Links', 'ensemble'); ?></h3>
                        <div class="es-featured-panel-social">
                            <?php if (!empty($website)): ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="Website">
                                <?php echo ES_Icons::get('website'); ?>
                            </a>
                            <?php endif; ?>
                            <?php foreach ($social as $platform => $url): if (!empty($url)): ?>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="<?php echo esc_attr(ucfirst($platform)); ?>">
                                <?php echo ES_Icons::get($platform); ?>
                            </a>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Load gallery
                    $gallery_ids = array();
                    if (function_exists('get_field')) {
                        $gallery_ids = get_field('artist_gallery', $artist_id);
                    }
                    if (empty($gallery_ids)) {
                        $gallery_ids = get_post_meta($artist_id, 'artist_gallery', true);
                    }
                    
                    if (!empty($gallery_ids) && is_array($gallery_ids)): 
                    ?>
                    <div class="es-featured-gallery">
                        <h3 class="es-featured-gallery-title"><?php _e('Galerie', 'ensemble'); ?></h3>
                        <div class="es-featured-gallery-grid">
                            <?php foreach ($gallery_ids as $img_id): 
                                $img_url = wp_get_attachment_image_url($img_id, 'medium');
                                $img_full = wp_get_attachment_image_url($img_id, 'full');
                                if ($img_url):
                            ?>
                            <a href="<?php echo esc_url($img_full); ?>" class="es-featured-gallery-item" data-lightbox="gallery" data-index="<?php echo $gallery_index ?? 0; ?>">
                                <img src="<?php echo esc_url($img_url); ?>" alt="">
                            </a>
                            <?php $gallery_index = isset($gallery_index) ? $gallery_index + 1 : 1; endif; endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($artist->post_content) && empty($artist_data['references']) && empty($gallery_ids) && empty($genre)): ?>
                    <p class="es-featured-no-content"><?php _e('Keine weiteren Informationen verfügbar.', 'ensemble'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Lightbox -->
            <div class="es-featured-lightbox" id="<?php echo esc_attr($instance_id); ?>-lightbox">
                <div class="es-featured-lightbox-content">
                    <button type="button" class="es-featured-lightbox-close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                    <button type="button" class="es-featured-lightbox-nav es-featured-lightbox-prev">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </button>
                    <img src="" alt="">
                    <button type="button" class="es-featured-lightbox-nav es-featured-lightbox-next">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </button>
                </div>
            </div>
            
        </div>
        
        <script>
        (function() {
            var container = document.getElementById('<?php echo esc_js($instance_id); ?>');
            if (!container) return;
            
            // Add body class for fullscreen takeover
            document.body.classList.add('es-featured-active');
            
            // Panel functionality - find elements within container
            var panel = container.querySelector('.es-featured-panel');
            var overlay = container.querySelector('.es-featured-overlay');
            var moreBtn = container.querySelector('.es-featured-more-btn');
            var closeBtn = container.querySelector('.es-featured-panel-close');
            
            // Lightbox functionality
            var lightbox = document.getElementById('<?php echo esc_js($instance_id); ?>-lightbox');
            var lightboxImg = lightbox ? lightbox.querySelector('img') : null;
            var lightboxClose = lightbox ? lightbox.querySelector('.es-featured-lightbox-close') : null;
            var lightboxPrev = lightbox ? lightbox.querySelector('.es-featured-lightbox-prev') : null;
            var lightboxNext = lightbox ? lightbox.querySelector('.es-featured-lightbox-next') : null;
            var galleryImages = [];
            var currentLightboxIndex = 0;
            
            function openLightbox(index) {
                if (!lightbox || galleryImages.length === 0) return;
                currentLightboxIndex = index;
                lightboxImg.src = galleryImages[index];
                lightbox.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }
            
            function closeLightbox() {
                if (!lightbox) return;
                lightbox.classList.remove('is-open');
                document.body.style.overflow = '';
            }
            
            function nextLightbox() {
                currentLightboxIndex = (currentLightboxIndex + 1) % galleryImages.length;
                lightboxImg.src = galleryImages[currentLightboxIndex];
            }
            
            function prevLightbox() {
                currentLightboxIndex = (currentLightboxIndex - 1 + galleryImages.length) % galleryImages.length;
                lightboxImg.src = galleryImages[currentLightboxIndex];
            }
            
            // Collect gallery images and bind click events
            function initGallery() {
                galleryImages = [];
                var items = container.querySelectorAll('.es-featured-gallery-item[data-lightbox]');
                items.forEach(function(item, index) {
                    galleryImages.push(item.href);
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        openLightbox(index);
                    });
                });
            }
            
            // Initialize gallery
            initGallery();
            
            // Lightbox controls
            if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
            if (lightboxPrev) lightboxPrev.addEventListener('click', prevLightbox);
            if (lightboxNext) lightboxNext.addEventListener('click', nextLightbox);
            if (lightbox) {
                lightbox.addEventListener('click', function(e) {
                    if (e.target === lightbox) closeLightbox();
                });
            }
            
            // Keyboard navigation for lightbox
            document.addEventListener('keydown', function(e) {
                if (!lightbox || !lightbox.classList.contains('is-open')) return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') prevLightbox();
                if (e.key === 'ArrowRight') nextLightbox();
            });
            
            function openPanel() {
                if (panel) panel.classList.add('is-open');
                if (overlay) overlay.classList.add('is-visible');
                // Re-init gallery when panel opens (for dynamically loaded content)
                setTimeout(initGallery, 100);
            }
            
            function closePanel() {
                if (panel) panel.classList.remove('is-open');
                if (overlay) overlay.classList.remove('is-visible');
            }
            
            // Bind More Info button
            if (moreBtn) {
                moreBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openPanel();
                });
            }
            
            // Bind Close button
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closePanel();
                });
            }
            
            // Bind overlay click
            if (overlay) {
                overlay.addEventListener('click', closePanel);
            }
            
            // ESC key to close panel
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePanel();
                }
            });
            
            // Slider drag functionality
            var slider = container.querySelector('.es-featured-slider');
            if (slider) {
                var isDown = false, startX, scrollLeft;
                
                slider.addEventListener('mousedown', function(e) {
                    isDown = true;
                    slider.classList.add('is-dragging');
                    startX = e.pageX - slider.offsetLeft;
                    scrollLeft = slider.scrollLeft;
                });
                
                slider.addEventListener('mouseleave', function() {
                    isDown = false;
                    slider.classList.remove('is-dragging');
                });
                
                slider.addEventListener('mouseup', function() {
                    isDown = false;
                    slider.classList.remove('is-dragging');
                });
                
                slider.addEventListener('mousemove', function(e) {
                    if (!isDown) return;
                    e.preventDefault();
                    var x = e.pageX - slider.offsetLeft;
                    var walk = (x - startX) * 2;
                    slider.scrollLeft = scrollLeft - walk;
                });
            }
            
            // Navigation arrows (for slider variant)
            var navBtns = container.querySelectorAll('.es-featured-nav-btn');
            var sliderItems = container.querySelectorAll('.es-featured-slider-item');
            var currentIndex = 0;
            
            // Find current artist index
            sliderItems.forEach(function(item, index) {
                if (item.classList.contains('is-active')) {
                    currentIndex = index;
                }
            });
            
            navBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var direction = this.getAttribute('data-direction');
                    var newIndex = currentIndex;
                    
                    if (direction === 'prev') {
                        newIndex = currentIndex > 0 ? currentIndex - 1 : sliderItems.length - 1;
                    } else {
                        newIndex = currentIndex < sliderItems.length - 1 ? currentIndex + 1 : 0;
                    }
                    
                    if (sliderItems[newIndex]) {
                        window.location.href = sliderItems[newIndex].getAttribute('href');
                    }
                });
            });
            
            // Cleanup on page unload
            window.addEventListener('beforeunload', function() {
                document.body.classList.remove('es-featured-active');
            });
            
            // Also cleanup if element is removed
            var observer = new MutationObserver(function(mutations) {
                if (!document.body.contains(container)) {
                    document.body.classList.remove('es-featured-active');
                    observer.disconnect();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        })();
        </script>
        <?php
    }
    
    /**
     * Render Artists Featured Slider
     * Fullscreen layout with all artists in a slider
     * 
     * @param array $artists Array of artist posts
     * @param array $atts Shortcode attributes
     */
    private function render_artists_featured_slider($artists, $atts) {
        global $wpdb;
        
        if (empty($artists)) {
            return;
        }
        
        // Get first artist to display initially
        $current_artist = $artists[0];
        $current_index = 0;
        
        // Get layout set for styling
        $active_set = class_exists('ES_Layout_Sets') ? ES_Layout_Sets::get_active_set() : 'modern';
        $layout_config = array();
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $active_set . '/config.php')) {
            $layout_config = include ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $active_set . '/config.php';
        }
        $is_dark_mode = ($layout_config['default_mode'] ?? 'dark') === 'dark';
        $mode_class = $is_dark_mode ? 'es-featured-dark' : 'es-featured-light';
        
        // Enqueue CSS
        wp_enqueue_style('ensemble-featured-artist', ENSEMBLE_PLUGIN_URL . 'assets/css/single-artist-featured.css', array(), ENSEMBLE_VERSION);
        
        // Add body class via JavaScript
        $instance_id = 'es-featured-slider-' . wp_rand(1000, 9999);
        
        // Prepare all artists data as JSON for JavaScript
        $artists_data = array();
        foreach ($artists as $artist) {
            $artist_id = $artist->ID;
            
            // Load artist data
            $artist_data_loaded = function_exists('es_load_artist_data') ? es_load_artist_data($artist_id) : array();
            
            // Genre
            $genre = '';
            $genre_terms = get_the_terms($artist_id, 'ensemble_genre');
            if ($genre_terms && !is_wp_error($genre_terms)) {
                $genre = wp_list_pluck($genre_terms, 'name');
            }
            if (empty($genre)) {
                $genre = get_post_meta($artist_id, 'es_artist_genre', true);
            }
            
            // Hero video
            $hero_video_url = get_post_meta($artist_id, '_artist_hero_video_url', true);
            $video_type = '';
            $video_embed_url = '';
            if (!empty($hero_video_url)) {
                if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $hero_video_url, $matches) || 
                    preg_match('/youtu\.be\/([^?]+)/', $hero_video_url, $matches)) {
                    $video_type = 'youtube';
                    $video_id = $matches[1];
                    $video_embed_url = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&mute=1&loop=1&playlist=' . $video_id . '&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1';
                } elseif (preg_match('/vimeo\.com\/(\d+)/', $hero_video_url, $matches)) {
                    $video_type = 'vimeo';
                    $video_id = $matches[1];
                    $video_embed_url = 'https://player.vimeo.com/video/' . $video_id . '?autoplay=1&muted=1&loop=1&background=1&autopause=0';
                } elseif (preg_match('/\.(mp4|webm|ogg)$/i', $hero_video_url)) {
                    $video_type = 'mp4';
                    $video_embed_url = $hero_video_url;
                }
            }
            
            // Load artist data using the standard function
            $artist_data_loaded = function_exists('es_load_artist_data') ? es_load_artist_data($artist_id) : array();
            
            // Website and social from es_load_artist_data (same approach as original)
            $website = $artist_data_loaded['website'] ?? '';
            $social = array_filter(array(
                'facebook' => $artist_data_loaded['facebook'] ?? '',
                'instagram' => $artist_data_loaded['instagram'] ?? '',
                'twitter' => $artist_data_loaded['twitter'] ?? '',
                'soundcloud' => $artist_data_loaded['soundcloud'] ?? '',
                'spotify' => $artist_data_loaded['spotify'] ?? '',
                'youtube' => $artist_data_loaded['youtube'] ?? '',
                'tiktok' => $artist_data_loaded['tiktok'] ?? '',
                'bandcamp' => $artist_data_loaded['bandcamp'] ?? '',
                'mixcloud' => $artist_data_loaded['mixcloud'] ?? '',
            ));
            
            // Get events for this artist using same SQL pattern as single artist
            $events = array();
            $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
            $today = date('Y-m-d');
            
            // Get the correct meta key for artist field
            $artist_meta_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('artist') : 'event_artist';
            
            // Use same working query pattern
            $event_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT pm.post_id 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = %s 
                AND p.post_type = %s
                AND p.post_status = 'publish'
                AND (
                    pm.meta_value LIKE %s
                    OR pm.meta_value LIKE %s
                    OR pm.meta_value = %s
                )
            ", 
                $artist_meta_key,
                $event_post_type,
                '%i:' . $artist_id . ';%',
                '%s:' . strlen($artist_id) . ':"' . $artist_id . '";%',
                $artist_id
            ));
            
            if (!empty($event_ids)) {
                // Get all events for this artist, then filter by date in PHP
                $all_events_query = new WP_Query(array(
                    'post_type' => $event_post_type,
                    'post__in' => $event_ids,
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                ));
                
                $upcoming = array();
                $past = array();
                
                if ($all_events_query->have_posts()) {
                    while ($all_events_query->have_posts()) {
                        $all_events_query->the_post();
                        $evt_id = get_the_ID();
                        
                        // Use unified helper for date retrieval
                        $start_date = function_exists('ensemble_get_event_meta') 
                            ? ensemble_get_event_meta($evt_id, 'start_date')
                            : get_post_meta($evt_id, 'event_date', true);
                        
                        $evt_data = array(
                            'id' => $evt_id,
                            'title' => get_the_title(),
                            'permalink' => get_permalink(),
                            'date' => $start_date ?: '1970-01-01',
                            'day' => $start_date ? date_i18n('d', strtotime($start_date)) : '',
                            'month' => $start_date ? date_i18n('M', strtotime($start_date)) : '',
                        );
                        
                        if (!empty($start_date) && $start_date >= $today) {
                            $upcoming[$start_date . '-' . $evt_id] = $evt_data;
                        } else {
                            $past[$start_date . '-' . $evt_id] = $evt_data;
                        }
                    }
                    wp_reset_postdata();
                }
                
                // Use upcoming events, or past if none upcoming
                if (!empty($upcoming)) {
                    ksort($upcoming);
                    $events = array_slice(array_values($upcoming), 0, 3);
                } elseif (!empty($past)) {
                    krsort($past);
                    $events = array_slice(array_values($past), 0, 3);
                }
            }
            
            // Get gallery for this artist
            $gallery = array();
            $gallery_ids = array();
            if (function_exists('get_field')) {
                $gallery_ids = get_field('artist_gallery', $artist_id);
            }
            if (empty($gallery_ids)) {
                $gallery_ids = get_post_meta($artist_id, 'artist_gallery', true);
            }
            if (!empty($gallery_ids) && is_array($gallery_ids)) {
                foreach ($gallery_ids as $img_id) {
                    $img_medium = wp_get_attachment_image_url($img_id, 'medium');
                    $img_full = wp_get_attachment_image_url($img_id, 'full');
                    if ($img_medium) {
                        $gallery[] = array(
                            'medium' => $img_medium,
                            'full' => $img_full,
                        );
                    }
                }
            }
            
            $artists_data[] = array(
                'id' => $artist_id,
                'name' => $artist->post_title,
                'origin' => $artist_data_loaded['origin'] ?? '',
                'genre' => $genre,
                'bio' => $artist->post_content,
                'references' => $artist_data_loaded['references'] ?? '',
                'image' => get_the_post_thumbnail_url($artist_id, 'full'),
                'thumbnail' => get_the_post_thumbnail_url($artist_id, 'thumbnail'),
                'video_type' => $video_type,
                'video_url' => $video_embed_url,
                'website' => $website,
                'social' => array_filter($social),
                'events' => $events,
                'gallery' => $gallery,
                'permalink' => get_permalink($artist_id),
            );
        }
        
        $first_artist = $artists_data[0];
        ?>
        
        <div id="<?php echo esc_attr($instance_id); ?>" 
             class="es-featured-artist has-slider <?php echo esc_attr($mode_class); ?> es-set-<?php echo esc_attr($active_set); ?>" 
             data-artists='<?php echo esc_attr(json_encode($artists_data)); ?>'
             data-current-index="0">
            
            <!-- HERO -->
            <section class="es-featured-hero">
                
                <!-- Background Media (will be updated via JS) -->
                <div class="es-featured-hero-media">
                    <?php if ($first_artist['video_type'] === 'mp4'): ?>
                        <video class="es-featured-hero-video" autoplay muted loop playsinline>
                            <source src="<?php echo esc_url($first_artist['video_url']); ?>" type="video/mp4">
                        </video>
                    <?php elseif ($first_artist['video_type'] === 'youtube' || $first_artist['video_type'] === 'vimeo'): ?>
                        <div class="es-featured-hero-video-wrapper">
                            <iframe class="es-featured-hero-video" 
                                    src="<?php echo esc_url($first_artist['video_url']); ?>" 
                                    frameborder="0" 
                                    allow="autoplay; fullscreen" 
                                    allowfullscreen></iframe>
                        </div>
                    <?php elseif ($first_artist['image']): ?>
                        <div class="es-featured-hero-image">
                            <img src="<?php echo esc_url($first_artist['image']); ?>" alt="<?php echo esc_attr($first_artist['name']); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="es-featured-hero-gradient"></div>
                </div>
                
                <!-- Hero Content -->
                <div class="es-featured-hero-content">
                    
                    <!-- LEFT SIDE -->
                    <div class="es-featured-left">
                        <h1 class="es-featured-title"><?php echo esc_html($first_artist['name']); ?></h1>
                        
                        <?php if (!empty($first_artist['origin'])): ?>
                        <p class="es-featured-origin"><?php echo esc_html($first_artist['origin']); ?></p>
                        <?php endif; ?>
                        
                        <div class="es-featured-hero-social"<?php echo (!$first_artist['website'] && empty($first_artist['social'])) ? ' style="display:none"' : ''; ?>>
                            <?php if ($first_artist['website']): ?>
                            <a href="<?php echo esc_url($first_artist['website']); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="Website">
                                <?php echo ES_Icons::get('website'); ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($first_artist['social'])): foreach ($first_artist['social'] as $platform => $url): if ($url): ?>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="es-featured-social-icon" title="<?php echo esc_attr(ucfirst($platform)); ?>">
                                <?php echo ES_Icons::get($platform); ?>
                            </a>
                            <?php endif; endforeach; endif; ?>
                        </div>
                    </div>
                    
                    <!-- RIGHT SIDE -->
                    <div class="es-featured-right">
                        <?php if (!empty($first_artist['events'])): ?>
                        <div class="es-featured-info-section">
                            <h3 class="es-featured-info-title"><?php _e('Nächste Events', 'ensemble'); ?></h3>
                            <div class="es-featured-events-compact">
                                <?php foreach ($first_artist['events'] as $evt): ?>
                                <a href="<?php echo esc_url($evt['permalink']); ?>" class="es-featured-event-row">
                                    <div class="es-featured-event-date-small">
                                        <span class="day"><?php echo esc_html($evt['day']); ?></span>
                                        <span class="month"><?php echo esc_html($evt['month']); ?></span>
                                    </div>
                                    <h4><?php echo esc_html($evt['title']); ?></h4>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <button type="button" class="es-featured-more-btn">
                            <?php _e('Mehr Info', 'ensemble'); ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    
                </div>
                
            </section>
            
            <!-- Navigation Arrows -->
            <div class="es-featured-nav es-featured-nav-prev">
                <button type="button" class="es-featured-nav-btn" data-direction="prev" title="<?php esc_attr_e('Vorheriger', 'ensemble'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </button>
            </div>
            <div class="es-featured-nav es-featured-nav-next">
                <button type="button" class="es-featured-nav-btn" data-direction="next" title="<?php esc_attr_e('Nächster', 'ensemble'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
            </div>
            
            <!-- Artist Slider -->
            <section class="es-featured-slider-section">
                <div class="es-featured-slider">
                    <?php foreach ($artists_data as $index => $artist): ?>
                    <div class="es-featured-slider-item <?php echo $index === 0 ? 'is-active' : ''; ?>" 
                         data-index="<?php echo esc_attr($index); ?>"
                         title="<?php echo esc_attr($artist['name']); ?>">
                        <div class="es-featured-slider-image">
                            <?php if ($artist['thumbnail']): ?>
                                <img src="<?php echo esc_url($artist['thumbnail']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                            <?php else: ?>
                                <div class="es-featured-slider-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- Overlay -->
            <div class="es-featured-overlay"></div>
            
            <!-- Panel -->
            <div class="es-featured-panel">
                <div class="es-featured-panel-header">
                    <h2 class="es-featured-panel-title"><?php echo esc_html($first_artist['name']); ?></h2>
                    <button type="button" class="es-featured-panel-close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="es-featured-panel-content">
                    <div class="es-featured-panel-references">
                        <?php if (!empty($first_artist['references'])): ?>
                        <blockquote class="es-featured-references"><?php echo esc_html($first_artist['references']); ?></blockquote>
                        <?php endif; ?>
                    </div>
                    <div class="es-featured-panel-bio">
                        <?php if (!empty($first_artist['bio'])): ?>
                        <div class="es-featured-description"><?php echo wpautop($first_artist['bio']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="es-featured-panel-genres">
                        <?php if (!empty($first_artist['genre'])): ?>
                        <div class="es-featured-panel-genres-section">
                            <h3 class="es-featured-gallery-title"><?php _e('Genres', 'ensemble'); ?></h3>
                            <div class="es-featured-genres-compact">
                                <?php 
                                $genres = is_array($first_artist['genre']) ? $first_artist['genre'] : explode(',', $first_artist['genre']);
                                foreach ($genres as $g): 
                                    $g = trim($g);
                                    if (!empty($g)):
                                ?>
                                <span class="es-featured-genre-tag"><?php echo esc_html($g); ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="es-featured-panel-gallery">
                        <?php if (!empty($first_artist['gallery'])): ?>
                        <div class="es-featured-gallery">
                            <h3 class="es-featured-gallery-title"><?php _e('Galerie', 'ensemble'); ?></h3>
                            <div class="es-featured-gallery-grid">
                                <?php foreach ($first_artist['gallery'] as $gidx => $img): ?>
                                <a href="<?php echo esc_url($img['full']); ?>" class="es-featured-gallery-item" data-lightbox="gallery" data-index="<?php echo $gidx; ?>">
                                    <img src="<?php echo esc_url($img['medium']); ?>" alt="">
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Lightbox -->
            <div class="es-featured-lightbox" id="<?php echo esc_attr($instance_id); ?>-lightbox">
                <div class="es-featured-lightbox-content">
                    <button type="button" class="es-featured-lightbox-close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                    <button type="button" class="es-featured-lightbox-nav es-featured-lightbox-prev">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </button>
                    <img src="" alt="">
                    <button type="button" class="es-featured-lightbox-nav es-featured-lightbox-next">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </button>
                </div>
            </div>
            
        </div>
        
        <script>
        (function() {
            var container = document.getElementById('<?php echo esc_js($instance_id); ?>');
            if (!container) return;
            
            var artistsData = JSON.parse(container.getAttribute('data-artists'));
            var currentIndex = 0;
            
            // Add body class
            document.body.classList.add('es-featured-active');
            
            // Elements
            var heroMedia = container.querySelector('.es-featured-hero-media');
            var title = container.querySelector('.es-featured-title');
            var origin = container.querySelector('.es-featured-origin');
            var genreBadges = container.querySelector('.es-featured-genre-badges');
            var socialIcons = container.querySelector('.es-featured-hero-social');
            var eventsSection = container.querySelector('.es-featured-events-compact');
            var genresSection = container.querySelector('.es-featured-genres-compact');
            var moreBtn = container.querySelector('.es-featured-more-btn');
            var panel = container.querySelector('.es-featured-panel');
            var panelTitle = container.querySelector('.es-featured-panel-title');
            var panelContent = container.querySelector('.es-featured-panel-content');
            var overlay = container.querySelector('.es-featured-overlay');
            var sliderItems = container.querySelectorAll('.es-featured-slider-item');
            var navBtns = container.querySelectorAll('.es-featured-nav-btn');
            
            // Lightbox elements
            var lightbox = document.getElementById('<?php echo esc_js($instance_id); ?>-lightbox');
            var lightboxImg = lightbox ? lightbox.querySelector('img') : null;
            var lightboxClose = lightbox ? lightbox.querySelector('.es-featured-lightbox-close') : null;
            var lightboxPrev = lightbox ? lightbox.querySelector('.es-featured-lightbox-prev') : null;
            var lightboxNext = lightbox ? lightbox.querySelector('.es-featured-lightbox-next') : null;
            var galleryImages = [];
            var currentLightboxIndex = 0;
            
            function openLightbox(index) {
                if (!lightbox || galleryImages.length === 0) return;
                currentLightboxIndex = index;
                lightboxImg.src = galleryImages[index];
                lightbox.classList.add('is-open');
            }
            
            function closeLightbox() {
                if (!lightbox) return;
                lightbox.classList.remove('is-open');
            }
            
            function nextLightbox() {
                currentLightboxIndex = (currentLightboxIndex + 1) % galleryImages.length;
                lightboxImg.src = galleryImages[currentLightboxIndex];
            }
            
            function prevLightbox() {
                currentLightboxIndex = (currentLightboxIndex - 1 + galleryImages.length) % galleryImages.length;
                lightboxImg.src = galleryImages[currentLightboxIndex];
            }
            
            function initGalleryLightbox() {
                galleryImages = [];
                var items = container.querySelectorAll('.es-featured-gallery-item[data-lightbox]');
                items.forEach(function(item, index) {
                    galleryImages.push(item.href);
                    item.onclick = function(e) {
                        e.preventDefault();
                        openLightbox(index);
                    };
                });
            }
            
            // Lightbox controls
            if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
            if (lightboxPrev) lightboxPrev.addEventListener('click', prevLightbox);
            if (lightboxNext) lightboxNext.addEventListener('click', nextLightbox);
            if (lightbox) {
                lightbox.addEventListener('click', function(e) {
                    if (e.target === lightbox) closeLightbox();
                });
            }
            
            function updateArtist(index) {
                if (index < 0) index = artistsData.length - 1;
                if (index >= artistsData.length) index = 0;
                
                // Don't re-render if same artist
                if (index === currentIndex) return;
                
                var heroSection = container.querySelector('.es-featured-hero');
                var contentSection = container.querySelector('.es-featured-hero-content');
                
                // Fade out
                if (heroSection) heroSection.classList.add('es-transitioning');
                
                // Wait for fade out, then update
                setTimeout(function() {
                    currentIndex = index;
                    var artist = artistsData[index];
                    
                    // Update media
                    var mediaHtml = '';
                    if (artist.video_type === 'mp4') {
                        mediaHtml = '<video class="es-featured-hero-video" autoplay muted loop playsinline><source src="' + artist.video_url + '" type="video/mp4"></video>';
                    } else if (artist.video_type === 'youtube' || artist.video_type === 'vimeo') {
                        mediaHtml = '<div class="es-featured-hero-video-wrapper"><iframe class="es-featured-hero-video" src="' + artist.video_url + '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe></div>';
                    } else if (artist.image) {
                        mediaHtml = '<div class="es-featured-hero-image"><img src="' + artist.image + '" alt="' + artist.name + '"></div>';
                    }
                    mediaHtml += '<div class="es-featured-hero-gradient"></div>';
                    heroMedia.innerHTML = mediaHtml;
                    
                    // Update title
                    title.textContent = artist.name;
                    
                    // Update origin
                    if (origin) {
                        origin.textContent = artist.origin || '';
                        origin.style.display = artist.origin ? '' : 'none';
                    }
                    
                    // Update genres badges
                    if (genreBadges) {
                        var genres = Array.isArray(artist.genre) ? artist.genre : (artist.genre || '').split(',');
                        var badgesHtml = '';
                        genres.slice(0, 3).forEach(function(g) {
                            g = g.trim();
                            if (g) badgesHtml += '<span class="es-featured-genre-badge">' + g + '</span>';
                        });
                        genreBadges.innerHTML = badgesHtml;
                        genreBadges.style.display = badgesHtml ? '' : 'none';
                    }
                
                // Update social links
                if (socialIcons) {
                    var socialHtml = '';
                    var socialIconsSvg = {
                        'website': '<?php echo addslashes(ES_Icons::get('website')); ?>',
                        'facebook': '<?php echo addslashes(ES_Icons::get('facebook')); ?>',
                        'instagram': '<?php echo addslashes(ES_Icons::get('instagram')); ?>',
                        'twitter': '<?php echo addslashes(ES_Icons::get('twitter')); ?>',
                        'soundcloud': '<?php echo addslashes(ES_Icons::get('soundcloud')); ?>',
                        'spotify': '<?php echo addslashes(ES_Icons::get('spotify')); ?>',
                        'youtube': '<?php echo addslashes(ES_Icons::get('youtube')); ?>',
                        'tiktok': '<?php echo addslashes(ES_Icons::get('tiktok')); ?>',
                        'bandcamp': '<?php echo addslashes(ES_Icons::get('bandcamp')); ?>',
                        'mixcloud': '<?php echo addslashes(ES_Icons::get('mixcloud')); ?>'
                    };
                    if (artist.website) {
                        socialHtml += '<a href="' + artist.website + '" target="_blank" rel="noopener" class="es-featured-social-icon" title="Website">' + socialIconsSvg['website'] + '</a>';
                    }
                    if (artist.social) {
                        Object.keys(artist.social).forEach(function(platform) {
                            if (artist.social[platform] && socialIconsSvg[platform]) {
                                socialHtml += '<a href="' + artist.social[platform] + '" target="_blank" rel="noopener" class="es-featured-social-icon" title="' + platform.charAt(0).toUpperCase() + platform.slice(1) + '">' + socialIconsSvg[platform] + '</a>';
                            }
                        });
                    }
                    socialIcons.innerHTML = socialHtml;
                    socialIcons.style.display = socialHtml ? '' : 'none';
                }
                
                // Update events
                if (eventsSection) {
                    var parentSection = eventsSection.closest('.es-featured-info-section');
                    if (artist.events && artist.events.length > 0) {
                        var eventsHtml = '';
                        artist.events.forEach(function(evt) {
                            eventsHtml += '<a href="' + evt.permalink + '" class="es-featured-event-row">' +
                                '<div class="es-featured-event-date-small"><span class="day">' + evt.day + '</span><span class="month">' + evt.month + '</span></div>' +
                                '<h4>' + evt.title + '</h4></a>';
                        });
                        eventsSection.innerHTML = eventsHtml;
                        if (parentSection) parentSection.style.display = '';
                    } else {
                        if (parentSection) parentSection.style.display = 'none';
                    }
                }
                
                // Update more button visibility
                if (moreBtn) {
                    // Always show more button (genres are now in panel)
                    moreBtn.style.display = '';
                }
                
                // Update panel
                panelTitle.textContent = artist.name;
                var panelHtml = '';
                if (artist.references) {
                    panelHtml += '<blockquote class="es-featured-references">' + artist.references + '</blockquote>';
                }
                if (artist.bio) {
                    panelHtml += '<div class="es-featured-description">' + artist.bio + '</div>';
                }
                // Add genres to panel
                if (artist.genre) {
                    var genres = Array.isArray(artist.genre) ? artist.genre : (artist.genre || '').split(',');
                    var genresHtml = '';
                    genres.forEach(function(g) {
                        g = g.trim ? g.trim() : g;
                        if (g) genresHtml += '<span class="es-featured-genre-tag">' + g + '</span>';
                    });
                    if (genresHtml) {
                        panelHtml += '<div class="es-featured-panel-genres-section">';
                        panelHtml += '<h3 class="es-featured-gallery-title"><?php echo esc_js(__('Genres', 'ensemble')); ?></h3>';
                        panelHtml += '<div class="es-featured-genres-compact">' + genresHtml + '</div>';
                        panelHtml += '</div>';
                    }
                }
                // Add social links to panel
                var socialHtml = '';
                if (artist.website) {
                    socialHtml += '<a href="' + artist.website + '" target="_blank" class="es-featured-social-icon" title="Website"><?php echo addslashes(ES_Icons::get('website')); ?></a>';
                }
                if (artist.social) {
                    var socialIcons = {
                        'facebook': '<?php echo addslashes(ES_Icons::get('facebook')); ?>',
                        'instagram': '<?php echo addslashes(ES_Icons::get('instagram')); ?>',
                        'twitter': '<?php echo addslashes(ES_Icons::get('twitter')); ?>',
                        'soundcloud': '<?php echo addslashes(ES_Icons::get('soundcloud')); ?>',
                        'spotify': '<?php echo addslashes(ES_Icons::get('spotify')); ?>',
                        'youtube': '<?php echo addslashes(ES_Icons::get('youtube')); ?>',
                        'tiktok': '<?php echo addslashes(ES_Icons::get('tiktok')); ?>'
                    };
                    Object.keys(artist.social).forEach(function(platform) {
                        if (artist.social[platform] && socialIcons[platform]) {
                            socialHtml += '<a href="' + artist.social[platform] + '" target="_blank" class="es-featured-social-icon" title="' + platform.charAt(0).toUpperCase() + platform.slice(1) + '">' + socialIcons[platform] + '</a>';
                        }
                    });
                }
                if (socialHtml) {
                    panelHtml += '<div class="es-featured-panel-social-section">';
                    panelHtml += '<h3 class="es-featured-gallery-title"><?php echo esc_js(__('Links', 'ensemble')); ?></h3>';
                    panelHtml += '<div class="es-featured-panel-social">' + socialHtml + '</div>';
                    panelHtml += '</div>';
                }
                if (artist.gallery && artist.gallery.length > 0) {
                    panelHtml += '<div class="es-featured-gallery">';
                    panelHtml += '<h3 class="es-featured-gallery-title"><?php echo esc_js(__('Galerie', 'ensemble')); ?></h3>';
                    panelHtml += '<div class="es-featured-gallery-grid">';
                    artist.gallery.forEach(function(img, imgIndex) {
                        panelHtml += '<a href="' + img.full + '" class="es-featured-gallery-item" data-lightbox="gallery" data-index="' + imgIndex + '">';
                        panelHtml += '<img src="' + img.medium + '" alt="">';
                        panelHtml += '</a>';
                    });
                    panelHtml += '</div></div>';
                }
                panelContent.innerHTML = panelHtml;
                
                // Re-init gallery lightbox after content update
                initGalleryLightbox();
                
                // Update slider active state
                sliderItems.forEach(function(item, i) {
                    item.classList.toggle('is-active', i === index);
                });
                
                // Scroll slider horizontally to show active item (without moving page)
                var activeItem = container.querySelector('.es-featured-slider-item.is-active');
                var sliderContainer = container.querySelector('.es-featured-slider');
                if (activeItem && sliderContainer) {
                    var itemRect = activeItem.getBoundingClientRect();
                    var containerRect = sliderContainer.getBoundingClientRect();
                    var scrollLeft = sliderContainer.scrollLeft + (itemRect.left - containerRect.left) - (containerRect.width / 2) + (itemRect.width / 2);
                    sliderContainer.scrollTo({ left: scrollLeft, behavior: 'smooth' });
                }
                
                // Fade back in
                setTimeout(function() {
                    if (heroSection) heroSection.classList.remove('es-transitioning');
                }, 50);
                
                }, 300); // End of fade-out timeout
            }
            
            // Panel functions
            function openPanel() {
                panel.classList.add('is-open');
                overlay.classList.add('is-visible');
            }
            
            function closePanel() {
                panel.classList.remove('is-open');
                overlay.classList.remove('is-visible');
            }
            
            // Event listeners
            if (moreBtn) moreBtn.addEventListener('click', openPanel);
            container.querySelector('.es-featured-panel-close').addEventListener('click', closePanel);
            overlay.addEventListener('click', closePanel);
            
            document.addEventListener('keydown', function(e) {
                // Lightbox takes priority
                if (lightbox && lightbox.classList.contains('is-open')) {
                    if (e.key === 'Escape') closeLightbox();
                    if (e.key === 'ArrowLeft') prevLightbox();
                    if (e.key === 'ArrowRight') nextLightbox();
                    return;
                }
                // Panel/slider navigation
                if (e.key === 'Escape') closePanel();
                if (e.key === 'ArrowLeft') updateArtist(currentIndex - 1);
                if (e.key === 'ArrowRight') updateArtist(currentIndex + 1);
            });
            
            // Initialize gallery lightbox on load
            initGalleryLightbox();
            
            // Navigation arrows
            navBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var direction = this.getAttribute('data-direction');
                    updateArtist(direction === 'prev' ? currentIndex - 1 : currentIndex + 1);
                });
            });
            
            // Slider item clicks
            sliderItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    var index = parseInt(this.getAttribute('data-index'));
                    updateArtist(index);
                });
            });
            
            // Slider drag
            var slider = container.querySelector('.es-featured-slider');
            var isDown = false, startX, scrollLeft;
            slider.addEventListener('mousedown', function(e) { isDown = true; startX = e.pageX - slider.offsetLeft; scrollLeft = slider.scrollLeft; });
            slider.addEventListener('mouseleave', function() { isDown = false; });
            slider.addEventListener('mouseup', function() { isDown = false; });
            slider.addEventListener('mousemove', function(e) {
                if (!isDown) return;
                e.preventDefault();
                slider.scrollLeft = scrollLeft - ((e.pageX - slider.offsetLeft - startX) * 2);
            });
            
            // Cleanup
            window.addEventListener('beforeunload', function() {
                document.body.classList.remove('es-featured-active');
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Locations List Shortcode
     * Displays a list or grid of locations
     * 
     * Usage: [ensemble_locations layout="grid" columns="3" limit="12"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
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
    public function gallery_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id'        => 0,           // Gallery Post ID
            'event'     => 0,           // Get gallery linked to event
            'artist'    => 0,           // Get gallery linked to artist
            'location'  => 0,           // Get gallery linked to location
            'layout'    => '',          // grid, masonry, slider (empty = use gallery setting)
            'columns'   => 0,           // 2-5 (0 = use gallery setting)
            'lightbox'  => '',          // true/false (empty = use gallery setting)
            'limit'     => 0,           // Max images (0 = all)
            'class'     => '',          // Additional CSS class
        ), $atts, 'ensemble_gallery');
        
        // Sanitize
        $gallery_id = absint($atts['id']);
        $event_id = absint($atts['event']);
        $artist_id = absint($atts['artist']);
        $location_id = absint($atts['location']);
        $layout = sanitize_key($atts['layout']);
        $columns = absint($atts['columns']);
        $lightbox = $atts['lightbox'];
        $limit = absint($atts['limit']);
        $extra_class = sanitize_html_class($atts['class']);
        
        $manager = new ES_Gallery_Manager();
        $gallery = null;
        
        // Get gallery by ID
        if ($gallery_id) {
            $gallery = $manager->get_gallery($gallery_id);
        }
        // Get gallery by linked event
        elseif ($event_id) {
            $galleries = $manager->get_galleries_by_event($event_id);
            if (!empty($galleries)) {
                $gallery = $galleries[0];
            }
        }
        // Get gallery by linked artist
        elseif ($artist_id) {
            $galleries = $manager->get_galleries_by_artist($artist_id);
            if (!empty($galleries)) {
                $gallery = $galleries[0];
            }
        }
        // Get gallery by linked location
        elseif ($location_id) {
            $galleries = $manager->get_galleries_by_location($location_id);
            if (!empty($galleries)) {
                $gallery = $galleries[0];
            }
        }
        
        if (!$gallery || empty($gallery['images'])) {
            return '';
        }
        
        // Use gallery settings if not overridden
        if (empty($layout)) {
            $layout = $gallery['layout'] ?: 'grid';
        }
        if ($columns === 0) {
            $columns = $gallery['columns'] ?: 3;
        }
        if ($lightbox === '') {
            $lightbox = $gallery['lightbox'];
        } else {
            $lightbox = filter_var($lightbox, FILTER_VALIDATE_BOOLEAN);
        }
        
        // Get images
        $images = $gallery['images'];
        if ($limit > 0) {
            $images = array_slice($images, 0, $limit);
        }
        
        // Build output
        ob_start();
        
        $wrapper_class = 'es-gallery';
        $wrapper_class .= ' es-gallery--' . $layout;
        $wrapper_class .= ' es-gallery--cols-' . $columns;
        if ($lightbox) {
            $wrapper_class .= ' es-gallery--lightbox';
        }
        if ($extra_class) {
            $wrapper_class .= ' ' . $extra_class;
        }
        
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>" 
             data-gallery-id="<?php echo esc_attr($gallery['id']); ?>"
             data-lightbox="<?php echo $lightbox ? 'true' : 'false'; ?>">
            
            <?php if (!empty($gallery['title']) && $atts['id']): ?>
            <h3 class="es-gallery__title"><?php echo esc_html($gallery['title']); ?></h3>
            <?php endif; ?>
            
            <div class="es-gallery__grid" style="--es-gallery-columns: <?php echo esc_attr($columns); ?>;">
                <?php foreach ($images as $index => $image): ?>
                    <figure class="es-gallery__item" data-index="<?php echo esc_attr($index); ?>">
                        <?php if ($lightbox): ?>
                        <a href="<?php echo esc_url($image['full']); ?>" 
                           class="es-gallery__link"
                           data-caption="<?php echo esc_attr($image['caption']); ?>">
                        <?php endif; ?>
                        
                        <img src="<?php echo esc_url($image['large'] ?: $image['url']); ?>" 
                             alt="<?php echo esc_attr($image['alt']); ?>"
                             class="es-gallery__image"
                             loading="lazy">
                        
                        <?php if ($lightbox): ?>
                        <span class="es-gallery__zoom">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                                <path d="M11 8v6M8 11h6"/>
                            </svg>
                        </span>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($image['caption']): ?>
                        <figcaption class="es-gallery__caption"><?php echo esc_html($image['caption']); ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
            
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Single Location Shortcode
     * Embeds a single location anywhere
     * 
     * Usage: [ensemble_location id="123" layout="card"]
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
    
    /**
     * Upcoming Events Shortcode
     * Shows only upcoming events in a compact widget-friendly format
     * 
     * Usage: [ensemble_upcoming_events limit="5" show_countdown="true"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function upcoming_events_shortcode($atts) {
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
            'show_search' => 'true',
            
            // Display Options
            'show_image' => '1',
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
        $show_filters = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);
        $show_search = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
        
        if ($is_slider_layout) {
            $show_filters = false;
            $show_search = false;
        }
        
        // Display options
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
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
            // ACF stores as: a:2:{i:0;i:123;i:1;i:456;} or a:1:{i:0;s:3:"123";}
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
                                    'show_date' => $show_date,
                                    'show_time' => $show_time,
                                    'show_location' => $show_location_meta,
                                    'show_price' => $show_price,
                                    'show_category' => $show_category,
                                );
                                $card_data_attributes = '';
                                $card_index = $slide_index;
                                include $card_template;
                            } else {
                                // Fallback card
                                $this->render_slider_event_card($event_data, array(
                                    'show_image' => $show_image,
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
                                        $aid = reset($aid); // Get first element if array
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
                        // Ensure all values are integers for implode
                        $artist_ids_array = array_filter(array_map('intval', $artist_ids_array));
                        $artist_ids_string = implode(',', $artist_ids_array);
                        
                        // BUILD DATA ATTRIBUTES STRING (used by ALL templates)
                        // Use data-artist-ids (plural) with comma-separated IDs for multi-artist support
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
                                'show_date' => $show_date,
                                'show_time' => $show_time,
                                'show_location' => $show_location_meta,
                                'show_price' => $show_price,
                                'show_category' => $show_category,
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
                                    
                                    <h3 class="ensemble-event-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
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
                
                <h3 class="ensemble-event-title">
                    <a href="<?php echo esc_url($event['permalink']); ?>"><?php echo esc_html($event['title']); ?></a>
                </h3>
                
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
    public function calendar_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'view' => 'dayGridMonth',       // dayGridMonth, timeGridWeek, timeGridDay, listMonth
            'height' => 'auto',              // auto or pixel value
            'initial_date' => '',            // Y-m-d format
        ), $atts, 'ensemble_calendar');
        
        // Sanitize and normalize view name
        $view = sanitize_text_field($atts['view']);
        
        // Map simple view names to FullCalendar view names
        $view_map = array(
            'month' => 'dayGridMonth',
            'week' => 'timeGridWeek',
            'day' => 'timeGridDay',
            'list' => 'listMonth',
        );
        
        // Map lowercase to correct camelCase
        $view_lowercase_map = array(
            'daygridmonth' => 'dayGridMonth',
            'timegridweek' => 'timeGridWeek',
            'timegridday' => 'timeGridDay',
            'listmonth' => 'listMonth',
            'listweek' => 'listWeek',
        );
        
        // Check if it's a simple name first
        if (isset($view_map[strtolower($view)])) {
            $view = $view_map[strtolower($view)];
        }
        // Or if it's already a FullCalendar view but lowercase
        elseif (isset($view_lowercase_map[strtolower($view)])) {
            $view = $view_lowercase_map[strtolower($view)];
        }
        // Otherwise keep as is but validate it's safe
        else {
            // Only allow known FullCalendar view types
            $valid_views = array('dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listMonth', 'listWeek');
            if (!in_array($view, $valid_views)) {
                $view = 'dayGridMonth'; // Fallback to default
            }
        }
        
        $height = sanitize_text_field($atts['height']);
        $initial_date = sanitize_text_field($atts['initial_date']);
        
        // Generate unique ID for this calendar
        $calendar_id = 'ensemble-calendar-' . uniqid();
        
        // Enqueue FullCalendar
        $this->enqueue_fullcalendar();
        
        // Get events for initial display (next 3 months)
        $start_date = $initial_date ? $initial_date : date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+3 months', strtotime($start_date)));
        
        $events = $this->get_calendar_events($start_date, $end_date);
        $events_json = json_encode($events);
        
        ob_start();
        ?>
        
        <div class="ensemble-fullcalendar-wrapper">
            <div id="<?php echo esc_attr($calendar_id); ?>" class="ensemble-fullcalendar"></div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('<?php echo esc_js($calendar_id); ?>');
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: '<?php echo esc_js($view); ?>',
                <?php if ($initial_date): ?>
                initialDate: '<?php echo esc_js($initial_date); ?>',
                <?php endif; ?>
                height: '<?php echo esc_js($height); ?>',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: '<?php _e('Today', 'ensemble'); ?>',
                    month: '<?php _e('Month', 'ensemble'); ?>',
                    week: '<?php _e('Week', 'ensemble'); ?>',
                    day: '<?php _e('Day', 'ensemble'); ?>',
                    list: '<?php _e('List', 'ensemble'); ?>'
                },
                locale: '<?php echo esc_js(substr(get_locale(), 0, 2)); ?>',
                firstDay: 1,
                navLinks: true,
                editable: false,
                dayMaxEvents: true,
                
                // Load events
                events: function(info, successCallback, failureCallback) {
                    // AJAX call to get events for the current view
                    jQuery.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'ensemble_get_calendar_events',
                            start: info.startStr,
                            end: info.endStr,
                            nonce: '<?php echo wp_create_nonce('ensemble_calendar'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                successCallback(response.data);
                            } else {
                                failureCallback();
                            }
                        },
                        error: function() {
                            failureCallback();
                        }
                    });
                },
                
                eventClick: function(info) {
                    if (info.event.url) {
                        window.open(info.event.url, '_self');
                        info.jsEvent.preventDefault();
                    }
                },
                
                eventDidMount: function(info) {
                    // Add custom styling
                    if (info.event.extendedProps.eventType === 'recurring') {
                        info.el.classList.add('fc-event-recurring');
                    }
                }
            });
            
            calendar.render();
        });
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue FullCalendar library with all necessary plugins
     */
    private function enqueue_fullcalendar() {
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // FullCalendar with ALL plugins included (scheduler bundle)
        wp_enqueue_script(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
            array('jquery'),
            '6.1.10',
            true
        );
        
        // Custom calendar styles with Designer Settings integration
        $calendar_css = $this->generate_calendar_css();
        wp_add_inline_style('ensemble-shortcodes', $calendar_css);
    }
    
    /**
     * Get events formatted for FullCalendar
     * 
     * @param string $start_date Y-m-d
     * @param string $end_date Y-m-d
     * @return array Events array
     */
    private function get_calendar_events($start_date, $end_date) {
        $events_array = array();
        
        // Use Virtual Events handler to get both real and recurring events
        if (class_exists('ES_Virtual_Events')) {
            $virtual_events = new ES_Virtual_Events();
            $all_events = $virtual_events->get_events_for_range($start_date, $end_date);
            
            foreach ($all_events as $event) {
                $event_id = is_object($event) ? $event->ID : $event['ID'];
                $is_virtual = is_object($event) ? ($event->is_virtual ?? false) : ($event['is_virtual'] ?? false);
                
                // For virtual events, get parent ID for metadata
                $meta_id = $event_id;
                if ($is_virtual && is_string($event_id) && strpos($event_id, 'virtual_') === 0) {
                    preg_match('/virtual_(\d+)_/', $event_id, $matches);
                    $meta_id = isset($matches[1]) ? intval($matches[1]) : $event_id;
                }
                
                // Get event data from object or use helpers
                $event_date = is_object($event) ? $event->event_date : ($event['event_date'] ?? '');
                $event_time = is_object($event) ? ($event->event_time ?? '') : ($event['event_time'] ?? '');
                $event_time_end = is_object($event) ? ($event->event_time_end ?? '') : ($event['event_time_end'] ?? '');
                $event_title = is_object($event) ? $event->title : ($event['title'] ?? get_the_title($meta_id));
                $location_id = is_object($event) ? ($event->event_location ?? '') : ($event['event_location'] ?? '');
                $artist_id = is_object($event) ? ($event->event_artist ?? '') : ($event['event_artist'] ?? '');
                
                if (empty($event_date)) {
                    continue;
                }
                
                // Build event object for FullCalendar
                $calendar_event = array(
                    'id' => $event_id,
                    'title' => $event_title,
                    'start' => $event_date,
                    'url' => $is_virtual ? get_permalink($meta_id) : get_permalink($event_id),
                    'allDay' => empty($event_time),
                );
                
                // Add time if available
                if ($event_time) {
                    $calendar_event['start'] = $event_date . 'T' . $event_time;
                    if ($event_time_end) {
                        $calendar_event['end'] = $event_date . 'T' . $event_time_end;
                    }
                }
                
                // Add extended properties
                $calendar_event['extendedProps'] = array(
                    'is_recurring' => is_object($event) ? ($event->is_recurring ?? false) : ($event['is_recurring'] ?? false),
                    'is_virtual' => $is_virtual,
                );
                
                if ($location_id) {
                    $location = get_post($location_id);
                    if ($location) {
                        $calendar_event['extendedProps']['location'] = $location->post_title;
                    }
                }
                
                if ($artist_id) {
                    $artist = get_post($artist_id);
                    if ($artist) {
                        $calendar_event['extendedProps']['artist'] = $artist->post_title;
                    }
                }
                
                $events_array[] = $calendar_event;
            }
        } else {
            // Fallback to direct query if ES_Virtual_Events not available
            $events_array = $this->get_calendar_events_fallback($start_date, $end_date);
        }
        
        return $events_array;
    }
    
    /**
     * Fallback method for calendar events without ES_Virtual_Events
     */
    private function get_calendar_events_fallback($start_date, $end_date) {
        $events_array = array();
        
        $date_key = $this->get_date_meta_key();
        
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => $date_key,
                    'value' => array($start_date, $end_date),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $event_id = get_the_ID();
                
                $event_date = $this->get_event_meta($event_id, 'start_date');
                $start_time = $this->get_event_meta($event_id, 'start_time');
                $end_time = $this->get_event_meta($event_id, 'end_time');
                
                if (!$event_date) {
                    continue;
                }
                
                $event = array(
                    'id' => $event_id,
                    'title' => get_the_title(),
                    'start' => $event_date,
                    'url' => get_permalink(),
                    'allDay' => empty($start_time),
                );
                
                if ($start_time) {
                    $event['start'] = $event_date . 'T' . $start_time;
                    if ($end_time) {
                        $event['end'] = $event_date . 'T' . $end_time;
                    }
                }
                
                $events_array[] = $event;
            }
        }
        
        wp_reset_postdata();
        
        return $events_array;
    }
    
    /**
     * Generate Calendar CSS with Designer Settings
     * Integrates Design Settings into FullCalendar styling
     */
    private function generate_calendar_css() {
        // Get Design Settings if available
        $settings = array();
        if (class_exists('ES_Design_Settings')) {
            $settings = ES_Design_Settings::get_settings();
        }
        
        // Fallback values if Design Settings not available
        $primary = $settings['primary_color'] ?? '#667eea';
        $secondary = $settings['secondary_color'] ?? '#764ba2';
        $card_bg = $settings['card_background'] ?? '#ffffff';
        $text = $settings['text_color'] ?? '#1a202c';
        $border = $settings['border_color'] ?? '#e2e8f0';
        $card_radius = $settings['card_radius'] ?? 12;
        $button_radius = $settings['button_radius'] ?? 8;
        $font_body = $settings['body_font'] ?? 'inherit';
        $font_heading = $settings['heading_font'] ?? 'inherit';
        
        ob_start();
        ?>
        
        /* Ensemble Calendar - Designer Settings Integration */
        .ensemble-fullcalendar-wrapper {
            margin: 20px 0;
            background: <?php echo esc_attr($card_bg); ?>;
            padding: 20px;
            border-radius: <?php echo esc_attr($card_radius); ?>px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid <?php echo esc_attr($border); ?>;
        }
        
        .ensemble-fullcalendar {
            max-width: 100%;
        }
        
        /* FullCalendar Base Styling */
        .fc {
            font-family: '<?php echo esc_attr($font_body); ?>', sans-serif;
            color: <?php echo esc_attr($text); ?>;
        }
        
        /* Calendar Title */
        .fc .fc-toolbar-title {
            font-family: '<?php echo esc_attr($font_heading); ?>', sans-serif;
            color: <?php echo esc_attr($text); ?>;
        }
        
        /* Buttons */
        .fc .fc-button {
            background: <?php echo esc_attr($primary); ?>;
            border-color: <?php echo esc_attr($primary); ?>;
            color: #fff;
            text-transform: none;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: <?php echo esc_attr($button_radius); ?>px;
            transition: all 0.2s ease;
        }
        
        .fc .fc-button:hover {
            background: <?php echo esc_attr($secondary); ?>;
            border-color: <?php echo esc_attr($secondary); ?>;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .fc .fc-button:active {
            transform: translateY(0);
        }
        
        .fc .fc-button-primary:disabled {
            background: #ccc;
            border-color: #ccc;
            opacity: 0.5;
        }
        
        .fc .fc-button-active {
            background: <?php echo esc_attr($secondary); ?>;
            border-color: <?php echo esc_attr($secondary); ?>;
        }
        
        /* Grid Lines */
        .fc-theme-standard .fc-scrollgrid {
            border-color: <?php echo esc_attr($border); ?>;
        }
        
        .fc-theme-standard td,
        .fc-theme-standard th {
            border-color: <?php echo esc_attr($border); ?>;
        }
        
        /* Header */
        .fc-col-header-cell {
            background: rgba(<?php 
                // Convert hex to rgba for subtle background
                $rgb = sscanf($primary, "#%02x%02x%02x");
                echo implode(',', $rgb);
            ?>, 0.05);
            font-weight: 600;
            padding: 12px 8px;
            color: <?php echo esc_attr($text); ?>;
        }
        
        /* Day Numbers */
        .fc-daygrid-day-number {
            padding: 8px;
            font-weight: 500;
            color: <?php echo esc_attr($text); ?>;
        }
        
        /* Today Highlight */
        .fc-day-today {
            background: rgba(<?php 
                $rgb = sscanf($primary, "#%02x%02x%02x");
                echo implode(',', $rgb);
            ?>, 0.1) !important;
        }
        
        .fc-day-today .fc-daygrid-day-number {
            background: <?php echo esc_attr($primary); ?>;
            color: #fff;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Events */
        .fc-event {
            background: <?php echo esc_attr($primary); ?>;
            border-color: <?php echo esc_attr($primary); ?>;
            border-radius: <?php echo max(3, $button_radius - 4); ?>px;
            padding: 4px 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .fc-event:hover {
            background: <?php echo esc_attr($secondary); ?>;
            border-color: <?php echo esc_attr($secondary); ?>;
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .fc-event-recurring {
            border-left: 4px solid #f39c12 !important;
        }
        
        .fc-event-title {
            font-weight: 600;
            font-size: 13px;
        }
        
        .fc-daygrid-event {
            cursor: pointer;
        }
        
        /* List View Styling */
        .fc-list-event:hover td {
            background: rgba(<?php 
                $rgb = sscanf($primary, "#%02x%02x%02x");
                echo implode(',', $rgb);
            ?>, 0.05);
        }
        
        .fc-list-event-dot {
            border-color: <?php echo esc_attr($primary); ?>;
        }
        
        /* Week/Day View Time Grid */
        .fc-timegrid-slot {
            height: 3em;
        }
        
        .fc-timegrid-event {
            border-radius: <?php echo max(2, $button_radius - 6); ?>px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .ensemble-fullcalendar-wrapper {
                padding: 12px;
            }
            
            .fc .fc-button {
                padding: 6px 10px;
                font-size: 12px;
            }
            
            .fc .fc-toolbar-title {
                font-size: 18px;
            }
        }
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for calendar events
     * Called by FullCalendar when view changes
     */
    public static function ajax_get_calendar_events() {
        // Verify nonce
        check_ajax_referer('ensemble_calendar', 'nonce');
        
        // Get parameters
        $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
        $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '';
        
        if (empty($start) || empty($end)) {
            wp_send_json_error('Invalid date range');
        }
        
        // Create instance to use non-static methods
        $shortcodes = new self();
        $events = $shortcodes->get_calendar_events($start, $end);
        
        wp_send_json_success($events);
    }
    
    /**
     * Get current layout from URL parameter or default
     * 
     * @param string $default Default layout if none specified
     * @return string Current layout slug
     */
    public static function get_current_demo_layout($default = 'lovepop') {
        $available_layouts = self::get_available_layouts();
        
        // Check URL parameter
        if (isset($_GET['es_layout']) && !empty($_GET['es_layout'])) {
            $requested = sanitize_text_field($_GET['es_layout']);
            if (isset($available_layouts[$requested])) {
                return $requested;
            }
        }
        
        return $default;
    }
    
    /**
     * Get available layouts with metadata
     * 
     * @return array Available layouts
     */
    public static function get_available_layouts() {
        return array(
            'lovepop' => array(
                'name' => 'Lovepop',
                'description' => __('Bold, dark gradient design with magenta accents', 'ensemble'),
                'icon' => '🎆',
                'style' => 'dark',
            ),
            'classic' => array(
                'name' => 'Classic',
                'description' => __('Clean, traditional layout with sidebar', 'ensemble'),
                'icon' => '📰',
                'style' => 'light',
            ),
            'modern' => array(
                'name' => 'Modern (Noir)',
                'description' => __('Full-width dark mode, minimal and refined', 'ensemble'),
                'icon' => '🌙',
                'style' => 'dark',
            ),
            'magazine' => array(
                'name' => 'Magazine',
                'description' => __('Editorial-style with large hero images', 'ensemble'),
                'icon' => '📖',
                'style' => 'light',
            ),
            'minimal' => array(
                'name' => 'Minimal',
                'description' => __('Clean, text-focused with minimal elements', 'ensemble'),
                'icon' => '✨',
                'style' => 'light',
            ),
            'stage' => array(
                'name' => 'Stage',
                'description' => __('Theater & cultural institution style', 'ensemble'),
                'icon' => '🎭',
                'style' => 'light',
            ),
            'club' => array(
                'name' => 'Club',
                'description' => __('Nightlife & club event design', 'ensemble'),
                'icon' => '🎵',
                'style' => 'dark',
            ),
            'agenda' => array(
                'name' => 'Agenda',
                'description' => __('List-style event agenda view', 'ensemble'),
                'icon' => '📋',
                'style' => 'light',
            ),
        );
    }
    
    /**
     * Layout Switcher Shortcode
     * Displays a dropdown/buttons to switch between layouts
     * 
     * Usage: [ensemble_layout_switcher style="dropdown|buttons|pills" default="lovepop"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function layout_switcher_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'pills',       // dropdown, buttons, pills
            'default' => 'lovepop',
            'show_icons' => 'true',
            'show_descriptions' => 'false',
            'layouts' => '',          // Comma-separated list to limit layouts
            'label' => __('Layout:', 'ensemble'),
        ), $atts, 'ensemble_layout_switcher');
        
        $available_layouts = self::get_available_layouts();
        $current_layout = self::get_current_demo_layout($atts['default']);
        $show_icons = filter_var($atts['show_icons'], FILTER_VALIDATE_BOOLEAN);
        $show_descriptions = filter_var($atts['show_descriptions'], FILTER_VALIDATE_BOOLEAN);
        
        // Filter layouts if specified
        if (!empty($atts['layouts'])) {
            $allowed = array_map('trim', explode(',', $atts['layouts']));
            $available_layouts = array_intersect_key($available_layouts, array_flip($allowed));
        }
        
        // Build current URL without layout parameter
        $current_url = remove_query_arg('es_layout');
        
        ob_start();
        ?>
        <div class="es-layout-switcher es-layout-switcher--<?php echo esc_attr($atts['style']); ?>">
            
            <?php if (!empty($atts['label'])): ?>
            <span class="es-layout-switcher__label"><?php echo esc_html($atts['label']); ?></span>
            <?php endif; ?>
            
            <?php if ($atts['style'] === 'dropdown'): ?>
            <!-- Dropdown Style -->
            <div class="es-layout-switcher__dropdown">
                <select onchange="window.location.href=this.value" class="es-layout-switcher__select">
                    <?php foreach ($available_layouts as $slug => $layout): 
                        $url = add_query_arg('es_layout', $slug, $current_url);
                        $selected = ($slug === $current_layout) ? 'selected' : '';
                    ?>
                    <option value="<?php echo esc_url($url); ?>" <?php echo $selected; ?>>
                        <?php if ($show_icons): ?><?php echo $layout['icon']; ?> <?php endif; ?>
                        <?php echo esc_html($layout['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php else: ?>
            <!-- Buttons/Pills Style -->
            <div class="es-layout-switcher__options">
                <?php foreach ($available_layouts as $slug => $layout): 
                    $url = add_query_arg('es_layout', $slug, $current_url);
                    $active = ($slug === $current_layout) ? 'es-layout-switcher__option--active' : '';
                    $style_class = 'es-layout-switcher__option--' . $layout['style'];
                ?>
                <a href="<?php echo esc_url($url); ?>" 
                   class="es-layout-switcher__option <?php echo $active; ?> <?php echo $style_class; ?>"
                   title="<?php echo esc_attr($layout['description']); ?>">
                    <?php if ($show_icons): ?>
                    <span class="es-layout-switcher__icon"><?php echo $layout['icon']; ?></span>
                    <?php endif; ?>
                    <span class="es-layout-switcher__name"><?php echo esc_html($layout['name']); ?></span>
                    <?php if ($show_descriptions): ?>
                    <span class="es-layout-switcher__desc"><?php echo esc_html($layout['description']); ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
        </div>
        
        <style>
        /* Layout Switcher Styles */
        .es-layout-switcher {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 32px;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .es-layout-switcher__label {
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.7;
        }
        
        /* Dropdown Style */
        .es-layout-switcher__select {
            padding: 10px 40px 10px 16px;
            font-size: 15px;
            font-weight: 500;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: inherit;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23fff' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }
        
        .es-layout-switcher__select:hover {
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .es-layout-switcher__select:focus {
            outline: none;
            border-color: var(--lp-primary, #e91e8c);
        }
        
        /* Buttons/Pills Style */
        .es-layout-switcher__options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .es-layout-switcher__option {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            color: inherit;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid transparent;
            border-radius: 24px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .es-layout-switcher--buttons .es-layout-switcher__option {
            border-radius: 8px;
        }
        
        .es-layout-switcher__option:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }
        
        .es-layout-switcher__option--active {
            background: var(--lp-primary, #e91e8c) !important;
            color: #fff !important;
            border-color: var(--lp-primary, #e91e8c);
        }
        
        .es-layout-switcher__icon {
            font-size: 16px;
        }
        
        .es-layout-switcher__desc {
            display: block;
            font-size: 11px;
            opacity: 0.7;
            font-weight: 400;
        }
        
        /* Light theme adjustments */
        body:not(:has(.es-lovepop-layout)):not(:has(.es-noir-single)) .es-layout-switcher {
            background: rgba(0, 0, 0, 0.03);
        }
        
        body:not(:has(.es-lovepop-layout)):not(:has(.es-noir-single)) .es-layout-switcher__select {
            border-color: rgba(0, 0, 0, 0.15);
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        }
        
        body:not(:has(.es-lovepop-layout)):not(:has(.es-noir-single)) .es-layout-switcher__option {
            background: rgba(0, 0, 0, 0.05);
        }
        
        body:not(:has(.es-lovepop-layout)):not(:has(.es-noir-single)) .es-layout-switcher__option:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        /* Mobile */
        @media (max-width: 768px) {
            .es-layout-switcher {
                flex-direction: column;
                align-items: stretch;
            }
            
            .es-layout-switcher__options {
                justify-content: center;
            }
            
            .es-layout-switcher__option {
                flex: 1;
                justify-content: center;
                min-width: 80px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Demo Page Shortcode
     * Displays a complete demo page with layout switcher and content
     * 
     * Usage: [ensemble_demo sections="hero,events,calendar,artists" default="lovepop"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function demo_page_shortcode($atts) {
        $atts = shortcode_atts(array(
            'default' => 'lovepop',
            'sections' => 'hero,events,calendar',  // Comma-separated sections to show
            'hero_count' => 3,
            'events_count' => 6,
            'artists_count' => 8,
            'switcher_style' => 'pills',
            'title' => __('Layout Demo', 'ensemble'),
        ), $atts, 'ensemble_demo');
        
        $current_layout = self::get_current_demo_layout($atts['default']);
        $sections = array_map('trim', explode(',', $atts['sections']));
        $layouts = self::get_available_layouts();
        $layout_info = isset($layouts[$current_layout]) ? $layouts[$current_layout] : $layouts['lovepop'];
        
        ob_start();
        ?>
        <div class="es-demo-page es-layout-<?php echo esc_attr($current_layout); ?>">
            
            <!-- Demo Header -->
            <div class="es-demo-header">
                <?php if (!empty($atts['title'])): ?>
                <h1 class="es-demo-title"><?php echo esc_html($atts['title']); ?></h1>
                <?php endif; ?>
                
                <div class="es-demo-current">
                    <span class="es-demo-current__icon"><?php echo $layout_info['icon']; ?></span>
                    <span class="es-demo-current__name"><?php echo esc_html($layout_info['name']); ?></span>
                    <span class="es-demo-current__desc"><?php echo esc_html($layout_info['description']); ?></span>
                </div>
                
                <?php echo do_shortcode('[ensemble_layout_switcher style="' . esc_attr($atts['switcher_style']) . '" default="' . esc_attr($atts['default']) . '" label=""]'); ?>
            </div>
            
            <!-- Demo Content -->
            <div class="es-demo-content">
                
                <?php if (in_array('hero', $sections)): ?>
                <!-- Hero Slider Section -->
                <section class="es-demo-section es-demo-section--hero">
                    <?php echo do_shortcode('[ensemble_events layout="hero" limit="' . intval($atts['hero_count']) . '" autoplay="true" show_filters="false" show_search="false"]'); ?>
                </section>
                <?php endif; ?>
                
                <?php if (in_array('events', $sections)): ?>
                <!-- Events Grid Section -->
                <section class="es-demo-section es-demo-section--events">
                    <h2 class="es-demo-section__title"><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                    <?php echo do_shortcode('[ensemble_events layout="grid" limit="' . intval($atts['events_count']) . '" columns="3" show_filters="false" show_search="false"]'); ?>
                </section>
                <?php endif; ?>
                
                <?php if (in_array('calendar', $sections)): ?>
                <!-- Calendar Section -->
                <section class="es-demo-section es-demo-section--calendar">
                    <h2 class="es-demo-section__title"><?php _e('Event Calendar', 'ensemble'); ?></h2>
                    <?php echo do_shortcode('[ensemble_calendar]'); ?>
                </section>
                <?php endif; ?>
                
                <?php if (in_array('artists', $sections)): ?>
                <!-- Artists Section -->
                <section class="es-demo-section es-demo-section--artists">
                    <h2 class="es-demo-section__title"><?php _e('Artists', 'ensemble'); ?></h2>
                    <?php echo do_shortcode('[ensemble_artists limit="' . intval($atts['artists_count']) . '"]'); ?>
                </section>
                <?php endif; ?>
                
                <?php if (in_array('locations', $sections)): ?>
                <!-- Locations Section -->
                <section class="es-demo-section es-demo-section--locations">
                    <h2 class="es-demo-section__title"><?php _e('Locations', 'ensemble'); ?></h2>
                    <?php echo do_shortcode('[ensemble_locations]'); ?>
                </section>
                <?php endif; ?>
                
            </div>
            
        </div>
        
        <style>
        /* Demo Page Styles */
        .es-demo-page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .es-demo-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, rgba(233, 30, 140, 0.1) 0%, rgba(102, 126, 234, 0.1) 100%);
            border-radius: 20px;
        }
        
        .es-demo-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0 0 20px;
            background: linear-gradient(135deg, #e91e8c 0%, #667eea 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .es-demo-current {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .es-demo-current__icon {
            font-size: 2rem;
        }
        
        .es-demo-current__name {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .es-demo-current__desc {
            font-size: 1rem;
            opacity: 0.7;
        }
        
        .es-demo-header .es-layout-switcher {
            justify-content: center;
            background: transparent;
            padding: 0;
        }
        
        .es-demo-section {
            margin-bottom: 60px;
        }
        
        .es-demo-section__title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid rgba(233, 30, 140, 0.2);
        }
        
        .es-demo-section--hero {
            margin-left: -20px;
            margin-right: -20px;
        }
        
        @media (max-width: 768px) {
            .es-demo-title {
                font-size: 1.75rem;
            }
            
            .es-demo-current__name {
                font-size: 1.25rem;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Preview Events Shortcode
     * Zeigt Events mit Status "preview" als kompakte Liste an
     * 
     * Usage: [ensemble_preview_events]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function preview_events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '-1',              // -1 = alle
            'title' => '',                // Optionale Überschrift (leer = Standard)
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
                            // Get short day name and ensure no trailing punctuation
                            $day_name = date_i18n('D', $timestamp); // Mo, Di, Mi... (may include dot in German)
                            $day_name = rtrim($day_name, '. '); // Remove any trailing dots or spaces
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
            background: var(--ensemble-card-bg, #f7fafc);
            border: 1px solid var(--ensemble-card-border, #e2e8f0);
            border-radius: var(--ensemble-card-radius, 8px);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .es-preview-toggle:hover {
            background: var(--ensemble-card-hover-bg, #edf2f7);
            border-color: var(--ensemble-primary, #667eea);
        }
        
        .es-preview-toggle-text {
            font-size: var(--ensemble-h3-size, 1.25rem);
            font-weight: var(--ensemble-heading-weight, 700);
            color: var(--ensemble-text, #1a202c);
        }
        
        .es-preview-count {
            font-weight: 500;
            color: var(--ensemble-text-secondary, #718096);
            margin-left: 8px;
        }
        
        .es-preview-toggle-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            color: var(--ensemble-primary, #667eea);
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
            overflow: hidden;
            transition: max-height 0.4s ease, opacity 0.3s ease;
            background: var(--ensemble-card-bg, #f7fafc);
            border: 1px solid var(--ensemble-card-border, #e2e8f0);
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
            border-bottom: 1px solid var(--ensemble-card-border, #e2e8f0);
            font-size: 1.125rem;
            color: var(--ensemble-text, #1a202c);
        }
        
        .es-preview-event-item:last-child {
            border-bottom: none;
        }
        
        .es-preview-date {
            font-weight: 700;
            font-family: var(--ensemble-font-heading, inherit);
            color: var(--ensemble-primary, #667eea);
            font-size: 1.125rem;
        }
        
        .es-preview-separator {
            color: var(--ensemble-text-muted, #a0aec0);
            font-size: 1.125rem;
        }
        
        .es-preview-location {
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--ensemble-text, #1a202c);
        }
        
        .es-preview-arrow {
            color: var(--ensemble-primary, #667eea);
            font-weight: 700;
            font-size: 1.125rem;
        }
        
        .es-preview-title {
            color: var(--ensemble-text-secondary, #718096);
            font-size: 1rem;
        }
        
        /* Dark Mode Support */
        .es-mode-dark .es-preview-toggle {
            background: var(--ensemble-card-bg, #1a1a2e);
            border-color: var(--ensemble-card-border, #333);
        }
        
        .es-mode-dark .es-preview-toggle:hover {
            background: var(--ensemble-card-hover-bg, #252540);
        }
        
        .es-mode-dark .es-preview-toggle-text,
        .es-mode-dark .es-preview-location {
            color: var(--ensemble-text, #ffffff);
        }
        
        .es-mode-dark .es-preview-content {
            background: var(--ensemble-card-bg, #1a1a2e);
            border-color: var(--ensemble-card-border, #333);
        }
        
        .es-mode-dark .es-preview-event-item {
            border-color: var(--ensemble-card-border, #333);
            color: var(--ensemble-text, #ffffff);
        }
        
        .es-mode-dark .es-preview-title {
            color: var(--ensemble-text-secondary, #999);
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
                
                // Toggle state
                toggle.setAttribute('aria-expanded', !isExpanded);
                
                if (isExpanded) {
                    // Collapse
                    content.hidden = true;
                    container.classList.remove('es-preview-expanded');
                    container.classList.add('es-preview-collapsed');
                } else {
                    // Expand
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