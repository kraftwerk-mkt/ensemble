/**
 * Ensemble Locations Map Block
 * 
 * Gutenberg block for displaying all locations on an interactive map.
 * Wraps the [ensemble_locations_map] shortcode.
 */
( function( blocks, element, blockEditor, components, i18n, serverSideRender ) {
	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var RangeControl = components.RangeControl;
	var TextControl = components.TextControl;
	var ServerSideRender = serverSideRender;

	// Register the block
	blocks.registerBlockType( 'ensemble/locations-map', {
		title: __( 'Locations Map', 'ensemble' ),
		description: __( 'Display all locations on an interactive map with filtering and search.', 'ensemble' ),
		icon: 'location-alt',
		category: 'ensemble',
		keywords: [ 
			__( 'map', 'ensemble' ), 
			__( 'locations', 'ensemble' ), 
			__( 'venues', 'ensemble' ),
			__( 'openstreetmap', 'ensemble' ),
			__( 'google', 'ensemble' )
		],
		supports: {
			align: [ 'wide', 'full' ],
			html: false,
		},

		attributes: {
			height: { type: 'string', default: '500px' },
			style: { type: 'string', default: 'default' },
			clustering: { type: 'boolean', default: true },
			fullscreen: { type: 'boolean', default: true },
			geolocation: { type: 'boolean', default: true },
			search: { type: 'boolean', default: true },
			markerThumbnails: { type: 'boolean', default: true },
			filter: { type: 'boolean', default: true },
			category: { type: 'string', default: '' },
			city: { type: 'string', default: '' },
			limit: { type: 'number', default: 0 },
		},

		edit: function( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return el( 'div', { className: props.className },
				// Inspector Controls (Sidebar)
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Map Settings', 'ensemble' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Height', 'ensemble' ),
							value: attributes.height,
							options: [
								{ label: '300px', value: '300px' },
								{ label: '400px', value: '400px' },
								{ label: '500px', value: '500px' },
								{ label: '600px', value: '600px' },
								{ label: '700px', value: '700px' },
								{ label: '800px', value: '800px' },
							],
							onChange: function( val ) { setAttributes( { height: val } ); }
						} ),
						el( SelectControl, {
							label: __( 'Map Style', 'ensemble' ),
							value: attributes.style,
							options: [
								{ label: __( 'Default', 'ensemble' ), value: 'default' },
								{ label: __( 'Light', 'ensemble' ), value: 'light' },
								{ label: __( 'Dark', 'ensemble' ), value: 'dark' },
								{ label: __( 'Voyager', 'ensemble' ), value: 'voyager' },
								{ label: __( 'Satellite', 'ensemble' ), value: 'satellite' },
								{ label: __( 'Terrain', 'ensemble' ), value: 'terrain' },
								{ label: __( 'Watercolor', 'ensemble' ), value: 'watercolor' },
							],
							onChange: function( val ) { setAttributes( { style: val } ); }
						} )
					),
					el( PanelBody, { title: __( 'Features', 'ensemble' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Enable Clustering', 'ensemble' ),
							help: __( 'Group nearby markers into clusters.', 'ensemble' ),
							checked: attributes.clustering,
							onChange: function( val ) { setAttributes( { clustering: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Enable Fullscreen', 'ensemble' ),
							checked: attributes.fullscreen,
							onChange: function( val ) { setAttributes( { fullscreen: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Enable Geolocation', 'ensemble' ),
							help: __( 'Allow users to find their location.', 'ensemble' ),
							checked: attributes.geolocation,
							onChange: function( val ) { setAttributes( { geolocation: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Enable Search', 'ensemble' ),
							checked: attributes.search,
							onChange: function( val ) { setAttributes( { search: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show Thumbnail Markers', 'ensemble' ),
							help: __( 'Display location images in markers.', 'ensemble' ),
							checked: attributes.markerThumbnails,
							onChange: function( val ) { setAttributes( { markerThumbnails: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show Filter Bar', 'ensemble' ),
							help: __( 'Display city/category filters above map.', 'ensemble' ),
							checked: attributes.filter,
							onChange: function( val ) { setAttributes( { filter: val } ); }
						} )
					),
					el( PanelBody, { title: __( 'Filtering', 'ensemble' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Category', 'ensemble' ),
							help: __( 'Filter by location category slug.', 'ensemble' ),
							value: attributes.category,
							onChange: function( val ) { setAttributes( { category: val } ); }
						} ),
						el( TextControl, {
							label: __( 'City', 'ensemble' ),
							help: __( 'Filter by city name.', 'ensemble' ),
							value: attributes.city,
							onChange: function( val ) { setAttributes( { city: val } ); }
						} ),
						el( RangeControl, {
							label: __( 'Limit', 'ensemble' ),
							help: __( 'Maximum number of locations (0 = all).', 'ensemble' ),
							value: attributes.limit,
							onChange: function( val ) { setAttributes( { limit: val } ); },
							min: 0,
							max: 100,
							step: 1
						} )
					)
				),

				// Block Preview - Show placeholder because map requires JS
				el( 'div', { 
					style: { 
						padding: '40px 20px', 
						textAlign: 'center', 
						background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 
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
						className: 'dashicons dashicons-location-alt',
						style: { fontSize: '64px', width: '64px', height: '64px', marginBottom: '15px' }
					} ),
					el( 'p', { style: { margin: 0, fontSize: '20px', fontWeight: '600' } }, 
						__( 'Locations Map', 'ensemble' )
					),
					el( 'p', { style: { margin: '10px 0 0', opacity: 0.9 } }, 
						attributes.height + ' • ' + 
						( attributes.clustering ? __( 'Clustering', 'ensemble' ) + ' • ' : '' ) +
						( attributes.search ? __( 'Search', 'ensemble' ) + ' • ' : '' ) +
						attributes.style + ' ' + __( 'style', 'ensemble' )
					),
					el( 'p', { style: { margin: '15px 0 0', fontSize: '12px', opacity: 0.7, maxWidth: '400px' } }, 
						__( 'The interactive map will be displayed on the frontend. Map functionality requires JavaScript which cannot run in the editor preview.', 'ensemble' )
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
	window.wp.i18n,
	window.wp.serverSideRender
);
