# Ensemble U18 Authorization Addon (Muttizettel)

## Übersicht

Eigenständiges Addon für digitale Aufsichtsübertragung (Muttizettel) nach § 1 Abs. 1 Nr. 4 JuSchG.
Ermöglicht die digitale Übertragung der Aufsichtspflicht für Minderjährige (16-17 Jahre) bei Events.

**Vormals Teil von:** Reservations Pro Addon  
**Jetzt:** Eigenständiges Addon ohne Abhängigkeiten

## Features

- ✅ Eigene Datenbanktabelle (`wp_ensemble_u18_authorizations`)
- ✅ Multi-Step Wizard (Erziehungsberechtigter → Minderjähriger → Begleitperson → Unterschriften)
- ✅ Digitale Unterschriften (Signature Pad)
- ✅ PDF-Generierung des Muttizettels
- ✅ QR-Code Check-in System
- ✅ E-Mail-Benachrichtigungen (Eltern, Begleitperson, Admin)
- ✅ DSGVO-konforme Auto-Löschung nach Event
- ✅ REST API Integration
- ✅ ID-Upload Option (optional)

## Installation

### 1. Dateien kopieren

Kopiere den gesamten `u18-authorization/` Ordner nach:
```
/wp-content/plugins/ensemble/includes/addons/u18-authorization/
```

### 2. In ensemble.php registrieren

**In `load_dependencies()` hinzufügen (ca. Zeile 285):**

```php
// U18 Authorization Addon
if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/u18-authorization/class-es-u18-addon.php')) {
    require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/u18-authorization/class-es-u18-addon.php';
}
```

**In `register_addons()` hinzufügen (ca. Zeile 700):**

```php
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
```

## Meta Keys

Das Addon verwendet folgende Meta-Keys für Events:

| Meta Key | Beschreibung | Werte |
|----------|--------------|-------|
| `_u18_enabled` | U18-Formular aktivieren | `'1'` oder `''` |
| `_u18_require_id` | Ausweis-Upload erforderlich | `'1'` oder `''` |
| `_u18_auto_approve` | Auto-Genehmigung aktivieren | `'1'` oder `''` |

**Backward Compatibility:** Das Addon unterstützt auch den alten Meta-Key `_u18_authorization_enabled`.

## Shortcode

```php
[ensemble_u18_form event_id="123"]
```

## Datenbank-Tabelle

Die Tabelle `wp_ensemble_u18_authorizations` wird automatisch erstellt und enthält:

- Daten des Erziehungsberechtigten
- Daten des Minderjährigen
- Daten der Begleitperson
- Digitale Unterschriften (Base64)
- Authorization-Code für Check-in
- Status-Tracking
- DSGVO: Automatische Löschung nach Ablaufdatum

## Integration mit Booking Engine

Das Addon integriert sich mit der Booking Engine:
- Zusätzlicher Tab "U18 Authorizations" im Admin-Bereich
- Gemeinsames Check-in-System

## Migration von Reservations Pro

Wenn das alte Reservations Pro Addon verwendet wurde:
1. Die U18-Datenbank-Tabelle bleibt erhalten
2. Nur das Addon wird ersetzt
3. Keine Datenmigration notwendig

## Dateistruktur

```
u18-authorization/
├── class-es-u18-addon.php          # Haupt-Addon-Klasse
├── class-es-u18-authorization.php  # U18-Logik (AJAX, Email, PDF, etc.)
├── assets/
│   ├── u18-admin.css              # Admin-Styles (Unified CSS Variables)
│   ├── u18-admin.js               # Admin-Scripts
│   ├── u18.css                    # Frontend-Styles
│   └── u18.js                     # Frontend-Scripts (Multi-Step, Signatures)
└── templates/
    ├── u18-form.php               # Frontend-Formular
    ├── u18-admin-tab.php          # Admin-Tab
    ├── u18-status-page.php        # Status-Seite
    ├── u18-pdf-html.php           # PDF-Template
    ├── email-u18-confirmation.php # E-Mail: Bestätigung
    ├── email-u18-companion.php    # E-Mail: Begleitperson
    ├── email-u18-approved.php     # E-Mail: Genehmigt
    └── email-u18-admin.php        # E-Mail: Admin-Benachrichtigung
```

## Changelog

### 1.0.0
- Initiale Version als eigenständiges Addon
- Extrahiert aus Reservations Pro
- Unterstützung für neuen Meta-Key `_u18_enabled`
- Unified Admin CSS Variables

---

*Dokumentation erstellt: Januar 2025*
