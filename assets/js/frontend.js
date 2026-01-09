/**
 * Ensemble Frontend JavaScript
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    var EnsembleFrontend = {
        
        init: function() {
            this.setupEventCards();
            this.setupGridFilters();
            this.setupSearch();
        },
        
        /**
         * Setup Event Cards - clickable
         */
        setupEventCards: function() {
            // ALL templates use .ensemble-event-card as base class
            $('.ensemble-event-card').on('click', function(e) {
                if (!$(e.target).is('a') && !$(e.target).closest('a').length) {
                    var link = $(this).find('a').first().attr('href');
                    if (link) {
                        window.location.href = link;
                    }
                }
            });
        },
        
        /**
         * Setup Grid Filters
         */
        setupGridFilters: function() {
            var self = this;
            
            // Filter change handler
            $(document).on('change', '.ensemble-filter', function() {
                var $wrapper = $(this).closest('.ensemble-events-grid-wrapper');
                self.applyFilters($wrapper);
            });
        },
        
        /**
         * Setup Search
         */
        setupSearch: function() {
            var self = this;
            var searchTimeout;
            
            $(document).on('input', '.ensemble-search-input', function() {
                var $input = $(this);
                var $wrapper = $input.closest('.ensemble-events-grid-wrapper');
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.applyFilters($wrapper);
                }, 300);
            });
        },
        
        /**
         * Apply all filters to a grid
         */
        applyFilters: function($wrapper) {
            var $grid = $wrapper.find('.ensemble-events-grid');
            // ALL templates use .ensemble-event-card as base class
            var $cards = $grid.find('.ensemble-event-card');
            var $searchInput = $wrapper.find('.ensemble-search-input');
            
            // Get filter values
            var timeFilter = $wrapper.find('.ensemble-filter[data-filter="time"]').val() || '';
            var categoryFilter = $wrapper.find('.ensemble-filter[data-filter="category"]').val() || '';
            var artistFilter = $wrapper.find('.ensemble-filter[data-filter="artist"]').val() || '';
            var locationFilter = $wrapper.find('.ensemble-filter[data-filter="location"]').val() || '';
            var searchTerm = ($searchInput.val() || '').toLowerCase().trim();
            
            // If all filters empty, show all
            if (!timeFilter && !categoryFilter && !artistFilter && !locationFilter && !searchTerm) {
                $cards.show();
                $wrapper.find('.ensemble-filter-no-results').remove();
                return;
            }
            
            var visibleCount = 0;
            
            $cards.each(function() {
                var $card = $(this);
                var show = true;
                
                // Time filter
                if (timeFilter && timeFilter !== 'all') {
                    var dateStr = $card.attr('data-date') || '';
                    if (dateStr) {
                        show = show && EnsembleFrontend.checkTimeFilter(dateStr, timeFilter);
                    }
                }
                
                // Category filter (by ID)
                if (show && categoryFilter) {
                    var cardCategoryId = $card.attr('data-category-id') || '';
                    show = show && (cardCategoryId == categoryFilter);
                }
                
                // Artist filter (by ID) - support multiple artists per event
                if (show && artistFilter) {
                    var cardArtistIds = $card.attr('data-artist-ids') || $card.attr('data-artist-id') || '';
                    var artistIdArray = cardArtistIds.split(',');
                    show = show && (artistIdArray.indexOf(artistFilter) !== -1);
                }
                
                // Location filter (by ID)
                if (show && locationFilter) {
                    var cardLocationId = $card.attr('data-location-id') || '';
                    show = show && (cardLocationId == locationFilter);
                }
                
                // Search filter (text search in card content)
                if (show && searchTerm) {
                    var cardText = $card.text().toLowerCase();
                    show = show && (cardText.indexOf(searchTerm) !== -1);
                }
                
                if (show) {
                    $card.show();
                    visibleCount++;
                } else {
                    $card.hide();
                }
            });
            
            // Show/hide no results message
            $wrapper.find('.ensemble-filter-no-results').remove();
            
            if (visibleCount === 0) {
                var noResultsText = 'No events found matching your criteria.';
                if (typeof ensembleSettings !== 'undefined' && ensembleSettings.noResultsText) {
                    noResultsText = ensembleSettings.noResultsText;
                }
                $grid.append('<div class="ensemble-no-results ensemble-filter-no-results">' + noResultsText + '</div>');
            }
        },
        
        /**
         * Check time filter
         */
        checkTimeFilter: function(dateStr, filter) {
            if (!dateStr || filter === 'all') return true;
            
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            
            var eventDate = new Date(dateStr);
            if (isNaN(eventDate.getTime())) {
                // Try alternative parsing
                eventDate = this.parseEventDate(dateStr);
            }
            
            if (!eventDate || isNaN(eventDate.getTime())) {
                return true; // Can't parse, show it
            }
            
            eventDate.setHours(0, 0, 0, 0);
            
            switch (filter) {
                case 'upcoming':
                    return eventDate >= today;
                    
                case 'today':
                    return eventDate.getTime() === today.getTime();
                    
                case 'this-week':
                    var weekEnd = new Date(today);
                    weekEnd.setDate(weekEnd.getDate() + 7);
                    return eventDate >= today && eventDate < weekEnd;
                    
                case 'past':
                    return eventDate < today;
                    
                default:
                    return true;
            }
        },
        
        /**
         * Parse date string
         */
        parseEventDate: function(dateStr) {
            if (!dateStr) return null;
            
            // Try YYYY-MM-DD format
            var match = dateStr.match(/(\d{4})-(\d{2})-(\d{2})/);
            if (match) {
                return new Date(parseInt(match[1]), parseInt(match[2]) - 1, parseInt(match[3]));
            }
            
            // Try DD.MM.YYYY format (German)
            match = dateStr.match(/(\d{1,2})\.(\d{1,2})\.(\d{4})/);
            if (match) {
                return new Date(parseInt(match[3]), parseInt(match[2]) - 1, parseInt(match[1]));
            }
            
            return null;
        }
    };
    
    $(document).ready(function() {
        EnsembleFrontend.init();
    });
    
    window.EnsembleFrontend = EnsembleFrontend;
    
})(jQuery);
