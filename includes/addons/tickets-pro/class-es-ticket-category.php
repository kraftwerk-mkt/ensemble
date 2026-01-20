<?php
/**
 * Ticket Category Model
 * 
 * Represents a ticket category for an event or a global template.
 * Templates have event_id = 0 and can be imported into events.
 * 
 * NEW in 3.1.1: Added 'source' field to track where the category was created:
 * - 'manual': Created via central Tickets admin
 * - 'wizard': Created via Event Wizard
 * - 'floor_plan': Auto-created from Floor Plan element
 * - 'import': Imported from template
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Ticket_Category {
    
    /**
     * Category ID
     * @var int
     */
    public $id = 0;
    
    /**
     * Event ID (0 = global template)
     * @var int
     */
    public $event_id = 0;
    
    /**
     * Category name
     * @var string
     */
    public $name = '';
    
    /**
     * Description
     * @var string
     */
    public $description = '';
    
    /**
     * Price
     * @var float
     */
    public $price = 0.00;
    
    /**
     * Currency
     * @var string
     */
    public $currency = 'EUR';
    
    /**
     * Capacity (null = unlimited)
     * @var int|null
     */
    public $capacity = null;
    
    /**
     * Sold count
     * @var int
     */
    public $sold = 0;
    
    /**
     * Floor plan ID (optional)
     * @var int|null
     */
    public $floor_plan_id = null;
    
    /**
     * Floor plan zone (optional)
     * @var string|null
     */
    public $floor_plan_zone = null;
    
    /**
     * Floor plan element ID (optional, for direct element mapping)
     * @var string|null
     * @since 3.1.1
     */
    public $floor_plan_element_id = null;
    
    /**
     * Sale start date
     * @var string|null
     */
    public $sale_start = null;
    
    /**
     * Sale end date
     * @var string|null
     */
    public $sale_end = null;
    
    /**
     * Minimum quantity per order
     * @var int
     */
    public $min_quantity = 1;
    
    /**
     * Maximum quantity per order
     * @var int
     */
    public $max_quantity = 10;
    
    /**
     * Status (active, inactive)
     * @var string
     */
    public $status = 'active';
    
    /**
     * Sort order
     * @var int
     */
    public $sort_order = 0;
    
    /**
     * Source of category creation
     * Possible values: 'manual', 'wizard', 'floor_plan', 'import'
     * @var string
     * @since 3.1.1
     */
    public $source = 'manual';
    
    /**
     * WooCommerce product ID (optional, for WC integration)
     * @var int|null
     * @since 3.1.1
     */
    public $woo_product_id = null;
    
    /**
     * Ticket type: 'paid' for self-hosted, 'external' for third-party links
     * @var string
     * @since 3.2.0
     */
    public $ticket_type = 'paid';
    
    /**
     * External provider (for external tickets)
     * Possible values: 'eventbrite', 'resident_advisor', 'eventim', 'ticketmaster', 'dice', 'reservix', 'tickets_io', 'custom'
     * @var string|null
     * @since 3.2.0
     */
    public $provider = null;
    
    /**
     * External ticket URL
     * @var string|null
     * @since 3.2.0
     */
    public $external_url = null;
    
    /**
     * Button text for external tickets
     * @var string|null
     * @since 3.2.0
     */
    public $button_text = null;
    
    /**
     * Availability status for external tickets
     * Possible values: 'available', 'limited', 'few_left', 'presale', 'sold_out', 'cancelled'
     * @var string
     * @since 3.2.0
     */
    public $availability_status = 'available';
    
    /**
     * Maximum price (for price ranges on external tickets)
     * @var float|null
     * @since 3.2.0
     */
    public $price_max = null;
    
    /**
     * Created timestamp
     * @var string
     */
    public $created_at = '';
    
    /**
     * Updated timestamp
     * @var string
     */
    public $updated_at = '';
    
    /**
     * Constructor
     * 
     * @param int|object $data Category ID or data object
     */
    public function __construct($data = null) {
        if (is_numeric($data)) {
            $this->load($data);
        } elseif (is_object($data) || is_array($data)) {
            $this->populate($data);
        }
    }
    
    /**
     * Load category from database
     * 
     * @param int $id
     * @return bool
     */
    public function load($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
        
        if ($row) {
            $this->populate($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Populate from data
     * 
     * @param object|array $data
     */
    private function populate($data) {
        $data = (array) $data;
        
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        // Type casting
        $this->id = (int) $this->id;
        $this->event_id = (int) $this->event_id;
        $this->price = (float) $this->price;
        $this->capacity = $this->capacity !== null ? (int) $this->capacity : null;
        $this->sold = (int) $this->sold;
        $this->floor_plan_id = $this->floor_plan_id ? (int) $this->floor_plan_id : null;
        $this->woo_product_id = $this->woo_product_id ? (int) $this->woo_product_id : null;
        $this->min_quantity = (int) $this->min_quantity;
        $this->max_quantity = (int) $this->max_quantity;
        $this->sort_order = (int) $this->sort_order;
        
        // Default source if not set
        if (empty($this->source)) {
            $this->source = 'manual';
        }
    }
    
    /**
     * Check if this is a global template
     * 
     * @return bool
     */
    public function is_template() {
        return $this->event_id === 0;
    }
    
    /**
     * Check if this is an external ticket (link to third-party provider)
     * 
     * @return bool
     * @since 3.2.0
     */
    public function is_external() {
        return $this->ticket_type === 'external';
    }
    
    /**
     * Check if this is a paid ticket (self-hosted)
     * 
     * @return bool
     * @since 3.2.0
     */
    public function is_paid() {
        return $this->ticket_type === 'paid';
    }
    
    /**
     * Check if this category is linked to a floor plan element
     * 
     * @return bool
     * @since 3.1.1
     */
    public function is_floor_plan_linked() {
        return !empty($this->floor_plan_id) || !empty($this->floor_plan_element_id);
    }
    
    /**
     * Save category
     * 
     * @return int|false Category ID or false on failure
     */
    public function save() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        $now = current_time('mysql');
        
        $data = array(
            'event_id'              => $this->event_id,
            'name'                  => $this->name,
            'description'           => $this->description,
            'price'                 => $this->price,
            'currency'              => $this->currency,
            'capacity'              => $this->capacity,
            'sold'                  => $this->sold,
            'floor_plan_id'         => $this->floor_plan_id,
            'floor_plan_zone'       => $this->floor_plan_zone,
            'floor_plan_element_id' => $this->floor_plan_element_id,
            'sale_start'            => $this->sale_start,
            'sale_end'              => $this->sale_end,
            'min_quantity'          => $this->min_quantity,
            'max_quantity'          => $this->max_quantity,
            'status'                => $this->status,
            'sort_order'            => $this->sort_order,
            'source'                => $this->source,
            'woo_product_id'        => $this->woo_product_id,
            // External ticket fields
            'ticket_type'           => $this->ticket_type,
            'provider'              => $this->provider,
            'external_url'          => $this->external_url,
            'button_text'           => $this->button_text,
            'availability_status'   => $this->availability_status,
            'price_max'             => $this->price_max,
            'updated_at'            => $now,
        );
        
        $format = array(
            '%d', '%s', '%s', '%f', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%d',
            '%s', '%s', '%s', '%s', '%s', '%f', '%s' // External fields + updated_at
        );
        
        if ($this->id) {
            // Update
            $result = $wpdb->update($table, $data, array('id' => $this->id), $format, array('%d'));
            return $result !== false ? $this->id : false;
        } else {
            // Insert
            $data['created_at'] = $now;
            $format[] = '%s';
            
            $result = $wpdb->insert($table, $data, $format);
            
            if ($result) {
                $this->id = $wpdb->insert_id;
                return $this->id;
            }
            
            return false;
        }
    }
    
    /**
     * Delete category
     * 
     * @return bool
     */
    public function delete() {
        if (!$this->id) {
            return false;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        
        return $wpdb->delete($table, array('id' => $this->id), array('%d')) !== false;
    }
    
    /**
     * Create a copy of this category for an event
     * 
     * @param int $event_id Target event ID
     * @return ES_Ticket_Category|false
     */
    public function copy_to_event($event_id) {
        $copy = new self();
        $copy->event_id = $event_id;
        $copy->name = $this->name;
        $copy->description = $this->description;
        $copy->price = $this->price;
        $copy->currency = $this->currency;
        $copy->capacity = $this->capacity;
        $copy->sold = 0; // Reset sold count
        $copy->min_quantity = $this->min_quantity;
        $copy->max_quantity = $this->max_quantity;
        $copy->status = $this->status;
        $copy->sort_order = $this->sort_order;
        $copy->source = 'import'; // Mark as imported
        
        // External ticket fields
        $copy->ticket_type = $this->ticket_type;
        $copy->provider = $this->provider;
        $copy->external_url = $this->external_url;
        $copy->button_text = $this->button_text;
        $copy->availability_status = $this->availability_status;
        $copy->price_max = $this->price_max;
        
        // Don't copy: floor_plan_id, floor_plan_zone, floor_plan_element_id, sale_start, sale_end (event-specific)
        
        if ($copy->save()) {
            return $copy;
        }
        
        return false;
    }
    
    /**
     * Duplicate this category/template
     * 
     * @return ES_Ticket_Category|false
     */
    public function duplicate() {
        $copy = new self();
        $copy->event_id = $this->event_id;
        $copy->name = $this->name . ' ' . __('(Copy)', 'ensemble');
        $copy->description = $this->description;
        $copy->price = $this->price;
        $copy->currency = $this->currency;
        $copy->capacity = $this->capacity;
        $copy->sold = 0;
        $copy->floor_plan_id = $this->floor_plan_id;
        $copy->floor_plan_zone = $this->floor_plan_zone;
        $copy->floor_plan_element_id = $this->floor_plan_element_id;
        $copy->sale_start = $this->sale_start;
        $copy->sale_end = $this->sale_end;
        $copy->min_quantity = $this->min_quantity;
        $copy->max_quantity = $this->max_quantity;
        $copy->status = $this->status;
        $copy->sort_order = $this->sort_order + 1;
        $copy->source = $this->source; // Keep same source
        
        // External ticket fields
        $copy->ticket_type = $this->ticket_type;
        $copy->provider = $this->provider;
        $copy->external_url = $this->external_url;
        $copy->button_text = $this->button_text;
        $copy->availability_status = $this->availability_status;
        $copy->price_max = $this->price_max;
        
        if ($copy->save()) {
            return $copy;
        }
        
        return false;
    }
    
    /**
     * Get available count
     * 
     * @return int|null (null = unlimited)
     */
    public function get_available_count() {
        if ($this->capacity === null) {
            return null; // Unlimited
        }
        
        return max(0, $this->capacity - $this->sold);
    }
    
    /**
     * Get availability status
     * 
     * @return string (available, limited, sold_out, not_on_sale)
     */
    public function get_availability_status() {
        // Check if on sale
        $now = current_time('mysql');
        
        if ($this->sale_start && $now < $this->sale_start) {
            return 'not_on_sale';
        }
        
        if ($this->sale_end && $now > $this->sale_end) {
            return 'not_on_sale';
        }
        
        // Check capacity
        $available = $this->get_available_count();
        
        if ($available === null) {
            return 'available';
        }
        
        if ($available <= 0) {
            return 'sold_out';
        }
        
        if ($available <= 10) {
            return 'limited';
        }
        
        return 'available';
    }
    
    /**
     * Check if currently on sale
     * 
     * @return bool
     */
    public function is_on_sale() {
        if ($this->status !== 'active') {
            return false;
        }
        
        $now = current_time('mysql');
        
        if ($this->sale_start && $now < $this->sale_start) {
            return false;
        }
        
        if ($this->sale_end && $now > $this->sale_end) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Increment sold count
     * 
     * @param int $quantity
     * @return bool
     */
    public function increment_sold($quantity = 1) {
        global $wpdb;
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table SET sold = sold + %d, updated_at = %s WHERE id = %d",
            $quantity,
            current_time('mysql'),
            $this->id
        ));
        
        if ($result !== false) {
            $this->sold += $quantity;
            return true;
        }
        
        return false;
    }
    
    /**
     * Decrement sold count
     * 
     * @param int $quantity
     * @return bool
     */
    public function decrement_sold($quantity = 1) {
        global $wpdb;
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table SET sold = GREATEST(0, sold - %d), updated_at = %s WHERE id = %d",
            $quantity,
            current_time('mysql'),
            $this->id
        ));
        
        if ($result !== false) {
            $this->sold = max(0, $this->sold - $quantity);
            return true;
        }
        
        return false;
    }
    
    /**
     * Format price for display
     * 
     * @return string
     */
    public function get_formatted_price() {
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CHF' => 'CHF',
        );
        
        $symbol = isset($symbols[$this->currency]) ? $symbols[$this->currency] : $this->currency . ' ';
        
        return $symbol . number_format($this->price, 2, ',', '.');
    }
    
    /**
     * Get source label for display
     * 
     * @return string
     * @since 3.1.1
     */
    public function get_source_label() {
        $labels = array(
            'manual'     => __('Manual', 'ensemble'),
            'wizard'     => __('Event Wizard', 'ensemble'),
            'floor_plan' => __('Floor Plan', 'ensemble'),
            'import'     => __('Imported', 'ensemble'),
        );
        
        return $labels[$this->source] ?? $this->source;
    }
    
    /**
     * Convert to array
     * 
     * @return array
     */
    public function to_array() {
        return array(
            'id'                    => $this->id,
            'event_id'              => $this->event_id,
            'name'                  => $this->name,
            'description'           => $this->description,
            'price'                 => $this->price,
            'currency'              => $this->currency,
            'capacity'              => $this->capacity,
            'sold'                  => $this->sold,
            'available'             => $this->get_available_count(),
            'floor_plan_id'         => $this->floor_plan_id,
            'floor_plan_zone'       => $this->floor_plan_zone,
            'floor_plan_element_id' => $this->floor_plan_element_id,
            'sale_start'            => $this->sale_start,
            'sale_end'              => $this->sale_end,
            'min_quantity'          => $this->min_quantity,
            'max_quantity'          => $this->max_quantity,
            'status'                => $this->status,
            'availability'          => $this->is_external() ? $this->availability_status : $this->get_availability_status(),
            'on_sale'               => $this->is_on_sale(),
            'formatted_price'       => $this->get_formatted_price(),
            'sort_order'            => $this->sort_order,
            'is_template'           => $this->is_template(),
            'source'                => $this->source,
            'source_label'          => $this->get_source_label(),
            'woo_product_id'        => $this->woo_product_id,
            'is_floor_plan_linked'  => $this->is_floor_plan_linked(),
            // External ticket fields
            'ticket_type'           => $this->ticket_type,
            'is_external'           => $this->is_external(),
            'is_paid'               => $this->is_paid(),
            'provider'              => $this->provider,
            'external_url'          => $this->external_url,
            'button_text'           => $this->button_text,
            'availability_status'   => $this->availability_status,
            'price_max'             => $this->price_max,
        );
    }
    
    /**
     * Get categories by event
     * 
     * @param int  $event_id
     * @param bool $active_only
     * @return ES_Ticket_Category[]
     */
    public static function get_by_event($event_id, $active_only = true) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        $where = $active_only ? "AND status = 'active'" : '';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE event_id = %d $where ORDER BY sort_order ASC, name ASC",
            $event_id
        ));
        
        $categories = array();
        foreach ($results as $row) {
            $categories[] = new self($row);
        }
        
        return $categories;
    }
    
    /**
     * Get categories by floor plan
     * 
     * @param int  $floor_plan_id
     * @param bool $active_only
     * @return ES_Ticket_Category[]
     * @since 3.1.1
     */
    public static function get_by_floor_plan($floor_plan_id, $active_only = true) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        $where = $active_only ? "AND status = 'active'" : '';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE floor_plan_id = %d $where ORDER BY sort_order ASC, name ASC",
            $floor_plan_id
        ));
        
        $categories = array();
        foreach ($results as $row) {
            $categories[] = new self($row);
        }
        
        return $categories;
    }
    
    /**
     * Get category by floor plan element
     * 
     * @param int    $floor_plan_id
     * @param string $element_id
     * @return ES_Ticket_Category|null
     * @since 3.1.1
     */
    public static function get_by_floor_plan_element($floor_plan_id, $element_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE floor_plan_id = %d AND floor_plan_element_id = %s LIMIT 1",
            $floor_plan_id,
            $element_id
        ));
        
        return $row ? new self($row) : null;
    }
    
    /**
     * Get global templates (event_id = 0)
     * 
     * @param bool $active_only
     * @return ES_Ticket_Category[]
     */
    public static function get_templates($active_only = false) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        $where = $active_only ? "AND status = 'active'" : '';
        
        $results = $wpdb->get_results(
            "SELECT * FROM $table WHERE event_id = 0 $where ORDER BY sort_order ASC, name ASC"
        );
        
        $templates = array();
        foreach ($results as $row) {
            $templates[] = new self($row);
        }
        
        return $templates;
    }
    
    /**
     * Get category by ID
     * 
     * @param int $id
     * @return ES_Ticket_Category|null
     */
    public static function get($id) {
        $category = new self($id);
        return $category->id ? $category : null;
    }
    
    /**
     * Create from array
     * 
     * @param array $data
     * @return ES_Ticket_Category
     */
    public static function create($data) {
        $category = new self($data);
        $category->save();
        return $category;
    }
    
    /**
     * Create or update from floor plan element
     * 
     * @param int    $event_id
     * @param int    $floor_plan_id
     * @param string $element_id
     * @param array  $element_data Element data (name, price, capacity, etc.)
     * @return ES_Ticket_Category
     * @since 3.1.1
     */
    public static function create_or_update_from_floor_plan($event_id, $floor_plan_id, $element_id, $element_data) {
        // Check if category already exists for this element
        $existing = self::get_by_floor_plan_element($floor_plan_id, $element_id);
        
        if ($existing) {
            // Update existing
            $existing->name = $element_data['name'] ?? $existing->name;
            $existing->price = isset($element_data['price']) ? (float) $element_data['price'] : $existing->price;
            $existing->capacity = isset($element_data['capacity']) ? (int) $element_data['capacity'] : $existing->capacity;
            $existing->floor_plan_zone = $element_data['zone'] ?? $existing->floor_plan_zone;
            $existing->save();
            return $existing;
        }
        
        // Create new
        $category = new self();
        $category->event_id = $event_id;
        $category->floor_plan_id = $floor_plan_id;
        $category->floor_plan_element_id = $element_id;
        $category->name = $element_data['name'] ?? sprintf(__('Element %s', 'ensemble'), $element_id);
        $category->price = isset($element_data['price']) ? (float) $element_data['price'] : 0;
        $category->capacity = isset($element_data['capacity']) ? (int) $element_data['capacity'] : 1;
        $category->floor_plan_zone = $element_data['zone'] ?? null;
        $category->source = 'floor_plan';
        $category->save();
        
        return $category;
    }
    
    /**
     * Import templates to an event
     * 
     * @param int   $event_id
     * @param array $template_ids
     * @return ES_Ticket_Category[] Created categories
     */
    public static function import_templates_to_event($event_id, $template_ids) {
        $created = array();
        
        foreach ($template_ids as $template_id) {
            $template = self::get($template_id);
            
            if ($template && $template->is_template()) {
                $copy = $template->copy_to_event($event_id);
                if ($copy) {
                    $created[] = $copy;
                }
            }
        }
        
        return $created;
    }
    
    /**
     * Delete all categories for a floor plan
     * 
     * @param int $floor_plan_id
     * @return int Number of deleted categories
     * @since 3.1.1
     */
    public static function delete_by_floor_plan($floor_plan_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        
        return $wpdb->delete(
            $table, 
            array('floor_plan_id' => $floor_plan_id), 
            array('%d')
        );
    }
}
