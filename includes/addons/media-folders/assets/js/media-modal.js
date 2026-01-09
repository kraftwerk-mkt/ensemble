/**
 * Ensemble Media Folders Pro - Media Modal Integration
 * =====================================================
 * Extends WordPress Media Modal with folder filtering
 *
 * @package Ensemble
 * @subpackage Addons/MediaFolders
 * @since 2.7.0
 */

(function($) {
    'use strict';

    /**
     * Media Modal Folders Extension
     */
    const ESMediaModalFolders = {
        
        currentFolder: '',
        initialized: false,
        
        /**
         * Initialize
         */
        init: function() {
            console.log('ESMediaModalFolders: init() called, initialized=', this.initialized);
            
            if (this.initialized) {
                console.log('ESMediaModalFolders: Already initialized, skipping');
                return;
            }
            
            const self = this;
            
            // Check if data is available
            if (typeof esMediaFoldersData === 'undefined') {
                console.log('ESMediaModalFolders: esMediaFoldersData not available, will retry...');
                setTimeout(function() {
                    if (typeof esMediaFoldersData !== 'undefined') {
                        self.initialized = false;
                        self.init();
                    } else {
                        console.log('ESMediaModalFolders: esMediaFoldersData still not available after retry');
                    }
                }, 500);
                return;
            }
            
            this.data = esMediaFoldersData;
            this.config = typeof esMediaFoldersModal !== 'undefined' ? esMediaFoldersModal : {};
            
            // Debug: Log folder data
            console.log('ESMediaModalFolders: Loaded data:', {
                folderCount: this.data.folders?.length || 0,
                folders: this.data.folders,
                tree: this.data.tree,
                debug: this.data.debug
            });
            
            // Extend WordPress media query for filtering
            this.extendMedia();
            
            // Watch for media modal opening
            this.watchForModal();
            
            this.initialized = true;
            console.log('ESMediaModalFolders: Initialization complete');
        },
        
        /**
         * Extend WordPress media query to include folder filter
         */
        extendMedia: function() {
            const self = this;
            
            try {
                if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                    return;
                }
                
                // Wait for wp.media.model.Query
                if (typeof wp.media.model === 'undefined' || typeof wp.media.model.Query === 'undefined') {
                    setTimeout(function() {
                        self.extendMedia();
                    }, 100);
                    return;
                }
                
                // Check if already extended
                if (wp.media.model.Query.prototype._esExtended) {
                    return;
                }
                
                // Override the sync method to add folder filter
                const originalSync = wp.media.model.Query.prototype.sync;
                
                wp.media.model.Query.prototype.sync = function(method, model, options) {
                    if (self.currentFolder) {
                        options = options || {};
                        options.data = options.data || {};
                        options.data.query = options.data.query || {};
                        options.data.query.es_folder = self.currentFolder;
                    }
                    return originalSync.apply(this, arguments);
                };
                
                // Mark as extended
                wp.media.model.Query.prototype._esExtended = true;
                console.log('ESMediaModalFolders: Extended wp.media.model.Query');
                
                // Hook into uploader to add folder to uploads
                this.extendUploader();
                
            } catch (e) {
                console.error('ESMediaModalFolders: Error extending media', e);
            }
        },
        
        /**
         * Extend uploader to include folder in upload
         */
        extendUploader: function() {
            const self = this;
            
            // Override wp.Uploader defaults to include folder
            if (typeof wp.Uploader !== 'undefined') {
                // Store original init
                const originalInit = wp.Uploader.prototype.init;
                
                wp.Uploader.prototype.init = function() {
                    // Call original init first
                    if (originalInit) {
                        originalInit.apply(this, arguments);
                    }
                    
                    const uploader = this.uploader;
                    if (!uploader) return;
                    
                    // Add BeforeUpload handler
                    uploader.bind('BeforeUpload', function(up, file) {
                        const folder = self.currentFolder;
                        if (folder && folder !== 'uncategorized' && !folder.toString().startsWith('smart_')) {
                            up.settings.multipart_params = up.settings.multipart_params || {};
                            up.settings.multipart_params.es_upload_folder = folder;
                            console.log('ESMediaModalFolders: Uploading to folder:', folder);
                        }
                    });
                    
                    // Refresh after upload complete
                    uploader.bind('UploadComplete', function() {
                        console.log('ESMediaModalFolders: Upload complete, refreshing view...');
                        setTimeout(function() {
                            self.refreshCollection();
                        }, 300);
                    });
                };
                
                console.log('ESMediaModalFolders: Extended wp.Uploader');
            }
            
            // Also hook into plupload init events
            $(document).on('click', '.browser.button, .upload-ui button', function() {
                setTimeout(function() {
                    self.patchActivePlupload();
                }, 100);
            });
        },
        
        /**
         * Patch the currently active plupload instance
         */
        patchActivePlupload: function() {
            const self = this;
            
            // Find all plupload instances
            if (typeof window.plupload !== 'undefined') {
                // Look for uploader on the modal
                const $modal = $('.media-modal:visible');
                if ($modal.length) {
                    const uploaderDiv = $modal.find('.uploader-inline')[0];
                    if (uploaderDiv && uploaderDiv.plupload) {
                        const uploader = uploaderDiv.plupload;
                        if (!uploader._esFolderPatched) {
                            uploader.bind('BeforeUpload', function(up, file) {
                                const folder = self.currentFolder;
                                if (folder && folder !== 'uncategorized' && !folder.toString().startsWith('smart_')) {
                                    up.settings.multipart_params = up.settings.multipart_params || {};
                                    up.settings.multipart_params.es_upload_folder = folder;
                                }
                            });
                            uploader._esFolderPatched = true;
                            console.log('ESMediaModalFolders: Patched plupload instance');
                        }
                    }
                }
            }
        },
        
        /**
         * Watch for media modal to be opened
         */
        watchForModal: function() {
            const self = this;
            
            // Use MutationObserver to detect when modal is added to DOM
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            // Check if it's the media modal
                            if ($(node).hasClass('media-modal') || $(node).find('.media-modal').length) {
                                setTimeout(function() {
                                    self.injectSidebar();
                                }, 200);
                            }
                        }
                    });
                });
            });
            
            observer.observe(document.body, { 
                childList: true, 
                subtree: true 
            });
            
            // Also bind to common triggers
            $(document).on('click', '.insert-media, .set-post-thumbnail, [data-editor], .upload-button, .media-button, .acf-gallery-add, .acf-image-uploader, .components-button.editor-post-featured-image__toggle, .components-button.block-editor-media-placeholder__button', function() {
                setTimeout(function() {
                    self.injectSidebar();
                }, 300);
            });
            
            // ACF specific
            $(document).on('click', '[data-name="add-attachment"], [data-name="add"], .acf-gallery-add', function() {
                setTimeout(function() {
                    self.injectSidebar();
                }, 300);
            });
        },
        
        /**
         * Inject folder sidebar into media modal
         */
        injectSidebar: function() {
            const self = this;
            const $modal = $('.media-modal:visible');
            
            if (!$modal.length) {
                console.log('ESMediaModalFolders: No visible modal found');
                return;
            }
            
            // Check if sidebar already exists anywhere in modal
            if ($modal.find('.es-modal-folder-sidebar').length) {
                console.log('ESMediaModalFolders: Sidebar already exists');
                return;
            }
            
            // Prevent re-injection loop
            if (this._injecting) {
                console.log('ESMediaModalFolders: Already injecting, skipping');
                return;
            }
            this._injecting = true;
            
            // Find the best container - prefer media-frame-content as it's more stable
            const $frameContent = $modal.find('.media-frame-content');
            
            if (!$frameContent.length) {
                console.log('ESMediaModalFolders: No frame-content found');
                this._injecting = false;
                return;
            }
            
            console.log('ESMediaModalFolders: Injecting sidebar into frame-content');
            
            // Build sidebar
            const sidebarHtml = this.buildSidebarHtml();
            const $sidebar = $(sidebarHtml);
            
            // Add sidebar as FIRST child of frame-content (before any dynamic content)
            $frameContent.prepend($sidebar);
            $frameContent.addClass('es-has-folder-sidebar');
            
            // Bind events
            this.bindSidebarEvents($sidebar);
            
            // Log folder data for debugging
            console.log('ESMediaModalFolders: Folder data loaded:', {
                totalFolders: this.data.folders?.length || 0,
                folders: this.data.folders,
                hasTree: !!this.data.tree,
                treeLength: this.data.tree?.length || 0
            });
            
            // Small delay before allowing next injection
            setTimeout(function() {
                self._injecting = false;
            }, 500);
            
            console.log('ESMediaModalFolders: Sidebar injection complete');
        },
        
        /**
         * Watch for view changes and ensure sidebar stays visible
         */
        watchSidebarRemoval: function($modal) {
            const self = this;
            
            // Don't create multiple observers
            if (this._sidebarObserver) {
                return;
            }
            
            // Debounce function
            let debounceTimer = null;
            const debouncedInject = function() {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    if ($modal.is(':visible') && !$modal.find('.es-modal-folder-sidebar').length) {
                        console.log('ESMediaModalFolders: Sidebar missing, re-injecting (debounced)');
                        self.injectSidebar();
                    }
                }, 300);
            };
            
            // Create observer
            this._sidebarObserver = new MutationObserver(function(mutations) {
                // Only react if sidebar is actually gone
                if (!$modal.find('.es-modal-folder-sidebar').length) {
                    debouncedInject();
                }
            });
            
            // Observe the frame content
            const frameContent = $modal.find('.media-frame-content')[0];
            if (frameContent) {
                this._sidebarObserver.observe(frameContent, { 
                    childList: true
                });
                console.log('ESMediaModalFolders: Watching for sidebar removal');
            }
        },
        
        /**
         * Build sidebar HTML
         */
        buildSidebarHtml: function() {
            const self = this;
            let html = '<div class="es-modal-folder-sidebar">';
            
            // Header
            html += '<div class="es-modal-folder-header">';
            html += '<span class="dashicons dashicons-portfolio"></span>';
            html += '<span>Folders</span>';
            html += '</div>';
            
            // Folder list
            html += '<ul class="es-modal-folder-list">';
            
            // All Media - check if currently active
            const allActive = !this.currentFolder ? ' es-modal-folder-item--active' : '';
            html += '<li class="es-modal-folder-item' + allActive + '" data-folder="">';
            html += '<span class="dashicons dashicons-images-alt2"></span>';
            html += '<span class="es-modal-folder-name">All Media</span>';
            html += '</li>';
            
            // Uncategorized - check if currently active
            const uncatCount = this.data.counts?.uncategorized || 0;
            const uncatActive = this.currentFolder === 'uncategorized' ? ' es-modal-folder-item--active' : '';
            html += '<li class="es-modal-folder-item' + uncatActive + '" data-folder="uncategorized">';
            html += '<span class="dashicons dashicons-category"></span>';
            html += '<span class="es-modal-folder-name">Uncategorized</span>';
            html += '<span class="es-modal-folder-count">' + uncatCount + '</span>';
            html += '</li>';
            
            // Smart Folders
            if (this.data.smartFolders && this.data.smartFolders.length) {
                html += '<li class="es-modal-folder-divider"><span>Smart Filters</span></li>';
                
                this.data.smartFolders.forEach(function(folder) {
                    const colorStyle = folder.color ? ' style="color:' + folder.color + '"' : '';
                    const isActive = self.currentFolder === folder.id ? ' es-modal-folder-item--active' : '';
                    html += '<li class="es-modal-folder-item es-modal-folder-item--smart' + isActive + '" data-folder="' + folder.id + '">';
                    html += '<span class="dashicons ' + (folder.icon || 'dashicons-filter') + '"' + colorStyle + '></span>';
                    html += '<span class="es-modal-folder-name">' + self.escapeHtml(folder.name) + '</span>';
                    if (folder.count > 0) {
                        html += '<span class="es-modal-folder-count">' + folder.count + '</span>';
                    }
                    html += '</li>';
                });
            }
            
            // Regular Folders
            if (this.data.folders && this.data.folders.length) {
                html += '<li class="es-modal-folder-divider"><span>Folders</span></li>';
                
                this.data.folders.forEach(function(folder) {
                    const colorStyle = folder.color ? ' style="color:' + folder.color + '"' : '';
                    // Use depth field if available, otherwise check for em-dash prefix
                    const isChild = folder.depth > 0 || folder.name.startsWith('—') || folder.name.startsWith('— ');
                    const indent = isChild ? ' es-modal-folder-item--child' : '';
                    const count = self.data.counts?.[folder.id] || folder.count || 0;
                    // Check if this folder is active (compare as strings to handle numeric IDs)
                    const isActive = String(self.currentFolder) === String(folder.id) ? ' es-modal-folder-item--active' : '';
                    
                    // Clean name (remove em-dash prefix for display)
                    let displayName = folder.name;
                    while (displayName.startsWith('—') || displayName.startsWith('— ')) {
                        displayName = displayName.replace(/^—\s*/, '');
                    }
                    
                    html += '<li class="es-modal-folder-item' + indent + isActive + '" data-folder="' + folder.id + '">';
                    html += '<span class="dashicons ' + (folder.icon || 'dashicons-portfolio') + '"' + colorStyle + '></span>';
                    html += '<span class="es-modal-folder-name">' + self.escapeHtml(displayName) + '</span>';
                    if (count > 0) {
                        html += '<span class="es-modal-folder-count">' + count + '</span>';
                    }
                    html += '</li>';
                });
            }
            
            html += '</ul>';
            html += '</div>';
            
            return html;
        },
        
        /**
         * Bind sidebar events
         */
        bindSidebarEvents: function($sidebar) {
            const self = this;
            
            $sidebar.on('click', '.es-modal-folder-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $item = $(this);
                // Use attr instead of data to get the raw value
                const folderId = $item.attr('data-folder') || '';
                
                // Update active state
                $sidebar.find('.es-modal-folder-item').removeClass('es-modal-folder-item--active');
                $item.addClass('es-modal-folder-item--active');
                
                // Set current folder (empty string = all media)
                self.currentFolder = folderId;
                
                console.log('ESMediaModalFolders: Selected folder:', folderId || '(all media)');
                
                // Refresh the collection
                self.refreshCollection();
            });
        },
        
        /**
         * Refresh the media collection
         */
        refreshCollection: function() {
            const self = this;
            
            console.log('ESMediaModalFolders: Refreshing with folder:', this.currentFolder || 'all');
            
            // Try to find the frame and refresh
            if (typeof wp !== 'undefined' && wp.media && wp.media.frame) {
                const frame = wp.media.frame;
                
                // Try to get the library from current state
                try {
                    const state = frame.state();
                    if (state) {
                        const library = state.get('library');
                        if (library && library.props) {
                            // Set the folder filter and force refresh
                            library.props.set({ 
                                es_folder: this.currentFolder,
                                // Force cache bust
                                _esRefresh: Date.now()
                            });
                            
                            // Clear and reload
                            library.reset();
                            
                            // Small delay then fetch more
                            setTimeout(function() {
                                if (library.more) {
                                    library.more();
                                }
                            }, 50);
                            
                            console.log('ESMediaModalFolders: Refreshed via library.props');
                            return;
                        }
                    }
                } catch (e) {
                    console.log('ESMediaModalFolders: Could not refresh via state', e);
                }
                
                // Alternative: Try through content region
                try {
                    if (frame.content && frame.content.get) {
                        const view = frame.content.get();
                        if (view && view.collection && view.collection.props) {
                            view.collection.props.set({ 
                                es_folder: this.currentFolder,
                                _esRefresh: Date.now()
                            });
                            view.collection.reset();
                            setTimeout(function() {
                                if (view.collection.more) {
                                    view.collection.more();
                                }
                            }, 50);
                            console.log('ESMediaModalFolders: Refreshed via content.collection');
                            return;
                        }
                    }
                } catch (e) {
                    console.log('ESMediaModalFolders: Could not refresh via frame.content', e);
                }
            }
            
            // Last resort: Trigger a search change to force refresh
            const $search = $('.media-modal .media-toolbar-secondary .search');
            if ($search.length) {
                // Add and remove a space to trigger change
                const val = $search.val() || '';
                $search.val(val + ' ').trigger('input');
                setTimeout(function() {
                    $search.val(val.trim()).trigger('input');
                }, 100);
                console.log('ESMediaModalFolders: Refreshed via search trigger');
            }
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Initialize when ready
     */
    function initWhenReady() {
        console.log('ESMediaModalFolders: initWhenReady called');
        console.log('ESMediaModalFolders: jQuery exists:', typeof jQuery !== 'undefined');
        console.log('ESMediaModalFolders: wp exists:', typeof wp !== 'undefined');
        console.log('ESMediaModalFolders: wp.media exists:', typeof wp !== 'undefined' && typeof wp.media !== 'undefined');
        console.log('ESMediaModalFolders: esMediaFoldersData exists:', typeof esMediaFoldersData !== 'undefined');
        
        // Wait for jQuery and wp.media
        if (typeof jQuery !== 'undefined' && typeof wp !== 'undefined' && typeof wp.media !== 'undefined') {
            ESMediaModalFolders.init();
        } else {
            setTimeout(initWhenReady, 200);
        }
    }
    
    // Start initialization on document ready
    $(document).ready(function() {
        // Small delay to ensure WordPress media is loaded
        setTimeout(initWhenReady, 100);
    });
    
    // Also try on window load as backup
    $(window).on('load', function() {
        setTimeout(initWhenReady, 500);
    });

    // Export
    window.ESMediaModalFolders = ESMediaModalFolders;

})(jQuery);
