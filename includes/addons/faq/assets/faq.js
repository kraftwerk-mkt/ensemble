/**
 * Ensemble FAQ Add-on JavaScript
 * 
 * Handles accordion interactions, search, filtering
 * 
 * @package Ensemble
 * @subpackage Addons/FAQ
 * @version 1.0.0
 */

(function() {
    'use strict';
    
    // Settings from WordPress
    const settings = window.esFAQ || {
        animationSpeed: 300,
        allowMultipleOpen: false,
        searchPlaceholder: 'FAQs durchsuchen...',
        noResults: 'Keine FAQs gefunden',
        showAll: 'Alle anzeigen'
    };
    
    /**
     * Initialize FAQ functionality
     */
    function init() {
        const wrappers = document.querySelectorAll('.es-faq-wrapper');
        
        wrappers.forEach(wrapper => {
            initAccordion(wrapper);
            initSearch(wrapper);
            initFilter(wrapper);
        });
    }
    
    /**
     * Initialize accordion functionality
     */
    function initAccordion(wrapper) {
        const items = wrapper.querySelectorAll('.es-faq-item');
        const allowMultiple = wrapper.dataset.allowMultiple === 'true' || settings.allowMultipleOpen;
        
        items.forEach(item => {
            const question = item.querySelector('.es-faq-question');
            const answer = item.querySelector('.es-faq-answer');
            const answerInner = item.querySelector('.es-faq-answer-inner');
            
            if (!question || !answer || !answerInner) return;
            
            // Set initial state for expanded items
            if (item.classList.contains('active')) {
                answer.style.maxHeight = answerInner.offsetHeight + 'px';
            }
            
            question.addEventListener('click', (e) => {
                e.preventDefault();
                
                const isActive = item.classList.contains('active');
                
                // Close others if not allowing multiple
                if (!allowMultiple && !isActive) {
                    items.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('active')) {
                            closeItem(otherItem);
                        }
                    });
                }
                
                // Toggle current item
                if (isActive) {
                    closeItem(item);
                } else {
                    openItem(item);
                }
            });
            
            // Keyboard accessibility
            question.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    question.click();
                }
            });
        });
    }
    
    /**
     * Open FAQ item with animation
     */
    function openItem(item) {
        const answer = item.querySelector('.es-faq-answer');
        const answerInner = item.querySelector('.es-faq-answer-inner');
        
        if (!answer || !answerInner) return;
        
        item.classList.add('active');
        
        // Trigger reflow
        answer.offsetHeight;
        
        // Set max-height to content height
        answer.style.maxHeight = answerInner.offsetHeight + 'px';
        
        // Update ARIA
        const question = item.querySelector('.es-faq-question');
        if (question) {
            question.setAttribute('aria-expanded', 'true');
        }
        
        // Remove max-height after animation (allows content to resize)
        setTimeout(() => {
            if (item.classList.contains('active')) {
                answer.style.maxHeight = 'none';
            }
        }, settings.animationSpeed);
    }
    
    /**
     * Close FAQ item with animation
     */
    function closeItem(item) {
        const answer = item.querySelector('.es-faq-answer');
        const answerInner = item.querySelector('.es-faq-answer-inner');
        
        if (!answer || !answerInner) return;
        
        // Set explicit height first (for animation)
        answer.style.maxHeight = answerInner.offsetHeight + 'px';
        
        // Trigger reflow
        answer.offsetHeight;
        
        // Animate to 0
        answer.style.maxHeight = '0';
        
        item.classList.remove('active');
        
        // Update ARIA
        const question = item.querySelector('.es-faq-question');
        if (question) {
            question.setAttribute('aria-expanded', 'false');
        }
    }
    
    /**
     * Initialize search functionality
     */
    function initSearch(wrapper) {
        const searchInput = wrapper.querySelector('.es-faq-search');
        
        if (!searchInput) return;
        
        let debounceTimer;
        
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            
            debounceTimer = setTimeout(() => {
                const query = e.target.value.toLowerCase().trim();
                filterBySearch(wrapper, query);
            }, 200);
        });
        
        // Clear search with Escape key
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                searchInput.value = '';
                filterBySearch(wrapper, '');
                searchInput.blur();
            }
        });
    }
    
    /**
     * Filter FAQs by search query
     */
    function filterBySearch(wrapper, query) {
        const items = wrapper.querySelectorAll('.es-faq-item');
        const noResults = wrapper.querySelector('.es-faq-no-results');
        let visibleCount = 0;
        
        items.forEach(item => {
            const question = item.querySelector('.es-faq-question-text');
            const answer = item.querySelector('.es-faq-answer-inner');
            
            if (!question) return;
            
            const questionText = question.textContent.toLowerCase();
            const answerText = answer ? answer.textContent.toLowerCase() : '';
            
            // Check if matches active category filter
            const activeFilter = wrapper.querySelector('.es-faq-filter-btn.active');
            const filterCategory = activeFilter ? activeFilter.dataset.category : '';
            
            const matchesCategory = !filterCategory || 
                filterCategory === 'all' || 
                item.dataset.categories.includes(filterCategory);
            
            const matchesSearch = !query || 
                questionText.includes(query) || 
                answerText.includes(query);
            
            if (matchesCategory && matchesSearch) {
                item.classList.remove('es-faq-hidden');
                visibleCount++;
                
                // Highlight matching text
                if (query) {
                    highlightText(question, query);
                    if (answer) highlightText(answer, query);
                }
            } else {
                item.classList.add('es-faq-hidden');
                
                // Close if open
                if (item.classList.contains('active')) {
                    closeItem(item);
                }
            }
            
            // Remove highlights if no query
            if (!query) {
                removeHighlights(question);
                if (answer) removeHighlights(answer);
            }
        });
        
        // Show/hide no results message
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }
    
    /**
     * Initialize category filter
     */
    function initFilter(wrapper) {
        const filterBtns = wrapper.querySelectorAll('.es-faq-filter-btn');
        
        if (filterBtns.length === 0) return;
        
        filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Update active state
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const category = btn.dataset.category;
                filterByCategory(wrapper, category);
            });
        });
    }
    
    /**
     * Filter FAQs by category
     */
    function filterByCategory(wrapper, category) {
        const items = wrapper.querySelectorAll('.es-faq-item');
        const searchInput = wrapper.querySelector('.es-faq-search');
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const noResults = wrapper.querySelector('.es-faq-no-results');
        let visibleCount = 0;
        
        items.forEach(item => {
            const categories = item.dataset.categories || '';
            const question = item.querySelector('.es-faq-question-text');
            const answer = item.querySelector('.es-faq-answer-inner');
            
            const matchesCategory = !category || 
                category === 'all' || 
                categories.includes(category);
            
            const questionText = question ? question.textContent.toLowerCase() : '';
            const answerText = answer ? answer.textContent.toLowerCase() : '';
            
            const matchesSearch = !query || 
                questionText.includes(query) || 
                answerText.includes(query);
            
            if (matchesCategory && matchesSearch) {
                item.classList.remove('es-faq-hidden');
                visibleCount++;
            } else {
                item.classList.add('es-faq-hidden');
                
                // Close if open
                if (item.classList.contains('active')) {
                    closeItem(item);
                }
            }
        });
        
        // Show/hide no results message
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }
    
    /**
     * Highlight matching text (simple version)
     */
    function highlightText(element, query) {
        // Note: This is a simplified version
        // For production, use a proper highlight library
        // to avoid breaking HTML structure
    }
    
    /**
     * Remove text highlights
     */
    function removeHighlights(element) {
        const marks = element.querySelectorAll('mark.es-faq-highlight');
        marks.forEach(mark => {
            const text = document.createTextNode(mark.textContent);
            mark.parentNode.replaceChild(text, mark);
        });
    }
    
    /**
     * Reset search handler
     */
    window.esFaqResetSearch = function(btn) {
        const wrapper = btn.closest('.es-faq-wrapper');
        if (!wrapper) return;
        
        const searchInput = wrapper.querySelector('.es-faq-search');
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Reset filter to "All"
        const allBtn = wrapper.querySelector('.es-faq-filter-btn[data-category="all"]');
        if (allBtn) {
            wrapper.querySelectorAll('.es-faq-filter-btn').forEach(b => b.classList.remove('active'));
            allBtn.classList.add('active');
        }
        
        // Show all items
        wrapper.querySelectorAll('.es-faq-item').forEach(item => {
            item.classList.remove('es-faq-hidden');
        });
        
        // Hide no results
        const noResults = wrapper.querySelector('.es-faq-no-results');
        if (noResults) {
            noResults.style.display = 'none';
        }
    };
    
    /**
     * Expand all FAQs
     */
    window.esFaqExpandAll = function(wrapper) {
        if (typeof wrapper === 'string') {
            wrapper = document.querySelector(wrapper);
        }
        
        if (!wrapper) return;
        
        wrapper.querySelectorAll('.es-faq-item:not(.es-faq-hidden)').forEach(item => {
            if (!item.classList.contains('active')) {
                openItem(item);
            }
        });
    };
    
    /**
     * Collapse all FAQs
     */
    window.esFaqCollapseAll = function(wrapper) {
        if (typeof wrapper === 'string') {
            wrapper = document.querySelector(wrapper);
        }
        
        if (!wrapper) return;
        
        wrapper.querySelectorAll('.es-faq-item.active').forEach(item => {
            closeItem(item);
        });
    };
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Re-initialize on AJAX content load (for dynamic content)
    document.addEventListener('es:contentLoaded', init);
    
})();
