<?php
/**
 * Tickets Pro Admin - Central Ticket Management
 * 
 * Single source of truth for all ticket categories across events.
 * Filter by event, manage categories, view sales stats.
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Admin
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get filter parameters
$filter_event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
$filter_status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';
$filter_source = isset($_GET['source']) ? sanitize_key($_GET['source']) : 'all';

// Get settings
$settings = get_option('ensemble_tickets_pro_settings', array());
$currency = $settings['currency'] ?? 'EUR';
$currency_symbols = array(
    'EUR' => '€',
    'USD' => '$',
    'GBP' => '£',
    'CHF' => 'CHF',
);
$currency_symbol = $currency_symbols[$currency] ?? $currency;

// Get all events with ticket categories
global $wpdb;
$table = $wpdb->prefix . 'ensemble_ticket_categories';

// Build query for tickets
$where_clauses = array('event_id > 0'); // Exclude templates (event_id = 0)

if ($filter_event_id > 0) {
    $where_clauses[] = $wpdb->prepare('event_id = %d', $filter_event_id);
}

if ($filter_status !== 'all') {
    $where_clauses[] = $wpdb->prepare('status = %s', $filter_status);
}

if ($filter_source !== 'all') {
    $where_clauses[] = $wpdb->prepare('source = %s', $filter_source);
}

$where_sql = implode(' AND ', $where_clauses);
$categories = $wpdb->get_results("SELECT * FROM $table WHERE $where_sql ORDER BY event_id DESC, sort_order ASC, name ASC");

// Convert to objects
$tickets = array();
foreach ($categories as $row) {
    $tickets[] = new ES_Ticket_Category($row);
}

// Get events for dropdown filter
$events_with_tickets = $wpdb->get_results(
    "SELECT DISTINCT p.ID, p.post_title, p.post_date
     FROM {$wpdb->posts} p
     INNER JOIN $table tc ON tc.event_id = p.ID
     WHERE p.post_type = 'ensemble_event' AND p.post_status IN ('publish', 'draft', 'future')
     AND tc.event_id > 0
     ORDER BY p.post_date DESC"
);

// Calculate statistics
$total_tickets = count($tickets);
$total_sold = array_sum(array_column($tickets, 'sold'));
$total_revenue = 0;
foreach ($tickets as $ticket) {
    $total_revenue += $ticket->price * $ticket->sold;
}
$total_available = 0;
$unlimited_count = 0;
foreach ($tickets as $ticket) {
    if ($ticket->capacity === null) {
        $unlimited_count++;
    } else {
        $total_available += max(0, $ticket->capacity - $ticket->sold);
    }
}

// Source labels
$source_labels = array(
    'manual'     => __('Manual', 'ensemble'),
    'wizard'     => __('Event Wizard', 'ensemble'),
    'floor_plan' => __('Floor Plan', 'ensemble'),
    'import'     => __('Imported', 'ensemble'),
);
?>

<div class="es-admin-section">
    
    <!-- Section Header with Stats -->
    <div class="es-section-header">
        <div class="es-section-title">
            <h2><?php _e('All Ticket Categories', 'ensemble'); ?></h2>
            <p class="description">
                <?php _e('Central management for all ticket categories across your events.', 'ensemble'); ?>
            </p>
        </div>
        <div class="es-section-actions">
            <button type="button" class="button button-primary" id="es-add-ticket-category">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Add Category', 'ensemble'); ?>
            </button>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="es-stats-grid es-stats-grid-4">
        <div class="es-stat-card">
            <span class="es-stat-icon dashicons dashicons-tickets-alt"></span>
            <div class="es-stat-content">
                <span class="es-stat-value"><?php echo number_format($total_tickets); ?></span>
                <span class="es-stat-label"><?php _e('Categories', 'ensemble'); ?></span>
            </div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-icon dashicons dashicons-cart"></span>
            <div class="es-stat-content">
                <span class="es-stat-value"><?php echo number_format($total_sold); ?></span>
                <span class="es-stat-label"><?php _e('Sold', 'ensemble'); ?></span>
            </div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-icon dashicons dashicons-groups"></span>
            <div class="es-stat-content">
                <span class="es-stat-value">
                    <?php 
                    if ($unlimited_count > 0) {
                        echo number_format($total_available) . '+';
                    } else {
                        echo number_format($total_available);
                    }
                    ?>
                </span>
                <span class="es-stat-label"><?php _e('Available', 'ensemble'); ?></span>
            </div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-icon dashicons dashicons-money-alt"></span>
            <div class="es-stat-content">
                <span class="es-stat-value"><?php echo esc_html($currency_symbol . number_format($total_revenue, 2, ',', '.')); ?></span>
                <span class="es-stat-label"><?php _e('Revenue', 'ensemble'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="es-filter-bar">
        <form method="get" class="es-filters">
            <input type="hidden" name="page" value="ensemble-tickets-pro">
            <input type="hidden" name="tab" value="tickets">
            
            <!-- Event Filter -->
            <div class="es-filter-group">
                <label for="filter-event"><?php _e('Event', 'ensemble'); ?></label>
                <select name="event_id" id="filter-event" class="es-filter-select">
                    <option value="0"><?php _e('All Events', 'ensemble'); ?></option>
                    <?php foreach ($events_with_tickets as $event): ?>
                    <option value="<?php echo esc_attr($event->ID); ?>" <?php selected($filter_event_id, $event->ID); ?>>
                        <?php echo esc_html($event->post_title); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Status Filter -->
            <div class="es-filter-group">
                <label for="filter-status"><?php _e('Status', 'ensemble'); ?></label>
                <select name="status" id="filter-status" class="es-filter-select">
                    <option value="all"><?php _e('All Status', 'ensemble'); ?></option>
                    <option value="active" <?php selected($filter_status, 'active'); ?>><?php _e('Active', 'ensemble'); ?></option>
                    <option value="inactive" <?php selected($filter_status, 'inactive'); ?>><?php _e('Inactive', 'ensemble'); ?></option>
                </select>
            </div>
            
            <!-- Source Filter -->
            <div class="es-filter-group">
                <label for="filter-source"><?php _e('Source', 'ensemble'); ?></label>
                <select name="source" id="filter-source" class="es-filter-select">
                    <option value="all"><?php _e('All Sources', 'ensemble'); ?></option>
                    <?php foreach ($source_labels as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($filter_source, $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="button"><?php _e('Filter', 'ensemble'); ?></button>
            
            <?php if ($filter_event_id > 0 || $filter_status !== 'all' || $filter_source !== 'all'): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ensemble-tickets-pro&tab=tickets')); ?>" class="button es-clear-filters">
                <?php _e('Clear', 'ensemble'); ?>
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Tickets Table -->
    <?php if (empty($tickets)): ?>
    <div class="es-empty-state">
        <span class="dashicons dashicons-tickets-alt"></span>
        <h3><?php _e('No Ticket Categories Found', 'ensemble'); ?></h3>
        <p>
            <?php 
            if ($filter_event_id > 0) {
                _e('No categories match your filters. Try adjusting the filters or add a new category.', 'ensemble');
            } else {
                _e('Create ticket categories in the Event Wizard or add them here.', 'ensemble');
            }
            ?>
        </p>
        <button type="button" class="button button-primary" id="es-add-ticket-category-empty">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php _e('Add First Category', 'ensemble'); ?>
        </button>
    </div>
    <?php else: ?>
    <div class="es-table-wrap">
        <table class="es-table es-table-striped">
            <thead>
                <tr>
                    <th class="es-col-name"><?php _e('Category', 'ensemble'); ?></th>
                    <th class="es-col-event"><?php _e('Event', 'ensemble'); ?></th>
                    <th class="es-col-price"><?php _e('Price', 'ensemble'); ?></th>
                    <th class="es-col-capacity"><?php _e('Capacity', 'ensemble'); ?></th>
                    <th class="es-col-sold"><?php _e('Sold', 'ensemble'); ?></th>
                    <th class="es-col-source"><?php _e('Source', 'ensemble'); ?></th>
                    <th class="es-col-status"><?php _e('Status', 'ensemble'); ?></th>
                    <th class="es-col-actions"><?php _e('Actions', 'ensemble'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $current_event_id = 0;
                foreach ($tickets as $ticket): 
                    // Event grouping separator
                    if ($filter_event_id === 0 && $ticket->event_id !== $current_event_id) {
                        $current_event_id = $ticket->event_id;
                        $event_title = get_the_title($current_event_id);
                        ?>
                        <tr class="es-table-group-header">
                            <td colspan="8">
                                <strong>
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo esc_html($event_title); ?>
                                </strong>
                                <a href="<?php echo esc_url(get_edit_post_link($current_event_id)); ?>" class="es-event-link" target="_blank">
                                    <?php _e('Edit Event', 'ensemble'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                        </tr>
                        <?php
                    }
                    
                    $availability = $ticket->get_availability_status();
                    $available_count = $ticket->get_available_count();
                    $source = $ticket->source ?? 'manual';
                    $source_label = $source_labels[$source] ?? $source;
                ?>
                <tr class="es-ticket-row" data-id="<?php echo esc_attr($ticket->id); ?>">
                    <td class="es-col-name">
                        <strong><?php echo esc_html($ticket->name); ?></strong>
                        <?php if ($ticket->description): ?>
                        <span class="es-row-description"><?php echo esc_html(wp_trim_words($ticket->description, 10)); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($ticket->floor_plan_zone): ?>
                        <span class="es-badge es-badge-info es-badge-sm">
                            <span class="dashicons dashicons-layout"></span>
                            <?php echo esc_html($ticket->floor_plan_zone); ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="es-col-event">
                        <?php if ($filter_event_id > 0): ?>
                        <a href="<?php echo esc_url(get_edit_post_link($ticket->event_id)); ?>" target="_blank">
                            <?php echo esc_html(get_the_title($ticket->event_id)); ?>
                        </a>
                        <?php else: ?>
                        <span class="es-text-muted">â€”</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="es-col-price">
                        <span class="es-price"><?php echo esc_html($ticket->get_formatted_price()); ?></span>
                    </td>
                    
                    <td class="es-col-capacity">
                        <?php if ($ticket->capacity === null): ?>
                        <span class="es-text-muted"><?php _e('Unlimited', 'ensemble'); ?></span>
                        <?php else: ?>
                        <?php echo number_format($ticket->capacity); ?>
                        <?php endif; ?>
                    </td>
                    
                    <td class="es-col-sold">
                        <span class="es-sold-count"><?php echo number_format($ticket->sold); ?></span>
                        <?php if ($available_count !== null && $available_count > 0): ?>
                        <span class="es-available-count">(<?php printf(__('%d left', 'ensemble'), $available_count); ?>)</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="es-col-source">
                        <span class="es-badge es-badge-<?php echo esc_attr($source); ?>">
                            <?php echo esc_html($source_label); ?>
                        </span>
                    </td>
                    
                    <td class="es-col-status">
                        <?php if ($ticket->status === 'active'): ?>
                            <?php if ($availability === 'sold_out'): ?>
                            <span class="es-status es-status-danger"><?php _e('Sold Out', 'ensemble'); ?></span>
                            <?php elseif ($availability === 'limited'): ?>
                            <span class="es-status es-status-warning"><?php _e('Limited', 'ensemble'); ?></span>
                            <?php elseif ($availability === 'not_on_sale'): ?>
                            <span class="es-status es-status-muted"><?php _e('Not on Sale', 'ensemble'); ?></span>
                            <?php else: ?>
                            <span class="es-status es-status-success"><?php _e('Active', 'ensemble'); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                        <span class="es-status es-status-inactive"><?php _e('Inactive', 'ensemble'); ?></span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="es-col-actions">
                        <div class="es-actions">
                            <button type="button" class="button es-action-btn es-edit-category" data-id="<?php echo esc_attr($ticket->id); ?>" title="<?php esc_attr_e('Edit', 'ensemble'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button es-action-btn es-duplicate-category" data-id="<?php echo esc_attr($ticket->id); ?>" title="<?php esc_attr_e('Duplicate', 'ensemble'); ?>">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                            <button type="button" class="button es-action-btn es-action-danger es-delete-category" data-id="<?php echo esc_attr($ticket->id); ?>" title="<?php esc_attr_e('Delete', 'ensemble'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
</div>

<!-- Add/Edit Category Modal -->
<div class="es-modal" id="es-category-modal" style="display: none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2 id="es-category-modal-title"><?php _e('Add Ticket Category', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body">
            <form id="es-category-form">
                <input type="hidden" name="category_id" id="category_id" value="">
                <?php wp_nonce_field('es_save_ticket_category', 'category_nonce'); ?>
                
                <!-- Event Selection -->
                <div class="es-form-row">
                    <label for="category_event_id"><?php _e('Event', 'ensemble'); ?> <span class="required">*</span></label>
                    <select name="event_id" id="category_event_id" required>
                        <option value=""><?php _e('Select Event...', 'ensemble'); ?></option>
                        <?php
                        // Get all events
                        $all_events = get_posts(array(
                            'post_type'      => 'ensemble_event',
                            'post_status'    => array('publish', 'draft', 'future'),
                            'posts_per_page' => -1,
                            'orderby'        => 'date',
                            'order'          => 'DESC',
                        ));
                        foreach ($all_events as $event):
                        ?>
                        <option value="<?php echo esc_attr($event->ID); ?>" <?php selected($filter_event_id, $event->ID); ?>>
                            <?php echo esc_html($event->post_title); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Basic Info -->
                <div class="es-form-row">
                    <label for="category_name"><?php _e('Category Name', 'ensemble'); ?> <span class="required">*</span></label>
                    <input type="text" name="name" id="category_name" required placeholder="<?php esc_attr_e('e.g. Standard, VIP, Early Bird', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-row">
                    <label for="category_description"><?php _e('Description', 'ensemble'); ?></label>
                    <textarea name="description" id="category_description" rows="2" placeholder="<?php esc_attr_e('Short description shown to customers', 'ensemble'); ?>"></textarea>
                </div>
                
                <!-- Price & Capacity -->
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label for="category_price"><?php _e('Price', 'ensemble'); ?> (<?php echo esc_html($currency); ?>)</label>
                        <input type="number" name="price" id="category_price" step="0.01" min="0" value="0.00">
                    </div>
                    <div class="es-form-row">
                        <label for="category_capacity"><?php _e('Capacity', 'ensemble'); ?></label>
                        <input type="number" name="capacity" id="category_capacity" min="0" placeholder="<?php esc_attr_e('Leave empty for unlimited', 'ensemble'); ?>">
                    </div>
                </div>
                
                <!-- Quantity Limits -->
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label for="category_min_quantity"><?php _e('Min per Order', 'ensemble'); ?></label>
                        <input type="number" name="min_quantity" id="category_min_quantity" min="1" value="1">
                    </div>
                    <div class="es-form-row">
                        <label for="category_max_quantity"><?php _e('Max per Order', 'ensemble'); ?></label>
                        <input type="number" name="max_quantity" id="category_max_quantity" min="1" value="10">
                    </div>
                </div>
                
                <!-- Floor Plan Zone (optional) -->
                <div class="es-form-row" id="floor-plan-zone-row" style="display: none;">
                    <label for="category_floor_plan_zone"><?php _e('Floor Plan Zone', 'ensemble'); ?></label>
                    <select name="floor_plan_zone" id="category_floor_plan_zone">
                        <option value=""><?php _e('No Zone (General Admission)', 'ensemble'); ?></option>
                    </select>
                    <p class="description"><?php _e('Link this category to a specific floor plan zone.', 'ensemble'); ?></p>
                </div>
                
                <!-- Sale Period -->
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label for="category_sale_start"><?php _e('Sale Start', 'ensemble'); ?></label>
                        <input type="datetime-local" name="sale_start" id="category_sale_start">
                    </div>
                    <div class="es-form-row">
                        <label for="category_sale_end"><?php _e('Sale End', 'ensemble'); ?></label>
                        <input type="datetime-local" name="sale_end" id="category_sale_end">
                    </div>
                </div>
                <p class="description"><?php _e('Optional: Limit when this ticket is available for purchase.', 'ensemble'); ?></p>
                
                <!-- Status -->
                <div class="es-form-row">
                    <label><?php _e('Status', 'ensemble'); ?></label>
                    <label class="es-toggle">
                        <input type="checkbox" name="status" id="category_status" value="active" checked>
                        <span class="es-toggle-track"></span>
                        <span class="es-toggle-label"><?php _e('Active', 'ensemble'); ?></span>
                    </label>
                </div>
                
            </form>
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="button button-primary" id="es-save-category">
                <?php _e('Save Category', 'ensemble'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    var $modal = $('#es-category-modal');
    var $form = $('#es-category-form');
    
    // Open modal for new category
    $('#es-add-ticket-category, #es-add-ticket-category-empty').on('click', function() {
        $form[0].reset();
        $('#category_id').val('');
        $('#category_status').prop('checked', true);
        $('#es-category-modal-title').text('<?php _e('Add Ticket Category', 'ensemble'); ?>');
        $modal.fadeIn(200);
    });
    
    // Edit category
    $('.es-edit-category').on('click', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_ticket_category',
                category_id: id,
                nonce: esTicketsPro.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#category_id').val(data.id);
                    $('#category_event_id').val(data.event_id);
                    $('#category_name').val(data.name);
                    $('#category_description').val(data.description);
                    $('#category_price').val(data.price);
                    $('#category_capacity').val(data.capacity || '');
                    $('#category_min_quantity').val(data.min_quantity);
                    $('#category_max_quantity').val(data.max_quantity);
                    $('#category_floor_plan_zone').val(data.floor_plan_zone || '');
                    $('#category_sale_start').val(data.sale_start ? data.sale_start.replace(' ', 'T').substring(0, 16) : '');
                    $('#category_sale_end').val(data.sale_end ? data.sale_end.replace(' ', 'T').substring(0, 16) : '');
                    $('#category_status').prop('checked', data.status === 'active');
                    $('#es-category-modal-title').text('<?php _e('Edit Ticket Category', 'ensemble'); ?>');
                    $modal.fadeIn(200);
                }
            }
        });
    });
    
    // Duplicate category
    $('.es-duplicate-category').on('click', function() {
        var id = $(this).data('id');
        
        if (!confirm('<?php _e('Create a copy of this category?', 'ensemble'); ?>')) {
            return;
        }
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_duplicate_ticket_category',
                category_id: id,
                nonce: esTicketsPro.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error');
                }
            }
        });
    });
    
    // Delete category
    $('.es-delete-category').on('click', function() {
        var id = $(this).data('id');
        var $row = $(this).closest('.es-ticket-row');
        
        if (!confirm('<?php _e('Are you sure you want to delete this category? This cannot be undone.', 'ensemble'); ?>')) {
            return;
        }
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_delete_ticket_category',
                category_id: id,
                nonce: esTicketsPro.nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // Check if no categories left
                        if ($('.es-ticket-row').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.data.message || 'Error');
                }
            }
        });
    });
    
    // Save category
    $('#es-save-category').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('Saving...', 'ensemble'); ?>');
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: $form.serialize() + '&action=es_save_ticket_category_central',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error');
                    $btn.prop('disabled', false).text('<?php _e('Save Category', 'ensemble'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred.', 'ensemble'); ?>');
                $btn.prop('disabled', false).text('<?php _e('Save Category', 'ensemble'); ?>');
            }
        });
    });
    
    // Close modal
    $('.es-modal-close, .es-modal-cancel, .es-modal-overlay').on('click', function() {
        $modal.fadeOut(200);
    });
    
    // ESC key closes modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $modal.is(':visible')) {
            $modal.fadeOut(200);
        }
    });
    
    // Event filter auto-submit
    $('#filter-event, #filter-status, #filter-source').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>

<style>
/* Stats Grid - 4 columns */
.es-stats-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin: 20px 0;
}

.es-stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: var(--es-surface);
    border: 1px solid var(--es-border);
    border-radius: var(--es-radius-lg);
    padding: 20px;
}

.es-stat-icon {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: var(--es-primary);
    opacity: 0.8;
}

.es-stat-content {
    display: flex;
    flex-direction: column;
}

.es-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--es-text);
}

.es-stat-label {
    font-size: 12px;
    color: var(--es-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filter Bar */
.es-filter-bar {
    background: var(--es-surface);
    border: 1px solid var(--es-border);
    border-radius: var(--es-radius-lg);
    padding: 16px 20px;
    margin-bottom: 20px;
}

.es-filters {
    display: flex;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
}

.es-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.es-filter-group label {
    font-size: 11px;
    color: var(--es-text-muted);
    text-transform: uppercase;
    font-weight: 500;
}

.es-filter-select {
    min-width: 180px;
    padding: 8px 12px;
    border: 1px solid var(--es-border);
    border-radius: var(--es-radius);
    background: var(--es-surface-secondary);
    color: var(--es-text);
}

.es-clear-filters {
    color: var(--es-text-muted);
}

/* Table Enhancements */
.es-table-group-header {
    background: var(--es-surface-secondary) !important;
}

.es-table-group-header td {
    padding: 12px 16px !important;
    border-bottom: 2px solid var(--es-border);
}

.es-table-group-header strong {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--es-text);
}

.es-event-link {
    float: right;
    font-size: 12px;
    color: var(--es-primary);
    text-decoration: none;
}

.es-event-link:hover {
    text-decoration: underline;
}

.es-event-link .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
    vertical-align: middle;
}

/* Row Details */
.es-row-description {
    display: block;
    font-size: 12px;
    color: var(--es-text-muted);
    margin-top: 2px;
}

.es-col-name .es-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
}

/* Price Column */
.es-price {
    font-weight: 600;
    color: var(--es-success);
}

/* Sold Column */
.es-sold-count {
    font-weight: 600;
}

.es-available-count {
    font-size: 11px;
    color: var(--es-text-muted);
}

/* Source Badges */
.es-badge-manual { background: var(--es-info-light); color: var(--es-info); }
.es-badge-wizard { background: var(--es-success-light); color: var(--es-success); }
.es-badge-floor_plan { background: var(--es-warning-light); color: var(--es-warning); }
.es-badge-import { background: var(--es-surface-secondary); color: var(--es-text-muted); }

.es-badge-sm {
    font-size: 10px;
    padding: 2px 6px;
}

/* Actions */
.es-actions {
    display: flex;
    gap: 4px;
}

.es-action-btn {
    padding: 4px 8px !important;
    min-height: 28px !important;
}

.es-action-btn .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.es-action-danger:hover {
    color: var(--es-danger);
    border-color: var(--es-danger);
}

/* Status */
.es-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: var(--es-radius-pill);
    font-size: 11px;
    font-weight: 500;
}

.es-status-success { background: var(--es-success-light); color: var(--es-success); }
.es-status-warning { background: var(--es-warning-light); color: var(--es-warning); }
.es-status-danger { background: var(--es-danger-light); color: var(--es-danger); }
.es-status-muted { background: var(--es-surface-secondary); color: var(--es-text-muted); }
.es-status-inactive { background: var(--es-surface-secondary); color: var(--es-text-muted); }

/* Responsive */
@media (max-width: 1200px) {
    .es-stats-grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .es-stats-grid-4 {
        grid-template-columns: 1fr;
    }
    
    .es-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .es-filter-group {
        width: 100%;
    }
    
    .es-filter-select {
        width: 100%;
    }
}
</style>
