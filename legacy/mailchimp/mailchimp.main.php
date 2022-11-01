<?php
	namespace QPP;
	
	function getMailChimp($key) {
		
		return new MailChimp($key);
		
	}

	function subscribe($email, $name = '') {
		
		
		$list = \qpp_get_stored_mailinglist();
		
		$MailChimp = getMailChimp($list['mailchimpkey']);
		
		$options = array();
		
		$options['email_address'] = $email;
		$options['status'] = 'subscribed';
		
		if (strlen($name)) {
			$names = explode(' ',$name);
			$merge = array(
				'FNAME' => $names[0]
			);
			if (isset($names[1])) {
				$merge['LNAME'] = $names[1];
			}
		}
		
		if (isset($merge)) $options['merge_fields'] = $merge;
		
		$result = $MailChimp->post("lists/{$list['mailchimplistid']}/members", $options);
		
		// $result['status'] 
		//	-> 400 - Failure
		//	-> 'subscribed' - Success
		
	}
?>