<?php
/**
 * Creating a WordPress account for a buyer.
 *
 * Free of side effects at load so the unit bootstrap can require it, same reason
 * plan-gating.php and buttons.php are separate files.
 *
 * @package Quick_Paypal_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The roles a payment form may be allowed to create.
 *
 * Administrator is deliberately absent. This creates an account from a form
 * anyone on the internet can submit, and the username is whatever they typed in
 * the first name box, so an administrator here is a way to hand the site away.
 * Anything else the site has is offered, including roles added by other plugins,
 * because a membership or shop role is exactly what a merchant would want.
 *
 * @return array<string, string> role slug => display name
 */
function qpp_creatable_roles() {
	$roles = array();

	if ( function_exists( 'get_editable_roles' ) ) {
		foreach ( get_editable_roles() as $slug => $role ) {
			if ( 'administrator' === $slug ) {
				continue;
			}
			$roles[ $slug ] = isset( $role['name'] ) ? $role['name'] : $slug;
		}
	}

	if ( empty( $roles ) ) {
		$roles['subscriber'] = 'Subscriber';
	}

	return $roles;
}

/**
 * The role a form should actually create, whatever is stored.
 *
 * A stored role can stop existing, because the plugin that added it was removed
 * or a site was migrated, and wp_update_user() with a role that does not exist
 * leaves the account with no role at all. It can also be a role that is no longer
 * allowed. Either way the answer is subscriber, which every WordPress site has.
 *
 * @param string $stored Saved role slug.
 *
 * @return string
 */
function qpp_creatable_role( $stored ) {
	$stored = (string) $stored;
	$roles  = qpp_creatable_roles();

	return isset( $roles[ $stored ] ) ? $stored : 'subscriber';
}

/**
 * Whether an account should be created at this point in the payment.
 *
 * It used to be created when the form was submitted, which is before any money
 * has moved, so a buyer who did not complete payment still got a working login.
 * On a form where the account is the thing being bought, that gave it away. After payment
 * is the default now, and the older behaviour is still available for anyone who
 * wants the account to exist before the buyer reaches PayPal.
 *
 * @param array  $send  Stored send options for the form.
 * @param string $stage 'aftersubmission' or 'afterpayment'.
 *
 * @return bool
 */
function qpp_should_create_user( $send, $stage ) {
	if ( empty( $send['createuser'] ) ) {
		return false;
	}

	$when = isset( $send['createuserwhen'] ) ? (string) $send['createuserwhen'] : '';

	// Anything unrecognised, including the empty value stored before this was a
	// setting, means after payment.
	if ( 'aftersubmission' !== $when ) {
		$when = 'afterpayment';
	}

	return $when === $stage;
}

/**
 * Reads a number out of a settings field a merchant typed into.
 *
 * The fields are free text, so they arrive with currency symbols, spaces, a
 * comma for a decimal point, or nothing at all. The old check was
 * is_numeric( (float) $value ), which is true for everything, because casting to
 * float is what it was meant to be guarding against: an empty postage
 * percentage got through it and "" / 100 is a TypeError in PHP 8, so a form with
 * postage switched on and no percentage entered fatally errored on submission.
 *
 * @param mixed $value Stored setting.
 *
 * @return float|null The number, or null if there is not one.
 */
function qpp_numeric_setting( $value ) {
	if ( is_int( $value ) || is_float( $value ) ) {
		return (float) $value;
	}

	// A comma is a decimal point to most of Europe, and "2,50" cast straight to
	// float is 2.0, which silently undercharges rather than failing.
	$clean = str_replace( ',', '.', (string) $value );
	$clean = preg_replace( '/[^.0-9-]/', '', $clean );

	if ( ! is_numeric( $clean ) ) {
		return null;
	}

	return (float) $clean;
}
