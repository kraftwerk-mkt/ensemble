/**
 * Gallery Pro - Frontend JavaScript
 * 
 * Handles lightbox, carousel, and filmstrip functionality
 * Supports images and videos (YouTube, Vimeo, Self-hosted)
 * 
 * @package Ensemble
 * @subpackage Addons/Gallery Pro
 * @since 3.0.0
 */

(function($) {
    'use strict';

    /**
     * Gallery Pro Module
     */
    const EnsembleGalleryPro = {
        
        /**
         * Lightbox instances
         */
        lightboxes: {},
        
        /**
         * Swiper instances
         */
        swipers: {},
        
        /**
         * Settings
         */
        settings: {},
        
        /**
         * Initialize
         */
        init: function() {
            this.settings = window.ensembleGalleryPro || {};
            
            this.initLightboxes();
            this.initCarousels();
            this.initFilmstrips();
            this.initMasonry();
        },
        
        /**
         * Initialize GLightbox instances
         */
        initLightboxes: function() {
            const self = this;
            const lightboxSettings = this.settings.lightbox || {};
            
            // Find all galleries
            $('.es-gallery').each(function() {
                const $gallery = $(this);
                const galleryId = $gallery.attr('id');
                
                if (!galleryId) return;
                
                // Check if gallery has lightbox links
                const $links = $gallery.find('.glightbox');
                if ($links.length === 0) return;
                
                // Initialize GLightbox
                self.lightboxes[galleryId] = GLightbox({
                    selector: '#' + galleryId + ' .glightbox',
                    touchNavigation: true,
                    loop: lightboxSettings.loop !== false,
                    autoplayVideos: lightboxSettings.autoplay === true,
                    openEffect: lightboxSettings.slideEffect || 'zoom',
                    closeEffect: 'fade',
                    cssEfects: {
                        fade: { in: 'fadeIn', out: 'fadeOut' },
                        zoom: { in: 'zoomIn', out: 'zoomOut' },
                        slide: { in: 'slideInRight', out: 'slideOutLeft' }
                    },
                    plyr: {
                        css: 'https://cdn.plyr.io/3.7.8/plyr.css',
                        js: 'https://cdn.plyr.io/3.7.8/plyr.js',
                        config: {
                            ratio: '16:9',
                            youtube: {
                                noCookie: true,
                                rel: 0,
                                showinfo: 0
                            },
                            vimeo: {
                                byline: false,
                                portrait: false,
                                title: false,
                                transparent: false
                            }
                        }
                    },
                    // Custom video source handler for local videos
                    videosWidth: '900px'
                });
            });
        },
        
        /**
         * Initialize Swiper carousels
         */
        initCarousels: function() {
            const self = this;
            const carouselSettings = this.settings.carousel || {};
            
            $('.es-gallery-carousel').each(function() {
                const $gallery = $(this);
                const galleryId = $gallery.attr('id');
                const $swiper = $gallery.find('.es-gallery-swiper');
                
                if ($swiper.length === 0) return;
                
                self.swipers[galleryId] = new Swiper($swiper[0], {
                    slidesPerView: 1,
                    spaceBetween: 16,
                    loop: carouselSettings.loop !== false,
                    autoplay: carouselSettings.autoplay ? {
                        delay: carouselSettings.delay || 5000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true
                    } : false,
                    navigation: {
                        nextEl: $gallery.find('.swiper-button-next')[0],
                        prevEl: $gallery.find('.swiper-button-prev')[0]
                    },
                    pagination: {
                        el: $gallery.find('.swiper-pagination')[0],
                        clickable: true
                    },
                    keyboard: {
                        enabled: true
                    },
                    // Pause autoplay when video is playing
                    on: {
                        slideChangeTransitionEnd: function() {
                            // Pause any playing videos when slide changes
                            const $videos = $gallery.find('video');
                            $videos.each(function() {
                                this.pause();
                            });
                        }
                    }
                });
            });
        },
        
        /**
         * Initialize filmstrip navigation
         */
        initFilmstrips: function() {
            $('.es-gallery-filmstrip').each(function() {
                const $gallery = $(this);
                const $track = $gallery.find('.es-filmstrip-items');
                const $prev = $gallery.find('.es-filmstrip-prev');
                const $next = $gallery.find('.es-filmstrip-next');
                
                const scrollAmount = 200;
                
                $prev.on('click', function() {
                    $track.animate({
                        scrollLeft: $track.scrollLeft() - scrollAmount
                    }, 300);
                });
                
                $next.on('click', function() {
                    $track.animate({
                        scrollLeft: $track.scrollLeft() + scrollAmount
                    }, 300);
                });
                
                // Update button states
                function updateNavButtons() {
                    const scrollLeft = $track.scrollLeft();
                    const maxScroll = $track[0].scrollWidth - $track.width();
                    
                    $prev.prop('disabled', scrollLeft <= 0);
                    $next.prop('disabled', scrollLeft >= maxScroll - 1);
                }
                
                $track.on('scroll', updateNavButtons);
                $(window).on('resize', updateNavButtons);
                updateNavButtons();
            });
        },
        
        /**
         * Initialize masonry layout
         * Uses CSS columns, but we handle image loading for smooth layout
         */
        initMasonry: function() {
            $('.es-gallery-masonry').each(function() {
                const $gallery = $(this);
                const $items = $gallery.find('.es-gallery-image');
                
                // Wait for images to load for proper layout
                let loadedCount = 0;
                const totalCount = $items.length;
                
                if (totalCount === 0) return;
                
                $items.each(function() {
                    const img = this;
                    
                    if (img.complete) {
                        loadedCount++;
                        checkAllLoaded();
                    } else {
                        $(img).on('load error', function() {
                            loadedCount++;
                            checkAllLoaded();
                        });
                    }
                });
                
                function checkAllLoaded() {
                    if (loadedCount >= totalCount) {
                        $gallery.addClass('es-masonry-loaded');
                    }
                }
            });
        },
        
        /**
         * Refresh a specific gallery
         * Useful after dynamic content updates
         */
        refresh: function(galleryId) {
            // Refresh lightbox
            if (this.lightboxes[galleryId]) {
                this.lightboxes[galleryId].reload();
            }
            
            // Refresh swiper
            if (this.swipers[galleryId]) {
                this.swipers[galleryId].update();
            }
        },
        
        /**
         * Destroy a specific gallery
         */
        destroy: function(galleryId) {
            // Destroy lightbox
            if (this.lightboxes[galleryId]) {
                this.lightboxes[galleryId].destroy();
                delete this.lightboxes[galleryId];
            }
            
            // Destroy swiper
            if (this.swipers[galleryId]) {
                this.swipers[galleryId].destroy();
                delete this.swipers[galleryId];
            }
        }
    };
    
    /**
     * Initialize on DOM ready
     */
    $(document).ready(function() {
        EnsembleGalleryPro.init();
    });
    
    /**
     * Re-initialize after AJAX content loads
     */
    $(document).on('ajaxComplete', function() {
        // Small delay to ensure DOM is updated
        setTimeout(function() {
            EnsembleGalleryPro.init();
        }, 100);
    });
    
    /**
     * Expose to global scope
     */
    window.EnsembleGalleryPro = EnsembleGalleryPro;

})(jQuery);
