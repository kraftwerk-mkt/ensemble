# Kongress Layout - Integration Guide

## Neue Dateien erstellt:

```
templates/layouts/kongress/
├── config.php           ✅
├── preset.php           ✅
├── style.css            ✅
├── single-event.php     ✅
├── single-artist.php    ✅
├── artist-card.php      ✅
└── event-card.php       ✅

includes/
└── ensemble-agenda-helpers.php  ✅

assets/images/agenda-icons/
├── coffee.svg           ✅
├── lunch.svg            ✅
├── networking.svg       ✅
├── workshop.svg         ✅
├── registration.svg     ✅
├── pause.svg            ✅
├── panel.svg            ✅
└── keynote.svg          ✅
```

---

## Änderungen an bestehenden Dateien:

### 1. ensemble.php - Require hinzufügen

```php
// Nach den anderen requires hinzufügen (ca. Zeile 150):
require_once ENSEMBLE_PLUGIN_DIR . 'includes/ensemble-agenda-helpers.php';
```

### 2. class-es-ajax-handler.php - Agenda Breaks speichern

In der `save_event` Methode, nach dem Speichern von `artist_times` (ca. Zeile 153):

```php
// Handle agenda breaks (JSON string from JS)
if (isset($_POST['agenda_breaks']) && !empty($_POST['agenda_breaks'])) {
    $breaks_raw = json_decode(stripslashes($_POST['agenda_breaks']), true);
    if (is_array($breaks_raw) && function_exists('ensemble_save_agenda_breaks')) {
        ensemble_save_agenda_breaks($event_id, $breaks_raw);
    }
} else {
    // Clear breaks if empty
    delete_post_meta($event_id, '_agenda_breaks');
}
```

### 3. class-es-wizard.php - Agenda Breaks laden

In der `format_event` Methode, nach `$event['artist_order']` (ca. Zeile 156):

```php
// Get agenda breaks for timeline
$agenda_breaks = get_post_meta($post->ID, '_agenda_breaks', true);
$event['agenda_breaks'] = is_array($agenda_breaks) ? $agenda_breaks : array();
```

### 4. admin.js - UI für Breaks hinzufügen

In der Wizard JavaScript, im Lineup-Bereich, nach dem "Artist hinzufügen" Button:

```javascript
// Agenda Breaks State
let agendaBreaks = eventData.agenda_breaks || [];

// Break Types
const breakTypes = {
    coffee: 'Kaffeepause',
    lunch: 'Mittagspause',
    networking: 'Networking',
    registration: 'Registrierung',
    workshop: 'Workshop',
    panel: 'Panel-Diskussion',
    keynote: 'Keynote',
    pause: 'Pause'
};

// Render Break in Timeline
function renderBreakItem(breakData, index) {
    return `
        <div class="es-lineup-break" data-index="${index}">
            <div class="es-break-time">
                <input type="time" value="${breakData.time || ''}" 
                       onchange="updateBreakTime(${index}, this.value)">
            </div>
            <div class="es-break-icon">
                <select onchange="updateBreakIcon(${index}, this.value)">
                    ${Object.entries(breakTypes).map(([key, label]) => 
                        `<option value="${key}" ${breakData.icon === key ? 'selected' : ''}>${label}</option>`
                    ).join('')}
                </select>
            </div>
            <div class="es-break-title">
                <input type="text" value="${breakData.title || ''}" 
                       placeholder="Titel"
                       onchange="updateBreakTitle(${index}, this.value)">
            </div>
            <div class="es-break-duration">
                <input type="number" value="${breakData.duration || ''}" 
                       placeholder="Min." min="0" max="480"
                       onchange="updateBreakDuration(${index}, this.value)">
                <span>Min.</span>
            </div>
            <button type="button" class="es-break-remove" onclick="removeBreak(${index})">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    `;
}

// Add Break
function addBreak() {
    agendaBreaks.push({
        time: '',
        title: 'Pause',
        duration: 30,
        icon: 'pause'
    });
    renderLineup();
}

// Update Break Functions
function updateBreakTime(index, value) {
    agendaBreaks[index].time = value;
}

function updateBreakTitle(index, value) {
    agendaBreaks[index].title = value;
}

function updateBreakDuration(index, value) {
    agendaBreaks[index].duration = parseInt(value) || 0;
}

function updateBreakIcon(index, value) {
    agendaBreaks[index].icon = value;
}

function removeBreak(index) {
    agendaBreaks.splice(index, 1);
    renderLineup();
}

// In saveEvent() - Add to form data:
formData.append('agenda_breaks', JSON.stringify(agendaBreaks));
```

### 5. CSS für Break-Editor (admin.css oder inline)

```css
/* Agenda Break Editor */
.es-lineup-break {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f0f7ff;
    border-left: 4px solid #B87333;
    border-radius: 4px;
    margin-bottom: 8px;
}

.es-break-time input[type="time"] {
    width: 100px;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.es-break-icon select {
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 140px;
}

.es-break-title input {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.es-break-duration {
    display: flex;
    align-items: center;
    gap: 4px;
}

.es-break-duration input {
    width: 60px;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
}

.es-break-duration span {
    color: #666;
    font-size: 13px;
}

.es-break-remove {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #999;
    transition: color 0.2s;
}

.es-break-remove:hover {
    color: #dc2626;
}

/* Add Break Button */
.es-add-break-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #f8f9fa;
    border: 1px dashed #ccc;
    border-radius: 4px;
    color: #666;
    cursor: pointer;
    font-size: 13px;
    margin-top: 8px;
    transition: all 0.2s;
}

.es-add-break-btn:hover {
    background: #fff;
    border-color: #B87333;
    color: #B87333;
}
```

---

## Zusammenfassung der Änderungen:

| Datei | Änderung | Aufwand |
|-------|----------|---------|
| `ensemble.php` | 1 Zeile require hinzufügen | 1 Min |
| `class-es-ajax-handler.php` | ~10 Zeilen für Breaks speichern | 5 Min |
| `class-es-wizard.php` | ~3 Zeilen für Breaks laden | 2 Min |
| `admin.js` | ~80 Zeilen für Break-UI | 15 Min |
| `admin.css` | ~50 Zeilen Styling | 5 Min |

**Gesamt: ~30 Minuten Integration**

---

## Testing Checklist:

- [ ] Layout "Kongress" in Layout-Sets sichtbar
- [ ] Preset-Farben (Navy/Kupfer) werden angewendet
- [ ] Single Event zeigt Agenda/Timeline korrekt
- [ ] Breaks erscheinen in der Timeline
- [ ] Speaker-Grid funktioniert
- [ ] Single Speaker zeigt lange Vita korrekt
- [ ] Katalog-Hook wird aufgerufen
- [ ] Scroll-Animationen funktionieren
- [ ] Counter-Animation funktioniert
- [ ] Responsive Design OK
