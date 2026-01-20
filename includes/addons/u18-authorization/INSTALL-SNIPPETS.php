<?php
/**
 * ÄNDERUNGEN FÜR ensemble.php
 * 
 * Diese Snippets müssen in die Hauptdatei ensemble.php eingefügt werden.
 */

// ====================================================================
// 1. IN load_dependencies() HINZUFÜGEN (ca. Zeile 285):
// ====================================================================

// U18 Authorization Addon (Muttizettel)
if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/u18-authorization/class-es-u18-addon.php')) {
    require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/u18-authorization/class-es-u18-addon.php';
}


// ====================================================================
// 2. IN register_addons() HINZUFÜGEN (ca. Zeile 700):
// ====================================================================

// U18 Authorization (Muttizettel)
if (class_exists('ES_U18_Addon')) {
    ES_Addon_Manager::register_addon('u18-authorization', array(
        'name'          => __('U18 Muttizettel', 'ensemble'),
        'description'   => __('Digitale Aufsichtsübertragung für Minderjährige (16-17 Jahre) nach JuSchG.', 'ensemble'),
        'version'       => '1.0.0',
        'author'        => 'Fabian',
        'author_uri'    => 'https://kraftwerk-mkt.com',
        'requires_pro'  => true,
        'class'         => 'ES_U18_Addon',
        'icon'          => 'dashicons-id-alt',
        'settings_page' => true,
        'has_frontend'  => true,
    ));
}


// ====================================================================
// 3. OPTIONAL: ALTES RESERVATIONS-ADDON ENTFERNEN
// ====================================================================

// Falls das alte Reservations-Addon komplett entfernt werden soll,
// diese Zeilen in ensemble.php auskommentieren oder löschen:

// IN load_dependencies():
// require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/reservations/class-es-reservations-addon.php';

// IN register_addons():
// ES_Addon_Manager::register_addon('reservations', array(...));
