/**
 * Tickets Pro - Frontend JavaScript
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro
 * @since 3.1.0
 */

(function($) {
    'use strict';

    /**
     * Tickets Pro Frontend
     */
    var ESTicketsPro = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.updateOrderSummary();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            
            // Quantity buttons
            $(document).on('click', '.es-qty-minus', function() {
                var $input = $(this).siblings('input');
                var val = parseInt($input.val()) || 0;
                var min = parseInt($input.attr('min')) || 0;
                if (val > min) {
                    $input.val(val - 1).trigger('change');
                }
            });
            
            $(document).on('click', '.es-qty-plus', function() {
                var $input = $(this).siblings('input');
                var val = parseInt($input.val()) || 0;
                var max = parseInt($input.attr('max')) || 99;
                if (val < max) {
                    $input.val(val + 1).trigger('change');
                }
            });
            
            // Quantity change
            $(document).on('change', '.es-ticket-qty-input', function() {
                self.updateOrderSummary();
            });
            
            // Gateway selection
            $(document).on('change', '.es-payment-gateway-input', function() {
                $('.es-payment-gateway-option').removeClass('es-selected');
                $(this).closest('.es-payment-gateway-option').addClass('es-selected');
            });
            
            // Checkout form submit
            $(document).on('submit', '.es-ticket-checkout-form', function(e) {
                e.preventDefault();
                self.processCheckout($(this));
            });
        },
        
        /**
         * Update order summary
         */
        updateOrderSummary: function() {
            var $form = $('.es-ticket-checkout-form');
            if (!$form.length) return;
            
            var total = 0;
            var items = [];
            
            $form.find('.es-ticket-item').each(function() {
                var $item = $(this);
                var qty = parseInt($item.find('.es-ticket-qty-input').val()) || 0;
                var price = parseFloat($item.data('price')) || 0;
                var name = $item.data('name');
                
                if (qty > 0) {
                    var subtotal = qty * price;
                    total += subtotal;
                    items.push({
                        name: name,
                        qty: qty,
                        price: price,
                        subtotal: subtotal
                    });
                }
            });
            
            // Update summary display
            var $summary = $('.es-order-summary-items');
            $summary.empty();
            
            if (items.length === 0) {
                $summary.html('<p class="es-no-items">' + esTicketsProFrontend.i18n.selectTicket + '</p>');
                $('.es-checkout-btn').prop('disabled', true);
            } else {
                items.forEach(function(item) {
                    $summary.append(
                        '<div class="es-order-summary-item">' +
                            '<span>' + item.qty + 'x ' + item.name + '</span>' +
                            '<span>' + ESTicketsPro.formatPrice(item.subtotal) + '</span>' +
                        '</div>'
                    );
                });
                $('.es-checkout-btn').prop('disabled', false);
            }
            
            // Update total
            $('.es-total-price').text(this.formatPrice(total));
            $form.find('input[name="total"]').val(total);
        },
        
        /**
         * Format price
         */
        formatPrice: function(amount) {
            return '€' + amount.toFixed(2).replace('.', ',');
        },
        
        /**
         * Process checkout
         */
        processCheckout: function($form) {
            var self = this;
            var $btn = $form.find('.es-checkout-btn');
            
            // Validate
            var hasTickets = false;
            $form.find('.es-ticket-qty-input').each(function() {
                if (parseInt($(this).val()) > 0) {
                    hasTickets = true;
                }
            });
            
            if (!hasTickets) {
                alert(esTicketsProFrontend.i18n.selectTicket);
                return;
            }
            
            // Get selected gateway
            var gateway = $form.find('.es-payment-gateway-input:checked').val();
            if (!gateway) {
                alert('Please select a payment method.');
                return;
            }
            
            // Disable form
            $form.addClass('es-loading');
            $btn.prop('disabled', true).text(esTicketsProFrontend.i18n.processing);
            
            // Collect ticket data
            var tickets = [];
            $form.find('.es-ticket-item').each(function() {
                var $item = $(this);
                var qty = parseInt($item.find('.es-ticket-qty-input').val()) || 0;
                if (qty > 0) {
                    tickets.push({
                        category_id: $item.data('category-id'),
                        quantity: qty
                    });
                }
            });
            
            // Submit booking first
            $.ajax({
                url: esTicketsProFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_create_ticket_booking',
                    nonce: esTicketsProFrontend.nonce,
                    event_id: $form.find('input[name="event_id"]').val(),
                    tickets: tickets,
                    customer_name: $form.find('input[name="customer_name"]').val(),
                    customer_email: $form.find('input[name="customer_email"]').val(),
                    customer_phone: $form.find('input[name="customer_phone"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Now process payment
                        self.processPayment(response.data.booking_id, gateway, $form);
                    } else {
                        alert(response.data.message);
                        $form.removeClass('es-loading');
                        $btn.prop('disabled', false).text($btn.data('text'));
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $form.removeClass('es-loading');
                    $btn.prop('disabled', false).text($btn.data('text'));
                }
            });
        },
        
        /**
         * Process payment
         */
        processPayment: function(bookingId, gateway, $form) {
            var $btn = $form.find('.es-checkout-btn');
            
            $btn.text(esTicketsProFrontend.i18n.redirecting);
            
            $.ajax({
                url: esTicketsProFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_process_ticket_payment',
                    nonce: esTicketsProFrontend.nonce,
                    booking_id: bookingId,
                    gateway: gateway
                },
                success: function(response) {
                    if (response.success && response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert(response.data.message || 'Payment failed.');
                        $form.removeClass('es-loading');
                        $btn.prop('disabled', false).text($btn.data('text'));
                    }
                },
                error: function() {
                    alert('Payment processing failed. Please try again.');
                    $form.removeClass('es-loading');
                    $btn.prop('disabled', false).text($btn.data('text'));
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ESTicketsPro.init();
    });

})(jQuery);
