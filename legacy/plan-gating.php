<?php

/**
 * Which settings each paid plan unlocks, and how a lower plan sees them.
 *
 * Kept in its own file with no side effects at load so the unit suite can require
 * it in isolation. It must not pull in options.php, because the unit tests mock
 * the qpp_get_stored_*() accessors and WP_Mock can only mock functions that are
 * still undefined.
 *
 * @package Quick_Paypal_Payments
 */
// Prevent direct access.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Form options that need a paid plan, keyed by the lowest plan that unlocks them.
 *
 * Lists the behaviour driving toggles and the settings that belong to them. The
 * dependents are inert once the toggle is off, so blanking them costs nothing, and
 * listing them means a save on a lower plan preserves rather than discards them.
 *
 * Deliberately excludes refselector, selector, optionselector, recurring,
 * postagetype, processtype, paypal-location and currency_seperator. The settings
 * screen expands those through ${$qpp[...]}, so an empty value would assign to
 * ${''}. The setup screen's 'location' used to be another, until the dead
 * setting that read it was removed.
 *
 * @return array
 */
function qpp_plan_gated_options() {
    return array(
        'silver'   => array(
            'usepostage',
            'postageblurb',
            'postagepercent',
            'postagefixed'
        ),
        'gold'     => array(
            'usecoupon',
            'couponblurb',
            'couponbutton',
            'couponref',
            'userecurring',
            'recurringblurb',
            'recurringhowmany',
            'every',
            'Dperiod',
            'Wperiod',
            'Mperiod',
            'Yperiod',
            'variablerecurring',
            'use_slider',
            'sliderlabel',
            'min',
            'max',
            'initial',
            'step',
            'fixedreference',
            // fixedamount is free: a form that cannot set a price is a donation
            // box, not a payment form. What stays paid is the powerful part,
            // the comma separated list of prices and the selector that renders
            // it. See qpp_get_stored_options(), which trims a stored list down
            // to its first price below Gold.
            'minamount',
            'allow_amount',
            'combobox',
            'comboboxword',
            'comboboxlabel',
            'inline_amount',
        ),
        'platinum' => array(
            'use_datepicker',
            'ruse_datepicker',
            'datepickerlabel',
            'use_multiples'
        ),
    );
}

/**
 * Send options that need a paid plan, keyed by the lowest plan that unlocks them.
 *
 * @return array
 */
function qpp_plan_gated_send() {
    return array(
        'silver' => array('combine', 'createuser'),
        'gold'   => array('donate'),
    );
}

/**
 * Auto Responder options that need a paid plan.
 *
 * @return array
 */
function qpp_plan_gated_autoresponder() {
    return array(
        'silver' => array('enable'),
    );
}

/**
 * Stripe options, which are Platinum.
 *
 * Deliberately above the free Gold licence offered to pre 6.0 installs. Gold is
 * the equivalent of the old free version, so putting a new gateway there would
 * hand it to the entire existing base for nothing.
 *
 * @return array
 */
function qpp_plan_gated_stripe() {
    return array(
        'platinum' => array('enable'),
    );
}

/**
 * Flattens a gated map down to the keys the current plan may not use.
 *
 * Fails open. If the Freemius global is somehow unavailable this returns nothing
 * locked, because wrongly stripping features from a paying customer is a worse
 * failure than briefly leaking one to a free install.
 *
 * @param array $gated Plan name => option keys.
 *
 * @return array
 */
function qpp_plan_locked_keys(  $gated  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    if ( !isset( $quick_paypal_payments_fs ) || !is_object( $quick_paypal_payments_fs ) ) {
        return array();
    }
    $locked = array();
    foreach ( $gated as $plan => $keys ) {
        $locked = array_merge( $locked, $keys );
    }
    return $locked;
}

/**
 * Stored values for settings the current plan may not edit.
 *
 * The settings screens rebuild their option from POST alone, so a setting with no
 * rendered input is absent from the post and would be dropped on the next save.
 * Seeding a save with these keeps a gated configuration intact, so it comes back
 * as it was if the site upgrades rather than having been silently erased.
 *
 * Reads the raw option deliberately, since the accessors blank these on read.
 *
 * @param string $option Option name to read, without the form id.
 * @param string $id     Form id.
 * @param array  $gated  Gated map to apply.
 *
 * @return array
 */
function qpp_plan_preserved_values(  $option, $id, $gated  ) {
    $locked = qpp_plan_locked_keys( $gated );
    if ( empty( $locked ) ) {
        return array();
    }
    $stored = get_option( $option . $id );
    if ( !is_array( $stored ) ) {
        return array();
    }
    return array_intersect_key( $stored, array_flip( $locked ) );
}
