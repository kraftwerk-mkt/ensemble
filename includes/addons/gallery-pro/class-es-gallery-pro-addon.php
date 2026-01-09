<?php
/**
 * Ensemble Gallery Pro Add-on
 * 
 * Advanced gallery functionality for events
 * - Multiple layouts: Grid, Masonry, Carousel, Justified, Filmstrip
 * - Lightbox with touch/swipe support
 * - Video support (YouTube, Vimeo)
 * - Image captions
 * - Fullscreen mode
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Gallery_Pro_Addon extends ES_Addon_Base {
    
    /**
     * Add-on configuration
     */
    protected $slug = 'gallery-pro';
    protected $name = 'Gallery Pro';
    protected $version = '1.1.0';
    
    /**
     * Available layouts
     * @var array
     */
    private $layouts = array(
        'grid'       => 'Grid',
        'masonry'    => 'Masonry',
        'carousel'   => 'Carousel',
        'filmstrip'  => 'Filmstrip',
    );
    
    /**
     * Lightbox themes
     * @var array
     */
    private $lightbox_themes = array(
        'dark'    => 'Dark',
        'light'   => 'Light',
        'minimal' => 'Minimal',
    );
    
    /**
     * Initialize add-on
     */
    protected function init() {
        $this->log('Gallery Pro add-on initialized');
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Template hooks - überschreibt die Standard-Gallery im Single-Event Template
        $this->register_template_hook('ensemble_gallery_area', array($this, 'render_event_gallery'), 10);
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_es_gallery_get_media', array($this, 'ajax_get_media'));
        add_action('wp_ajax_nopriv_es_gallery_get_media', array($this, 'ajax_get_media'));
        
        // Shortcodes
        add_shortcode('ensemble_gallery', array($this, 'shortcode_gallery'));
        add_shortcode('ensemble_gallery_pro', array($this, 'shortcode_gallery'));
        
        // Register REST API endpoints for gallery data
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Check if we're on an event page
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        $is_event_page = is_singular($post_type);
        
        // Also check for posts with ensemble_category
        if (!$is_event_page && is_singular('post')) {
            $terms = get_the_terms(get_the_ID(), 'ensemble_category');
            if ($terms && !is_wp_error($terms)) {
                $is_event_page = true;
            }
        }
        
        if (!$is_event_page && !$this->has_gallery_shortcode()) {
            return;
        }
        
        // GLightbox for lightbox functionality
        wp_enqueue_style(
            'glightbox',
            'https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/css/glightbox.min.css',
            array(),
            '3.2.0'
        );
        
        wp_enqueue_script(
            'glightbox',
            'https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/js/glightbox.min.js',
            array(),
            '3.2.0',
            true
        );
        
        // Swiper for carousel
        if ($this->get_setting('enable_carousel', true)) {
            wp_enqueue_style(
                'swiper',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
                array(),
                '11.0.0'
            );
            
            wp_enqueue_script(
                'swiper',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
                array(),
                '11.0.0',
                true
            );
        }
        
        // Gallery Pro styles and scripts
        wp_enqueue_style(
            'ensemble-gallery-pro',
            $this->get_addon_url() . 'assets/gallery-pro.css',
            array('glightbox'),
            $this->version
        );
        
        wp_enqueue_script(
            'ensemble-gallery-pro',
            $this->get_addon_url() . 'assets/gallery-pro.js',
            array('jquery', 'glightbox'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-gallery-pro', 'ensembleGalleryPro', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('ensemble_gallery_pro'),
            'defaultLayout' => $this->get_setting('default_layout', 'grid'),
            'lightbox'      => array(
                'theme'       => $this->get_setting('lightbox_theme', 'dark'),
                'loop'        => $this->get_setting('lightbox_loop', true),
                'autoplay'    => $this->get_setting('lightbox_autoplay', false),
                'slideEffect' => $this->get_setting('lightbox_effect', 'zoom'),
            ),
            'carousel'      => array(
                'autoplay'    => $this->get_setting('carousel_autoplay', true),
                'delay'       => $this->get_setting('carousel_delay', 5000),
                'loop'        => $this->get_setting('carousel_loop', true),
            ),
            'strings'       => array(
                'loading'     => __('Loading gallery...', 'ensemble'),
                'noImages'    => __('No images available', 'ensemble'),
                'prev'        => __('Vorheriges', 'ensemble'),
                'next'        => __('Next', 'ensemble'),
                'close'       => __('Close', 'ensemble'),
                'fullscreen'  => __('Vollbild', 'ensemble'),
                'slideOf'     => __('von', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'ensemble_page_ensemble-addons') {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_style(
            'ensemble-gallery-pro-admin',
            $this->get_addon_url() . 'assets/gallery-pro-admin.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'ensemble-gallery-pro-admin',
            $this->get_addon_url() . 'assets/gallery-pro-admin.js',
            array('jquery'),
            $this->version,
            true
        );
    }
    
    /**
     * Check if current page has gallery shortcode
     * 
     * @return bool
     */
    private function has_gallery_shortcode() {
        global $post;
        return $post && (has_shortcode($post->post_content, 'ensemble_gallery') || 
                         has_shortcode($post->post_content, 'ensemble_gallery_pro'));
    }
    
    /**
     * Render event gallery
     * Called via template hook 'ensemble_gallery_area'
     * 
     * @param int $event_id
     * @param array $existing_gallery Gallery data from template (optional)
     */
    public function render_event_gallery($event_id, $existing_gallery = array()) {
        // Prevent infinite loops
        static $rendering = array();
        if (isset($rendering[$event_id])) {
            return;
        }
        $rendering[$event_id] = true;
        
        // Check display settings
        if (function_exists('ensemble_show_addon') && !ensemble_show_addon('gallery')) {
            unset($rendering[$event_id]);
            return;
        }
        
        // Ensure existing_gallery is an array
        if (!is_array($existing_gallery)) {
            $existing_gallery = array();
        }
        
        // Nutze existierende Gallery-Daten aus dem Template oder lade neu
        if (!empty($existing_gallery)) {
            // Konvertiere das Template-Format in unser Format
            $gallery = array('items' => array());
            foreach ($existing_gallery as $image) {
                // Skip invalid entries
                if (empty($image)) {
                    continue;
                }
                
                // Check if $image is an array or an ID
                if (is_numeric($image)) {
                    // Es ist eine Bild-ID - lade die Bild-Daten
                    $image_id = intval($image);
                    if ($image_id <= 0) continue;
                    
                    $image_url = wp_get_attachment_url($image_id);
                    if (empty($image_url)) continue;
                    
                    $image_data = array(
                        'type'        => 'image',
                        'id'          => $image_id,
                        'url'         => $image_url ?: '',
                        'thumb'       => wp_get_attachment_image_url($image_id, 'medium') ?: $image_url,
                        'large'       => wp_get_attachment_image_url($image_id, 'large') ?: $image_url,
                        'full'        => $image_url ?: '',
                        'alt'         => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: '',
                        'title'       => get_the_title($image_id) ?: '',
                        'caption'     => wp_get_attachment_caption($image_id) ?: '',
                        'description' => '',
                        'width'       => 0,
                        'height'      => 0,
                    );
                } elseif (is_array($image)) {
                    // Es ist bereits ein Array mit Bild-Daten
                    $image_data = array(
                        'type'        => 'image',
                        'id'          => $image['id'] ?? $image['ID'] ?? 0,
                        'url'         => $image['url'] ?? $image['URL'] ?? '',
                        'thumb'       => $image['medium'] ?? $image['sizes']['medium'] ?? $image['thumb'] ?? $image['url'] ?? '',
                        'large'       => $image['large'] ?? $image['sizes']['large'] ?? $image['url'] ?? '',
                        'full'        => $image['full'] ?? $image['url'] ?? '',
                        'alt'         => $image['alt'] ?? '',
                        'title'       => $image['title'] ?? '',
                        'caption'     => $image['caption'] ?? '',
                        'description' => '',
                        'width'       => 0,
                        'height'      => 0,
                    );
                    
                    // Skip if no URL
                    if (empty($image_data['url']) && empty($image_data['full'])) {
                        continue;
                    }
                } else {
                    // Unbekanntes Format - überspringen
                    continue;
                }
                
                $gallery['items'][] = $image_data;
            }
            
            // Additionally load videos (if available)
            $videos = $this->get_event_videos($event_id);
            if (!empty($videos)) {
                $gallery['items'] = array_merge($gallery['items'], $videos);
            }
        } else {
            // Lade komplett neu
            $gallery = $this->get_event_gallery($event_id);
        }
        
        if (empty($gallery['items'])) {
            unset($rendering[$event_id]);
            return;
        }
        
        $layout = $this->get_setting('default_layout', 'grid');
        $columns = $this->get_setting('default_columns', 4);
        $show_captions = $this->get_setting('show_captions', true);
        
        // Render mit Section-Wrapper (wie im Original-Template)
        $show_header = !function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('gallery');
        ?>
        <div class="es-section es-gallery-section es-gallery-pro-section">
            <?php if ($show_header): ?>
            <h2 class="es-section-title">
                <span class="dashicons dashicons-format-gallery"></span>
                <?php _e('Gallery', 'ensemble'); ?>
            </h2>
            <?php endif; ?>
            <?php
            echo $this->render_gallery($gallery, array(
                'layout'        => $layout,
                'columns'       => $columns,
                'show_captions' => $show_captions,
                'lightbox'      => true,
                'event_id'      => $event_id,
            ));
            ?>
        </div>
        <?php
        
        unset($rendering[$event_id]);
    }
    
    /**
     * Get event videos
     * 
     * @param int $event_id
     * @return array
     */
    private function get_event_videos($event_id) {
        $videos = array();
        
        // Try ACF videos field
        if (function_exists('get_field')) {
            $acf_videos = get_field('videos', $event_id);
            if (!empty($acf_videos)) {
                foreach ($acf_videos as $video) {
                    $video_item = $this->format_video_item($video);
                    if ($video_item) {
                        $videos[] = $video_item;
                    }
                }
            }
        }
        
        // Fallback to meta
        if (empty($videos)) {
            $meta_videos = get_post_meta($event_id, 'event_videos', true);
            if (!empty($meta_videos) && is_array($meta_videos)) {
                foreach ($meta_videos as $video) {
                    $video_item = $this->format_video_item($video);
                    if ($video_item) {
                        $videos[] = $video_item;
                    }
                }
            }
        }
        
        return $videos;
    }
    
    /**
     * Render gallery HTML
     * 
     * @param array $gallery
     * @param array $args
     * @return string
     */
    public function render_gallery($gallery, $args = array()) {
        $defaults = array(
            'layout'        => 'grid',
            'columns'       => 4,
            'show_captions' => true,
            'lightbox'      => true,
            'event_id'      => 0,
            'class'         => '',
            'max_items'     => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        $items = $gallery['items'] ?? array();
        
        if (empty($items)) {
            return '';
        }
        
        // Limit items if needed
        if ($args['max_items'] > 0) {
            $items = array_slice($items, 0, $args['max_items']);
        }
        
        return $this->load_template('gallery-' . $args['layout'], array(
            'items'         => $items,
            'gallery_id'    => 'es-gallery-' . ($args['event_id'] ?: uniqid()),
            'columns'       => $args['columns'],
            'show_captions' => $args['show_captions'],
            'lightbox'      => $args['lightbox'],
            'extra_class'   => $args['class'],
            'layout'        => $args['layout'],
        ));
    }
    
    /**
     * Get event gallery data
     * 
     * @param int $event_id
     * @return array
     */
    public function get_event_gallery($event_id) {
        $gallery = array(
            'items' => array(),
            'title' => '',
        );
        
        // Try ACF gallery field first
        if (function_exists('get_field')) {
            $acf_gallery = get_field('gallery', $event_id);
            
            if (!empty($acf_gallery)) {
                foreach ($acf_gallery as $image) {
                    $gallery['items'][] = $this->format_image_item($image);
                }
            }
            
            // Check for videos field
            $videos = get_field('videos', $event_id);
            if (!empty($videos)) {
                foreach ($videos as $video) {
                    $video_item = $this->format_video_item($video);
                    if ($video_item) {
                        $gallery['items'][] = $video_item;
                    }
                }
            }
        }
        
        // Fallback to native meta
        if (empty($gallery['items'])) {
            $native_gallery = get_post_meta($event_id, 'event_gallery', true);
            
            if (!empty($native_gallery)) {
                if (is_array($native_gallery)) {
                    foreach ($native_gallery as $item) {
                        if (is_numeric($item)) {
                            $attachment = get_post($item);
                            if ($attachment) {
                                $gallery['items'][] = $this->format_attachment_item($item);
                            }
                        } elseif (is_array($item)) {
                            if (!empty($item['video_url'])) {
                                $video_item = $this->format_video_item($item);
                                if ($video_item) {
                                    $gallery['items'][] = $video_item;
                                }
                            } else {
                                $gallery['items'][] = $item;
                            }
                        }
                    }
                }
            }
        }
        
        // Get attached media as fallback
        if (empty($gallery['items'])) {
            $attachments = get_attached_media('image', $event_id);
            
            foreach ($attachments as $attachment) {
                $gallery['items'][] = $this->format_attachment_item($attachment->ID);
            }
        }
        
        return $gallery;
    }
    
    /**
     * Format ACF image to gallery item
     * 
     * @param array $image
     * @return array
     */
    private function format_image_item($image) {
        return array(
            'type'        => 'image',
            'id'          => $image['ID'] ?? 0,
            'url'         => $image['url'] ?? '',
            'thumb'       => $image['sizes']['medium'] ?? $image['url'],
            'large'       => $image['sizes']['large'] ?? $image['url'],
            'full'        => $image['url'] ?? '',
            'alt'         => $image['alt'] ?? '',
            'title'       => $image['title'] ?? '',
            'caption'     => $image['caption'] ?? '',
            'description' => $image['description'] ?? '',
            'width'       => $image['width'] ?? 0,
            'height'      => $image['height'] ?? 0,
        );
    }
    
    /**
     * Format attachment ID to gallery item
     * 
     * @param int $attachment_id
     * @return array
     */
    private function format_attachment_item($attachment_id) {
        $attachment = get_post($attachment_id);
        
        return array(
            'type'        => 'image',
            'id'          => $attachment_id,
            'url'         => wp_get_attachment_url($attachment_id),
            'thumb'       => wp_get_attachment_image_url($attachment_id, 'medium'),
            'large'       => wp_get_attachment_image_url($attachment_id, 'large'),
            'full'        => wp_get_attachment_url($attachment_id),
            'alt'         => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'title'       => $attachment ? $attachment->post_title : '',
            'caption'     => $attachment ? $attachment->post_excerpt : '',
            'description' => $attachment ? $attachment->post_content : '',
            'width'       => 0,
            'height'      => 0,
        );
    }
    
    /**
     * Format video to gallery item
     * 
     * @param mixed $video
     * @return array|false
     */
    private function format_video_item($video) {
        $video_url = '';
        $title = '';
        $thumb = '';
        
        if (is_string($video)) {
            $video_url = $video;
        } elseif (is_array($video)) {
            $video_url = $video['url'] ?? $video['video_url'] ?? '';
            $title = $video['title'] ?? '';
            $thumb = $video['thumbnail'] ?? '';
        }
        
        if (empty($video_url)) {
            return false;
        }
        
        $video_data = $this->parse_video_url($video_url);
        
        if (!$video_data) {
            return false;
        }
        
        // Get thumbnail if not provided
        if (empty($thumb)) {
            $thumb = $this->get_video_thumbnail($video_data['provider'], $video_data['id']);
        }
        
        return array(
            'type'        => 'video',
            'provider'    => $video_data['provider'],
            'video_id'    => $video_data['id'],
            'url'         => $video_url,
            'embed_url'   => $video_data['embed_url'],
            'thumb'       => $thumb,
            'title'       => $title,
            'caption'     => '',
            'description' => '',
        );
    }
    
    /**
     * Parse video URL to get provider and ID
     * 
     * @param string $url
     * @return array|false
     */
    private function parse_video_url($url) {
        // YouTube patterns
        $youtube_patterns = array(
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
        );
        
        foreach ($youtube_patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return array(
                    'provider'  => 'youtube',
                    'id'        => $matches[1],
                    'embed_url' => "https://www.youtube.com/embed/{$matches[1]}",
                );
            }
        }
        
        // Vimeo patterns
        $vimeo_patterns = array(
            '/vimeo\.com\/([0-9]+)/',
            '/player\.vimeo\.com\/video\/([0-9]+)/',
        );
        
        foreach ($vimeo_patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return array(
                    'provider'  => 'vimeo',
                    'id'        => $matches[1],
                    'embed_url' => "https://player.vimeo.com/video/{$matches[1]}",
                );
            }
        }
        
        return false;
    }
    
    /**
     * Get video thumbnail
     * 
     * @param string $provider
     * @param string $video_id
     * @return string
     */
    private function get_video_thumbnail($provider, $video_id) {
        if ($provider === 'youtube') {
            return "https://img.youtube.com/vi/{$video_id}/hqdefault.jpg";
        }
        
        if ($provider === 'vimeo') {
            // Vimeo requires API call, use placeholder or cached value
            $cached = get_transient("es_vimeo_thumb_{$video_id}");
            if ($cached) {
                return $cached;
            }
            
            // Fallback placeholder
            return '';
        }
        
        return '';
    }
    
    /**
     * Shortcode handler
     * 
     * @param array $atts
     * @return string
     */
    public function shortcode_gallery($atts) {
        $atts = shortcode_atts(array(
            'event'        => 0,
            'event_id'     => 0, // alias
            'layout'       => $this->get_setting('default_layout', 'grid'),
            'columns'      => $this->get_setting('default_columns', 4),
            'captions'     => $this->get_setting('show_captions', true),
            'lightbox'     => true,
            'max'          => 0,
            'class'        => '',
            'ids'          => '', // comma-separated attachment IDs
        ), $atts, 'ensemble_gallery');
        
        // Get event ID
        $event_id = intval($atts['event'] ?: $atts['event_id']);
        
        if (!$event_id) {
            $event_id = get_the_ID();
        }
        
        // Custom IDs mode
        if (!empty($atts['ids'])) {
            $ids = array_map('intval', explode(',', $atts['ids']));
            $gallery = array('items' => array());
            
            foreach ($ids as $id) {
                $attachment = get_post($id);
                if ($attachment && wp_attachment_is_image($id)) {
                    $gallery['items'][] = $this->format_attachment_item($id);
                }
            }
        } else {
            $gallery = $this->get_event_gallery($event_id);
        }
        
        if (empty($gallery['items'])) {
            return '';
        }
        
        return $this->render_gallery($gallery, array(
            'layout'        => $atts['layout'],
            'columns'       => intval($atts['columns']),
            'show_captions' => filter_var($atts['captions'], FILTER_VALIDATE_BOOLEAN),
            'lightbox'      => filter_var($atts['lightbox'], FILTER_VALIDATE_BOOLEAN),
            'event_id'      => $event_id,
            'class'         => sanitize_html_class($atts['class']),
            'max_items'     => intval($atts['max']),
        ));
    }
    
    /**
     * AJAX: Get media data
     */
    public function ajax_get_media() {
        check_ajax_referer('ensemble_gallery_pro', 'nonce');
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Event ID erforderlich', 'ensemble')));
        }
        
        $gallery = $this->get_event_gallery($event_id);
        
        wp_send_json_success($gallery);
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('ensemble/v1', '/gallery/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_gallery'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
    }
    
    /**
     * REST API: Get gallery
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_gallery($request) {
        $event_id = $request->get_param('id');
        $gallery = $this->get_event_gallery($event_id);
        
        return new WP_REST_Response($gallery, 200);
    }
    
    /**
     * Render settings page
     * 
     * @return string
     */
    public function render_settings() {
        return $this->load_template('settings', array(
            'settings'        => $this->settings,
            'layouts'         => $this->layouts,
            'lightbox_themes' => $this->lightbox_themes,
        ));
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $settings
     * @return array
     */
    public function sanitize_settings($settings) {
        $sanitized = array();
        
        // Layout settings
        $sanitized['default_layout'] = isset($settings['default_layout']) && 
            array_key_exists($settings['default_layout'], $this->layouts) ? 
            $settings['default_layout'] : 'grid';
        
        $sanitized['default_columns'] = isset($settings['default_columns']) ? 
            min(max(intval($settings['default_columns']), 2), 8) : 4;
        
        $sanitized['show_captions'] = isset($settings['show_captions']) ? 
            (bool)$settings['show_captions'] : true;
        
        // Lightbox settings
        $sanitized['lightbox_theme'] = isset($settings['lightbox_theme']) && 
            array_key_exists($settings['lightbox_theme'], $this->lightbox_themes) ? 
            $settings['lightbox_theme'] : 'dark';
        
        $sanitized['lightbox_loop'] = isset($settings['lightbox_loop']) ? 
            (bool)$settings['lightbox_loop'] : true;
        
        $sanitized['lightbox_autoplay'] = isset($settings['lightbox_autoplay']) ? 
            (bool)$settings['lightbox_autoplay'] : false;
        
        $sanitized['lightbox_effect'] = isset($settings['lightbox_effect']) ? 
            sanitize_text_field($settings['lightbox_effect']) : 'zoom';
        
        // Carousel settings
        $sanitized['enable_carousel'] = isset($settings['enable_carousel']) ? 
            (bool)$settings['enable_carousel'] : true;
        
        $sanitized['carousel_autoplay'] = isset($settings['carousel_autoplay']) ? 
            (bool)$settings['carousel_autoplay'] : true;
        
        $sanitized['carousel_delay'] = isset($settings['carousel_delay']) ? 
            max(intval($settings['carousel_delay']), 1000) : 5000;
        
        $sanitized['carousel_loop'] = isset($settings['carousel_loop']) ? 
            (bool)$settings['carousel_loop'] : true;
        
        // Video settings
        $sanitized['enable_videos'] = isset($settings['enable_videos']) ? 
            (bool)$settings['enable_videos'] : true;
        
        $sanitized['video_autoplay'] = isset($settings['video_autoplay']) ? 
            (bool)$settings['video_autoplay'] : false;
        
        return $sanitized;
    }
    
    /**
     * Get available layouts
     * 
     * @return array
     */
    public function get_layouts() {
        return $this->layouts;
    }
    
    /**
     * Get layout icon SVG
     * 
     * @param string $layout
     * @return string
     */
    public function get_layout_icon($layout) {
        $icons = array(
            'grid' => '<svg viewBox="0 0 50 40"><rect x="2" y="2" width="14" height="11" rx="1" fill="currentColor"/><rect x="18" y="2" width="14" height="11" rx="1" fill="currentColor"/><rect x="34" y="2" width="14" height="11" rx="1" fill="currentColor"/><rect x="2" y="15" width="14" height="11" rx="1" fill="currentColor"/><rect x="18" y="15" width="14" height="11" rx="1" fill="currentColor"/><rect x="34" y="15" width="14" height="11" rx="1" fill="currentColor"/><rect x="2" y="28" width="14" height="11" rx="1" fill="currentColor"/><rect x="18" y="28" width="14" height="11" rx="1" fill="currentColor"/><rect x="34" y="28" width="14" height="11" rx="1" fill="currentColor"/></svg>',
            'masonry' => '<svg viewBox="0 0 50 40"><rect x="2" y="2" width="14" height="16" rx="1" fill="currentColor"/><rect x="18" y="2" width="14" height="10" rx="1" fill="currentColor"/><rect x="34" y="2" width="14" height="22" rx="1" fill="currentColor"/><rect x="2" y="20" width="14" height="18" rx="1" fill="currentColor"/><rect x="18" y="14" width="14" height="24" rx="1" fill="currentColor"/><rect x="34" y="26" width="14" height="12" rx="1" fill="currentColor"/></svg>',
            'carousel' => '<svg viewBox="0 0 50 40"><rect x="6" y="5" width="38" height="24" rx="2" fill="currentColor"/><circle cx="25" cy="35" r="2" fill="currentColor"/><circle cx="19" cy="35" r="1.5" fill="currentColor" opacity="0.5"/><circle cx="31" cy="35" r="1.5" fill="currentColor" opacity="0.5"/><path d="M3 17l4-4v8z" fill="currentColor" opacity="0.7"/><path d="M47 17l-4-4v8z" fill="currentColor" opacity="0.7"/></svg>',
            'filmstrip' => '<svg viewBox="0 0 50 40"><rect x="0" y="5" width="50" height="30" rx="1" fill="currentColor" opacity="0.2"/><rect x="4" y="10" width="12" height="20" rx="1" fill="currentColor"/><rect x="19" y="10" width="12" height="20" rx="1" fill="currentColor"/><rect x="34" y="10" width="12" height="20" rx="1" fill="currentColor"/><circle cx="7" cy="7" r="1.5" fill="currentColor"/><circle cx="13" cy="7" r="1.5" fill="currentColor"/><circle cx="25" cy="7" r="1.5" fill="currentColor"/><circle cx="40" cy="7" r="1.5" fill="currentColor"/><circle cx="7" cy="33" r="1.5" fill="currentColor"/><circle cx="13" cy="33" r="1.5" fill="currentColor"/><circle cx="25" cy="33" r="1.5" fill="currentColor"/><circle cx="40" cy="33" r="1.5" fill="currentColor"/></svg>',
        );
        
        return $icons[$layout] ?? '';
    }
}
