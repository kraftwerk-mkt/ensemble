<?php
/**
 * Ensemble Media Folders Pro Add-on
 * 
 * Adds folder organization to WordPress Media Library
 * with automatic folder creation for Events, Artists, and Locations
 *
 * @package Ensemble
 * @subpackage Addons/MediaFolders
 * @since 2.7.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Media_Folders_Addon extends ES_Addon_Base {
    
    /**
     * Add-on slug
     * @var string
     */
    protected $slug = 'media-folders';
    
    /**
     * Add-on name
     * @var string
     */
    protected $name = 'Media Folders Pro';
    
    /**
     * Add-on version
     * @var string
     */
    protected $version = '1.0.13';
    
    /**
     * Taxonomy instance
     * @var ES_Folder_Taxonomy
     */
    public $taxonomy;
    
    /**
     * AJAX handler instance
     * @var ES_Folder_Ajax
     */
    public $ajax;
    
    /**
     * Automation instance
     * @var ES_Folder_Automation
     */
    public $automation;
    
    /**
     * Default settings
     * @var array
     */
    protected $default_settings = array(
        // Automation settings
        'auto_events'         => true,
        'auto_artists'        => true,
        'auto_locations'      => true,
        'auto_assign_upload'  => true,
        'delete_folder_on_post_delete' => false,
        
        // Display settings
        'color_events'    => '#3582c4',
        'color_artists'   => '#9b59b6',
        'color_locations' => '#27ae60',
        'show_count'      => true,
        'hide_empty'      => false,
        
        // Smart Folders
        'smart_folders_enabled' => true,
        'smart_all_images'      => true,
        'smart_all_videos'      => true,
        'smart_all_documents'   => true,
        'smart_this_week'       => true,
        'smart_unused'          => true,
        'smart_large_files'     => true,
        'smart_large_threshold' => 2, // MB
    );
    
    /**
     * Initialize add-on
     */
    protected function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        $this->taxonomy   = new ES_Folder_Taxonomy($this);
        $this->ajax       = new ES_Folder_Ajax($this);
        $this->automation = new ES_Folder_Automation($this);
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        $addon_path = $this->get_addon_path();
        
        require_once $addon_path . 'class-es-folder-taxonomy.php';
        require_once $addon_path . 'class-es-folder-ajax.php';
        require_once $addon_path . 'class-es-folder-automation.php';
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add body class for styling
        add_filter('admin_body_class', array($this, 'add_body_class'));
        
        // Media Library integration
        add_action('restrict_manage_posts', array($this, 'add_folder_filter_dropdown'));
        add_filter('parse_query', array($this, 'filter_media_by_folder'));
        
        // Media Modal integration
        add_action('wp_enqueue_media', array($this, 'enqueue_media_modal_assets'));
        add_filter('ajax_query_attachments_args', array($this, 'filter_media_modal_query'));
        
        // Print folder data for Media Modal
        add_action('admin_footer', array($this, 'print_media_modal_data'));
        
        // Auto-assign uploaded media to folder
        add_action('add_attachment', array($this, 'auto_assign_upload_to_folder'));
        
        // AJAX endpoints are registered in ES_Folder_Ajax
    }
    
    /**
     * Add body class for styling
     */
    public function add_body_class($classes) {
        global $pagenow;
        
        if ($pagenow === 'upload.php') {
            $classes .= ' es-media-folders-active';
        }
        
        return $classes;
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Always load on media library
        if ($hook === 'upload.php') {
            $this->do_enqueue_assets();
            return;
        }
        
        // Load on specific hooks where media modal might be used
        $allowed_hooks = array(
            'post.php',
            'post-new.php',
            'media-upload-popup',
            'ensemble_page_ensemble-addons',
            'ensemble_page_ensemble-settings',
            'toplevel_page_ensemble',
            'ensemble_page_ensemble-events',
            'ensemble_page_ensemble-artists', 
            'ensemble_page_ensemble-locations',
            'ensemble_page_ensemble-wizard',
        );
        
        if (in_array($hook, $allowed_hooks)) {
            $this->do_enqueue_assets();
            return;
        }
        
        // Load on any Ensemble admin page
        if (strpos($hook, 'ensemble') !== false) {
            $this->do_enqueue_assets();
            return;
        }
        
        // Also load on post edit screens for ensemble post types
        global $post_type;
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            $ensemble_types = array('es_event', 'es_artist', 'es_location', 'ensemble_event', 'ensemble_artist', 'ensemble_location');
            if (in_array($post_type, $ensemble_types)) {
                $this->do_enqueue_assets();
                return;
            }
        }
        
        // Load on any admin page that has media scripts enqueued
        // This catches ACF fields, Gutenberg blocks, etc.
        if (did_action('wp_enqueue_media')) {
            $this->do_enqueue_assets();
            return;
        }
    }
    
    /**
     * Actually enqueue the assets
     */
    private function do_enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'es-media-folders',
            $this->get_addon_url() . 'assets/css/media-folders.css',
            array(),
            $this->version
        );
        
        // JS
        wp_enqueue_script(
            'es-media-folders',
            $this->get_addon_url() . 'assets/js/media-folders.js',
            array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
            $this->version,
            true
        );
        
        // Ensure media scripts are loaded
        wp_enqueue_media();
        
        // Also load modal script on all Ensemble pages
        wp_enqueue_script(
            'es-media-folders-modal',
            $this->get_addon_url() . 'assets/js/media-modal.js',
            array('jquery', 'media-views'),
            $this->version,
            true
        );
        
        // Localize for modal
        wp_localize_script('es-media-folders-modal', 'esMediaFoldersModal', array(
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('es_media_folders'),
            'strings'  => array(
                'allFolders'    => __('All Folders', 'ensemble'),
                'uncategorized' => __('Uncategorized', 'ensemble'),
                'filterBy'      => __('Filter by folder', 'ensemble'),
            ),
        ));
        
        // Localize script
        wp_localize_script('es-media-folders', 'esMediaFolders', array(
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('es_media_folders'),
            'settings' => $this->get_all_settings(),
            'strings'  => array(
                'newFolder'     => __('New Folder', 'ensemble'),
                'rename'        => __('Rename', 'ensemble'),
                'delete'        => __('Delete', 'ensemble'),
                'confirmDelete' => __('Are you sure you want to delete this folder? Media files will be moved to "Uncategorized".', 'ensemble'),
                'moveItems'     => __('Move %d items', 'ensemble'),
                'folderCreated' => __('Folder created', 'ensemble'),
                'folderDeleted' => __('Folder deleted', 'ensemble'),
                'itemsMoved'    => __('Items moved', 'ensemble'),
                'error'         => __('An error occurred', 'ensemble'),
                'allMedia'      => __('All Media', 'ensemble'),
                'uncategorized' => __('Uncategorized', 'ensemble'),
            ),
            'colors' => array(
                'events'    => $this->get_setting('color_events', '#3582c4'),
                'artists'   => $this->get_setting('color_artists', '#9b59b6'),
                'locations' => $this->get_setting('color_locations', '#27ae60'),
            ),
        ));
    }
    
    /**
     * Add folder filter dropdown in Media Library
     */
    public function add_folder_filter_dropdown($post_type) {
        if ($post_type !== 'attachment') {
            return;
        }
        
        $folders = get_terms(array(
            'taxonomy'   => 'es_media_folder',
            'hide_empty' => $this->get_setting('hide_empty', false),
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));
        
        if (is_wp_error($folders) || empty($folders)) {
            return;
        }
        
        $selected = isset($_GET['es_media_folder']) ? sanitize_text_field($_GET['es_media_folder']) : '';
        
        echo '<select name="es_media_folder" id="es-media-folder-filter" class="es-folder-filter">';
        echo '<option value="">' . esc_html__('All Folders', 'ensemble') . '</option>';
        echo '<option value="uncategorized"' . selected($selected, 'uncategorized', false) . '>' . esc_html__('Uncategorized', 'ensemble') . '</option>';
        
        // Build hierarchical list
        $this->render_folder_options($folders, $selected);
        
        echo '</select>';
    }
    
    /**
     * Render folder options hierarchically
     */
    private function render_folder_options($folders, $selected, $parent = 0, $depth = 0) {
        foreach ($folders as $folder) {
            if ($folder->parent != $parent) {
                continue;
            }
            
            $prefix = str_repeat('— ', $depth);
            $count = $this->get_setting('show_count', true) ? " ({$folder->count})" : '';
            
            printf(
                '<option value="%s" %s>%s%s%s</option>',
                esc_attr($folder->slug),
                selected($selected, $folder->slug, false),
                $prefix,
                esc_html($folder->name),
                $count
            );
            
            // Render children
            $this->render_folder_options($folders, $selected, $folder->term_id, $depth + 1);
        }
    }
    
    /**
     * Filter media by folder
     */
    public function filter_media_by_folder($query) {
        global $pagenow;
        
        if ($pagenow !== 'upload.php' || !is_admin()) {
            return;
        }
        
        if (empty($_GET['es_media_folder'])) {
            return;
        }
        
        $folder = sanitize_text_field($_GET['es_media_folder']);
        
        if ($folder === 'uncategorized') {
            // Show media without any folder
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'es_media_folder',
                    'operator' => 'NOT EXISTS',
                ),
            ));
        } elseif (strpos($folder, 'smart_') === 0) {
            // Smart folder filter
            $this->apply_smart_folder_to_query($query, $folder);
        } else {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'es_media_folder',
                    'field'    => 'slug',
                    'terms'    => $folder,
                ),
            ));
        }
    }
    
    /**
     * Apply smart folder filter to WP_Query
     */
    private function apply_smart_folder_to_query($query, $smart_folder) {
        switch ($smart_folder) {
            case 'smart_images':
                $query->set('post_mime_type', 'image');
                break;
                
            case 'smart_videos':
                $query->set('post_mime_type', 'video');
                break;
                
            case 'smart_documents':
                $query->set('post_mime_type', array(
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain',
                ));
                break;
                
            case 'smart_audio':
                $query->set('post_mime_type', 'audio');
                break;
                
            case 'smart_this_week':
                $query->set('date_query', array(
                    array(
                        'after' => '1 week ago',
                    ),
                ));
                break;
                
            case 'smart_unused':
                $query->set('post_parent', 0);
                break;
        }
    }
    
    /**
     * Get setting with default fallback
     */
    public function get_setting($key, $default = null) {
        if ($default === null && isset($this->default_settings[$key])) {
            $default = $this->default_settings[$key];
        }
        return parent::get_setting($key, $default);
    }
    
    /**
     * Get all settings with defaults
     */
    public function get_all_settings() {
        return wp_parse_args($this->settings, $this->default_settings);
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        ob_start();
        include $this->get_addon_path() . 'templates/settings.php';
        return ob_get_clean();
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings) {
        $sanitized = array();
        
        // Boolean settings - handle both string "true"/"false" and actual booleans
        $booleans = array(
            'auto_events', 'auto_artists', 'auto_locations', 'auto_assign_upload', 
            'delete_folder_on_post_delete', 'show_count', 'hide_empty',
            'smart_folders_enabled', 'smart_all_images', 'smart_all_videos',
            'smart_all_documents', 'smart_this_week', 'smart_unused', 'smart_large_files'
        );
        foreach ($booleans as $key) {
            // Handle various boolean representations from JS
            if (!isset($settings[$key])) {
                $sanitized[$key] = false;
            } elseif ($settings[$key] === 'false' || $settings[$key] === '0' || $settings[$key] === '') {
                $sanitized[$key] = false;
            } else {
                $sanitized[$key] = (bool) $settings[$key];
            }
        }
        
        // Color settings
        $colors = array('color_events', 'color_artists', 'color_locations');
        foreach ($colors as $key) {
            $sanitized[$key] = isset($settings[$key]) ? sanitize_hex_color($settings[$key]) : $this->default_settings[$key];
        }
        
        // Numeric settings
        if (isset($settings['smart_large_threshold'])) {
            $sanitized['smart_large_threshold'] = max(1, min(100, intval($settings['smart_large_threshold'])));
        } else {
            $sanitized['smart_large_threshold'] = $this->default_settings['smart_large_threshold'];
        }
        
        return $sanitized;
    }
    
    /**
     * Enqueue Media Modal assets
     */
    public function enqueue_media_modal_assets() {
        // CSS for modal sidebar
        wp_enqueue_style(
            'es-media-folders',
            $this->get_addon_url() . 'assets/css/media-folders.css',
            array(),
            $this->version
        );
        
        // Media Modal JS
        wp_enqueue_script(
            'es-media-folders-modal',
            $this->get_addon_url() . 'assets/js/media-modal.js',
            array('jquery', 'media-views'),
            $this->version,
            true
        );
        
        // Localize for modal
        wp_localize_script('es-media-folders-modal', 'esMediaFoldersModal', array(
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('es_media_folders'),
            'strings'  => array(
                'allFolders'    => __('All Folders', 'ensemble'),
                'uncategorized' => __('Uncategorized', 'ensemble'),
                'filterBy'      => __('Filter by folder', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Filter Media Modal query by folder
     */
    public function filter_media_modal_query($query) {
        // Check multiple sources for the folder filter
        $folder = '';
        
        // Check in query array first (from wp.media)
        if (!empty($query['es_folder'])) {
            $folder = sanitize_text_field($query['es_folder']);
        }
        // Fallback to REQUEST
        elseif (!empty($_REQUEST['es_folder'])) {
            $folder = sanitize_text_field($_REQUEST['es_folder']);
        }
        // Check POST query parameter
        elseif (!empty($_POST['query']['es_folder'])) {
            $folder = sanitize_text_field($_POST['query']['es_folder']);
        }
        
        // Debug logging (can be removed later)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ES Media Folders: filter_media_modal_query called, folder=' . ($folder ?: '(empty)'));
        }
        
        if (empty($folder)) {
            return $query;
        }
        
        // Apply filter based on folder type
        if ($folder === 'uncategorized') {
            $query['tax_query'] = array(
                array(
                    'taxonomy' => 'es_media_folder',
                    'operator' => 'NOT EXISTS',
                ),
            );
        } elseif (strpos($folder, 'smart_') === 0) {
            // Smart folder filter
            $query = $this->apply_smart_folder_filter($query, $folder);
        } else {
            $query['tax_query'] = array(
                array(
                    'taxonomy' => 'es_media_folder',
                    'field'    => 'term_id',
                    'terms'    => intval($folder),
                ),
            );
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ES Media Folders: Applied tax_query for folder=' . $folder);
        }
        
        return $query;
    }
    
    /**
     * Apply smart folder filter to query
     */
    private function apply_smart_folder_filter($query, $smart_folder) {
        switch ($smart_folder) {
            case 'smart_images':
                $query['post_mime_type'] = 'image';
                break;
                
            case 'smart_videos':
                $query['post_mime_type'] = 'video';
                break;
                
            case 'smart_documents':
                $query['post_mime_type'] = array(
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain',
                );
                break;
                
            case 'smart_audio':
                $query['post_mime_type'] = 'audio';
                break;
                
            case 'smart_this_week':
                $query['date_query'] = array(
                    array(
                        'after' => '1 week ago',
                    ),
                );
                break;
                
            case 'smart_this_month':
                $query['date_query'] = array(
                    array(
                        'after' => '1 month ago',
                    ),
                );
                break;
                
            case 'smart_unused':
                // Media not attached to any post
                $query['post_parent'] = 0;
                // Also check if not used in content - this is more complex
                // For now just check post_parent
                break;
                
            case 'smart_large':
                // Large files - handled via meta query
                $threshold = $this->get_setting('smart_large_threshold', 2) * 1024 * 1024; // Convert MB to bytes
                $query['meta_query'] = array(
                    array(
                        'key'     => '_wp_attachment_metadata',
                        'compare' => 'EXISTS',
                    ),
                );
                // Note: File size filtering is complex in WP, we'll handle it in post-processing
                break;
        }
        
        return $query;
    }
    
    /**
     * Print folder data for Media Modal
     */
    public function print_media_modal_data() {
        $screen = get_current_screen();
        
        // Only on screens that might use media modal
        if (!$screen) {
            return;
        }
        
        // Get folder tree
        $tree = $this->taxonomy->get_folder_tree();
        $counts = $this->taxonomy->get_folder_counts();
        
        // Debug: Log tree structure
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ES Media Folders: Tree count = ' . count($tree));
            foreach ($tree as $folder) {
                error_log('  - ' . $folder->name . ' (ID: ' . $folder->term_id . ', children: ' . count($folder->children ?? []) . ')');
            }
        }
        
        // Build flat folder list for dropdown
        $folders = $this->build_flat_folder_list($tree, $counts);
        
        // Get smart folders
        $smart_folders = $this->get_smart_folders();
        
        ?>
        <script type="text/javascript">
            var esMediaFoldersData = {
                folders: <?php echo json_encode($folders); ?>,
                tree: <?php echo json_encode($tree); ?>,
                counts: <?php echo json_encode($counts); ?>,
                smartFolders: <?php echo json_encode($smart_folders); ?>,
                colors: {
                    events: '<?php echo esc_js($this->get_setting('color_events', '#3582c4')); ?>',
                    artists: '<?php echo esc_js($this->get_setting('color_artists', '#9b59b6')); ?>',
                    locations: '<?php echo esc_js($this->get_setting('color_locations', '#27ae60')); ?>'
                },
                debug: {
                    screen: '<?php echo esc_js($screen->id); ?>',
                    folderCount: <?php echo count($folders); ?>,
                    treeCount: <?php echo count($tree); ?>
                }
            };
            console.log('esMediaFoldersData loaded:', esMediaFoldersData);
            console.log('Folders with depth:', esMediaFoldersData.folders.filter(f => f.depth > 0));
        </script>
        <?php
    }
    
    /**
     * Get smart folders configuration
     */
    private function get_smart_folders() {
        if (!$this->get_setting('smart_folders_enabled', true)) {
            return array();
        }
        
        $smart_folders = array();
        
        if ($this->get_setting('smart_all_images', true)) {
            $smart_folders[] = array(
                'id'    => 'smart_images',
                'name'  => __('All Images', 'ensemble'),
                'icon'  => 'dashicons-format-image',
                'color' => '#e91e63',
                'count' => $this->count_media_by_type('image'),
            );
        }
        
        if ($this->get_setting('smart_all_videos', true)) {
            $smart_folders[] = array(
                'id'    => 'smart_videos',
                'name'  => __('All Videos', 'ensemble'),
                'icon'  => 'dashicons-video-alt3',
                'color' => '#ff5722',
                'count' => $this->count_media_by_type('video'),
            );
        }
        
        if ($this->get_setting('smart_all_documents', true)) {
            $smart_folders[] = array(
                'id'    => 'smart_documents',
                'name'  => __('Documents', 'ensemble'),
                'icon'  => 'dashicons-media-document',
                'color' => '#607d8b',
                'count' => $this->count_documents(),
            );
        }
        
        if ($this->get_setting('smart_this_week', true)) {
            $smart_folders[] = array(
                'id'    => 'smart_this_week',
                'name'  => __('This Week', 'ensemble'),
                'icon'  => 'dashicons-calendar',
                'color' => '#00bcd4',
                'count' => $this->count_media_this_week(),
            );
        }
        
        if ($this->get_setting('smart_unused', true)) {
            $smart_folders[] = array(
                'id'    => 'smart_unused',
                'name'  => __('Unattached', 'ensemble'),
                'icon'  => 'dashicons-dismiss',
                'color' => '#ff9800',
                'count' => $this->count_unattached_media(),
            );
        }
        
        if ($this->get_setting('smart_large_files', true)) {
            $threshold = $this->get_setting('smart_large_threshold', 2);
            $smart_folders[] = array(
                'id'    => 'smart_large',
                'name'  => sprintf(__('Large Files (>%dMB)', 'ensemble'), $threshold),
                'icon'  => 'dashicons-database',
                'color' => '#f44336',
                'count' => $this->count_large_files($threshold),
            );
        }
        
        return $smart_folders;
    }
    
    /**
     * Count media by MIME type
     */
    private function count_media_by_type($type) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE %s",
            $type . '%'
        ));
    }
    
    /**
     * Count documents
     */
    private function count_documents() {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND (
                post_mime_type LIKE 'application/pdf%'
                OR post_mime_type LIKE 'application/msword%'
                OR post_mime_type LIKE 'application/vnd.%'
                OR post_mime_type LIKE 'text/%'
            )"
        );
    }
    
    /**
     * Count media uploaded this week
     */
    private function count_media_this_week() {
        global $wpdb;
        
        $week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_date >= %s",
            $week_ago
        ));
    }
    
    /**
     * Count unattached media
     */
    private function count_unattached_media() {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_parent = 0"
        );
    }
    
    /**
     * Count large files
     */
    private function count_large_files($threshold_mb) {
        global $wpdb;
        
        $threshold_bytes = $threshold_mb * 1024 * 1024;
        
        // Get all attachments and check file size
        $attachments = $wpdb->get_results(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment'"
        );
        
        $count = 0;
        foreach ($attachments as $attachment) {
            $file = get_attached_file($attachment->ID);
            if ($file && file_exists($file) && filesize($file) > $threshold_bytes) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Build flat folder list for dropdown
     */
    private function build_flat_folder_list($tree, $counts, $depth = 0) {
        $folders = array();
        
        foreach ($tree as $folder) {
            $prefix = str_repeat('— ', $depth);
            $count = isset($counts[$folder->term_id]) ? $counts[$folder->term_id] : 0;
            $hasChildren = !empty($folder->children);
            
            $folders[] = array(
                'id'          => $folder->term_id,
                'name'        => $prefix . $folder->name,
                'slug'        => $folder->slug,
                'count'       => $count,
                'parent'      => $folder->parent,
                'color'       => $folder->color,
                'icon'        => $folder->icon,
                'type'        => $folder->type,
                'isParent'    => $folder->type === 'parent',
                'hasChildren' => $hasChildren,
                'depth'       => $depth,
            );
            
            // Add children recursively
            if ($hasChildren) {
                $children = $this->build_flat_folder_list($folder->children, $counts, $depth + 1);
                $folders = array_merge($folders, $children);
            }
        }
        
        return $folders;
    }
    
    /**
     * Auto-assign uploaded media to folder
     * 
     * @param int $attachment_id The attachment ID
     */
    public function auto_assign_upload_to_folder($attachment_id) {
        // Check for folder parameter in POST
        $folder_id = 0;
        
        if (!empty($_POST['es_upload_folder'])) {
            $folder_id = intval($_POST['es_upload_folder']);
        } elseif (!empty($_REQUEST['es_upload_folder'])) {
            $folder_id = intval($_REQUEST['es_upload_folder']);
        }
        
        // Skip if no folder specified or uncategorized
        if (empty($folder_id) || $folder_id <= 0) {
            return;
        }
        
        // Verify the folder exists
        $term = get_term($folder_id, 'es_media_folder');
        if (!$term || is_wp_error($term)) {
            return;
        }
        
        // Assign attachment to folder
        wp_set_object_terms($attachment_id, $folder_id, 'es_media_folder', false);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("ES Media Folders: Auto-assigned attachment {$attachment_id} to folder {$folder_id}");
        }
    }
}
