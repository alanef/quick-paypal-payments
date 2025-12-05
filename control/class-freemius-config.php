<?php

/**
 * @copyright (c) 2019.
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
namespace Quick_Paypal_Payments\Control;

class Freemius_Config {
    public function init() {
        global $quick_paypal_payments_fs;
        if ( !defined( 'QPP_DEMO' ) ) {
            define( 'QPP_DEMO', false );
        }
        if ( !isset( $quick_paypal_payments_fs ) ) {
            // Include Freemius SDK.
            require_once QUICK_PAYPAL_PAYMENTS_PLUGIN_DIR . 'vendor/freemius/wordpress-sdk/start.php';
            $quick_paypal_payments_fs = fs_dynamic_init( array(
                'id'             => '5623',
                'slug'           => 'quick-paypal-payments',
                'type'           => 'plugin',
                'public_key'     => 'pk_fe42c0234babc6d6acb8ca8948f97',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                    'days'               => 14,
                    'is_require_payment' => true,
                ),
                'navigation'     => 'tabs',
                'menu'           => array(
                    'slug'           => 'quick-paypal-payments',
                    'override_exact' => true,
                    'support'        => false,
                    'parent'         => array(
                        'slug' => 'options-general.php',
                    ),
                ),
                'anonymous_mode' => QPP_DEMO,
                'is_live'        => true,
            ) );
        }
        $quick_paypal_payments_fs->add_filter(
            'is_submenu_visible',
            array($this, '_fs_show_support_menu'),
            10,
            2
        );
        return $quick_paypal_payments_fs;
    }

    public function _fs_show_support_menu( $is_visible, $menu_id ) {
        /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
        global $quick_paypal_payments_fs;
        if ( 'support' === $menu_id ) {
            return $quick_paypal_payments_fs->is_free_plan();
        }
        return $is_visible;
    }

}
