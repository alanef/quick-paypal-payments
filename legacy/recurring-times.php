<?php
/**
 * How many times a subscription payment recurs.
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
 * The number of payments to ask PayPal for (its srt field).
 *
 * The posted value used to override the form's setting unconditionally, so a
 * buyer could turn a twelve payment plan into two. It is now taken from the
 * buyer only when the form lets them choose, and only as a whole number PayPal
 * accepts. Anything else falls back to the form's setting, which is also what a
 * left blank choice has always meant.
 *
 * @param array $qpp       Form options.
 * @param mixed $submitted The srt value posted with the form.
 *
 * @return string
 */
function qpp_recurring_times( $qpp, $submitted ) {
	$configured = isset( $qpp['recurringhowmany'] ) ? (string) $qpp['recurringhowmany'] : '';

	if ( empty( $qpp['variablerecurring'] ) ) {
		return $configured;
	}

	$submitted = trim( (string) $submitted );

	if ( ! ctype_digit( $submitted ) || (int) $submitted < 1 || (int) $submitted > 52 ) {
		return $configured;
	}

	return (string) (int) $submitted;
}
