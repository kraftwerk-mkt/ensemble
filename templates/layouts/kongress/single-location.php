<?php
/**
 * Template: Kongress Single Location
 * 
 * Professional venue/location page
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

$location_id = get_the_ID();

// Basic Info
$name = get_the_title();
$content = get_the_content();
$featured_image = get_the_post_thumbnail_url($location_id, 'full');

// Location Details
$address = get_post_meta($location_id, 'location_address', true);
$city = get_post_meta($location_id, 'location_city', true);
$postal_code = get_post_meta($location_id, 'location_postal_code', true);
$country = get_post_meta($location_id, 'location_country', true);
$website = get_post_meta($location_id, 'location_website', true);
$phone = get_post_meta($location_id, 'location_phone', true);
$email = get_post_meta($location_id, 'location_email', true);

// Coordinates for map
$lat = get_post_meta($location_id, 'location_lat', true);
$lng = get_post_meta($location_id, 'location_lng', true);

// Location types
$location_types = get_the_terms($location_id, 'ensemble_location_type');

// Get events at this location
$location_events = get_posts(array(
    'post_type'      => ensemble_get_post_type(),
    'posts_per_page' => 6,
    'meta_query'     => array(
        array(
            'key'     => 'event_location',
            'value'   => $location_id,
            'compare' => '=',
        ),
    ),
    'meta_key'       => 'event_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
));

// Gallery
$gallery = get_post_meta($location_id, 'location_gallery', true);
?>

<article class="es-kongress-single-location es-layout-kongress" id="es-location-<?php echo esc_attr($location_id); ?>">

    <?php do_action('ensemble_before_single_location', $location_id); ?>

    <!-- HERO -->
    <section class="es-kongress-hero" style="min-height: 400px;">
        <?php if ($featured_image): ?>
        <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($name); ?>" class="es-kongress-hero-bg">
        <?php endif; ?>
        <div class="es-kongress-hero-overlay"></div>
        
        <div class="es-kongress-hero-content">
            <?php if ($location_types && !is_wp_error($location_types)): ?>
            <div class="es-kongress-hero-meta">
                <div class="es-kongress-hero-meta-item">
                    <?php echo esc_html($location_types[0]->name); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <h1 class="es-kongress-hero-title" style="font-size: calc(var(--ensemble-hero-size) * 0.8);"><?php echo esc_html($name); ?></h1>
            
            <?php if ($address || $city): ?>
            <p class="es-kongress-hero-subtitle">
                <?php 
                $full_address = array();
                if ($address) $full_address[] = $address;
                if ($postal_code) $full_address[] = $postal_code;
                if ($city) $full_address[] = $city;
                if ($country) $full_address[] = $country;
                echo esc_html(implode(', ', $full_address));
                ?>
            </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <div class="es-kongress-content-wrapper">
        <div class="es-kongress-grid">
            
            <!-- MAIN -->
            <main class="es-kongress-main">
                
                <!-- Description -->
                <?php if ($content): ?>
                <section class="es-kongress-section es-kongress-animate">
                    <div class="es-kongress-section-header">
                        <div class="es-kongress-section-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <h2 class="es-kongress-section-title"><?php _e('Über den Veranstaltungsort', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-kongress-content">
                        <?php echo wp_kses_post($content); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Events at this location -->
                <?php if (!empty($location_events)): ?>
                <section class="es-kongress-section es-kongress-animate">
                    <div class="es-kongress-section-header">
                        <div class="es-kongress-section-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <h2 class="es-kongress-section-title"><?php _e('Veranstaltungen', 'ensemble'); ?></h2>
                    </div>
                    
                    <div style="display: grid; gap: 16px;">
                        <?php foreach ($location_events as $event):
                            $event_date = ensemble_get_field('event_date', $event->ID);
                            $formatted_date = $event_date ? date_i18n('j. F Y', strtotime($event_date)) : '';
                            $event_time = ensemble_get_field('event_time', $event->ID);
                        ?>
                        <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" class="es-kongress-session" style="text-decoration: none;">
                            <div class="es-kongress-session-header">
                                <h3 class="es-kongress-session-title"><?php echo esc_html($event->post_title); ?></h3>
                            </div>
                            <div style="display: flex; gap: 24px; margin-top: 8px; font-size: var(--ensemble-small-size); color: var(--ensemble-text-secondary);">
                                <?php if ($formatted_date): ?>
                                <span><?php echo esc_html($formatted_date); ?></span>
                                <?php endif; ?>
                                <?php if ($event_time): ?>
                                <span><?php echo esc_html($event_time); ?> <?php _e('Uhr', 'ensemble'); ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <?php 
                // Hook: Map
                do_action('ensemble_location_map', $location_id);
                
                if (function_exists('ensemble_after_location')) {
                    ensemble_after_location($location_id);
                }
                ?>
                
                <?php 
                // CONTACTS SECTION (Staff Addon)
                $contact_ids = get_post_meta($location_id, '_es_location_contacts', true);
                
                if (!empty($contact_ids) && is_array($contact_ids)):
                    // Get staff data - try multiple methods
                    $contacts = array();
                    
                    // Method 1: Via Addon Manager (gets all fields)
                    if (class_exists('ES_Addon_Manager')) {
                        $staff_addon = ES_Addon_Manager::get_active_addon('staff');
                        if ($staff_addon && method_exists($staff_addon, 'get_staff_manager')) {
                            $staff_manager = $staff_addon->get_staff_manager();
                            foreach ($contact_ids as $contact_id) {
                                $person = $staff_manager->get_staff($contact_id);
                                if ($person) {
                                    $contacts[] = $person;
                                }
                            }
                        }
                    }
                    
                    // Method 2: Direct post query fallback
                    if (empty($contacts)) {
                        foreach ($contact_ids as $contact_id) {
                            $post = get_post($contact_id);
                            if ($post && $post->post_status === 'publish') {
                                $contacts[] = array(
                                    'id' => $post->ID,
                                    'name' => $post->post_title,
                                    'position' => get_post_meta($post->ID, '_es_staff_position', true),
                                    'email' => get_post_meta($post->ID, '_es_staff_email', true),
                                    'phone_numbers' => get_post_meta($post->ID, '_es_staff_phone_numbers', true),
                                    'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium'),
                                );
                            }
                        }
                    }
                    
                    if (!empty($contacts)):
                        $contact_label = function_exists('ensemble_label') ? ensemble_label('staff', true) : __('Contact Persons', 'ensemble');
                ?>
                <section class="es-kongress-section es-kongress-animate" style="margin-top: 48px;">
                    <div class="es-kongress-section-header">
                        <div class="es-kongress-section-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <h2 class="es-kongress-section-title"><?php echo esc_html($contact_label); ?></h2>
                    </div>
                    
                    <div class="es-kongress-contacts-grid">
                        <?php foreach ($contacts as $person): ?>
                        <div class="es-kongress-contact-card">
                            <?php if (!empty($person['featured_image'])): ?>
                            <div class="es-kongress-contact-image">
                                <img src="<?php echo esc_url($person['featured_image']); ?>" alt="<?php echo esc_attr($person['name']); ?>">
                            </div>
                            <?php else: ?>
                            <div class="es-kongress-contact-image es-kongress-contact-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <?php endif; ?>
                            <div class="es-kongress-contact-info">
                                <h4 class="es-kongress-contact-name"><?php echo esc_html($person['name']); ?></h4>
                                
                                <?php if (!empty($person['position'])): ?>
                                <p class="es-kongress-contact-position"><?php echo esc_html($person['position']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($person['department'])): ?>
                                <p class="es-kongress-contact-department"><?php echo esc_html($person['department']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($person['responsibility'])): ?>
                                <p class="es-kongress-contact-responsibility"><?php echo esc_html($person['responsibility']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($person['office_hours'])): ?>
                                <p class="es-kongress-contact-hours">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12,6 12,12 16,14"/>
                                    </svg>
                                    <?php echo esc_html($person['office_hours']); ?>
                                </p>
                                <?php endif; ?>
                                
                                <div class="es-kongress-contact-links">
                                    <?php if (!empty($person['email'])): ?>
                                    <a href="mailto:<?php echo esc_attr($person['email']); ?>" class="es-kongress-contact-email" title="<?php echo esc_attr($person['email']); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                            <polyline points="22,6 12,13 2,6"/>
                                        </svg>
                                        <span><?php echo esc_html($person['email']); ?></span>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Phone numbers - support both 'phones' array and legacy 'phone_numbers'
                                    $phones = !empty($person['phones']) ? $person['phones'] : array();
                                    if (empty($phones) && !empty($person['phone_numbers'])) {
                                        foreach ((array)$person['phone_numbers'] as $num) {
                                            $phones[] = array('type' => 'office', 'number' => $num);
                                        }
                                    }
                                    if (!empty($phones) && is_array($phones)):
                                        foreach ($phones as $phone):
                                            if (empty($phone['number'])) continue;
                                            $phone_type = isset($phone['type']) ? $phone['type'] : 'office';
                                            $type_labels = array(
                                                'office' => __('Office', 'ensemble'),
                                                'mobile' => __('Mobile', 'ensemble'),
                                                'fax' => __('Fax', 'ensemble')
                                            );
                                            $type_label = isset($type_labels[$phone_type]) ? $type_labels[$phone_type] : $type_labels['office'];
                                    ?>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone['number'])); ?>" class="es-kongress-contact-phone" title="<?php echo esc_attr($type_label); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                        </svg>
                                        <span><?php echo esc_html($phone['number']); ?><?php if ($phone_type !== 'office'): ?> <small>(<?php echo esc_html($type_label); ?>)</small><?php endif; ?></span>
                                    </a>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </div>
                                
                                <?php 
                                // Social Links
                                $has_social = !empty($person['website']) || !empty($person['linkedin']) || !empty($person['twitter']);
                                if ($has_social): 
                                ?>
                                <div class="es-kongress-contact-social">
                                    <?php if (!empty($person['website'])): ?>
                                    <a href="<?php echo esc_url($person['website']); ?>" target="_blank" rel="noopener noreferrer" title="<?php esc_attr_e('Website', 'ensemble'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 16px; height: 16px;">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="2" y1="12" x2="22" y2="12"/>
                                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($person['linkedin'])): ?>
                                    <a href="<?php echo esc_url($person['linkedin']); ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn">
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 16px; height: 16px;">
                                            <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($person['twitter'])): ?>
                                    <a href="<?php echo esc_url($person['twitter']); ?>" target="_blank" rel="noopener noreferrer" title="Twitter / X">
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 16px; height: 16px;">
                                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php 
                    endif;
                endif;
                ?>
                
                <?php 
                // ADDON HOOK: Downloads, Floor Plans, etc.
                if (class_exists('ES_Addon_Manager')) {
                    ES_Addon_Manager::do_addon_hook('ensemble_after_location_content', $location_id);
                }
                ?>
                
            </main>
            
            <!-- SIDEBAR -->
            <aside class="es-kongress-sidebar">
                
                <!-- Contact Info -->
                <div class="es-kongress-sidebar-block">
                    <h3 class="es-kongress-sidebar-title"><?php _e('Kontakt', 'ensemble'); ?></h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        
                        <?php if ($address || $city): ?>
                        <div style="display: flex; gap: 12px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-primary)" stroke-width="1.5" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <div style="font-size: var(--ensemble-body-size); color: var(--ensemble-text); line-height: 1.5;">
                                <?php 
                                if ($address) echo esc_html($address) . '<br>';
                                if ($postal_code || $city) {
                                    echo esc_html(trim($postal_code . ' ' . $city));
                                }
                                if ($country) echo '<br>' . esc_html($country);
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($phone): ?>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-primary)" stroke-width="1.5" style="width: 20px; height: 20px;">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <a href="tel:<?php echo esc_attr($phone); ?>" style="color: var(--ensemble-text); text-decoration: none;">
                                <?php echo esc_html($phone); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($email): ?>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-primary)" stroke-width="1.5" style="width: 20px; height: 20px;">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <a href="mailto:<?php echo esc_attr($email); ?>" style="color: var(--ensemble-text); text-decoration: none;">
                                <?php echo esc_html($email); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($website): ?>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-primary)" stroke-width="1.5" style="width: 20px; height: 20px;">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" style="color: var(--ensemble-text); text-decoration: none;">
                                <?php echo esc_html(str_replace(array('https://', 'http://', 'www.'), '', $website)); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
                
                <!-- Directions Button -->
                <?php if ($lat && $lng): ?>
                <div class="es-kongress-sidebar-block">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr($lat); ?>,<?php echo esc_attr($lng); ?>" 
                       class="es-kongress-btn es-kongress-btn-primary es-kongress-btn-block" 
                       target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                        </svg>
                        <?php _e('Route planen', 'ensemble'); ?>
                    </a>
                </div>
                <?php endif; ?>
                
            </aside>
            
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="es-kongress-footer" style="padding: var(--ensemble-section-spacing) 0;">
        <div class="es-kongress-content-wrapper">
            <?php 
            if (function_exists('ensemble_social_share')) {
                ensemble_social_share($location_id);
            }
            
            do_action('ensemble_after_single_location', $location_id);
            ?>
            
            <div style="text-align: center; margin-top: 48px;">
                <a href="javascript:history.back()" class="es-kongress-btn es-kongress-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <?php _e('Zurück', 'ensemble'); ?>
                </a>
            </div>
        </div>
    </footer>

</article>

<script>
(function() {
    var animateElements = document.querySelectorAll('.es-kongress-animate');
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, { threshold: 0.1 });
    
    animateElements.forEach(function(el) {
        observer.observe(el);
    });
})();
</script>

<?php get_footer(); ?>
