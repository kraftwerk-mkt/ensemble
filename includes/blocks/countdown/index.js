(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var ServerSideRender = wp.serverSideRender;
    var Placeholder = wp.components.Placeholder;

    registerBlockType('ensemble/countdown', {
        title: __('Event Countdown', 'ensemble'),
        description: __('Display a countdown timer to an event or date', 'ensemble'),
        icon: 'clock',
        category: 'ensemble',  // Must match registered category
        keywords: [__('countdown', 'ensemble'), __('timer', 'ensemble'), __('event', 'ensemble')],
        
        attributes: {
            mode: { type: 'string', default: 'event' },
            eventId: { type: 'number', default: 0 },
            date: { type: 'string', default: '' },
            time: { type: 'string', default: '' },
            title: { type: 'string', default: '' },
            style: { type: 'string', default: 'default' },
            showDays: { type: 'boolean', default: true },
            showHours: { type: 'boolean', default: true },
            showMinutes: { type: 'boolean', default: true },
            showSeconds: { type: 'boolean', default: true },
            showLabels: { type: 'boolean', default: true },
            showTitle: { type: 'boolean', default: true },
            showDate: { type: 'boolean', default: true },
            showLink: { type: 'boolean', default: true },
            linkText: { type: 'string', default: 'View Event' },
            expiredText: { type: 'string', default: 'Event has started!' },
            hideExpired: { type: 'boolean', default: false },
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();
            
            var hasValidSource = (attributes.mode === 'event' && attributes.eventId > 0) || 
                                 (attributes.mode === 'date' && attributes.date);
            
            return el('div', blockProps,
                el(InspectorControls, null,
                    // Source Panel
                    el(PanelBody, { title: __('Countdown Source', 'ensemble'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Countdown To', 'ensemble'),
                            value: attributes.mode,
                            options: [
                                { label: __('Event', 'ensemble'), value: 'event' },
                                { label: __('Custom Date', 'ensemble'), value: 'date' },
                            ],
                            onChange: function(value) {
                                setAttributes({ mode: value });
                            }
                        }),
                        
                        attributes.mode === 'event' && el(TextControl, {
                            label: __('Event ID', 'ensemble'),
                            type: 'number',
                            value: attributes.eventId || '',
                            onChange: function(value) {
                                setAttributes({ eventId: parseInt(value, 10) || 0 });
                            },
                            help: __('Enter the ID of the event', 'ensemble')
                        }),
                        
                        attributes.mode === 'date' && el(TextControl, {
                            label: __('Date', 'ensemble'),
                            type: 'date',
                            value: attributes.date,
                            onChange: function(value) {
                                setAttributes({ date: value });
                            }
                        }),
                        
                        attributes.mode === 'date' && el(TextControl, {
                            label: __('Time (optional)', 'ensemble'),
                            type: 'time',
                            value: attributes.time,
                            onChange: function(value) {
                                setAttributes({ time: value });
                            }
                        }),
                        
                        attributes.mode === 'date' && el(TextControl, {
                            label: __('Title', 'ensemble'),
                            value: attributes.title,
                            onChange: function(value) {
                                setAttributes({ title: value });
                            },
                            help: __('Custom title for the countdown', 'ensemble')
                        })
                    ),
                    
                    // Style Panel
                    el(PanelBody, { title: __('Style', 'ensemble'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Style', 'ensemble'),
                            value: attributes.style,
                            options: [
                                { label: __('Default', 'ensemble'), value: 'default' },
                                { label: __('Minimal', 'ensemble'), value: 'minimal' },
                                { label: __('Compact', 'ensemble'), value: 'compact' },
                                { label: __('Hero', 'ensemble'), value: 'hero' },
                            ],
                            onChange: function(value) {
                                setAttributes({ style: value });
                            }
                        })
                    ),
                    
                    // Display Panel
                    el(PanelBody, { title: __('Display Options', 'ensemble'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show Days', 'ensemble'),
                            checked: attributes.showDays,
                            onChange: function(value) {
                                setAttributes({ showDays: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Hours', 'ensemble'),
                            checked: attributes.showHours,
                            onChange: function(value) {
                                setAttributes({ showHours: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Minutes', 'ensemble'),
                            checked: attributes.showMinutes,
                            onChange: function(value) {
                                setAttributes({ showMinutes: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Seconds', 'ensemble'),
                            checked: attributes.showSeconds,
                            onChange: function(value) {
                                setAttributes({ showSeconds: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Labels', 'ensemble'),
                            checked: attributes.showLabels,
                            onChange: function(value) {
                                setAttributes({ showLabels: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Title', 'ensemble'),
                            checked: attributes.showTitle,
                            onChange: function(value) {
                                setAttributes({ showTitle: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Date', 'ensemble'),
                            checked: attributes.showDate,
                            onChange: function(value) {
                                setAttributes({ showDate: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Link', 'ensemble'),
                            checked: attributes.showLink,
                            onChange: function(value) {
                                setAttributes({ showLink: value });
                            }
                        }),
                        attributes.showLink && el(TextControl, {
                            label: __('Link Text', 'ensemble'),
                            value: attributes.linkText,
                            onChange: function(value) {
                                setAttributes({ linkText: value });
                            }
                        })
                    ),
                    
                    // Expired Options
                    el(PanelBody, { title: __('Expired Options', 'ensemble'), initialOpen: false },
                        el(TextControl, {
                            label: __('Expired Text', 'ensemble'),
                            value: attributes.expiredText,
                            onChange: function(value) {
                                setAttributes({ expiredText: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Hide When Expired', 'ensemble'),
                            checked: attributes.hideExpired,
                            onChange: function(value) {
                                setAttributes({ hideExpired: value });
                            }
                        })
                    )
                ),
                
                // Preview - pass ALL attributes directly
                hasValidSource 
                    ? el('div', { 
                        className: 'ensemble-block-preview-wrapper',
                        style: { pointerEvents: 'none' }
                      },
                        el(ServerSideRender, {
                            block: 'ensemble/countdown',
                            attributes: attributes  // Pass all attributes directly
                        })
                      )
                    : el(Placeholder, {
                        icon: 'clock',
                        label: __('Event Countdown', 'ensemble'),
                        instructions: attributes.mode === 'event' 
                            ? __('Enter an Event ID in the sidebar to display a countdown', 'ensemble')
                            : __('Select a date in the sidebar to display a countdown', 'ensemble')
                      })
            );
        },
        
        save: function() {
            return null;
        }
    });
})(window.wp);
