/**
 * Catalog Manager Admin JavaScript
 */
(function($) {
    'use strict';
    
    const Catalog = {
        currentCatalogId: 0,
        currentCategoryId: 0,
        currentType: 'menu',
        categories: [],
        items: [],
        
        init: function() {
            this.bindEvents();
            this.loadCatalogs();
        },
        
        bindEvents: function() {
            // List panel
            $('#es-new-catalog-btn').on('click', () => this.showNewCatalogModal());
            $('#es-filter-location').on('change', () => this.loadCatalogs());
            
            // Editor panel
            $('#es-back-to-list, #es-cancel-catalog-btn').on('click', () => this.showListPanel());
            $('#es-save-catalog-btn').on('click', () => this.saveCatalog());
            $('#es-delete-catalog-btn').on('click', () => this.deleteCatalog());
            $('#es-catalog-type').on('change', (e) => { this.currentType = e.target.value; });
            
            // Categories
            $('#es-add-category-btn').on('click', () => this.showCategoryModal());
            $('#es-save-category-btn').on('click', () => this.saveCategory());
            
            // Items
            $('#es-add-item-btn').on('click', () => this.showItemModal());
            $('#es-save-item-btn').on('click', () => this.saveItem());
            
            // Import/Export
            $('#es-import-csv-btn').on('click', () => this.showImportModal());
            $('#es-export-csv-btn').on('click', () => this.exportCsv());
            $('#es-start-import-btn').on('click', () => this.importCsv());
            
            // Modal close
            $('.es-modal-close, .es-modal-cancel, .es-modal-backdrop').on('click', function() {
                $(this).closest('.es-modal').hide();
            });
            
            // New catalog type selection
            $(document).on('click', '.es-catalog-type-card', function() {
                const type = $(this).data('type');
                Catalog.createNewCatalog(type);
            });
            
            // Catalog card click
            $(document).on('click', '.es-catalog-card', function() {
                const id = $(this).data('id');
                Catalog.loadCatalog(id);
            });
            
            // Category click
            $(document).on('click', '.es-category-item', function(e) {
                if ($(e.target).closest('.es-category-actions').length) return;
                const id = $(this).data('id');
                Catalog.selectCategory(id);
            });
            
            // Category edit/delete
            $(document).on('click', '.es-category-edit', function(e) {
                e.stopPropagation();
                const id = $(this).closest('.es-category-item').data('id');
                Catalog.editCategory(id);
            });
            
            $(document).on('click', '.es-category-delete', function(e) {
                e.stopPropagation();
                const id = $(this).closest('.es-category-item').data('id');
                Catalog.deleteCategory(id);
            });
            
            // Item edit/delete
            $(document).on('click', '.es-item-edit', function() {
                const id = $(this).closest('.es-item-card').data('id');
                Catalog.editItem(id);
            });
            
            $(document).on('click', '.es-item-delete', function() {
                const id = $(this).closest('.es-item-card').data('id');
                Catalog.deleteItem(id);
            });
            
            // Image upload
            $('#es-select-image-btn').on('click', () => this.selectImage());
            $(document).on('click', '.es-remove-image', () => this.removeImage());
        },
        
        // ========================================
        // CATALOG LIST
        // ========================================
        
        loadCatalogs: function() {
            const locationId = $('#es-filter-location').val();
            
            $.post(ensembleCatalog.ajaxUrl, {
                action: 'es_catalog_list',
                nonce: ensembleCatalog.nonce,
                location_id: locationId
            }, (response) => {
                if (response.success) {
                    this.renderCatalogGrid(response.data);
                }
            });
        },
        
        renderCatalogGrid: function(catalogs) {
            const $grid = $('#es-catalog-grid');
            
            if (!catalogs.length) {
                $grid.html(`
                    <div class="es-empty-state" style="grid-column: 1/-1;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                        <p>${ensembleCatalog.strings.noItems || 'Noch keine Kataloge'}</p>
                    </div>
                `);
                return;
            }
            
            let html = '';
            catalogs.forEach(cat => {
                html += `
                    <div class="es-catalog-card" data-id="${cat.id}" data-type="${cat.type}">
                        <div class="es-catalog-card-header">
                            <div class="es-catalog-card-icon">
                                ${this.getTypeIcon(cat.type_icon)}
                            </div>
                            <div>
                                <h3 class="es-catalog-card-title">${this.escapeHtml(cat.title)}</h3>
                                <p class="es-catalog-card-type">${this.escapeHtml(cat.type_name)}</p>
                            </div>
                        </div>
                        <div class="es-catalog-card-meta">
                            <span class="es-catalog-card-count">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                ${cat.item_count} Einträge
                            </span>
                        </div>
                    </div>
                `;
            });
            
            $grid.html(html);
        },
        
        getTypeIcon: function(iconName) {
            const icons = {
                'utensils': '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"></path><path d="M7 2v20"></path><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"></path></svg>',
                'glass': '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 22h8"></path><path d="M12 11v11"></path><path d="m19 3-7 8-7-8Z"></path></svg>',
                'shirt': '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23Z"></path></svg>',
                'package': '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>',
                'speaker': '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><circle cx="12" cy="14" r="4"></circle><line x1="12" x2="12.01" y1="6" y2="6"></line></svg>',
                'home': '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
                'graduation': '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>',
                'award': '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path></svg>',
                'grid': '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>'
            };
            return icons[iconName] || icons['grid'];
        },
        
        showNewCatalogModal: function() {
            $('#es-new-catalog-modal').show();
        },
        
        createNewCatalog: function(type) {
            $('#es-new-catalog-modal').hide();
            this.currentCatalogId = 0;
            this.currentType = type;
            this.currentCategoryId = 0;
            this.categories = [];
            this.items = [];
            
            // Reset form
            $('#es-catalog-id').val(0);
            $('#es-catalog-title').val('');
            $('#es-catalog-type').val(type);
            $('#es-catalog-location').val('');
            $('#es-catalog-event').val('');
            
            // Show editor
            $('#es-catalog-list-panel').hide();
            $('#es-catalog-editor-panel').show();
            $('#es-editor-title').text('Neuer Katalog');
            $('#es-delete-catalog-btn').hide();
            $('#es-export-csv-btn').hide();
            
            // Clear categories and items
            this.renderCategories();
            this.renderItems();
            
            // Disable add item until saved
            $('#es-add-item-btn').prop('disabled', true);
        },
        
        // ========================================
        // CATALOG EDITOR
        // ========================================
        
        loadCatalog: function(catalogId) {
            $.post(ensembleCatalog.ajaxUrl, {
                action: 'es_catalog_get',
                nonce: ensembleCatalog.nonce,
                catalog_id: catalogId
            }, (response) => {
                if (response.success) {
                    this.openCatalogEditor(response.data);
                }
            });
        },
        
        openCatalogEditor: function(data) {
            this.currentCatalogId = data.id;
            this.currentType = data.type;
            this.categories = data.categories || [];
            this.items = data.items || [];
            this.currentCategoryId = this.categories.length ? this.categories[0].id : 0;
            
            // Fill form
            $('#es-catalog-id').val(data.id);
            $('#es-catalog-title').val(data.title);
            $('#es-catalog-type').val(data.type);
            $('#es-catalog-location').val(data.location_id || '');
            $('#es-catalog-event').val(data.event_id || '');
            
            // Show editor
            $('#es-catalog-list-panel').hide();
            $('#es-catalog-editor-panel').show();
            $('#es-editor-title').text(data.title);
            $('#es-delete-catalog-btn').show();
            $('#es-export-csv-btn').show();
            $('#es-add-item-btn').prop('disabled', false);
            
            // Render categories and items
            this.renderCategories();
            if (this.currentCategoryId) {
                this.selectCategory(this.currentCategoryId);
            } else {
                this.renderItems();
            }
            
            // Make categories sortable
            this.initSortable();
        },
        
        showListPanel: function() {
            $('#es-catalog-editor-panel').hide();
            $('#es-catalog-list-panel').show();
            this.loadCatalogs();
        },
        
        saveCatalog: function() {
            const title = $('#es-catalog-title').val().trim();
            if (!title) {
                alert('Bitte gib einen Namen ein');
                return;
            }
            
            const $btn = $('#es-save-catalog-btn');
            $btn.prop('disabled', true).text(ensembleCatalog.strings.saving);
            
            $.post(ensembleCatalog.ajaxUrl, {
                action: 'es_catalog_save',
                nonce: ensembleCatalog.nonce,
                catalog_id: this.currentCatalogId,
                title: title,
                type: $('#es-catalog-type').val(),
                location_id: $('#es-catalog-location').val(),
                event_id: $('#es-catalog-event').val(),
                settings: {}
            }, (response) => {
                $btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Katalog speichern');
                
                if (response.success) {
                    if (!this.currentCatalogId) {
                        // New catalog - reload to get categories
                        this.loadCatalog(response.data.catalog_id);
                    } else {
                        this.showNotice(response.data.message, 'success');
                    }
                } else {
                    this.showNotice(response.data, 'error');
                }
            });
        },
        
        deleteCatalog: function() {
            if (!confirm(ensembleCatalog.strings.confirmDeleteCatalog)) return;
            
            $.post(ensembleCatalog.ajaxUrl, {
                action: 'es_catalog_delete',
                nonce: ensembleCatalog.nonce,
                catalog_id: this.currentCatalogId
            }, (response) => {
                if (response.success) {
                    this.showListPanel();
                }
            });
        },
        
        // ========================================
        // CATEGORIES
        // ========================================
        
        renderCategories: function() {
            const $list = $('#es-categories-list');
            
            if (!this.categories.length) {
                $list.html('<div class="es-empty-state"><p>Keine Kategorien</p></div>');
                return;
            }
            
            let html = '';
            this.categories.forEach(cat => {
                const count = this.items.filter(i => i.category_id == cat.id).length;
                const isActive = cat.id == this.currentCategoryId;
                
                html += `
                    <div class="es-category-item ${isActive ? 'active' : ''}" data-id="${cat.id}">
                        <span class="es-category-drag">☰</span>
                        <span class="es-category-color" style="background: ${cat.color || '#3582c4'}"></span>
                        <span class="es-category-name">${this.escapeHtml(cat.name)}</span>
                        <span class="es-category-count">${count}</span>
                        <div class="es-category-actions">
                            <button type="button" class="es-category-edit" title="Bearbeiten">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                            <button type="button" class="es-category-delete delete" title="Löschen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            $list.html(html);
        },
        
        selectCategory: function(categoryId) {
            this.currentCategoryId = categoryId;
            
            // Update active state
            $('.es-category-item').removeClass('active');
            $(`.es-category-item[data-id="${categoryId}"]`).addClass('active');
            
            // Update title
            const cat = this.categories.find(c => c.id == categoryId);
            $('#es-items-category-title').text(cat ? cat.name : 'Einträge');
            
            // Render items
            this.renderItems();
            
            // Enable add button
            $('#es-add-item-btn').prop('disabled', false);
        },
        
        showCategoryModal: function(categoryId = 0) {
            const cat = categoryId ? this.categories.find(c => c.id == categoryId) : null;
            
            $('#es-category-modal-title').text(cat ? 'Kategorie bearbeiten' : 'Neue Kategorie');
            $('#es-category-id').val(categoryId);
            $('#es-category-name').val(cat ? cat.name : '');
            $('#es-category-color').val(cat ? (cat.color || '#3582c4') : '#3582c4');
            
            $('#es-category-modal').show();
            $('#es-category-name').focus();
        },
        
        editCategory: function(categoryId) {
            this.showCategoryModal(categoryId);
        },
        
        saveCategory: function() {
            const name = $('#es-category-name').val().trim();
            if (!name) {
                alert('Bitte gib einen Namen ein');
                return;
            }
            
            $.post(ensembleCatalog.ajaxUrl, {
                action: 'es_catalog_category_save',
                nonce: ensembleCatalog.nonce,
                category_id: $('#es-category-id').val(),
                catalog_id: this.currentCatalogId,
                name: name,
                color: $('#es-category-color').val()
            }, (response) => {
                if (response.success) {
                    $('#es-category-modal').hide();
                    // Reload catalog to get updated categories
                    this.loadCatalog(this.currentCatalogId);
                }
            });
        },
        
        deleteCategory: function(categoryId) {
            if (!confirm(ensembleCatalog.strings.confirmDelete)) return;
            
            $.post(ensembleCatalog.ajaxUrl, {
                action: 'es_catalog_category_delete',
                nonce: ensembleCatalog.nonce,
                category_id: categoryId
            }, (response) => {
                if (response.success) {
                    this.loadCatalog(this.currentCatalogId);
                }
            });
        },
        
        // ========================================
        // ITEMS
        // ========================================
        
        renderItems: function() {
            const $list = $('#es-items-list');
            const categoryItems = this.items.filter(i => i.category_id == this.currentCategoryId);
            
            if (!this.currentCategoryId) {
                $list.html(`
                    <div class="es-empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <p>Wähle eine Kategorie</p>
                    </div>
                `);
                return;
            }
            
            if (!categoryItems.length) {
                $list.html(`
                    <div class="es-empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        <p>Noch keine Einträge in dieser Kategorie</p>
                    </div>
                `);
                return;
            }
            
            let html = '';
            categoryItems.forEach(item => {
                const attrs = item.attributes || {};
                const price = attrs.price ? this.formatPrice(attrs.price) : '';
                
                let badges = '';
                if (attrs.vegan) badges += '<span class="es-item-badge vegan">Vegan</span>';
                if (attrs.vegetarian) badges += '<span class="es-item-badge vegetarian">Vegetarisch</span>';
                if (attrs.gluten_free) badges += '<span class="es-item-badge gluten-free">Glutenfrei</span>';
                if (attrs.non_alcoholic) badges += '<span class="es-item-badge non-alcoholic">Alkoholfrei</span>';
                if (attrs.new) badges += '<span class="es-item-badge new">Neu</span>';
                if (attrs.highlight) badges += '<span class="es-item-badge highlight">Empfehlung</span>';
                if (attrs.sale) badges += '<span class="es-item-badge sale">Sale</span>';
                if (attrs.limited) badges += '<span class="es-item-badge limited">Limited</span>';
                if (attrs.popular) badges += '<span class="es-item-badge popular">Beliebt</span>';
                if (attrs.exclusive) badges += '<span class="es-item-badge exclusive">Exklusiv</span>';
                if (attrs.delivery) badges += '<span class="es-item-badge delivery">Lieferung</span>';
                
                html += `
                    <div class="es-item-card" data-id="${item.id}">
                        <span class="es-item-drag">☰</span>
                        <div class="es-item-image">
                            ${item.image ? `<img src="${item.image}" alt="">` : '<div class="es-item-image-placeholder"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div>'}
                        </div>
                        <div class="es-item-content">
                            <h4 class="es-item-title">${this.escapeHtml(item.title)}</h4>
                            ${item.description ? `<p class="es-item-description">${this.escapeHtml(item.description)}</p>` : ''}
                            ${badges ? `<div class="es-item-badges">${badges}</div>` : ''}
                        </div>
                        ${price ? `<div class="es-item-price">${price}</div>` : ''}
                        <div class="es-item-actions">
                            <button type="button" class="es-item-edit" title="Bearbeiten">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                            <button type="button" class="es-item-delete delete" title="Löschen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            $list.html(html);
            this.initItemsSortable();
        },
        
        showItemModal: function(itemId = 0) {
            const item = itemId ? this.items.find(i => i.id == itemId) : null;
            const attrs = item ? (item.attributes || {}) : {};
            
            $('#es-item-modal-title').text(item ? 'Eintrag bearbeiten' : 'Neuer Eintrag');
            $('#es-item-id').val(itemId);
            $('#es-item-category-id').val(this.currentCategoryId);
            $('#es-item-title').val(item ? item.title : '');
            $('#es-item-description').val(item ? item.description : '');
            
            // Image
            if (item && item.image) {
                $('#es-item-image-preview').show().find('img').attr('src', item.image);
                $('#es-item-image-id').val(item.image_id || 0);
            } else {
                $('#es-item-image-preview').hide();
                $('#es-item-image-id').val(0);
            }
            
            // Build attributes form based on catalog type
            this.renderItemAttributes(attrs);
            
            $('#es-item-modal').show();
            $('#es-item-title').focus();
        },
        
        renderItemAttributes: function(attrs) {
            const typeConfig = ensembleCatalog.catalogTypes[this.currentType];
            if (!typeConfig || !typeConfig.item_attributes) return;
            
            let html = '';
            
            for (const [key, config] of Object.entries(typeConfig.item_attributes)) {
                const value = attrs[key] !== undefined ? attrs[key] : '';
                const fullWidth = config.type === 'textarea' || config.type === 'checkboxes';
                
                html += `<div class="es-form-row ${fullWidth ? 'full-width' : ''}">`;
                html += `<label>${config.label}${config.required ? ' *' : ''}</label>`;
                
                switch (config.type) {
                    case 'currency':
                        html += `<input type="number" step="0.01" name="attr_${key}" value="${value}" placeholder="0.00">`;
                        break;
                    case 'number':
                        html += `<input type="number" name="attr_${key}" value="${value}">`;
                        break;
                    case 'text':
                        html += `<input type="text" name="attr_${key}" value="${this.escapeHtml(value)}" placeholder="${config.placeholder || ''}">`;
                        break;
                    case 'textarea':
                        html += `<textarea name="attr_${key}" rows="2">${this.escapeHtml(value)}</textarea>`;
                        break;
                    case 'toggle':
                        // Standard Toggle Switch
                        const isChecked = value === 1 || value === '1' || value === true;
                        html += `
                            <label class="es-toggle">
                                <input type="checkbox" name="attr_${key}" value="1" ${isChecked ? 'checked' : ''}>
                                <span class="es-toggle-slider"></span>
                            </label>`;
                        break;
                    case 'checkbox':
                        // Legacy checkbox support - konvertiere zu toggle
                        const isCheckedLegacy = value === 1 || value === '1' || value === true;
                        html += `
                            <label class="es-toggle">
                                <input type="checkbox" name="attr_${key}" value="1" ${isCheckedLegacy ? 'checked' : ''}>
                                <span class="es-toggle-slider"></span>
                            </label>`;
                        break;
                    case 'select':
                        html += `<select name="attr_${key}">`;
                        for (const [optVal, optLabel] of Object.entries(config.options)) {
                            html += `<option value="${optVal}" ${value == optVal ? 'selected' : ''}>${optLabel}</option>`;
                        }
                        html += '</select>';
                        break;
                    case 'checkboxes':
                        html += '<div class="es-checkbox-row">';
                        const selectedValues = Array.isArray(value) ? value : (value ? [value] : []);
                        for (const [optVal, optLabel] of Object.entries(config.options)) {
                            const isSelected = selectedValues.includes(optVal);
                            html += `<label class="es-checkbox-item"><input type="checkbox" name="attr_${key}[]" value="${optVal}" ${isSelected ? 'checked' : ''}> ${optLabel}</label>`;
                        }
                        html += '</div>';
                        break;
                }
                
                html += '</div>';
            }
            
            $('#es-item-attributes').html(html);
        },
        
        editItem: function(itemId) {
            this.showItemModal(itemId);
        },
        
        saveItem: function() {
            const title = $('#es-item-title').val().trim();
            if (!title) {
                alert('Bitte gib einen Namen ein');
                return;
            }
            
            // Gather attributes - wichtig: auch ungeprüfte Toggles erfassen
            const attributes = {};
            const typeConfig = ensembleCatalog.catalogTypes[this.currentType];
            
            // Initialisiere alle Toggle/Checkbox-Felder mit 0
            if (typeConfig && typeConfig.item_attributes) {
                for (const [key, config] of Object.entries(typeConfig.item_attributes)) {
                    if (config.type === 'toggle' || config.type === 'checkbox') {
                        attributes[key] = 0;
                    }
                    if (config.type === 'checkboxes') {
                        attributes[key] = [];
                    }
                }
            }
            
            // Dann die tatsächlichen Werte sammeln
            $('#es-item-attributes').find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                if (!name || !name.startsWith('attr_')) return;
                
                const key = name.replace('attr_', '').replace('[]', '');
                
                if ($(this).is(':checkbox')) {
                    if (name.includes('[]')) {
                        // Checkboxes array
                        if (!Array.isArray(attributes[key])) attributes[key] = [];
                        if ($(this).is(':checked')) attributes[key].push($(this).val());
                    } else {
                        // Single toggle/checkbox
                        if ($(this).is(':checked')) {
                            attributes[key] = 1;
                        }
                        // Wenn nicht gecheckt, bleibt es bei 0 (oben initialisiert)
                    }
                } else {
                    attributes[key] = $(this).val();
                }
            });
            
            $.post(ensembleCatalog.ajaxUrl, {
                action: 'es_catalog_item_save',
                nonce: ensembleCatalog.nonce,
                item_id: $('#es-item-id').val(),
                catalog_id: this.currentCatalogId,
                category_id: this.currentCategoryId,
                title: title,
                description: $('#es-item-description').val(),
                attributes: attributes,
                image_id: $('#es-item-image-id').val()
            }, (response) => {
                if (response.success) {
                    $('#es-item-modal').hide();
                    this.loadCatalog(this.currentCatalogId);
                } else {
                    alert(response.data);
                }
            });
        },
        
        deleteItem: function(itemId) {
            if (!confirm(ensembleCatalog.strings.confirmDelete)) return;
            
            $.post(ensembleCatalog.ajaxUrl, {
                action: 'es_catalog_item_delete',
                nonce: ensembleCatalog.nonce,
                item_id: itemId
            }, (response) => {
                if (response.success) {
                    this.loadCatalog(this.currentCatalogId);
                }
            });
        },
        
        // ========================================
        // SORTABLE
        // ========================================
        
        initSortable: function() {
            $('#es-categories-list').sortable({
                handle: '.es-category-drag',
                update: () => {
                    const order = [];
                    $('.es-category-item').each(function() {
                        order.push($(this).data('id'));
                    });
                    
                    $.post(ensembleCatalog.ajaxUrl, {
                        action: 'es_catalog_category_reorder',
                        nonce: ensembleCatalog.nonce,
                        order: order
                    });
                }
            });
        },
        
        initItemsSortable: function() {
            $('#es-items-list').sortable({
                handle: '.es-item-drag',
                update: () => {
                    const order = [];
                    $('.es-item-card').each(function() {
                        order.push($(this).data('id'));
                    });
                    
                    $.post(ensembleCatalog.ajaxUrl, {
                        action: 'es_catalog_item_reorder',
                        nonce: ensembleCatalog.nonce,
                        order: order
                    });
                }
            });
        },
        
        // ========================================
        // IMAGE UPLOAD
        // ========================================
        
        selectImage: function() {
            const frame = wp.media({
                title: ensembleCatalog.strings.uploadImage,
                button: { text: ensembleCatalog.strings.uploadImage },
                multiple: false
            });
            
            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                $('#es-item-image-id').val(attachment.id);
                $('#es-item-image-preview').show().find('img').attr('src', attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url);
            });
            
            frame.open();
        },
        
        removeImage: function() {
            $('#es-item-image-id').val(0);
            $('#es-item-image-preview').hide();
        },
        
        // ========================================
        // IMPORT / EXPORT
        // ========================================
        
        showImportModal: function() {
            $('#es-csv-file').val('');
            $('#es-import-modal').show();
        },
        
        importCsv: function() {
            const file = $('#es-csv-file')[0].files[0];
            if (!file) {
                alert('Bitte wähle eine Datei');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'es_catalog_import_csv');
            formData.append('nonce', ensembleCatalog.nonce);
            formData.append('catalog_id', this.currentCatalogId);
            formData.append('csv_file', file);
            
            $.ajax({
                url: ensembleCatalog.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    $('#es-import-modal').hide();
                    if (response.success) {
                        alert(response.data.message);
                        this.loadCatalog(this.currentCatalogId);
                    } else {
                        alert(response.data);
                    }
                }
            });
        },
        
        exportCsv: function() {
            window.location.href = ensembleCatalog.ajaxUrl + '?action=es_catalog_export_csv&nonce=' + ensembleCatalog.nonce + '&catalog_id=' + this.currentCatalogId;
        },
        
        // ========================================
        // HELPERS
        // ========================================
        
        formatPrice: function(price) {
            return parseFloat(price).toFixed(2).replace('.', ',') + ' €';
        },
        
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        showNotice: function(message, type) {
            // Simple notice - could be enhanced
            console.log(type + ': ' + message);
        }
    };
    
    $(document).ready(() => Catalog.init());
    
})(jQuery);
