# Ensemble Elementor Widgets v2

Diese Integration fügt alle Ensemble-Shortcodes als native Elementor-Widgets hinzu, **inklusive vollständigem Style-Tab** für per-Widget Anpassungen.

## ✨ Neu in v2: Style Tab

Jedes Widget hat jetzt einen **Style Tab** mit zwei Modi:

### 🌍 Global Mode (Standard)
- Nutzt die Einstellungen aus **Ensemble → Designer**
- Änderungen im Designer wirken sich auf alle Widgets aus
- Empfohlen für konsistentes Design

### 🎨 Custom Mode
- Überschreibt Designer-Einstellungen pro Widget
- Perfekt für einzelne Highlights oder Abweichungen

**Verfügbare Style-Optionen:**
- **Colors:** Primary, Secondary, Text, Background, Links, Hover
- **Cards:** Background, Border, Radius, Padding, Shadow, Hover-Effect
- **Typography:** Heading Size/Weight, Body Size, Small Size
- **Buttons:** Background, Text, Hover, Radius, Padding
- **Spacing:** Grid Gap, Section Spacing
- **Images:** Height, Radius, Object-Fit

## Installation

1. Kopiere den `elementor/` Ordner nach `ensemble/includes/elementor/`

2. Füge folgende Zeile in `ensemble.php` nach den Blocks hinzu (ca. Zeile 212):

```php
// Blocks
require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-blocks.php';
require_once ENSEMBLE_PLUGIN_DIR . 'includes/shortcodes/class-es-location-shortcodes.php';

// ✅ NEW: Elementor Integration (v3.0)
if ( did_action( 'elementor/loaded' ) ) {
    require_once ENSEMBLE_PLUGIN_DIR . 'includes/elementor/class-es-elementor-loader.php';
}
```

## Struktur

```
includes/elementor/
├── class-es-elementor-loader.php      # Registrierung & Setup
├── class-es-elementor-base-widget.php # Basisklasse mit Style Tab
├── assets/
│   └── css/
│       └── elementor-widgets.css      # Custom Style Classes
└── widgets/
    ├── class-es-elementor-event-grid.php
    ├── class-es-elementor-artist-grid.php
    ├── class-es-elementor-location-grid.php
    ├── class-es-elementor-calendar.php
    ├── class-es-elementor-countdown.php
    ├── class-es-elementor-upcoming-events.php
    └── class-es-elementor-single-event.php
```

## Verfügbare Widgets

| Widget | Shortcode | Content Tab | Style Tab |
|--------|-----------|-------------|-----------|
| Event Grid | `[ensemble_events]` | Layout, Query, Filter, Display | ✅ Full |
| Artist Grid | `[ensemble_artists]` | Layout, Query, Filter, Display | ✅ Full |
| Location Grid | `[ensemble_locations]` | Layout, Query, Filter, Display | ✅ Full |
| Event Calendar | `[ensemble_calendar]` | View, Height, Date | ✅ Full |
| Event Countdown | `[ensemble_countdown]` | Source, Style, Display, Expired | ✅ Full |
| Upcoming Events | `[ensemble_upcoming_events]` | Limit, Display Options | ✅ Full |
| Single Event | `[ensemble_event]` | Event ID, Layout, Display | ✅ Full |

## Style Tab Sections

### 1. Style Mode
```
☑ Global (Ensemble Designer)  → Nutzt globale Designer-Einstellungen
☐ Custom (Override)           → Eigene Einstellungen pro Widget
```

### 2. Colors (nur im Custom Mode)
- Primary Color
- Secondary Color
- Text Color
- Text Secondary
- Background Color
- Link Color
- Hover Color

### 3. Cards (nur im Custom Mode)
- Card Background
- Card Border Color
- Card Border Radius
- Card Padding
- Card Shadow (None/Light/Medium/Heavy)
- Card Hover Effect (None/Lift/Glow/Border)

### 4. Typography (nur im Custom Mode)
- Heading Size
- Body Text Size
- Small Text Size
- Heading Weight

### 5. Buttons (nur im Custom Mode)
- Button Background
- Button Text Color
- Button Hover Background
- Button Border Radius
- Button Padding (H/V)

### 6. Spacing (nur im Custom Mode)
- Grid Gap
- Section Spacing

### 7. Images (nur im Custom Mode)
- Image Height
- Image Border Radius
- Image Fit (Cover/Contain/Fill)

## CSS-Variablen

Die Widgets nutzen CSS Custom Properties, die vom Designer gesetzt werden:

```css
/* Werden automatisch aus Designer geladen */
--ensemble-primary
--ensemble-secondary
--ensemble-text
--ensemble-text-secondary
--ensemble-bg
--ensemble-card-bg
--ensemble-card-border
--ensemble-card-radius
--ensemble-card-padding
--ensemble-button-bg
--ensemble-button-text
--ensemble-button-hover-bg
--ensemble-button-radius
--ensemble-grid-gap
--ensemble-image-height
--ensemble-heading-size
--ensemble-body-size
--ensemble-small-size
--ensemble-heading-weight
```

## Neue Widgets hinzufügen

1. Erstelle neue Klasse in `widgets/` die `ES_Elementor_Base_Widget` erweitert
2. Implementiere `register_controls()` - **Style Tab wird automatisch hinzugefügt!**
3. Implementiere `get_shortcode()`
4. Füge Widget in `class-es-elementor-loader.php` zum `$widgets` Array hinzu

```php
class ES_Elementor_My_Widget extends ES_Elementor_Base_Widget {
    
    public function get_name() {
        return 'ensemble-my-widget';
    }
    
    public function get_title() {
        return __( 'My Widget', 'ensemble' );
    }
    
    protected function register_controls() {
        // Deine Content Controls hier
        $this->start_controls_section( 'content_section', [...] );
        // ...
        $this->end_controls_section();
        
        // Style Tab wird automatisch von der Base-Klasse hinzugefügt!
    }
    
    protected function get_shortcode( $settings ) {
        return $this->build_shortcode( 'ensemble_my_shortcode', $settings, $attribute_map );
    }
}
```

## Anforderungen

- Elementor 3.0.0+
- Ensemble 3.0.0+
- PHP 7.4+

## Changelog

### v2.0.0
- ✨ Style Tab für alle Widgets
- ✨ Global/Custom Style Mode Toggle
- ✨ CSS-Variablen Integration mit Designer
- ✨ Shadow & Hover Effect Klassen
- ✨ Dark Mode Support für Custom Styles
