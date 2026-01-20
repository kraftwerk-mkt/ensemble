/**
 * Ensemble Reservations Blocks
 * 
 * Registers all reservation-related Gutenberg blocks.
 * 
 * @package Ensemble
 * @since 3.0.0
 */

(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl, ToggleControl, RangeControl, Placeholder } = wp.components;
    const { Fragment, createElement: el } = wp.element;
    const { __ } = wp.i18n;
    const ServerSideRender = wp.serverSideRender;

    // Get localized data
    const blockData = window.ensembleReservationsBlocks || {};
    const events = blockData.events || [];

    // Build event options
    const eventOptions = [
        { value: 0, label: __('Current Event (on event pages)', 'ensemble') },
        ...events.map(function(event) {
            return {
                value: event.value,
                label: event.label
            };
        })
    ];

    // =========================================================================
    // Block: Reservation Form
    // =========================================================================
    registerBlockType('ensemble/reservation-form', {
        title: __('Reservation Form', 'ensemble'),
        description: __('Display a reservation form for events.', 'ensemble'),
        icon: 'clipboard',
        category: 'ensemble',
        keywords: [
            __('reservation', 'ensemble'),
            __('booking', 'ensemble'),
            __('guestlist', 'ensemble'),
            __('form', 'ensemble'),
        ],
        supports: {
            html: false,
            align: ['wide', 'full'],
        },
        attributes: {
            eventId: { type: 'number', default: 0 },
            buttonText: { type: 'string', default: '' },
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Settings', 'ensemble'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Select Event', 'ensemble'),
                            value: attributes.eventId,
                            options: eventOptions,
                            onChange: function(value) {
                                setAttributes({ eventId: parseInt(value) });
                            },
                            help: events.length === 0 ? __('No events with reservations enabled.', 'ensemble') : ''
                        }),
                        el(TextControl, {
                            label: __('Button Text', 'ensemble'),
                            value: attributes.buttonText,
                            onChange: function(value) {
                                setAttributes({ buttonText: value });
                            },
                            placeholder: __('Submit reservation', 'ensemble')
                        })
                    )
                ),
                el('div', blockProps,
                    el(ServerSideRender, {
                        block: 'ensemble/reservation-form',
                        attributes: attributes,
                        EmptyResponsePlaceholder: function() {
                            return el(Placeholder, {
                                icon: 'clipboard',
                                label: __('Reservation Form', 'ensemble'),
                                instructions: __('Select an event or add this block to an event page.', 'ensemble')
                            });
                        }
                    })
                )
            );
        },

        save: function() {
            return null;
        }
    });

    // =========================================================================
    // Block: Guest List
    // =========================================================================
    registerBlockType('ensemble/guestlist', {
        title: __('Guest List', 'ensemble'),
        description: __('Display the guest list for an event (admin only).', 'ensemble'),
        icon: 'groups',
        category: 'ensemble',
        keywords: [
            __('guestlist', 'ensemble'),
            __('guests', 'ensemble'),
            __('reservations', 'ensemble'),
        ],
        supports: {
            html: false,
            align: ['wide', 'full'],
        },
        attributes: {
            eventId: { type: 'number', default: 0 },
            limit: { type: 'number', default: 0 },
            showCheckedIn: { type: 'boolean', default: false },
            showType: { type: 'boolean', default: true },
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Settings', 'ensemble'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Select Event', 'ensemble'),
                            value: attributes.eventId,
                            options: eventOptions,
                            onChange: function(value) {
                                setAttributes({ eventId: parseInt(value) });
                            }
                        }),
                        el(RangeControl, {
                            label: __('Limit', 'ensemble'),
                            value: attributes.limit,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            },
                            min: 0,
                            max: 100,
                            help: __('0 = Show all', 'ensemble')
                        }),
                        el(ToggleControl, {
                            label: __('Show Check-in Status', 'ensemble'),
                            checked: attributes.showCheckedIn,
                            onChange: function(value) {
                                setAttributes({ showCheckedIn: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Type', 'ensemble'),
                            checked: attributes.showType,
                            onChange: function(value) {
                                setAttributes({ showType: value });
                            }
                        })
                    )
                ),
                el('div', blockProps,
                    el(ServerSideRender, {
                        block: 'ensemble/guestlist',
                        attributes: attributes,
                        EmptyResponsePlaceholder: function() {
                            return el(Placeholder, {
                                icon: 'groups',
                                label: __('Guest List', 'ensemble'),
                                instructions: __('Only visible to authorized users.', 'ensemble')
                            });
                        }
                    })
                )
            );
        },

        save: function() {
            return null;
        }
    });

    // =========================================================================
    // Block: Availability
    // =========================================================================
    registerBlockType('ensemble/availability', {
        title: __('Availability', 'ensemble'),
        description: __('Show reservation availability for an event.', 'ensemble'),
        icon: 'chart-bar',
        category: 'ensemble',
        keywords: [
            __('availability', 'ensemble'),
            __('capacity', 'ensemble'),
            __('spots', 'ensemble'),
        ],
        supports: {
            html: false,
            align: ['left', 'center', 'right'],
        },
        attributes: {
            eventId: { type: 'number', default: 0 },
            style: { type: 'string', default: 'badge' },
            showNumbers: { type: 'boolean', default: true },
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Settings', 'ensemble'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Select Event', 'ensemble'),
                            value: attributes.eventId,
                            options: eventOptions,
                            onChange: function(value) {
                                setAttributes({ eventId: parseInt(value) });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Style', 'ensemble'),
                            value: attributes.style,
                            options: [
                                { value: 'badge', label: __('Badge', 'ensemble') },
                                { value: 'text', label: __('Text', 'ensemble') },
                            ],
                            onChange: function(value) {
                                setAttributes({ style: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Numbers', 'ensemble'),
                            checked: attributes.showNumbers,
                            onChange: function(value) {
                                setAttributes({ showNumbers: value });
                            }
                        })
                    )
                ),
                el('div', blockProps,
                    el(ServerSideRender, {
                        block: 'ensemble/availability',
                        attributes: attributes,
                        EmptyResponsePlaceholder: function() {
                            return el(Placeholder, {
                                icon: 'chart-bar',
                                label: __('Availability', 'ensemble'),
                                instructions: __('Select an event to display availability.', 'ensemble')
                            });
                        }
                    })
                )
            );
        },

        save: function() {
            return null;
        }
    });

})(window.wp);
