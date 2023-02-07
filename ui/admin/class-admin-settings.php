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

namespace Quick_Paypal_Payments\UI\Admin;

class Admin_Settings extends Admin_Pages {
	protected $settings_page;
	// protected $settings_page_id = 'toplevel_page_quick-paypal-payments';  // top level
	protected $settings_page_id = 'settings_page_quick-paypal-payments-settings';
	protected $option_group = 'quick-paypal-payments';
	protected $settings_title;
	/**
	 * @param \Freemius $freemius Freemius SDK.
	 */
	protected $freemius;

	public function __construct( $plugin_name, $version, $freemius ) {
		$this->plugin_name    = $plugin_name;
		$this->version        = $version;
		$this->freemius       = $freemius;
		$this->settings_title = esc_html__( 'Quick_Paypal_Payments Settings', 'quick-paypal-payments' );
		// options set up
		if ( ! get_option( 'quick-paypal-payments-settings-1' ) ) {
			update_option( 'quick-paypal-payments-settings-1', $this->option_defaults( 'quick-paypal-payments-settings-1' ) );
		}
		if ( ! get_option( 'quick-paypal-payments-settings-2' ) ) {
			update_option( 'quick-paypal-payments-settings-2', $this->option_defaults( 'quick-paypal-payments-settings-2' ) );
		}
		parent::__construct();
	}

	public static function option_defaults( $option ) {
		switch ( $option ) {
			case 'quick-paypal-payments-settings-1':
				return array(
					// set defaults
					'checkbox' => 1,
					'text'     => 'Default text',
				);
			case 'quick-paypal-payments-settings-2':
				return array(
					'option1' => 50,
					'option2' => array(
						'number' => '15',
						'unit'   => 'MINUTE'
					)
				);
			default:
				return false;
		}
	}

	public function hooks() {
		add_action( 'admin_menu', array( $this, 'settings_setup' ) );
	}

	public function register_settings() {
		/* Register our setting. */
		register_setting(
			$this->option_group,                         /* Option Group */
			'quick-paypal-payments-settings-1',                   /* Option Name */
			array( $this, 'sanitize_settings_1' )          /* Sanitize Callback */
		);
		register_setting(
			$this->option_group,                         /* Option Group */
			'quick-paypal-payments-settings-2',                   /* Option Name */
			array( $this, 'sanitize_settings_2' )          /* Sanitize Callback */
		);

		/* Add settings menu page */
		$this->settings_page = add_submenu_page(
			'quick-paypal-payments',
			'Settings', /* Page Title */
			'Settings',                       /* Menu Title */
			'manage_options',                 /* Capability */
			'quick-paypal-payments',                         /* Page Slug */
			array( $this, 'settings_page' )          /* Settings Page Function Callback */
		);
		register_setting(
			$this->option_group,                         /* Option Group */
			"{$this->option_group}-reset",                   /* Option Name */
			array( $this, 'reset_sanitize' )          /* Sanitize Callback */
		);
	}

	public function delete_options() {
		update_option( 'quick-paypal-payments-settings-1', self::option_defaults( 'quick-paypal-payments-settings-1' ) );
		update_option( 'quick-paypal-payments-settings-2', self::option_defaults( 'quick-paypal-payments-settings-2' ) );

	}

	public function add_meta_boxes() {
		add_meta_box(
			'settings-1',                  /* Meta Box ID */
			esc_html__( 'Settings 1', 'quick-paypal-payments' ),               /* Title */
			array( $this, 'meta_box_1' ),  /* Function Callback */
			$this->settings_page_id,               /* Screen: Our Settings Page */
			'normal',                 /* Context */
			'default'                 /* Priority */
		);
		add_meta_box(
			'settings-2',                  /* Meta Box ID */
			esc_html__( 'Settings 2', 'quick-paypal-payments' ),               /* Title */
			array( $this, 'meta_box_2' ),  /* Function Callback */
			$this->settings_page_id,               /* Screen: Our Settings Page */
			'normal',                 /* Context */
			'default'                 /* Priority */
		);
	}

	public function meta_box_1() {
		?>
		<?php
		$options = get_option( 'quick-paypal-payments-settings-1' );
		?>
        <table class="form-table">
            <tbody>
            <tr valign="top" class="alternate">
                <th scope="row"><?php esc_html_e( 'Checkbox', 'quick-paypal-payments' ); ?></th>
                <td>
                    <label for="quick-paypal-payments-settings-1[checkbox]"><input type="checkbox"
                                                                         name="quick-paypal-payments-settings-1[checkbox]"
                                                                         id="quick-paypal-payments-settings-1[checkbox]"
                                                                         value="1"
							<?php checked( '1', $options['checkbox'] ); ?>>
						<?php esc_html_e( 'Checkbox description', 'quick-paypal-payments' ); ?></label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Text', 'quick-paypal-payments' ); ?></th>
                <td>
                    <input type="text"
                           class="regular-text"
                           name="quick-paypal-payments-settings-1[text]"
                           id="quick-paypal-payments-settings-1[text]"
                           value="<?php echo esc_attr( $options['text'] ) ?>">
                    <p>
                        <span class="description"><?php esc_html_e( 'Text Description', 'quick-paypal-payments' ); ?></span>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	public function sanitize_settings_1( $settings ) {
		if ( ! isset( $settings['checkbox'] ) ) {
			$settings['checkbox'] = 0;  // always set checkboxes of they dont exist
		}

		return $settings;
	}

	public function sanitize_settings_2( $settings ) {

		return $settings;
	}


	public function meta_box_2() {
		?>
		<?php
		$options = get_option( 'quick-paypal-payments-settings-2' );
		$units   = array(
			array(
				'MINUTE',
				esc_html__( 'Minutes', 'quick-paypal-payments' )
			),
			array(
				'HOUR',
				esc_html__( 'Hours', 'quick-paypal-payments' )
			),
			array(
				'DAY',
				esc_html__( 'Days', 'quick-paypal-payments' )
			),
		);
		?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Number', 'quick-paypal-payments' ); ?></th>
                <td>
                    <label for="quick-paypal-payments-settings-2[option1]"><input type="number"
                                                                        name="quick-paypal-payments-settings-2[option1]"
                                                                        id="quick-paypal-payments-settings-2[option1]"
                                                                        class="small-text"
                                                                        value="<?php echo esc_attr( $options['option1'] ); ?>"
                                                                        min="0">
						<?php esc_html_e( 'Integer', 'quick-paypal-payments' ); ?></label>
                    <p>
                        <span class="description"><?php esc_html_e( 'Number', 'quick-paypal-payments' ); ?></span>
                    </p>
                </td>
            </tr>
            <tr valign="top" class="alternate">
                <th scope="row"><?php esc_html_e( 'Number with units', 'quick-paypal-payments' ); ?></th>
                <td>
                    <input type="number"
                           name="quick-paypal-payments-settings-2[option2][number]"
                           id="quick-paypal-payments-settings-2[option2][number]"
                           class="small-text"
                           value="<?php echo esc_attr( $options['option2']['number'] ); ?>"
                           min="1"
                           max="60">
                    <select name="quick-paypal-payments-settings-2[option2][unit]"
                            id="quick-paypal-payments-settings-2[option2][unit]"
                            class="small-text">
						<?php foreach ( $units as $unit ) {
							?>
                            <option value="<?php echo esc_attr( $unit[0] ); ?>"
								<?php echo ( $options['option2']['unit'] == $unit[0] ) ? " selected" : ""; ?>>
                                <?php echo esc_html( $unit[1] ); ?></option>
						<?php } ?>
                    </select>
                    <p>
                        <span class="description"><?php esc_html_e( 'Description', 'quick-paypal-payments' ); ?></span>
                    </p>
                </td>
            </tr>


            </tbody>
        </table>
		<?php
	}
}

