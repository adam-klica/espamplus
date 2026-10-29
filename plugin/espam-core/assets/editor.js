/**
 * Editor side of the ESPAM PLUS blocks: server-side preview + attribute controls.
 * Plain script on the wp.* globals — no build step.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl, TextControl, Disabled } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const meta = window.espamBlocks || {};

	Object.keys( meta ).forEach( ( name ) => {
		const attrs = meta[ name ].attributes || {};
		registerBlockType( name, {
			apiVersion: 3,
			edit( { attributes, setAttributes } ) {
				const blockProps = useBlockProps();
				const controls = Object.keys( attrs ).map( ( k ) =>
					attrs[ k ].enum
						? el( SelectControl, { key: k, label: k, value: attributes[ k ], options: attrs[ k ].enum.map( ( v ) => ( { label: v, value: v } ) ), onChange: ( v ) => setAttributes( { [ k ]: v } ) } )
						: el( TextControl, { key: k, label: k, value: attributes[ k ] || '', onChange: ( v ) => setAttributes( { [ k ]: v } ) } )
				);
				return el(
					'div',
					blockProps,
					controls.length ? el( InspectorControls, null, el( PanelBody, { title: 'Podešavanja' }, controls ) ) : null,
					el( Disabled, null, el( ServerSideRender, { block: name, attributes, skipBlockSupportAttributes: true } ) )
				);
			},
			save: () => null,
		} );
	} );
}( window.wp ) );
