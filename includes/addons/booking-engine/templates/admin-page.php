<?php
/**
 * Booking Engine Admin Page
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get events for filter dropdown and modal
// Use passed events if available, otherwise query
if (!isset($events) || empty($events)) {
    $post_type = 'ensemble_event';
    if (function_exists('ensemble_get_post_type')) {
        $post_type = ensemble_get_post_type();
    }
    
    $events = get_posts(array(
        'post_type'      => $post_type,
        'posts_per_page' => 100,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => array('publish', 'future'),
    ));
}

// Current filters
$current_event = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
$current_status = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
$current_type = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';
?>
<div class="es-manager-wrap">
    
    <!-- Header -->
    <div class="es-manager-header">
        <div class="es-header-left">
            <h1>
                <span class="dashicons dashicons-tickets-alt"></span>
                <?php _e('Bookings', 'ensemble'); ?>
            </h1>
        </div>
        <div class="es-header-right">
            <button type="button" class="button button-primary" id="es-add-booking">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Add Booking', 'ensemble'); ?>
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="es-filters-bar">
        <div class="es-filters-left">
            <select id="es-filter-event" class="es-filter-select">
                <option value=""><?php _e('All Events', 'ensemble'); ?></option>
                <?php foreach ($events as $event): 
                    $event_date = get_post_meta($event->ID, 'event_date', true);
                ?>
                <option value="<?php echo esc_attr($event->ID); ?>" <?php selected($current_event, $event->ID); ?>>
                    <?php echo esc_html($event->post_title); ?>
                    <?php if ($event_date): ?>
                        (<?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?>)
                    <?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <select id="es-filter-type" class="es-filter-select">
                <option value=""><?php _e('All Types', 'ensemble'); ?></option>
                <option value="reservation" <?php selected($current_type, 'reservation'); ?>><?php _e('Reservation', 'ensemble'); ?></option>
                <option value="ticket" <?php selected($current_type, 'ticket'); ?>><?php _e('Ticket', 'ensemble'); ?></option>
            </select>
            
            <select id="es-filter-status" class="es-filter-select">
                <option value=""><?php _e('All Status', 'ensemble'); ?></option>
                <option value="pending" <?php selected($current_status, 'pending'); ?>><?php _e('Pending', 'ensemble'); ?></option>
                <option value="confirmed" <?php selected($current_status, 'confirmed'); ?>><?php _e('Confirmed', 'ensemble'); ?></option>
                <option value="checked_in" <?php selected($current_status, 'checked_in'); ?>><?php _e('Checked In', 'ensemble'); ?></option>
                <option value="cancelled" <?php selected($current_status, 'cancelled'); ?>><?php _e('Cancelled', 'ensemble'); ?></option>
                <option value="no_show" <?php selected($current_status, 'no_show'); ?>><?php _e('No Show', 'ensemble'); ?></option>
            </select>
        </div>
        
        <div class="es-filters-right">
            <div class="es-search-wrap">
                <input type="text" id="es-search-bookings" placeholder="<?php esc_attr_e('Search by name, email or code...', 'ensemble'); ?>" class="es-search-input">
                <span class="dashicons dashicons-search"></span>
            </div>
            
            <div class="es-export-dropdown">
                <button type="button" class="button es-export-btn">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export', 'ensemble'); ?>
                </button>
                <div class="es-export-menu">
                    <a href="#" data-format="csv"><?php _e('Export CSV', 'ensemble'); ?></a>
                    <a href="#" data-format="pdf"><?php _e('Export PDF', 'ensemble'); ?></a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="es-stats-row" id="es-booking-stats">
        <div class="es-stat-card">
            <span class="es-stat-icon" style="background: var(--es-info-light); color: var(--es-info);">
                <span class="dashicons dashicons-groups"></span>
            </span>
            <div class="es-stat-content">
                <span class="es-stat-value" id="stat-total">-</span>
                <span class="es-stat-label"><?php _e('Total Bookings', 'ensemble'); ?></span>
            </div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-icon" style="background: var(--es-success-light); color: var(--es-success);">
                <span class="dashicons dashicons-yes-alt"></span>
            </span>
            <div class="es-stat-content">
                <span class="es-stat-value" id="stat-confirmed">-</span>
                <span class="es-stat-label"><?php _e('Confirmed', 'ensemble'); ?></span>
            </div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-icon" style="background: var(--es-warning-light); color: var(--es-warning);">
                <span class="dashicons dashicons-clock"></span>
            </span>
            <div class="es-stat-content">
                <span class="es-stat-value" id="stat-pending">-</span>
                <span class="es-stat-label"><?php _e('Pending', 'ensemble'); ?></span>
            </div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-icon" style="background: rgba(156, 39, 176, 0.15); color: #9c27b0;">
                <span class="dashicons dashicons-yes"></span>
            </span>
            <div class="es-stat-content">
                <span class="es-stat-value" id="stat-checkedin">-</span>
                <span class="es-stat-label"><?php _e('Checked In', 'ensemble'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Bookings Table -->
    <div class="es-table-container">
        <table class="es-data-table" id="es-bookings-table">
            <thead>
                <tr>
                    <th class="es-col-checkbox">
                        <input type="checkbox" id="es-select-all">
                    </th>
                    <th class="es-col-code"><?php _e('Code', 'ensemble'); ?></th>
                    <th class="es-col-event"><?php _e('Event', 'ensemble'); ?></th>
                    <th class="es-col-type"><?php _e('Type', 'ensemble'); ?></th>
                    <th class="es-col-customer"><?php _e('Customer', 'ensemble'); ?></th>
                    <th class="es-col-guests"><?php _e('Guests', 'ensemble'); ?></th>
                    <th class="es-col-status"><?php _e('Status', 'ensemble'); ?></th>
                    <th class="es-col-payment"><?php _e('Payment', 'ensemble'); ?></th>
                    <th class="es-col-date"><?php _e('Created', 'ensemble'); ?></th>
                    <th class="es-col-actions"><?php _e('Actions', 'ensemble'); ?></th>
                </tr>
            </thead>
            <tbody id="es-bookings-tbody">
                <tr class="es-loading-row">
                    <td colspan="10">
                        <div class="es-loading">
                            <span class="spinner is-active"></span>
                            <?php _e('Loading bookings...', 'ensemble'); ?>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Empty State -->
    <div class="es-empty-state" id="es-empty-state" style="display: none;">
        <span class="dashicons dashicons-tickets-alt"></span>
        <h3><?php _e('No bookings found', 'ensemble'); ?></h3>
        <p><?php _e('There are no bookings matching your filters.', 'ensemble'); ?></p>
    </div>
    
    <!-- Pagination -->
    <div class="es-pagination" id="es-pagination" style="display: none;">
        <span class="es-pagination-info"></span>
        <div class="es-pagination-buttons">
            <button type="button" class="button es-page-prev" disabled>
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </button>
            <button type="button" class="button es-page-next">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
        </div>
    </div>
    
</div>

<!-- Add/Edit Booking Modal -->
<div class="es-modal" id="es-booking-modal" style="display: none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2 id="es-modal-title"><?php _e('Add Booking', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body">
            <form id="es-booking-form">
                <input type="hidden" name="booking_id" id="booking_id" value="">
                
                <div class="es-form-row">
                    <label for="booking_event_id"><?php _e('Event', 'ensemble'); ?> <span class="required">*</span></label>
                    <select name="event_id" id="booking_event_id" required>
                        <option value=""><?php _e('Select Event', 'ensemble'); ?></option>
                        <?php foreach ($events as $event): ?>
                        <option value="<?php echo esc_attr($event->ID); ?>">
                            <?php echo esc_html($event->post_title); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label for="booking_type"><?php _e('Type', 'ensemble'); ?></label>
                        <select name="booking_type" id="booking_type">
                            <option value="reservation"><?php _e('Reservation', 'ensemble'); ?></option>
                            <option value="ticket"><?php _e('Ticket', 'ensemble'); ?></option>
                        </select>
                    </div>
                    <div class="es-form-row">
                        <label for="booking_status"><?php _e('Status', 'ensemble'); ?></label>
                        <select name="status" id="booking_status">
                            <option value="confirmed"><?php _e('Confirmed', 'ensemble'); ?></option>
                            <option value="pending"><?php _e('Pending', 'ensemble'); ?></option>
                        </select>
                    </div>
                </div>
                
                <hr class="es-form-divider">
                
                <div class="es-form-row">
                    <label for="customer_name"><?php _e('Customer Name', 'ensemble'); ?> <span class="required">*</span></label>
                    <input type="text" name="customer_name" id="customer_name" required>
                </div>
                
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label for="customer_email"><?php _e('Email', 'ensemble'); ?> <span class="required">*</span></label>
                        <input type="email" name="customer_email" id="customer_email" required>
                    </div>
                    <div class="es-form-row">
                        <label for="customer_phone"><?php _e('Phone', 'ensemble'); ?></label>
                        <input type="tel" name="customer_phone" id="customer_phone">
                    </div>
                </div>
                
                <div class="es-form-row">
                    <label for="booking_guests"><?php _e('Number of Guests', 'ensemble'); ?></label>
                    <input type="number" name="guests" id="booking_guests" value="1" min="1" max="100">
                </div>
                
                <div class="es-form-row">
                    <label for="internal_notes"><?php _e('Internal Notes', 'ensemble'); ?></label>
                    <textarea name="internal_notes" id="internal_notes" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="button button-primary" id="es-save-booking">
                <?php _e('Save Booking', 'ensemble'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Booking Detail Modal -->
<div class="es-modal" id="es-detail-modal" style="display: none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content es-modal-content-wide">
        <div class="es-modal-header">
            <h2><?php _e('Booking Details', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body" id="es-detail-content">
            <!-- Filled by JavaScript -->
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel"><?php _e('Close', 'ensemble'); ?></button>
            <button type="button" class="button" id="es-resend-email">
                <span class="dashicons dashicons-email"></span>
                <?php _e('Resend Email', 'ensemble'); ?>
            </button>
            <button type="button" class="button" id="es-detail-cancel" style="color: #d63638;">
                <span class="dashicons dashicons-dismiss"></span>
                <?php _e('Cancel Booking', 'ensemble'); ?>
            </button>
            <button type="button" class="button button-secondary" id="es-detail-confirm">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Confirm', 'ensemble'); ?>
            </button>
            <button type="button" class="button button-primary" id="es-detail-checkin">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Check In', 'ensemble'); ?>
            </button>
        </div>
    </div>
</div>
