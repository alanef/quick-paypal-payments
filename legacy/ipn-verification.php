<?php
/**
 * PayPal IPN payment verification.
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
 * The raw IPN payload as posted by PayPal.
 *
 * Wrapped in a function so the request body can be supplied by a filter. There
 * is no other way to exercise the listener end to end, because php://input
 * cannot be populated from a test.
 *
 * @return string Raw request body.
 */
function qpp_get_raw_ipn_payload() {

	/**
	 * Filters the raw IPN request body before it is parsed.
	 *
	 * @param string $payload Raw request body.
	 */
	return (string) apply_filters( 'qpp_raw_ipn_payload', file_get_contents( 'php://input' ) );
}

/**
 * Verify that an IPN describes a completed payment of the expected amount to
 * the expected PayPal account.
 *
 * A VERIFIED response from PayPal proves only that the message is genuine. It
 * says nothing about who was paid, how much, or whether the payment completed.
 * PayPal Standard lets the buyer control the amount they actually pay, so all
 * of that has to be checked before an order is treated as paid.
 *
 * @param array $ipn      Raw IPN fields as posted by PayPal.
 * @param array $expected Expected payment, from qpp_get_ipn_expectation().
 *
 * @return true|string True when the payment is good, otherwise the reason it was rejected.
 */
function qpp_verify_ipn_payment( $ipn, $expected ) {

	$result = qpp_check_ipn_payment( $ipn, $expected );

	/**
	 * Filters the result of IPN payment verification.
	 *
	 * Return true to accept the payment, or a string explaining why it was
	 * rejected. Useful where a form deliberately accepts an amount other than
	 * the one it asked for, such as an open donation.
	 *
	 * @param true|string $result   True when verified, otherwise the rejection reason.
	 * @param array       $ipn      Raw IPN fields as posted by PayPal.
	 * @param array       $expected Expected payment for this order.
	 */
	return apply_filters( 'qpp_verify_ipn_payment', $result, $ipn, $expected );
}

/**
 * Run the payment checks. See qpp_verify_ipn_payment().
 *
 * @param array $ipn      Raw IPN fields as posted by PayPal.
 * @param array $expected Expected payment for this order.
 *
 * @return true|string True when the payment is good, otherwise the reason it was rejected.
 */
function qpp_check_ipn_payment( $ipn, $expected ) {

	$txn_type = isset( $ipn['txn_type'] ) ? strtolower( trim( $ipn['txn_type'] ) ) : '';

	/*
	 * PayPal reports the account the button was addressed to in business, and
	 * the account's primary address in receiver_email. The two differ when a
	 * merchant takes payment on a secondary address, so either one matching the
	 * configured account is good enough.
	 */
	$receivers = array();

	foreach ( array( 'receiver_email', 'business' ) as $key ) {
		if ( ! empty( $ipn[ $key ] ) ) {
			$receivers[] = strtolower( trim( $ipn[ $key ] ) );
		}
	}

	if ( empty( $receivers ) ) {
		return 'IPN did not identify the receiving PayPal account';
	}

	if ( '' === $expected['receiver'] ) {
		return 'no merchant PayPal account is configured to check the receiver against';
	}

	if ( ! in_array( $expected['receiver'], $receivers, true ) ) {
		return 'payment was received by ' . implode( '/', $receivers ) . ' but this form is configured for ' . $expected['receiver'];
	}

	/*
	 * Which field carries the amount depends on the notification. A new
	 * subscription is confirmed before any money moves and carries the
	 * per-period amount in amount3. Everything else reports what was actually
	 * received in mc_gross. Subscription notifications that are not payments at
	 * all (cancellation, failure, end of term) must never mark an order paid.
	 */
	if ( 'subscr_signup' === $txn_type ) {
		$amount_field = 'amount3';
	} elseif ( 0 === strpos( $txn_type, 'subscr_' ) && 'subscr_payment' !== $txn_type ) {
		return 'notification type ' . $txn_type . ' is not a payment';
	} else {
		$amount_field   = 'mc_gross';
		$payment_status = isset( $ipn['payment_status'] ) ? trim( $ipn['payment_status'] ) : '';

		if ( 'Completed' !== $payment_status ) {
			return 'payment status is ' . ( '' === $payment_status ? 'missing' : $payment_status ) . ', not Completed';
		}
	}

	if ( '' !== $expected['currency'] && ! empty( $ipn['mc_currency'] ) ) {
		if ( strtoupper( trim( $ipn['mc_currency'] ) ) !== $expected['currency'] ) {
			return 'paid in ' . strtoupper( trim( $ipn['mc_currency'] ) ) . ' but this order is in ' . $expected['currency'];
		}
	}

	/*
	 * A zero expected total means the form takes whatever amount the buyer
	 * enters, so there is nothing to compare against.
	 */
	if ( $expected['gross'] > 0 ) {
		if ( ! isset( $ipn[ $amount_field ] ) || ! is_numeric( $ipn[ $amount_field ] ) ) {
			return 'IPN carried no usable ' . $amount_field . ' to check the amount against';
		}

		$paid = (float) $ipn[ $amount_field ];

		// Half a cent of tolerance so rounding never rejects a good payment.
		if ( $paid + 0.005 < $expected['gross'] ) {
			return 'paid ' . $paid . ' but this order totals ' . $expected['gross'];
		}
	}

	return true;
}

/**
 * Build the payment expected for an order.
 *
 * Prefers the record written when the order was created, which is what the
 * PayPal button actually asked for. Orders placed before this version have no
 * such record, so fall back to the stored order total and the form's current
 * settings.
 *
 * @param string $custom  The order's PayPal custom token.
 * @param array  $order   The stored order record.
 * @param string $form_id The form the order belongs to.
 *
 * @return array Expected gross, currency and receiver.
 */
function qpp_get_ipn_expectation( $custom, $order, $form_id ) {

	$expectations = get_option( 'qpp_ipn_expectations' );

	if ( is_array( $expectations ) && isset( $expectations[ $custom ] ) ) {
		$stored = $expectations[ $custom ];

		return array(
			'gross'    => isset( $stored['gross'] ) ? (float) $stored['gross'] : 0.0,
			'currency' => isset( $stored['currency'] ) ? strtoupper( (string) $stored['currency'] ) : '',
			'receiver' => isset( $stored['receiver'] ) ? strtolower( trim( (string) $stored['receiver'] ) ) : '',
		);
	}

	$qpp_setup = qpp_get_stored_setup();
	$send      = qpp_get_stored_send( $form_id );
	$currency  = qpp_get_stored_curr();

	$receiver = ! empty( $send['email'] ) ? $send['email'] : ( isset( $qpp_setup['email'] ) ? $qpp_setup['email'] : '' );

	return array(
		'gross'    => isset( $order['field3'] ) ? (float) $order['field3'] : 0.0,
		'currency' => isset( $currency[ $form_id ] ) ? strtoupper( substr( (string) $currency[ $form_id ], 0, 3 ) ) : '',
		'receiver' => strtolower( trim( (string) $receiver ) ),
	);
}

/**
 * Record what an order is asking PayPal to charge, for the IPN listener to
 * check the payment against later.
 *
 * @param string $custom The order's PayPal custom token.
 * @param array  $args   Expected gross, currency, receiver and form id.
 */
function qpp_store_ipn_expectation( $custom, $args ) {

	if ( empty( $custom ) ) {
		return;
	}

	$expectations = get_option( 'qpp_ipn_expectations' );

	if ( ! is_array( $expectations ) ) {
		$expectations = array();
	}

	$expectations[ $custom ] = array(
		'gross'    => isset( $args['gross'] ) ? (float) $args['gross'] : 0.0,
		'currency' => isset( $args['currency'] ) ? strtoupper( (string) $args['currency'] ) : '',
		'receiver' => isset( $args['receiver'] ) ? strtolower( trim( (string) $args['receiver'] ) ) : '',
		'form'     => isset( $args['form'] ) ? (string) $args['form'] : '',
		'created'  => time(),
	);

	update_option( 'qpp_ipn_expectations', qpp_prune_ipn_expectations( $expectations ), false );
}

/**
 * Drop an order's expectation record once its payment has been accepted.
 *
 * @param string $custom The order's PayPal custom token.
 */
function qpp_clear_ipn_expectation( $custom ) {

	$expectations = get_option( 'qpp_ipn_expectations' );

	if ( ! is_array( $expectations ) || ! isset( $expectations[ $custom ] ) ) {
		return;
	}

	unset( $expectations[ $custom ] );

	update_option( 'qpp_ipn_expectations', $expectations, false );
}

/**
 * Keep the expectation store bounded. Forms that are filled in but never taken
 * through PayPal would otherwise accumulate indefinitely.
 *
 * @param array $expectations Current expectation records.
 *
 * @return array Pruned expectation records.
 */
function qpp_prune_ipn_expectations( $expectations ) {

	$cutoff = time() - ( 180 * DAY_IN_SECONDS );

	foreach ( $expectations as $key => $expectation ) {
		if ( ! isset( $expectation['created'] ) || (int) $expectation['created'] < $cutoff ) {
			unset( $expectations[ $key ] );
		}
	}

	$limit = (int) apply_filters( 'qpp_ipn_expectation_limit', 2000 );

	if ( $limit > 0 && count( $expectations ) > $limit ) {
		$expectations = array_slice( $expectations, - $limit, null, true );
	}

	return $expectations;
}

/**
 * Whether a PayPal transaction has already been processed.
 *
 * @param string $txn_id PayPal transaction id.
 *
 * @return bool
 */
function qpp_ipn_txn_seen( $txn_id ) {

	$seen = get_option( 'qpp_ipn_txn_ids' );

	return is_array( $seen ) && in_array( $txn_id, $seen, true );
}

/**
 * Record a processed PayPal transaction so the same one cannot be replayed.
 *
 * @param string $txn_id PayPal transaction id.
 */
function qpp_ipn_record_txn( $txn_id ) {

	$seen = get_option( 'qpp_ipn_txn_ids' );

	if ( ! is_array( $seen ) ) {
		$seen = array();
	}

	$seen[] = $txn_id;

	$limit = (int) apply_filters( 'qpp_ipn_txn_history_limit', 1000 );

	if ( $limit > 0 && count( $seen ) > $limit ) {
		$seen = array_slice( $seen, - $limit );
	}

	update_option( 'qpp_ipn_txn_ids', $seen, false );
}

/**
 * Log an IPN that came from PayPal but did not describe a valid payment for the
 * order. The order is left pending and no confirmation is sent.
 *
 * @param string $reason Why the IPN was rejected.
 * @param array  $ipn    Raw IPN fields as posted by PayPal.
 */
function qpp_ipn_reject( $reason, $ipn ) {

	$txn     = ! empty( $ipn['txn_id'] ) ? $ipn['txn_id'] : 'unknown';
	$message = 'Quick PayPal Payments: IPN rejected (txn ' . $txn . '): ' . $reason;

	if ( defined( 'QPP_IPN_DEBUG_LOG_FILE' ) && false !== QPP_IPN_DEBUG_LOG_FILE ) {
		error_log( gmdate( '[Y-m-d H:i e] ' ) . $message . PHP_EOL, 3, QPP_IPN_DEBUG_LOG_FILE );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( $message );
	}

	/**
	 * Fires when a genuine IPN is rejected because it does not match the order.
	 *
	 * @param string $reason Why the IPN was rejected.
	 * @param array  $ipn    Raw IPN fields as posted by PayPal.
	 */
	do_action( 'qpp_ipn_rejected', $reason, $ipn );
}
