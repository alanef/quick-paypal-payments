<?php

// Prevent direct access.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
global $quick_paypal_payments_fs;
// remove freemius tabs from this page
$quick_paypal_payments_fs->add_filter(
    'is_submenu_visible',
    function ( $is_visible, $menu_id ) {
        return false;
    },
    9999,
    2
);
$qpp_setup = qpp_get_stored_setup();
$tabs = explode( ",", $qpp_setup['alternative'] );
$qpp_firsttab = reset( $tabs );
echo '<div class="wrap">';
qpp_admin_header();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
if ( isset( $_GET['tab'] ) ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
    $tab = sanitize_text_field( $_GET['tab'] );
    qpp_messages_admin_tabs( $tab );
} else {
    qpp_messages_admin_tabs( $qpp_firsttab );
    $tab = $qpp_firsttab;
}
qpp_show_messages( $tab );
echo '</div>';
function qpp_messages_admin_tabs(  $current = 'default'  ) {
    $qpp_setup = qpp_get_stored_setup();
    $tabs = explode( ",", $qpp_setup['alternative'] );
    array_push( $tabs, 'default' );
    sort( $tabs );
    $message = get_option( 'qpp_message' );
    echo '<h2 class="nav-tab-wrapper qpp-messages">';
    foreach ( $tabs as $tab => $name ) {
        if ( empty( $name ) ) {
            continue;
        }
        $class = ( $name == $current ? ' nav-tab-active' : '' );
        echo '<a class="nav-tab' . esc_attr( $class ) . '" href="?page=quick-paypal-payments-messages&tab=' . esc_attr( $name ) . '">' . esc_attr( $name ) . '</a>';
    }
    echo '</h2>';
}

/**
 * The line shown after marking a payment paid by hand.
 *
 * Only pitched below Silver. An install with the IPN listener settles orders
 * automatically, so the button is a convenience there rather than a workaround,
 * and pitching an upgrade they already have would be nonsense.
 *
 * This is the moment the free tier's one real cost is felt, which makes it the
 * honest place to say what removes it.
 *
 * @return string
 */
function qpp_mark_paid_upsell() {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    $upurl = qpp_upgrade_url();
    return 'Silver confirms payments automatically from PayPal, so you do not have to check them by hand. <a href="' . esc_url( $upurl ) . '">See plans and prices</a>.';
}

function qpp_show_messages(  $id  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    if ( $id == 'default' ) {
        $id = '';
    }
    qpp_generate_csv();
    $sendtoemail = false;
    if ( isset( $_POST['qpp_emaillist'] ) ) {
        check_admin_referer( 'qpp_download_form', 'qpp_download_form_nonce' );
        $messageoptions = qpp_get_stored_msg();
        $content = qpp_messagetable( $id, 'checked' );
        $title = $id;
        if ( $id == '' ) {
            $title = 'Default';
        }
        $title = 'Payment List for ' . $title . ' as at ' . wp_date( 'j M Y' );
        $sendtoemail = sanitize_email( wp_unslash( $_POST['sendtoemail'] ) );
        if ( !is_email( $sendtoemail ) ) {
            qpp_admin_notice( 'That is not a valid email address, nothing was sent.' );
            return;
        }
        // sanitize_email() strips the CR/LF that would otherwise let a crafted
        // address inject extra mail headers here.
        $headers = 'From: <' . $sendtoemail . '>' . "\r\n" . 'Content-Type: text/html; charset="utf-8"' . "\r\n";
        qpp_wp_mail(
            'Message Email',
            $sendtoemail,
            $title,
            $content,
            $headers
        );
        qpp_admin_notice( 'Message list has been sent to ' . $sendtoemail . '.' );
    }
    if ( isset( $_POST['qpp_reset_message'] ) ) {
        check_admin_referer( 'qpp_download_form', 'qpp_download_form_nonce' );
        delete_option( 'qpp_messages' . $id );
        qpp_admin_notice( 'Payment list has been reset.' );
    }
    if ( isset( $_POST['Submit'] ) ) {
        check_admin_referer( 'qpp_payments_form', 'qpp_payments_form_nonce' );
        $options = array(
            'messageqty',
            'messageorder',
            'hidepaid',
            'showaddress'
        );
        foreach ( $options as $item ) {
            if ( isset( $_POST[$item] ) ) {
                $messageoptions[$item] = stripslashes( sanitize_text_field( $_POST[$item] ) );
            }
        }
        $messageoptions = qpp_merge_msg( $messageoptions );
        update_option( 'qpp_messageoptions', $messageoptions );
        qpp_admin_notice( "The message options have been updated." );
    }
    if ( isset( $_POST['qpp_mark_paid'] ) ) {
        check_admin_referer( 'qpp_download_form', 'qpp_download_form_nonce' );
        $id = sanitize_text_field( wp_unslash( $_POST['formname'] ) );
        $message = get_option( 'qpp_messages' . $id );
        if ( false !== $message && is_array( $message ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by check_admin_referer above.
            $selected = qpp_selected_payment_rows( wp_unslash( $_POST ), count( $message ) );
            $result = qpp_mark_orders_paid( $message, $selected );
            update_option( 'qpp_messages' . $id, $result['messages'], false );
            /*
             * Settling by hand is a payment completing, so it earns the buyer the
             * same account an automatic confirmation would. On the free version
             * there is no listener, so this is the only route an order has to being
             * paid at all.
             */
            $send = qpp_get_stored_send( $id );
            if ( qpp_should_create_user( $send, 'afterpayment' ) ) {
                foreach ( $result['settled'] as $index ) {
                    qpp_create_user( qpp_order_row_to_values( $result['messages'][$index] ), $send );
                }
            }
            if ( $result['marked'] ) {
                qpp_admin_notice( sprintf( 
                    /* translators: %d is the number of payments marked as paid. */
                    _n(
                        '%d payment marked as paid.',
                        '%d payments marked as paid.',
                        $result['marked'],
                        'quick-paypal-payments'
                    ),
                    $result['marked']
                 ) . ' ' . qpp_mark_paid_upsell() );
            } else {
                qpp_admin_notice( 'No payments were changed. Select the payments you have checked in PayPal, then mark them as paid.' );
            }
        }
    }
    if ( isset( $_POST['qpp_delete_selected'] ) ) {
        check_admin_referer( 'qpp_download_form', 'qpp_download_form_nonce' );
        $id = sanitize_text_field( $_POST['formname'] );
        $message = get_option( 'qpp_messages' . $id );
        if ( $message !== false ) {
            $count = count( $message );
            for ($i = 0; $i <= $count; $i++) {
                if ( isset( $_POST[$i] ) && 'checked' === sanitize_text_field( wp_unslash( $_POST[$i] ) ) ) {
                    unset($message[$i]);
                }
            }
        }
        $message = array_values( $message );
        update_option( 'qpp_messages' . $id, $message, false );
        qpp_admin_notice( 'Selected payments have been deleted.' );
    }
    global $current_user;
    if ( !$sendtoemail ) {
        $sendtoemail = $current_user->user_email;
    }
    $messageoptions = qpp_get_stored_msg();
    $fifty = $hundred = $all = $oldest = $newest = '';
    $showthismany = '9999';
    if ( $messageoptions['messageqty'] == 'fifty' ) {
        $showthismany = '50';
    }
    if ( $messageoptions['messageqty'] == 'hundred' ) {
        $showthismany = '100';
    }
    ${$messageoptions['messageqty']} = "checked";
    ${$messageoptions['messageorder']} = "checked";
    $dashboard = '<form method="post" action="">';
    $dashboard .= wp_nonce_field(
        'qpp_payments_form',
        'qpp_payments_form_nonce',
        true,
        false
    );
    $dashboard .= '<p><b>Show</b> <input style="margin:0; padding:0; border:none;" type="radio" name="messageqty" value="fifty" "' . esc_attr( $fifty ) . ' /> 50 
    <input style="margin:0; padding:0; border:none;" type="radio" name="messageqty" value="hundred" ' . esc_attr( $hundred ) . ' /> 100 
    <input style="margin:0; padding:0; border:none;" type="radio" name="messageqty" value="all" ' . esc_attr( $all ) . ' /> all messages.&nbsp;&nbsp;
    <b>List</b> <input style="margin:0; padding:0; border:none;" type="radio" name="messageorder" value="oldest" ' . esc_attr( $oldest ) . ' /> oldest first 
    <input style="margin:0; padding:0; border:none;" type="radio" name="messageorder" value="newest" ' . esc_attr( $newest ) . ' /> newest first
    &nbsp;&nbsp;
    <input style="margin:0; padding:0; border:none;" type="checkbox" name="hidepaid" value="checked" ' . esc_attr( $messageoptions['hidepaid'] ) . ' /> Hide paid transactions
    &nbsp;&nbsp;
    <input style="margin:0; padding:0; border:none;" type="checkbox" name="showaddress" value="checked" ' . esc_attr( $messageoptions['showaddress'] ) . ' /> Show addresses
    &nbsp;&nbsp;
    <input type="submit" name="Submit" class="button-secondary" value="Update options" />
    </form></p>';
    /*
     * Delete All permanently destroys the payment record for this form and was a
     * plain secondary button sitting beside Export to CSV, guarded by a confirm
     * that did not say what would be lost or how much of it. It now names the
     * form and the number of records, and is styled as destructive.
     */
    $stored_rows = get_option( 'qpp_messages' . $id );
    $stored_count = ( is_array( $stored_rows ) ? count( $stored_rows ) : 0 );
    $delete_all_form = ( '' === $id ? 'the default form' : $id );
    $delete_all_label = sprintf( 
        /* translators: %d is the number of payment records. */
        _n(
            'Delete All (%d record)',
            'Delete All (%d records)',
            $stored_count,
            'quick-paypal-payments'
        ),
        $stored_count
     );
    $delete_all_warning = sprintf( 
        /* translators: 1: number of payment records, 2: name of the form. */
        _n(
            'Permanently delete %1$d payment record for %2$s? This cannot be undone. Export to CSV first if you need it.',
            'Permanently delete all %1$d payment records for %2$s? This cannot be undone. Export to CSV first if you need them.',
            $stored_count,
            'quick-paypal-payments'
        ),
        $stored_count,
        $delete_all_form
     );
    $dashboard .= '<form method="post" id="download_form" action="">';
    $dashboard .= wp_nonce_field(
        'qpp_download_form',
        'qpp_download_form_nonce',
        true,
        false
    );
    $dashboard .= qpp_messagetable( $id, '' );
    $dashboard .= '<input type="hidden" name="formname" value = "' . esc_attr( $id ) . '" />
    <p>Send to this email address: <input type="text" name="sendtoemail" value="' . esc_attr( $sendtoemail ) . '">&nbsp;
    <input type="submit" name="qpp_emaillist" class="button-primary" value="Email List" />&nbsp;
    <input type="submit" name="download_qpp_csv" class="button-primary" value="Export to CSV" />
    
    <input type="submit" name="qpp_mark_paid" class="button-primary" value="Mark Selected as Paid" />

    <input type="submit" name="qpp_delete_selected" class="button-secondary qpp-danger" value="Delete Selected" onclick="return window.confirm( \'Delete the selected payment records? This cannot be undone.\' );"/>
    <input type="submit" name="qpp_reset_message" class="button-secondary qpp-danger" value="' . esc_attr( $delete_all_label ) . '" onclick="return window.confirm( \'' . esc_js( $delete_all_warning ) . '\' );"/>
    </form>
    ';
    $dashboard .= '<p class="description">Payments are not confirmed automatically on your plan. Check a payment in your PayPal account, then select it above and mark it as paid.</p>';
    $dashboard .= qpp_upgrade_box();
    echo wp_kses( $dashboard, qpp_allowed_html() );
}
