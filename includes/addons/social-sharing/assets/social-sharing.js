/**
 * Social Sharing Addon - JavaScript
 * 
 * @package Ensemble
 * @subpackage Addons/SocialSharing
 */

(function($) {
    'use strict';
    
    const ESSocialSharing = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindCopyLink();
            this.bindNativeShare();
            this.checkNativeShareSupport();
        },
        
        /**
         * Bind copy link button
         */
        bindCopyLink: function() {
            $(document).on('click', '.es-share-copy', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const url = $btn.data('url');
                
                ESSocialSharing.copyToClipboard(url, $btn);
            });
        },
        
        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(text, $btn) {
            // Modern Clipboard API
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    ESSocialSharing.showCopySuccess($btn);
                }).catch(function() {
                    ESSocialSharing.fallbackCopy(text, $btn);
                });
            } else {
                // Fallback for older browsers
                ESSocialSharing.fallbackCopy(text, $btn);
            }
        },
        
        /**
         * Fallback copy method
         */
        fallbackCopy: function(text, $btn) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                ESSocialSharing.showCopySuccess($btn);
            } catch (err) {
                console.error('Copy failed:', err);
                alert(esSocialSharing.copyError || 'Kopieren fehlgeschlagen');
            }
            
            document.body.removeChild(textarea);
        },
        
        /**
         * Show copy success feedback
         */
        showCopySuccess: function($btn) {
            const $iconCopy = $btn.find('.es-share-icon-copy');
            const $iconCheck = $btn.find('.es-share-icon-check');
            
            // Switch icons
            $iconCopy.hide();
            $iconCheck.show();
            
            // Add success class
            $btn.addClass('copied');
            
            // Update title temporarily
            const originalTitle = $btn.attr('title');
            $btn.attr('title', esSocialSharing.copied || 'Link kopiert!');
            
            // Reset after delay
            setTimeout(function() {
                $iconCheck.hide();
                $iconCopy.show();
                $btn.removeClass('copied');
                $btn.attr('title', originalTitle);
            }, 2000);
        },
        
        /**
         * Bind native share button
         */
        bindNativeShare: function() {
            $(document).on('click', '.es-share-native', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const shareData = {
                    title: $btn.data('title'),
                    text: $btn.data('text'),
                    url: $btn.data('url')
                };
                
                ESSocialSharing.triggerNativeShare(shareData);
            });
        },
        
        /**
         * Trigger native Web Share API
         */
        triggerNativeShare: function(shareData) {
            if (navigator.share) {
                navigator.share(shareData).then(function() {
                    console.log('Shared successfully');
                }).catch(function(err) {
                    // User cancelled or error
                    if (err.name !== 'AbortError') {
                        console.error('Share failed:', err);
                    }
                });
            }
        },
        
        /**
         * Check if Web Share API is supported
         */
        checkNativeShareSupport: function() {
            if (navigator.share) {
                // Show native share button
                $('.es-share-native').show();
            } else {
                // Hide native share button on desktop
                $('.es-share-native').hide();
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        ESSocialSharing.init();
    });
    
})(jQuery);
