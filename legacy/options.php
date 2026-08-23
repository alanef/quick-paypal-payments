<?php
// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function qpp_get_stored_setup () {
    $qpp_setup = get_option('qpp_setup');
    if(!is_array($qpp_setup)) $qpp_setup = array();
    $default = array(
        /*
         * Both name a form, and the default form's name is the empty string.
         * They defaulted to false, so on a fresh install $id was false, and
         * using it as an array key casts it to the integer 0: every screen that
         * looked up $currency[$id] warned until the setup screen had been saved.
         * Empty string is as falsy as false for the truth checks around them,
         * and explode() on false is deprecated in any case.
         */
        'current'       => '',
        'alternative'   => '',
        'disable_error' => false,
        'sandbox'       => false,
        'encryption'    => false,
        'location'      => 'head',
        'image_url'     => false,
        'nostore'       => false,
        // The merchant PayPal address. Absent until the setup screen is saved,
        // and read unconditionally by qpp_get_default_email() among others.
        'email'         => ''
    );
    $qpp_setup = array_merge($default, $qpp_setup);
    return $qpp_setup;
}

function qpp_get_stored_curr () {
    $qpp_curr = get_option('qpp_curr');
    if(!is_array($qpp_curr)) $qpp_curr = array();
    $default = qpp_get_default_curr();
    $qpp_curr = array_merge($default, $qpp_curr);
    return $qpp_curr;
}

function qpp_get_default_curr () {
    $qpp_curr = array();
    $qpp_curr[''] = 'USD';
    return $qpp_curr;
}

function qpp_get_stored_email () {
    $qpp_email = get_option('qpp_email');
    if(!is_array($qpp_email)) $qpp_email = array();
    $default = qpp_get_default_email();
    $qpp_email = array_merge($default, $qpp_email);
    return $qpp_email;
}

function qpp_get_default_email () {
    $qpp_setup = qpp_get_stored_setup();
    $qpp_email = array();
    $qpp_email[''] = $qpp_setup['email'];
    return $qpp_email;
}

function qpp_get_stored_msg () {
    $messageoptions = get_option('qpp_messageoptions');

    return qpp_merge_msg($messageoptions);
}
function qpp_merge_msg ($messageoptions) {
	if(!is_array($messageoptions)) $messageoptions = array();
	$default = array(
		'messageqty'    => 'fifty',
		'messageorder'  => 'newest',
		'hidepaid'      => '',
		'showaddress'   => '',
	);
	$messageoptions = array_merge($default, $messageoptions);
	return $messageoptions;
}

function qpp_get_stored_options($id) {
    $qpp = get_option('qpp_options'.$id);
    if(!is_array($qpp)) $qpp = array();
    $default = array(
        'sort' => 'field1,field4,field2,field3,field5,field6,field7,field9,field12,field13,field14,field11,field8,field10,field15,field16,field17,field18,field19,field21,field22',
        'title' => 'Payment Form',
        'blurb' => 'Enter the payment details and submit',
        'inputreference' => 'Payment reference',
        'inputamount' => 'Amount to pay',
        'comboboxword' => 'Other',
        'comboboxlabel' => 'Enter Amount',
        'sandbox' =>'',
        'quantitylabel' => 'Quantity',
        'quantity' => '1',
        'stocklabel' => 'Item Number',
        'use_stock' => '',
        'cflabel' => 'Codice Fiscale',
        'use_cf' => '',
        'ruse_cf' => '',
        // Saved by the form options screen but was missing here, so it warned
        // on any form whose options had not been saved yet.
        'ruse_consent' => '',
        'consentlabel' =>  esc_html__('I consent to my data being retained by the site owner after payment has been processed.','quick-paypal-payments'),
        'consentpaypal' => esc_html__('Consent Given','quick-paypal-payments'),
        'noconsentpaypal' => esc_html__('Consent NOT Given','quick-paypal-payments'),
        'use_consent' => '',
        'optionlabel' => 'Options',
        'optionvalues' => 'Large,Medium,Small',
        'use_options' => '',
        'use_slider' => '',
        'sliderlabel' => 'Amount to pay',
        'min' => '0',
        'max' => '100',
        'initial' => '50',
        'step' => '10',
        'output-values' => 'checked',
        'messagelabel' => 'Message',
        'shortcodereference' => 'Payment for: ',
        'shortcodeamount' => 'Amount: ',
        'paypal-location' => 'imagebelow',
        'captcha' => '',
        'mathscaption' => 'Spambot blocker question',
        'submitcaption' => 'Make Payment',
        'resetcaption' => 'Reset Form',
        'use_reset' => '',
        'useprocess' => '',
        'processblurb' => 'A processing fee will be added before payment',
        'processref' => 'Processing Fee',
        'processtype' => 'processpercent',
        'processpercent' => '5',
        'processfixed' => '2',
        'usepostage' => '',
        'postageblurb' => 'Handling charge will be added before payment',
        'postageref' => 'Handling',
        'postagepercent' => '5',
        'postagefixed' => '5',
        'usecoupon' => '',
        'useblurb' => '',
        'useemail' => '',
        'extrablurb'            => 'Make sure you complete the next field',
        'couponblurb'           => 'Enter coupon code',
        'couponref'             => 'Coupon Applied',
        'couponbutton'          => 'Apply Coupon',
        'termsblurb'            => 'I agree to the Terms and Conditions',
        'termsurl'              => home_url(),
        'termspage'             => 'checked',
        'quantitymaxblurb'      => 'maximum of 99',
        'userecurring'          => '',
        'recurringblurb'        => 'Subscription details:',
        'recurring'             => 'M',
        'recurringhowmany'      => '52',
        'Dperiod'               => 'day',
        'Wperiod'               => 'week',
        'Mperiod'               => 'month',
        'Yperiod'               => 'year',
        'quick-paypal-payments'                   => 0,
        'srt'                   => 2,
        'payments'              => 'Number of payments:',
        'every'                 => 'payments every ',
        'useaddress'            => '',
        'addressblurb'          => 'Enter your details below',
        'use_datepicker'        => '',
        'datepickerlabel'       => 'Select date',
        'usetotals'             => '',
        'totalsblurb'           => 'Total:',
        'emailblurb'            => 'Your email address',
        'couponapplied'         => '',
        'currency_seperator'    => 'period',
        'inline_amount'         => '',
        'selector'              => 'radio',
        'refselector'           => 'radio',
        'optionsselector'       => 'radio',
        'fixedreference'        => false,
        'fixedamount'           => false,
        'minamount'             => 0,
        'use_multiples'         => false,
        'noproduct'             => false,
        'paypal-url'            => false,
        'use_quantity'          => false,
        'useterms'              => false,
        'use_message'           => false,
        'postagetype'           => false,
	    'optionselector' => '',
	    'allow_amount' => '',
	    'combobox' => '',
	    'fixedstock' => '',
	    'ruse_stock' => '',
	    'quantitymax' => '',
	    'inline_options' => '',
	    'variablerecurring' => '',
	    'ruseemail' => '',
	    'ruse_message' => '',
	    'ruse_datepicker' => '',

    );
    $qpp = array_merge($default, $qpp);

    if ($qpp['postagetype']) {
        if ($qpp['postagetype'] == 'postagefixed') $qpp['postagepercent'] = false;
        if ($qpp['postagetype'] == 'postagepercent') $qpp['postagefixed'] = false;
        $qpp['postagetype'] = false;
    }

    // Applied here rather than at each use because this is the only read path for
    // the option, so the form builder, the IPN listener, the confirmation email and
    // the enqueued JS all see a gated feature as switched off. The stored value is
    // left untouched, so it returns if the site upgrades.
    foreach (qpp_plan_locked_keys(qpp_plan_gated_options()) as $locked) {
        $qpp[$locked] = '';
    }

    /*
     * One set price is free. A comma separated list of prices is not, so below
     * Gold a stored list is trimmed to its first price rather than the whole
     * feature being switched off. The form still sells something at a price,
     * which is the point of the free tier, and upgrading restores the rest
     * because nothing here is written back.
     */
    if (!empty($qpp['fixedamount'])
        && !empty($qpp['inputamount'])
        && false !== strpos($qpp['inputamount'], ',')
        && in_array('combobox', qpp_plan_locked_keys(qpp_plan_gated_options()), true)) {
        $parts = explode(',', $qpp['inputamount']);
        $qpp['inputamount'] = trim($parts[0]);
    }

    return $qpp;
}

function qpp_get_stored_send($id) {
    $send = get_option('qpp_send'.$id);
    if(!is_array($send)) $send = array();
    $default = array(
        'waiting'   => 'Waiting for PayPal...',
        'cancelurl' => '',
        'thanksurl' => '',
        'target'    => 'current',
        'use_lc'    => false,
        'donate'    => false,
        'createuser'=> false,
        // Subscriber unless the site owner chooses otherwise. A form on the open
        // internet creating anything more capable is a decision, not a default.
        'createuserrole'=> 'subscriber',
        // After payment, not after submission. An account created when the form
        // is submitted is an account for somebody who never paid.
        'createuserwhen'=> 'afterpayment',
        'enable'    => false,
        'lc'        => ''
    );
    $send = array_merge($default, $send);

    // Same reasoning as qpp_get_stored_options(): gate at the single read path so
    // no consumer can act on a setting the plan does not include.
    foreach (qpp_plan_locked_keys(qpp_plan_gated_send()) as $locked) {
        $send[$locked] = '';
    }

    return $send;
}

function qpp_get_stored_style($id) {
    $style = get_option('qpp_style'.$id);
    if(!is_array($style)) $style = array();
    $default = array(
        /*
         * Defaults refreshed for 6.0. These are defaults only: qpp_get_stored_style()
         * merges over whatever is saved, so a form whose styling has been set keeps
         * every choice its owner made. Only new installs and untouched forms change.
         *
         * The old defaults were a 280px fixed box with a dark slab button and a
         * bright green outline on required fields, which looked like a 2012 form
         * dropped onto a 2025 theme.
         *
         * inherit for the font family, so the form takes the theme's typeface
         * rather than forcing Arial onto every site. Colours are the QPP palette,
         * see marketing/qpp-brand.md.
         */
        'font' => 'plugin',
        'font-family' => 'inherit',
        'font-size' => '1em',
        'font-colour' => '#011C4C',
        'header-type' => 'h2',
        'header-size' => '1.5em',
        'header-colour' => '#011C4C',
        'text-font-family' => 'inherit',
        'text-font-size' => '1em',
        'text-font-colour' => '#011C4C',
        // A card, not a column and not the full content width. 280px was cramped
        // enough to wrap the terms link; 100% stretches a five field form across
        // a wide theme. max-width:100% is always applied, so this stays responsive.
        'width' => 460,
        'form-border' => '1px solid #dcdce4',
        'widthtype' => 'pixel',
        'border' => 'rounded',
        'input-border' => '1px solid #c4c7d0',
        // Was #00C618, a fluorescent green outline on every required field.
        'required-border' => '1px solid #c4c7d0',
        'error-colour' => '#b3261e',
        'bordercolour' => '#dcdce4',
        'background' => 'white',
        'backgroundhex' => '#FFF',
        'corners' => 'corner',
        'line_margin' => 'margin: 4px 0 14px 0;padding: 11px 13px;',
        'para_margin' => 'margin: 18px 0 4px 0;padding: 0',
        'submit-colour' => '#FFF',
        'submit-background' => '#0E97EB',
        'submit-hover-background' => '#045ABA',
        'submit-button' => '',
        'submit-border' => '1px solid #415063',
        'submitwidth' => 'submitpercent',
        'submitwidthset' => '',
        'submitposition' => 'submitleft',
        'coupon-colour' => '#FFF',
        'coupon-background' => '#1f8416',
        'slider-thickness' => '2',
        'slider-background' => '#CCC',
        'slider-revealed' => '#00ff00',
        'handle-background' => 'white',
        'handle-border' => '#CCC',
        'handle-corners' => 50,
        'handle-colours' => '#FFF',
        'output-size' => '1.2em',
        'output-colour' => '#465069',
        'styles' => 'plugin',
        'use_custom' => '',
        'custom' => "#qpp-style {\r\n\r\n}",
        'header-type' => 'h2',
        'backgroundimage' => '',
        'labeltype' => 'hiding'
    );
    $style = array_merge($default, $style);
    return $style;
}

function qpp_get_stored_error ($id) {
    $error = get_option('qpp_error'.$id);
    if(!is_array($error)) $error = array();
    $default = array(
        'errortitle' => 'There is a problem with this payment',
        'errorblurb' => 'Please check the highlighted fields and try again.'
    );
    $error = array_merge($default, $error);

    /*
     * A saved blank is a value, not a missing key, so array_merge leaves it
     * alone. Once the validation screen had been saved with the fields empty the
     * form rejected a payment with red boxes and no explanation at all, on the
     * page and over ajax alike. Falling back on an empty value means a refused
     * payment always says why. Reset deletes the option, which is the way back
     * to the shipped wording.
     */
    foreach ($default as $key => $value) {
        if ('' === trim((string) $error[$key])) {
            $error[$key] = $value;
        }
    }

    return $error;
}

function qpp_get_stored_ipn () {
    $ipn = get_option('qpp_ipn');
    if(!is_array($ipn)) $ipn = array();
    $default = array(
        'ipn' => '',
        'title' => 'Payment',
        'paid' => 'Complete',
        'listener' => '',
        // Saved by the IPN screen but was never defaulted, so it warned in the
        // IPN listener itself as well as on the settings screen.
        'deleterecord' => '',
		'default' => site_url('/?qpp_ipn')
    );
    $ipn = array_merge($default, $ipn);
    return $ipn;
}


function qpp_get_stored_multiples($id) {
    $multiples = get_option('qpp_multiples'.$id);
    if(!is_array($multiples)) $multiples = array();
    $default = array(
        'use_quantity' => true,
        // Blank by default: an existing form must not gain a heading it never
        // had on upgrade. New forms get one from the settings screen.
        'title' => '',
        'shortcode' => '[product] at $[cost] each',
        'error' => 'No products selected',
    );

    for ($i=1; $i<=9; $i++) {
        $default['product'.$i] = false;
        $default['cost'.$i] = false;
    }
    $multiples = array_merge($default, $multiples);
    return $multiples;
}

function qpp_get_stored_coupon ($id) {
    $coupon = get_option('qpp_coupon'.$id);
    if(!is_array($coupon)) $coupon = array();
    $default = qpp_get_default_coupon();
    $coupon = array_merge($default, $coupon);
    return $coupon;
}

function qpp_get_default_coupon () {
    for ($i=1; $i<=10; $i++) {
        $coupon['couponget'] = 'Coupon Code:';
        $coupon['coupontype'.$i] = 'percent'.$i;
        $coupon['couponpercent'.$i] = '10';
        $coupon['couponfixed'.$i] = '5';
    }
    $coupon['couponget'] = 'Coupon Code:';
    $coupon['couponnumber'] = '10';
    $coupon['duplicate'] = '';
    $coupon['couponerror'] = 'Invalid Code';
    $coupon['couponexpired'] = 'Coupon Expired';
    return $coupon;
}

function qpp_get_stored_address($id) {
    $address = get_option('qpp_address'.$id);
    if(!is_array($address)) $address = array();
    $default = array(
        'firstname' => 'First Name',
        'lastname' => 'Last Name',
        'email' => 'Your Email Address',
        'address1' => 'Address Line 1',
        'address2' => 'Address Line 2',
        'city' => 'City',
        'state' => 'State',
        'zip' => 'ZIP Code',
        'country' => 'Country',
        'night_phone_b' => 'Phone Number',
        // The address screen saves all of these, but none were defaulted, so a
        // form whose address options had not been saved warned on both the
        // settings screen and the front end.
        'useaddress' => '',
        'rfirstname' => '',
        'rlastname' => '',
        'remail' => '',
        'raddress1' => '',
        'raddress2' => '',
        'rcity' => '',
        'rstate' => '',
        'rzip' => '',
        'rcountry' => '',
        'rnight_phone_b' => '',
        'permitted_country' => array(),
        'default_country' => ''
    );
    $address = array_merge($default, $address);
    return $address;
}

function qpp_get_stored_autoresponder($id) {
    $auto = get_option('qpp_autoresponder'.$id);
    if(!is_array($auto)) $auto = array(
        'enable' => '',
        'subject' => 'Thank you for your payment.',
        'whenconfirm' => 'aftersubmission',
        'message' => 'Once payment has been confirmed we will process your order and be in contact soon.',
        'paymentdetails' => 'checked',
        'fromname' => '',
        'fromemail' => ''
    );
    // The Auto Responder became a Silver feature in 6.0. Switching it off at the
    // read path stops the payer email being sent without touching the send code.
    if (in_array('enable', qpp_plan_locked_keys(qpp_plan_gated_autoresponder()), true)) {
        $auto['enable'] = '';
    }

    return $auto;
}

function qpp_get_stored_stripe () {
    $stripe = get_option('qpp_stripe');
    if(!is_array($stripe)) $stripe = array();
    $default = array(
        'enable' => '',
        'secret_key' => '',
        'webhook_secret' => '',
    );
    $stripe = array_merge($default, $stripe);
    // Gated at the read path like every other paid setting, so a downgrade stops
    // routing payments to Stripe without the keys being destroyed.
    foreach (qpp_plan_locked_keys(qpp_plan_gated_stripe()) as $locked) {
        $stripe[$locked] = '';
    }

    return $stripe;
}

function qpp_get_stored_mailinglist () {
    $list = get_option('qpp_mailinglist');
    if(!is_array($list)) $list = array();
    $default = array(
        'enable' => '',
        'mailchimpoptin' => 'Join our mailing list',
        'mailchimpkey' => '',
        'mailchimplistid' => '',
    );
    $list = array_merge($default, $list);
    return $list;
}

function qpp_get_stored_sandbox () {
    $payment = get_option('qpp_sandbox');
    if(!is_array($payment)) $payment = array();
    $default = array(
        'merchantid' => '',
        'api_username' => '',
        'api_password' => '',
        'api_key' => ''
    );
    $payment = array_merge($default, $payment);
    return $payment;
}

function qpp_get_stored_messages () {
    $payment = get_option('qpp_screen_messages');
    if(!is_array($payment)) $payment = array();
    $default = array(
        'validating' => 'Validating payment information...',
        'waiting' => 'Waiting for PayPal...',
        'errortitle' => 'There is a problem',
        'errorblurb' => 'Your payment could not be processed. Please try again',
        'technicalerrorblurb' => 'There seems to be a technical issue, contact an administrator!',
        'failuretitle' => 'Order Failure',
        'failureblurb' => 'The payment has not been completed.',
        'failureanchor' => 'Try Again',
        'pendingtitle' => 'Payment Pending',
        'pendingblurb' => 'The payment has been processed, but confirmation is currently pending. Refresh this page for real-time changes to this order.',
        'pendinganchor' => 'Refresh This Page',
        'confirmationtitle' => 'Order Confirmation',
        'confirmationblurb' => 'The transaction has been completed successfully. Keep this information for your records.',
        'confirmationreference' => 'Payment Reference:',
        'confirmationamount' => 'Amount Paid:',
        'confirmationanchor' => 'Continue Shopping',
    );
    $payment = array_merge($default, $payment);
    return $payment;
}
