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
 */
spl_autoload_register( function ( $class_name ) {
	/**
	 * Autoloader based on tutorial by
	 * Tom McFarlin https://tommcfarlin.com/  Licensed    GPL-2.0+
	 */
	if ( false === strpos( $class_name, 'Quick_Paypal_Payments' ) ) {
		return;
	}
	$file_parts = explode( '\\', $class_name );
	$namespace  = '';
	for ( $i = count( $file_parts ) - 1; $i > 0; $i -- ) {
		$current = strtolower( $file_parts[ $i ] );
		$current = str_ireplace( '_', '-', $current );
		if ( count( $file_parts ) - 1 === $i ) {
			if ( strpos( strtolower( $file_parts[ count( $file_parts ) - 1 ] ), 'interface' ) ) {
				$interface_name = explode( '_', $file_parts[ count( $file_parts ) - 1 ] );
				$interface_name = $interface_name[0];
				$file_name      = "interface-$interface_name.php";
			} else {
				$file_name = "class-$current.php";
			}
		} else {
			$namespace = '/' . $current . $namespace;
		}
	}
	$filepath = trailingslashit( dirname( dirname( __FILE__ ) ) . $namespace );
	$filepath .= $file_name;
	if ( file_exists( $filepath ) ) {
		include_once( $filepath );
	} else {
		// changed to be translatable
		wp_die( sprintf( esc_html__( 'The system file attempting to be loaded at %1$s does not exist.', 'quick-paypal-payments' ), esc_html( $filepath ) ) );
	}
} );
