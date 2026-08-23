<?php
/**
 * The PayPal account a form is paid into.
 *
 * A mistyped account is the most common support question this plugin gets, and
 * it is close to invisible: PayPal answers the customer with "Things don't
 * appear to be working at the moment", nothing comes back to the site, no IPN
 * arrives, and the merchant hears about it days later from the one customer who
 * bothered to say so.
 *
 * Format checking is not enough on its own. The real typo that prompted this was
 * seller_1336038004_biz@sailfun.co.ukx, which is a perfectly valid email address
 * and passes is_email() without complaint. Only PayPal can say whether an
 * address is an account, so there is also a check that asks them.
 *
 * Free of side effects at load, so the unit bootstrap can require it.
 *
 * @package Quick_Paypal_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What is wrong with the PayPal account a form would be paid into.
 *
 * A mistyped account is the most common support question this plugin gets, and
 * it is invisible until a customer reaches PayPal, who answer with their own
 * generic page. Nothing comes back to the site, no IPN arrives, and the merchant
 * hears "something went wrong" second hand, days later, from the one customer who
 * bothered to say so.
 *
 * Nothing can prove an address is a PayPal account without asking PayPal, and
 * there is no API for that. What can be caught is the shape being wrong, which
 * is what a typo usually produces.
 *
 * @param string $email Stored account address.
 *
 * @return string Empty when it looks usable, otherwise a reason to show.
 */
function qpp_paypal_account_problem( $email ) {
	$email = (string) $email;

	if ( '' === trim( $email ) ) {
		return __( 'No PayPal account has been entered, so this form cannot take a payment.', 'quick-paypal-payments' );
	}

	if ( trim( $email ) !== $email ) {
		return __( 'The PayPal account has a space at the start or the end.', 'quick-paypal-payments' );
	}

	/*
	 * A merchant ID is the other thing PayPal accepts here: 13 characters, upper
	 * case letters and digits. Allowed so anyone using one is not told their
	 * working setup is broken.
	 */
	if ( preg_match( '/^[A-Z0-9]{13}$/', $email ) ) {
		return '';
	}

	if ( ! is_email( $email ) ) {
		return __( 'The PayPal account is not a valid email address, so PayPal will refuse every payment.', 'quick-paypal-payments' );
	}

	return '';
}

/**
 * The PayPal account a given form would actually be paid into.
 *
 * Send Options carries a per form address that overrides the site wide one, so
 * checking only the Global screen would miss the form that is actually wrong.
 *
 * @param array $setup Stored setup options.
 * @param array $send  Stored send options for the form.
 *
 * @return string
 */
function qpp_paypal_account_for( $setup, $send ) {
	if ( ! empty( $send['email'] ) ) {
		return (string) $send['email'];
	}

	return isset( $setup['email'] ) ? (string) $setup['email'] : '';
}

/**
 * What PayPal's error codes mean, in words a merchant can act on.
 *
 * @return array<string, string>
 */
function qpp_paypal_error_messages() {
	return array(
		'INVALID_BUSINESS_ERROR' => __( 'PayPal does not recognise this account. Check the address for a typo, and check it is the address the PayPal account is registered to.', 'quick-paypal-payments' ),
		'INVALID_RECEIVER_ERROR' => __( 'PayPal will not accept payments to this account. It may not be confirmed, or it may not be able to receive payments in this currency.', 'quick-paypal-payments' ),
		'CURRENCY_NOT_SUPPORTED'  => __( 'PayPal does not support this currency for this account.', 'quick-paypal-payments' ),
	);
}

/**
 * Turns PayPal's answer to the check into a result.
 *
 * Split out from the request so it can be tested without a network. PayPal
 * answers a good account with the checkout page, and a bad one with a redirect
 * to an error page carrying a code in the query string. That code is the thing
 * worth showing: it is the only place the real reason is ever stated.
 *
 * @param int    $status   HTTP status.
 * @param string $location Location header, if any.
 *
 * @return array{ok: bool, code: string, message: string}
 */
function qpp_read_paypal_check( $status, $location ) {
	$status   = (int) $status;
	$location = (string) $location;

	if ( 200 === $status ) {
		return array(
			'ok'      => true,
			'code'    => '',
			'message' => __( 'PayPal accepted this account. A payment to it will reach the checkout page.', 'quick-paypal-payments' ),
		);
	}

	$code = '';
	if ( preg_match( '/[?&]code=([A-Z_]+)/', $location, $match ) ) {
		$code = $match[1];
	}

	$known = qpp_paypal_error_messages();

	if ( isset( $known[ $code ] ) ) {
		return array(
			'ok'      => false,
			'code'    => $code,
			'message' => $known[ $code ],
		);
	}

	if ( '' !== $code ) {
		return array(
			'ok'      => false,
			'code'    => $code,
			/* translators: %s is an error code from PayPal. */
			'message' => sprintf( __( 'PayPal refused this account and gave the reason %s.', 'quick-paypal-payments' ), $code ),
		);
	}

	return array(
		'ok'      => false,
		'code'    => '',
		'message' => __( 'PayPal did not accept this account, and did not say why.', 'quick-paypal-payments' ),
	);
}

/**
 * Asks PayPal whether it recognises an account.
 *
 * Sends the same checkout request a customer's browser would, and reads the
 * answer instead of following it. Nothing is charged and no order is created:
 * this is the step that builds the checkout page, which is exactly where a wrong
 * account is rejected. It is only ever run when a merchant presses the button.
 *
 * @param string $email   Account to check.
 * @param bool   $sandbox Whether the form is in sandbox mode.
 *
 * @return array{ok: bool, code: string, message: string}
 */
function qpp_check_paypal_account( $email, $sandbox = false ) {
	$problem = qpp_paypal_account_problem( $email );
	if ( '' !== $problem ) {
		return array(
			'ok'      => false,
			'code'    => '',
			'message' => $problem,
		);
	}

	$host = $sandbox ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';

	$response = wp_remote_post(
		$host . '/cgi-bin/webscr',
		array(
			'timeout'     => 20,
			// The answer is the redirect itself, so it must not be followed.
			'redirection' => 0,
			'body'        => array(
				'cmd'           => '_xclick',
				'business'      => $email,
				'currency_code' => 'GBP',
				'item_name'     => 'Account check',
				'amount'        => '1.00',
				'quantity'      => '1',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'ok'      => false,
			'code'    => '',
			/* translators: %s is an error message from WordPress. */
			'message' => sprintf( __( 'Could not reach PayPal to check: %s', 'quick-paypal-payments' ), $response->get_error_message() ),
		);
	}

	$location = wp_remote_retrieve_header( $response, 'location' );
	if ( is_array( $location ) ) {
		$location = reset( $location );
	}

	return qpp_read_paypal_check( wp_remote_retrieve_response_code( $response ), $location );
}

/**
 * The currencies PayPal accepts, with the countries they belong to.
 *
 * The other common support question, and the settings screen used to admit it:
 * "If your currency is not listed the plugin will work but PayPal will not
 * accept the payment." A free text box that takes an answer PayPal will refuse
 * is not a setting, it is a trap, so the screens offer this list instead.
 *
 * From PayPal's own currency code reference. Kept here rather than fetched,
 * because it changes about once a decade and a form must not stop working
 * because PayPal was unreachable.
 *
 * @return array<string, string> code => label
 */
function qpp_paypal_currencies() {
	return array(
		'AUD' => 'Australian dollar',
		'BRL' => 'Brazilian real',
		'CAD' => 'Canadian dollar',
		'CNY' => 'Chinese renminbi',
		'CZK' => 'Czech koruna',
		'DKK' => 'Danish krone',
		'EUR' => 'Euro',
		'HKD' => 'Hong Kong dollar',
		'HUF' => 'Hungarian forint',
		'ILS' => 'Israeli new shekel',
		'JPY' => 'Japanese yen',
		'MYR' => 'Malaysian ringgit',
		'MXN' => 'Mexican peso',
		'NZD' => 'New Zealand dollar',
		'NOK' => 'Norwegian krone',
		'PHP' => 'Philippine peso',
		'PLN' => 'Polish zloty',
		'GBP' => 'Pound sterling',
		'RUB' => 'Russian ruble',
		'SGD' => 'Singapore dollar',
		'SEK' => 'Swedish krona',
		'CHF' => 'Swiss franc',
		'TWD' => 'Taiwan new dollar',
		'THB' => 'Thai baht',
		'USD' => 'United States dollar',
	);
}

/**
 * Whether PayPal will take a payment in this currency.
 *
 * @param string $code Three letter code.
 *
 * @return bool
 */
function qpp_is_paypal_currency( $code ) {
	return array_key_exists( strtoupper( trim( (string) $code ) ), qpp_paypal_currencies() );
}

/**
 * A currency dropdown.
 *
 * A code that is already stored but is not on the list is kept and marked,
 * rather than quietly replaced. Somebody may have a working arrangement this
 * list does not know about, and silently changing what a form charges in is the
 * one thing worse than the free text box.
 *
 * @param string $name     Field name.
 * @param string $selected Stored code.
 * @param string $id       Optional element id.
 *
 * @return string
 */
function qpp_currency_select( $name, $selected, $id = '' ) {
	$selected = strtoupper( trim( (string) $selected ) );
	$out      = '<select name="' . esc_attr( $name ) . '"'
		. ( '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>';

	if ( '' !== $selected && ! qpp_is_paypal_currency( $selected ) ) {
		$out .= '<option value="' . esc_attr( $selected ) . '" selected="selected">'
			. esc_html( $selected ) . ' '
			. esc_html__( '(PayPal does not list this one)', 'quick-paypal-payments' )
			. '</option>';
	}

	$in_country = qpp_paypal_in_country_currencies();
	$no_pennies = qpp_paypal_zero_decimal_currencies();

	foreach ( qpp_paypal_currencies() as $code => $label ) {
		$note = '';
		if ( in_array( $code, $no_pennies, true ) ) {
			$note .= ' ' . __( '(whole units only)', 'quick-paypal-payments' );
		}
		if ( in_array( $code, $in_country, true ) ) {
			$note .= ' ' . __( '(in country accounts only)', 'quick-paypal-payments' );
		}

		$out .= '<option value="' . esc_attr( $code ) . '"'
			. selected( $selected, $code, false ) . '>'
			. esc_html( $code . ' - ' . $label . $note ) . '</option>';
	}

	return $out . '</select>';
}

/**
 * The currencies PayPal will not accept decimals in.
 *
 * The plugin had this as HKD, JPY, MYR and TWD, which is wrong twice over. HKD
 * and MYR do take decimals, so their amounts were being truncated to whole units
 * and the merchant was undercharged: 99.50 Hong Kong dollars was charged as 99.
 * HUF does not take decimals and was missing, so PayPal refused those payments
 * outright, which the customer saw as the payment not working.
 *
 * PayPal's list is HUF, JPY and TWD.
 *
 * @return string[]
 */
function qpp_paypal_zero_decimal_currencies() {
	return array( 'HUF', 'JPY', 'TWD' );
}

/**
 * How many decimal places PayPal expects for a currency.
 *
 * @param string $currency Three letter code.
 *
 * @return int 0 or 2.
 */
function qpp_paypal_decimals( $currency ) {
	$currency = strtoupper( trim( (string) $currency ) );

	return in_array( $currency, qpp_paypal_zero_decimal_currencies(), true ) ? 0 : 2;
}

/**
 * Currencies PayPal only supports for accounts held in that country.
 *
 * Footnotes 2 and 3 of PayPal's currency reference. Taking payment in one of
 * these from outside the country still works, but PayPal converts the funds into
 * the account's own currency at its own rate, which is a surprise on the
 * statement rather than a failure at the checkout. Worth saying on the screen
 * where the currency is chosen.
 *
 * @return string[]
 */
function qpp_paypal_in_country_currencies() {
	return array( 'BRL', 'CNY', 'MYR' );
}
