/**
 * Agenda Add-on - Admin JavaScript
 * 
 * Drag & Drop Agenda Editor for Event Wizard
 * 
 * @package Ensemble
 * @subpackage Addons/Agenda
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Namespace
    window.EnsembleAgenda = window.EnsembleAgenda || {};
    
    /**
     * Agenda Editor
     */
    EnsembleAgenda.Editor = {
        
        // Data
        agenda: {
            days: [],
            rooms: [],
            tracks: []
        },
        
        // Available artists/speakers
        speakers: [],
        
        // Session types from PHP
        sessionTypes: {},
        
        // Current event ID
        eventId: 0,
        
        // i18n strings
        i18n: {},
        
        /**
         * Initialize
         */
        init: function() {
            var self = this;
            
            // Check if we're in the wizard
            if (!$('#es-agenda-editor').length) {
                return;
            }
            
            // Get localized data
            if (typeof ensembleAgenda !== 'undefined') {
                this.sessionTypes = ensembleAgenda.sessionTypes || {};
                this.i18n = ensembleAgenda.i18n || {};
            }
            
            // Get event ID
            this.eventId = $('#es-event-id').val() || 0;
            
            // Load existing agenda data
            this.loadAgendaData();
            
            // Bind events
            this.bindEvents();
            
            // Initialize sortable
            this.initSortable();
        },
        
        /**
         * Load agenda data from hidden field or AJAX
         */
        loadAgendaData: function() {
            var self = this;
            
            // Try to get from wizard data
            if (typeof esWizardData !== 'undefined' && esWizardData.event && esWizardData.event.agenda) {
                this.agenda = esWizardData.event.agenda;
                this.render();
                return;
            }
            
            // Try hidden field
            var hiddenData = $('#es-agenda-data').val();
            if (hiddenData) {
                try {
                    this.agenda = JSON.parse(hiddenData);
                    this.render();
                    return;
                } catch (e) {
                    console.error('Error parsing agenda data:', e);
                }
            }
            
            // Default empty agenda
            this.agenda = {
                days: [],
                rooms: [],
                tracks: []
            };
            this.render();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            var $editor = $('#es-agenda-editor');
            
            // Add day
            $editor.on('click', '.es-agenda-add-day', function() {
                self.addDay();
            });
            
            // Delete day
            $editor.on('click', '.delete-day', function() {
                var dayIndex = $(this).closest('.es-agenda-day').data('day-index');
                self.deleteDay(dayIndex);
            });
            
            // Add session
            $editor.on('click', '.add-session', function() {
                var dayIndex = $(this).closest('.es-agenda-day').data('day-index');
                self.addSession(dayIndex, 'talk');
            });
            
            // Add break
            $editor.on('click', '.add-break', function() {
                var dayIndex = $(this).closest('.es-agenda-day').data('day-index');
                self.addSession(dayIndex, 'break');
            });
            
            // Delete session
            $editor.on('click', '.delete-session', function() {
                var $session = $(this).closest('.es-agenda-session');
                var dayIndex = $session.closest('.es-agenda-day').data('day-index');
                var sessionIndex = $session.data('session-index');
                self.deleteSession(dayIndex, sessionIndex);
            });
            
            // Edit session
            $editor.on('click', '.edit-session', function() {
                var $session = $(this).closest('.es-agenda-session');
                var dayIndex = $session.closest('.es-agenda-day').data('day-index');
                var sessionIndex = $session.data('session-index');
                self.editSession(dayIndex, sessionIndex);
            });
            
            // Update session fields (live)
            $editor.on('change input', '.es-agenda-session input, .es-agenda-session select', function() {
                self.updateSessionFromField($(this));
            });
            
            // Update day fields
            $editor.on('change input', '.es-agenda-day-date input', function() {
                var dayIndex = $(this).closest('.es-agenda-day').data('day-index');
                self.agenda.days[dayIndex].date = $(this).val();
                self.saveToHidden();
            });
            
            // Add speaker button
            $editor.on('click', '.es-agenda-add-speaker', function() {
                var $session = $(this).closest('.es-agenda-session');
                var dayIndex = $session.closest('.es-agenda-day').data('day-index');
                var sessionIndex = $session.data('session-index');
                self.openSpeakerPicker(dayIndex, sessionIndex);
            });
            
            // Remove speaker
            $editor.on('click', '.remove-speaker', function() {
                var $session = $(this).closest('.es-agenda-session');
                var dayIndex = $session.closest('.es-agenda-day').data('day-index');
                var sessionIndex = $session.data('session-index');
                var speakerId = $(this).closest('.es-agenda-speaker-tag').data('speaker-id');
                self.removeSpeaker(dayIndex, sessionIndex, speakerId);
            });
            
            // Add room
            $editor.on('click', '.add-room', function() {
                self.addRoom();
            });
            
            // Remove room
            $editor.on('click', '.remove-room', function() {
                var room = $(this).closest('.es-agenda-room-tag').find('input').val();
                self.removeRoom(room);
            });
            
            // Update room name
            $editor.on('change', '.es-agenda-room-tag input', function() {
                self.updateRooms();
            });
        },
        
        /**
         * Initialize sortable
         */
        initSortable: function() {
            var self = this;
            
            // Sortable days
            $('#es-agenda-days').sortable({
                handle: '.es-agenda-day-drag',
                placeholder: 'es-agenda-day-placeholder',
                update: function(event, ui) {
                    self.reorderDays();
                }
            });
            
            // Sortable sessions within days
            $('.es-agenda-sessions-list').sortable({
                handle: '.es-agenda-session-drag',
                connectWith: '.es-agenda-sessions-list',
                placeholder: 'es-agenda-session-placeholder',
                update: function(event, ui) {
                    self.reorderSessions(ui.item);
                }
            });
        },
        
        /**
         * Render the editor
         */
        render: function() {
            var self = this;
            var $days = $('#es-agenda-days');
            
            $days.empty();
            
            if (this.agenda.days.length === 0) {
                $days.html(this.renderEmptyState());
            } else {
                this.agenda.days.forEach(function(day, dayIndex) {
                    $days.append(self.renderDay(day, dayIndex));
                });
            }
            
            // Render rooms
            this.renderRooms();
            
            // Re-init sortable
            this.initSortable();
            
            // Save to hidden field
            this.saveToHidden();
        },
        
        /**
         * Render empty state
         */
        renderEmptyState: function() {
            return '<div class="es-agenda-empty">' +
                '<p>' + (this.i18n.noAgenda || 'Noch keine Agenda angelegt') + '</p>' +
                '</div>';
        },
        
        /**
         * Render a day
         */
        renderDay: function(day, dayIndex) {
            var self = this;
            var dayNum = dayIndex + 1;
            var dateFormatted = day.date ? this.formatDate(day.date) : '';
            
            var html = '<div class="es-agenda-day" data-day-index="' + dayIndex + '">' +
                '<div class="es-agenda-day-header">' +
                    '<div class="es-agenda-day-drag">' + this.getIcon('grip-vertical') + '</div>' +
                    '<div class="es-agenda-day-info">' +
                        '<span class="es-agenda-day-label">' + this.i18n.day + ' ' + dayNum + '</span>' +
                        '<span class="es-agenda-day-date">' +
                            '<input type="date" value="' + (day.date || '') + '">' +
                        '</span>' +
                    '</div>' +
                    '<div class="es-agenda-day-actions">' +
                        '<button type="button" class="delete-day">' + this.getIcon('trash') + '</button>' +
                    '</div>' +
                '</div>' +
                '<div class="es-agenda-sessions">';
            
            if (day.sessions && day.sessions.length > 0) {
                html += '<div class="es-agenda-sessions-list">';
                day.sessions.forEach(function(session, sessionIndex) {
                    html += self.renderSession(session, sessionIndex, dayIndex);
                });
                html += '</div>';
            } else {
                html += '<div class="es-agenda-sessions-empty">' +
                    this.getIcon('calendar') +
                    '<p>' + (this.i18n.noSessions || 'Keine Sessions') + '</p>' +
                '</div>';
            }
            
            html += '</div>' +
                '<div class="es-agenda-add-buttons">' +
                    '<button type="button" class="es-agenda-add-btn add-session">' +
                        this.getIcon('plus') + ' ' + this.i18n.addSession +
                    '</button>' +
                    '<button type="button" class="es-agenda-add-btn add-break">' +
                        this.getIcon('coffee') + ' ' + this.i18n.addBreak +
                    '</button>' +
                '</div>' +
            '</div>';
            
            return html;
        },
        
        /**
         * Render a session
         */
        renderSession: function(session, sessionIndex, dayIndex) {
            var typeInfo = this.sessionTypes[session.type] || this.sessionTypes['custom'] || {};
            var isBreak = typeInfo.is_break || false;
            var color = typeInfo.color || '#718096';
            
            var html = '<div class="es-agenda-session ' + (isBreak ? 'is-break' : '') + '" ' +
                'data-session-index="' + sessionIndex + '" style="--session-color: ' + color + '">' +
                '<div class="es-agenda-session-drag">' + this.getIcon('grip-vertical') + '</div>' +
                '<div class="es-agenda-session-type">' + this.getSessionIcon(session.type) + '</div>' +
                '<div class="es-agenda-session-time">' +
                    '<input type="time" class="session-start" value="' + (session.start || '') + '" placeholder="Start">' +
                    '<span>–</span>' +
                    '<input type="time" class="session-end" value="' + (session.end || '') + '" placeholder="Ende">' +
                '</div>' +
                '<div class="es-agenda-session-content">' +
                    '<div class="es-agenda-session-title">' +
                        '<select class="session-type">' + this.renderSessionTypeOptions(session.type) + '</select>' +
                        '<input type="text" class="session-title" value="' + this.escapeHtml(session.title || '') + '" placeholder="Titel...">' +
                    '</div>' +
                    '<div class="es-agenda-session-meta">' +
                        '<div class="es-agenda-session-meta-item">' +
                            this.getIcon('map-pin') +
                            '<select class="session-room">' +
                                '<option value="">– ' + this.i18n.room + ' –</option>' +
                                this.renderRoomOptions(session.room) +
                            '</select>' +
                        '</div>' +
                    '</div>';
            
            // Speakers (not for breaks)
            if (!isBreak) {
                html += '<div class="es-agenda-session-speakers">';
                if (session.speakers && session.speakers.length > 0) {
                    session.speakers.forEach(function(speaker) {
                        html += '<span class="es-agenda-speaker-tag" data-speaker-id="' + speaker.id + '">' +
                            (speaker.image ? '<img src="' + speaker.image + '" alt="">' : '') +
                            '<span>' + (speaker.name || 'Speaker #' + speaker.id) + '</span>' +
                            '<button type="button" class="remove-speaker">×</button>' +
                        '</span>';
                    });
                }
                html += '<button type="button" class="es-agenda-add-speaker">' +
                    this.getIcon('plus') + ' ' + this.i18n.speaker +
                '</button></div>';
            }
            
            html += '</div>' +
                '<div class="es-agenda-session-actions">' +
                    '<button type="button" class="edit-session" title="Bearbeiten">' + this.getIcon('edit') + '</button>' +
                    '<button type="button" class="delete-session" title="Löschen">' + this.getIcon('trash') + '</button>' +
                '</div>' +
            '</div>';
            
            return html;
        },
        
        /**
         * Render session type options
         */
        renderSessionTypeOptions: function(selectedType) {
            var html = '';
            for (var type in this.sessionTypes) {
                var info = this.sessionTypes[type];
                var selected = type === selectedType ? ' selected' : '';
                html += '<option value="' + type + '"' + selected + '>' + info.label + '</option>';
            }
            return html;
        },
        
        /**
         * Render room options
         */
        renderRoomOptions: function(selectedRoom) {
            var html = '';
            this.agenda.rooms.forEach(function(room) {
                var selected = room === selectedRoom ? ' selected' : '';
                html += '<option value="' + room + '"' + selected + '>' + room + '</option>';
            });
            return html;
        },
        
        /**
         * Render rooms manager
         */
        renderRooms: function() {
            var self = this;
            var $container = $('#es-agenda-rooms-list');
            
            if (!$container.length) return;
            
            $container.empty();
            
            this.agenda.rooms.forEach(function(room) {
                $container.append(
                    '<span class="es-agenda-room-tag">' +
                        '<input type="text" value="' + self.escapeHtml(room) + '">' +
                        '<button type="button" class="remove-room">×</button>' +
                    '</span>'
                );
            });
            
            $container.append(
                '<button type="button" class="es-agenda-add-btn add-room">' +
                    self.getIcon('plus') + ' Raum' +
                '</button>'
            );
        },
        
        /**
         * Add a new day
         */
        addDay: function() {
            var newDay = {
                date: '',
                label: this.i18n.day + ' ' + (this.agenda.days.length + 1),
                sessions: []
            };
            
            this.agenda.days.push(newDay);
            this.render();
        },
        
        /**
         * Delete a day
         */
        deleteDay: function(dayIndex) {
            if (!confirm(this.i18n.confirmDelete)) {
                return;
            }
            
            this.agenda.days.splice(dayIndex, 1);
            this.render();
        },
        
        /**
         * Add a session
         */
        addSession: function(dayIndex, type) {
            type = type || 'talk';
            
            var newSession = {
                id: 'sess_' + Date.now(),
                type: type,
                title: '',
                start: '',
                end: '',
                room: '',
                track: '',
                description: '',
                speakers: [],
                catalog_id: 0,
                materials: [],
                livestream: ''
            };
            
            if (!this.agenda.days[dayIndex].sessions) {
                this.agenda.days[dayIndex].sessions = [];
            }
            
            this.agenda.days[dayIndex].sessions.push(newSession);
            this.render();
        },
        
        /**
         * Delete a session
         */
        deleteSession: function(dayIndex, sessionIndex) {
            if (!confirm(this.i18n.confirmDelete)) {
                return;
            }
            
            this.agenda.days[dayIndex].sessions.splice(sessionIndex, 1);
            this.render();
        },
        
        /**
         * Update session from field change
         */
        updateSessionFromField: function($field) {
            var $session = $field.closest('.es-agenda-session');
            var dayIndex = $session.closest('.es-agenda-day').data('day-index');
            var sessionIndex = $session.data('session-index');
            
            var session = this.agenda.days[dayIndex].sessions[sessionIndex];
            
            if ($field.hasClass('session-title')) {
                session.title = $field.val();
            } else if ($field.hasClass('session-start')) {
                session.start = $field.val();
            } else if ($field.hasClass('session-end')) {
                session.end = $field.val();
            } else if ($field.hasClass('session-type')) {
                session.type = $field.val();
                // Re-render to update styling
                this.render();
                return;
            } else if ($field.hasClass('session-room')) {
                session.room = $field.val();
            }
            
            this.saveToHidden();
        },
        
        /**
         * Edit session (open modal)
         */
        editSession: function(dayIndex, sessionIndex) {
            // TODO: Implement full session editor modal
            console.log('Edit session:', dayIndex, sessionIndex);
        },
        
        /**
         * Reorder days after drag
         */
        reorderDays: function() {
            var self = this;
            var newDays = [];
            
            $('#es-agenda-days .es-agenda-day').each(function() {
                var oldIndex = $(this).data('day-index');
                newDays.push(self.agenda.days[oldIndex]);
            });
            
            this.agenda.days = newDays;
            this.render();
        },
        
        /**
         * Reorder sessions after drag
         */
        reorderSessions: function($item) {
            var self = this;
            var newDayIndex = $item.closest('.es-agenda-day').data('day-index');
            
            // Rebuild sessions for each day
            $('.es-agenda-day').each(function() {
                var dayIndex = $(this).data('day-index');
                var newSessions = [];
                
                $(this).find('.es-agenda-session').each(function() {
                    var oldSessionIndex = $(this).data('session-index');
                    var oldDayIndex = $(this).data('original-day') || dayIndex;
                    
                    if (self.agenda.days[oldDayIndex] && self.agenda.days[oldDayIndex].sessions[oldSessionIndex]) {
                        newSessions.push(self.agenda.days[oldDayIndex].sessions[oldSessionIndex]);
                    }
                });
                
                if (self.agenda.days[dayIndex]) {
                    self.agenda.days[dayIndex].sessions = newSessions;
                }
            });
            
            this.render();
        },
        
        /**
         * Open speaker picker
         */
        openSpeakerPicker: function(dayIndex, sessionIndex) {
            var self = this;
            
            // Get available speakers from wizard data
            var speakers = [];
            if (typeof esWizardData !== 'undefined' && esWizardData.artists) {
                speakers = esWizardData.artists;
            }
            
            // Build modal
            var html = '<div class="es-speaker-picker-overlay">' +
                '<div class="es-speaker-picker">' +
                    '<div class="es-speaker-picker-header">' +
                        '<h4>' + this.i18n.selectSpeaker + '</h4>' +
                        '<button type="button" class="es-speaker-picker-close">×</button>' +
                    '</div>' +
                    '<div class="es-speaker-picker-search">' +
                        '<input type="text" placeholder="Suchen...">' +
                    '</div>' +
                    '<div class="es-speaker-picker-list">';
            
            if (speakers.length > 0) {
                speakers.forEach(function(speaker) {
                    html += '<div class="es-speaker-picker-item" data-speaker-id="' + speaker.id + '">' +
                        '<img src="' + (speaker.image || '') + '" alt="">' +
                        '<div class="es-speaker-picker-item-info">' +
                            '<div class="es-speaker-picker-item-name">' + speaker.name + '</div>' +
                            '<div class="es-speaker-picker-item-role">' + (speaker.role || '') + '</div>' +
                        '</div>' +
                    '</div>';
                });
            } else {
                html += '<p style="padding: 20px; text-align: center; color: #a0aec0;">' +
                    this.i18n.noSpeakers + '</p>';
            }
            
            html += '</div></div></div>';
            
            var $modal = $(html).appendTo('body');
            
            // Close modal
            $modal.on('click', '.es-speaker-picker-close, .es-speaker-picker-overlay', function(e) {
                if (e.target === this) {
                    $modal.remove();
                }
            });
            
            // Search filter
            $modal.on('input', '.es-speaker-picker-search input', function() {
                var query = $(this).val().toLowerCase();
                $modal.find('.es-speaker-picker-item').each(function() {
                    var name = $(this).find('.es-speaker-picker-item-name').text().toLowerCase();
                    $(this).toggle(name.indexOf(query) !== -1);
                });
            });
            
            // Select speaker
            $modal.on('click', '.es-speaker-picker-item', function() {
                var speakerId = $(this).data('speaker-id');
                var speakerName = $(this).find('.es-speaker-picker-item-name').text();
                var speakerImage = $(this).find('img').attr('src');
                
                self.addSpeaker(dayIndex, sessionIndex, {
                    id: speakerId,
                    name: speakerName,
                    image: speakerImage,
                    role: 'speaker'
                });
                
                $modal.remove();
            });
        },
        
        /**
         * Add speaker to session
         */
        addSpeaker: function(dayIndex, sessionIndex, speaker) {
            var session = this.agenda.days[dayIndex].sessions[sessionIndex];
            
            if (!session.speakers) {
                session.speakers = [];
            }
            
            // Check if already added
            var exists = session.speakers.some(function(s) {
                return s.id === speaker.id;
            });
            
            if (!exists) {
                session.speakers.push(speaker);
                this.render();
            }
        },
        
        /**
         * Remove speaker from session
         */
        removeSpeaker: function(dayIndex, sessionIndex, speakerId) {
            var session = this.agenda.days[dayIndex].sessions[sessionIndex];
            
            if (session.speakers) {
                session.speakers = session.speakers.filter(function(s) {
                    return s.id !== speakerId;
                });
                this.render();
            }
        },
        
        /**
         * Add room
         */
        addRoom: function() {
            var roomName = prompt(this.i18n.room + ':');
            if (roomName && roomName.trim()) {
                this.agenda.rooms.push(roomName.trim());
                this.render();
            }
        },
        
        /**
         * Remove room
         */
        removeRoom: function(room) {
            this.agenda.rooms = this.agenda.rooms.filter(function(r) {
                return r !== room;
            });
            this.render();
        },
        
        /**
         * Update rooms from inputs
         */
        updateRooms: function() {
            var self = this;
            this.agenda.rooms = [];
            
            $('#es-agenda-rooms-list .es-agenda-room-tag input').each(function() {
                var val = $(this).val().trim();
                if (val) {
                    self.agenda.rooms.push(val);
                }
            });
            
            this.saveToHidden();
        },
        
        /**
         * Save to hidden field
         */
        saveToHidden: function() {
            $('#es-agenda-data').val(JSON.stringify(this.agenda));
        },
        
        /**
         * Get agenda data
         */
        getData: function() {
            return this.agenda;
        },
        
        /**
         * Get icon SVG
         */
        getIcon: function(name) {
            var icons = {
                'grip-vertical': '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>',
                'plus': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
                'trash': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"/></svg>',
                'edit': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
                'calendar': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'coffee': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>',
                'map-pin': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'
            };
            
            return icons[name] || '';
        },
        
        /**
         * Get session type icon
         */
        getSessionIcon: function(type) {
            var typeInfo = this.sessionTypes[type] || {};
            var iconName = typeInfo.icon || 'calendar';
            
            // Use agenda icons if available
            if (typeof ensembleAgenda !== 'undefined' && ensembleAgenda.iconUrl) {
                return '<img src="' + ensembleAgenda.iconUrl + iconName + '.svg" alt="">';
            }
            
            return this.getIcon(iconName);
        },
        
        /**
         * Format date
         */
        formatDate: function(dateStr) {
            if (!dateStr) return '';
            var date = new Date(dateStr);
            return date.toLocaleDateString('de-DE', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;')
                     .replace(/</g, '&lt;')
                     .replace(/>/g, '&gt;')
                     .replace(/"/g, '&quot;')
                     .replace(/'/g, '&#039;');
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        EnsembleAgenda.Editor.init();
    });
    
    // Also init when wizard tab changes to agenda
    $(document).on('ensemble_wizard_tab_changed', function(e, tabId) {
        if (tabId === 'agenda') {
            EnsembleAgenda.Editor.init();
        }
    });
    
})(jQuery);
