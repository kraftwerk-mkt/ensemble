/**
 * Ensemble Downloads - Frontend JavaScript
 * 
 * @package Ensemble
 * @subpackage Addons/Downloads
 */

(function($) {
    'use strict';
    
    var EsDownloads = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initAccordion();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Track downloads
            $(document).on('click', '[data-download-id]', this.handleDownloadClick);
            
            // Login required
            $(document).on('click', '[data-require-login="true"]', this.handleLoginRequired);
        },
        
        /**
         * Initialize Tabs
         */
        initTabs: function() {
            $('.es-downloads-tabs__tab').on('click', function() {
                var $tab = $(this);
                var $container = $tab.closest('.es-downloads-tabs');
                var tabId = $tab.data('tab');
                
                // Update tabs
                $container.find('.es-downloads-tabs__tab').removeClass('is-active').attr('aria-selected', 'false');
                $tab.addClass('is-active').attr('aria-selected', 'true');
                
                // Update panels
                $container.find('.es-downloads-tabs__panel').removeClass('is-active').attr('hidden', true);
                $('#tab-panel-' + tabId).addClass('is-active').removeAttr('hidden');
            });
            
            // Keyboard navigation for tabs
            $('.es-downloads-tabs__nav').on('keydown', '.es-downloads-tabs__tab', function(e) {
                var $tabs = $(this).closest('.es-downloads-tabs__nav').find('.es-downloads-tabs__tab');
                var index = $tabs.index(this);
                var newIndex;
                
                switch (e.key) {
                    case 'ArrowLeft':
                        newIndex = index > 0 ? index - 1 : $tabs.length - 1;
                        break;
                    case 'ArrowRight':
                        newIndex = index < $tabs.length - 1 ? index + 1 : 0;
                        break;
                    case 'Home':
                        newIndex = 0;
                        break;
                    case 'End':
                        newIndex = $tabs.length - 1;
                        break;
                    default:
                        return;
                }
                
                e.preventDefault();
                $tabs.eq(newIndex).focus().click();
            });
        },
        
        /**
         * Initialize Accordion
         */
        initAccordion: function() {
            $('.es-downloads-accordion__header').on('click', function() {
                var $header = $(this);
                var $item = $header.closest('.es-downloads-accordion__item');
                var $panel = $item.find('.es-downloads-accordion__panel');
                var isOpen = $item.hasClass('is-open');
                
                // Toggle this item
                $item.toggleClass('is-open');
                $header.attr('aria-expanded', !isOpen);
                
                if (isOpen) {
                    $panel.attr('hidden', true);
                } else {
                    $panel.removeAttr('hidden');
                }
            });
            
            // Keyboard support
            $('.es-downloads-accordion__header').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
        },
        
        /**
         * Handle download click
         */
        handleDownloadClick: function(e) {
            var $link = $(this);
            var downloadId = $link.data('download-id');
            
            // Check if login required
            if ($link.data('require-login') && !es_downloads.is_logged_in) {
                e.preventDefault();
                EsDownloads.showLoginMessage($link);
                return false;
            }
            
            // Track download
            if (es_downloads.track_enabled && downloadId) {
                EsDownloads.trackDownload(downloadId);
            }
        },
        
        /**
         * Handle login required click
         */
        handleLoginRequired: function(e) {
            if (!es_downloads.is_logged_in) {
                e.preventDefault();
                EsDownloads.showLoginMessage($(this));
                return false;
            }
        },
        
        /**
         * Show login message
         */
        showLoginMessage: function($element) {
            // Remove existing messages
            $('.es-download-login-message').remove();
            
            var $message = $('<div class="es-download-login-message">' +
                '<span class="es-download-login-message__text">' + es_downloads.i18n.login_required + '</span>' +
                '<a href="' + es_downloads.login_url + '" class="es-download-login-message__link">Login</a>' +
                '</div>');
            
            // Position message
            var offset = $element.offset();
            $message.css({
                position: 'absolute',
                top: offset.top - 10,
                left: offset.left,
                transform: 'translateY(-100%)'
            });
            
            $('body').append($message);
            
            // Animate in
            $message.hide().fadeIn(200);
            
            // Auto remove after 5 seconds
            setTimeout(function() {
                $message.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Remove on click elsewhere
            $(document).one('click', function() {
                $message.fadeOut(200, function() {
                    $(this).remove();
                });
            });
        },
        
        /**
         * Track download via AJAX
         */
        trackDownload: function(downloadId) {
            $.ajax({
                url: es_downloads.ajax_url,
                type: 'POST',
                data: {
                    action: 'es_track_download',
                    nonce: es_downloads.nonce,
                    download_id: downloadId
                }
            });
        }
    };
    
    // Initialize on DOM ready
    $(document).ready(function() {
        EsDownloads.init();
    });
    
    // Add CSS for login message
    var style = document.createElement('style');
    style.textContent = '\
        .es-download-login-message {\
            background: #1e1e1e;\
            color: #fff;\
            padding: 10px 16px;\
            border-radius: 6px;\
            font-size: 13px;\
            display: flex;\
            align-items: center;\
            gap: 12px;\
            z-index: 10000;\
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);\
        }\
        .es-download-login-message::after {\
            content: "";\
            position: absolute;\
            bottom: -6px;\
            left: 20px;\
            border-left: 6px solid transparent;\
            border-right: 6px solid transparent;\
            border-top: 6px solid #1e1e1e;\
        }\
        .es-download-login-message__link {\
            color: #fff;\
            background: rgba(255,255,255,0.2);\
            padding: 4px 12px;\
            border-radius: 4px;\
            text-decoration: none;\
            font-weight: 500;\
        }\
        .es-download-login-message__link:hover {\
            background: rgba(255,255,255,0.3);\
            color: #fff;\
        }\
    ';
    document.head.appendChild(style);
    
})(jQuery);
