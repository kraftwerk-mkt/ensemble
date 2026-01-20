/**
 * Event Grid Block
 * 
 * @package Ensemble
 * @since 3.0.0
 */
( function( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { 
		PanelBody, 
		SelectControl, 
		RangeControl, 
		ToggleControl, 
		TextControl,
		Placeholder,
		Spinner
	} = wp.components;
	const { __ } = wp.i18n;
	const { useSelect } = wp.data;
	const ServerSideRender = wp.serverSideRender;

	// Block icon
	const blockIcon = el( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
		el( 'path', { d: 'M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z' } )
	);

	// Edit component
	function EditComponent( props ) {
		const { attributes, setAttributes } = props;
		const {
			layout, columns, style, limit, offset, orderby, order, show, featured,
			category, location, artist,
			showImage, showTitle, showDate, showTime, showLocation, showCategory, showPrice, showDesc, showArtists,
			showFilter, showSearch,
			autoplay, autoplaySpeed, loop, dots, arrows, fullscreen
		} = attributes;

		const blockProps = useBlockProps();
		const isSlider = [ 'slider', 'hero', 'carousel' ].indexOf( layout ) !== -1;

		// Fetch categories
		const categories = useSelect( function( select ) {
			return select( 'core' ).getEntityRecords( 'taxonomy', 'ensemble_category', { per_page: -1 } ) || [];
		}, [] );

		// Build category options
		const categoryOptions = [ { label: __( 'All Categories', 'ensemble' ), value: '' } ];
		if ( categories ) {
			categories.forEach( function( c ) {
				categoryOptions.push( { label: c.name, value: c.slug } );
			} );
		}

		return el( Fragment, {},
			el( InspectorControls, {},
				// Layout Panel
				el( PanelBody, { title: __( 'Layout', 'ensemble' ), initialOpen: true },
					el( SelectControl, {
						label: __( 'Layout', 'ensemble' ),
						value: layout,
						options: [
							{ label: __( 'Grid', 'ensemble' ), value: 'grid' },
							{ label: __( 'List', 'ensemble' ), value: 'list' },
							{ label: __( 'Masonry', 'ensemble' ), value: 'masonry' },
							{ label: __( 'Slider', 'ensemble' ), value: 'slider' },
							{ label: __( 'Hero', 'ensemble' ), value: 'hero' },
							{ label: __( 'Carousel', 'ensemble' ), value: 'carousel' },
						],
						onChange: function( val ) { setAttributes( { layout: val } ); }
					} ),
					el( SelectControl, {
						label: __( 'Card Style', 'ensemble' ),
						value: style,
						options: [
							{ label: __( 'Default', 'ensemble' ), value: 'default' },
							{ label: __( 'Minimal', 'ensemble' ), value: 'minimal' },
							{ label: __( 'Overlay', 'ensemble' ), value: 'overlay' },
							{ label: __( 'Compact', 'ensemble' ), value: 'compact' },
							{ label: __( 'Featured', 'ensemble' ), value: 'featured' },
						],
						onChange: function( val ) { setAttributes( { style: val } ); }
					} ),
					el( RangeControl, {
						label: __( 'Columns', 'ensemble' ),
						value: columns,
						onChange: function( val ) { setAttributes( { columns: val } ); },
						min: 1,
						max: 4
					} )
				),

				// Slider Panel
				isSlider && el( PanelBody, { title: __( 'Slider', 'ensemble' ), initialOpen: false },
					el( ToggleControl, {
						label: __( 'Autoplay', 'ensemble' ),
						checked: autoplay,
						onChange: function( val ) { setAttributes( { autoplay: val } ); }
					} ),
					autoplay && el( RangeControl, {
						label: __( 'Speed (ms)', 'ensemble' ),
						value: autoplaySpeed,
						onChange: function( val ) { setAttributes( { autoplaySpeed: val } ); },
						min: 1000,
						max: 10000,
						step: 500
					} ),
					el( ToggleControl, {
						label: __( 'Loop', 'ensemble' ),
						checked: loop,
						onChange: function( val ) { setAttributes( { loop: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Dots', 'ensemble' ),
						checked: dots,
						onChange: function( val ) { setAttributes( { dots: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Arrows', 'ensemble' ),
						checked: arrows,
						onChange: function( val ) { setAttributes( { arrows: val } ); }
					} ),
					layout === 'hero' && el( ToggleControl, {
						label: __( 'Fullscreen', 'ensemble' ),
						checked: fullscreen,
						onChange: function( val ) { setAttributes( { fullscreen: val } ); }
					} )
				),

				// Query Panel
				el( PanelBody, { title: __( 'Query', 'ensemble' ), initialOpen: false },
					el( RangeControl, {
						label: __( 'Number of Events', 'ensemble' ),
						value: limit,
						onChange: function( val ) { setAttributes( { limit: val } ); },
						min: 1,
						max: 50
					} ),
					el( RangeControl, {
						label: __( 'Offset', 'ensemble' ),
						value: offset,
						onChange: function( val ) { setAttributes( { offset: val } ); },
						min: 0,
						max: 20
					} ),
					el( SelectControl, {
						label: __( 'Show', 'ensemble' ),
						value: show,
						options: [
							{ label: __( 'Upcoming', 'ensemble' ), value: 'upcoming' },
							{ label: __( 'Past', 'ensemble' ), value: 'past' },
							{ label: __( 'All', 'ensemble' ), value: 'all' },
						],
						onChange: function( val ) { setAttributes( { show: val } ); }
					} ),
					el( SelectControl, {
						label: __( 'Order By', 'ensemble' ),
						value: orderby,
						options: [
							{ label: __( 'Event Date', 'ensemble' ), value: 'event_date' },
							{ label: __( 'Title', 'ensemble' ), value: 'title' },
							{ label: __( 'Published Date', 'ensemble' ), value: 'date' },
						],
						onChange: function( val ) { setAttributes( { orderby: val } ); }
					} ),
					el( SelectControl, {
						label: __( 'Order', 'ensemble' ),
						value: order,
						options: [
							{ label: __( 'Ascending', 'ensemble' ), value: 'ASC' },
							{ label: __( 'Descending', 'ensemble' ), value: 'DESC' },
						],
						onChange: function( val ) { setAttributes( { order: val } ); }
					} ),
					el( SelectControl, {
						label: __( 'Featured Only', 'ensemble' ),
						value: featured,
						options: [
							{ label: __( 'No', 'ensemble' ), value: '' },
							{ label: __( 'Yes', 'ensemble' ), value: 'true' },
						],
						onChange: function( val ) { setAttributes( { featured: val } ); }
					} )
				),

				// Filter Panel
				el( PanelBody, { title: __( 'Filter', 'ensemble' ), initialOpen: false },
					el( SelectControl, {
						label: __( 'Category', 'ensemble' ),
						value: category,
						options: categoryOptions,
						onChange: function( val ) { setAttributes( { category: val } ); }
					} ),
					el( TextControl, {
						label: __( 'Location ID', 'ensemble' ),
						value: location,
						onChange: function( val ) { setAttributes( { location: val } ); },
						help: __( 'Filter by location post ID', 'ensemble' )
					} ),
					el( TextControl, {
						label: __( 'Artist ID', 'ensemble' ),
						value: artist,
						onChange: function( val ) { setAttributes( { artist: val } ); },
						help: __( 'Filter by artist post ID', 'ensemble' )
					} )
				),

				// Display Panel
				el( PanelBody, { title: __( 'Display', 'ensemble' ), initialOpen: false },
					el( ToggleControl, {
						label: __( 'Show Image', 'ensemble' ),
						checked: showImage,
						onChange: function( val ) { setAttributes( { showImage: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Title', 'ensemble' ),
						checked: showTitle,
						onChange: function( val ) { setAttributes( { showTitle: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Date', 'ensemble' ),
						checked: showDate,
						onChange: function( val ) { setAttributes( { showDate: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Time', 'ensemble' ),
						checked: showTime,
						onChange: function( val ) { setAttributes( { showTime: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Location', 'ensemble' ),
						checked: showLocation,
						onChange: function( val ) { setAttributes( { showLocation: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Category', 'ensemble' ),
						checked: showCategory,
						onChange: function( val ) { setAttributes( { showCategory: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Price', 'ensemble' ),
						checked: showPrice,
						onChange: function( val ) { setAttributes( { showPrice: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Description', 'ensemble' ),
						checked: showDesc,
						onChange: function( val ) { setAttributes( { showDesc: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Artists', 'ensemble' ),
						checked: showArtists,
						onChange: function( val ) { setAttributes( { showArtists: val } ); }
					} )
				),

				// Filter Bar Panel
				el( PanelBody, { title: __( 'Filter Bar', 'ensemble' ), initialOpen: false },
					el( ToggleControl, {
						label: __( 'Show Category Filter', 'ensemble' ),
						help: __( 'Display category dropdown above events', 'ensemble' ),
						checked: showFilter,
						onChange: function( val ) { setAttributes( { showFilter: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Search', 'ensemble' ),
						help: __( 'Display search input field', 'ensemble' ),
						checked: showSearch,
						onChange: function( val ) { setAttributes( { showSearch: val } ); }
					} )
				)
			),

			el( 'div', Object.assign({}, blockProps, {
					onClick: function(e) {
						if (e.target !== e.currentTarget) {
							e.preventDefault();
							e.stopPropagation();
						}
					},
					style: { cursor: 'pointer' }
				}),
				el( 'div', { 
					style: { pointerEvents: 'none' },
					className: 'ensemble-block-preview-wrapper'
				},
					el( ServerSideRender, {
						block: 'ensemble/event-grid',
						attributes: attributes,
						LoadingResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Event Grid', 'ensemble' ) },
								el( Spinner )
							);
						},
						ErrorResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Event Grid', 'ensemble' ) },
								__( 'Error loading events.', 'ensemble' )
							);
						},
						EmptyResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Event Grid', 'ensemble' ) },
								__( 'No events found.', 'ensemble' )
							);
						}
					} )
				) // Close preview wrapper div
			)
		);
	}

	// Register block
	registerBlockType( 'ensemble/event-grid', {
		title: __( 'Event Grid', 'ensemble' ),
		description: __( 'Display events in a grid, list, or slider.', 'ensemble' ),
		category: 'ensemble',
		icon: blockIcon,
		keywords: [ 'events', 'calendar', 'grid', 'ensemble' ],
		attributes: {
			layout: { type: 'string', default: 'grid' },
			columns: { type: 'number', default: 3 },
			style: { type: 'string', default: 'default' },
			limit: { type: 'number', default: 12 },
			offset: { type: 'number', default: 0 },
			orderby: { type: 'string', default: 'event_date' },
			order: { type: 'string', default: 'ASC' },
			show: { type: 'string', default: 'upcoming' },
			featured: { type: 'string', default: '' },
			category: { type: 'string', default: '' },
			location: { type: 'string', default: '' },
			artist: { type: 'string', default: '' },
			showImage: { type: 'boolean', default: true },
			showTitle: { type: 'boolean', default: true },
			showDate: { type: 'boolean', default: true },
			showTime: { type: 'boolean', default: true },
			showLocation: { type: 'boolean', default: true },
			showCategory: { type: 'boolean', default: true },
			showPrice: { type: 'boolean', default: true },
			showDesc: { type: 'boolean', default: false },
			showArtists: { type: 'boolean', default: false },
			showFilter: { type: 'boolean', default: false },
			showSearch: { type: 'boolean', default: false },
			autoplay: { type: 'boolean', default: false },
			autoplaySpeed: { type: 'number', default: 5000 },
			loop: { type: 'boolean', default: false },
			dots: { type: 'boolean', default: true },
			arrows: { type: 'boolean', default: true },
			fullscreen: { type: 'boolean', default: false },
		},
		supports: { html: false, align: [ 'wide', 'full' ] },
		edit: EditComponent,
		save: function() { return null; }
	} );

} )( window.wp );
