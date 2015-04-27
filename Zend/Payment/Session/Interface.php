<?php

/**
 * Payment session interface.
 * 
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Project page: http://lifeinide.blogspot.com/2010/12/multi-payment-gateway-module-for-zend.html
 * 
 * @author l0co@wp.pl
 */
interface Zend_Payment_Session_Interface {

    const STATUS_NEW 		= 0; // new, initiated transaction
    const STATUS_DONE 		= 1; // done, payment successful 
    const STATUS_CANCELLED 	= 2; // cancel, payment unsuccessful
    const STATUS_DROPPED 	= 3; // dropped, response timeout, payment unsuccessful
	
	/** Payment session id **/
	public function getId();
	public function setId($id);
	
	/** Payment session type name **/
	public function getType();
	public function setType($type);
	
	/** Payment session amount **/
	public function getAmount();
	public function setAmount($amount);

	/** Payment session description **/
	public function getDescription();
	public function setDescription($description);
	
	/** URL in our app we won't to get when payment is succeed **/
	public function getSuccessUrl();
	public function setSuccessUrl($successUrl);
	
	/** URL in our app we won't to get when payment is failes **/
	public function getErrorUrl();
	public function setErrorUrl($errorUrl);
	
	/** Transaction status **/
	public function getStatus();
	public function setStatus($status);
	
	/** Status data log sent to us by the payment server **/
	public function getStatusData();
	public function addStatusData($statusData);

	/** Response data log sent to the payment server by us **/
	public function getResponseData();
	public function addResponseData($responseData);
		
}