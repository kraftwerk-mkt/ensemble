/**
 * Ensemble Timetable Editor - Admin JavaScript
 * 
 * @package Ensemble
 * @since 2.9.0
 */

(function($) {
    'use strict';

    const ESTimetable = {
        
        // State
        eventId: 0,
        data: null,
        
        // Settings
        timeInterval: 30,
        defaultDuration: 60,
        slotHeight: 40,
        
        /**
         * Initialize
         */
        init: function() {
            const $container = $('.es-timetable-container');
            if (!$container.length) {
                this.bindEventSelector();
                return;
            }
            
            this.eventId = parseInt($container.data('event-id'), 10);
            this.timeInterval = parseInt($container.data('interval'), 10) || 30;
            
            // Parse initial data
            const $dataScript = $('#es-timetable-data');
            if ($dataScript.length) {
                try {
                    this.data = JSON.parse($dataScript.html());
                    this.defaultDuration = this.data.default_duration || 60;
                } catch (e) {
                    console.error('Failed to parse timetable data:', e);
                }
            }
            
            this.bindEvents();
            this.initDragDrop();
        },
        
        /**
         * Bind event selector (for when no event selected)
         */
        bindEventSelector: function() {
            // Main dropdown
            $('#es-event-select').on('change', function() {
                const eventId = $(this).val();
                if (eventId) {
                    window.location.href = window.location.pathname + '?page=ensemble-timetable&event_id=' + eventId;
                }
            });
            
            // Filter for events list
            $('#es-filter-timetable').on('change', function() {
                const filter = $(this).val();
                const $cards = $('.es-timetable-event-card');
                
                if (!filter) {
                    $cards.show();
                } else {
                    $cards.each(function() {
                        const hasTimetable = $(this).data('has-timetable');
                        $(this).toggle(hasTimetable === filter);
                    });
                }
            });
        },
        
        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // Event selector
            $('#es-event-select').on('change', function() {
                const eventId = $(this).val();
                if (eventId) {
                    window.location.href = window.location.pathname + '?page=ensemble-timetable&event_id=' + eventId;
                }
            });
            
            // Add buttons
            $('#es-add-speaker-btn').on('click', function() {
                self.openAddSpeakerModal();
            });
            
            $('#es-add-break-btn').on('click', function() {
                self.openBreakModal();
            });
            
            // Save button
            $('#es-save-timetable').on('click', function() {
                self.saveTimetable();
            });
            
            // Modal close - direct binding on each button
            $('.es-modal-close').each(function() {
                $(this).on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('[Timetable] Close button clicked directly');
                    $('.es-modal').hide();
                    return false;
                });
            });
            
            // Also add mousedown as fallback
            $(document).on('mousedown', '.es-modal-close', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[Timetable] Close button mousedown');
                $('.es-modal').hide();
                return false;
            });
            
            // Cancel button
            $('.es-modal-cancel').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[Timetable] Cancel button clicked');
                self.closeModals();
                return false;
            });
            
            // Overlay click
            $('.es-modal-overlay').on('click', function(e) {
                console.log('[Timetable] Overlay clicked');
                self.closeModals();
            });
            
            // ESC key to close modals
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeModals();
                }
            });
            
            // Session double-click to edit
            $(document).on('dblclick', '.es-session-block', function(e) {
                if ($(e.target).closest('.es-session-actions').length) return;
                const artistId = $(this).data('artist-id');
                self.openSessionModal(artistId);
            });
            
            // Session edit/remove buttons
            $(document).on('click', '.es-edit-session', function(e) {
                e.stopPropagation();
                const artistId = $(this).closest('.es-session-block').data('artist-id');
                self.openSessionModal(artistId);
            });
            
            $(document).on('click', '.es-remove-session', function(e) {
                e.stopPropagation();
                const artistId = $(this).closest('.es-session-block').data('artist-id');
                self.confirmRemoveSpeaker(artistId);
            });
            
            // Unassigned speaker double-click to edit
            $(document).on('dblclick', '.es-speaker-card', function(e) {
                if ($(e.target).closest('.es-speaker-actions').length) return;
                const artistId = $(this).data('artist-id');
                self.openSessionModal(artistId);
            });
            
            // Unassigned speaker edit/remove buttons
            $(document).on('click', '.es-speaker-card .es-edit-speaker', function(e) {
                e.stopPropagation();
                const artistId = $(this).closest('.es-speaker-card').data('artist-id');
                self.openSessionModal(artistId);
            });
            
            $(document).on('click', '.es-speaker-card .es-remove-speaker', function(e) {
                e.stopPropagation();
                const artistId = $(this).closest('.es-speaker-card').data('artist-id');
                self.confirmRemoveSpeaker(artistId);
            });
            
            // Break double-click to edit
            $(document).on('dblclick', '.es-break-block', function(e) {
                if ($(e.target).hasClass('es-break-remove')) return;
                const index = $(this).data('break-index');
                self.openBreakModal(index);
            });
            
            // Break remove button
            $(document).on('click', '.es-break-remove', function(e) {
                e.stopPropagation();
                const index = $(this).closest('.es-break-block').data('break-index');
                self.confirmRemoveBreak(index);
            });
            
            // Session modal save
            $('#es-session-modal .es-modal-save').on('click', function() {
                self.saveSession();
            });
            
            // Session modal unassign
            $('#es-session-modal .es-modal-unassign').on('click', function() {
                self.unassignSession();
            });
            
            // Break modal save
            $('#es-break-modal .es-modal-save').on('click', function() {
                self.saveBreak();
            });
            
            // Break modal delete
            $('#es-break-modal .es-break-delete').on('click', function() {
                const index = $('#es-break-index').val();
                if (index >= 0) {
                    self.confirmRemoveBreak(parseInt(index));
                    self.closeModals();
                }
            });
            
            // Speaker search
            let searchTimeout;
            $('#es-speaker-search').on('input', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val();
                searchTimeout = setTimeout(function() {
                    self.searchSpeakers(query);
                }, 300);
            });
            
            // Prevent modal close on content click
            $('.es-modal-content').on('click', function(e) {
                e.stopPropagation();
            });
        },
        
        /**
         * Initialize drag and drop
         */
        initDragDrop: function() {
            const self = this;
            
            // Safely destroy existing instances (only if initialized)
            $('.es-speaker-card.es-draggable').each(function() {
                if ($(this).data('ui-draggable')) {
                    $(this).draggable('destroy');
                }
            });
            $('.es-session-block').each(function() {
                if ($(this).data('ui-draggable')) {
                    $(this).draggable('destroy');
                }
            });
            $('.es-break-block').each(function() {
                if ($(this).data('ui-draggable')) {
                    $(this).draggable('destroy');
                }
            });
            $('.es-grid-cell').each(function() {
                if ($(this).data('ui-droppable')) {
                    $(this).droppable('destroy');
                }
            });
            $('#es-unassigned-list').each(function() {
                if ($(this).data('ui-droppable')) {
                    $(this).droppable('destroy');
                }
            });
            
            // Make unassigned speakers draggable
            $('.es-speaker-card.es-draggable').draggable({
                helper: 'clone',
                appendTo: 'body',
                zIndex: 1000,
                cursor: 'grabbing',
                revert: 'invalid',
                start: function(event, ui) {
                    $(this).addClass('is-dragging');
                    ui.helper.addClass('es-dragging-helper');
                },
                stop: function() {
                    $(this).removeClass('is-dragging');
                }
            });
            
            // Make session blocks draggable
            $('.es-session-block').draggable({
                helper: 'clone',
                appendTo: 'body',
                zIndex: 1000,
                cursor: 'grabbing',
                revert: 'invalid',
                start: function(event, ui) {
                    $(this).addClass('is-dragging').css('opacity', 0.5);
                    ui.helper.addClass('es-dragging-helper');
                },
                stop: function() {
                    $(this).removeClass('is-dragging').css('opacity', 1);
                }
            });
            
            // Make break blocks draggable
            $('.es-break-block').draggable({
                helper: 'clone',
                appendTo: 'body',
                zIndex: 1000,
                cursor: 'grabbing',
                revert: 'invalid',
                axis: 'y', // Breaks only move vertically (span all rooms)
                start: function(event, ui) {
                    $(this).addClass('is-dragging').css('opacity', 0.5);
                    ui.helper.addClass('es-dragging-helper es-break-helper');
                },
                stop: function() {
                    $(this).removeClass('is-dragging').css('opacity', 1);
                }
            });
            
            // Make grid cells droppable
            $('.es-grid-cell').droppable({
                accept: '.es-speaker-card, .es-session-block, .es-break-block',
                hoverClass: 'es-drop-hover',
                tolerance: 'pointer',
                drop: function(event, ui) {
                    const $cell = $(this);
                    const time = $cell.data('time');
                    const room = $cell.data('room');
                    
                    // Check if it's a break being dropped
                    if (ui.draggable.hasClass('es-break-block')) {
                        const breakIndex = ui.draggable.data('break-index');
                        console.log('[Timetable] Break drop:', {breakIndex, time});
                        self.updateBreakTime(breakIndex, time);
                        return;
                    }
                    
                    // It's a speaker/session
                    const artistId = ui.draggable.data('artist-id');
                    const duration = ui.draggable.data('duration') || self.defaultDuration;
                    
                    console.log('[Timetable] Drop:', {artistId, time, room, duration});
                    self.updateSession(artistId, time, room, duration);
                }
            });
            
            // Make time column droppable for breaks (easier targeting)
            $('.es-grid-time').droppable({
                accept: '.es-break-block',
                hoverClass: 'es-drop-hover',
                tolerance: 'pointer',
                drop: function(event, ui) {
                    const $row = $(this).closest('.es-grid-row');
                    const time = $row.data('time');
                    const breakIndex = ui.draggable.data('break-index');
                    
                    console.log('[Timetable] Break drop on time:', {breakIndex, time});
                    self.updateBreakTime(breakIndex, time);
                }
            });
            
            // Make unassigned area droppable (to unassign sessions)
            $('#es-unassigned-list').droppable({
                accept: '.es-session-block',
                hoverClass: 'es-drop-hover',
                drop: function(event, ui) {
                    const artistId = ui.draggable.data('artist-id');
                    self.updateSession(artistId, '', '', self.defaultDuration);
                }
            });
        },
        
        /**
         * Update session via AJAX
         */
        updateSession: function(artistId, time, room, duration, sessionTitle) {
            const self = this;
            
            console.log('[Timetable] updateSession:', {artistId, time, room, duration});
            
            this.showLoading();
            
            $.ajax({
                url: esTimetable.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_timetable_update_session',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    artist_id: artistId,
                    time: time || '',
                    venue: room || '',
                    duration: duration || this.defaultDuration,
                    session_title: sessionTitle || ''
                },
                success: function(response) {
                    console.log('[Timetable] updateSession response:', response);
                    self.hideLoading();
                    
                    if (response.success && response.data) {
                        self.data = response.data;
                        self.renderAll();
                    } else {
                        alert(response.data?.message || 'Error updating session');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Timetable] updateSession error:', status, error);
                    self.hideLoading();
                    alert('Network error: ' + error);
                }
            });
        },
        
        /**
         * Render everything
         */
        renderAll: function() {
            console.log('[Timetable] renderAll called, data:', this.data);
            try {
                this.renderSessions();
                this.renderBreaks();
                this.renderUnassigned();
                this.initDragDrop();
                console.log('[Timetable] renderAll complete');
            } catch (e) {
                console.error('[Timetable] renderAll error:', e);
            }
        },
        
        /**
         * Render sessions on grid
         */
        renderSessions: function() {
            const self = this;
            
            // Remove existing
            $('.es-session-block').remove();
            
            if (!this.data || !this.data.sessions) return;
            
            const $gridBody = $('#es-grid-body');
            const startTime = $('.es-timetable-container').data('start-time') || '08:00';
            const startMinutes = this.timeToMinutes(startTime);
            const rooms = this.data.rooms || [];
            const roomCount = rooms.length || 1;
            
            this.data.sessions.forEach(function(session) {
                if (!session.time) return;
                
                // Find room index
                let roomIndex = 0;
                for (let i = 0; i < rooms.length; i++) {
                    if (rooms[i].name === session.venue) {
                        roomIndex = i;
                        break;
                    }
                }
                
                const duration = session.duration || self.defaultDuration;
                const sessionStart = self.timeToMinutes(session.time);
                const offsetMinutes = sessionStart - startMinutes;
                
                const topPx = (offsetMinutes / self.timeInterval) * self.slotHeight;
                const heightPx = Math.max((duration / self.timeInterval) * self.slotHeight, 60);
                
                const left = 'calc(80px + ' + roomIndex + ' * (100% - 80px) / ' + roomCount + ' + 4px)';
                const width = 'calc((100% - 80px) / ' + roomCount + ' - 8px)';
                
                const title = session.session_title || session.artist_name;
                const endTime = self.minutesToTime(sessionStart + duration);
                
                const $block = $(`
                    <div class="es-session-block" 
                         data-artist-id="${session.artist_id}"
                         data-duration="${duration}"
                         data-room="${session.venue || ''}"
                         style="position: absolute; top: ${topPx}px; height: ${heightPx}px; left: ${left}; width: ${width}; z-index: 10;">
                        <div class="es-session-time">${session.time} - ${endTime}</div>
                        <div class="es-session-title">${self.escapeHtml(title)}</div>
                        <div class="es-session-speaker">
                            ${session.artist_image ? `<img src="${session.artist_image}" alt="">` : ''}
                            <span>${self.escapeHtml(session.artist_name)}</span>
                        </div>
                        <div class="es-session-actions">
                            <button type="button" class="es-edit-session" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="es-remove-session" title="Remove">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                `);
                
                $gridBody.append($block);
            });
            
            // Update stats
            const totalSpeakers = (this.data.sessions?.length || 0) + (this.data.unassigned?.length || 0);
            $('.es-stat-speakers').text(totalSpeakers);
            $('.es-stat-sessions').text(this.data.sessions?.length || 0);
        },
        
        /**
         * Render breaks on grid
         */
        renderBreaks: function() {
            const self = this;
            
            // Remove existing
            $('.es-break-block').remove();
            
            if (!this.data || !this.data.breaks) return;
            
            const $gridBody = $('#es-grid-body');
            const startTime = $('.es-timetable-container').data('start-time') || '08:00';
            const startMinutes = this.timeToMinutes(startTime);
            
            const icons = {
                coffee: '☕', lunch: '🍽️', networking: '🤝',
                registration: '📋', pause: '⏸️', discussion: '💬'
            };
            
            this.data.breaks.forEach(function(breakItem, index) {
                if (!breakItem.time) return;
                
                const duration = breakItem.duration || 30;
                const breakStart = self.timeToMinutes(breakItem.time);
                const offsetMinutes = breakStart - startMinutes;
                
                const topPx = (offsetMinutes / self.timeInterval) * self.slotHeight;
                const heightPx = Math.max((duration / self.timeInterval) * self.slotHeight, 30);
                
                const icon = icons[breakItem.icon] || icons.pause;
                
                const $block = $(`
                    <div class="es-break-block" 
                         data-break-index="${index}"
                         style="position: absolute; top: ${topPx}px; height: ${heightPx}px; left: 80px; right: 0; z-index: 5;">
                        <span class="es-break-icon">${icon}</span>
                        <span class="es-break-title">${self.escapeHtml(breakItem.title)}</span>
                        <span class="es-break-time">${breakItem.time}</span>
                        <span class="es-break-duration">${duration} min</span>
                        <button type="button" class="es-break-remove" title="Remove">×</button>
                    </div>
                `);
                
                $gridBody.append($block);
            });
        },
        
        /**
         * Render unassigned speakers
         */
        renderUnassigned: function() {
            const self = this;
            const $list = $('#es-unassigned-list');
            $list.empty();
            
            if (!this.data || !this.data.unassigned || this.data.unassigned.length === 0) {
                $list.html('<p class="es-no-unassigned">All speakers have been assigned.</p>');
                $('#es-unassigned-speakers .es-count').text('(0)');
                return;
            }
            
            this.data.unassigned.forEach(function(speaker) {
                $list.append(`
                    <div class="es-speaker-card es-draggable" 
                         data-artist-id="${speaker.artist_id}"
                         data-duration="${speaker.duration || self.defaultDuration}">
                        ${speaker.artist_image ? 
                            `<img src="${speaker.artist_image}" alt="" class="es-speaker-image">` : 
                            '<div class="es-speaker-image es-no-image"><span class="dashicons dashicons-admin-users"></span></div>'}
                        <div class="es-speaker-info">
                            <div class="es-speaker-name">${self.escapeHtml(speaker.artist_name)}</div>
                            ${speaker.artist_role ? `<div class="es-speaker-role">${self.escapeHtml(speaker.artist_role)}</div>` : ''}
                        </div>
                        <div class="es-speaker-actions">
                            <button type="button" class="es-edit-speaker" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="es-remove-speaker" title="Remove">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                `);
            });
            
            $('#es-unassigned-speakers .es-count').text('(' + this.data.unassigned.length + ')');
        },
        
        /**
         * Open session edit modal
         */
        openSessionModal: function(artistId) {
            const self = this;
            
            // Find session data
            let session = null;
            if (this.data.sessions) {
                session = this.data.sessions.find(s => s.artist_id == artistId);
            }
            if (!session && this.data.unassigned) {
                session = this.data.unassigned.find(s => s.artist_id == artistId);
            }
            
            if (!session) return;
            
            // Fill modal
            $('#es-session-artist-id').val(artistId);
            $('#es-session-title').val(session.session_title || '');
            $('#es-session-time').val(session.time || '');
            $('#es-session-duration').val(session.duration || this.defaultDuration);
            
            // Speaker display
            $('#es-session-speaker-display').html(`
                ${session.artist_image ? `<img src="${session.artist_image}" alt="">` : ''}
                <span>${this.escapeHtml(session.artist_name)}</span>
            `);
            
            // Room select
            const $roomSelect = $('#es-session-room');
            $roomSelect.empty().append('<option value="">-- Select Room --</option>');
            if (this.data.rooms) {
                this.data.rooms.forEach(function(room) {
                    $roomSelect.append(`<option value="${room.name}" ${room.name === session.venue ? 'selected' : ''}>${room.name}</option>`);
                });
            }
            
            // Show/hide unassign button
            $('#es-session-modal .es-modal-unassign').toggle(!!session.time);
            
            $('#es-session-modal').show();
        },
        
        /**
         * Save session from modal
         */
        saveSession: function() {
            const artistId = $('#es-session-artist-id').val();
            const time = $('#es-session-time').val();
            const room = $('#es-session-room').val();
            const duration = $('#es-session-duration').val();
            const title = $('#es-session-title').val();
            
            this.closeModals();
            this.updateSession(artistId, time, room, duration, title);
        },
        
        /**
         * Unassign session
         */
        unassignSession: function() {
            const artistId = $('#es-session-artist-id').val();
            this.closeModals();
            this.updateSession(artistId, '', '', this.defaultDuration, '');
        },
        
        /**
         * Open break modal
         */
        openBreakModal: function(index) {
            const isEdit = index !== undefined && index >= 0;
            let breakData = {};
            
            if (isEdit && this.data.breaks && this.data.breaks[index]) {
                breakData = this.data.breaks[index];
            }
            
            $('#es-break-index').val(isEdit ? index : -1);
            $('#es-break-title-input').val(breakData.title || '');
            $('#es-break-time').val(breakData.time || '');
            $('#es-break-duration-input').val(breakData.duration || 30);
            $('#es-break-type').val(breakData.icon || 'pause');
            
            // Update button text and show delete
            $('#es-break-modal .es-modal-save').text(isEdit ? 'Update' : 'Add Break');
            $('#es-break-modal .es-break-delete').toggle(isEdit);
            $('#es-break-modal h2').text(isEdit ? 'Edit Break' : 'Add Break');
            
            $('#es-break-modal').show();
        },
        
        /**
         * Save break from modal
         */
        saveBreak: function() {
            const self = this;
            const index = parseInt($('#es-break-index').val());
            const isEdit = index >= 0;
            
            const breakData = {
                time: $('#es-break-time').val(),
                title: $('#es-break-title-input').val() || 'Break',
                duration: parseInt($('#es-break-duration-input').val()) || 30,
                icon: $('#es-break-type').val() || 'pause'
            };
            
            if (!breakData.time) {
                alert('Please enter a time');
                return;
            }
            
            this.closeModals();
            this.showLoading();
            
            $.ajax({
                url: esTimetable.ajaxUrl,
                type: 'POST',
                data: {
                    action: isEdit ? 'es_timetable_update_break' : 'es_timetable_add_break',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    break_index: index,
                    time: breakData.time,
                    title: breakData.title,
                    duration: breakData.duration,
                    icon: breakData.icon
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.data = response.data;
                        self.renderAll();
                    } else {
                        alert(response.data?.message || 'Error saving break');
                    }
                    self.hideLoading();
                },
                error: function() {
                    alert('Network error');
                    self.hideLoading();
                }
            });
        },
        
        /**
         * Update break time via drag & drop
         */
        updateBreakTime: function(breakIndex, newTime) {
            const self = this;
            
            if (breakIndex < 0 || !this.data.breaks || !this.data.breaks[breakIndex]) {
                console.error('[Timetable] Invalid break index:', breakIndex);
                return;
            }
            
            const breakData = this.data.breaks[breakIndex];
            
            console.log('[Timetable] updateBreakTime:', {breakIndex, oldTime: breakData.time, newTime});
            
            this.showLoading();
            
            $.ajax({
                url: esTimetable.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_timetable_update_break',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    break_index: breakIndex,
                    time: newTime,
                    title: breakData.title,
                    duration: breakData.duration,
                    icon: breakData.icon
                },
                success: function(response) {
                    console.log('[Timetable] updateBreakTime response:', response);
                    self.hideLoading();
                    
                    if (response.success && response.data) {
                        self.data = response.data;
                        self.renderAll();
                    } else {
                        alert(response.data?.message || 'Error updating break');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Timetable] updateBreakTime error:', status, error);
                    self.hideLoading();
                    alert('Network error');
                }
            });
        },
        
        /**
         * Confirm and remove break
         */
        confirmRemoveBreak: function(index) {
            if (!confirm('Remove this break?')) return;
            
            const self = this;
            this.showLoading();
            
            $.ajax({
                url: esTimetable.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_timetable_remove_break',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    break_index: index
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.data = response.data;
                        self.renderAll();
                    }
                    self.hideLoading();
                },
                error: function() {
                    alert('Network error');
                    self.hideLoading();
                }
            });
        },
        
        /**
         * Open add speaker modal
         */
        openAddSpeakerModal: function() {
            $('#es-speaker-search').val('');
            $('#es-speaker-results').html('<p class="es-loading">Loading speakers...</p>');
            $('#es-add-speaker-modal').show();
            
            // Load all speakers immediately
            this.searchSpeakers('');
            
            setTimeout(function() {
                $('#es-speaker-search').focus();
            }, 100);
        },
        
        /**
         * Search speakers via AJAX
         */
        searchSpeakers: function(query) {
            const self = this;
            
            $.ajax({
                url: esTimetable.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_timetable_search_speakers',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    search: query
                },
                success: function(response) {
                    if (response.success && response.data?.speakers) {
                        self.renderSpeakerResults(response.data.speakers);
                    } else {
                        $('#es-speaker-results').html('<p class="es-no-results">No speakers found.</p>');
                    }
                },
                error: function() {
                    $('#es-speaker-results').html('<p class="es-error">Error loading speakers.</p>');
                }
            });
        },
        
        /**
         * Render speaker search results
         */
        renderSpeakerResults: function(speakers) {
            const self = this;
            const $container = $('#es-speaker-results');
            $container.empty();
            
            console.log('[Timetable] renderSpeakerResults:', speakers.length, 'speakers');
            
            if (!speakers.length) {
                $container.html('<p class="es-no-results">No speakers found.</p>');
                return;
            }
            
            speakers.forEach(function(speaker) {
                const $item = $(`
                    <div class="es-speaker-result ${speaker.assigned ? 'is-assigned' : ''}" data-speaker-id="${speaker.id}">
                        ${speaker.image ? 
                            `<img src="${speaker.image}" alt="">` : 
                            '<div class="es-no-image"><span class="dashicons dashicons-admin-users"></span></div>'}
                        <div class="es-speaker-info">
                            <div class="es-speaker-name">${self.escapeHtml(speaker.name)}</div>
                            ${speaker.role ? `<div class="es-speaker-role">${self.escapeHtml(speaker.role)}</div>` : ''}
                        </div>
                        ${speaker.assigned ? '<span class="es-assigned-badge">Added</span>' : '<span class="es-add-badge">+ Add</span>'}
                    </div>
                `);
                
                if (!speaker.assigned) {
                    $item.on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('[Timetable] Speaker clicked:', speaker.id, speaker.name);
                        self.addSpeaker(speaker.id);
                    });
                    $item.css('cursor', 'pointer');
                }
                
                $container.append($item);
            });
        },
        
        /**
         * Add speaker to event
         */
        addSpeaker: function(speakerId) {
            const self = this;
            
            console.log('[Timetable] addSpeaker called:', {speakerId, eventId: this.eventId});
            
            if (!this.eventId) {
                console.error('[Timetable] No eventId set!');
                alert('Error: No event selected');
                return;
            }
            
            this.closeModals();
            this.showLoading();
            
            $.ajax({
                url: esTimetable.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_timetable_add_speaker',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    speaker_id: speakerId
                },
                success: function(response) {
                    console.log('[Timetable] addSpeaker response:', response);
                    self.hideLoading();
                    
                    if (response.success && response.data) {
                        self.data = response.data;
                        self.renderAll();
                    } else {
                        alert(response.data?.message || 'Error adding speaker');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Timetable] addSpeaker error:', status, error, xhr.responseText);
                    self.hideLoading();
                    alert('Network error: ' + error);
                }
            });
        },
        
        /**
         * Confirm and remove speaker
         */
        confirmRemoveSpeaker: function(artistId) {
            if (!confirm('Remove this speaker from the event?')) return;
            
            const self = this;
            this.showLoading();
            
            $.ajax({
                url: esTimetable.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_timetable_remove_speaker',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    speaker_id: artistId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.data = response.data;
                        self.renderAll();
                    }
                    self.hideLoading();
                },
                error: function() {
                    alert('Network error');
                    self.hideLoading();
                }
            });
        },
        
        /**
         * Save complete timetable
         */
        saveTimetable: function() {
            const self = this;
            const $btn = $('#es-save-timetable');
            
            $btn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: esTimetable.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_timetable_save',
                    nonce: esTimetable.nonce,
                    event_id: this.eventId,
                    sessions: JSON.stringify(this.data.sessions || []),
                    breaks: JSON.stringify(this.data.breaks || [])
                },
                success: function(response) {
                    if (response.success) {
                        $btn.text('✓ Saved!');
                        setTimeout(function() {
                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Timetable');
                        }, 2000);
                    } else {
                        alert(response.data?.message || 'Error saving');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Timetable');
                    }
                },
                error: function() {
                    alert('Network error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Timetable');
                }
            });
        },
        
        /**
         * Close all modals
         */
        closeModals: function() {
            $('.es-modal').hide();
        },
        
        /**
         * Show loading overlay
         */
        showLoading: function() {
            if (!$('#es-loading-overlay').length) {
                $('body').append('<div id="es-loading-overlay"><div class="es-spinner"></div></div>');
            }
            $('#es-loading-overlay').show();
        },
        
        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            $('#es-loading-overlay').hide();
        },
        
        /**
         * Time to minutes helper
         */
        timeToMinutes: function(time) {
            if (!time) return 0;
            const parts = time.split(':');
            return parseInt(parts[0]) * 60 + parseInt(parts[1] || 0);
        },
        
        /**
         * Minutes to time helper
         */
        minutesToTime: function(minutes) {
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize on ready
    $(document).ready(function() {
        ESTimetable.init();
    });

})(jQuery);
