<?php
/**
 * The free lifetime Gold offer to sites that had the plugin before 6.0.
 *
 * The offer made good on features 6.0 moved to paid plans, and was announced in
 * the plugin admin from 2022. It closes at the end of 3 November 2026, with 30
 * days' notice given in 6.0.3. Licences already claimed are kept for good; only
 * the unclaimed offer ends.
 *
 * Date driven, so no further release is needed to close it. Sites that never
 * update keep showing the old notice, so the claim endpoint on fullworks.net is
 * what actually refuses a late claim.
 *
 * Free of side effects at load, so the unit bootstrap can require it.
 *
 * @package Quick_Paypal_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The last moment a claim is accepted, as a Unix timestamp.
 *
 * The end of 3 November 2026 anywhere on Earth (UTC-12), so no site, whatever
 * its time zone, loses any of the last day.
 *
 * Filterable so the closed offer can be tested before the date arrives.
 *
 * @return int
 */
function qpp_free_gold_offer_deadline() {
	return (int) apply_filters( 'qpp_free_gold_offer_deadline', 1793793599 ); // 2026-11-04 11:59:59 UTC.
}

/**
 * Whether the unclaimed offer can still be claimed.
 *
 * @param int|null $now Timestamp to test, for tests. Defaults to now.
 *
 * @return bool
 */
function qpp_free_gold_offer_open( $now = null ) {
	if ( null === $now ) {
		$now = time();
	}

	return (int) $now <= qpp_free_gold_offer_deadline();
}
