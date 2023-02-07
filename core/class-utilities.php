<?php
/**
 * @copyright (c) 2019.
 * @author            Alan Fuller (support@fullworks)
 * @licence           GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link                  https://fullworks.net
 *
 * This file is part of Fullworks Security.
 *
 *     Fullworks Security is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     Fullworks Security is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with   Fullworks Security.  https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 *
 */


namespace Quick_Paypal_Payments\Core;

/**
 * used for shared data
 */
class Utilities {

	/**
	 * @var
	 */
	protected static $instance;


	private $settings_page_tabs;

	const CURR_SYMB_BEFORE = array(
		'USD' => '&#x24;',
		'CDN' => '&#x24;',
		'EUR' => '&euro;',
		'GBP' => '&pound;',
		'JPY' => '&yen;',
		'AUD' => '&#x24;',
		'BRL' => 'R&#x24;',
		'HKD' => '&#x24;',
		'ILS' => '&#x20aa;',
		'MXN' => '&#x24;',
		'NZD' => '&#x24;',
		'PHP' => '&#8369;',
		'SGD' => '&#x24;',
		'TWD' => 'NT&#x24;',
		'TRY' => '&pound;'
	);
	const CURR_SYMB_AFTER = array(
		'CZK' => 'K&#269;',
		'DKK' => 'Kr',
		'HUF' => 'Ft',
		'MYR' => 'RM',
		'NOK' => 'kr',
		'PLN' => 'z&#322',
		'RUB' => '&#1056;&#1091;&#1073;',
		'SEK' => 'kr',
		'CHF' => 'CHF',
		'THB' => '&#3647;'
	);

	/**
	 * Utilities constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return Utilities
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function register_settings_page_tab( $title, $page, $href, $position ) {
		$this->settings_page_tabs[ $page ][ $position ] = array( 'title' => $title, 'href' => $href );
	}

	public function upgrade_options() {
		// @TODO  move legacy to new options
	}

	public function display_tabs() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
		$split     = explode( "-", sanitize_text_field($_GET['page']) );
		$page_type = $split[ count( $split ) - 1 ];
		$tabs      = $this->get_settings_page_tabs( $page_type );
		?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $key => $tab ) {
				$active = '';
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
				if ( preg_match( '#' . $_GET['page'] . '$#', $tab['href'] ) ) {
					$active = ' nav-tab-active';
				}
				echo '<a href="' . esc_attr($tab['href']) . '" class="nav-tab' . esc_attr($active) . '">' . esc_attr( $tab['title'] ) . '</a>';
			}
			?>
		</h2>
		<?php
	}

	public function get_settings_page_tabs( $page ) {
		$tabs = $this->settings_page_tabs[ $page ];
		ksort( $tabs );

		return $tabs;
	}


	public function get_default_meta( $method ) {
		return array_map(
			function ( $element ) {
				if ( isset( $element['default'] ) ) {
					return $element['default'];
				}

				return array_map(
					function ( $field ) {
						return $field['default'];
					},
					$element
				);
			},
			$this->{$method}()
		);
	}

	public function get_product_meta() {
		return array(
			'cost'  => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'forms' => array(
				'default'     => 'all',
				'sanitize_cb' => 'sanitize_array'
			),
		);

	}

	public function get_payment_columns() {
		$form_meta = $this->get_form_fields_meta();

		return array(
			'field1'        => array(
				'type'  => 'string',
				'label' => 'A meta key associated with a string meta value.',
			),
			'field2'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field3'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field4'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field5'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field6'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field8'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field17'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field18'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field21'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field22'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'firstname'     => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'lastname'      => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'email'         => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'address1'      => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'address2'      => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'city'          => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'state'         => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'zip'           => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'country'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'night_phone_b' => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			//	),

		);
	}

	public function get_form_fields_meta() {
		return array(
			'field1'  => array(
				'use'            => array(
					'default'     => 'checked',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'inputreference'
				'fixedreference' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'refselector'    => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'label'          => array(
					'default'     => __( 'Payment Reference', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'required'       => array(
					'default'     => 'checked',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field2'  => array(
				'use'        => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'stocklabel'
				'label'      => array(
					'default'     => __( 'Item Number', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'fixedstock' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'required'   => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field3'  => array(
				'use'              => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'quantitylabel'
				'label'            => array(
					'default'     => __( 'Quantity', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'quantitymax'      => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'quantitymaxblurb' => array(
					'default'     => __( 'maximum of 99', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field4'  => array(
				'use'           => array(
					'default'     => 'checked',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'inputamount'
				'label'         => array(
					'default'     => __( 'Amount to pay', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'allow_amount'  => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'fixedamount'   => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'selector'      => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'inline_amount' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'combobox'      => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'comboboxword'  => array(
					'default'     => __( 'Other', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'comboboxlabel' => array(
					'default'     => __( 'Enter Amount', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'required'      => array(
					'default'     => 'checked',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field5'  => array(
				'use'            => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'optionlabel'
				'label'          => array(
					'default'     => __( 'Options', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'optionvalues'   => array(
					'default'     => __( 'Large,Medium,Small', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'optionselector' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'inline_options' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field6'  => array(
				'use'            => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'postageblurb'
				'caption'        => array(
					'default'     => __( 'Handling charge will be added before payment', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'postagepercent' => array(
					'default'     => 5,
					'sanitize_cb' => 'sanitize_text_field'
				),
				'postagefixed'   => array(
					'default'     => 5,
					'sanitize_cb' => 'sanitize_text_field'
				),
				/** field7
				 * no field 7
				 */
			),
			'field8'  => array(
				'use'     => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'mathscaption'
				'caption' => array(
					'default'     => __( 'Spambot blocker question', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field9'  => array(
				'use'           => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'couponblurb'
				'label'         => array(
					'default'     => __( 'Enter coupon code', 'quick-paypal-manager' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'couponbutton'  => array(
					'default'     => __( 'Apply Coupon', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'couponref'     => array(
					'default'     => __( 'Coupon Applied', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'couponerror'   => array(
					'default'     => __( 'Invalid Code', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'couponexpired' => array(
					'default'     => __( 'Coupon No Longer Available', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'couponget'     => array(
					'default'     => __( 'Coupon Code:', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field10' => array(
				'use'       => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'termsblurb
				'caption'   => array(
					'default'     => __( 'I agree to the Terms and Conditions', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'termsurl'  => array(
					'default'     => get_home_url(),
					'sanitize_cb' => 'esc_url'
				),
				'termspage' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field11' => array(
				'use'     => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'extrablurb'
				'caption' => array(
					'default'     => __( 'Make sure you complete the next field', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field12' => array(
				'use'               => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'recurringblurb'
				'caption'           => array(
					'default'     => __( 'Subscription details:', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'recurringhowmany'  => array(
					'default'     => 52,
					'sanitize_cb' => 'sanitize_text_field'
				),
				'every'             => array(
					'default'     => __( 'payments every ', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'recurring'         => array(
					'default'     => 'M',
					'sanitize_cb' => 'sanitize_text_field'
				),
				'Dperiod'           => array(
					'default'     => __( 'day', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'Wperiod'           => array(
					'default'     => __( 'week', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'Mperiod'           => array(
					'default'     => __( 'month', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'Yperiod'           => array(
					'default'     => __( 'year', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'variablerecurring' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field13' => array(
				'use'     => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'addressblurb'
				'caption' => array(
					'default'     => __( 'Enter your details below', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field14' => array(
				'use'     => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'totalsblurb'
				'caption' => array(
					'default'     => __( 'Total:', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field15' => array(
				'use'     => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'sliderlabel'
				'label'   => array(
					'default'     => __( 'Amount to pay', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'min'     => array(
					'default'     => 0,
					'sanitize_cb' => 'sanitize_text_field'
				),
				'max'     => array(
					'default'     => 100,
					'sanitize_cb' => 'sanitize_text_field'
				),
				'initial' => array(
					'default'     => 50,
					'sanitize_cb' => 'sanitize_text_field'
				),
				'step'    => array(
					'default'     => 10,
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field16' => array(
				'use'      => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'emailblurb'
				'label'    => array(
					'default'     => __( 'Your email address', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'required' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field17' => array(
				'use'      => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'messagelabel'
				'label'    => array(
					'default'     => __( 'Message', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'required' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field18' => array(
				'use'      => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'datepickerlabel'
				'label'    => array(
					'default'     => __( 'Select date', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_date'
				),
				'required' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field19' => array(
				'use'                  => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'product'
				'label'                => array(
					'default'     => sprintf(
						esc_html__(
						/* translators: %1$s* = [product] %$2 = [cost] literals as shortcodes */
							'%1$s at $%2$s each',
							'quick-paypal-payments'
						),
						'[product]',
						'[cost]'
					),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'use_product_quantity' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				/** field20
				 * no field 20
				 */
			),
			'field21' => array(
				'use'      => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'cflabel'
				'label'    => array(
					'default'     => __( 'Codice Fiscale', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
				'required' => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
			'field22' => array(
				'use'   => array(
					'default'     => '',
					'sanitize_cb' => 'sanitize_text_field'
				),
				// 'consentlabel'
				'label' => array(
					'default'     => __( 'I consent to my data being retained by the site owner after payment has been processed.', 'quick-paypal-payments' ),
					'sanitize_cb' => 'sanitize_text_field'
				),
			),
		);
	}

	public function get_payment_meta() {
		return array(
			// 'fields'        => array(
			'field1'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field2'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field3'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field4'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field5'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field6'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field8'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field17'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field18'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field21'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'field22'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			//	),
			//	'personal_info' => array(
			'firstname'     => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'lastname'      => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'email'         => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'address1'      => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'address2'      => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'city'          => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'state'         => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'zip'           => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'country'       => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'night_phone_b' => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			// Paypal Data
			'payer_email'   => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'txn_id'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			// general
			'form'          => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'status'        => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'payment_id'    => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			'total'         => array(
				'type'         => 'string',
				'description'  => 'A meta key associated with a string meta value.',
				'single'       => true,
				'show_in_rest' => true,
			),
			//	),

		);
	}

	public function get_coupon_meta() {
		return array(
			'discount'       => array(
				'default'     => '10',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'discounttype'   => array(
				'default'     => '%',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'redemptions'    => array(
				'default'     => 0,
				'sanitize_cb' => 'sanitize_text_field'
			),
			'maxredemptions' => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'forms'          => array(
				'default'     => 'all',
				'sanitize_cb' => 'sanitize_array'
			),
		);

	}

	public function get_form_meta() {
		$user = wp_get_current_user();

		return array(
			/** generic */
			'sort'                    => array(
				// no field 7 or 20
				'default'     => 'field1,field4,field16,field10,field17,field2,field3,field5,field6,field9,field12,field13,field14,field11,field8,field15,field18,field19,field21,field22',
				'sanitize_cb' => 'sanitize_text_field'
			),
			/** form settings */
			'currency'                => array(
				'default'     => 'USD',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'title'                   => array(
				'default'     => __( 'Payment Form', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'blurb'                   => array(
				'default'     => __( 'Enter the payment details and submit', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'shortcodereference'      => array(
				'default'     => __( 'Payment for: ', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'shortcodeamount'         => array(
				'default'     => __( 'Amount: ', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'submitcaption'           => array(
				'default'     => __( 'Make Payment', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'use_reset'               => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'resetcaption'            => array(
				'default'     => __( 'Reset Form', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'errortitle'              => array(
				'default'     => __( 'Oops, got a problem here', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'errorblurb'              => array(
				'default'     => __( 'Please check the payment details', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),


			/** Paypal Image  */
			'paypal-location'         => array(
				'default'     => 'imagebelow',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'paypal-url'              => array(
				'default'     => 'imagebelow',
				'sanitize_cb' => 'esc_url'
			),

			/** Auto responder */
			'enable'                  => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'whenconfirm'             => array(
				'default'     => 'aftersubmission',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'fromname'                => array(
				'default'     => get_bloginfo( 'name' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			// @TODO think about pre populate
			'fromemail'               => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_email'
			),
			'subject'                 => array(
				'default'     => __( 'Thank you for your payment.', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'message'                 => array(
				'default'     => __( 'Once payment has been confirmed we will process your order and be in contact soon.', 'quick-paypal-payments' ),
				'sanitize_cb' => 'wp_kses_post'
			),
			'paymentdetails'          => array(
				'default'     => 'checked',
				'sanitize_cb' => 'sanitize_text_field'
			),

			/** personal Information */
			'firstname'               => array(
				'default'     => __( 'First Name', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'rfirstname'              => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'lastname'                => array(
				'default'     => __( 'Last Name', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'rlastname'               => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'email'                   => array(
				'default'     => __( 'Your Email Address', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_email'
			),
			'remail'                  => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'address1'                => array(
				'default'     => __( 'Address Line 1', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'raddress1'               => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'address2'                => array(
				'default'     => __( 'Address Line 2', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'raddress2'               => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'city'                    => array(
				'default'     => __( 'City', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'rcity'                   => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'state'                   => array(
				'default'     => __( 'State', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'rstate'                  => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'zip'                     => array(
				'default'     => __( 'Zip Code', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'rzip'                    => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'country'                 => array(
				'default'     => __( 'Country', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'rcountry'                => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'night_phone_b'           => array(
				'default'     => __( 'Phone Number', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'rnight_phone_b'          => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			/** styles */
			'widthtype'               => array(
				'default'     => 'pixel',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'width'                   => array(
				'default'     => '280',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'border'                  => array(
				'default'     => 'plain',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'form-border'             => array(
				'default'     => '1px solid #415063',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'background'              => array(
				'default'     => 'white',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'backgroundhex'           => array(
				'default'     => '#ffffff',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'backgroundimage'         => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'font'                    => array(
				'default'     => 'plugin',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'header-type'             => array(
				'default'     => 'h2',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'header-size'             => array(
				'default'     => '1.6em',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'header-colour'           => array(
				'default'     => '#465069',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'labeltype'               => array(
				'default'     => 'hiding',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'font-family'             => array(
				'default'     => 'arial, sans-serif',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'font-size'               => array(
				'default'     => '1em',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'font-colour'             => array(
				'default'     => '#343838',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'input-border'            => array(
				'default'     => '1px solid #415063',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'required-border'         => array(
				'default'     => '1px solid #00C618',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'error-colour'            => array(
				'default'     => '#ff0000',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'corners'                 => array(
				'default'     => 'corner',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'line_margin'             => array(
				'default'     => 'margin: 2px 0 3px 0;padding: 6px',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'text-font-family'        => array(
				'default'     => 'arial, sans-serif',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'text-font-size'          => array(
				'default'     => '1em',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'text-font-colour'        => array(
				'default'     => '#465069',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'submit-colour'           => array(
				'default'     => '#ffffff',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'submit-background'       => array(
				'default'     => '#343838',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'submit-hover-background' => array(
				'default'     => '#888888',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'submit-border'           => array(
				'default'     => '1px solid #415063',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'submitwidth'             => array(
				'default'     => 'submitpercent',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'submitwidthset'          => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'submitposition'          => array(
				'default'     => 'submitleft',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'submit-button'           => array(
				'default'     => '',
				'sanitize_cb' => 'esc_url'
			),
			'use_custom'              => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'custom'                  => array(
				'default'     => '#qpp-style {

}',
				'sanitize_cb' => 'wp_kses_post'
			),
			/** mailchimp */
			'mailchimp-enable'        => array(  // used to be enable
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'mailchimpoptin'          => array(
				'default'     => __( 'Join our mailing list', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'mailchimpkey'            => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'mailchimplistid'         => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),

			/** create new user */
			'createuser'              => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),

			/** Paypal Settings */
			'waiting'                 => array(
				'default'     => __( 'Waiting for PayPal...', 'quick-paypal-payments' ),
				'sanitize_cb' => 'sanitize_text_field'
			),
			'use_lc'                  => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'lc'                      => array(
				'default'     => 'US',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'cancelurl'               => array(
				'default'     => '',
				'sanitize_cb' => 'esc_url'
			),
			'thanksurl'               => array(
				'default'     => '',
				'sanitize_cb' => 'esc_url'
			),
			'confirmmesssage'         => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'confirmemail'            => array(
				'default'     => $user->user_email,
				'sanitize_cb' => 'sanitize_email'
			),
			'donate'                  => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'combine'                 => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'paypal-alternate-email'  => array( // used to be email
				'default'     => '',
				'sanitize_cb' => 'sanitize_email'
			),
			'target'                  => array(
				'default'     => 'current',
				'sanitize_cb' => 'sanitize_email'
			),
			'google_onclick'          => array(
				'default'     => '',
				'sanitize_cb' => 'sanitize_email'
			),

		);


	}

	public function get_form_input_fields() {
		return array(
			'field1'  => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field2'  => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field3'  => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field4'  => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field5'  => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field6'  => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field8'  => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field9'  => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field10' => array(
				'type'        => 'checkbox',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field11' => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field12' => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field13' => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field14' => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field15' => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field16' => array(
				'type'        => 'email',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field17' => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field18' => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field19' => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field21' => array(
				'type'        => 'text',
				'sanitize_cb' => 'sanitize_text_field'
			),
			'field22' => array(
				'type'        => 'checkbox',
				'sanitize_cb' => 'sanitize_text_field'
			),
		);


	}

	public function get_paypal_currency_codes() {
		return array(
			'USD' => __( 'United States dollar', 'quick-paypal-payments' ),
			'GBP' => __( 'Pound sterling', 'quick-paypal-payments' ),
			'EUR' => __( 'Euro', 'quick-paypal-payments' ),
			'AUD' => __( 'Australian dollar', 'quick-paypal-payments' ),
			'BRL' => __( 'Brazilian real ', 'quick-paypal-payments' ),
			'CAD' => __( 'Canadian dollar', 'quick-paypal-payments' ),
			'CZK' => __( 'Czech koruna', 'quick-paypal-payments' ),
			'DKK' => __( 'Danish krone', 'quick-paypal-payments' ),
			'HKD' => __( 'Hong Kong dollar', 'quick-paypal-payments' ),
			'HUF' => __( 'Hungarian forint 1', 'quick-paypal-payments' ),
			'INR' => __( 'Indian rupee ', 'quick-paypal-payments' ),
			'ILS' => __( 'Israeli new shekel', 'quick-paypal-payments' ),
			'JPY' => __( 'Japanese yen ', 'quick-paypal-payments' ),
			'MYR' => __( 'Malaysian ringgit 4', 'quick-paypal-payments' ),
			'MXN' => __( 'Mexican peso', 'quick-paypal-payments' ),
			'TWD' => __( 'New Taiwan dollar 1', 'quick-paypal-payments' ),
			'NZD' => __( 'New Zealand dollar', 'quick-paypal-payments' ),
			'NOK' => __( 'Norwegian krone', 'quick-paypal-payments' ),
			'PHP' => __( 'Philippine peso', 'quick-paypal-payments' ),
			'PLN' => __( 'Polish złoty', 'quick-paypal-payments' ),
			'RUB' => __( 'Russian ruble', 'quick-paypal-payments' ),
			'SGD' => __( 'Singapore dollar', 'quick-paypal-payments' ),
			'SEK' => __( 'Swedish krona', 'quick-paypal-payments' ),
			'CHF' => __( 'Swiss franc', 'quick-paypal-payments' ),
			'THB' => __( 'Thai baht', 'quick-paypal-payments' ),
		);
	}

	public function get_paypal_locales() {
		return array(
			'AL' => array( 'region' => __( 'Albania', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'DZ' => array( 'region' => __( 'Algeria', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'AD' => array( 'region' => __( 'Andorra', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'AO' => array( 'region' => __( 'Angola', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'AI' => array( 'region' => __( 'Anguilla', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'AG' => array( 'region' => __( 'Antigua & Barbuda', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'AR' => array( 'region' => __( 'Argentina', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'AM' => array( 'region' => __( 'Armenia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'AW' => array( 'region' => __( 'Aruba', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'AU' => array( 'region' => __( 'Australia', 'quick-paypal-payments' ), 'lc' => 'en_AU', ),
			'AT' => array( 'region' => __( 'Austria', 'quick-paypal-payments' ), 'lc' => 'de_DE', ),
			'AZ' => array( 'region' => __( 'Azerbaijan', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BS' => array( 'region' => __( 'Bahamas', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BH' => array( 'region' => __( 'Bahrain', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'BB' => array( 'region' => __( 'Barbados', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BY' => array( 'region' => __( 'Belarus', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BE' => array( 'region' => __( 'Belgium', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BZ' => array( 'region' => __( 'Belize', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'BJ' => array( 'region' => __( 'Benin', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'BM' => array( 'region' => __( 'Bermuda', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BT' => array( 'region' => __( 'Bhutan', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BO' => array( 'region' => __( 'Bolivia', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'BA' => array( 'region' => __( 'Bosnia & Herzegovina', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BW' => array( 'region' => __( 'Botswana', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BR' => array( 'region' => __( 'Brazil', 'quick-paypal-payments' ), 'lc' => 'pt_BR', ),
			'VG' => array( 'region' => __( 'British Virgin Islands', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BN' => array( 'region' => __( 'Brunei', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BG' => array( 'region' => __( 'Bulgaria', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'BF' => array( 'region' => __( 'Burkina Faso', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'BI' => array( 'region' => __( 'Burundi', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'KH' => array( 'region' => __( 'Cambodia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'CM' => array( 'region' => __( 'Cameroon', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'CA' => array( 'region' => __( 'Canada', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'CV' => array( 'region' => __( 'Cape Verde', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'KY' => array( 'region' => __( 'Cayman Islands', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'TD' => array( 'region' => __( 'Chad', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'CL' => array( 'region' => __( 'Chile', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'CN' => array( 'region' => __( 'China', 'quick-paypal-payments' ), 'lc' => 'zh_CN', ),
			'C2' => array( 'region' => __( 'China Worldwide', 'quick-paypal-payments' ), 'lc' => 'zh_XC', ),
			'CO' => array( 'region' => __( 'Colombia', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'KM' => array( 'region' => __( 'Comoros', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'CG' => array( 'region' => __( 'Congo - Brazzaville', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'CD' => array( 'region' => __( 'Congo - Kinshasa', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'CK' => array( 'region' => __( 'Cook Islands', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'CR' => array( 'region' => __( 'Costa Rica', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'CI' => array( 'region' => __( 'Côte D’Ivoire', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'HR' => array( 'region' => __( 'Croatia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'CY' => array( 'region' => __( 'Cyprus', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'CZ' => array( 'region' => __( 'Czech Republic', 'quick-paypal-payments' ), 'lc' => 'cs_CZ', ),
			'DK' => array( 'region' => __( 'Denmark', 'quick-paypal-payments' ), 'lc' => 'da_DK', ),
			'DJ' => array( 'region' => __( 'Djibouti', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'DM' => array( 'region' => __( 'Dominica', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'DO' => array( 'region' => __( 'Dominican Republic', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'EC' => array( 'region' => __( 'Ecuador', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'EG' => array( 'region' => __( 'Egypt', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'SV' => array( 'region' => __( 'El Salvador', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'ER' => array( 'region' => __( 'Eritrea', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'EE' => array( 'region' => __( 'Estonia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'ET' => array( 'region' => __( 'Ethiopia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'FK' => array( 'region' => __( 'Falkland Islands', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'FO' => array( 'region' => __( 'Faroe Islands', 'quick-paypal-payments' ), 'lc' => 'da_DK', ),
			'FJ' => array( 'region' => __( 'Fiji', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'FI' => array( 'region' => __( 'Finland', 'quick-paypal-payments' ), 'lc' => 'fi_FI', ),
			'FR' => array( 'region' => __( 'France', 'quick-paypal-payments' ), 'lc' => 'fr_FR', ),
			'GF' => array( 'region' => __( 'French Guiana', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'PF' => array( 'region' => __( 'French Polynesia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'GA' => array( 'region' => __( 'Gabon', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'GM' => array( 'region' => __( 'Gambia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'GE' => array( 'region' => __( 'Georgia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'DE' => array( 'region' => __( 'Germany', 'quick-paypal-payments' ), 'lc' => 'de_DE', ),
			'GI' => array( 'region' => __( 'Gibraltar', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'GR' => array( 'region' => __( 'Greece', 'quick-paypal-payments' ), 'lc' => 'el_GR', ),
			'GL' => array( 'region' => __( 'Greenland', 'quick-paypal-payments' ), 'lc' => 'da_DK', ),
			'GD' => array( 'region' => __( 'Grenada', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'GP' => array( 'region' => __( 'Guadeloupe', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'GT' => array( 'region' => __( 'Guatemala', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'GN' => array( 'region' => __( 'Guinea', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'GW' => array( 'region' => __( 'Guinea-Bissau', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'GY' => array( 'region' => __( 'Guyana', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'HN' => array( 'region' => __( 'Honduras', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'HK' => array( 'region' => __( 'Hong Kong Sar China', 'quick-paypal-payments' ), 'lc' => 'en_GB', ),
			'HU' => array( 'region' => __( 'Hungary', 'quick-paypal-payments' ), 'lc' => 'hu_HU', ),
			'IS' => array( 'region' => __( 'Iceland', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'IN' => array( 'region' => __( 'India', 'quick-paypal-payments' ), 'lc' => 'en_IN', ),
			'ID' => array( 'region' => __( 'Indonesia', 'quick-paypal-payments' ), 'lc' => 'id_ID', ),
			'IE' => array( 'region' => __( 'Ireland', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'IL' => array( 'region' => __( 'Israel', 'quick-paypal-payments' ), 'lc' => 'he_IL', ),
			'IT' => array( 'region' => __( 'Italy', 'quick-paypal-payments' ), 'lc' => 'it_IT', ),
			'JM' => array( 'region' => __( 'Jamaica', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'JP' => array( 'region' => __( 'Japan', 'quick-paypal-payments' ), 'lc' => 'ja_JP', ),
			'JO' => array( 'region' => __( 'Jordan', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'KZ' => array( 'region' => __( 'Kazakhstan', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'KE' => array( 'region' => __( 'Kenya', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'KI' => array( 'region' => __( 'Kiribati', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'KW' => array( 'region' => __( 'Kuwait', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'KG' => array( 'region' => __( 'Kyrgyzstan', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'LA' => array( 'region' => __( 'Laos', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'LV' => array( 'region' => __( 'Latvia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'LS' => array( 'region' => __( 'Lesotho', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'LI' => array( 'region' => __( 'Liechtenstein', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'LT' => array( 'region' => __( 'Lithuania', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'LU' => array( 'region' => __( 'Luxembourg', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MK' => array( 'region' => __( 'Macedonia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MG' => array( 'region' => __( 'Madagascar', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MW' => array( 'region' => __( 'Malawi', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MY' => array( 'region' => __( 'Malaysia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MV' => array( 'region' => __( 'Maldives', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'ML' => array( 'region' => __( 'Mali', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'MT' => array( 'region' => __( 'Malta', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MH' => array( 'region' => __( 'Marshall Islands', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MQ' => array( 'region' => __( 'Martinique', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MR' => array( 'region' => __( 'Mauritania', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MU' => array( 'region' => __( 'Mauritius', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'YT' => array( 'region' => __( 'Mayotte', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MX' => array( 'region' => __( 'Mexico', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'FM' => array( 'region' => __( 'Micronesia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MD' => array( 'region' => __( 'Moldova', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MC' => array( 'region' => __( 'Monaco', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'MN' => array( 'region' => __( 'Mongolia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'ME' => array( 'region' => __( 'Montenegro', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MS' => array( 'region' => __( 'Montserrat', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'MA' => array( 'region' => __( 'Morocco', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'MZ' => array( 'region' => __( 'Mozambique', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'NA' => array( 'region' => __( 'Namibia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'NR' => array( 'region' => __( 'Nauru', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'NP' => array( 'region' => __( 'Nepal', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'NL' => array( 'region' => __( 'Netherlands', 'quick-paypal-payments' ), 'lc' => 'nl_NL', ),
			'NC' => array( 'region' => __( 'New Caledonia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'NZ' => array( 'region' => __( 'New Zealand', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'NI' => array( 'region' => __( 'Nicaragua', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'NE' => array( 'region' => __( 'Niger', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'NG' => array( 'region' => __( 'Nigeria', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'NU' => array( 'region' => __( 'Niue', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'NF' => array( 'region' => __( 'Norfolk Island', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'NO' => array( 'region' => __( 'Norway', 'quick-paypal-payments' ), 'lc' => 'no_NO', ),
			'OM' => array( 'region' => __( 'Oman', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'PW' => array( 'region' => __( 'Palau', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'PA' => array( 'region' => __( 'Panama', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'PG' => array( 'region' => __( 'Papua New Guinea', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'PY' => array( 'region' => __( 'Paraguay', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'PE' => array( 'region' => __( 'Peru', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'PH' => array( 'region' => __( 'Philippines', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'PN' => array( 'region' => __( 'Pitcairn Islands', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'PL' => array( 'region' => __( 'Poland', 'quick-paypal-payments' ), 'lc' => 'pl_PL', ),
			'PT' => array( 'region' => __( 'Portugal', 'quick-paypal-payments' ), 'lc' => 'pt_PT', ),
			'QA' => array( 'region' => __( 'Qatar', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'RE' => array( 'region' => __( 'Réunion', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'RO' => array( 'region' => __( 'Romania', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'RU' => array( 'region' => __( 'Russia', 'quick-paypal-payments' ), 'lc' => 'ru_RU', ),
			'RW' => array( 'region' => __( 'Rwanda', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'WS' => array( 'region' => __( 'Samoa', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SM' => array( 'region' => __( 'San Marino', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'ST' => array( 'region' => __( 'São Tomé & Príncipe', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SA' => array( 'region' => __( 'Saudi Arabia', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'SN' => array( 'region' => __( 'Senegal', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'RS' => array( 'region' => __( 'Serbia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SC' => array( 'region' => __( 'Seychelles', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'SL' => array( 'region' => __( 'Sierra Leone', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SG' => array( 'region' => __( 'Singapore', 'quick-paypal-payments' ), 'lc' => 'en_GB', ),
			'SK' => array( 'region' => __( 'Slovakia', 'quick-paypal-payments' ), 'lc' => 'sk_SK', ),
			'SI' => array( 'region' => __( 'Slovenia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SB' => array( 'region' => __( 'Solomon Islands', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SO' => array( 'region' => __( 'Somalia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'ZA' => array( 'region' => __( 'South Africa', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'KR' => array( 'region' => __( 'South Korea', 'quick-paypal-payments' ), 'lc' => 'ko_KR', ),
			'ES' => array( 'region' => __( 'Spain', 'quick-paypal-payments' ), 'lc' => 'es_ES', ),
			'LK' => array( 'region' => __( 'Sri Lanka', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SH' => array( 'region' => __( 'St. Helena', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'KN' => array( 'region' => __( 'St. Kitts & Nevis', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'LC' => array( 'region' => __( 'St. Lucia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'PM' => array( 'region' => __( 'St. Pierre & Miquelon', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'VC' => array( 'region' => __( 'St. Vincent & Grenadines', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SR' => array( 'region' => __( 'Suriname', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SJ' => array( 'region' => __( 'Svalbard & Jan Mayen', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SZ' => array( 'region' => __( 'Swaziland', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'SE' => array( 'region' => __( 'Sweden', 'quick-paypal-payments' ), 'lc' => 'sv_SE', ),
			'CH' => array( 'region' => __( 'Switzerland', 'quick-paypal-payments' ), 'lc' => 'de_DE', ),
			'TW' => array( 'region' => __( 'Taiwan', 'quick-paypal-payments' ), 'lc' => 'zh_TW', ),
			'TJ' => array( 'region' => __( 'Tajikistan', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'TZ' => array( 'region' => __( 'Tanzania', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'TH' => array( 'region' => __( 'Thailand', 'quick-paypal-payments' ), 'lc' => 'th_TH', ),
			'TG' => array( 'region' => __( 'Togo', 'quick-paypal-payments' ), 'lc' => 'fr_XC', ),
			'TO' => array( 'region' => __( 'Tonga', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'TT' => array( 'region' => __( 'Trinidad & Tobago', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'TN' => array( 'region' => __( 'Tunisia', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'TM' => array( 'region' => __( 'Turkmenistan', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'TC' => array( 'region' => __( 'Turks & Caicos Islands', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'TV' => array( 'region' => __( 'Tuvalu', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'UG' => array( 'region' => __( 'Uganda', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'UA' => array( 'region' => __( 'Ukraine', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'AE' => array( 'region' => __( 'United Arab Emirates', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'GB' => array( 'region' => __( 'United Kingdom', 'quick-paypal-payments' ), 'lc' => 'en_GB', ),
			'US' => array( 'region' => __( 'United States', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'UY' => array( 'region' => __( 'Uruguay', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'VU' => array( 'region' => __( 'Vanuatu', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'VA' => array( 'region' => __( 'Vatican City', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'VE' => array( 'region' => __( 'Venezuela', 'quick-paypal-payments' ), 'lc' => 'es_XC', ),
			'VN' => array( 'region' => __( 'Vietnam', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'WF' => array( 'region' => __( 'Wallis & Futuna', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'YE' => array( 'region' => __( 'Yemen', 'quick-paypal-payments' ), 'lc' => 'ar_EG', ),
			'ZM' => array( 'region' => __( 'Zambia', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
			'ZW' => array( 'region' => __( 'Zimbabwe', 'quick-paypal-payments' ), 'lc' => 'en_US', ),
		);
	}

	public function post_title_where( $where, $wp_query ) {
		global $wpdb;
		if ( $qpp_post_title = $wp_query->get( 'qpp_post_title' ) ) {
			return $where . " AND $wpdb->posts.post_title = '" . esc_sql( $qpp_post_title ) . "'";
		}

		return $where;
	}

	public function render_form_field_data( $id, $field_array, $settings ) {
		// @TODO condider refactoring into frontend somewhere
		$return = array(
			'name'        => $field_array['name'],
			'data'        => $field_array['data'],
			'placeholder' => 'placeholder=""',
			'label_class' => '',
			'label'       => ( isset( $field_array['data']['label'] ) ) ? $field_array['data']['label'] : $field_array['data']['caption'],
			'required'    => '',
		);

		if ( isset( $field_array['data']['required'] ) && 'checked' === $field_array['data']['required'] ) {
			$return['required'] = 'required';
		}
		switch ( $settings['labeltype'] ) {
			case 'hiding':
				if ( isset( $field_array['data']['label'] ) ) {
					$return['label_class'] = 'qpp_label_blurr';
					$return['placeholder'] = 'placeholder="' . $field_array['data']['label'] . '"';
				} else if ( isset( $field_array['data']['caption'] ) ) {
					$return['label_class'] = 'qpp_label_line';
				}
				break;
			case 'tiny':
				$return['label_class'] = 'qpp_label_tiny';
				break;
			case 'plain':
				$return['label_class'] = 'qpp_label_line';
				break;
		}

		return $return;
	}

	public function reference_type( $work ) {
		if ( $work['fixedreference'] == 'checked' ) {
			switch ( $work['refselector'] ) {
				case 'ignore':
					return 'text';
					break;
				default:
					$options = explode( ',', $work['label'] );
					if ( count( $options ) > 1 ) {
						return 'choice';
					} else {
						return 'text';
					}
					break;
			}
		} else {
			return 'input';
		}
	}

	public function format_amount( $currency, $amount ) {
		$curr = ( $currency == '' ? 'USD' : $currency );
		if ( in_array( $currency, array( 'HKD', 'JPY', 'MYR', 'TWD' ) ) ) {
			return intval( preg_replace( '/[^\.0-9]/', '', $amount ) );
		}
		$check = preg_replace( '/[^,0-9]/', '', $amount );
		$check = str_replace( ',', '.', $check );

		return number_format( $check, 2, '.', '' );
	}

	public function format_currency( $currency, $amount ) {
		$curr_symb = $this->get_currency_symbol( $currency );

		return sprintf( '%1$s%2$s%3$s',
			( $curr_symb->before ) ? $curr_symb->symb : '',
			$amount,
			( $curr_symb->before ) ? '' : $curr_symb->symb
		);
	}

	public function get_currency_symbol( $currency ) {
		$result           = array();
		$result['before'] = isset(self::CURR_SYMB_BEFORE[$currency]);
		if ( $result['before'] ) {
			$result['symb'] = self::CURR_SYMB_BEFORE[ $currency ];
		} else {
			$result['symb'] = self::CURR_SYMB_AFTER[ $currency ];
		}

		return (object) $result;
	}
}
