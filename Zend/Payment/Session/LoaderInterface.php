<?php

/**
 * Payment session loader interface.
 * 
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Project page: http://lifeinide.blogspot.com/2010/12/multi-payment-gateway-module-for-zend.html
 * 
 * @author l0co@wp.pl
 */
interface Zend_Payment_Session_LoaderInterface {
	
	/**
	 * @return Zend_Payment_Session_Interface
	 */
	public function load($id);
	
	/**
	 * @return Zend_Payment_Session_Interface
	 */
	public function build($type, $amount, $description, $success_url, $error_url);
	
	public function save(Zend_Payment_Session_Interface $session);
	
}