<?php
/** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
global $quick_paypal_payments_fs;
// remove freemius tabs from this page
$quick_paypal_payments_fs->add_filter( 'is_submenu_visible', function ( $is_visible, $menu_id ) {
	return false;
}, 9999, 2 );



$qpp_setup = qpp_get_stored_setup();
$tabs = explode(",",$qpp_setup['alternative']);
$firsttab = reset($tabs);
echo '<div class="wrap">';
echo '<h1>Quick Paypal Payments</h1>';
if ( isset ($_GET['tab'])) {qpp_messages_admin_tabs($_GET['tab']); $tab = $_GET['tab'];} else {qpp_messages_admin_tabs($firsttab); $tab = $firsttab;}
qpp_show_messages($tab);
echo '</div>';

function qpp_messages_admin_tabs($current = 'default') {
    $qpp_setup = qpp_get_stored_setup();
    $tabs = explode(",",$qpp_setup['alternative']);
    array_push($tabs, 'default');
sort($tabs);
    $message = get_option( 'qpp_message' );
    echo '<h2 class="nav-tab-wrapper qpp-messages">';
    foreach( $tabs as $tab ) {
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        if ($tab) echo "<a class='nav-tab$class' href='?page=quick-paypal-payments-messages&tab=".$tab."'>$tab</a>";
    }
    echo '</h2>';
}

function qpp_show_messages($id) {
    if ($id == 'default') $id='';
    $qpp_setup = qpp_get_stored_setup();
    $qpp = qpp_get_stored_options($id);
    qpp_generate_csv();

    if( isset($_POST['qpp_emaillist'])) {
        $message = get_option('qpp_messages'.$id);
        $messageoptions = qpp_get_stored_msg();
        $content = qpp_messagetable ($id,'checked');
        $title = $id; if ($id == '') $title = 'Default';
        $title = 'Payment List for '.$title.' as at '.date('j M Y');
        $sendtoemail = $_POST['sendtoemail'];
        $headers = "From: {<{$sendtoemail}>\r\n"
. "Content-Type: text/html; charset=\"utf-8\"\r\n";
        wp_mail($sendtoemail, $title, $content, $headers);
        qpp_admin_notice('Message list has been sent to '.$sendtoemail.'.');
    }

    if (isset($_POST['qpp_reset_message'])) delete_option('qpp_messages'.$id);

    if( isset( $_POST['Submit'])) {
        $options = array( 'messageqty','messageorder','hidepaid','showaddress');
        foreach ( $options as $item) $messageoptions[$item] = stripslashes($_POST[$item]);
        update_option( 'qpp_messageoptions', $messageoptions );
        qpp_admin_notice("The message options have been updated.");
    }

    if( isset($_POST['qpp_delete_selected'])) {
        $id = $_POST['formname'];
        $message = get_option('qpp_messages'.$id);
        $count = count($message);
        for($i = 0; $i <= $count; $i++) {
            if ($_POST[$i] == 'checked') {
                unset($message[$i]);
            }
        }
        $message = array_values($message);
        update_option('qpp_messages'.$id, $message );
        qpp_admin_notice('Selected payments have been deleted.');
    }

    global $current_user;
    get_currentuserinfo();

    if (!$sendtoemail) {
        $sendtoemail = $current_user->user_email;
    }

    $messageoptions = qpp_get_stored_msg();
    $fifty = $hundred = $all = $oldest = $newest = '';
    $showthismany = '9999';
    if ($messageoptions['messageqty'] == 'fifty') $showthismany = '50';
    if ($messageoptions['messageqty'] == 'hundred') $showthismany = '100';
    ${$messageoptions['messageqty']} = "checked";
    ${$messageoptions['messageorder']} = "checked";
    $dashboard = '<form method="post" action="">
    <p><b>Show</b> <input style="margin:0; padding:0; border:none;" type="radio" name="messageqty" value="fifty" ' . $fifty . ' /> 50 
    <input style="margin:0; padding:0; border:none;" type="radio" name="messageqty" value="hundred" ' . $hundred . ' /> 100 
    <input style="margin:0; padding:0; border:none;" type="radio" name="messageqty" value="all" ' . $all . ' /> all messages.&nbsp;&nbsp;
    <b>List</b> <input style="margin:0; padding:0; border:none;" type="radio" name="messageorder" value="oldest" ' . $oldest . ' /> oldest first 
    <input style="margin:0; padding:0; border:none;" type="radio" name="messageorder" value="newest" ' . $newest . ' /> newest first
    &nbsp;&nbsp;
    <input style="margin:0; padding:0; border:none;" type="checkbox" name="hidepaid" value="checked" ' . $messageoptions['hidepaid'] . ' /> Hide paid transactions
    &nbsp;&nbsp;
    <input style="margin:0; padding:0; border:none;" type="checkbox" name="showaddress" value="checked" ' . $messageoptions['showaddress'] . ' /> Show addresses
    &nbsp;&nbsp;
    <input type="submit" name="Submit" class="button-secondary" value="Update options" />
    </form></p>';
    $dashboard .='<form method="post" id="download_form" action="">';
    $dashboard .= qpp_messagetable($id,'');
    $dashboard .='<input type="hidden" name="formname" value = "'.$id.'" />
    <p>Send to this email address: <input type="text" name="sendtoemail" value="'.$sendtoemail.'">&nbsp;<input type="submit" name="qpp_emaillist" class="button-primary" value="Email List" />&nbsp;
    <input type="submit" name="download_qpp_csv" class="button-primary" value="Export to CSV" />
    
    <input type="submit" name="qpp_reset_message" class="button-secondary" value="Delete All" onclick="return window.confirm( \'Are you sure you want to delete all the payment details?\' );"/>
    <input type="submit" name="qpp_delete_selected" class="button-secondary" value="Delete Selected" onclick="return window.confirm( \'Are you sure you want to delete the selected payment details?\' );"/>
    </form>
    ';
    global $quick_paypal_payments_fs;
    if (! qpp_is_platinum()) {
	    if ( $quick_paypal_payments_fs->is_trial() || $quick_paypal_payments_fs->is_trial_utilized()) {
		    $upurl= $quick_paypal_payments_fs->get_upgrade_url();
		    $upmsg = '<p>From only $14.99 per annum</p>';;
	    } else {
		    $upurl= $quick_paypal_payments_fs->get_trial_url();
		    $upmsg = '<p>Free 14 Day Trial, then from $14.99 per annum</p>';
	    }
	    $dashboard .= '<div class="qppupgrade"><a href="' . $upurl . '">
        <h3>Upgrade to Pro Platinum</h3>
        <p>Upgrading gives Mailchimp data collection, Multiple products and Personalised Support.</p>
        <p>Click to find out more</p>' . $upmsg . '
        </a></div>';
    }
    echo $dashboard;
}
