<?php
/**
 * Asking for a review on wordpress.org, once, after the plugin has done its job.
 *
 * The plugin had 32 ratings in fourteen years. Ratings feed the directory's
 * search ranking, and the people most likely to leave one are those it has been
 * working for, so the ask waits for the site's tenth payment record.
 *
 * Every record counts, paid or not. Free and no-IPN sites often never mark
 * payments paid, so a paid-only count would rarely reach the threshold for the
 * very sites the ask is for. The wording says the payments were recorded, not
 * that they succeeded.
 *
 * The rules come from the wordpress.org plugin guidelines and are not optional:
 * the notice can be dismissed and the dismissal is stored, it asks once with at
 * most one "maybe later", nothing is offered in return for a review, and there is
 * no "do you like us" gate that sends only happy users to the review page.
 *
 * State is per site, in one option, so one administrator answering answers for
 * the site.
 *
 * Free of side effects at load, so the unit bootstrap can require it.
 *
 * @package Quick_Paypal_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * How many payment records before asking.
 *
 * @return int
 */
function qpp_review_prompt_threshold() {
	return 10;
}

/**
 * How long "Maybe later" puts the ask off for.
 *
 * @return int Seconds.
 */
function qpp_review_prompt_snooze() {
	return 30 * DAY_IN_SECONDS;
}

/**
 * Where the review link goes.
 *
 * @return string
 */
function qpp_review_prompt_url() {
	return 'https://wordpress.org/support/plugin/quick-paypal-payments/reviews/#new-post';
}

/**
 * The stored state, with defaults.
 *
 * waiting: not yet asked, or asked and not answered.
 * snoozed: "Maybe later" was chosen; asks again once after `until`.
 * done:    answered for good, by reviewing, declining or closing it.
 *
 * @param mixed $stored The raw option.
 *
 * @return array{state: string, until: int, snoozes: int, reached: bool}
 */
function qpp_review_prompt_state( $stored ) {
	$stored = is_array( $stored ) ? $stored : array();

	return array(
		'state'   => isset( $stored['state'] ) && in_array( $stored['state'], array( 'waiting', 'snoozed', 'done' ), true ) ? $stored['state'] : 'waiting',
		'until'   => isset( $stored['until'] ) ? (int) $stored['until'] : 0,
		'snoozes' => isset( $stored['snoozes'] ) ? (int) $stored['snoozes'] : 0,
		'reached' => ! empty( $stored['reached'] ),
	);
}

/**
 * Whether the ask is due, ignoring who is looking and where.
 *
 * @param array $state Normalised state.
 * @param int   $count Payment records on the site.
 * @param int   $now   Timestamp.
 *
 * @return bool
 */
function qpp_review_prompt_due( $state, $count, $now ) {
	if ( 'done' === $state['state'] ) {
		return false;
	}
	if ( ! $state['reached'] && $count < qpp_review_prompt_threshold() ) {
		return false;
	}

	return $now >= $state['until'];
}

/**
 * Whether "Maybe later" is still on offer. It is offered once.
 *
 * @param array $state Normalised state.
 *
 * @return bool
 */
function qpp_review_prompt_can_snooze( $state ) {
	return $state['snoozes'] < 1;
}

/**
 * The state after an answer.
 *
 * Reviewing, declining and closing the notice all end the ask for good. A
 * second "Maybe later" is treated as a decline, so the ask can never repeat
 * more than once whatever is posted.
 *
 * @param array  $state  Normalised state.
 * @param string $action review, later, never or dismiss.
 * @param int    $now    Timestamp.
 *
 * @return array
 */
function qpp_review_prompt_answer( $state, $action, $now ) {
	if ( 'later' === $action && qpp_review_prompt_can_snooze( $state ) ) {
		$state['state']   = 'snoozed';
		$state['until']   = $now + qpp_review_prompt_snooze();
		$state['snoozes'] = $state['snoozes'] + 1;

		return $state;
	}

	$state['state'] = 'done';

	return $state;
}

/**
 * Payment records across a set of stored payment lists, whatever their status.
 *
 * @param array $lists Payment lists, one per form.
 *
 * @return int
 */
function qpp_count_payment_rows( $lists ) {
	$count = 0;
	foreach ( (array) $lists as $rows ) {
		if ( ! is_array( $rows ) ) {
			continue;
		}
		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$count ++;
			}
		}
	}

	return $count;
}

/**
 * Payment records on this site, across every form.
 *
 * Read from the stored payment lists, so payments recorded before this existed
 * count too. Cached for an hour, and only asked for until the threshold has been
 * reached once.
 *
 * @return int
 */
function qpp_review_prompt_payment_count() {
	$cached = get_transient( 'qpp_review_prompt_count' );
	if ( false !== $cached ) {
		return (int) $cached;
	}

	$setup = get_option( 'qpp_setup' );
	$forms = array( '' );
	if ( is_array( $setup ) && ! empty( $setup['alternative'] ) ) {
		$forms = array_merge( $forms, array_filter( array_map( 'trim', explode( ',', $setup['alternative'] ) ) ) );
	}

	$lists = array();
	foreach ( array_unique( $forms ) as $form ) {
		$lists[] = get_option( 'qpp_messages' . $form );
	}

	$count = qpp_count_payment_rows( $lists );
	set_transient( 'qpp_review_prompt_count', $count, HOUR_IN_SECONDS );

	return $count;
}
