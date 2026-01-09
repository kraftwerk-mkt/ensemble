<?php
/**
 * Homepage Shortcodes
 * 
 * Spezielle Shortcodes für individuelle Startseiten-Layouts
 * - Hero-Bereich mit Video/Bild
 * - Event-Listen (inkl. Preview-Status)
 * - Logo-Display
 * 
 * @package Ensemble
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

class ES_Homepage_Shortcodes {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('ensemble_homepage_events', array($this, 'homepage_events_shortcode'));
        add_shortcode('ensemble_hero', array($this, 'hero_shortcode'));
        add_shortcode('ensemble_homepage_logo', array($this, 'logo_shortcode'));
    }
    
    /**
     * Get the correct date meta key
     */
    private function get_date_meta_key() {
        $mapping = get_option('ensemble_field_mapping', array());
        
        if (!empty($mapping['event_date'])) {
            return $mapping['event_date'];
        }
        
        // Standard Ensemble meta key (ohne Unterstrich)
        return 'event_date';
    }
    
    /**
     * Get location meta key
     */
    private function get_location_meta_key() {
        $mapping = get_option('ensemble_field_mapping', array());
        
        if (!empty($mapping['location'])) {
            return $mapping['location'];
        }
        
        // Standard Ensemble meta key (ohne Unterstrich)
        return 'event_location';
    }
    
    // =========================================================================
    // HOMEPAGE EVENTS - Event-Liste mit Published + Preview Status
    // =========================================================================
    
    /**
     * Homepage Events Shortcode
     * 
     * Zeigt alle kommenden Events an - published UND preview Status
     * Preview-Events werden NICHT verlinkt
     * 
     * Usage: [ensemble_homepage_events limit="10" show_year="false" style="minimal"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function homepage_events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit'           => '20',           // Anzahl Events
            'show_year'       => 'false',        // Jahr im Datum anzeigen
            'show_location'   => 'true',         // Location anzeigen
            'show_status'     => 'false',         // Preview-Badge anzeigen
            'style'           => 'minimal',      // minimal, detailed
            'class'           => '',             // Extra CSS-Klasse
            'template'        => '',             // Layout template override
            'title'           => '',             // Optionale Überschrift
            'separator'       => '·',            // Trennzeichen zwischen Elementen
            'position'        => 'default',      // default, overlay (über Hero)
            'offset'          => '0',            // Offset nach oben (negativer Wert) z.B. -200px
        ), $atts, 'ensemble_homepage_events');
        
        $limit = intval($atts['limit']);
        $show_year = filter_var($atts['show_year'], FILTER_VALIDATE_BOOLEAN);
        $show_location = filter_var($atts['show_location'], FILTER_VALIDATE_BOOLEAN);
        $show_status = filter_var($atts['show_status'], FILTER_VALIDATE_BOOLEAN);
        $style = sanitize_key($atts['style']);
        $extra_class = sanitize_html_class($atts['class']);
        $title = sanitize_text_field($atts['title']);
        $separator = esc_html($atts['separator']);
        $position = in_array($atts['position'], array('default', 'overlay')) ? $atts['position'] : 'default';
        $offset = sanitize_text_field($atts['offset']);
        
        // Get meta keys
        $date_key = $this->get_date_meta_key();
        $location_key = $this->get_location_meta_key();
        
        // Query: Upcoming events - published UND preview Status
        $today = date('Y-m-d');
        
        $args = array(
            'post_type'      => ensemble_get_post_type(),
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'meta_key'       => $date_key,
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'AND',
                // Nur zukünftige Events
                array(
                    'key'     => $date_key,
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
                // Published ODER Preview Status (nicht cancelled/postponed)
                array(
                    'relation' => 'OR',
                    // Kein Status gesetzt (= normal published)
                    array(
                        'key'     => '_event_status',
                        'compare' => 'NOT EXISTS',
                    ),
                    // Leerer Status (= normal published)
                    array(
                        'key'     => '_event_status',
                        'value'   => '',
                        'compare' => '=',
                    ),
                    // Explizit publish
                    array(
                        'key'     => '_event_status',
                        'value'   => 'publish',
                        'compare' => '=',
                    ),
                    // Preview Status
                    array(
                        'key'     => '_event_status',
                        'value'   => 'preview',
                        'compare' => '=',
                    ),
                ),
            ),
        );
        
        $events = new WP_Query($args);
        
        if (!$events->have_posts()) {
            return '<div class="es-homepage-events es-homepage-events--empty">' . 
                   __('Keine kommenden Events.', 'ensemble') . '</div>';
        }
        
        // Get active layout mode
        $mode_class = '';
        if (class_exists('ES_Layout_Sets')) {
            $mode = ES_Layout_Sets::get_active_mode();
            $mode_class = 'es-mode-' . $mode;
        }
        
        // Build position class and style
        $position_class = ($position === 'overlay') ? 'es-homepage-events--overlay' : '';
        $position_style = '';
        if ($position === 'overlay' && !empty($offset)) {
            $position_style = 'margin-top: ' . esc_attr($offset) . ';';
        }
        
        ob_start();
        ?>
        <div class="es-homepage-events es-homepage-events--<?php echo esc_attr($style); ?> <?php echo esc_attr($position_class); ?> <?php echo esc_attr($mode_class); ?> <?php echo esc_attr($extra_class); ?>"<?php if ($position_style): ?> style="<?php echo $position_style; ?>"<?php endif; ?>>
            
            <?php if (!empty($title)): ?>
            <h2 class="es-homepage-events__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            
            <ul class="es-homepage-events__list">
                <?php while ($events->have_posts()): $events->the_post();
                    $event_id = get_the_ID();
                    $event_title = get_the_title();
                    $event_date = get_post_meta($event_id, $date_key, true);
                    $event_status = get_post_meta($event_id, '_event_status', true);
                    $is_preview = ($event_status === 'preview');
                    
                    // Location
                    $location_name = '';
                    if ($show_location) {
                        $location_id = get_post_meta($event_id, $location_key, true);
                        if (!empty($location_id)) {
                            $location_name = get_the_title($location_id);
                        }
                    }
                    
                    // Datum formatieren
                    $date_display = $this->format_date($event_date, $show_year);
                ?>
                <li class="es-homepage-events__item <?php echo $is_preview ? 'es-homepage-events__item--preview' : ''; ?>">
                    
                    <span class="es-homepage-events__date"><?php echo esc_html($date_display); ?></span>
                    
                    <?php if ($style === 'minimal'): ?>
                        
                        <span class="es-homepage-events__separator"><?php echo $separator; ?></span>
                        
                        <?php if ($is_preview): ?>
                            <span class="es-homepage-events__name es-homepage-events__name--preview">
                                <?php echo esc_html($event_title); ?>
                            </span>
                        <?php else: ?>
                            <a href="<?php the_permalink(); ?>" class="es-homepage-events__name">
                                <?php echo esc_html($event_title); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($show_location && !empty($location_name)): ?>
                            <span class="es-homepage-events__separator"><?php echo $separator; ?></span>
                            <span class="es-homepage-events__location"><?php echo esc_html($location_name); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($show_status && $is_preview): ?>
                            <span class="es-homepage-events__badge es-homepage-events__badge--preview">
                                <?php _e('tba', 'ensemble'); ?>
                            </span>
                        <?php endif; ?>
                        
                    <?php else: // detailed style ?>
                        
                        <div class="es-homepage-events__content">
                            <?php if ($is_preview): ?>
                                <span class="es-homepage-events__name es-homepage-events__name--preview">
                                    <?php echo esc_html($event_title); ?>
                                </span>
                            <?php else: ?>
                                <a href="<?php the_permalink(); ?>" class="es-homepage-events__name">
                                    <?php echo esc_html($event_title); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($show_location && !empty($location_name)): ?>
                                <span class="es-homepage-events__location"><?php echo esc_html($location_name); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($show_status && $is_preview): ?>
                            <span class="es-homepage-events__badge es-homepage-events__badge--preview">
                                <?php _e('tba', 'ensemble'); ?>
                            </span>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                    
                </li>
                <?php endwhile; ?>
            </ul>
            
        </div>
        
        <style>
        /* ============================================================
           HOMEPAGE EVENTS - Base Styles
           ============================================================ */
        .es-homepage-events {
            --hp-bg: var(--ensemble-background, transparent);
            --hp-text: var(--ensemble-text, #ffffff);
            --hp-text-muted: var(--ensemble-text-secondary, rgba(255,255,255,0.6));
            --hp-primary: var(--ensemble-primary, #ff3366);
            --hp-border: var(--ensemble-card-border, rgba(255,255,255,0.1));
            --hp-font-heading: var(--ensemble-font-heading, inherit);
            --hp-font-body: var(--ensemble-font-body, inherit);
            
            font-family: var(--hp-font-body);
            color: var(--hp-text);
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        /* ============================================================
           OVERLAY POSITION - Über Hero positioniert
           ============================================================ */
        .es-homepage-events--overlay {
            position: relative;
            z-index: 10;
            background: transparent;
        }
        
        .es-homepage-events__title {
            font-family: var(--hp-font-heading);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--hp-text);
            text-align: center;
            margin: 0 0 2rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        
        .es-homepage-events__list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        /* ============================================================
           MINIMAL STYLE
           ============================================================ */
        .es-homepage-events--minimal .es-homepage-events__item {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--hp-border);
            text-align: center;
        }
        
        .es-homepage-events--minimal .es-homepage-events__item:last-child {
            border-bottom: none;
        }
        
        .es-homepage-events--minimal .es-homepage-events__date {
            font-family: var(--hp-font-heading);
            font-weight: 700;
            font-size: 1rem;
            color: var(--hp-primary);
            min-width: 100px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        
        .es-homepage-events--minimal .es-homepage-events__separator {
            color: var(--hp-text-muted);
            font-size: 0.875rem;
        }
        
        .es-homepage-events--minimal .es-homepage-events__name {
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--hp-text);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .es-homepage-events--minimal .es-homepage-events__name:hover {
            color: var(--hp-primary);
        }
        
        .es-homepage-events--minimal .es-homepage-events__name--preview {
            color: var(--hp-text-muted);
            font-style: italic;
        }
        
        .es-homepage-events--minimal .es-homepage-events__location {
            font-size: 0.9375rem;
            color: var(--hp-text-muted);
        }
        
        /* ============================================================
           DETAILED STYLE
           ============================================================ */
        .es-homepage-events--detailed .es-homepage-events__item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            align-items: center;
            gap: 1.5rem;
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--hp-border);
        }
        
        .es-homepage-events--detailed .es-homepage-events__item:last-child {
            border-bottom: none;
        }
        
        .es-homepage-events--detailed .es-homepage-events__date {
            font-family: var(--hp-font-heading);
            font-weight: 700;
            font-size: 0.9375rem;
            color: var(--hp-primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .es-homepage-events--detailed .es-homepage-events__content {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .es-homepage-events--detailed .es-homepage-events__name {
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--hp-text);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .es-homepage-events--detailed .es-homepage-events__name:hover {
            color: var(--hp-primary);
        }
        
        .es-homepage-events--detailed .es-homepage-events__name--preview {
            color: var(--hp-text-muted);
        }
        
        .es-homepage-events--detailed .es-homepage-events__location {
            font-size: 0.875rem;
            color: var(--hp-text-muted);
        }
        
        /* ============================================================
           BADGE
           ============================================================ */
        .es-homepage-events__badge {
            display: inline-block;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.25rem 0.5rem;
            border-radius: 2px;
            background: var(--hp-text-muted);
            color: var(--hp-bg);
        }
        
        .es-homepage-events__badge--preview {
            background: transparent;
            border: 1px solid var(--hp-text-muted);
            color: var(--hp-text-muted);
        }
        
        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 640px) {
            .es-homepage-events--minimal .es-homepage-events__item {
                flex-direction: column;
                gap: 0.375rem;
            }
            
            .es-homepage-events--minimal .es-homepage-events__separator {
                display: none;
            }
            
            .es-homepage-events--detailed .es-homepage-events__item {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .es-homepage-events--detailed .es-homepage-events__date {
                font-size: 0.8125rem;
            }
        }
        </style>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    // =========================================================================
    // HERO - Fullscreen Hero mit Video/Bild
    // =========================================================================
    
    /**
     * Hero Shortcode
     * 
     * Fullscreen Hero-Bereich mit Video oder Bild Hintergrund
     * 
     * Usage: [ensemble_hero video="URL" image="URL" logo="URL" title="Text" overlay="0.5"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function hero_shortcode($atts) {
        // Trim whitespace from all attributes (fixes multi-line shortcode issues)
        if (is_array($atts)) {
            $atts = array_map('trim', $atts);
        }
        
        $atts = shortcode_atts(array(
            'video'           => '',              // Video URL
            'image'           => '',              // Fallback Bild URL
            'logo'            => '',              // Logo URL
            'logo_width'      => '300',           // Logo Breite in px
            'title'           => '',              // Optionaler Titel
            'subtitle'        => '',              // Optionaler Untertitel
            'overlay'         => '0.4',           // Overlay Opacity 0-1
            'overlay_color'   => '#000000',       // Overlay Farbe
            'overlay_type'    => 'solid',         // solid, gradient-bottom, gradient-top
            'height'          => '100vh',         // Höhe (100vh, 80vh, 600px)
            'min_height'      => '500px',         // Mindesthöhe
            'align'           => 'center',        // Logo/Content Position: center, bottom, top
            'class'           => '',              // Extra CSS-Klasse
            'scroll_indicator' => 'true',         // Scroll-Pfeil anzeigen
            'debug'           => 'false',         // Debug-Modus
        ), $atts, 'ensemble_hero');
        
        // Clean and validate URLs - don't use esc_url here, it can break some URLs
        $video_url = trim($atts['video']);
        $image_url = trim($atts['image']);
        $logo_url = trim($atts['logo']);
        $logo_width = intval($atts['logo_width']);
        $title = sanitize_text_field($atts['title']);
        $subtitle = sanitize_text_field($atts['subtitle']);
        $overlay = floatval($atts['overlay']);
        $overlay_color = sanitize_hex_color($atts['overlay_color']) ?: '#000000';
        $overlay_type = in_array($atts['overlay_type'], array('solid', 'gradient-bottom', 'gradient-top')) ? $atts['overlay_type'] : 'solid';
        $height = sanitize_text_field($atts['height']);
        $min_height = sanitize_text_field($atts['min_height']);
        $align = in_array($atts['align'], array('center', 'bottom', 'top')) ? $atts['align'] : 'center';
        $extra_class = sanitize_html_class($atts['class']);
        $scroll_indicator = filter_var($atts['scroll_indicator'], FILTER_VALIDATE_BOOLEAN);
        $debug = filter_var($atts['debug'], FILTER_VALIDATE_BOOLEAN);
        
        // Debug output
        if ($debug && current_user_can('manage_options')) {
            return '<div style="background:#222;color:#0f0;padding:20px;font-family:monospace;white-space:pre-wrap;">
DEBUG ensemble_hero:
video_url: ' . esc_html($video_url) . '
logo_url: ' . esc_html($logo_url) . '
image_url: ' . esc_html($image_url) . '
logo_width: ' . $logo_width . '
overlay: ' . $overlay . '
overlay_type: ' . $overlay_type . '
height: ' . esc_html($height) . '
</div>';
        }
        
        // Get active layout mode
        $mode_class = '';
        if (class_exists('ES_Layout_Sets')) {
            $mode = ES_Layout_Sets::get_active_mode();
            $mode_class = 'es-mode-' . $mode;
        }
        
        // Unique ID für Styles
        $unique_id = 'es-hero-' . wp_unique_id();
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr($unique_id); ?>" class="es-hero es-hero--<?php echo esc_attr($align); ?> es-hero--overlay-<?php echo esc_attr($overlay_type); ?> <?php echo esc_attr($mode_class); ?> <?php echo esc_attr($extra_class); ?>">
            
            <!-- Background Media -->
            <div class="es-hero__media">
                <?php if (!empty($video_url)): ?>
                    <video class="es-hero__video" autoplay muted loop playsinline<?php if (!empty($image_url)): ?> poster="<?php echo esc_url($image_url); ?>"<?php endif; ?>>
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php elseif (!empty($image_url)): ?>
                    <img class="es-hero__image" src="<?php echo esc_url($image_url); ?>" alt="">
                <?php endif; ?>
            </div>
            
            <!-- Overlay -->
            <div class="es-hero__overlay"></div>
            
            <!-- Content -->
            <div class="es-hero__content">
                <?php if (!empty($logo_url)): ?>
                    <img class="es-hero__logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($title); ?>">
                <?php endif; ?>
                
                <?php if (!empty($title)): ?>
                    <h1 class="es-hero__title"><?php echo esc_html($title); ?></h1>
                <?php endif; ?>
                
                <?php if (!empty($subtitle)): ?>
                    <p class="es-hero__subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($scroll_indicator): ?>
            <div class="es-hero__scroll">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12l7 7 7-7"/>
                </svg>
            </div>
            <?php endif; ?>
            
        </div>
        
        <style>
        #<?php echo esc_attr($unique_id); ?> {
            --hero-height: <?php echo esc_attr($height); ?>;
            --hero-min-height: <?php echo esc_attr($min_height); ?>;
            --hero-overlay-color: <?php echo esc_attr($overlay_color); ?>;
            --hero-overlay-opacity: <?php echo esc_attr($overlay); ?>;
            --hero-logo-width: <?php echo intval($logo_width); ?>px;
            --hero-text: var(--ensemble-text, #ffffff);
            --hero-font-heading: var(--ensemble-font-heading, inherit);
        }
        
        #<?php echo esc_attr($unique_id); ?>.es-hero {
            position: relative;
            width: 100%;
            height: var(--hero-height);
            min-height: var(--hero-min-height);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #<?php echo esc_attr($unique_id); ?>.es-hero--bottom {
            align-items: flex-end;
            padding-bottom: 10vh;
        }
        
        #<?php echo esc_attr($unique_id); ?> .es-hero__media {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        #<?php echo esc_attr($unique_id); ?> .es-hero__video,
        #<?php echo esc_attr($unique_id); ?> .es-hero__image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Solid Overlay (default) */
        #<?php echo esc_attr($unique_id); ?> .es-hero__overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--hero-overlay-color);
            opacity: var(--hero-overlay-opacity);
            z-index: 2;
        }
        
        /* Gradient Overlay - Bottom */
        #<?php echo esc_attr($unique_id); ?>.es-hero--overlay-gradient-bottom .es-hero__overlay {
            background: linear-gradient(
                to top,
                var(--hero-overlay-color) 0%,
                rgba(0,0,0,0.5) 30%,
                rgba(0,0,0,0.2) 60%,
                transparent 100%
            );
            opacity: 1;
        }
        
        /* Gradient Overlay - Top */
        #<?php echo esc_attr($unique_id); ?>.es-hero--overlay-gradient-top .es-hero__overlay {
            background: linear-gradient(
                to bottom,
                var(--hero-overlay-color) 0%,
                rgba(0,0,0,0.5) 30%,
                rgba(0,0,0,0.2) 60%,
                transparent 100%
            );
            opacity: 1;
        }
        
        #<?php echo esc_attr($unique_id); ?> .es-hero__content {
            position: relative;
            z-index: 3;
            text-align: center;
            padding: 2rem;
        }
        
        /* Align Top */
        #<?php echo esc_attr($unique_id); ?>.es-hero--top {
            align-items: flex-start;
            padding-top: 10vh;
        }
        
        #<?php echo esc_attr($unique_id); ?> .es-hero__logo {
            display: block;
            max-width: var(--hero-logo-width);
            width: auto;
            height: auto;
            margin: 0 auto 1.5rem;
            object-fit: contain;
        }
        
        #<?php echo esc_attr($unique_id); ?> .es-hero__title {
            font-family: var(--hero-font-heading);
            font-size: clamp(2rem, 6vw, 4rem);
            font-weight: 700;
            color: var(--hero-text);
            margin: 0 0 1rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        
        #<?php echo esc_attr($unique_id); ?> .es-hero__subtitle {
            font-size: 1.25rem;
            color: var(--hero-text);
            opacity: 0.8;
            margin: 0;
            max-width: 600px;
        }
        
        #<?php echo esc_attr($unique_id); ?> .es-hero__scroll {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            color: var(--hero-text);
            opacity: 0.6;
            animation: es-hero-bounce 2s ease infinite;
        }
        
        @keyframes es-hero-bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-10px); }
            60% { transform: translateX(-50%) translateY(-5px); }
        }
        
        @media (max-width: 640px) {
            #<?php echo esc_attr($unique_id); ?> .es-hero__logo {
                max-width: calc(var(--hero-logo-width) * 0.7);
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    // =========================================================================
    // LOGO - Simple Logo Display
    // =========================================================================
    
    /**
     * Logo Shortcode
     * 
     * Einfache Logo-Anzeige für Homepage
     * 
     * Usage: [ensemble_homepage_logo url="URL" width="300" align="center"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function logo_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url'    => '',
            'width'  => '300',
            'align'  => 'center',
            'class'  => '',
            'alt'    => '',
        ), $atts, 'ensemble_homepage_logo');
        
        $url = esc_url($atts['url']);
        $width = intval($atts['width']);
        $align = in_array($atts['align'], array('left', 'center', 'right')) ? $atts['align'] : 'center';
        $extra_class = sanitize_html_class($atts['class']);
        $alt = sanitize_text_field($atts['alt']);
        
        if (empty($url)) {
            return '';
        }
        
        $align_style = array(
            'left'   => 'margin-right: auto;',
            'center' => 'margin-left: auto; margin-right: auto;',
            'right'  => 'margin-left: auto;',
        );
        
        return sprintf(
            '<div class="es-homepage-logo %s"><img src="%s" alt="%s" style="display: block; max-width: %dpx; width: 100%%; height: auto; %s"></div>',
            esc_attr($extra_class),
            esc_attr($url),
            esc_attr($alt),
            $width,
            $align_style[$align]
        );
    }
    
    // =========================================================================
    // HELPER METHODS
    // =========================================================================
    
    /**
     * Format date for display
     * 
     * @param string $date_string Date string
     * @param bool $show_year Include year
     * @return string Formatted date
     */
    private function format_date($date_string, $show_year = false) {
        if (empty($date_string)) {
            return __('tba', 'ensemble');
        }
        
        $timestamp = strtotime($date_string);
        
        if (!$timestamp) {
            return __('tba', 'ensemble');
        }
        
        // Kurzform Wochentag (ohne Punkt)
        $day_name = date_i18n('D', $timestamp);
        $day_name = rtrim($day_name, '. ');
        
        $day = date('d', $timestamp);
        $month = date('m', $timestamp);
        
        if ($show_year) {
            $year = date('Y', $timestamp);
            return $day_name . ' ' . $day . '.' . $month . '.' . $year;
        }
        
        return $day_name . ' ' . $day . '.' . $month . '.';
    }
}

// Initialize
ES_Homepage_Shortcodes::get_instance();
