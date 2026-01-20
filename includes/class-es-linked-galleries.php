<?php
/**
 * Linked Galleries Component
 * 
 * Displays galleries linked to an entity (event, artist, location)
 * Used in Wizard, Artist Manager, and Location Manager
 *
 * @package Ensemble
 * @since 3.0.0
 * 
 * Usage:
 * <?php ES_Linked_Galleries::render('event', $event_id); ?>
 * <?php ES_Linked_Galleries::render('artist', $artist_id); ?>
 * <?php ES_Linked_Galleries::render('location', $location_id); ?>
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Linked_Galleries {
    
    /**
     * Render linked galleries section
     * 
     * @param string $entity_type 'event', 'artist', or 'location'
     * @param int $entity_id
     * @param array $args Optional arguments
     */
    public static function render($entity_type, $entity_id, $args = array()) {
        $defaults = array(
            'show_header'       => true,
            'show_create_btn'   => true,
            'show_link_btn'     => true,
            'header_text'       => __('Linked Galleries', 'ensemble'),
            'empty_text'        => __('No galleries linked yet', 'ensemble'),
            'collapsible'       => true,
            'initially_open'    => false,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Get linked galleries
        $manager = new ES_Gallery_Manager();
        $method = "get_galleries_by_{$entity_type}";
        
        if (!method_exists($manager, $method)) {
            return;
        }
        
        $galleries = $entity_id ? $manager->$method($entity_id) : array();
        $has_galleries = !empty($galleries);
        
        // Get all galleries for linking dropdown
        $all_galleries = $manager->get_galleries();
        
        // Filter out already linked galleries
        $linked_ids = array_column($galleries, 'id');
        $available_galleries = array_filter($all_galleries, function($g) use ($linked_ids) {
            return !in_array($g['id'], $linked_ids);
        });
        
        ?>
        <div class="es-linked-galleries-section" 
             data-entity-type="<?php echo esc_attr($entity_type); ?>" 
             data-entity-id="<?php echo esc_attr($entity_id); ?>">
            
            <?php if ($args['show_header']) : ?>
                <div class="es-lg-header <?php echo $args['collapsible'] ? 'es-lg-collapsible' : ''; ?>" 
                     <?php if ($args['collapsible']) : ?>data-collapsed="<?php echo $args['initially_open'] ? 'false' : 'true'; ?>"<?php endif; ?>>
                    <h4 class="es-lg-title">
                        <span class="dashicons dashicons-format-gallery"></span>
                        <?php echo esc_html($args['header_text']); ?>
                        <?php if ($has_galleries) : ?>
                            <span class="es-lg-count">(<?php echo count($galleries); ?>)</span>
                        <?php endif; ?>
                    </h4>
                    <?php if ($args['collapsible']) : ?>
                        <span class="es-lg-toggle dashicons dashicons-arrow-down-alt2"></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="es-lg-content" <?php if ($args['collapsible'] && !$args['initially_open']) : ?>style="display: none;"<?php endif; ?>>
                
                <!-- Linked Galleries List -->
                <div class="es-lg-list" id="es-linked-galleries-<?php echo esc_attr($entity_type); ?>-<?php echo esc_attr($entity_id); ?>">
                    <?php if ($has_galleries) : ?>
                        <?php foreach ($galleries as $gallery) : ?>
                            <div class="es-lg-item" data-gallery-id="<?php echo esc_attr($gallery['id']); ?>">
                                <div class="es-lg-item-preview">
                                    <?php if (!empty($gallery['featured_image'])) : ?>
                                        <img src="<?php echo esc_url($gallery['featured_image']); ?>" alt="">
                                    <?php elseif (!empty($gallery['images'])) : ?>
                                        <img src="<?php echo esc_url($gallery['images'][0]['url']); ?>" alt="">
                                    <?php else : ?>
                                        <span class="dashicons dashicons-format-gallery"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="es-lg-item-info">
                                    <span class="es-lg-item-title"><?php echo esc_html($gallery['title']); ?></span>
                                    <span class="es-lg-item-meta">
                                        <?php 
                                        $counts = array();
                                        if ($gallery['image_count'] > 0) {
                                            $counts[] = sprintf(_n('%d image', '%d images', $gallery['image_count'], 'ensemble'), $gallery['image_count']);
                                        }
                                        if ($gallery['video_count'] > 0) {
                                            $counts[] = sprintf(_n('%d video', '%d videos', $gallery['video_count'], 'ensemble'), $gallery['video_count']);
                                        }
                                        echo esc_html(implode(', ', $counts) ?: __('Empty', 'ensemble'));
                                        ?>
                                    </span>
                                </div>
                                <div class="es-lg-item-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ensemble-galleries&edit=' . $gallery['id'])); ?>" 
                                       class="es-lg-edit" 
                                       title="<?php esc_attr_e('Edit Gallery', 'ensemble'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <button type="button" 
                                            class="es-lg-unlink" 
                                            data-gallery-id="<?php echo esc_attr($gallery['id']); ?>"
                                            title="<?php esc_attr_e('Unlink', 'ensemble'); ?>">
                                        <span class="dashicons dashicons-dismiss"></span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="es-lg-empty">
                            <span class="dashicons dashicons-format-gallery"></span>
                            <p><?php echo esc_html($args['empty_text']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Actions -->
                <div class="es-lg-actions">
                    <?php if ($args['show_link_btn'] && !empty($available_galleries)) : ?>
                        <div class="es-lg-link-form">
                            <select class="es-lg-select" id="es-lg-select-<?php echo esc_attr($entity_type); ?>-<?php echo esc_attr($entity_id); ?>">
                                <option value=""><?php _e('— Select gallery to link —', 'ensemble'); ?></option>
                                <?php foreach ($available_galleries as $g) : ?>
                                    <option value="<?php echo esc_attr($g['id']); ?>">
                                        <?php echo esc_html($g['title']); ?>
                                        (<?php echo esc_html($g['total_count']); ?> <?php _e('items', 'ensemble'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="button es-lg-link-btn">
                                <span class="dashicons dashicons-admin-links"></span>
                                <?php _e('Link', 'ensemble'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($args['show_create_btn']) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=ensemble-galleries&new=1&link_' . $entity_type . '=' . $entity_id)); ?>" 
                           class="button es-lg-create-btn">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Create New Gallery', 'ensemble'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .es-linked-galleries-section {
            margin: 20px 0;
            border: 1px solid var(--es-admin-border, #ddd);
            border-radius: 6px;
            background: var(--es-admin-surface, #fff);
        }
        
        .es-lg-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            background: var(--es-admin-bg, #f9f9f9);
            border-bottom: 1px solid var(--es-admin-border, #eee);
            border-radius: 6px 6px 0 0;
        }
        
        .es-lg-header.es-lg-collapsible {
            cursor: pointer;
        }
        
        .es-lg-header.es-lg-collapsible:hover {
            background: var(--es-admin-hover, #f0f0f0);
        }
        
        .es-lg-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--es-admin-text, #1a1a1a);
        }
        
        .es-lg-title .dashicons {
            color: var(--es-admin-text-secondary, #666);
        }
        
        .es-lg-count {
            font-weight: 400;
            color: var(--es-admin-text-secondary, #666);
        }
        
        .es-lg-toggle {
            color: var(--es-admin-text-secondary, #999);
            transition: transform 0.2s;
        }
        
        .es-lg-header[data-collapsed="false"] .es-lg-toggle {
            transform: rotate(180deg);
        }
        
        .es-lg-content {
            padding: 15px;
        }
        
        .es-lg-list {
            margin-bottom: 15px;
        }
        
        .es-lg-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            border: 1px solid var(--es-admin-border, #eee);
            border-radius: 4px;
            margin-bottom: 8px;
            background: var(--es-admin-bg, #fafafa);
        }
        
        .es-lg-item:last-child {
            margin-bottom: 0;
        }
        
        .es-lg-item-preview {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            overflow: hidden;
            background: var(--es-admin-border, #e5e5e5);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .es-lg-item-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .es-lg-item-preview .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            color: var(--es-admin-text-secondary, #999);
        }
        
        .es-lg-item-info {
            flex: 1;
            min-width: 0;
        }
        
        .es-lg-item-title {
            display: block;
            font-weight: 500;
            color: var(--es-admin-text, #1a1a1a);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .es-lg-item-meta {
            display: block;
            font-size: 12px;
            color: var(--es-admin-text-secondary, #666);
        }
        
        .es-lg-item-actions {
            display: flex;
            gap: 4px;
        }
        
        .es-lg-item-actions a,
        .es-lg-item-actions button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            padding: 0;
            border: none;
            background: transparent;
            color: var(--es-admin-text-secondary, #999);
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .es-lg-item-actions a:hover,
        .es-lg-item-actions button:hover {
            background: var(--es-admin-hover, #e5e5e5);
            color: var(--es-admin-text, #333);
        }
        
        .es-lg-unlink:hover {
            background: #fee2e2 !important;
            color: #dc2626 !important;
        }
        
        .es-lg-empty {
            text-align: center;
            padding: 30px 20px;
            color: var(--es-admin-text-secondary, #999);
        }
        
        .es-lg-empty .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        
        .es-lg-empty p {
            margin: 0;
            font-size: 13px;
        }
        
        .es-lg-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid var(--es-admin-border, #eee);
        }
        
        .es-lg-link-form {
            display: flex;
            gap: 8px;
            flex: 1;
        }
        
        .es-lg-select {
            flex: 1;
            max-width: 300px;
        }
        
        .es-lg-create-btn {
            margin-left: auto;
        }
        </style>
        
        <script>
        (function($) {
            // Toggle collapsible
            $(document).on('click', '.es-lg-collapsible', function() {
                const $header = $(this);
                const $content = $header.next('.es-lg-content');
                const isCollapsed = $header.data('collapsed') === true || $header.data('collapsed') === 'true';
                
                if (isCollapsed) {
                    $content.slideDown(200);
                    $header.data('collapsed', false);
                } else {
                    $content.slideUp(200);
                    $header.data('collapsed', true);
                }
            });
            
            // Link gallery
            $(document).on('click', '.es-lg-link-btn', function() {
                const $section = $(this).closest('.es-linked-galleries-section');
                const $select = $section.find('.es-lg-select');
                const galleryId = $select.val();
                const entityType = $section.data('entity-type');
                const entityId = $section.data('entity-id');
                
                if (!galleryId) return;
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ensemble_link_gallery',
                        nonce: ensembleAdmin.nonce,
                        gallery_id: galleryId,
                        entity_type: entityType,
                        entity_id: entityId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });
            
            // Unlink gallery
            $(document).on('click', '.es-lg-unlink', function() {
                if (!confirm('<?php _e('Remove this gallery link?', 'ensemble'); ?>')) {
                    return;
                }
                
                const $item = $(this).closest('.es-lg-item');
                const $section = $(this).closest('.es-linked-galleries-section');
                const galleryId = $(this).data('gallery-id');
                const entityType = $section.data('entity-type');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ensemble_unlink_gallery',
                        nonce: ensembleAdmin.nonce,
                        gallery_id: galleryId,
                        entity_type: entityType
                    },
                    success: function(response) {
                        if (response.success) {
                            $item.fadeOut(200, function() {
                                $(this).remove();
                            });
                        }
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}
