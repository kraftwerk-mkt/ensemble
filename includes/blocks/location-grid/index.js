/**
 * Ensemble Location Grid Block
 * 
 * @package Ensemble
 * @version 1.0.0
 */

( function( blocks, element, blockEditor, components, serverSideRender, data ) {
	'use strict';

	var el = element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;
	var ToggleControl = components.ToggleControl;
	var TextControl = components.TextControl;
	var Placeholder = components.Placeholder;
	var Spinner = components.Spinner;
	var ServerSideRender = serverSideRender;

	// Block icon
	var blockIcon = el( 'svg', { 
		width: 24, 
		height: 24, 
		viewBox: '0 0 24 24',
		fill: 'none',
		stroke: 'currentColor',
		strokeWidth: 2
	},
		el( 'path', { d: 'M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z' } ),
		el( 'circle', { cx: 12, cy: 10, r: 3 } )
	);

	// Edit function
	function EditLocationGrid( props ) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var blockProps = useBlockProps();

		// Destructure attributes
		var layout = attributes.layout;
		var columns = attributes.columns;
		var limit = attributes.limit;
		var orderby = attributes.orderby;
		var order = attributes.order;
		var type = attributes.type;
		var showImage = attributes.showImage;
		var showName = attributes.showName;
		var showType = attributes.showType;
		var showAddress = attributes.showAddress;
		var showCapacity = attributes.showCapacity;
		var showEvents = attributes.showEvents;
		var showDescription = attributes.showDescription;
		var showSocial = attributes.showSocial;
		var showLink = attributes.showLink;
		var linkText = attributes.linkText;

		// Fetch location types for dropdown
		var locationTypes = data.useSelect( function( select ) {
			return select( 'core' ).getEntityRecords( 'taxonomy', 'ensemble_location_type', { per_page: -1 } );
		}, [] );

		var typeOptions = [ { label: __( 'All Types', 'ensemble' ), value: '' } ];
		if ( locationTypes ) {
			locationTypes.forEach( function( t ) {
				typeOptions.push( { label: t.name, value: t.slug } );
			} );
		}

		return el( element.Fragment, {},
			el( InspectorControls, {},
				// Layout Panel
				el( PanelBody, { title: __( 'Layout', 'ensemble' ), initialOpen: true },
					el( SelectControl, {
						label: __( 'Layout', 'ensemble' ),
						value: layout,
						options: [
							{ label: __( 'Grid', 'ensemble' ), value: 'grid' },
							{ label: __( 'List', 'ensemble' ), value: 'list' },
							{ label: __( 'Cards', 'ensemble' ), value: 'cards' },
							{ label: __( 'Slider', 'ensemble' ), value: 'slider' },
						],
						onChange: function( val ) { setAttributes( { layout: val } ); }
					} ),
					( layout === 'grid' || layout === 'cards' || layout === 'slider' ) && el( RangeControl, {
						label: __( 'Columns', 'ensemble' ),
						value: columns,
						onChange: function( val ) { setAttributes( { columns: val } ); },
						min: 2,
						max: 4
					} ),
					el( RangeControl, {
						label: __( 'Limit', 'ensemble' ),
						value: limit,
						onChange: function( val ) { setAttributes( { limit: val } ); },
						min: 1,
						max: 50
					} ),
					el( SelectControl, {
						label: __( 'Order By', 'ensemble' ),
						value: orderby,
						options: [
							{ label: __( 'Title', 'ensemble' ), value: 'title' },
							{ label: __( 'Date', 'ensemble' ), value: 'date' },
							{ label: __( 'Menu Order', 'ensemble' ), value: 'menu_order' },
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
					} )
				),

				// Filter Panel
				el( PanelBody, { title: __( 'Filter', 'ensemble' ), initialOpen: false },
					el( SelectControl, {
						label: __( 'Location Type', 'ensemble' ),
						value: type,
						options: typeOptions,
						onChange: function( val ) { setAttributes( { type: val } ); }
					} )
				),

				// Display Panel - Only for grid/list layouts
				( layout === 'grid' || layout === 'list' ) && el( PanelBody, { title: __( 'Display', 'ensemble' ), initialOpen: true },
					el( ToggleControl, {
						label: __( 'Show Image', 'ensemble' ),
						checked: showImage,
						onChange: function( val ) { setAttributes( { showImage: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Name', 'ensemble' ),
						checked: showName,
						onChange: function( val ) { setAttributes( { showName: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Type', 'ensemble' ),
						help: __( 'Location type category', 'ensemble' ),
						checked: showType,
						onChange: function( val ) { setAttributes( { showType: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Address', 'ensemble' ),
						help: __( 'Address and city', 'ensemble' ),
						checked: showAddress,
						onChange: function( val ) { setAttributes( { showAddress: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Capacity', 'ensemble' ),
						help: __( 'Venue capacity', 'ensemble' ),
						checked: showCapacity,
						onChange: function( val ) { setAttributes( { showCapacity: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Events Count', 'ensemble' ),
						help: __( 'Number of upcoming events', 'ensemble' ),
						checked: showEvents,
						onChange: function( val ) { setAttributes( { showEvents: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Description', 'ensemble' ),
						checked: showDescription,
						onChange: function( val ) { setAttributes( { showDescription: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Social Links', 'ensemble' ),
						help: __( 'Website and social media', 'ensemble' ),
						checked: showSocial,
						onChange: function( val ) { setAttributes( { showSocial: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Link', 'ensemble' ),
						checked: showLink,
						onChange: function( val ) { setAttributes( { showLink: val } ); }
					} ),
					showLink && el( TextControl, {
						label: __( 'Link Text', 'ensemble' ),
						value: linkText,
						onChange: function( val ) { setAttributes( { linkText: val } ); }
					} )
				)
			),

			el( 'div', Object.assign( {}, blockProps, {
					onClick: function( e ) {
						if ( e.target !== e.currentTarget ) {
							e.preventDefault();
							e.stopPropagation();
						}
					},
					style: { cursor: 'pointer' }
				} ),
				el( 'div', { 
					style: { pointerEvents: 'none' },
					className: 'ensemble-block-preview-wrapper'
				},
					el( ServerSideRender, {
						block: 'ensemble/location-grid',
						attributes: attributes,
						LoadingResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Location Grid', 'ensemble' ) },
								el( Spinner )
							);
						},
						ErrorResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Location Grid', 'ensemble' ) },
								__( 'Error loading locations.', 'ensemble' )
							);
						},
						EmptyResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Location Grid', 'ensemble' ) },
								__( 'No locations found.', 'ensemble' )
							);
						}
					} )
				)
			)
		);
	}

	// Register block
	registerBlockType( 'ensemble/location-grid', {
		title: __( 'Location Grid', 'ensemble' ),
		description: __( 'Display locations in a grid, list, or slider.', 'ensemble' ),
		category: 'ensemble',
		icon: blockIcon,
		keywords: [ 'locations', 'venues', 'grid', 'ensemble', 'places' ],
		attributes: {
			layout: { type: 'string', default: 'grid' },
			columns: { type: 'number', default: 3 },
			limit: { type: 'number', default: 12 },
			orderby: { type: 'string', default: 'title' },
			order: { type: 'string', default: 'ASC' },
			type: { type: 'string', default: '' },
			showImage: { type: 'boolean', default: true },
			showName: { type: 'boolean', default: true },
			showType: { type: 'boolean', default: true },
			showAddress: { type: 'boolean', default: true },
			showCapacity: { type: 'boolean', default: false },
			showEvents: { type: 'boolean', default: false },
			showDescription: { type: 'boolean', default: false },
			showSocial: { type: 'boolean', default: false },
			showLink: { type: 'boolean', default: true },
			linkText: { type: 'string', default: 'View Location' },
			// Slider options
			autoplay: { type: 'boolean', default: false },
			autoplaySpeed: { type: 'number', default: 5000 },
			loop: { type: 'boolean', default: false },
			dots: { type: 'boolean', default: true },
			arrows: { type: 'boolean', default: true },
		},
		edit: EditLocationGrid,
		save: function() {
			return null; // Server-side render
		}
	} );

} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender,
	window.wp.data
);
