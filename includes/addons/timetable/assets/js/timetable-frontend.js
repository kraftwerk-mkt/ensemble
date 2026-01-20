/**
 * Ensemble Timetable Frontend JavaScript
 * 
 * Handles day filtering for both layouts
 *
 * @package Ensemble
 * @subpackage Addons/Timetable
 */

(function($) {
    'use strict';

    const ESTimetableFrontend = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Day filter click
            $(document).on('click', '.es-day-filter', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const $container = $btn.closest('.es-timetable-frontend');
                const day = $btn.data('day');

                // Update active state
                $container.find('.es-day-filter').removeClass('active');
                $btn.addClass('active');

                // Filter based on layout
                const layout = $container.data('layout');
                
                if (layout === 'vertical') {
                    // Vertical: Filter day tables
                    ESTimetableFrontend.filterDayTables($container, day);
                } else {
                    // Horizontal: Filter day-columns
                    ESTimetableFrontend.filterDayColumns($container, day);
                }
            });
        },

        // Horizontal Layout: Show/hide entire day columns
        filterDayColumns: function($container, day) {
            const $dayColumns = $container.find('.es-day-column');

            if (day === 'all') {
                $dayColumns.removeClass('es-hidden').css('display', '');
            } else {
                $dayColumns.each(function() {
                    const columnDay = $(this).data('day');
                    if (columnDay === day) {
                        $(this).removeClass('es-hidden').css('display', '');
                    } else {
                        $(this).addClass('es-hidden').css('display', 'none');
                    }
                });
            }
        },

        // Vertical Layout: Show/hide entire day tables
        filterDayTables: function($container, day) {
            const $dayTables = $container.find('.es-day-table');

            if (day === 'all') {
                $dayTables.removeClass('es-hidden').css('display', '');
            } else {
                $dayTables.each(function() {
                    const tableDay = $(this).data('day');
                    if (tableDay === day) {
                        $(this).removeClass('es-hidden').css('display', '');
                    } else {
                        $(this).addClass('es-hidden').css('display', 'none');
                    }
                });
            }
        }
    };

    // Initialize
    $(document).ready(function() {
        if ($('.es-timetable-frontend').length) {
            ESTimetableFrontend.init();
        }
    });

})(jQuery);
