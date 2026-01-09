<?php
/**
 * Ensemble Utility Shortcodes
 *
 * Handles utility shortcodes including gallery, layout switcher,
 * and demo page functionality.
 *
 * @package Ensemble
 * @subpackage Shortcodes
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility Shortcodes class.
 *
 * @since 3.0.0
 */
class ES_Utility_Shortcodes extends ES_Shortcode_Base {

	/**
	 * Register utility shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'ensemble_gallery', array( $this, 'gallery_shortcode' ) );
		add_shortcode( 'ensemble_layout_switcher', array( $this, 'layout_switcher_shortcode' ) );
		add_shortcode( 'ensemble_demo', array( $this, 'demo_page_shortcode' ) );
	}

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
    
}
