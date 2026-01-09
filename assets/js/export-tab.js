/**
 * Ensemble Export Tab JavaScript
 * Handles event export to iCal format
 *
 * @package Ensemble
 */

(function($) {
    'use strict';

    const EnsembleExport = {
        previewData: null,

        init: function() {
            this.bindEvents();
            this.updateExportCount();
        },

        bindEvents: function() {
            // Date filter change
            $('input[name="date_filter"]').on('change', this.handleDateFilterChange.bind(this));
            
            // Category/Location filter change
            $('#es-category-filter, #es-location-filter').on('change', this.updateExportCount.bind(this));
            
            // Date inputs change
            $('#es-date-from, #es-date-to').on('change', this.updateExportCount.bind(this));
            
            // Preview button
            $('#es-preview-export-btn').on('click', this.loadPreview.bind(this));
            
            // Export button
            $('#es-export-btn').on('click', this.handleExport.bind(this));
        },

        handleDateFilterChange: function(e) {
            const value = $(e.target).val();
            
            if (value === 'custom') {
                $('#es-custom-date-range').show();
            } else {
                $('#es-custom-date-range').hide();
            }
            
            this.updateExportCount();
        },

        updateExportCount: function() {
            const filters = this.getFilters();
            
            $.ajax({
                url: ensembleExportData.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'ensemble_export_count',
                    nonce: ensembleExportData.nonce,
                    ...filters
                },
                success: (response) => {
                    if (response.success) {
                        $('#es-export-count').text(response.data.count || 0);
                        
                        // Enable export button if there are events
                        $('#es-export-btn').prop('disabled', response.data.count === 0);
                    }
                }
            });
        },

        loadPreview: function() {
            const filters = this.getFilters();
            const $list = $('#es-export-preview-list');
            
            $list.html('<div class="es-loading">Loading preview...</div>').show();
            
            $.ajax({
                url: ensembleExportData.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'ensemble_export_preview',
                    nonce: ensembleExportData.nonce,
                    ...filters
                },
                success: (response) => {
                    if (response.success && response.data.events) {
                        this.renderPreview(response.data.events);
                    } else {
                        $list.html('<div class="es-empty-state"><p>No events found</p></div>');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Preview error:', error);
                    $list.html('<div class="es-empty-state"><p>Error loading preview</p></div>');
                }
            });
        },

        renderPreview: function(events) {
            const $list = $('#es-export-preview-list');
            
            if (events.length === 0) {
                $list.html('<div class="es-empty-state"><p>No events found</p></div>');
                return;
            }
            
            let html = '';
            
            events.forEach(event => {
                html += `
                    <div class="es-export-event-item">
                        <div>
                            <div class="es-export-event-title">${this.escapeHtml(event.title)}</div>
                            <div class="es-export-event-date">${this.formatDate(event.date)}</div>
                        </div>
                    </div>
                `;
            });
            
            $list.html(html);
        },

        handleExport: function() {
            const filters = this.getFilters();
            
            // Show loading state
            $('#es-export-btn').prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: ensembleExportData.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'ensemble_export_events',
                    nonce: ensembleExportData.nonce,
                    ...filters
                },
                success: (response) => {
                    if (response.success && response.data.download_url) {
                        // Trigger download
                        window.location.href = response.data.download_url;
                        this.showMessage('Export generated successfully!', 'success');
                    } else {
                        this.showMessage(response.data || 'Export failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Export error:', error);
                    this.showMessage('Error during export: ' + error, 'error');
                },
                complete: () => {
                    $('#es-export-btn').prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Download Export');
                }
            });
        },

        getFilters: function() {
            const dateFilter = $('input[name="date_filter"]:checked').val();
            const filters = {
                date_filter: dateFilter,
                category_id: $('#es-category-filter').val(),
                location_id: $('#es-location-filter').val()
            };
            
            if (dateFilter === 'custom') {
                filters.date_from = $('#es-date-from').val();
                filters.date_to = $('#es-date-to').val();
            }
            
            return filters;
        },

        showMessage: function(message, type) {
            const $message = $('#es-message');
            $message
                .removeClass('success error')
                .addClass(type)
                .text(message)
                .fadeIn();
            
            setTimeout(() => {
                $message.fadeOut();
            }, 4000);
        },

        formatDate: function(dateString) {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.es-export-wrap').length) {
            EnsembleExport.init();
        }
    });

})(jQuery);
