# Elementor Pro Add-on Update v2

## Fixes in diesem Update

### Taxonomy/Post-Type Namen korrigiert
- `es_event_category` → `ensemble_category`
- `es_event` → dynamisch via `ensemble_get_post_type()`
- Calendar Widget: Taxonomy-Name korrigiert

### Widgets verwenden jetzt korrekte Shortcodes
- **Upcoming Events** → verwendet `ensemble_events_grid` mit `show="upcoming"`
- **Featured Events** → verwendet `ensemble_events_grid` mit `featured="1"`
- **Artists Grid** → verwendet `ensemble_artists` mit korrekten Parametern
- **Locations Grid** → verwendet `ensemble_locations` mit korrekten Parametern

## Installation

### Komplettes Add-on ersetzen
Ersetze den gesamten Ordner:
```
/includes/addons/elementor-pro/
```

Mit der neuen Struktur:
```
elementor-pro/
├── class-es-elementor-pro-addon.php
├── class-es-elementor-widget-base.php
├── assets/
│   ├── editor.css
│   └── elementor.css
└── widgets/
    ├── class-es-widget-events-grid.php
    ├── class-es-widget-calendar.php
    ├── class-es-widget-upcoming-events.php
    ├── class-es-widget-featured-events.php
    ├── class-es-widget-artists-grid.php
    └── class-es-widget-locations-grid.php
```

## Widget-Parameter Mapping

### Upcoming Events Widget
| Widget Setting | Shortcode Parameter |
|----------------|---------------------|
| layout | layout |
| limit | limit |
| - | show="upcoming" |
| show_image | show_image |
| show_date | show_date |
| show_time | show_time |
| show_location | show_location |
| category | category |
| location | location |
| artist | artist |

### Featured Events Widget
| Widget Setting | Shortcode Parameter |
|----------------|---------------------|
| layout | layout |
| limit | limit |
| - | featured="1" |
| - | style="featured" |
| columns | columns |
| show_image | show_image |
| show_date | show_date |
| show_description | show_description |
| events (manual) | ids |

### Artists Grid Widget
| Widget Setting | Shortcode Parameter |
|----------------|---------------------|
| layout | layout |
| columns | columns |
| limit | limit |
| orderby | orderby |
| order | order |
| show_image | show_image |
| show_bio | show_bio |
| show_events_count | show_events |
| genre | category |

### Locations Grid Widget
| Widget Setting | Shortcode Parameter |
|----------------|---------------------|
| layout | layout |
| columns | columns |
| limit | limit |
| orderby | orderby |
| order | order |
| show_image | show_image |
| show_address | show_address |
| show_description | show_description |
| show_events_count | show_events |
| location_type | type |

## Hinweise

Einige Widget-Controls haben mehr Optionen als die Shortcodes unterstützen.
Diese werden in zukünftigen Updates zum Shortcode hinzugefügt.
