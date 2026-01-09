<?php
/**
 * Agenda Add-on
 * 
 * Professional agenda/schedule management for conferences and congresses
 * Adds dedicated Agenda tab to Event Wizard when activated
 * 
 * @package Ensemble
 * @subpackage Addons
 * @since 2.9.5
 */

if (!defined('ABSPATH')) exit;

class ES_Agenda_Addon {
    
    /**
     * Addon ID
     */
    const ADDON_ID = 'agenda';
    
    /**
     * Addon version
     */
    const VERSION = '1.0.0';
    
    /**
     * Instance (for backwards compatibility)
     */
    private static $instance = null;
    
    /**
     * Session types with icons
     */
    public static $session_types = array();
    
    /**
     * Get instance (for backwards compatibility with ES_Agenda() function)
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            // Check if already loaded by Addon Manager
            if (class_exists('ES_Addon_Manager')) {
                $addon = ES_Addon_Manager::get_active_addon(self::ADDON_ID);
                if ($addon) {
                    self::$instance = $addon;
                    return self::$instance;
                }
            }
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - PUBLIC for Addon Manager compatibility
     */
    public function __construct() {
        // Store instance for backwards compatibility
        if (is_null(self::$instance)) {
            self::$instance = $this;
        }
        
        $this->define_session_types();
        $this->init_hooks();
    }
    
    /**
     * Define available session types
     */
    private function define_session_types() {
        self::$session_types = array(
            'keynote' => array(
                'label' => __('Keynote', 'ensemble'),
                'icon'  => 'keynote',
                'color' => '#1B365D',  // Navy
            ),
            'talk' => array(
                'label' => __('Talk', 'ensemble'),
                'icon'  => 'keynote',
                'color' => '#2D4A7C',
            ),
            'workshop' => array(
                'label' => __('Workshop', 'ensemble'),
                'icon'  => 'workshop',
                'color' => '#B87333',  // Copper
            ),
            'panel' => array(
                'label' => __('Panel Discussion', 'ensemble'),
                'icon'  => 'panel',
                'color' => '#4A5568',
            ),
            'break' => array(
                'label' => __('Break', 'ensemble'),
                'icon'  => 'coffee',
                'color' => '#718096',
                'is_break' => true,
            ),
            'lunch' => array(
                'label' => __('Lunch', 'ensemble'),
                'icon'  => 'lunch',
                'color' => '#718096',
                'is_break' => true,
            ),
            'dinner' => array(
                'label' => __('Dinner', 'ensemble'),
                'icon'  => 'lunch',
                'color' => '#718096',
                'is_break' => true,
            ),
            'networking' => array(
                'label' => __('Networking', 'ensemble'),
                'icon'  => 'networking',
                'color' => '#38A169',
                'is_break' => true,
            ),
            'registration' => array(
                'label' => __('Registration', 'ensemble'),
                'icon'  => 'registration',
                'color' => '#3182CE',
                'is_break' => true,
            ),
            'custom' => array(
                'label' => __('Other', 'ensemble'),
                'icon'  => 'pause',
                'color' => '#A0AEC0',
            ),
        );
        
        // Allow filtering
        self::$session_types = apply_filters('ensemble_agenda_session_types', self::$session_types);
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Only load if addon is active
        if (!$this->is_active()) {
            return;
        }
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('ensemble_wizard_tabs', array($this, 'add_wizard_tab'), 15);
        add_filter('ensemble_wizard_event_data', array($this, 'add_event_data'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_ensemble_save_agenda', array($this, 'ajax_save_agenda'));
        add_action('wp_ajax_ensemble_get_agenda', array($this, 'ajax_get_agenda'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_shortcode('ensemble_agenda', array($this, 'shortcode_agenda'));
        
        // Template hooks
        add_action('ensemble_event_agenda', array($this, 'display_agenda'), 10, 1);
    }
    
    /**
     * Check if addon is active
     */
    public function is_active() {
        // Use Addon Manager
        if (class_exists('ES_Addon_Manager')) {
            return ES_Addon_Manager::is_addon_active(self::ADDON_ID);
        }
        
        // Fallback: Check option directly
        $active_addons = get_option('ensemble_active_addons', array());
        return in_array(self::ADDON_ID, $active_addons);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only on ensemble pages
        if (strpos($hook, 'ensemble') === false && get_post_type() !== ensemble_get_post_type()) {
            return;
        }
        
        $addon_url = ENSEMBLE_PLUGIN_URL . 'includes/addons/agenda/';
        
        wp_enqueue_style(
            'ensemble-agenda-admin',
            $addon_url . 'assets/agenda-admin.css',
            array(),
            self::VERSION
        );
        
        wp_enqueue_script(
            'ensemble-agenda-admin',
            $addon_url . 'assets/agenda-admin.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable'),
            self::VERSION,
            true
        );
        
        wp_localize_script('ensemble-agenda-admin', 'ensembleAgenda', array(
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('ensemble_agenda_nonce'),
            'sessionTypes' => self::$session_types,
            'iconUrl'      => ENSEMBLE_PLUGIN_URL . 'assets/images/agenda-icons/',
            'i18n'         => array(
                'addDay'        => __('Add Day', 'ensemble'),
                'addSession'    => __('Add Session', 'ensemble'),
                'addBreak'      => __('Add Break', 'ensemble'),
                'deleteDay'     => __('Delete Day', 'ensemble'),
                'deleteSession' => __('Delete Session', 'ensemble'),
                'confirmDelete' => __('Are you sure?', 'ensemble'),
                'room'          => __('Room', 'ensemble'),
                'speaker'       => __('Speaker', 'ensemble'),
                'speakers'      => __('Speaker', 'ensemble'),
                'duration'      => __('Duration', 'ensemble'),
                'minutes'       => __('min', 'ensemble'),
                'linkCatalog'   => __('Link Menu', 'ensemble'),
                'noSpeakers'    => __('No speakers assigned', 'ensemble'),
                'selectSpeaker' => __('Select speaker...', 'ensemble'),
                'day'           => __('Day', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_singular(ensemble_get_post_type())) {
            return;
        }
        
        $event_id = get_the_ID();
        $agenda = $this->get_agenda($event_id);
        
        if (empty($agenda['days'])) {
            return;
        }
        
        $addon_url = ENSEMBLE_PLUGIN_URL . 'includes/addons/agenda/';
        
        wp_enqueue_style(
            'ensemble-agenda-frontend',
            $addon_url . 'assets/agenda-frontend.css',
            array(),
            self::VERSION
        );
    }
    
    /**
     * Add Agenda tab to wizard
     */
    public function add_wizard_tab($tabs) {
        // Insert after 'lineup' tab if it exists, otherwise after 'details'
        $position = array_search('lineup', array_keys($tabs));
        if ($position === false) {
            $position = array_search('details', array_keys($tabs));
        }
        $position = $position !== false ? $position + 1 : 3;
        
        $agenda_tab = array(
            'agenda' => array(
                'label' => __('Agenda', 'ensemble'),
                'icon'  => 'calendar-alt',
            ),
        );
        
        $tabs = array_slice($tabs, 0, $position, true) 
              + $agenda_tab 
              + array_slice($tabs, $position, null, true);
        
        return $tabs;
    }
    
    /**
     * Add agenda data to event data in wizard
     */
    public function add_event_data($event_data, $post) {
        $event_data['agenda'] = $this->get_agenda($post->ID);
        $event_data['agenda_session_types'] = self::$session_types;
        
        return $event_data;
    }
    
    /**
     * Get agenda for event
     */
    public function get_agenda($event_id) {
        $agenda = get_post_meta($event_id, '_ensemble_agenda', true);
        
        if (empty($agenda)) {
            $agenda = array(
                'days'   => array(),
                'rooms'  => array(),
                'tracks' => array(),
            );
        }
        
        return $agenda;
    }
    
    /**
     * Save agenda for event
     */
    public function save_agenda($event_id, $agenda_data) {
        // Sanitize the data
        $sanitized = $this->sanitize_agenda($agenda_data);
        
        // Save
        update_post_meta($event_id, '_ensemble_agenda', $sanitized);
        
        // Clear cache
        if (class_exists('ES_Cache')) {
            ES_Cache::delete('agenda_' . $event_id);
        }
        
        return true;
    }
    
    /**
     * Sanitize agenda data
     */
    private function sanitize_agenda($data) {
        $sanitized = array(
            'days'   => array(),
            'rooms'  => array(),
            'tracks' => array(),
        );
        
        // Sanitize rooms
        if (!empty($data['rooms']) && is_array($data['rooms'])) {
            $sanitized['rooms'] = array_map('sanitize_text_field', $data['rooms']);
        }
        
        // Sanitize tracks
        if (!empty($data['tracks']) && is_array($data['tracks'])) {
            $sanitized['tracks'] = array_map('sanitize_text_field', $data['tracks']);
        }
        
        // Sanitize days and sessions
        if (!empty($data['days']) && is_array($data['days'])) {
            foreach ($data['days'] as $day) {
                $sanitized_day = array(
                    'date'     => sanitize_text_field($day['date'] ?? ''),
                    'label'    => sanitize_text_field($day['label'] ?? ''),
                    'sessions' => array(),
                );
                
                if (!empty($day['sessions']) && is_array($day['sessions'])) {
                    foreach ($day['sessions'] as $session) {
                        $sanitized_session = array(
                            'id'          => sanitize_key($session['id'] ?? uniqid('sess_')),
                            'type'        => sanitize_key($session['type'] ?? 'talk'),
                            'title'       => sanitize_text_field($session['title'] ?? ''),
                            'start'       => sanitize_text_field($session['start'] ?? ''),
                            'end'         => sanitize_text_field($session['end'] ?? ''),
                            'room'        => sanitize_text_field($session['room'] ?? ''),
                            'track'       => sanitize_key($session['track'] ?? ''),
                            'description' => wp_kses_post($session['description'] ?? ''),
                            'speakers'    => array(),
                            'catalog_id'  => intval($session['catalog_id'] ?? 0),
                            'materials'   => array(),
                            'livestream'  => esc_url_raw($session['livestream'] ?? ''),
                        );
                        
                        // Sanitize speakers
                        if (!empty($session['speakers']) && is_array($session['speakers'])) {
                            foreach ($session['speakers'] as $speaker) {
                                $sanitized_session['speakers'][] = array(
                                    'id'   => intval($speaker['id'] ?? 0),
                                    'role' => sanitize_text_field($speaker['role'] ?? 'speaker'),
                                );
                            }
                        }
                        
                        // Sanitize materials
                        if (!empty($session['materials']) && is_array($session['materials'])) {
                            foreach ($session['materials'] as $material) {
                                $sanitized_session['materials'][] = array(
                                    'title' => sanitize_text_field($material['title'] ?? ''),
                                    'url'   => esc_url_raw($material['url'] ?? ''),
                                    'type'  => sanitize_key($material['type'] ?? 'file'),
                                );
                            }
                        }
                        
                        $sanitized_day['sessions'][] = $sanitized_session;
                    }
                }
                
                $sanitized['days'][] = $sanitized_day;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * AJAX: Save agenda
     */
    public function ajax_save_agenda() {
        check_ajax_referer('ensemble_agenda_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $event_id = intval($_POST['event_id'] ?? 0);
        $agenda_data = json_decode(stripslashes($_POST['agenda'] ?? '{}'), true);
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('No event ID', 'ensemble')));
        }
        
        $result = $this->save_agenda($event_id, $agenda_data);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Agenda saved', 'ensemble')));
        } else {
            wp_send_json_error(array('message' => __('Error saving', 'ensemble')));
        }
    }
    
    /**
     * AJAX: Get agenda
     */
    public function ajax_get_agenda() {
        check_ajax_referer('ensemble_agenda_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id'] ?? 0);
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('No event ID', 'ensemble')));
        }
        
        $agenda = $this->get_agenda($event_id);
        
        wp_send_json_success(array('agenda' => $agenda));
    }
    
    /**
     * Shortcode: Display agenda
     */
    public function shortcode_agenda($atts) {
        $atts = shortcode_atts(array(
            'event_id' => get_the_ID(),
            'view'     => 'timeline',  // timeline | grid
            'day'      => '',          // Specific day number
            'track'    => '',          // Specific track
            'room'     => '',          // Specific room
        ), $atts, 'ensemble_agenda');
        
        $event_id = intval($atts['event_id']);
        $agenda = $this->get_agenda($event_id);
        
        if (empty($agenda['days'])) {
            return '';
        }
        
        ob_start();
        
        $template = $atts['view'] === 'grid' ? 'agenda-grid.php' : 'agenda-timeline.php';
        $template_path = ENSEMBLE_PLUGIN_DIR . 'includes/addons/agenda/templates/' . $template;
        
        if (file_exists($template_path)) {
            include $template_path;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Display agenda (for template hook)
     */
    public function display_agenda($event_id) {
        echo $this->shortcode_agenda(array('event_id' => $event_id));
    }
    
    /**
     * Get sessions for a specific day
     */
    public function get_day_sessions($event_id, $day_index = 0) {
        $agenda = $this->get_agenda($event_id);
        
        if (empty($agenda['days'][$day_index]['sessions'])) {
            return array();
        }
        
        return $agenda['days'][$day_index]['sessions'];
    }
    
    /**
     * Get merged timeline (sessions + breaks sorted by time)
     * For use in Kongress layout
     */
    public function get_merged_timeline($event_id, $day_index = 0) {
        $sessions = $this->get_day_sessions($event_id, $day_index);
        
        if (empty($sessions)) {
            return array();
        }
        
        // Sort by start time
        usort($sessions, function($a, $b) {
            return strcmp($a['start'], $b['start']);
        });
        
        // Enrich with speaker data
        foreach ($sessions as &$session) {
            if (!empty($session['speakers'])) {
                foreach ($session['speakers'] as &$speaker) {
                    if (!empty($speaker['id'])) {
                        $speaker_post = get_post($speaker['id']);
                        if ($speaker_post) {
                            $speaker['name'] = $speaker_post->post_title;
                            $speaker['image'] = get_the_post_thumbnail_url($speaker['id'], 'thumbnail');
                            $speaker['role_title'] = get_post_meta($speaker['id'], 'artist_role', true);
                        }
                    }
                }
            }
            
            // Add session type info
            $type_key = $session['type'] ?? 'talk';
            $session['type_info'] = self::$session_types[$type_key] ?? self::$session_types['custom'];
        }
        
        return $sessions;
    }
    
    /**
     * Check if event has agenda
     */
    public static function has_agenda($event_id) {
        $agenda = get_post_meta($event_id, '_ensemble_agenda', true);
        return !empty($agenda['days']);
    }
}

// Initialize
function ES_Agenda() {
    return ES_Agenda_Addon::instance();
}

// Hook into plugins_loaded or init
add_action('init', 'ES_Agenda', 15);

/**
 * Helper function to check if event has agenda
 */
function ensemble_has_agenda($event_id) {
    return ES_Agenda_Addon::has_agenda($event_id);
}

/**
 * Helper function to get agenda
 */
function ensemble_get_agenda($event_id) {
    return ES_Agenda()->get_agenda($event_id);
}

/**
 * Helper function to get merged timeline
 */
function ensemble_get_agenda_timeline($event_id, $day_index = 0) {
    return ES_Agenda()->get_merged_timeline($event_id, $day_index);
}

/**
 * Register the Agenda add-on with Ensemble Addon Manager
 */
if (class_exists('ES_Addon_Manager')) {
    ES_Addon_Manager::register_addon('agenda', array(
        'name'          => __('Agenda Pro', 'ensemble'),
        'description'   => __('Professional agenda management for congresses and conferences. Multi-day events, parallel tracks, sessions with speakers.', 'ensemble'),
        'version'       => '1.0.0',
        'author'        => 'Kraftwerk Marketing',
        'requires_pro'  => true,
        'class'         => 'ES_Agenda_Addon',
        'icon'          => 'dashicons-calendar-alt',
        'settings_page' => false,
        'has_frontend'  => true,
    ));
}
