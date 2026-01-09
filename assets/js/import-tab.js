/**
 * Ensemble Import Tab JavaScript
 * Handles the complete import workflow with event selection
 *
 * @package Ensemble
 */

(function($) {
    'use strict';

    const EnsembleImport = {
        currentStep: 1,
        previewData: null,
        selectedUIDs: [],

        init: function() {
            this.bindEvents();
            this.showStep(1);
        },

        bindEvents: function() {
            // Source selection
            $('input[name="import_source"]').on('change', this.handleSourceChange.bind(this));
            
            // Preview button
            $('#es-preview-btn').on('click', this.handlePreview.bind(this));
            
            // Update mode selection
            $('input[name="update_mode"]').on('change', this.updateSummary.bind(this));
            
            // Selection buttons
            $('#es-select-all-btn').on('click', this.selectAll.bind(this));
            $('#es-deselect-all-btn').on('click', this.deselectAll.bind(this));
            
            // Event selection checkboxes (delegated)
            $(document).on('change', '.es-event-checkbox', this.handleEventSelection.bind(this));
            
            // Import button
            $('#es-import-selected-btn').on('click', this.handleImport.bind(this));
            
            // Navigation buttons
            $('#es-back-to-source-btn').on('click', () => this.showStep(1));
            $('#es-import-another-btn').on('click', this.resetImport.bind(this));
            
            // Date filter change
            $('input[name="date_filter"]').on('change', this.handleDateFilterChange.bind(this));
        },

        handleSourceChange: function(e) {
            const source = $(e.target).val();
            
            if (source === 'url') {
                $('#es-url-input').show();
                $('#es-file-input').hide();
            } else {
                $('#es-url-input').hide();
                $('#es-file-input').show();
            }
        },

        handlePreview: function() {
            const source = $('input[name="import_source"]:checked').val();
            let sourceData = null;
            
            if (source === 'url') {
                sourceData = $('#es-ical-url').val().trim();
                if (!sourceData) {
                    this.showMessage('Please enter an iCal URL', 'error');
                    return;
                }
            } else {
                const fileInput = document.getElementById('es-ical-file');
                if (!fileInput.files || fileInput.files.length === 0) {
                    this.showMessage('Please select a file', 'error');
                    return;
                }
                sourceData = fileInput.files[0];
            }
            
            this.loadPreview(source, sourceData);
        },

        loadPreview: function(sourceType, sourceData) {
            const $container = $('#es-events-preview-container');
            $container.html('<div class="es-loading">Loading preview...</div>');
            
            $('#es-preview-btn').prop('disabled', true).text('Loading...');
            
            const formData = new FormData();
            formData.append('action', 'ensemble_import_preview');
            formData.append('nonce', ensembleImportData.nonce);
            formData.append('source_type', sourceType);
            
            if (sourceType === 'url') {
                formData.append('source', sourceData);
            } else {
                formData.append('source', sourceData);
            }
            
            $.ajax({
                url: ensembleImportData.ajaxUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success && response.data) {
                        this.previewData = response.data;
                        this.renderPreview(response.data);
                        this.showStep(2);
                    } else {
                        const errorMsg = response.data && response.data.message 
                            ? response.data.message 
                            : (typeof response.data === 'string' ? response.data : 'Failed to load preview');
                        this.showMessage(errorMsg, 'error');
                        $container.html('<div class="es-empty-state"><p>Failed to load events</p></div>');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Preview error:', error);
                    this.showMessage('Error loading preview: ' + error, 'error');
                    $container.html('<div class="es-empty-state"><p>Error loading events</p></div>');
                },
                complete: () => {
                    $('#es-preview-btn').prop('disabled', false).text('Preview Events');
                }
            });
        },

        renderPreview: function(data) {
            const $container = $('#es-events-preview-container');
            
            // Update summary
            $('#es-total-events').text(data.total || 0);
            $('#es-new-events').text(data.new || 0);
            $('#es-existing-events').text(data.existing || 0);
            
            if (!data.events || data.events.length === 0) {
                $container.html('<div class="es-empty-state"><p>No events found</p></div>');
                return;
            }
            
            // Select all new events by default
            this.selectedUIDs = data.events
                .filter(event => event.existing_status === 'new')
                .map(event => event.uid);
            
            // Render events
            let html = '<div class="es-event-preview-list">';
            
            data.events.forEach((event, index) => {
                const isExisting = event.existing_status === 'exists';
                const isSelected = this.selectedUIDs.includes(event.uid);
                
                html += `
                    <div class="es-event-preview-item ${isExisting ? 'es-event-exists' : ''}">
                        <div class="es-event-preview-checkbox">
                            <input type="checkbox" 
                                   class="es-event-checkbox" 
                                   data-uid="${event.uid}" 
                                   ${isSelected ? 'checked' : ''}>
                        </div>
                        <div class="es-event-preview-details">
                            <div class="es-event-preview-title">
                                ${this.escapeHtml(event.title)}
                                <span class="es-event-status-badge es-event-status-${isExisting ? 'exists' : 'new'}">
                                    ${isExisting ? 'Exists' : 'New'}
                                </span>
                            </div>
                            <div class="es-event-preview-meta">
                                ${event.date ? `
                                    <span class="es-event-preview-meta-item">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        ${this.formatDate(event.date)}
                                    </span>
                                ` : ''}
                                ${event.location_raw ? `
                                    <span class="es-event-preview-meta-item">
                                        <span class="dashicons dashicons-location"></span>
                                        ${this.escapeHtml(event.location_raw)}
                                        ${event.location_match && event.location_match.status === 'matched' ? 
                                            `<span class="dashicons dashicons-yes-alt" style="color: var(--es-success);"></span>` : 
                                            ''}
                                    </span>
                                ` : ''}
                                ${event.is_recurring ? `
                                    <span class="es-event-preview-meta-item">
                                        <span class="dashicons dashicons-update"></span>
                                        Recurring
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            $container.html(html);
            
            this.updateSelectedCount();
        },

        handleEventSelection: function(e) {
            const uid = $(e.target).data('uid');
            const isChecked = $(e.target).is(':checked');
            
            if (isChecked) {
                if (!this.selectedUIDs.includes(uid)) {
                    this.selectedUIDs.push(uid);
                }
            } else {
                this.selectedUIDs = this.selectedUIDs.filter(id => id !== uid);
            }
            
            this.updateSelectedCount();
        },

        selectAll: function() {
            if (!this.previewData || !this.previewData.events) return;
            
            this.selectedUIDs = this.previewData.events.map(event => event.uid);
            $('.es-event-checkbox').prop('checked', true);
            this.updateSelectedCount();
        },

        deselectAll: function() {
            this.selectedUIDs = [];
            $('.es-event-checkbox').prop('checked', false);
            this.updateSelectedCount();
        },

        updateSelectedCount: function() {
            $('#es-selected-count').text(this.selectedUIDs.length + ' selected');
            
            // Enable/disable import button
            $('#es-import-selected-btn').prop('disabled', this.selectedUIDs.length === 0);
        },

        updateSummary: function() {
            // This would update statistics based on update mode
            // For now, just a placeholder
        },

        handleImport: function() {
            if (this.selectedUIDs.length === 0) {
                this.showMessage('Please select at least one event to import', 'error');
                return;
            }
            
            const sourceType = $('input[name="import_source"]:checked').val();
            const updateMode = $('input[name="update_mode"]:checked').val();
            
            let sourceData = null;
            if (sourceType === 'url') {
                sourceData = $('#es-ical-url').val().trim();
            } else {
                const fileInput = document.getElementById('es-ical-file');
                sourceData = fileInput.files[0];
            }
            
            this.performImport(sourceType, sourceData, updateMode);
        },

        performImport: function(sourceType, sourceData, updateMode) {
            $('#es-import-selected-btn').prop('disabled', true).text('Importing...');
            
            const formData = new FormData();
            formData.append('action', 'ensemble_import_execute');
            formData.append('nonce', ensembleImportData.nonce);
            formData.append('source_type', sourceType);
            formData.append('update_mode', updateMode);
            formData.append('selected_uids', JSON.stringify(this.selectedUIDs));
            
            if (sourceType === 'url') {
                formData.append('source', sourceData);
            } else {
                formData.append('source', sourceData);
            }
            
            $.ajax({
                url: ensembleImportData.ajaxUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success && response.data) {
                        this.showImportResults(response.data);
                        this.showStep(3);
                        this.showMessage('Import completed successfully!', 'success');
                    } else {
                        const errorMsg = response.data && response.data.message 
                            ? response.data.message 
                            : (typeof response.data === 'string' ? response.data : 'Import failed');
                        this.showMessage(errorMsg, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Import error:', error);
                    this.showMessage('Error during import: ' + error, 'error');
                },
                complete: () => {
                    $('#es-import-selected-btn').prop('disabled', false).text('Import Selected Events');
                }
            });
        },

        showImportResults: function(results) {
            // Update counts
            $('#es-result-created').text(results.created || 0);
            $('#es-result-updated').text(results.updated || 0);
            $('#es-result-skipped').text(results.skipped || 0);
            $('#es-result-failed').text(results.failed || 0);
            
            // Show detailed results
            const $details = $('#es-import-details');
            
            if (results.details && results.details.length > 0) {
                let html = '<h3>Import Details</h3><div class="es-import-detail-list">';
                
                results.details.forEach(detail => {
                    const actionClass = detail.action.replace('_', '-');
                    const icon = this.getActionIcon(detail.action);
                    
                    html += `
                        <div class="es-import-detail-item ${actionClass}">
                            <span class="dashicons dashicons-${icon}"></span>
                            <span>${this.escapeHtml(detail.event)}</span>
                            <span class="es-detail-action">${detail.action}</span>
                        </div>
                    `;
                });
                
                html += '</div>';
                $details.html(html);
            }
            
            if (results.errors && results.errors.length > 0) {
                let errorHtml = '<h3 style="color: var(--es-danger);">Errors</h3><div class="es-import-detail-list">';
                
                results.errors.forEach(error => {
                    errorHtml += `
                        <div class="es-import-detail-item failed">
                            <span class="dashicons dashicons-warning"></span>
                            <span>${this.escapeHtml(error.event)}: ${this.escapeHtml(error.error)}</span>
                        </div>
                    `;
                });
                
                errorHtml += '</div>';
                $details.append(errorHtml);
            }
        },

        getActionIcon: function(action) {
            const icons = {
                'created': 'yes-alt',
                'created_duplicate': 'yes-alt',
                'updated': 'update',
                'skipped': 'dismiss',
                'failed': 'warning'
            };
            return icons[action] || 'marker';
        },

        showStep: function(step) {
            this.currentStep = step;
            
            // Hide all steps
            $('.es-import-step').hide();
            
            // Show current step
            $(`#es-import-step-${step}`).show();
            
            // Update step indicator
            $('.es-step').removeClass('active completed');
            
            for (let i = 1; i <= step; i++) {
                if (i === step) {
                    $(`.es-step[data-step="${i}"]`).addClass('active');
                } else {
                    $(`.es-step[data-step="${i}"]`).addClass('completed');
                }
            }
        },

        resetImport: function() {
            this.previewData = null;
            this.selectedUIDs = [];
            $('#es-ical-url').val('');
            $('#es-ical-file').val('');
            $('input[name="update_mode"][value="skip"]').prop('checked', true);
            this.showStep(1);
        },

        handleDateFilterChange: function(e) {
            const value = $(e.target).val();
            
            if (value === 'custom') {
                $('#es-custom-date-range').show();
            } else {
                $('#es-custom-date-range').hide();
            }
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
        if ($('.es-import-wrap').length) {
            EnsembleImport.init();
        }
    });

})(jQuery);
