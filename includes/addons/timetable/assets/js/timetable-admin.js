/**
 * Ensemble Timetable Admin JavaScript
 * 
 * Handles both modes:
 * - Festival Timeline (Multi-Event)
 * - Conference Agenda (Single-Event)
 */

(function($) {
    'use strict';

    // =========================================
    // FESTIVAL TIMELINE (MULTI-EVENT MODE)
    // =========================================

    const ESFestivalTimeline = {
        
        data: null,
        pixelsPerMinute: 2,
        timeStart: 10,
        timeEnd: 26,
        activeDay: 'all',
        
        init: function() {
            this.pixelsPerMinute = esTimetable.pixel_per_min || 2;
            this.bindEvents();
            this.loadTimeline();
        },

        bindEvents: function() {
            // Load button
            $('#es-load-timeline').on('click', () => this.loadTimeline());

            // Time range change
            $('#es-time-start, #es-time-end').on('change', () => this.rebuildTimeline());

            // Day tabs
            $(document).on('click', '.es-day-tab', (e) => {
                const $tab = $(e.currentTarget);
                $('.es-day-tab').removeClass('active');
                $tab.addClass('active');
                this.activeDay = $tab.data('day');
                this.renderEvents();
            });

            // Search events
            $('#es-event-search').on('input', (e) => {
                const query = e.target.value.toLowerCase();
                $('.es-unscheduled-event').each(function() {
                    const title = $(this).find('.es-event-title').text().toLowerCase();
                    $(this).toggle(title.includes(query));
                });
            });

            // Event double-click to edit
            $(document).on('dblclick', '.es-timeline-event', (e) => {
                const eventId = $(e.currentTarget).data('event-id');
                this.openEventModal(eventId);
            });

            // Event edit button
            $(document).on('click', '.es-event-edit-btn', (e) => {
                e.stopPropagation();
                const eventId = $(e.currentTarget).closest('.es-timeline-event, .es-unscheduled-event').data('event-id');
                this.openEventModal(eventId);
            });

            // Save event from modal
            $('#es-save-event-schedule').on('click', () => this.saveEventFromModal());

            // Modal close
            $('[data-dismiss="modal"]').on('click', () => {
                $('.es-modal').removeClass('active');
            });
        },

        loadTimeline: function() {
            const dateFrom = $('#es-date-from').val();
            const dateTo = $('#es-date-to').val();

            $('#es-festival-loading').addClass('active');

            $.ajax({
                url: esTimetable.ajax_url,
                type: 'POST',
                data: {
                    action: 'es_load_multi_timetable',
                    nonce: esTimetable.nonce,
                    date_from: dateFrom,
                    date_to: dateTo
                },
                success: (response) => {
                    $('#es-festival-loading').removeClass('active');
                    if (response.success) {
                        this.data = response.data;
                        this.buildDayTabs();
                        this.buildTimeline();
                        this.renderEvents();
                        this.initDragDrop();
                    } else {
                        this.showToast('Error loading', 'error');
                    }
                },
                error: () => {
                    $('#es-festival-loading').removeClass('active');
                    this.showToast('Connection error', 'error');
                }
            });
        },

        buildDayTabs: function() {
            const $tabs = $('#es-day-tabs');
            $tabs.find('.es-day-tab:not(:first)').remove();

            if (!this.data || !this.data.days) return;

            this.data.days.forEach(day => {
                $tabs.append(`
                    <button type="button" class="es-day-tab" data-day="${day.date}">
                        <span class="es-day-name">${day.day_name}</span>
                        <span class="es-day-date">${day.label}</span>
                    </button>
                `);
            });
        },

        buildTimeline: function() {
            this.timeStart = parseInt($('#es-time-start').val()) || 10;
            this.timeEnd = parseInt($('#es-time-end').val()) || 26;

            this.buildTimelineHeader();
            this.buildStageRows();
        },

        rebuildTimeline: function() {
            this.buildTimeline();
            this.renderEvents();
        },

        buildTimelineHeader: function() {
            const $header = $('#es-timeline-header');
            $header.empty();

            // Spacer for stage labels
            $header.append('<div class="es-timeline-spacer"></div>');

            const hours = this.timeEnd - this.timeStart;
            const totalWidth = hours * 60 * this.pixelsPerMinute;

            const $slots = $('<div class="es-time-slots"></div>').css('width', totalWidth + 'px');

            for (let h = this.timeStart; h < this.timeEnd; h++) {
                const displayHour = h % 24;
                $slots.append(`
                    <div class="es-time-slot" style="width: ${60 * this.pixelsPerMinute}px">
                        ${String(displayHour).padStart(2, '0')}:00
                    </div>
                `);
            }

            $header.append($slots);
        },

        buildStageRows: function() {
            const $grid = $('#es-timeline-grid');
            $grid.empty();

            if (!this.data || !this.data.locations) return;

            const hours = this.timeEnd - this.timeStart;
            const totalWidth = hours * 60 * this.pixelsPerMinute;

            this.data.locations.forEach(loc => {
                const $row = $(`
                    <div class="es-stage-row" data-location-id="${loc.id}">
                        <div class="es-stage-label" style="border-left-color: ${loc.color}">
                            <span class="es-stage-name">${loc.name}</span>
                        </div>
                        <div class="es-stage-timeline" style="width: ${totalWidth}px">
                            <div class="es-events-container"></div>
                        </div>
                    </div>
                `);
                $grid.append($row);
            });
        },

        renderEvents: function() {
            if (!this.data || !this.data.events) return;

            // Clear existing
            $('.es-events-container').empty();
            $('#es-unscheduled-list').empty();

            let scheduledCount = 0;
            let unscheduledCount = 0;

            this.data.events.forEach(event => {
                // Filter by day
                if (this.activeDay !== 'all' && event.date !== this.activeDay) {
                    return;
                }

                if (event.scheduled && event.location_id && event.start_time) {
                    this.renderScheduledEvent(event);
                    scheduledCount++;
                } else {
                    this.renderUnscheduledEvent(event);
                    unscheduledCount++;
                }
            });

            $('#es-unscheduled-count').text(unscheduledCount);

            // Init drag for new elements
            this.initDragDrop();
        },

        renderScheduledEvent: function(event) {
            const $container = $(`.es-stage-row[data-location-id="${event.location_id}"] .es-events-container`);
            if (!$container.length) return;

            const startMins = this.timeToMinutes(event.start_time);
            const offsetMins = startMins - (this.timeStart * 60);

            if (offsetMins < 0) return; // Outside visible range

            const left = offsetMins * this.pixelsPerMinute;
            const width = (event.duration || 60) * this.pixelsPerMinute;

            const $event = $(`
                <div class="es-timeline-event" 
                     data-event-id="${event.id}"
                     data-date="${event.date}"
                     data-start="${event.start_time}"
                     data-duration="${event.duration}"
                     style="left: ${left}px; width: ${width}px; background-color: ${event.location_color || '#3582c4'}">
                    <div class="es-event-content">
                        <span class="es-event-time">${event.start_time}</span>
                        <span class="es-event-title">${event.title}</span>
                        ${event.artist ? `<span class="es-event-artist">${event.artist}</span>` : ''}
                    </div>
                    <button type="button" class="es-event-edit-btn" title="Bearbeiten">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <div class="es-event-resize-handle"></div>
                </div>
            `);

            $container.append($event);
            this.makeResizable($event);
        },

        renderUnscheduledEvent: function(event) {
            const $list = $('#es-unscheduled-list');

            const $event = $(`
                <div class="es-unscheduled-event" data-event-id="${event.id}">
                    ${event.image ? `<img src="${event.image}" class="es-event-thumb" alt="">` : ''}
                    <div class="es-event-info">
                        <span class="es-event-title">${event.title}</span>
                        ${event.artist ? `<span class="es-event-artist">${event.artist}</span>` : ''}
                        ${event.date ? `<span class="es-event-date">${event.date}</span>` : ''}
                    </div>
                    <button type="button" class="es-event-edit-btn" title="Bearbeiten">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                </div>
            `);

            $list.append($event);
        },

        initDragDrop: function() {
            // Unscheduled events are draggable
            $('.es-unscheduled-event').draggable({
                helper: 'clone',
                appendTo: 'body',
                zIndex: 1000,
                revert: 'invalid',
                cursor: 'move',
                opacity: 0.8
            });

            // Scheduled events are draggable within timeline
            $('.es-timeline-event').draggable({
                axis: 'x',
                containment: 'parent',
                grid: [5 * this.pixelsPerMinute, 0], // Snap to 5 min
                stop: (e, ui) => this.onEventDrag(e, ui)
            });

            // Stage timelines are droppable
            $('.es-stage-timeline').droppable({
                accept: '.es-unscheduled-event',
                hoverClass: 'es-drop-hover',
                drop: (e, ui) => this.onEventDrop(e, ui)
            });

            // Sidebar is droppable (to unschedule)
            $('#es-unscheduled-list').droppable({
                accept: '.es-timeline-event',
                hoverClass: 'es-drop-hover',
                drop: (e, ui) => this.onEventUnschedule(e, ui)
            });
        },

        makeResizable: function($event) {
            $event.find('.es-event-resize-handle').on('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const startX = e.pageX;
                const startWidth = $event.width();
                const eventId = $event.data('event-id');

                $(document).on('mousemove.resize', (moveE) => {
                    const diff = moveE.pageX - startX;
                    let newWidth = startWidth + diff;
                    
                    // Snap to 5 minutes
                    const minWidth = 15 * this.pixelsPerMinute; // Min 15 min
                    newWidth = Math.max(minWidth, Math.round(newWidth / (5 * this.pixelsPerMinute)) * (5 * this.pixelsPerMinute));
                    
                    $event.width(newWidth);
                });

                $(document).on('mouseup.resize', () => {
                    $(document).off('.resize');
                    
                    // Calculate new duration
                    const newWidth = $event.width();
                    const newDuration = Math.round(newWidth / this.pixelsPerMinute);
                    
                    // Update data
                    $event.data('duration', newDuration);
                    
                    // Save
                    this.saveEventSchedule(eventId, {
                        duration: newDuration
                    });
                });
            });
        },

        onEventDrag: function(e, ui) {
            const $event = ui.helper;
            const eventId = $event.data('event-id');
            const left = parseInt($event.css('left'));
            
            // Calculate new time
            const offsetMins = Math.round(left / this.pixelsPerMinute);
            const totalMins = (this.timeStart * 60) + offsetMins;
            const hours = Math.floor(totalMins / 60) % 24;
            const mins = totalMins % 60;
            const newTime = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;

            // Update display
            $event.find('.es-event-time').text(newTime);
            $event.data('start', newTime);

            // Save
            this.saveEventSchedule(eventId, {
                start_time: newTime
            });
        },

        onEventDrop: function(e, ui) {
            const $event = ui.draggable;
            const eventId = $event.data('event-id');
            const $target = $(e.target);
            const locationId = $target.closest('.es-stage-row').data('location-id');

            // Calculate drop position time
            const offset = ui.offset.left - $target.offset.left;
            const offsetMins = Math.round(offset / this.pixelsPerMinute);
            const totalMins = (this.timeStart * 60) + offsetMins;
            const hours = Math.floor(totalMins / 60) % 24;
            const mins = totalMins % 60;
            const newTime = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;

            // Get event data
            const eventData = this.data.events.find(ev => ev.id === eventId);
            const duration = eventData ? eventData.duration : 60;
            const endMins = totalMins + duration;
            const endHours = Math.floor(endMins / 60) % 24;
            const endM = endMins % 60;
            const endTime = `${String(endHours).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;

            // Get date (use active day or first available)
            let date = this.activeDay !== 'all' ? this.activeDay : '';
            if (!date && this.data.days && this.data.days.length) {
                date = this.data.days[0].date;
            }

            // Save
            this.saveEventSchedule(eventId, {
                location_id: locationId,
                start_time: newTime,
                end_time: endTime,
                date: date
            }, () => {
                // Update local data
                if (eventData) {
                    eventData.location_id = locationId;
                    eventData.start_time = newTime;
                    eventData.end_time = endTime;
                    eventData.date = date;
                    eventData.scheduled = true;
                    
                    // Get location color
                    const loc = this.data.locations.find(l => l.id === locationId);
                    if (loc) {
                        eventData.location_color = loc.color;
                    }
                }
                this.renderEvents();
            });
        },

        onEventUnschedule: function(e, ui) {
            const $event = ui.draggable;
            const eventId = $event.data('event-id');

            this.saveEventSchedule(eventId, {
                unschedule: true
            }, () => {
                // Update local data
                const eventData = this.data.events.find(ev => ev.id === eventId);
                if (eventData) {
                    eventData.scheduled = false;
                    eventData.location_id = 0;
                    eventData.start_time = '';
                }
                this.renderEvents();
            });
        },

        saveEventSchedule: function(eventId, data, callback) {
            const postData = {
                action: 'es_update_event_schedule',
                nonce: esTimetable.nonce,
                event_id: eventId,
                ...data
            };

            $.ajax({
                url: esTimetable.ajax_url,
                type: 'POST',
                data: postData,
                success: (response) => {
                    if (response.success) {
                        this.showToast('Gespeichert!', 'success');
                        if (callback) callback();
                    } else {
                        this.showToast('Error saving', 'error');
                    }
                },
                error: () => {
                    this.showToast('Verbindungsfehler', 'error');
                }
            });
        },

        openEventModal: function(eventId) {
            const event = this.data.events.find(ev => ev.id === eventId);
            if (!event) return;

            $('#es-edit-event-id').val(eventId);
            $('#es-edit-location').val(event.location_id || '');
            $('#es-edit-date').val(event.date || '');
            $('#es-edit-start').val(event.start_time || '');
            $('#es-edit-end').val(event.end_time || '');
            $('#es-edit-full').attr('href', event.edit_url || '#');

            // Preview
            $('#es-event-preview').html(`
                <div class="es-preview-title">${event.title}</div>
                ${event.artist ? `<div class="es-preview-artist">${event.artist}</div>` : ''}
            `);

            $('#es-event-modal').addClass('active');
        },

        saveEventFromModal: function() {
            const eventId = $('#es-edit-event-id').val();
            const data = {
                location_id: $('#es-edit-location').val(),
                date: $('#es-edit-date').val(),
                start_time: $('#es-edit-start').val(),
                end_time: $('#es-edit-end').val()
            };

            this.saveEventSchedule(eventId, data, () => {
                // Update local data
                const event = this.data.events.find(ev => ev.id == eventId);
                if (event) {
                    Object.assign(event, data);
                    event.scheduled = !!(data.location_id && data.start_time);
                    
                    // Update location color
                    const loc = this.data.locations.find(l => l.id == data.location_id);
                    if (loc) {
                        event.location_color = loc.color;
                    }
                }
                
                $('#es-event-modal').removeClass('active');
                this.renderEvents();
            });
        },

        timeToMinutes: function(time) {
            if (!time) return 0;
            const parts = time.split(':');
            return parseInt(parts[0]) * 60 + parseInt(parts[1] || 0);
        },

        showToast: function(message, type = 'info') {
            const $toast = $('#es-toast');
            $toast.removeClass('success error info').addClass(type);
            $toast.find('.es-toast-message').text(message);
            $toast.addClass('active');
            
            setTimeout(() => {
                $toast.removeClass('active');
            }, 3000);
        }
    };

    // =========================================
    // CONFERENCE AGENDA (SINGLE-EVENT MODE)
    // =========================================

    const ESConferenceAgenda = {

        eventId: null,
        data: null,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Event select
            $('#es-event-select').on('change', (e) => {
                this.eventId = $(e.target).val();
                if (this.eventId) {
                    this.loadAgenda();
                    $('#es-add-entry, #es-add-break').prop('disabled', false);
                } else {
                    this.showEmptyState();
                    $('#es-add-entry, #es-add-break').prop('disabled', true);
                }
            });

            // Add entry
            $('#es-add-entry').on('click', () => this.openEntryModal());
            $('#es-add-break').on('click', () => this.openEntryModal(true));

            // Save entry
            $('#es-save-entry').on('click', () => this.saveEntry());

            // Delete entry
            $('#es-delete-entry').on('click', () => this.deleteEntry());

            // Edit entry
            $(document).on('click', '.es-entry-edit', (e) => {
                const entryId = $(e.currentTarget).closest('.es-agenda-entry').data('entry-id');
                this.openEntryModal(false, entryId);
            });
        },

        loadAgenda: function() {
            $('#es-conference-loading').addClass('active');

            $.ajax({
                url: esTimetable.ajax_url,
                type: 'POST',
                data: {
                    action: 'es_load_timetable',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId
                },
                success: (response) => {
                    $('#es-conference-loading').removeClass('active');
                    if (response.success) {
                        this.data = response.data;
                        this.populateSpeakerSelect();
                        this.renderAgenda();
                    }
                },
                error: () => {
                    $('#es-conference-loading').removeClass('active');
                }
            });
        },

        populateSpeakerSelect: function() {
            const $select = $('#es-entry-speaker');
            $select.find('option:not(:first)').remove();

            if (this.data && this.data.speakers) {
                this.data.speakers.forEach(speaker => {
                    $select.append(`<option value="${speaker.id}">${speaker.name}</option>`);
                });
            }
        },

        renderAgenda: function() {
            const $grid = $('#es-conference-grid');
            $grid.empty();

            if (!this.data || !this.data.entries || !this.data.entries.length) {
                $grid.html(`
                    <div class="es-empty-state">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <p>No entries yet. Click "Add Entry" to get started.</p>
                    </div>
                `);
                return;
            }

            // Sort by time
            const entries = [...this.data.entries].sort((a, b) => {
                return (a.start_time || '').localeCompare(b.start_time || '');
            });

            entries.forEach(entry => {
                const speaker = this.data.speakers.find(s => s.id == entry.speaker_id);
                const room = this.data.rooms.find(r => r.id == entry.room_id);

                const $entry = $(`
                    <div class="es-agenda-entry ${entry.is_break ? 'is-break' : ''}" data-entry-id="${entry.id}">
                        <div class="es-entry-time">
                            <span class="es-time-start">${entry.start_time || '--:--'}</span>
                            <span class="es-time-sep">–</span>
                            <span class="es-time-end">${entry.end_time || '--:--'}</span>
                        </div>
                        <div class="es-entry-content">
                            ${entry.is_break ? 
                                `<span class="es-entry-break"><span class="dashicons dashicons-coffee"></span> ${entry.title || 'Break'}</span>` :
                                `<span class="es-entry-title">${entry.title || ''}</span>
                                 ${speaker ? `<span class="es-entry-speaker">${speaker.name}</span>` : ''}
                                 ${room ? `<span class="es-entry-room">${room.name}</span>` : ''}`
                            }
                        </div>
                        <div class="es-entry-actions">
                            <button type="button" class="es-btn es-btn-sm es-btn-icon es-entry-edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                        </div>
                    </div>
                `);

                $grid.append($entry);
            });
        },

        showEmptyState: function() {
            $('#es-conference-grid').html(`
                <div class="es-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p>Select an event to edit the agenda.</p>
                </div>
            `);
        },

        openEntryModal: function(isBreak = false, entryId = null) {
            const $modal = $('#es-entry-modal');
            
            // Reset form
            $('#es-entry-id').val('');
            $('#es-entry-is-break').val(isBreak ? '1' : '0');
            $('#es-entry-speaker').val('');
            $('#es-entry-room').val('');
            $('#es-entry-title').val('');
            $('#es-entry-start').val('09:00');
            $('#es-entry-end').val('10:00');

            // Show/hide fields
            $('#es-speaker-row').toggle(!isBreak);
            $('#es-room-row').toggle(!isBreak);

            // Set title
            $('#es-modal-title').text(isBreak ? 'Add Break' : (entryId ? 'Edit Entry' : 'Add Entry'));

            // If editing, populate
            if (entryId && this.data && this.data.entries) {
                const entry = this.data.entries.find(e => e.id === entryId);
                if (entry) {
                    $('#es-entry-id').val(entry.id);
                    $('#es-entry-is-break').val(entry.is_break ? '1' : '0');
                    $('#es-entry-speaker').val(entry.speaker_id || '');
                    $('#es-entry-room').val(entry.room_id || '');
                    $('#es-entry-title').val(entry.title || '');
                    $('#es-entry-start').val(entry.start_time || '09:00');
                    $('#es-entry-end').val(entry.end_time || '10:00');
                    
                    $('#es-speaker-row').toggle(!entry.is_break);
                    $('#es-room-row').toggle(!entry.is_break);
                    $('#es-delete-entry').show();
                }
            } else {
                $('#es-delete-entry').hide();
            }

            $modal.addClass('active');
        },

        saveEntry: function() {
            const entry = {
                id: $('#es-entry-id').val() || null,
                speaker_id: $('#es-entry-speaker').val(),
                room_id: $('#es-entry-room').val(),
                title: $('#es-entry-title').val(),
                start_time: $('#es-entry-start').val(),
                end_time: $('#es-entry-end').val(),
                is_break: $('#es-entry-is-break').val() === '1'
            };

            $.ajax({
                url: esTimetable.ajax_url,
                type: 'POST',
                data: {
                    action: 'es_save_timetable_entry',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    entry: entry
                },
                success: (response) => {
                    if (response.success) {
                        $('#es-entry-modal').removeClass('active');
                        this.loadAgenda();
                        ESFestivalTimeline.showToast('Gespeichert!', 'success');
                    }
                }
            });
        },

        deleteEntry: function() {
            if (!confirm(esTimetable.strings.confirm_delete)) return;

            const entryId = $('#es-entry-id').val();

            $.ajax({
                url: esTimetable.ajax_url,
                type: 'POST',
                data: {
                    action: 'es_delete_timetable_entry',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    entry_id: entryId
                },
                success: (response) => {
                    if (response.success) {
                        $('#es-entry-modal').removeClass('active');
                        this.loadAgenda();
                        ESFestivalTimeline.showToast('Gelöscht!', 'success');
                    }
                }
            });
        }
    };

    // =========================================
    // MODE SWITCHER
    // =========================================

    function initModeSwitcher() {
        $('.es-tab[data-mode]').on('click', function() {
            const mode = $(this).data('mode');
            
            // Update tabs
            $('.es-tab[data-mode]').removeClass('active');
            $(this).addClass('active');

            // Show/hide modes
            $('.es-timetable-mode').removeClass('active');
            $(`#es-mode-${mode}`).addClass('active');
        });
    }

    // =========================================
    // INIT
    // =========================================

    $(document).ready(function() {
        // Only init if on timetable page
        if (!$('.es-timetable-wrap').length) return;

        initModeSwitcher();
        ESFestivalTimeline.init();
        ESConferenceAgenda.init();
    });

})(jQuery);
