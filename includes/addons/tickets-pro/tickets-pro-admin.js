/**
 * Tickets Pro - Admin JavaScript
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro
 * @since 3.1.0
 */

(function($) {
    'use strict';

    /**
     * Tickets Pro Admin
     */
    var ESTicketsProAdmin = {
        
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
            // Dismissible notices
            $(document).on('click', '.notice.is-dismissible .notice-dismiss', function() {
                $(this).closest('.notice').fadeOut();
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ESTicketsProAdmin.init();
    });

})(jQuery);
