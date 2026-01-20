/**
 * Ensemble U18 Muttizettel - Admin Scripts
 * 
 * @package Ensemble
 * @subpackage Addons/U18 Authorization
 * @since 3.0.0
 */

(function($) {
    'use strict';
    
    var esU18Admin = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Status update buttons
            $(document).on('click', '.es-u18-action-btn.action-approve', this.approveAuthorization);
            $(document).on('click', '.es-u18-action-btn.action-reject', this.rejectAuthorization);
            $(document).on('click', '.es-u18-action-btn.action-checkin', this.checkinAuthorization);
            $(document).on('click', '.es-u18-action-btn.action-delete', this.deleteAuthorization);
            
            // View details
            $(document).on('click', '.es-u18-action-btn.action-view', this.viewDetails);
            
            // Modal close
            $(document).on('click', '.es-u18-modal-close', this.closeModal);
            $(document).on('click', '.es-u18-modal', function(e) {
                if (e.target === this) {
                    esU18Admin.closeModal();
                }
            });
            
            // Filter form
            $(document).on('change', '.es-u18-filter-select', this.filterTable);
            $(document).on('keyup', '.es-u18-search-input', this.debounce(this.filterTable, 300));
            
            // Resend emails
            $(document).on('click', '.es-u18-action-btn.action-resend', this.resendEmails);
            
            // Download PDF
            $(document).on('click', '.es-u18-action-btn.action-pdf', this.downloadPdf);
        },
        
        approveAuthorization: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var id = $btn.data('id');
            
            if (!confirm(window.esU18Admin?.i18n?.confirm_approve || 'Approve this authorization?')) {
                return;
            }
            
            esU18Admin.updateStatus(id, 'approved', $btn);
        },
        
        rejectAuthorization: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var id = $btn.data('id');
            
            if (!confirm(window.esU18Admin?.i18n?.confirm_reject || 'Reject this authorization?')) {
                return;
            }
            
            esU18Admin.updateStatus(id, 'rejected', $btn);
        },
        
        checkinAuthorization: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var id = $btn.data('id');
            
            esU18Admin.doCheckin(id, $btn);
        },
        
        deleteAuthorization: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var id = $btn.data('id');
            
            if (!confirm(window.esU18Admin?.i18n?.confirm_delete || 'Delete this authorization?')) {
                return;
            }
            
            $.ajax({
                url: window.esU18Admin?.ajaxUrl || ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_delete_u18_authorization',
                    id: id,
                    nonce: window.esU18Admin?.nonce || ''
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data?.message || 'Error');
                    }
                },
                error: function() {
                    alert(window.esU18Admin?.i18n?.error || 'An error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        updateStatus: function(id, status, $btn) {
            $.ajax({
                url: window.esU18Admin?.ajaxUrl || ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_update_u18_status',
                    id: id,
                    status: status,
                    nonce: window.esU18Admin?.nonce || ''
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // Update the status pill in the row
                        var $row = $btn.closest('tr');
                        var $statusCell = $row.find('.es-u18-status-pill');
                        
                        $statusCell
                            .removeClass('es-u18-status-submitted es-u18-status-reviewed es-u18-status-approved es-u18-status-rejected')
                            .addClass('es-u18-status-' + status)
                            .text(response.data?.status_label || status);
                        
                        // Update action buttons
                        if (status === 'approved') {
                            $row.find('.action-approve').hide();
                            $row.find('.action-checkin').show();
                        } else if (status === 'rejected') {
                            $row.find('.action-approve, .action-reject, .action-checkin').hide();
                        }
                    } else {
                        alert(response.data?.message || 'Error');
                    }
                },
                error: function() {
                    alert(window.esU18Admin?.i18n?.error || 'An error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        doCheckin: function(id, $btn) {
            $.ajax({
                url: window.esU18Admin?.ajaxUrl || ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_u18_checkin',
                    id: id,
                    nonce: window.esU18Admin?.nonce || ''
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        var $row = $btn.closest('tr');
                        var $statusCell = $row.find('.es-u18-status-pill');
                        
                        $statusCell
                            .removeClass('es-u18-status-approved')
                            .addClass('es-u18-status-used')
                            .text(response.data?.status_label || 'Used');
                        
                        $row.find('.action-checkin').hide();
                    } else {
                        alert(response.data?.message || 'Error');
                    }
                },
                error: function() {
                    alert(window.esU18Admin?.i18n?.error || 'An error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        viewDetails: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var id = $btn.data('id');
            
            // For now, just log - in production this would load a modal
            console.log('View details for ID:', id);
            
            // Show modal (if exists)
            var $modal = $('#es-u18-detail-modal');
            if ($modal.length) {
                $modal.addClass('active');
                // Load details via AJAX if needed
            }
        },
        
        closeModal: function() {
            $('.es-u18-modal').removeClass('active');
        },
        
        filterTable: function() {
            var status = $('#es-u18-filter-status').val();
            var search = $('#es-u18-search').val().toLowerCase();
            
            $('.es-u18-table tbody tr').each(function() {
                var $row = $(this);
                var rowStatus = $row.data('status');
                var rowText = $row.text().toLowerCase();
                
                var statusMatch = !status || rowStatus === status;
                var searchMatch = !search || rowText.indexOf(search) > -1;
                
                $row.toggle(statusMatch && searchMatch);
            });
        },
        
        resendEmails: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var id = $btn.data('id');
            
            $.ajax({
                url: window.esU18Admin?.ajaxUrl || ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_resend_u18_emails',
                    id: id,
                    nonce: window.esU18Admin?.nonce || ''
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    alert(response.data?.message || (response.success ? 'Emails sent!' : 'Error'));
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        downloadPdf: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var code = $btn.data('code');
            
            // Open PDF download in new window
            var url = window.esU18Admin?.ajaxUrl || ajaxurl;
            url += '?action=es_download_u18_pdf&code=' + encodeURIComponent(code) + '&nonce=' + encodeURIComponent(window.esU18Admin?.nonce || '');
            
            window.open(url, '_blank');
        },
        
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        esU18Admin.init();
    });
    
})(jQuery);
