var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender = wp.components.ServerSideRender,
	TextControl = wp.components.TextControl,
	RadioControl = wp.components.RadioControl,
    SelectControl = wp.components.SelectControl,
	TextareaControl = wp.components.TextareaControl,
	CheckboxControl = wp.components.CheckboxControl;

registerBlockType( 'quick-paypal-payments/block', {
	title: 'Quick PayPal Payments',
    description: 'Displays the payment form',
	icon: 'admin-settings',
	category: 'widgets',

	edit: function( props ) {		
		return [
			el( 'h2', // Tag type.
					{
						className: props.className,  // Class name is generated using the block's name prefixed with wp-block-, replacing the / namespace separator with a single -.
					},
					'Quick PayPal Payments' // Block content
				),
		];
	},

	save: function() {
		return null;
	},
} );