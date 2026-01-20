/**
 * Artist Grid Block
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
		el( 'path', { d: 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z' } )
	);

	// Edit component
	function EditComponent( props ) {
		const { attributes, setAttributes } = props;
		const {
			layout, columns, limit, orderby, order, genre, type,
			showImage, showName, showPosition, showCompany, showGenre, showType,
			showBio, showEvents, showSocial, showLink, linkText,
			autoplay, autoplaySpeed, loop, dots, arrows
		} = attributes;

		const blockProps = useBlockProps();
		const isSlider = layout === 'slider';

		// Fetch genres
		const genres = useSelect( function( select ) {
			return select( 'core' ).getEntityRecords( 'taxonomy', 'ensemble_genre', { per_page: -1 } ) || [];
		}, [] );

		// Fetch artist types
		const artistTypes = useSelect( function( select ) {
			return select( 'core' ).getEntityRecords( 'taxonomy', 'ensemble_artist_type', { per_page: -1 } ) || [];
		}, [] );

		// Build options
		const genreOptions = [ { label: __( 'All Genres', 'ensemble' ), value: '' } ];
		if ( genres ) {
			genres.forEach( function( g ) {
				genreOptions.push( { label: g.name, value: g.slug } );
			} );
		}

		const typeOptions = [ { label: __( 'All Types', 'ensemble' ), value: '' } ];
		if ( artistTypes ) {
			artistTypes.forEach( function( t ) {
				typeOptions.push( { label: t.name, value: t.slug } );
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
							{ label: __( 'Cards', 'ensemble' ), value: 'cards' },
							{ label: __( 'Compact', 'ensemble' ), value: 'compact' },
							{ label: __( 'Slider', 'ensemble' ), value: 'slider' },
							{ label: __( 'Featured', 'ensemble' ), value: 'featured' },
						],
						onChange: function( val ) { setAttributes( { layout: val } ); }
					} ),
					el( RangeControl, {
						label: __( 'Columns', 'ensemble' ),
						value: columns,
						onChange: function( val ) { setAttributes( { columns: val } ); },
						min: 2,
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
					} )
				),

				// Query Panel
				el( PanelBody, { title: __( 'Query', 'ensemble' ), initialOpen: false },
					el( RangeControl, {
						label: __( 'Number of Artists', 'ensemble' ),
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
						label: __( 'Genre', 'ensemble' ),
						value: genre,
						options: genreOptions,
						onChange: function( val ) { setAttributes( { genre: val } ); }
					} ),
					el( SelectControl, {
						label: __( 'Type', 'ensemble' ),
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
						label: __( 'Show Position', 'ensemble' ),
						help: __( 'Job title or role', 'ensemble' ),
						checked: showPosition,
						onChange: function( val ) { setAttributes( { showPosition: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Company', 'ensemble' ),
						checked: showCompany,
						onChange: function( val ) { setAttributes( { showCompany: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Genre', 'ensemble' ),
						checked: showGenre,
						onChange: function( val ) { setAttributes( { showGenre: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Type', 'ensemble' ),
						help: __( 'Artist type category', 'ensemble' ),
						checked: showType,
						onChange: function( val ) { setAttributes( { showType: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Bio', 'ensemble' ),
						checked: showBio,
						onChange: function( val ) { setAttributes( { showBio: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Events Count', 'ensemble' ),
						help: __( 'Number of upcoming sessions', 'ensemble' ),
						checked: showEvents,
						onChange: function( val ) { setAttributes( { showEvents: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Social Links', 'ensemble' ),
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

			el( 'div', Object.assign({}, blockProps, {
					onClick: function(e) {
						// Allow click on block wrapper but stop clicks inside preview
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
						block: 'ensemble/artist-grid',
						attributes: attributes,
						LoadingResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Artist Grid', 'ensemble' ) },
								el( Spinner )
							);
						},
						ErrorResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Artist Grid', 'ensemble' ) },
								__( 'Error loading artists.', 'ensemble' )
							);
						},
						EmptyResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Artist Grid', 'ensemble' ) },
								__( 'No artists found.', 'ensemble' )
						);
					}
				} )
				) // Close preview wrapper div
			)
		);
	}

	// Register block
	registerBlockType( 'ensemble/artist-grid', {
		title: __( 'Artist Grid', 'ensemble' ),
		description: __( 'Display artists in a grid, list, or slider.', 'ensemble' ),
		category: 'ensemble',
		icon: blockIcon,
		keywords: [ 'artists', 'speakers', 'grid', 'ensemble' ],
		attributes: {
			layout: { type: 'string', default: 'grid' },
			columns: { type: 'number', default: 3 },
			style: { type: 'string', default: 'default' },
			limit: { type: 'number', default: 12 },
			orderby: { type: 'string', default: 'title' },
			order: { type: 'string', default: 'ASC' },
			genre: { type: 'string', default: '' },
			type: { type: 'string', default: '' },
			// Display options - all fields
			showImage: { type: 'boolean', default: true },
			showName: { type: 'boolean', default: true },
			showPosition: { type: 'boolean', default: true },
			showCompany: { type: 'boolean', default: true },
			showGenre: { type: 'boolean', default: false },
			showType: { type: 'boolean', default: false },
			showBio: { type: 'boolean', default: true },
			showEvents: { type: 'boolean', default: false },
			showSocial: { type: 'boolean', default: false },
			showLink: { type: 'boolean', default: true },
			linkText: { type: 'string', default: 'View Profile' },
			// Slider options
			autoplay: { type: 'boolean', default: false },
			autoplaySpeed: { type: 'number', default: 5000 },
			loop: { type: 'boolean', default: false },
			dots: { type: 'boolean', default: true },
			arrows: { type: 'boolean', default: true },
		},
		supports: { html: false, align: [ 'wide', 'full' ] },
		edit: EditComponent,
		save: function() { return null; }
	} );

} )( window.wp );
