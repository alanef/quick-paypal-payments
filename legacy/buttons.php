<?php
/**
 * The button images that ship with the plugin.
 *
 * Kept apart from the main legacy file, which registers hooks as it loads and so
 * cannot be required from the unit bootstrap. Nothing here has any effect at
 * load. Same reason plan-gating.php exists.
 *
 * @package Quick_Paypal_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The button images that ship with the plugin.
 *
 * The gateway is which one the button names. A form sends its payment to one
 * gateway, so a Stripe button on a form that pays through PayPal tells the
 * customer something untrue about where their money is going, and vice versa.
 *
 * @return array<string, array{label: string, gateway: string}>
 */
function qpp_bundled_buttons() {
	return array(
		'paypal-button.png'            => array(
			'label'   => 'PayPal',
			'gateway' => 'paypal',
		),
		'pay-with-paypal-button.png'   => array(
			'label'   => 'Pay with PayPal',
			'gateway' => 'paypal',
		),
		'donate-via-paypal-button.png' => array(
			'label'   => 'Donate via PayPal',
			'gateway' => 'paypal',
		),
		'pay-with-stripe-button.png'   => array(
			'label'   => 'Pay with Stripe',
			'gateway' => 'stripe',
		),
		'pay-by-card-button.png'       => array(
			'label'   => 'Pay by Card',
			'gateway' => 'stripe',
		),
	);
}

/**
 * The buttons that name one gateway.
 *
 * @param string $gateway 'paypal' or 'stripe'.
 *
 * @return array<string, array{label: string, gateway: string}>
 */
function qpp_bundled_buttons_for( $gateway ) {
	return array_filter(
		qpp_bundled_buttons(),
		function ( $button ) use ( $gateway ) {
			return $gateway === $button['gateway'];
		}
	);
}

/**
 * URL of a button image that ships with the plugin.
 *
 * @param string $file File name from qpp_bundled_buttons().
 *
 * @return string
 */
function qpp_bundled_button_url( $file ) {
	return plugins_url( 'images/' . $file, QUICK_PAYPAL_PAYMENTS_PLUGIN_DIR . 'quick-paypal-payments.php' );
}

/**
 * Resolves a stored button image URL against where the plugin lives now.
 *
 * A bundled button is stored as a full URL, the same as one chosen from the
 * media library, because that is what the field has always held. That URL stops
 * working if the site moves domain or changes its content directory, and a
 * broken image where the pay button should be is the worst place to find out.
 * A stored URL that points at the plugin's own images folder is rebuilt from
 * the current location; anything else is returned untouched.
 *
 * @param string $url Stored value.
 *
 * @return string
 */
function qpp_resolve_button_url( $url ) {
	$url = (string) $url;

	if ( false === strpos( $url, '/images/' ) ) {
		return $url;
	}

	$file = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );

	if ( ! array_key_exists( $file, qpp_bundled_buttons() ) ) {
		return $url;
	}

	return qpp_bundled_button_url( $file );
}

/**
 * Which gateway a stored button image advertises.
 *
 * @param string $url Stored value.
 *
 * @return string 'paypal', 'stripe', or '' for an image that is not one of ours.
 */
function qpp_button_gateway( $url ) {
	$file    = basename( (string) wp_parse_url( (string) $url, PHP_URL_PATH ) );
	$buttons = qpp_bundled_buttons();

	return isset( $buttons[ $file ] ) ? $buttons[ $file ]['gateway'] : '';
}

/**
 * Whether a button image may be shown on a form as it is set up now.
 *
 * A merchant who took card payments and then stopped, by downgrading or by
 * switching Stripe off, keeps whatever button they chose. Showing it would put
 * "Pay by Card" on a form that sends the customer to PayPal, which is a lie
 * about where their money is going, so the form falls back to its text button
 * until Stripe is available again. The setting is not touched, so it comes back.
 *
 * An image the merchant uploaded is their business and is always allowed.
 *
 * @param string $url         Stored value.
 * @param bool   $stripe_live Whether the form can actually charge a card.
 *
 * @return bool
 */
function qpp_button_is_usable( $url, $stripe_live ) {
	if ( 'stripe' !== qpp_button_gateway( $url ) ) {
		return true;
	}

	return (bool) $stripe_live;
}
