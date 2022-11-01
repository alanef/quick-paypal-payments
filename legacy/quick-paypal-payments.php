<?php

/*
	Register the scripts we need
*/
use  Quick_Paypal_Payments\Core\Utilities ;
include 'WPHttp.class.php';
function qpp_shutdown()
{
    $error = error_get_last();
}

register_shutdown_function( 'qpp_shutdown' );
add_action( 'init', 'qpp_register_scripts' );
/*
	Add footer event to fire and include the javascript file only when needed
*/
add_action( 'wp_footer', 'qpp_display_scripts' );
add_shortcode( 'qpp', 'qpp_loop' );
add_shortcode( 'qppreport', 'qpp_report' );
add_filter(
    'plugin_action_links',
    'qpp_plugin_action_links',
    10,
    2
);
add_action( 'wp_enqueue_scripts', 'qpp_enqueue_scripts' );
add_action( 'template_redirect', 'qpp_ipn' );
add_action( 'wp_head', 'qpp_head_css' );
add_action( 'wp_ajax_qpp_validate_form', 'qpp_validate_form_callback' );
add_action( 'wp_ajax_nopriv_qpp_validate_form', 'qpp_validate_form_callback' );
add_action( 'wp_ajax_qpp_process_payment', 'qpp_process_payment' );
add_action( 'wp_ajax_nopriv_qpp_process_express_checkout_payment', 'qpp_process_express_checkout_payment' );
$qpp_end_loop = false;
$qpp_current_custom = '';
$qpp_attributes = array();
require_once plugin_dir_path( __FILE__ ) . '/options.php';
require_once plugin_dir_path( __FILE__ ) . '/mailchimp/mailchimp.init.php';
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . '/settings.php';
}
function qpp_block_init()
{
    if ( !function_exists( 'register_block_type' ) ) {
        return;
    }
    // Register our block editor script.
    wp_register_script( 'qpp_block', plugins_url( 'block.js', __FILE__ ), array(
        'wp-blocks',
        'wp-element',
        'wp-components',
        'wp-editor'
    ) );
    // Register our block, and explicitly define the attributes we accept.
    register_block_type( 'quick-paypal-payments/block', array(
        'editor_script'   => 'qpp_block',
        'render_callback' => 'qpp_loop',
    ) );
}

add_action( 'init', 'qpp_block_init' );
/*
	Function which registers qpp scripts
*/
function qpp_register_scripts()
{
    wp_register_script(
        'qpp_script',
        plugins_url( 'payments.js', __FILE__ ),
        array( 'jquery' ),
        false,
        true
    );
}

/*
	Function which displays registered scripts
	ONLY IF $qpp_shortcode_exists EXISTS
*/
function qpp_display_scripts()
{
    global  $qpp_shortcode_exists ;
    if ( $qpp_shortcode_exists ) {
        wp_print_scripts( 'qpp_script' );
    }
}

function qpp_create_css_file( $update )
{
    
    if ( function_exists( 'file_put_contents' ) ) {
        $css_dir = plugin_dir_path( __FILE__ ) . '/custom.css';
        $filename = plugin_dir_path( __FILE__ );
        
        if ( is_writable( $filename ) && !file_exists( $css_dir ) || !empty($update) ) {
            $data = qpp_generate_css();
            file_put_contents( $css_dir, $data, LOCK_EX );
        }
    
    } else {
        add_action( 'wp_head', 'qpp_head_css' );
    }

}

function qpp_enqueue_scripts()
{
    $qpp_setup = qpp_get_stored_setup();
    wp_register_script(
        'paypal_checkout_js',
        "https://www.paypalobjects.com/api/checkout.js",
        array(),
        false,
        true
    );
    wp_register_script(
        'qpp_script',
        plugins_url( 'payments.js', __FILE__ ),
        array(),
        QUICK_PAYPAL_PAYMENTS_VERSION,
        true
    );
    wp_register_style( 'qpp_style', plugins_url( 'payments.css', __FILE__ ) );
    
    if ( $qpp_setup['location'] == 'php' ) {
        qpp_create_css_file( '' );
        wp_register_style( 'qpp_custom_style', plugins_url( 'custom.css', __FILE__ ) );
    } else {
        add_action( 'wp_head', 'qpp_head_css' );
    }
    
    wp_register_style(
        'jquery-style',
        plugins_url( 'jquery-ui.css', __FILE__ ),
        array(),
        '1.8.9'
    );
}

/*
	@Change
	@Add function qpp_validate_form
*/
function qpp_validate_form_callback( $degrade = false )
{
    if ( !wp_doing_ajax() ) {
        return;
    }
    $setup = qpp_get_stored_setup();
    $json = new stdClass();
    
    if ( isset( $_POST['form_id'] ) ) {
        $formerrors = array();
        $form = sanitize_text_field( $_POST['form_id'] );
        $style = qpp_get_stored_style( $form );
        $error = qpp_get_stored_error( $form );
        $currency = qpp_get_stored_curr();
        $current_currency = $currency[$form];
        $qpp = qpp_get_stored_options( $form );
        $send = qpp_get_stored_send( $form );
        $json = (object) array(
            'success'     => false,
            'errors'      => array(),
            'display'     => $error['errortitle'],
            'blurb'       => $error['errorblurb'],
            'error_color' => $style['error-colour'],
        );
        $info = qpp_default_merge_v( $_POST );
        
        if ( !qpp_verify_form( $info, $formerrors, $form ) ) {
            $json->success = false;
            /* Format Form Errors */
            foreach ( $formerrors as $k => $v ) {
                if ( $k == 'captcha' ) {
                    $k = 'maths';
                }
                if ( $k == 'use_stock' ) {
                    $k = 'stock';
                }
                if ( $k == 'use_cf' ) {
                    $k = 'cf';
                }
                if ( $k == 'useterms' ) {
                    $k = 'termschecked';
                }
                if ( $k == 'use_message' ) {
                    $k = 'yourmessage';
                }
                $json->errors[] = (object) array(
                    'name'  => $k,
                    'error' => $v,
                );
            }
        } else {
            $json->success = true;
            // No errors
            $v = array();
            $form = $amount = $id = '';
            $v = formulate_v(
                $_POST['sc'],
                $form,
                $amount,
                $id
            );
            if ( strlen( $amount ) ) {
                $v['amount'] = $amount;
            }
            if ( strlen( $id ) ) {
                $v['reference'] = $id;
            }
            $payment = qpp_process_values( $v, $form );
            $json->html = qpp_process_form( $v, $form, $payment );
        }
    
    } else {
        // error
    }
    
    echo  json_encode( $json ) ;
    wp_die();
}

function qpp_default_merge_v( $array )
{
    $defaults = array(
        'quantity'      => false,
        'itemvalue'     => false,
        'stock'         => false,
        'cf'            => false,
        'otheramount'   => false,
        'couponblurb'   => false,
        'yourmessage'   => false,
        'datepicker'    => false,
        'qtyproduct'    => false,
        'combine'       => false,
        'couponapplied' => false,
        'couponget'     => false,
        'maths'         => false,
        'explodepay'    => false,
        'explode'       => false,
        'recurring'     => false,
        'termschecked'  => false,
        'consent'       => false,
        'email'         => false,
        'setref'        => false,
        'reference'     => false,
        'setpay'        => false,
        'amount'        => false,
        'fixedamount'   => false,
        'couponerror'   => false,
        'noproduct'     => false,
        'items'         => array(),
        'thesum'        => false,
        'answer'        => false,
    );
    // Apply values to the default array
    foreach ( $array as $k => $v ) {
        $defaults[$k] = $v;
    }
    return $defaults;
}

function qpp_reference_type( $qpp )
{
    
    if ( $qpp['fixedreference'] == 'checked' ) {
        switch ( $qpp['refselector'] ) {
            case 'ignore':
                return 'text';
                break;
            default:
                $options = explode( ',', $qpp['inputreference'] );
                
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

function collect_data( $form )
{
    $qpp = qpp_get_stored_options( $form );
    $coupon = qpp_get_stored_coupon( $form );
    $currency = qpp_get_stored_curr();
    $d = qpp_sanitize( $_POST );
    global  $qpp_attributes ;
    $atts = $qpp_attributes[$form];
    /*
    	Lets check what kinda reference we have
    */
    $isCombined = false;
    $returning = [
        'reference' => [],
        'amount'    => [],
        'stock'     => -1,
        'labels'    => '',
        'other'     => [
        'use' => qpp_get_element( $qpp, 'combobox' ) == 'checked',
    ],
    ];
    /*
    	Do attributes
    */
    if ( isset( $atts['id'] ) ) {
        $qpp['inputreference'] = $atts['id'];
    }
    
    if ( isset( $atts['amount'] ) && !empty($atts['amount']) ) {
        $qpp['fixedamount'] = 'checked';
        $qpp['inputamount'] = $atts['amount'];
    }
    
    if ( isset( $atts['stock'] ) ) {
        $returning['stock'] = $atts['stock'];
    }
    switch ( qpp_reference_type( $qpp ) ) {
        case 'choice':
            
            if ( strpos( $qpp['inputreference'], ';' ) ) {
                $isCombined = true;
                $explode = explode( ',', $qpp['inputreference'] );
                $options = [];
                foreach ( $explode as $k => $v ) {
                    $new = explode( ';', $v );
                    $val = (double) qpp_format_amount( $currency[$form], $qpp, $new[1] );
                    $options[] = [
                        'name'  => $new[0],
                        'value' => $val,
                    ];
                }
            } else {
                $temp = explode( ',', $qpp['inputreference'] );
                foreach ( $temp as $v ) {
                    $options[] = [
                        'name' => $v,
                    ];
                }
            }
            
            $returning['reference']['options'] = $options;
            
            if ( isset( $d['reference'] ) ) {
                $returning['reference']['value'] = $d['reference'];
            } else {
                $returning['reference']['value'] = implode( '&', $options[0] );
            }
            
            $returning['reference']['type'] = 'choice';
            break;
        case 'text':
            // static text
            $returning['reference']['options'] = [];
            $returning['reference']['value'] = $qpp['inputreference'];
            $returning['reference']['type'] = 'text';
            $returning['reference']['default'] = $qpp['inputreference'];
            break;
        case 'input':
            $returning['reference']['options'] = [];
            
            if ( isset( $d['reference'] ) ) {
                $returning['reference']['value'] = $d['reference'];
            } else {
                $returning['reference']['value'] = '';
            }
            
            $returning['reference']['type'] = 'input';
            $returning['reference']['default'] = $qpp['inputreference'];
            break;
    }
    $returning['combined'] = $isCombined;
    /*
    	Now calculate amount
    */
    
    if ( $isCombined ) {
        $explode = explode( '&', $returning['reference']['value'] );
        $returning['amount']['values'] = [];
        foreach ( $returning['reference']['options'] as $v ) {
            $returning['amount']['values'][] = (double) $v['value'];
        }
        $returning['amount']['value'] = (double) $explode[1];
        $returning['amount']['type'] = 'combined';
        // disallow the other box for combined values
        $returning['other']['use'] = false;
    } else {
        
        if ( $qpp['fixedamount'] == 'checked' ) {
            // Fixed Amount, involves flat fee, dropdown, radio
            $list = explode( ',', $qpp['inputamount'] );
            
            if ( count( $list ) > 1 ) {
                $values = [];
                foreach ( $list as $v ) {
                    $values[] = (double) qpp_format_amount( $currency[$form], $qpp, $v );
                }
                $returning['amount']['values'] = $values;
                $returning['amount']['type'] = 'choice';
                
                if ( isset( $d['amount'] ) ) {
                    $val = (double) $d['amount'];
                    $returning['amount']['received'] = $val;
                    // Is the value an acceptable fixed value?
                    
                    if ( in_array( $val, $returning['amount']['values'] ) || $val == 'other' && $returning['other']['use'] ) {
                        $returning['amount']['value'] = $val;
                    } else {
                        $returning['amount']['value'] = (double) $returning['amount']['values'][0];
                    }
                
                } else {
                    $returning['amount']['value'] = $returning['amount']['values'][0];
                }
                
                // Calculate Other
                
                if ( $returning['other']['use'] ) {
                    $returning['other']['caption'] = $qpp['comboboxword'];
                    $returning['other']['instruction'] = $qpp['comboboxlabel'];
                    
                    if ( $returning['amount']['value'] == 'other' ) {
                        $returning['other']['value'] = (double) qpp_format_amount( $currency[$form], $qpp, $d['otheramount'] );
                        $returning['other']['toggled'] = true;
                    } else {
                        $returning['other']['value'] = 0;
                        $returning['other']['toggled'] = false;
                    }
                
                }
            
            } else {
                // just a fixed value
                $returning['amount']['values'] = [];
                $returning['amount']['type'] = 'fixed';
                $returning['amount']['value'] = (double) qpp_format_amount( $currency[$form], $qpp, $qpp['inputamount'] );
            }
        
        } else {
            // Input amount
            
            if ( isset( $d['amount'] ) ) {
                
                if ( $d['amount'] == $qpp['inputamount'] ) {
                    $returning['amount']['value'] = 0;
                } else {
                    $returning['amount']['value'] = (double) qpp_format_amount( $currency[$form], $qpp, $d['amount'] );
                }
            
            } else {
                $returning['amount']['value'] = 0;
            }
            
            $returning['amount']['values'] = [];
            $returning['amount']['type'] = 'input';
        }
    
    }
    
    $returning['currency'] = qpp_currency( $form );
    /*
    	Factor In Attributes
    */
    return $returning;
}

function formulate_v(
    $atts,
    &$form = '',
    &$amount = '',
    &$id = '',
    &$stock = '',
    &$labels = ''
)
{
    extract( shortcode_atts( array(
        'form'   => '',
        'amount' => '',
        'id'     => '',
        'stock'  => '',
        'labels' => '',
    ), $atts ) );
    $qpp = qpp_get_stored_options( $form );
    $address = qpp_get_stored_address( $form );
    $coupon = qpp_get_stored_coupon( $form );
    $currency = qpp_get_stored_curr();
    $shortcodereference = '';
    // Will be used at the end
    $total = 0;
    global  $_REQUEST, $qpp_attributes ;
    $qpp_attributes[$form] = $atts;
    /*
    	Make sure this form is the form which is being submitted
    */
    
    if ( isset( $_REQUEST['form_id'] ) && $_REQUEST['form_id'] == $form ) {
        if ( isset( $_REQUEST["reference"] ) ) {
            $id = $_REQUEST["reference"];
        }
        if ( isset( $_REQUEST["amount"] ) ) {
            $amount = $_REQUEST["amount"];
        }
        if ( isset( $_REQUEST["item"] ) ) {
            $qpp['stocklabel'] = $_REQUEST["item"];
        }
        if ( isset( $_REQUEST["form"] ) ) {
            $form = $_REQUEST["form"];
        }
    }
    
    $arr = array(
        'email',
        'firstname',
        'lastname',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        'night_phone_b'
    );
    foreach ( $arr as $item ) {
        $v[$item] = $address[$item];
    }
    $v['form_data'] = collect_data( $form );
    $v['quantity'] = 1;
    $v['itemvalue'] = $v['mailchimp'] = $v['couponerror'] = $v['option1'] = $v['noproduct'] = false;
    $v['stock'] = $qpp['stocklabel'];
    $v['cf'] = $qpp['cflabel'];
    $v['otheramount'] = $qpp['comboboxlabel'];
    $v['couponblurb'] = $qpp['couponblurb'];
    $v['yourmessage'] = $qpp['messagelabel'];
    $v['datepicker'] = $qpp['datepickerlabel'];
    $v['coupon'] = false;
    for ( $i = 1 ;  $i <= 9 ;  $i++ ) {
        $v['qtyproduct' . $i] = '0';
    }
    $v['srt'] = $qpp['recurringhowmany'];
    $d = qpp_sanitize( $_POST );
    for ( $i = 1 ;  $i <= 9 ;  $i++ ) {
        if ( isset( $d['qtyproduct' . $i] ) ) {
            $v['qtyproduct' . $i] = $d['qtyproduct' . $i];
        }
    }
    
    if ( isset( $_POST['qppapply' . $form] ) || isset( $_POST['qppsubmit' . $form] ) || isset( $_POST['qppsubmit' . $form . '_x'] ) ) {
        // check for combobox option
        if ( isset( $d['otheramount'] ) && isset( $d['use_other_amount'] ) ) {
            if ( strtolower( $d['use_other_amount'] ) == 'true' ) {
                /* $d['amount'] = $d['otheramount']; */
            }
        }
        
        if ( $qpp['use_options'] && $qpp['optionselector'] == 'optionscheckbox' ) {
            $checks = '';
            $arr = explode( ",", $qpp['optionvalues'] );
            foreach ( $arr as $key ) {
                if ( $d['option1_' . str_replace( ' ', '', $key )] ) {
                    $checks .= $key . ', ';
                }
            }
            $d['option1'] = rtrim( $checks, ', ' );
        }
        
        $arr = array(
            'reference',
            'amount',
            'stock',
            'quantity',
            'option1',
            'couponblurb',
            'maths',
            'thesum',
            'answer',
            'termschecked',
            'yourmessage',
            'datepicker',
            'email',
            'firstname',
            'lastname',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b',
            'combine',
            'srt',
            'cf',
            'consent'
        );
        foreach ( $arr as $item ) {
            if ( isset( $d[$item] ) ) {
                $v[$item] = $d[$item];
            }
        }
    }
    
    // This is a soft application of the coupon -- validation happens later
    
    if ( isset( $d['qppapply' . $form] ) ) {
        if ( strlen( qpp_get_element( $v, 'couponblurb' ) ) ) {
            $couponcode = $v['couponblurb'];
        }
    } else {
        if ( strlen( qpp_get_element( $d, 'couponblurb' ) ) ) {
            $couponcode = $d['couponblurb'];
        }
    }
    
    // Validate the coupon
    if ( isset( $couponcode ) ) {
        
        if ( $ticket = qpp_get_coupon( $couponcode, $form ) ) {
            // Is the coupon expired?
            
            if ( $ticket['expired'] ) {
                $v['couponerror'] = $coupon['couponexpired'];
            } else {
                // There is an available coupon
                
                if ( qpp_get_element( $ticket, 'qty', 0 ) !== 0 ) {
                    $v['couponapplied'] = 'checked';
                    $v['couponblurb'] = $couponblurb;
                    $v['coupon'] = array(
                        'type'  => $ticket['type'],
                        'value' => $ticket['value'],
                        'code'  => $ticket['code'],
                    );
                }
            
            }
            
            if ( !$v['couponapplied'] && !$v['couponerror'] ) {
                $v['couponerror'] = $coupon['couponerror'];
            }
        } else {
            //invalid
            $v['couponerror'] = $coupon['couponerror'];
        }
    
    }
    $v['items'] = array();
    
    if ( $qpp['use_multiples'] ) {
        $multiples = qpp_get_stored_multiples( $form );
        $pointer = 0;
        for ( $i = 1 ;  $i <= 9 ;  $i++ ) {
            $check = $multiples['cost' . $i];
            if ( isset( $d['qtyproduct' . $i] ) && $d['qtyproduct' . $i] == 'checked' ) {
                $d['qtyproduct' . $i] = 1;
            }
            //converts checked to 1
            
            if ( isset( $d['qtyproduct' . $i] ) && $d['qtyproduct' . $i] > 0 ) {
                $pointer++;
                $v['items'][] = array(
                    'item_name' => (string) $multiples['product' . $i],
                    'amount'    => (double) qpp_format_amount( $currency[$form], $qpp, $check ),
                    'quantity'  => (int) $d['qtyproduct' . $i],
                );
                $total += $check * $d['qtyproduct' . $i];
            }
        
        }
    } else {
        // Check for the otheramount case
        $using = $v['form_data']['amount']['value'];
        if ( $v['form_data']['other']['use'] ) {
            if ( $v['form_data']['other']['toggled'] ) {
                $using = $v['form_data']['other']['value'];
            }
        }
        $v['items'][] = array(
            'item_name' => (string) qpp_get_element( $v, 'reference' ),
            'amount'    => ( isset( $currency[$form] ) ? (double) qpp_format_amount( $currency[$form], $qpp, $using ) : 0 ),
            'quantity'  => (int) $v['quantity'],
        );
        $total = $v['items'][0]['amount'] * $v['items'][0]['quantity'];
    }
    
    $discount = 0;
    
    if ( $v['coupon'] ) {
        $discount = $v['coupon']['value'];
        if ( $v['coupon']['type'] == 'percent' ) {
            $discount = $total * ($discount * 0.01);
        }
    }
    
    $v['aftercoupon'] = $total - $discount;
    $v['amount'] = $total;
    $amount = $v['amount'];
    /*
    	Fix email
    */
    if ( $qpp['useemail'] && !$qpp['useaddress'] ) {
        if ( $v['email'] == $address['email'] ) {
            $v['email'] = $qpp['emailblurb'];
        }
    }
    return $v;
}

function qpp_get_total( $a )
{
    $total = 0;
    foreach ( $a as $item ) {
        $total += $item['amount'] * $item['quantity'];
    }
    return $total;
}

function qpp_display_success( $form, $tid, $amt )
{
    $f = ( $form == 'default' ? '' : $form );
    $style = qpp_get_stored_style( $f );
    $message = qpp_get_stored_messages();
    $send = qpp_get_stored_send( $f );
    $c = qpp_currency( $f );
    $post = $_POST['sc']['post'];
    $url = get_permalink( $post );
    $display = '';
    
    if ( strlen( $send['thanksurl'] ) ) {
        $display .= "<script type='text/javascript'>";
        $display .= "window.location.href = '{$send['thanksurl']}';";
        $display .= "</script>";
    }
    
    $display .= <<<Form
\t<div class="qpp-style qpp-complete {$form}"><div id="{$style['border']}">
\t\t<form id="frmPayment{$f}" name="frmPayment{$f}" method="post" action="">
\t\t\t<h2>{$message['confirmationtitle']}</h2>
            <p class="qpp-blurb">{$message['confirmationblurb']}</p>
\t\t\t<p class="qpp-blurb"><b>{$message['confirmationreference']}</b> {$tid}</p>
\t\t\t<p class="qpp-blurb"><b>{$message['confirmationamount']}</b> {$c['b']}{$amt}{$c['a']}</p>
\t\t\t<p><a href="{$url}">{$message['confirmationanchor']}</a></p>
\t\t</form>
\t</div></div>
Form;
    echo  $display ;
}

function qpp_display_pending( $form, $token, $payerid )
{
    $f = ( $form == 'default' ? '' : $form );
    $style = qpp_get_stored_style( $f );
    $message = qpp_get_stored_messages();
    $send = qpp_get_stored_send( $f );
    $post = $_POST['sc']['post'];
    $url = get_permalink( $post );
    $display = <<<Form
\t<div class="qpp-style qpp-complete {$form}"><div id="{$style['border']}">
\t\t<form id="frmPayment{$f}" name="frmPayment{$f}" method="post" action="">
\t\t\t<h2>{$message['pendingtitle']}</h2>
\t\t\t<p class="qpp-blurb">{$message['pendingblurb']}</p>
\t\t\t<p><a href="{$url}?token={$token}&PayerID={$payerid}">{$message['pendinganchor']}</a></p>
\t\t</form>
\t</div></div>
Form;
    echo  $display ;
}

function qpp_display_failure( $form, $result = false )
{
    $f = ( $form == 'default' ? '' : $form );
    $style = qpp_get_stored_style( $f );
    $message = qpp_get_stored_messages();
    $send = qpp_get_stored_send( $f );
    $post = $_POST['sc']['post'];
    $url = get_permalink( $post );
    $display = '';
    
    if ( strlen( $send['cancelurl'] ) ) {
        $display .= "<script type='text/javascript'>";
        $display .= "window.location.href = '{$send['cancelurl']}';";
        $display .= "</script>";
    }
    
    $display .= <<<Form
\t<div class="qpp-style qpp-complete {$form}"><div id="{$style['border']}">
\t\t<form id="frmPayment{$f}" name="frmPayment{$f}" method="post" action="">
\t\t<h2>{$message['failuretitle']}</h2>
Form;
    if ( $result && isset( $result['L_LONGMESSAGE0'] ) ) {
        $display .= '<p class="qpp-blurb">' . $result['L_LONGMESSAGE0'] . '</p><br />';
    }
    $display .= <<<Form
\t\t\t<p class="qpp-blurb">{$message['failureblurb']}</p><br />
\t\t\t<a href="{$url}">{$message['failureanchor']}</a>
\t\t</form>
\t</div></div>
Form;
    echo  $display ;
}

function qpp_loop( $atts )
{
    $qpp_setup = qpp_get_stored_setup();
    wp_enqueue_script( 'paypal_checkout_js' );
    wp_enqueue_script( 'qpp_script' );
    wp_enqueue_style( 'qpp_style' );
    
    if ( $qpp_setup['location'] == 'php' ) {
        qpp_create_css_file( '' );
        wp_enqueue_style( 'qpp_custom_style' );
    } else {
        add_action( 'wp_head', 'qpp_head_css' );
    }
    
    wp_enqueue_script( "jquery-effects-core" );
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
    /*
    	Let the rest of wordpress know that there is a shortcode that we're looking for!
    */
    global  $qpp_shortcode_exists, $qpp_end_loop ;
    if ( $qpp_end_loop ) {
        return;
    }
    $qpp_shortcode_exists = true;
    $form = $amount = $id = '';
    $v = formulate_v(
        $atts,
        $form,
        $amount,
        $id
    );
    $form = ( $form ? $form : 'default' );
    ob_start();
    $v = array();
    $form = $amount = $id = '';
    $v = formulate_v(
        $atts,
        $form,
        $amount,
        $id
    );
    
    if ( isset( $_POST['qppsubmit' . $form] ) || isset( $_POST['qppsubmit' . $form . '_x'] ) ) {
        $formerrors = array();
        
        if ( !qpp_verify_form( $v, $formerrors, $form ) ) {
            qpp_display_form(
                $v,
                $formerrors,
                $form,
                $atts
            );
        } else {
            if ( $amount ) {
                $v['amount'] = $amount;
            }
            if ( $id ) {
                $v['reference'] = $id;
            }
            $payment = qpp_process_values( $v, $form );
            echo  qpp_process_form( $v, $form, $payment ) ;
            echo  '<script language="JavaScript">document.getElementById("frmCart").submit();</script>' ;
        }
    
    } else {
        $digit1 = mt_rand( 1, 10 );
        $digit2 = mt_rand( 1, 10 );
        
        if ( $digit2 >= $digit1 ) {
            $v['thesum'] = "{$digit1} + {$digit2}";
            $v['answer'] = $digit1 + $digit2;
        } else {
            $v['thesum'] = "{$digit1} - {$digit2}";
            $v['answer'] = $digit1 - $digit2;
        }
        
        qpp_display_form(
            $v,
            array(),
            $form,
            $atts
        );
    }
    
    $output_string = ob_get_contents();
    ob_end_clean();
    return $output_string;
}

function qpp_display_form(
    $values,
    $errors,
    $id,
    $attr = ''
)
{
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global  $quick_paypal_payments_fs ;
    if ( !$attr ) {
        $attr = array();
    }
    $qpp = qpp_get_stored_options( $id );
    if ( isset( $_GET["form"] ) && !$id ) {
        $id = santitize_text_field( $_GET["form"] );
    }
    
    if ( isset( $_GET["reference"] ) ) {
        $values['reference'] = sanitize_text_field( $_GET["reference"] );
        $values['setref'] = true;
    }
    
    if ( isset( $_GET["amount"] ) ) {
        // $values['amount'] = $qpp['shortcodeamount'].' '.$_GET["amount"];
        // $values['fixedamount'] = $_GET["amount"];
        // $values['setpay'] = true;
    }
    
    if ( isset( $_GET["coupon"] ) ) {
        $values['couponblurb'] = sanitize_text_field( $_GET["coupon"] );
        $values['couponget'] = $values['couponget'];
    }
    
    $qpp_form = qpp_get_stored_setup();
    $error = qpp_get_stored_error( $id );
    $coupon = qpp_get_stored_coupon( $id );
    $send = qpp_get_stored_send( $id );
    $style = qpp_get_stored_style( $id );
    $currency = qpp_get_stored_curr();
    $address = qpp_get_stored_address( $id );
    $messages = qpp_get_stored_messages();
    $list = qpp_get_stored_mailinglist();
    $curr = ( !isset( $currency[$id] ) || $currency[$id] == '' ? 'USD' : $currency[$id] );
    $check = preg_replace( '/[^.0-9]/', '', $values['amount'] );
    $decimal = array(
        'HKD',
        'JPY',
        'MYR',
        'TWD'
    );
    $d = '2';
    foreach ( $decimal as $item ) {
        if ( isset( $currency[$id] ) && $item == $currency[$id] ) {
            $d = '0';
        }
    }
    $p = $h = '';
    if ( $qpp['use_slider'] ) {
        $values['amount'] = $qpp['initial'];
    }
    $c = qpp_currency( $id );
    $t = ( $id ? $id : 'default' );
    $hd = $style['header-type'];
    $content = "<script type='text/javascript'>ajaxurl = '" . admin_url( 'admin-ajax.php' ) . "';</script>";
    
    if ( $id ) {
        $formstyle = $id;
    } else {
        $formstyle = 'default';
    }
    
    if ( !empty($qpp['title']) ) {
        $qpp['title'] = '<' . $hd . ' id="qpp_reload" class="qpp-header">' . $qpp['title'] . '</' . $hd . '>';
    }
    if ( !empty($qpp['blurb']) ) {
        $qpp['blurb'] = '<p class="qpp-blurb">' . $qpp['blurb'] . '</p>';
    }
    $content .= '<div class="qpp-style ' . $formstyle . '"><div id="' . $style['border'] . '">';
    $content .= '<form id="frmPayment' . $t . '" name="frmPayment' . $t . '" method="post" action="">';
    
    if ( count( $errors ) > 0 || $values['noproduct'] ) {
        $content .= "<script type='text/javascript' language='javascript'>document.querySelector('#qpp_reload').scrollIntoView();</script>";
        $content .= "<" . $hd . " class='qpp-header' id='qpp_reload' style='color:" . $style['error-colour'] . ";'>" . $error['errortitle'] . "</" . $hd . ">\n        <p class='qpp-blurb' style='color:" . $style['error-colour'] . ";'>" . $error['errorblurb'] . "</p>";
        $arr = array(
            'amount',
            'reference',
            'quantity',
            'use_stock',
            'use_cf',
            'answer',
            'quantity',
            'email',
            'firstname',
            'lastname',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b'
        );
        foreach ( $arr as $item ) {
            if ( $errors[$item] == 'error' ) {
                $errors[$item] = ' style="border:1px solid ' . $style['error-colour'] . ';" ';
            }
        }
        if ( $errors['useterms'] ) {
            $errors['useterms'] = 'border:1px solid ' . $style['error-colour'] . ';';
        }
        if ( $errors['captcha'] ) {
            $errors['captcha'] = 'border:1px solid ' . $style['error-colour'] . ';';
        }
        if ( $errors['quantity'] ) {
            $errors['quantity'] = 'border:1px solid ' . $style['error-colour'] . ';';
        }
    } else {
        $content .= $qpp['title'];
        if ( $qpp['paypal-url'] && $qpp['paypal-location'] == 'imageabove' ) {
            $content .= "<img src='" . $qpp['paypal-url'] . "' />";
        }
        $content .= $qpp['blurb'];
    }
    
    /*
    	Build shortcode value array
    */
    $attr['post'] = get_the_ID();
    if ( count( $attr ) ) {
        foreach ( $attr as $k => $v ) {
            $content .= "<input type='hidden' name='sc[" . $k . "]' value='" . $v . "' />";
        }
    }
    $content .= '<input type="hidden" name="form_id" value="' . $id . '" />';
    $content .= '<input type="hidden" name="currencybefore" value="' . $c['b'] . '" />';
    $content .= '<input type="hidden" name="currencyafter" value="' . $c['a'] . '" />';
    /*
    	Labels
    */
    switch ( $style['labeltype'] ) {
        case 'tiny':
            $label = 1;
            break;
        case 'hiding':
            $label = 2;
            break;
        case 'plain':
            $label = 3;
            break;
    }
    $data = $values['form_data'];
    $qpp_multiples = false;
    foreach ( explode( ',', $qpp['sort'] ) as $name ) {
        switch ( $name ) {
            case 'field1':
                if ( !$qpp['use_multiples'] ) {
                    switch ( $data['reference']['type'] ) {
                        case 'choice':
                            $content .= '<p class="input" >' . $qpp['shortcodereference'] . '</p>';
                            $content .= qpp_reference_choice( $qpp['refselector'], $data );
                            break;
                        case 'text':
                            $content .= '<p class="input" >' . $qpp['shortcodereference'] . ' ' . $data['reference']['value'] . '</p><input type="hidden" name="reference" value="' . $data['reference']['value'] . '" />';
                            break;
                        case 'input':
                            $required = ( !qpp_get_element( $errors, 'reference', false ) ? ' class="required" ' : '' );
                            $content .= qpp_nice_label(
                                'reference' . $id,
                                'reference',
                                'text',
                                $qpp['inputreference'],
                                $label,
                                $required . qpp_get_element( $errors, 'reference', false ),
                                ( $data['reference']['value'] ? $data['reference']['value'] : $qpp['inputreference'] )
                            );
                            break;
                    }
                }
                break;
            case 'field2':
                
                if ( $qpp['use_stock'] ) {
                    $requiredstock = ( !$errors['use_stock'] && $qpp['ruse_stock'] ? ' class="required" ' : '' );
                    
                    if ( $qpp['fixedstock'] || isset( $_REQUEST["item"] ) ) {
                        $content .= '<p class="input" >' . $values['stock'] . '</p>';
                    } else {
                        $content .= qpp_nice_label(
                            'stock' . $id,
                            'stock',
                            'text',
                            $qpp['stocklabel'],
                            $label,
                            $requiredstock . $errors['use_stock'],
                            $values['stock']
                        );
                    }
                
                }
                
                break;
            case 'field3':
                
                if ( $qpp['use_quantity'] && !$qpp['use_multiples'] ) {
                    $content .= '<p>
                <span class="input">' . $qpp['quantitylabel'] . '</span>
                <input type="text" style=" ' . $errors['quantity'] . 'width:3em;margin-left:5px" id="qppquantity' . $t . '" label="quantity" name="quantity" value="' . $values['quantity'] . '" onfocus="qppclear(this, \'' . $values['quantity'] . '\')" onblur="qpprecall(this, \'' . $values['quantity'] . '\')" />';
                    if ( $qpp['quantitymax'] ) {
                        $content .= '&nbsp;' . $qpp['quantitymaxblurb'];
                    }
                    $content .= '</p>';
                } else {
                    $content .= '<input type="hidden" id="qppquantity' . $t . '" name="quantity" value="1">';
                }
                
                break;
            case 'field4':
                if ( !$qpp['use_multiples'] ) {
                    
                    if ( $qpp['use_slider'] ) {
                        $content .= '<p style="margin-bottom:0.7em;">' . $qpp['sliderlabel'] . '</p>
                    <input type="range" id="qppamount' . $t . '" name="amount" min="' . $qpp['min'] . '" max="' . $qpp['max'] . '" value="' . $values['amount'] . '" step="' . $qpp['step'] . '" data-rangeslider>
                    <div class="qpp-slideroutput">
                    <span class="qpp-sliderleft">' . $qpp['min'] . '</span>
                    <span class="qpp-slidercenter"><output></output></span>
                    <span class="qpp-sliderright">' . $qpp['max'] . '</span>
                    </div><div style="clear: both;"></div>';
                    } else {
                        
                        if ( $data['amount']['type'] == 'choice' ) {
                            $type = $qpp['selector'];
                            if ( $qpp['selector'] == 'radio' && $qpp['inline_amount'] == 'checked' ) {
                                $type = 'inline';
                            }
                            $content .= '<p class="input">' . $qpp['shortcodeamount'] . '</p>';
                            $content .= qpp_amount_choice( $type, $data );
                        } elseif ( $data['amount']['type'] == 'combined' ) {
                            // do nothing here
                        } else {
                            switch ( $data['amount']['type'] ) {
                                case 'fixed':
                                    $content .= '<p class="input">' . $qpp['shortcodeamount'] . $c['b'] . qpp_format_amount( $curr, $qpp, $values['aftercoupon'] ) . $c['a'] . '</p><input type="hidden" id="qppamount' . $t . '" name="amount" value="' . $values['amount'] . '" />';
                                    break;
                                default:
                                    $required = ( !qpp_get_element( $errors, 'amount', false ) ? ' class="required" ' : '' );
                                    $content .= qpp_nice_label(
                                        'amount' . $id,
                                        'amount',
                                        'text',
                                        $qpp['inputamount'],
                                        $label,
                                        $required . qpp_get_element( $errors, 'reference' ),
                                        ( $data['amount']['value'] > 0 ? $data['amount']['value'] : $qpp['inputamount'] )
                                    );
                                    break;
                            }
                        }
                    
                    }
                
                }
                break;
            case 'field5':
                
                if ( $qpp['use_options'] ) {
                    $content .= '<p class="input">' . $qpp['optionlabel'] . '</p><p>';
                    $arr = explode( ",", $qpp['optionvalues'] );
                    $br = ( $qpp['inline_options'] ? '&nbsp;' : '<br>' );
                    
                    if ( $qpp['optionselector'] == 'optionsdropdown' ) {
                        $content .= qpp_dropdown(
                            $arr,
                            $values,
                            'option1',
                            ''
                        );
                    } elseif ( $qpp['optionselector'] == 'optionscheckbox' ) {
                        $content .= qpp_checkbox(
                            $arr,
                            $values,
                            'option1',
                            $br
                        );
                    } else {
                        foreach ( $arr as $item ) {
                            $checked = '';
                            if ( $values['option1'] == $item ) {
                                $checked = 'checked';
                            }
                            
                            if ( $item === reset( $arr ) ) {
                                $content .= '<input type="radio" style="margin:0; padding: 0; border: none" name="option1" value="' . $item . '" id="' . $item . '" checked><label for="' . $item . '"> ' . $item . '</label>' . $br;
                            } else {
                                $content .= '<input type="radio" style="margin:0; padding: 0; border: none" name="option1" value="' . $item . '" id="' . $item . '" ' . $checked . '><label for="' . $item . '"> ' . $item . '</label>' . $br;
                            }
                        
                        }
                        $content .= '</p>';
                    }
                
                }
                
                break;
            case 'field6':
                
                if ( $qpp['usepostage'] ) {
                    $content .= '<p class="input" >' . $qpp['postageblurb'] . '</p>';
                    // @Change
                    // @Add name='postage_type'
                    $content .= '<input type="hidden" name="postage_type" value="' . (( htmlentities( $qpp['postagetype'] ) == 'postagepercent' ? 'percent' : 'fixed' )) . '" />';
                    // @Add name='postage'
                    $content .= '<input type="hidden" name="postagefixed" value="' . htmlentities( $qpp['postagefixed'] ) . '" /><input type="hidden" name="postagepercent" value="' . htmlentities( $qpp['postagepercent'] ) . '" />';
                    $content .= '</p>';
                }
                
                break;
            case 'field7':
                
                if ( $qpp['useprocess'] ) {
                    $content .= '<p class="input" >' . $qpp['processblurb'];
                    // @Change
                    // @Add name='processing_type'
                    $content .= '<input type="hidden" name="processing_type" value="' . (( htmlentities( $qpp['processtype'] ) == 'processpercent' ? 'percent' : 'fixed' )) . '" />';
                    // @Add name='processing'
                    $content .= '<input type="hidden" name="processing" value="' . htmlentities( $qpp[$qpp['processtype']] ) . '" />';
                    $content .= '</p>';
                }
                
                break;
            case 'field8':
                
                if ( $qpp['captcha'] ) {
                    $required = ( !$errors['captcha'] ? ' class="required" ' : '' );
                    if ( !empty($qpp['mathscaption']) ) {
                        $content .= '<p class="input">' . $qpp['mathscaption'] . '</p>';
                    }
                    $content .= '<p>' . strip_tags( $values['thesum'] ) . ' = <input type="text" ' . $required . ' style="width:3em;font-size:100%;' . $errors['captcha'] . '" label="Sum" name="maths"  value="' . $values['maths'] . '"></p> 
                <input type="hidden" name="answer" value="' . strip_tags( $values['answer'] ) . '" />
                <input type="hidden" name="thesum" value="' . strip_tags( $values['thesum'] ) . '" />';
                }
                
                break;
            case 'field9':
                $content .= '<input type="hidden" name="couponapplied" value="' . qpp_get_element( $values, 'couponapplied' ) . '" />';
                
                if ( $qpp['usecoupon'] && $values['coupon'] ) {
                    $content .= '<input type="hidden" name="couponblurb" value="' . $values['coupon']['code'] . '" />';
                    $content .= '<input type="hidden" name="coupontype" value="' . $values['coupon']['type'] . '" />';
                    $content .= '<input type="hidden" name="couponvalue" value="' . $values['coupon']['value'] . '" />';
                }
                
                
                if ( $qpp['usecoupon'] && $values['couponapplied'] != 'checked' ) {
                    if ( $values['couponerror'] ) {
                        
                        if ( $values['noproduct'] ) {
                            $content .= '<p style="color:' . $style['error-colour'] . ';">No products selected.</p>';
                        } else {
                            $content .= '<p style="color:' . $style['error-colour'] . ';">' . $values['couponerror'] . '</p>';
                        }
                    
                    }
                    $content .= '<p>' . $values['couponget'] . '</p>';
                    $content .= qpp_nice_label(
                        'coupon' . $id,
                        'couponblurb',
                        'text',
                        $qpp['couponblurb'],
                        $label,
                        '',
                        $values['couponblurb']
                    );
                    //$content .= '<p><input type="text" id="coupon" name="couponblurb" value="' . $values['couponblurb'] . '" rel="' . $values['couponblurb'] . '" onfocus="qppclear(this, \'' . $values['couponblurb'] . '\')" onblur="qpprecall(this, \'' . $values['couponblurb'] . '\')"/></p>
                    $content .= '<p class="submit">
                <input type="submit" value="' . $qpp['couponbutton'] . '" id="couponsubmit" name="qppapply' . $id . '" />
                </p>';
                } elseif ( $qpp['usecoupon'] && $values['couponapplied'] ) {
                    $content .= '<div class="coupon"><p>' . $qpp['couponref'] . '</p><div class="coupon-details">&nbsp;</div></div>';
                }
                
                break;
            case 'field10':
                
                if ( $qpp['useterms'] ) {
                    if ( $qpp['termspage'] ) {
                        $target = ' target="blank" ';
                    }
                    $required = ( !$errors['useterms'] ? 'border:' . $style['required-border'] . ';' : ' style="' . $errors['useterms'] . '"' );
                    $color = ( $errors['useterms'] ? ' style="color:' . $style['error-colour'] . ';" ' : '' );
                    $content .= '<p class="input">
                <input type="checkbox" style="margin:0; padding: 0;width:auto;" name="termschecked" value="checked" ' . $values['termschecked'] . '>
                &nbsp;';
                    
                    if ( $qpp['termsurl'] ) {
                        $content .= '<a class="qpp-terms" href="' . $qpp['termsurl'] . '"' . $target . '>' . $qpp['termsblurb'] . '</a></p>';
                    } else {
                        $content .= '<span class="qpp-terms">' . $qpp['termsblurb'] . '</span></p>';
                    }
                
                }
                
                break;
            case 'field11':
                if ( $qpp['useblurb'] ) {
                    $content .= '<p>' . $qpp['extrablurb'] . '</p>';
                }
                break;
            case 'field12':
                
                if ( $qpp['userecurring'] && !$qpp['use_multiples'] ) {
                    $recurringperiod = $qpp['recurring'] . 'period';
                    $content .= '<p>' . $qpp['recurringblurb'] . '<br>';
                    
                    if ( $qpp['variablerecurring'] ) {
                        $content .= '<input type="text" style=" ' . $errors['srt'] . 'width:3em;margin-left:5px" id="srt' . $t . '" label="srt" name="srt" value="' . $values['srt'] . '" onfocus="qppclear(this, \'' . $values['srt'] . '\')" onblur="qpprecall(this, \'' . $values['srt'] . '\')" /> ' . $qpp['every'] . ' ' . $qpp[$recurringperiod];
                    } else {
                        $content .= $qpp['recurringhowmany'] . ' ' . $qpp['every'] . ' ' . $qpp[$recurringperiod];
                    }
                    
                    $content .= '</p>';
                    $checked = 'checked';
                    $ref = explode( ",", $values['recurring'] );
                }
                
                break;
            case 'field13':
                
                if ( $qpp['useaddress'] ) {
                    $content .= '<p>' . $qpp['addressblurb'] . '</p>';
                    $arr = array(
                        'firstname',
                        'lastname',
                        'email',
                        'address1',
                        'address2',
                        'city',
                        'state',
                        'zip',
                        'country',
                        'night_phone_b'
                    );
                    foreach ( $arr as $item ) {
                        if ( $address[$item] ) {
                            
                            if ( 'country' != $item ) {
                                $required = ( $address['r' . $item] && !$errors[$item] ? ' class="required" ' : '' );
                                $content .= qpp_nice_label(
                                    $item . $id,
                                    $item,
                                    'text',
                                    $address[$item],
                                    $label,
                                    $required . $errors[$item],
                                    $values[$item]
                                );
                                //$content .='<p><input type="text" id="'.$item.'" name="'.$item.'" '..' value="'.$values[$item].'" rel="' . $values[$item] . '" onfocus="qppclear(this, \'' . $values[$item] . '\')" onblur="qpprecall(this, \'' . $values[$item] . '\')"/></p>';
                            } else {
                                $locales = Utilities::get_instance()->get_paypal_locales();
                                if ( !isset( $address['permitted_country'] ) || empty($address['permitted_country']) ) {
                                    $address['permitted_country'] = array_keys( $locales );
                                }
                                $countries = '';
                                foreach ( $address['permitted_country'] as $code ) {
                                    $sel = '';
                                    if ( isset( $address['default_country'] ) && $code == $address['default_country'] ) {
                                        $sel = 'selected';
                                    }
                                    $countries .= '<option value="' . $code . '" ' . $sel . '>' . $locales[$code]['region'] . '</option>';
                                }
                                if ( !isset( $address['default_country'] ) || empty($address['default_country']) ) {
                                    $first_option = '<option value="" disabled selected>' . $address[$item] . '</option>';
                                }
                                $content .= '
<div class="qpp_nice_label qpp_label_blur">
							<select name="country">
							' . $first_option . $countries . '
							
    </select>
    </div>';
                            }
                        
                        }
                    }
                }
                
                break;
            case 'field14':
                
                if ( $qpp['usetotals'] ) {
                    $content .= '<p style="font-weight:bold;">' . $qpp['totalsblurb'] . ' ' . $c['b'] . '<input type="text" id="qpptotal" name="total" value="0.00" readonly="readonly" />' . $c['a'] . '</p>';
                } else {
                    $content .= '<input type="hidden" id="qpptotal" name="total"  />';
                }
                
                break;
            case 'field16':
                
                if ( $qpp['useemail'] && !$qpp['useaddress'] ) {
                    $requiredemail = ( !$errors['useemail'] && $qpp['ruseemail'] ? ' class="required" ' : '' );
                    $content .= qpp_nice_label(
                        'email' . $id,
                        'email',
                        'text',
                        $qpp['emailblurb'],
                        $label,
                        $requiredemail . $errors['email'],
                        $values['email']
                    );
                    // $content .= '<input type="text" id="email" name="email"'.$requiredstock.$errors['email'].'value="' . $values['email'] . '" rel="' . $values['email'] . ' "onfocus="qppclear(this, \'' . $values['email'] . '\')" onblur="qpprecall(this, \'' . $values['email'] . '\')"/>';
                }
                
                break;
            case 'field17':
                
                if ( $qpp['use_message'] ) {
                    $requiredmessage = ( !$errors['yourmessage'] && $qpp['ruse_message'] ? ' class="required" ' : '' );
                    $content .= qpp_nice_label(
                        'yourmessage' . $id,
                        'yourmessage',
                        'textarea',
                        $qpp['messagelabel'],
                        $label,
                        $requiredmessage . $errors['use_message'],
                        $values['yourmessage']
                    );
                    // $content .= '<textarea rows="4" name="yourmessage" '.$requiredmessage.$errors['use_message'].' onblur="if (this.value == \'\') {this.value = \''.$values['yourmessage'].'\';}" onfocus="if (this.value == \''.$values['yourmessage'].'\') {this.value = \'\';}" />' . stripslashes($values['yourmessage']) . '</textarea>';
                }
                
                break;
            case 'field18':
                break;
            case 'field19':
                break;
            case 'field21':
                
                if ( $qpp['use_cf'] ) {
                    $requiredcf = ( !$errors['use_cf'] && $qpp['ruse_cf'] ? ' class="required" ' : '' );
                    $content .= qpp_nice_label(
                        'cf' . $id,
                        'cf',
                        'text',
                        $qpp['cflabel'],
                        $label,
                        $requiredcf . $errors['use_cf'],
                        $values['cf']
                    );
                }
                
                break;
            case 'field22':
                if ( $qpp['use_consent'] ) {
                    $content .= '<p class="input">
                <input type="checkbox" name="consent" value="checked" ' . qpp_get_element( $values, 'consent' ) . '>
                &nbsp;' . $qpp['consentlabel'] . '</p>';
                }
                break;
        }
    }
    $content .= '<input type="hidden" name="multiples" value="' . $qpp_multiples . '" />';
    $caption = $qpp['submitcaption'];
    
    if ( $style['submit-button'] ) {
        $content .= '<p class="submit pay-button"><input type="image" id="submitimage" value="' . $caption . '" src="' . $style['submit-button'] . '" name="qppsubmit' . $id . '" /></p>';
    } else {
        $content .= '<p class="submit pay-button"><input type="submit" value="' . $caption . '" id="submit" name="qppsubmit' . $id . '" /></p>';
    }
    
    if ( $qpp['use_reset'] ) {
        $content .= '<p><input type="reset" value="' . $qpp['resetcaption'] . '" /></p>';
    }
    $content .= '<div id="qppchecking">' . $messages['validating'] . '</div>';
    $content .= '</form>' . "\r\t";
    $content .= '<script type="text/javascript"> to_list.push("#frmPayment' . (( $id ? $id : 'default' )) . '"); </script></script>';
    $content .= "<div class='qpp-loading'>" . $send['waiting'] . "</div>";
    $content .= "<div class='qpp-validating-form'>" . $messages['validating'] . "</div>";
    $content .= "<div class='qpp-processing-form'>" . $messages['waiting'] . "</div>";
    if ( $qpp['paypal-url'] && $qpp['paypal-location'] == 'imagebelow' ) {
        $content .= '<img src="' . $qpp['paypal-url'] . '" />';
    }
    if ( $qpp['usetotals'] || $qpp['use_slider'] || isset( $qpp['combobox'] ) && $qpp['combobox'] == 'checked' ) {
        $content .= '<script type="text/javascript">to_totals.push("#frmPayment' . $t . '");</script>';
    }
    $content .= '<div style="clear:both;"></div></div></div>' . "\r\t";
    echo  $content ;
}

function qpp_coupon_value( $qpp, $values )
{
    $coupon = qpp_calculate_coupon( $qpp, $values );
    
    if ( $coupon !== false ) {
        // coupon is applied to the order...
        $data = "";
        $data .= '<p>Coupon Code: ' . $coupon['code'] . '</p>';
        //$data .= '<p>Coupon Savings: '.$coupon['value'].' ()
        return '';
    }
    
    // coupon not actually applied yet
    return '';
}

function qpp_calculate_coupon( $qpp, $values )
{
    return false;
}

function qpp_display_multiples( $id, $values )
{
    $multiples = qpp_get_stored_multiples( $id );
    $content = '';
    for ( $i = 1 ;  $i <= 9 ;  $i++ ) {
        $label = $multiples['shortcode'];
        $label = str_replace( '[product]', $multiples['product' . $i], $label );
        $label = str_replace( '[cost]', $multiples['cost' . $i], $label );
        
        if ( $multiples['product' . $i] ) {
            
            if ( $multiples['use_quantity'] ) {
                $content .= '<div style="clear:both;"><span style="float:left;padding:7px 0">' . $label . '</span><span style="float:right;"><input type="text" style="width:4em;text-align:right" name="qtyproduct' . $i . '" id="qtyproduct' . $i . '" value="' . $values['qtyproduct' . $i] . '" onfocus="qppclear(this, \'' . $values['qtyproduct' . $i] . '\')" onblur="qpprecall(this,\'' . $values['qtyproduct' . $i] . '\')" /></span></div>';
            } else {
                $content .= '<p><input type="checkbox" style="margin:0; padding: 0;width:auto;" name="qtyproduct' . $i . '" value="checked" ' . $values['qtyproduct' . $i] . '>&nbsp;' . $label . '</p>';
            }
            
            $content .= '<input type="hidden" name="product' . $i . '" value="' . $multiples['cost' . $i] . '" />';
        }
    
    }
    $content .= '<div style="clear:both;"></div>';
    return $content;
}

function qpp_amount_choice( $type, $data )
{
    $choices = $data['amount']['values'];
    $set = $data['amount']['value'];
    $currency = $data['currency'];
    $other = $data['other'];
    $otherinput = <<<other
<div id="otheramount">
<input type="text" label="{$other['instruction']}" onfocus="qppclear(this, '{$other['instruction']}')" onblur="qpprecall(this, '{$other['instruction']}')" value="{$other['value']}" name="otheramount" style="display: none;" />
</div>
<input type="hidden" name="use_other_amount" value="false" />
other;
    $returning = "";
    if ( $other['use'] ) {
        $choices[] = 'other';
    }
    switch ( $type ) {
        case 'dropdown':
            $returning .= '<select name="amount">';
            foreach ( $choices as $v ) {
                $display = $currency['b'] . $v . $currency['a'];
                $value = (double) $v;
                $selected = ( $v == $set || $v == 'other' && $other['toggled'] ? ' selected="selected"' : '' );
                
                if ( $v != 'other' ) {
                    $returning .= "<option value='{$value}'{$selected}>{$display}</option>";
                } else {
                    $returning .= "<option value='other'{$selected}>{$other['caption']}</option>";
                }
            
            }
            $returning .= '</select>';
            break;
        default:
            foreach ( $choices as $v ) {
                $display = $currency['b'] . $v . $currency['a'];
                $value = (double) $v;
                $selected = ( $v == $set || $v == 'other' && $other['toggled'] ? ' checked="checked"' : '' );
                if ( $type != 'inline' ) {
                    $returning .= '<p>';
                }
                
                if ( $v != 'other' ) {
                    $returning .= <<<amtradio
\t\t\t\t\t\t\t<label>
\t\t\t\t\t\t\t\t<input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="amount" value="{$value}"{$selected}>{$display}
\t\t\t\t\t\t\t</label>
amtradio;
                } else {
                    $returning .= <<<amtradio
\t\t\t\t\t\t\t<label>
\t\t\t\t\t\t\t\t<input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="amount" value="other"{$selected}>{$other['caption']}
\t\t\t\t\t\t\t</label>
amtradio;
                }
                
                if ( $type != 'inline' ) {
                    $returning .= '</p>';
                }
            }
            break;
    }
    if ( $other['use'] ) {
        $returning .= $otherinput;
    }
    return $returning;
}

function qpp_reference_choice( $type, $data )
{
    $choices = $data['reference']['options'];
    $set = $data['reference']['value'];
    $currency = $data['currency'];
    $combined = $data['combined'];
    $returning = '';
    switch ( $type ) {
        case 'refdropdown':
            $returning .= "<select name='reference'>";
            foreach ( $choices as $v ) {
                $value = implode( '&', $v );
                
                if ( $combined ) {
                    $display = $v['name'] . ' (' . $currency['b'] . $v['value'] . $currency['a'] . ')';
                } else {
                    $display = $value;
                }
                
                $selected = ( $value == $set ? ' selected=\'selected\'' : '' );
                $returning .= "<option value='{$value}'{$selected}>{$display}</option>";
            }
            $returning .= "</select>";
            break;
        default:
            $returning = '';
            foreach ( $choices as $v ) {
                $value = implode( '&', $v );
                
                if ( $combined ) {
                    $display = $v['name'] . ' (' . $currency['b'] . $v['value'] . $currency['a'] . ')';
                } else {
                    $display = $value;
                }
                
                $selected = ( $value == $set ? ' checked=\'checked\'' : '' );
                if ( $type == 'refradio' ) {
                    $returning .= '<p>';
                }
                $returning .= <<<refradio
\t\t\t\t\t\t<label>
\t\t\t\t\t\t\t<input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="reference" value="{$value}"{$selected}>{$display}
\t\t\t\t\t\t</label>
refradio;
                if ( $type == 'refradio' ) {
                    $returning .= '</p>';
                }
            }
            break;
    }
    $returning .= '<input type="hidden" name="combine" value="' . (( $combined ? 'checked' : '' )) . '" />';
    return $returning;
}

function qpp_dropdown(
    $arr,
    $values,
    $name,
    $blurb,
    $combine = false
)
{
    $content = '';
    if ( $blurb ) {
        $content = '<p class="payment" >' . $blurb . '</p>';
    }
    $content .= '<select name="' . $name . '">';
    
    if ( !$combine ) {
        foreach ( $arr as $item ) {
            $selected = '';
            if ( $values[$name] == $item ) {
                $selected = 'selected';
            }
            $content .= '<option value="' . $item . '" ' . $selected . '>' . $item . '</option>' . "\r\t";
        }
    } else {
        foreach ( $arr as $item ) {
            $selected = ( strrpos( $values['reference'], $item[0] ) !== false && $values['combine'] != 'initial' ? 'selected' : '' );
            $content .= '<option value="' . $item[0] . '&' . $item[1] . '" ' . $selected . '> ' . $item[0] . ' ' . $item[1] . '</option>';
            $selected = '';
        }
    }
    
    $content .= '</select>' . "\r\t";
    return $content;
}

function qpp_checkbox(
    $arr,
    $values,
    $name,
    $br
)
{
    $content = '<p class="input">';
    foreach ( $arr as $item ) {
        $checked = '';
        if ( $values[$name . '_' . str_replace( ' ', '', $item )] == $item ) {
            $checked = 'checked';
        }
        $content .= '<label><input type="checkbox" style="margin:0; padding: 0; border: none" name="' . $name . '_' . str_replace( ' ', '', $item ) . '" value="' . $item . '" ' . $checked . '> ' . $item . '</label>' . $br;
    }
    $content .= '</p>';
    return $content;
}

function explode_by_semicolon( $_ )
{
    return explode( ';', $_ );
}

function qpp_postage( $qpp, $check, $quantity )
{
    $packing = '';
    
    if ( $qpp['usepostage'] && $qpp['postagepercent'] ) {
        $percent = preg_replace( '/[^.,0-9]/', '', $qpp['postagepercent'] ) / 100;
        $packing = $check * $quantity * $percent;
    }
    
    if ( $qpp['usepostage'] && $qpp['postagefixed'] ) {
        $packing = preg_replace( '/[^.,0-9]/', '', $qpp['postagefixed'] );
    }
    return $packing;
}

function qpp_format_amount( $currency, $qpp, $amount )
{
    $curr = ( $currency == '' ? 'USD' : $currency );
    $decimal = array(
        'HKD',
        'JPY',
        'MYR',
        'TWD'
    );
    $d = '2';
    foreach ( $decimal as $item ) {
        
        if ( $item == $curr ) {
            $d = '';
            break;
        }
    
    }
    
    if ( !$d ) {
        $check = preg_replace( '/[^.0-9]/', '', $amount );
        $check = intval( $check );
    } elseif ( $qpp['currency_seperator'] == 'comma' && strpos( $amount, ',' ) ) {
        $check = preg_replace( '/[^,0-9]/', '', $amount );
        $check = str_replace( ',', '.', $check );
        $check = number_format(
            $check,
            $d,
            '.',
            ''
        );
    } else {
        $check = preg_replace( '/[^.0-9]/', '', $amount );
        $check = number_format(
            (double) $check,
            $d,
            '.',
            ''
        );
    }
    
    return $check;
}

function qpp_verify_form( &$v, &$errors, $form )
{
    /*
    	Skip this step if all we're doing is activating a coupon
    */
    if ( isset( $_POST['qppapply' . $form] ) ) {
        return 1;
    }
    global  $qpp_attributes ;
    $qpp_attributes[$form] = $_POST['sc'];
    $data = collect_data( $form );
    $qpp = qpp_get_stored_options( $form );
    $address = qpp_get_stored_address( $form );
    $check = preg_replace( '/[^.,0-9]/', '', $v['amount'] );
    $arr = array(
        'amount',
        'reference',
        'quantity',
        'stock',
        'email',
        'yourmessage'
    );
    foreach ( $arr as $item ) {
        $v[$item] = filter_var( $v[$item], FILTER_SANITIZE_STRING );
    }
    $v['yourmessage'] = nl2br( $v['yourmessage'] );
    
    if ( $qpp['use_multiples'] ) {
        $qpp['use_quantity'] = false;
        $v['setpay'] = $v['setref'] = true;
        for ( $i = 1 ;  $i <= 9 ;  $i++ ) {
            if ( $v['qtyproduct' . $i] ) {
                
                if ( is_numeric( $v['qtyproduct' . $i] ) ) {
                    $checkmultiple = true;
                } elseif ( 'checked' === $v['qtyproduct' . $i] ) {
                    $checkmultiple = true;
                    $v['qtyproduct' . $i] = 1;
                } elseif ( empty($v['qtyproduct' . $i]) ) {
                    $checkmultiple = true;
                    $v['qtyproduct' . $i] = 0;
                } else {
                    $checkmultiple = false;
                    break;
                }
            
            }
        }
        if ( !$checkmultiple ) {
            $errors['multiple'] = 'error';
        }
    }
    
    /*
    	Edit: More precise quantity checking
    */
    
    if ( $qpp['use_quantity'] ) {
        $max = preg_replace( '/[^0-9]/', '', $qpp['quantitymaxblurb'] );
        
        if ( is_numeric( $v['quantity'] ) && $v['quantity'] >= 1 ) {
            // quantity exists and is a number!
            // double check if the quanity is invalid with the max blurb
            if ( $qpp['quantitymax'] ) {
                if ( $max < $v['quantity'] ) {
                    $errors['quantity'] = 'error';
                }
            }
        } else {
            // is not a number or is 0
            $errors['quantity'] = 'error';
        }
    
    }
    
    // This should be easy
    // Make sure the amount is valid
    if ( !$qpp['use_multiples'] ) {
        
        if ( $data['amount']['type'] == 'combined' ) {
            // Make sure the amount and reference exist as an option
            $dRef = $data['reference']['value'];
            $errors['reference'] = 'error';
            foreach ( $data['reference']['options'] as $obj ) {
                if ( implode( '&', $obj ) == $dRef ) {
                    $errors['reference'] = '';
                }
            }
        } else {
            // Validate reference
            $errors['reference'] = 'error';
            switch ( $data['reference']['type'] ) {
                case 'text':
                    if ( $data['reference']['value'] == $data['reference']['default'] ) {
                        $errors['reference'] = '';
                    }
                    break;
                case 'input':
                    if ( $data['reference']['value'] != $data['reference']['default'] ) {
                        $errors['reference'] = '';
                    }
                    break;
                case 'choice':
                    foreach ( $data['reference']['options'] as $obj ) {
                        if ( $obj['name'] == $data['reference']['value'] ) {
                            $errors['reference'] = '';
                        }
                    }
                    break;
            }
            // Validate amount
            $errors['amount'] = 'error';
            $minamount = 1.0E-5;
            if ( isset( $qpp['minamount'] ) && $qpp['minamount'] > 0 ) {
                $minamount = $qpp['minamount'];
            }
            switch ( $data['amount']['type'] ) {
                case 'fixed':
                    if ( $data['amount']['value'] == (double) qpp_format_amount( $currency[$form], $qpp, $data['amount']['value'] ) ) {
                        if ( $data['amount']['value'] >= $minamount ) {
                            $errors['amount'] = '';
                        }
                    }
                    break;
                case 'input':
                    if ( $data['amount']['value'] == (double) qpp_format_amount( $currency[$form], $qpp, $data['amount']['value'] ) ) {
                        if ( $data['amount']['value'] >= $minamount ) {
                            $errors['amount'] = '';
                        }
                    }
                    break;
                case 'choice':
                    
                    if ( $data['other']['toggled'] && $data['other']['use'] == true ) {
                        if ( $data['other']['value'] > 0 && $data['other']['value'] != $data['other']['instruction'] ) {
                            $errors['amount'] = '';
                        }
                    } else {
                        foreach ( $data['amount']['values'] as $obj ) {
                            if ( $data['amount']['received'] == $data['amount']['value'] ) {
                                $errors['amount'] = '';
                            }
                        }
                    }
                    
                    break;
            }
        }
    
    }
    
    if ( $qpp['captcha'] == 'checked' ) {
        $v['maths'] = strip_tags( $v['maths'] );
        if ( $v['maths'] != $v['answer'] ) {
            $errors['captcha'] = 'error';
        }
        if ( empty($v['maths']) ) {
            $errors['captcha'] = 'error';
        }
    }
    
    if ( $qpp['useterms'] && !$v['termschecked'] ) {
        $errors['useterms'] = 'error';
    }
    
    if ( $qpp['useaddress'] ) {
        $arr = array(
            'email',
            'firstname',
            'lastname',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b'
        );
        foreach ( $arr as $item ) {
            $v[$item] = filter_var( $v[$item], FILTER_SANITIZE_STRING );
            if ( $address['r' . $item] && ($v[$item] == $address[$item] || empty($v[$item])) ) {
                $errors[$item] = 'error';
            }
        }
    }
    
    if ( !qpp_get_element( $qpp, 'fixedstock' ) && qpp_get_element( $qpp, 'use_stock' ) && qpp_get_element( $qpp, 'ruse_stock' ) && ($v['stock'] == qpp_get_element( $qpp, 'stocklabel' ) || empty($v['stock'])) ) {
        $errors['use_stock'] = 'error';
    }
    $match = preg_match( "/^[a-zA-Z]{6}[0-9]{2}[a-zA-Z][0-9]{2}[a-zA-Z][0-9]{3}[a-zA-Z]\$/", $v['cf'] );
    if ( $qpp['use_cf'] && $qpp['ruse_cf'] && ($v['cf'] == $qpp['cflabel'] || empty($v['cf']) || $match == false) ) {
        $errors['use_cf'] = 'error';
    }
    if ( $qpp['use_message'] && $qpp['ruse_message'] && ($v['yourmessage'] == $qpp['messagelabel'] || empty($v['yourmessage'])) ) {
        $errors['use_message'] = 'error';
    }
    if ( $qpp['useemail'] && $qpp['ruseemail'] && ($v['email'] == $qpp['emailblurb'] || empty($v['email'])) ) {
        $errors['email'] = 'error';
    }
    $errors = array_filter( $errors );
    return count( $errors ) == 0;
}

function qpp_process_values( $values, $id )
{
    $currency = qpp_get_stored_curr();
    $qpp = qpp_get_stored_options( $id );
    $coupon = qpp_get_stored_coupon( $id );
    $address = qpp_get_stored_address( $id );
    if ( $values['srt'] ) {
        $qpp['recurringhowmany'] = $values['srt'];
    }
    $custom = ( $qpp['custom'] ? $qpp['custom'] : md5( mt_rand() ) );
    
    if ( isset( $_REQUEST['combine'] ) && 'checked' == sanitize_text_field( $_REQUEST['combine'] ) ) {
        $arr = explode( '&', $values['reference'] );
        $values['reference'] = $arr[0];
        $values['amount'] = (double) qpp_format_amount( $currency[$id], $qpp, $arr[1] );
    }
    
    $amount = (double) $values['items'][0]['amount'];
    if ( isset( $_POST['itemamount'] ) && sanitize_text_field( $_POST['itemamount'] ) >= $values['items'][0]['amount'] ) {
        $amount = (double) qpp_format_amount( $currency[$id], $qpp, sanitize_text_field( $_POST['itemamount'] ) );
    }
    $quantity = (double) (( $values['items'][0]['quantity'] < 1 ? '1' : strip_tags( $values['items'][0]['quantity'] ) ));
    $percent = $fixedpostage = $percentpostage = 0;
    
    if ( $qpp['usepostage'] ) {
        
        if ( is_numeric( (double) $qpp['postagepercent'] ) ) {
            $percent = preg_replace( '/[^.,0-9]/', '', $qpp['postagepercent'] ) / 100;
            $percentpostage = round( $amount * $quantity * $percent, 2 );
        }
        
        if ( is_numeric( (double) $qpp['postagefixed'] ) ) {
            $fixedpostage = round( (double) preg_replace( '/[^.,0-9]/', '', $qpp['postagefixed'] ), 2 );
        }
    }
    
    $handling = $percentpostage + $fixedpostage;
    $multiple_handling = 0;
    $multiple_packing = 0;
    $combined_total = qpp_get_total( $values['items'] );
    
    if ( $qpp['use_multiples'] ) {
        if ( $qpp['usepostage'] && $qpp['postagepercent'] ) {
            $multiple_handling += $combined_total * $percent;
        }
        if ( $qpp['usepostage'] && $qpp['postagefixed'] ) {
            $multiple_handling += $fixedpostage;
        }
    } else {
        if ( isset( $handling ) ) {
            $multiple_handling = $handling;
        }
    }
    
    if ( isset( $qpp['stock'] ) && $qpp['stock'] == $values['stock'] && !$qpp['fixedstock'] ) {
        $values['stock'] = '';
    }
    $addr = array();
    $arr = array(
        'email',
        'firstname',
        'lastname',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        'night_phone_b'
    );
    foreach ( $arr as $item ) {
        
        if ( $address[$item] == $values[$item] ) {
            $addr[$item] = '';
            $values[$item] = '';
        } else {
            $addr[$item] = $values[$item];
        }
    
    }
    
    if ( $qpp['use_multiples'] ) {
        foreach ( $values['items'] as $k => $item ) {
            if ( $item['quantity'] ) {
                $details .= $item['item_name'] . ' x ' . $item['quantity'] . '</br>';
            }
        }
        $values['reference'] = $details;
        /*
        $coupon = qpp_get_stored_coupon($fid);
        for ($i=1; $i<=$coupon['couponnumber']; $i++) {
        	if ($values['couponblurb'] == $coupon['code'.$i]) {
        		$c_array['couponblurb'] = $values['couponblurb'];
        		if ($coupon['coupontype'.$i] == 'percent'.$i) $c_array['couponrate'] = $coupon['couponpercent'.$i];
        		if ($coupon['coupontype'.$i] == 'fixed'.$i) $c_array['couponamount'] = $coupon['couponfixed'.$i];
        	}
        }
        */
    }
    
    // Coupons
    $c_array = array(
        'use' => false,
    );
    
    if ( $values['coupon'] ) {
        $c_array = $values['coupon'];
        $c_array['use'] = true;
    }
    
    if ( !isset( $combined ) ) {
        $combined = 0;
    }
    $returning = array(
        'reference'   => (string) $values['reference'],
        'custom'      => (string) $custom,
        'cost'        => (double) $amount,
        'quantity'    => (int) $quantity,
        'totalamount' => (double) qpp_get_total( $values['items'] ),
        'combined'    => (double) $combined,
        'handling'    => (double) $multiple_handling,
        'coupon'      => (object) $c_array,
        'address'     => (object) $addr,
        'items'       => (array) $values['items'],
    );
    /*
    	Apply coupon
    */
    return (object) $returning;
}

function qpp_process_form( $values, $id, $payment )
{
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global  $quick_paypal_payments_fs ;
    $currency = qpp_get_stored_curr();
    $qpp = qpp_get_stored_options( $id );
    $send = qpp_get_stored_send( $id );
    $auto = qpp_get_stored_autoresponder( $id );
    $coupon = qpp_get_stored_coupon( $id );
    $address = qpp_get_stored_address( $id );
    $style = qpp_get_stored_style( $id );
    $qpp_setup = qpp_get_stored_setup();
    $ipn = qpp_get_stored_ipn();
    $list = qpp_get_stored_mailinglist();
    $ajaxurl = admin_url( 'admin-ajax.php' );
    $page_url = qpp_current_page_url();
    $page_url = ( $ajaxurl == $page_url ? $_SERVER['HTTP_REFERER'] : $page_url );
    $paypalurl = 'https://www.paypal.com/cgi-bin/webscr';
    if ( $values['srt'] ) {
        $qpp['recurringhowmany'] = $values['srt'];
    }
    if ( $send['customurl'] ) {
        $paypalurl = $send['customurl'];
    }
    if ( $qpp_setup['sandbox'] ) {
        $paypalurl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }
    if ( empty($send['thanksurl']) ) {
        $send['thanksurl'] = $page_url;
    }
    if ( empty($send['cancelurl']) ) {
        $send['cancelurl'] = $page_url;
    }
    
    if ( $send['target'] == 'newpage' ) {
        $target = ' target="_blank" ';
    } else {
        $target = '';
    }
    
    $custom = $payment->custom;
    $email = ( $send['email'] ? $send['email'] : $qpp_setup['email'] );
    $payment = qpp_process_values( $values, $id );
    $values['amount'] = $payment->totalamount + $payment->handling;
    $qpp_messages = get_option( 'qpp_messages' . $id );
    if ( !is_array( $qpp_messages ) ) {
        $qpp_messages = array();
    }
    $sentdate = date_i18n( 'd M Y' );
    if ( qpp_get_element( $qpp, 'stock' ) == qpp_get_element( $values, 'stock' ) && !qpp_get_element( $qpp, 'fixedstock', false ) ) {
        $values['stock'] = '';
    }
    if ( !$ipn['ipn'] && isset( $payment->coupon->code ) ) {
        qpp_check_coupon( $payment->coupon->code, $id );
    }
    
    if ( !$qpp_setup['nostore'] || $values['consent'] ) {
        $A = $payment->totalamount;
        
        if ( $payment->coupon->use ) {
            $discount = $payment->coupon->value;
            if ( $payment->coupon->type == 'percent' ) {
                $discount = $A * ($discount * 0.01);
            }
            $A -= $discount;
        }
        
        $A += $payment->handling;
        $qpp_messages[] = array(
            'field0'  => $sentdate,
            'field1'  => $payment->reference,
            'field2'  => $payment->quantity,
            'field3'  => $A,
            'field4'  => $values['stock'],
            'field5'  => $values['option1'],
            'field6'  => ( isset( $payment->coupon->code ) ? $payment->coupon->code : '' ),
            'field8'  => $payment->address->email,
            'field9'  => $payment->address->firstname,
            'field10' => $payment->address->lastname,
            'field11' => $payment->address->address1,
            'field12' => $payment->address->address2,
            'field13' => $payment->address->city,
            'field14' => $payment->address->state,
            'field15' => $payment->address->zip,
            'field16' => $payment->address->country,
            'field17' => $payment->address->night_phone_b,
            'field18' => $payment->custom,
            'field19' => $values['yourmessage'],
            'field20' => $values['datepicker'],
            'field21' => $values['cf'],
            'field22' => qpp_get_element( $values, 'consent' ),
        );
        update_option( 'qpp_messages' . $id, $qpp_messages );
    }
    
    if ( $auto['whenconfirm'] == 'aftersubmission' ) {
        qpp_send_confirmation( $values, $id );
    }
    $consent = '';
    if ( isset( $qpp['use_consent'] ) && 'checked' == $qpp['use_consent'] ) {
        
        if ( isset( $values['consent'] ) && 'checked' == $values['consent'] ) {
            if ( !empty($qpp['consentpaypal']) ) {
                $consent = ' [ ' . $qpp['consentpaypal'] . ' ]';
            }
        } else {
            if ( !empty($qpp['noconsentpaypal']) ) {
                $consent = ' [ ' . $qpp['noconsentpaypal'] . ' ]';
            }
        }
    
    }
    $content = '<span><h2 id="qpp_reload">' . $send['waiting'] . '</h2>
    <script type="text/javascript" language="javascript">
    document.querySelector("#qpp_reload").scrollIntoView();
    </script>
    <form action="' . $paypalurl . '" method="post" name="frmCart" id="frmCart" ' . $target . '>
    <input type="hidden" name="custom" value="' . trim( $payment->custom ) . '"/>
    <input type="hidden" name="tax" value="0.00">
    <input type="hidden" name="bn" value="quickplugins_SP">
    <input type="hidden" name="business" value="' . trim( $email ) . '">
    <input type="hidden" name="return" value="' . trim( $send['thanksurl'] ) . '">
    <input type="hidden" name="cancel_return" value="' . trim( $send['cancelurl'] ) . '">
    <input type="hidden" name="currency_code" value="' . trim( $currency[$id] ) . '">';
    if ( $qpp_setup['image_url'] ) {
        $content .= '<input type="hidden" name="image_url" value="' . $qpp_setup['image_url'] . '">';
    }
    
    if ( $qpp['use_multiples'] ) {
        $content .= '<input type="hidden" name="upload" value="1">';
        if ( $payment->coupon->use ) {
            
            if ( $payment->coupon->type == 'percent' ) {
                $content .= '<input type="hidden" name="discount_rate_cart" value="' . $payment->coupon->value . '" />';
            } else {
                $content .= '<input type="hidden" name="discount_amount_cart" value="' . $payment->coupon->value . '" />';
            }
        
        }
        // Coupons End
        foreach ( $values['items'] as $k => $item ) {
            $display_consent = '';
            if ( 0 == $k ) {
                $display_consent = $consent;
            }
            $content .= '<input type="hidden" name="item_name_' . ((int) $k + 1) . '" value="' . $item['item_name'] . $display_consent . '">
				<input type="hidden" name="amount_' . ((int) $k + 1) . '" value="' . $item['amount'] . '">
				<input type="hidden" name="quantity_' . ((int) $k + 1) . '" value="' . $item['quantity'] . '">';
        }
    } else {
        $content .= '<input type="hidden" name="item_name" value="' . strip_tags( $payment->reference ) . $consent . '"/>';
    }
    
    $ipn_listener = ( $ipn['listener'] ? $ipn['listener'] : $ipn['default'] );
    if ( 'checked' === $ipn['ipn'] ) {
        $content .= '<input type="hidden" name="notify_url" value = "' . $ipn_listener . '">';
    }
    
    if ( $qpp['userecurring'] ) {
        $content .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
    } elseif ( $qpp['use_multiples'] ) {
        $content .= '<input type="hidden" name="cmd" value="_cart">';
    } elseif ( isset( $send['donate'] ) && $send['donate'] ) {
        $content .= '<input type="hidden" name="cmd" value="_donations">';
    } else {
        $content .= '<input type="hidden" name="cmd" value="_xclick">';
    }
    
    if ( $qpp['use_stock'] ) {
        $content .= '<input type="hidden" name="item_number" value="' . strip_tags( $values['stock'] ) . '">';
    }
    $multi_p_s = '';
    $multi_p_h = '';
    
    if ( $qpp['use_multiples'] ) {
        $multi_p_s = '_1';
        $multi_p_h = '_cart';
    }
    
    
    if ( $qpp['userecurring'] ) {
        $content .= '<input type="hidden" name="a3" value="' . $payment->cost . '">
        <input type="hidden" name="p3" value="1">
        <input type="hidden" name="t3" value="' . $qpp['recurring'] . '">
        <input type="hidden" name="src" value="1">
        <input type="hidden" name="srt" value="' . $qpp['recurringhowmany'] . '">';
    } else {
        
        if ( !$qpp['use_multiples'] ) {
            $content .= '<input type="hidden" name="quantity" value="' . $payment->quantity . '">
			<input type="hidden" name="amount" value="' . $payment->cost . '">';
            // Apply coupon
            
            if ( $payment->coupon->use ) {
                $name = 'discount_amount';
                
                if ( $payment->coupon->type == 'percent' ) {
                    $content .= '<input type="hidden" name="discount_rate" value="' . $payment->coupon->value . '" />';
                    $content .= '<input type="hidden" name="discount_rate2" value="' . $payment->coupon->value . '" />';
                } else {
                    $content .= '<input type="hidden" name="' . $name . '" value="' . $payment->coupon->value . '" />';
                }
            
            }
        
        }
        
        if ( $qpp['use_options'] ) {
            $content .= '<input type="hidden" name="on0" value="' . $qpp['optionlabel'] . '" />
            <input type="hidden" name="os0" value="' . $values['option1'] . '" />';
        }
        
        if ( $qpp['usepostage'] ) {
            $content .= '<input type="hidden" name="handling' . $multi_p_h . '" value="' . $payment->handling . '">';
        } else {
            $content .= '<input type="hidden" name="handling' . $multi_p_h . '" value="0.00">';
        }
    
    }
    
    if ( isset( $send['use_lc'] ) && $send['use_lc'] ) {
        $content .= '<input type="hidden" name="lc" value="' . $send['lc'] . '">
        <input type="hidden" name="country" value="' . $send['lc'] . '">';
    }
    
    if ( $qpp['useaddress'] ) {
        $arr = array(
            'email',
            'firstname',
            'lastname',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b'
        );
        foreach ( $arr as $item ) {
            if ( $payment->address->{$item} && $address[$item] != $payment->address->{$item} ) {
                $content .= '<input type="hidden" name="' . $item . '" value="' . strip_tags( $payment->address->{$item} ) . '">';
            }
        }
    }
    
    $content .= '</form>';
    if ( defined( 'QPP_PAYPAL_DEBUG' ) && QPP_PAYPAL_DEBUG ) {
        //  echo 'DEBUG';
    }
    if ( isset( $send['createuser'] ) && $send['createuser'] ) {
        qpp_create_user( $values );
    }
    
    if ( isset( $ipn['ipn'] ) && $ipn['ipn'] == 'checked' ) {
        global  $qpp_current_custom ;
        $qpp_current_custom = $payment->custom;
    }
    
    return $content;
}

function qpp_current_page_url()
{
    $pageURL = 'http';
    if ( !isset( $_SERVER['HTTPS'] ) ) {
        $_SERVER['HTTPS'] = '';
    }
    if ( !empty($_SERVER["HTTPS"]) ) {
        $pageURL .= "s";
    }
    $pageURL .= "://";
    
    if ( $_SERVER["SERVER_PORT"] != "80" && $_SERVER['SERVER_PORT'] != '443' ) {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    
    return $pageURL;
}

function qpp_currency( $id )
{
    $currency = qpp_get_stored_curr();
    $c = array();
    $c['a'] = $c['b'] = '';
    if ( !isset( $currency[$id] ) ) {
        $currency[$id] = 0;
    }
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
    foreach ( $before as $item => $key ) {
        if ( $item == $currency[$id] ) {
            $c['b'] = $key;
        }
    }
    foreach ( $after as $item => $key ) {
        if ( $item == $currency[$id] ) {
            $c['a'] = $key;
        }
    }
    return $c;
}

function qpp_sanitize( $input )
{
    $output = array();
    if ( is_array( $input ) ) {
        foreach ( $input as $var => $val ) {
            $output[$var] = filter_var( $val, FILTER_SANITIZE_STRING );
        }
    }
    return $output;
}

function register_qpp_widget()
{
    register_widget( 'qpp_Widget' );
}

add_action( 'widgets_init', 'register_qpp_widget' );
class qpp_widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'qpp_widget',
            // Base ID
            'Paypal Payments',
            // Name
            array(
                'description' => __( 'Paypal Payments', 'Add paypal payment form to your sidebar' ),
            )
        );
    }
    
    public function widget( $args, $instance )
    {
        extract( $args, EXTR_SKIP );
        $id = $instance['id'];
        $amount = $instance['amount'];
        $form = $instance['form'];
        echo  qpp_loop( $instance ) ;
    }
    
    public function update( $new_instance, $old_instance )
    {
        $instance = $old_instance;
        $instance['id'] = $new_instance['id'];
        $instance['amount'] = $new_instance['amount'];
        $instance['form'] = $new_instance['form'];
        return $instance;
    }
    
    public function form( $instance )
    {
        $instance = wp_parse_args( (array) $instance, array(
            'amount' => '',
            'id'     => '',
            'form'   => '',
        ) );
        $id = $instance['id'];
        $amount = $instance['amount'];
        $form = $instance['form'];
        $qpp_setup = qpp_get_stored_setup();
        ?>
        <h3>Select Form:</h3>
        <select class="widefat" name="<?php 
        echo  $this->get_field_name( 'form' ) ;
        ?>">
			<?php 
        $arr = explode( ",", $qpp_setup['alternative'] );
        foreach ( $arr as $item ) {
            
            if ( $item == '' ) {
                $showname = 'default';
                $item = '';
            } else {
                $showname = $item;
            }
            
            
            if ( $showname == $form || $form == '' ) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            
            ?>
                <option value="<?php 
            echo  $item ;
            ?>"
                        id="<?php 
            echo  $this->get_field_id( 'form' ) ;
            ?>" <?php 
            echo  $selected ;
            ?>><?php 
            echo  $showname ;
            ?></option><?php 
        }
        ?>
        </select>
        <h3>Presets:</h3>
        <p><label for="<?php 
        echo  $this->get_field_id( 'id' ) ;
        ?>">Payment Reference: <input class="widefat"
                                                                                             id="<?php 
        echo  $this->get_field_id( 'id' ) ;
        ?>"
                                                                                             name="<?php 
        echo  $this->get_field_name( 'id' ) ;
        ?>"
                                                                                             type="text"
                                                                                             value="<?php 
        echo  attribute_escape( $id ) ;
        ?>"/></label>
        </p>
        <p><label for="<?php 
        echo  $this->get_field_id( 'amount' ) ;
        ?>">Amount: <input class="widefat"
                                                                                      id="<?php 
        echo  $this->get_field_id( 'amount' ) ;
        ?>"
                                                                                      name="<?php 
        echo  $this->get_field_name( 'amount' ) ;
        ?>"
                                                                                      type="text"
                                                                                      value="<?php 
        echo  attribute_escape( $amount ) ;
        ?>"/></label>
        </p>
        <p>Leave blank to use the default settings.</p>
        <p>To configure the payment form use the <a
                    href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/quick-paypal-payments.php">Settings</a>
            page</p>
		<?php 
    }

}
function qpp_generate_css()
{
    $qpp_form = qpp_get_stored_setup();
    $arr = explode( ",", $qpp_form['alternative'] );
    $handle = $code = $font = $inputfont = $selectfont = $submitfont = $bg = $header = false;
    foreach ( $arr as $item ) {
        $corners = $input = $background = $paragraph = $submit = '';
        $style = qpp_get_stored_style( $item );
        $settings = qpp_get_stored_options( $item );
        
        if ( $item != '' ) {
            $id = '.' . $item;
        } else {
            $id = '.default';
        }
        
        
        if ( $style['font'] == 'plugin' ) {
            $font = "font-family: " . $style['text-font-family'] . "; font-size: " . $style['text-font-size'] . ";color: " . $style['text-font-colour'] . ";line-height:100%;";
            $inputfont = "font-family: " . $style['font-family'] . "; font-size: " . $style['font-size'] . "; color: " . $style['font-colour'] . ";";
            $selectfont = "font-family: " . $style['font-family'] . "; font-size: inherit; color: " . $style['font-colour'] . ";";
            $submitfont = "font-family: " . $style['font-family'];
            if ( $style['header-size'] || $style['header-colour'] ) {
                $header = ".qpp-style" . $id . " " . $style['header-type'] . " {font-size: " . $style['header-size'] . "; color: " . $style['header-colour'] . ";}";
            }
        }
        
        $input = ".qpp-style" . $id . " input[type=text], .qpp-style" . $id . " textarea {border: " . $style['input-border'] . ";" . $inputfont . ";height:auto;line-height:normal; " . $style['line_margin'] . ";}";
        $input .= ".qpp-style" . $id . " select {border: " . $style['input-border'] . ";" . $selectfont . ";height:auto;line-height:normal;}";
        $input .= ".qpp-style" . $id . " .qppcontainer input + label, .qpp-style" . $id . " .qppcontainer textarea + label {" . $inputfont . "}";
        $required = ".qpp-style" . $id . " input[type=text].required, .qpp-style" . $id . " textarea.required {border: " . $style['required-border'] . ";}";
        $paragraph = ".qpp-style" . $id . " p, .qpp-style" . $id . " li {margin:4px 0 4px 0;padding:0;" . $font . ";}";
        if ( $style['submitwidth'] == 'submitpercent' ) {
            $submitwidth = 'width:100%;';
        }
        if ( $style['submitwidth'] == 'submitrandom' ) {
            $submitwidth = 'width:auto;';
        }
        if ( $style['submitwidth'] == 'submitpixel' ) {
            $submitwidth = 'width:' . $style['submitwidthset'] . ';';
        }
        
        if ( $style['submitposition'] == 'submitleft' ) {
            $submitposition = 'text-align:left;';
        } else {
            $submitposition = 'text-align:right;';
        }
        
        if ( $style['submitposition'] == 'submitmiddle' ) {
            $submitposition = 'margin:0 auto;text-align:center;';
        }
        $submitbutton = ".qpp-style" . $id . " p.submit {" . $submitposition . "}\n.qpp-style" . $id . " #submitimage {" . $submitwidth . "height:auto;overflow:hidden;}\n.qpp-style" . $id . " #submit, .qpp-style" . $id . " #submitimage {" . $submitwidth . "color:" . $style['submit-colour'] . ";background:" . $style['submit-background'] . ";border:" . $style['submit-border'] . ";" . $submitfont . ";font-size: inherit;text-align:center;}";
        $submithover = ".qpp-style" . $id . " #submit:hover {background:" . $style['submit-hover-background'] . ";}";
        $couponbutton = ".qpp-style" . $id . " #couponsubmit, .qpp-style" . $id . " #couponsubmit:hover{" . $submitwidth . "color:" . $style['coupon-colour'] . ";background:" . $style['coupon-background'] . ";border:" . $style['submit-border'] . ";" . $submitfont . ";font-size: inherit;margin: 3px 0px 7px;padding: 6px;text-align:center;}";
        if ( $style['border'] != 'none' ) {
            $border = ".qpp-style" . $id . " #" . $style['border'] . " {border:" . $style['form-border'] . ";}";
        }
        
        if ( $style['background'] == 'white' ) {
            $bg = "background:#FFF";
            $background = ".qpp-style" . $id . " div {background:#FFF;}";
        }
        
        
        if ( $style['background'] == 'color' ) {
            $background = ".qpp-style" . $id . " div {background:" . $style['backgroundhex'] . ";}";
            $bg = "background:" . $style['backgroundhex'] . ";";
        }
        
        if ( $style['backgroundimage'] ) {
            $background = ".qpp-style" . $id . " #" . $style['border'] . " {background: url('" . $style['backgroundimage'] . "');}";
        }
        $formwidth = preg_split( '#(?<=\\d)(?=[a-z%])#i', $style['width'] );
        if ( !isset( $formwidth[1] ) ) {
            $formwidth[1] = 'px';
        }
        
        if ( $style['widthtype'] == 'pixel' ) {
            $width = $formwidth[0] . $formwidth[1];
        } else {
            $width = '100%';
        }
        
        
        if ( $style['corners'] == 'round' ) {
            $corner = '5px';
        } else {
            $corner = '0';
        }
        
        $corners = ".qpp-style" . $id . " input[type=text], .qpp-style" . $id . " textarea, .qpp-style" . $id . " select, .qpp-style" . $id . " #submit, .qpp-style" . $id . " #couponsubmit {border-radius:" . $corner . ";}";
        if ( $style['corners'] == 'theme' ) {
            $corners = '';
        }
        
        if ( $settings['use_slider'] ) {
            $handle = (double) $style['slider-thickness'] + 1;
            $slider = '.qpp-style' . $id . ' div.rangeslider, .qpp-style' . $id . ' div.rangeslider__fill {height: ' . $style['slider-thickness'] . 'em;background: ' . $style['slider-background'] . ';}
.qpp-style' . $id . ' div.rangeslider__fill {background: ' . $style['slider-revealed'] . ';}
.qpp-style' . $id . ' div.rangeslider__handle {background: ' . $style['handle-background'] . ';border: 1px solid ' . $style['handle-border'] . ';width: ' . $handle . 'em;height: ' . $handle . 'em;position: absolute;top: -0.5em;-webkit-border-radius:' . $style['handle-colours'] . '%;-moz-border-radius:' . $style['handle-corners'] . '%;-ms-border-radius:' . $style['handle-corners'] . '%;-o-border-radius:' . $style['handle-corners'] . '%;border-radius:' . $style['handle-corners'] . '%;}
.qpp-style' . $id . ' div.qpp-slideroutput{font-size:' . $style['output-size'] . ';color:' . $style['output-colour'] . ';}';
        } else {
            $slider = '';
        }
        
        $code .= ".qpp-style" . $id . " {width:" . $width . ";max-width:100%; }" . $border . $corners . $header . $paragraph . $input . $required . $background . $submitbutton . $submithover . $couponbutton . $slider;
        $code .= '.qpp-style' . $id . ' input#qpptotal {font-weight:bold;font-size:inherit;padding: 0;margin-left:3px;border:none;' . $bg . '}';
        if ( $style['use_custom'] == 'checked' ) {
            $code .= $style['custom'];
        }
    }
    return $code;
}

function qpp_head_css()
{
    $qpp_setup = qpp_get_stored_setup();
    $mode = ( $qpp_setup['sandbox'] ? 'SANDBOX' : 'PRODUCTION' );
    $data = '<style type="text/css" media="screen">' . "\r\n" . qpp_generate_css() . "\r\n" . '</style>
    <script type="text/javascript">qpp_containers = new Array(); to_list = new Array(); to_totals = new Array();</script>';
    echo  $data ;
}

function qpp_plugin_action_links( $links, $file )
{
    
    if ( $file == QUICK_PAYPAL_PAYMENTS_PLUGIN_FILE ) {
        $qpp_links = '<a href="' . get_admin_url() . 'options-general.php?page=quick-paypal-payments">' . __( 'Settings' ) . '</a>';
        array_unshift( $links, $qpp_links );
    }
    
    return $links;
}

function qpp_report( $atts )
{
    extract( shortcode_atts( array(
        'form' => '',
    ), $atts ) );
    return qpp_messagetable( $form, '' );
}

function qpp_messagetable( $id, $email )
{
    $qpp_setup = qpp_get_stored_setup();
    $qpp_ipn = qpp_get_stored_ipn();
    $options = qpp_get_stored_options( $id );
    $message = get_option( 'qpp_messages' . $id );
    $coupon = qpp_get_stored_coupon( $id );
    $messageoptions = qpp_get_stored_msg();
    $address = qpp_get_stored_address( $id );
    $c = qpp_currency( $id );
    $showthismany = '9999';
    $content = $padding = $count = $arr = '';
    if ( $messageoptions['messageqty'] == 'fifty' ) {
        $showthismany = '50';
    }
    if ( $messageoptions['messageqty'] == 'hundred' ) {
        $showthismany = '100';
    }
    ${$messageoptions['messageqty']} = "checked";
    ${$messageoptions['messageorder']} = "checked";
    if ( !is_array( $message ) ) {
        $message = array();
    }
    $title = $id;
    if ( $id == '' ) {
        $title = 'Default';
    }
    if ( $options['fixedamount'] ) {
        $options['inputamount'] = ( $options['shortcodeamount'] ? $options['shortcodeamount'] : 'Amount' );
    }
    if ( $options['fixedreference'] ) {
        $options['inputreference'] = ( $options['shortcodereference'] ? $options['shortcodereference'] : 'Reference' );
    }
    
    if ( !$email ) {
        $dashboard = '<div class="wrap"><div id="qpp-widget">';
    } else {
        $padding = 'cellpadding="5"';
    }
    
    $dashboard .= '<table cellspacing="0" ' . $padding . '><tr>';
    if ( !$email ) {
        $dashboard .= '<th></th>';
    }
    $dashboard .= '<th style="text-align:left">Date Sent</th>';
    foreach ( explode( ',', $options['sort'] ) as $name ) {
        $title = '';
        switch ( $name ) {
            case 'field1':
                $dashboard .= '<th style="text-align:left">' . $options['inputreference'] . '</th>';
                break;
            case 'field2':
                if ( !$options['use_multiples'] ) {
                    $dashboard .= '<th style="text-align:left">' . $options['quantitylabel'] . '</th>';
                }
                break;
            case 'field3':
                $dashboard .= '<th style="text-align:left">' . $options['inputamount'] . '</th>';
                break;
            case 'field4':
                if ( $options['use_stock'] ) {
                    $dashboard .= '<th style="text-align:left">' . $options['stocklabel'] . '</th>';
                }
                break;
            case 'field5':
                if ( $options['use_options'] ) {
                    $dashboard .= '<th style="text-align:left">' . $options['optionlabel'] . '</th>';
                }
                break;
            case 'field6':
                if ( $options['usecoupon'] ) {
                    $dashboard .= '<th style="text-align:left">' . $options['couponblurb'] . '</th>';
                }
                break;
            case 'field8':
                if ( $options['useemail'] || !$options['useemail'] && $address['email'] ) {
                    $dashboard .= '<th style="text-align:left">' . $options['emailblurb'] . '</th>';
                }
                break;
            case 'field17':
                if ( $options['use_message'] ) {
                    $dashboard .= '<th style="text-align:left:max-width:20%;">' . $options['messagelabel'] . '</th>';
                }
                break;
            case 'field18':
                if ( $options['use_datepicker'] ) {
                    $dashboard .= '<th style="text-align:left:max-width:20%;">' . $options['datepickerlabel'] . '</th>';
                }
                break;
            case 'field21':
                if ( $options['use_cf'] ) {
                    $dashboard .= '<th style="text-align:left:max-width:20%;">' . $options['cflabel'] . '</th>';
                }
                break;
            case 'field22':
                if ( $options['use_consent'] ) {
                    $dashboard .= '<th style="text-align:left:max-width:20%;">Consent</th>';
                }
                break;
        }
    }
    
    if ( $messageoptions['showaddress'] ) {
        $arr = array(
            'firstname',
            'lastname',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b'
        );
        foreach ( $arr as $item ) {
            $dashboard .= '<th style="text-align:left">' . $address[$item] . '</th>';
        }
    }
    
    if ( $qpp_ipn['ipn'] ) {
        $dashboard .= '<th>' . $qpp_ipn['title'] . '</th>';
    }
    $dashboard .= '</tr>';
    
    if ( $messageoptions['messageorder'] == 'newest' ) {
        $i = count( $message ) - 1;
        $count = 0;
        foreach ( array_reverse( $message ) as $value ) {
            
            if ( $count < $showthismany ) {
                if ( $value['field0'] ) {
                    $report = 'messages';
                }
                $content .= qpp_messagecontent(
                    $id,
                    $value,
                    $options,
                    $c,
                    $messageoptions,
                    $address,
                    $arr,
                    $i,
                    $email
                );
                $count = $count + 1;
                $i--;
            }
        
        }
    } else {
        $i = 0;
        $count = 0;
        foreach ( $message as $value ) {
            
            if ( $count < $showthismany ) {
                if ( $value['field0'] ) {
                    $report = 'messages';
                }
                $content .= qpp_messagecontent(
                    $id,
                    $value,
                    $options,
                    $c,
                    $messageoptions,
                    $address,
                    $arr,
                    $i,
                    $email
                );
                $count = $count + 1;
                $i++;
            }
        
        }
    }
    
    
    if ( $report ) {
        $dashboard .= $content . '</table>';
    } else {
        $dashboard .= '</table><p>No messages found</p>';
    }
    
    for ( $i = 1 ;  $i <= $coupon['couponnumber'] ;  $i++ ) {
        if ( $coupon['qty' . $i] > 0 ) {
            $coups .= '<p>' . $coupon['code' . $i] . ' - ' . $coupon['qty' . $i] . '</p>';
        }
    }
    if ( $coups ) {
        $dashboard .= '<h2>Coupons remaining</h2>' . $coups;
    }
    $dashboard .= '</div></div>';
    return $dashboard;
}

function qpp_messagecontent(
    $id,
    $value,
    $options,
    $c,
    $messageoptions,
    $address,
    $arr,
    $i,
    $email
)
{
    $qpp_setup = qpp_get_stored_setup();
    $qpp_ipn = qpp_get_stored_ipn();
    if ( $value['field18'] == 'Paid' && $messageoptions['hidepaid'] ) {
        return;
    }
    $content = '<tr>';
    if ( !$email ) {
        $content .= '<td><input type="checkbox" name="' . $i . '" value="checked" /></td>';
    }
    $content .= '<td>' . strip_tags( $value['field0'] ) . '</td>';
    foreach ( explode( ',', $options['sort'] ) as $name ) {
        $title = '';
        $amount = preg_replace( '/[^.,0-9]/', '', $value['field3'] );
        switch ( $name ) {
            case 'field1':
                $content .= '<td>' . $value['field1'] . '</td>';
                break;
            case 'field2':
                if ( !$options['use_multiples'] ) {
                    $content .= '<td>' . $value['field2'] . '</td>';
                }
                break;
            case 'field3':
                $content .= '<td>' . $c['b'] . $amount . $c['a'] . '</td>';
                break;
            case 'field4':
                
                if ( $options['use_stock'] ) {
                    if ( $options['stocklabel'] == $value['field4'] ) {
                        $value['field4'] = '';
                    }
                    $content .= '<td>' . $value['field4'] . '</td>';
                }
                
                break;
            case 'field5':
                
                if ( $options['use_options'] ) {
                    if ( $options['optionlabel'] == $value['field5'] ) {
                        $value['field5'] = '';
                    }
                    $content .= '<td>' . $value['field5'] . '</td>';
                }
                
                break;
            case 'field6':
                
                if ( $options['usecoupon'] ) {
                    if ( $options['couponblurb'] == $value['field6'] ) {
                        $value['field6'] = '';
                    }
                    $content .= '<td>' . $value['field6'] . '</td>';
                }
                
                break;
            case 'field8':
                
                if ( $options['useemail'] || !$options['useemail'] && $address['email'] ) {
                    if ( $options['emailblurb'] == $value['field8'] ) {
                        $value['field8'] = '';
                    }
                    $content .= '<td>' . $value['field8'] . '</td>';
                }
                
                break;
            case 'field17':
                
                if ( $options['use_message'] ) {
                    if ( $options['messagelabel'] == $value['field19'] ) {
                        $value['field19'] = '';
                    }
                    $content .= '<td>' . $value['field19'] . '</td>';
                }
                
                break;
            case 'field18':
                
                if ( $options['use_datepicker'] ) {
                    if ( $options['datepickerlabel'] == $value['field20'] ) {
                        $value['field20'] = '';
                    }
                    $content .= '<td>' . $value['field20'] . '</td>';
                }
                
                break;
            case 'field21':
                
                if ( $options['use_cf'] ) {
                    if ( $options['cflabel'] == $value['field21'] ) {
                        $value['field21'] = '';
                    }
                    $content .= '<td>' . $value['field21'] . '</td>';
                }
                
                break;
            case 'field22':
                
                if ( $options['use_consent'] ) {
                    if ( $options['consentlabel'] == $value['field22'] ) {
                        $value['field22'] = '';
                    }
                    $content .= '<td>' . $value['field22'] . '</td>';
                }
                
                break;
        }
    }
    
    if ( $messageoptions['showaddress'] ) {
        $arr = array(
            'field9',
            'field10',
            'field11',
            'field12',
            'field13',
            'field14',
            'field15',
            'field16',
            'field17'
        );
        foreach ( $arr as $item ) {
            if ( $value[$item] == $address[$item] ) {
                $value[$item] = '';
            }
            $content .= '<td>' . $value[$item] . '</td>';
        }
    }
    
    
    if ( $qpp_ipn['ipn'] ) {
        $ipn = ( $qpp_setup['sandbox'] ? $value['field18'] : '' );
        $content .= ( $value['field18'] == "Paid" ? '<td>' . $qpp_ipn['paid'] . '</td>' : '<td>' . $ipn . '</td>' );
    }
    
    $content .= '</tr>';
    return $content;
}

function qpp_ipn()
{
    if ( !isset( $_REQUEST['qpp_ipn'] ) ) {
        return;
    }
    if ( !defined( 'IPN_DEBUG_LOG_FILE' ) ) {
        define( 'IPN_DEBUG_LOG_FILE', false );
    }
    $qpp_setup = qpp_get_stored_setup();
    $qpp_ipn = qpp_get_stored_ipn();
    $qpp_setup['disable_error'] = false;
    $raw_post_data = file_get_contents( 'php://input' );
    $raw_post_array = explode( '&', $raw_post_data );
    
    if ( false !== IPN_DEBUG_LOG_FILE ) {
        error_log( date( '[Y-m-d H:i e] ' ) . "INCOMING IPN" . PHP_EOL, 3, IPN_DEBUG_LOG_FILE );
        error_log( date( '[Y-m-d H:i e] ' ) . $raw_post_data . PHP_EOL, 3, IPN_DEBUG_LOG_FILE );
    }
    
    $myPost = array();
    foreach ( $raw_post_array as $keyval ) {
        $keyval = explode( '=', $keyval );
        if ( count( $keyval ) == 2 ) {
            $myPost[$keyval[0]] = urldecode( $keyval[1] );
        }
    }
    // see https://developer.paypal.com/docs/ipn/integration-guide/ht-ipn/#do-it
    $req = 'cmd=_notify-validate';
    if ( function_exists( 'get_magic_quotes_gpc' ) ) {
        $get_magic_quotes_exists = true;
    }
    foreach ( $myPost as $key => $value ) {
        
        if ( $get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1 ) {
            $value = urlencode( stripslashes( $value ) );
        } else {
            $value = urlencode( $value );
        }
        
        $req .= "&{$key}={$value}";
    }
    
    if ( $qpp_setup['sandbox'] ) {
        $paypal_url = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    } else {
        $paypal_url = "https://ipnpb.paypal.com/cgi-bin/webscr";
    }
    
    $response = wp_remote_post( $paypal_url, array(
        'timeout' => 30,
        'body'    => $req,
    ) );
    
    if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
        if ( false !== IPN_DEBUG_LOG_FILE ) {
            error_log( date( '[Y-m-d H:i e] ' ) . "Can't connect to PayPal to validate IPN message: Indetermined" . PHP_EOL, 3, IPN_DEBUG_LOG_FILE );
        }
        return;
    }
    
    $status = wp_remote_retrieve_body( $response );
    
    if ( false !== IPN_DEBUG_LOG_FILE ) {
        error_log( date( '[Y-m-d H:i e] ' ) . "HTTP request of validation request:  for IPN payload: {$req}" . print_r( wp_remote_retrieve_headers( $response ), true ) . PHP_EOL, 3, IPN_DEBUG_LOG_FILE );
        error_log( date( '[Y-m-d H:i e] ' ) . "HTTP response of validation request: {$status}" . PHP_EOL, 3, IPN_DEBUG_LOG_FILE );
    }
    
    
    if ( 'VERIFIED' == $status ) {
        $custom = sanitize_text_field( $_POST['custom'] );
        $arr = explode( ",", $qpp_setup['alternative'] );
        foreach ( $arr as $item ) {
            $message = get_option( 'qpp_messages' . $item );
            $count = count( $message );
            for ( $i = 0 ;  $i <= $count ;  $i++ ) {
                
                if ( $message[$i]['field18'] == $custom && $message[$i]['field1'] ) {
                    $message[$i]['field18'] = 'Paid';
                    $auto = qpp_get_stored_autoresponder( $item );
                    $send = qpp_get_stored_send( $item );
                    qpp_check_coupon( $message[$i]['field6'], $item );
                    
                    if ( $auto['enable'] && $message[$i]['field8'] && $auto['whenconfirm'] == 'afterpayment' ) {
                        $values = array(
                            'reference'     => $message[$i]['field1'],
                            'quantity'      => $message[$i]['field2'],
                            'amount'        => $message[$i]['field3'],
                            'stock'         => $message[$i]['field4'],
                            'option1'       => $message[$i]['field5'],
                            'email'         => $message[$i]['field8'],
                            'firstname'     => $message[$i]['field9'],
                            'lastname'      => $message[$i]['field10'],
                            'address1'      => $message[$i]['field11'],
                            'address2'      => $message[$i]['field12'],
                            'city'          => $message[$i]['field13'],
                            'state'         => $message[$i]['field14'],
                            'zip'           => $message[$i]['field15'],
                            'country'       => $message[$i]['field16'],
                            'night_phone_b' => $message[$i]['field17'],
                            'yourmessage'   => $message[$i]['field19'],
                            'datepicker'    => $message[$i]['field20'],
                            'cf'            => $message[$i]['field21'],
                            'consent'       => $message[$i]['field22'],
                        );
                        qpp_send_confirmation( $values, $item );
                    }
                    
                    
                    if ( $qpp_ipn['deleterecord'] ) {
                        unset( $message[$i] );
                        $message = array_values( $message );
                    }
                    
                    update_option( 'qpp_messages' . $item, $message );
                }
            
            }
        }
        if ( false !== IPN_DEBUG_LOG_FILE ) {
            error_log( date( '[Y-m-d H:i e] ' ) . "Verified IPN: {$req} " . PHP_EOL, 3, IPN_DEBUG_LOG_FILE );
        }
    } else {
        
        if ( false !== IPN_DEBUG_LOG_FILE ) {
            error_log( date( '[Y-m-d H:i e] ' ) . "IPN response: {$status} {$req}" . PHP_EOL, 3, IPN_DEBUG_LOG_FILE );
            error_log( date( '[Y-m-d H:i e] ' ) . "RAW DATA: {$response}" . PHP_EOL, 3, IPN_DEBUG_LOG_FILE );
        }
    
    }

}

function qpp_get_coupon( $couponcode, $id )
{
    $coupon = qpp_get_stored_coupon( $id );
    $couponcode = trim( $couponcode );
    if ( $couponcode == '' ) {
        return false;
    }
    for ( $i = 1 ;  $i <= $coupon['couponnumber'] ;  $i++ ) {
        
        if ( isset( $coupon['code' . $i] ) && $couponcode == $coupon['code' . $i] ) {
            $r = array(
                'id'      => $i,
                'code'    => $coupon['code' . $i],
                'qty'     => $coupon['qty' . $i],
                'expired' => $coupon['expired' . $i],
                'fixed'   => $coupon['couponfixed' . $i],
                'percent' => $coupon['couponpercent' . $i],
                'type'    => preg_replace( '/([0-9]+)/', '', $coupon['coupontype' . $i] ),
            );
            $r['value'] = (double) str_replace( '%', '', $r[$r['type']] );
            return $r;
        }
    
    }
    return false;
}

function qpp_check_coupon( $couponcode, $id )
{
    $coupon = qpp_get_stored_coupon( $id );
    $c = qpp_get_coupon( $couponcode, $id );
    if ( $c['qty'] > 0 ) {
        $c['qty' . $c['id']]--;
    }
    
    if ( $c['qty' . $c['id']] <= 0 ) {
        $coupon['code' . $c['id']] = $coupon['qty' . $c['id']] = '';
        $coupon['expired' . $c['id']] = true;
    }
    
    update_option( 'qpp_coupon' . $id, $coupon );
}

function qpp_send_confirmation( $values, $form )
{
    $qpp_setup = qpp_get_stored_setup();
    $qpp = qpp_get_stored_options( $form );
    $address = qpp_get_stored_address( $form );
    $send = qpp_get_stored_send( $form );
    $auto = qpp_get_stored_autoresponder( $form );
    $currency = qpp_get_stored_curr();
    $curr = $currency[$form];
    $c = qpp_currency( $form );
    $auto['fromename'] = ( qpp_get_element( $auto, 'fromename', false ) ? $auto['fromename'] : get_bloginfo( 'name' ) );
    $auto['fromemail'] = ( qpp_get_element( $auto, 'fromemail', false ) ? $auto['fromemail'] : get_bloginfo( 'admin_email' ) );
    $confirmemail = ( qpp_get_element( $send, 'confirmemail', false ) ? $send['confirmemail'] : get_bloginfo( 'admin_email' ) );
    $fullamount = $c['b'] . $values['amount'] . $c['a'];
    $headers = "From: {$auto['fromname']} <{$auto['fromemail']}>\r\n" . "Content-Type: text/html; charset=\"utf-8\"\r\n";
    $subject = $auto['subject'];
    $ref = ( $qpp['fixedreference'] && $qpp['shortcodereference'] ? $qpp['shortcodereference'] : $qpp['inputreference'] );
    $amt = ( $qpp['shortcodeamount'] && $qpp['fixedamount'] ? $qpp['shortcodeamount'] : $qpp['totalsblurb'] . ' ' );
    $rcolon = ( strpos( $ref, ':' ) ? '' : ': ' );
    $acolon = ( strpos( $amt, ':' ) ? '' : ': ' );
    $details = '<h2>Order Details:</h2>';
    
    if ( $qpp['use_multiples'] ) {
        $multiple = '<table><tr><th>Item</th><th>Qty</th></tr>';
        foreach ( $values['items'] as $k => $item ) {
            if ( $item['quantity'] ) {
                $multiple .= '<tr><td>' . $item['item_name'] . '</td><td>' . $item['quantity'] . '</td></tr>';
            }
        }
        $multiple .= '</table>';
        $details .= $multiple;
    } else {
        $details .= '<p>' . $ref . $rcolon . $values['reference'] . '</p>
        <p>' . $qpp['quantitylabel'] . ': ' . $values['quantity'];
        if ( $values['quantity'] > 1 ) {
            $details .= ' @ ' . $c['b'] . $values['amount'] . $c['a'] . ' each';
        }
        $details .= '</p>';
    }
    
    if ( $qpp['use_stock'] ) {
        
        if ( $qpp['fixedstock'] ) {
            $details .= '<p>' . $qpp['stocklabel'] . '</p>';
        } else {
            $details .= '<p>' . $qpp['stocklabel'] . ': ' . strip_tags( $values['stock'] ) . '</p>';
        }
    
    }
    if ( $qpp['use_cf'] ) {
        $details .= '<p>' . $qpp['cflabel'] . ': ' . strip_tags( $values['cf'] ) . '</p>';
    }
    if ( $qpp['use_options'] ) {
        $details .= '<p>' . $qpp['optionlabel'] . ': ' . strip_tags( $values['option1'] ) . '</p>';
    }
    $details .= '<p>' . $amt . $acolon . $fullamount . '</p>';
    if ( $qpp['use_message'] && $qpp['messagelabel'] != $values['yourmessage'] ) {
        $details .= '<p>' . $qpp['messagelabel'] . ': ' . strip_tags( $values['yourmessage'] ) . '</p>';
    }
    if ( $qpp['use_datepicker'] ) {
        $details .= '<p>' . $qpp['datepickerlabel'] . ': ' . strip_tags( $values['datepicker'] ) . '</p>';
    }
    $content = '<p>' . $auto['message'] . '</p>';
    $content = str_replace( '<p><p>', '<p>', $content );
    $content = str_replace( '</p></p>', '</p>', $content );
    $content = str_replace( '[firstname]', $values['firstname'], $content );
    $content = str_replace( '[name]', $values['firstname'] . ' ' . $values['lastname'], $content );
    $content = str_replace( '[reference]', $values['reference'], $content );
    $content = str_replace( '[quantity]', $values['quantity'], $content );
    $content = str_replace( '[fullamount]', $fullamount, $content );
    $content = str_replace( '[amount]', $values['amount'], $content );
    $content = str_replace( '[stock]', $values['stock'], $content );
    $content = str_replace( '[option]', $values['option1'], $content );
    $content = str_replace( '[details]', $details, $content );
    if ( isset( $multiple ) ) {
        $content = str_replace( '[multiple]', $multiple, $content );
    }
    if ( $auto['paymentdetails'] ) {
        $content .= $details;
    }
    if ( $auto['enable'] && $values['email'] ) {
        wp_mail(
            $values['email'],
            $subject,
            '<html>' . $content . '</html>',
            $headers
        );
    }
    
    if ( isset( $send['confirmmessage'] ) && $send['confirmmessage'] ) {
        $subject = 'Payment Notification';
        $contentb = '';
        if ( $qpp['useaddress'] ) {
            $contentb .= '<h2>Personal Details</h2>
            <table>
            <tr><td>' . $address['email'] . '</td><td>' . $values['email'] . '</td></tr></tr>
            <tr><td>' . $address['firstname'] . '</td><td>' . $values['firstname'] . '</td></tr>
            <tr><td>' . $address['lastname'] . '</td><td>' . $values['lastname'] . '</td></tr>
            <tr><td>' . $address['address1'] . '</td><td>' . $values['address1'] . '</td></tr>
            <tr><td>' . $address['address2'] . '</td><td>' . $values['address2'] . '</td></tr>
            <tr><td>' . $address['city'] . '</td><td>' . $values['city'] . '</td></tr>
            <tr><td>' . $address['state'] . '</td><td>' . $values['state'] . '</td></tr>
            <tr><td>' . $address['zip'] . '</td><td>' . $values['zip'] . '</td></tr>
            <tr><td>' . $address['country'] . '</td><td>' . $values['country'] . '</td></tr>
            <tr><td>' . $address['night_phone_b'] . '</td><td>' . $values['night_phone_b'] . '</td></tr>
            </table>';
        }
        $content = '<html>' . $details . $contentb . '</html>';
        wp_mail(
            $confirmemail,
            $subject,
            $content,
            $headers
        );
    }

}

function qpp_total_amount( $currency, $qpp, $values )
{
    $check = qpp_format_amount( $currency, $qpp, $values['amount'] );
    $quantity = ( $values['quantity'] < 1 ? '1' : strip_tags( $values['quantity'] ) );
    
    if ( $qpp['usepostage'] && $qpp['postagetype'] == 'postagepercent' ) {
        $percent = preg_replace( '/[^.,0-9]/', '', $qpp['postagepercent'] ) / 100;
        $packing = $check * $quantity * $percent;
        $packing = (double) qpp_format_amount( $currency, $qpp, $packing );
    }
    
    
    if ( $qpp['usepostage'] && $qpp['postagetype'] == 'postagefixed' ) {
        $packing = preg_replace( '/[^.,0-9]/', '', $qpp['postagefixed'] );
        $packing = (double) qpp_format_amount( $currency, $qpp, $packing );
    }
    
    $amounttopay = $check * $quantity + $packing;
    return $amounttopay;
}

function qpp_create_user( $values )
{
    $user_name = $values['firstname'];
    $user_email = $values['email'];
    $user_id = username_exists( $user_name );
    
    if ( !$user_id and email_exists( $user_email ) == false and $user_name and $user_email ) {
        $password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
        $user_id = wp_create_user( $user_name, $password, $user_email );
        wp_update_user( array(
            'ID'   => $user_id,
            'role' => 'subscriber',
        ) );
        wp_new_user_notification( $user_id, $notify = 'both' );
    }

}

function qpp_mailchimp( $values, $send )
{
    $content = '<form action="https://mailchimp.us8.list-manage.com/subscribe/post" method="POST" id="mailchimpsubmit">
    <input type="hidden" name="u" value="' . $send['mailchimpuser'] . '">
    <input type="hidden" name="id" value="' . $send['mailchimpid'] . '">
    <input type="hidden" name="MERGE0" id="MERGE0" value=' . $values['email'] . '>
    <input type="hidden" name="FNAME" id="FNAME" value=' . $values['firstname'] . '>
    <input type="hidden" name="LNAME" id="LNAME" value=' . $values['lastname'] . '>
    </form>
    <script language="JavaScript">document.getElementById("mailchimpsubmit").submit();</script>';
    return $content;
}

function qpp_start_transaction(
    &$paypal,
    $currency,
    $qpp,
    $v,
    $form,
    $payment
)
{
    /*
    	Use GET Variables
    */
    $amount = (double) $payment->totalamount;
    $name = (string) $payment->items[0]['item_name'];
    $qty = (double) $payment->items[0]['quantity'];
    $order = $paypal->NewOrder();
    $items = array();
    foreach ( $payment->items as $k => $i ) {
        $item = $order->NewItem( $i['amount'], $i['quantity'] );
        /*
        	Build Item Name
        */
        $option = '';
        if ( isset( $v['option1'] ) ) {
            $option = ' (' . $v['option1'] . ')';
        }
        $item->setAttribute( 'NAME', $i['item_name'] . $option );
        $items[] = $item;
    }
    /*
    	Build Note
    */
    if ( isset( $v['yourmessage'] ) && strlen( $v['yourmessage'] ) ) {
        $order->setAttribute( 'NOTETEXT', $v['yourmessage'] );
    }
    /*
    	Build Address
    */
    $a_vars = array(
        'firstname'     => 'CALCULATE',
        'lastname'      => 'CALCULATE',
        'address1'      => 'SHIPTOSTREET',
        'address2'      => 'SHIPTOSTREET',
        'city'          => 'SHIPTOCITY',
        'state'         => 'SHIPTOSTATE',
        'zip'           => 'SHIPTOZIP',
        'country'       => 'SHIPTOCOUNTRY',
        'night_phone_b' => 'SHIPTOPHONENUM',
    );
    $name = $fname = $lname = '';
    foreach ( $payment->address as $k => $val ) {
        
        if ( array_key_exists( $k, $a_vars ) ) {
            $prop = $a_vars[$k];
            // Address variable -- COLLECT IT
            switch ( $prop ) {
                case 'CALCULATE':
                    
                    if ( $k == 'firstname' ) {
                        $fname = $val;
                    } else {
                        $lname = $val;
                    }
                    
                    break;
                default:
                    $order->setAttribute( $prop, $val );
                    break;
            }
        }
    
    }
    
    if ( !empty($fname) || !empty($lname) ) {
        $name = trim( $fname . ' ' . $lname );
        $order->setAttribute( 'SHIPTONAME', $name );
    }
    
    
    if ( $v['coupon'] ) {
        $x = $order->dump();
        $x = $x['PAYMENTREQUEST_0_ITEMAMT'];
        // Apply coupon
        $discount = $v['coupon']['value'];
        if ( $v['coupon']['type'] == 'percent' ) {
            $discount = $x * ($discount * 0.01);
        }
        if ( $discount > $x ) {
            $discount = $x;
        }
        // Add the discount
        $item = $order->NewItem( -1 * abs( $discount ), 1 );
        $item->setAttribute( 'NAME', 'Coupon Code: ' . $v['coupon']['code'] );
    }
    
    /*
    	Handle Shipping & Handling
    */
    $postage = 0;
    $processing = 0;
    
    if ( $qpp['usepostage'] ) {
        $postage = (double) $payment->handling;
        $order->setAttribute( 'SHIPPINGAMT', $postage );
    }
    
    /*
    	Add IPN code and put this transaction into the MESSAGES table
    */
    $ipn = qpp_get_stored_ipn();
    
    if ( isset( $ipn['ipn'] ) && $ipn['ipn'] == 'checked' ) {
        $ipn_url = ( $ipn['listener'] ? $ipn['listener'] : $ipn['default'] );
        global  $qpp_current_custom ;
        $order->setAttribute( 'CUSTOM', $payment->custom );
        $order->setAttribute( 'NOTIFYURL', $ipn_url );
    }
    
    /*
    	Add currency code
    	Defaults to USD
    */
    if ( !empty($currency) ) {
        $order->setAttribute( 'CURRENCYCODE', $currency );
    }
}

function qpp_execute_transaction( $p )
{
    return $p->execute();
}

function qpp_nice_label(
    $id,
    $name,
    $type,
    $label,
    $labelType,
    $error,
    $value
)
{
    $class = '';
    if ( $name == 'datepicker' ) {
        $class = ' class="qpp-datepicker"';
    }
    
    if ( $type == 'text' ) {
        switch ( $labelType ) {
            case 0:
                $v = ( $value == $label ? '' : $value );
                $returning = '<div class="qpp_nice_label qpp_label_none">';
                $returning .= '<input type="text" id="' . $id . '"' . $class . ' name="' . $name . '" value="' . $v . '" ' . $error . ' />';
                break;
            case 1:
                $v = ( $value == $label ? '' : $value );
                $returning = '<div class="qpp_nice_label qpp_label_tiny">';
                $returning .= '<label for="' . $id . '">' . $label . '</label>';
                $returning .= '<input type="text" id="' . $id . '"' . $class . ' name="' . $name . '" value="' . $v . '" ' . $error . ' />';
                break;
            case 2:
                $returning = '<div class="qpp_nice_label qpp_label_blur">';
                $returning .= '<input type="text" id="' . $id . '"' . $class . ' name="' . $name . '" value="' . $value . '" ' . $error . ' onfocus="qppclear(this, \'' . $value . '\')" onblur="qpprecall(this, \'' . $value . '\')"/>';
                break;
            case 3:
                $v = ( $value == $label ? '' : $value );
                $returning = '<div class="qpp_nice_label qpp_label_line">';
                $returning .= '<label for="' . $id . '">' . $label . '</label>';
                $returning .= '<input type="text" id="' . $id . '"' . $class . ' name="' . $name . '" value="' . $v . '" ' . $error . ' />';
                break;
        }
    } elseif ( $type == 'textarea' ) {
        // textarea
        switch ( $labelType ) {
            case 0:
                $v = ( $value == $label ? '' : $value );
                $returning = '<div class="qpp_nice_label qpp_label_none">';
                $returning .= '<textarea rows="4" label="message" id="' . $id . '" name="' . $name . '"' . $error . '>' . stripslashes( $v ) . '</textarea>';
                break;
            case 1:
                $v = ( $value == $label ? '' : $value );
                $returning = '<div class="qpp_nice_label qpp_label_tiny">';
                $returning .= '<label for="' . $id . '">' . $label . '</label>';
                $returning .= '<textarea rows="4" label="message" id="' . $id . '" name="' . $name . '"' . $error . '>' . stripslashes( $v ) . '</textarea>';
                break;
            case 2:
                $returning = '<div class="qpp_nice_label qpp_label_blur">';
                $returning .= '<textarea rows="4" label="message" name="' . $name . '"' . $error . ' onfocus="qppclear(this, \'' . $value . '\')" onblur="qpprecall(this, \'' . $value . '\')" />' . stripslashes( $value ) . '</textarea>';
                break;
            case 3:
                $v = ( $value == $label ? '' : $value );
                $returning = '<div class="qpp_nice_label qpp_label_line">';
                $returning .= '<label for="' . $id . '">' . $label . '</label>';
                $returning .= '<textarea rows="4" label="message" id="' . $id . '" name="' . $name . '"' . $error . '>' . stripslashes( $v ) . '</textarea>';
                break;
        }
    }
    
    $returning .= '</div>';
    return $returning;
}

function qpp_get_element( $array, $key, $default = '' )
{
    if ( !is_array( $array ) ) {
        return $array;
    }
    if ( array_key_exists( $key, $array ) ) {
        return $array[$key];
    }
    return $default;
}

/**
 * legacy code licence validation
 *
 * @return bool|mixed
 */
function qpp_is_platinum()
{
    global  $quick_paypal_payments_fs ;
    if ( $quick_paypal_payments_fs->can_use_premium_code() && $quick_paypal_payments_fs->is_plan( 'platinum' ) ) {
        return true;
    }
    $qpp_key = get_option( 'qpp_key', array(
        'authorised' => false,
    ) );
    return $qpp_key['authorised'];
}
