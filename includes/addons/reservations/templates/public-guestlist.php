<?php
/**
 * Reservations Pro - Public Guestlist Template
 * 
 * Shows guest count or names publicly
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="es-public-guestlist">
    <?php if ($show_count): ?>
    <div class="es-guestlist-count">
        <span class="es-guestlist-count-number"><?php echo $total_guests; ?></span>
        <span class="es-guestlist-count-label">
            <?php echo _n('guest registered', 'guests registered', $total_guests, 'ensemble'); ?>
        </span>
    </div>
    <?php endif; ?>
    
    <?php if ($show_names && !empty($reservations)): ?>
    <div class="es-guestlist-names">
        <?php foreach ($reservations as $res): ?>
        <span class="es-guestlist-name">
            <?php 
            // Show first name only for privacy
            $name_parts = explode(' ', $res->name);
            echo esc_html($name_parts[0]); 
            if ($res->guests > 1) {
                echo ' +' . ($res->guests - 1);
            }
            ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
