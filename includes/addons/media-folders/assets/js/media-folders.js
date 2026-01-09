/**
 * Ensemble Media Folders Pro - JavaScript
 * ========================================
 * Handles folder sidebar, drag & drop, and AJAX operations
 *
 * @package Ensemble
 * @subpackage Addons/MediaFolders
 * @since 2.7.0
 */

(function($) {
    'use strict';

    /**
     * Media Folders Module
     */
    const ESMediaFolders = {
        
        // State
        currentFolder: null,
        selectedAttachments: [],
        isDragging: false,
        
        /**
         * Initialize
         */
        init: function() {
            if (typeof esMediaFolders === 'undefined') {
                console.warn('ESMediaFolders: Config not found');
                return;
            }
            
            this.config = esMediaFolders;
            this.bindEvents();
            this.injectSidebar();
            this.loadFolderTree();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;
            
            // Folder navigation
            $(document).on('click', '.es-folder-link', function(e) {
                e.preventDefault();
                // Don't navigate if clicking on toggle button
                if ($(e.target).closest('.es-folder-toggle').length) {
                    return;
                }
                const folderId = $(this).data('folder-id');
                self.navigateToFolder(folderId);
            });
            
            // Folder toggle (expand/collapse)
            $(document).on('click', '.es-folder-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $item = $(this).closest('.es-folder-item');
                $item.toggleClass('es-folder-item--expanded');
            });
            
            // Add folder button (header)
            $(document).on('click', '.es-folder-sidebar-header .es-add-folder-btn', function(e) {
                e.preventDefault();
                self.showNewFolderInput(0);
            });
            
            // Add subfolder button (in folder actions)
            $(document).on('click', '.es-add-subfolder-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const parentId = $(this).data('parent-id');
                self.showNewFolderInput(parentId);
            });
            
            // Rename folder button
            $(document).on('click', '.es-rename-folder-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $item = $(this).closest('.es-folder-item');
                self.renameFolder($item);
            });
            
            // Delete folder button
            $(document).on('click', '.es-delete-folder-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $item = $(this).closest('.es-folder-item');
                self.deleteFolder($item);
            });
            
            // New folder input
            $(document).on('keypress', '.es-new-folder-input', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.createFolder($(this));
                }
            });
            
            $(document).on('keydown', '.es-new-folder-input', function(e) {
                if (e.which === 27) { // Escape
                    $(this).closest('.es-new-folder-input-wrapper').parent().remove();
                }
            });
            
            $(document).on('click', '.es-new-folder-save', function() {
                const $input = $(this).siblings('.es-new-folder-input');
                self.createFolder($input);
            });
            
            $(document).on('click', '.es-new-folder-cancel', function() {
                $(this).closest('.es-new-folder-input-wrapper').parent().remove();
            });
            
            // Context menu
            $(document).on('contextmenu', '.es-folder-item:not(.es-folder-item--special)', function(e) {
                e.preventDefault();
                self.showContextMenu(e, $(this));
            });
            
            $(document).on('click', function() {
                self.hideContextMenu();
            });
            
            // Context menu actions
            $(document).on('click', '.es-folder-context-add-subfolder', function() {
                const $item = $(this).closest('.es-folder-context-menu').data('folder-item');
                self.hideContextMenu();
                self.showNewFolderInput($item.data('folder-id'));
            });
            
            $(document).on('click', '.es-folder-context-rename', function() {
                const $item = $(this).closest('.es-folder-context-menu').data('folder-item');
                self.hideContextMenu();
                self.renameFolder($item);
            });
            
            $(document).on('click', '.es-folder-context-delete', function() {
                const $item = $(this).closest('.es-folder-context-menu').data('folder-item');
                self.hideContextMenu();
                self.deleteFolder($item);
            });
            
            // Folder Drag & Drop (moving folders)
            this.initFolderDragDrop();
            
            // Media Drag & Drop (moving media to folders)
            this.initDragDrop();
            
            // Media Modal integration
            if (wp.media) {
                this.initMediaModal();
            }
        },
        
        /**
         * Inject sidebar into Media Library
         */
        injectSidebar: function() {
            // Only on media library page
            if (!$('body').hasClass('upload-php')) {
                return;
            }
            
            // Check current folder from URL
            const urlParams = new URLSearchParams(window.location.search);
            const currentFolder = urlParams.get('es_media_folder') || 'all';
            
            const sidebarHtml = `
                <div class="es-folder-sidebar" id="es-folder-sidebar">
                    <div class="es-folder-sidebar-header">
                        <h3 class="es-folder-sidebar-title">
                            <span class="dashicons dashicons-portfolio"></span>
                            Folders
                        </h3>
                        <button type="button" class="es-add-folder-btn" title="${this.config.strings.newFolder}">
                            <span class="dashicons dashicons-plus-alt2"></span>
                        </button>
                    </div>
                    <ul class="es-folder-list" id="es-folder-list">
                        <li class="es-folder-item es-folder-item--special">
                            <a href="#" class="es-folder-link ${currentFolder === 'all' || !currentFolder ? 'active' : ''}" data-folder-id="all" data-folder-slug="">
                                <span class="es-folder-icon"><span class="dashicons dashicons-images-alt2"></span></span>
                                <span class="es-folder-name">${this.config.strings.allMedia}</span>
                                <span class="es-folder-count" data-count="all">-</span>
                            </a>
                        </li>
                        <li class="es-folder-item es-folder-item--special">
                            <a href="#" class="es-folder-link ${currentFolder === 'uncategorized' ? 'active' : ''}" data-folder-id="uncategorized" data-folder-slug="uncategorized">
                                <span class="es-folder-icon"><span class="dashicons dashicons-category"></span></span>
                                <span class="es-folder-name">${this.config.strings.uncategorized}</span>
                                <span class="es-folder-count" data-count="uncategorized">-</span>
                            </a>
                        </li>
                    </ul>
                </div>
            `;
            
            $('body').append(sidebarHtml);
            
            // Store current folder
            this.currentFolder = currentFolder;
        },
        
        /**
         * Load folder tree via AJAX
         */
        loadFolderTree: function() {
            const self = this;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_get_folder_tree',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderFolderTree(response.data.tree, response.data.counts);
                    }
                }
            });
        },
        
        /**
         * Render folder tree
         */
        renderFolderTree: function(tree, counts) {
            const self = this;
            const $list = $('#es-folder-list');
            
            // Store tree for later use
            this.folderTree = tree;
            this.folderCounts = counts;
            
            // Update counts for special folders
            $list.find('[data-count="all"]').text(this.calculateTotalCount(counts));
            $list.find('[data-count="uncategorized"]').text(counts.uncategorized || 0);
            
            // Clear existing folders (except special)
            $list.find('.es-folder-item:not(.es-folder-item--special)').remove();
            $list.find('.es-folder-divider').remove();
            
            // Add Smart Folders if available
            if (this.config.settings && this.config.settings.smart_folders_enabled) {
                this.renderSmartFolders($list, counts);
            }
            
            // Add divider before regular folders
            if (tree.length > 0) {
                $list.append('<li class="es-folder-divider"><span>Folders</span></li>');
            }
            
            // Render each parent folder
            tree.forEach(function(folder) {
                const html = self.renderFolderItem(folder, counts);
                $list.append(html);
            });
            
            // Render breadcrumb and subfolders in main area
            this.renderBreadcrumbAndSubfolders(tree, counts);
        },
        
        /**
         * Render breadcrumb navigation and subfolder grid
         */
        renderBreadcrumbAndSubfolders: function(tree, counts) {
            const self = this;
            
            // Remove existing
            $('.es-folder-breadcrumb, .es-subfolder-grid').remove();
            
            // Only show if we're in a folder
            if (!this.currentFolder || this.currentFolder === 'all' || this.currentFolder === 'uncategorized') {
                return;
            }
            
            // Find current folder in tree
            const currentFolderData = this.findFolderBySlug(tree, this.currentFolder);
            
            if (!currentFolderData) {
                return;
            }
            
            // Build breadcrumb
            const breadcrumb = this.buildBreadcrumb(tree, currentFolderData);
            
            // Render breadcrumb
            let breadcrumbHtml = '<div class="es-folder-breadcrumb">';
            breadcrumbHtml += '<a href="#" class="es-folder-breadcrumb-item" data-folder-id="all">';
            breadcrumbHtml += '<span class="dashicons dashicons-admin-media"></span> All Media';
            breadcrumbHtml += '</a>';
            
            breadcrumb.forEach(function(folder, index) {
                breadcrumbHtml += '<span class="es-folder-breadcrumb-separator">/</span>';
                const isCurrent = index === breadcrumb.length - 1;
                breadcrumbHtml += `<a href="#" class="es-folder-breadcrumb-item ${isCurrent ? 'current' : ''}" data-folder-id="${folder.term_id}" data-folder-slug="${folder.slug}">`;
                breadcrumbHtml += `<span class="dashicons ${folder.icon || 'dashicons-portfolio'}"></span> ${this.escapeHtml(folder.name)}`;
                breadcrumbHtml += '</a>';
            }.bind(this));
            
            breadcrumbHtml += '</div>';
            
            // Insert breadcrumb after .wp-filter
            const $filter = $('.upload-php .wp-filter');
            if ($filter.length) {
                $filter.after(breadcrumbHtml);
            }
            
            // Render subfolders if current folder has children
            if (currentFolderData.children && currentFolderData.children.length > 0) {
                let subfolderHtml = '<div class="es-subfolder-grid">';
                
                currentFolderData.children.forEach(function(child) {
                    const count = counts[child.term_id] || 0;
                    subfolderHtml += `
                        <a href="#" class="es-subfolder-card" data-folder-id="${child.term_id}" data-folder-slug="${child.slug}">
                            <div class="es-subfolder-card-icon" style="color: ${child.color || 'var(--es-primary)'}">
                                <span class="dashicons ${child.icon || 'dashicons-portfolio'}"></span>
                            </div>
                            <div class="es-subfolder-card-name">${this.escapeHtml(child.name)}</div>
                            <div class="es-subfolder-card-count">${count} items</div>
                        </a>
                    `;
                }.bind(this));
                
                subfolderHtml += '</div>';
                
                // Insert after breadcrumb
                $('.es-folder-breadcrumb').after(subfolderHtml);
            }
            
            // Bind breadcrumb navigation
            $(document).off('click.breadcrumb').on('click.breadcrumb', '.es-folder-breadcrumb-item, .es-subfolder-card', function(e) {
                e.preventDefault();
                const folderId = $(this).data('folder-id');
                self.navigateToFolder(folderId);
            });
        },
        
        /**
         * Find folder by slug in tree
         */
        findFolderBySlug: function(tree, slug) {
            for (let folder of tree) {
                if (folder.slug === slug) {
                    return folder;
                }
                if (folder.children && folder.children.length > 0) {
                    const found = this.findFolderBySlug(folder.children, slug);
                    if (found) return found;
                }
            }
            return null;
        },
        
        /**
         * Find folder by ID in tree
         */
        findFolderById: function(tree, id) {
            for (let folder of tree) {
                if (folder.term_id == id) {
                    return folder;
                }
                if (folder.children && folder.children.length > 0) {
                    const found = this.findFolderById(folder.children, id);
                    if (found) return found;
                }
            }
            return null;
        },
        
        /**
         * Build breadcrumb path to folder
         */
        buildBreadcrumb: function(tree, targetFolder) {
            const path = [];
            
            const findPath = function(folders, target, currentPath) {
                for (let folder of folders) {
                    const newPath = [...currentPath, folder];
                    if (folder.term_id === target.term_id) {
                        return newPath;
                    }
                    if (folder.children && folder.children.length > 0) {
                        const found = findPath(folder.children, target, newPath);
                        if (found) return found;
                    }
                }
                return null;
            };
            
            return findPath(tree, targetFolder, []) || [targetFolder];
        },
        
        /**
         * Render Smart Folders
         */
        renderSmartFolders: function($list, counts) {
            const smartFolders = [
                { id: 'smart_images', name: this.config.strings.smartImages || 'All Images', icon: 'dashicons-format-image', color: '#e91e63' },
                { id: 'smart_videos', name: this.config.strings.smartVideos || 'All Videos', icon: 'dashicons-video-alt3', color: '#ff5722' },
                { id: 'smart_documents', name: this.config.strings.smartDocuments || 'Documents', icon: 'dashicons-media-document', color: '#607d8b' },
                { id: 'smart_this_week', name: this.config.strings.smartThisWeek || 'This Week', icon: 'dashicons-calendar', color: '#00bcd4' },
                { id: 'smart_unused', name: this.config.strings.smartUnused || 'Unattached', icon: 'dashicons-dismiss', color: '#ff9800' },
                { id: 'smart_large', name: this.config.strings.smartLarge || 'Large Files', icon: 'dashicons-database', color: '#f44336' },
            ];
            
            // Add divider
            $list.append('<li class="es-folder-divider"><span>Smart Filters</span></li>');
            
            // Add smart folders
            smartFolders.forEach(function(folder) {
                const html = `
                    <li class="es-folder-item es-folder-item--smart" data-folder-id="${folder.id}">
                        <a href="#" class="es-folder-link" data-folder-id="${folder.id}">
                            <span class="es-folder-icon" style="color: ${folder.color}">
                                <span class="dashicons ${folder.icon}"></span>
                            </span>
                            <span class="es-folder-name">${folder.name}</span>
                        </a>
                    </li>
                `;
                $list.append(html);
            });
        },
        
        /**
         * Render a single folder item
         */
        renderFolderItem: function(folder, counts) {
            const self = this;
            const isParent = folder.type === 'parent';
            const hasChildren = folder.children && folder.children.length > 0;
            const count = counts[folder.term_id] || 0;
            const isActive = this.currentFolder === folder.slug;
            
            let typeClass = '';
            if (folder.slug === 'es-events') typeClass = 'es-folder-item--events';
            if (folder.slug === 'es-artists') typeClass = 'es-folder-item--artists';
            if (folder.slug === 'es-locations') typeClass = 'es-folder-item--locations';
            
            let html = `
                <li class="es-folder-item ${isParent ? 'es-folder-item--parent' : ''} ${typeClass} ${hasChildren ? 'es-folder-item--has-children es-folder-item--expanded' : ''}" 
                    data-folder-id="${folder.term_id}" 
                    data-folder-slug="${folder.slug}"
                    data-folder-name="${this.escapeHtml(folder.name)}">
                    <a href="#" class="es-folder-link ${isActive ? 'active' : ''}" data-folder-id="${folder.term_id}" data-folder-slug="${folder.slug}">
                        ${hasChildren ? '<button type="button" class="es-folder-toggle"><span class="dashicons dashicons-arrow-right-alt2"></span></button>' : ''}
                        <span class="es-folder-icon" style="color: ${folder.color || 'inherit'}">
                            <span class="dashicons ${folder.icon || 'dashicons-portfolio'}"></span>
                        </span>
                        ${folder.color ? `<span class="es-folder-color" style="background-color: ${folder.color}"></span>` : ''}
                        <span class="es-folder-name">${this.escapeHtml(folder.name)}</span>
                        <span class="es-folder-count">${count}</span>
                    </a>
                    <div class="es-folder-actions">
                        <button type="button" class="es-folder-action-btn es-add-subfolder-btn" data-parent-id="${folder.term_id}" title="Add Subfolder">
                            <span class="dashicons dashicons-plus-alt"></span>
                        </button>
                        <button type="button" class="es-folder-action-btn es-rename-folder-btn" data-folder-id="${folder.term_id}" title="Rename">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        ${!folder.locked ? `
                        <button type="button" class="es-folder-action-btn es-delete-folder-btn danger" data-folder-id="${folder.term_id}" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                        ` : ''}
                    </div>
            `;
            
            // Render children
            if (hasChildren) {
                html += '<ul class="es-folder-children">';
                folder.children.forEach(function(child) {
                    html += self.renderFolderItem(child, counts);
                });
                html += '</ul>';
                
                // Add "New Folder" button for parent folders
                if (isParent) {
                    html += `
                        <div class="es-folder-item es-folder-item--add">
                            <button type="button" class="es-add-folder-btn es-folder-link" data-parent-id="${folder.term_id}">
                                <span class="es-folder-icon"><span class="dashicons dashicons-plus"></span></span>
                                <span class="es-folder-name">${this.config.strings.newFolder}</span>
                            </button>
                        </div>
                    `;
                }
            }
            
            html += '</li>';
            
            return html;
        },
        
        /**
         * Calculate total media count
         */
        calculateTotalCount: function(counts) {
            let total = counts.uncategorized || 0;
            for (let key in counts) {
                if (key !== 'uncategorized') {
                    total += counts[key];
                }
            }
            return total;
        },
        
        /**
         * Navigate to folder
         */
        navigateToFolder: function(folderId) {
            const currentUrl = new URL(window.location.href);
            
            // Remove existing folder filter
            currentUrl.searchParams.delete('es_media_folder');
            
            // Set new filter
            if (folderId && folderId !== 'all') {
                if (folderId === 'uncategorized') {
                    currentUrl.searchParams.set('es_media_folder', 'uncategorized');
                } else {
                    // Get folder slug
                    const $link = $(`.es-folder-link[data-folder-id="${folderId}"]`);
                    const folderSlug = $link.data('folder-slug') || folderId;
                    currentUrl.searchParams.set('es_media_folder', folderSlug);
                }
            }
            
            // Navigate
            window.location.href = currentUrl.toString();
        },
        
        /**
         * Show new folder input
         */
        showNewFolderInput: function(parentId) {
            // Remove any existing input
            $('.es-new-folder-input-wrapper').remove();
            
            const inputHtml = `
                <div class="es-new-folder-input-wrapper">
                    <input type="text" class="es-new-folder-input" placeholder="${this.config.strings.newFolder}" data-parent-id="${parentId}" autofocus>
                    <button type="button" class="es-new-folder-save"><span class="dashicons dashicons-yes"></span></button>
                    <button type="button" class="es-new-folder-cancel"><span class="dashicons dashicons-no"></span></button>
                </div>
            `;
            
            if (parentId) {
                // Insert after parent folder
                const $parent = $(`.es-folder-item[data-folder-id="${parentId}"]`);
                if ($parent.find('.es-folder-children').length) {
                    $parent.find('.es-folder-children').prepend(inputHtml);
                } else {
                    $parent.append('<ul class="es-folder-children">' + inputHtml + '</ul>');
                }
                $parent.addClass('es-folder-item--expanded');
            } else {
                $('#es-folder-list').append('<li>' + inputHtml + '</li>');
            }
            
            $('.es-new-folder-input').focus();
        },
        
        /**
         * Create folder
         */
        createFolder: function($input) {
            const self = this;
            const name = $input.val().trim();
            const parentId = $input.data('parent-id') || 0;
            
            if (!name) {
                $input.focus();
                return;
            }
            
            // Disable input during request
            $input.prop('disabled', true);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_create_folder',
                    nonce: this.config.nonce,
                    name: name,
                    parent_id: parentId
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the input wrapper
                        $input.closest('.es-new-folder-input-wrapper').parent().remove();
                        
                        self.showToast(self.config.strings.folderCreated, 'success');
                        self.loadFolderTree();
                    } else {
                        $input.prop('disabled', false);
                        self.showToast(response.data || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    $input.prop('disabled', false);
                    self.showToast(self.config.strings.error, 'error');
                }
            });
        },
        
        /**
         * Show context menu
         */
        showContextMenu: function(e, $item) {
            this.hideContextMenu();
            
            const folderId = $item.data('folder-id');
            const folderName = $item.data('folder-name');
            const isLocked = $item.find('.es-folder-link').first().data('locked');
            
            const menuHtml = `
                <div class="es-folder-context-menu" style="top: ${e.pageY}px; left: ${e.pageX}px;">
                    <div class="es-folder-context-menu-header">${this.escapeHtml(folderName)}</div>
                    <button type="button" class="es-folder-context-menu-item es-folder-context-add-subfolder">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Add Subfolder
                    </button>
                    <button type="button" class="es-folder-context-menu-item es-folder-context-rename">
                        <span class="dashicons dashicons-edit"></span>
                        ${this.config.strings.rename}
                    </button>
                    ${!isLocked ? `
                    <div class="es-folder-context-menu-separator"></div>
                    <button type="button" class="es-folder-context-menu-item es-folder-context-delete danger">
                        <span class="dashicons dashicons-trash"></span>
                        ${this.config.strings.delete}
                    </button>
                    ` : ''}
                </div>
            `;
            
            const $menu = $(menuHtml).appendTo('body');
            $menu.data('folder-item', $item);
            
            // Adjust position if menu goes off-screen
            const menuWidth = $menu.outerWidth();
            const menuHeight = $menu.outerHeight();
            const windowWidth = $(window).width();
            const windowHeight = $(window).height();
            
            if (e.pageX + menuWidth > windowWidth) {
                $menu.css('left', (e.pageX - menuWidth) + 'px');
            }
            if (e.pageY + menuHeight > windowHeight) {
                $menu.css('top', (e.pageY - menuHeight) + 'px');
            }
        },
        
        /**
         * Hide context menu
         */
        hideContextMenu: function() {
            $('.es-folder-context-menu').remove();
        },
        
        /**
         * Rename folder
         */
        renameFolder: function($item) {
            const self = this;
            const folderId = $item.data('folder-id');
            const $nameEl = $item.find('.es-folder-name').first();
            const currentName = $nameEl.text();
            
            // Replace with input
            const $input = $('<input type="text" class="es-folder-rename-input">').val(currentName);
            $nameEl.replaceWith($input);
            $input.focus().select();
            
            const saveRename = function() {
                const newName = $input.val().trim();
                
                if (newName && newName !== currentName) {
                    $.ajax({
                        url: self.config.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'es_rename_folder',
                            nonce: self.config.nonce,
                            folder_id: folderId,
                            name: newName
                        },
                        success: function(response) {
                            if (response.success) {
                                $input.replaceWith(`<span class="es-folder-name">${self.escapeHtml(newName)}</span>`);
                            } else {
                                $input.replaceWith(`<span class="es-folder-name">${self.escapeHtml(currentName)}</span>`);
                                self.showToast(response.data || self.config.strings.error, 'error');
                            }
                        }
                    });
                } else {
                    $input.replaceWith(`<span class="es-folder-name">${self.escapeHtml(currentName)}</span>`);
                }
            };
            
            $input.on('blur', saveRename).on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    saveRename();
                }
            });
        },
        
        /**
         * Delete folder
         */
        deleteFolder: function($item) {
            const self = this;
            const folderId = $item.data('folder-id');
            
            if (!confirm(this.config.strings.confirmDelete)) {
                return;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_delete_folder',
                    nonce: this.config.nonce,
                    folder_id: folderId
                },
                success: function(response) {
                    if (response.success) {
                        $item.slideUp(200, function() {
                            $(this).remove();
                        });
                        self.showToast(self.config.strings.folderDeleted, 'success');
                    } else {
                        self.showToast(response.data || self.config.strings.error, 'error');
                    }
                }
            });
        },
        
        /**
         * Initialize Folder Drag & Drop (moving folders)
         * Uses mousedown approach to avoid WordPress upload overlay
         */
        initFolderDragDrop: function() {
            const self = this;
            
            // Track folder dragging state
            let draggedFolder = null;
            let $dragHelper = null;
            let isDragging = false;
            
            // Folder drag start via mousedown
            $(document).on('mousedown', '.es-folder-item:not(.es-folder-item--special) > .es-folder-link', function(e) {
                // Only left mouse button
                if (e.which !== 1) return;
                
                // Don't start drag if clicking on buttons or toggle
                if ($(e.target).closest('.es-folder-toggle, .es-folder-actions, button').length) {
                    return;
                }
                
                const $item = $(this).closest('.es-folder-item');
                const folderId = $item.data('folder-id');
                const folderName = $item.data('folder-name');
                
                let startX = e.pageX;
                let startY = e.pageY;
                
                // Track mouse movement
                $(document).on('mousemove.folderdrag', function(e) {
                    // Start drag after moving 5px
                    if (!isDragging && (Math.abs(e.pageX - startX) > 5 || Math.abs(e.pageY - startY) > 5)) {
                        isDragging = true;
                        draggedFolder = {
                            id: folderId,
                            name: folderName,
                            $item: $item
                        };
                        
                        $item.addClass('es-folder-item--dragging');
                        
                        // Create drag helper
                        $dragHelper = $(`
                            <div class="es-folder-drag-helper">
                                <span class="dashicons dashicons-portfolio"></span>
                                <span>${self.escapeHtml(folderName)}</span>
                            </div>
                        `).appendTo('body');
                    }
                    
                    if (isDragging && $dragHelper) {
                        // Move helper
                        $dragHelper.css({
                            left: e.pageX + 15,
                            top: e.pageY + 15
                        });
                        
                        // Find drop target
                        const $target = self.findFolderDropTarget(e.pageX, e.pageY, folderId);
                        
                        // Update drop indicators
                        $('.es-folder-item').removeClass('es-folder-item--drop-target es-folder-item--drop-above es-folder-item--drop-below');
                        
                        if ($target && $target.$item) {
                            $target.$item.addClass('es-folder-item--drop-' + $target.position);
                        }
                    }
                });
                
                // Handle mouseup
                $(document).on('mouseup.folderdrag', function(e) {
                    $(document).off('mousemove.folderdrag mouseup.folderdrag');
                    
                    if (isDragging) {
                        // Find drop target
                        const $target = self.findFolderDropTarget(e.pageX, e.pageY, draggedFolder.id);
                        
                        if ($target && $target.$item) {
                            self.moveFolder(draggedFolder.id, $target.$item.data('folder-id'), $target.position);
                        }
                        
                        // Cleanup
                        $('.es-folder-item').removeClass('es-folder-item--dragging es-folder-item--drop-target es-folder-item--drop-above es-folder-item--drop-below');
                        if ($dragHelper) {
                            $dragHelper.remove();
                            $dragHelper = null;
                        }
                    }
                    
                    isDragging = false;
                    draggedFolder = null;
                });
                
                // Prevent text selection while potentially dragging
                e.preventDefault();
            });
        },
        
        /**
         * Find folder drop target at position
         */
        findFolderDropTarget: function(x, y, excludeId) {
            const self = this;
            let result = null;
            
            $('.es-folder-item:not(.es-folder-item--special)').each(function() {
                const $item = $(this);
                const folderId = $item.data('folder-id');
                
                // Skip the dragged folder itself
                if (folderId == excludeId) return;
                
                // Skip if this is a child of the dragged folder
                if ($item.closest(`.es-folder-item[data-folder-id="${excludeId}"]`).length) return;
                
                const rect = this.getBoundingClientRect();
                
                // Check if mouse is over this item
                if (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom) {
                    const relY = y - rect.top;
                    const height = rect.height;
                    
                    let position = 'target';
                    if (relY < height * 0.3) {
                        position = 'above';
                    } else if (relY > height * 0.7) {
                        position = 'below';
                    }
                    
                    result = {
                        $item: $item,
                        position: position
                    };
                    return false; // Stop iteration
                }
            });
            
            return result;
        },
        
        /**
         * Move folder to new parent
         */
        moveFolder: function(folderId, targetId, position) {
            const self = this;
            
            // Determine new parent based on position
            let newParentId = 0;
            
            if (position === 'target') {
                // Dropped inside - target becomes parent
                newParentId = targetId;
            } else {
                // Dropped above or below - get parent of target (becomes sibling)
                const $target = $(`.es-folder-item[data-folder-id="${targetId}"]`);
                const $parent = $target.closest('.es-folder-children').closest('.es-folder-item');
                newParentId = $parent.length ? $parent.data('folder-id') : 0;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_move_folder',
                    nonce: this.config.nonce,
                    folder_id: folderId,
                    new_parent_id: newParentId
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('Folder moved', 'success');
                        self.loadFolderTree();
                    } else {
                        self.showToast(response.data || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(self.config.strings.error, 'error');
                }
            });
        },
        
        /**
         * Initialize Drag & Drop (media to folders)
         */
        initDragDrop: function() {
            const self = this;
            
            // Make attachments draggable
            $(document).on('mousedown', '.attachment', function(e) {
                // Start drag tracking
                const $attachment = $(this);
                let startX = e.pageX;
                let startY = e.pageY;
                
                $(document).on('mousemove.esdrag', function(e) {
                    if (Math.abs(e.pageX - startX) > 5 || Math.abs(e.pageY - startY) > 5) {
                        $(document).off('mousemove.esdrag');
                        self.startDrag($attachment, e);
                    }
                });
                
                $(document).on('mouseup.esdrag', function() {
                    $(document).off('mousemove.esdrag mouseup.esdrag');
                });
            });
        },
        
        /**
         * Start dragging
         */
        startDrag: function($attachment, e) {
            const self = this;
            this.isDragging = true;
            
            // Get selected attachments
            const selectedIds = [];
            if ($attachment.hasClass('selected')) {
                $('.attachment.selected').each(function() {
                    selectedIds.push($(this).data('id'));
                    $(this).addClass('es-dragging');
                });
            } else {
                selectedIds.push($attachment.data('id'));
                $attachment.addClass('es-dragging');
            }
            
            this.selectedAttachments = selectedIds;
            
            // Create drag helper
            const $helper = $(`
                <div class="es-drag-helper">
                    <span class="es-drag-count">${selectedIds.length}</span>
                    <span>${selectedIds.length === 1 ? 'item' : 'items'}</span>
                </div>
            `).appendTo('body');
            
            // Track mouse
            $(document).on('mousemove.esdragging', function(e) {
                $helper.css({
                    left: e.pageX + 15,
                    top: e.pageY + 15
                });
                
                // Check if over a folder
                const $folder = self.getFolderAtPoint(e.pageX, e.pageY);
                $('.es-folder-item--drop-target').removeClass('es-folder-item--drop-target');
                
                if ($folder) {
                    $folder.addClass('es-folder-item--drop-target');
                }
            });
            
            $(document).on('mouseup.esdragging', function(e) {
                $(document).off('mousemove.esdragging mouseup.esdragging');
                $helper.remove();
                $('.attachment').removeClass('es-dragging');
                
                // Check if dropped on folder
                const $folder = self.getFolderAtPoint(e.pageX, e.pageY);
                $('.es-folder-item--drop-target').removeClass('es-folder-item--drop-target');
                
                if ($folder) {
                    const folderId = $folder.data('folder-id') || $folder.find('.es-folder-link').data('folder-id');
                    self.moveToFolder(self.selectedAttachments, folderId);
                }
                
                self.isDragging = false;
                self.selectedAttachments = [];
            });
        },
        
        /**
         * Get folder element at point
         */
        getFolderAtPoint: function(x, y) {
            let result = null;
            
            $('.es-folder-item').each(function() {
                const rect = this.getBoundingClientRect();
                if (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom) {
                    result = $(this);
                }
            });
            
            return result;
        },
        
        /**
         * Move attachments to folder
         */
        moveToFolder: function(attachmentIds, folderId) {
            const self = this;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_move_to_folder',
                    nonce: this.config.nonce,
                    attachment_ids: attachmentIds,
                    folder_id: folderId === 'uncategorized' ? 0 : folderId
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message, 'success');
                        self.loadFolderTree();
                        
                        // Refresh media grid if filtering by folder
                        const urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('es_media_folder')) {
                            window.location.reload();
                        }
                    } else {
                        self.showToast(response.data || self.config.strings.error, 'error');
                    }
                }
            });
        },
        
        /**
         * Initialize Media Modal integration
         */
        initMediaModal: function() {
            // Hook into media modal open
            if (wp.media.view.Modal) {
                const originalOpen = wp.media.view.Modal.prototype.open;
                const self = this;
                
                wp.media.view.Modal.prototype.open = function() {
                    originalOpen.apply(this, arguments);
                    setTimeout(function() {
                        self.addFolderFilterToModal();
                    }, 100);
                };
            }
        },
        
        /**
         * Add folder filter to media modal
         */
        addFolderFilterToModal: function() {
            // This would inject folder filtering into the media modal
            // Implementation depends on specific requirements
        },
        
        /**
         * Show toast notification
         */
        showToast: function(message, type) {
            const icon = type === 'success' ? 'yes-alt' : 'warning';
            
            const $toast = $(`
                <div class="es-toast es-toast--${type}">
                    <span class="dashicons dashicons-${icon}"></span>
                    <span>${this.escapeHtml(message)}</span>
                </div>
            `).appendTo('body');
            
            setTimeout(function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Initialize on DOM ready
     */
    $(document).ready(function() {
        ESMediaFolders.init();
    });

    // Export for external access
    window.ESMediaFolders = ESMediaFolders;

})(jQuery);
