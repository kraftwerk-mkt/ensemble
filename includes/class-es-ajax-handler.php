<?php
/**
 * AJAX Handler
 * 
 * Handles all AJAX requests from the wizard
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_AJAX_Handler {
    
    /**
     * Save event
     */
    public function save_event() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        
        if (empty($title)) {
            wp_send_json_error(array('message' => 'Event title is required'));
            return;
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'   => $title,
            'post_type' => ensemble_get_post_type(),
            'post_status'  => 'publish',
        );
        
        // Handle event status
        $event_status = isset($_POST['event_status']) ? sanitize_text_field($_POST['event_status']) : 'publish';
        if (in_array($event_status, array('draft', 'publish', 'cancelled', 'postponed', 'preview'))) {
            // For cancelled/postponed/preview, keep post published but store status in meta
            if (in_array($event_status, array('cancelled', 'postponed', 'preview'))) {
                $post_data['post_status'] = 'publish';
            } else {
                $post_data['post_status'] = $event_status;
            }
        }
        
        // Generate post_name (slug) from title if creating new post
        if (!$event_id) {
            $post_data['post_name'] = sanitize_title($title);
        }
        
        // Update or create
        if ($event_id) {
            $post_data['ID'] = $event_id;
            // Also update slug if title changed
            $current_post = get_post($event_id);
            if ($current_post && $current_post->post_title !== $title) {
                $post_data['post_name'] = sanitize_title($title);
            }
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        $event_id = $result;
        
        // Ensure the post has a proper permalink by clearing post cache
        clean_post_cache($event_id);
        
        // Save event fields - works with or without ACF
        $event_fields = array(
            'event_date' => isset($_POST['event_date']) ? sanitize_text_field($_POST['event_date']) : '',
            'event_time' => isset($_POST['event_time']) ? sanitize_text_field($_POST['event_time']) : '',
            'event_time_end' => isset($_POST['event_time_end']) ? sanitize_text_field($_POST['event_time_end']) : '',
            'event_description' => isset($_POST['event_description']) ? wp_kses_post($_POST['event_description']) : '',
            'event_additional_info' => isset($_POST['event_additional_info']) ? wp_kses_post($_POST['event_additional_info']) : '',
            'event_location' => isset($_POST['event_location']) ? intval($_POST['event_location']) : 0,
            'event_venue' => isset($_POST['event_venue']) ? sanitize_text_field($_POST['event_venue']) : '',
            'event_price' => isset($_POST['event_price']) ? sanitize_text_field($_POST['event_price']) : '',
            'event_price_note' => isset($_POST['event_price_note']) ? sanitize_text_field($_POST['event_price_note']) : '',
            'event_ticket_url' => isset($_POST['event_ticket_url']) ? esc_url_raw($_POST['event_ticket_url']) : '',
            'event_button_text' => isset($_POST['event_button_text']) ? sanitize_text_field($_POST['event_button_text']) : '',
            'event_external_url' => isset($_POST['event_external_url']) ? esc_url_raw($_POST['event_external_url']) : '',
            'event_external_text' => isset($_POST['event_external_text']) ? sanitize_text_field($_POST['event_external_text']) : '',
            'event_badge' => isset($_POST['event_badge']) ? sanitize_text_field($_POST['event_badge']) : '',
            'event_badge_custom' => isset($_POST['event_badge_custom']) ? sanitize_text_field($_POST['event_badge_custom']) : '',
            'event_status' => $event_status,
        );
        
        // Handle venue configuration (for multivenue events)
        if (isset($_POST['venue_config'])) {
            $venue_config_val = stripslashes($_POST['venue_config']);
            // Only process if it's not empty JSON object
            if ($venue_config_val && $venue_config_val !== '{}') {
                $venue_config_raw = json_decode($venue_config_val, true);
                if (is_array($venue_config_raw) && !empty($venue_config_raw)) {
                    $venue_config = array();
                    foreach ($venue_config_raw as $venue_name => $config) {
                        $venue_config[sanitize_text_field($venue_name)] = array(
                            'enabled' => !empty($config['enabled']),
                            'customName' => isset($config['customName']) ? sanitize_text_field($config['customName']) : '',
                            'description' => isset($config['description']) ? sanitize_textarea_field($config['description']) : '',
                            'genres' => isset($config['genres']) ? array_map('intval', (array) $config['genres']) : array(),
                        );
                    }
                    $event_fields['venue_config'] = $venue_config;
                } else {
                    // Clear venue config if empty
                    $event_fields['venue_config'] = array();
                }
            } else {
                // Clear venue config if empty JSON
                $event_fields['venue_config'] = array();
            }
        }
        
        // Save artist order
        if (isset($_POST['artist_order']) && !empty($_POST['artist_order'])) {
            $event_fields['artist_order'] = sanitize_text_field($_POST['artist_order']);
        }
        
        // Handle artists (can be array)
        if (isset($_POST['event_artist'])) {
            $event_fields['event_artist'] = array_map('intval', (array) $_POST['event_artist']);
        } else {
            $event_fields['event_artist'] = array();
        }
        
        // Handle artist times (JSON string from JS)
        if (isset($_POST['artist_times']) && !empty($_POST['artist_times'])) {
            $artist_times_raw = json_decode(stripslashes($_POST['artist_times']), true);
            if (is_array($artist_times_raw)) {
                $artist_times = array();
                foreach ($artist_times_raw as $artist_id => $time) {
                    if (!empty($time)) {
                        $artist_times[intval($artist_id)] = sanitize_text_field($time);
                    }
                }
                $event_fields['artist_times'] = $artist_times;
            }
        }
        
        // Handle artist venues (JSON string from JS)
        if (isset($_POST['artist_venues']) && !empty($_POST['artist_venues'])) {
            $artist_venues_raw = json_decode(stripslashes($_POST['artist_venues']), true);
            if (is_array($artist_venues_raw)) {
                $artist_venues = array();
                foreach ($artist_venues_raw as $artist_id => $venue) {
                    if (!empty($venue)) {
                        $artist_venues[intval($artist_id)] = sanitize_text_field($venue);
                    }
                }
                $event_fields['artist_venues'] = $artist_venues;
            }
        }
        
        // Handle agenda breaks (JSON string from JS) - for Kongress layout timeline
        if (isset($_POST['agenda_breaks']) && !empty($_POST['agenda_breaks'])) {
            $breaks_raw = json_decode(stripslashes($_POST['agenda_breaks']), true);
            if (is_array($breaks_raw) && function_exists('ensemble_save_agenda_breaks')) {
                ensemble_save_agenda_breaks($event_id, $breaks_raw);
            }
        } else {
            // Clear breaks if empty
            delete_post_meta($event_id, '_agenda_breaks');
        }
        
        // Handle artist session titles (JSON string from JS)
        if (isset($_POST['artist_session_titles']) && !empty($_POST['artist_session_titles'])) {
            $session_titles_raw = json_decode(stripslashes($_POST['artist_session_titles']), true);
            if (is_array($session_titles_raw)) {
                $session_titles = array();
                foreach ($session_titles_raw as $artist_id => $title) {
                    if (!empty($title)) {
                        $session_titles[intval($artist_id)] = sanitize_text_field($title);
                    }
                }
                $event_fields['artist_session_titles'] = $session_titles;
            }
        }
        
        // Handle full agenda data from Agenda Add-on
        if (isset($_POST['agenda_data']) && !empty($_POST['agenda_data'])) {
            $agenda_data = json_decode(stripslashes($_POST['agenda_data']), true);
            if (is_array($agenda_data) && function_exists('ES_Agenda')) {
                ES_Agenda()->save_agenda($event_id, $agenda_data);
            }
        }
        
        // Extract special fields that should NOT go through ensemble_update_field
        // Some need underscore prefix (private), some don't
        $underscore_fields = array('event_status', 'artist_order');
        $direct_fields = array('artist_times', 'artist_venues', 'artist_session_titles', 'event_venue', 'venue_config', 'event_price_note', 'event_badge', 'event_badge_custom');
        
        $underscore_special = array();
        $direct_special = array();
        
        foreach ($underscore_fields as $field_name) {
            if (isset($event_fields[$field_name])) {
                $underscore_special[$field_name] = $event_fields[$field_name];
                unset($event_fields[$field_name]);
            }
        }
        
        foreach ($direct_fields as $field_name) {
            if (isset($event_fields[$field_name])) {
                $direct_special[$field_name] = $event_fields[$field_name];
                unset($event_fields[$field_name]);
            }
        }
        
        // Save regular fields using mapping helper OR direct post meta
        foreach ($event_fields as $field_name => $field_value) {
            // Use ensemble_update_field if available (handles ACF and mapping)
            if (function_exists('ensemble_update_field')) {
                ensemble_update_field($field_name, $field_value, $event_id);
            } else {
                // Fallback: Direct post meta
                update_post_meta($event_id, $field_name, $field_value);
            }
        }
        
        // Save underscore-prefixed fields (private meta)
        foreach ($underscore_special as $field_name => $field_value) {
            update_post_meta($event_id, '_' . $field_name, $field_value);
        }
        
        // Save direct fields without underscore
        foreach ($direct_special as $field_name => $field_value) {
            update_post_meta($event_id, $field_name, $field_value);
        }
        
        // Save custom ACF fields from wizard steps (only if ACF is active)
        if (function_exists('update_field') && isset($_POST['acf']) && is_array($_POST['acf'])) {
            foreach ($_POST['acf'] as $field_key => $field_value) {
                // Sanitize based on field type
                $sanitized_value = $this->sanitize_acf_value($field_key, $field_value);
                update_field($field_key, $sanitized_value, $event_id);
            }
        }
        
        // Set categories
        if (isset($_POST['categories']) && is_array($_POST['categories']) && !empty($_POST['categories'])) {
            $categories = array_map('intval', $_POST['categories']);
            wp_set_post_terms($event_id, $categories, 'ensemble_category');
        } else {
            // No categories set - assign default "General" category
            // First, check if "General" category exists, if not create it
            $default_cat = get_term_by('name', 'General', 'ensemble_category');
            
            if (!$default_cat) {
                $default_cat = wp_insert_term('General', 'ensemble_category', array(
                    'description' => 'Default category for events',
                    'slug' => 'general',
                ));
                
                if (!is_wp_error($default_cat)) {
                    $default_cat = get_term($default_cat['term_id'], 'ensemble_category');
                }
            }
            
            // Assign the default category
            if ($default_cat && !is_wp_error($default_cat)) {
                wp_set_post_terms($event_id, array($default_cat->term_id), 'ensemble_category');
            }
        }
        
        // Set genres
        if (isset($_POST['event_genres']) && is_array($_POST['event_genres']) && !empty($_POST['event_genres'])) {
            $genres = array_map('intval', $_POST['event_genres']);
            wp_set_post_terms($event_id, $genres, 'ensemble_genre');
        } else {
            // Clear genres if none selected
            wp_set_post_terms($event_id, array(), 'ensemble_genre');
        }
        
        // Save show_artist_genres option
        if (isset($_POST['show_artist_genres']) && $_POST['show_artist_genres'] == '1') {
            update_post_meta($event_id, '_es_show_artist_genres', '1');
        } else {
            delete_post_meta($event_id, '_es_show_artist_genres');
        }
        
        // ====================================
        // Duration Type System (Festival/Exhibition)
        // ====================================
        
        // Save duration type (single, multi_day, permanent)
        $duration_type = isset($_POST['duration_type']) ? sanitize_key($_POST['duration_type']) : 'single';
        if (in_array($duration_type, array('single', 'multi_day', 'permanent'))) {
            update_post_meta($event_id, ES_Meta_Keys::EVENT_DURATION_TYPE, $duration_type);
        } else {
            update_post_meta($event_id, ES_Meta_Keys::EVENT_DURATION_TYPE, 'single');
        }
        
        // Handle date based on duration type
        if ($duration_type === 'multi_day') {
            // Multi-day: use date range
            $date_start = isset($_POST['event_date_start']) ? sanitize_text_field($_POST['event_date_start']) : '';
            $date_end = isset($_POST['event_date_end']) ? sanitize_text_field($_POST['event_date_end']) : '';
            
            // Store start date in regular event_date field for compatibility
            if (!empty($date_start)) {
                ensemble_update_field('event_date', $date_start, $event_id);
            }
            
            // Store end date in dedicated field
            if (!empty($date_end)) {
                update_post_meta($event_id, ES_Meta_Keys::EVENT_DATE_END, $date_end);
            } else {
                delete_post_meta($event_id, ES_Meta_Keys::EVENT_DATE_END);
            }
            
        } elseif ($duration_type === 'permanent') {
            // Permanent: optional start date, no end date
            $date_permanent = isset($_POST['event_date_permanent']) ? sanitize_text_field($_POST['event_date_permanent']) : '';
            
            if (!empty($date_permanent)) {
                ensemble_update_field('event_date', $date_permanent, $event_id);
            }
            
            // Clear end date for permanent events
            delete_post_meta($event_id, ES_Meta_Keys::EVENT_DATE_END);
            
        } else {
            // Single event: normal date handling (already done above via event_fields)
            // Clear end date if switching from multi_day to single
            delete_post_meta($event_id, ES_Meta_Keys::EVENT_DATE_END);
        }
        
        // Save has_children flag
        $has_children = isset($_POST['has_children']) && $_POST['has_children'] === '1';
        if ($has_children && in_array($duration_type, array('multi_day', 'permanent'))) {
            update_post_meta($event_id, ES_Meta_Keys::EVENT_HAS_CHILDREN, '1');
        } else {
            delete_post_meta($event_id, ES_Meta_Keys::EVENT_HAS_CHILDREN);
        }
        
        // Save parent event ID (for child events)
        $parent_event_id = isset($_POST['parent_event_id']) ? intval($_POST['parent_event_id']) : 0;
        if ($parent_event_id > 0) {
            update_post_meta($event_id, ES_Meta_Keys::EVENT_PARENT_ID, $parent_event_id);
        } else {
            delete_post_meta($event_id, ES_Meta_Keys::EVENT_PARENT_ID);
        }
        
        // Set featured image
        if (isset($_POST['featured_image_id']) && !empty($_POST['featured_image_id'])) {
            set_post_thumbnail($event_id, intval($_POST['featured_image_id']));
        }
        
        // Save gallery images
        if (isset($_POST['gallery_ids']) && !empty($_POST['gallery_ids'])) {
            $gallery_ids = array();
            if (is_array($_POST['gallery_ids'])) {
                $gallery_ids = array_map('intval', $_POST['gallery_ids']);
            } elseif (is_string($_POST['gallery_ids'])) {
                // Handle comma-separated string from hidden input
                $gallery_ids = array_map('intval', array_filter(explode(',', $_POST['gallery_ids'])));
            }
            update_post_meta($event_id, '_event_gallery', $gallery_ids);
        } else {
            // Clear gallery if empty
            delete_post_meta($event_id, '_event_gallery');
        }
        
        // Save hero video settings
        if (isset($_POST['hero_video_url']) && !empty($_POST['hero_video_url'])) {
            $video_url = esc_url_raw($_POST['hero_video_url']);
            update_post_meta($event_id, '_hero_video_url', $video_url);
            
            // Video options
            $autoplay = isset($_POST['hero_video_autoplay']) && $_POST['hero_video_autoplay'] === '1' ? '1' : '0';
            $loop = isset($_POST['hero_video_loop']) && $_POST['hero_video_loop'] === '1' ? '1' : '0';
            $controls = isset($_POST['hero_video_controls']) && $_POST['hero_video_controls'] === '1' ? '1' : '0';
            
            update_post_meta($event_id, '_hero_video_autoplay', $autoplay);
            update_post_meta($event_id, '_hero_video_loop', $loop);
            update_post_meta($event_id, '_hero_video_controls', $controls);
        } else {
            // Clear video settings if URL is empty
            delete_post_meta($event_id, '_hero_video_url');
            delete_post_meta($event_id, '_hero_video_autoplay');
            delete_post_meta($event_id, '_hero_video_loop');
            delete_post_meta($event_id, '_hero_video_controls');
        }
        
        // Save recurring rules
        if (isset($_POST['is_recurring']) && $_POST['is_recurring'] == '1' && isset($_POST['recurring_rules'])) {
            $recurring_engine = new ES_Recurring_Engine();
            $recurring_engine->save_rules($event_id, $_POST['recurring_rules']);
        } else {
            // Remove recurring rules if unchecked
            $recurring_engine = new ES_Recurring_Engine();
            $recurring_engine->remove_rules($event_id);
        }
        
        // Save reservation settings (from Reservations Pro addon)
        if (isset($_POST['reservation_enabled']) && $_POST['reservation_enabled'] === '1') {
            update_post_meta($event_id, '_reservation_enabled', '1');
            
            // Reservation types
            $types = isset($_POST['reservation_types']) ? array_map('sanitize_key', (array)$_POST['reservation_types']) : array('guestlist');
            update_post_meta($event_id, '_reservation_types', $types);
            
            // Capacity
            $capacity = isset($_POST['reservation_capacity']) && $_POST['reservation_capacity'] !== '' ? absint($_POST['reservation_capacity']) : '';
            update_post_meta($event_id, '_reservation_capacity', $capacity);
            
            // Deadline hours
            $deadline = isset($_POST['reservation_deadline_hours']) ? absint($_POST['reservation_deadline_hours']) : 24;
            update_post_meta($event_id, '_reservation_deadline_hours', $deadline);
            
            // Auto confirm
            $auto_confirm = isset($_POST['reservation_auto_confirm']) && $_POST['reservation_auto_confirm'] === '1' ? '1' : '0';
            update_post_meta($event_id, '_reservation_auto_confirm', $auto_confirm);
        } else {
            // If checkbox not set or value is '0', disable reservations
            update_post_meta($event_id, '_reservation_enabled', '0');
        }
        
        // Save tickets data (from Tickets addon)
        if (isset($_POST['tickets_data']) && !empty($_POST['tickets_data'])) {
            $tickets_raw = json_decode(stripslashes($_POST['tickets_data']), true);
            if (is_array($tickets_raw)) {
                $tickets = array();
                foreach ($tickets_raw as $ticket) {
                    // Skip global tickets - they shouldn't be saved to event meta
                    if (!empty($ticket['is_global'])) {
                        continue;
                    }
                    $tickets[] = array(
                        'id'           => sanitize_text_field($ticket['id'] ?? 'ticket_' . uniqid()),
                        'provider'     => sanitize_key($ticket['provider'] ?? 'custom'),
                        'name'         => sanitize_text_field($ticket['name'] ?? ''),
                        'url'          => esc_url_raw($ticket['url'] ?? ''),
                        'price'        => floatval($ticket['price'] ?? 0),
                        'price_max'    => floatval($ticket['price_max'] ?? 0),
                        'currency'     => sanitize_text_field($ticket['currency'] ?? 'EUR'),
                        'availability' => sanitize_key($ticket['availability'] ?? 'available'),
                        'custom_text'  => sanitize_text_field($ticket['custom_text'] ?? ''),
                    );
                }
                update_post_meta($event_id, '_ensemble_tickets', $tickets);
            }
        }
        
        // Save excluded global tickets
        if (isset($_POST['excluded_global_tickets'])) {
            $excluded_raw = json_decode(stripslashes($_POST['excluded_global_tickets']), true);
            if (is_array($excluded_raw)) {
                $excluded = array_map('sanitize_text_field', $excluded_raw);
                update_post_meta($event_id, '_ensemble_excluded_global_tickets', $excluded);
            }
        }
        
        // Save event contacts (from Staff addon)
        if (isset($_POST['event_contacts'])) {
            $contacts = array_map('absint', (array) $_POST['event_contacts']);
            $contacts = array_filter($contacts); // Remove zeros
            if (!empty($contacts)) {
                update_post_meta($event_id, '_es_event_contacts', $contacts);
            } else {
                delete_post_meta($event_id, '_es_event_contacts');
            }
        }
        
        // Save downloads data (from Downloads addon)
        if (isset($_POST['downloads_data'])) {
            $downloads_raw = json_decode(stripslashes($_POST['downloads_data']), true);
            if (is_array($downloads_raw) && class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')) {
                $downloads_addon = ES_Addon_Manager::get_active_addon('downloads');
                
                if ($downloads_addon) {
                    $download_manager = $downloads_addon->get_download_manager();
                    // Use centralized method for consistent handling
                    $download_manager->update_event_downloads($event_id, array_map('absint', $downloads_raw));
                }
            }
        }
        
        wp_send_json_success(array(
            'message'  => 'Event saved successfully!',
            'event_id' => $event_id,
        ));
    }
    
    /**
     * Delete event
     */
    public function delete_event() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Invalid event ID'));
            return;
        }
        
        $result = wp_delete_post($event_id, true);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Failed to delete event'));
            return;
        }
        
        wp_send_json_success(array('message' => 'Event deleted successfully!'));
    }
    
    /**
     * Copy event with all ACF fields
     */
    public function copy_event() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Invalid event ID'));
            return;
        }
        
        // Get original event
        $original_post = get_post($event_id);
        if (!$original_post) {
            wp_send_json_error(array('message' => 'Event not found'));
            return;
        }
        
        // Create new post with copied data
        $new_post_data = array(
            'post_title'   => $original_post->post_title . ' (Copy)',
            'post_content' => $original_post->post_content,
            'post_type'    => $original_post->post_type,
            'post_status'  => 'draft', // Start as draft
            'post_author'  => get_current_user_id(),
        );
        
        $new_event_id = wp_insert_post($new_post_data);
        
        if (is_wp_error($new_event_id)) {
            wp_send_json_error(array('message' => 'Failed to create copy'));
            return;
        }
        
        // Copy featured image
        $thumbnail_id = get_post_thumbnail_id($event_id);
        if ($thumbnail_id) {
            set_post_thumbnail($new_event_id, $thumbnail_id);
        }
        
        // Copy taxonomies
        $taxonomies = get_object_taxonomies($original_post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($event_id, $taxonomy, array('fields' => 'ids'));
            if (!is_wp_error($terms) && !empty($terms)) {
                wp_set_object_terms($new_event_id, $terms, $taxonomy);
            }
        }
        
        // Copy ALL meta fields (including ACF)
        global $wpdb;
        $meta_data = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
            $event_id
        ));
        
        foreach ($meta_data as $meta) {
            // Skip some WordPress internal meta fields
            if (in_array($meta->meta_key, array('_edit_lock', '_edit_last'))) {
                continue;
            }
            
            add_post_meta($new_event_id, $meta->meta_key, maybe_unserialize($meta->meta_value));
        }
        
        // If ACF is active, also use ACF functions to ensure proper field copying
        if (function_exists('get_fields')) {
            $fields = get_fields($event_id);
            if ($fields) {
                foreach ($fields as $field_name => $field_value) {
                    update_field($field_name, $field_value, $new_event_id);
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => 'Event copied successfully!',
            'event_id' => $new_event_id
        ));
    }
    
    /**
     * Get single event
     */
    public function get_event() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : '';
        
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Invalid event ID'));
            return;
        }
        
        // Check if it's a virtual event
        if (is_string($event_id) && strpos($event_id, 'virtual_') === 0) {
            $virtual_events = new ES_Virtual_Events();
            $event_obj = $virtual_events->get_event($event_id);
            
            if (!$event_obj) {
                wp_send_json_error(array('message' => 'Virtual event not found'));
                return;
            }
            
            // Convert to array format
            $event = array(
                'id' => $event_obj->ID,
                'title' => $event_obj->title,
                'date' => $event_obj->event_date,
                'time' => $event_obj->event_time,
                'description' => $event_obj->event_description,
                'location_id' => $event_obj->event_location,
                'artist_ids' => $event_obj->event_artist,
                'price' => $event_obj->event_price,
                'is_virtual' => true,
                'is_recurring' => true,
                'parent_id' => $event_obj->parent_id,
            );
            
            wp_send_json_success($event);
            return;
        }
        
        // Normal event
        $event_id = intval($event_id);
        $wizard = new ES_Wizard();
        $event = $wizard->get_event($event_id);
        
        if (!$event) {
            wp_send_json_error(array('message' => 'Event not found'));
            return;
        }
        
        // Add downloads data if Downloads addon is active
        if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')) {
            $downloads_addon = ES_Addon_Manager::get_active_addon('downloads');
            if ($downloads_addon) {
                $download_manager = $downloads_addon->get_download_manager();
                $event['downloads'] = $download_manager->get_downloads(array('event_id' => $event_id));
            }
        }
        
        wp_send_json_success($event);
    }
    
    /**
     * Get all events
     */
    public function get_events() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $wizard = new ES_Wizard();
        $events = $wizard->get_events();
        
        wp_send_json_success($events);
    }
    
    /**
     * Search events
     */
    public function search_events() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $exclude = isset($_POST['exclude']) ? intval($_POST['exclude']) : 0;
        $orphans_only = isset($_POST['orphans_only']) && $_POST['orphans_only'] === 'true';
        
        $args = array(
            's' => $search,
            'post_status' => array('publish', 'draft'),
        );
        
        // Exclude specific event
        if ($exclude) {
            $args['post__not_in'] = array($exclude);
        }
        
        // Only show events without a parent (orphans)
        if ($orphans_only) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => ES_Meta_Keys::EVENT_PARENT_ID,
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => ES_Meta_Keys::EVENT_PARENT_ID,
                    'value'   => '',
                    'compare' => '=',
                ),
                array(
                    'key'     => ES_Meta_Keys::EVENT_PARENT_ID,
                    'value'   => '0',
                    'compare' => '=',
                ),
            );
        }
        
        $wizard = new ES_Wizard();
        $events = $wizard->get_events($args);
        
        wp_send_json_success($events);
    }
    
    /**
     * Filter events with multiple criteria
     */
    public function filter_events() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        // Default: show all posts (published and drafts)
        $post_status = array('publish', 'draft');
        
        // If filtering by status
        $status_filter = !empty($filters['status']) ? sanitize_text_field($filters['status']) : '';
        
        if ($status_filter === 'draft') {
            $post_status = array('draft');
        } elseif ($status_filter) {
            // For publish, cancelled, postponed - only published posts
            $post_status = array('publish');
        }
        
        $args = array(
            'post_type'      => ensemble_get_post_type(),
            'posts_per_page' => -1,
            'post_status'    => $post_status,
        );
        
        // Check if we have any active filters (excluding status for has_filters check)
        $has_filters = !empty($filters['search']) || !empty($filters['category']) || 
                       !empty($filters['location']) || !empty($filters['date']) ||
                       !empty($filters['artist']);
        
        // Only apply category filtering for 'post' type when NO filters are active
        if (!$has_filters && ensemble_get_post_type() === 'post') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'operator' => 'EXISTS',
                ),
            );
        }
        
        // Ordering is now handled in PHP by the Wizard class
        // (more reliable with ACF field mapping)
        $sort_order = isset($filters['sort_order']) && $filters['sort_order'] === 'desc' ? 'DESC' : 'ASC';
        $args['orderby'] = 'date'; // Order by post date initially
        $args['order'] = $sort_order;
        // NOTE: meta_key filter removed - it excluded posts with mapped ACF fields
        
        // Search query
        if (!empty($filters['search'])) {
            $args['s'] = sanitize_text_field($filters['search']);
        }
        
        // Category filter
        if (!empty($filters['category'])) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = array();
            }
            $args['tax_query'][] = array(
                'taxonomy' => 'ensemble_category',
                'field'    => 'term_id',
                'terms'    => intval($filters['category']),
            );
        }
        
        // Get correct meta keys
        $location_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('location') : 'event_location';
        $date_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('date') : 'event_date';
        $artist_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('artist') : 'event_artist';
        
        // Location filter
        if (!empty($filters['location'])) {
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = array();
            }
            $args['meta_query'][] = array(
                'key'   => $location_key,
                'value' => intval($filters['location']),
                'compare' => '=',
            );
        }
        
        // Artist filter - handle serialized arrays
        if (!empty($filters['artist'])) {
            $artist_id = intval($filters['artist']);
            
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = array();
            }
            
            // ACF stores artists as serialized array, need LIKE queries
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => $artist_key,
                    'value' => 'i:' . $artist_id . ';',
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => $artist_key,
                    'value' => '"' . $artist_id . '"',
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => $artist_key,
                    'value' => $artist_id,
                    'compare' => '=',
                ),
            );
        }
        
        // Date filter
        if (!empty($filters['date'])) {
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = array();
            }
            
            $today = date('Y-m-d');
            
            switch ($filters['date']) {
                case 'upcoming':
                    $args['meta_query'][] = array(
                        'key'     => $date_key,
                        'value'   => $today,
                        'compare' => '>=',
                        'type'    => 'DATE',
                    );
                    break;
                    
                case 'past':
                    $args['meta_query'][] = array(
                        'key'     => $date_key,
                        'value'   => $today,
                        'compare' => '<',
                        'type'    => 'DATE',
                    );
                    $args['orderby'] = array('meta_value' => 'DESC');
                    break;
                    
                case 'today':
                    $args['meta_query'][] = array(
                        'key'     => $date_key,
                        'value'   => $today,
                        'compare' => '=',
                        'type'    => 'DATE',
                    );
                    break;
                    
                case 'this_week':
                    $week_start = date('Y-m-d', strtotime('monday this week'));
                    $week_end = date('Y-m-d', strtotime('sunday this week'));
                    $args['meta_query'][] = array(
                        'key'     => $date_key,
                        'value'   => array($week_start, $week_end),
                        'compare' => 'BETWEEN',
                        'type'    => 'DATE',
                    );
                    break;
                    
                case 'this_month':
                    $month_start = date('Y-m-01');
                    $month_end = date('Y-m-t');
                    $args['meta_query'][] = array(
                        'key'     => $date_key,
                        'value'   => array($month_start, $month_end),
                        'compare' => 'BETWEEN',
                        'type'    => 'DATE',
                    );
                    break;
            }
        }
        
        // Status filter (for cancelled, postponed, preview which are stored in meta)
        if (!empty($filters['status'])) {
            $status = sanitize_text_field($filters['status']);
            
            if (in_array($status, array('cancelled', 'postponed', 'preview', 'publish'))) {
                if (!isset($args['meta_query'])) {
                    $args['meta_query'] = array();
                }
                
                if ($status === 'publish') {
                    // Published = not cancelled, not postponed, not preview
                    $args['meta_query'][] = array(
                        'relation' => 'OR',
                        array(
                            'key'     => '_event_status',
                            'value'   => 'publish',
                            'compare' => '=',
                        ),
                        array(
                            'key'     => '_event_status',
                            'compare' => 'NOT EXISTS',
                        ),
                    );
                } else {
                    $args['meta_query'][] = array(
                        'key'   => '_event_status',
                        'value' => $status,
                        'compare' => '=',
                    );
                }
            }
        }
        
        // Ensure meta_query and tax_query have proper relation
        if (!empty($args['meta_query']) && count($args['meta_query']) > 1) {
            $args['meta_query']['relation'] = 'AND';
        }
        
        if (!empty($args['tax_query']) && count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }
        
        $wizard = new ES_Wizard();
        $events = $wizard->get_events($args);
        
        // Sort by event_date in PHP (more reliable than WP_Query with ACF field mapping)
        $sort_order = isset($filters['sort_order']) && $filters['sort_order'] === 'desc' ? 'desc' : 'asc';
        usort($events, function($a, $b) use ($sort_order) {
            $date_a = !empty($a['date']) ? strtotime($a['date']) : 0;
            $date_b = !empty($b['date']) ? strtotime($b['date']) : 0;
            
            if ($sort_order === 'desc') {
                return $date_b - $date_a;
            }
            return $date_a - $date_b;
        });
        
        wp_send_json_success($events);
    }
    
    /**
     * Get all artists
     */
    public function get_artists() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $manager = new ES_Artist_Manager();
        $artists = $manager->get_artists();
        
        wp_send_json_success($artists);
    }
    
    /**
     * Get single artist
     */
    public function get_artist() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $artist_id = isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0;
        
        if (!$artist_id) {
            wp_send_json_error(array('message' => 'Invalid artist ID'));
            return;
        }
        
        $manager = new ES_Artist_Manager();
        $artist = $manager->get_artist($artist_id);
        
        if (!$artist) {
            wp_send_json_error(array('message' => 'Artist not found'));
            return;
        }
        
        wp_send_json_success($artist);
    }
    
    /**
     * Save artist
     */
    public function save_artist() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        // Get social_links array from POST
        $social_links = array();
        if (isset($_POST['social_links']) && is_array($_POST['social_links'])) {
            $social_links = array_map('esc_url_raw', $_POST['social_links']);
        }
        
        // Get gallery_ids array from POST
        $gallery_ids = array();
        if (isset($_POST['gallery_ids'])) {
            if (is_array($_POST['gallery_ids'])) {
                $gallery_ids = array_map('intval', $_POST['gallery_ids']);
            } elseif (is_string($_POST['gallery_ids']) && !empty($_POST['gallery_ids'])) {
                // Handle comma-separated string
                $gallery_ids = array_map('intval', explode(',', $_POST['gallery_ids']));
            }
        }
        
        $data = array(
            'artist_id'             => isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0,
            'name'                  => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'description'           => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'references'            => isset($_POST['references']) ? sanitize_text_field($_POST['references']) : '',
            'genre'                 => isset($_POST['genre']) ? $_POST['genre'] : array(),
            'artist_type'           => isset($_POST['artist_type']) ? $_POST['artist_type'] : array(),
            'email'                 => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'website'               => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
            'social_links'          => $social_links,
            'youtube'               => isset($_POST['youtube']) ? esc_url_raw($_POST['youtube']) : '',
            'vimeo'                 => isset($_POST['vimeo']) ? esc_url_raw($_POST['vimeo']) : '',
            'gallery_ids'           => $gallery_ids,
            'featured_image_id'     => isset($_POST['featured_image_id']) ? intval($_POST['featured_image_id']) : 0,
            // Hero video fields
            'hero_video_url'        => isset($_POST['hero_video_url']) ? esc_url_raw($_POST['hero_video_url']) : '',
            'hero_video_autoplay'   => isset($_POST['hero_video_autoplay']) && $_POST['hero_video_autoplay'],
            'hero_video_loop'       => isset($_POST['hero_video_loop']) && $_POST['hero_video_loop'],
            'hero_video_controls'   => isset($_POST['hero_video_controls']) && $_POST['hero_video_controls'],
            // Single layout preference
            'single_layout'         => isset($_POST['single_layout']) ? sanitize_text_field($_POST['single_layout']) : 'default',
            // Professional info fields (NEW)
            'position'              => isset($_POST['position']) ? sanitize_text_field($_POST['position']) : '',
            'company'               => isset($_POST['company']) ? sanitize_text_field($_POST['company']) : '',
            'additional'            => isset($_POST['additional']) ? sanitize_textarea_field($_POST['additional']) : '',
        );
        
        $manager = new ES_Artist_Manager();
        $result = $manager->save_artist($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'message'   => 'Artist saved successfully!',
            'artist_id' => $result,
        ));
    }
    
    /**
     * Delete artist
     */
    public function delete_artist() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $artist_id = isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0;
        
        if (!$artist_id) {
            wp_send_json_error(array('message' => 'Invalid artist ID'));
            return;
        }
        
        $manager = new ES_Artist_Manager();
        $result = $manager->delete_artist($artist_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Failed to delete artist'));
            return;
        }
        
        wp_send_json_success(array('message' => 'Artist deleted successfully!'));
    }
    
    /**
     * Search artists
     */
    public function search_artists() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $manager = new ES_Artist_Manager();
        $artists = $manager->search_artists($search);
        
        wp_send_json_success($artists);
    }
    
    /**
     * Bulk delete artists
     */
    public function bulk_delete_artists() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $artist_ids = isset($_POST['artist_ids']) ? array_map('intval', (array) $_POST['artist_ids']) : array();
        
        if (empty($artist_ids)) {
            wp_send_json_error(array('message' => 'No artists selected'));
            return;
        }
        
        $manager = new ES_Artist_Manager();
        $result = $manager->bulk_delete($artist_ids);
        
        wp_send_json_success($result);
    }
    
    /**
     * Bulk assign taxonomy to artists
     */
    public function bulk_assign_artist_taxonomy() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $artist_ids = isset($_POST['artist_ids']) ? array_map('intval', (array) $_POST['artist_ids']) : array();
        $taxonomy_type = isset($_POST['taxonomy_type']) ? sanitize_text_field($_POST['taxonomy_type']) : '';
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        
        if (empty($artist_ids)) {
            wp_send_json_error(array('message' => 'No artists selected'));
            return;
        }
        
        if (empty($taxonomy_type) || !$term_id) {
            wp_send_json_error(array('message' => 'Invalid taxonomy or term'));
            return;
        }
        
        // Determine taxonomy name
        $taxonomy = '';
        if ($taxonomy_type === 'genre') {
            $taxonomy = 'ensemble_genre';
        } elseif ($taxonomy_type === 'type') {
            $taxonomy = 'ensemble_artist_type';
        } else {
            wp_send_json_error(array('message' => 'Invalid taxonomy type'));
            return;
        }
        
        // Verify term exists
        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            wp_send_json_error(array('message' => 'Invalid term'));
            return;
        }
        
        $success_count = 0;
        foreach ($artist_ids as $artist_id) {
            $result = wp_set_object_terms($artist_id, array($term_id), $taxonomy, false);
            if (!is_wp_error($result)) {
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Assigned %s to %d artist(s).', 'ensemble'), $term->name, $success_count),
            'success_count' => $success_count
        ));
    }
    
    /**
     * Bulk remove taxonomy from artists
     */
    public function bulk_remove_artist_taxonomy() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $artist_ids = isset($_POST['artist_ids']) ? array_map('intval', (array) $_POST['artist_ids']) : array();
        $taxonomy_type = isset($_POST['taxonomy_type']) ? sanitize_text_field($_POST['taxonomy_type']) : '';
        
        if (empty($artist_ids)) {
            wp_send_json_error(array('message' => 'No artists selected'));
            return;
        }
        
        // Determine taxonomy name
        $taxonomy = '';
        if ($taxonomy_type === 'genre') {
            $taxonomy = 'ensemble_genre';
        } elseif ($taxonomy_type === 'type') {
            $taxonomy = 'ensemble_artist_type';
        } else {
            wp_send_json_error(array('message' => 'Invalid taxonomy type'));
            return;
        }
        
        $success_count = 0;
        foreach ($artist_ids as $artist_id) {
            $result = wp_set_object_terms($artist_id, array(), $taxonomy, false);
            if (!is_wp_error($result)) {
                $success_count++;
            }
        }
        
        $type_label = ($taxonomy_type === 'genre') ? __('genre', 'ensemble') : __('type', 'ensemble');
        
        wp_send_json_success(array(
            'message' => sprintf(__('Removed %s from %d artist(s).', 'ensemble'), $type_label, $success_count),
            'success_count' => $success_count
        ));
    }
    
    /**
     * Get all locations
     */
    public function get_locations() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $manager = new ES_Location_Manager();
        $locations = $manager->get_locations();
        
        wp_send_json_success($locations);
    }
    
    /**
     * Get single location
     */
    public function get_location() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        
        if (!$location_id) {
            wp_send_json_error(array('message' => 'Invalid location ID'));
            return;
        }
        
        $manager = new ES_Location_Manager();
        $location = $manager->get_location($location_id);
        
        if (!$location) {
            wp_send_json_error(array('message' => 'Location not found'));
            return;
        }
        
        // Add location contacts (from Staff addon)
        $contacts = get_post_meta($location_id, '_es_location_contacts', true);
        $location['contacts'] = is_array($contacts) ? $contacts : array();
        
        wp_send_json_success($location);
    }
    
    /**
     * Save location
     */
    public function save_location() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $data = array(
            'location_id'        => isset($_POST['location_id']) ? intval($_POST['location_id']) : 0,
            'name'               => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'description'        => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'location_type'      => isset($_POST['location_type']) ? $_POST['location_type'] : array(),
            'address'            => isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '',
            'city'               => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
            'capacity'           => isset($_POST['capacity']) ? intval($_POST['capacity']) : 0,
            'website'            => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
            'featured_image_id'  => isset($_POST['featured_image_id']) ? intval($_POST['featured_image_id']) : 0,
            // Social & Media
            'social_links'       => isset($_POST['social_links']) ? $_POST['social_links'] : array(),
            'youtube'            => isset($_POST['youtube']) ? esc_url_raw($_POST['youtube']) : '',
            'vimeo'              => isset($_POST['vimeo']) ? esc_url_raw($_POST['vimeo']) : '',
            'gallery_ids'        => isset($_POST['gallery_ids']) ? $_POST['gallery_ids'] : array(),
            // Maps Add-on fields
            'zip_code'           => isset($_POST['zip_code']) ? sanitize_text_field($_POST['zip_code']) : '',
            'additional_info'    => isset($_POST['additional_info']) ? sanitize_textarea_field($_POST['additional_info']) : '',
            'latitude'           => isset($_POST['latitude']) ? floatval($_POST['latitude']) : '',
            'longitude'          => isset($_POST['longitude']) ? floatval($_POST['longitude']) : '',
            'show_map'           => isset($_POST['show_map']) ? intval($_POST['show_map']) : 1,
            'map_type'           => isset($_POST['map_type']) ? sanitize_text_field($_POST['map_type']) : 'embedded',
            // Multivenue fields
            'is_multivenue'      => isset($_POST['is_multivenue']) ? intval($_POST['is_multivenue']) : 0,
            'venues'             => array(), // Wird unten gefüllt
            // Opening Hours fields
            'has_opening_hours'    => isset($_POST['has_opening_hours']) ? intval($_POST['has_opening_hours']) : 0,
            'opening_hours'        => array(), // Wird unten gefüllt
            'opening_hours_note'   => isset($_POST['opening_hours_note']) ? sanitize_text_field($_POST['opening_hours_note']) : '',
        );
        
        // Parse venues JSON
        if (isset($_POST['venues']) && !empty($_POST['venues'])) {
            $venues_json = stripslashes($_POST['venues']);
            $venues_decoded = json_decode($venues_json, true);
            if (is_array($venues_decoded)) {
                $data['venues'] = $venues_decoded;
            }
        }
        
        // Parse opening_hours JSON
        if (isset($_POST['opening_hours']) && !empty($_POST['opening_hours'])) {
            $hours_json = stripslashes($_POST['opening_hours']);
            $hours_decoded = json_decode($hours_json, true);
            if (is_array($hours_decoded)) {
                $data['opening_hours'] = $hours_decoded;
            }
        }
        
        $manager = new ES_Location_Manager();
        $result = $manager->save_location($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Save location contacts (from Staff addon)
        $location_id = $result;
        if (isset($_POST['location_contacts'])) {
            $contacts = array_map('absint', (array) $_POST['location_contacts']);
            $contacts = array_filter($contacts); // Remove zeros
            if (!empty($contacts)) {
                update_post_meta($location_id, '_es_location_contacts', $contacts);
            } else {
                delete_post_meta($location_id, '_es_location_contacts');
            }
        }
        
        wp_send_json_success(array(
            'message'     => 'Location saved successfully!',
            'location_id' => $result,
        ));
    }
    
    /**
     * Delete location
     */
    public function delete_location() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        
        if (!$location_id) {
            wp_send_json_error(array('message' => 'Invalid location ID'));
            return;
        }
        
        $manager = new ES_Location_Manager();
        $result = $manager->delete_location($location_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Failed to delete location'));
            return;
        }
        
        wp_send_json_success(array('message' => 'Location deleted successfully!'));
    }
    
    /**
     * Search locations
     */
    public function search_locations() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $manager = new ES_Location_Manager();
        $locations = $manager->search_locations($search);
        
        wp_send_json_success($locations);
    }
    
    /**
     * Bulk delete locations
     */
    public function bulk_delete_locations() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $location_ids = isset($_POST['location_ids']) ? array_map('intval', (array) $_POST['location_ids']) : array();
        
        if (empty($location_ids)) {
            wp_send_json_error(array('message' => 'No locations selected'));
            return;
        }
        
        $manager = new ES_Location_Manager();
        $result = $manager->bulk_delete($location_ids);
        
        wp_send_json_success($result);
    }
    
    // =========================================
    // GALLERY AJAX HANDLERS
    // =========================================
    
    /**
     * Get all galleries
     */
    public function get_galleries() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $manager = new ES_Gallery_Manager();
        $galleries = $manager->get_galleries();
        
        wp_send_json_success($galleries);
    }
    
    /**
     * Get single gallery
     */
    public function get_gallery() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $gallery_id = isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0;
        
        if (!$gallery_id) {
            wp_send_json_error(array('message' => 'Invalid gallery ID'));
            return;
        }
        
        $manager = new ES_Gallery_Manager();
        $gallery = $manager->get_gallery($gallery_id);
        
        if (!$gallery) {
            wp_send_json_error(array('message' => 'Gallery not found'));
            return;
        }
        
        wp_send_json_success($gallery);
    }
    
    /**
     * Save gallery
     */
    public function save_gallery() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        // Parse image_ids if it's an array
        $image_ids = array();
        if (isset($_POST['image_ids'])) {
            if (is_array($_POST['image_ids'])) {
                $image_ids = array_map('intval', $_POST['image_ids']);
            } elseif (is_string($_POST['image_ids']) && !empty($_POST['image_ids'])) {
                $image_ids = array_map('intval', explode(',', $_POST['image_ids']));
            }
        }
        
        $data = array(
            'gallery_id'        => isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0,
            'title'             => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'description'       => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'categories'        => isset($_POST['categories']) ? array_map('intval', (array) $_POST['categories']) : array(),
            'image_ids'         => $image_ids,
            'linked_event'      => isset($_POST['linked_event']) ? intval($_POST['linked_event']) : 0,
            'linked_artist'     => isset($_POST['linked_artist']) ? intval($_POST['linked_artist']) : 0,
            'linked_location'   => isset($_POST['linked_location']) ? intval($_POST['linked_location']) : 0,
            'layout'            => isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'grid',
            'columns'           => isset($_POST['columns']) ? absint($_POST['columns']) : 3,
            'lightbox'          => isset($_POST['lightbox']) && $_POST['lightbox'],
            'featured_image_id' => isset($_POST['featured_image_id']) ? intval($_POST['featured_image_id']) : 0,
        );
        
        $manager = new ES_Gallery_Manager();
        $result = $manager->save_gallery($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'message'    => __('Gallery saved successfully!', 'ensemble'),
            'gallery_id' => $result,
        ));
    }
    
    /**
     * Delete gallery
     */
    public function delete_gallery() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $gallery_id = isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0;
        
        if (!$gallery_id) {
            wp_send_json_error(array('message' => 'Invalid gallery ID'));
            return;
        }
        
        $manager = new ES_Gallery_Manager();
        $result = $manager->delete_gallery($gallery_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Failed to delete gallery'));
            return;
        }
        
        wp_send_json_success(array('message' => __('Gallery deleted successfully!', 'ensemble')));
    }
    
    /**
     * Search galleries
     */
    public function search_galleries() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $manager = new ES_Gallery_Manager();
        $galleries = $manager->search_galleries($search);
        
        wp_send_json_success($galleries);
    }
    
    /**
     * Bulk delete galleries
     */
    public function bulk_delete_galleries() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $gallery_ids = isset($_POST['gallery_ids']) ? array_map('intval', (array) $_POST['gallery_ids']) : array();
        
        if (empty($gallery_ids)) {
            wp_send_json_error(array('message' => 'No galleries selected'));
            return;
        }
        
        $manager = new ES_Gallery_Manager();
        $result = $manager->bulk_delete($gallery_ids);
        
        wp_send_json_success($result);
    }
    
    /**
     * Get custom wizard steps for a category
     */
    public function get_custom_wizard_steps() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        // Support multiple categories
        $category_ids = isset($_POST['category_ids']) ? array_map('intval', (array) $_POST['category_ids']) : array();
        
        // Backward compatibility: single category_id
        if (empty($category_ids) && isset($_POST['category_id'])) {
            $category_ids = array(intval($_POST['category_id']));
        }
        
        if (empty($category_ids)) {
            wp_send_json_success(array('steps' => array()));
            return;
        }
        
        // Get wizard configuration
        $wizard_config = get_option('ensemble_wizard_config', array());
        
        // Collect all unique field groups from all selected categories
        $all_field_groups = array();
        $field_group_order = array(); // Track which category contributed each group
        
        foreach ($category_ids as $category_id) {
            if (!isset($wizard_config[$category_id]) || empty($wizard_config[$category_id]['field_groups'])) {
                continue;
            }
            
            $field_groups = $wizard_config[$category_id]['field_groups'];
            foreach ($field_groups as $index => $group_key) {
                if (!in_array($group_key, $all_field_groups)) {
                    $all_field_groups[] = $group_key;
                    $field_group_order[$group_key] = array(
                        'category_id' => $category_id,
                        'order' => $index
                    );
                }
            }
        }
        
        if (empty($all_field_groups)) {
            wp_send_json_success(array('steps' => array()));
            return;
        }
        
        $steps = array();
        
        // Load each unique field group
        foreach ($all_field_groups as $group_key) {
            if (!function_exists('acf_get_field_group')) {
                continue;
            }
            
            $group = acf_get_field_group($group_key);
            if (!$group) {
                continue;
            }
            
            // Get fields for this group
            $fields = acf_get_fields($group_key);
            if (!$fields) {
                continue;
            }
            
            // Render fields as HTML
            $fields_html = '';
            foreach ($fields as $field) {
                $fields_html .= $this->render_acf_field($field);
            }
            
            $steps[] = array(
                'key' => $group_key,
                'title' => $group['title'],
                'fields_html' => $fields_html,
            );
        }
        
        wp_send_json_success(array('steps' => $steps));
    }
    
    /**
     * Render ACF field as HTML for wizard
     */
    private function render_acf_field($field, $value = '') {
        // Skip structural fields
        $structural_types = array('tab', 'message', 'accordion');
        if (in_array($field['type'], $structural_types)) {
            return '';
        }
        
        $required = !empty($field['required']) ? 'required' : '';
        $field_name = 'acf[' . $field['key'] . ']';
        
        $html = '<div class="es-form-row" data-field-key="' . esc_attr($field['key']) . '">';
        
        if (!empty($field['label'])) {
            $html .= '<label for="' . esc_attr($field['key']) . '">';
            $html .= esc_html($field['label']);
            if ($required) {
                $html .= ' <span style="color: red;">*</span>';
            }
            $html .= '</label>';
        }
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'url':
            case 'number':
                $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
                $html .= sprintf(
                    '<input type="%s" id="%s" name="%s" value="%s" placeholder="%s" %s>',
                    esc_attr($field['type']),
                    esc_attr($field['key']),
                    esc_attr($field_name),
                    esc_attr($value),
                    esc_attr($placeholder),
                    $required
                );
                break;
                
            case 'textarea':
                $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
                $rows = !empty($field['rows']) ? intval($field['rows']) : 4;
                $html .= sprintf(
                    '<textarea id="%s" name="%s" placeholder="%s" rows="%d" %s>%s</textarea>',
                    esc_attr($field['key']),
                    esc_attr($field_name),
                    esc_attr($placeholder),
                    $rows,
                    $required,
                    esc_textarea($value)
                );
                break;
                
            case 'select':
                $html .= sprintf(
                    '<select id="%s" name="%s" %s>',
                    esc_attr($field['key']),
                    esc_attr($field_name),
                    $required
                );
                
                if (!empty($field['allow_null'])) {
                    $html .= '<option value="">- Select -</option>';
                }
                
                if (!empty($field['choices'])) {
                    foreach ($field['choices'] as $choice_value => $choice_label) {
                        $selected = ($value == $choice_value) ? 'selected' : '';
                        $html .= sprintf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($choice_value),
                            $selected,
                            esc_html($choice_label)
                        );
                    }
                }
                
                $html .= '</select>';
                break;
                
            case 'true_false':
                $checked = $value ? 'checked' : '';
                $html .= sprintf(
                    '<label><input type="checkbox" id="%s" name="%s" value="1" %s> %s</label>',
                    esc_attr($field['key']),
                    esc_attr($field_name),
                    $checked,
                    esc_html($field['message'] ?? 'Yes')
                );
                break;
                
            case 'date_picker':
                $html .= sprintf(
                    '<input type="date" id="%s" name="%s" value="%s" %s>',
                    esc_attr($field['key']),
                    esc_attr($field_name),
                    esc_attr($value),
                    $required
                );
                break;
                
            case 'time_picker':
                $html .= sprintf(
                    '<input type="time" id="%s" name="%s" value="%s" %s>',
                    esc_attr($field['key']),
                    esc_attr($field_name),
                    esc_attr($value),
                    $required
                );
                break;
                
            default:
                // For unsupported field types, add a text input as fallback
                $html .= sprintf(
                    '<input type="text" id="%s" name="%s" value="%s" placeholder="Field type: %s" %s>',
                    esc_attr($field['key']),
                    esc_attr($field_name),
                    esc_attr($value),
                    esc_attr($field['type']),
                    $required
                );
                break;
        }
        
        if (!empty($field['instructions'])) {
            $html .= '<p class="description">' . esc_html($field['instructions']) . '</p>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Sanitize ACF value based on field type
     */
    private function sanitize_acf_value($field_key, $value) {
        if (!function_exists('acf_get_field')) {
            return sanitize_text_field($value);
        }
        
        $field = acf_get_field($field_key);
        if (!$field) {
            return sanitize_text_field($value);
        }
        
        switch ($field['type']) {
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'number':
                return floatval($value);
            case 'true_false':
                return $value === '1' || $value === 1 || $value === true ? 1 : 0;
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Get field group fields for editing
     */
    public function get_field_group_fields() {
        check_ajax_referer('es_get_fields', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $group_key = isset($_POST['group_key']) ? sanitize_text_field($_POST['group_key']) : '';
        
        if (empty($group_key)) {
            wp_send_json_error(array('message' => 'Group key required'));
            return;
        }
        
        if (!function_exists('acf_get_fields')) {
            wp_send_json_error(array('message' => 'ACF not available'));
            return;
        }
        
        $fields = acf_get_fields($group_key);
        
        if (!$fields) {
            wp_send_json_success(array('fields' => array()));
            return;
        }
        
        // Simplify field data for editing
        $simplified_fields = array();
        foreach ($fields as $field) {
            $simplified_fields[] = array(
                'key' => $field['key'],
                'label' => $field['label'],
                'name' => $field['name'],
                'type' => $field['type'],
            );
        }
        
        wp_send_json_success(array('fields' => $simplified_fields));
    }
    
    /**
     * Autosave event (draft)
     */
    public function autosave_event() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        
        if (empty($title) || strlen($title) < 3) {
            wp_send_json_error(array('message' => 'Title too short for autosave'));
            return;
        }
        
        // Remove any existing "(Entwurf)" suffix that might have been added before
        $title = preg_replace('/\s*\(Entwurf\)\s*$/i', '', $title);
        
        // Check if this is an existing published event
        $is_new_event = empty($event_id);
        $current_status = 'draft';
        
        if (!$is_new_event) {
            $existing_post = get_post($event_id);
            if ($existing_post) {
                // Keep the current status - don't change published to draft!
                $current_status = $existing_post->post_status;
            }
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'  => $title,
            'post_type'   => ensemble_get_post_type(),
            'post_status' => $current_status, // Keep existing status, only new events get 'draft'
        );
        
        // Update or create
        if ($event_id) {
            $post_data['ID'] = $event_id;
            $result = wp_update_post($post_data);
        } else {
            $post_data['post_status'] = 'draft'; // New events are always drafts
            $post_data['post_name'] = sanitize_title($title) . '-draft';
            $result = wp_insert_post($post_data);
            $event_id = $result;
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Save basic meta
        if (isset($_POST['event_date'])) {
            update_post_meta($event_id, '_event_date', sanitize_text_field($_POST['event_date']));
        }
        if (isset($_POST['event_description'])) {
            update_post_meta($event_id, '_event_description', sanitize_textarea_field($_POST['event_description']));
        }
        
        // Mark as autosaved
        update_post_meta($event_id, '_es_autosaved', current_time('mysql'));
        
        wp_send_json_success(array(
            'event_id' => $event_id,
            'message'  => 'Autosave successful'
        ));
    }
    
    /**
     * Check for location conflicts
     */
    public function check_location_conflict() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $time_start = isset($_POST['time_start']) ? sanitize_text_field($_POST['time_start']) : '';
        $time_end = isset($_POST['time_end']) ? sanitize_text_field($_POST['time_end']) : '';
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        $exclude_event_id = isset($_POST['exclude_event_id']) ? intval($_POST['exclude_event_id']) : 0;
        
        if (empty($date) || empty($location_id)) {
            wp_send_json_success(array('has_conflict' => false));
            return;
        }
        
        // Query events on same date at same location
        $args = array(
            'post_type'      => ensemble_get_post_type(),
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'   => '_event_date',
                    'value' => $date,
                ),
                array(
                    'key'   => '_event_location',
                    'value' => $location_id,
                ),
            ),
        );
        
        // Exclude current event
        if ($exclude_event_id) {
            $args['post__not_in'] = array($exclude_event_id);
        }
        
        $events = get_posts($args);
        
        if (empty($events)) {
            wp_send_json_success(array('has_conflict' => false));
            return;
        }
        
        // Check for time overlap if times are provided
        $conflicts = array();
        
        foreach ($events as $event) {
            $event_time_start = get_post_meta($event->ID, '_event_time_start', true);
            $event_time_end = get_post_meta($event->ID, '_event_time_end', true);
            
            // If no times, assume full day conflict
            if (empty($time_start) || empty($event_time_start)) {
                $conflicts[] = array(
                    'id'    => $event->ID,
                    'title' => $event->post_title,
                    'time'  => $event_time_start ? $event_time_start . ' - ' . $event_time_end : 'All Day',
                );
                continue;
            }
            
            // Check time overlap
            $new_start = strtotime($time_start);
            $new_end = $time_end ? strtotime($time_end) : $new_start + 7200; // Default 2 hours
            $existing_start = strtotime($event_time_start);
            $existing_end = $event_time_end ? strtotime($event_time_end) : $existing_start + 7200;
            
            // Times overlap if one starts before the other ends
            if ($new_start < $existing_end && $new_end > $existing_start) {
                $conflicts[] = array(
                    'id'    => $event->ID,
                    'title' => $event->post_title,
                    'time'  => $event_time_start . ' - ' . $event_time_end,
                );
            }
        }
        
        if (empty($conflicts)) {
            wp_send_json_success(array('has_conflict' => false));
        } else {
            wp_send_json_success(array(
                'has_conflict' => true,
                'conflicts'    => $conflicts,
            ));
        }
    }
    
    /**
     * Bulk event actions
     */
    public function bulk_event_action() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $event_ids = isset($_POST['event_ids']) ? array_map('intval', $_POST['event_ids']) : array();
        
        if (empty($action) || empty($event_ids)) {
            wp_send_json_error(array('message' => 'No action or events selected'));
            return;
        }
        
        $results = array(
            'success' => 0,
            'failed'  => 0,
        );
        
        foreach ($event_ids as $event_id) {
            switch ($action) {
                case 'delete':
                    if (wp_delete_post($event_id, true)) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                    break;
                    
                case 'trash':
                    if (wp_trash_post($event_id)) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                    break;
                    
                case 'publish':
                    if (wp_update_post(array('ID' => $event_id, 'post_status' => 'publish'))) {
                        update_post_meta($event_id, '_event_status', 'publish');
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                    break;
                    
                case 'draft':
                    if (wp_update_post(array('ID' => $event_id, 'post_status' => 'draft'))) {
                        update_post_meta($event_id, '_event_status', 'draft');
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                    break;
                    
                case 'cancel':
                    update_post_meta($event_id, '_event_status', 'cancelled');
                    $results['success']++;
                    break;
                    
                case 'postpone':
                    update_post_meta($event_id, '_event_status', 'postponed');
                    $results['success']++;
                    break;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                '%d Events erfolgreich bearbeitet, %d fehlgeschlagen.',
                $results['success'],
                $results['failed']
            ),
            'results' => $results,
        ));
    }
    
    /**
     * Get child events for a parent event
     * 
     * @since 2.9.6
     */
    public function get_child_events() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        
        if (!$parent_id) {
            wp_send_json_error(array('message' => 'Parent ID required'));
            return;
        }
        
        // Get child events
        $children = get_posts(array(
            'post_type'      => ensemble_get_post_type(),
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => ES_Meta_Keys::EVENT_PARENT_ID,
                    'value'   => $parent_id,
                    'compare' => '=',
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => ES_Meta_Keys::get('date'),
            'order'          => 'ASC',
        ));
        
        $result = array();
        foreach ($children as $child) {
            $result[] = array(
                'id'    => $child->ID,
                'title' => $child->post_title,
                'date'  => ensemble_get_event_meta($child->ID, 'date'),
                'time'  => ensemble_get_event_meta($child->ID, 'time'),
            );
        }
        
        wp_send_json_success(array(
            'children' => $result,
            'count'    => count($result),
        ));
    }
    
    /**
     * Unlink a child event from its parent
     * 
     * @since 2.9.6
     */
    public function unlink_child_event() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
        
        if (!$child_id) {
            wp_send_json_error(array('message' => 'Child ID required'));
            return;
        }
        
        // Remove parent reference
        delete_post_meta($child_id, ES_Meta_Keys::EVENT_PARENT_ID);
        
        // Update parent's child count if needed
        // (This is optional, the count is calculated dynamically)
        
        wp_send_json_success(array(
            'message' => __('Event unlinked from parent', 'ensemble'),
        ));
    }
    
    /**
     * Link an existing event as child to a parent event
     * 
     * @since 2.9.7
     */
    public function link_child_event() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        
        if (!$child_id || !$parent_id) {
            wp_send_json_error(array('message' => __('Both child and parent ID required', 'ensemble')));
            return;
        }
        
        // Verify both posts exist
        $child = get_post($child_id);
        $parent = get_post($parent_id);
        
        if (!$child || !$parent) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'ensemble')));
            return;
        }
        
        // Check if child already has a parent
        $existing_parent = get_post_meta($child_id, ES_Meta_Keys::EVENT_PARENT_ID, true);
        if ($existing_parent && $existing_parent != $parent_id) {
            wp_send_json_error(array('message' => __('This event is already linked to another parent', 'ensemble')));
            return;
        }
        
        // Prevent circular reference
        if ($child_id === $parent_id) {
            wp_send_json_error(array('message' => __('An event cannot be its own parent', 'ensemble')));
            return;
        }
        
        // Set parent reference on child
        update_post_meta($child_id, ES_Meta_Keys::EVENT_PARENT_ID, $parent_id);
        
        // Ensure parent has has_children flag
        update_post_meta($parent_id, ES_Meta_Keys::EVENT_HAS_CHILDREN, '1');
        
        wp_send_json_success(array(
            'message' => sprintf(__('"%s" linked to parent event', 'ensemble'), $child->post_title),
            'child_id' => $child_id,
            'parent_id' => $parent_id,
        ));
    }
    
    /**
     * Get all events that can be linked as children
     * Returns events that have no parent and are not parent events themselves
     * 
     * @since 2.9.7
     */
    public function get_linkable_events() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $exclude = isset($_POST['exclude']) ? intval($_POST['exclude']) : 0;
        
        // Get all events that:
        // 1. Have no parent (not already a child)
        // 2. Are not parent events (don't have has_children flag)
        // 3. Are single events (not multi_day or permanent)
        $args = array(
            'post_type'      => ensemble_get_post_type(),
            'posts_per_page' => 100, // Reasonable limit
            'post_status'    => array('publish', 'draft'),
            'meta_query'     => array(
                'relation' => 'AND',
                // Not a child event
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => ES_Meta_Keys::EVENT_PARENT_ID,
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => ES_Meta_Keys::EVENT_PARENT_ID,
                        'value'   => '',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => ES_Meta_Keys::EVENT_PARENT_ID,
                        'value'   => '0',
                        'compare' => '=',
                    ),
                ),
                // Not a parent event
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => ES_Meta_Keys::EVENT_HAS_CHILDREN,
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => ES_Meta_Keys::EVENT_HAS_CHILDREN,
                        'value'   => '1',
                        'compare' => '!=',
                    ),
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => ES_Meta_Keys::get('date'),
            'order'          => 'DESC',
        );
        
        // Exclude specific event
        if ($exclude) {
            $args['post__not_in'] = array($exclude);
        }
        
        // For 'post' post type, filter by ensemble_category
        if (ensemble_get_post_type() === 'post') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'operator' => 'EXISTS',
                ),
            );
        }
        
        $posts = get_posts($args);
        
        $events = array();
        foreach ($posts as $post) {
            $events[] = array(
                'id'    => $post->ID,
                'title' => $post->post_title,
                'date'  => ensemble_get_event_meta($post->ID, 'date'),
                'time'  => ensemble_get_event_meta($post->ID, 'time'),
            );
        }
        
        wp_send_json_success(array(
            'events' => $events,
            'count'  => count($events),
        ));
    }
}

