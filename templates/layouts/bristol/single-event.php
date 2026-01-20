<?php
/**
 * Single Event Template - Bristol Editorial Style
 * Asymmetric split layout, bold typography
 * Hero Info Toggle for quick access to details
 * 
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 2.1.2
 */
if (!defined('ABSPATH')) exit;

get_header();

$event_id = get_the_ID();
$event = es_load_event_data($event_id);

// Date formatting
$date_day = '';
$date_month = '';
$date_full = '';
$time_display = '';
$date_weekday = '';

if (!empty($event['date'])) {
    $timestamp = strtotime($event['date']);
    $date_day = date_i18n('j', $timestamp);
    $date_month = date_i18n('F', $timestamp);
    $date_full = date_i18n('j. F Y', $timestamp);
    $date_weekday = date_i18n('l', $timestamp);
    $time_display = date_i18n('H:i', $timestamp);
}

// Location
$location_name = !empty($event['location']['name']) ? $event['location']['name'] : '';
$location_address = !empty($event['location']['address']) ? $event['location']['address'] : '';
$location_id = !empty($event['location']['id']) ? $event['location']['id'] : 0;

// Category
$category = !empty($event['categories']) ? $event['categories'][0]->name : '';

// Price
$price_display = '';
if (!empty($event['price'])) {
    $price_display = number_format($event['price'], 2, ',', '.') . ' €';
} elseif (!empty($event['ticket_categories']) && is_array($event['ticket_categories'])) {
    $prices = array_column($event['ticket_categories'], 'price');
    if (!empty($prices)) {
        $min_price = min(array_filter($prices));
        $price_display = __('ab', 'ensemble') . ' ' . number_format($min_price, 2, ',', '.') . ' €';
    }
}

// Theme mode
$mode_class = isset($_COOKIE['es_bristol_mode']) && $_COOKIE['es_bristol_mode'] === 'light' ? 'es-mode-light' : '';
?>

<div class="es-bristol <?php echo esc_attr($mode_class); ?>">

    <?php 
    // Hook: Before event
    if (function_exists('ensemble_before_event')) {
        ensemble_before_event($event_id);
    }
    ?>

    <!-- HERO: Split Layout with Info Toggle -->
    <header class="es-bristol-single-hero es-bristol-hero-toggleable">
        <!-- Left: Image -->
        <div class="es-bristol-single-hero-media">
            <?php if (has_post_thumbnail()): ?>
                <?php the_post_thumbnail('full'); ?>
            <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--es-surface) 0%, var(--es-bg-alt) 100%);"></div>
            <?php endif; ?>
        </div>
        
        <!-- Right: Content with Toggle Panels -->
        <div class="es-bristol-single-hero-content">
            
            <!-- Info Toggle Button -->
            <button class="es-bristol-hero-info-toggle" aria-label="<?php esc_attr_e('Details anzeigen', 'ensemble'); ?>" aria-expanded="false">
                <span class="es-bristol-hero-toggle-text"><?php _e('Info', 'ensemble'); ?></span>
                <svg class="es-bristol-hero-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
            
            <!-- Panel 1: Title (Default) -->
            <div class="es-bristol-hero-panel es-bristol-hero-panel--title es-active">
                <?php 
                // Badges row - Category + Event Badge
                $badge_label = !empty($event['badge_label']) ? $event['badge_label'] : '';
                $badge_raw = !empty($event['badge_raw']) ? $event['badge_raw'] : '';
                
                // Badge colors
                $badge_colors = array(
                    'sold_out' => 'background:#dc2626;',
                    'few_tickets' => 'background:#f59e0b;color:#000;',
                    'free' => 'background:#10b981;',
                    'new' => 'background:#00e5ff;color:#000;',
                    'premiere' => 'background:linear-gradient(135deg,#ff5722,#00e5ff);',
                    'special' => 'background:linear-gradient(135deg,#ff5722,#00e5ff);',
                    'last_show' => 'background:#7c3aed;',
                    'category' => 'background:transparent;border:2px solid var(--es-primary,#ff5722);',
                );
                $badge_style = isset($badge_colors[$badge_raw]) ? $badge_colors[$badge_raw] : 'background:var(--es-primary,#ff5722);';
                
                if ($category || $badge_label): 
                ?>
                <div class="es-bristol-hero-badges">
                    <?php if ($category): ?>
                    <span class="es-bristol-single-hero-badge"><span><?php echo esc_html($category); ?></span></span>
                    <?php endif; ?>
                    
                    <?php if ($badge_label): ?>
                    <span class="es-bristol-event-badge es-badge-<?php echo esc_attr($badge_raw ?: 'default'); ?>" style="<?php echo $badge_style; ?>">
                        <?php echo esc_html($badge_label); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <h1 class="es-bristol-single-hero-title"><?php the_title(); ?></h1>
                
                <?php if (has_excerpt()): ?>
                <p class="es-bristol-single-hero-subtitle"><?php echo get_the_excerpt(); ?></p>
                <?php endif; ?>
                
                <!-- Quick Info (visible on title panel) -->
                <div class="es-bristol-hero-quick-info">
                    <?php if ($date_full): ?>
                    <span class="es-bristol-hero-quick-item">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?php echo esc_html($date_full); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($location_name): ?>
                    <span class="es-bristol-hero-quick-item">
                        <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo esc_html($location_name); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Panel 2: Info Details (Toggle) -->
            <div class="es-bristol-hero-panel es-bristol-hero-panel--info">
                <h2 class="es-bristol-hero-info-heading"><?php _e('Event Details', 'ensemble'); ?></h2>
                
                <?php 
                // Description preview in info panel
                $desc_text = !empty($event['description']) ? $event['description'] : (!empty($event['content']) ? $event['content'] : '');
                if ($desc_text): 
                ?>
                <div class="es-bristol-hero-info-description">
                    <?php 
                    $description = wp_strip_all_tags($desc_text);
                    if (strlen($description) > 300) {
                        $description = substr($description, 0, 300) . '…';
                    }
                    echo '<p>' . esc_html($description) . '</p>';
                    ?>
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons - direkt unter Text -->
                <?php 
                // Check for booking/tickets
                $has_booking = has_action('ensemble_event_booking_section');
                $has_tickets = !empty($event['ticket_url']) || !empty($event['ticket_categories']);
                $has_reservations = !empty(get_post_meta($event_id, '_reservation_enabled', true));
                ?>
                <div class="es-bristol-hero-actions">
                    <!-- Weiterlesen Button -->
                    <a href="#es-event-content" class="es-bristol-hero-btn es-bristol-hero-btn--secondary">
                        <?php _e('Weiterlesen', 'ensemble'); ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12l7 7 7-7"/>
                        </svg>
                    </a>
                    
                    <?php if ($has_booking || $has_tickets || $has_reservations): ?>
                    <a href="#es-event-booking" class="es-bristol-hero-btn es-bristol-hero-btn--primary">
                        <?php 
                        if ($has_reservations) {
                            _e('Reservieren', 'ensemble');
                        } else {
                            _e('Tickets', 'ensemble');
                        }
                        ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12l7 7 7-7"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Meta Details Box -->
                <div class="es-bristol-single-hero-meta">
                    <?php if ($date_full): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Datum', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html($date_weekday . ', ' . $date_full); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($time_display && $time_display !== '00:00'): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Einlass', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html($time_display); ?> Uhr</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($location_name): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Location', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value">
                                <?php if ($location_id): ?>
                                <a href="<?php echo get_permalink($location_id); ?>" class="es-bristol-info-link"><?php echo esc_html($location_name); ?></a>
                                <?php else: ?>
                                <?php echo esc_html($location_name); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($location_address): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Adresse', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html($location_address); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($price_display): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Preis', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html($price_display); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['age_restriction'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Alter', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html($event['age_restriction']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </header>
    
    <?php 
    // Hook: Event header
    if (function_exists('ensemble_event_header')) {
        ensemble_event_header($event_id);
    }
    ?>

    <!-- BODY: Asymmetric Grid -->
    <div class="es-bristol-body">
        <div class="es-bristol-container">
            <div class="es-bristol-layout">
                
                <!-- Main Content -->
                <main class="es-bristol-main" id="es-event-content">
                    
                    <?php 
                    // Hook: Before content
                    if (function_exists('ensemble_before_content')) {
                        ensemble_before_content($event_id);
                    }
                    ?>
                    
                    <?php 
                    // Description - Support multiple data sources
                    $has_description = !empty($event['description']) || !empty($event['content']) || get_the_content();
                    if ($has_description): 
                    ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Über das Event', 'ensemble'); ?></h2>
                        <div class="es-bristol-prose">
                            <?php 
                            if (!empty($event['description'])) {
                                echo wpautop($event['description']);
                            } elseif (!empty($event['content'])) {
                                echo wp_kses_post($event['content']);
                            } else {
                                the_content();
                            }
                            ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php 
                    // Hook: After description (Agenda, Timetable, etc.)
                    if (function_exists('ensemble_after_description')) {
                        ensemble_after_description($event_id);
                    }
                    // NOTE: Booking Engine renders in sidebar via ensemble_event_booking_section
                    // Do NOT call ensemble_event_after_content here to avoid duplicate booking widgets
                    ?>
                    
                    <!-- Artists -->
                    <?php if (!empty($event['artists'])): ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Line-Up', 'ensemble'); ?></h2>
                        <div class="es-bristol-artist-grid">
                            <?php foreach ($event['artists'] as $artist): ?>
                            <a href="<?php echo get_permalink($artist->ID); ?>" class="es-bristol-artist-item">
                                <?php if (has_post_thumbnail($artist->ID)): ?>
                                    <?php echo get_the_post_thumbnail($artist->ID, 'medium'); ?>
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--es-surface), var(--es-bg-alt));display:flex;align-items:center;justify-content:center;">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--es-text-muted)" stroke-width="1.5">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <span class="es-bristol-artist-item-name"><?php echo esc_html($artist->post_title); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php 
                    // Hook: Artist section
                    if (function_exists('ensemble_artist_section')) {
                        ensemble_artist_section($event_id);
                    }
                    ?>
                    
                    <?php 
                    // Hook: Gallery area (for Gallery Pro addon)
                    if (function_exists('ensemble_gallery_area')) {
                        ensemble_gallery_area($event_id);
                    }
                    ?>
                    
                    <!-- Gallery (fallback if no addon renders content) -->
                    <?php 
                    // Check if we have gallery images and no addon rendered content
                    $has_gallery_addon = function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('gallery_area');
                    if (!empty($event['gallery']) && !$has_gallery_addon): 
                        
                        // Process gallery data - can be IDs, URLs, or mixed
                        $gallery_images = array();
                        foreach ($event['gallery'] as $image) {
                            $img_data = array('url' => '', 'full' => '', 'alt' => '');
                            
                            // Case 1: Numeric ID (attachment ID)
                            if (is_numeric($image)) {
                                $attachment_id = intval($image);
                                if ($attachment_id > 0) {
                                    $img_data['url'] = wp_get_attachment_image_url($attachment_id, 'large');
                                    $img_data['full'] = wp_get_attachment_image_url($attachment_id, 'full');
                                    $img_data['alt'] = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                                }
                            }
                            // Case 2: Array with 'id' key (ACF format)
                            elseif (is_array($image) && !empty($image['id'])) {
                                $attachment_id = intval($image['id']);
                                if ($attachment_id > 0) {
                                    $img_data['url'] = wp_get_attachment_image_url($attachment_id, 'large');
                                    $img_data['full'] = wp_get_attachment_image_url($attachment_id, 'full');
                                    $img_data['alt'] = $image['alt'] ?? get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                                }
                            }
                            // Case 3: Array with 'url' key (already processed)
                            elseif (is_array($image) && !empty($image['url'])) {
                                $img_data['url'] = $image['url'];
                                $img_data['full'] = $image['full'] ?? $image['url'];
                                $img_data['alt'] = $image['alt'] ?? '';
                            }
                            // Case 4: Direct URL string
                            elseif (is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
                                $img_data['url'] = $image;
                                $img_data['full'] = $image;
                            }
                            
                            // Validate URL - skip invalid/empty
                            if (empty($img_data['url'])) continue;
                            
                            // Skip invalid IP-based URLs (0.x.x.x pattern)
                            if (preg_match('/^https?:\/\/0\.\d+\.\d+\.\d+/', $img_data['url'])) continue;
                            
                            $gallery_images[] = $img_data;
                        }
                        
                        if (!empty($gallery_images)):
                    ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Galerie', 'ensemble'); ?></h2>
                        <div class="es-bristol-gallery">
                            <?php foreach ($gallery_images as $img): ?>
                            <div class="es-bristol-gallery-item">
                                <a href="<?php echo esc_url($img['full']); ?>" data-lightbox="gallery">
                                    <img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($img['alt']); ?>" loading="lazy">
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php 
                        endif;
                    endif; 
                    ?>
                    
                    <?php 
                    // ========================================
                    // BOOKING ENGINE / FLOOR PLAN - Main Content
                    // ========================================
                    ?>
                    <div id="es-event-booking">
                    <?php
                    // This is the primary location for Booking Engine with Floor Plan
                    do_action('ensemble_event_booking_section', $event_id);
                    
                    // Legacy ticket area hook (for backward compatibility)
                    if (function_exists('ensemble_ticket_area')) {
                        ensemble_ticket_area($event_id);
                    }
                    ?>
                    </div>
                    
                    <?php 
                    // ========================================
                    // ADDITIONAL INFO - From Wizard
                    // ========================================
                    $additional_info = !empty($event['additional_info']) ? $event['additional_info'] : '';
                    if ($additional_info): 
                    ?>
                    <section class="es-bristol-section es-bristol-additional-info">
                        <h2 class="es-bristol-section-title"><?php _e('Weitere Informationen', 'ensemble'); ?></h2>
                        <div class="es-bristol-prose">
                            <?php echo wpautop(wp_kses_post($additional_info)); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php 
                    // Hook: After location
                    if (function_exists('ensemble_after_location')) {
                        ensemble_after_location($event_id, $location_id);
                    }
                    ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="es-bristol-aside">
                    
                    <?php 
                    // Hook: Event sidebar (general)
                    if (function_exists('ensemble_event_sidebar')) {
                        ensemble_event_sidebar($event_id);
                    }
                    ?>
                    
                    <?php 
                    // ========================================
                    // MAP - Same as Simple Club template
                    // ========================================
                    ensemble_after_location($event_id, $event['location'] ?: array()); 
                    ?>
                    
                    <!-- Tickets (fallback if no addon) -->
                    <?php 
                    $has_booking_addon = has_action('ensemble_event_booking_section');
                    if (!$has_booking_addon && (!empty($event['ticket_categories']) || !empty($event['ticket_link']))): 
                    ?>
                    <div class="es-bristol-tickets-card">
                        <h3 class="es-bristol-tickets-card-title"><?php _e('Tickets', 'ensemble'); ?></h3>
                        
                        <?php if (!empty($event['ticket_categories'])): ?>
                        <div class="es-bristol-ticket-list">
                            <?php foreach ($event['ticket_categories'] as $cat): ?>
                            <div class="es-bristol-ticket-cat <?php echo !empty($cat['sold_out']) ? 'es-sold-out' : ''; ?>">
                                <span class="es-bristol-cat-name"><?php echo esc_html($cat['name']); ?></span>
                                <span class="es-bristol-cat-price">
                                    <?php if (!empty($cat['sold_out'])): ?>
                                        <?php _e('Ausverkauft', 'ensemble'); ?>
                                    <?php else: ?>
                                        <?php echo esc_html(number_format($cat['price'], 2, ',', '.') . ' €'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($event['ticket_link'])): ?>
                        <a href="<?php echo esc_url($event['ticket_link']); ?>" class="es-bristol-btn es-bristol-btn-primary" style="width:100%;margin-top:24px;" target="_blank">
                            <?php _e('Tickets kaufen', 'ensemble'); ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Hook: After tickets
                    if (function_exists('ensemble_after_tickets')) {
                        ensemble_after_tickets($event_id);
                    }
                    ?>
                    
                    <!-- Event Info -->
                    <div class="es-bristol-info-card">
                        <h4 class="es-bristol-info-card-title"><?php _e('Details', 'ensemble'); ?></h4>
                        
                        <?php if (!empty($event['organizer'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Veranstalter', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($event['organizer']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($location_address): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Adresse', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($location_address); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($event['age_restriction'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Alter', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($event['age_restriction']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($event['status']) && $event['status'] !== 'scheduled'): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Status', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value">
                                <?php 
                                $statuses = array(
                                    'cancelled' => __('Abgesagt', 'ensemble'),
                                    'postponed' => __('Verschoben', 'ensemble'),
                                    'soldout' => __('Ausverkauft', 'ensemble')
                                );
                                echo esc_html($statuses[$event['status']] ?? $event['status']);
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Hook: Social share
                    if (function_exists('ensemble_social_share')) {
                        ensemble_social_share($event_id);
                    }
                    ?>
                    
                </aside>
                
            </div>
        </div>
    </div>
    
    <?php 
    // Hook: Event footer
    if (function_exists('ensemble_event_footer')) {
        ensemble_event_footer($event_id);
    }
    ?>
    
    <?php 
    // Hook: Related events
    if (function_exists('ensemble_related_events')) {
        ensemble_related_events($event_id);
    }
    ?>
    
    <!-- Related Events (fallback if no addon) -->
    <?php
    if (!function_exists('ensemble_related_events') || !ensemble_has_addon_hook('related_events')):
        // Get category for related events
        $event_cats = !empty($event['categories']) ? wp_list_pluck($event['categories'], 'term_id') : array();
        
        $related_args = array(
            'post_type' => ensemble_get_post_type('event'),
            'posts_per_page' => 6,
            'post__not_in' => array($event_id),
            'meta_key' => '_event_date',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        );
        
        // If we have categories, prefer same category
        if (!empty($event_cats)) {
            $related_args['tax_query'] = array(
                array(
                    'taxonomy' => 'event_category',
                    'field' => 'term_id',
                    'terms' => $event_cats
                )
            );
        }
        
        $related = new WP_Query($related_args);
        if ($related->have_posts()):
    ?>
    <section class="es-bristol-related">
        <div class="es-bristol-related-header">
            <h2 class="es-bristol-related-title"><?php _e('Weitere Events', 'ensemble'); ?></h2>
            <div class="es-bristol-related-nav">
                <button class="es-related-prev"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg></button>
                <button class="es-related-next"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button>
            </div>
        </div>
        <div class="es-bristol-related-scroll">
            <?php while ($related->have_posts()): $related->the_post(); 
                // Try to load the event-card template
                $template_path = ES_PLUGIN_DIR . 'templates/layouts/bristol/event-card.php';
                if (file_exists($template_path)) {
                    include($template_path);
                } else {
                    // Fallback: Simple card
                    $ev = es_load_event_data(get_the_ID());
                    ?>
                    <article class="es-bristol-card">
                        <a href="<?php the_permalink(); ?>" class="es-bristol-card-link">
                            <div class="es-bristol-card-media">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('medium_large'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="es-bristol-card-title-bar">
                                <h3 class="es-bristol-card-title"><?php the_title(); ?></h3>
                            </div>
                        </a>
                    </article>
                    <?php
                }
            endwhile; wp_reset_postdata(); ?>
        </div>
    </section>
    <?php 
        endif;
    endif;
    ?>
    
    <?php 
    // Hook: After event
    if (function_exists('ensemble_after_event')) {
        ensemble_after_event($event_id, $event);
    }
    ?>

    <!-- Theme Toggle -->
    <button class="es-bristol-theme-toggle" aria-label="<?php esc_attr_e('Theme wechseln', 'ensemble'); ?>">
        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
        </svg>
        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>
    </button>

</div>

<script>
(function() {
    // Theme Toggle
    const themeToggle = document.querySelector('.es-bristol-theme-toggle');
    const root = document.querySelector('.es-bristol');
    
    themeToggle?.addEventListener('click', function() {
        root.classList.toggle('es-mode-light');
        const isLight = root.classList.contains('es-mode-light');
        document.cookie = 'es_bristol_mode=' + (isLight ? 'light' : 'dark') + ';path=/;max-age=31536000';
    });
    
    // Hero Info Toggle
    const infoToggle = document.querySelector('.es-bristol-hero-info-toggle');
    const heroContent = document.querySelector('.es-bristol-single-hero-content');
    const titlePanel = document.querySelector('.es-bristol-hero-panel--title');
    const infoPanel = document.querySelector('.es-bristol-hero-panel--info');
    
    if (infoToggle && titlePanel && infoPanel) {
        infoToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            // Toggle state
            this.setAttribute('aria-expanded', !isExpanded);
            this.classList.toggle('es-active');
            
            // Toggle panels
            titlePanel.classList.toggle('es-active');
            infoPanel.classList.toggle('es-active');
            
            // Update button text
            const textEl = this.querySelector('.es-bristol-hero-toggle-text');
            if (textEl) {
                textEl.textContent = isExpanded ? '<?php echo esc_js(__('Info', 'ensemble')); ?>' : '<?php echo esc_js(__('Zurück', 'ensemble')); ?>';
            }
        });
    }
    
    // Related scroll
    const scroll = document.querySelector('.es-bristol-related-scroll');
    const prev = document.querySelector('.es-related-prev');
    const next = document.querySelector('.es-related-next');
    
    prev?.addEventListener('click', () => scroll.scrollBy({ left: -350, behavior: 'smooth' }));
    next?.addEventListener('click', () => scroll.scrollBy({ left: 350, behavior: 'smooth' }));
    
    // ========================================
    // SMOOTH SCROLL FOR HERO BUTTONS
    // ========================================
    document.querySelectorAll('.es-bristol-hero-btn[href^="#"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const target = document.querySelector(targetId);
            
            if (target) {
                // Get header height for offset (if sticky header exists)
                const headerHeight = document.querySelector('.site-header')?.offsetHeight || 
                                     document.querySelector('header')?.offsetHeight || 80;
                
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                // Optional: Add highlight effect
                target.style.transition = 'box-shadow 0.3s ease';
                target.style.boxShadow = '0 0 0 4px var(--es-primary, #ff5722)';
                setTimeout(() => {
                    target.style.boxShadow = 'none';
                }, 1500);
            }
        });
    });
})();
</script>

<?php get_footer(); ?>
