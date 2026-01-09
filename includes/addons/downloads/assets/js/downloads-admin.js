/**
 * Ensemble Downloads - Admin JavaScript
 * 
 * @package Ensemble
 * @subpackage Addons/Downloads
 */

(function($) {
    'use strict';
    
    var EsDownloadsAdmin = {
        
        currentDownload: null,
        mediaFrame: null,
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.loadDownloads();
            this.initSelect2();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            
            // Add new download
            $('#es-add-download, #es-add-download-empty').on('click', function() {
                self.openModal();
            });
            
            // Edit download
            $(document).on('click', '.es-edit-download', function(e) {
                e.preventDefault();
                var $row = $(this).closest('tr');
                var downloadId = $row.data('id');
                self.editDownload(downloadId);
            });
            
            // Delete download
            $(document).on('click', '.es-delete-download', function(e) {
                e.preventDefault();
                var downloadId = $(this).data('id');
                self.deleteDownload(downloadId);
            });
            
            // Close modal
            $('.es-modal-close, .es-modal-close-btn, .es-modal-overlay').on('click', function() {
                self.closeModal();
            });
            
            // Save download
            $('#es-save-download').on('click', function() {
                self.saveDownload();
            });
            
            // File upload
            $('#es-select-file').on('click', function(e) {
                e.preventDefault();
                self.openMediaLibrary();
            });
            
            // Remove file
            $('#es-remove-file').on('click', function() {
                self.removeFile();
            });
            
            // Search
            var searchTimeout;
            $('#es-download-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.loadDownloads();
                }, 300);
            });
            
            // Type filter
            $('#es-download-type-filter').on('change', function() {
                self.loadDownloads();
            });
            
            // Prevent form submit
            $('#es-download-form').on('submit', function(e) {
                e.preventDefault();
            });
        },
        
        /**
         * Initialize Select2
         */
        initSelect2: function() {
            if ($.fn.select2) {
                $('.es-select2').each(function() {
                    var $select = $(this);
                    var postType = $select.data('post-type');
                    var placeholder = $select.data('placeholder');
                    
                    $select.select2({
                        placeholder: placeholder || 'Select...',
                        allowClear: true,
                        dropdownParent: $('#es-download-modal'),
                        ajax: {
                            url: ajaxurl,
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    action: 'ensemble_search_posts',
                                    nonce: es_downloads_admin.nonce,
                                    q: params.term || '',
                                    post_type: postType,
                                    page: params.page || 1
                                };
                            },
                            processResults: function(data, params) {
                                params.page = params.page || 1;
                                return {
                                    results: data.results || [],
                                    pagination: {
                                        more: data.pagination ? data.pagination.more : false
                                    }
                                };
                            }
                        },
                        minimumInputLength: 0
                    });
                });
            }
        },
        
        /**
         * Load downloads
         */
        loadDownloads: function() {
            var self = this;
            var $list = $('#es-downloads-list');
            var search = $('#es-download-search').val();
            var type = $('#es-download-type-filter').val();
            
            $list.html('<tr class="es-loading"><td colspan="6"><span class="spinner is-active"></span> Loading downloads...</td></tr>');
            
            $.ajax({
                url: es_downloads_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'es_get_downloads',
                    nonce: es_downloads_admin.nonce,
                    search: search,
                    type: type
                },
                success: function(response) {
                    if (response.success) {
                        self.renderDownloads(response.data.downloads);
                        self.updateStats(response.data.downloads);
                    } else {
                        $list.html('<tr><td colspan="6">Error loading downloads</td></tr>');
                    }
                },
                error: function() {
                    $list.html('<tr><td colspan="6">Error loading downloads</td></tr>');
                }
            });
        },
        
        /**
         * Render downloads table
         */
        renderDownloads: function(downloads) {
            var $list = $('#es-downloads-list');
            $list.empty();
            
            if (!downloads || downloads.length === 0) {
                var emptyTemplate = wp.template('es-downloads-empty');
                $list.html(emptyTemplate({}));
                return;
            }
            
            var rowTemplate = wp.template('es-download-row');
            
            downloads.forEach(function(download) {
                // Add computed properties
                download.file_size_formatted = EsDownloadsAdmin.formatFileSize(download.file_size);
                download.file_icon = EsDownloadsAdmin.getFileIcon(download.file_extension);
                download.type_color = EsDownloadsAdmin.getTypeColor(download.type_slug);
                
                $list.append(rowTemplate(download));
            });
        },
        
        /**
         * Update stats
         */
        updateStats: function(downloads) {
            $('#es-download-count').text(downloads.length);
            
            var totalDownloads = downloads.reduce(function(sum, d) {
                return sum + (d.download_count || 0);
            }, 0);
            
            $('#es-total-downloads').text(totalDownloads);
            
            // Update bulk download button visibility
            if (typeof EsBulkDownload !== 'undefined') {
                EsBulkDownload.updateButtonVisibility(downloads.length);
            }
        },
        
        /**
         * Open modal for new download
         */
        openModal: function() {
            this.currentDownload = null;
            this.resetForm();
            $('#es-modal-title').text('New Download');
            $('#es-download-modal').fadeIn(200);
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            $('#es-download-modal').fadeOut(200);
            this.currentDownload = null;
        },
        
        /**
         * Reset form
         */
        resetForm: function() {
            $('#es-download-form')[0].reset();
            $('#es-download-id').val('');
            this.removeFile();
            
            // Clear Select2 - both value and options
            $('#es-download-events').empty().trigger('change');
            $('#es-download-artists').empty().trigger('change');
            $('#es-download-locations').empty().trigger('change');
        },
        
        /**
         * Edit download
         */
        editDownload: function(downloadId) {
            var self = this;
            
            $.ajax({
                url: es_downloads_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'es_get_download',
                    nonce: es_downloads_admin.nonce,
                    download_id: downloadId
                },
                success: function(response) {
                    if (response.success) {
                        self.populateForm(response.data.download);
                        $('#es-modal-title').text('Edit Download');
                        $('#es-download-modal').fadeIn(200);
                    } else {
                        alert(response.data.message || es_downloads_admin.i18n.error);
                    }
                }
            });
        },
        
        /**
         * Populate form with download data
         */
        populateForm: function(download) {
            var self = this;
            this.currentDownload = download;
            
            $('#es-download-id').val(download.id);
            $('#es-download-title').val(download.title);
            $('#es-download-description').val(download.description);
            
            // Set type - ensure the value exists in the select
            var $typeSelect = $('#es-download-type');
            var typeSlug = download.type_slug || 'other';
            $typeSelect.val(typeSlug).trigger('change');
            
            $('#es-download-order').val(download.menu_order);
            $('#es-download-from').val(download.available_from);
            $('#es-download-until').val(download.available_until);
            $('#es-download-login').prop('checked', download.require_login);
            
            // Set file
            if (download.file_id) {
                $('#es-download-file-id').val(download.file_id);
                this.showFilePreview({
                    id: download.file_id,
                    filename: download.file_name,
                    filesizeHumanReadable: this.formatFileSize(download.file_size)
                });
            }
            
            // Clear and set Select2 values
            var $artists = $('#es-download-artists');
            $artists.empty().trigger('change');
            if (download.artists && download.artists.length) {
                download.artists.forEach(function(artist) {
                    var option = new Option(artist.title, artist.id, true, true);
                    $artists.append(option);
                });
                $artists.trigger('change');
            }
            
            var $events = $('#es-download-events');
            $events.empty().trigger('change');
            if (download.events && download.events.length) {
                download.events.forEach(function(event) {
                    var option = new Option(event.title, event.id, true, true);
                    $events.append(option);
                });
                $events.trigger('change');
            }
            
            var $locations = $('#es-download-locations');
            $locations.empty().trigger('change');
            if (download.locations && download.locations.length) {
                download.locations.forEach(function(location) {
                    var option = new Option(location.title, location.id, true, true);
                    $locations.append(option);
                });
                $locations.trigger('change');
            }
        },
        
        /**
         * Save download
         */
        saveDownload: function() {
            var self = this;
            var $form = $('#es-download-form');
            var $saveBtn = $('#es-save-download');
            
            // Validate
            var title = $('#es-download-title').val().trim();
            var fileId = $('#es-download-file-id').val();
            
            if (!title) {
                alert('Please enter a title.');
                $('#es-download-title').focus();
                return;
            }
            
            if (!fileId) {
                alert('Please select a file.');
                return;
            }
            
            // Collect data
            var data = {
                action: 'es_save_download',
                nonce: es_downloads_admin.nonce,
                download_id: $('#es-download-id').val(),
                title: title,
                description: $('#es-download-description').val(),
                type: $('#es-download-type').val(),
                file_id: fileId,
                artists: $('#es-download-artists').val() || [],
                events: $('#es-download-events').val() || [],
                locations: $('#es-download-locations').val() || [],
                available_from: $('#es-download-from').val(),
                available_until: $('#es-download-until').val(),
                require_login: $('#es-download-login').is(':checked') ? 1 : 0,
                menu_order: $('#es-download-order').val()
            };
            
            $saveBtn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: es_downloads_admin.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.closeModal();
                        self.loadDownloads();
                        self.showNotice(es_downloads_admin.i18n.save_success, 'success');
                    } else {
                        alert(response.data.message || es_downloads_admin.i18n.error);
                    }
                },
                error: function() {
                    alert(es_downloads_admin.i18n.error);
                },
                complete: function() {
                    $saveBtn.prop('disabled', false).text('Save Download');
                }
            });
        },
        
        /**
         * Delete download
         */
        deleteDownload: function(downloadId) {
            var self = this;
            
            if (!confirm(es_downloads_admin.i18n.confirm_delete)) {
                return;
            }
            
            $.ajax({
                url: es_downloads_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'es_delete_download',
                    nonce: es_downloads_admin.nonce,
                    download_id: downloadId
                },
                success: function(response) {
                    if (response.success) {
                        self.loadDownloads();
                        self.showNotice(es_downloads_admin.i18n.delete_success, 'success');
                    } else {
                        alert(response.data.message || es_downloads_admin.i18n.error);
                    }
                }
            });
        },
        
        /**
         * Open media library
         */
        openMediaLibrary: function() {
            var self = this;
            
            if (this.mediaFrame) {
                this.mediaFrame.open();
                return;
            }
            
            this.mediaFrame = wp.media({
                title: es_downloads_admin.i18n.select_file,
                button: {
                    text: es_downloads_admin.i18n.select_file
                },
                multiple: false
            });
            
            this.mediaFrame.on('select', function() {
                var attachment = self.mediaFrame.state().get('selection').first().toJSON();
                $('#es-download-file-id').val(attachment.id);
                self.showFilePreview(attachment);
            });
            
            this.mediaFrame.open();
        },
        
        /**
         * Show file preview
         */
        showFilePreview: function(file) {
            var $preview = $('#es-file-preview');
            var icon = this.getFileIcon(file.filename ? file.filename.split('.').pop() : '');
            
            $preview.addClass('has-file').html(
                '<span class="dashicons ' + icon + '"></span>' +
                '<span class="es-file-upload__info">' +
                    '<span class="es-file-upload__name">' + file.filename + '</span>' +
                    '<span class="es-file-upload__meta">' + file.filesizeHumanReadable + '</span>' +
                '</span>'
            );
            
            $('#es-select-file').text(es_downloads_admin.i18n.change_file);
            $('#es-remove-file').show();
        },
        
        /**
         * Remove file
         */
        removeFile: function() {
            $('#es-download-file-id').val('');
            $('#es-file-preview')
                .removeClass('has-file')
                .html('<span class="es-file-upload__placeholder">' + es_downloads_admin.i18n.no_file + '</span>');
            $('#es-select-file').text(es_downloads_admin.i18n.select_file);
            $('#es-remove-file').hide();
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.es-downloads-admin h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        /**
         * Format file size
         */
        formatFileSize: function(bytes) {
            if (!bytes) return '0 Bytes';
            
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        /**
         * Get file icon class
         */
        getFileIcon: function(ext) {
            if (!ext) return 'dashicons-download';
            
            var icons = {
                'pdf': 'dashicons-pdf',
                'doc': 'dashicons-media-document',
                'docx': 'dashicons-media-document',
                'xls': 'dashicons-media-spreadsheet',
                'xlsx': 'dashicons-media-spreadsheet',
                'ppt': 'dashicons-slides',
                'pptx': 'dashicons-slides',
                'zip': 'dashicons-archive',
                'rar': 'dashicons-archive',
                'mp4': 'dashicons-video-alt3',
                'webm': 'dashicons-video-alt3',
                'mp3': 'dashicons-format-audio',
                'jpg': 'dashicons-format-image',
                'jpeg': 'dashicons-format-image',
                'png': 'dashicons-format-image',
                'gif': 'dashicons-format-image'
            };
            
            return icons[ext.toLowerCase()] || 'dashicons-download';
        },
        
        /**
         * Get type color
         */
        getTypeColor: function(slug) {
            var types = es_downloads_admin.download_types || {};
            return types[slug] ? types[slug].color : '#95a5a6';
        }
    };
    
    /**
     * Wizard Integration for Downloads
     */
    var EsDownloadsWizard = {
        
        downloads: [],
        linkModal: null,
        
        /**
         * Initialize wizard integration
         */
        init: function() {
            // Only initialize if wizard elements exist
            if (!$('#es-downloads-wizard-list').length) {
                return;
            }
            
            this.bindEvents();
            this.initLinkModal();
        },
        
        /**
         * Bind wizard events
         */
        bindEvents: function() {
            var self = this;
            
            // Link existing download
            $(document).on('click', '#es-link-download-btn', function(e) {
                e.preventDefault();
                self.openLinkModal();
            });
            
            // Create new download (opens main modal)
            $(document).on('click', '#es-add-download-wizard-btn', function(e) {
                e.preventDefault();
                self.openCreateModal();
            });
            
            // Remove download from event
            $(document).on('click', '.es-download-wizard-remove', function(e) {
                e.preventDefault();
                var downloadId = $(this).closest('.es-download-wizard-item').data('download-id');
                self.removeDownload(downloadId);
            });
            
            // Listen for downloads loaded event from admin.js
            $(document).on('es:downloads:loaded', function(e, downloads) {
                self.downloads = downloads || [];
                self.render();
            });
            
            // Listen for form reset (new event)
            $(document).on('ensemble_form_reset', function() {
                self.downloads = [];
                self.render();
            });
        },
        
        /**
         * Initialize link modal HTML
         */
        initLinkModal: function() {
            if ($('#es-download-link-modal').length) {
                return;
            }
            
            var modalHtml = '<div id="es-download-link-modal" class="es-modal" style="display: none;">' +
                '<div class="es-modal__overlay"></div>' +
                '<div class="es-modal__container" style="max-width: 500px;">' +
                    '<div class="es-modal__header">' +
                        '<h2 class="es-modal__title">Link Download</h2>' +
                        '<button type="button" class="es-modal__close">&times;</button>' +
                    '</div>' +
                    '<div class="es-modal__body">' +
                        '<div class="es-form-row">' +
                            '<label>Select an existing download:</label>' +
                            '<select id="es-download-link-select" class="es-select2-link" style="width: 100%;"></select>' +
                        '</div>' +
                    '</div>' +
                    '<div class="es-modal__footer">' +
                        '<button type="button" class="button es-modal__cancel">Cancel</button>' +
                        '<button type="button" class="button button-primary" id="es-download-link-confirm">Link</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            $('body').append(modalHtml);
            
            // Initialize Select2 for link modal
            var self = this;
            $('#es-download-link-select').select2({
                placeholder: 'Search downloads...',
                allowClear: true,
                dropdownParent: $('#es-download-link-modal'),
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'es_get_downloads',
                            nonce: es_downloads_admin.nonce,
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data) {
                        var results = [];
                        if (data.success && data.data.downloads) {
                            data.data.downloads.forEach(function(dl) {
                                // Skip already linked downloads
                                if (!self.isLinked(dl.id)) {
                                    results.push({
                                        id: dl.id,
                                        text: dl.title,
                                        download: dl
                                    });
                                }
                            });
                        }
                        return { results: results };
                    }
                },
                templateResult: function(data) {
                    if (!data.download) return data.text;
                    var dl = data.download;
                    return $('<span><strong>' + dl.title + '</strong><br><small>' + 
                             (dl.type ? dl.type.name : '') + ' - ' + 
                             EsDownloadsAdmin.formatFileSize(dl.file_size) + '</small></span>');
                }
            });
            
            // Modal events
            $('#es-download-link-modal').on('click', '.es-modal__close, .es-modal__cancel, .es-modal__overlay', function() {
                self.closeLinkModal();
            });
            
            $('#es-download-link-confirm').on('click', function() {
                self.confirmLink();
            });
        },
        
        /**
         * Check if download is already linked
         */
        isLinked: function(downloadId) {
            return this.downloads.some(function(d) {
                return d.id == downloadId;
            });
        },
        
        /**
         * Open link modal
         */
        openLinkModal: function() {
            $('#es-download-link-select').val(null).trigger('change');
            $('#es-download-link-modal').fadeIn(200);
        },
        
        /**
         * Close link modal
         */
        closeLinkModal: function() {
            $('#es-download-link-modal').fadeOut(200);
        },
        
        /**
         * Confirm link selection
         */
        confirmLink: function() {
            var $select = $('#es-download-link-select');
            var selected = $select.select2('data')[0];
            
            if (!selected || !selected.download) {
                alert('Please select a download.');
                return;
            }
            
            this.addDownload(selected.download);
            this.closeLinkModal();
        },
        
        /**
         * Open create modal (reuses main admin modal or redirects)
         */
        openCreateModal: function() {
            // Check if we're on the downloads admin page (modal exists)
            if ($('#es-download-modal').length) {
                // We're on downloads page - use the modal
                var eventId = $('#es-event-id').val();
                
                if (typeof EsDownloadsAdmin !== 'undefined') {
                    EsDownloadsAdmin.openModal();
                    
                    // Pre-select current event
                    if (eventId) {
                        var eventTitle = $('#es-event-title').val() || 'Aktuelles Event';
                        var $events = $('#es-download-events');
                        if ($events.length) {
                            var option = new Option(eventTitle, eventId, true, true);
                            $events.append(option).trigger('change');
                        }
                    }
                }
            } else {
                // We're in wizard - open downloads page in new tab
                var url = ajaxurl.replace('admin-ajax.php', 'admin.php?page=ensemble-downloads');
                window.open(url, '_blank');
            }
        },
        
        /**
         * Load downloads for event
         */
        loadEventDownloads: function(eventData) {
            var self = this;
            this.downloads = [];
            
            if (!eventData || !eventData.id) {
                this.render();
                return;
            }
            
            // Load downloads linked to this event
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_get_downloads',
                    nonce: es_downloads_admin.nonce,
                    event_id: eventData.id
                },
                success: function(response) {
                    if (response.success && response.data.downloads) {
                        self.downloads = response.data.downloads;
                    }
                    self.render();
                },
                error: function() {
                    self.render();
                }
            });
        },
        
        /**
         * Add download to list
         */
        addDownload: function(download) {
            if (this.isLinked(download.id)) {
                return;
            }
            
            this.downloads.push(download);
            this.render();
            this.updateHiddenField();
        },
        
        /**
         * Remove download from list
         */
        removeDownload: function(downloadId) {
            this.downloads = this.downloads.filter(function(d) {
                return d.id != downloadId;
            });
            this.render();
            this.updateHiddenField();
        },
        
        /**
         * Update hidden field with download IDs
         */
        updateHiddenField: function() {
            var ids = this.downloads.map(function(d) {
                return d.id;
            });
            $('#es-downloads-data').val(JSON.stringify(ids));
        },
        
        /**
         * Render downloads list
         */
        render: function() {
            var $list = $('#es-downloads-wizard-list');
            var $empty = $('#es-downloads-wizard-empty');
            
            $list.empty();
            
            if (this.downloads.length === 0) {
                $empty.show();
                return;
            }
            
            $empty.hide();
            
            var self = this;
            this.downloads.forEach(function(download) {
                var typeLabel = download.type ? (download.type.label || download.type.name || 'Other') : 'Other';
                var fileSize = EsDownloadsAdmin.formatFileSize(download.file_size);
                var icon = EsDownloadsAdmin.getFileIcon(download.file_extension);
                
                var itemHtml = '<div class="es-download-wizard-item" data-download-id="' + download.id + '">' +
                    '<div class="es-download-wizard-item__icon">' +
                        '<span class="dashicons ' + icon + '"></span>' +
                    '</div>' +
                    '<div class="es-download-wizard-item__info">' +
                        '<span class="es-download-wizard-item__title">' + self.escapeHtml(download.title) + '</span>' +
                        '<span class="es-download-wizard-item__meta">' + typeLabel + ' &bull; ' + fileSize + '</span>' +
                    '</div>' +
                    '<button type="button" class="es-download-wizard-remove" title="Remove">' +
                        '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>' +
                '</div>';
                
                $list.append(itemHtml);
            });
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    /**
     * Artist Manager Downloads Integration
     */
    var EsDownloadsArtist = {
        
        downloads: [],
        
        /**
         * Initialize
         */
        init: function() {
            if (!$('#es-artist-downloads-list').length) {
                return;
            }
            
            this.bindEvents();
            this.initLinkModal();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            
            // Link existing download
            $(document).on('click', '#es-artist-link-download-btn', function(e) {
                e.preventDefault();
                self.openLinkModal();
            });
            
            // Remove download
            $(document).on('click', '#es-artist-downloads-list .es-download-wizard-remove', function(e) {
                e.preventDefault();
                var downloadId = $(this).closest('.es-download-wizard-item').data('download-id');
                self.removeDownload(downloadId);
            });
            
            // Listen for artist loaded
            $(document).on('es:artist:loaded', function(e, artist) {
                if (artist && artist.downloads) {
                    self.downloads = artist.downloads;
                    self.render();
                }
            });
            
            // Listen for form reset
            $(document).on('es:artist:reset', function() {
                self.downloads = [];
                self.render();
            });
        },
        
        /**
         * Initialize link modal
         */
        initLinkModal: function() {
            // Reuse the wizard link modal logic
            if (!$('#es-artist-download-link-modal').length) {
                var modalHtml = '<div id="es-artist-download-link-modal" class="es-modal" style="display: none;">' +
                    '<div class="es-modal__overlay"></div>' +
                    '<div class="es-modal__container" style="max-width: 500px;">' +
                        '<div class="es-modal__header">' +
                            '<h2 class="es-modal__title">Link Download</h2>' +
                            '<button type="button" class="es-modal__close">&times;</button>' +
                        '</div>' +
                        '<div class="es-modal__body">' +
                            '<div class="es-form-row">' +
                                '<label>Select an existing download:</label>' +
                                '<select id="es-artist-download-link-select" style="width: 100%;"></select>' +
                            '</div>' +
                        '</div>' +
                        '<div class="es-modal__footer">' +
                            '<button type="button" class="button es-modal__cancel">Cancel</button>' +
                            '<button type="button" class="button button-primary" id="es-artist-download-link-confirm">Link</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
                
                $('body').append(modalHtml);
                
                var self = this;
                
                // Initialize Select2
                $('#es-artist-download-link-select').select2({
                    placeholder: 'Search downloads...',
                    allowClear: true,
                    dropdownParent: $('#es-artist-download-link-modal'),
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action: 'es_get_downloads',
                                nonce: es_downloads_admin.nonce,
                                search: params.term
                            };
                        },
                        processResults: function(data) {
                            var results = [];
                            if (data.success && data.data) {
                                data.data.forEach(function(download) {
                                    results.push({
                                        id: download.id,
                                        text: download.title,
                                        download: download
                                    });
                                });
                            }
                            return { results: results };
                        }
                    }
                });
                
                // Close modal
                $('#es-artist-download-link-modal .es-modal__close, #es-artist-download-link-modal .es-modal__cancel').on('click', function() {
                    $('#es-artist-download-link-modal').fadeOut(200);
                });
                
                // Confirm link
                $('#es-artist-download-link-confirm').on('click', function() {
                    var selected = $('#es-artist-download-link-select').select2('data');
                    if (selected && selected.length && selected[0].download) {
                        self.addDownload(selected[0].download);
                        $('#es-artist-download-link-modal').fadeOut(200);
                        $('#es-artist-download-link-select').val(null).trigger('change');
                    } else {
                        alert('Please select a download.');
                    }
                });
            }
        },
        
        /**
         * Open link modal
         */
        openLinkModal: function() {
            $('#es-artist-download-link-modal').fadeIn(200);
        },
        
        /**
         * Add download
         */
        addDownload: function(download) {
            // Check if already added
            var exists = this.downloads.some(function(d) { return d.id === download.id; });
            if (exists) {
                return;
            }
            
            this.downloads.push(download);
            this.updateHiddenField();
            this.render();
        },
        
        /**
         * Remove download
         */
        removeDownload: function(downloadId) {
            this.downloads = this.downloads.filter(function(d) { return d.id !== downloadId; });
            this.updateHiddenField();
            this.render();
        },
        
        /**
         * Update hidden field
         */
        updateHiddenField: function() {
            var ids = this.downloads.map(function(d) { return d.id; });
            $('#es-artist-downloads-data').val(JSON.stringify(ids));
        },
        
        /**
         * Render downloads list
         */
        render: function() {
            var $list = $('#es-artist-downloads-list');
            var $empty = $('#es-artist-downloads-empty');
            
            $list.empty();
            
            if (this.downloads.length === 0) {
                $empty.show();
                return;
            }
            
            $empty.hide();
            
            var self = this;
            this.downloads.forEach(function(download) {
                var typeLabel = download.type ? (download.type.label || download.type.name || 'Other') : 'Other';
                var fileSize = EsDownloadsAdmin.formatFileSize(download.file_size);
                var icon = EsDownloadsAdmin.getFileIcon(download.file_extension);
                
                var itemHtml = '<div class="es-download-wizard-item" data-download-id="' + download.id + '">' +
                    '<div class="es-download-wizard-item__icon">' +
                        '<span class="dashicons ' + icon + '"></span>' +
                    '</div>' +
                    '<div class="es-download-wizard-item__info">' +
                        '<span class="es-download-wizard-item__title">' + self.escapeHtml(download.title) + '</span>' +
                        '<span class="es-download-wizard-item__meta">' + typeLabel + ' &bull; ' + fileSize + '</span>' +
                    '</div>' +
                    '<button type="button" class="es-download-wizard-remove" title="Remove">' +
                        '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>' +
                '</div>';
                
                $list.append(itemHtml);
            });
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    };
    
    /**
     * Location Manager Downloads Integration
     */
    var EsDownloadsLocation = {
        
        downloads: [],
        
        /**
         * Initialize
         */
        init: function() {
            if (!$('#es-location-downloads-list').length) {
                return;
            }
            
            this.bindEvents();
            this.initLinkModal();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            
            // Link existing download
            $(document).on('click', '#es-location-link-download-btn', function(e) {
                e.preventDefault();
                self.openLinkModal();
            });
            
            // Remove download
            $(document).on('click', '#es-location-downloads-list .es-download-wizard-remove', function(e) {
                e.preventDefault();
                var downloadId = $(this).closest('.es-download-wizard-item').data('download-id');
                self.removeDownload(downloadId);
            });
            
            // Listen for location loaded
            $(document).on('es:location:loaded', function(e, location) {
                if (location && location.downloads) {
                    self.downloads = location.downloads;
                    self.render();
                }
            });
            
            // Listen for form reset
            $(document).on('es:location:reset', function() {
                self.downloads = [];
                self.render();
            });
        },
        
        /**
         * Initialize link modal
         */
        initLinkModal: function() {
            if (!$('#es-location-download-link-modal').length) {
                var modalHtml = '<div id="es-location-download-link-modal" class="es-modal" style="display: none;">' +
                    '<div class="es-modal__overlay"></div>' +
                    '<div class="es-modal__container" style="max-width: 500px;">' +
                        '<div class="es-modal__header">' +
                            '<h2 class="es-modal__title">Link Download</h2>' +
                            '<button type="button" class="es-modal__close">&times;</button>' +
                        '</div>' +
                        '<div class="es-modal__body">' +
                            '<div class="es-form-row">' +
                                '<label>Select an existing download:</label>' +
                                '<select id="es-location-download-link-select" style="width: 100%;"></select>' +
                            '</div>' +
                        '</div>' +
                        '<div class="es-modal__footer">' +
                            '<button type="button" class="button es-modal__cancel">Cancel</button>' +
                            '<button type="button" class="button button-primary" id="es-location-download-link-confirm">Link</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
                
                $('body').append(modalHtml);
                
                var self = this;
                
                // Initialize Select2
                $('#es-location-download-link-select').select2({
                    placeholder: 'Search downloads...',
                    allowClear: true,
                    dropdownParent: $('#es-location-download-link-modal'),
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action: 'es_get_downloads',
                                nonce: es_downloads_admin.nonce,
                                search: params.term
                            };
                        },
                        processResults: function(data) {
                            var results = [];
                            if (data.success && data.data) {
                                data.data.forEach(function(download) {
                                    results.push({
                                        id: download.id,
                                        text: download.title,
                                        download: download
                                    });
                                });
                            }
                            return { results: results };
                        }
                    }
                });
                
                // Close modal
                $('#es-location-download-link-modal .es-modal__close, #es-location-download-link-modal .es-modal__cancel').on('click', function() {
                    $('#es-location-download-link-modal').fadeOut(200);
                });
                
                // Confirm link
                $('#es-location-download-link-confirm').on('click', function() {
                    var selected = $('#es-location-download-link-select').select2('data');
                    if (selected && selected.length && selected[0].download) {
                        self.addDownload(selected[0].download);
                        $('#es-location-download-link-modal').fadeOut(200);
                        $('#es-location-download-link-select').val(null).trigger('change');
                    } else {
                        alert('Please select a download.');
                    }
                });
            }
        },
        
        /**
         * Open link modal
         */
        openLinkModal: function() {
            $('#es-location-download-link-modal').fadeIn(200);
        },
        
        /**
         * Add download
         */
        addDownload: function(download) {
            var exists = this.downloads.some(function(d) { return d.id === download.id; });
            if (exists) {
                return;
            }
            
            this.downloads.push(download);
            this.updateHiddenField();
            this.render();
        },
        
        /**
         * Remove download
         */
        removeDownload: function(downloadId) {
            this.downloads = this.downloads.filter(function(d) { return d.id !== downloadId; });
            this.updateHiddenField();
            this.render();
        },
        
        /**
         * Update hidden field
         */
        updateHiddenField: function() {
            var ids = this.downloads.map(function(d) { return d.id; });
            $('#es-location-downloads-data').val(JSON.stringify(ids));
        },
        
        /**
         * Render downloads list
         */
        render: function() {
            var $list = $('#es-location-downloads-list');
            var $empty = $('#es-location-downloads-empty');
            
            $list.empty();
            
            if (this.downloads.length === 0) {
                $empty.show();
                return;
            }
            
            $empty.hide();
            
            var self = this;
            this.downloads.forEach(function(download) {
                var typeLabel = download.type ? (download.type.label || download.type.name || 'Other') : 'Other';
                var fileSize = EsDownloadsAdmin.formatFileSize(download.file_size);
                var icon = EsDownloadsAdmin.getFileIcon(download.file_extension);
                
                var itemHtml = '<div class="es-download-wizard-item" data-download-id="' + download.id + '">' +
                    '<div class="es-download-wizard-item__icon">' +
                        '<span class="dashicons ' + icon + '"></span>' +
                    '</div>' +
                    '<div class="es-download-wizard-item__info">' +
                        '<span class="es-download-wizard-item__title">' + self.escapeHtml(download.title) + '</span>' +
                        '<span class="es-download-wizard-item__meta">' + typeLabel + ' &bull; ' + fileSize + '</span>' +
                    '</div>' +
                    '<button type="button" class="es-download-wizard-remove" title="Remove">' +
                        '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>' +
                '</div>';
                
                $list.append(itemHtml);
            });
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    };
    
    // =========================================
    // BULK UPLOAD MODULE
    // =========================================
    var EsBulkUpload = {
        
        files: [],
        uploading: false,
        
        // Type detection by file extension
        typeMap: {
            // Presentations
            'ppt': 'presentation',
            'pptx': 'presentation',
            'key': 'presentation',
            'odp': 'presentation',
            // Documents/Handouts
            'pdf': 'handout',
            'doc': 'handout',
            'docx': 'handout',
            'odt': 'handout',
            'rtf': 'handout',
            'txt': 'handout',
            // Spreadsheets
            'xls': 'handout',
            'xlsx': 'handout',
            'csv': 'handout',
            // Videos
            'mp4': 'video',
            'webm': 'video',
            'mov': 'video',
            'avi': 'video',
            'mkv': 'video',
            // Images/Photos
            'jpg': 'photo',
            'jpeg': 'photo',
            'png': 'photo',
            'gif': 'photo',
            'webp': 'photo',
            'svg': 'photo',
            // Archives
            'zip': 'package',
            'rar': 'package',
            '7z': 'package',
            'tar': 'package',
            'gz': 'package'
        },
        
        /**
         * Initialize
         */
        init: function() {
            if (!$('#es-bulk-upload-modal').length) {
                return;
            }
            
            this.bindEvents();
            this.initSelect2();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            
            // Open modal
            $(document).on('click', '#es-bulk-upload', function(e) {
                e.preventDefault();
                self.openModal();
            });
            
            // Close modal
            $(document).on('click', '#es-bulk-upload-modal .es-modal-close, #es-bulk-upload-modal .es-modal-cancel', function() {
                self.closeModal();
            });
            
            $(document).on('click', '#es-bulk-upload-modal .es-modal-overlay', function() {
                if (!self.uploading) {
                    self.closeModal();
                }
            });
            
            // Select files button
            $(document).on('click', '#es-bulk-select-files', function() {
                $('#es-bulk-file-input').click();
            });
            
            // File input change
            $(document).on('change', '#es-bulk-file-input', function(e) {
                self.handleFiles(e.target.files);
            });
            
            // Drag & Drop
            var $dropzone = $('#es-bulk-dropzone');
            
            $dropzone.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('is-dragover');
            });
            
            $dropzone.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('is-dragover');
            });
            
            $dropzone.on('drop', function(e) {
                var files = e.originalEvent.dataTransfer.files;
                self.handleFiles(files);
            });
            
            // Remove file
            $(document).on('click', '.es-bulk-file-remove', function() {
                var index = $(this).closest('.es-bulk-file-item').data('index');
                self.removeFile(index);
            });
            
            // Type change
            $(document).on('change', '.es-bulk-file-type', function() {
                var index = $(this).closest('.es-bulk-file-item').data('index');
                var type = $(this).val();
                if (self.files[index]) {
                    self.files[index].type = type;
                }
            });
            
            // Title change
            $(document).on('input', '.es-bulk-file-title', function() {
                var index = $(this).closest('.es-bulk-file-item').data('index');
                var title = $(this).val();
                if (self.files[index]) {
                    self.files[index].title = title;
                }
            });
            
            // Submit
            $(document).on('click', '#es-bulk-upload-submit', function() {
                self.uploadAll();
            });
        },
        
        /**
         * Initialize Select2
         */
        initSelect2: function() {
            $('#es-bulk-events, #es-bulk-artists, #es-bulk-locations').each(function() {
                var $select = $(this);
                var postType = $select.data('post-type');
                var placeholder = $select.data('placeholder');
                
                $select.select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#es-bulk-upload-modal'),
                    minimumInputLength: 0,
                    ajax: {
                        url: es_downloads_admin.ajax_url,
                        dataType: 'json',
                        delay: 250,
                        type: 'POST',
                        data: function(params) {
                            return {
                                action: 'ensemble_search_posts',
                                nonce: es_downloads_admin.nonce,
                                q: params.term || '',
                                post_type: postType,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            
                            // Handle direct Select2 format response
                            if (data.results) {
                                return {
                                    results: data.results,
                                    pagination: {
                                        more: data.pagination && data.pagination.more
                                    }
                                };
                            }
                            
                            // Handle wp_send_json_success format
                            if (data.success && data.data) {
                                return {
                                    results: data.data.map(function(item) {
                                        return { id: item.id, text: item.text || item.title };
                                    })
                                };
                            }
                            
                            return { results: [] };
                        }
                    }
                });
            });
        },
        
        /**
         * Open modal
         */
        openModal: function() {
            this.resetModal();
            $('#es-bulk-upload-modal').fadeIn(200);
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            if (this.uploading) {
                if (!confirm('Upload in progress. Cancel?')) {
                    return;
                }
            }
            $('#es-bulk-upload-modal').fadeOut(200);
            this.resetModal();
        },
        
        /**
         * Reset modal
         */
        resetModal: function() {
            this.files = [];
            this.uploading = false;
            $('#es-bulk-file-input').val('');
            $('#es-bulk-files-list').empty();
            $('.es-bulk-step-files').hide();
            $('#es-bulk-progress').hide();
            $('#es-bulk-upload-submit').prop('disabled', true);
            $('#es-bulk-events, #es-bulk-artists, #es-bulk-locations').val(null).trigger('change');
        },
        
        /**
         * Handle files
         */
        handleFiles: function(fileList) {
            var self = this;
            
            for (var i = 0; i < fileList.length; i++) {
                var file = fileList[i];
                var ext = file.name.split('.').pop().toLowerCase();
                var title = file.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
                
                // Capitalize first letter
                title = title.charAt(0).toUpperCase() + title.slice(1);
                
                self.files.push({
                    file: file,
                    title: title,
                    type: self.typeMap[ext] || 'other',
                    extension: ext
                });
            }
            
            this.renderFiles();
        },
        
        /**
         * Remove file
         */
        removeFile: function(index) {
            this.files.splice(index, 1);
            this.renderFiles();
        },
        
        /**
         * Render files list
         */
        renderFiles: function() {
            var self = this;
            var $list = $('#es-bulk-files-list');
            $list.empty();
            
            if (this.files.length === 0) {
                $('.es-bulk-step-files').hide();
                $('#es-bulk-upload-submit').prop('disabled', true);
                return;
            }
            
            $('.es-bulk-step-files').show();
            $('#es-bulk-upload-submit').prop('disabled', false);
            
            this.files.forEach(function(fileData, index) {
                var icon = EsDownloadsAdmin.getFileIcon(fileData.extension);
                var size = EsDownloadsAdmin.formatFileSize(fileData.file.size);
                
                var html = '<div class="es-bulk-file-item" data-index="' + index + '">' +
                    '<div class="es-bulk-file-info">' +
                        '<span class="dashicons ' + icon + '"></span>' +
                        '<span class="es-bulk-file-name">' + self.escapeHtml(fileData.file.name) + '</span>' +
                        '<span class="es-bulk-file-size">' + size + '</span>' +
                    '</div>' +
                    '<input type="text" class="es-bulk-file-title" value="' + self.escapeHtml(fileData.title) + '" placeholder="Title">' +
                    '<select class="es-bulk-file-type">' +
                        self.getTypeOptions(fileData.type) +
                    '</select>' +
                    '<button type="button" class="es-bulk-file-remove button-link">' +
                        '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>' +
                '</div>';
                
                $list.append(html);
            });
        },
        
        /**
         * Get type options HTML
         */
        getTypeOptions: function(selectedType) {
            var types = es_downloads_admin.download_types || {};
            var html = '';
            
            for (var slug in types) {
                var selected = (slug === selectedType) ? ' selected' : '';
                html += '<option value="' + slug + '"' + selected + '>' + types[slug].label + '</option>';
            }
            
            return html;
        },
        
        /**
         * Upload all files
         */
        uploadAll: function() {
            var self = this;
            
            if (this.files.length === 0 || this.uploading) {
                return;
            }
            
            this.uploading = true;
            
            // Get assignments
            var events = $('#es-bulk-events').val() || [];
            var artists = $('#es-bulk-artists').val() || [];
            var locations = $('#es-bulk-locations').val() || [];
            
            // Show progress
            $('#es-bulk-progress').show();
            $('#es-bulk-upload-submit').prop('disabled', true);
            
            var total = this.files.length;
            var completed = 0;
            var errors = [];
            
            // Upload files sequentially using our own AJAX handler
            function uploadNext() {
                if (completed >= total) {
                    // All done
                    self.uploading = false;
                    
                    if (errors.length > 0) {
                        alert('Upload completed with ' + errors.length + ' error(s):\n' + errors.join('\n'));
                    } else {
                        EsDownloadsAdmin.showNotice('All ' + total + ' files uploaded successfully!', 'success');
                    }
                    
                    self.closeModal();
                    EsDownloadsAdmin.loadDownloads();
                    return;
                }
                
                var fileData = self.files[completed];
                
                // Update progress
                var percent = Math.round(((completed + 1) / total) * 100);
                $('.es-progress-fill').css('width', percent + '%');
                $('#es-bulk-progress-count').text((completed + 1) + '/' + total);
                
                // Create FormData with file and metadata
                var formData = new FormData();
                formData.append('action', 'es_bulk_upload_file');
                formData.append('nonce', es_downloads_admin.nonce);
                formData.append('file', fileData.file);
                formData.append('title', fileData.title);
                formData.append('type', fileData.type);
                formData.append('events', JSON.stringify(events));
                formData.append('artists', JSON.stringify(artists));
                formData.append('locations', JSON.stringify(locations));
                
                $.ajax({
                    url: es_downloads_admin.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            completed++;
                            uploadNext();
                        } else {
                            errors.push(fileData.file.name + ': ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                            completed++;
                            uploadNext();
                        }
                    },
                    error: function(xhr) {
                        errors.push(fileData.file.name + ': Upload failed');
                        completed++;
                        uploadNext();
                    }
                });
            }
            
            uploadNext();
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    };
    
    // =========================================
    // BULK DOWNLOAD (ZIP) MODULE
    // =========================================
    var EsBulkDownload = {
        
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
            var self = this;
            
            // Bulk download button
            $(document).on('click', '#es-bulk-download', function(e) {
                e.preventDefault();
                self.downloadZip();
            });
        },
        
        /**
         * Update button visibility
         */
        updateButtonVisibility: function(count) {
            if (count > 0) {
                $('#es-bulk-download').show();
            } else {
                $('#es-bulk-download').hide();
            }
        },
        
        /**
         * Download ZIP
         */
        downloadZip: function() {
            var params = new URLSearchParams();
            params.append('action', 'es_bulk_download_zip');
            params.append('nonce', es_downloads_admin.nonce);
            
            // Add current filters
            var search = $('#es-download-search').val();
            var type = $('#es-download-type-filter').val();
            
            if (search) params.append('search', search);
            if (type) params.append('type', type);
            
            // Open download in new window/tab
            window.location.href = es_downloads_admin.ajax_url + '?' + params.toString();
        }
    };
    
    // Initialize on DOM ready
    $(document).ready(function() {
        EsDownloadsAdmin.init();
        EsDownloadsWizard.init();
        EsDownloadsArtist.init();
        EsDownloadsLocation.init();
        EsBulkUpload.init();
        EsBulkDownload.init();
    });
    
})(jQuery);
