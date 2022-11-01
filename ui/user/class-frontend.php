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

namespace Quick_Paypal_Payments\UI\User;

use Quick_Paypal_Payments\Control\User_Template_Loader;

class FrontEnd {

	private $plugin_name;
	private $version;
	/**
	 * @param \Freemius $freemius Object for freemius.
	 */
	private $freemius;

	public function __construct( $plugin_name, $version, $freemius ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->freemius    = $freemius;
	}

	public function hooks() {
		// @TODO check if style / js actually needed
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// @TODO check if shortcode needed
		add_action( 'wp', array( $this, 'add_shortcode' ) );
	}

	public function enqueue_styles() {
		/**
		 * @TODO decide if front end style sheet needed
		 */
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/frontend.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		/**
		 * @TODO decide if front end js required
		 */
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/frontend.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * @TODO are shortcode required
	 */
	public function add_shortcode() {
		add_shortcode( 'test', array( $this, 'build_shortcode' ) );
	}

	public function build_shortcode( $atts ) {

		$template_loader = new User_Template_Loader;
		$template_loader->set_template_data( array( 'message' => 'test shortcode message etc' ) );
		ob_start();
		$template_loader->get_template_part( 'test' );
		$html = ob_get_clean();

		return $html;
	}
}
