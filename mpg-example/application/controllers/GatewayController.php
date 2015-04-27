<?php

/**
 * A testing payment gateway controller.
 * 
 * @author l0co@wp.pl
 */
class GatewayController extends Zend_Payment_Controller {

	/***********************************************************************************************
	 * TEST PAYMENT CONTROLLER - YOU CAN USE THIS CODE TO SEE HOW DOES IMPLEMENT PAYMENT FLOW
	 **********************************************************************************************/
	
	/** Just displays payment testing form **/
	public function indexAction() {
		$this->view->payments = Zend_Payment_Registry::getInstance();
	}
	
	/** Receives data from 'test' action form and runs payments procedure **/
	public function startAction() {
		// WARN: no any validation in this test method
		$amount = $this->_getParam('amount');
		$description = $this->_getParam('description');
		$method = $this->_getParam('method');
		
		$pid = Zend_Payment_Registry::getInstance()->getByType($method)->beginSession(
			$amount, $description, 'gateway/success', 'gateway/error');
		
		$this->_redirect("/payment/begin?pid=".$pid);
	}
	
	/** Reacts on success payment **/ 
	public function successAction() {
		$this->disableView();
		echo "Thanks for your money, transaction is done.";
		$this->debugRequest();
	}

	/** Reacts on failed payment **/ 
	public function errorAction() {
		$this->disableView();
		echo "Unfortunatelly your transaction failed, are you sure you have any money?";
		$this->debugRequest();
	}
	
	protected function debugRequest() {
		echo "<p><i>Request debug</i></p><ul style='text-align:left'>";
		foreach ($this->getRequest()->getParams() as $key=>$value) {
			echo "<li>$key = $value";
		}
		echo "</ul>";
	}
	
	/***********************************************************************************************
	 * TEST PAYMENT GATEWAY - THIS WORK IS USUALLY DONE BY PAYMENT GATEWAY AND IS NOT RELEVANT
	 **********************************************************************************************/
	
	/**
	 * This action simulates external payment provider interface. It does no direct interactions with our app.
	 * THIS IS ONLY THE EXAMPLE OF WORKING PAYMENT SYSTEM  
	 **/ 
	public function pgformAction() {
		$this->disableLayout(); // just give us the view
		// we need to forward our important params to the form
		// this also is done by payment gateway provider
		$this->view->success = $this->_getParam("success");
		$this->view->status_url = $this->_getParam("STATUS_URL");
		$this->view->success_url = $this->_getParam("SUCCESS_URL");
		$this->view->error_url = $this->_getParam("ERROR_URL");
		$this->view->pid = $this->_getParam("PID");
		$this->view->amount = $this->_getParam("amount");
		$this->view->description = $this->_getParam("description");
	}

	/**
	 * This action simulates external payment provider interface. It does no direct interactions with our app.
	 * THIS IS ONLY THE EXAMPLE OF WORKING PAYMENT SYSTEM  
	 **/ 
	public function pgactionAction() {
		$this->disableLayout()->disableView(); // just give us the view

		// payment provider sends us an info about payment is done through its own POST
		if ($this->_getParam("success")=="true") {
			$client = new Zend_Http_Client($this->_getParam("STATUS_URL"));
			$client->setMethod("POST");
			// if success!=true we are sending invalid data in POST
			$client->setParameterPost("PID", $this->_getParam("PID"));
			$response = $client->request();
			$this->_redirect($this->_getParam("SUCCESS_URL"));
		}
		
		$this->_redirect($this->_getParam("ERROR_URL"));
	}
	
}

