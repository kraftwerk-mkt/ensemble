<?php
/**
 * Floor Plan Frontend Display Template
 *
 * @package Ensemble
 * @subpackage Addons/FloorPlan
 *
 * Variables available:
 * - $atts: Shortcode attributes
 * - $floor_plan_id: Floor plan ID
 * - $floor_plan: Floor plan data array
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$event_id = intval($atts['event_id']);
$bookable = $atts['bookable'] === 'true';
$mode = isset($atts['mode']) ? $atts['mode'] : 'display';
$height = $atts['height'];
$extra_class = sanitize_html_class($atts['class']);

// Canvas data
$canvas = $floor_plan['canvas'];

// Get ticket categories linked to floor plan elements (for ticket mode)
$ticket_categories = array();
if ($mode === 'ticket' && $event_id && class_exists('ES_Ticket_Category')) {
    $categories = ES_Ticket_Category::get_by_event($event_id);
    foreach ($categories as $cat) {
        if (!empty($cat->floor_plan_element_id)) {
            $ticket_categories[$cat->floor_plan_element_id] = array(
                'id'        => $cat->id,
                'name'      => $cat->name,
                'price'     => $cat->price,
                'capacity'  => $cat->capacity,
                'sold'      => $cat->sold,
                'available' => $cat->capacity - $cat->sold,
            );
        }
    }
}

// Prepare data for JS
$floor_plan_json = wp_json_encode(array(
    'id'               => $floor_plan_id,
    'title'            => $floor_plan['title'],
    'canvas'           => $canvas,
    'sections'         => $floor_plan['sections'],
    'elements'         => $floor_plan['elements'],
    'eventId'          => $event_id,
    'bookable'         => $bookable,
    'mode'             => $mode,
    'ticketCategories' => $ticket_categories,
));
?>

<div class="es-floor-plan-wrapper es-floor-plan-mode-<?php echo esc_attr($mode); ?> <?php echo esc_attr($extra_class); ?>" 
     data-floor-plan-id="<?php echo esc_attr($floor_plan_id); ?>"
     data-event-id="<?php echo esc_attr($event_id); ?>"
     data-bookable="<?php echo $bookable ? 'true' : 'false'; ?>"
     data-mode="<?php echo esc_attr($mode); ?>">
    
    <!-- Legend -->
    <div class="es-floor-plan-legend">
        <div class="es-legend-title"><?php echo esc_html($floor_plan['title']); ?></div>
        <div class="es-legend-items">
            <span class="es-legend-item es-legend-available">
                <span class="es-legend-dot"></span>
                <?php _e('Available', 'ensemble'); ?>
            </span>
            <?php if ($mode !== 'ticket'): ?>
            <span class="es-legend-item es-legend-partial">
                <span class="es-legend-dot"></span>
                <?php _e('Partially Reserved', 'ensemble'); ?>
            </span>
            <?php endif; ?>
            <span class="es-legend-item es-legend-sold-out">
                <span class="es-legend-dot"></span>
                <?php _e('Sold Out', 'ensemble'); ?>
            </span>
        </div>
    </div>
    
    <!-- Canvas Container -->
    <div class="es-floor-plan-canvas-wrap" style="<?php echo $height !== 'auto' ? 'max-height: ' . esc_attr($height) . ';' : ''; ?>">
        <div class="es-floor-plan-canvas" 
             id="es-floor-plan-<?php echo esc_attr($floor_plan_id); ?>">
            <!-- Canvas will be rendered by JS -->
            <div class="es-floor-plan-loading">
                <span class="es-spinner"></span>
                <?php _e('Loading floor plan...', 'ensemble'); ?>
            </div>
        </div>
    </div>
    
    <!-- Section Legend -->
    <?php if (!empty($floor_plan['sections'])): ?>
    <div class="es-floor-plan-sections-legend">
        <?php foreach ($floor_plan['sections'] as $section): ?>
        <div class="es-section-legend-item">
            <span class="es-section-dot" style="background-color: <?php echo esc_attr($section['color']); ?>;"></span>
            <span class="es-section-name"><?php echo esc_html($section['name']); ?></span>
            <?php if ($section['default_price'] > 0): ?>
            <span class="es-section-price"><?php 
                if (function_exists('ensemble_format_price')) {
                    echo esc_html(ensemble_format_price($section['default_price']));
                } else {
                    $currency = get_option('ensemble_currency_symbol', '€');
                    echo esc_html($currency . number_format($section['default_price'], 2));
                }
            ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Hidden data for JS -->
    <script type="application/json" class="es-floor-plan-data">
        <?php echo $floor_plan_json; ?>
    </script>
</div>

<?php if ($bookable && $mode !== 'ticket'): ?>
<!-- Booking Modal (only for reservations) -->
<div class="es-floor-plan-booking-modal" id="es-booking-modal-<?php echo esc_attr($floor_plan_id); ?>" style="display: none;">
    <div class="es-booking-modal-content">
        <button type="button" class="es-booking-modal-close">&times;</button>
        
        <div class="es-booking-modal-header">
            <h3 class="es-booking-element-name"></h3>
            <span class="es-booking-section-badge"></span>
        </div>
        
        <div class="es-booking-modal-body">
            <div class="es-booking-info">
                <div class="es-booking-capacity">
                    <span class="dashicons dashicons-groups"></span>
                    <span class="es-booking-capacity-text"></span>
                </div>
                <div class="es-booking-price">
                    <span class="dashicons dashicons-tag"></span>
                    <span class="es-booking-price-text"></span>
                </div>
                <div class="es-booking-available">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span class="es-booking-available-text"></span>
                </div>
            </div>
            
            <div class="es-booking-description" style="display: none;"></div>
            
            <div class="es-booking-form">
                <label for="es-booking-seats-<?php echo esc_attr($floor_plan_id); ?>">
                    <?php _e('Number of seats', 'ensemble'); ?>
                </label>
                <select id="es-booking-seats-<?php echo esc_attr($floor_plan_id); ?>" class="es-booking-seats">
                    <!-- Options will be populated by JS -->
                </select>
            </div>
        </div>
        
        <div class="es-booking-modal-footer">
            <?php if (class_exists('ES_Reservations_Addon')): ?>
            <a href="#" class="es-booking-reserve-btn button button-primary">
                <?php _e('Reserve Now', 'ensemble'); ?>
            </a>
            <?php elseif (class_exists('ES_Tickets_Addon')): ?>
            <a href="#" class="es-booking-tickets-btn button button-primary">
                <?php _e('Buy Tickets', 'ensemble'); ?>
            </a>
            <?php else: ?>
            <p class="es-booking-info-text">
                <?php _e('Contact us to make a reservation.', 'ensemble'); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
