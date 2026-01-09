<?php
/**
 * Template: Pure Single Location
 * Ultra-minimal location profile
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

$location_id = get_the_ID();
$title = get_the_title();
$content = get_the_content();
$featured_image = get_the_post_thumbnail_url($location_id, 'full');

// Meta
$address = get_post_meta($location_id, 'es_location_address', true);
$city = get_post_meta($location_id, 'es_location_city', true);
$country = get_post_meta($location_id, 'es_location_country', true);
$website = get_post_meta($location_id, 'es_location_website', true);
$phone = get_post_meta($location_id, 'es_location_phone', true);
$capacity = get_post_meta($location_id, 'es_location_capacity', true);

// Get current mode
$current_mode = 'light';
if (class_exists('ES_Layout_Sets')) {
    $current_mode = ES_Layout_Sets::get_active_mode();
}

// Upcoming events
$upcoming_events = array();
if (function_exists('es_get_location_events')) {
    $upcoming_events = es_get_location_events($location_id, 6);
}
?>

<article class="es-pure-single-location es-layout-pure <?php echo 'es-mode-' . esc_attr($current_mode); ?>">

    <!-- HERO IMAGE -->
    <?php if ($featured_image): ?>
    <div class="es-pure-hero">
        <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>">
    </div>
    <?php endif; ?>

    <!-- HEADER -->
    <header class="es-pure-header">
        <div class="es-pure-header-inner">
            
            <?php if ($city || $country): ?>
            <div class="es-pure-header-meta">
                <div class="es-pure-header-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span>
                        <?php 
                        echo esc_html($city);
                        if ($city && $country) echo ', ';
                        echo esc_html($country);
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <h1 class="es-pure-title"><?php echo esc_html($title); ?></h1>
            
            <!-- Actions -->
            <div class="es-pure-header-actions">
                <?php if ($website): ?>
                <a href="<?php echo esc_url($website); ?>" class="es-pure-btn es-pure-btn-ghost" target="_blank" rel="noopener">
                    <?php _e('Website', 'ensemble'); ?>
                </a>
                <?php endif; ?>
                
                <?php if ($address): ?>
                <a href="https://maps.google.com/?q=<?php echo urlencode($address . ', ' . $city); ?>" class="es-pure-btn es-pure-btn-ghost" target="_blank" rel="noopener">
                    <?php _e('Directions', 'ensemble'); ?>
                </a>
                <?php endif; ?>
            </div>
            
        </div>
    </header>

    <!-- CONTENT -->
    <div class="es-pure-content-wrapper">
        <div class="es-pure-layout">
            
            <main class="es-pure-main">
                
                <!-- Description -->
                <?php if ($content): ?>
                <section class="es-pure-section">
                    <h2 class="es-pure-section-title"><?php _e('About', 'ensemble'); ?></h2>
                    <div class="es-pure-prose">
                        <?php echo wp_kses_post(wpautop($content)); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Upcoming Events -->
                <?php if (!empty($upcoming_events)): ?>
                <section class="es-pure-section">
                    <h2 class="es-pure-section-title"><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                    <div class="es-pure-lineup">
                        <?php foreach ($upcoming_events as $evt): 
                            $evt_title = is_array($evt) ? ($evt['title'] ?? '') : $evt->post_title;
                            $evt_permalink = is_array($evt) ? ($evt['permalink'] ?? '') : get_permalink($evt->ID);
                            $evt_date = is_array($evt) ? ($evt['formatted_date'] ?? '') : '';
                            $evt_image = is_array($evt) ? ($evt['featured_image'] ?? '') : get_the_post_thumbnail_url($evt->ID, 'thumbnail');
                        ?>
                        <a href="<?php echo esc_url($evt_permalink); ?>" class="es-pure-lineup-item">
                            <?php if ($evt_image): ?>
                            <div class="es-pure-lineup-image">
                                <img src="<?php echo esc_url($evt_image); ?>" alt="<?php echo esc_attr($evt_title); ?>">
                            </div>
                            <?php endif; ?>
                            <div class="es-pure-lineup-info">
                                <div class="es-pure-lineup-name"><?php echo esc_html($evt_title); ?></div>
                            </div>
                            <?php if ($evt_date): ?>
                            <div class="es-pure-lineup-time"><?php echo esc_html($evt_date); ?></div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
            </main>
            
            <!-- SIDEBAR -->
            <aside class="es-pure-sidebar">
                
                <!-- Location Details -->
                <div class="es-pure-sidebar-block">
                    <h3 class="es-pure-sidebar-title"><?php _e('Details', 'ensemble'); ?></h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if ($address): ?>
                        <div>
                            <div class="es-pure-text-xs es-pure-text-muted" style="margin-bottom: 4px;"><?php _e('Address', 'ensemble'); ?></div>
                            <div><?php echo esc_html($address); ?></div>
                            <?php if ($city): ?>
                            <div><?php echo esc_html($city); ?><?php if ($country) echo ', ' . esc_html($country); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($phone): ?>
                        <div>
                            <div class="es-pure-text-xs es-pure-text-muted" style="margin-bottom: 4px;"><?php _e('Phone', 'ensemble'); ?></div>
                            <div><a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($capacity): ?>
                        <div>
                            <div class="es-pure-text-xs es-pure-text-muted" style="margin-bottom: 4px;"><?php _e('Capacity', 'ensemble'); ?></div>
                            <div><?php echo esc_html($capacity); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </aside>
            
        </div>
    </div>

</article>

<?php 
// Include Pure mode script (once per page)
if (!defined('ES_PURE_MODE_SCRIPT_LOADED')) {
    define('ES_PURE_MODE_SCRIPT_LOADED', true);
    ?>
    <script id="es-pure-mode-script">
    (function(){var k='ensemble_pure_mode';function g(){try{return localStorage.getItem(k)||'light'}catch(e){return'light'}}function s(m){try{localStorage.setItem(k,m)}catch(e){}}function a(m){document.body.classList.remove('es-mode-light','es-mode-dark');document.body.classList.add('es-mode-'+m);document.querySelectorAll('.es-layout-pure,.es-pure-single-event,.es-pure-single-artist,.es-pure-single-location').forEach(function(el){el.classList.remove('es-mode-light','es-mode-dark');el.classList.add('es-mode-'+m)});document.querySelectorAll('.es-mode-toggle').forEach(function(t){var sun=t.querySelector('.es-icon-sun'),moon=t.querySelector('.es-icon-moon');if(sun&&moon){sun.style.display=m==='dark'?'block':'none';moon.style.display=m==='dark'?'none':'block'}})}function t(){var c=g(),n=c==='dark'?'light':'dark';s(n);a(n)}function c(){var b=document.createElement('button');b.className='es-mode-toggle';b.innerHTML='<svg class="es-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg><svg class="es-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';b.onclick=t;return b}function i(){if(!document.querySelector('.es-layout-pure,.es-pure-single-event,.es-pure-single-artist,.es-pure-single-location'))return;a(g());if(!document.querySelector('.es-mode-toggle'))document.body.appendChild(c())}document.documentElement.classList.add('es-mode-'+g());if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',i);else i();window.togglePureMode=t})();
    </script>
    <?php
}
?>

<?php get_footer(); ?>
