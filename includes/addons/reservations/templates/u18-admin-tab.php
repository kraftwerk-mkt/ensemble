<?php
/**
 * U18 Authorization Admin Tab Template
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Use $addon if available (from admin-page.php), otherwise try $this
$handler = isset($addon) ? $addon->u18_handler : (isset($this) && isset($this->u18_handler) ? $this->u18_handler : null);

if (!$handler) {
    echo '<p>' . __('U18 Handler nicht verfügbar.', 'ensemble') . '</p>';
    return;
}

$search = isset($_GET['u18_search']) ? sanitize_text_field($_GET['u18_search']) : '';
$status_filter = isset($_GET['u18_status']) ? sanitize_key($_GET['u18_status']) : '';

// Re-filter if needed
if ($search || $status_filter) {
    $authorizations = $handler->get_event_authorizations($event_id, array(
        'search' => $search,
        'status' => $status_filter,
    ));
}

$admin_nonce = wp_create_nonce('ensemble_u18_admin');
?>

<div class="es-u18-admin-tab" data-event-id="<?php echo esc_attr($event_id); ?>">
    
    <!-- Statistics -->
    <div class="es-u18-stats">
        <div class="es-u18-stat">
            <div class="es-u18-stat-value"><?php echo esc_html($stats['total']); ?></div>
            <div class="es-u18-stat-label"><?php _e('Gesamt', 'ensemble'); ?></div>
        </div>
        <div class="es-u18-stat">
            <div class="es-u18-stat-value" style="color: #6c757d;"><?php echo esc_html($stats['submitted']); ?></div>
            <div class="es-u18-stat-label"><?php _e('Eingereicht', 'ensemble'); ?></div>
        </div>
        <div class="es-u18-stat">
            <div class="es-u18-stat-value" style="color: #ffc107;"><?php echo esc_html($stats['reviewed']); ?></div>
            <div class="es-u18-stat-label"><?php _e('In Prüfung', 'ensemble'); ?></div>
        </div>
        <div class="es-u18-stat">
            <div class="es-u18-stat-value" style="color: #28a745;"><?php echo esc_html($stats['approved']); ?></div>
            <div class="es-u18-stat-label"><?php _e('Genehmigt', 'ensemble'); ?></div>
        </div>
        <div class="es-u18-stat">
            <div class="es-u18-stat-value" style="color: #17a2b8;"><?php echo esc_html($stats['used']); ?></div>
            <div class="es-u18-stat-label"><?php _e('Eingecheckt', 'ensemble'); ?></div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="es-u18-filters" style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center;">
        <div class="es-filter-group">
            <select id="es-u18-status-filter" class="es-select">
                <option value=""><?php _e('Alle Status', 'ensemble'); ?></option>
                <option value="submitted" <?php selected($status_filter, 'submitted'); ?>><?php _e('Eingereicht', 'ensemble'); ?></option>
                <option value="reviewed" <?php selected($status_filter, 'reviewed'); ?>><?php _e('In Prüfung', 'ensemble'); ?></option>
                <option value="approved" <?php selected($status_filter, 'approved'); ?>><?php _e('Genehmigt', 'ensemble'); ?></option>
                <option value="rejected" <?php selected($status_filter, 'rejected'); ?>><?php _e('Abgelehnt', 'ensemble'); ?></option>
                <option value="used" <?php selected($status_filter, 'used'); ?>><?php _e('Eingecheckt', 'ensemble'); ?></option>
            </select>
        </div>
        
        <div class="es-filter-group" style="flex: 1;">
            <input type="text" id="es-u18-search" class="es-input" placeholder="<?php esc_attr_e('Suchen...', 'ensemble'); ?>" value="<?php echo esc_attr($search); ?>">
        </div>
        
        <div class="es-filter-group">
            <button type="button" class="es-btn es-btn-secondary es-u18-export">
                <span class="dashicons dashicons-download"></span>
                <?php _e('CSV Export', 'ensemble'); ?>
            </button>
        </div>
    </div>
    
    <?php if (empty($authorizations)): ?>
    
    <!-- Empty State -->
    <div class="es-empty-state" style="text-align: center; padding: 50px 20px;">
        <span class="dashicons dashicons-groups" style="font-size: 48px; color: #ccc; margin-bottom: 20px; display: block;"></span>
        <h3><?php _e('Keine U18-Anträge', 'ensemble'); ?></h3>
        <p style="color: #666;">
            <?php _e('Für dieses Event wurden noch keine Aufsichtsübertragungen eingereicht.', 'ensemble'); ?>
        </p>
    </div>
    
    <?php else: ?>
    
    <!-- Table -->
    <table class="es-u18-table">
        <thead>
            <tr>
                <th><?php _e('Code', 'ensemble'); ?></th>
                <th><?php _e('Minderjährig', 'ensemble'); ?></th>
                <th><?php _e('Geb.', 'ensemble'); ?></th>
                <th><?php _e('Begleitperson', 'ensemble'); ?></th>
                <th><?php _e('Eltern', 'ensemble'); ?></th>
                <th><?php _e('Status', 'ensemble'); ?></th>
                <th><?php _e('Ausweis', 'ensemble'); ?></th>
                <th><?php _e('Datum', 'ensemble'); ?></th>
                <th><?php _e('Aktionen', 'ensemble'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($authorizations as $auth): ?>
            <tr data-id="<?php echo esc_attr($auth->id); ?>" data-code="<?php echo esc_attr($auth->authorization_code); ?>">
                <td>
                    <code style="font-weight: bold;"><?php echo esc_html($auth->authorization_code); ?></code>
                </td>
                <td>
                    <strong><?php echo esc_html($auth->minor_firstname . ' ' . $auth->minor_lastname); ?></strong>
                </td>
                <td>
                    <?php echo esc_html(date_i18n('d.m.Y', strtotime($auth->minor_birthdate))); ?>
                    <br>
                    <small style="color: #666;">
                        <?php 
                        $age = (new DateTime($auth->minor_birthdate))->diff(new DateTime())->y;
                        echo sprintf(__('%d Jahre', 'ensemble'), $age);
                        ?>
                    </small>
                </td>
                <td>
                    <?php echo esc_html($auth->companion_firstname . ' ' . $auth->companion_lastname); ?>
                    <?php if ($auth->companion_phone): ?>
                        <br><small><?php echo esc_html($auth->companion_phone); ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo esc_html($auth->parent_firstname . ' ' . $auth->parent_lastname); ?>
                    <br>
                    <small>
                        <a href="mailto:<?php echo esc_attr($auth->parent_email); ?>"><?php echo esc_html($auth->parent_email); ?></a>
                    </small>
                </td>
                <td>
                    <span class="es-u18-status-badge es-u18-status-<?php echo esc_attr($auth->status); ?>">
                        <?php echo esc_html($handler->get_status_label($auth->status)); ?>
                    </span>
                    <?php if ($auth->checked_in_at): ?>
                        <br><small><?php echo esc_html(date_i18n('d.m. H:i', strtotime($auth->checked_in_at))); ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($auth->id_upload_path)): ?>
                        <button type="button" class="es-btn es-btn-small es-u18-view-id" 
                                data-id="<?php echo esc_attr($auth->id); ?>"
                                title="<?php esc_attr_e('Ausweis ansehen', 'ensemble'); ?>">
                            🪪
                        </button>
                    <?php else: ?>
                        <span style="color: #999;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo esc_html(date_i18n('d.m.Y', strtotime($auth->created_at))); ?>
                    <br>
                    <small><?php echo esc_html(date_i18n('H:i', strtotime($auth->created_at))); ?> Uhr</small>
                </td>
                <td>
                    <div class="es-u18-actions">
                        <?php if ($auth->status === 'submitted'): ?>
                            <button type="button" class="es-btn es-btn-small es-btn-primary es-u18-approve" title="<?php esc_attr_e('Genehmigen', 'ensemble'); ?>">
                                ✓
                            </button>
                            <button type="button" class="es-btn es-btn-small es-btn-secondary es-u18-review" title="<?php esc_attr_e('In Prüfung', 'ensemble'); ?>">
                                👁
                            </button>
                        <?php elseif ($auth->status === 'reviewed'): ?>
                            <button type="button" class="es-btn es-btn-small es-btn-primary es-u18-approve" title="<?php esc_attr_e('Genehmigen', 'ensemble'); ?>">
                                ✓
                            </button>
                        <?php elseif ($auth->status === 'approved'): ?>
                            <button type="button" class="es-btn es-btn-small es-btn-primary es-u18-checkin" title="<?php esc_attr_e('Check-in', 'ensemble'); ?>">
                                📱
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($auth->status !== 'used'): ?>
                            <button type="button" class="es-btn es-btn-small es-btn-secondary es-u18-reject" title="<?php esc_attr_e('Ablehnen', 'ensemble'); ?>">
                                ✗
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="es-btn es-btn-small es-u18-pdf" title="<?php esc_attr_e('PDF', 'ensemble'); ?>">
                            📄
                        </button>
                        
                        <button type="button" class="es-btn es-btn-small es-u18-resend" title="<?php esc_attr_e('E-Mail erneut senden', 'ensemble'); ?>">
                            ✉
                        </button>
                        
                        <button type="button" class="es-btn es-btn-small es-btn-danger es-u18-delete" title="<?php esc_attr_e('Löschen', 'ensemble'); ?>">
                            🗑
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php endif; ?>
    
</div>

<!-- Image Preview Modal -->
<div id="es-u18-image-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 999999; justify-content: center; align-items: center;">
    <div style="position: relative; max-width: 90%; max-height: 90%;">
        <button type="button" id="es-u18-image-close" style="position: absolute; top: -40px; right: 0; background: #fff; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">✕ Schließen</button>
        <img id="es-u18-image-preview" src="" style="max-width: 100%; max-height: 85vh; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <iframe id="es-u18-pdf-preview" src="" style="display: none; width: 800px; height: 85vh; border: none; border-radius: 8px;"></iframe>
    </div>
</div>

<script>
jQuery(function($) {
    var $tab = $('.es-u18-admin-tab');
    var eventId = $tab.data('event-id');
    var nonce = '<?php echo esc_js($admin_nonce); ?>';
    
    // Status filter
    $('#es-u18-status-filter').on('change', function() {
        var url = new URL(window.location.href);
        url.searchParams.set('u18_status', $(this).val());
        window.location.href = url.toString();
    });
    
    // Search
    var searchTimeout;
    $('#es-u18-search').on('input', function() {
        clearTimeout(searchTimeout);
        var search = $(this).val();
        searchTimeout = setTimeout(function() {
            var url = new URL(window.location.href);
            if (search) {
                url.searchParams.set('u18_search', search);
            } else {
                url.searchParams.delete('u18_search');
            }
            window.location.href = url.toString();
        }, 500);
    });
    
    // Approve
    $tab.on('click', '.es-u18-approve', function() {
        var $tr = $(this).closest('tr');
        updateStatus($tr.data('id'), 'approved', $tr);
    });
    
    // Review
    $tab.on('click', '.es-u18-review', function() {
        var $tr = $(this).closest('tr');
        updateStatus($tr.data('id'), 'reviewed', $tr);
    });
    
    // Reject
    $tab.on('click', '.es-u18-reject', function() {
        if (!confirm('<?php echo esc_js(__('Antrag wirklich ablehnen?', 'ensemble')); ?>')) return;
        var $tr = $(this).closest('tr');
        updateStatus($tr.data('id'), 'rejected', $tr);
    });
    
    // Check-in
    $tab.on('click', '.es-u18-checkin', function() {
        if (!confirm('<?php echo esc_js(__('Check-in bestätigen?', 'ensemble')); ?>')) return;
        var $tr = $(this).closest('tr');
        var code = $tr.data('code');
        
        $.post(ajaxurl, {
            action: 'es_u18_checkin',
            code: code,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message + '\n\nMinderjährig: ' + response.data.minor + '\nBegleitung: ' + response.data.companion);
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });
    
    // PDF - Admin users can always download
    $tab.on('click', '.es-u18-pdf', function() {
        var code = $(this).closest('tr').data('code');
        var url = ajaxurl + '?action=es_download_u18_pdf&code=' + encodeURIComponent(code);
        window.open(url, '_blank');
    });
    
    // View ID Upload
    $tab.on('click', '.es-u18-view-id', function() {
        var id = $(this).data('id');
        var url = ajaxurl + '?action=es_view_u18_id&id=' + id + '&nonce=' + nonce;
        
        // Check if PDF or Image
        var $img = $('#es-u18-image-preview');
        var $pdf = $('#es-u18-pdf-preview');
        
        // Try to load as image first
        $img.attr('src', url).show();
        $pdf.hide();
        
        $('#es-u18-image-modal').css('display', 'flex');
    });
    
    // Close image modal
    $('#es-u18-image-close, #es-u18-image-modal').on('click', function(e) {
        if (e.target === this) {
            $('#es-u18-image-modal').hide();
            $('#es-u18-image-preview').attr('src', '');
        }
    });
    
    // Resend emails
    $tab.on('click', '.es-u18-resend', function() {
        if (!confirm('<?php echo esc_js(__('E-Mails erneut senden?', 'ensemble')); ?>')) return;
        var id = $(this).closest('tr').data('id');
        
        $.post(ajaxurl, {
            action: 'es_resend_u18_emails',
            id: id,
            nonce: nonce
        }, function(response) {
            alert(response.success ? response.data.message : response.data.message);
        });
    });
    
    // Delete
    $tab.on('click', '.es-u18-delete', function() {
        if (!confirm('<?php echo esc_js(__('Antrag wirklich löschen? Dies kann nicht rückgängig gemacht werden.', 'ensemble')); ?>')) return;
        var $tr = $(this).closest('tr');
        var id = $tr.data('id');
        
        $.post(ajaxurl, {
            action: 'es_delete_u18_authorization',
            id: id,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                $tr.fadeOut(300, function() { $(this).remove(); });
            } else {
                alert(response.data.message);
            }
        });
    });
    
    // Helper: Update status
    function updateStatus(id, status, $tr) {
        $.post(ajaxurl, {
            action: 'es_update_u18_status',
            id: id,
            status: status,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    }
});
</script>
