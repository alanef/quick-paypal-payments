<?php
/**
 * Payment list actions that do not need WordPress.
 *
 * Kept free of side effects at load so the unit suite can require it in
 * isolation, and free of the qpp_get_stored_*() accessors so those stay mockable
 * for the tests that need them.
 *
 * @package Quick_Paypal_Payments
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marks the selected orders paid.
 *
 * Reconciling by hand is the free tier's answer to having no IPN listener. The
 * listener is a Silver feature from 6.0, so without this a free install has a
 * payment list it can never resolve: the status cell stays empty for the life of
 * the order and nothing the site owner can do changes it.
 *
 * Writes the same 'Paid' value into field18 that the IPN listener writes, so a
 * hand marked order is indistinguishable downstream and the existing hide paid
 * filter and CSV export keep working.
 *
 * @param array $messages Stored payment rows.
 * @param array $selected Row indexes the site owner ticked.
 *
 * @return array {
 *     @type array $messages Rows, with the selected ones marked paid.
 *     @type int   $marked   How many rows this actually changed.
 * }
 */
function qpp_mark_orders_paid( $messages, $selected ) {
	if ( ! is_array( $messages ) ) {
		return array(
			'messages' => array(),
			'marked'   => 0,
		);
	}

	$marked  = 0;
	$settled = array();
	foreach ( $selected as $index ) {
		if ( ! isset( $messages[ $index ] ) || ! is_array( $messages[ $index ] ) ) {
			continue;
		}
		// Already settled, by IPN or by hand. Not an error, but not a change
		// either, so it must not inflate the count the notice reports back.
		if ( isset( $messages[ $index ]['field18'] ) && 'Paid' === $messages[ $index ]['field18'] ) {
			continue;
		}
		$messages[ $index ]['field18'] = 'Paid';
		$marked ++;
		// The caller acts on these. This function stays free of side effects so
		// it can be tested on its own.
		$settled[] = $index;
	}

	return array(
		'messages' => $messages,
		'marked'   => $marked,
		// Indexes of the rows this call settled, for whatever the caller has
		// to do about a payment completing.
		'settled'  => $settled,
	);
}

/**
 * Maps a stored payment row onto the named values the mail templates use.
 *
 * The row is stored as field1 to field22. Every consumer that wants to send a
 * confirmation has to translate it, so the translation lives once. Two gateways
 * now settle orders, and a mapping that drifts between them would send different
 * details for the same purchase depending on how it was paid for.
 *
 * @param array $row Stored payment row.
 *
 * @return array
 */
function qpp_order_row_to_values( $row ) {
	$field = function ( $key ) use ( $row ) {
		return isset( $row[ $key ] ) ? $row[ $key ] : '';
	};

	return array(
		'reference'     => $field( 'field1' ),
		'quantity'      => $field( 'field2' ),
		'amount'        => $field( 'field3' ),
		'stock'         => $field( 'field4' ),
		'option1'       => $field( 'field5' ),
		'email'         => $field( 'field8' ),
		'firstname'     => $field( 'field9' ),
		'lastname'      => $field( 'field10' ),
		'address1'      => $field( 'field11' ),
		'address2'      => $field( 'field12' ),
		'city'          => $field( 'field13' ),
		'state'         => $field( 'field14' ),
		'zip'           => $field( 'field15' ),
		'country'       => $field( 'field16' ),
		'night_phone_b' => $field( 'field17' ),
		'yourmessage'   => $field( 'field19' ),
		'datepicker'    => $field( 'field20' ),
		'cf'            => $field( 'field21' ),
		'consent'       => $field( 'field22' ),
	);
}

/**
 * Row indexes ticked on the payment list.
 *
 * The list posts one field per row, named for the row index and valued
 * 'checked'. Reading it back means trusting nothing but the shape.
 *
 * @param array $post   The request body.
 * @param int   $count  How many rows the list held.
 *
 * @return array Integer indexes.
 */
function qpp_selected_payment_rows( $post, $count ) {
	$selected = array();
	for ( $i = 0; $i <= $count; $i ++ ) {
		if ( isset( $post[ $i ] ) && 'checked' === $post[ $i ] ) {
			$selected[] = $i;
		}
	}

	return $selected;
}
