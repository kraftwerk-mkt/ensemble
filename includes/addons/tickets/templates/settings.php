<?php
/**
 * Tickets Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/Tickets
 * 
 * Variables available:
 * @var array $settings Settings array (passed from render_settings())
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ensure $settings has defaults
$settings = wp_parse_args($settings, array(
    'currency'           => 'EUR',
    'price_format'       => 'symbol_before',
    'add_utm_params'     => false,
    'utm_source'         => 'ensemble',
    'utm_medium'         => 'event_widget',
    'utm_campaign'       => 'tickets',
    'track_clicks'       => false,
    'open_new_tab'       => true,
    'show_provider_logo' => true,
    'widget_title'       => __('Tickets', 'ensemble'),
    'button_text'        => __('Buy Tickets', 'ensemble'),
    'global_tickets'     => array(),
));
?>

<div class="es-tickets-settings">
    
    <div class="es-addon-settings-grid">
        
        <!-- General Settings -->
        <div class="es-addon-settings-section">
            <h4><?php _e('General Settings', 'ensemble'); ?></h4>
            
            <div class="es-addon-settings-row">
                <label class="es-addon-settings-label" for="tickets_currency">
                    <?php _e('Currency', 'ensemble'); ?>
                </label>
                <select name="currency" id="tickets_currency" class="es-addon-settings-select">
                    <option value="EUR" <?php selected($settings['currency'], 'EUR'); ?>>EUR (€)</option>
                    <option value="USD" <?php selected($settings['currency'], 'USD'); ?>>USD ($)</option>
                    <option value="GBP" <?php selected($settings['currency'], 'GBP'); ?>>GBP (£)</option>
                    <option value="CHF" <?php selected($settings['currency'], 'CHF'); ?>>CHF</option>
                </select>
            </div>
            
            <div class="es-addon-settings-row">
                <label class="es-addon-settings-label" for="tickets_price_format">
                    <?php _e('Price Format', 'ensemble'); ?>
                </label>
                <select name="price_format" id="tickets_price_format" class="es-addon-settings-select">
                    <option value="symbol_before" <?php selected($settings['price_format'], 'symbol_before'); ?>>
                        <?php _e('Symbol before price (€ 29.99)', 'ensemble'); ?>
                    </option>
                    <option value="symbol_after" <?php selected($settings['price_format'], 'symbol_after'); ?>>
                        <?php _e('Symbol after price (29.99 €)', 'ensemble'); ?>
                    </option>
                </select>
            </div>
            
            <div class="es-addon-settings-row">
                <label class="es-addon-settings-label" for="tickets_widget_title">
                    <?php _e('Widget Titel', 'ensemble'); ?>
                </label>
                <input type="text" 
                       name="widget_title" 
                       id="tickets_widget_title" 
                       class="es-addon-settings-input"
                       value="<?php echo esc_attr($settings['widget_title']); ?>"
                       placeholder="<?php esc_attr_e('Tickets', 'ensemble'); ?>">
            </div>
            
            <div class="es-addon-settings-row">
                <label class="es-addon-settings-label" for="tickets_button_text">
                    <?php _e('Button Text', 'ensemble'); ?>
                </label>
                <input type="text" 
                       name="button_text" 
                       id="tickets_button_text" 
                       class="es-addon-settings-input"
                       value="<?php echo esc_attr($settings['button_text']); ?>"
                       placeholder="<?php esc_attr_e('Buy Tickets', 'ensemble'); ?>">
            </div>
        </div>
        
        <!-- Display Options -->
        <div class="es-addon-settings-section">
            <h4><?php _e('Display Options', 'ensemble'); ?></h4>
            
            <div class="es-toggle-group">
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="show_provider_logo" 
                           value="1" 
                           <?php checked($settings['show_provider_logo']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Show provider name', 'ensemble'); ?></span>
                </label>
                
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="open_new_tab" 
                           value="1" 
                           <?php checked($settings['open_new_tab']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Open links in new tab', 'ensemble'); ?></span>
                </label>
                
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="track_clicks" 
                           value="1" 
                           <?php checked($settings['track_clicks']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Track ticket link clicks', 'ensemble'); ?></span>
                </label>
            </div>
        </div>
        
    </div>
    
    <!-- Global Tickets Section -->
    <div class="es-addon-settings-section" style="margin-top: 20px;">
        <h4><?php _e('Global Tickets', 'ensemble'); ?></h4>
        <p class="description" style="margin-bottom: 16px;">
            <?php _e('Define tickets that automatically appear on all events. Useful for festival passes or general admission tickets.', 'ensemble'); ?>
        </p>
        
        <div id="es-global-tickets-container">
            <div id="es-global-tickets-list" class="es-tickets-list" style="margin-bottom: 16px;">
                <!-- Global tickets will be rendered here via JS -->
            </div>
            
            <button type="button" class="button button-secondary" id="es-add-global-ticket">
                <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
                <?php _e('Add Global Ticket', 'ensemble'); ?>
            </button>
        </div>
        
        <!-- Hidden field to store global tickets JSON -->
        <input type="hidden" 
               name="global_tickets_json" 
               id="es-global-tickets-json"
               value="<?php echo esc_attr(json_encode($settings['global_tickets'] ?? array())); ?>">
    </div>
    
    <!-- Global Ticket Modal -->
    <div id="es-global-ticket-modal" class="es-ticket-wizard-modal" style="display: none;">
        <div class="es-ticket-wizard-modal-content" style="max-width: 600px;">
            <div class="es-ticket-wizard-modal-header">
                <h3><?php _e('Add Global Ticket', 'ensemble'); ?></h3>
                <button type="button" class="es-ticket-wizard-modal-close">&times;</button>
            </div>
            <div class="es-ticket-wizard-modal-body">
                <input type="hidden" id="es-gt-ticket-id" value="">
                
                <div class="es-form-row">
                    <label for="es-gt-provider"><?php _e('Provider', 'ensemble'); ?></label>
                    <select id="es-gt-provider" class="es-form-control">
                        <option value="custom"><?php _e('Custom Provider', 'ensemble'); ?></option>
                        <option value="eventbrite">Eventbrite</option>
                        <option value="eventim">Eventim</option>
                        <option value="ticketmaster">Ticketmaster</option>
                        <option value="reservix">Reservix</option>
                        <option value="tickets_io">tickets.io</option>
                    </select>
                </div>
                
                <div class="es-form-row">
                    <label for="es-gt-name"><?php _e('Ticket Name', 'ensemble'); ?></label>
                    <input type="text" id="es-gt-name" class="es-form-control" placeholder="<?php esc_attr_e('e.g. Festival Pass, Weekend Ticket', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-row">
                    <label for="es-gt-url"><?php _e('Ticket URL', 'ensemble'); ?> *</label>
                    <input type="url" id="es-gt-url" class="es-form-control" placeholder="https://..." required>
                </div>
                
                <div class="es-form-row-inline">
                    <div class="es-form-row">
                        <label for="es-gt-price"><?php _e('Price (min)', 'ensemble'); ?></label>
                        <input type="number" id="es-gt-price" class="es-form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="es-form-row">
                        <label for="es-gt-price-max"><?php _e('Price (max)', 'ensemble'); ?></label>
                        <input type="number" id="es-gt-price-max" class="es-form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                
                <div class="es-form-row">
                    <label for="es-gt-availability"><?php _e('Availability', 'ensemble'); ?></label>
                    <select id="es-gt-availability" class="es-form-control">
                        <option value="available"><?php _e('Available', 'ensemble'); ?></option>
                        <option value="limited"><?php _e('Limited', 'ensemble'); ?></option>
                        <option value="few_left"><?php _e('Few Left', 'ensemble'); ?></option>
                        <option value="presale"><?php _e('Presale', 'ensemble'); ?></option>
                        <option value="sold_out"><?php _e('Sold Out', 'ensemble'); ?></option>
                        <option value="cancelled"><?php _e('Cancelled', 'ensemble'); ?></option>
                    </select>
                </div>
                
                <div class="es-form-row">
                    <label for="es-gt-custom-text"><?php _e('Custom Text (optional)', 'ensemble'); ?></label>
                    <input type="text" id="es-gt-custom-text" class="es-form-control" placeholder="<?php esc_attr_e('e.g. All 3 days included', 'ensemble'); ?>">
                </div>
            </div>
            <div class="es-ticket-wizard-modal-footer">
                <button type="button" class="button button-secondary es-gt-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
                <button type="button" class="button button-primary es-gt-save"><?php _e('Save', 'ensemble'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- UTM Tracking -->
    <div class="es-addon-settings-section" style="margin-top: 20px;">
        <h4><?php _e('UTM Tracking Parameters', 'ensemble'); ?></h4>
        <p class="description" style="margin-bottom: 16px;">
            <?php _e('Automatically add UTM parameters to all ticket links for better tracking.', 'ensemble'); ?>
        </p>
        
        <div class="es-form-group" style="margin-bottom: 16px;">
            <label class="es-toggle">
                <input type="checkbox" 
                       name="add_utm_params" 
                       value="1" 
                       id="tickets_add_utm"
                       <?php checked($settings['add_utm_params']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Enable UTM parameters', 'ensemble'); ?></span>
            </label>
        </div>
        
        <div class="es-utm-fields" style="margin-top: 16px; <?php echo !$settings['add_utm_params'] ? 'opacity: 0.5;' : ''; ?>">
            <div class="es-addon-settings-grid">
                <div class="es-addon-settings-row">
                    <label class="es-addon-settings-label" for="tickets_utm_source">
                        <?php _e('UTM Source', 'ensemble'); ?>
                    </label>
                    <input type="text" 
                           name="utm_source" 
                           id="tickets_utm_source"
                           class="es-addon-settings-input"
                           value="<?php echo esc_attr($settings['utm_source']); ?>"
                           placeholder="ensemble">
                </div>
                
                <div class="es-addon-settings-row">
                    <label class="es-addon-settings-label" for="tickets_utm_medium">
                        <?php _e('UTM Medium', 'ensemble'); ?>
                    </label>
                    <input type="text" 
                           name="utm_medium" 
                           id="tickets_utm_medium"
                           class="es-addon-settings-input"
                           value="<?php echo esc_attr($settings['utm_medium']); ?>"
                           placeholder="event_widget">
                </div>
                
                <div class="es-addon-settings-row">
                    <label class="es-addon-settings-label" for="tickets_utm_campaign">
                        <?php _e('UTM Campaign', 'ensemble'); ?>
                    </label>
                    <input type="text" 
                           name="utm_campaign" 
                           id="tickets_utm_campaign"
                           class="es-addon-settings-input"
                           value="<?php echo esc_attr($settings['utm_campaign']); ?>"
                           placeholder="tickets">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Shortcodes Info -->
    <div class="es-addon-settings-section" style="margin-top: 20px;">
        <h4><?php _e('Shortcodes', 'ensemble'); ?></h4>
        <p class="description">
            <?php _e('Use these shortcodes to display tickets anywhere on your site:', 'ensemble'); ?>
        </p>
        
        <table class="widefat" style="margin-top: 12px;">
            <thead>
                <tr>
                    <th style="width: 40%;"><?php _e('Shortcode', 'ensemble'); ?></th>
                    <th><?php _e('Description', 'ensemble'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[ensemble_tickets]</code></td>
                    <td><?php _e('Displays all tickets for the current event', 'ensemble'); ?></td>
                </tr>
                <tr>
                    <td><code>[ensemble_tickets event_id="123"]</code></td>
                    <td><?php _e('Displays tickets for a specific event', 'ensemble'); ?></td>
                </tr>
                <tr>
                    <td><code>[ensemble_tickets style="compact"]</code></td>
                    <td><?php _e('Compact display (default, compact, list)', 'ensemble'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
</div>

<script>
jQuery(document).ready(function($) {
    $('#tickets_add_utm').on('change', function() {
        $('.es-utm-fields').css('opacity', this.checked ? '1' : '0.5');
    });
    
    // Global Tickets Manager
    var GlobalTicketsManager = {
        tickets: [],
        $list: $('#es-global-tickets-list'),
        $json: $('#es-global-tickets-json'),
        $modal: $('#es-global-ticket-modal'),
        
        init: function() {
            // Load existing tickets
            try {
                this.tickets = JSON.parse(this.$json.val() || '[]');
            } catch(e) {
                this.tickets = [];
            }
            
            this.render();
            this.bindEvents();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Add ticket button
            $('#es-add-global-ticket').on('click', function() {
                self.openModal();
            });
            
            // Modal close
            this.$modal.on('click', '.es-ticket-wizard-modal-close, .es-gt-cancel', function() {
                self.closeModal();
            });
            
            // Save ticket
            this.$modal.on('click', '.es-gt-save', function() {
                self.saveTicket();
            });
            
            // Edit ticket
            this.$list.on('click', '.es-ticket-edit', function() {
                var id = $(this).closest('.es-ticket-item').data('id');
                self.openModal(id);
            });
            
            // Delete ticket
            this.$list.on('click', '.es-ticket-delete', function() {
                if (confirm('<?php _e('Delete this global ticket?', 'ensemble'); ?>')) {
                    var id = $(this).closest('.es-ticket-item').data('id');
                    self.deleteTicket(id);
                }
            });
        },
        
        render: function() {
            var self = this;
            this.$list.empty();
            
            if (this.tickets.length === 0) {
                this.$list.html('<p class="description"><?php _e('No global tickets defined yet.', 'ensemble'); ?></p>');
                return;
            }
            
            this.tickets.forEach(function(ticket) {
                var priceText = ticket.price > 0 ? '€ ' + parseFloat(ticket.price).toFixed(2) : '<?php _e('Free', 'ensemble'); ?>';
                if (ticket.price_max > ticket.price) {
                    priceText += ' - € ' + parseFloat(ticket.price_max).toFixed(2);
                }
                
                var html = '<div class="es-ticket-item" data-id="' + ticket.id + '" style="display: flex; align-items: center; padding: 12px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px;">';
                html += '<div style="flex: 1;">';
                html += '<strong>' + self.escapeHtml(ticket.name || '<?php _e('Unnamed Ticket', 'ensemble'); ?>') + '</strong>';
                if (ticket.custom_text) {
                    html += '<br><small style="color: #666;">' + self.escapeHtml(ticket.custom_text) + '</small>';
                }
                html += '<br><span style="color: #888;">' + priceText + '</span>';
                html += '</div>';
                html += '<div style="display: flex; gap: 8px;">';
                html += '<button type="button" class="button button-small es-ticket-edit"><?php _e('Edit', 'ensemble'); ?></button>';
                html += '<button type="button" class="button button-small es-ticket-delete" style="color: #a00;"><?php _e('Delete', 'ensemble'); ?></button>';
                html += '</div>';
                html += '</div>';
                
                self.$list.append(html);
            });
        },
        
        openModal: function(ticketId) {
            // Reset form
            this.$modal.find('#es-gt-ticket-id').val('');
            this.$modal.find('#es-gt-provider').val('custom');
            this.$modal.find('#es-gt-name').val('');
            this.$modal.find('#es-gt-url').val('');
            this.$modal.find('#es-gt-price').val('');
            this.$modal.find('#es-gt-price-max').val('');
            this.$modal.find('#es-gt-availability').val('available');
            this.$modal.find('#es-gt-custom-text').val('');
            
            if (ticketId) {
                var ticket = this.tickets.find(function(t) { return t.id === ticketId; });
                if (ticket) {
                    this.$modal.find('#es-gt-ticket-id').val(ticket.id);
                    this.$modal.find('#es-gt-provider').val(ticket.provider || 'custom');
                    this.$modal.find('#es-gt-name').val(ticket.name || '');
                    this.$modal.find('#es-gt-url').val(ticket.url || '');
                    this.$modal.find('#es-gt-price').val(ticket.price || '');
                    this.$modal.find('#es-gt-price-max').val(ticket.price_max || '');
                    this.$modal.find('#es-gt-availability').val(ticket.availability || 'available');
                    this.$modal.find('#es-gt-custom-text').val(ticket.custom_text || '');
                    
                    this.$modal.find('.es-ticket-wizard-modal-header h3').text('<?php _e('Edit Global Ticket', 'ensemble'); ?>');
                }
            } else {
                this.$modal.find('.es-ticket-wizard-modal-header h3').text('<?php _e('Add Global Ticket', 'ensemble'); ?>');
            }
            
            this.$modal.show();
        },
        
        closeModal: function() {
            this.$modal.hide();
        },
        
        saveTicket: function() {
            var url = this.$modal.find('#es-gt-url').val();
            if (!url) {
                alert('<?php _e('Please enter a ticket URL.', 'ensemble'); ?>');
                this.$modal.find('#es-gt-url').focus();
                return;
            }
            
            var ticketData = {
                id: this.$modal.find('#es-gt-ticket-id').val() || 'global_' + Date.now(),
                provider: this.$modal.find('#es-gt-provider').val(),
                name: this.$modal.find('#es-gt-name').val(),
                url: url,
                price: parseFloat(this.$modal.find('#es-gt-price').val()) || 0,
                price_max: parseFloat(this.$modal.find('#es-gt-price-max').val()) || 0,
                currency: '<?php echo esc_js($settings['currency'] ?? 'EUR'); ?>',
                availability: this.$modal.find('#es-gt-availability').val(),
                custom_text: this.$modal.find('#es-gt-custom-text').val(),
                is_global: true
            };
            
            // Update or add
            var existingIndex = this.tickets.findIndex(function(t) { return t.id === ticketData.id; });
            if (existingIndex > -1) {
                this.tickets[existingIndex] = ticketData;
            } else {
                this.tickets.push(ticketData);
            }
            
            this.updateJson();
            this.render();
            this.closeModal();
        },
        
        deleteTicket: function(ticketId) {
            this.tickets = this.tickets.filter(function(t) { return t.id !== ticketId; });
            this.updateJson();
            this.render();
        },
        
        updateJson: function() {
            this.$json.val(JSON.stringify(this.tickets));
        },
        
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    GlobalTicketsManager.init();
});
</script>
