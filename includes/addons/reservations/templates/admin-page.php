<?php
/**
 * Reservations Pro - Admin Page Template
 * 
 * Modern Card-Based Design System
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get filter values
$event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
$status_filter = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Get post type
$post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';

// Get ALL events
$all_events = get_posts(array(
    'post_type'      => $post_type,
    'post_status'    => array('publish', 'draft', 'future'),
    'posts_per_page' => 100,
    'orderby'        => 'date',
    'order'          => 'DESC',
));

// If no event selected, use first one
if (!$event_id && !empty($all_events)) {
    $event_id = $all_events[0]->ID;
}

// Get reservations and stats
$reservations = array();
$stats = array(
    'total'      => 0,
    'pending'    => 0,
    'confirmed'  => 0,
    'checked_in' => 0,
    'cancelled'  => 0,
    'guests'     => 0,
);

if ($event_id) {
    $reservations = $addon->get_event_reservations($event_id, array(
        'status' => $status_filter,
        'type'   => $type_filter,
        'search' => $search,
    ));
    
    // Calculate stats
    $all_reservations = $addon->get_event_reservations($event_id);
    foreach ($all_reservations as $res) {
        $stats['total']++;
        $stats['guests'] += $res->guests;
        if (isset($stats[$res->status])) {
            $stats[$res->status]++;
        }
    }
}

$current_event = $event_id ? get_post($event_id) : null;
$reservation_enabled = $event_id ? get_post_meta($event_id, '_reservation_enabled', true) : false;
$capacity = $event_id ? get_post_meta($event_id, '_reservation_capacity', true) : 0;
?>
<div class="wrap es-manager-wrap es-reservations-wrap">
    
    <!-- Page Header -->
    <div class="es-page-header">
        <div class="es-page-header-content">
            <h1>
                <span class="es-page-icon">
                    <span class="dashicons dashicons-clipboard"></span>
                </span>
                <?php _e('Reservierungen', 'ensemble'); ?>
            </h1>
            <p class="es-page-description"><?php _e('Manage guestlists, table reservations and VIP bookings', 'ensemble'); ?></p>
        </div>
        
        <?php if ($event_id && $reservation_enabled): ?>
        <div class="es-page-actions">
            <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=es_export_reservations&event_id=' . $event_id . '&format=csv'), 'ensemble_reservations_admin', 'nonce'); ?>" 
               class="button">
                <span class="dashicons dashicons-download"></span>
                <?php _e('CSV Export', 'ensemble'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="es-reservations-content">
        
        <!-- Event Selector Card -->
        <div class="es-form-card">
            <div class="es-form-card-header">
                <div class="es-form-card-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="es-form-card-title">
                    <h3><?php _e('Event auswählen', 'ensemble'); ?></h3>
                    <p class="es-form-card-desc"><?php _e('Select an event to manage reservations', 'ensemble'); ?></p>
                </div>
            </div>
            
            <div class="es-form-card-body">
                <?php if (empty($all_events)): ?>
                    <div class="es-inline-notice es-notice-warning">
                        <span class="dashicons dashicons-warning"></span>
                        <span><?php _e('Keine Events vorhanden. Erstelle zuerst ein Event.', 'ensemble'); ?></span>
                        <a href="<?php echo admin_url('admin.php?page=ensemble-wizard'); ?>" class="button button-small">
                            <?php _e('Event erstellen', 'ensemble'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="es-event-selector-grid">
                        <div class="es-form-row">
                            <label for="es-event-selector"><?php _e('Event', 'ensemble'); ?></label>
                            <select id="es-event-selector" class="es-select-large">
                                <?php foreach ($all_events as $event): 
                                    $event_date = get_post_meta($event->ID, 'es_event_start_date', true);
                                    if (empty($event_date)) {
                                        $event_date = get_post_meta($event->ID, '_event_start_date', true);
                                    }
                                    $has_reservations = get_post_meta($event->ID, '_reservation_enabled', true);
                                    $res_count = count($addon->get_event_reservations($event->ID));
                                ?>
                                <option value="<?php echo $event->ID; ?>" <?php selected($event_id, $event->ID); ?>>
                                    <?php echo esc_html($event->post_title); ?>
                                    <?php if ($event_date): ?>
                                        (<?php echo date_i18n('d.m.Y', strtotime($event_date)); ?>)
                                    <?php endif; ?>
                                    <?php if ($res_count > 0): ?>
                                        - <?php echo $res_count; ?> <?php _e('Res.', 'ensemble'); ?>
                                    <?php endif; ?>
                                    <?php if (!$has_reservations): ?>
                                        [<?php _e('inaktiv', 'ensemble'); ?>]
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if ($current_event && !$reservation_enabled): ?>
                            <div class="es-inline-notice es-notice-warning">
                                <span class="dashicons dashicons-warning"></span>
                                <span><?php printf(__('Reservations are not enabled for "%s"', 'ensemble'), esc_html($current_event->post_title)); ?></span>
                                <a href="<?php echo get_edit_post_link($event_id); ?>" class="button button-small button-primary">
                                    <?php _e('Aktivieren', 'ensemble'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($event_id && $reservation_enabled): ?>
        
        <!-- Stats Cards -->
        <div class="es-stats-cards-grid">
            <div class="es-stat-card-modern">
                <div class="es-stat-card-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                    <span class="dashicons dashicons-clipboard"></span>
                </div>
                <div class="es-stat-card-content">
                    <span class="es-stat-number"><?php echo $stats['total']; ?></span>
                    <span class="es-stat-label"><?php _e('Reservierungen', 'ensemble'); ?></span>
                </div>
            </div>
            
            <div class="es-stat-card-modern">
                <div class="es-stat-card-icon" style="background: linear-gradient(135deg, #3b82f6, #06b6d4);">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="es-stat-card-content">
                    <span class="es-stat-number"><?php echo $stats['guests']; ?></span>
                    <span class="es-stat-label"><?php _e('Gäste', 'ensemble'); ?></span>
                </div>
            </div>
            
            <div class="es-stat-card-modern">
                <div class="es-stat-card-icon" style="background: linear-gradient(135deg, #f59e0b, #f97316);">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="es-stat-card-content">
                    <span class="es-stat-number"><?php echo $stats['pending']; ?></span>
                    <span class="es-stat-label"><?php _e('Ausstehend', 'ensemble'); ?></span>
                </div>
            </div>
            
            <div class="es-stat-card-modern">
                <div class="es-stat-card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="es-stat-card-content">
                    <span class="es-stat-number"><?php echo $stats['confirmed']; ?></span>
                    <span class="es-stat-label"><?php _e('Bestätigt', 'ensemble'); ?></span>
                </div>
            </div>
            
            <div class="es-stat-card-modern">
                <div class="es-stat-card-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                    <span class="dashicons dashicons-location"></span>
                </div>
                <div class="es-stat-card-content">
                    <span class="es-stat-number"><?php echo $stats['checked_in']; ?></span>
                    <span class="es-stat-label"><?php _e('Eingecheckt', 'ensemble'); ?></span>
                </div>
            </div>
        </div>
        
        <?php if ($capacity): 
            $percentage = min(100, round(($stats['guests'] / $capacity) * 100));
            $is_warning = $percentage > 90;
        ?>
        <!-- Capacity Progress -->
        <div class="es-form-card es-capacity-card">
            <div class="es-capacity-info">
                <div class="es-capacity-text">
                    <span class="es-capacity-title"><?php _e('Kapazität', 'ensemble'); ?></span>
                    <span class="es-capacity-numbers">
                        <strong><?php echo $stats['guests']; ?></strong> / <?php echo $capacity; ?> <?php _e('Gäste', 'ensemble'); ?>
                    </span>
                </div>
                <span class="es-capacity-percentage <?php echo $is_warning ? 'es-warning' : ''; ?>">
                    <?php echo $percentage; ?>%
                </span>
            </div>
            <div class="es-capacity-bar-wrapper">
                <div class="es-capacity-bar-fill <?php echo $is_warning ? 'es-warning' : ''; ?>" style="width: <?php echo $percentage; ?>%;"></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Reservations List Card -->
        <div class="es-form-card">
            <div class="es-form-card-header">
                <div class="es-form-card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <span class="dashicons dashicons-list-view"></span>
                </div>
                <div class="es-form-card-title">
                    <h3><?php _e('Reservierungsliste', 'ensemble'); ?></h3>
                    <p class="es-form-card-desc"><?php printf(__('%d reservations for this event', 'ensemble'), count($reservations)); ?></p>
                </div>
            </div>
            
            <div class="es-form-card-body">
                <!-- Filter Toolbar -->
                <div class="es-filter-toolbar">
                    <form method="get" class="es-filter-form-inline">
                        <input type="hidden" name="page" value="ensemble-reservations">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        
                        <div class="es-filter-group">
                            <select name="status" class="es-filter-select">
                                <option value=""><?php _e('Alle Status', 'ensemble'); ?></option>
                                <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Ausstehend', 'ensemble'); ?></option>
                                <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php _e('Bestätigt', 'ensemble'); ?></option>
                                <option value="checked_in" <?php selected($status_filter, 'checked_in'); ?>><?php _e('Eingecheckt', 'ensemble'); ?></option>
                                <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php _e('Storniert', 'ensemble'); ?></option>
                            </select>
                            
                            <select name="type" class="es-filter-select">
                                <option value=""><?php _e('Alle Typen', 'ensemble'); ?></option>
                                <option value="guestlist" <?php selected($type_filter, 'guestlist'); ?>><?php _e('Guestlist', 'ensemble'); ?></option>
                                <option value="table" <?php selected($type_filter, 'table'); ?>><?php _e('Tisch', 'ensemble'); ?></option>
                                <option value="vip" <?php selected($type_filter, 'vip'); ?>><?php _e('VIP', 'ensemble'); ?></option>
                            </select>
                        </div>
                        
                        <div class="es-search-group">
                            <div class="es-search-input-wrap">
                                <span class="dashicons dashicons-search"></span>
                                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                                       placeholder="<?php esc_attr_e('Name, E-Mail oder Code...', 'ensemble'); ?>">
                            </div>
                            <button type="submit" class="button"><?php _e('Suchen', 'ensemble'); ?></button>
                        </div>
                        
                        <?php if ($status_filter || $type_filter || $search): ?>
                        <a href="<?php echo admin_url('admin.php?page=ensemble-reservations&event_id=' . $event_id); ?>" class="es-filter-reset">
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php _e('Filter zurücksetzen', 'ensemble'); ?>
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Reservations Table -->
                <?php if (empty($reservations)): ?>
                <div class="es-empty-state-inline">
                    <span class="dashicons dashicons-format-aside"></span>
                    <div>
                        <strong><?php _e('Keine Reservierungen gefunden', 'ensemble'); ?></strong>
                        <p><?php _e('Für die aktuellen Filterkriterien gibt es keine Reservierungen.', 'ensemble'); ?></p>
                    </div>
                </div>
                <?php else: ?>
                <div class="es-table-responsive">
                    <table class="es-reservations-table">
                        <thead>
                            <tr>
                                <th class="es-col-status"><?php _e('Status', 'ensemble'); ?></th>
                                <th class="es-col-name"><?php _e('Name', 'ensemble'); ?></th>
                                <th class="es-col-contact"><?php _e('Kontakt', 'ensemble'); ?></th>
                                <th class="es-col-guests"><?php _e('Gäste', 'ensemble'); ?></th>
                                <th class="es-col-type"><?php _e('Typ', 'ensemble'); ?></th>
                                <th class="es-col-code"><?php _e('Code', 'ensemble'); ?></th>
                                <th class="es-col-date"><?php _e('Erstellt', 'ensemble'); ?></th>
                                <th class="es-col-actions"><?php _e('Aktionen', 'ensemble'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $res): ?>
                            <tr data-id="<?php echo $res->id; ?>" class="<?php echo $res->status === 'cancelled' ? 'es-row-cancelled' : ''; ?>">
                                <td class="es-col-status">
                                    <span class="es-status-pill es-status-<?php echo $res->status; ?>">
                                        <?php echo $addon->get_status_label($res->status); ?>
                                    </span>
                                </td>
                                <td class="es-col-name">
                                    <div class="es-name-cell">
                                        <strong><?php echo esc_html($res->name); ?></strong>
                                        <?php if ($res->notes): ?>
                                        <span class="es-has-notes" title="<?php echo esc_attr($res->notes); ?>">
                                            <span class="dashicons dashicons-admin-comments"></span>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="es-col-contact">
                                    <div class="es-contact-cell">
                                        <a href="mailto:<?php echo esc_attr($res->email); ?>" class="es-email-link">
                                            <?php echo esc_html($res->email); ?>
                                        </a>
                                        <?php if ($res->phone): ?>
                                        <span class="es-phone"><?php echo esc_html($res->phone); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="es-col-guests">
                                    <span class="es-guest-count"><?php echo $res->guests; ?></span>
                                </td>
                                <td class="es-col-type">
                                    <span class="es-type-pill es-type-<?php echo $res->type; ?>">
                                        <?php echo $addon->get_type_label($res->type); ?>
                                    </span>
                                    <?php if ($res->table_number): ?>
                                    <span class="es-table-number"><?php printf(__('Tisch %s', 'ensemble'), $res->table_number); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="es-col-code">
                                    <code class="es-confirmation-code"><?php echo esc_html($res->confirmation_code); ?></code>
                                </td>
                                <td class="es-col-date">
                                    <div class="es-date-cell">
                                        <span class="es-date"><?php echo date_i18n('d.m.Y', strtotime($res->created_at)); ?></span>
                                        <span class="es-time"><?php echo date_i18n('H:i', strtotime($res->created_at)); ?></span>
                                        <?php if ($res->status === 'checked_in' && $res->checked_in_at): ?>
                                        <span class="es-checkin-time">✓ <?php echo date_i18n('H:i', strtotime($res->checked_in_at)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="es-col-actions">
                                    <div class="es-action-buttons">
                                        <?php if ($res->status !== 'checked_in' && $res->status !== 'cancelled'): ?>
                                        <button type="button" class="es-action-btn es-action-checkin" data-id="<?php echo $res->id; ?>" title="<?php esc_attr_e('Einchecken', 'ensemble'); ?>">
                                            <span class="dashicons dashicons-yes"></span>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($res->status === 'pending'): ?>
                                        <button type="button" class="es-action-btn es-action-confirm" data-id="<?php echo $res->id; ?>" title="<?php esc_attr_e('Bestätigen', 'ensemble'); ?>">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($res->status !== 'cancelled'): ?>
                                        <button type="button" class="es-action-btn es-action-cancel" data-id="<?php echo $res->id; ?>" title="<?php esc_attr_e('Stornieren', 'ensemble'); ?>">
                                            <span class="dashicons dashicons-dismiss"></span>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="es-action-btn es-action-delete" data-id="<?php echo $res->id; ?>" title="<?php esc_attr_e('Löschen', 'ensemble'); ?>">
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
        </div>
        
        <?php endif; ?>
        
    </div>
    
</div>

<script>
jQuery(function($) {
    // Event selector
    $('#es-event-selector').on('change', function() {
        var eventId = $(this).val();
        if (eventId) {
            window.location.href = '<?php echo admin_url('admin.php?page=ensemble-reservations&event_id='); ?>' + eventId;
        }
    });
    
    // Check-in
    $(document).on('click', '.es-action-checkin', function() {
        var id = $(this).data('id');
        var $row = $('tr[data-id="' + id + '"]');
        
        if (!confirm('<?php echo esc_js(__('Gast einchecken?', 'ensemble')); ?>')) return;
        
        $.post(ensembleReservationsAdmin.ajaxUrl, {
            action: 'es_checkin_reservation',
            nonce: ensembleReservationsAdmin.nonce,
            reservation_id: id
        }, function(response) {
            if (response.success) {
                $row.find('.es-status-pill').removeClass('es-status-pending es-status-confirmed').addClass('es-status-checked_in').text('<?php _e('Eingecheckt', 'ensemble'); ?>');
                $row.find('.es-action-checkin, .es-action-confirm, .es-action-cancel').remove();
            } else {
                alert(response.data.message);
            }
        });
    });
    
    // Confirm
    $(document).on('click', '.es-action-confirm', function() {
        var id = $(this).data('id');
        var $row = $('tr[data-id="' + id + '"]');
        
        $.post(ensembleReservationsAdmin.ajaxUrl, {
            action: 'es_update_reservation_status',
            nonce: ensembleReservationsAdmin.nonce,
            reservation_id: id,
            status: 'confirmed'
        }, function(response) {
            if (response.success) {
                $row.find('.es-status-pill').removeClass('es-status-pending').addClass('es-status-confirmed').text('<?php _e('Bestätigt', 'ensemble'); ?>');
                $row.find('.es-action-confirm').remove();
            } else {
                alert(response.data.message);
            }
        });
    });
    
    // Cancel
    $(document).on('click', '.es-action-cancel', function() {
        var id = $(this).data('id');
        var $row = $('tr[data-id="' + id + '"]');
        
        if (!confirm('<?php echo esc_js(__('Reservierung wirklich stornieren?', 'ensemble')); ?>')) return;
        
        $.post(ensembleReservationsAdmin.ajaxUrl, {
            action: 'es_update_reservation_status',
            nonce: ensembleReservationsAdmin.nonce,
            reservation_id: id,
            status: 'cancelled'
        }, function(response) {
            if (response.success) {
                $row.addClass('es-row-cancelled');
                $row.find('.es-status-pill').removeClass('es-status-pending es-status-confirmed').addClass('es-status-cancelled').text('<?php _e('Storniert', 'ensemble'); ?>');
                $row.find('.es-action-checkin, .es-action-confirm, .es-action-cancel').remove();
            } else {
                alert(response.data.message);
            }
        });
    });
    
    // Delete
    $(document).on('click', '.es-action-delete', function() {
        var id = $(this).data('id');
        
        if (!confirm('<?php echo esc_js(__('Reservierung endgültig löschen?', 'ensemble')); ?>')) return;
        
        $.post(ensembleReservationsAdmin.ajaxUrl, {
            action: 'es_delete_reservation',
            nonce: ensembleReservationsAdmin.nonce,
            reservation_id: id
        }, function(response) {
            if (response.success) {
                $('tr[data-id="' + id + '"]').fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                alert(response.data.message);
            }
        });
    });
});
</script>
