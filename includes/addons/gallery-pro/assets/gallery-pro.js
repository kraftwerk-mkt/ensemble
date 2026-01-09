/**
 * Ensemble Gallery Pro - JavaScript
 * 
 * @package Ensemble
 * @subpackage Addons/Gallery Pro
 */

(function($) {
    'use strict';
    
    /**
     * Gallery Pro Controller
     */
    var EnsembleGalleryPro = {
        
        /**
         * Configuration from localized script
         */
        config: window.ensembleGalleryPro || {},
        
        /**
         * GLightbox instances
         */
        lightboxInstances: {},
        
        /**
         * Initialize
         */
        init: function() {
            this.initLightboxes();
            this.initMasonryLayout();
            this.initJustifiedLayout();
            this.bindEvents();
        },
        
        /**
         * Initialize all lightboxes
         */
        initLightboxes: function() {
            var self = this;
            
            // Find all galleries with lightbox
            $('.es-gallery').each(function() {
                var $gallery = $(this);
                var galleryId = $gallery.attr('id');
                
                if (!galleryId) return;
                
                // Get lightbox elements for this gallery
                var selector = '#' + galleryId + ' .glightbox';
                
                if ($(selector).length === 0) return;
                
                // Initialize GLightbox
                self.lightboxInstances[galleryId] = GLightbox({
                    selector: selector,
                    touchNavigation: true,
                    loop: self.config.lightbox?.loop !== false,
                    autoplayVideos: self.config.lightbox?.autoplay || false,
                    slideEffect: self.config.lightbox?.slideEffect || 'zoom',
                    closeEffect: 'fade',
                    cssEfects: {
                        fade: { in: 'fadeIn', out: 'fadeOut' },
                        zoom: { in: 'zoomIn', out: 'zoomOut' },
                        slide: { in: 'slideInRight', out: 'slideOutLeft' }
                    },
                    skin: 'glightbox-' + (self.config.lightbox?.theme || 'dark'),
                    moreText: self.config.strings?.more || 'Mehr',
                    moreLength: 60,
                    closeButton: true,
                    touchFollowAxis: true,
                    keyboardNavigation: true,
                    draggable: true,
                    zoomable: true,
                    preload: true,
                    svg: {
                        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
                        next: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>',
                        prev: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>'
                    },
                    plyr: {
                        css: 'https://cdn.plyr.io/3.7.8/plyr.css',
                        js: 'https://cdn.plyr.io/3.7.8/plyr.polyfilled.js',
                        config: {
                            ratio: '16:9',
                            muted: false,
                            hideControls: true,
                            youtube: {
                                noCookie: true,
                                rel: 0,
                                showinfo: 0,
                                iv_load_policy: 3
                            },
                            vimeo: {
                                byline: false,
                                portrait: false,
                                title: false,
                                speed: true,
                                transparent: false
                            }
                        }
                    }
                });
            });
        },
        
        /**
         * Initialize Masonry layout (using CSS columns, but we can add JS enhancements)
         */
        initMasonryLayout: function() {
            var self = this;
            
            $('.es-gallery-masonry').each(function() {
                var $gallery = $(this);
                
                // Lazy load images and reflow on load
                $gallery.find('img').each(function() {
                    var $img = $(this);
                    
                    if ($img[0].complete) {
                        $img.addClass('loaded');
                    } else {
                        $img.on('load', function() {
                            $(this).addClass('loaded');
                        });
                    }
                });
            });
        },
        
        /**
         * Initialize Justified layout
         */
        initJustifiedLayout: function() {
            var self = this;
            
            $('.es-gallery-justified').each(function() {
                var $gallery = $(this);
                
                // Calculate and set proper flex values based on loaded images
                $gallery.find('.es-gallery-justified-item img').each(function() {
                    var $img = $(this);
                    var $item = $img.closest('.es-gallery-justified-item');
                    
                    function setFlexGrow() {
                        var naturalWidth = this.naturalWidth || 400;
                        var naturalHeight = this.naturalHeight || 300;
                        var ratio = naturalWidth / Math.max(naturalHeight, 1);
                        var flexGrow = Math.max(1, Math.min(ratio * 100, 300));
                        
                        $item.css('flex-grow', flexGrow);
                        
                        // Update padding-bottom for aspect ratio
                        $item.find('i').css('padding-bottom', ((1 / ratio) * 100) + '%');
                    }
                    
                    if ($img[0].complete && $img[0].naturalWidth) {
                        setFlexGrow.call($img[0]);
                    } else {
                        $img.on('load', setFlexGrow);
                    }
                });
            });
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            
            // Keyboard navigation for galleries
            $(document).on('keydown', function(e) {
                // Only if a gallery is focused or hovered
                var $activeGallery = $('.es-gallery:hover, .es-gallery:focus-within').first();
                
                if ($activeGallery.length === 0) return;
                
                var galleryId = $activeGallery.attr('id');
                var lightbox = self.lightboxInstances[galleryId];
                
                if (!lightbox) return;
                
                // Enter key opens lightbox on first image
                if (e.key === 'Enter' && !$('body').hasClass('glightbox-open')) {
                    var $firstLink = $activeGallery.find('.glightbox').first();
                    if ($firstLink.length) {
                        lightbox.openAt(0);
                        e.preventDefault();
                    }
                }
            });
            
            // Fullscreen button
            $(document).on('click', '.es-gallery-fullscreen', function(e) {
                e.preventDefault();
                var $gallery = $(this).closest('.es-gallery');
                self.toggleFullscreen($gallery[0]);
            });
            
            // Window resize - recalculate layouts
            var resizeTimeout;
            $(window).on('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    self.initJustifiedLayout();
                }, 250);
            });
        },
        
        /**
         * Toggle fullscreen mode
         * 
         * @param {HTMLElement} element
         */
        toggleFullscreen: function(element) {
            if (!document.fullscreenElement && 
                !document.webkitFullscreenElement && 
                !document.mozFullScreenElement && 
                !document.msFullscreenElement) {
                
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                } else if (element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        },
        
        /**
         * Refresh lightbox instance
         * 
         * @param {string} galleryId
         */
        refreshLightbox: function(galleryId) {
            if (this.lightboxInstances[galleryId]) {
                this.lightboxInstances[galleryId].reload();
            }
        },
        
        /**
         * Open lightbox at specific index
         * 
         * @param {string} galleryId
         * @param {number} index
         */
        openLightbox: function(galleryId, index) {
            if (this.lightboxInstances[galleryId]) {
                this.lightboxInstances[galleryId].openAt(index || 0);
            }
        },
        
        /**
         * Close all lightboxes
         */
        closeAllLightboxes: function() {
            Object.keys(this.lightboxInstances).forEach(function(key) {
                this.lightboxInstances[key].close();
            }, this);
        },
        
        /**
         * Lazy load images
         * 
         * @param {jQuery} $container
         */
        lazyLoadImages: function($container) {
            var self = this;
            
            $container.find('img[loading="lazy"]').each(function() {
                var $img = $(this);
                
                if (self.isElementInViewport($img[0])) {
                    // Browser handles lazy loading, but we can add a class
                    $img.addClass('es-lazy-loaded');
                }
            });
        },
        
        /**
         * Check if element is in viewport
         * 
         * @param {HTMLElement} el
         * @return {boolean}
         */
        isElementInViewport: function(el) {
            var rect = el.getBoundingClientRect();
            
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }
    };
    
    /**
     * Carousel initialization (separate from main init to allow Swiper to load)
     */
    function initCarousels() {
        if (typeof Swiper === 'undefined') return;
        
        // Carousels are initialized inline in the template
        // This function can be used for additional setup
        
        $('.es-gallery-carousel').each(function() {
            var $gallery = $(this);
            
            // Stop autoplay on hover
            var $swiper = $gallery.find('.es-gallery-swiper-main');
            if ($swiper.length && $swiper[0].swiper) {
                $swiper.on('mouseenter', function() {
                    if ($swiper[0].swiper.autoplay) {
                        $swiper[0].swiper.autoplay.stop();
                    }
                });
                
                $swiper.on('mouseleave', function() {
                    if ($swiper[0].swiper.autoplay && EnsembleGalleryPro.config.carousel?.autoplay) {
                        $swiper[0].swiper.autoplay.start();
                    }
                });
            }
        });
    }
    
    /**
     * Document ready
     */
    $(document).ready(function() {
        EnsembleGalleryPro.init();
        
        // Initialize carousels after a short delay to ensure Swiper is loaded
        setTimeout(initCarousels, 100);
    });
    
    /**
     * Expose to global scope
     */
    window.EnsembleGalleryPro = EnsembleGalleryPro;
    
})(jQuery);
