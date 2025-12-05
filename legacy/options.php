<?php

function qpp_get_stored_setup () {
    $qpp_setup = get_option('qpp_setup');
    if(!is_array($qpp_setup)) $qpp_setup = array();
    $default = array(
        'current'       => false,
        'alternative'   => false,
        'disable_error' => false,
        'sandbox'       => false,
        'encryption'    => false,
        'location'      => 'head',
        'image_url'     => false,
        'nostore'       => false
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
        'enable'    => false
    );
    $send = array_merge($default, $send);
    return $send;
}

function qpp_get_stored_style($id) {
    $style = get_option('qpp_style'.$id);
    if(!is_array($style)) $style = array();
    $default = array(
        'font' => 'plugin',
        'font-family' => 'arial, sans-serif',
        'font-size' => '1em',
        'font-colour' => '#465069',
        'header-type' => 'h2',
        'header-size' => '1.6em',
        'header-colour' => '#465069',
        'text-font-family' => 'arial, sans-serif',
        'text-font-size' => '1em',
        'text-font-colour' => '#465069',
        'width' => 280,
        'form-border' => '1px solid #415063',
        'widthtype' => 'pixel',
        'border' => 'plain',
        'input-border' => '1px solid #415063',
        'required-border' => '1px solid #00C618',
        'error-colour' => '#FF0000',
        'bordercolour' => '#415063',
        'background' => 'white',
        'backgroundhex' => '#FFF',
        'corners' => 'corner',
        'line_margin' => 'margin: 2px 0 3px 0;padding: 6px;',
        'para_margin' => 'margin: 20px 0 3px 0;padding: 0',
        'submit-colour' => '#FFF',
        'submit-background' => '#343838',
        'submit-hover-background' => '#888888',
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
        'errortitle' => 'Oops, got a problem here',
        'errorblurb' => 'Please check the payment details'
    );
    $error = array_merge($default, $error);
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
        'night_phone_b' => 'Phone Number'
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
    return $auto;
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
        'pendingblurb' => 'The payment has been processed, but confimration is currently pending. Refresh this page for real-time changes to this order.',
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
