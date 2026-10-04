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
 * Settles through qpp_settle_order(), which writes the same 'Paid' value into
 * field18 that the IPN listener writes, so the existing hide paid filter and
 * CSV export keep working, and records that it was done by hand.
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
		$messages[ $index ] = qpp_settle_order( $messages[ $index ], 'manual' );
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
 * Marks one stored order row paid, and records what settled it.
 *
 * field18 holds 'Paid' whoever settled the order, which the hide paid filter,
 * the CSV export and the IPN status column all rely on, so it stays. Without IPN
 * the payments list has to say whether a payment was reconciled by hand, and a
 * Stripe payment on a site with IPN off must not read as one, so the route is
 * kept beside it. Rows settled before 6.0.3 have no route and read as plain paid.
 *
 * @param array  $row Stored payment row.
 * @param string $via 'manual', 'ipn' or 'stripe'.
 *
 * @return array
 */
function qpp_settle_order( $row, $via ) {
	$row['field18'] = 'Paid';
	$row['paidvia'] = (string) $via;

	return $row;
}

/**
 * The payment status to show for a row on a site without the IPN listener.
 *
 * With IPN on, the list keeps its long standing status column, labelled from the
 * IPN settings. Without it there used to be no column at all, so an unchecked
 * payment and a paid one looked identical. That is every free install, and any
 * paid one with IPN switched off.
 *
 * The public [qppreport] shortcode gets no status at all, since whether a named
 * person has paid is not something to publish. The emailed list goes to the site
 * owner but outside the admin, so it is plain paid or unpaid.
 *
 * @param array  $row     Stored payment row.
 * @param string $context 'admin', 'email' or 'public'.
 *
 * @return array{label: string, class: string}|null Null when no status is shown.
 */
function qpp_payment_status_without_ipn( $row, $context ) {
	if ( 'public' === $context ) {
		return null;
	}

	$paid = isset( $row['field18'] ) && 'Paid' === $row['field18'];

	if ( 'email' === $context ) {
		return array(
			'label' => $paid ? __( 'Paid', 'quick-paypal-payments' ) : __( 'Unpaid', 'quick-paypal-payments' ),
			'class' => $paid ? 'qpp-paid' : 'qpp-pending',
		);
	}

	if ( ! $paid ) {
		return array(
			'label' => __( 'Requires manual reconcile', 'quick-paypal-payments' ),
			'class' => 'qpp-pending',
		);
	}

	$via    = isset( $row['paidvia'] ) ? $row['paidvia'] : '';
	$labels = array(
		'manual' => __( 'Manually reconciled', 'quick-paypal-payments' ),
		'ipn'    => __( 'Confirmed by PayPal', 'quick-paypal-payments' ),
		'stripe' => __( 'Confirmed by Stripe', 'quick-paypal-payments' ),
	);

	return array(
		'label' => isset( $labels[ $via ] ) ? $labels[ $via ] : __( 'Paid', 'quick-paypal-payments' ),
		'class' => 'qpp-paid',
	);
}

/**
 * How many stored payments nobody has marked paid.
 *
 * @param array $messages Stored payment rows.
 *
 * @return int
 */
function qpp_count_unreconciled( $messages ) {
	if ( ! is_array( $messages ) ) {
		return 0;
	}

	$count = 0;
	foreach ( $messages as $row ) {
		if ( is_array( $row ) && ( ! isset( $row['field18'] ) || 'Paid' !== $row['field18'] ) ) {
			$count ++;
		}
	}

	return $count;
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
