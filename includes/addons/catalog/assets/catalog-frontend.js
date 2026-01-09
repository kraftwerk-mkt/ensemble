/**
 * Catalog Frontend JavaScript
 * Filter functionality
 */
(function($) {
    'use strict';
    
    $(document).on('click', '.es-filter-btn', function() {
        const $btn = $(this);
        const $catalog = $btn.closest('.es-catalog');
        const categoryId = $btn.data('category');
        
        // Update active state
        $catalog.find('.es-filter-btn').removeClass('active');
        $btn.addClass('active');
        
        // Filter categories
        if (categoryId === 'all') {
            $catalog.find('.es-catalog-category').show();
        } else {
            $catalog.find('.es-catalog-category').hide();
            $catalog.find(`.es-catalog-category[data-category-id="${categoryId}"]`).show();
        }
    });
    
})(jQuery);
