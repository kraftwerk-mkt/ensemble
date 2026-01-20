<?php
/**
 * Abstract Management Admin Page
 * 
 * @package Ensemble
 * @subpackage Addons/Staff
 * @since 2.8.0
 * 
 * Variables:
 * @var ES_Abstract_Manager $abstract_manager
 * @var array $abstracts
 * @var array $counts
 * @var string $current_status
 * @var int $current_staff
 * @var array $staff_list
 */

if (!defined('ABSPATH')) {
    exit;
}

$statuses = ES_Abstract_Manager::get_statuses();
?>

<div class="es-abstracts-page">
    
    <!-- Header -->
    <div class="es-abstracts-header">
        <h2><?php _e('Abstract Submissions', 'ensemble'); ?></h2>
        <div class="es-abstracts-stats">
            <span class="es-stat es-stat--total">
                <strong><?php echo number_format_i18n($counts['total']); ?></strong>
                <?php _e('Total', 'ensemble'); ?>
            </span>
            <?php foreach ($statuses as $status => $info) : ?>
                <span class="es-stat" style="--stat-color: <?php echo esc_attr($info['color']); ?>">
                    <strong><?php echo number_format_i18n($counts[$status]); ?></strong>
                    <?php echo esc_html($info['label']); ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="es-abstracts-filters">
        <div class="es-filter-tabs">
            <a href="<?php echo esc_url(add_query_arg(array('status' => '', 'staff' => $current_staff))); ?>" 
               class="es-filter-tab <?php echo empty($current_status) ? 'active' : ''; ?>">
                <?php _e('All', 'ensemble'); ?>
                <span class="count"><?php echo number_format_i18n($counts['total']); ?></span>
            </a>
            <?php foreach ($statuses as $status => $info) : ?>
                <a href="<?php echo esc_url(add_query_arg(array('status' => $status, 'staff' => $current_staff))); ?>" 
                   class="es-filter-tab <?php echo $current_status === $status ? 'active' : ''; ?>"
                   style="--tab-color: <?php echo esc_attr($info['color']); ?>">
                    <?php echo esc_html($info['label']); ?>
                    <span class="count"><?php echo number_format_i18n($counts[$status]); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($staff_list)) : ?>
        <div class="es-filter-select">
            <select id="es-filter-staff" onchange="window.location.href=this.value">
                <option value="<?php echo esc_url(add_query_arg(array('staff' => '', 'status' => $current_status))); ?>">
                    <?php _e('All Recipients', 'ensemble'); ?>
                </option>
                <?php foreach ($staff_list as $staff_member) : ?>
                    <option value="<?php echo esc_url(add_query_arg(array('staff' => $staff_member['id'], 'status' => $current_status))); ?>"
                            <?php selected($current_staff, $staff_member['id']); ?>>
                        <?php echo esc_html($staff_member['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Table -->
    <?php if (empty($abstracts)) : ?>
        <div class="es-empty-state">
            <span class="dashicons dashicons-inbox"></span>
            <h3><?php _e('No submissions found', 'ensemble'); ?></h3>
            <p><?php _e('Abstract submissions will appear here when users submit them through the contact form.', 'ensemble'); ?></p>
        </div>
    <?php else : ?>
        <table class="es-abstracts-table">
            <thead>
                <tr>
                    <th class="column-status"><?php _e('Status', 'ensemble'); ?></th>
                    <th class="column-title"><?php _e('Title', 'ensemble'); ?></th>
                    <th class="column-submitter"><?php _e('Submitter', 'ensemble'); ?></th>
                    <th class="column-recipient"><?php _e('Recipient', 'ensemble'); ?></th>
                    <th class="column-date"><?php _e('Date', 'ensemble'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'ensemble'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($abstracts as $abstract) : ?>
                    <tr data-id="<?php echo esc_attr($abstract['id']); ?>">
                        <td class="column-status">
                            <span class="es-status-badge" style="background: <?php echo esc_attr($abstract['status_color']); ?>">
                                <?php echo esc_html($abstract['status_label']); ?>
                            </span>
                        </td>
                        <td class="column-title">
                            <strong><?php echo esc_html($abstract['title']); ?></strong>
                            <?php if (!empty($abstract['attachment_url'])) : ?>
                                <a href="<?php echo esc_url($abstract['attachment_url']); ?>" 
                                   target="_blank" 
                                   class="es-attachment-link"
                                   title="<?php esc_attr_e('Download attachment', 'ensemble'); ?>">
                                    <span class="dashicons dashicons-paperclip"></span>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="column-submitter">
                            <span class="es-submitter-name"><?php echo esc_html($abstract['submitter_name']); ?></span>
                            <a href="mailto:<?php echo esc_attr($abstract['submitter_email']); ?>" class="es-submitter-email">
                                <?php echo esc_html($abstract['submitter_email']); ?>
                            </a>
                        </td>
                        <td class="column-recipient">
                            <?php echo esc_html($abstract['staff_name']); ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($abstract['submission_date']))); ?>
                            <span class="es-time"><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($abstract['submission_date']))); ?></span>
                        </td>
                        <td class="column-actions">
                            <button type="button" 
                                    class="es-btn es-btn--small es-btn--view" 
                                    data-action="view"
                                    data-id="<?php echo esc_attr($abstract['id']); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <button type="button" 
                                    class="es-btn es-btn--small es-btn--delete" 
                                    data-action="delete"
                                    data-id="<?php echo esc_attr($abstract['id']); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
</div>

<!-- Detail Modal -->
<div id="es-abstract-modal" class="es-modal" style="display: none;">
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h3 class="es-modal-title"><?php _e('Abstract Details', 'ensemble'); ?></h3>
            <button type="button" class="es-modal-close">&times;</button>
        </div>
        <div class="es-modal-body">
            <div class="es-abstract-detail">
                <!-- Populated via JS -->
            </div>
        </div>
    </div>
</div>

<style>
/* Abstracts Page Styles */
.es-abstracts-page {
    padding: 20px 0;
}

.es-abstracts-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.es-abstracts-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-abstracts-stats {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.es-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 16px;
    background: var(--es-surface-secondary, #252525);
    border-radius: 6px;
    font-size: 12px;
    color: var(--es-text-muted, #888);
}

.es-stat strong {
    font-size: 18px;
    color: var(--stat-color, var(--es-text, #e0e0e0));
}

.es-stat--total strong {
    color: var(--es-primary, #3b82f6);
}

/* Filters */
.es-abstracts-filters {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.es-filter-tabs {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.es-filter-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: var(--es-surface-secondary, #252525);
    border: 1px solid var(--es-border, #333);
    border-radius: 6px;
    color: var(--es-text-muted, #888);
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s ease;
}

.es-filter-tab:hover {
    border-color: var(--tab-color, var(--es-primary, #3b82f6));
    color: var(--es-text, #e0e0e0);
}

.es-filter-tab.active {
    background: var(--tab-color, var(--es-primary, #3b82f6));
    border-color: var(--tab-color, var(--es-primary, #3b82f6));
    color: #fff;
}

.es-filter-tab .count {
    background: rgba(255,255,255,0.15);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
}

.es-filter-select select {
    padding: 8px 12px;
    background: var(--es-surface-secondary, #252525);
    border: 1px solid var(--es-border, #333);
    border-radius: 6px;
    color: var(--es-text, #e0e0e0);
    font-size: 13px;
}

/* Empty State */
.es-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--es-surface-secondary, #252525);
    border-radius: 8px;
}

.es-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--es-text-muted, #888);
    margin-bottom: 15px;
}

.es-empty-state h3 {
    margin: 0 0 10px;
    color: var(--es-text, #e0e0e0);
}

.es-empty-state p {
    margin: 0;
    color: var(--es-text-muted, #888);
}

/* Table */
.es-abstracts-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--es-surface, #1e1e1e);
    border-radius: 8px;
    overflow: hidden;
}

.es-abstracts-table th,
.es-abstracts-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid var(--es-border, #333);
}

.es-abstracts-table th {
    background: var(--es-surface-secondary, #252525);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: var(--es-text-muted, #888);
}

.es-abstracts-table td {
    font-size: 14px;
    color: var(--es-text, #e0e0e0);
}

.es-abstracts-table tr:hover td {
    background: var(--es-surface-secondary, #252525);
}

.column-status { width: 120px; }
.column-title { width: auto; }
.column-submitter { width: 200px; }
.column-recipient { width: 150px; }
.column-date { width: 120px; }
.column-actions { width: 100px; text-align: right; }

.es-status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    color: #fff;
    text-transform: uppercase;
}

.es-attachment-link {
    color: var(--es-primary, #3b82f6);
    margin-left: 8px;
    vertical-align: middle;
}

.es-submitter-name {
    display: block;
    font-weight: 500;
}

.es-submitter-email {
    display: block;
    font-size: 12px;
    color: var(--es-text-muted, #888);
}

.es-time {
    display: block;
    font-size: 12px;
    color: var(--es-text-muted, #888);
}

.es-btn--small {
    padding: 6px 8px;
    border: none;
    border-radius: 4px;
    background: var(--es-surface-secondary, #252525);
    color: var(--es-text-muted, #888);
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-btn--small:hover {
    background: var(--es-primary, #3b82f6);
    color: #fff;
}

.es-btn--delete:hover {
    background: #d63638;
}

/* Modal */
.es-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.es-modal-content {
    background: var(--es-surface, #1e1e1e);
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.es-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: var(--es-surface-secondary, #252525);
    border-bottom: 1px solid var(--es-border, #333);
}

.es-modal-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: var(--es-text-muted, #888);
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.es-modal-close:hover {
    color: var(--es-text, #e0e0e0);
}

.es-modal-body {
    padding: 20px;
    overflow-y: auto;
}

/* Abstract Detail */
.es-abstract-detail-row {
    display: flex;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--es-border, #333);
}

.es-abstract-detail-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.es-abstract-detail-label {
    width: 120px;
    flex-shrink: 0;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: var(--es-text-muted, #888);
}

.es-abstract-detail-value {
    flex: 1;
    color: var(--es-text, #e0e0e0);
}

.es-abstract-message {
    background: var(--es-surface-secondary, #252525);
    padding: 15px;
    border-radius: 6px;
    white-space: pre-wrap;
}

.es-status-form {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--es-border, #333);
}

.es-status-form h4 {
    margin: 0 0 15px;
    font-size: 14px;
    color: var(--es-text, #e0e0e0);
}

.es-status-options {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.es-status-option {
    flex: 1;
    min-width: 120px;
}

.es-status-option input {
    display: none;
}

.es-status-option label {
    display: block;
    padding: 10px 15px;
    background: var(--es-surface-secondary, #252525);
    border: 2px solid var(--es-border, #333);
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-status-option input:checked + label {
    border-color: var(--status-color);
    background: var(--status-color);
    color: #fff;
}

.es-status-note {
    width: 100%;
    padding: 10px;
    background: var(--es-surface-secondary, #252525);
    border: 1px solid var(--es-border, #333);
    border-radius: 6px;
    color: var(--es-text, #e0e0e0);
    font-size: 14px;
    resize: vertical;
    min-height: 80px;
    margin-bottom: 15px;
}

.es-status-submit {
    display: flex;
    justify-content: flex-end;
}
</style>

<script>
jQuery(function($) {
    // View abstract
    $('.es-btn--view').on('click', function() {
        var id = $(this).data('id');
        loadAbstractDetail(id);
    });
    
    // Delete abstract
    $('.es-btn--delete').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to delete this submission?', 'ensemble'); ?>')) {
            return;
        }
        
        var id = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_delete_abstract',
                abstract_id: id,
                nonce: '<?php echo wp_create_nonce('es_abstract_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || '<?php _e('Error deleting submission', 'ensemble'); ?>');
                }
            }
        });
    });
    
    // Close modal
    $('.es-modal-close, .es-modal').on('click', function(e) {
        if (e.target === this) {
            $('#es-abstract-modal').hide();
        }
    });
    
    // Load abstract detail
    function loadAbstractDetail(id) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_abstract',
                abstract_id: id,
                nonce: '<?php echo wp_create_nonce('es_abstract_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    renderAbstractDetail(response.data);
                    $('#es-abstract-modal').show();
                }
            }
        });
    }
    
    // Render abstract detail
    function renderAbstractDetail(abstract) {
        var statuses = <?php echo json_encode($statuses); ?>;
        
        var html = '<div class="es-abstract-detail-row">' +
            '<div class="es-abstract-detail-label"><?php _e('Status', 'ensemble'); ?></div>' +
            '<div class="es-abstract-detail-value"><span class="es-status-badge" style="background: ' + abstract.status_color + '">' + abstract.status_label + '</span></div>' +
            '</div>';
        
        html += '<div class="es-abstract-detail-row">' +
            '<div class="es-abstract-detail-label"><?php _e('Title', 'ensemble'); ?></div>' +
            '<div class="es-abstract-detail-value"><strong>' + abstract.title + '</strong></div>' +
            '</div>';
        
        html += '<div class="es-abstract-detail-row">' +
            '<div class="es-abstract-detail-label"><?php _e('Submitter', 'ensemble'); ?></div>' +
            '<div class="es-abstract-detail-value">' + abstract.submitter_name + '<br><a href="mailto:' + abstract.submitter_email + '">' + abstract.submitter_email + '</a></div>' +
            '</div>';
        
        html += '<div class="es-abstract-detail-row">' +
            '<div class="es-abstract-detail-label"><?php _e('Recipient', 'ensemble'); ?></div>' +
            '<div class="es-abstract-detail-value">' + abstract.staff_name + '</div>' +
            '</div>';
        
        html += '<div class="es-abstract-detail-row">' +
            '<div class="es-abstract-detail-label"><?php _e('Submitted', 'ensemble'); ?></div>' +
            '<div class="es-abstract-detail-value">' + abstract.submission_date + '</div>' +
            '</div>';
        
        if (abstract.attachment_url) {
            html += '<div class="es-abstract-detail-row">' +
                '<div class="es-abstract-detail-label"><?php _e('Attachment', 'ensemble'); ?></div>' +
                '<div class="es-abstract-detail-value"><a href="' + abstract.attachment_url + '" target="_blank" class="button"><?php _e('Download', 'ensemble'); ?> (' + abstract.attachment_name + ')</a></div>' +
                '</div>';
        }
        
        if (abstract.message) {
            html += '<div class="es-abstract-detail-row">' +
                '<div class="es-abstract-detail-label"><?php _e('Message', 'ensemble'); ?></div>' +
                '<div class="es-abstract-detail-value"><div class="es-abstract-message">' + abstract.message + '</div></div>' +
                '</div>';
        }
        
        // Status change form
        html += '<div class="es-status-form">' +
            '<h4><?php _e('Change Status', 'ensemble'); ?></h4>' +
            '<form id="es-status-form" data-id="' + abstract.id + '">' +
            '<div class="es-status-options">';
        
        for (var status in statuses) {
            var checked = abstract.status === status ? 'checked' : '';
            html += '<div class="es-status-option" style="--status-color: ' + statuses[status].color + '">' +
                '<input type="radio" name="status" value="' + status + '" id="status-' + status + '" ' + checked + '>' +
                '<label for="status-' + status + '">' + statuses[status].label + '</label>' +
                '</div>';
        }
        
        html += '</div>' +
            '<textarea class="es-status-note" name="note" placeholder="<?php _e('Add a note (will be sent to submitter for certain status changes)...', 'ensemble'); ?>"></textarea>' +
            '<div class="es-status-submit">' +
            '<button type="submit" class="button button-primary"><?php _e('Update Status', 'ensemble'); ?></button>' +
            '</div>' +
            '</form></div>';
        
        $('.es-abstract-detail').html(html);
        
        // Handle status form submission
        $('#es-status-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var id = $form.data('id');
            var status = $form.find('input[name="status"]:checked').val();
            var note = $form.find('textarea[name="note"]').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_update_abstract_status',
                    abstract_id: id,
                    status: status,
                    note: note,
                    nonce: '<?php echo wp_create_nonce('es_abstract_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Error updating status', 'ensemble'); ?>');
                    }
                }
            });
        });
    }
});
</script>
