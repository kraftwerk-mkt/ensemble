/**
 * Ensemble Upcoming Events Block
 * 
 * Gutenberg block for displaying upcoming events in a compact widget format.
 * Wraps the [ensemble_upcoming_events] shortcode.
 */
( function( blocks, element, blockEditor, components, i18n, serverSideRender ) {
	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var RangeControl = components.RangeControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender;

	// Register the block
	blocks.registerBlockType( 'ensemble/upcoming-events', {
		title: __( 'Upcoming Events', 'ensemble' ),
		description: __( 'Display a compact list of upcoming events.', 'ensemble' ),
		icon: 'clock',
		category: 'ensemble',
		keywords: [ 
			__( 'upcoming', 'ensemble' ), 
			__( 'events', 'ensemble' ), 
			__( 'next', 'ensemble' ),
			__( 'widget', 'ensemble' ),
			__( 'sidebar', 'ensemble' )
		],
		supports: {
			align: false,
			html: false,
		},

		attributes: {
			limit: { type: 'number', default: 5 },
			showCountdown: { type: 'boolean', default: false },
			showImage: { type: 'boolean', default: true },
			showLocation: { type: 'boolean', default: true },
			showArtist: { type: 'boolean', default: true },
		},

		edit: function( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return el( 'div', { className: props.className },
				// Inspector Controls (Sidebar)
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Settings', 'ensemble' ), initialOpen: true },
						el( RangeControl, {
							label: __( 'Number of Events', 'ensemble' ),
							value: attributes.limit,
							onChange: function( val ) { setAttributes( { limit: val } ); },
							min: 1,
							max: 20,
							step: 1
						} )
					),
					el( PanelBody, { title: __( 'Display Options', 'ensemble' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Show Countdown', 'ensemble' ),
							help: __( 'Display countdown timer to each event.', 'ensemble' ),
							checked: attributes.showCountdown,
							onChange: function( val ) { setAttributes( { showCountdown: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show Image', 'ensemble' ),
							checked: attributes.showImage,
							onChange: function( val ) { setAttributes( { showImage: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show Location', 'ensemble' ),
							checked: attributes.showLocation,
							onChange: function( val ) { setAttributes( { showLocation: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show Artist', 'ensemble' ),
							checked: attributes.showArtist,
							onChange: function( val ) { setAttributes( { showArtist: val } ); }
						} )
					)
				),

				// Block Preview
				el( ServerSideRender, {
					block: 'ensemble/upcoming-events',
					attributes: attributes,
					EmptyResponsePlaceholder: function() {
						return el( 'div', { 
							style: { 
								padding: '30px', 
								textAlign: 'center', 
								background: '#f0f0f0', 
								border: '1px dashed #ccc',
								borderRadius: '4px'
							} 
						},
							el( 'span', { 
								className: 'dashicons dashicons-clock',
								style: { fontSize: '36px', color: '#666', display: 'block', marginBottom: '10px' }
							} ),
							el( 'p', { style: { margin: 0, color: '#666' } }, 
								__( 'Upcoming Events', 'ensemble' )
							),
							el( 'p', { style: { margin: '5px 0 0', fontSize: '12px', color: '#999' } }, 
								__( 'No upcoming events found', 'ensemble' )
							)
						);
					}
				} )
			);
		},

		save: function() {
			// Server-side rendering
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n,
	window.wp.serverSideRender
);
