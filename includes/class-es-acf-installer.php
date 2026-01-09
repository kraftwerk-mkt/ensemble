<?php
/**
 * ACF Field Installer
 * 
 * Automatically installs ACF field groups if ACF is available
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_ACF_Installer {
    
    /**
     * Check if ACF is installed and install fields if needed
     */
    public function check_and_install() {
        // Check if ACF is available
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        // Check if already installed
        if (get_option('ensemble_acf_installed')) {
            return;
        }
        
        // Install field groups
        $this->install_event_fields();
        $this->install_artist_fields();
        $this->install_location_fields();
        $this->install_test_field_groups(); // TEST Field Groups
        
        // Mark as installed
        update_option('ensemble_acf_installed', true);
    }
    
    /**
     * Install Event field group
     */
    private function install_event_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_ensemble_event',
            'title' => 'Event Details',
            'fields' => array(
                array(
                    'key' => 'field_event_date',
                    'label' => 'Event Date',
                    'name' => 'event_date',
                    'type' => 'date_picker',
                    'required' => 1,
                    'display_format' => 'd/m/Y',
                    'return_format' => 'Y-m-d',
                ),
                array(
                    'key' => 'field_event_time',
                    'label' => 'Event Time',
                    'name' => 'event_time',
                    'type' => 'time_picker',
                    'display_format' => 'H:i',
                    'return_format' => 'H:i',
                ),
                array(
                    'key' => 'field_event_location',
                    'label' => 'Location',
                    'name' => 'event_location',
                    'type' => 'post_object',
                    'post_type' => array('ensemble_location'),
                    'return_format' => 'id',
                    'allow_null' => 1,
                ),
                array(
                    'key' => 'field_event_artist',
                    'label' => 'Artist/Performer',
                    'name' => 'event_artist',
                    'type' => 'post_object',
                    'post_type' => array('ensemble_artist'),
                    'return_format' => 'id',
                    'allow_null' => 1,
                    'multiple' => 1,
                ),
                array(
                    'key' => 'field_event_description',
                    'label' => 'Description',
                    'name' => 'event_description',
                    'type' => 'textarea',
                    'rows' => 4,
                ),
                array(
                    'key' => 'field_event_price',
                    'label' => 'Price',
                    'name' => 'event_price',
                    'type' => 'text',
                    'placeholder' => 'e.g., €10 or Free',
                ),
                array(
                    'key' => 'field_event_ticket_url',
                    'label' => 'Ticket URL',
                    'name' => 'event_ticket_url',
                    'type' => 'url',
                    'placeholder' => 'https://tickets.example.com',
                    'instructions' => 'Link zum Ticket-Verkauf oder zur Anmeldung',
                ),
                array(
                    'key' => 'field_event_button_text',
                    'label' => 'Button Text',
                    'name' => 'event_button_text',
                    'type' => 'text',
                    'placeholder' => 'z.B. "Tickets kaufen" oder "Jetzt anmelden"',
                    'instructions' => 'Custom text for the button in event cards (optional)',
                    'default_value' => '',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                    array(
                        'param' => 'post_taxonomy',
                        'operator' => '==',
                        'value' => 'ensemble_category',
                    ),
                ),
            ),
        ));
    }
    
    /**
     * Install Artist field group
     */
    private function install_artist_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_ensemble_artist',
            'title' => 'Artist Details',
            'fields' => array(
                array(
                    'key' => 'field_artist_email',
                    'label' => 'E-Mail',
                    'name' => 'artist_email',
                    'type' => 'email',
                    'instructions' => 'Contact email for seminars, inquiries, etc.',
                ),
                array(
                    'key' => 'field_artist_genre',
                    'label' => 'Genre',
                    'name' => 'artist_genre',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_artist_website',
                    'label' => 'Website',
                    'name' => 'artist_website',
                    'type' => 'url',
                ),
                array(
                    'key' => 'field_artist_social',
                    'label' => 'Social Media',
                    'name' => 'artist_social',
                    'type' => 'textarea',
                    'rows' => 3,
                    'placeholder' => 'Facebook, Instagram, etc.',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'ensemble_artist',
                    ),
                ),
            ),
        ));
    }
    
    /**
     * Install Location field group
     */
    private function install_location_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_ensemble_location',
            'title' => 'Location Details',
            'fields' => array(
                array(
                    'key' => 'field_location_address',
                    'label' => 'Address',
                    'name' => 'location_address',
                    'type' => 'textarea',
                    'rows' => 3,
                ),
                array(
                    'key' => 'field_location_city',
                    'label' => 'City',
                    'name' => 'location_city',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_location_capacity',
                    'label' => 'Capacity',
                    'name' => 'location_capacity',
                    'type' => 'number',
                ),
                array(
                    'key' => 'field_location_website',
                    'label' => 'Website',
                    'name' => 'location_website',
                    'type' => 'url',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'ensemble_location',
                    ),
                ),
            ),
        ));
    }
    
    /**
     * Install test custom field groups for wizard steps
     * These can be assigned to categories in Settings
     */
    private function install_test_field_groups() {
        // Ticketing Field Group (for Concerts, Festivals, etc.)
        acf_add_local_field_group(array(
            'key' => 'group_ensemble_ticketing',
            'title' => 'Ticketing Information',
            'fields' => array(
                array(
                    'key' => 'field_ticket_price_regular',
                    'label' => 'Regular Ticket Price',
                    'name' => 'ticket_price_regular',
                    'type' => 'text',
                    'placeholder' => '€20',
                ),
                array(
                    'key' => 'field_ticket_price_vip',
                    'label' => 'VIP Ticket Price',
                    'name' => 'ticket_price_vip',
                    'type' => 'text',
                    'placeholder' => '€50',
                ),
                array(
                    'key' => 'field_ticket_url',
                    'label' => 'Ticket Shop URL',
                    'name' => 'ticket_url',
                    'type' => 'url',
                    'placeholder' => 'https://tickets.example.com',
                ),
                array(
                    'key' => 'field_ticket_availability',
                    'label' => 'Tickets Available',
                    'name' => 'ticket_availability',
                    'type' => 'number',
                    'placeholder' => '500',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                ),
            ),
            'menu_order' => 10,
        ));
        
        // Materials Field Group (for Workshops, Classes, etc.)
        acf_add_local_field_group(array(
            'key' => 'group_ensemble_materials',
            'title' => 'Materials & Requirements',
            'fields' => array(
                array(
                    'key' => 'field_materials_list',
                    'label' => 'Required Materials',
                    'name' => 'materials_list',
                    'type' => 'textarea',
                    'placeholder' => 'List materials participants need to bring...',
                    'rows' => 4,
                ),
                array(
                    'key' => 'field_skill_level',
                    'label' => 'Skill Level',
                    'name' => 'skill_level',
                    'type' => 'select',
                    'choices' => array(
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                    ),
                ),
                array(
                    'key' => 'field_max_participants',
                    'label' => 'Maximum Participants',
                    'name' => 'max_participants',
                    'type' => 'number',
                    'placeholder' => '20',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                ),
            ),
            'menu_order' => 11,
        ));
    }
}
