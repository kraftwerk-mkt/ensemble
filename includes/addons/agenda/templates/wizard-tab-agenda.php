<?php
/**
 * Agenda Tab Content for Event Wizard
 * 
 * @package Ensemble
 * @subpackage Addons/Agenda
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;
?>

<div id="es-wizard-tab-agenda" class="es-wizard-tab" data-tab="agenda">
    
    <div class="es-wizard-section">
        <h3 class="es-wizard-section-title">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php _e('Agenda verwalten', 'ensemble'); ?>
        </h3>
        <p class="es-wizard-section-desc">
            <?php _e('Erstellen Sie das Tagesprogramm mit Sessions, Pausen und Speakern. Ideal für mehrtägige Konferenzen und Kongresse.', 'ensemble'); ?>
        </p>
    </div>
    
    <div id="es-agenda-editor" class="es-agenda-editor">
        
        <!-- Hidden field for data -->
        <input type="hidden" id="es-agenda-data" name="agenda_data" value="">
        
        <!-- Header with global actions -->
        <div class="es-agenda-header">
            <h3><?php _e('Tage & Sessions', 'ensemble'); ?></h3>
            <div class="es-agenda-actions">
                <button type="button" class="button es-agenda-add-day">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Tag hinzufügen', 'ensemble'); ?>
                </button>
            </div>
        </div>
        
        <!-- Days container -->
        <div id="es-agenda-days" class="es-agenda-days">
            <!-- Days will be rendered by JS -->
        </div>
        
        <!-- Add first day button (shown when no days) -->
        <button type="button" class="es-agenda-add-day" style="margin-top: 16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <?php _e('Ersten Tag hinzufügen', 'ensemble'); ?>
        </button>
        
        <!-- Rooms Manager -->
        <div class="es-agenda-rooms">
            <div class="es-agenda-rooms-header">
                <h4><?php _e('Räume / Stages', 'ensemble'); ?></h4>
            </div>
            <div id="es-agenda-rooms-list" class="es-agenda-rooms-list">
                <!-- Rooms will be rendered by JS -->
            </div>
        </div>
        
    </div>
    
</div>

<style>
/* Quick inline styles for wizard integration */
#es-wizard-tab-agenda .es-wizard-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
}

#es-wizard-tab-agenda .es-wizard-section-title .dashicons {
    color: #B87333;
}
</style>
