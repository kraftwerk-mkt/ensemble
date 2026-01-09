<?php
/**
 * Ensemble Slider Renderer
 * 
 * Handles slider and hero layouts for Events, Artists, and Locations
 * Integrates with existing shortcodes via layout attribute
 * 
 * @package Ensemble
 * @since 2.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Slider_Renderer {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Track if assets are enqueued
     */
    private static $assets_enqueued = false;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Nothing here - use init() for hooks
    }
    
    /**
     * Initialize hooks
     */
    public static function init() {
        // Register assets
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));
    }
    
    /**
     * Register slider assets (but don't enqueue yet)
     */
    public static function register_assets() {
        $plugin_url = plugins_url('', dirname(__FILE__));
        $version = defined('ENSEMBLE_VERSION') ? ENSEMBLE_VERSION : '2.8.0';
        
        wp_register_style(
            'ensemble-slider',
            $plugin_url . '/assets/css/ensemble-slider.css',
            array(),
            $version
        );
        
        wp_register_script(
            'ensemble-slider',
            $plugin_url . '/assets/js/ensemble-slider.js',
            array(),
            $version,
            true
        );
    }
    
    /**
     * Enqueue slider assets (called when slider is used)
     */
    public static function enqueue_assets() {
        if (self::$assets_enqueued) {
            return;
        }
        
        wp_enqueue_style('ensemble-slider');
        wp_enqueue_script('ensemble-slider');
        
        self::$assets_enqueued = true;
    }
    
    /**
     * Check if layout requires slider
     */
    public static function is_slider_layout($layout) {
        return in_array($layout, array('slider', 'hero', 'carousel'));
    }
    
    /**
     * Render slider wrapper start
     * 
     * @param string $layout     Layout type (slider, hero)
     * @param array  $options    Slider options
     * @param string $type       Content type (events, artists, locations)
     * @return string HTML
     */
    public static function render_wrapper_start($layout, $options = array(), $type = 'events') {
        self::enqueue_assets();
        
        $defaults = array(
            'slides_to_show' => 3,
            'slides_to_scroll' => 1,
            'autoplay' => false,
            'autoplay_speed' => 5000,
            'loop' => false,
            'dots' => true,
            'arrows' => true,
            'gap' => 24,
            'class' => '',
            'fullscreen' => false,
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Build data attributes
        $data_attrs = array(
            'data-slides-to-show' => $options['slides_to_show'],
            'data-slides-to-scroll' => $options['slides_to_scroll'],
            'data-autoplay' => $options['autoplay'] ? 'true' : 'false',
            'data-autoplay-speed' => $options['autoplay_speed'],
            'data-loop' => $options['loop'] ? 'true' : 'false',
            'data-dots' => $options['dots'] ? 'true' : 'false',
            'data-arrows' => $options['arrows'] ? 'true' : 'false',
            'data-gap' => $options['gap'],
        );
        
        $data_string = '';
        foreach ($data_attrs as $key => $value) {
            $data_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        // Determine wrapper class
        $wrapper_class = 'es-slider';
        if ($layout === 'hero') {
            $wrapper_class = 'es-hero-slider';
            if (!empty($options['fullscreen'])) {
                $wrapper_class .= ' es-hero-slider--fullscreen';
            }
        }
        
        if (!empty($options['class'])) {
            $wrapper_class .= ' ' . sanitize_html_class($options['class']);
        }
        
        $wrapper_class .= ' es-slider--' . esc_attr($type);
        
        $html = sprintf(
            '<div class="%s"%s>',
            esc_attr($wrapper_class),
            $data_string
        );
        
        $html .= '<div class="es-slider__track">';
        
        return $html;
    }
    
    /**
     * Render slider wrapper end
     */
    public static function render_wrapper_end() {
        $html = '</div>'; // Close track
        $html .= '</div>'; // Close wrapper
        return $html;
    }
    
    /**
     * Render slide item wrapper start
     */
    public static function render_slide_start($index = 0) {
        return sprintf(
            '<div class="es-slider__slide" data-slide-index="%d">',
            absint($index)
        );
    }
    
    /**
     * Render slide item wrapper end
     */
    public static function render_slide_end() {
        return '</div>';
    }
    
    /**
     * Render Hero Event Slide
     * 
     * Uses layout-specific template if available
     * 
     * @param array $event Event data
     * @param array $atts  Shortcode attributes
     * @return string HTML
     */
    public static function render_hero_event_slide($event, $atts = array()) {
        $defaults = array(
            'show_category' => true,
            'show_badge' => true,
            'show_date' => true,
            'show_time' => true,
            'show_location' => true,
            'show_excerpt' => true,
            'show_button' => true,
            'show_ticket_button' => true,
            'button_text' => __('View Event', 'ensemble'),
            'ticket_button_text' => __('Get Tickets', 'ensemble'),
        );
        
        $atts = wp_parse_args($atts, $defaults);
        
        // Get current layout
        $layout = 'modern'; // Default
        if (class_exists('ES_Layout_Sets')) {
            $layout = ES_Layout_Sets::get_active_set();
        }
        
        // Try to load layout-specific template
        $template = self::get_hero_slide_template($layout);
        
        if ($template) {
            // Use layout-specific template
            ob_start();
            include $template;
            return ob_get_clean();
        }
        
        // Fallback to default rendering
        return self::render_default_hero_slide($event, $atts);
    }
    
    /**
     * Get layout-specific hero slide template path
     * 
     * @param string $layout Layout name
     * @return string|false Template path or false if not found
     */
    public static function get_hero_slide_template($layout) {
        $template_file = 'hero-slide.php';
        
        // Check in theme first (for overrides)
        $theme_path = get_stylesheet_directory() . '/ensemble/layouts/' . $layout . '/' . $template_file;
        if (file_exists($theme_path)) {
            return $theme_path;
        }
        
        // Check in plugin
        $plugin_path = ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $layout . '/' . $template_file;
        if (file_exists($plugin_path)) {
            return $plugin_path;
        }
        
        return false;
    }
    
    /**
     * Render default hero slide (fallback)
     * 
     * @param array $event Event data
     * @param array $atts  Shortcode attributes
     * @return string HTML
     */
    public static function render_default_hero_slide($event, $atts) {
        // Determine badge text - Badge has priority, no category fallback
        $badge_text = '';
        $is_badge = false;
        
        // First check for custom badge text (overrides everything)
        if (!empty($event['badge_custom'])) {
            $badge_text = $event['badge_custom'];
            $is_badge = true;
        }
        // Then check for event badge from wizard
        elseif (!empty($event['badge']) && $event['badge'] !== 'none' && $event['badge'] !== '') {
            // Special case: show_category displays the event category as badge
            if ($event['badge'] === 'show_category') {
                if (!empty($event['categories']) && !is_wp_error($event['categories'])) {
                    $category = is_array($event['categories']) ? $event['categories'][0] : $event['categories'];
                    $badge_text = is_object($category) ? $category->name : $category;
                    $is_badge = false; // Category styling, not badge styling
                }
            } else {
                // Badge labels matching wizard values
                $badge_labels = array(
                    'sold_out' => __('Sold Out', 'ensemble'),
                    'few_tickets' => __('Few Tickets', 'ensemble'),
                    'free' => __('Free Entry', 'ensemble'),
                    'new' => __('New', 'ensemble'),
                    'premiere' => __('Premiere', 'ensemble'),
                    'last_show' => __('Last Show', 'ensemble'),
                    'special' => __('Special Event', 'ensemble'),
                    // Legacy values
                    'hot' => __('Hot', 'ensemble'),
                    'cancelled' => __('Cancelled', 'ensemble'),
                    'postponed' => __('Postponed', 'ensemble'),
                    'featured' => __('Featured', 'ensemble'),
                    'last_tickets' => __('Last Tickets', 'ensemble'),
                );
                if (isset($badge_labels[$event['badge']])) {
                    $badge_text = $badge_labels[$event['badge']];
                    $is_badge = true;
                }
            }
        }
        
        ob_start();
        
        // Check for hero video
        $has_video = !empty($event['hero_video_url']);
        $video_embed_url = '';
        $video_type = '';
        
        if ($has_video) {
            $video_url = $event['hero_video_url'];
            $autoplay = isset($event['hero_video_autoplay']) && $event['hero_video_autoplay'] ? '1' : '0';
            $loop = isset($event['hero_video_loop']) && $event['hero_video_loop'] ? '1' : '0';
            $controls = isset($event['hero_video_controls']) && $event['hero_video_controls'] ? '1' : '0';
            
            // YouTube
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $matches)) {
                $video_type = 'youtube';
                $params = array(
                    'autoplay' => $autoplay,
                    'mute' => '1', // Required for autoplay
                    'loop' => $loop,
                    'controls' => $controls,
                    'playlist' => $matches[1], // Required for loop
                    'rel' => '0',
                    'showinfo' => '0',
                    'modestbranding' => '1',
                );
                $video_embed_url = 'https://www.youtube.com/embed/' . $matches[1] . '?' . http_build_query($params);
            }
            // Vimeo
            elseif (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $video_url, $matches)) {
                $video_type = 'vimeo';
                $params = array(
                    'autoplay' => $autoplay,
                    'muted' => '1',
                    'loop' => $loop,
                    'controls' => $controls,
                    'background' => $autoplay && !$controls ? '1' : '0',
                );
                $video_embed_url = 'https://player.vimeo.com/video/' . $matches[1] . '?' . http_build_query($params);
            }
            // MP4/WebM direct
            elseif (preg_match('/\.(mp4|webm)$/i', $video_url)) {
                $video_type = 'direct';
            }
        }
        ?>
        <div class="es-hero-slide<?php echo $has_video ? ' es-hero-slide--has-video' : ''; ?>">
            <?php if ($has_video && $video_type === 'direct'): ?>
                <!-- Direct Video (MP4/WebM) -->
                <video class="es-hero-slide__video"
                       <?php echo $event['hero_video_autoplay'] ? 'autoplay' : ''; ?>
                       <?php echo $event['hero_video_loop'] ? 'loop' : ''; ?>
                       <?php echo $event['hero_video_controls'] ? 'controls' : ''; ?>
                       muted
                       playsinline>
                    <source src="<?php echo esc_url($event['hero_video_url']); ?>" type="video/<?php echo pathinfo($event['hero_video_url'], PATHINFO_EXTENSION); ?>">
                </video>
                <?php if (!empty($event['featured_image'])): ?>
                    <img src="<?php echo esc_url($event['featured_image']); ?>" 
                         alt="<?php echo esc_attr($event['title']); ?>" 
                         class="es-hero-slide__image es-hero-slide__image--fallback"
                         loading="lazy">
                <?php endif; ?>
            <?php elseif ($has_video && $video_embed_url): ?>
                <!-- Embedded Video (YouTube/Vimeo) -->
                <div class="es-hero-slide__video-wrapper">
                    <iframe src="<?php echo esc_url($video_embed_url); ?>"
                            class="es-hero-slide__video-iframe"
                            frameborder="0"
                            allow="autoplay; fullscreen; picture-in-picture"
                            allowfullscreen>
                    </iframe>
                </div>
                <?php if (!empty($event['featured_image'])): ?>
                    <img src="<?php echo esc_url($event['featured_image']); ?>" 
                         alt="<?php echo esc_attr($event['title']); ?>" 
                         class="es-hero-slide__image es-hero-slide__image--fallback"
                         loading="lazy">
                <?php endif; ?>
            <?php elseif (!empty($event['featured_image'])): ?>
                <img src="<?php echo esc_url($event['featured_image']); ?>" 
                     alt="<?php echo esc_attr($event['title']); ?>" 
                     class="es-hero-slide__image"
                     loading="lazy">
            <?php else: ?>
                <div class="es-hero-slide__image es-hero-slide__image--placeholder" 
                     style="background: linear-gradient(135deg, var(--ensemble-primary, #667eea), var(--ensemble-secondary, #764ba2));">
                </div>
            <?php endif; ?>
            
            <div class="es-hero-slide__content">
                <?php if (!empty($badge_text)): ?>
                    <span class="es-hero-slide__category<?php echo $is_badge ? ' es-hero-slide__badge' : ''; ?>"><?php echo esc_html($badge_text); ?></span>
                <?php endif; ?>
                
                <h2 class="es-hero-slide__title">
                    <a href="<?php echo esc_url($event['permalink']); ?>">
                        <?php echo esc_html($event['title']); ?>
                    </a>
                </h2>
                
                <div class="es-hero-slide__meta">
                    <?php if ($atts['show_date'] && !empty($event['start_date'])): ?>
                        <span class="es-hero-slide__meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event['start_date']))); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_time'] && !empty($event['start_time'])): ?>
                        <span class="es-hero-slide__meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php echo esc_html($event['start_time']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_location'] && !empty($event['location'])): ?>
                        <span class="es-hero-slide__meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php echo esc_html($event['location']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['price'])): ?>
                        <span class="es-hero-slide__meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            <?php echo esc_html($event['price']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($atts['show_excerpt'] && !empty($event['excerpt'])): ?>
                    <div class="es-hero-slide__excerpt">
                        <?php echo wp_trim_words($event['excerpt'], 25, '...'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="es-hero-slide__actions">
                    <?php if ($atts['show_button']): ?>
                        <a href="<?php echo esc_url($event['permalink']); ?>" 
                           class="es-hero-slide__btn es-hero-slide__btn--primary">
                            <?php echo esc_html($atts['button_text']); ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_ticket_button'] && !empty($event['ticket_url'])): ?>
                        <a href="<?php echo esc_url($event['ticket_url']); ?>" 
                           class="es-hero-slide__btn es-hero-slide__btn--secondary"
                           target="_blank"
                           rel="noopener noreferrer">
                            <?php echo esc_html($atts['ticket_button_text']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get responsive breakpoints from design settings
     */
    public static function get_responsive_breakpoints($base_columns = 3) {
        // Get from design settings if available
        $tablet_columns = 2;
        $mobile_columns = 1;
        
        if (class_exists('ES_Design_Settings')) {
            $settings = ES_Design_Settings::get_settings();
            if (!empty($settings['grid_columns_tablet'])) {
                $tablet_columns = intval($settings['grid_columns_tablet']);
            }
            if (!empty($settings['grid_columns_mobile'])) {
                $mobile_columns = intval($settings['grid_columns_mobile']);
            }
        }
        
        return array(
            array('breakpoint' => 1024, 'slidesToShow' => min($base_columns, $tablet_columns)),
            array('breakpoint' => 640, 'slidesToShow' => $mobile_columns),
        );
    }
    
    /**
     * Get slider gap from design settings
     */
    public static function get_slider_gap() {
        if (class_exists('ES_Design_Settings')) {
            $settings = ES_Design_Settings::get_settings();
            if (!empty($settings['card_gap'])) {
                return intval($settings['card_gap']);
            }
        }
        return 24; // Default
    }
}

// Initialize
ES_Slider_Renderer::init();
