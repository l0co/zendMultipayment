<?php

/**
 * A testing payment gateway. Does completely nothing, but pretends to...
 * 
 * Default config for test payment gateway:
 * 
 * payments.test.name 									= "Test Gateway"
 * payments.test.clazz 									= "Zend_Payment_Gateway_Test"
 * payments.test.action									= "/gateway/pgform"
 * payments.test.form.test								= "testfield"
 * 
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Project page: http://lifeinide.blogspot.com/2010/12/multi-payment-gateway-module-for-zend.html
 * 
 * @author l0co@wp.pl
 */
class Zend_Payment_Gateway_Test extends Zend_Payment_Gateway_Base {
	
	public function getFormData() {
		$data = parent::getFormData();
		
		$data["PID"] = $this->session->id;
		$data["amount"] = $this->session->amount;
		$data["description"] = $this->session->description;
		
		return $data;
	}
	
	protected function getStatusUrlParam() {
		return "STATUS_URL";
	}

	protected function getSuccessUrlParam() {
		return "SUCCESS_URL";
	}

	protected function getFailedUrlParam() {
		return "ERROR_URL";
	}
	
	protected function validate(Zend_Controller_Request_Http $request) {
		// just simple verification here
		if ($request->getParam("PID") !== $this->session->id) 
			throw new Zend_Payment_Exception("Invalid payment id");
	}
	
}