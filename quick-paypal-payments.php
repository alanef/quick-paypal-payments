<?php
/**
 * @copyright (c) 2020.
 * @author            Alan Fuller (support@fullworks)
 * @licence           GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link                  https://fullworks.net
 *
 * This file is part of  a Fullworks plugin.
 *
 *   This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with  this plugin.  https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 *
 * Plugin Name: Quick Paypal Payments
 * Plugin URI: https://quick-plugins.com/quick-paypal-payments/
 * Description: Accept any amount or payment ID before submitting to paypal.
 * Version: 5.7.25
 * Requires at least: 5.0
 * Requires PHP: 5.6
 * Author: Fullworks
 * Author URI: https://fullworks.net/
 * Text-domain: quick-paypal-payments
 *
 * Original Author: Aerin
 */

namespace Quick_Paypal_Payments;

use \Quick_Paypal_Payments\Control\Plugin;
use \Quick_Paypal_Payments\Control\Freemius_Config;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( ! function_exists( 'Quick_Paypal_Payments\run_Quick_Paypal_Payments' ) ) {
	define( 'QUICK_PAYPAL_PAYMENTS_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'QUICK_PAYPAL_PAYMENTS_PLUGIN_FILE', plugin_basename( __FILE__ ) );
	define( 'QUICK_PAYPAL_PAYMENTS_VERSION', '5.7.25' );

// Include the autoloader so we can dynamically include the classes.
	require_once QUICK_PAYPAL_PAYMENTS_PLUGIN_DIR . 'control/autoloader.php';


	function run_Quick_Paypal_Payments() {
		$freemius = new Freemius_Config();
		$freemius = $freemius->init();
		// Signal that SDK was initiated.
		do_action( 'quick_paypal_payments_fs_loaded' );

		register_activation_hook( __FILE__, array( '\Quick_Paypal_Payments\Control\Activator', 'activate' ) );

		register_deactivation_hook( __FILE__, array( '\Quick_Paypal_Payments\Control\Deactivator', 'deactivate' ) );

		/**
		 * @var \Freemius $freemius freemius SDK.
		 */
		$freemius->add_action( 'after_uninstall', array( '\Quick_Paypal_Payments\Control\Uninstall', 'uninstall' ) );

		$plugin = new Plugin( 'quick-paypal-payments',
			QUICK_PAYPAL_PAYMENTS_VERSION,
			$freemius );
		$plugin->run();
	}

	run_Quick_Paypal_Payments();
} else {
	die( __( 'Cannot execute as the plugin already exists, if you have a free version installed deactivate that and try again', 'quick-paypal-payments' ) );
}

