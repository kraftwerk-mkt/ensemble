<?php
/**
 * ID Picker Tab
 * 
 * Find IDs for Events, Artists, and Locations to use in shortcodes
 * 
 * @package Ensemble
 * @since 2.9.3
 */

if (!defined('ABSPATH')) exit;
?>

        <div class="es-id-picker-section">
            
            <div class="es-section-intro">
                <h2>
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('ID Picker', 'ensemble'); ?>
                </h2>
                <p class="es-description">
                    <?php _e('Find IDs for Events, Artists, and Locations to use in your shortcodes. Simply search, select, and copy the ID.', 'ensemble'); ?>
                </p>
            </div>
            
            <div class="es-picker-tabs">
                <button class="es-picker-tab active" data-tab="events">
                    <span class="dashicons dashicons-calendar"></span>
                    <?php _e('Events', 'ensemble'); ?>
                </button>
                <button class="es-picker-tab" data-tab="artists">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php _e('Artists', 'ensemble'); ?>
                </button>
                <button class="es-picker-tab" data-tab="locations">
                    <span class="dashicons dashicons-location"></span>
                    <?php _e('Locations', 'ensemble'); ?>
                </button>
                <?php if (post_type_exists('ensemble_staff')): ?>
                <button class="es-picker-tab" data-tab="staff">
                    <span class="dashicons dashicons-businessperson"></span>
                    <?php echo esc_html(ES_Label_System::get_label('staff', true)); ?>
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Events Picker -->
            <div class="es-picker-content active" data-content="events">
                <div class="es-picker-search">
                    <input type="text" 
                           id="events-search" 
                           class="es-search-input" 
                           placeholder="<?php _e('Search events...', 'ensemble'); ?>">
                    <span class="dashicons dashicons-search"></span>
                </div>
                
                <div class="es-picker-results" id="events-results">
                    <div class="es-loading"><?php _e('Loading...', 'ensemble'); ?></div>
                </div>
            </div>
            
            <!-- Artists Picker -->
            <div class="es-picker-content" data-content="artists">
                <div class="es-picker-search">
                    <input type="text" 
                           id="artists-search" 
                           class="es-search-input" 
                           placeholder="<?php _e('Search artists...', 'ensemble'); ?>">
                    <span class="dashicons dashicons-search"></span>
                </div>
                
                <div class="es-picker-results" id="artists-results">
                    <div class="es-loading"><?php _e('Loading...', 'ensemble'); ?></div>
                </div>
            </div>
            
            <!-- Locations Picker -->
            <div class="es-picker-content" data-content="locations">
                <div class="es-picker-search">
                    <input type="text" 
                           id="locations-search" 
                           class="es-search-input" 
                           placeholder="<?php _e('Search locations...', 'ensemble'); ?>">
                    <span class="dashicons dashicons-search"></span>
                </div>
                
                <div class="es-picker-results" id="locations-results">
                    <div class="es-loading"><?php _e('Loading...', 'ensemble'); ?></div>
                </div>
            </div>
            
            <!-- Staff Picker -->
            <?php if (post_type_exists('ensemble_staff')): 
                $staff_plural = ES_Label_System::get_label('staff', true);
            ?>
            <div class="es-picker-content" data-content="staff">
                <div class="es-picker-search">
                    <input type="text" 
                           id="staff-search" 
                           class="es-search-input" 
                           placeholder="<?php printf(__('Search %s...', 'ensemble'), strtolower($staff_plural)); ?>">
                    <span class="dashicons dashicons-search"></span>
                </div>
                
                <div class="es-picker-results" id="staff-results">
                    <div class="es-loading"><?php _e('Loading...', 'ensemble'); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>

<script>
jQuery(document).ready(function($) {
    
    // Tab switching
    $('.es-picker-tab').on('click', function() {
        const tab = $(this).data('tab');
        
        $('.es-picker-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.es-picker-content').removeClass('active');
        $('.es-picker-content[data-content="' + tab + '"]').addClass('active');
        
        // Load data if not already loaded
        if (!$(`.es-picker-content[data-content="${tab}"]`).data('loaded')) {
            loadPickerData(tab);
        }
    });
    
    // Search functionality
    let searchTimeout;
    $('.es-search-input').on('input', function() {
        const input = $(this);
        const searchTerm = input.val();
        const type = input.attr('id').replace('-search', '');
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadPickerData(type, searchTerm);
        }, 300);
    });
    
    // Load initial data
    loadPickerData('events');
    
    // Load picker data
    function loadPickerData(type, search = '') {
        const resultsContainer = $('#' + type + '-results');
        resultsContainer.html('<div class="es-loading"><?php _e('Loading...', 'ensemble'); ?></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ensemble_get_picker_data',
                type: type,
                search: search,
                nonce: '<?php echo wp_create_nonce('ensemble_picker'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    renderPickerResults(type, response.data, resultsContainer);
                    $(`.es-picker-content[data-content="${type}"]`).data('loaded', true);
                } else {
                    resultsContainer.html('<div class="es-error">' + response.data + '</div>');
                }
            },
            error: function() {
                resultsContainer.html('<div class="es-error"><?php _e('Error loading data', 'ensemble'); ?></div>');
            }
        });
    }
    
    // Render results
    function renderPickerResults(type, items, container) {
        if (items.length === 0) {
            container.html('<div class="es-no-results"><?php _e('No items found', 'ensemble'); ?></div>');
            return;
        }
        
        let html = '<div class="es-picker-list">';
        
        items.forEach(function(item) {
            html += '<div class="es-picker-item">';
            html += '  <div class="es-picker-item-info">';
            html += '    <div class="es-picker-item-title">' + escapeHtml(item.title) + '</div>';
            html += '    <div class="es-picker-item-meta">';
            html += '      <span class="es-picker-item-id">ID: ' + item.id + '</span>';
            if (item.meta) {
                html += '      <span class="es-picker-item-meta-text">' + escapeHtml(item.meta) + '</span>';
            }
            html += '    </div>';
            html += '  </div>';
            html += '  <div class="es-picker-item-actions">';
            html += '    <button class="es-copy-id-btn" data-id="' + item.id + '" data-title="' + escapeHtml(item.title) + '">';
            html += '      <span class="dashicons dashicons-admin-page"></span>';
            html += '      <?php _e('Copy ID', 'ensemble'); ?>';
            html += '    </button>';
            html += '    <a href="' + item.edit_url + '" class="es-edit-btn" target="_blank">';
            html += '      <span class="dashicons dashicons-edit"></span>';
            html += '    </a>';
            html += '  </div>';
            html += '</div>';
        });
        
        html += '</div>';
        container.html(html);
        
        // Add copy functionality
        container.find('.es-copy-id-btn').on('click', function() {
            const btn = $(this);
            const id = btn.data('id');
            const title = btn.data('title');
            
            // Copy to clipboard
            copyToClipboard(id);
            
            // Visual feedback
            const originalText = btn.html();
            btn.html('<span class="dashicons dashicons-yes"></span> <?php _e('Copied!', 'ensemble'); ?>');
            btn.addClass('copied');
            
            setTimeout(function() {
                btn.html(originalText);
                btn.removeClass('copied');
            }, 2000);
            
            // Show notification
            showNotification('ID ' + id + ' (' + title + ') <?php _e('copied to clipboard!', 'ensemble'); ?>');
        });
    }
    
    // Copy to clipboard
    function copyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
    
    // Show notification
    function showNotification(message) {
        const notification = $('<div class="es-notification">' + message + '</div>');
        $('body').append(notification);
        
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>

<style>
/* ID Picker Styles */
.es-id-picker-section {
    background: var(--es-card-bg);
    border-radius: 12px;
    padding: 30px;
    margin-top: 20px;
}

.es-picker-tabs {
    display: flex;
    gap: 10px;
    margin: 20px 0;
    border-bottom: 2px solid var(--es-border);
    padding-bottom: 10px;
}

.es-picker-tab {
    background: none;
    border: none;
    padding: 12px 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--es-text-secondary);
    border-radius: 8px 8px 0 0;
    transition: all 0.2s;
}

.es-picker-tab:hover {
    background: var(--es-hover-bg);
    color: var(--es-text);
}

.es-picker-tab.active {
    background: var(--es-primary);
    color: white;
}

.es-picker-content {
    display: none;
    margin-top: 20px;
}

.es-picker-content.active {
    display: block;
}

.es-picker-search {
    position: relative;
    margin-bottom: 20px;
}

.es-search-input {
    width: 100%;
    padding: 12px 40px 12px 16px;
    border: 2px solid var(--es-border);
    border-radius: 8px;
    font-size: 14px;
    background: var(--es-input-bg);
    color: var(--es-text);
}

.es-search-input:focus {
    outline: none;
    border-color: var(--es-primary);
}

.es-picker-search .dashicons {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--es-text-secondary);
}

.es-picker-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.es-picker-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: var(--es-input-bg);
    border: 1px solid var(--es-border);
    border-radius: 8px;
    transition: all 0.2s;
}

.es-picker-item:hover {
    border-color: var(--es-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.es-picker-item-info {
    flex: 1;
}

.es-picker-item-title {
    font-weight: 600;
    font-size: 15px;
    color: var(--es-text);
    margin-bottom: 4px;
}

.es-picker-item-meta {
    display: flex;
    gap: 12px;
    font-size: 13px;
    color: var(--es-text-secondary);
}

.es-picker-item-id {
    font-family: 'Courier New', monospace;
    background: var(--es-card-bg);
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
    color: var(--es-primary);
}

.es-picker-item-actions {
    display: flex;
    gap: 8px;
}

.es-copy-id-btn,
.es-edit-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
}

.es-copy-id-btn {
    background: var(--es-primary);
    color: white;
}

.es-copy-id-btn:hover {
    background: var(--es-primary-dark);
    transform: translateY(-1px);
}

.es-copy-id-btn.copied {
    background: #10b981;
}

.es-edit-btn {
    background: var(--es-secondary);
    color: white;
}

.es-edit-btn:hover {
    background: var(--es-secondary-dark);
    transform: translateY(-1px);
}

.es-loading,
.es-error,
.es-no-results {
    text-align: center;
    padding: 40px;
    color: var(--es-text-secondary);
}

.es-error {
    color: #ef4444;
}

.es-notification {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #10b981;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 9999;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s;
}

.es-notification.show {
    opacity: 1;
    transform: translateY(0);
}

.es-badge-new {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: 8px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
</style>
