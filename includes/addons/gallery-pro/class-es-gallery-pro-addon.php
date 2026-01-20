<?php
/**
 * Ensemble Gallery Pro Add-on
 * 
 * Advanced gallery functionality for events, artists, and locations
 * - Multiple layouts: Grid, Masonry, Carousel, Filmstrip
 * - Lightbox with touch/swipe support
 * - Video support (YouTube, Vimeo, Self-hosted)
 * - Integration with Gallery Manager (linked galleries)
 * - Support for Events, Artists, and Locations
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 2.0.0
 * @updated 3.0.0 - Added video support, artist/location integration
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
    protected $version = '3.0.0';
    
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
     * Supported video extensions
     * @var array
     */
    private $video_extensions = array('mp4', 'webm', 'ogg', 'mov', 'm4v');
    
    /**
     * Initialize add-on
     */
    protected function init() {
        $this->log('Gallery Pro add-on initialized (v3.0 with video support)');
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Template hooks - Events
        $this->register_template_hook('ensemble_gallery_area', array($this, 'render_event_gallery'), 10);
        
        // Template hooks - Artists
        $this->register_template_hook('ensemble_artist_gallery_area', array($this, 'render_artist_gallery'), 10);
        add_action('ensemble_artist_after_content', array($this, 'render_artist_gallery_fallback'), 20);
        
        // Template hooks - Locations
        $this->register_template_hook('ensemble_location_gallery_area', array($this, 'render_location_gallery'), 10);
        add_action('ensemble_location_after_content', array($this, 'render_location_gallery_fallback'), 20);
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_es_gallery_get_media', array($this, 'ajax_get_media'));
        add_action('wp_ajax_nopriv_es_gallery_get_media', array($this, 'ajax_get_media'));
        add_action('wp_ajax_es_gallery_get_video_info', array($this, 'ajax_get_video_info'));
        
        // Shortcodes
        add_shortcode('ensemble_gallery', array($this, 'shortcode_gallery'));
        add_shortcode('ensemble_gallery_pro', array($this, 'shortcode_gallery'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Check if we're on a relevant page
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        $is_relevant_page = is_singular($post_type) || 
                            is_singular('ensemble_artist') || 
                            is_singular('ensemble_location') ||
                            is_singular('ensemble_gallery');
        
        // Also check for posts with ensemble_category
        if (!$is_relevant_page && is_singular('post')) {
            $terms = get_the_terms(get_the_ID(), 'ensemble_category');
            if ($terms && !is_wp_error($terms)) {
                $is_relevant_page = true;
            }
        }
        
        if (!$is_relevant_page && !$this->has_gallery_shortcode()) {
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
                'prev'        => __('Previous', 'ensemble'),
                'next'        => __('Next', 'ensemble'),
                'close'       => __('Close', 'ensemble'),
                'fullscreen'  => __('Fullscreen', 'ensemble'),
                'slideOf'     => __('of', 'ensemble'),
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
     * @return bool
     */
    private function has_gallery_shortcode() {
        global $post;
        return $post && (has_shortcode($post->post_content, 'ensemble_gallery') || 
                         has_shortcode($post->post_content, 'ensemble_gallery_pro'));
    }
    
    /**
     * Render event gallery
     * @param int $event_id
     * @param array $existing_gallery
     */
    public function render_event_gallery($event_id, $existing_gallery = array()) {
        $this->render_entity_gallery('event', $event_id, $existing_gallery);
    }
    
    /**
     * Render artist gallery
     * @param int $artist_id
     */
    public function render_artist_gallery($artist_id) {
        $this->render_entity_gallery('artist', $artist_id);
    }
    
    /**
     * Render artist gallery fallback (if no template hook)
     */
    public function render_artist_gallery_fallback() {
        if (!is_singular('ensemble_artist')) {
            return;
        }
        $this->render_entity_gallery('artist', get_the_ID());
    }
    
    /**
     * Render location gallery
     * @param int $location_id
     */
    public function render_location_gallery($location_id) {
        $this->render_entity_gallery('location', $location_id);
    }
    
    /**
     * Render location gallery fallback (if no template hook)
     */
    public function render_location_gallery_fallback() {
        if (!is_singular('ensemble_location')) {
            return;
        }
        $this->render_entity_gallery('location', get_the_ID());
    }
    
    /**
     * Render gallery for any entity type
     * @param string $entity_type 'event', 'artist', or 'location'
     * @param int $entity_id
     * @param array $existing_gallery Optional existing gallery data
     */
    private function render_entity_gallery($entity_type, $entity_id, $existing_gallery = array()) {
        // Prevent infinite loops
        static $rendering = array();
        $key = "{$entity_type}_{$entity_id}";
        if (isset($rendering[$key])) {
            return;
        }
        $rendering[$key] = true;
        
        // Check display settings
        if (function_exists('ensemble_show_addon') && !ensemble_show_addon('gallery')) {
            unset($rendering[$key]);
            return;
        }
        
        // Get gallery data
        $gallery = $this->get_entity_gallery($entity_type, $entity_id, $existing_gallery);
        
        if (empty($gallery['items'])) {
            unset($rendering[$key]);
            return;
        }
        
        $layout = $this->get_setting('default_layout', 'grid');
        $columns = $this->get_setting('default_columns', 4);
        $show_captions = $this->get_setting('show_captions', true);
        
        // Section title based on entity type
        $section_titles = array(
            'event'    => __('Gallery', 'ensemble'),
            'artist'   => __('Gallery', 'ensemble'),
            'location' => __('Gallery', 'ensemble'),
        );
        $section_title = $section_titles[$entity_type] ?? __('Gallery', 'ensemble');
        
        // Render with section wrapper
        $show_header = !function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('gallery');
        ?>
        <div class="es-section es-gallery-section es-gallery-pro-section">
            <?php if ($show_header): ?>
            <h2 class="es-section-title">
                <span class="dashicons dashicons-format-gallery"></span>
                <?php echo esc_html($section_title); ?>
            </h2>
            <?php endif; ?>
            <?php
            echo $this->render_gallery($gallery, array(
                'layout'        => $layout,
                'columns'       => $columns,
                'show_captions' => $show_captions,
                'lightbox'      => true,
                'event_id'      => $entity_id,
                'entity_type'   => $entity_type,
            ));
            ?>
        </div>
        <?php
        
        unset($rendering[$key]);
    }
    
    /**
     * Get gallery data for any entity type
     * @param string $entity_type
     * @param int $entity_id
     * @param array $existing_gallery
     * @return array
     */
    private function get_entity_gallery($entity_type, $entity_id, $existing_gallery = array()) {
        $gallery = array(
            'items' => array(),
        );
        
        // Ensure existing_gallery is an array
        if (!is_array($existing_gallery)) {
            $existing_gallery = array();
        }
        
        // 1. Process existing/inline gallery data
        if (!empty($existing_gallery)) {
            foreach ($existing_gallery as $image) {
                $item = $this->normalize_image_item($image);
                if ($item) {
                    $gallery['items'][] = $item;
                }
            }
        } else {
            // Load from meta/ACF
            $gallery['items'] = $this->load_entity_gallery($entity_type, $entity_id);
        }
        
        // 2. Load videos for this entity
        $videos = $this->get_entity_videos($entity_type, $entity_id);
        if (!empty($videos)) {
            $gallery['items'] = array_merge($gallery['items'], $videos);
        }
        
        return $gallery;
    }
    
    /**
     * Load gallery from entity meta/ACF
     * @param string $entity_type
     * @param int $entity_id
     * @return array
     */
    private function load_entity_gallery($entity_type, $entity_id) {
        $items = array();
        
        // Try ACF gallery field
        if (function_exists('get_field')) {
            $acf_gallery = get_field('gallery', $entity_id);
            
            if (!empty($acf_gallery)) {
                foreach ($acf_gallery as $image) {
                    $item = $this->format_acf_image($image);
                    if ($item) {
                        $items[] = $item;
                    }
                }
            }
        }
        
        // Fallback to native meta
        if (empty($items)) {
            $meta_key = $entity_type . '_gallery';
            if ($entity_type === 'event') {
                $meta_key = 'event_gallery';
            }
            
            $native_gallery = get_post_meta($entity_id, $meta_key, true);
            
            if (!empty($native_gallery) && is_array($native_gallery)) {
                foreach ($native_gallery as $item) {
                    $formatted = $this->normalize_image_item($item);
                    if ($formatted) {
                        $items[] = $formatted;
                    }
                }
            }
        }
        
        // Fallback to attached media
        if (empty($items)) {
            $attachments = get_attached_media('image', $entity_id);
            
            foreach ($attachments as $attachment) {
                $items[] = $this->format_attachment_item($attachment->ID);
            }
        }
        
        return $items;
    }
    
    /**
     * Get videos for entity
     * @param string $entity_type
     * @param int $entity_id
     * @return array
     */
    private function get_entity_videos($entity_type, $entity_id) {
        $videos = array();
        
        // Try ACF videos field
        if (function_exists('get_field')) {
            $acf_videos = get_field('videos', $entity_id);
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
            $meta_key = $entity_type . '_videos';
            if ($entity_type === 'event') {
                $meta_key = 'event_videos';
            }
            
            $meta_videos = get_post_meta($entity_id, $meta_key, true);
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
     * Normalize image item from various formats
     * @param mixed $image
     * @return array|false
     */
    private function normalize_image_item($image) {
        if (empty($image)) {
            return false;
        }
        
        if (is_numeric($image)) {
            return $this->format_attachment_item(intval($image));
        }
        
        if (is_array($image)) {
            // Check if it's a video item
            if (!empty($image['video_url']) || !empty($image['type']) && $image['type'] === 'video') {
                return $this->format_video_item($image);
            }
            
            // Image array
            $id = $image['id'] ?? $image['ID'] ?? 0;
            $url = $image['url'] ?? $image['URL'] ?? '';
            
            if (empty($url) && $id) {
                $url = wp_get_attachment_url($id);
            }
            
            if (empty($url)) {
                return false;
            }
            
            return array(
                'type'        => 'image',
                'id'          => $id,
                'url'         => $url,
                'thumb'       => $image['medium'] ?? $image['sizes']['medium'] ?? $image['thumb'] ?? $url,
                'large'       => $image['large'] ?? $image['sizes']['large'] ?? $url,
                'full'        => $image['full'] ?? $url,
                'alt'         => $image['alt'] ?? '',
                'title'       => $image['title'] ?? '',
                'caption'     => $image['caption'] ?? '',
                'description' => $image['description'] ?? '',
                'width'       => $image['width'] ?? 0,
                'height'      => $image['height'] ?? 0,
            );
        }
        
        return false;
    }
    
    /**
     * Format ACF image
     * @param array $image
     * @return array
     */
    private function format_acf_image($image) {
        if (!is_array($image) || empty($image['url'])) {
            return false;
        }
        
        return array(
            'type'        => 'image',
            'id'          => $image['ID'] ?? 0,
            'url'         => $image['url'],
            'thumb'       => $image['sizes']['medium'] ?? $image['url'],
            'large'       => $image['sizes']['large'] ?? $image['url'],
            'full'        => $image['url'],
            'alt'         => $image['alt'] ?? '',
            'title'       => $image['title'] ?? '',
            'caption'     => $image['caption'] ?? '',
            'description' => $image['description'] ?? '',
            'width'       => $image['width'] ?? 0,
            'height'      => $image['height'] ?? 0,
        );
    }
    
    /**
     * Format attachment item
     * @param int $attachment_id
     * @return array|false
     */
    private function format_attachment_item($attachment_id) {
        $attachment = get_post($attachment_id);
        if (!$attachment) {
            return false;
        }
        
        $url = wp_get_attachment_url($attachment_id);
        if (!$url) {
            return false;
        }
        
        return array(
            'type'        => 'image',
            'id'          => $attachment_id,
            'url'         => $url,
            'thumb'       => wp_get_attachment_image_url($attachment_id, 'medium') ?: $url,
            'large'       => wp_get_attachment_image_url($attachment_id, 'large') ?: $url,
            'full'        => $url,
            'alt'         => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'title'       => $attachment->post_title,
            'caption'     => $attachment->post_excerpt,
            'description' => $attachment->post_content,
            'width'       => 0,
            'height'      => 0,
        );
    }
    
    /**
     * Format video item
     * @param mixed $video
     * @return array|false
     */
    private function format_video_item($video) {
        $video_url = '';
        $title = '';
        $thumb = '';
        $attachment_id = 0;
        
        if (is_string($video)) {
            $video_url = $video;
        } elseif (is_array($video)) {
            $video_url = $video['url'] ?? $video['video_url'] ?? '';
            $title = $video['title'] ?? '';
            $thumb = $video['thumbnail'] ?? $video['thumb'] ?? '';
            $attachment_id = $video['attachment_id'] ?? 0;
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
            $thumb = $this->get_video_thumbnail($video_data['provider'], $video_data['id'], $attachment_id);
        }
        
        return array(
            'type'          => 'video',
            'provider'      => $video_data['provider'],
            'video_id'      => $video_data['id'],
            'url'           => $video_url,
            'embed_url'     => $video_data['embed_url'],
            'thumb'         => $thumb,
            'title'         => $title,
            'caption'       => '',
            'description'   => '',
            'attachment_id' => $attachment_id,
        );
    }
    
    /**
     * Parse video URL
     * @param string $url
     * @return array|false
     */
    private function parse_video_url($url) {
        // Check for self-hosted video
        if ($this->is_local_video($url)) {
            return array(
                'provider'  => 'local',
                'id'        => md5($url),
                'embed_url' => $url,
            );
        }
        
        // YouTube patterns
        $youtube_patterns = array(
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/',
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
     * Check if URL is a local video
     * @param string $url
     * @return bool
     */
    private function is_local_video($url) {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($ext, $this->video_extensions);
    }
    
    /**
     * Get video thumbnail
     * @param string $provider
     * @param string $video_id
     * @param int $attachment_id
     * @return string
     */
    private function get_video_thumbnail($provider, $video_id, $attachment_id = 0) {
        if ($provider === 'youtube') {
            return "https://img.youtube.com/vi/{$video_id}/hqdefault.jpg";
        }
        
        if ($provider === 'vimeo') {
            $cached = get_transient("es_vimeo_thumb_{$video_id}");
            if ($cached) {
                return $cached;
            }
            
            // Try Vimeo API
            $response = wp_remote_get("https://vimeo.com/api/v2/video/{$video_id}.json");
            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body[0]['thumbnail_large'])) {
                    $thumb = $body[0]['thumbnail_large'];
                    set_transient("es_vimeo_thumb_{$video_id}", $thumb, DAY_IN_SECONDS);
                    return $thumb;
                }
            }
            
            return '';
        }
        
        if ($provider === 'local') {
            if ($attachment_id) {
                $poster = get_post_meta($attachment_id, '_video_poster', true);
                if ($poster) {
                    return $poster;
                }
            }
            
            // Return placeholder
            return ENSEMBLE_PLUGIN_URL . 'assets/images/video-placeholder.svg';
        }
        
        return '';
    }
    
    /**
     * Render gallery HTML
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
            'entity_type'   => 'event',
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
            'entity_type'   => $args['entity_type'],
        ));
    }
    
    /**
     * Shortcode handler
     * @param array $atts
     * @return string
     */
    public function shortcode_gallery($atts) {
        $atts = shortcode_atts(array(
            'id'           => 0,          // Gallery Manager ID
            'event'        => 0,
            'event_id'     => 0,
            'artist'       => 0,
            'artist_id'    => 0,
            'location'     => 0,
            'location_id'  => 0,
            'layout'       => $this->get_setting('default_layout', 'grid'),
            'columns'      => $this->get_setting('default_columns', 4),
            'captions'     => $this->get_setting('show_captions', true),
            'lightbox'     => true,
            'max'          => 0,
            'class'        => '',
            'ids'          => '',
        ), $atts, 'ensemble_gallery');
        
        $gallery = array('items' => array());
        $entity_type = 'event';
        $entity_id = 0;
        
        // Priority: Custom IDs > Artist > Location > Event > Current Post
        if (!empty($atts['ids'])) {
            // Custom attachment IDs
            $ids = array_map('intval', explode(',', $atts['ids']));
            foreach ($ids as $id) {
                $item = $this->format_attachment_item($id);
                if ($item) {
                    $gallery['items'][] = $item;
                }
            }
        } elseif (!empty($atts['artist']) || !empty($atts['artist_id'])) {
            $entity_type = 'artist';
            $entity_id = intval($atts['artist'] ?: $atts['artist_id']);
            $gallery = $this->get_entity_gallery('artist', $entity_id);
        } elseif (!empty($atts['location']) || !empty($atts['location_id'])) {
            $entity_type = 'location';
            $entity_id = intval($atts['location'] ?: $atts['location_id']);
            $gallery = $this->get_entity_gallery('location', $entity_id);
        } else {
            $entity_type = 'event';
            $entity_id = intval($atts['event'] ?: $atts['event_id']);
            
            if (!$entity_id) {
                $entity_id = get_the_ID();
            }
            
            $gallery = $this->get_entity_gallery('event', $entity_id);
        }
        
        if (empty($gallery['items'])) {
            return '';
        }
        
        return $this->render_gallery($gallery, array(
            'layout'        => $atts['layout'],
            'columns'       => intval($atts['columns']),
            'show_captions' => filter_var($atts['captions'], FILTER_VALIDATE_BOOLEAN),
            'lightbox'      => filter_var($atts['lightbox'], FILTER_VALIDATE_BOOLEAN),
            'event_id'      => $entity_id,
            'entity_type'   => $entity_type,
            'class'         => sanitize_html_class($atts['class']),
            'max_items'     => intval($atts['max']),
        ));
    }
    
    /**
     * AJAX: Get media data
     */
    public function ajax_get_media() {
        check_ajax_referer('ensemble_gallery_pro', 'nonce');
        
        $entity_type = isset($_POST['entity_type']) ? sanitize_text_field($_POST['entity_type']) : 'event';
        $entity_id = isset($_POST['entity_id']) ? intval($_POST['entity_id']) : 0;
        
        if (!$entity_id) {
            // Fallback to event_id for backwards compatibility
            $entity_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        }
        
        if (!$entity_id) {
            wp_send_json_error(array('message' => __('Entity ID required', 'ensemble')));
        }
        
        $gallery = $this->get_entity_gallery($entity_type, $entity_id);
        
        wp_send_json_success($gallery);
    }
    
    /**
     * AJAX: Get video info (for admin preview)
     */
    public function ajax_get_video_info() {
        check_ajax_referer('ensemble_gallery_pro', 'nonce');
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('URL required', 'ensemble')));
        }
        
        $video_data = $this->parse_video_url($url);
        
        if (!$video_data) {
            wp_send_json_error(array('message' => __('Invalid video URL', 'ensemble')));
        }
        
        $thumb = $this->get_video_thumbnail($video_data['provider'], $video_data['id']);
        
        wp_send_json_success(array(
            'provider'  => $video_data['provider'],
            'video_id'  => $video_data['id'],
            'embed_url' => $video_data['embed_url'],
            'thumbnail' => $thumb,
        ));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('ensemble/v1', '/gallery/(?P<type>event|artist|location)/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_gallery'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'type' => array(
                    'required'          => true,
                    'validate_callback' => function($param) {
                        return in_array($param, array('event', 'artist', 'location'));
                    },
                ),
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
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_gallery($request) {
        $entity_type = $request->get_param('type');
        $entity_id = $request->get_param('id');
        
        $gallery = $this->get_entity_gallery($entity_type, $entity_id);
        
        return new WP_REST_Response($gallery, 200);
    }
    
    /**
     * Render settings page
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
     * @return array
     */
    public function get_layouts() {
        return $this->layouts;
    }
    
    /**
     * Get layout icon SVG
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
