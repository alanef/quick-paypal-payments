<?php
/**
 * Signed shortcode attributes.
 *
 * The [qpp] shortcode's attributes (form, amount, id, stock, labels) decide the
 * price. The ajax submission cannot see the shortcode, so they travel with the
 * form. They used to travel as plain hidden fields and were read back as
 * trusted, so posting sc[amount]=0.01 turned a fixed price form into a penny
 * one, and the IPN expectation was stored at the forged price too. They now
 * travel as one field carrying the attributes and an HMAC, and anything that
 * does not verify is refused.
 *
 * Kept in its own file with no side effects at load time so it can be required
 * directly by the test suite without booting the rest of the plugin.
 *
 * @package Quick_Paypal_Payments
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The HMAC over an encoded attribute set.
 *
 * @param string $encoded Hex encoded JSON of the attributes.
 *
 * @return string
 */
function qpp_shortcode_attributes_hmac( $encoded ) {
	return hash_hmac( 'sha256', 'qpp_shortcode_attributes|' . $encoded, wp_salt( 'auth' ) );
}

/**
 * Encode and sign a set of shortcode attributes for the form's hidden field.
 *
 * Hex encoding keeps the value to [0-9a-f], so neither escaping on the way out
 * nor sanitize_text_field() on the way back can change a byte of what was
 * signed. It also means a crafted attribute cannot break out of the field.
 *
 * The signature carries no timestamp, so a page served from a cache stays
 * valid for as long as the site's salts do.
 *
 * @param array $atts Shortcode attributes, including the post they render on.
 *
 * @return string Value for the qpp_sc hidden field.
 */
function qpp_sign_shortcode_attributes( $atts ) {
	$atts    = is_array( $atts ) ? $atts : array();
	$encoded = bin2hex( (string) wp_json_encode( $atts ) );

	return $encoded . '.' . qpp_shortcode_attributes_hmac( $encoded );
}

/**
 * The shortcode attributes a submission may be trusted with.
 *
 * Returns the signed attributes when the signature verifies, and false
 * otherwise, including when no signature was posted at all. An unsigned
 * submission cannot be told apart from one with its attributes stripped, which
 * would turn a page's [qpp amount="250"] back into the form's own settings, so
 * there is no unsigned fallback. A page cached before signing was introduced
 * needs its cache cleared.
 *
 * @param string $signed Posted qpp_sc value.
 *
 * @return array|false Sanitised attributes, or false if they cannot be trusted.
 */
function qpp_verified_shortcode_attributes( $signed ) {

	if ( ! is_string( $signed ) || '' === $signed ) {
		return false;
	}

	$parts = explode( '.', $signed );

	if ( 2 !== count( $parts ) || '' === $parts[0] || ! ctype_xdigit( $parts[0] ) || 0 !== strlen( $parts[0] ) % 2 ) {
		return false;
	}

	if ( ! hash_equals( qpp_shortcode_attributes_hmac( $parts[0] ), $parts[1] ) ) {
		return false;
	}

	$atts = json_decode( hex2bin( $parts[0] ), true );

	return is_array( $atts ) ? qpp_sanitize_shortcode_attributes( $atts ) : false;
}

/**
 * Sanitise a verified attribute set. Values are scalars from a shortcode or a
 * block, so anything else is dropped rather than passed on.
 *
 * @param array $atts Attributes.
 *
 * @return array
 */
function qpp_sanitize_shortcode_attributes( $atts ) {
	$clean = array();

	foreach ( $atts as $key => $value ) {
		if ( is_scalar( $value ) ) {
			$clean[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
		}
	}

	return $clean;
}
