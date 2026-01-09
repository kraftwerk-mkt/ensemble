/**
 * Ensemble Tickets Add-on - Frontend JavaScript
 * 
 * Handles click tracking and link behavior
 *
 * @package Ensemble
 * @subpackage Addons/Tickets
 */

(function($) {
    'use strict';
    
    /**
     * Tickets Frontend Handler
     */
    var EnsembleTickets = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Track ticket clicks
            $(document).on('click', '.es-ticket-button[data-track]', this.handleTicketClick.bind(this));
            
            // Handle new tab preference
            if (ensembleTickets.newTab) {
                $('.es-ticket-button').attr('target', '_blank').attr('rel', 'noopener noreferrer');
            }
        },
        
        /**
         * Handle ticket click
         * 
         * @param {Event} e
         */
        handleTicketClick: function(e) {
            var $button = $(e.currentTarget);
            var eventId = $button.data('event-id');
            var ticketIndex = $button.data('ticket-index');
            
            // Track click if enabled
            if (ensembleTickets.trackClicks && eventId) {
                this.trackClick(eventId, ticketIndex);
            }
            
            // Don't prevent default - let the link work normally
        },
        
        /**
         * Track click via AJAX
         * 
         * @param {number} eventId
         * @param {number} ticketIndex
         */
        trackClick: function(eventId, ticketIndex) {
            $.ajax({
                url: ensembleTickets.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_track_ticket_click',
                    nonce: ensembleTickets.nonce,
                    event_id: eventId,
                    ticket_index: ticketIndex
                },
                // Fire and forget - don't wait for response
                async: true
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        EnsembleTickets.init();
    });
    
})(jQuery);
