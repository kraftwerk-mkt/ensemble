<?php
/**
 * Single Sponsor Template
 * 
 * For [ensemble_sponsor id="123"] shortcode
 *
 * @package Ensemble
 * @subpackage Addons/Sponsors
 */

if (!defined('ABSPATH')) {
    exit;
}

$logo_url = !empty($sponsor['logo_url_full']) ? $sponsor['logo_url_full'] : $sponsor['logo_url'];
?>

<div class="es-sponsor-single" style="--es-sponsor-height: <?php echo esc_attr($height); ?>px;">
    <?php if ($show_link && !empty($sponsor['website'])): ?>
    <a href="<?php echo esc_url($sponsor['website']); ?>" target="_blank" rel="noopener noreferrer" class="es-sponsor-link" title="<?php echo esc_attr($sponsor['name']); ?>">
    <?php endif; ?>
    
    <?php if ($logo_url): ?>
    <img src="<?php echo esc_url($logo_url); ?>" 
         alt="<?php echo esc_attr($sponsor['name']); ?>" 
         class="es-sponsor-logo" 
         style="height: <?php echo esc_attr($height); ?>px;"
         loading="lazy">
    <?php else: ?>
    <span class="es-sponsor-name"><?php echo esc_html($sponsor['name']); ?></span>
    <?php endif; ?>
    
    <?php if ($show_link && !empty($sponsor['website'])): ?>
    </a>
    <?php endif; ?>
</div>
