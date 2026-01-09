/**
 * Visual Calendar Pro - JavaScript
 * 
 * Handles month navigation and interactivity
 * 
 * @package Ensemble
 * @subpackage Addons/VisualCalendar
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Visual Calendar Controller
     */
    class VisualCalendar {
        constructor(element) {
            this.$el = $(element);
            this.$grid = this.$el.find('.es-vc-grid');
            this.$title = this.$el.find('.es-vc-title');
            this.$prevBtn = this.$el.find('.es-vc-prev');
            this.$nextBtn = this.$el.find('.es-vc-next');
            
            this.year = parseInt(this.$el.data('year'));
            this.month = parseInt(this.$el.data('month'));
            this.category = this.$el.data('category') || '';
            this.location = this.$el.data('location') || '';
            
            this.isLoading = false;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
        }
        
        bindEvents() {
            // Navigation buttons
            this.$prevBtn.on('click', () => this.navigate(-1));
            this.$nextBtn.on('click', () => this.navigate(1));
            
            // Keyboard navigation
            this.$el.on('keydown', (e) => {
                if (e.key === 'ArrowLeft') this.navigate(-1);
                if (e.key === 'ArrowRight') this.navigate(1);
            });
            
            // Touch swipe support
            this.initTouchSwipe();
            
            // Multiple events cell click (mobile)
            this.$el.on('click', '.es-vc-cell.es-vc-multiple', (e) => {
                if (window.innerWidth <= 768) {
                    this.handleMultipleEventsClick(e);
                }
            });
        }
        
        /**
         * Navigate to previous/next month
         */
        navigate(direction) {
            if (this.isLoading) return;
            
            this.month += direction;
            
            if (this.month > 12) {
                this.month = 1;
                this.year++;
            } else if (this.month < 1) {
                this.month = 12;
                this.year--;
            }
            
            this.loadMonth();
        }
        
        /**
         * Load month via AJAX
         */
        loadMonth() {
            this.isLoading = true;
            this.$el.addClass('is-loading');
            this.$grid.attr('data-loading', 'true');
            
            $.ajax({
                url: esVisualCalendar.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_visual_calendar_events',
                    nonce: esVisualCalendar.nonce,
                    year: this.year,
                    month: this.month,
                    category: this.category,
                    location: this.location
                },
                success: (response) => {
                    if (response.success) {
                        this.$grid.html(response.data.html);
                        this.$title.text(response.data.month_name);
                        this.$el.data('year', response.data.year);
                        this.$el.data('month', response.data.month);
                        
                        // Trigger event for external use
                        this.$el.trigger('es:calendar:monthChanged', {
                            year: response.data.year,
                            month: response.data.month
                        });
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Visual Calendar Error:', error);
                },
                complete: () => {
                    this.isLoading = false;
                    this.$el.removeClass('is-loading');
                    this.$grid.attr('data-loading', 'false');
                }
            });
        }
        
        /**
         * Initialize touch swipe
         */
        initTouchSwipe() {
            let touchStartX = 0;
            let touchEndX = 0;
            const threshold = 50;
            
            this.$grid.on('touchstart', (e) => {
                touchStartX = e.originalEvent.changedTouches[0].screenX;
            });
            
            this.$grid.on('touchend', (e) => {
                touchEndX = e.originalEvent.changedTouches[0].screenX;
                const diff = touchStartX - touchEndX;
                
                if (Math.abs(diff) > threshold) {
                    if (diff > 0) {
                        // Swipe left - next month
                        this.navigate(1);
                    } else {
                        // Swipe right - previous month
                        this.navigate(-1);
                    }
                }
            });
        }
        
        /**
         * Handle click on multiple events cell (mobile)
         */
        handleMultipleEventsClick(e) {
            const $cell = $(e.currentTarget);
            const date = $cell.data('date');
            
            // Toggle active state
            if ($cell.hasClass('es-vc-expanded')) {
                $cell.removeClass('es-vc-expanded');
            } else {
                // Close others
                this.$el.find('.es-vc-cell.es-vc-expanded').removeClass('es-vc-expanded');
                $cell.addClass('es-vc-expanded');
            }
        }
        
        /**
         * Go to specific month
         */
        goToMonth(year, month) {
            this.year = year;
            this.month = month;
            this.loadMonth();
        }
        
        /**
         * Go to today
         */
        goToToday() {
            const today = new Date();
            this.goToMonth(today.getFullYear(), today.getMonth() + 1);
        }
        
        /**
         * Refresh current view
         */
        refresh() {
            this.loadMonth();
        }
    }
    
    /**
     * Initialize all Visual Calendars on page
     */
    function initVisualCalendars() {
        $('.es-visual-calendar').each(function() {
            if (!$(this).data('vcInstance')) {
                const instance = new VisualCalendar(this);
                $(this).data('vcInstance', instance);
            }
        });
    }
    
    // Initialize on DOM ready
    $(document).ready(initVisualCalendars);
    
    // Re-initialize on AJAX content load (for page builders)
    $(document).on('ajaxComplete', function() {
        setTimeout(initVisualCalendars, 100);
    });
    
    // Expose to global scope for external use
    window.ESVisualCalendar = VisualCalendar;
    
})(jQuery);
