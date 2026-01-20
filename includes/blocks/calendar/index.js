/**
 * Ensemble Calendar Block
 * 
 * Gutenberg block for displaying an interactive event calendar.
 * Wraps the [ensemble_calendar] shortcode.
 */
( function( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;

	// Register the block
	blocks.registerBlockType( 'ensemble/calendar', {
		title: __( 'Event Calendar', 'ensemble' ),
		description: __( 'Display an interactive calendar showing all events.', 'ensemble' ),
		icon: 'calendar-alt',
		category: 'ensemble',
		keywords: [ 
			__( 'calendar', 'ensemble' ), 
			__( 'events', 'ensemble' ), 
			__( 'schedule', 'ensemble' ),
			__( 'fullcalendar', 'ensemble' )
		],
		supports: {
			align: [ 'wide', 'full' ],
			html: false,
		},

		attributes: {
			view: { type: 'string', default: 'month' },
			height: { type: 'string', default: 'auto' },
			initialDate: { type: 'string', default: '' },
		},

		edit: function( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			// View label mapping
			var viewLabels = {
				'month': __( 'Month Grid', 'ensemble' ),
				'week': __( 'Week Grid', 'ensemble' ),
				'day': __( 'Day View', 'ensemble' ),
				'list': __( 'List View', 'ensemble' )
			};

			return el( 'div', blockProps,
				// Inspector Controls (Sidebar)
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Calendar Settings', 'ensemble' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Default View', 'ensemble' ),
							value: attributes.view,
							options: [
								{ label: __( 'Month Grid', 'ensemble' ), value: 'month' },
								{ label: __( 'Week Grid', 'ensemble' ), value: 'week' },
								{ label: __( 'Day View', 'ensemble' ), value: 'day' },
								{ label: __( 'List View', 'ensemble' ), value: 'list' },
							],
							onChange: function( val ) { setAttributes( { view: val } ); }
						} ),
						el( SelectControl, {
							label: __( 'Height', 'ensemble' ),
							value: attributes.height,
							options: [
								{ label: __( 'Auto', 'ensemble' ), value: 'auto' },
								{ label: '400px', value: '400' },
								{ label: '500px', value: '500' },
								{ label: '600px', value: '600' },
								{ label: '700px', value: '700' },
								{ label: '800px', value: '800' },
							],
							onChange: function( val ) { setAttributes( { height: val } ); }
						} ),
						el( TextControl, {
							label: __( 'Initial Date', 'ensemble' ),
							help: __( 'Start date in YYYY-MM-DD format. Leave empty for today.', 'ensemble' ),
							value: attributes.initialDate,
							onChange: function( val ) { setAttributes( { initialDate: val } ); }
						} )
					)
				),

				// Block Preview - Placeholder because FullCalendar requires JS
				el( 'div', { 
					style: { 
						padding: '40px 20px', 
						textAlign: 'center', 
						background: 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)', 
						borderRadius: '8px',
						minHeight: '300px',
						display: 'flex',
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'center',
						color: '#fff'
					} 
				},
					el( 'span', { 
						className: 'dashicons dashicons-calendar-alt',
						style: { fontSize: '64px', width: '64px', height: '64px', marginBottom: '15px' }
					} ),
					el( 'p', { style: { margin: 0, fontSize: '20px', fontWeight: '600' } }, 
						__( 'Event Calendar', 'ensemble' )
					),
					el( 'p', { style: { margin: '10px 0 0', opacity: 0.9 } }, 
						( viewLabels[ attributes.view ] || attributes.view ) + ' • ' +
						( attributes.height === 'auto' ? __( 'Auto Height', 'ensemble' ) : attributes.height )
					),
					el( 'p', { style: { margin: '15px 0 0', fontSize: '12px', opacity: 0.7, maxWidth: '400px' } }, 
						__( 'The interactive calendar will be displayed on the frontend. Calendar functionality requires JavaScript which cannot run in the editor preview.', 'ensemble' )
					)
				)
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
	window.wp.i18n
);
