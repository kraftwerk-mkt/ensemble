<?php
/**
 * Location Contacts Template
 * 
 * Displays contact persons for a location
 * 
 * @package Ensemble
 * @subpackage Addons/Staff
 * 
 * Variables:
 * @var array  $staff  Array of staff members
 * @var string $title  Section title
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($staff)) {
    return;
}
?>

<div class="es-location-contacts">
    <h3 class="es-location-contacts__title">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
        </svg>
        <?php echo esc_html($title); ?>
    </h3>
    
    <div class="es-location-contacts__list">
        <?php foreach ($staff as $person) : ?>
            <div class="es-location-contact" itemscope itemtype="https://schema.org/Person">
                <?php if ($person['featured_image']) : ?>
                    <div class="es-location-contact__image">
                        <img src="<?php echo esc_url($person['featured_image']); ?>" 
                             alt="<?php echo esc_attr($person['name']); ?>"
                             itemprop="image">
                    </div>
                <?php else : ?>
                    <div class="es-location-contact__image es-location-contact__image--placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <div class="es-location-contact__info">
                    <h4 class="es-location-contact__name" itemprop="name">
                        <a href="<?php echo esc_url($person['permalink']); ?>">
                            <?php echo esc_html($person['name']); ?>
                        </a>
                    </h4>
                    
                    <?php if (!empty($person['position'])) : ?>
                        <p class="es-location-contact__position" itemprop="jobTitle">
                            <?php echo esc_html($person['position']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($person['department'])) : ?>
                        <p class="es-location-contact__department">
                            <?php echo esc_html($person['department']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($person['responsibility'])) : ?>
                        <p class="es-location-contact__responsibility">
                            <?php echo esc_html($person['responsibility']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($person['office_hours'])) : ?>
                        <p class="es-location-contact__hours">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                            <?php echo esc_html($person['office_hours']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="es-location-contact__contact">
                    <?php if (!empty($person['email'])) : ?>
                        <a href="mailto:<?php echo esc_attr($person['email']); ?>" 
                           class="es-location-contact__link es-location-contact__email"
                           itemprop="email"
                           title="<?php esc_attr_e('Send email', 'ensemble'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <span><?php echo esc_html($person['email']); ?></span>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    // Handle multiple phones
                    if (!empty($person['phones']) && is_array($person['phones'])) : 
                        foreach ($person['phones'] as $phone) :
                            if (!empty($phone['number'])) :
                                $phone_type = !empty($phone['type']) ? $phone['type'] : 'office';
                                $phone_label = ucfirst($phone_type);
                    ?>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone['number'])); ?>" 
                           class="es-location-contact__link es-location-contact__phone"
                           itemprop="telephone"
                           title="<?php echo esc_attr($phone_label); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                            <span><?php echo esc_html($phone['number']); ?></span>
                            <span class="es-location-contact__phone-type">(<?php echo esc_html($phone_label); ?>)</span>
                        </a>
                    <?php 
                            endif;
                        endforeach;
                    elseif (!empty($person['phone'])) : 
                        // Fallback for single phone
                    ?>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $person['phone'])); ?>" 
                           class="es-location-contact__link es-location-contact__phone"
                           itemprop="telephone"
                           title="<?php echo esc_attr($person['phone']); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                            <span><?php echo esc_html($person['phone']); ?></span>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    // Social Links
                    $has_social = !empty($person['website']) || !empty($person['linkedin']) || !empty($person['twitter']);
                    if ($has_social) : 
                    ?>
                        <div class="es-location-contact__social">
                            <?php if (!empty($person['website'])) : ?>
                                <a href="<?php echo esc_url($person['website']); ?>" 
                                   class="es-location-contact__social-link"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="<?php esc_attr_e('Website', 'ensemble'); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($person['linkedin'])) : ?>
                                <a href="<?php echo esc_url($person['linkedin']); ?>" 
                                   class="es-location-contact__social-link"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="LinkedIn">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                        <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($person['twitter'])) : ?>
                                <a href="<?php echo esc_url($person['twitter']); ?>" 
                                   class="es-location-contact__social-link"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Twitter / X">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
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
</div>
