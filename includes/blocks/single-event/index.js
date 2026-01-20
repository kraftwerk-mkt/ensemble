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

    registerBlockType('ensemble/single-event', {
        title: __('Single Event', 'ensemble'),
        description: __('Display a single event card, compact view, or full details', 'ensemble'),
        icon: 'calendar',
        category: 'ensemble',
        keywords: [__('event', 'ensemble'), __('single', 'ensemble'), __('card', 'ensemble')],
        
        attributes: {
            eventId: { type: 'number', default: 0 },
            layout: { type: 'string', default: 'card' },
            showImage: { type: 'boolean', default: true },
            showDate: { type: 'boolean', default: true },
            showTime: { type: 'boolean', default: true },
            showLocation: { type: 'boolean', default: true },
            showArtist: { type: 'boolean', default: true },
            showExcerpt: { type: 'boolean', default: true },
            showLink: { type: 'boolean', default: true },
            linkText: { type: 'string', default: 'View Event' },
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();
            
            var hasEventId = attributes.eventId > 0;
            
            return el('div', blockProps,
                el(InspectorControls, null,
                    // Event Selection
                    el(PanelBody, { title: __('Event Selection', 'ensemble'), initialOpen: true },
                        el(TextControl, {
                            label: __('Event ID', 'ensemble'),
                            type: 'number',
                            value: attributes.eventId || '',
                            onChange: function(value) {
                                setAttributes({ eventId: parseInt(value, 10) || 0 });
                            },
                            help: __('Enter the ID of the event to display', 'ensemble')
                        })
                    ),
                    
                    // Layout
                    el(PanelBody, { title: __('Layout', 'ensemble'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Display Style', 'ensemble'),
                            value: attributes.layout,
                            options: [
                                { label: __('Card', 'ensemble'), value: 'card' },
                                { label: __('Compact', 'ensemble'), value: 'compact' },
                                { label: __('Full', 'ensemble'), value: 'full' },
                            ],
                            onChange: function(value) {
                                setAttributes({ layout: value });
                            }
                        })
                    ),
                    
                    // Display Options
                    el(PanelBody, { title: __('Display Options', 'ensemble'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show Image', 'ensemble'),
                            checked: attributes.showImage,
                            onChange: function(value) {
                                setAttributes({ showImage: value });
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
                            label: __('Show Time', 'ensemble'),
                            checked: attributes.showTime,
                            onChange: function(value) {
                                setAttributes({ showTime: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Location', 'ensemble'),
                            checked: attributes.showLocation,
                            onChange: function(value) {
                                setAttributes({ showLocation: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Artist', 'ensemble'),
                            checked: attributes.showArtist,
                            onChange: function(value) {
                                setAttributes({ showArtist: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Excerpt', 'ensemble'),
                            checked: attributes.showExcerpt,
                            onChange: function(value) {
                                setAttributes({ showExcerpt: value });
                            }
                        })
                    ),
                    
                    // Link Options
                    el(PanelBody, { title: __('Link Options', 'ensemble'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show Link Button', 'ensemble'),
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
                    )
                ),
                
                // Preview
                hasEventId 
                    ? el('div', { 
                        className: 'ensemble-block-preview-wrapper',
                        style: { pointerEvents: 'none' }
                      },
                        el(ServerSideRender, {
                            block: 'ensemble/single-event',
                            attributes: attributes
                        })
                      )
                    : el(Placeholder, {
                        icon: 'calendar',
                        label: __('Single Event', 'ensemble'),
                        instructions: __('Enter an Event ID in the sidebar to display an event', 'ensemble')
                      })
            );
        },
        
        save: function() {
            return null;
        }
    });
})(window.wp);
