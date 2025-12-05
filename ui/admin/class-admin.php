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

use Freemius;
class Admin {
    private $plugin_name;

    private $version;

    /**
     * @param Freemius $freemius Object for freemius.
     */
    private $freemius;

    public function __construct( $plugin_name, $version, $freemius ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->freemius = $freemius;
    }

    public function hooks() {
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
        add_action( 'admin_notices', array($this, 'admin_notice_freemius') );
        add_action( 'init', array($this, 'generate_freemius_licence') );
        update_option( 'qpp_legacy_free', true );
        // Add AJAX endpoint for refreshing nonces
        add_action( 'wp_ajax_qpp_refresh_nonce', array($this, 'refresh_nonce_callback') );
        add_action( 'wp_ajax_nopriv_qpp_refresh_nonce', array($this, 'refresh_nonce_callback') );
    }

    /**
     * AJAX callback to refresh the form submission nonce
     */
    public function refresh_nonce_callback() {
        if ( !wp_doing_ajax() ) {
            return;
        }
        // No nonce check here since we're actually getting a fresh nonce
        // Generate a fresh nonce
        $nonce = wp_create_nonce( 'qpp_payment_form' );
        // Return the new nonce
        wp_send_json_success( array(
            'nonce' => $nonce,
        ) );
        // Make sure to exit properly
        wp_die();
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/admin.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    public function qpp_dismiss_notice() {
        $user_id = get_current_user_id();
        if ( !wp_doing_ajax() ) {
            return;
        }
        if ( !current_user_can( 'install_plugins' ) ) {
            return;
        }
        $um = get_user_meta( $user_id, 'wfea_dismissed_notices', true );
        if ( !is_array( $um ) ) {
            $um = [];
        }
        // @TODO for neatness could apply a nonce but not a risk
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- just dismissing a notice
        $um[sanitize_text_field( $_POST['id'] )] = true;
        update_user_meta( $user_id, 'qpp_dismissed_notices', $um );
        wp_die();
    }

    public function admin_notice_freemius() {
        // Don't display notices to users that can't do anything about it.
        if ( !current_user_can( 'install_plugins' ) ) {
            return;
        }
        $user_id = get_current_user_id();
        $um = get_user_meta( $user_id, 'qpp_dismissed_notices', true );
        // Notices are only displayed on the dashboard, plugins, tools, and settings admin pages.
        $page = get_current_screen()->base;
        $display_on_pages = array(
            'dashboard',
            'plugins',
            'tools',
            'options-general'
        );
        $display = false;
        if ( preg_match( '#quick-paypal-payments#i', $page ) ) {
            $display = true;
        }
        if ( !isset( $um['qpp_notice_1'] ) || true !== $um['qpp_notice_1'] ) {
            if ( in_array( $page, $display_on_pages, true ) ) {
                $display = true;
            }
        }
        if ( !$display ) {
            return;
        }
        $qpp_freemius_licence = get_option( 'qpp_licence_generated', false );
        $qpp_key = get_option( 'qpp_key', array(
            'authorised' => false,
        ) );
        $user = wp_get_current_user();
        $freemius_nonce = wp_create_nonce( 'qpp_freemius_licence' );
        $notice = "";
        $logo = '<img style="float:left;padding-right: 10px" height="64px" quick-paypal-payments="https://ps.w.org/quick-paypal-payments/assets/icon-128x128.png">';
        global $quick_paypal_payments_fs;
        if ( $qpp_key['authorised'] && $quick_paypal_payments_fs->is_free_plan() ) {
            if ( 'generated' !== $qpp_freemius_licence ) {
                $failed = '';
                if ( 'failed' === $qpp_freemius_licence ) {
                    $failed = '<p><strong style="color: red;">Licence Generation Failed</strong>, make sure you are using the latest version and try again, 
if after several attempts you still get this message contact me at 
<a target="_blank" href="mailto:support@fullworks.net">support</a> with your details, name , email, domain name etc</p>';
                }
                $notice .= sprintf(
                    __( '%1$s<strong>Important action required</strong>. %2$s<p>Big changes are coming to Quick PayPal Payments and you MUST take action now to keep your premium features!!<br>
 You will need to <strong>download and install the PREMIUM</strong> plugin to keep your premium features. Don\'t worry it is easy.</p>
<p></p><a target="_blank" href="%3$s" class="button" >CLICK HERE TO GET YOUR DOWNLOAD AND LICENCE BY EMAIL</a></p><p>If you do not download and install the premium plugin 
- when version 6 is released you may lose features you depend on, so register now and be prepared.</p>
<p>The email will be sent to <strong>%4$s</strong> If you don\'t get the email soon, check your spam </p><p>To be clear if you already have paid for the Pro version - you do not have to pay again</p>', 'quick-paypal-payments' ),
                    $logo,
                    $failed,
                    '?action=qppfreemius&_wpnonce=' . $freemius_nonce,
                    $user->data->user_email
                );
            } else {
                $notice .= sprintf( __( '%1$s<strong>Important action required - Your licence request was successful</strong>. <p>Big changes are coming to Quick PayPal Payments  and you MUST take action now to keep your premium features!!<br>
 You have requested the PREMIUM plugin, please install it to keep your premium features. Don\'t worry it is easy.
</p><p>If you do not download and install the premium plugin - when version 6 is released you may lose features you depend on, so register now and be prepared.
The email was sent to <strong>%2$s</strong>. </p><p>If you do not have access to <strong>%2$s</strong>( check your spam ) contact me at 
<a target="_blank" href="mailto:support@fullworks.net">support</a> with your details.</p><p>You can also use your email <strong>%2$s</strong>  to login at <a href="https://fullworks.net/account/" target="_blank">
https://fullworks.net/account/</a> to get your download and licence key
</p>', 'quick-paypal-payments' ), $logo, $user->data->user_email );
            }
        } else {
            // start of free offer
            if ( 'generated' !== $qpp_freemius_licence ) {
                $failed = '';
                if ( 'failed' === $qpp_freemius_licence ) {
                    $failed = '<p><strong style="color: red;">Licence Generation Failed</strong>, make sure you are using the latest version and try again, 
if after several attempts you still get this message contact me at 
<a target="_blank" href="mailto:support@fullworks.net">support</a> with your details, name , email, domain name etc</p>';
                }
                $notice .= sprintf(
                    __( '%1$s<strong>Important NOTICE for FREE users of this plugin</strong>. %2$s<p>Version 6 of the plugin will have some features that are free today as paid only, this is necessary to be able to continue to support the free version.
</p><p> 
As the new free version will have  less features than the current free version, <strong>no one likes fre things being taken away</strong> so for a limited time we are offering a free lifetime upgrade to the Pro GOLD plan, which is teh equivalent of the free version today. </p>
<p>The licence will be given to your email, if you do not have access to <strong>%3$s</strong> please change it before requesting!</p><p><a href="https://wordpress.org/support/topic/the-futures-of-this-plugin-understanding-why-there-is-an-offer-of-gold-plan/" target="_blank">Read more details as why this is happening here on the official WordPress support forum</a></p>
<a target="_blank" href="%4$s" class="button" >CLICK HERE TO GET YOUR DOWNLOAD AND LICENCE BY EMAIL</a>', 'quick-paypal-payments' ),
                    $logo,
                    $failed,
                    $user->data->user_email,
                    '?page=quick-paypal-payments&action=qppfreemius&free=true&_wpnonce=' . $freemius_nonce
                );
            } else {
                // generated
                $notice .= sprintf( __( '%1$s<strong>Important action required - Your licence request was successful</strong>. <p>you MUST take action now!!<br>
 You have requested the Pro GOLD plugin free offer, please install it. Don\'t worry it is easy.
</p><p>If you do not download and install the premium plugin - when version 6 is released you may lose features you depend on, so register now and be prepared.
The email was sent to <strong>%2$s</strong>. </p><p>If you do not have access to <strong>%2$s</strong>( check your spam ) contact me at 
<a target="_blank" href="mailto:support@fullworks.net">support</a> with your details.</p><p>You can also use your email <strong>%2$s</strong>  to login at <a href="https://fullworks.net/account/" target="_blank">
https://fullworks.net/account/</a> to get your download and licence key
</p>', 'quick-paypal-payments' ), $logo, $user->data->user_email );
            }
        }
        // Output notice HTML.
        if ( !defined( 'QPP_DEMO' ) || !QPP_DEMO ) {
            if ( !empty( $notice ) ) {
                printf( '<div id="qpp_notice_1" class="qpp_notice is-dismissible notice notice-warning" style="overflow:hidden;font-size: 150%%;"><p>%1$s</p></div>', wp_kses_post( $notice ) );
            }
        }
    }

    public function generate_freemius_licence() {
        if ( wp_doing_ajax() ) {
            return;
        }
        if ( !isset( $_REQUEST['action'] ) || 'qppfreemius' !== $_REQUEST['action'] ) {
            return;
        }
        if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'qpp_freemius_licence' ) ) {
            die( esc_html__( 'Security check invalid, expired or missing', 'quick-paypal-payments' ) );
        }
        $user = wp_get_current_user();
        $qpp_key = get_option( 'qpp_key' );
        $suffix = '';
        if ( isset( $_REQUEST['free'] ) ) {
            $qpp_key['key'] = 'free';
            $suffix = '-free';
        }
        $request = wp_remote_get( 'https://fullworks.net/wp-json/fullworks-qpp-sync/v1/add/quick-paypal-payments' . $suffix . '/?key=' . $qpp_key['key'] . '&email=' . $user->data->user_email . '&domain=' . get_bloginfo( 'url' ) );
        if ( is_wp_error( $request ) ) {
            update_option( 'qpp_licence_generated', 'failed' );
            return;
        }
        $response_code = wp_remote_retrieve_response_code( $request );
        if ( 200 !== $response_code ) {
            update_option( 'qpp_licence_generated', 'failed' );
        }
        update_option( 'qpp_licence_generated', 'generated' );
    }

}
