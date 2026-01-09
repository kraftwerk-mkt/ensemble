<?php
/**
 * Custom Templates Manager
 * 
 * Handles creation, editing, deletion and rendering of custom event templates
 *
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Custom_Templates {
    
    /**
     * Get all custom templates
     */
    public static function get_all_templates() {
        return get_option('ensemble_custom_templates', array());
    }
    
    /**
     * Get a specific template
     */
    public static function get_template($slug) {
        $templates = self::get_all_templates();
        return isset($templates[$slug]) ? $templates[$slug] : null;
    }
    
    /**
     * Save a custom template
     */
    public static function save_template($slug, $template_data) {
        $templates = self::get_all_templates();
        
        $template = array(
            'name' => sanitize_text_field($template_data['name']),
            'html' => $template_data['html'],
            'css' => sanitize_textarea_field($template_data['css']),
            'created' => isset($templates[$slug]['created']) ? $templates[$slug]['created'] : current_time('mysql'),
            'modified' => current_time('mysql')
        );
        
        $saved = self::save_template_files($slug, $template);
        
        if ($saved) {
            $templates[$slug] = $template;
            update_option('ensemble_custom_templates', $templates);
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete a custom template
     */
    public static function delete_template($slug) {
        $templates = self::get_all_templates();
        
        if (isset($templates[$slug])) {
            self::delete_template_files($slug);
            unset($templates[$slug]);
            update_option('ensemble_custom_templates', $templates);
            return true;
        }
        
        return false;
    }
    
    /**
     * Save template files to filesystem
     */
    private static function save_template_files($slug, $template) {
        $upload_dir = wp_upload_dir();
        $templates_dir = $upload_dir['basedir'] . '/ensemble-templates';
        $template_dir = $templates_dir . '/custom-' . $slug;
        
        if (!file_exists($templates_dir)) {
            wp_mkdir_p($templates_dir);
        }
        
        if (!file_exists($template_dir)) {
            wp_mkdir_p($template_dir);
        }
        
        $php_content = self::wrap_template_html($template['html']);
        file_put_contents($template_dir . '/single-event.php', $php_content);
        
        if (!empty($template['css'])) {
            file_put_contents($template_dir . '/style.css', $template['css']);
        }
        
        return true;
    }
    
    /**
     * Delete template files from filesystem
     */
    private static function delete_template_files($slug) {
        $upload_dir = wp_upload_dir();
        $template_dir = $upload_dir['basedir'] . '/ensemble-templates/custom-' . $slug;
        
        if (file_exists($template_dir)) {
            $files = glob($template_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($template_dir);
        }
    }
    
    /**
     * Wrap user HTML in complete template structure
     */
    private static function wrap_template_html($user_html) {
        // Baue das Template als String zusammen - KEIN HEREDOC!
        $output = "<?php\n";
        $output .= "if (!defined('ABSPATH')) exit;\n";
        $output .= "get_header();\n";
        $output .= "while (have_posts()) : the_post();\n";
        $output .= "    \$event_id = get_the_ID();\n";
        $output .= "    // Use unified meta key access for format compatibility\n";
        $output .= "    \$event_date = function_exists('ensemble_get_event_meta') ? ensemble_get_event_meta(\$event_id, 'start_date') : get_post_meta(\$event_id, 'event_date', true);\n";
        $output .= "    \$event_time = function_exists('ensemble_get_event_meta') ? ensemble_get_event_meta(\$event_id, 'start_time') : get_post_meta(\$event_id, 'event_time', true);\n";
        $output .= "    \$event_time_end = function_exists('ensemble_get_event_meta') ? ensemble_get_event_meta(\$event_id, 'end_time') : get_post_meta(\$event_id, 'event_time_end', true);\n";
        $output .= "    \$price = function_exists('ensemble_get_event_meta') ? ensemble_get_event_meta(\$event_id, 'price') : get_post_meta(\$event_id, 'event_price', true);\n";
        $output .= "    \$ticket_url = function_exists('ensemble_get_event_meta') ? ensemble_get_event_meta(\$event_id, 'ticket_url') : get_post_meta(\$event_id, 'event_ticket_url', true);\n";
        $output .= "    \$description = function_exists('ensemble_get_event_meta') ? ensemble_get_event_meta(\$event_id, 'description') : get_post_meta(\$event_id, 'event_description', true);\n";
        $output .= "    \$location_id = intval(function_exists('ensemble_get_event_meta') ? ensemble_get_event_meta(\$event_id, 'location') : get_post_meta(\$event_id, 'event_location', true));\n";
        $output .= "    \$location_name = '';\n";
        $output .= "    \$location_address = '';\n";
        $output .= "    if (\$location_id > 0) {\n";
        $output .= "        \$location = get_post(\$location_id);\n";
        $output .= "        if (\$location) {\n";
        $output .= "            \$location_name = \$location->post_title;\n";
        $output .= "            \$location_address = function_exists('ensemble_get_location_meta') ? ensemble_get_location_meta(\$location_id, 'address') : get_post_meta(\$location_id, 'location_address', true);\n";
        $output .= "        }\n";
        $output .= "    }\n";
        $output .= "    \$artist_data = function_exists('ensemble_get_event_meta') ? ensemble_get_event_meta(\$event_id, 'artist') : get_post_meta(\$event_id, 'event_artist', true);\n";
        $output .= "    \$artists = array();\n";
        $output .= "    if (!empty(\$artist_data)) {\n";
        $output .= "        if (is_string(\$artist_data)) \$artist_data = maybe_unserialize(\$artist_data);\n";
        $output .= "        \$artist_ids = is_array(\$artist_data) ? array_map('intval', array_filter(\$artist_data)) : array(intval(\$artist_data));\n";
        $output .= "        foreach (\$artist_ids as \$aid) {\n";
        $output .= "            \$a = get_post(\$aid);\n";
        $output .= "            if (\$a && \$a->post_type === 'ensemble_artist') {\n";
        $output .= "                \$artists[] = array('id' => \$aid, 'name' => \$a->post_title, 'url' => get_permalink(\$aid));\n";
        $output .= "            }\n";
        $output .= "        }\n";
        $output .= "    }\n";
        $output .= "    \$event_genres = get_the_terms(\$event_id, 'ensemble_genre');\n";
        $output .= "    \$all_genres = array();\n";
        $output .= "    if (\$event_genres && !is_wp_error(\$event_genres)) {\n";
        $output .= "        foreach (\$event_genres as \$genre) \$all_genres[] = \$genre->name;\n";
        $output .= "    }\n";
        $output .= "    \$formatted_date = \$event_date ? date_i18n(get_option('date_format'), strtotime(\$event_date)) : '';\n";
        $output .= "    \$formatted_time = \$event_time ? date_i18n(get_option('time_format'), strtotime(\$event_time)) : '';\n";
        $output .= "    \$css_file = dirname(__FILE__) . '/style.css';\n";
        $output .= "    if (file_exists(\$css_file)) echo '<style>' . file_get_contents(\$css_file) . '</style>';\n";
        $output .= "?>\n";
        $output .= $user_html . "\n";
        $output .= "<?php endwhile; get_footer();";
        
        return $output;
    }
    
    /**
     * Get starter HTML template
     */
    public static function get_starter_html() {
        // KEIN HEREDOC - Nur normaler String!
        $html = '<article class="es-event-single">' . "\n";
        $html .= '    <header class="es-event-header">' . "\n";
        $html .= '        <h1 class="es-event-title"><?php the_title(); ?></h1>' . "\n";
        $html .= '        <?php if (!empty($all_genres)): ?>' . "\n";
        $html .= '        <div class="es-genre-tags">' . "\n";
        $html .= '            <?php foreach ($all_genres as $genre): ?>' . "\n";
        $html .= '                <span class="es-tag"><?php echo esc_html($genre); ?></span>' . "\n";
        $html .= '            <?php endforeach; ?>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '        <?php endif; ?>' . "\n";
        $html .= '    </header>' . "\n";
        $html .= '    <?php if (has_post_thumbnail()): ?>' . "\n";
        $html .= '    <div class="es-event-image">' . "\n";
        $html .= '        <?php the_post_thumbnail(\'large\'); ?>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '    <?php endif; ?>' . "\n";
        $html .= '    <div class="es-event-meta">' . "\n";
        $html .= '        <?php if ($formatted_date): ?>' . "\n";
        $html .= '        <div class="es-meta-item">' . "\n";
        $html .= '            <span class="es-icon">📅</span>' . "\n";
        $html .= '            <div><?php echo esc_html($formatted_date); ?></div>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '        <?php endif; ?>' . "\n";
        $html .= '        <?php if ($location_name): ?>' . "\n";
        $html .= '        <div class="es-meta-item">' . "\n";
        $html .= '            <span class="es-icon">📍</span>' . "\n";
        $html .= '            <div><?php echo esc_html($location_name); ?></div>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '        <?php endif; ?>' . "\n";
        $html .= '        <?php if (!empty($artists)): ?>' . "\n";
        $html .= '        <div class="es-meta-item">' . "\n";
        $html .= '            <span class="es-icon">🎤</span>' . "\n";
        $html .= '            <div class="es-artists-list">' . "\n";
        $html .= '                <?php foreach ($artists as $artist): ?>' . "\n";
        $html .= '                    <a href="<?php echo esc_url($artist[\'url\']); ?>" class="es-artist-link">' . "\n";
        $html .= '                        <?php echo esc_html($artist[\'name\']); ?>' . "\n";
        $html .= '                    </a>' . "\n";
        $html .= '                <?php endforeach; ?>' . "\n";
        $html .= '            </div>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '        <?php endif; ?>' . "\n";
        $html .= '        <?php if ($price): ?>' . "\n";
        $html .= '        <div class="es-meta-item">' . "\n";
        $html .= '            <span class="es-icon">💰</span>' . "\n";
        $html .= '            <div class="es-price"><?php echo esc_html($price); ?></div>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '        <?php endif; ?>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '    <?php if ($ticket_url): ?>' . "\n";
        $html .= '    <div class="es-text-center">' . "\n";
        $html .= '        <a href="<?php echo esc_url($ticket_url); ?>" class="es-btn es-btn-large">Tickets</a>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '    <?php endif; ?>' . "\n";
        $html .= '    <?php if ($description): ?>' . "\n";
        $html .= '    <div class="es-event-content">' . "\n";
        $html .= '        <?php echo wpautop($description); ?>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '    <?php endif; ?>' . "\n";
        $html .= '</article>';
        
        return $html;
    }
    
    /**
     * Get starter CSS template
     */
    public static function get_starter_css() {
        // KEIN HEREDOC - Nur normaler String!
        $css = "/* Ensemble Base CSS wird automatisch geladen! */\n\n";
        $css .= "/* Override CSS Variables: */\n";
        $css .= "/*\n";
        $css .= ":root {\n";
        $css .= "    --es-primary: #FF6B6B;\n";
        $css .= "}\n";
        $css .= "*/\n";
        
        return $css;
    }
    
    /**
     * Register custom templates as layout sets
     */
    public static function register_as_layout_sets($sets) {
        $custom_templates = self::get_all_templates();
        
        foreach ($custom_templates as $slug => $template) {
            $sets['custom_' . $slug] = array(
                'name' => $template['name'],
                'description' => 'Custom Template',
                'style' => 'Custom',
                'best_for' => 'Individuell',
                'features' => array(
                    'Custom HTML',
                    'Custom CSS',
                    'Nutzt Ensemble Base CSS'
                ),
                'custom' => true,
                'path' => wp_upload_dir()['basedir'] . '/ensemble-templates/custom-' . $slug
            );
        }
        
        return $sets;
    }
}

add_filter('ensemble_layout_sets', array('ES_Custom_Templates', 'register_as_layout_sets'));