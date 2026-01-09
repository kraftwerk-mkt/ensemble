<?php
/**
 * Template: Pure Single Artist
 * Ultra-minimal artist profile
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

$artist_id = get_the_ID();
$title = get_the_title();
$content = get_the_content();
$featured_image = get_the_post_thumbnail_url($artist_id, 'full');

// Meta
$genre = get_post_meta($artist_id, 'es_artist_genre', true);
$website = get_post_meta($artist_id, 'es_artist_website', true);
$facebook = get_post_meta($artist_id, 'es_artist_facebook', true);
$instagram = get_post_meta($artist_id, 'es_artist_instagram', true);
$spotify = get_post_meta($artist_id, 'es_artist_spotify', true);

// Get current mode
$current_mode = 'light';
if (class_exists('ES_Layout_Sets')) {
    $current_mode = ES_Layout_Sets::get_active_mode();
}

// Upcoming events
$upcoming_events = array();
if (function_exists('es_get_artist_events')) {
    $upcoming_events = es_get_artist_events($artist_id, 6);
}
?>

<article class="es-pure-single-artist es-layout-pure <?php echo 'es-mode-' . esc_attr($current_mode); ?>">

    <!-- HERO IMAGE -->
    <?php if ($featured_image): ?>
    <div class="es-pure-hero">
        <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>">
    </div>
    <?php endif; ?>

    <!-- HEADER -->
    <header class="es-pure-header">
        <div class="es-pure-header-inner">
            
            <?php if ($genre): ?>
            <div class="es-pure-header-meta">
                <div class="es-pure-header-meta-item">
                    <span><?php echo esc_html($genre); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <h1 class="es-pure-title"><?php echo esc_html($title); ?></h1>
            
            <!-- Social Links -->
            <?php if ($website || $facebook || $instagram || $spotify): ?>
            <div class="es-pure-header-actions">
                <?php if ($website): ?>
                <a href="<?php echo esc_url($website); ?>" class="es-pure-btn es-pure-btn-ghost es-pure-btn-sm" target="_blank" rel="noopener">
                    <?php _e('Website', 'ensemble'); ?>
                </a>
                <?php endif; ?>
                
                <?php if ($spotify): ?>
                <a href="<?php echo esc_url($spotify); ?>" class="es-pure-btn es-pure-btn-ghost es-pure-btn-sm" target="_blank" rel="noopener">
                    Spotify
                </a>
                <?php endif; ?>
                
                <?php if ($instagram): ?>
                <a href="<?php echo esc_url($instagram); ?>" class="es-pure-btn es-pure-btn-ghost es-pure-btn-sm" target="_blank" rel="noopener">
                    Instagram
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        </div>
    </header>

    <!-- CONTENT -->
    <div class="es-pure-content-wrapper">
        <div class="es-pure-layout es-pure-layout--single">
            
            <main class="es-pure-main">
                
                <!-- Bio -->
                <?php if ($content): ?>
                <section class="es-pure-section">
                    <h2 class="es-pure-section-title"><?php _e('Biography', 'ensemble'); ?></h2>
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
                            $evt_location = is_array($evt) ? ($evt['location'] ?? '') : '';
                        ?>
                        <a href="<?php echo esc_url($evt_permalink); ?>" class="es-pure-lineup-item">
                            <div class="es-pure-lineup-info">
                                <div class="es-pure-lineup-name"><?php echo esc_html($evt_title); ?></div>
                                <?php if ($evt_location): ?>
                                <div class="es-pure-lineup-meta"><?php echo esc_html($evt_location); ?></div>
                                <?php endif; ?>
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
