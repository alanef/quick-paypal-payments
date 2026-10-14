<?php
/**
 * Form names.
 *
 * A form name arrives from the [qpp form=""] and [qppreport form=""] shortcode
 * attributes, the ?form= prefill link and the ajax form_id, and ends up in
 * option names, HTML attributes and an inline script. It used to be passed on
 * as given, so a Contributor could store [qpp form='");alert(1);//'] and run
 * script for anyone viewing the post, admins previewing it included (QPP-60).
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
 * A form name reduced to the characters a form can have.
 *
 * The settings screen creates form names from letters only. Digits, underscores
 * and hyphens are kept as well so that any older form named with them still
 * resolves, and none of them can end a string or an attribute. Case is kept,
 * because the name is part of an option name and options are case sensitive,
 * which is why this is not sanitize_key().
 *
 * @param mixed $form Form name from a shortcode attribute or a request.
 *
 * @return string The name, or '' (the default form) if nothing usable is left.
 */
function qpp_sanitize_form_name( $form ) {
	if ( ! is_scalar( $form ) ) {
		return '';
	}

	return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $form );
}

/**
 * The form a ?form= link asks for, if it is one the merchant has set up.
 *
 * The link lets a merchant send a customer to a page with a plain [qpp] and
 * have it show one of their other forms. It used to be applied late, inside
 * the render, after the default form's settings had been read and with the
 * shortcode's own attributes signed, so the form showed mixed settings and its
 * submission was refused as not matching what was signed. Resolving it first,
 * and only to a form that exists, keeps the whole render and the submission on
 * the one form.
 *
 * @param array $setup     The qpp_setup option.
 * @param mixed $requested The ?form= value.
 *
 * @return string The form's name, or '' to stay on the default form.
 */
function qpp_prefill_form_name( $setup, $requested ) {
	$requested = qpp_sanitize_form_name( $requested );

	if ( '' === $requested || ! is_array( $setup ) || empty( $setup['alternative'] ) ) {
		return '';
	}

	$forms = array_map( 'trim', explode( ',', (string) $setup['alternative'] ) );

	return in_array( $requested, $forms, true ) ? $requested : '';
}
