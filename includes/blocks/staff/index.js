/**
 * Staff Grid Block
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
		el( 'path', { d: 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z' } )
	);

	// Edit component
	function EditComponent( props ) {
		const { attributes, setAttributes } = props;
		const {
			layout, columns, limit, orderby, order, department, ids,
			showImage, showEmail, showPhone, showPosition, showDepartment,
			showOfficeHours, showSocial, showResponsibility, showExcerpt
		} = attributes;

		const blockProps = useBlockProps();

		// Fetch departments
		const departments = useSelect( function( select ) {
			return select( 'core' ).getEntityRecords( 'taxonomy', 'ensemble_department', { per_page: -1 } ) || [];
		}, [] );

		// Build options
		const departmentOptions = [ { label: __( 'All Departments', 'ensemble' ), value: '' } ];
		if ( departments ) {
			departments.forEach( function( d ) {
				departmentOptions.push( { label: d.name, value: d.slug } );
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
							{ label: __( 'Cards (Flip)', 'ensemble' ), value: 'cards' },
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

				// Query Panel
				el( PanelBody, { title: __( 'Query', 'ensemble' ), initialOpen: false },
					el( RangeControl, {
						label: __( 'Number of Staff', 'ensemble' ),
						value: limit === -1 ? 0 : limit,
						onChange: function( val ) { setAttributes( { limit: val === 0 ? -1 : val } ); },
						min: 0,
						max: 50,
						help: __( '0 = show all', 'ensemble' )
					} ),
					el( SelectControl, {
						label: __( 'Order By', 'ensemble' ),
						value: orderby,
						options: [
							{ label: __( 'Menu Order', 'ensemble' ), value: 'menu_order' },
							{ label: __( 'Name', 'ensemble' ), value: 'title' },
							{ label: __( 'Date', 'ensemble' ), value: 'date' },
							{ label: __( 'Random', 'ensemble' ), value: 'rand' },
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
						label: __( 'Department', 'ensemble' ),
						value: department,
						options: departmentOptions,
						onChange: function( val ) { setAttributes( { department: val } ); }
					} ),
					el( TextControl, {
						label: __( 'Specific IDs', 'ensemble' ),
						value: ids,
						onChange: function( val ) { setAttributes( { ids: val } ); },
						help: __( 'Comma-separated IDs to show specific staff members', 'ensemble' )
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
						label: __( 'Show Email', 'ensemble' ),
						checked: showEmail,
						onChange: function( val ) { setAttributes( { showEmail: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Phone', 'ensemble' ),
						checked: showPhone,
						onChange: function( val ) { setAttributes( { showPhone: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Position', 'ensemble' ),
						checked: showPosition,
						onChange: function( val ) { setAttributes( { showPosition: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Department', 'ensemble' ),
						checked: showDepartment,
						onChange: function( val ) { setAttributes( { showDepartment: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Office Hours', 'ensemble' ),
						checked: showOfficeHours,
						onChange: function( val ) { setAttributes( { showOfficeHours: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Social Links', 'ensemble' ),
						checked: showSocial,
						onChange: function( val ) { setAttributes( { showSocial: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Responsibility', 'ensemble' ),
						checked: showResponsibility,
						onChange: function( val ) { setAttributes( { showResponsibility: val } ); }
					} ),
					el( ToggleControl, {
						label: __( 'Show Bio/Excerpt', 'ensemble' ),
						checked: showExcerpt,
						onChange: function( val ) { setAttributes( { showExcerpt: val } ); }
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
						block: 'ensemble/staff-grid',
						attributes: attributes,
						LoadingResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Staff Grid', 'ensemble' ) },
								el( Spinner )
							);
						},
						ErrorResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Staff Grid', 'ensemble' ) },
								__( 'Error loading staff.', 'ensemble' )
							);
						},
						EmptyResponsePlaceholder: function() {
							return el( Placeholder, { icon: blockIcon, label: __( 'Staff Grid', 'ensemble' ) },
								__( 'No staff found. Make sure the Staff addon is active.', 'ensemble' )
							);
						}
					} )
				)
			)
		);
	}

	// Register block
	registerBlockType( 'ensemble/staff-grid', {
		title: __( 'Staff Grid', 'ensemble' ),
		description: __( 'Display staff members in a grid, list, or card layout.', 'ensemble' ),
		category: 'ensemble',
		icon: blockIcon,
		keywords: [ 'staff', 'team', 'contacts', 'speakers', 'ensemble' ],
		attributes: {
			layout: { type: 'string', default: 'grid' },
			columns: { type: 'number', default: 3 },
			limit: { type: 'number', default: -1 },
			orderby: { type: 'string', default: 'menu_order' },
			order: { type: 'string', default: 'ASC' },
			department: { type: 'string', default: '' },
			ids: { type: 'string', default: '' },
			// Display options
			showImage: { type: 'boolean', default: true },
			showEmail: { type: 'boolean', default: true },
			showPhone: { type: 'boolean', default: true },
			showPosition: { type: 'boolean', default: true },
			showDepartment: { type: 'boolean', default: true },
			showOfficeHours: { type: 'boolean', default: false },
			showSocial: { type: 'boolean', default: false },
			showResponsibility: { type: 'boolean', default: false },
			showExcerpt: { type: 'boolean', default: false },
		},
		supports: { html: false, align: [ 'wide', 'full' ] },
		edit: EditComponent,
		save: function() { return null; }
	} );

} )( window.wp );
