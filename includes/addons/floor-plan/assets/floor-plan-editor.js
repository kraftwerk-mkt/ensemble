/**
 * Floor Plan Pro - Admin Editor
 *
 * Interactive drag & drop floor plan editor using Konva.js
 *
 * @package Ensemble
 * @subpackage Addons/FloorPlan
 */

(function($) {
    'use strict';

    // Main editor object
    window.ESFloorPlanEditor = {
        
        // State
        stage: null,
        layer: null,
        gridLayer: null,
        transformer: null,
        selectedElement: null,
        elements: [],
        sections: [],
        history: [],
        historyIndex: -1,
        isDirty: false,
        zoom: 1,
        
        // Settings
        settings: {
            canvasWidth: 1200,
            canvasHeight: 800,
            showGrid: true,
            gridSize: 20,
            background: '',
            linkedLocation: null
        },
        
        // Element colors
        elementColors: {
            table: '#3B82F6',
            section: '#8B5CF6',
            stage: '#EF4444',
            bar: '#F59E0B',
            entrance: '#10B981',
            lounge: '#EC4899',
            dancefloor: '#06B6D4',
            amenity: '#6B7280',
            custom: '#374151'
        },

        /**
         * Initialize editor
         */
        init: function() {
            this.bindEvents();
            this.loadFloorPlans();
            this.initSections();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Toolbar
            $('#es-create-floor-plan-btn').on('click', function() {
                self.openEditor();
            });
            
            // Editor header
            $('#es-floor-plan-back').on('click', function() {
                self.closeEditor();
            });
            
            $('#es-floor-plan-save').on('click', function() {
                self.save();
            });
            
            $('#es-floor-plan-preview').on('click', function() {
                self.preview();
            });
            
            // Canvas settings
            $('#es-canvas-width, #es-canvas-height').on('change', function() {
                self.updateCanvasSize();
            });
            
            $('#es-show-grid').on('change', function() {
                self.settings.showGrid = $(this).is(':checked');
                self.drawGrid();
            });
            
            $('#es-grid-size').on('change', function() {
                self.settings.gridSize = parseInt($(this).val());
                self.drawGrid();
            });
            
            // Background
            $('#es-select-background').on('click', function() {
                self.selectBackground();
            });
            
            $('#es-remove-background').on('click', function() {
                self.removeBackground();
            });
            
            // Zoom controls
            $('#es-zoom-in').on('click', function() {
                self.setZoom(self.zoom + 0.1);
            });
            
            $('#es-zoom-out').on('click', function() {
                self.setZoom(self.zoom - 0.1);
            });
            
            $('#es-zoom-fit').on('click', function() {
                self.fitToView();
            });
            
            // Canvas actions
            $('#es-undo').on('click', function() {
                self.undo();
            });
            
            $('#es-redo').on('click', function() {
                self.redo();
            });
            
            $('#es-select-all').on('click', function() {
                self.selectAll();
            });
            
            $('#es-delete-selected').on('click', function() {
                self.deleteSelected();
            });
            
            // Element palette drag & drop
            $('.es-element-item').on('dragstart', function(e) {
                e.originalEvent.dataTransfer.setData('element-type', $(this).data('type'));
            });
            
            // Properties panel
            $('#es-prop-bookable').on('change', function() {
                var isBookable = $(this).is(':checked');
                $('#es-bookable-options').toggle(isBookable);
                self.updateSelectedElement();
            });
            
            $('#es-prop-label, #es-prop-number, #es-prop-shape, #es-prop-width, #es-prop-height, #es-prop-rotation, #es-prop-seats, #es-prop-section, #es-prop-price, #es-prop-accessible, #es-prop-description').on('change', function() {
                self.updateSelectedElement();
            });
            
            $('#es-prop-duplicate').on('click', function() {
                self.duplicateElement();
            });
            
            $('#es-prop-delete').on('click', function() {
                self.deleteSelected();
            });
            
            // Sections
            $('#es-add-section').on('click', function() {
                self.openSectionModal();
            });
            
            $('#es-section-save').on('click', function() {
                self.saveSection();
            });
            
            $('#es-section-cancel, #es-section-modal .es-modal-close').on('click', function() {
                $('#es-section-modal').hide();
            });
            
            // Search
            $('#es-floor-plan-search').on('input', function() {
                self.filterFloorPlans($(this).val());
            });
            
            $('#es-floor-plan-location-filter').on('change', function() {
                self.loadFloorPlans($(this).val());
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                if (!$('#es-floor-plan-modal').is(':visible')) return;
                
                // Delete
                if (e.key === 'Delete' || e.key === 'Backspace') {
                    if (!$(e.target).is('input, textarea, select')) {
                        self.deleteSelected();
                        e.preventDefault();
                    }
                }
                
                // Ctrl+Z - Undo
                if (e.ctrlKey && e.key === 'z') {
                    self.undo();
                    e.preventDefault();
                }
                
                // Ctrl+Y - Redo
                if (e.ctrlKey && e.key === 'y') {
                    self.redo();
                    e.preventDefault();
                }
                
                // Ctrl+S - Save
                if (e.ctrlKey && e.key === 's') {
                    self.save();
                    e.preventDefault();
                }
                
                // Ctrl+D - Duplicate
                if (e.ctrlKey && e.key === 'd') {
                    self.duplicateElement();
                    e.preventDefault();
                }
                
                // Escape - Deselect
                if (e.key === 'Escape') {
                    self.deselectAll();
                }
            });
        },

        /**
         * Load floor plans list
         */
        loadFloorPlans: function(locationId) {
            var self = this;
            var $container = $('#es-floor-plans-container');
            
            $container.html('<div class="es-loading">' + esFloorPlan.strings.loading + '</div>');
            
            $.ajax({
                url: esFloorPlan.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_get_floor_plans',
                    nonce: esFloorPlan.nonce,
                    location_id: locationId || ''
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.renderFloorPlansList(response.data);
                    } else {
                        $container.html(self.getEmptyState());
                    }
                },
                error: function() {
                    $container.html('<div class="es-error">' + esFloorPlan.strings.error + '</div>');
                }
            });
        },

        /**
         * Render floor plans list
         */
        renderFloorPlansList: function(floorPlans) {
            var self = this;
            var html = '';
            
            floorPlans.forEach(function(fp) {
                html += self.getFloorPlanCardHTML(fp);
            });
            
            $('#es-floor-plans-container').html(html);
            
            // Bind card events
            $('.es-floor-plan-card').on('click', function() {
                var id = $(this).data('id');
                self.loadFloorPlan(id);
            });
            
            $('.es-fp-edit-btn').on('click', function(e) {
                e.stopPropagation();
                var id = $(this).closest('.es-floor-plan-card').data('id');
                self.loadFloorPlan(id);
            });
            
            $('.es-fp-duplicate-btn').on('click', function(e) {
                e.stopPropagation();
                var id = $(this).closest('.es-floor-plan-card').data('id');
                self.duplicateFloorPlan(id);
            });
            
            $('.es-fp-delete-btn').on('click', function(e) {
                e.stopPropagation();
                var id = $(this).closest('.es-floor-plan-card').data('id');
                self.deleteFloorPlan(id);
            });
        },

        /**
         * Get floor plan card HTML
         */
        getFloorPlanCardHTML: function(fp) {
            var thumbnail = fp.thumbnail 
                ? '<img src="' + fp.thumbnail + '" alt="">'
                : '<span class="es-no-preview"><span class="dashicons dashicons-layout"></span><span>No Preview</span></span>';
            
            return '<div class="es-floor-plan-card" data-id="' + fp.id + '">' +
                '<div class="es-floor-plan-card-thumbnail">' + thumbnail + '</div>' +
                '<div class="es-floor-plan-card-body">' +
                    '<h3 class="es-floor-plan-card-title">' + fp.title + '</h3>' +
                    '<div class="es-floor-plan-card-meta">' +
                        '<span><span class="dashicons dashicons-screenoptions"></span> ' + fp.element_count + ' elements</span>' +
                        '<span><span class="dashicons dashicons-groups"></span> ' + fp.total_capacity + ' capacity</span>' +
                        (fp.location_name ? '<span><span class="dashicons dashicons-location"></span> ' + fp.location_name + '</span>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="es-floor-plan-card-actions">' +
                    '<button class="button es-fp-edit-btn"><span class="dashicons dashicons-edit"></span> Edit</button>' +
                    '<button class="button es-fp-duplicate-btn"><span class="dashicons dashicons-admin-page"></span></button>' +
                    '<button class="button es-fp-delete-btn"><span class="dashicons dashicons-trash"></span></button>' +
                '</div>' +
            '</div>';
        },

        /**
         * Get empty state HTML
         */
        getEmptyState: function() {
            return '<div class="es-floor-plans-empty">' +
                '<span class="dashicons dashicons-layout"></span>' +
                '<h3>No Floor Plans Yet</h3>' +
                '<p>Create your first floor plan to get started.</p>' +
                '<button id="es-create-first-btn" class="button button-primary">Create Floor Plan</button>' +
            '</div>';
        },

        /**
         * Open editor (new or existing)
         */
        openEditor: function(floorPlanData) {
            var self = this;
            
            // Reset state
            this.elements = [];
            this.history = [];
            this.historyIndex = -1;
            this.isDirty = false;
            this.selectedElement = null;
            
            // Set defaults or load data
            if (floorPlanData) {
                $('#es-floor-plan-id').val(floorPlanData.id);
                $('#es-floor-plan-title').val(floorPlanData.title);
                this.settings.canvasWidth = floorPlanData.canvas.width || 1200;
                this.settings.canvasHeight = floorPlanData.canvas.height || 800;
                this.settings.showGrid = floorPlanData.canvas.grid !== false;
                this.settings.gridSize = floorPlanData.canvas.grid_size || 20;
                this.settings.background = floorPlanData.canvas.background || '';
                this.settings.linkedLocation = floorPlanData.linked_location;
                this.sections = floorPlanData.sections || esFloorPlan.defaultSections;
                this.elements = floorPlanData.elements || [];
            } else {
                $('#es-floor-plan-id').val('');
                $('#es-floor-plan-title').val('');
                this.settings = {
                    canvasWidth: 1200,
                    canvasHeight: 800,
                    showGrid: true,
                    gridSize: 20,
                    background: '',
                    linkedLocation: null
                };
                this.sections = JSON.parse(JSON.stringify(esFloorPlan.defaultSections));
            }
            
            // Update UI
            $('#es-canvas-width').val(this.settings.canvasWidth);
            $('#es-canvas-height').val(this.settings.canvasHeight);
            $('#es-show-grid').prop('checked', this.settings.showGrid);
            $('#es-grid-size').val(this.settings.gridSize);
            $('#es-linked-location').val(this.settings.linkedLocation || '');
            
            // Background
            if (this.settings.background) {
                $('#es-background-preview').html('<img src="' + this.settings.background + '">');
                $('#es-background-url').val(this.settings.background);
                $('#es-remove-background').show();
            } else {
                $('#es-background-preview').html('<span class="es-no-image">No image</span>');
                $('#es-background-url').val('');
                $('#es-remove-background').hide();
            }
            
            // Show modal
            $('#es-floor-plan-modal').show();
            
            // Initialize canvas
            this.initCanvas();
            
            // Render sections
            this.renderSections();
            
            // Load elements
            this.loadElements();
            
            // Hide properties panel
            $('#es-properties-panel').hide();
        },

        /**
         * Close editor
         */
        closeEditor: function() {
            var self = this;
            
            if (this.isDirty) {
                if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                    return;
                }
            }
            
            $('#es-floor-plan-modal').hide();
            
            // Destroy canvas
            if (this.stage) {
                this.stage.destroy();
                this.stage = null;
            }
            
            // Reload list
            this.loadFloorPlans();
        },

        /**
         * Initialize Konva canvas
         */
        initCanvas: function() {
            var self = this;
            var container = document.getElementById('es-canvas-container');
            
            // Clear existing
            if (this.stage) {
                this.stage.destroy();
            }
            
            // Create stage
            this.stage = new Konva.Stage({
                container: 'es-canvas-container',
                width: this.settings.canvasWidth,
                height: this.settings.canvasHeight
            });
            
            // Grid layer
            this.gridLayer = new Konva.Layer();
            this.stage.add(this.gridLayer);
            
            // Background layer
            this.bgLayer = new Konva.Layer();
            this.stage.add(this.bgLayer);
            
            // Main layer
            this.layer = new Konva.Layer();
            this.stage.add(this.layer);
            
            // Transformer
            this.transformer = new Konva.Transformer({
                rotateEnabled: true,
                rotationSnaps: [0, 45, 90, 135, 180, 225, 270, 315],
                boundBoxFunc: function(oldBox, newBox) {
                    if (newBox.width < 20 || newBox.height < 20) {
                        return oldBox;
                    }
                    return newBox;
                }
            });
            this.layer.add(this.transformer);
            
            // Draw grid
            this.drawGrid();
            
            // Load background
            if (this.settings.background) {
                this.loadBackgroundImage(this.settings.background);
            }
            
            // Stage click - deselect
            this.stage.on('click tap', function(e) {
                if (e.target === self.stage) {
                    self.deselectAll();
                }
            });
            
            // Drop handling
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
            });
            
            container.addEventListener('drop', function(e) {
                e.preventDefault();
                var type = e.dataTransfer.getData('element-type');
                if (type) {
                    var rect = container.getBoundingClientRect();
                    var x = (e.clientX - rect.left) / self.zoom;
                    var y = (e.clientY - rect.top) / self.zoom;
                    
                    // Snap to grid
                    if (self.settings.showGrid) {
                        x = Math.round(x / self.settings.gridSize) * self.settings.gridSize;
                        y = Math.round(y / self.settings.gridSize) * self.settings.gridSize;
                    }
                    
                    self.addElement(type, x, y);
                }
            });
        },

        /**
         * Draw grid
         */
        drawGrid: function() {
            if (!this.gridLayer) return;
            
            this.gridLayer.destroyChildren();
            
            if (!this.settings.showGrid) {
                this.gridLayer.draw();
                return;
            }
            
            var width = this.settings.canvasWidth;
            var height = this.settings.canvasHeight;
            var gridSize = this.settings.gridSize;
            
            // Vertical lines
            for (var x = 0; x <= width; x += gridSize) {
                this.gridLayer.add(new Konva.Line({
                    points: [x, 0, x, height],
                    stroke: 'rgba(0, 0, 0, 0.08)',
                    strokeWidth: 1
                }));
            }
            
            // Horizontal lines
            for (var y = 0; y <= height; y += gridSize) {
                this.gridLayer.add(new Konva.Line({
                    points: [0, y, width, y],
                    stroke: 'rgba(0, 0, 0, 0.08)',
                    strokeWidth: 1
                }));
            }
            
            this.gridLayer.draw();
        },

        /**
         * Load background image
         */
        loadBackgroundImage: function(url) {
            var self = this;
            
            this.bgLayer.destroyChildren();
            
            if (!url) {
                this.bgLayer.draw();
                return;
            }
            
            var imageObj = new Image();
            imageObj.onload = function() {
                var bgImage = new Konva.Image({
                    x: 0,
                    y: 0,
                    image: imageObj,
                    width: self.settings.canvasWidth,
                    height: self.settings.canvasHeight,
                    opacity: 0.5
                });
                
                self.bgLayer.add(bgImage);
                self.bgLayer.draw();
            };
            imageObj.src = url;
        },

        /**
         * Add element to canvas
         */
        addElement: function(type, x, y) {
            var self = this;
            var typeConfig = esFloorPlan.elementTypes[type] || {};
            var defaults = typeConfig.defaults || {};
            
            var element = {
                id: 'el_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                type: type,
                x: x,
                y: y,
                width: defaults.width || 60,
                height: defaults.height || 60,
                rotation: 0,
                shape: defaults.shape || 'rectangle',
                label: '',
                number: this.getNextNumber(type),
                seats: defaults.seats || 0,
                capacity: defaults.capacity || 0,
                section_id: '',
                bookable: typeConfig.bookable || false,
                price: 0,
                accessible: false,
                description: ''
            };
            
            this.elements.push(element);
            this.renderElement(element);
            this.saveHistory();
            this.updateElementsList();
            this.isDirty = true;
            
            // Select new element
            this.selectElement(element.id);
        },

        /**
         * Get next number for element type
         */
        getNextNumber: function(type) {
            var maxNum = 0;
            this.elements.forEach(function(el) {
                if (el.type === type && el.number > maxNum) {
                    maxNum = el.number;
                }
            });
            return maxNum + 1;
        },

        /**
         * Render single element on canvas
         */
        renderElement: function(element) {
            var self = this;
            var color = this.elementColors[element.type] || '#64748b';
            
            // Get section color if assigned
            if (element.section_id) {
                var section = this.sections.find(function(s) { return s.id === element.section_id; });
                if (section) {
                    color = section.color;
                }
            }
            
            var group = new Konva.Group({
                x: element.x,
                y: element.y,
                rotation: element.rotation,
                draggable: true,
                id: element.id,
                name: 'element'
            });
            
            // Shape
            var shape;
            if (element.shape === 'round' || element.type === 'table' && element.shape !== 'rectangle' && element.shape !== 'square') {
                shape = new Konva.Circle({
                    x: element.width / 2,
                    y: element.height / 2,
                    radius: Math.min(element.width, element.height) / 2,
                    fill: color,
                    opacity: 0.7,
                    stroke: color,
                    strokeWidth: 2
                });
            } else {
                var cornerRadius = element.type === 'stage' ? 0 : 4;
                shape = new Konva.Rect({
                    x: 0,
                    y: 0,
                    width: element.width,
                    height: element.height,
                    fill: color,
                    opacity: 0.7,
                    stroke: color,
                    strokeWidth: 2,
                    cornerRadius: cornerRadius
                });
            }
            
            group.add(shape);
            
            // Label
            var labelText = element.label || (element.number ? element.number.toString() : '');
            if (labelText) {
                var label = new Konva.Text({
                    x: 0,
                    y: element.height / 2 - 8,
                    width: element.width,
                    text: labelText,
                    fontSize: 14,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    fontStyle: 'bold',
                    fill: '#fff',
                    align: 'center'
                });
                group.add(label);
            }
            
            // Bookable indicator
            if (element.bookable) {
                var indicator = new Konva.Circle({
                    x: element.width - 8,
                    y: 8,
                    radius: 6,
                    fill: '#10B981',
                    stroke: '#fff',
                    strokeWidth: 2
                });
                group.add(indicator);
            }
            
            // Events
            group.on('click tap', function() {
                self.selectElement(element.id);
            });
            
            group.on('dragend', function() {
                var pos = group.position();
                
                // Snap to grid
                if (self.settings.showGrid) {
                    pos.x = Math.round(pos.x / self.settings.gridSize) * self.settings.gridSize;
                    pos.y = Math.round(pos.y / self.settings.gridSize) * self.settings.gridSize;
                    group.position(pos);
                }
                
                // Update element data
                var el = self.elements.find(function(e) { return e.id === element.id; });
                if (el) {
                    el.x = pos.x;
                    el.y = pos.y;
                }
                
                self.layer.draw();
                self.saveHistory();
                self.isDirty = true;
            });
            
            // ✅ FIX: transformend mit korrekter Größenberechnung
            group.on('transformend', function() {
                var el = self.elements.find(function(e) { return e.id === element.id; });
                if (el) {
                    el.x = group.x();
                    el.y = group.y();
                    el.rotation = group.rotation();
                    
                    // ✅ FIX: Größe aus Element-Daten * Scale berechnen
                    // (nicht aus group.width() - das gibt bei Groups 0 zurück!)
                    var scaleX = group.scaleX();
                    var scaleY = group.scaleY();
                    
                    el.width = Math.round(el.width * scaleX);
                    el.height = Math.round(el.height * scaleY);
                    
                    // Mindestgröße sicherstellen
                    el.width = Math.max(el.width, 20);
                    el.height = Math.max(el.height, 20);
                    
                    // Reset scale
                    group.scaleX(1);
                    group.scaleY(1);
                    
                    // Re-render element mit neuer Größe
                    group.destroy();
                    self.renderElement(el);
                    self.selectElement(el.id);
                }
                
                self.saveHistory();
                self.isDirty = true;
            });
            
            this.layer.add(group);
            this.layer.draw();
        },

        /**
         * Load all elements from data
         */
        loadElements: function() {
            var self = this;
            
            // Clear existing
            this.layer.find('.element').forEach(function(el) {
                el.destroy();
            });
            
            // Render each element
            this.elements.forEach(function(element) {
                self.renderElement(element);
            });
            
            this.updateElementsList();
        },

        /**
         * Select element
         */
        selectElement: function(elementId) {
            var self = this;
            
            var element = this.elements.find(function(e) { return e.id === elementId; });
            if (!element) return;
            
            this.selectedElement = element;
            
            // Find Konva node
            var node = this.layer.findOne('#' + elementId);
            if (node) {
                this.transformer.nodes([node]);
                this.layer.draw();
            }
            
            // Show properties panel
            $('#es-properties-panel').show();
            
            // Populate properties
            $('#es-prop-element-id').val(element.id);
            $('#es-prop-label').val(element.label);
            $('#es-prop-number').val(element.number);
            $('#es-prop-shape').val(element.shape);
            $('#es-prop-width').val(element.width);
            $('#es-prop-height').val(element.height);
            $('#es-prop-rotation').val(element.rotation);
            $('#es-prop-bookable').prop('checked', element.bookable);
            $('#es-prop-seats').val(element.seats || element.capacity);
            $('#es-prop-section').val(element.section_id);
            $('#es-prop-price').val(element.price);
            $('#es-prop-accessible').prop('checked', element.accessible);
            $('#es-prop-description').val(element.description);
            
            // Show/hide bookable options
            $('#es-bookable-options').toggle(element.bookable);
            
            // Update sections dropdown
            this.updateSectionsDropdown();
            
            // Enable delete button
            $('#es-delete-selected').prop('disabled', false);
            
            // Highlight in list
            $('.es-elements-list-item').removeClass('es-selected');
            $('.es-elements-list-item[data-id="' + elementId + '"]').addClass('es-selected');
        },

        /**
         * Deselect all elements
         */
        deselectAll: function() {
            this.selectedElement = null;
            this.transformer.nodes([]);
            this.layer.draw();
            $('#es-properties-panel').hide();
            $('#es-delete-selected').prop('disabled', true);
            $('.es-elements-list-item').removeClass('es-selected');
        },

        /**
         * Update selected element from properties
         */
        updateSelectedElement: function() {
            if (!this.selectedElement) return;
            
            var element = this.selectedElement;
            
            element.label = $('#es-prop-label').val();
            element.number = parseInt($('#es-prop-number').val()) || 0;
            element.shape = $('#es-prop-shape').val();
            element.width = parseInt($('#es-prop-width').val()) || 60;
            element.height = parseInt($('#es-prop-height').val()) || 60;
            element.rotation = parseInt($('#es-prop-rotation').val()) || 0;
            element.bookable = $('#es-prop-bookable').is(':checked');
            element.seats = parseInt($('#es-prop-seats').val()) || 0;
            element.capacity = element.seats;
            element.section_id = $('#es-prop-section').val();
            element.price = parseFloat($('#es-prop-price').val()) || 0;
            element.accessible = $('#es-prop-accessible').is(':checked');
            element.description = $('#es-prop-description').val();
            
            // Re-render element
            var node = this.layer.findOne('#' + element.id);
            if (node) {
                node.destroy();
            }
            this.renderElement(element);
            this.selectElement(element.id);
            this.updateElementsList();
            
            this.isDirty = true;
        },

        /**
         * Duplicate element
         */
        duplicateElement: function() {
            if (!this.selectedElement) return;
            
            var newElement = JSON.parse(JSON.stringify(this.selectedElement));
            newElement.id = 'el_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            newElement.x += 30;
            newElement.y += 30;
            newElement.number = this.getNextNumber(newElement.type);
            
            this.elements.push(newElement);
            this.renderElement(newElement);
            this.saveHistory();
            this.updateElementsList();
            this.selectElement(newElement.id);
            
            this.isDirty = true;
        },

        /**
         * Delete selected element
         */
        deleteSelected: function() {
            if (!this.selectedElement) return;
            
            if (!confirm(esFloorPlan.strings.confirmRemove)) return;
            
            var elementId = this.selectedElement.id;
            
            // Remove from array
            this.elements = this.elements.filter(function(e) { return e.id !== elementId; });
            
            // Remove from canvas
            var node = this.layer.findOne('#' + elementId);
            if (node) {
                node.destroy();
            }
            
            this.transformer.nodes([]);
            this.layer.draw();
            
            this.selectedElement = null;
            $('#es-properties-panel').hide();
            $('#es-delete-selected').prop('disabled', true);
            
            this.saveHistory();
            this.updateElementsList();
            this.isDirty = true;
        },

        /**
         * Update elements list
         */
        updateElementsList: function() {
            var self = this;
            var html = '';
            
            this.elements.forEach(function(element) {
                var typeLabel = esFloorPlan.strings[element.type] || element.type;
                var label = element.label || (element.number ? '#' + element.number : typeLabel);
                var sectionBadge = '';
                
                if (element.section_id) {
                    var section = self.sections.find(function(s) { return s.id === element.section_id; });
                    if (section) {
                        sectionBadge = '<span class="es-el-section" style="background: ' + section.color + '; color: #fff;">' + section.name + '</span>';
                    }
                }
                
                html += '<div class="es-elements-list-item" data-id="' + element.id + '">' +
                    '<span class="es-el-type" style="color: ' + self.elementColors[element.type] + '"><span class="dashicons dashicons-marker"></span></span>' +
                    '<span class="es-el-label">' + label + '</span>' +
                    sectionBadge +
                '</div>';
            });
            
            $('#es-elements-list').html(html);
            $('#es-element-count').text(this.elements.length);
            
            // Bind click
            $('.es-elements-list-item').on('click', function() {
                self.selectElement($(this).data('id'));
            });
        },

        /**
         * Initialize sections
         */
        initSections: function() {
            this.renderSections();
        },

        /**
         * Render sections list
         */
        renderSections: function() {
            var self = this;
            var html = '';
            
            this.sections.forEach(function(section) {
                html += '<div class="es-section-item" data-id="' + section.id + '">' +
                    '<span class="es-section-color" style="background: ' + section.color + ';"></span>' +
                    '<div class="es-section-info">' +
                        '<span class="es-section-name">' + section.name + '</span>' +
                        '<span class="es-section-price">' + (section.default_price ? '€' + section.default_price : 'No price') + '</span>' +
                    '</div>' +
                    '<div class="es-section-actions">' +
                        '<button type="button" class="es-edit-section" title="Edit"><span class="dashicons dashicons-edit"></span></button>' +
                        '<button type="button" class="es-delete-section" title="Delete"><span class="dashicons dashicons-trash"></span></button>' +
                    '</div>' +
                '</div>';
            });
            
            $('#es-sections-list').html(html);
            
            // Bind events
            $('.es-edit-section').on('click', function(e) {
                e.stopPropagation();
                var id = $(this).closest('.es-section-item').data('id');
                self.editSection(id);
            });
            
            $('.es-delete-section').on('click', function(e) {
                e.stopPropagation();
                var id = $(this).closest('.es-section-item').data('id');
                self.deleteSection(id);
            });
            
            // Update sections dropdown
            this.updateSectionsDropdown();
        },

        /**
         * Update sections dropdown
         */
        updateSectionsDropdown: function() {
            var html = '<option value="">No section</option>';
            this.sections.forEach(function(section) {
                html += '<option value="' + section.id + '">' + section.name + '</option>';
            });
            $('#es-prop-section').html(html);
            
            if (this.selectedElement) {
                $('#es-prop-section').val(this.selectedElement.section_id);
            }
        },

        /**
         * Open section modal
         */
        openSectionModal: function(sectionId) {
            var section = null;
            
            if (sectionId) {
                section = this.sections.find(function(s) { return s.id === sectionId; });
            }
            
            if (section) {
                $('#es-section-modal-title').text('Edit Section');
                $('#es-section-id').val(section.id);
                $('#es-section-name').val(section.name);
                $('#es-section-color').val(section.color);
                $('#es-section-price').val(section.default_price);
            } else {
                $('#es-section-modal-title').text('Add Section');
                $('#es-section-id').val('');
                $('#es-section-name').val('');
                $('#es-section-color').val('#3B82F6');
                $('#es-section-price').val('');
            }
            
            $('#es-section-modal').show();
        },

        /**
         * Edit section
         */
        editSection: function(sectionId) {
            this.openSectionModal(sectionId);
        },

        /**
         * Save section
         */
        saveSection: function() {
            var id = $('#es-section-id').val();
            var name = $('#es-section-name').val().trim();
            var color = $('#es-section-color').val();
            var price = parseFloat($('#es-section-price').val()) || 0;
            
            if (!name) {
                alert('Please enter a section name.');
                return;
            }
            
            if (id) {
                // Update existing
                var section = this.sections.find(function(s) { return s.id === id; });
                if (section) {
                    section.name = name;
                    section.color = color;
                    section.default_price = price;
                }
            } else {
                // Add new
                this.sections.push({
                    id: 'section_' + Date.now(),
                    name: name,
                    color: color,
                    default_price: price
                });
            }
            
            $('#es-section-modal').hide();
            this.renderSections();
            this.loadElements(); // Refresh colors
            this.isDirty = true;
        },

        /**
         * Delete section
         */
        deleteSection: function(sectionId) {
            if (!confirm('Are you sure you want to delete this section?')) return;
            
            this.sections = this.sections.filter(function(s) { return s.id !== sectionId; });
            
            // Remove from elements
            this.elements.forEach(function(element) {
                if (element.section_id === sectionId) {
                    element.section_id = '';
                }
            });
            
            this.renderSections();
            this.loadElements();
            this.isDirty = true;
        },

        /**
         * Select background image
         */
        selectBackground: function() {
            var self = this;
            
            var frame = wp.media({
                title: esFloorPlan.strings.selectBackground,
                button: { text: esFloorPlan.strings.useImage },
                multiple: false
            });
            
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                self.settings.background = attachment.url;
                
                $('#es-background-preview').html('<img src="' + attachment.url + '">');
                $('#es-background-url').val(attachment.url);
                $('#es-remove-background').show();
                
                self.loadBackgroundImage(attachment.url);
                self.isDirty = true;
            });
            
            frame.open();
        },

        /**
         * Remove background image
         */
        removeBackground: function() {
            this.settings.background = '';
            $('#es-background-preview').html('<span class="es-no-image">No image</span>');
            $('#es-background-url').val('');
            $('#es-remove-background').hide();
            
            this.bgLayer.destroyChildren();
            this.bgLayer.draw();
            
            this.isDirty = true;
        },

        /**
         * Update canvas size
         */
        updateCanvasSize: function() {
            this.settings.canvasWidth = parseInt($('#es-canvas-width').val()) || 1200;
            this.settings.canvasHeight = parseInt($('#es-canvas-height').val()) || 800;
            
            this.stage.width(this.settings.canvasWidth);
            this.stage.height(this.settings.canvasHeight);
            
            this.drawGrid();
            
            if (this.settings.background) {
                this.loadBackgroundImage(this.settings.background);
            }
            
            this.isDirty = true;
        },

        /**
         * Set zoom level
         */
        setZoom: function(level) {
            level = Math.max(0.25, Math.min(2, level));
            this.zoom = level;
            
            this.stage.scale({ x: level, y: level });
            this.stage.draw();
            
            $('#es-zoom-level').text(Math.round(level * 100) + '%');
        },

        /**
         * Fit canvas to view
         */
        fitToView: function() {
            var container = $('#es-canvas-container');
            var containerWidth = container.width() - 60;
            var containerHeight = container.height() - 60;
            
            var scaleX = containerWidth / this.settings.canvasWidth;
            var scaleY = containerHeight / this.settings.canvasHeight;
            var scale = Math.min(scaleX, scaleY, 1);
            
            this.setZoom(scale);
        },

        /**
         * Save history state
         */
        saveHistory: function() {
            // Remove future states
            this.history = this.history.slice(0, this.historyIndex + 1);
            
            // Add current state
            this.history.push(JSON.stringify(this.elements));
            this.historyIndex++;
            
            // Limit history
            if (this.history.length > 50) {
                this.history.shift();
                this.historyIndex--;
            }
            
            this.updateHistoryButtons();
        },

        /**
         * Undo
         */
        undo: function() {
            if (this.historyIndex <= 0) return;
            
            this.historyIndex--;
            this.elements = JSON.parse(this.history[this.historyIndex]);
            this.loadElements();
            this.deselectAll();
            this.updateHistoryButtons();
            this.isDirty = true;
        },

        /**
         * Redo
         */
        redo: function() {
            if (this.historyIndex >= this.history.length - 1) return;
            
            this.historyIndex++;
            this.elements = JSON.parse(this.history[this.historyIndex]);
            this.loadElements();
            this.deselectAll();
            this.updateHistoryButtons();
            this.isDirty = true;
        },

        /**
         * Update history buttons state
         */
        updateHistoryButtons: function() {
            $('#es-undo').prop('disabled', this.historyIndex <= 0);
            $('#es-redo').prop('disabled', this.historyIndex >= this.history.length - 1);
        },

        /**
         * Select all elements
         */
        selectAll: function() {
            var nodes = this.layer.find('.element');
            this.transformer.nodes(nodes);
            this.layer.draw();
        },

        /**
         * Save floor plan
         */
        save: function() {
            var self = this;
            var $btn = $('#es-floor-plan-save');
            
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Saving...');
            
            var data = {
                action: 'es_save_floor_plan',
                nonce: esFloorPlan.nonce,
                floor_plan_id: $('#es-floor-plan-id').val(),
                title: $('#es-floor-plan-title').val() || esFloorPlan.strings.untitled,
                canvas_width: this.settings.canvasWidth,
                canvas_height: this.settings.canvasHeight,
                background: this.settings.background,
                show_grid: this.settings.showGrid,
                grid_size: this.settings.gridSize,
                location_id: $('#es-linked-location').val(),
                sections: JSON.stringify(this.sections),
                elements: JSON.stringify(this.elements)
            };
            
            $.ajax({
                url: esFloorPlan.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        $('#es-floor-plan-id').val(response.data.floor_plan_id);
                        self.isDirty = false;
                        
                        $btn.html('<span class="dashicons dashicons-yes"></span> Saved!');
                        setTimeout(function() {
                            $btn.html('<span class="dashicons dashicons-cloud-saved"></span> Save');
                        }, 2000);
                    } else {
                        alert(response.data.message || esFloorPlan.strings.error);
                        $btn.html('<span class="dashicons dashicons-cloud-saved"></span> Save');
                    }
                },
                error: function() {
                    alert(esFloorPlan.strings.error);
                    $btn.html('<span class="dashicons dashicons-cloud-saved"></span> Save');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Load floor plan for editing
         */
        loadFloorPlan: function(floorPlanId) {
            var self = this;
            
            $.ajax({
                url: esFloorPlan.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_get_floor_plan',
                    nonce: esFloorPlan.nonce,
                    floor_plan_id: floorPlanId
                },
                success: function(response) {
                    if (response.success) {
                        self.openEditor(response.data);
                    } else {
                        alert(response.data.message || esFloorPlan.strings.error);
                    }
                },
                error: function() {
                    alert(esFloorPlan.strings.error);
                }
            });
        },

        /**
         * Duplicate floor plan
         */
        duplicateFloorPlan: function(floorPlanId) {
            var self = this;
            
            $.ajax({
                url: esFloorPlan.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_duplicate_floor_plan',
                    nonce: esFloorPlan.nonce,
                    floor_plan_id: floorPlanId
                },
                success: function(response) {
                    if (response.success) {
                        self.loadFloorPlans();
                    } else {
                        alert(response.data.message || esFloorPlan.strings.error);
                    }
                },
                error: function() {
                    alert(esFloorPlan.strings.error);
                }
            });
        },

        /**
         * Delete floor plan
         */
        deleteFloorPlan: function(floorPlanId) {
            var self = this;
            
            if (!confirm(esFloorPlan.strings.confirmDelete)) return;
            
            $.ajax({
                url: esFloorPlan.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_delete_floor_plan',
                    nonce: esFloorPlan.nonce,
                    floor_plan_id: floorPlanId
                },
                success: function(response) {
                    if (response.success) {
                        self.loadFloorPlans();
                    } else {
                        alert(response.data.message || esFloorPlan.strings.error);
                    }
                },
                error: function() {
                    alert(esFloorPlan.strings.error);
                }
            });
        },

        /**
         * Preview floor plan
         */
        preview: function() {
            // TODO: Implement preview modal
            alert('Preview functionality coming soon!');
        },

        /**
         * Filter floor plans by search term
         */
        filterFloorPlans: function(term) {
            term = term.toLowerCase();
            
            $('.es-floor-plan-card').each(function() {
                var title = $(this).find('.es-floor-plan-card-title').text().toLowerCase();
                var location = $(this).find('.es-floor-plan-card-meta').text().toLowerCase();
                
                if (title.indexOf(term) > -1 || location.indexOf(term) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.es-floor-plans-wrap').length) {
            ESFloorPlanEditor.init();
        }
        
        // Handle "Create First" button
        $(document).on('click', '#es-create-first-btn', function() {
            ESFloorPlanEditor.openEditor();
        });
    });

})(jQuery);
