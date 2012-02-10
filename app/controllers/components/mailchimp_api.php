<?php 

/*
 * CakePHP Component for mailchimp.  
 * Originally By Scott T. Murphy (hapascott) 2008
 * 
 * Heavly modified by Carlos G. Limardo (Musical Heritage Society) 2010
 * 
 */

App::import('Vendor', 'mailchimp');

class MailchimpApiComponent extends Object {

	/**
	 * Mailchimp username
	 * 
	 * @var string
	 * @access private
	 * @deprecated 1.0 - Jan 26, 2011
	 */
	var $_username = '';
	
	/**
	 * Mailchimp password
	 * 
	 * @var string
	 * @access private
	 * @deprecated 1.0 - Jan 26, 2011
	 */
	var $_password = '';
	
	/**
	 * Mailchimp API Key
	 * 
	 * @var string
	 * @access private
	 */
	var $_apikey = '';

	/**
	 * Mailchimp API url, now using 1.2 (1.1 uses username/password).
	 * Actually set directy in the Mailchimp API so removing from here.
	 * 
	 * @var string
	 * @access private
	 * @deprecated 1.0 - Jan 26, 2011
	 */
	var $_apiUrl = 'http://api.mailchimp.com/1.2/';
	
	/**
	 * Array of errors from this component.
	 * 
	 * @var array
	 * @access public
	 */
	var $errors = array();
	
	function startup(&$controller) {
		$this->controller =& $controller;

		if (Configure::read('MailChimp.apikey')) {
			$this->_apikey = Configure::read('MailChimp.apikey');
		}
	}
		
	/**
	 * Get an array of available mailing lists
	 * 
	 * @return mixed returns array list or false on error
	 */
	function lists() {
		$api = $this->_credentials();
		
		if (!$arrList = $api->lists()){
			$this->errors[] = $api->errorMessage;
			return false;
		}
		
		return $arrList;
	}

	/**
	 * Get an array list of members to passed list_id.
	 * 
	 * $default_options = array(
	 *			'type' => 'subscribed', // or 'unsubscribed'
	 *			'start' => '0',
	 *			'end' => '5000',
	 *		);	
	 * 
	 * @var string list_id
	 * @var array options
	 * @return mixed array of members if su
	 */
	function members($list_id = null, $options = array()) {
		$api = $this->_credentials();
		
		$default_options = array(
				'type' => 'subscribed', // or 'unsubscribed'
				'start' => '0',
				'end' => '5000',
				'since' => null, // date
			);
		$options = am($default_options, $options);
		
		if (!$arrMembers = $api->listMembers($list_id , $options['type'], $options['since'], $options['start'], $options['end'])){
			$errors[] = $api->errorMessage;
			return false;
		} 
		
		return $arrMembers;
	}
	
	/**
	 * Get information about a member in a particular list.
	 * 
	 * @params $email
	 * @params $list_id
	 */
	 function member($email, $list_id) {
	 	$api = $this->_credentials();
	 	
	 	$arrMember = $api->listMemberInfo($list_id, $email);
	 	if (!$arrMember) {
	 		$errors[] = $api->errorMessage;
	 		return false; 		
	 	}
	 	
	 	return $arrMember;
	 }
	 

	/**
	 * Subscribe to a mailchimp mailing list.
	 * 
	 * @var string e-mail
	 * @var string list_id
	 * @var array merge_vars
	 * @var array options
	 * @return bool true if succefully subscribed
	 */
	function subscribe($email = null, $list_id = null, $merge_vars = array(), $options = array()) {
		$this->errors = array();
		$api = $this->_credentials();
		
		$default_options = array(
									'email_type'=>'html',
									'double_optin' => false,
									'update_existing' => true,
									'replace_intrests' => true,
									'send_welcome' => false,
								);
								
		$options = array_merge($default_options, $options);
		
		if (!$api->listSubscribe($list_id, $email, $merge_vars, $options['email_type'], $options['double_optin'], $options['update_existing'], $options['replace_intrests'], $options['send_welcome'])) {
			$this->errors[] = $api->errorMessage;
			return false;
		}
		return true;
	}
	
	/**
	 * Unsubscribe to a mailchimp mailing list.
	 * 
	 * @var string e-mail
	 * @var string list_id
	 * @var array options
	 * @return bool true if successfully unsubscribed
	 */
	function unsubscribe($email = null, $list_id = null, $options = array()) {
		$api = $this->_credentials();
		
		$default_options = array(
			'delete_member' => false,
			'send_goodbye' => true,
			'send_notify' => true,
		);
		
		$options = array_merge($default_options, $options);
				
		
		if (!$api->listUnsubscribe($list_id, $email, $options['delete_member'], $options['send_goodbye'], $options['send_notify'])){
			$this->errors[] = $api->errorMessage;
			return false;
		}
		
		return true;
	}
		
	/**
	 * Login to Mailchimp
	 * 
	 * @return mixed
	 */
	function _credentials() {
		/*
		 * Depricated login using username & password, use API key.
		 * $api = new MCAPI($this->_username, $this->_password);
		 */

		$api = new MCAPI($this->_apikey);
		
		if ($api->errorCode!=''){
			$retval = $api->errorMessage;
			e($retval); die;
			exit();
		}
		return $api;
	}
	
	/**
	 * Old Legacy functions, will be depricated by the time we move to 1.0.
	 */
	
	///*************LIST ALL MEMBERS IN A LIST*****************************************************/
	/***returns an array of all members you have under the specified mailchimp list *
	Example
	Controller
		function mclist_view($id) {
			$lists = $this->MailchimpApi->listMembers($id);
			$this->set('id',$id);
			$this->set('lists', $lists); 
		}
	*
	View (mclist_view.ctp)
	  var_dump($lists); //to view the full array.
	*/
	
	function listMembers($id, $subscribed = true) {
		
		$api = $this->_credentials();
		
		$retval = $api->listMembers( $id , ($subscribed ? 'subscribed' : 'unsubscribed'), 0, 5000 );
		if (!$retval){
					$retval = $api->errorMessage;
			} 
		return $retval;
	}
	
	
	/**
	 * Legacy function, kept for backward compatibility and so that nothing breaks.
	 * 
	 * @deprecated 1.0 - Jan 26, 2011
	*/
	function addMembers($list_id, $email, $merge_vars = array()) {
			return $this->subscribe($email, $list_id, $merge_vars);
	}
	
	
	
	/**
	 * Legacy remove/unsubscribe from list
	 * 
	 * @deprecated 1.0 - Jan 26, 2011
	*/
	
	function remove($user_email, $id) {
		$this->unsubscribe($user_email, $id);
	}

}