# Ensemble Timetable Addon - Installation Guide

## Overview

The Timetable Addon provides a visual grid editor for managing complex conference/congress schedules. It uses the **same meta fields** as the existing Wizard, ensuring full compatibility.

---

## Files Created

```
includes/addons/timetable/
├── class-es-timetable-addon.php       # Main addon class (42 KB)
├── class-es-timetable-ajax.php        # AJAX handlers (18 KB)
├── assets/
│   ├── css/
│   │   └── timetable-admin.css        # Admin styles (12 KB)
│   └── js/
│       └── timetable-admin.js         # Grid editor logic (18 KB)
└── templates/
    └── admin/
        └── timetable-editor.php       # Admin template (11 KB)
```

---

## Integration Steps

### Step 1: Add require in ensemble.php

Add after line ~222 (after the countdown require):

```php
// Timetable Addon
if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/timetable/class-es-timetable-addon.php')) {
    require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/timetable/class-es-timetable-addon.php';
}
```

### Step 2: Register addon in ensemble.php

Add in the `register_addons()` method (after line ~633):

```php
// Timetable Add-on
if (class_exists('ES_Timetable_Addon')) {
    ES_Addon_Manager::register_addon('timetable', array(
        'name'          => __('Timetable Editor', 'ensemble'),
        'description'   => __('Visual grid editor for complex conference schedules. Drag & drop sessions, manage rooms, detect conflicts. Perfect for multi-track congresses.', 'ensemble'),
        'version'       => '1.0.0',
        'author'        => 'Fabian',
        'author_uri'    => 'https://kraftwerk-mkt.com',
        'requires_pro'  => true,
        'class'         => 'ES_Timetable_Addon',
        'icon'          => 'dashicons-schedule',
        'settings_page' => true,
        'has_frontend'  => false,  // Admin-only for now
    ));
}
```

---

## New Meta Field: artist_durations

The addon adds a new meta field to store session durations:

```php
// Existing fields (unchanged):
'event_artist'           => [123, 456, 789]           // Artist IDs
'artist_times'           => [123 => '09:00', ...]     // Start time
'artist_venues'          => [123 => 'Saal A', ...]    // Room
'artist_session_titles'  => [123 => 'Keynote: ...']   // Custom title
'_agenda_breaks'         => [...]                     // Breaks

// NEW field:
'artist_durations'       => [123 => 60, 456 => 90]    // Duration in minutes
```

This field is automatically integrated into `ensemble_get_merged_agenda()` via the `ensemble_merged_agenda` filter.

---

## Features Implemented (MVP)

### ✅ Admin Grid Editor
- Visual timetable with Time (Y-axis) × Rooms (X-axis)
- Sessions displayed as colored blocks
- Breaks displayed as spanning rows
- Click on cell to add session
- Click on session to edit

### ✅ Session Management
- Add speakers from existing Artists
- Set time, duration, room, custom title
- Remove/unassign sessions
- Validation for conflicts (speaker busy, room busy)

### ✅ Break Management
- Add/edit/delete breaks
- Break types: Coffee, Lunch, Networking, Registration, etc.
- Duration and custom title

### ✅ Conflict Detection
- Prevents double-booking of speakers
- Prevents room conflicts
- Real-time validation in modal

### ✅ Drag & Drop (Basic)
- Drag unassigned speakers to grid
- (Full drag & drop of positioned sessions is Phase 2)

---

## Usage

1. Navigate to **Ensemble → Timetable**
2. Select an event from the dropdown
3. The grid shows rooms from the event's location
4. Click on a cell to add a session
5. Click on existing sessions to edit
6. Click "Add Break" to add breaks
7. Save with "Save Timetable" button

---

## Compatibility

- ✅ Works with existing Wizard data
- ✅ Changes in Timetable appear in Wizard
- ✅ Changes in Wizard appear in Timetable
- ✅ Works with Kongress layout agenda display
- ✅ Works with all layouts that use `ensemble_get_merged_agenda()`

---

## Future Enhancements (Phase 2+)

- [ ] Full drag & drop of positioned sessions
- [ ] Multi-day tabs for conferences
- [ ] Visual session resizing for duration
- [ ] PDF export of timetable
- [ ] iCal export per session
- [ ] Frontend timetable shortcode
- [ ] Session type badges (Keynote, Workshop, Panel)
- [ ] Color coding by track/category

---

## Testing Checklist

- [ ] Addon appears in Ensemble → Addons
- [ ] Timetable menu item appears when activated
- [ ] Event selector shows events correctly
- [ ] Grid displays with correct rooms
- [ ] Sessions can be added
- [ ] Sessions can be edited
- [ ] Sessions can be removed
- [ ] Breaks can be added/edited/removed
- [ ] Conflict validation works
- [ ] Data persists after save
- [ ] Data appears in Wizard
- [ ] Data appears in frontend agenda

---

*Created: January 2026*
