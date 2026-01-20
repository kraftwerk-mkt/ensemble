<?php
/**
 * Staff List Template
 * 
 * @package Ensemble
 * @subpackage Addons/Staff
 * @version 1.1.0
 * 
 * Variables:
 * @var array $staff    Array of staff members
 * @var array $atts     Shortcode attributes
 * @var int   $columns  Number of columns (not used in list layout)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Helper function to check show_* attributes (handles both 'yes'/'no' strings and booleans)
if (!function_exists('es_staff_show_attr')) {
    function es_staff_show_attr($atts, $key, $default = true) {
        if (!isset($atts[$key])) {
            return $default;
        }
        $value = $atts[$key];
        if (is_string($value)) {
            return in_array(strtolower($value), array('yes', '1', 'true'), true);
        }
        return (bool) $value;
    }
}

// Parse display options from attributes
$show_image        = es_staff_show_attr($atts, 'show_image', true);
$show_email        = es_staff_show_attr($atts, 'show_email', true);
$show_phone        = es_staff_show_attr($atts, 'show_phone', true);
$show_position     = es_staff_show_attr($atts, 'show_position', true);
$show_department   = es_staff_show_attr($atts, 'show_department', true);
$show_office_hours = es_staff_show_attr($atts, 'show_office_hours', false);
$show_social       = es_staff_show_attr($atts, 'show_social', false);
$show_responsibility = es_staff_show_attr($atts, 'show_responsibility', false);
$show_excerpt      = es_staff_show_attr($atts, 'show_excerpt', false);
?>

<div class="es-staff-list-wrapper">
    <div class="es-staff-list">
        <?php foreach ($staff as $person) : ?>
            <article class="es-staff-list-item" itemscope itemtype="https://schema.org/Person">
                <?php if ($show_image) : ?>
                    <div class="es-staff-list-item__image">
                        <?php if ($person['featured_image']) : ?>
                            <a href="<?php echo esc_url($person['permalink']); ?>">
                                <img src="<?php echo esc_url($person['featured_image']); ?>" 
                                     alt="<?php echo esc_attr($person['name']); ?>"
                                     itemprop="image">
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url($person['permalink']); ?>" class="es-staff-list-item__placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="es-staff-list-item__content">
                    <div class="es-staff-list-item__header">
                        <h3 class="es-staff-list-item__name" itemprop="name">
                            <a href="<?php echo esc_url($person['permalink']); ?>">
                                <?php echo esc_html($person['name']); ?>
                            </a>
                        </h3>
                        
                        <?php if ($show_position && !empty($person['position'])) : ?>
                            <span class="es-staff-list-item__position" itemprop="jobTitle">
                                <?php echo esc_html($person['position']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (($show_department && !empty($person['department'])) || ($show_responsibility && !empty($person['responsibility']))) : ?>
                        <div class="es-staff-list-item__meta">
                            <?php if ($show_department && !empty($person['department'])) : ?>
                                <span class="es-staff-list-item__department">
                                    <?php echo esc_html($person['department']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($show_responsibility && !empty($person['responsibility'])) : ?>
                                <span class="es-staff-list-item__responsibility">
                                    <?php echo esc_html($person['responsibility']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($show_office_hours && !empty($person['office_hours'])) : ?>
                        <div class="es-staff-list-item__office-hours">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                            <span><?php echo esc_html($person['office_hours']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($show_excerpt && !empty($person['excerpt'])) : ?>
                        <p class="es-staff-list-item__excerpt" itemprop="description">
                            <?php echo esc_html($person['excerpt']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="es-staff-list-item__contact">
                    <?php if ($show_email && !empty($person['email'])) : ?>
                        <a href="mailto:<?php echo esc_attr($person['email']); ?>" 
                           class="es-staff-list-item__email es-btn es-btn--outline"
                           itemprop="email">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <span><?php _e('Email', 'ensemble'); ?></span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($show_phone && !empty($person['phone'])) : ?>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $person['phone'])); ?>" 
                           class="es-staff-list-item__phone es-btn es-btn--outline"
                           itemprop="telephone">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                            <span><?php echo esc_html($person['phone']); ?></span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($show_social && (!empty($person['website']) || !empty($person['linkedin']) || !empty($person['twitter']))) : ?>
                        <div class="es-staff-list-item__social">
                            <?php if (!empty($person['website'])) : ?>
                                <a href="<?php echo esc_url($person['website']); ?>" target="_blank" rel="noopener noreferrer" title="<?php esc_attr_e('Website', 'ensemble'); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($person['linkedin'])) : ?>
                                <a href="<?php echo esc_url($person['linkedin']); ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($person['twitter'])) : ?>
                                <a href="<?php echo esc_url($person['twitter']); ?>" target="_blank" rel="noopener noreferrer" title="Twitter / X">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
