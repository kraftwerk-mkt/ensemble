<?php
/**
 * Ensemble Template Hooks
 * 
 * Central template hooks system for add-ons
 * Provides hook points in templates without modifying template files
 *
 * @package Ensemble
 * @since 2.0.0
 * @version 3.0.0 - Complete hook system for Events, Locations, Artists
 * 
 * ============================================================================
 * HOOK REFERENCE
 * ============================================================================
 * 
 * EVENT HOOKS (Single Event Templates):
 * --------------------------------------
 * - ensemble_before_event        : Before entire event article
 * - ensemble_event_header        : Inside header, after image
 * - ensemble_after_title         : After event title
 * - ensemble_event_meta          : In meta section (date/time/price)
 * - ensemble_before_content      : Before description
 * - ensemble_after_description   : After description
 * - ensemble_after_location      : After location details
 * - ensemble_artist_section      : In artist section
 * - ensemble_ticket_area         : Ticket section (for Tickets addon)
 * - ensemble_after_tickets       : After ticket section
 * - ensemble_gallery_area        : Gallery section (for Gallery addon)
 * - ensemble_event_sidebar       : Sidebar area
 * - ensemble_social_share        : Social sharing area
 * - ensemble_related_events      : Related events section
 * - ensemble_event_footer        : Footer before closing
 * - ensemble_after_event         : After entire event article
 * - ensemble_event_catalog       : Catalog/Menu section (for Catalog addon)
 * 
 * LOCATION HOOKS (Single Location Templates):
 * -------------------------------------------
 * - ensemble_before_location     : Before entire location article
 * - ensemble_location_header     : Inside header, after image
 * - ensemble_after_location_title: After location title
 * - ensemble_location_meta       : In meta section (address/phone)
 * - ensemble_location_map        : Map section (for Maps addon)
 * - ensemble_before_location_content : Before description
 * - ensemble_after_location_description : After description
 * - ensemble_location_catalog    : Menu/Offerings section (for Catalog addon)
 * - ensemble_location_gallery    : Gallery section
 * - ensemble_location_events     : Before events list
 * - ensemble_after_location_events : After events list
 * - ensemble_location_sidebar    : Sidebar area
 * - ensemble_location_contact    : Contact section
 * - ensemble_location_footer     : Footer before closing
 * - ensemble_after_location      : After entire location article
 * 
 * ARTIST HOOKS (Single Artist Templates):
 * ---------------------------------------
 * - ensemble_before_artist       : Before entire artist article
 * - ensemble_artist_header       : Inside header, after image
 * - ensemble_after_artist_title  : After artist title
 * - ensemble_artist_meta         : In meta section (genre/references)
 * - ensemble_artist_social       : Social links section
 * - ensemble_before_artist_content : Before bio
 * - ensemble_after_artist_bio    : After bio
 * - ensemble_artist_gallery      : Gallery/Media section
 * - ensemble_artist_events       : Before events list
 * - ensemble_after_artist_events : After events list
 * - ensemble_artist_sidebar      : Sidebar area
 * - ensemble_artist_footer       : Footer before closing
 * - ensemble_after_artist        : After entire artist article
 * 
 * CARD/LIST HOOKS:
 * ----------------
 * - ensemble_event_card_footer   : In event card footer
 * - ensemble_location_card_footer: In location card footer
 * - ensemble_artist_card_footer  : In artist card footer
 * 
 * CALENDAR HOOKS:
 * ---------------
 * - ensemble_calendar_header     : Before calendar grid
 * - ensemble_calendar_footer     : After calendar grid
 * - ensemble_calendar_event      : For each calendar event
 * 
 * ============================================================================
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// CORE HOOK FUNCTIONS
// ============================================================================

/**
 * Execute add-on hook
 * 
 * Wrapper function for templates - fires both addon manager and WordPress hooks
 * Supports hooks registered with or without 'ensemble_' prefix
 * 
 * @param string $hook_name Hook name
 * @param mixed ...$args Arguments to pass
 */
function ensemble_do_addon_hook($hook_name, ...$args) {
    // Fire addon manager hook - try both with and without prefix
    if (class_exists('ES_Addon_Manager')) {
        // Try without prefix first (e.g. 'ticket_area')
        ES_Addon_Manager::do_addon_hook($hook_name, ...$args);
        
        // Also try with ensemble_ prefix (e.g. 'ensemble_ticket_area')
        ES_Addon_Manager::do_addon_hook("ensemble_{$hook_name}", ...$args);
    }
    
    // WordPress action for third-party plugins
    do_action("ensemble_addon_hook_{$hook_name}", ...$args);
    
    // Also fire the full hook name for WordPress add_action usage
    do_action("ensemble_{$hook_name}", ...$args);
}

/**
 * Check if any add-on has registered for a hook
 * 
 * @param string $hook_name
 * @return bool
 */
function ensemble_has_addon_hook($hook_name) {
    $has_hook = has_action("ensemble_addon_hook_{$hook_name}") || 
                has_action("ensemble_{$hook_name}");
    
    if (class_exists('ES_Addon_Manager')) {
        // Check both with and without prefix
        $has_hook = $has_hook || 
                    ES_Addon_Manager::has_hook($hook_name) ||
                    ES_Addon_Manager::has_hook("ensemble_{$hook_name}");
    }
    
    return $has_hook;
}

/**
 * Get list of all available hooks for documentation
 * 
 * @return array
 */
function ensemble_get_available_hooks() {
    return array(
        'event' => array(
            'ensemble_before_event',
            'ensemble_event_header',
            'ensemble_after_title',
            'ensemble_event_meta',
            'ensemble_before_content',
            'ensemble_after_description',
            'ensemble_after_location',
            'ensemble_artist_section',
            'ensemble_ticket_area',
            'ensemble_after_tickets',
            'ensemble_gallery_area',
            'ensemble_event_sidebar',
            'ensemble_social_share',
            'ensemble_related_events',
            'ensemble_event_footer',
            'ensemble_after_event',
            'ensemble_event_catalog',
        ),
        'location' => array(
            'ensemble_before_location',
            'ensemble_location_header',
            'ensemble_after_location_title',
            'ensemble_location_meta',
            'ensemble_location_map',
            'ensemble_before_location_content',
            'ensemble_after_location_description',
            'ensemble_location_catalog',
            'ensemble_location_gallery',
            'ensemble_location_events',
            'ensemble_after_location_events',
            'ensemble_location_sidebar',
            'ensemble_location_contact',
            'ensemble_location_footer',
            'ensemble_after_location',
        ),
        'artist' => array(
            'ensemble_before_artist',
            'ensemble_artist_header',
            'ensemble_after_artist_title',
            'ensemble_artist_meta',
            'ensemble_artist_social_section',
            'ensemble_before_artist_content',
            'ensemble_after_artist_bio',
            'ensemble_artist_gallery',
            'ensemble_artist_events',
            'ensemble_after_artist_events',
            'ensemble_artist_sidebar',
            'ensemble_artist_footer',
            'ensemble_after_artist',
        ),
        'card' => array(
            'ensemble_event_card_footer',
            'ensemble_location_card_footer',
            'ensemble_artist_card_footer',
        ),
        'calendar' => array(
            'ensemble_calendar_header',
            'ensemble_calendar_footer',
            'ensemble_calendar_event',
        ),
    );
}

// ============================================================================
// EVENT HOOK FUNCTIONS
// ============================================================================

/**
 * Before event hook
 * @param int $event_id
 */
function ensemble_before_event($event_id) {
    ensemble_do_addon_hook('before_event', $event_id);
}

/**
 * Event header hook
 * @param int $event_id
 */
function ensemble_event_header($event_id) {
    ensemble_do_addon_hook('event_header', $event_id);
}

/**
 * After title hook
 * @param int $event_id
 */
function ensemble_after_title($event_id) {
    ensemble_do_addon_hook('after_title', $event_id);
}

/**
 * Event meta hook
 * @param int $event_id
 * @param array $meta_data Optional meta data
 */
function ensemble_event_meta($event_id, $meta_data = array()) {
    ensemble_do_addon_hook('event_meta', $event_id, $meta_data);
}

/**
 * Before content hook
 * @param int $event_id
 */
function ensemble_before_content($event_id) {
    ensemble_do_addon_hook('before_content', $event_id);
}

/**
 * After description hook
 * @param int $event_id
 */
function ensemble_after_description($event_id) {
    ensemble_do_addon_hook('after_description', $event_id);
}

/**
 * After location hook
 * @param int $event_id
 * @param array $location_data Location data array
 */
function ensemble_after_location($event_id, $location_data = array()) {
    ensemble_do_addon_hook('after_location', $event_id, $location_data);
}

/**
 * Artist section hook
 * @param int $event_id
 * @param array $artists Artists array
 */
function ensemble_artist_section($event_id, $artists = array()) {
    ensemble_do_addon_hook('artist_section', $event_id, $artists);
}

/**
 * Ticket area hook
 * @param int $event_id
 * @param array $ticket_data Optional ticket data
 */
function ensemble_ticket_area($event_id, $ticket_data = array()) {
    ensemble_do_addon_hook('ticket_area', $event_id, $ticket_data);
}

/**
 * After tickets hook
 * @param int $event_id
 */
function ensemble_after_tickets($event_id) {
    ensemble_do_addon_hook('after_tickets', $event_id);
}

/**
 * Gallery area hook
 * @param int $event_id
 * @param array $gallery Gallery images array
 */
function ensemble_gallery_area($event_id, $gallery = array()) {
    ensemble_do_addon_hook('gallery_area', $event_id, $gallery);
}

/**
 * Event sidebar hook
 * @param int $event_id
 */
function ensemble_event_sidebar($event_id) {
    ensemble_do_addon_hook('event_sidebar', $event_id);
}

/**
 * Social share hook
 * @param int $event_id
 * @param array $share_data Title, URL, image etc.
 */
function ensemble_social_share($event_id, $share_data = array()) {
    ensemble_do_addon_hook('social_share', $event_id, $share_data);
}

/**
 * Related events hook
 * @param int $event_id
 * @param array $context Categories, location, artists etc.
 */
function ensemble_related_events($event_id, $context = array()) {
    ensemble_do_addon_hook('related_events', $event_id, $context);
}

/**
 * Event footer hook
 * @param int $event_id
 */
function ensemble_event_footer($event_id) {
    ensemble_do_addon_hook('event_footer', $event_id);
}

/**
 * After event hook
 * @param int $event_id
 * @param array $event_data Full event data
 */
function ensemble_after_event($event_id, $event_data = array()) {
    ensemble_do_addon_hook('after_event', $event_id, $event_data);
}

/**
 * Event catalog hook (for menus, happy hour cards etc.)
 * @param int $event_id
 * @param int $location_id Associated location
 */
function ensemble_event_catalog($event_id, $location_id = 0) {
    ensemble_do_addon_hook('event_catalog', $event_id, $location_id);
}

// ============================================================================
// LOCATION HOOK FUNCTIONS
// ============================================================================

/**
 * Before location hook
 * @param int $location_id
 */
function ensemble_before_location($location_id) {
    ensemble_do_addon_hook('before_location', $location_id);
}

/**
 * Location header hook
 * @param int $location_id
 */
function ensemble_location_header($location_id) {
    ensemble_do_addon_hook('location_header', $location_id);
}

/**
 * After location title hook
 * @param int $location_id
 */
function ensemble_after_location_title($location_id) {
    ensemble_do_addon_hook('after_location_title', $location_id);
}

/**
 * Location meta hook
 * @param int $location_id
 * @param array $meta_data Address, phone etc.
 */
function ensemble_location_meta($location_id, $meta_data = array()) {
    ensemble_do_addon_hook('location_meta', $location_id, $meta_data);
}

/**
 * Location map hook
 * @param int $location_id
 * @param array $address_data Address components
 */
function ensemble_location_map($location_id, $address_data = array()) {
    ensemble_do_addon_hook('location_map', $location_id, $address_data);
}

/**
 * Before location content hook
 * @param int $location_id
 */
function ensemble_before_location_content($location_id) {
    ensemble_do_addon_hook('before_location_content', $location_id);
}

/**
 * After location description hook
 * @param int $location_id
 */
function ensemble_after_location_description($location_id) {
    ensemble_do_addon_hook('after_location_description', $location_id);
}

/**
 * Location catalog hook (for menus, offerings, packages)
 * @param int $location_id
 */
function ensemble_location_catalog($location_id) {
    ensemble_do_addon_hook('location_catalog', $location_id);
}

/**
 * Location gallery hook
 * @param int $location_id
 * @param array $gallery Gallery images
 */
function ensemble_location_gallery($location_id, $gallery = array()) {
    ensemble_do_addon_hook('location_gallery', $location_id, $gallery);
}

/**
 * Location events hook (before events list)
 * @param int $location_id
 */
function ensemble_location_events($location_id) {
    ensemble_do_addon_hook('location_events', $location_id);
}

/**
 * After location events hook
 * @param int $location_id
 * @param WP_Query $events_query The events query
 */
function ensemble_after_location_events($location_id, $events_query = null) {
    ensemble_do_addon_hook('after_location_events', $location_id, $events_query);
}

/**
 * Location sidebar hook
 * @param int $location_id
 */
function ensemble_location_sidebar($location_id) {
    ensemble_do_addon_hook('location_sidebar', $location_id);
}

/**
 * Location contact hook
 * @param int $location_id
 * @param array $contact_data Phone, email, website
 */
function ensemble_location_contact($location_id, $contact_data = array()) {
    ensemble_do_addon_hook('location_contact', $location_id, $contact_data);
}

/**
 * Location footer hook
 * @param int $location_id
 */
function ensemble_location_footer($location_id) {
    ensemble_do_addon_hook('location_footer', $location_id);
}

/**
 * After location hook
 * @param int $location_id
 * @param array $location_data Full location data
 */
function ensemble_after_location_hook($location_id, $location_data = array()) {
    ensemble_do_addon_hook('after_location_complete', $location_id, $location_data);
}

// ============================================================================
// ARTIST HOOK FUNCTIONS
// ============================================================================

/**
 * Before artist hook
 * @param int $artist_id
 */
function ensemble_before_artist($artist_id) {
    ensemble_do_addon_hook('before_artist', $artist_id);
}

/**
 * Artist header hook
 * @param int $artist_id
 */
function ensemble_artist_header($artist_id) {
    ensemble_do_addon_hook('artist_header', $artist_id);
}

/**
 * After artist title hook
 * @param int $artist_id
 */
function ensemble_after_artist_title($artist_id) {
    ensemble_do_addon_hook('after_artist_title', $artist_id);
}

/**
 * Artist meta hook
 * @param int $artist_id
 * @param array $meta_data Genre, references etc.
 */
function ensemble_artist_meta($artist_id, $meta_data = array()) {
    ensemble_do_addon_hook('artist_meta', $artist_id, $meta_data);
}

/**
 * Artist social section hook
 * @param int $artist_id
 * @param array $social_links Social media URLs
 */
function ensemble_artist_social_section($artist_id, $social_links = array()) {
    ensemble_do_addon_hook('artist_social_section', $artist_id, $social_links);
}

/**
 * Before artist content hook
 * @param int $artist_id
 */
function ensemble_before_artist_content($artist_id) {
    ensemble_do_addon_hook('before_artist_content', $artist_id);
}

/**
 * After artist bio hook
 * @param int $artist_id
 */
function ensemble_after_artist_bio($artist_id) {
    ensemble_do_addon_hook('after_artist_bio', $artist_id);
}

/**
 * Artist gallery hook
 * @param int $artist_id
 * @param array $gallery Gallery/media items
 */
function ensemble_artist_gallery($artist_id, $gallery = array()) {
    ensemble_do_addon_hook('artist_gallery', $artist_id, $gallery);
}

/**
 * Artist events hook (before events list)
 * @param int $artist_id
 */
function ensemble_artist_events($artist_id) {
    ensemble_do_addon_hook('artist_events', $artist_id);
}

/**
 * After artist events hook
 * @param int $artist_id
 * @param WP_Query $events_query The events query
 */
function ensemble_after_artist_events($artist_id, $events_query = null) {
    ensemble_do_addon_hook('after_artist_events', $artist_id, $events_query);
}

/**
 * Artist sidebar hook
 * @param int $artist_id
 */
function ensemble_artist_sidebar($artist_id) {
    ensemble_do_addon_hook('artist_sidebar', $artist_id);
}

/**
 * Artist footer hook
 * @param int $artist_id
 */
function ensemble_artist_footer($artist_id) {
    ensemble_do_addon_hook('artist_footer', $artist_id);
}

/**
 * After artist hook
 * @param int $artist_id
 * @param array $artist_data Full artist data
 */
function ensemble_after_artist($artist_id, $artist_data = array()) {
    ensemble_do_addon_hook('after_artist', $artist_id, $artist_data);
}

// ============================================================================
// CARD/LIST HOOK FUNCTIONS
// ============================================================================

/**
 * Event card footer hook
 * @param int $event_id
 */
function ensemble_event_card_footer($event_id) {
    ensemble_do_addon_hook('event_card_footer', $event_id);
}

/**
 * Location card footer hook
 * @param int $location_id
 */
function ensemble_location_card_footer($location_id) {
    ensemble_do_addon_hook('location_card_footer', $location_id);
}

/**
 * Artist card footer hook
 * @param int $artist_id
 */
function ensemble_artist_card_footer($artist_id) {
    ensemble_do_addon_hook('artist_card_footer', $artist_id);
}

// ============================================================================
// CALENDAR HOOK FUNCTIONS
// ============================================================================

/**
 * Calendar header hook
 * @param array $args Calendar arguments
 */
function ensemble_calendar_header($args = array()) {
    ensemble_do_addon_hook('calendar_header', $args);
}

/**
 * Calendar footer hook
 * @param array $args Calendar arguments
 */
function ensemble_calendar_footer($args = array()) {
    ensemble_do_addon_hook('calendar_footer', $args);
}

/**
 * Calendar event hook (for each event in calendar)
 * @param int $event_id
 * @param array $event_data Event data
 */
function ensemble_calendar_event($event_id, $event_data = array()) {
    ensemble_do_addon_hook('calendar_event', $event_id, $event_data);
}

// ============================================================================
// LEGACY COMPATIBILITY
// ============================================================================

/**
 * Event location info hook (legacy - kept for backward compatibility)
 * @param int $event_id
 * @param int $location_id
 */
function ensemble_event_location_info($event_id, $location_id) {
    if (!$location_id) {
        return;
    }
    
    // Basic location info
    $location_name = get_the_title($location_id);
    $location_url = get_permalink($location_id);
    $address = get_post_meta($location_id, 'location_address', true);
    $city = get_post_meta($location_id, 'location_city', true);
    
    ?>
    <div class="ensemble-location-info">
        <h3 class="ensemble-section-title">
            <span class="dashicons dashicons-location-alt"></span>
            <?php _e('Location', 'ensemble'); ?>
        </h3>
        
        <div class="ensemble-location-details">
            <h4 class="ensemble-location-name">
                <a href="<?php echo esc_url($location_url); ?>">
                    <?php echo esc_html($location_name); ?>
                </a>
            </h4>
            
            <?php if ($address || $city): ?>
                <p class="ensemble-location-address">
                    <?php 
                    if ($address) echo esc_html($address);
                    if ($address && $city) echo '<br>';
                    if ($city) echo esc_html($city);
                    ?>
                </p>
            <?php endif; ?>
        </div>
        
        <?php
        // Add-on hook for maps, directions, etc.
        ensemble_do_addon_hook('event_location_info', $event_id, $location_id);
        ?>
    </div>
    <?php
}

/**
 * Legacy hook aliases for backward compatibility
 */
function ensemble_after_event_content($event_id) {
    ensemble_after_description($event_id);
}

function ensemble_before_event_content($event_id) {
    ensemble_before_content($event_id);
}

function ensemble_event_meta_info($event_id) {
    ensemble_event_meta($event_id);
}

function ensemble_artist_info($event_id, $artist_id) {
    ensemble_do_addon_hook('artist_info', $event_id, $artist_id);
}

function ensemble_location_detail($location_id) {
    ensemble_do_addon_hook('location_detail', $location_id);
}

function ensemble_event_list_item($event_id) {
    ensemble_do_addon_hook('event_list_item', $event_id);
}

function ensemble_calendar_view($args = array()) {
    ensemble_do_addon_hook('calendar_view', $args);
}

function ensemble_event_categories($event_id, $categories) {
    ensemble_do_addon_hook('event_categories', $event_id, $categories);
}

function ensemble_ticket_info($event_id) {
    ensemble_ticket_area($event_id);
}

function ensemble_event_gallery($event_id) {
    ensemble_gallery_area($event_id);
}
