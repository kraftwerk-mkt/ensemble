/**
 * Ensemble FAQ Manager Admin JavaScript
 * 
 * @package Ensemble
 * @subpackage Addons/FAQ
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    const FAQManager = {
        
        // State
        faqs: [],
        currentFaqId: null,
        selectedFaqs: [],
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.loadFAQs();
        },
        
        /**
         * Bind all events
         */
        bindEvents: function() {
            // Add FAQ button
            $('#es-add-faq-btn').on('click', () => this.openModal());
            
            // Save FAQ
            $('#es-faq-save').on('click', () => this.saveFAQ());
            
            // Modal close
            $('.es-modal-close, .es-modal-cancel, .es-modal-overlay').on('click', function() {
                $(this).closest('.es-modal').fadeOut(200);
            });
            
            // Search
            $('#es-faq-search').on('input', $.debounce(300, () => this.filterFAQs()));
            
            // Category filter
            $('#es-faq-category-filter').on('change', () => this.filterFAQs());
            
            // Add category button
            $('#es-add-category-btn').on('click', () => this.openCategoryModal());
            
            // Save category
            $('#es-category-save').on('click', () => this.saveCategory());
            
            // Confirm delete
            $('#es-confirm-delete').on('click', () => this.confirmDelete());
            
            // Bulk actions
            $('#es-faq-apply-bulk').on('click', () => this.applyBulkAction());
            
            // Delegated events for dynamic content
            $(document).on('click', '.es-faq-edit', (e) => this.editFAQ(e));
            $(document).on('click', '.es-faq-delete', (e) => this.deleteFAQ(e));
            $(document).on('change', '.es-faq-checkbox', () => this.updateBulkActions());
            $(document).on('change', '#es-faq-select-all', (e) => this.selectAll(e));
            
            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') {
                    $('.es-modal:visible').fadeOut(200);
                }
            });
        },
        
        /**
         * Load FAQs via AJAX
         */
        loadFAQs: function() {
            const $container = $('#es-faq-list');
            $container.html('<div class="es-loading"><span class="spinner is-active"></span> ' + esFaqManager.loading + '</div>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_faq_get_all',
                    nonce: esFaqManager.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.faqs = response.data.faqs;
                        this.renderFAQs(this.faqs);
                        this.updateCount(this.faqs.length);
                    } else {
                        $container.html('<div class="es-error">' + (response.data?.message || esFaqManager.error) + '</div>');
                    }
                },
                error: () => {
                    $container.html('<div class="es-error">' + esFaqManager.error + '</div>');
                }
            });
        },
        
        /**
         * Render FAQ list
         */
        renderFAQs: function(faqs) {
            const $container = $('#es-faq-list');
            
            if (faqs.length === 0) {
                $container.html(`
                    <div class="es-empty-state">
                        <span class="dashicons dashicons-editor-help"></span>
                        <h3>${esFaqManager.emptyTitle}</h3>
                        <p>${esFaqManager.emptyText}</p>
                        <button type="button" class="button button-primary" id="es-empty-add-btn">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            ${esFaqManager.addFirst}
                        </button>
                    </div>
                `);
                $('#es-empty-add-btn').on('click', () => this.openModal());
                return;
            }
            
            let html = `
                <table class="es-faq-table">
                    <thead>
                        <tr>
                            <th class="es-col-check">
                                <input type="checkbox" id="es-faq-select-all">
                            </th>
                            <th class="es-col-order">#</th>
                            <th class="es-col-question">${esFaqManager.colQuestion}</th>
                            <th class="es-col-category">${esFaqManager.colCategory}</th>
                            <th class="es-col-actions">${esFaqManager.colActions}</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            faqs.forEach((faq, index) => {
                const categoryBadges = faq.categories.map(cat => 
                    `<span class="es-category-badge">${this.escapeHtml(cat.name)}</span>`
                ).join('') || '<span class="es-no-category">—</span>';
                
                const expandedIcon = faq.expanded ? '<span class="dashicons dashicons-visibility es-expanded-icon" title="' + esFaqManager.expandedHint + '"></span>' : '';
                
                html += `
                    <tr class="es-faq-row" data-id="${faq.id}">
                        <td class="es-col-check">
                            <input type="checkbox" class="es-faq-checkbox" value="${faq.id}">
                        </td>
                        <td class="es-col-order">
                            <span class="es-order-badge">${faq.order}</span>
                        </td>
                        <td class="es-col-question">
                            <div class="es-faq-question-cell">
                                <strong class="es-faq-title">${this.escapeHtml(faq.question)}</strong>
                                ${expandedIcon}
                            </div>
                            <div class="es-faq-answer-preview">${this.truncate(this.stripHtml(faq.answer), 100)}</div>
                        </td>
                        <td class="es-col-category">${categoryBadges}</td>
                        <td class="es-col-actions">
                            <button type="button" class="button button-small es-faq-edit" data-id="${faq.id}" title="${esFaqManager.edit}">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button button-small es-faq-delete" data-id="${faq.id}" data-title="${this.escapeHtml(faq.question)}" title="${esFaqManager.delete}">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            $container.html(html);
        },
        
        /**
         * Filter FAQs
         */
        filterFAQs: function() {
            const search = $('#es-faq-search').val().toLowerCase();
            const categoryId = $('#es-faq-category-filter').val();
            
            const filtered = this.faqs.filter(faq => {
                const matchesSearch = !search || 
                    faq.question.toLowerCase().includes(search) ||
                    faq.answer.toLowerCase().includes(search);
                
                const matchesCategory = !categoryId || 
                    faq.categories.some(cat => cat.term_id == categoryId);
                
                return matchesSearch && matchesCategory;
            });
            
            this.renderFAQs(filtered);
            this.updateCount(filtered.length);
        },
        
        /**
         * Open FAQ modal
         */
        openModal: function(faq = null) {
            this.currentFaqId = faq ? faq.id : null;
            
            // Reset form
            $('#es-faq-form')[0].reset();
            $('#es-faq-id').val('');
            
            // Set title
            $('#es-faq-modal-title').text(faq ? esFaqManager.editTitle : esFaqManager.addTitle);
            
            // Fill form if editing
            if (faq) {
                $('#es-faq-id').val(faq.id);
                $('#es-faq-question').val(faq.question);
                $('#es-faq-answer').val(faq.answer_raw || faq.answer);
                $('#es-faq-order').val(faq.order);
                $('#es-faq-expanded').prop('checked', faq.expanded);
                
                if (faq.categories.length > 0) {
                    $('#es-faq-category').val(faq.categories[0].term_id);
                }
            }
            
            $('#es-faq-modal').fadeIn(200);
            $('#es-faq-question').focus();
        },
        
        /**
         * Edit FAQ
         */
        editFAQ: function(e) {
            const id = $(e.currentTarget).data('id');
            const faq = this.faqs.find(f => f.id == id);
            
            if (faq) {
                this.openModal(faq);
            }
        },
        
        /**
         * Save FAQ
         */
        saveFAQ: function() {
            const $btn = $('#es-faq-save');
            const $form = $('#es-faq-form');
            
            // Validate
            if (!$('#es-faq-question').val().trim() || !$('#es-faq-answer').val().trim()) {
                this.showNotice(esFaqManager.requiredFields, 'error');
                return;
            }
            
            $btn.prop('disabled', true).html('<span class="spinner is-active"></span> ' + esFaqManager.saving);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_faq_save',
                    nonce: esFaqManager.nonce,
                    faq_id: $('#es-faq-id').val(),
                    question: $('#es-faq-question').val(),
                    answer: $('#es-faq-answer').val(),
                    category: $('#es-faq-category').val(),
                    menu_order: $('#es-faq-order').val(),
                    expanded: $('#es-faq-expanded').is(':checked') ? 1 : 0
                },
                success: (response) => {
                    if (response.success) {
                        $('#es-faq-modal').fadeOut(200);
                        this.loadFAQs();
                        this.showNotice(response.data.message, 'success');
                    } else {
                        this.showNotice(response.data?.message || esFaqManager.error, 'error');
                    }
                },
                error: () => {
                    this.showNotice(esFaqManager.error, 'error');
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> ' + esFaqManager.save);
                }
            });
        },
        
        /**
         * Delete FAQ
         */
        deleteFAQ: function(e) {
            const id = $(e.currentTarget).data('id');
            const title = $(e.currentTarget).data('title');
            
            this.currentFaqId = id;
            $('.es-delete-faq-title').html('<strong>"' + title + '"</strong>');
            $('#es-delete-modal').fadeIn(200);
        },
        
        /**
         * Confirm delete
         */
        confirmDelete: function() {
            const $btn = $('#es-confirm-delete');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_faq_delete',
                    nonce: esFaqManager.nonce,
                    faq_id: this.currentFaqId
                },
                success: (response) => {
                    if (response.success) {
                        $('#es-delete-modal').fadeOut(200);
                        this.loadFAQs();
                        this.showNotice(response.data.message, 'success');
                    } else {
                        this.showNotice(response.data?.message || esFaqManager.error, 'error');
                    }
                },
                error: () => {
                    this.showNotice(esFaqManager.error, 'error');
                },
                complete: () => {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * Open category modal
         */
        openCategoryModal: function() {
            $('#es-category-form')[0].reset();
            $('#es-category-modal').fadeIn(200);
            $('#es-category-name').focus();
        },
        
        /**
         * Save category
         */
        saveCategory: function() {
            const name = $('#es-category-name').val().trim();
            
            if (!name) {
                return;
            }
            
            const $btn = $('#es-category-save');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_faq_create_category',
                    nonce: esFaqManager.nonce,
                    name: name
                },
                success: (response) => {
                    if (response.success) {
                        // Add to dropdown
                        const cat = response.data.category;
                        $('#es-faq-category').append(
                            `<option value="${cat.term_id}">${this.escapeHtml(cat.name)}</option>`
                        );
                        $('#es-faq-category').val(cat.term_id);
                        
                        // Also add to filter
                        $('#es-faq-category-filter').append(
                            `<option value="${cat.term_id}">${this.escapeHtml(cat.name)} (0)</option>`
                        );
                        
                        $('#es-category-modal').fadeOut(200);
                        this.showNotice(response.data.message, 'success');
                    } else {
                        this.showNotice(response.data?.message || esFaqManager.error, 'error');
                    }
                },
                error: () => {
                    this.showNotice(esFaqManager.error, 'error');
                },
                complete: () => {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * Update bulk actions visibility
         */
        updateBulkActions: function() {
            const $checked = $('.es-faq-checkbox:checked');
            this.selectedFaqs = $checked.map(function() { return $(this).val(); }).get();
            
            if (this.selectedFaqs.length > 0) {
                $('#es-bulk-actions').show();
                $('#es-faq-selected-count').text(this.selectedFaqs.length + ' ' + esFaqManager.selected);
            } else {
                $('#es-bulk-actions').hide();
            }
        },
        
        /**
         * Select all FAQs
         */
        selectAll: function(e) {
            const checked = $(e.target).is(':checked');
            $('.es-faq-checkbox').prop('checked', checked);
            this.updateBulkActions();
        },
        
        /**
         * Apply bulk action
         */
        applyBulkAction: function() {
            const action = $('#es-faq-bulk-action').val();
            
            if (!action || this.selectedFaqs.length === 0) {
                return;
            }
            
            if (action === 'delete') {
                if (!confirm(esFaqManager.confirmBulkDelete)) {
                    return;
                }
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_faq_bulk_action',
                    nonce: esFaqManager.nonce,
                    bulk_action: action,
                    faq_ids: this.selectedFaqs
                },
                success: (response) => {
                    if (response.success) {
                        this.loadFAQs();
                        this.showNotice(response.data.message, 'success');
                        $('#es-faq-bulk-action').val('');
                    } else {
                        this.showNotice(response.data?.message || esFaqManager.error, 'error');
                    }
                }
            });
        },
        
        /**
         * Update count display
         */
        updateCount: function(count) {
            const text = count === 1 ? esFaqManager.faqSingular : esFaqManager.faqPlural;
            $('#es-faq-count').text(count + ' ' + text);
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type = 'success') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible es-faq-notice">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss"></button>
                </div>
            `);
            
            $('.es-faq-notice').remove();
            $('.es-manager-wrap > h1').after($notice);
            
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(200, function() { $(this).remove(); });
            });
            
            setTimeout(() => $notice.fadeOut(200, function() { $(this).remove(); }), 5000);
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },
        
        /**
         * Strip HTML tags
         */
        stripHtml: function(html) {
            const div = document.createElement('div');
            div.innerHTML = html;
            return div.textContent || div.innerText || '';
        },
        
        /**
         * Truncate text
         */
        truncate: function(str, length) {
            if (!str) return '';
            if (str.length <= length) return str;
            return str.substring(0, length) + '...';
        }
    };
    
    // Simple debounce
    $.debounce = function(wait, func) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    };
    
    // Initialize on ready
    $(document).ready(function() {
        if ($('.es-faq-manager-wrap').length) {
            FAQManager.init();
        }
    });
    
})(jQuery);
