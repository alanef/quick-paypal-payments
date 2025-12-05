<?php
function qpp_block_init() {

	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

// Register our block editor script.
	wp_register_script(
		'qpp_block',
		plugins_url( 'block.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
	);

// Register our block, and explicitly define the attributes we accept.
	register_block_type(
		'quick-paypal-payments/block', array(
			'editor_script'   => 'qpp_block', // The script name we gave in the wp_register_script() call.
			'render_callback' => 'qpp_loop'
		)
	);
}

add_action( 'init', 'qpp_block_init' );