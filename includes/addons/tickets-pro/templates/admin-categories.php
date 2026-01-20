<?php
/**
 * Tickets Pro Admin - Ticket Templates Tab
 * 
 * Manage global ticket category templates (Paid + External)
 * 
 * VERSION: 2025-01-16-EXTERNAL-TABS
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Templates
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get global templates (event_id = 0)
$templates = ES_Ticket_Category::get_templates();
$settings = get_option('ensemble_tickets_pro_settings', array());
$currency = $settings['currency'] ?? 'EUR';
$currency_symbols = array(
    'EUR' => '€',
    'USD' => '$',
    'GBP' => '£',
    'CHF' => 'CHF',
);
$currency_symbol = $currency_symbols[$currency] ?? $currency;

// Separate paid and external templates
$paid_templates = array();
$external_templates = array();

foreach ($templates as $template) {
    $ticket_type = $template->ticket_type ?? 'paid';
    if ($ticket_type === 'external') {
        $external_templates[] = $template;
    } else {
        $paid_templates[] = $template;
    }
}

// External ticket providers
$providers = array(
    'eventbrite' => array(
        'name'  => 'Eventbrite',
        'color' => '#f05537',
    ),
    'resident_advisor' => array(
        'name'  => 'Resident Advisor',
        'color' => '#0a0a0a',
    ),
    'eventim' => array(
        'name'  => 'Eventim',
        'color' => '#003d7c',
    ),
    'ticketmaster' => array(
        'name'  => 'Ticketmaster',
        'color' => '#026cdf',
    ),
    'dice' => array(
        'name'  => 'DICE',
        'color' => '#000000',
    ),
    'reservix' => array(
        'name'  => 'Reservix',
        'color' => '#e30613',
    ),
    'tickets_io' => array(
        'name'  => 'tickets.io',
        'color' => '#00a8e8',
    ),
    'custom' => array(
        'name'  => __('Custom / Other', 'ensemble'),
        'color' => '#6b7280',
    ),
);
$providers = apply_filters('ensemble_ticket_providers', $providers);

// Availability statuses
$statuses = array(
    'available' => __('Available', 'ensemble'),
    'limited'   => __('Limited', 'ensemble'),
    'few_left'  => __('Few Tickets Left', 'ensemble'),
    'presale'   => __('Presale', 'ensemble'),
    'sold_out'  => __('Sold Out', 'ensemble'),
);
?>

<div class="es-admin-section">
    <!-- DEBUG: NEW VERSION WITH EXTERNAL TABS 2025-01-16 -->
    
    <!-- Section Header -->
    <div class="es-section-header">
        <div class="es-section-title">
            <h2><?php _e('Ticket Templates', 'ensemble'); ?></h2>
            <p class="description">
                <?php _e('Create reusable ticket templates. Import them into events to save time.', 'ensemble'); ?>
            </p>
        </div>
        <div class="es-section-actions">
            <button type="button" class="button" id="es-add-external-template">
                <span class="dashicons dashicons-external"></span>
                <?php _e('Add External Link', 'ensemble'); ?>
            </button>
            <button type="button" class="button button-primary" id="es-add-template">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Add Paid Ticket', 'ensemble'); ?>
            </button>
        </div>
    </div>
    
    <!-- Tabs for Paid / External -->
    <div class="es-templates-tabs">
        <button type="button" class="es-tab-btn active" data-tab="paid">
            <span class="dashicons dashicons-tickets-alt"></span>
            <?php _e('Paid Tickets', 'ensemble'); ?>
            <span class="es-tab-count"><?php echo count($paid_templates); ?></span>
        </button>
        <button type="button" class="es-tab-btn" data-tab="external">
            <span class="dashicons dashicons-external"></span>
            <?php _e('External Links', 'ensemble'); ?>
            <span class="es-tab-count"><?php echo count($external_templates); ?></span>
        </button>
    </div>
    
    <!-- Paid Tickets Tab -->
    <div class="es-tab-content active" data-tab="paid">
        <?php if (empty($paid_templates)): ?>
        <div class="es-empty-state">
            <span class="dashicons dashicons-tickets-alt"></span>
            <h3><?php _e('No Paid Ticket Templates', 'ensemble'); ?></h3>
            <p><?php _e('Create ticket templates for your own ticket sales.', 'ensemble'); ?></p>
            <button type="button" class="button button-primary es-add-paid-btn">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Create Paid Ticket', 'ensemble'); ?>
            </button>
        </div>
        <?php else: ?>
        <div class="es-templates-grid">
            <?php foreach ($paid_templates as $template): ?>
            <div class="es-template-card" data-id="<?php echo esc_attr($template->id); ?>" data-type="paid">
                <div class="es-template-header">
                    <h3 class="es-template-name"><?php echo esc_html($template->name); ?></h3>
                    <span class="es-template-price"><?php echo esc_html($currency_symbol . number_format($template->price, 2, ',', '.')); ?></span>
                </div>
                
                <?php if ($template->description): ?>
                <p class="es-template-description"><?php echo esc_html($template->description); ?></p>
                <?php endif; ?>
                
                <div class="es-template-meta">
                    <?php if ($template->capacity): ?>
                    <span class="es-template-meta-item">
                        <span class="dashicons dashicons-groups"></span>
                        <?php printf(__('Capacity: %d', 'ensemble'), $template->capacity); ?>
                    </span>
                    <?php else: ?>
                    <span class="es-template-meta-item">
                        <span class="dashicons dashicons-groups"></span>
                        <?php _e('Unlimited', 'ensemble'); ?>
                    </span>
                    <?php endif; ?>
                    
                    <span class="es-template-meta-item">
                        <span class="dashicons dashicons-cart"></span>
                        <?php printf(__('Min: %d / Max: %d', 'ensemble'), $template->min_quantity, $template->max_quantity); ?>
                    </span>
                </div>
                
                <div class="es-template-actions">
                    <button type="button" class="button es-edit-template" data-id="<?php echo esc_attr($template->id); ?>" data-type="paid">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Edit', 'ensemble'); ?>
                    </button>
                    <button type="button" class="button es-duplicate-template" data-id="<?php echo esc_attr($template->id); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php _e('Duplicate', 'ensemble'); ?>
                    </button>
                    <button type="button" class="button es-delete-template" data-id="<?php echo esc_attr($template->id); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- External Links Tab -->
    <div class="es-tab-content" data-tab="external">
        <?php if (empty($external_templates)): ?>
        <div class="es-empty-state">
            <span class="dashicons dashicons-external"></span>
            <h3><?php _e('No External Link Templates', 'ensemble'); ?></h3>
            <p><?php _e('Create templates for external ticket providers like Eventbrite, Resident Advisor, etc.', 'ensemble'); ?></p>
            <button type="button" class="button button-primary es-add-external-btn">
                <span class="dashicons dashicons-external"></span>
                <?php _e('Create External Link', 'ensemble'); ?>
            </button>
        </div>
        <?php else: ?>
        <div class="es-templates-grid">
            <?php foreach ($external_templates as $template): 
                $provider_key = $template->provider ?? 'custom';
                $provider = $providers[$provider_key] ?? $providers['custom'];
            ?>
            <div class="es-template-card es-template-external" data-id="<?php echo esc_attr($template->id); ?>" data-type="external">
                <div class="es-template-header">
                    <h3 class="es-template-name"><?php echo esc_html($template->name); ?></h3>
                    <span class="es-template-provider" style="background-color: <?php echo esc_attr($provider['color']); ?>;">
                        <?php echo esc_html($provider['name']); ?>
                    </span>
                </div>
                
                <?php if ($template->description): ?>
                <p class="es-template-description"><?php echo esc_html($template->description); ?></p>
                <?php endif; ?>
                
                <div class="es-template-meta">
                    <?php if (!empty($template->external_url)): ?>
                    <span class="es-template-meta-item es-template-url">
                        <span class="dashicons dashicons-admin-links"></span>
                        <span class="es-url-truncate"><?php echo esc_html($template->external_url); ?></span>
                    </span>
                    <?php else: ?>
                    <span class="es-template-meta-item">
                        <span class="dashicons dashicons-admin-links"></span>
                        <em><?php _e('URL set per event', 'ensemble'); ?></em>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($template->button_text)): ?>
                    <span class="es-template-meta-item">
                        <span class="dashicons dashicons-button"></span>
                        "<?php echo esc_html($template->button_text); ?>"
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($template->price) && $template->price > 0): ?>
                    <span class="es-template-meta-item">
                        <span class="dashicons dashicons-tag"></span>
                        <?php printf(__('from %s', 'ensemble'), $currency_symbol . number_format($template->price, 2, ',', '.')); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="es-template-actions">
                    <button type="button" class="button es-edit-template" data-id="<?php echo esc_attr($template->id); ?>" data-type="external">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Edit', 'ensemble'); ?>
                    </button>
                    <button type="button" class="button es-duplicate-template" data-id="<?php echo esc_attr($template->id); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php _e('Duplicate', 'ensemble'); ?>
                    </button>
                    <button type="button" class="button es-delete-template" data-id="<?php echo esc_attr($template->id); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
</div>

<!-- Add/Edit Paid Template Modal -->
<div class="es-modal" id="es-template-modal" style="display: none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2 id="es-template-modal-title"><?php _e('Add Paid Ticket', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body">
            <form id="es-template-form">
                <input type="hidden" name="template_id" id="template_id" value="">
                <input type="hidden" name="ticket_type" value="paid">
                <?php wp_nonce_field('es_save_ticket_template', 'template_nonce'); ?>
                
                <div class="es-form-row">
                    <label for="template_name"><?php _e('Template Name', 'ensemble'); ?> <span class="required">*</span></label>
                    <input type="text" name="name" id="template_name" required placeholder="<?php esc_attr_e('e.g. Early Bird, VIP, Standard', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-row">
                    <label for="template_description"><?php _e('Description', 'ensemble'); ?></label>
                    <textarea name="description" id="template_description" rows="2" placeholder="<?php esc_attr_e('Short description shown to customers', 'ensemble'); ?>"></textarea>
                </div>
                
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label for="template_price"><?php _e('Price', 'ensemble'); ?> (<?php echo esc_html($currency); ?>)</label>
                        <input type="number" name="price" id="template_price" step="0.01" min="0" value="0.00">
                    </div>
                    <div class="es-form-row">
                        <label for="template_capacity"><?php _e('Capacity', 'ensemble'); ?></label>
                        <input type="number" name="capacity" id="template_capacity" min="0" placeholder="<?php esc_attr_e('Leave empty for unlimited', 'ensemble'); ?>">
                    </div>
                </div>
                
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label for="template_min_quantity"><?php _e('Min per Order', 'ensemble'); ?></label>
                        <input type="number" name="min_quantity" id="template_min_quantity" min="1" value="1">
                    </div>
                    <div class="es-form-row">
                        <label for="template_max_quantity"><?php _e('Max per Order', 'ensemble'); ?></label>
                        <input type="number" name="max_quantity" id="template_max_quantity" min="1" value="10">
                    </div>
                </div>
                
                <div class="es-form-row">
                    <label><?php _e('Status', 'ensemble'); ?></label>
                    <label class="es-toggle">
                        <input type="checkbox" name="status" id="template_status" value="active" checked>
                        <span class="es-toggle-track"></span>
                        <span class="es-toggle-label"><?php _e('Active', 'ensemble'); ?></span>
                    </label>
                    <p class="description"><?php _e('Inactive templates will not appear in the import list.', 'ensemble'); ?></p>
                </div>
                
            </form>
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="button button-primary" id="es-save-template">
                <?php _e('Save Template', 'ensemble'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Add/Edit External Template Modal -->
<div class="es-modal" id="es-external-template-modal" style="display: none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2 id="es-external-modal-title"><?php _e('Add External Link', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body">
            <form id="es-external-template-form">
                <input type="hidden" name="template_id" id="external_template_id" value="">
                <input type="hidden" name="ticket_type" value="external">
                <?php wp_nonce_field('es_save_ticket_template', 'external_template_nonce'); ?>
                
                <div class="es-form-row">
                    <label for="external_name"><?php _e('Template Name', 'ensemble'); ?> <span class="required">*</span></label>
                    <input type="text" name="name" id="external_name" required placeholder="<?php esc_attr_e('e.g. Eventbrite Early Bird, RA Tickets', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-row">
                    <label for="external_provider"><?php _e('Provider', 'ensemble'); ?></label>
                    <select name="provider" id="external_provider">
                        <?php foreach ($providers as $key => $provider): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($provider['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="es-form-row">
                    <label for="external_url"><?php _e('Default Ticket URL', 'ensemble'); ?></label>
                    <input type="url" name="external_url" id="external_url" placeholder="https://www.eventbrite.com/e/...">
                    <p class="description"><?php _e('Leave empty if URL varies per event. You can set it when importing.', 'ensemble'); ?></p>
                </div>
                
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label for="external_button_text"><?php _e('Button Text', 'ensemble'); ?></label>
                        <input type="text" name="button_text" id="external_button_text" placeholder="<?php esc_attr_e('Buy Tickets', 'ensemble'); ?>">
                    </div>
                    <div class="es-form-row">
                        <label for="external_availability"><?php _e('Default Availability', 'ensemble'); ?></label>
                        <select name="availability_status" id="external_availability">
                            <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label for="external_price"><?php _e('Starting Price', 'ensemble'); ?> (<?php echo esc_html($currency); ?>)</label>
                        <input type="number" name="price" id="external_price" step="0.01" min="0" placeholder="<?php esc_attr_e('Optional', 'ensemble'); ?>">
                        <p class="description"><?php _e('Display as "from X €"', 'ensemble'); ?></p>
                    </div>
                    <div class="es-form-row">
                        <label for="external_price_max"><?php _e('Max Price', 'ensemble'); ?> (<?php echo esc_html($currency); ?>)</label>
                        <input type="number" name="price_max" id="external_price_max" step="0.01" min="0" placeholder="<?php esc_attr_e('Optional', 'ensemble'); ?>">
                    </div>
                </div>
                
                <div class="es-form-row">
                    <label for="external_description"><?php _e('Description', 'ensemble'); ?></label>
                    <textarea name="description" id="external_description" rows="2" placeholder="<?php esc_attr_e('Optional notes about this ticket type', 'ensemble'); ?>"></textarea>
                </div>
                
                <div class="es-form-row">
                    <label><?php _e('Status', 'ensemble'); ?></label>
                    <label class="es-toggle">
                        <input type="checkbox" name="status" id="external_status" value="active" checked>
                        <span class="es-toggle-track"></span>
                        <span class="es-toggle-label"><?php _e('Active', 'ensemble'); ?></span>
                    </label>
                </div>
                
            </form>
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="button button-primary" id="es-save-external-template">
                <?php _e('Save Template', 'ensemble'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    var $paidModal = $('#es-template-modal');
    var $externalModal = $('#es-external-template-modal');
    var $paidForm = $('#es-template-form');
    var $externalForm = $('#es-external-template-form');
    
    // Helper function to switch tabs
    function switchToTab(tabName) {
        $('.es-tab-btn').removeClass('active');
        $('.es-tab-btn[data-tab="' + tabName + '"]').addClass('active');
        $('.es-tab-content').removeClass('active');
        $('.es-tab-content[data-tab="' + tabName + '"]').addClass('active');
    }
    
    // Tab switching
    $(document).on('click', '.es-tab-btn', function() {
        var tab = $(this).data('tab');
        switchToTab(tab);
    });
    
    // Open modal for new paid template
    $(document).on('click', '#es-add-template, .es-add-paid-btn', function() {
        switchToTab('paid');
        $paidForm[0].reset();
        $('#template_id').val('');
        $('#template_status').prop('checked', true);
        $('#es-template-modal-title').text('<?php echo esc_js(__('Add Paid Ticket', 'ensemble')); ?>');
        $paidModal.fadeIn(200);
    });
    
    // Open modal for new external template
    $(document).on('click', '#es-add-external-template, .es-add-external-btn', function() {
        // Switch to external tab first
        switchToTab('external');
        
        // Then open modal
        $externalForm[0].reset();
        $('#external_template_id').val('');
        $('#external_status').prop('checked', true);
        $('#es-external-modal-title').text('<?php echo esc_js(__('Add External Link', 'ensemble')); ?>');
        $externalModal.fadeIn(200);
    });
    
    // Edit template
    $(document).on('click', '.es-edit-template', function() {
        var id = $(this).data('id');
        var type = $(this).data('type');
        
        // Load template data via AJAX
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_ticket_template',
                template_id: id,
                nonce: esTicketsPro.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    if (type === 'external' || data.ticket_type === 'external') {
                        // External template
                        $('#external_template_id').val(data.id);
                        $('#external_name').val(data.name);
                        $('#external_provider').val(data.provider || 'custom');
                        $('#external_url').val(data.external_url || '');
                        $('#external_button_text').val(data.button_text || '');
                        $('#external_availability').val(data.availability_status || 'available');
                        $('#external_price').val(data.price || '');
                        $('#external_price_max').val(data.price_max || '');
                        $('#external_description').val(data.description || '');
                        $('#external_status').prop('checked', data.status === 'active');
                        $('#es-external-modal-title').text('<?php echo esc_js(__('Edit External Link', 'ensemble')); ?>');
                        $externalModal.fadeIn(200);
                    } else {
                        // Paid template
                        $('#template_id').val(data.id);
                        $('#template_name').val(data.name);
                        $('#template_description').val(data.description);
                        $('#template_price').val(data.price);
                        $('#template_capacity').val(data.capacity || '');
                        $('#template_min_quantity').val(data.min_quantity);
                        $('#template_max_quantity').val(data.max_quantity);
                        $('#template_status').prop('checked', data.status === 'active');
                        $('#es-template-modal-title').text('<?php echo esc_js(__('Edit Paid Ticket', 'ensemble')); ?>');
                        $paidModal.fadeIn(200);
                    }
                }
            }
        });
    });
    
    // Duplicate template
    $(document).on('click', '.es-duplicate-template', function() {
        var id = $(this).data('id');
        
        if (!confirm('<?php echo esc_js(__('Create a copy of this template?', 'ensemble')); ?>')) {
            return;
        }
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_duplicate_ticket_template',
                template_id: id,
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
    
    // Delete template
    $(document).on('click', '.es-delete-template', function() {
        var id = $(this).data('id');
        var $card = $(this).closest('.es-template-card');
        
        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this template?', 'ensemble')); ?>')) {
            return;
        }
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_delete_ticket_template',
                template_id: id,
                nonce: esTicketsPro.nonce
            },
            success: function(response) {
                if (response.success) {
                    $card.fadeOut(300, function() {
                        $(this).remove();
                        updateTabCounts();
                        // Check if no templates left in current tab
                        var $currentTab = $('.es-tab-content.active');
                        if ($currentTab.find('.es-template-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.data.message || 'Error');
                }
            }
        });
    });
    
    // Save paid template
    $('#es-save-template').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Saving...', 'ensemble')); ?>');
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: $paidForm.serialize() + '&action=es_save_ticket_template',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error');
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Save Template', 'ensemble')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred.', 'ensemble')); ?>');
                $btn.prop('disabled', false).text('<?php echo esc_js(__('Save Template', 'ensemble')); ?>');
            }
        });
    });
    
    // Save external template
    $('#es-save-external-template').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Saving...', 'ensemble')); ?>');
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: $externalForm.serialize() + '&action=es_save_ticket_template',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error');
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Save Template', 'ensemble')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred.', 'ensemble')); ?>');
                $btn.prop('disabled', false).text('<?php echo esc_js(__('Save Template', 'ensemble')); ?>');
            }
        });
    });
    
    // Update tab counts
    function updateTabCounts() {
        var paidCount = $('.es-tab-content[data-tab="paid"] .es-template-card').length;
        var externalCount = $('.es-tab-content[data-tab="external"] .es-template-card').length;
        $('.es-tab-btn[data-tab="paid"] .es-tab-count').text(paidCount);
        $('.es-tab-btn[data-tab="external"] .es-tab-count').text(externalCount);
    }
    
    // Close modal
    $('.es-modal-close, .es-modal-cancel, .es-modal-overlay').on('click', function() {
        $paidModal.fadeOut(200);
        $externalModal.fadeOut(200);
    });
    
    // ESC key closes modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $paidModal.fadeOut(200);
            $externalModal.fadeOut(200);
        }
    });
});
</script>

<style>
/* Templates Tabs */
.es-admin-section .es-templates-tabs {
    display: flex !important;
    gap: 8px;
    margin: 20px 0;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--es-border, #e2e8f0);
}

.es-admin-section .es-tab-btn {
    display: flex !important;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--es-surface, #ffffff);
    border: 1px solid var(--es-border, #e2e8f0);
    border-radius: var(--es-radius-lg, 8px);
    color: var(--es-text-secondary, #64748b);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
    font-family: inherit;
}

.es-admin-section .es-tab-btn:hover {
    background: var(--es-surface-hover, #f8fafc);
    color: var(--es-text, #1e293b);
}

.es-admin-section .es-tab-btn.active {
    background: var(--es-primary, #3b82f6);
    border-color: var(--es-primary, #3b82f6);
    color: #fff;
}

.es-admin-section .es-tab-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.es-admin-section .es-tab-count {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
}

.es-admin-section .es-tab-btn:not(.active) .es-tab-count {
    background: var(--es-surface-secondary, #f1f5f9);
}

/* Tab Content - wichtig für Sichtbarkeit */
.es-admin-section .es-tab-content {
    display: none;
}

.es-admin-section .es-tab-content.active {
    display: block !important;
}

/* Templates Grid */
.es-admin-section .es-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.es-template-card {
    background: var(--es-surface, #ffffff);
    border: 1px solid var(--es-border, #e2e8f0);
    border-radius: var(--es-radius-lg, 8px);
    padding: 20px;
    transition: all 0.2s ease;
}

.es-template-card:hover {
    border-color: var(--es-primary, #3b82f6);
    box-shadow: var(--es-shadow, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
}

.es-template-external {
    border-left: 3px solid var(--es-info, #0ea5e9);
}

.es-template-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
}

.es-template-name {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--es-text, #1e293b);
}

.es-template-price {
    background: var(--es-primary-light, #dbeafe);
    color: var(--es-primary, #3b82f6);
    padding: 4px 10px;
    border-radius: var(--es-radius-pill, 9999px);
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
}

.es-template-provider {
    padding: 4px 10px;
    border-radius: var(--es-radius-pill, 9999px);
    font-weight: 600;
    font-size: 12px;
    color: #fff;
    white-space: nowrap;
}

.es-template-description {
    color: var(--es-text-secondary, #64748b);
    font-size: 13px;
    margin: 0 0 12px;
    line-height: 1.4;
}

.es-template-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
    padding-top: 12px;
    border-top: 1px solid var(--es-border, #e2e8f0);
}

.es-template-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--es-text-muted, #94a3b8);
}

.es-template-meta-item .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.es-template-url {
    flex: 1 1 100%;
    overflow: hidden;
}

.es-url-truncate {
    display: block;
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.es-template-actions {
    display: flex;
    gap: 8px;
}

.es-template-actions .button {
    flex: 1;
    justify-content: center;
}

.es-template-actions .es-delete-template {
    flex: 0 0 auto;
    color: var(--es-danger, #ef4444);
}

.es-template-actions .es-delete-template:hover {
    background: var(--es-danger-light, #fee2e2);
    border-color: var(--es-danger, #ef4444);
}

/* Empty State */
.es-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--es-surface, #ffffff);
    border: 2px dashed var(--es-border, #e2e8f0);
    border-radius: var(--es-radius-lg, 8px);
    margin-top: 20px;
}

.es-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--es-text-muted, #94a3b8);
    margin-bottom: 16px;
}

.es-empty-state h3 {
    margin: 0 0 8px;
    color: var(--es-text, #1e293b);
}

.es-empty-state p {
    color: var(--es-text-secondary, #64748b);
    margin: 0 0 20px;
}

/* Section Header */
.es-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.es-section-title h2 {
    margin: 0 0 4px;
}

.es-section-title .description {
    margin: 0;
}

.es-section-actions {
    display: flex;
    gap: 8px;
}

/* Form row group */
.es-form-row-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 600px) {
    .es-form-row-group {
        grid-template-columns: 1fr;
    }
    
    .es-admin-section .es-templates-grid {
        grid-template-columns: 1fr;
    }
    
    .es-admin-section .es-templates-tabs {
        flex-direction: column;
    }
    
    .es-section-header {
        flex-direction: column;
        gap: 12px;
    }
}
</style>
