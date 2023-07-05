<?php

use  Quick_Paypal_Payments\Core\Utilities ;
global  $quick_paypal_payments_fs ;
add_action( 'init', 'qpp_settings_init' );
add_action( 'admin_menu', 'qpp_page_init' );
add_action( 'admin_menu', 'qpp_admin_pages' );
add_action(
    'plugin_row_meta',
    'qpp_plugin_row_meta',
    10,
    2
);
add_action( 'wp_head', 'qpp_head_css' );
function qpp_admin_tabs( $current = 'settings' )
{
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global  $quick_paypal_payments_fs ;
    $tabs = array(
        'setup'        => esc_html__( 'Setup', 'quick-event-manager' ),
        'settings'     => esc_html__( 'Form Settings', 'quick-event-manager' ),
        'styles'       => esc_html__( 'Styling', 'quick-event-manager' ),
        'send'         => esc_html__( 'Send Options', 'quick-event-manager' ),
        'error'        => esc_html__( 'Validation Messages', 'quick-event-manager' ),
        'autoresponce' => esc_html__( 'Auto Responder', 'quick-event-manager' ),
        'coupon'       => esc_html__( 'Coupons', 'quick-event-manager' ),
        'ipn'          => 'IPN',
    );
    echo  '<h2 class="nav-tab-wrapper">' ;
    foreach ( $tabs as $tab => $name ) {
        $class = ( $tab == $current ? ' nav-tab-active' : '' );
        echo  '<a class="nav-tab' . esc_attr( $class ) . '" href="?page=quick-paypal-payments&tab=' . esc_attr( $tab ) . '">' . esc_attr( $name ) . '</a>' ;
    }
    echo  '</h2>' ;
}

function qpp_tabbed_page()
{
    global  $quick_paypal_payments_fs ;
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    echo  '<h1>Quick Paypal Payments</h1>' ;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
    
    if ( isset( $_GET['tab'] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
        $tab = sanitize_text_field( $_GET['tab'] );
        qpp_admin_tabs( $tab );
    } else {
        qpp_admin_tabs( 'setup' );
        $tab = 'setup';
    }
    
    switch ( $tab ) {
        case 'setup':
            qpp_setup( $id );
            break;
        case 'settings':
            qpp_form_options( $id );
            break;
        case 'styles':
            qpp_styles( $id );
            break;
        case 'send':
            qpp_send_page( $id );
            break;
        case 'error':
            qpp_error_page( $id );
            break;
        case 'address':
            qpp_address( $id );
            break;
        case 'shortcodes':
            qpp_shortcodes();
            break;
        case 'reset':
            qpp_reset_page( $id );
            break;
        case 'coupon':
            qpp_coupon_codes( $id );
            break;
        case 'ipn':
            qpp_ipn_page();
            break;
        case 'autoresponce':
            qpp_autoresponce_page( $id );
            break;
        case 'multipleproducts':
            break;
    }
}

function qpp_setup( $id )
{
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global  $quick_paypal_payments_fs ;
    $qpp_setup = qpp_get_stored_setup();
    $new_curr = $php = $head = '';
    
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $qpp_setup['alternative'] = filter_var( $_POST['alternative'], FILTER_SANITIZE_STRING );
        $qpp_setup['email'] = filter_var( $_POST['email'], FILTER_SANITIZE_STRING );
        
        if ( !empty($_POST['new_form']) ) {
            $qpp_setup['current'] = stripslashes( $_POST['new_form'] );
            $qpp_setup['current'] = filter_var( $qpp_setup['current'], FILTER_SANITIZE_STRING );
            $qpp_setup['current'] = preg_replace( "/[^A-Za-z]/", '', $qpp_setup['current'] );
            $qpp_setup['alternative'] = $qpp_setup['current'] . ',' . $qpp_setup['alternative'];
        } else {
            $qpp_setup['current'] = filter_var( $_POST['current'], FILTER_SANITIZE_STRING );
        }
        
        if ( empty($qpp_setup['current']) ) {
            $qpp_setup['current'] = '';
        }
        $arr = explode( ",", $qpp_setup['alternative'] );
        foreach ( $arr as $item ) {
            $qpp_curr[$item] = ( isset( $_POST['qpp_curr' . $item] ) ? stripslashes( $_POST['qpp_curr' . $item] ) : '' );
            $qpp_curr[$item] = filter_var( $qpp_curr[$item], FILTER_SANITIZE_STRING );
            $qpp_email[$item] = ( isset( $_POST['qpp_email' . $item] ) ? stripslashes( $_POST['qpp_email' . $item] ) : '' );
            $qpp_email[$item] = filter_var( $qpp_email[$item], FILTER_SANITIZE_STRING );
        }
        
        if ( !empty($_POST['new_form']) ) {
            $email = $qpp_setup['current'];
            $qpp_curr[$email] = stripslashes( $_POST['new_curr'] );
            $qpp_curr[$email] = filter_var( $qpp_curr[$email], FILTER_SANITIZE_STRING );
        }
        
        $qpp_setup['image_url'] = ( isset( $_POST['image_url'] ) ? esc_url_raw( $_POST['image_url'] ) : '' );
        $qpp_setup['location'] = ( isset( $_POST['location'] ) ? sanitize_text_field( $_POST['location'] ) : '' );
        $qpp_setup['sandbox'] = ( isset( $_POST['sandbox'] ) ? sanitize_text_field( $_POST['sandbox'] ) : '' );
        $qpp_setup['disable_error'] = ( isset( $_POST['disable_error'] ) ? sanitize_text_field( $_POST['disable_error'] ) : '' );
        $qpp_setup['nostore'] = ( isset( $_POST['nostore'] ) ? sanitize_text_field( $_POST['nostore'] ) : '' );
        update_option( 'qpp_curr', $qpp_curr );
        update_option( 'qpp_email', $qpp_email );
        update_option( 'qpp_setup', $qpp_setup );
        $qpp_setup = qpp_get_stored_setup();
        qpp_admin_notice( "The forms have been updated." );
        if ( $_POST['qpp_clone'] && !empty($_POST['new_form']) ) {
            qpp_clone( $qpp_setup['current'], sanitize_text_field( $_POST['qpp_clone'] ) );
        }
    }
    
    
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        qpp_delete_everything();
        qpp_admin_notice( "Everything has been reset." );
        $qpp_setup = qpp_get_stored_setup();
    }
    
    $arr = explode( ",", $qpp_setup['alternative'] );
    foreach ( $arr as $item ) {
        
        if ( isset( $_POST['deleteform' . $item] ) && $_POST['deleteform' . $item] == $item && isset( $_POST['delete' . $item] ) && $item != '' ) {
            $forms = $qpp_setup['alternative'];
            qpp_delete_things( $_POST['deleteform' . $item] );
            $qpp_setup['alternative'] = str_replace( $_POST['deleteform' . $item] . ',', '', $forms );
            $qpp_setup['current'] = '';
            $qpp_setup['email'] = $_POST['email'];
            update_option( 'qpp_setup', $qpp_setup );
            qpp_admin_notice( "<b>The form named " . $item . " has been deleted.</b>" );
            $id = '';
        }
    
    }
    $qpp_curr = qpp_get_stored_curr();
    $qpp_email = qpp_get_stored_email();
    ${$qpp_setup['location']} = 'checked';
    if ( !$new_curr ) {
        $new_curr = $qpp_curr[''];
    }
    $content = '<div class="qpp-settings"><div class="qpp-options">
    <form method="post" action="">
    <h2>Account Email</h2>
    <p><span style="color:red; font-weight: bold; margin-right: 3px">Important!</span> Enter your PAYPAL email address</p>
    <input type="text" label="Email" name="email" value="' . esc_attr( $qpp_setup['email'] ) . '" /></p>
    <h2>Existing Forms</h2>
    <table>
    <tr>
    <td><b>Form name&nbsp;&nbsp;</b></td>
    <td><b>Currency</b></td>
    <td><b>Shortcode</b></td>
    </tr>';
    $arr = explode( ",", $qpp_setup['alternative'] );
    sort( $arr );
    foreach ( $arr as $item ) {
        
        if ( $qpp_setup['current'] == $item ) {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        
        if ( !$qpp_email[$item] ) {
            $qpp_email[$item] = $qpp_setup['email'];
        }
        
        if ( $item == '' ) {
            $formname = 'default';
        } else {
            $formname = $item;
        }
        
        $content .= '<tr>
        <td><input  type="radio" name="current" value="' . esc_attr( $item ) . '" ' . checked( $checked, 'checked', false ) . ' /> ' . $formname . '</td>
        <td><input type="text" style="width:3em;padding:1px;" name="qpp_curr' . esc_attr( $item ) . '" value="' . esc_attr( $qpp_curr[$item] ) . '" /></td>';
        
        if ( $item ) {
            $shortcode = ' form="' . $item . '"';
        } else {
            $shortcode = '';
        }
        
        $content .= '<td><code>[qpp' . $shortcode . ']</code></td><td>';
        if ( $item ) {
            $content .= '<input type="hidden" name="deleteform' . $item . '" value="' . esc_attr( $item ) . '">
			<input type="submit" name="delete' . esc_attr( $item ) . '" class="button-secondary" value="delete" onclick="return window.confirm( \'Are you sure you want to delete ' . $item . '?\' );" />';
        }
        $content .= '</td></tr>';
    }
    $content .= '</table>
    <h2>Create New Form</h2>
    <p>Enter form name (letters only - no numbers, spaces or punctuation marks)</p>
    <p><input type="text" label="new_Form" name="new_form" value="" /></p>
    <p>Enter currency code: <input type="text" style="width:3em" label="new_curr" name="new_curr" value="' . esc_attr( $new_curr ) . '" />&nbsp;(For example: GBP, USD, EUR)</p>
    <p>Allowed Paypal Currency codes are given <a href="https://developer.paypal.com/webapps/developer/docs/classic/api/currency_codes/" target="blank">here</a>.</p>
    <p><span style="color:red; font-weight: bold; margin-right: 3px">Important!</span> If your currency is not listed the plugin will work but paypal will not accept the payment.</p>
    <input type="hidden" name="alternative" value="' . esc_attr( $qpp_setup['alternative'] ) . '" />
    <p>Copy settings from an exisiting form.</p>
    <select name="qpp_clone"><option>Do not copy settings</option>';
    foreach ( $arr as $item ) {
        if ( $item == '' ) {
            $item = 'default';
        }
        $content .= '<option value="' . esc_attr( $item ) . '">' . $item . '</option>';
    }
    $content .= '</select>
    <h2>Styles Location</h2>
    <p><input type="radio" name="location" value="php"' . esc_attr( $php ) . ' /> Extenal Stylesheet<br />
    <input type="radio" name="location" value="head"' . esc_attr( $head ) . ' /> Document Head</p>
    
    <h2>Checkout Logo</h2>
    <p>The URL of the 150x50-pixel image displayed as your logo in the upper left corner of the PayPal checkout pages.</p>';
    
    if ( $quick_paypal_payments_fs->is_trial() || $quick_paypal_payments_fs->is_trial_utilized() ) {
        $upurl = $quick_paypal_payments_fs->get_upgrade_url();
    } else {
        $upurl = $quick_paypal_payments_fs->get_trial_url();
    }
    
    $content .= '<p><a href="' . $upurl . '">Pro-version only</a></p>';
    $content .= '<h2>Global Settings</h2>
    <p><input type="checkbox" name="sandbox" ' . checked( $qpp_setup['sandbox'], 'checked', false ) . ' value="checked" /> Use Paypal sandbox (developer use only)</p>
    <p><input type="checkbox" name="disable_error" ' . checked( $qpp_setup['disable_error'], 'checked', false ) . ' value="checked" /> Disable IPN Error logging</p>
    <p><input type="checkbox" name="nostore"' . checked( $qpp_setup['nostore'], 'checked', false ) . ' value="checked"> Do not store messages in the database (this will disable all notifications).</p>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Update Settings" /> <input type="submit" name="Reset" class="button-secondary" value="Reset Everything" onclick="return window.confirm( \'This will delete all your forms and settings.\\nAre you sure you want to reset everything?\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>';
    $content .= '</div>
    <div class="qpp-options" style="float:right">';
    
    if ( $quick_paypal_payments_fs->is_trial() || $quick_paypal_payments_fs->is_trial_utilized() ) {
        $upurl = $quick_paypal_payments_fs->get_upgrade_url();
        $upmsg = '<p>From only $14.99 per annum</p>';
    } else {
        $upurl = $quick_paypal_payments_fs->get_trial_url();
        $upmsg = '<p>Free 14 Day Trial, then from $14.99 per annum</p>';
    }
    
    $content .= '<div class="qppupgrade"><a href="' . $upurl . '">
        <h3>Upgrade to Pro Platinum</h3>
        <p>Upgrading gives Mailchimp data collection, Multiple products and Personalised Support.</p>
        <p>Click to find out more</p>' . $upmsg . '
        </a></div>';
    $content .= '<h2>Adding the payment form to your site</h2>
    <p>To add the basic payment form to your posts or pages use the shortcode: <code>[qpp]</code>. Shortcodes for named forms are given on the left.</p>
    <p>There is also a widget called "Quick Paypal Payments" you can drag and drop into a sidebar.</p>
    <p>That\'s it. The payment form is ready to use.</p>
    <h2>Shortcodes and Examples</h2>
    <p>All the shortcodes are given <a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">on this page</a>.</p>
    <p>There are examples of payment forms <a href="https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/" target="_blank">on this page</a>.</p>
    <h2>Options and Settings</h2>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=settings">Form Settings.</a></span> Change the layout of the form, add or remove fields and the order they appear and edit the labels and captions.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=styles">Styling.</a></span> Change fonts, colours, borders, images and submit button.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=reply">Send Options.</a></span> Change how the form is sent.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=error">Validation Messages.</a></span> Change the error message.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=autoresponce">Auto Responder.</a></span> Set up a thank you message.</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=ipn">Instant Payment Notification.</a></span> Keep track of completed payments.</p>
    <p><span style="font-weight:bold"><a href="' . esc_url( admin_url( '?page=quick-paypal-payments-messages' ) ) . '">Payment Records.</a></span> See all the payment records. Or click on the <b>Payments</b> link in the dashboard menu.</p>
    <h2>Support</h2>
    <p>First please check the knowldege base to see if a resolution to your issue is already documented</p>
    <a href="https://fullworks.net/docs/quick-paypal-payments/" class="button" target=""_blank">Knowldege Base</a>';
    $content .= '<p>If you can not find an answer, for the free version please raise your support questions on the WordPress community forum </p>
<a href="https://wordpress.org/support/plugin/quick-paypal-payments/" class="button" target=""_blank">WordPress Support Forum</a>
<p>If you require urgent or personal support please <a href="' . esc_url( $quick_paypal_payments_fs->get_upgrade_url() ) . '" >upgrade to a paid plan</a></p>';
    $content .= '</div>
    </div>';
    echo  $content ;
}

function qpp_clone( $id, $clone )
{
    if ( $clone == 'default' ) {
        $clone = '';
    }
    $update = qpp_get_stored_options( $clone );
    update_option( 'qpp_options' . $id, $update );
    $update = qpp_get_stored_send( $clone );
    update_option( 'qpp_send' . $id, $update );
    $update = qpp_get_stored_style( $clone );
    update_option( 'qpp_style' . $id, $update );
    $update = qpp_get_stored_coupon( $clone );
    update_option( 'qpp_coupon' . $id, $update );
    $update = qpp_get_stored_error( $clone );
    update_option( 'qpp_error' . $id, $update );
    $update = qpp_get_stored_address( $clone );
    update_option( 'qpp_address' . $id, $update );
}

function qpp_form_options( $id )
{
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global  $quick_paypal_payments_fs ;
    qpp_change_form_update( $id );
    
    if ( isset( $_POST['qpp_submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'title',
            'blurb',
            'sort',
            'inputreference',
            'inputamount',
            'allow_amount',
            'combobox',
            'comboboxword',
            'comboboxlabel',
            'shortcodereference',
            'use_quantity',
            'quantitylabel',
            'use_stock',
            'ruse_stock',
            'fixedstock',
            'stocklabel',
            'use_cf',
            'ruse_cf',
            'cflabel',
            'use_consent',
            'consentlabel',
            'consentpaypal',
            'noconsentpaypal',
            'use_message',
            'ruse_message',
            'messagelabel',
            'use_options',
            'optionlabel',
            'optionvalues',
            'optionselector',
            'inline_options',
            'shortcodeamount',
            'shortcode_labels',
            'submitcaption',
            'cancelurl,',
            'thanksurl',
            'target',
            'paypal-url',
            'paypal-location',
            'postageblurb',
            'postagepercent',
            'postagefixed',
            'usepostage',
            'usecoupon',
            'couponblurb',
            'couponref',
            'couponbutton',
            'captcha',
            'mathscaption',
            'fixedreference',
            'fixedamount',
            'minamount',
            'useterms',
            'useblurb',
            'extrablurb',
            'userecurring',
            'recurringblurb',
            'recurring',
            'recurringhowmany',
            'Dperiod',
            'Wperiod',
            'Mperiod',
            'Yperiod',
            'srt',
            'payments',
            'every',
            'termsblurb',
            'termsurl',
            'termspage',
            'quantitymax',
            'quantitymaxblurb',
            'combine',
            'usetotals',
            'totalsblurb',
            'useaddress',
            'addressblurb',
            'use_datepicker',
            'ruse_datepicker',
            'use_multiples',
            'datepickerlabel',
            'currency_seperator',
            'selector',
            'refselector',
            'use_reset',
            'resetcaption',
            'use_slider',
            'sliderlabel',
            'min',
            'max',
            'initial',
            'step',
            'inline_amount',
            'useemail',
            'ruseemail',
            'emailblurb',
            'variablerecurring'
        );
        $args = array(
            'strong' => array(),
            'em'     => array(),
            'b'      => array(),
            'i'      => array(),
            'a'      => array(
            'href'   => array(),
            'target' => array(),
            'class'  => array(),
            'rel'    => array(),
            'title'  => array(),
        ),
        );
        foreach ( $options as $item ) {
            if ( isset( $_POST[$item] ) ) {
                $qpp[$item] = wp_kses_post( stripslashes( $_POST[$item] ) );
            }
        }
        
        if ( qpp_get_element( $qpp, 'userecurring', false ) ) {
            $qpp['use_quantity'] = '';
            $qpp['usepostage'] = '';
            $qpp[''] = '';
            $qpp['useprocess'] = '';
            $qpp['use_stock'] = '';
        }
        
        update_option( 'qpp_options' . $id, $qpp );
        qpp_admin_notice( "The form and submission settings have been updated." );
    }
    
    
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_options' . $id );
        qpp_admin_notice( "The form and submission settings have been reset." );
    }
    
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $currency = qpp_get_stored_curr();
    $refnone = $refradio = $refdropdown = $ignore = $radio = $dropdown = $optionsradio = $optionsdropdown = false;
    $optionscheckbox = $processfixed = $postagefixed = $postagepercent = $comma = $M = $D = $W = $Y = $imagebelow = $imageabove = false;
    $qpp = qpp_get_stored_options( $id );
    ${$qpp['paypal-location']} = 'checked';
    ${$qpp['processtype']} = 'checked';
    ${$qpp['postagetype']} = 'checked';
    // ${$qpp['coupontype']} = 'checked';
    ${$qpp['recurring']} = 'checked';
    ${$qpp['currency_seperator']} = 'checked';
    ${$qpp['refselector']} = 'checked';
    ${$qpp['optionselector']} = 'checked';
    ${$qpp['selector']} = 'checked';
    $content = qpp_head_css();
    $content .= '<script>
    jQuery(function() {var qpp_sort = jQuery( "#qpp_sort" ).sortable({ axis: "y" ,
    update:function(e,ui) {
    var order = qpp_sort.sortable("toArray").join();
    jQuery("#qpp_settings_sort").val(order);}
    });});
    </script>';
    $content .= '<div class="qpp-settings"><div class="qpp-options">';
    
    if ( $id ) {
        $content .= '<h2>Form settings for ' . $id . '</h2>';
    } else {
        $content .= '<h2>Default form settings</h2>';
    }
    
    $content .= qpp_change_form( $qpp_setup );
    $content .= '<form action="" method="POST">
    <p>Paypal form heading (optional)</p>
    <input type="text" style="width:100%" name="title" value="' . esc_attr( $qpp['title'] ) . '" />
    <p>This is the text that will appear below the heading and above the form (optional):</p>
    <input type="text" style="width:100%" name="blurb" value="' . esc_attr( $qpp['blurb'] ) . '" />
    <h2>Form Fields</h2>
    <p>Drag and drop to change order of the fields</p>
    <div style="margin-left:7px;font-weight:bold;"><div style="float:left; width:30%;">Form Fields</div><div style="float:left; width:30%;">Labels and Options</div></div>
    <div style="clear:left"></div>
    <ul id="qpp_sort">';
    foreach ( explode( ',', $qpp['sort'] ) as $name ) {
        $check = $type = $input = $checked = $options = false;
        switch ( $name ) {
            case 'field1':
                $check = '&nbsp;';
                $type = 'Reference';
                $input = 'inputreference';
                $checked = 'checked';
                $options = '<input type="checkbox" name="fixedreference" ' . checked( $qpp['fixedreference'], 'checked', false ) . ' value="checked" /> Display as a pre-set reference<br><span class="description">Use commas to seperate options: Red,Green, Blue<br>Use semi-colons to combine with amount: Red;$5,Green;$10,Blue;£20</span><br>
            Options Selector: <input type="radio" name="refselector" value="refradio" ' . esc_attr( $refradio ) . ' /> Radio&nbsp;
            <input type="radio" name="refselector" value="refdropdown" ' . checked( $refdropdown, 'refdropdown', false ) . ' /> Dropdown&nbsp;
            <input type="radio" name="refselector" value="refnone" ' . checked( $refnone, 'refnone', false ) . ' /> Inline&nbsp;
            <input type="radio" name="refselector" value="ignore" ' . checked( $ignore, 'ignore', false ) . ' /> None</br>';
                break;
            case 'field2':
                $check = '<input type="checkbox" name="use_stock" ' . checked( $qpp['use_stock'], 'checked', false ) . ' value="checked" />';
                $type = 'Use Item Number';
                $input = 'stocklabel';
                $checked = $qpp['use_stock'];
                $options = '<input type="checkbox" name="fixedstock" ' . checked( $qpp['fixedstock'], 'checked', false ) . ' value="checked" /> Display as a pre-set item number<br>
<input type="checkbox" name="ruse_stock" ' . esc_attr( $qpp['ruse_stock'] ) . ' value="checked" /> Required Field';
                break;
            case 'field3':
                $check = ( $qpp['userecurring'] ? '&nbsp;' : '<input type="checkbox"   name="use_quantity" ' . esc_attr( $qpp['use_quantity'] ) . ' value="checked" />' );
                $type = 'Quantity';
                $input = 'quantitylabel';
                $checked = $qpp['use_quantity'];
                $options = '<input type="checkbox" name="quantitymax" ' . checked( $qpp['quantitymax'], 'checked', false ) . ' value="checked" /> Display and validate a maximum quantity<br><span class="description">Message that will display on the form:</span><br>
            <input type="text" name="quantitymaxblurb" value="' . esc_attr( $qpp['quantitymaxblurb'] ) . '" />';
                break;
            case 'field4':
                $check = '&nbsp;';
                $type = 'Amount';
                $input = 'inputamount';
                $checked = 'checked';
                $options = '<input type="checkbox" name="allow_amount" ' . checked( $qpp['allow_amount'], 'checked', false ) . ' value="checked" /> Do not validate (use default amount value)<br>
            <input type="checkbox" name="fixedamount" ' . checked( $qpp['fixedamount'], 'checked', false ) . ' value="checked" /> Display as a pre-set amount<br>
             <input type="text" style="border:1px solid #415063; width:3em;" name="minamount" . value ="' . esc_attr( $qpp['minamount'] ) . '" />&nbsp;Minimum value<br>
            <span class="description">Use commas to create an options list: £10,£20,£30</span><br>
            Options Selector: <input type="radio" name="selector" value="radio" ' . checked( $radio, 'radio', false ) . ' /> Radio&nbsp;
            <input type="radio" name="selector" value="dropdown" ' . checked( $dropdown, 'dropdown', false ) . ' /> Dropdown<br>
            <input type="checkbox" name="inline_amount" ' . checked( $qpp['inline_amount'], 'checked', false ) . ' value="checked" />&nbsp;Display inline radio fields<br>
            <input type="checkbox" name="combobox" ' . checked( $qpp['combobox'], 'checked', false ) . ' value="checked" /> Add input field to dropdown<br>
            Caption:&nbsp;<input type="text" style="width:7em;" name="comboboxword" value="' . esc_attr( $qpp['comboboxword'] ) . '" />
            <br>
            Instruction:&nbsp;<input type="text" style="width:10em;" name="comboboxlabel" value="' . esc_attr( $qpp['comboboxlabel'] ) . '" />
            ';
                break;
            case 'field5':
                $check = ( $qpp['userecurring'] ? '&nbsp;' : '<input type="checkbox"   name="use_options" ' . checked( $qpp['use_options'], 'checked', false ) . ' value="checked" />' );
                $type = 'Options';
                $input = 'optionlabel';
                $checked = $qpp['use_options'];
                $options = '<span class="description">Options (separate with a comma):</span><br><textarea  name="optionvalues" label="Radio" rows="2">' . $qpp['optionvalues'] . '</textarea><br>
            Options Selector: <input type="radio" name="optionselector" value="optionsradio" ' . checked( $optionsradio, 'optionradio', false ) . ' /> Radio&nbsp;
            <input type="radio" name="optionselector" value="optionscheckbox" ' . checked( $optionscheckbox, 'optionscheckbox', false ) . ' /> Checkbox&nbsp;
            <input type="radio" name="optionselector" value="optionsdropdown" ' . checked( $optionsdropdown, 'optionscheckbox', false ) . ' /> Dropdown<br>
            <input type="checkbox" name="inline_options" ' . checked( $qpp['inline_options'], 'checked', false ) . ' value="checked" />&nbsp;Display inline radio and checkbox fields';
                break;
            case 'field6':
                $check = ( $qpp['userecurring'] ? '&nbsp;' : '<input type="checkbox" name="usepostage" ' . checked( $qpp['usepostage'], 'checked', false ) . ' value="checked" />' );
                $type = 'Handling';
                $input = 'postageblurb';
                $checked = $qpp['usepostage'];
                $options = '<span class="description">Post and Packing charge type:</span><br>
            ' . esc_html__( 'Percentage of the total', 'quick-event-manager' ) . ': <input type="text" style="width:4em;padding:2px" label="postagepercent" name="postagepercent" value="' . esc_attr( $qpp['postagepercent'] ) . '" /> %<br>
            ' . esc_html__( 'Fixed amount', 'quick-event-manager' ) . ': <input type="text" style="width:4em;padding:2px" label="postagefixed" name="postagefixed" value="' . esc_attr( $qpp['postagefixed'] ) . '" /> ' . esc_html( $currency[$id] );
                break;
            case 'field8':
                $check = '<input  type="checkbox"   name="captcha"' . checked( $qpp['captcha'], 'checked', false ) . ' value="checked" />';
                $type = 'Maths Captcha';
                $input = 'mathscaption';
                $checked = $qpp['captcha'];
                $options = '<span class="description">Add a maths checker to the form to (hopefully) block most of the spambots.</span>';
                break;
            case 'field9':
                $check = ( $qpp['userecurring'] ? '&nbsp;' : '<input  type="checkbox" name="usecoupon"' . checked( $qpp['usecoupon'], 'checked', false ) . ' value="checked" />' );
                $type = 'Coupon Code';
                $input = 'couponblurb';
                $checked = $qpp['usecoupon'];
                $options = '<span class="description">Button label:</span><br>
            <input type="text" name="couponbutton" value="' . esc_attr( $qpp['couponbutton'] ) . '" /><br>
            <span class="description">Coupon applied message:</span><br>
            <input type="text" name="couponref" value="' . esc_attr( $qpp['couponref'] ) . '" /><br>
            <a href="?page=quick-paypal-payments&tab=coupon">Set coupon codes</a>';
                break;
            case 'field10':
                $check = '<input  type="checkbox" name="useterms"' . checked( $qpp['useterms'], 'checked', false ) . ' value="checked" />';
                $type = 'Terms and Conditions';
                $input = 'termsblurb';
                $checked = $qpp['termsblurb'];
                $options = '<span class="description">URL of Terms and Conditions:</span><br>
            <input type="text" name="termsurl" value="' . esc_attr( $qpp['termsurl'] ) . '" /><br>
            <input  type="checkbox" name="termspage"' . checked( $qpp['termspage'], 'checked', false ) . ' value="checked" /> Open link in a new page';
                break;
            case 'field11':
                $check = '<input  type="checkbox" name="useblurb"' . checked( $qpp['useblurb'], 'checked', false ) . ' value="checked" />';
                $type = 'Additional Information';
                $input = 'extrablurb';
                $checked = $qpp['useblurb'];
                $options = '<span class="description">Add additional information to your form</span>';
                break;
            case 'field12':
                $check = '<input  type="checkbox" name="userecurring"' . checked( $qpp['userecurring'], 'checked', false ) . ' value="checked" />';
                $type = 'Recurring Payments';
                $input = 'recurringblurb';
                $checked = $qpp['userecurring'];
                $options = '<p>Number of payments: <input type="text" style="width:2em;padding:2px" name="recurringhowmany" value="' . esc_attr( $qpp['recurringhowmany'] ) . '" /> (max 52 , min 2)<br>
            Message: <input type="text" style="width:10em;padding:2px" name="every" value="' . esc_attr( $qpp['every'] ) . '" /></p>
            <p><input type="radio" name="recurring" value="D"' . esc_attr( $D ) . ' /> 
            <input type="text" style="width:6em;padding:2px" name="Dperiod" value="' . esc_attr( $qpp['Dperiod'] ) . '" /></p>
            <p><input type="radio" name="recurring" value="W"' . esc_attr( $W ) . ' />
             <input type="text" style="width:6em;padding:2px" name="Wperiod" value="' . esc_attr( $qpp['Wperiod'] ) . '" /></p>
            <p><input type="radio" name="recurring" value="M"' . esc_attr( $M ) . ' /> 
            <input type="text" style="width:6em;padding:2px" name="Mperiod" value="' . esc_attr( $qpp['Mperiod'] ) . '" /></p>
            <p><input type="radio" name="recurring" value="Y"' . esc_attr( $Y ) . ' /> 
            <input type="text" style="width:6em;padding:2px" name="Yperiod" value="' . esc_attr( $qpp['Yperiod'] ) . '" /></p>
            <p><input  type="checkbox" name="variablerecurring"' . checked( $qpp['variablerecurring'], 'checked', false ) . ' value="checked" />&nbsp;Allow users to change number of payments.</p>
            <p><span style="color:red">WARNING!</span> Recurring payments only work if you have a Business or Premier account.<br>Using recurring payments will disable some form fields.</p>';
                break;
            case 'field13':
                $check = '<input  type="checkbox" name="useaddress"' . checked( $qpp['useaddress'], 'checked', false ) . ' value="checked" />';
                $type = 'Personal Details';
                $input = 'addressblurb';
                $checked = $qpp['useaddress'];
                $options = '<p><a href="?page=quick-paypal-payments&tab=address">Personal details Settings</a></p>';
                break;
            case 'field14':
                $check = '<input  type="checkbox" name="usetotals"' . checked( $qpp['usetotals'], 'checked', false ) . ' value="checked" />';
                $type = 'Show totals';
                $input = 'totalsblurb';
                $checked = $qpp['usetotals'];
                $options = '<span class="description">Show live totals on your form. Warning: Only works if you have one form on the page and you aren\'t using multiple amounts</span>';
                break;
            case 'field15':
                $check = '<input  type="checkbox" name="use_slider"' . checked( $qpp['use_slider'], 'checked', false ) . ' value="checked" />';
                $type = 'Range slider';
                $input = 'sliderlabel';
                $checked = $qpp['use_slider'];
                $options = 'The range slider replaces the amount field.<br>
            <input type="text" style="border:1px solid #415063; width:3em;" name="min" . value ="' . esc_attr( $qpp['min'] ) . '" />&nbsp;Minimum value<br>
            <input type="text" style="border:1px solid #415063; width:3em;" name="max" . value ="' . esc_attr( $qpp['max'] ) . '" />&nbsp;Maximum value<br>
            <input type="text" style="border:1px solid #415063; width:3em;" name="initial" . value ="' . esc_attr( $qpp['initial'] ) . '" />&nbsp;Initial value<br>
            <input type="text" style="border:1px solid #415063; width:3em;" name="step" . value ="' . esc_attr( $qpp['step'] ) . '" />&nbspStep';
                break;
            case 'field16':
                $check = '<input  type="checkbox" name="useemail"' . checked( $qpp['useemail'], 'checked', false ) . ' value="checked" />';
                $type = 'Email Address';
                $input = 'emailblurb';
                $checked = $qpp['useemail'];
                $options = '<span class="description">Use this to collect the Payers email address.</span><br>
            <input  type="checkbox" name="ruseemail"' . checked( $qpp['ruseemail'], 'checked', false ) . ' value="checked" /> Required Field';
                break;
            case 'field17':
                $check = '<input  type="checkbox" name="use_message"' . checked( $qpp['use_message'], 'checked', false ) . ' value="checked" />';
                $type = 'Add textbox for comments';
                $input = 'messagelabel';
                $checked = $qpp['use_message'];
                $options = '<input  type="checkbox" name="ruse_message"' . checked( $qpp['ruse_message'], 'checked', false ) . ' value="checked" /> Required Field';
                break;
            case 'field18':
                $type = 'Add datepicker';
                $input = '';
                $options = 'The datepicker option is only available in the Pro Version';
                break;
            case 'field19':
                $type = 'Multiple Products';
                $input = '';
                $options = 'The multiple products option is only available in the Pro Version';
                break;
            case 'field21':
                $check = '<input  type="checkbox" name="use_cf"' . checked( $qpp['use_cf'], 'checked', false ) . ' value="checked" />';
                $type = 'Codice Fiscale (Solo Italia)';
                $input = 'cflabel';
                $checked = $qpp['use_cf'];
                $options = '<input  type="checkbox" name="ruse_cf"' . checked( $qpp['ruse_cf'], 'checked', false ) . ' value="checked" /> Required Field';
                break;
            case 'field22':
                $check = '<input  type="checkbox" name="use_consent"' . checked( $qpp['use_consent'], 'checked', false ) . ' value="checked" />';
                $type = 'Consent';
                $input = 'consentlabel';
                $checked = $qpp['use_consent'];
                $options = '<span class="description">' . esc_html__( 'Add a checkbox to permit data storage. You may use html links above.', 'quick-paypal-payments' ) . '</span><br>' . '<span class="description">' . esc_html__( 'Add consent info to PayPal item line. Blank out if not required.', 'quick-paypal-payments' ) . '</span><br>' . '<input type="text" name="consentpaypal" value="' . esc_attr( $qpp['consentpaypal'] ) . '" />' . esc_html__( 'if consent given', 'quick-paypal-payments' ) . '<br>' . '<input type="text" name="noconsentpaypal" value="' . esc_attr( $qpp['noconsentpaypal'] ) . '" />' . esc_html__( 'if consent NOT given', 'quick-paypal-payments' ) . '<br>';
                break;
        }
        $li_class = ( $checked ? 'button_active' : 'button_inactive' );
        
        if ( $check ) {
            $content .= '<li class="' . esc_attr( $li_class ) . '" id="' . esc_attr( $name ) . '">
            <div style="float:left; width:5%;">' . $check . '</div>
            <div style="float:left; width:25%;">' . $type . '</div>
            <div style="float:left; width:65%;">';
            if ( $input ) {
                $content .= '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $input ) . '" value="' . esc_attr( esc_attr( $qpp[$input] ) ) . '" />';
            }
            if ( $options ) {
                $content .= $options;
            }
            $content .= '</div>
            <div style="clear:left"></div></li>';
        }
    
    }
    $content .= '</ul>
    <h2>Fixed payment and shortcode labels</h2>
    <p>These are the labels that will display if you are using a fixed reference or amount or shortcode attributes</a>. All the shortcodes are given <a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">on this page</a>.</p>
    <p>Label for the payment Reference/ID/Number:</p>
    <input type="text" name="shortcodereference" value="' . esc_attr( $qpp['shortcodereference'] ) . '" />
    <p>Label for the amount field:</p>
    <input type="text" name="shortcodeamount" value="' . esc_attr( $qpp['shortcodeamount'] ) . '" />
    <h2>Submit button caption</h2>
    <input type="text" name="submitcaption" value="' . esc_attr( $qpp['submitcaption'] ) . '" />
    <h2>Reset button</h2>
    <p><input  type="checkbox" name="use_reset"' . checked( $qpp['use_reset'], 'checked', false ) . ' value="checked" /> Show Reset Button</p>
    <input type="text" name="resetcaption" value="' . esc_attr( $qpp['resetcaption'] ) . '" />
    <h2>PayPal Image</h2>
    <p>Upload an image and select where you want it to display (Leave blank if you don\'t want to use an image).</p>
    <p>Below form title: <input type="radio" label="paypal-location" name="paypal-location" value="imageabove"' . esc_attr( $imageabove ) . ' /> Below Submit Button:&nbsp;
    <input type="radio" label="paypal-location" name="paypal-location" value="imagebelow"' . esc_attr( $imagebelow ) . ' /></p>
    <p>
    <input id="qpp_upload_image" type="text" name="paypal-url" value="' . esc_attr( $qpp['paypal-url'] ) . '" />
    <input id="qpp_upload_media_button" class="button" type="button" value="Upload Image" />
    </p>
    <p><input type="submit" name="qpp_submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form settings?\' );"/></p>
    <input type="hidden" id="qpp_settings_sort" name="sort" value="' . esc_attr( $qpp['sort'] ) . '" />';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>Form Preview</h2>
    <p>Note: The preview form uses the wordpress admin styles. Your form will use the theme styles so won\'t look exactly like the one below.</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args );
    $content .= '<p>There are some more examples of payment forms <a href="https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo  $content ;
}

function qpp_styles( $id )
{
    qpp_change_form_update();
    
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'font',
            'font-family',
            'font-size',
            'font-colour',
            'text-font-family',
            'text-font-size',
            'text-font-colour',
            'form-border',
            'input-border',
            'required-border',
            'error-colour',
            'border',
            'width',
            'widthtype',
            'background',
            'backgroundhex',
            'backgroundimage',
            'corners',
            'custom',
            'use_custom',
            'usetheme',
            'styles',
            'submit-colour',
            'submit-background',
            'submit-hover-background',
            'submit-button',
            'submit-border',
            'submitwidth',
            'submitwidthset',
            'submitposition',
            'coupon-colour',
            'coupon-background',
            'header-type',
            'header-size',
            'header-colour',
            'slider-background',
            'slider-revealed',
            'handle-background',
            'handle-border',
            'output-size',
            'output-colour',
            'slider-thickness',
            'handle-corners',
            'line_margin',
            'labeltype'
        );
        foreach ( $options as $item ) {
            
            if ( isset( $_POST[$item] ) ) {
                $styles[$item] = stripslashes( $_POST[$item] );
                $styles[$item] = filter_var( $styles[$item], FILTER_SANITIZE_STRING );
            }
        
        }
        update_option( 'qpp_style' . $id, $styles );
        qpp_admin_notice( "The form styles have been updated." );
    }
    
    
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_style' . $id );
        qpp_admin_notice( "The form styles have been reset." );
    }
    
    $font = $h2 = $h3 = $h4 = $percent = $pixel = $none = $plain = $shadow = $roundshadow = false;
    $corner = $square = $round = $rounded = $color = $white = $square = $theme = $submitrandom = false;
    $submitmiddle = $submitpixel = $submitleft = $submitmiddle = $submitright = false;
    $submitpercent = $submitrandom = $submitpixel = false;
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $style = qpp_get_stored_style( $id );
    ${$style['widthtype']} = 'checked';
    ${$style['submitwidth']} = 'checked';
    ${$style['submitposition']} = 'checked';
    ${$style['border']} = 'checked';
    ${$style['background']} = 'checked';
    ${$style['corners']} = 'checked';
    ${$style['styles']} = 'checked';
    ${$style['header-type']} = 'checked';
    $tiny = $none = $hiding = $plain = "";
    switch ( $style['labeltype'] ) {
        case 'none':
            $none = " checked='checked' ";
            break;
        case 'tiny':
            $tiny = " checked='checked' ";
            break;
        case 'plain':
            $plain = " checked='checked' ";
            break;
        case 'hiding':
            $hiding = " checked='checked' ";
            break;
    }
    $content = qpp_head_css();
    $content .= '<div class="qpp-settings"><div class="qpp-options">';
    
    if ( $id ) {
        $content .= '<h2>Style options for ' . $id . '</h2>';
    } else {
        $content .= '<h2>Default form style options</h2>';
    }
    
    $content .= qpp_change_form( $qpp_setup );
    $qpp = qpp_get_stored_options( $id );
    $content .= '
    <form method="post" action=""> 
    <p<span<b>Note:</b> Leave fields blank if you don\'t want to use them</span></p>
    <table>
    <tr>
    <td colspan="2"><h2>Form Width</h2></td>
    </tr>
    <tr>
    <td width="30%"></td>
    <td><input type="radio" name="widthtype" value="percent"' . esc_attr( $percent ) . ' /> 100% (fill the available space)<br />
    <input type="radio" name="widthtype" value="pixel"' . esc_attr( $pixel ) . ' /> Pixel (fixed): 
    <input type="text" style="width:4em" label="width" name="width" value="' . esc_attr( $style['width'] ) . '" /> use px, em or %. Default is px.</td>
    </tr>
    <tr>
    <td colspan="2"><h2>Form Border</h2>
    <p>Note: The rounded corners and shadows only work on CSS3 supported browsers and even then not in IE8. Don\'t blame me, blame Microsoft.</p></td
    </tr>
    <tr>
    <td>Type:</td>
    <td><input type="radio" name="border" value="none"' . esc_attr( $none ) . ' /> No border<br />
    <input type="radio" name="border" value="plain"' . esc_attr( $plain ) . ' /> Plain Border<br />
    <input type="radio" name="border" value="rounded"' . esc_attr( $rounded ) . ' /> Round Corners (Not IE8)<br />
    <input type="radio" name="border" value="shadow"' . esc_attr( $shadow ) . ' /> Shadowed Border(Not IE8)<br />
    <input type="radio" name="border" value="roundshadow"' . esc_attr( $roundshadow ) . ' /> Rounded Shadowed Border (Not IE8)</td>
    </tr>
    <tr>
    <td>Style:</td>
    <td><input type="text" label="form-border" name="form-border" value="' . esc_attr( $style['form-border'] ) . '" /></td>
    </tr>
    <tr>
    <td colspan="2"><h2>Background</h2></td>
    </tr>
    <tr>
    <td>Colour:</td>
    <td><input type="radio" name="background" value="white"' . esc_attr( $white ) . ' /> White<br />
    <input type="radio" name="background" value="theme"' . esc_attr( $theme ) . ' /> Use theme colours<br />
    <input style="margin-bottom:5px;" type="radio" name="background" value="color"' . esc_attr( $color ) . ' />
    <input type="text" class="qpp-color" label="background" name="backgroundhex" value="' . esc_attr( $style['backgroundhex'] ) . '" /></td>
    </tr>
    <tr><td>Background<br>Image:</td>
    <td>
    <input id="qpp_background_image" type="text" name="backgroundimage" value="' . esc_attr( $style['backgroundimage'] ) . '" />
    <input id="qpp_upload_background_image" class="button" type="button" value="Upload Image" /></td>
    </tr>
    <tr><td colspan="2"><h2>Font Styles</h2></td>
    </tr>
    <tr>
    <td></td>
    <td><input  type="radio" name="font" value="theme"' . esc_attr( $theme ) . ' /> Use theme font styles<br />
    <input  type="radio" name="font" value="plugin"' . esc_attr( $plugin ) . ' /> Use Plugin font styles (enter font family and size below)
    </td>
    </tr>
    <tr>
    <td colspan="2"><h2>Form Header</h2></td>
    </tr>
    <tr>
    <td style="vertical-align:top;">' . esc_html__( 'Header', 'quick-event-manager' ) . '</td>
    <td><input type="radio" name="header-type" value="h2"' . esc_attr( $h2 ) . ' /> H2 
    <input type="radio" name="header-type" value="h3"' . esc_attr( $h3 ) . ' /> H3 
    <input type="radio" name="header-type" value="h4"' . esc_attr( $h4 ) . ' /> H4</td>
    </tr>
    <tr>
    <td>Header Size:</td>
    <td><input type="text" style="width:6em" label="header-size" name="header-size" value="' . esc_attr( $style['header-size'] ) . '" /></td>
    </tr>
    <tr><td>Header Colour:</td>
    <td><input type="text" class="qpp-color" label="header-colour" name="header-colour" value="' . esc_attr( $style['header-colour'] ) . '" /></td>
    </tr>
    <tr>
    <td colspan="2"><h2>Field Label Locations</h2></td>
    <tr>
    <td colspan="2"><input type="radio" name="labeltype" value="tiny"' . esc_attr( $tiny ) . ' />
     ' . esc_html__( 'Reduce in size on focus', 'quick-interest-slider' ) . '&nbsp;&nbsp;&nbsp;
     <input type="radio" name="labeltype" value="hiding"' . esc_attr( $hiding ) . ' /> ' . esc_html__( 'Placeholders', 'quick-interest-slider' ) . '&nbsp;&nbsp;&nbsp;
     <input type="radio" name="labeltype" value="plain"' . esc_attr( $plain ) . ' /> ' . esc_html__( 'Above Input Fields', 'quick-interest-slider' ) . '</td>
    </tr>
    <tr>
    <td colspan="2"><h2>Input fields</h2></td>
    </tr>
    <tr>
    <td>Font Family: </td>
    <td><input type="text" label="font-family" name="font-family" value="' . esc_attr( $style['font-family'] ) . '" /></td>
    </tr>
    <tr>
    <td>Font Size: </td>
    <td><input type="text" label="font-size" name="font-size" value="' . esc_attr( $style['font-size'] ) . '" /></td>
    </tr>
    <tr>
    <td>Font Colour: </td>
    <td><input type="text" class="qpp-color" label="font-colour" name="font-colour" value="' . esc_attr( $style['font-colour'] ) . '" /></td
    </tr>
    <tr>
    <td>Normal Border: </td>
    <td><input type="text" label="input-border" name="input-border" value="' . esc_attr( $style['input-border'] ) . '" /></td>
    </tr>
    <tr>
    <td>Required Border: </td>
    <td><input type="text" name="required-border" value="' . esc_attr( $style['required-border'] ) . '" /></td>
    </tr>
    <tr>
    <td>Error Colour: </td>
    <td><input type="text" class="qpp-color" name="error-colour" value="' . esc_attr( $style['error-colour'] ) . '" /></td>
    </tr>
    <tr>
    <td>Corners: </td>
    <td><input type="radio" name="corners" value="corner"' . esc_attr( $corner ) . ' /> Use theme settings<br />
    <input type="radio" name="corners" value="square"' . esc_attr( $square ) . ' /> Square corners<br />
    <input type="radio" name="corners" value="round"' . esc_attr( $round ) . ' /> 5px rounded corners</td></tr>
    <tr>
    <td style="vertical-align:top;">' . esc_html__( 'Margins and Padding', 'quick-event-manager' ) . '</td>
    <td><span class="description">' . esc_html__( 'Set the margins and padding of each bit using CSS shortcodes', 'quick-contact-form' ) . ':</span><br>
    <input type="text" label="line margin" name="line_margin" value="' . esc_attr( $style['line_margin'] ) . '" /></td>
    </tr>
    <tr>';
    if ( $qpp['usecoupon'] ) {
        $content .= '<td colspan="2"><h2>Apply Coupon Button</h2></td>
    </tr>
    <tr>
    <td>Font Colour: </td>
    <td><input type="text" class="qpp-color" label="coupon-colour" name="coupon-colour" value="' . esc_attr( $style['coupon-colour'] ) . '" /></td>
    </tr>
    <tr>
    <td>Background: </td>
    <td><input type="text" class="qpp-color" label="coupon-background" name="coupon-background" value="' . esc_attr( $style['coupon-background'] ) . '" /><br>Other settings are the same as the Submit Button</td>
    </tr>';
    }
    $content .= '<tr>
    <td colspan="2"><h2>Other text content</h2></td>
    </tr>
    <tr>
    <td>Font Family: </td>
    <td><input type="text" label="text-font-family" name="text-font-family" value="' . esc_attr( $style['text-font-family'] ) . '" /></td>
    </tr>
    <tr>
    <td>Font Size: </td>
    <td><input type="text" style="width:6em" label="text-font-size" name="text-font-size" value="' . esc_attr( $style['text-font-size'] ) . '" /></td>
    </tr>
    <tr>
    <td>Font Colour: </td>
    <td><input type="text" class="qpp-color" label="text-font-colour" name="text-font-colour" value="' . esc_attr( $style['text-font-colour'] ) . '" /></td>
    </tr>
    <tr>
    <td colspan="2"><h2>Submit Button</h2></td>
    </tr>
    <tr>
    <td>Font Colour:</td>
    <td><input type="text" class="qpp-color" label="submit-colour" name="submit-colour" value="' . esc_attr( $style['submit-colour'] ) . '" /></td></tr>
    <tr>
    <td>Background:</td>
    <td><input type="text" class="qpp-color" label="submit-background" name="submit-background" value="' . esc_attr( $style['submit-background'] ) . '" /></td>
    </tr>
    <tr>
    <td>Hover: </td>
    <td><input type="text" class="qcf-color" label="submit-hover-background" name="submit-hover-background" value="' . esc_attr( $style['submit-hover-background'] ) . '" /></td>
    </tr>
    <tr>
    <td>Border:</td>
    <td><input type="text" label="submit-border" name="submit-border" value="' . esc_attr( $style['submit-border'] ) . '" /></td></tr>
    <tr>
    <td>Size:</td>
    <td><input type="radio" name="submitwidth" value="submitpercent"' . esc_attr( $submitpercent ) . ' /> Same width as the form<br />
    <input type="radio" name="submitwidth" value="submitrandom"' . esc_attr( $submitrandom ) . ' /> Same width as the button text<br />
    <input type="radio" name="submitwidth" value="submitpixel"' . esc_attr( $submitpixel ) . ' /> Set your own width: 
    <input type="text" style="width:5em" label="submitwidthset" name="submitwidthset" value="' . esc_attr( $style['submitwidthset'] ) . '" /> (px, % or em)</td></tr>
    <tr>
    <td>Position:</td>
    <td><input type="radio" name="submitposition" value="submitleft"' . esc_attr( $submitleft ) . ' /> Left 
    <input type="radio" name="submitposition" value="submitmiddle"' . esc_attr( $submitmiddle ) . ' /> Centre 
    <input type="radio" name="submitposition" value="submitright"' . esc_attr( $submitright ) . ' /> Right</td>
    </tr>
    <tr>
    <td>Button Image: </td><td>
    <input id="qpp_submit_button" type="text" name="submit-button" value="' . esc_attr( $style['submit-button'] ) . '" />
    <input id="qpp_upload_submit_button" class="button-secondary" type="button" value="Upload Image" /></td></tr>';
    if ( $qpp['use_slider'] ) {
        $content .= '<tr>
    <td colspan="2"><h2>Slider</h2></td>
    </tr>
    <tr>
    <td>Thickness</td>
    <td><input type="number" step = "0.125"  min="0.25" style="width:7em" label="input-border" name="slider-thickness" value="' . esc_attr( $style['slider-thickness'] ) . '" />em</td>
    </tr>
    <tr>
    <td>Normal Background</td>
    <td><input type="text" class="qpp-color" label="input-border" name="slider-background" value="' . esc_attr( $style['slider-background'] ) . '" /></td>
    </tr>
    <tr>
    <td>Revealed Background</td>
    <td><input type="text" class="qpp-color" label="input-border" name="slider-revealed" value="' . esc_attr( $style['slider-revealed'] ) . '" /></td>
    </tr>
    <tr>
    <td>Handle Background</td>
    <td><input type="text" class="qpp-color" label="input-border" name="handle-background" value="' . esc_attr( $style['handle-background'] ) . '" /></td>
    </tr>
    <tr>
    <td>Handle Border</td>
    <td><input type="text" class="qpp-color" label="input-border" name="handle-border" value="' . esc_attr( $style['handle-border'] ) . '" /></td>
    </tr>
    <tr>
    <td>Corners</td>
    <td><input type="number"  style="width:4em" name="handle-corners" value="' . esc_attr( $style['handle-corners'] ) . '" />&nbsp;%</td>
    </tr>
    <tr>
    <td>Output Size</td>
    <td><input type="text" style="width:5em" label="input-border" name="output-size" value="' . esc_attr( $style['output-size'] ) . '" /></td>
    </tr>
    <tr>
    <td>Output Colour</td>
    <td><input type="text" class="qpp-color" label="input-border" name="output-colour" value="' . esc_attr( $style['output-colour'] ) . '" /></td>
    </tr>';
    }
    $content .= '</table>

    <h2>Custom CSS</h2>
    <p><input  type="checkbox" style="margin:0; padding: 0; border: none" name="use_custom"' . checked( $style['use_custom'], 'checked', false ) . ' value="checked" /> Use Custom CSS</p>
    <p><textarea style="width:100%; height: 200px" name="custom">' . $style['custom'] . '</textarea></p>
    <p>The main style wrapper is the <code>.qpp-style</code> id.</p>
    <p>The form borders are: #none, #plain, #rounded, #shadow, #roundshadow.</p>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form styles?\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;"> <h2>Test Form</h2>
    <p>Not all of your style selections will display here (because of how WordPress works). So check the form on your site.</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args );
    $content .= '<p>There are some more examples of payment forms <a href="https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo  $content ;
}

function qpp_send_page( $id )
{
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global  $quick_paypal_payments_fs ;
    $AU = $AT = $BE = $BR = $pt_BR = $CA = $CH = $CN = $da_DK = $FR = $DE = $he_IL = $id_ID = $IT = $ja_JP = $NL = $no_NO = false;
    $PL = $PT = $RU = $ru_RU = $zh_CN = $zh_HK = $zh_TW = $ES = $sv_SE = $th_TH = $tr_TR = $GB = $US = false;
    qpp_change_form_update();
    
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'waiting',
            'use_lc',
            'lc',
            'image_url',
            'customurl',
            'cancelurl',
            'thanksurl',
            'target',
            'email',
            'donate',
            'combine',
            'confirmmessage',
            'google_onclick',
            'confirmemail',
            'createuser'
        );
        foreach ( $options as $item ) {
            $send[$item] = stripslashes( $_POST[$item] );
            $send[$item] = filter_var( $send[$item], FILTER_SANITIZE_STRING );
        }
        update_option( 'qpp_send' . $id, $send );
        qpp_admin_notice( "The submission settings have been updated." );
    }
    
    
    if ( isset( $_POST['Mailchimp'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'enable',
            'mailchimpoptin',
            'mailchimpkey',
            'mailchimplistid'
        );
        foreach ( $options as $item ) {
            $list[$item] = stripslashes( $_POST[$item] );
        }
        update_option( 'qpp_mailinglist', $list );
        qpp_admin_notice( "The mailinglist settings have been updated." );
    }
    
    
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_send' . $id );
        qpp_admin_notice( "The submission settings have been reset." );
    }
    
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $send = qpp_get_stored_send( $id );
    $newpage = $customurl = '';
    $list = qpp_get_stored_mailinglist();
    if ( isset( $send['target'] ) ) {
        ${$send['target']} = 'checked';
    }
    if ( isset( $send['lc'] ) ) {
        ${$send['lc']} = 'selected';
    }
    if ( empty(qpp_get_element( $send, 'confirmemail' )) ) {
        $send['confirmemail'] = get_bloginfo( 'admin_email' );
    }
    $content = qpp_head_css();
    $content .= '<div class="qpp-settings"><div class="qpp-options">';
    
    if ( $id ) {
        $content .= '<h2>Send settings for ' . $id . '</h2>';
    } else {
        $content .= '<h2>Default form send options</h2>';
    }
    
    $content .= qpp_change_form( $qpp_setup );
    $content .= '
    <form action="" method="POST">
    
    <h2>Submission Message</h2>
    <p>This is what the visitor sees while the paypal page loads</p>
    <input type="text" style="width:100%" name="waiting" value="' . esc_attr( $send['waiting'] ) . '" />
    
    <h2>Force Locale</h2>
    <p clsss="description">This may or may not work, Paypal has some very strange rule regarding language</p>
    <p><input  type="checkbox" name="use_lc"' . checked( $send['use_lc'], 'checked', false ) . ' value="checked" /> Use Locale</p>
    <select name="lc">
    <option value="AU" ' . $AU . '>Australia</option>
    <option value="AT" ' . $AT . '>Austria</option>
    <option value="BE" ' . $BE . '>Belgium</option>
    <option value="BR" ' . $BR . '>Brazil</option>
    <option value="pt_BR" ' . $pt_BR . '>Brazilian Portuguese (for Portugal and Brazil only)</option>
    <option value="CA" ' . $CA . '>Canada</option>
    <option value="CH" ' . $CH . '>Switzerland</option>
    <option value="CN" ' . $CN . '>China</option>
    <option value="da_DK" ' . $da_DK . '>Danish (for Denmark only)</option>
    <option value="FR" ' . $FR . '>France</option>
    <option value="DE" ' . $DE . '>Germany</option>
    <option value="he_IL" ' . $he_IL . '>Hebrew (all)</option>
    <option value="id_ID" ' . $id_ID . '>Indonesian (for Indonesia only)</option>
    <option value="IT" ' . $IT . '>Italy</option>
    <option value="ja_JP" ' . $ja_JP . '>Japanese (for Japan only)</option>
    <option value="NL" ' . $NL . '>Netherlands</option>
    <option value="no_NO" ' . $no_NO . '>Norwegian (for Norway only)</option>
    <option value="PL" ' . $PL . '>Poland</option>
    <option value="PT" ' . $PT . '>Portugal</option>
    <option value="RU" ' . $RU . '>Russia</option>
    <option value="ru_RU" ' . $ru_RU . '>Russian (for Lithuania, Latvia, and Ukraine only)</option>
    <option value="zh_CN" ' . $zh_CN . '>Simplified Chinese (for China only)</option>
    <option value="zh_HK" ' . $zh_HK . '>Traditional Chinese (for Hong Kong only)</option>
    <option value="zh_TW" ' . $zh_TW . '>Traditional Chinese (for Taiwan only)</option>
    <option value="ES" ' . $ES . '>Spain</option>
    <option value="sv_SE" ' . $sv_SE . '>Swedish (for Sweden only)</option>
    <option value="th_TH" ' . $th_TH . '>Thai (for Thailand only)</option>
    <option value="tr_TR" ' . $tr_TR . '>Turkish (for Turkey only)</option>
    <option value="GB" ' . $GB . '>United Kingdom</option>
    <option value="US" ' . $US . '>United States</option>
    </select>
    
    <h2>Cancel and Thank you pages</h2>
    <p>If you leave these blank paypal will return the user to the current page.</p>
    <p>URL of cancellation page</p>
    <input type="text" style="width:100%" name="cancelurl" value="' . esc_attr( qpp_get_element( $send, 'cancelurl' ) ) . '" />
    <p>URL of thank you page</p>
    <input type="text" style="width:100%" name="thanksurl" value="' . esc_attr( qpp_get_element( $send, 'thanksurl' ) ) . '" />
    <h2>Confirmation Message</h2>
    <p><input  type="checkbox" name="confirmmessage"' . checked( qpp_get_element( $send, 'confirmmessage' ), 'checked', false ) . ' value="checked" /> Send yourself a copy of the payment details.</p>
    <p><input type="text" style="width:100%" name="confirmemail" value="' . esc_attr( qpp_get_element( $send, 'confirmemail' ) ) . '" /></p>
    <p>You can send the payer a confirmation message using the <a href="?page=quick-paypal-payments&tab=autoresponce">Auto Responder</a> options.</p>
    
    <h2>Custom Paypal Settings</h2>
    <p><input  type="checkbox" name="donate"' . checked( qpp_get_element( $send, 'donate' ), 'checked', false ) . ' value="checked" /> Form is for donations only</p>
    <p><input  type="checkbox" name="combine"' . checked( qpp_get_element( $send, 'combine' ), 'checked', false ) . ' value="checked" /> Include Postage and Processing in the amount to pay.</p>
    <p>If you have a custom PayPal page enter the URL here. Leave blank to use the standard PayPal payment page</p>
    <p><input type="text" style="width:100%" name="customurl" value="' . esc_attr( qpp_get_element( $send, 'customurl' ) ) . '" /></p>
    <p>Alternate PayPal email address:</p>
    <p><input type="text" style="width:100%" name="email" value="' . esc_attr( qpp_get_element( $send, 'email' ) ) . '" /></p>
    <p><input type="radio" name="target" value="current"' . esc_attr( $current ) . ' /> Open in existing page<br>
    <input type="radio" name="target" value="newpage"' . esc_attr( $newpage ) . ' /> Open link in new page/tab <span class="description">This is very browser dependant. Use with caution!</span></p>
    
    <h2>Google onClick Event</h2>
    <p><input type="text" style="width:100%" name="google_onclick" value="' . esc_attr( qpp_get_element( $send, 'google_onclick' ) ) . '" /></p>
    
    <h2>Create New User</h2>
    <p><input  type="checkbox" name="createuser"' . checked( qpp_get_element( $send, 'createuser' ), 'checked', false ) . ' value="checked" /> Creates a new WordPress user when the form is submitted.</p>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form settings?\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>';
    $content .= '<h2>Mailchimp</h2>';
    global  $quick_paypal_payments_fs ;
    
    if ( $quick_paypal_payments_fs->is_trial() || $quick_paypal_payments_fs->is_trial_utilized() ) {
        $upurl = $quick_paypal_payments_fs->get_upgrade_url();
    } else {
        $upurl = $quick_paypal_payments_fs->get_trial_url();
    }
    
    $content .= '<p>Upgrade to Pro Platinum to use the Mailchimp data collection option. <a href="' . $upurl . '">Upgrade</a></p>';
    $content .= '</div>
    <div class="qpp-options" style="float:right;">
    
    <h2>Form Preview</h2>
    <p>Note: The preview form uses the wordpress admin styles. Your form will use the theme styles so won\'t look exactly like the one below.</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args );
    $content .= '<p>There are some more examples of payment forms <a href="https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo  $content ;
}

function qpp_error_page( $id )
{
    qpp_change_form_update();
    
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array( 'errortitle', 'errorblurb' );
        foreach ( $options as $item ) {
            $error[$item] = stripslashes( $_POST[$item] );
            $error[$item] = filter_var( $error[$item], FILTER_SANITIZE_STRING );
        }
        update_option( 'qpp_error' . $id, $error );
        qpp_admin_notice( "The error settings have been updated." );
    }
    
    
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_error' . $id );
        qpp_admin_notice( "The error messages have been reset." );
    }
    
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $error = qpp_get_stored_error( $id );
    $content = qpp_head_css();
    $content .= '<div class="qpp-settings"><div class="qpp-options">';
    
    if ( $id ) {
        $content .= '<h2>Eror message settings for ' . $id . '</h2>';
    } else {
        $content .= '<h2>Default form error message</h2>';
    }
    
    $content .= qpp_change_form( $qpp_setup );
    $content .= '<form method="post" action="">
    <p<span<b>Note:</b> Leave fields blank if you don\'t want to use them</span></p>
    <table>
    <tr>
    <td>Error header</td>
    <td><input type="text"  style="width:100%" name="errortitle" value="' . esc_attr( $error['errortitle'] ) . '" /></td>
    </tr>
    <tr>
    <td>Error message</td>
    <td><input type="text" style="width:100%" name="errorblurb" value="' . esc_attr( $error['errorblurb'] ) . '" /></td>
    </tr>
    </table>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the error message?\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>Error Checker</h2>
    <p>Try sending a blank form to test your error messages.</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args );
    $content .= '<p>There are some more examples of payment forms <a href="https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo  $content ;
}

function qpp_ipn_page()
{
    
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'ipn',
            'paid',
            'title',
            'listener',
            'deleterecord'
        );
        foreach ( $options as $item ) {
            $ipn[$item] = stripslashes( $_POST[$item] );
            $ipn[$item] = filter_var( $ipn[$item], FILTER_SANITIZE_STRING );
        }
        update_option( 'qpp_ipn', $ipn );
        qpp_admin_notice( "The IPN settings have been updated." );
    }
    
    
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_ipn' );
        qpp_admin_notice( "The IPN settings have been reset." );
    }
    
    $ipn = qpp_get_stored_ipn();
    $content = '<div class="qpp-settings"><div class="qpp-options">
	<h2>Instant Payment Notifications</h2>
    <p><b>Note:</b> IPN only works if you have a PayPal Business or Premier account and IPN has been set up on that account.</p>
    <p>See the <a href="https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNSetup/">PayPal IPN Integration Guide</a> for more information on how to set up IPN.</p>
	<form method="post" action="">
    <table>
    <tr>
    <td><input  type="checkbox" name="ipn"' . checked( $ipn['ipn'], 'checked', false ) . ' value="checked" /></td>
    <td colspan="2"> Enable IPN.</td>
    </tr>
    <tr>
    <td></td>
    <td width ="40%">Payment Report Column header:</td>
    <td><input type="text"  style="width:100%" name="title" value="' . esc_attr( $ipn['title'] ) . '" /></td>
    </tr>
    <tr>
    <td></td>
    <td>Payment Complete Label:</td>
    <td><input type="text"  style="width:100%" name="paid" value="' . esc_attr( $ipn['paid'] ) . '" /></td>
    </tr>
    <tr>
    <td></td>
    <td>Third Party Listener URL (optional: advanced):</td>
    <td><input type="text"  style="width:100%" name="listener" value="' . esc_attr( qpp_get_element( $ipn, 'listener' ) ) . '" /></td>
    </tr>
    <tr>
    <td><input  type="checkbox" name="deleterecord"' . checked( qpp_get_element( $ipn, 'deleterecord' ), 'checked', false ) . ' value="checked" /></td>
    <td colspan="2"> Delete record after payment.</td>
    </tr>
    </table>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the IPN settings?\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    <p>If you set a Listener URL above this plugin will not automatically handle IPN\'s, this is for advanced usage e.g. split IPN handling. If you haven\'t set an IPN listener URL above this is the one you need to get payment confimration:<pre>' . site_url( '/?qpp_ipn' ) . '</pre></p>
    <p>To check completed payments click on the <b>Payments</b> link in your dashboard menu or <a href="?page=quick-paypal-payments-messages">click here</a>.</p>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>IPN Simulation</h2>
    <p>IPN can be blocked or resticted by your server settings, theme or other plugins. The good news is you can simulate the notifications to check if all is working.</p>
    <p>To carry out a simulation:</p>
    <ol>
    <li>Enable the PayPal Sandbox on the <a href="?page=quick-paypal-payments&tab=setup">plugin setup page</a></li>
    <li>Fill in and send your payment form (you do not need to make an actual payment)</li>
    <li>Go to the <a href="?page=quick-paypal-payments-messages">Payments Report</a> and copy the long number in the last column from the payment you have just made</li>
    <li>Go to the IPN simulation page: <a href="https://developer.paypal.com/developer/ipnSimulator" target="_blank">https://developer.paypal.com/developer/ipnSimulator</a></li>
    <li>Login and enter the IPN listener URL</li>
    <li>Select \'Express Checkout\' from the drop down</li>
    <li>Scroll to the bottom of the page and enter the long number you copied at step 3 into the \'Custom\' field</li>
    <li>Click \'Send IPN\'. Scroll up the page and you should see an \'IPN Verified\' message.</li>
    <li>Go back to your Payments Report and refresh, you should now see the payment completed message</li>
    </ol>
    </div>
    </div>';
    echo  $content ;
}

function qpp_autoresponce_page( $id )
{
    qpp_change_form_update();
    $afterpayment = $aftersubmission = false;
    
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'enable',
            'whenconfirm',
            'fromname',
            'fromemail',
            'subject',
            'message',
            'paymentdetails'
        );
        foreach ( $options as $item ) {
            $auto[$item] = stripslashes( $_POST[$item] );
        }
        update_option( 'qpp_autoresponder' . $id, $auto );
        
        if ( $id ) {
            qpp_admin_notice( "The autoresponder settings for " . $id . " have been updated." );
        } else {
            qpp_admin_notice( "The default form autoresponder settings have been updated." );
        }
    
    }
    
    
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_autoresponder' . $id );
        qpp_admin_notice( "The autoresponder settings for the form called " . $id . " have been reset." );
    }
    
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $qpp = qpp_get_stored_options( $id );
    $auto = qpp_get_stored_autoresponder( $id );
    ${$auto['whenconfirm']} = 'checked';
    $message = $auto['message'];
    $content = '<div class="qpp-settings"><div class="qpp-options" style="width:90%;">';
    
    if ( $id ) {
        $content .= '<h2 style="color:#B52C00">Autoresponse settings for ' . $id . '</h2>';
    } else {
        $content .= '<h2 style="color:#B52C00">Default form autoresponse settings</h2>';
    }
    
    $content .= qpp_change_form( $qpp_setup );
    $content .= '<p>The auto responder sends a confirmation message to the Payer. Use the editor below to send links, images and anything else you normally add to a post or page.</p>
    <p class="description">Note that the autoresponder only works if you collect an email address on the <a href="?page=quick-paypal-payments&tab=settings">Form Settings</a>.</p>
    <p class="description">If you want to receive notificationmessages use the option on the <a href="?page=quick-paypal-payments&tab=send">Send Options</a> tab.</p>
    <form method="post" action="">
    <p><input type="checkbox" name="enable"' . checked( $auto['enable'], 'checked', false ) . ' value="checked" /> Enable Auto Responder</p> 
    <p><input type="radio" name="whenconfirm" value="aftersubmission"' . checked( $aftersubmission, 'aftersubmission', false ) . ' /> After submission to PayPal<br>
    <input type="radio" name="whenconfirm" value="afterpayment"' . checked( $afterpayment, 'afterpayment', false ) . ' /> After payment (only works if <a href="?page=quick-paypal-payments&tab=ipn">IPN</a> is enabled)</span></p>
    <p>From Name (<span class="description">Defaults to your <a href="' . get_admin_url() . 'options-general.php">Site Title</a> if left blank.</span>):<br>
    <input type="text" style="width:50%" name="fromname" value="' . esc_attr( $auto['fromname'] ) . '" /></p>
    <p>From Email (<span class="description">Defaults to the your <a href="?page=quick-paypal-payments&tab=setup">PayPal email address</a> if left blank.</span>):<br>
    <input type="text" style="width:50%" name="fromemail" value="' . esc_attr( $auto['fromemail'] ) . '" /></p>
    <p>Subject</p>
    <input style="width:100%" type="text" name="subject" value="' . esc_attr( $auto['subject'] ) . '"/><br>
    <p>Message Content</p>';
    echo  $content ;
    wp_editor( $message, 'message', $settings = array(
        'textarea_rows' => '20',
        'wpautop'       => false,
    ) );
    $content = '<p>You can use the following shortcodes in the message body:</p>
    <table>
    <tr>
    <th>Shortcode</th>
    <th>Replacement Text</th>
    </tr>
    <tr>
    <td>[firstname]</td>
    <td>The registrants first name if you are using the <a href="?page=quick-paypal-payments&tab=address">personal details</a> option.</td>
    </tr>
    <tr>
    <td>[name]</td>
    <td>The registrants first and last name if you are using the <a href="?page=quick-paypal-payments&tab=address">personal details</a> option.</td>
    </tr>
    <tr>
    <td>[reference]</td>
    <td>The name of the item being purchased</td>
    </tr>
    <tr>
    <td>[amount]</td>
    <td>The total amount to be paid without the currency symbol</td>
    </tr>
    <tr>
    <td>[fullamount]</td>
    <td>The total amount to be paid with currency symbol</td>
    </tr>
    <tr>
    <td>[quantity]</td>
    <td>The number of items purchased</td>
    </tr>
    <tr>
    <td>[option]</td>
    <td>The option selected</td>
    </tr>
    <tr>
    <td>[stock]</td>
    <td>The stock, SKU or item number</td>
    </tr>
    <tr>
    <td>[details]</td>
    <td>The payment information (reference, quantity, options, stock number, amount)</td>
    </tr>
    <tr>
    <td>[multiple]</td>
    <td>A table with the names and quantities of each item ordered (pro version only)</td>
    </tr>
    </table>
    <p><input type="checkbox" name="paymentdetails"' . checked( $auto['paymentdetails'], 'checked', false ) . ' value="checked" /> Add payment details to the message</p> 
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the error settings for ' . $id . '?\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    </div>';
    echo  $content ;
}

function qpp_address( $id )
{
    qpp_change_form_update();
    
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'useaddress',
            'firstname',
            'lastname',
            'email',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b',
            'rfirstname',
            'rlastname',
            'remail',
            'raddress1',
            'raddress2',
            'rcity',
            'rstate',
            'rzip',
            'rcountry',
            'rnight_phone_b',
            'permitted_country',
            'default_country'
        );
        foreach ( $options as $item ) {
            $address[$item] = ( is_array( $_POST[$item] ) ? array_map( 'esc_attr', $_POST[$item] ) : esc_attr( $_POST[$item] ) );
        }
        update_option( 'qpp_address' . $id, $address );
        qpp_admin_notice( "The form settings have been updated." );
    }
    
    
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_error' . $id );
        qpp_admin_notice( "The form settings have been reset." );
    }
    
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $address = qpp_get_stored_address( $id );
    $content = '<div class="qpp-settings"><div class="qpp-options">';
    
    if ( $id ) {
        $content .= '<h2>Personal Information Fields for ' . $id . '</h2>';
    } else {
        $content .= '<h2>Personal Information Fields</h2>';
    }
    
    $content .= qpp_change_form( $qpp_setup );
    $ccodes = Utilities::get_instance()->get_paypal_locales();
    $default_countries = '';
    foreach ( $ccodes as $code => $data ) {
        $sel = '';
        if ( $code == $address['default_country'] ) {
            $sel = 'selected';
        }
        $default_countries .= '<option value="' . esc_attr( $code ) . '" ' . $sel . '>' . $data['region'] . '</option>';
    }
    $permitted_countries = '';
    foreach ( $ccodes as $code => $data ) {
        $sel = '';
        if ( isset( $address['permitted_country'] ) && is_array( $address['permitted_country'] ) && in_array( $code, $address['permitted_country'] ) ) {
            $sel = 'selected';
        }
        $permitted_countries .= '<option value="' . esc_attr( $code ) . '" ' . $sel . '>' . $data['region'] . '</option>';
    }
    $content .= '<form method="post" action="">
    <p class="description">Note: The information will be collected and saved and passed to PayPal but usage is dependant on browser and user settings. Which means they may have to fill in the information again when they get to PayPal</p>
    <p>1. Delete labels for fields you do not want to use.</p>
    <p>2. Check the <b>R</b> box for madatory/required fields.</p>
    <table>
    <tr>
    
    <th>Field</th>
    <th>Label</th>
    <th>R</th>
    </tr>
    <tr>
    
    <td width="20%">First Name</td>
    <td><input type="text"  style="width:100%" name="firstname" value="' . esc_attr( $address['firstname'] ) . '" /></td>
    <td width="5%"><input  type="checkbox" name="rfirstname"' . checked( $address['rfirstname'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Last Name</td>
    <td><input type="text"  style="width:100%" name="lastname" value="' . esc_attr( $address['lastname'] ) . '" /></td>
    <td><input  type="checkbox" name="rlastname"' . checked( $address['rlastname'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Email</td>
    <td><input type="text" style="width:100%" name="email" value="' . esc_attr( $address['email'] ) . '" /></td>
    <td><input  type="checkbox" name="remail"' . checked( $address['remail'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Address Line 1</td>
    <td><input type="text" style="width:100%" name="address1" value="' . esc_attr( $address['address1'] ) . '" /></td>
    <td><input  type="checkbox" name="raddress1"' . checked( $address['raddress1'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Address Line 2</td>
    <td><input type="text" style="width:100%" name="address2" value="' . esc_attr( $address['address2'] ) . '" /></td>
    <td><input  type="checkbox" name="raddress2"' . checked( $address['raddress2'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>City</td>
    <td><input type="text" style="width:100%" name="city" value="' . esc_attr( $address['city'] ) . '" /></td>
    <td><input  type="checkbox" name="rcity"' . checked( $address['rcity'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>State</td>
    <td><input type="text" style="width:100%" name="state" value="' . esc_attr( $address['state'] ) . '" /></td>
    <td><input  type="checkbox" name="rstate"' . checked( $address['rstate'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Zip</td>
    <td><input type="text" style="width:100%" name="zip" value="' . esc_attr( $address['zip'] ) . '" /></td>
    <td><input  type="checkbox" name="rzip"' . checked( $address['rzip'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Country</td>
    <td><input type="text" style="width:100%" name="country" value="' . esc_attr( $address['country'] ) . '" /></td>
    <td><input  type="checkbox" name="rcountry"' . checked( $address['rcountry'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>Default / Permitted Countries</td>
    <td><select style="width:40%" name="default_country">
							<option value="" disabled selected>' . esc_html__( 'Default Country', 'quick-paypal-payments' ) . '</option>' . esc_html( $default_countries ) . '
							
    </select><select style="width:50%; min-height: 200px;" name="permitted_country[]" multiple>
							<option value="" disabled selected>' . esc_html__( 'Permitted ( Multi-select)', 'quick-paypal-payments' ) . '</option>' . esc_html( $permitted_countries ) . '
							
    </select></td>
    
    <tr>
    
    <td>Phone</td>
    <td><input type="text" style="width:100%" name="night_phone_b" value="' . esc_attr( $address['night_phone_b'] ) . '" /></td>
    <td><input  type="checkbox" name="rnight_phone_b"' . checked( $address['rnight_phone_b'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    </table>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the error message?\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>Example Form</h2>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args );
    $content .= '<p>There are some more examples of payment forms <a href="https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo  $content ;
}

function qpp_coupon_codes( $id )
{
    qpp_change_form_update();
    
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $arr = array(
            'couponnumber',
            'couponget',
            'duplicate',
            'couponerror',
            'couponexpired'
        );
        foreach ( $arr as $item ) {
            
            if ( isset( $_POST[$item] ) ) {
                $coupon[$item] = stripslashes( $_POST[$item] );
                $coupon[$item] = filter_var( $coupon[$item], FILTER_SANITIZE_STRING );
            }
        
        }
        $options = array(
            'code',
            'coupontype',
            'couponpercent',
            'couponfixed',
            'qty',
            'expired'
        );
        if ( $coupon['couponnumber'] < 1 ) {
            $coupon['couponnumber'] = 1;
        }
        for ( $i = 1 ;  $i <= $coupon['couponnumber'] ;  $i++ ) {
            foreach ( $options as $item ) {
                
                if ( isset( $_POST[$item . $i] ) ) {
                    $coupon[$item . $i] = stripslashes( sanitize_text_field( $_POST[$item . $i] ) );
                } else {
                    $coupon[$item . $i] = '';
                }
            
            }
            if ( $coupon['qty' . $i] > 0 || $coupon['qty' . $i] === '' ) {
                $coupon['expired' . $i] = false;
            }
            if ( !$coupon['coupontype' . $i] ) {
                $coupon['coupontype' . $i] = 'percent' . $i;
            }
            if ( !$coupon['couponpercent' . $i] ) {
                $coupon['couponpercent' . $i] = '10';
            }
            if ( !$coupon['couponfixed' . $i] ) {
                $coupon['couponfixed' . $i] = '5';
            }
        }
        update_option( 'qpp_coupon' . $id, $coupon );
        
        if ( isset( $coupon['duplicate'] ) && $coupon['duplicate'] ) {
            $qpp_setup = qpp_get_stored_setup();
            $arr = explode( ",", $qpp_setup['alternative'] );
            foreach ( $arr as $item ) {
                update_option( 'qpp_coupon' . $item, $coupon );
            }
        }
        
        qpp_admin_notice( "The coupon settings have been updated." );
    }
    
    
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_coupon' . $id );
        qpp_admin_notice( "The coupon settings have been reset." );
    }
    
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $currency = qpp_get_stored_curr();
    $before = array(
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
        'TRY' => '&pound;',
    );
    $after = array(
        'CZK' => 'K&#269;',
        'DKK' => 'Kr',
        'HUF' => 'Ft',
        'MYR' => 'RM',
        'NOK' => 'kr',
        'PLN' => 'z&#322',
        'RUB' => '&#1056;&#1091;&#1073;',
        'SEK' => 'kr',
        'CHF' => 'CHF',
        'THB' => '&#3647;',
    );
    $b = '';
    foreach ( $before as $item => $key ) {
        if ( $item == $currency[$id] ) {
            $b = $key;
        }
    }
    $a = '';
    foreach ( $after as $item => $key ) {
        if ( $item == $currency[$id] ) {
            $a = $key;
        }
    }
    $coupon = qpp_get_stored_coupon( $id );
    $content = '<div class="qpp-settings"><div class="qpp-options">';
    
    if ( $id ) {
        $content .= '<h2>Coupons codes for ' . $id . '</h2>';
    } else {
        $content .= '<h2>Default form coupons codes</h2>';
    }
    
    $content .= qpp_change_form( $qpp_setup );
    $content .= '<form method="post" action="">
    <p<span<b>Note:</b> Leave fields blank if you don\'t want to use them</span></p>
    <p>Number of Coupons: <input type="text" name="couponnumber" value="' . esc_attr( $coupon['couponnumber'] ) . '" style="width:4em"></p>
    <table>
    <tr><td>Code</td><td>Percentage</td><td>Fixed Amount</td><td>Qty<br>(remaining <br>/ blank unlimited)</td></tr>';
    for ( $i = 1 ;  $i <= $coupon['couponnumber'] ;  $i++ ) {
        $percent = ( $coupon['coupontype' . $i] == 'percent' . $i ? 'checked' : '' );
        $fixed = ( $coupon['coupontype' . $i] == 'fixed' . $i ? 'checked' : '' );
        $content .= '<tr><td><input placeholder="Enter Coupon Code" type="text" name="code' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'code' . $i ) ) . '" /></td>
        <td><input type="radio" name="coupontype' . $i . '" value="percent' . $i . '" ' . esc_attr( $percent ) . ' />&nbsp;
        <input type="text" style="width:4em;padding:2px" label="couponpercent' . $i . '" name="couponpercent' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'couponpercent' . $i ) ) . '" /> %</td>
        <td><input type="radio" name="coupontype' . $i . '" value="fixed' . $i . '" ' . esc_attr( $fixed ) . ' />&nbsp;' . $b . '&nbsp;
        <input type="text" style="width:4em;padding:2px" label="couponfixed' . $i . '" name="couponfixed' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'couponfixed' . $i ) ) . '" /> ' . $a . '</td>
        <td><input type="text" style="width:3em;padding:2px" name="qty' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'qty' . $i ) ) . '" /></td>
                    <td><input type="checkbox" name="expired' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'expired' . $i ) ) . '" ' . checked( qpp_get_element( $coupon, 'expired' . $i ), '1', false ) . ' /></td></tr>
    </tr>';
    }
    $content .= '</table>
    <h2>Invalid Coupon Code Message</h2>
    <input id="couponerror" type="text" name="couponerror" value="' . esc_attr( $coupon['couponerror'] ) . '" /></p>
    <h2>Expired Coupon Message</h2>
    <input id="couponexpired" type="text" name="couponexpired" value="' . esc_attr( $coupon['couponexpired'] ) . '" /></p>
    <h2>Coupon Code Autofill</h2>
    <p>You can add coupon codes to URLs which will autofill the field. The URL format is: mysite.com/mypaymentpage/?coupon=code. The code you set will appear on the form with the following caption:<br>
    <input id="couponget" type="text" name="couponget" value="' . esc_attr( $coupon['couponget'] ) . '" /></p>
    <h2>Clone Coupon Settings</h2>
    <p><input  type="checkbox" name="duplicate"' . checked( $coupon['duplicate'], 'checked', false ) . ' value="checked" /> Duplicate coupon codes across all forms</p>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the coupon codes?\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>Coupon Check</h2>
    <p>Test your coupon codes.</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args );
    $content .= '<p>There are some more examples of payment forms <a href="https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/" target="_blank">on this page</a>.</p>
    <p>And there are loads of shortcode options <a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">on this page</a>.</p>
    </div></div>';
    echo  $content ;
}

function qpp_delete_everything()
{
    $qpp_setup = qpp_get_stored_setup();
    $arr = explode( ",", $qpp_setup['alternative'] );
    foreach ( $arr as $item ) {
        qpp_delete_things( $item );
    }
    qpp_delete_things( '' );
    delete_option( 'qpp_setup' );
    delete_option( 'qpp_curr' );
    delete_option( 'qpp_message' );
}

function qpp_delete_things( $id )
{
    delete_option( 'qpp_options' . $id );
    delete_option( 'qpp_send' . $id );
    delete_option( 'qpp_error' . $id );
    delete_option( 'qpp_style' . $id );
}

function qpp_change_form( $qpp_setup )
{
    $content = '';
    
    if ( $qpp_setup['alternative'] ) {
        $content .= '<form style="margin-top: 8px" method="post" action="" >';
        $arr = explode( ",", $qpp_setup['alternative'] );
        sort( $arr );
        foreach ( $arr as $item ) {
            
            if ( $qpp_setup['current'] == $item ) {
                $checked = 'checked';
            } else {
                $checked = '';
            }
            
            
            if ( $item == '' ) {
                $formname = 'default';
                $item = '';
            } else {
                $formname = $item;
            }
            
            $content .= '<input  type="radio" name="current" value="' . esc_attr( $item ) . '" ' . checked( $item, $qpp_setup['current'], false ) . '>' . esc_attr( $formname ) . ' &nbsp;';
        }
        $content .= wp_nonce_field(
            'qpp_save',
            '_wpnonce',
            true,
            false
        );
        $content .= '<input type="hidden" name="alternative" value = "' . esc_attr( $qpp_setup['alternative'] ) . '" />
        <input type="hidden" name="email" value = "' . esc_attr( $qpp_setup['email'] ) . '" />&nbsp;&nbsp;
        <input type="submit" name="Select" class="button-secondary" value="Select Form" />
        </form>';
    }
    
    return $content;
}

function qpp_change_form_update()
{
    
    if ( isset( $_POST['Select'] ) ) {
        check_admin_referer( 'qpp_save' );
        $qpp_setup = qpp_get_stored_setup();
        $qpp_setup['current'] = sanitize_text_field( $_POST['current'] );
        $qpp_setup['alternative'] = sanitize_text_field( $_POST['alternative'] );
        $qpp_setup['email'] = sanitize_text_field( $_POST['email'] );
        update_option( 'qpp_setup', $qpp_setup );
    }

}

function qpp_generate_csv()
{
    $qpp_setup = qpp_get_stored_setup();
    $ipn = qpp_get_stored_ipn();
    
    if ( isset( $_POST['download_qpp_csv'] ) ) {
        check_admin_referer( 'qpp_download_form', 'qpp_download_form_nonce' );
        $id = $_POST['formname'];
        $filename = urlencode( $id . '.csv' );
        if ( $id == '' ) {
            $filename = urlencode( 'default.csv' );
        }
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Type: text/csv' );
        $outstream = fopen( "php://output", 'w' );
        $message = get_option( 'qpp_messages' . $id );
        $messageoptions = qpp_get_stored_msg();
        if ( !is_array( $message ) ) {
            $message = array();
        }
        $qpp = qpp_get_stored_options( $id );
        $address = qpp_get_stored_address( $id );
        $headerrow = array();
        array_push( $headerrow, esc_html__( 'Date Sent', 'quick-paypal-payments' ) );
        array_push( $headerrow, $qpp['inputreference'] );
        array_push( $headerrow, $qpp['quantitylabel'] );
        array_push( $headerrow, esc_html__( 'Amount', 'quick-paypal-payments' ) );
        if ( $qpp['use_stock'] ) {
            array_push( $headerrow, $qpp['stocklabel'] );
        }
        if ( $qpp['use_options'] ) {
            array_push( $headerrow, $qpp['optionlabel'] );
        }
        if ( $qpp['usecoupon'] ) {
            array_push( $headerrow, $qpp['couponblurb'] );
        }
        if ( $qpp['use_message'] ) {
            array_push( $headerrow, $qpp['messagelabel'] );
        }
        
        if ( $messageoptions['showaddress'] ) {
            if ( $address['email'] ) {
                array_push( $headerrow, $address['email'] );
            }
            if ( $address['firstname'] ) {
                array_push( $headerrow, $address['firstname'] );
            }
            if ( $address['lastname'] ) {
                array_push( $headerrow, $address['lastname'] );
            }
            if ( $address['address1'] ) {
                array_push( $headerrow, $address['address1'] );
            }
            if ( $address['address2'] ) {
                array_push( $headerrow, $address['address2'] );
            }
            if ( $address['city'] ) {
                array_push( $headerrow, $address['city'] );
            }
            if ( $address['state'] ) {
                array_push( $headerrow, $address['state'] );
            }
            if ( $address['zip'] ) {
                array_push( $headerrow, $address['zip'] );
            }
            if ( $address['country'] ) {
                array_push( $headerrow, $address['country'] );
            }
            if ( $address['night_phone_b'] ) {
                array_push( $headerrow, $address['night_phone_b'] );
            }
        }
        
        if ( $ipn['ipn'] ) {
            array_push( $headerrow, 'Paid' );
        }
        fputcsv(
            $outstream,
            $headerrow,
            ',',
            '"'
        );
        foreach ( array_reverse( $message ) as $value ) {
            $cells = array();
            array_push( $cells, $value['field0'] );
            array_push( $cells, $value['field1'] );
            array_push( $cells, $value['field2'] );
            array_push( $cells, $value['field3'] );
            
            if ( $qpp['use_stock'] ) {
                $value['field4'] = ( $value['field4'] != $value['stocklabel'] ? $value['field4'] : '' );
                array_push( $cells, $value['field4'] );
            }
            
            
            if ( $qpp['use_options'] ) {
                $value['field5'] = ( $value['field5'] != $value['optionlabel'] ? $value['field5'] : '' );
                array_push( $cells, $value['field5'] );
            }
            
            
            if ( $qpp['usecoupon'] ) {
                $value['field6'] = ( $value['field6'] != $value['couponblurb'] ? $value['field6'] : '' );
                array_push( $cells, $value['field6'] );
            }
            
            
            if ( $qpp['use_message'] ) {
                $value['field19'] = ( $value['field19'] != $value['messagelabel'] ? $value['field19'] : '' );
                array_push( $cells, $value['field19'] );
            }
            
            
            if ( $messageoptions['showaddress'] ) {
                
                if ( $address['email'] ) {
                    $value['field8'] = ( $value['field8'] != $address['email'] ? $value['field8'] : '' );
                    array_push( $cells, $value['field8'] );
                }
                
                
                if ( $address['firstname'] ) {
                    $value['field9'] = ( $value['field9'] != $address['firstname'] ? $value['field9'] : '' );
                    array_push( $cells, $value['field9'] );
                }
                
                
                if ( $address['lastname'] ) {
                    $value['field10'] = ( $value['field10'] != $address['lastname'] ? $value['field10'] : '' );
                    array_push( $cells, $value['field10'] );
                }
                
                
                if ( $address['address1'] ) {
                    $value['field11'] = ( $value['field11'] != $address['address1'] ? $value['field11'] : '' );
                    array_push( $cells, $value['field11'] );
                }
                
                
                if ( $address['address2'] ) {
                    $value['field12'] = ( $value['field12'] != $address['address2'] ? $value['field12'] : '' );
                    array_push( $cells, $value['field12'] );
                }
                
                
                if ( $address['city'] ) {
                    $value['field13'] = ( $value['field13'] != $address['city'] ? $value['field13'] : '' );
                    array_push( $cells, $value['field13'] );
                }
                
                
                if ( $address['state'] ) {
                    $value['field14'] = ( $value['field14'] != $address['state'] ? $value['field14'] : '' );
                    array_push( $cells, $value['field14'] );
                }
                
                
                if ( $address['zip'] ) {
                    $value['field15'] = ( $value['field15'] != $address['zip'] ? $value['field15'] : '' );
                    array_push( $cells, $value['field15'] );
                }
                
                
                if ( $address['country'] ) {
                    $value['field16'] = ( $value['field16'] != $address['country'] ? $value['field16'] : '' );
                    array_push( $cells, $value['field16'] );
                }
                
                
                if ( $address['night_phone_b'] ) {
                    $value['field17'] = ( $value['field17'] != $address['night_phone_b'] ? $value['field17'] : '' );
                    array_push( $cells, $value['field17'] );
                }
            
            }
            
            
            if ( $ipn['ipn'] ) {
                $paid = ( $value['field18'] == 'Paid' ? 'Paid' : '' );
                array_push( $cells, $paid );
            }
            
            fputcsv(
                $outstream,
                $cells,
                ',',
                '"'
            );
        }
        fclose( $outstream );
        exit;
    }

}

function qpp_settings_init()
{
    qpp_generate_csv();
    return;
}

function qpp_scripts_init( $hook )
{
    if ( $hook != 'settings_page_quick-paypal-payments' && $hook != 'toplevel_page_quick-paypal-payments-messages' ) {
        return;
    }
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
    wp_enqueue_style( 'qpp_settings', plugins_url( 'settings.css', __FILE__ ) );
    wp_enqueue_style( 'qpp_style', plugins_url( 'payments.css', __FILE__ ) );
    wp_enqueue_media();
    wp_enqueue_script(
        'qpp-media',
        plugins_url( 'media.js', __FILE__ ),
        array( 'jquery', 'wp-color-picker' ),
        false,
        true
    );
    wp_enqueue_script( 'qpp_script', plugins_url( 'payments.js', __FILE__ ) );
}

add_action( 'admin_enqueue_scripts', 'qpp_scripts_init' );
function qpp_page_init()
{
    add_options_page(
        'Paypal Payments',
        'Paypal Payments',
        'manage_options',
        'quick-paypal-payments',
        'qpp_tabbed_page'
    );
}

function qpp_admin_notice( $message = '' )
{
    if ( !empty($message) ) {
        echo  '<div class="updated"><p>' . $message . '</p></div>' ;
    }
}

function qpp_admin_pages()
{
    add_menu_page(
        'Payments',
        'Payments',
        'manage_options',
        'quick-paypal-payments-messages',
        function () {
        require_once 'messages.php';
    },
        'dashicons-cart'
    );
}

function qpp_plugin_row_meta( $links, $file = '' )
{
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global  $quick_paypal_payments_fs ;
    
    if ( false !== strpos( $file, '/quick-paypal-payments.php' ) ) {
        $new_links[] = '<a href="https://fullworks.net/docs/quick-paypal-payments/"><strong>Help and Support</strong></a>';
        
        if ( false === $quick_paypal_payments_fs->is_plan_or_trial( 'platinum' ) ) {
            global  $quick_paypal_payments_fs ;
            
            if ( $quick_paypal_payments_fs->is_trial() || $quick_paypal_payments_fs->is_trial_utilized() ) {
                $upurl = $quick_paypal_payments_fs->get_upgrade_url();
                $upmsg = 'Upgrade to Pro Platinum';
                $new_links[] = '<a href="' . $upurl . '"><strong>' . $upmsg . '</strong></a>';
            } else {
                $upurl = $quick_paypal_payments_fs->get_trial_url();
                $upmsg = 'Pro Platinum: Free 14 Day Trial';
                $new_links[] = '<a href="' . $upurl . '"><strong>' . $upmsg . '</strong></a>';
            }
        
        }
        
        $links = array_merge( $links, $new_links );
    }
    
    return $links;
}
