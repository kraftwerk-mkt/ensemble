<?php
/**
 * Single Artist Template - Bristol Editorial Style
 * Asymmetric split layout
 * Hero Info Toggle for quick access to details
 * 
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 2.1.0
 */
if (!defined('ABSPATH')) exit;

get_header();

$artist_id = get_the_ID();
$artist = es_load_artist_data($artist_id);

// Image
$has_image = has_post_thumbnail();
$image_url = '';
if (!$has_image && !empty($artist['featured_image'])) {
    $image_url = $artist['featured_image'];
    $has_image = true;
}

// Theme mode
$mode_class = isset($_COOKIE['es_bristol_mode']) && $_COOKIE['es_bristol_mode'] === 'light' ? 'es-mode-light' : '';

// Social links config
$socials = array(
    'website' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
    'facebook' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
    'twitter' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>',
    'spotify' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 15c3-1 6-1 8 1"/><path d="M7 12c4-1 7-1 10 1"/><path d="M6 9c5-1 9-1 12 2"/></svg>',
    'youtube' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>',
    'soundcloud' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6m4 6v-8m4 8V4m4 14v-6m4 6V8"/></svg>',
);

$has_social = false;
foreach ($socials as $key => $icon) {
    if (!empty($artist[$key])) { $has_social = true; break; }
}
?>

<div class="es-bristol <?php echo esc_attr($mode_class); ?>">

    <?php 
    // Hook: Before artist
    if (function_exists('ensemble_before_artist')) {
        ensemble_before_artist($artist_id);
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
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
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
                <?php if (!empty($artist['genre'])): ?>
                <span class="es-bristol-single-hero-badge"><span><?php echo esc_html($artist['genre']); ?></span></span>
                <?php endif; ?>
                
                <h1 class="es-bristol-single-hero-title"><?php the_title(); ?></h1>
                
                <?php if (!empty($artist['tagline']) || has_excerpt()): ?>
                <p class="es-bristol-single-hero-subtitle"><?php echo !empty($artist['tagline']) ? esc_html($artist['tagline']) : get_the_excerpt(); ?></p>
                <?php endif; ?>
                
                <!-- Quick Info (visible on title panel) -->
                <div class="es-bristol-hero-quick-info">
                    <?php if (!empty($artist['origin'])): ?>
                    <span class="es-bristol-hero-quick-item">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        <?php echo esc_html($artist['origin']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($artist['label'])): ?>
                    <span class="es-bristol-hero-quick-item">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                        <?php echo esc_html($artist['label']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- Social Links (visible on title panel) -->
                <?php if ($has_social): ?>
                <div class="es-bristol-single-hero-social">
                    <?php foreach ($socials as $key => $icon): ?>
                        <?php if (!empty($artist[$key])): ?>
                        <a href="<?php echo esc_url($artist[$key]); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr(ucfirst($key)); ?>">
                            <?php echo $icon; ?>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Panel 2: Info Details (Toggle) -->
            <div class="es-bristol-hero-panel es-bristol-hero-panel--info">
                <h2 class="es-bristol-hero-info-heading"><?php _e('Künstler Details', 'ensemble'); ?></h2>
                
                <?php 
                // Bio preview in info panel
                $bio_text = !empty($artist['bio']) ? $artist['bio'] : (!empty($artist['content']) ? $artist['content'] : '');
                if ($bio_text): 
                ?>
                <div class="es-bristol-hero-info-description">
                    <?php echo wp_kses_post(wp_trim_words($bio_text, 60, '...')); ?>
                </div>
                <?php endif; ?>
                
                <div class="es-bristol-single-hero-meta">
                    <?php if (!empty($artist['genre'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Genre', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html($artist['genre']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artist['origin'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Herkunft', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html($artist['origin']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artist['label'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Label', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value"><?php echo esc_html($artist['label']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artist['booking_contact'])): ?>
                    <div class="es-bristol-single-hero-meta-item">
                        <div class="es-bristol-single-hero-meta-icon">
                            <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div class="es-bristol-single-hero-meta-text">
                            <span class="es-bristol-single-hero-meta-label"><?php _e('Booking', 'ensemble'); ?></span>
                            <span class="es-bristol-single-hero-meta-value">
                                <a href="mailto:<?php echo esc_attr($artist['booking_contact']); ?>" class="es-bristol-info-link"><?php echo esc_html($artist['booking_contact']); ?></a>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Social Links in Info Panel -->
                <?php if ($has_social): ?>
                <div class="es-bristol-single-hero-social" style="margin-top: 32px;">
                    <?php foreach ($socials as $key => $icon): ?>
                        <?php if (!empty($artist[$key])): ?>
                        <a href="<?php echo esc_url($artist[$key]); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr(ucfirst($key)); ?>">
                            <?php echo $icon; ?>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </header>
    
    <?php 
    // Hook: Artist header
    if (function_exists('ensemble_artist_header')) {
        ensemble_artist_header($artist_id);
    }
    ?>

    <!-- BODY -->
    <div class="es-bristol-body">
        <div class="es-bristol-container">
            <div class="es-bristol-layout">
                
                <!-- Main Content -->
                <main class="es-bristol-main">
                    
                    <?php 
                    // Hook: Before artist content
                    if (function_exists('ensemble_before_artist_content')) {
                        ensemble_before_artist_content($artist_id);
                    }
                    ?>
                    
                    <?php 
                    // Bio - Support multiple data sources
                    $has_bio = !empty($artist['bio']) || !empty($artist['content']) || get_the_content();
                    if ($has_bio): 
                    ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Biografie', 'ensemble'); ?></h2>
                        <div class="es-bristol-prose">
                            <?php 
                            if (!empty($artist['bio'])) {
                                echo wpautop($artist['bio']);
                            } elseif (!empty($artist['content'])) {
                                echo wp_kses_post($artist['content']);
                            } else {
                                the_content();
                            }
                            ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php 
                    // Hook: After artist bio
                    if (function_exists('ensemble_after_artist_bio')) {
                        ensemble_after_artist_bio($artist_id);
                    }
                    ?>
                    
                    <?php 
                    // Hook: Artist events (before list)
                    if (function_exists('ensemble_artist_events')) {
                        ensemble_artist_events($artist_id);
                    }
                    ?>
                    
                    <!-- Upcoming Events -->
                    <?php 
                    // Query events where this artist is linked
                    $events = new WP_Query(array(
                        'post_type' => ensemble_get_post_type('event'),
                        'posts_per_page' => 10,
                        'meta_query' => array(
                            array(
                                'key' => '_event_artists',
                                'value' => $artist_id,
                                'compare' => 'LIKE'
                            )
                        ),
                        'meta_key' => '_event_date',
                        'orderby' => 'meta_value',
                        'order' => 'ASC',
                        'meta_compare' => '>=',
                        'meta_value' => date('Y-m-d')
                    ));
                    if ($events->have_posts()): 
                    ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Kommende Auftritte', 'ensemble'); ?></h2>
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
                                    <?php if (!empty($ev['location']['name'])): ?>
                                    <p><?php echo esc_html($ev['location']['name']); ?></p>
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
                    // Hook: After artist events
                    if (function_exists('ensemble_after_artist_events')) {
                        ensemble_after_artist_events($artist_id, $events ?? null);
                    }
                    ?>
                    
                    <?php 
                    // Hook: Artist gallery
                    if (function_exists('ensemble_artist_gallery')) {
                        ensemble_artist_gallery($artist_id, $artist['gallery'] ?? array());
                    }
                    ?>
                    
                    <!-- Gallery (fallback if no hook output) -->
                    <?php if (!empty($artist['gallery']) && (!function_exists('ensemble_has_addon_hook') || !ensemble_has_addon_hook('artist_gallery'))): ?>
                    <section class="es-bristol-section">
                        <h2 class="es-bristol-section-title"><?php _e('Galerie', 'ensemble'); ?></h2>
                        <div class="es-bristol-gallery">
                            <?php foreach ($artist['gallery'] as $image): ?>
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
                    // Hook: Artist sidebar
                    if (function_exists('ensemble_artist_sidebar')) {
                        ensemble_artist_sidebar($artist_id);
                    }
                    ?>
                    
                    <div class="es-bristol-info-card">
                        <h4 class="es-bristol-info-card-title"><?php _e('Infos', 'ensemble'); ?></h4>
                        
                        <?php if (!empty($artist['genre'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Genre', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($artist['genre']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($artist['origin'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Herkunft', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($artist['origin']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($artist['label'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Label', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value"><?php echo esc_html($artist['label']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($artist['booking_contact'])): ?>
                        <div class="es-bristol-info-row">
                            <span class="es-bristol-info-label"><?php _e('Booking', 'ensemble'); ?></span>
                            <span class="es-bristol-info-value">
                                <a href="mailto:<?php echo esc_attr($artist['booking_contact']); ?>" class="es-bristol-info-link"><?php echo esc_html($artist['booking_contact']); ?></a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                </aside>
                
            </div>
        </div>
    </div>
    
    <?php 
    // Hook: Artist footer
    if (function_exists('ensemble_artist_footer')) {
        ensemble_artist_footer($artist_id);
    }
    ?>
    
    <?php 
    // Hook: After artist
    if (function_exists('ensemble_after_artist')) {
        ensemble_after_artist($artist_id, $artist);
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
