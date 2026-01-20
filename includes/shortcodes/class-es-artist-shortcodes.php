<?php
/**
 * Ensemble Artist Shortcodes
 *
 * Handles all artist-related shortcodes including artist lists, grids,
 * single artists, and featured artist sliders.
 *
 * @package Ensemble
 * @subpackage Shortcodes
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Artist Shortcodes class.
 *
 * @since 3.0.0
 */
class ES_Artist_Shortcodes extends ES_Shortcode_Base {

	/**
	 * Register artist-related shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'ensemble_artists', array( $this, 'artists_list_shortcode' ) );
		add_shortcode( 'ensemble_artist', array( $this, 'single_artist_shortcode' ) );
	}

    public function artists_list_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('artists');
        }
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'layout' => 'grid',           // grid, list, slider, cards, compact, featured
            'columns' => '3',             // 2, 3, 4 (nur für grid)
            'style' => 'default',
            'limit' => '12',              // Anzahl Artists
            'orderby' => 'title',         // title, date, menu_order
            'order' => 'ASC',             // ASC, DESC
            'genre' => '',                // Genre slug(s), comma-separated
            'type' => '',                 // Artist Type slug(s), comma-separated
            'category' => '',             // Alias for type (backward compatibility)
            // Display options
            'show_image' => 'true',       // Bild anzeigen
            'show_name' => 'true',        // Name anzeigen (immer true im Normalfall)
            'show_position' => 'true',    // Position/Rolle anzeigen
            'show_company' => 'true',     // Firma anzeigen
            'show_genre' => 'false',      // Genre anzeigen
            'show_type' => 'false',       // Artist Type anzeigen
            'show_bio' => 'true',         // Bio/Excerpt anzeigen
            'show_events' => 'false',     // Kommende Events anzeigen
            'show_social' => 'false',     // Social Links anzeigen
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
        
        // Check if using Kongress layout set for correct CSS class
        $active_set = '';
        if (class_exists('ES_Layout_Sets')) {
            $active_set = ES_Layout_Sets::get_active_set();
        }
        
        // Build container class - ALWAYS include base classes for grid functionality
        $container_class = 'ensemble-artists-list ensemble-layout-' . $layout;
        
        // Add column classes for grid layouts
        if (in_array($layout, array('grid', 'cards', 'compact'))) {
            $container_class .= ' ensemble-columns-' . $columns;
        }
        
        // Add Kongress-specific class as additional class (not replacement!)
        if ($active_set === 'kongress') {
            $container_class .= ' es-kongress-speakers-grid';
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
        // Parse ALL display options
        $show_image = filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN);
        $show_name = isset($atts['show_name']) ? filter_var($atts['show_name'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_position = isset($atts['show_position']) ? filter_var($atts['show_position'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_company = isset($atts['show_company']) ? filter_var($atts['show_company'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_genre = isset($atts['show_genre']) ? filter_var($atts['show_genre'], FILTER_VALIDATE_BOOLEAN) : false;
        $show_type = isset($atts['show_type']) ? filter_var($atts['show_type'], FILTER_VALIDATE_BOOLEAN) : false;
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_social = isset($atts['show_social']) ? filter_var($atts['show_social'], FILTER_VALIDATE_BOOLEAN) : false;
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
                
                // Pass ALL attributes to template
                $shortcode_atts = $atts;
                $style = isset($atts['style']) ? $atts['style'] : 'default';
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
                        <?php echo get_the_post_thumbnail($artist_id, 'large'); ?>
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
        $show_name = isset($atts['show_name']) ? filter_var($atts['show_name'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_position = isset($atts['show_position']) ? filter_var($atts['show_position'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_company = isset($atts['show_company']) ? filter_var($atts['show_company'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_genre = isset($atts['show_genre']) ? filter_var($atts['show_genre'], FILTER_VALIDATE_BOOLEAN) : false;
        $show_bio = filter_var($atts['show_bio'], FILTER_VALIDATE_BOOLEAN);
        $show_events = filter_var($atts['show_events'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $link_text = sanitize_text_field($atts['link_text']);
        
        $artist = get_post($artist_id);
        
        // Get professional info
        $position = get_post_meta($artist_id, '_es_artist_position', true);
        $company = get_post_meta($artist_id, '_es_artist_company', true);
        
        // Get genre from taxonomy
        $genre = '';
        $genre_terms = get_the_terms($artist_id, 'ensemble_genre');
        if ($genre_terms && !is_wp_error($genre_terms)) {
            $genre_names = wp_list_pluck($genre_terms, 'name');
            $genre = implode(', ', $genre_names);
        }
        ?>
        <div class="ensemble-artist-list-item">
            
            <?php if ($show_image): ?>
            <div class="ensemble-artist-thumb">
                <?php if (has_post_thumbnail($artist_id)): ?>
                    <a href="<?php echo get_permalink($artist_id); ?>">
                        <?php echo get_the_post_thumbnail($artist_id, 'thumbnail'); ?>
                    </a>
                <?php else: ?>
                    <div class="ensemble-artist-placeholder-small" style="width: 80px; height: 80px; background: var(--ensemble-placeholder-bg, #f0f0f0); border-radius: var(--ensemble-card-radius, 8px); display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-placeholder-icon, #999)" stroke-width="1" style="width: 32px; height: 32px;">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-artist-list-content">
                
                <div class="ensemble-artist-header">
                    <?php if ($show_name): ?>
                    <h4 class="ensemble-artist-title" style="font-family: var(--ensemble-font-heading); font-size: var(--ensemble-lg-size, 1.125rem); font-weight: var(--ensemble-heading-weight, 600); color: var(--ensemble-text); margin: 0 0 4px 0;">
                        <a href="<?php echo get_permalink($artist_id); ?>" style="color: inherit; text-decoration: none;">
                            <?php echo esc_html($artist->post_title); ?>
                        </a>
                    </h4>
                    <?php endif; ?>
                    
                    <?php if ($show_position && $position): ?>
                    <div class="ensemble-artist-position" style="font-size: var(--ensemble-small-size, 0.875rem); color: var(--ensemble-secondary); margin-bottom: 2px;">
                        <?php echo esc_html($position); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_company && $company): ?>
                    <div class="ensemble-artist-company" style="font-size: var(--ensemble-small-size, 0.875rem); color: var(--ensemble-text-secondary);">
                        <?php echo esc_html($company); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_genre && $genre): ?>
                    <span class="ensemble-artist-genre" style="display: inline-block; margin-top: 6px; padding: 2px 8px; background: var(--ensemble-surface, #f5f5f5); color: var(--ensemble-text-secondary); font-size: var(--ensemble-xs-size, 0.75rem); border-radius: 4px;">
                        <?php echo esc_html($genre); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($show_bio && $artist->post_excerpt): ?>
                <div class="ensemble-artist-bio" style="margin-top: 8px; font-size: var(--ensemble-small-size, 0.875rem); color: var(--ensemble-text-secondary); line-height: 1.5;">
                    <?php echo wp_trim_words($artist->post_excerpt, 30); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_events): ?>
                    <?php $this->render_artist_upcoming_events($artist_id); ?>
                <?php endif; ?>
                
            </div>
            
            <?php if ($show_link): ?>
            <div class="ensemble-artist-list-action">
                <a href="<?php echo get_permalink($artist_id); ?>" 
                   style="display: inline-block; padding: var(--ensemble-button-padding-v, 10px) var(--ensemble-button-padding-h, 20px); background: var(--ensemble-button-bg); color: var(--ensemble-button-text); font-size: var(--ensemble-button-font-size, 14px); font-weight: var(--ensemble-button-weight, 600); text-decoration: none; border-radius: var(--ensemble-button-radius, 4px); transition: all 0.3s ease;">
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
        
        // Fallback: Default template with Designer variables
        $show_position = isset($atts['show_position']) ? filter_var($atts['show_position'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_company = isset($atts['show_company']) ? filter_var($atts['show_company'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_genre_opt = isset($atts['show_genre']) ? filter_var($atts['show_genre'], FILTER_VALIDATE_BOOLEAN) : false;
        
        // Get professional info
        $position = get_post_meta($artist_id, '_es_artist_position', true);
        $company = get_post_meta($artist_id, '_es_artist_company', true);
        ?>
        <div class="ensemble-artist-card ensemble-artist-card--large" style="background: var(--ensemble-card-bg); border: var(--ensemble-card-border-width, 1px) solid var(--ensemble-card-border); border-radius: var(--ensemble-card-radius, 8px); overflow: hidden; box-shadow: var(--ensemble-card-shadow); transition: all 0.3s ease;">
            <?php if ($show_image): ?>
            <div class="ensemble-artist-portrait" style="aspect-ratio: 4/5; overflow: hidden;">
                <?php if (has_post_thumbnail($artist_id)): ?>
                    <a href="<?php echo esc_url($artist_permalink); ?>" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
                        <img src="<?php echo get_the_post_thumbnail_url($artist_id, 'large'); ?>" alt="<?php echo esc_attr($artist_post->post_title); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                    </a>
                <?php else: ?>
                    <div class="ensemble-artist-placeholder-large" style="width: 100%; height: 100%; background: var(--ensemble-placeholder-bg, #f0f0f0); display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-placeholder-icon, #999)" stroke-width="1" style="width: 64px; height: 64px;">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ensemble-artist-card-content" style="padding: var(--ensemble-card-padding, 20px);">
                <h3 class="ensemble-artist-title" style="font-family: var(--ensemble-font-heading); font-size: var(--ensemble-lg-size, 1.125rem); font-weight: var(--ensemble-heading-weight, 600); color: var(--ensemble-text); margin: 0 0 8px 0;">
                    <a href="<?php echo esc_url($artist_permalink); ?>" style="color: inherit; text-decoration: none;" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html($artist_post->post_title); ?></a>
                </h3>
                
                <?php if ($show_position && $position): ?>
                <div class="ensemble-artist-position" style="font-size: var(--ensemble-small-size, 0.875rem); color: var(--ensemble-secondary); margin-bottom: 4px;">
                    <?php echo esc_html($position); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_company && $company): ?>
                <div class="ensemble-artist-company" style="font-size: var(--ensemble-small-size, 0.875rem); color: var(--ensemble-text-secondary); margin-bottom: 8px;">
                    <?php echo esc_html($company); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_genre_opt && $genre): ?>
                <div class="ensemble-artist-genre" style="font-size: var(--ensemble-xs-size, 0.75rem); color: var(--ensemble-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                    <?php echo esc_html($genre); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_bio && $artist_post->post_excerpt): ?>
                <div class="ensemble-artist-bio" style="font-size: var(--ensemble-small-size, 0.875rem); color: var(--ensemble-text-secondary); line-height: 1.5; margin-bottom: 16px;">
                    <?php echo wp_trim_words($artist_post->post_excerpt, 25, '...'); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_link): ?>
                <a href="<?php echo esc_url($artist_permalink); ?>" 
                   style="display: inline-block; padding: var(--ensemble-button-padding-v, 12px) var(--ensemble-button-padding-h, 24px); background: var(--ensemble-button-bg); color: var(--ensemble-button-text); font-size: var(--ensemble-button-font-size, 14px); font-weight: var(--ensemble-button-weight, 600); text-decoration: none; border-radius: var(--ensemble-button-radius, 4px); transition: all 0.3s ease;"
                   <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
                    <?php echo esc_html($link_text); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render upcoming events for an artist
     * 
     * @param int $artist_id Artist post ID
     */
    private function render_artist_upcoming_events($artist_id) {
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
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('artists');
        }
        
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
        
        // Get all event IDs for this artist (same pattern as working count function)
        $all_event_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'event_artist' 
            AND p.post_type = %s
            AND p.post_status = 'publish'
            AND (
                pm.meta_value LIKE %s
                OR pm.meta_value LIKE %s
                OR pm.meta_value = %s
            )
        ", 
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
                    
                    // Try multiple date field names
                    $event_date = get_post_meta($evt_id, 'es_event_start_date', true);
                    if (empty($event_date)) {
                        $event_date = get_post_meta($evt_id, 'event_date', true);
                    }
                    if (empty($event_date)) {
                        // Try ACF
                        if (function_exists('get_field')) {
                            $event_date = get_field('event_date', $evt_id);
                        }
                    }
                    
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
                                    $start_date = get_post_meta($evt_id, 'es_event_start_date', true);
                                    if (empty($start_date)) {
                                        $start_date = get_post_meta($evt_id, 'event_date', true);
                                    }
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
            
            // Use same working query pattern
            $event_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT pm.post_id 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = 'event_artist' 
                AND p.post_type = %s
                AND p.post_status = 'publish'
                AND (
                    pm.meta_value LIKE %s
                    OR pm.meta_value LIKE %s
                    OR pm.meta_value = %s
                )
            ", 
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
                        $start_date = get_post_meta($evt_id, 'es_event_start_date', true);
                        if (empty($start_date)) {
                            $start_date = get_post_meta($evt_id, 'event_date', true);
                        }
                        if (function_exists('get_field') && empty($start_date)) {
                            $start_date = get_field('event_date', $evt_id);
                        }
                        
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
    
}
