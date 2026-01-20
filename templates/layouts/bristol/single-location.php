<?php
/**
 * Single Location Template - Bristol Editorial Style
 * Asymmetric split layout
 * Hero Info Toggle for quick access to details
 * 
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 2.1.0
 */
if (!defined('ABSPATH')) exit;

get_header();

$location_id = get_the_ID();
$location = es_load_location_data($location_id);

// Image
$has_image = has_post_thumbnail();
$image_url = '';
if (!$has_image && !empty($location['featured_image'])) {
    $image_url = $location['featured_image'];
    $has_image = true;
}

// Category
$location_cats = get_the_terms($location_id, 'location_category');
$category = ($location_cats && !is_wp_error($location_cats)) ? $location_cats[0]->name : '';

// Full address
$full_address = '';
if (!empty($location['address'])) {
    $full_address = $location['address'];
    if (!empty($location['postal_code']) || !empty($location['city'])) {
        $full_address .= ', ' . trim($location['postal_code'] . ' ' . $location['city']);
    }
}

// Theme mode
$mode_class = isset($_COOKIE['es_bristol_mode']) && $_COOKIE['es_bristol_mode'] === 'light' ? 'es-mode-light' : '';
?>

<div class="es-bristol <?php echo esc_attr($mode_class); ?>">

    <?php 
    // Hook: Before location
    if (function_exists('ensemble_before_location')) {
        ensemble_before_location($location_id);
    }
    ?>

    <!-- HERO: Split Layout with Info Toggle -->
    <header class="es-bristol-single-hero es-bristol-hero-toggleable">
        <!-- Left: Image -->
        <div class="es-bristol-single-hero-media">
            <?php if (has_post_thumbnail()): ?>
                <?php the_post_thumbnail('full'); ?>
            <?php elseif ($image_url): ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title_attribute(); ?>">
            <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--es-surface) 0%, var(--es-bg-alt) 100%);display:flex;align-items:center;justify-content:center;">
                    <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="var(--es-text-muted)" stroke-width="0.5" opacity="0.3">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
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
                <?php if ($category): ?>
                <span class="es-bristol-single-hero-badge"><span><?php echo esc_html($category); ?></span></span>
                <?php endif; ?>
                
                <h1 class="es-bristol-single-hero-title"><?php the_title(); ?></h1>
                
                <?php if (has_excerpt()): ?>
                <p class="es-bristol-single-hero-subtitle"><?php echo get_the_excerpt(); ?></p>
                <?php endif; ?>
                
                <!-- Quick Info (visible on title panel) -->
                <div class="es-bristol-hero-quick-info">
                    <?php if (!empty($location['city'])): ?>
                    <span class="es-bristol-hero-quick-item">
                        <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo esc_html($location['city']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($location['capacity'])): ?>
                    <span class="es-bristol-hero-quick-item">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <?php echo esc_html(number_format($location['capacity'], 0, ',', '.')); ?> <?php _e('Personen', 'ensemble'); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- Website Link (visible on title panel) -->
                <?php if (!empty($location['website'])): ?>
                <div class="es-bristol-single-hero-social">
                    <a href="<?php echo esc_url($location['website']); ?>" target="_blank" rel="noopener" title="Website">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Panel 2: Info Details (Toggle) -->
            <div class="es-bristol-hero-panel es-bristol-hero-panel--info">
                <h2 class="es-bristol-hero-info-heading"><?php _e('Location Details', 'ensemble'); ?></h2>
                
                <?php 
                // Description preview in info panel
                $desc_text = !empty($location['description']) ? $location['description'] : (!empty($location['content']) ? $location['content'] : '');
                if ($desc_text): 
                ?>
                <div class="es-bristol-hero-info-description">
                    <?php echo wp_kses_post(wp_trim_words($desc_text, 60, '...')); ?>
                </div>
                <?php endif; ?>
                
                <div class="es-bristol-single-hero-meta">
                    <?php if (!empty($location['address'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Adresse', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html($location['address']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['postal_code']) || !empty($location['city'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Stadt', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html(trim($location['postal_code'] . ' ' . $location['city'])); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['capacity'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Kapazität', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html(number_format($location['capacity'], 0, ',', '.')); ?> <?php _e('Personen', 'ensemble'); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['phone'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Telefon', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value">
                                <a href="tel:<?php echo esc_attr($location['phone']); ?>" class="es-bristol-info-link"><?php echo esc_html($location['phone']); ?></a>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['email'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('E-Mail', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value">
                                <a href="mailto:<?php echo esc_attr($location['email']); ?>" class="es-bristol-info-link"><?php echo esc_html($location['email']); ?></a>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <?php if (!empty($location['lat']) && !empty($location['lng'])): ?>
                <div class="es-bristol-hero-info-actions">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr($location['lat'] . ',' . $location['lng']); ?>" 
                       class="es-bristol-btn es-bristol-btn-primary" target="_blank" rel="noopener">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                        </svg>
                        <?php _e('Route planen', 'ensemble'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </header>
    
    <?php 
    // Hook: Location header
    if (function_exists('ensemble_location_header')) {
        ensemble_location_header($location_id);
    }
    ?>

    <!-- BODY -->
    <div class="es-bristol-body">
        <div class="es-bristol-container">
            <div class="es-bristol-layout">
                
                <!-- Main Content -->
                <main class="es-bristol-main">
                    
                    <?php 
                    // Hook: Before location content
                    if (function_exists('ensemble_before_location_content')) {
                        ensemble_before_location_content($location_id);
                    }
                    ?>
                    
                    <?php 
                    // Description - Support multiple data sources
                    $has_description = !empty($location['description']) || !empty($location['content']) || get_the_content();
                    if ($has_description): 
                    ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Über die Location', 'ensemble'); ?></h2>
                        <div class="es-bristol-prose">
                            <?php 
                            if (!empty($location['description'])) {
                                echo wpautop($location['description']);
                            } elseif (!empty($location['content'])) {
                                echo wp_kses_post($location['content']);
                            } else {
                                the_content();
                            }
                            ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php 
                    // Hook: After location description
                    if (function_exists('ensemble_after_location_description')) {
                        ensemble_after_location_description($location_id);
                    }
                    
                    // Direct action hook for Floor Plan compatibility
                    do_action('ensemble_location_after_details', $location_id);
                    ?>
                    
                    <?php 
                    // Hook: Location catalog (for menu/offerings)
                    if (function_exists('ensemble_location_catalog')) {
                        ensemble_location_catalog($location_id);
                    }
                    ?>
                    
                    <?php 
                    // Hook: Location events (before list)
                    if (function_exists('ensemble_location_events')) {
                        ensemble_location_events($location_id);
                    }
                    ?>
                    
                    <!-- Upcoming Events at this location -->
                    <?php 
                    // Query events at this location
                    $events = new WP_Query(array(
                        'post_type' => ensemble_get_post_type('event'),
                        'posts_per_page' => 10,
                        'meta_query' => array(
                            array(
                                'key' => '_event_location',
                                'value' => $location_id,
                                'compare' => '='
                            )
                        ),
                        'meta_key' => '_event_date',
                        'orderby' => 'meta_value',
                        'order' => 'ASC'
                    ));
                    if ($events->have_posts()): 
                    ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Kommende Events', 'ensemble'); ?></h2>
                        <div class="es-bristol-events-list">
                            <?php while ($events->have_posts()): $events->the_post(); 
                                $ev = es_load_event_data(get_the_ID());
                                $timestamp = !empty($ev['date']) ? strtotime($ev['date']) : 0;
                            ?>
                            <a href="<?php the_permalink(); ?>" class="es-bristol-event-row">
                                <div class="es-bristol-event-date">
                                    <span class="es-bristol-event-date-day"><?php echo $timestamp ? date_i18n('j', $timestamp) : '—'; ?></span>
                                    <span class="es-bristol-event-date-month"><?php echo $timestamp ? date_i18n('M', $timestamp) : ''; ?></span>
                                </div>
                                <div class="es-bristol-event-info">
                                    <h4><?php the_title(); ?></h4>
                                    <?php if ($timestamp): ?>
                                    <p><?php echo date_i18n('l, H:i', $timestamp); ?> Uhr</p>
                                    <?php endif; ?>
                                </div>
                                <span class="es-bristol-event-arrow">
                                    <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </span>
                            </a>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php 
                    // Hook: After location events
                    if (function_exists('ensemble_after_location_events')) {
                        ensemble_after_location_events($location_id, $events ?? null);
                    }
                    ?>
                    
                    <?php 
                    // Hook: Location map
                    if (function_exists('ensemble_location_map')) {
                        ensemble_location_map($location_id, $location_id);
                    }
                    ?>
                    
                    <!-- Map (fallback if no addon) -->
                    <?php if (!empty($location['lat']) && !empty($location['lng']) && (!function_exists('ensemble_has_addon_hook') || !ensemble_has_addon_hook('location_map'))): ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Anfahrt', 'ensemble'); ?></h2>
                        <div class="es-bristol-map-wrapper">
                            <?php echo do_shortcode('[ensemble_map lat="' . $location['lat'] . '" lng="' . $location['lng'] . '" zoom="15"]'); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php 
                    // Hook: Location gallery
                    if (function_exists('ensemble_location_gallery')) {
                        ensemble_location_gallery($location_id, $location['gallery'] ?? array());
                    }
                    ?>
                    
                    <!-- Gallery (fallback if no hook output) -->
                    <?php if (!empty($location['gallery']) && (!function_exists('ensemble_has_addon_hook') || !ensemble_has_addon_hook('location_gallery'))): ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Galerie', 'ensemble'); ?></h2>
                        <div class="es-bristol-gallery">
                            <?php foreach ($location['gallery'] as $image): ?>
                            <div class="es-bristol-gallery-item">
                                <img src="<?php echo esc_url($image['url']); ?>" alt="">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="es-bristol-aside">
                    
                    <?php 
                    // Hook: Location sidebar
                    if (function_exists('ensemble_location_sidebar')) {
                        ensemble_location_sidebar($location_id);
                    }
                    ?>
                    
                    <div class="es-bristol-info-card">
                        <h4 class="es-bristol-info-card-title"><?php _e('Details', 'ensemble'); ?></h4>
                        
                        <?php if (!empty($location['address'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Adresse', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($location['address']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['postal_code']) || !empty($location['city'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Ort', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html(trim($location['postal_code'] . ' ' . $location['city'])); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['country'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Land', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($location['country']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['capacity'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Kapazität', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html(number_format($location['capacity'], 0, ',', '.')); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['phone'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Telefon', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value">
                                <a href="tel:<?php echo esc_attr($location['phone']); ?>" class="es-bristol-info-link"><?php echo esc_html($location['phone']); ?></a>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['email'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('E-Mail', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value">
                                <a href="mailto:<?php echo esc_attr($location['email']); ?>" class="es-bristol-info-link"><?php echo esc_html($location['email']); ?></a>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($location['website'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Website', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value">
                                <a href="<?php echo esc_url($location['website']); ?>" class="es-bristol-info-link" target="_blank" rel="noopener"><?php echo esc_html(str_replace(['https://', 'http://', 'www.'], '', $location['website'])); ?></a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Hook: Location contact
                    if (function_exists('ensemble_location_contact')) {
                        ensemble_location_contact($location_id);
                    }
                    ?>
                    
                    <!-- Directions Button -->
                    <?php if (!empty($location['lat']) && !empty($location['lng'])): ?>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr($location['lat'] . ',' . $location['lng']); ?>" 
                       class="es-bristol-btn es-bristol-btn-outline" 
                       style="width:100%;margin-top:24px;"
                       target="_blank" rel="noopener">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                        </svg>
                        <?php _e('Route planen', 'ensemble'); ?>
                    </a>
                    <?php endif; ?>
                    
                </aside>
                
            </div>
        </div>
    </div>
    
    <?php 
    // Hook: Location footer
    if (function_exists('ensemble_location_footer')) {
        ensemble_location_footer($location_id);
    }
    ?>
    
    <?php 
    // Hook: After location
    if (function_exists('ensemble_after_location')) {
        ensemble_after_location($location_id, $location);
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
        document.cookie = 'es_bristol_mode=' + (root.classList.contains('es-mode-light') ? 'light' : 'dark') + ';path=/;max-age=31536000';
    });
    
    // Hero Info Toggle
    const infoToggle = document.querySelector('.es-bristol-hero-info-toggle');
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
})();
</script>

<?php get_footer(); ?>
