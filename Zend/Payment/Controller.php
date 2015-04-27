<?php

/**
 * Payment controller implements all base payment actions. You need to derive your own
 * payment controller from this class and put it into appropriate application context.
 *
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Project page: http://lifeinide.blogspot.com/2010/12/multi-payment-gateway-module-for-zend.html
 * 
 * @author l0co@wp.pl
 */
class Zend_Payment_Controller extends Zend_Controller_Action {
	
	const MAX_WAIT_FOR_STATUS_SECS = 30;
	const WAIT_FOR_STATUS_DELAY = 5;
	const TIMEOUT_PARAM = "timeout";
	
	/**
	 * Begins payment. Requires "paymentId" parameter from request.
	 */
	public function beginAction() {
		$paymentId = $this->_getParam(Zend_Payment_Gateway_Base::PAYMENT_ID_PARAM);
		
		if (!$paymentId)
			throw new Zend_Exception("No paymentId provided (pid is empty)");
			
		$payment = Zend_Payment_Registry::getInstance()->getLoader()->load($paymentId);
		
		// check prerequisites
		if (!$payment)
			throw new Zend_Exception("No such payment registered (pid=$paymentId)");
		else if ($payment->getStatus() != Zend_Payment_Session_Interface::STATUS_NEW)
			throw new Zend_Exception("This payment is not appropriated for further processing");

		$payobj = Zend_Payment_Registry::getInstance()->getByType($payment->getType());
		$payobj->restoreSession($payment);
			
		// init view variables and show the form
		$action = $payobj->getFormAction();
		$method = $payobj->getFormMethod();
		$data = $payobj->getFormData();
		
		$this->disableLayout()->disableView();
		include "Controller.begin.php";
	}
	
	/**
	 * Markes payment as done. Requires $_POST data from payment server to realize.
	 * SHOULD INTERACT ONLY WITH PAYMENT PROVIDER SERVER (!)
	 */
	public function statusAction() {
		$this->disableLayout()->disableView();
		
		$paymentId = $this->findPaymentId();
		$payment = $paymentId ? Zend_Payment_Registry::getInstance()->getLoader()->load($paymentId) : null;
		$result = $this->doStatusCheck($paymentId, $payment);
		
		// output and store into db response status
		if ($payment) {
			$d = new Zend_Date();
			$payment->addResponseData("[response time=".$d->toString()."] ".$result);
			Zend_Payment_Registry::getInstance()->getLoader()->save($payment);
		}
		
		echo $result;
	}
	
	protected function doStatusCheck($paymentId, Zend_Payment_Session_Interface $payment) {
		try {
			// check if we have loaded properly payment session
			if (!$payment) 
				throw new Zend_Payment_Exception("Cannot restore payment session using " . 
					Zend_Payment_Base::PAYMENT_ID_PARAM . "=$paymentId");
			
			$payment->addStatusData($this->getRequestData());
			Zend_Payment_Registry::getInstance()->getLoader()->save($payment);
			
			// perform payment verification
			$payobj = Zend_Payment_Registry::getInstance()->getByType($payment->getType());
			$payobj->restoreSession($payment);
			
			$payobj->doValidation($this->getRequest()); 
		} catch (Exception $e) {
			$this->getResponse()->setHttpResponseCode(500);
			return "error, ".$e->getMessage();
		}
		
		return "ok, Payment accepted";
	}
	
	/**
	 * Successful payment screen.
	 */
	public function successAction() {
		$paymentId = $this->findPaymentId();
		
		if ($paymentId) {
			$payment = Zend_Payment_Registry::getInstance()->getLoader()->load($paymentId);
			
			if ($payment) {
				// this is very funny, but these payment gateways can first redirect to us success link
				// and then, after few seconds, acknowledge the transation status using 'status' action
				if ($payment->getStatus() == Zend_Payment_Session_Interface::STATUS_NEW) {
					// our payment is finished with success action but was not confirmed by payment server
					// we will wait some time for acknowledge
					$timeout = $this->_getParam(self::TIMEOUT_PARAM);
					if ($timeout===null)
						$timeout = self::MAX_WAIT_FOR_STATUS_SECS;
					$timeout = ((int) $timeout) - self::WAIT_FOR_STATUS_DELAY;
						
					if ($timeout>0) {
						// we are still waiting
						sleep(self::WAIT_FOR_STATUS_DELAY);
						$this->_redirect("/".$this->getRequest()->getControllerName()."/success?pid=$paymentId&".
							self::TIMEOUT_PARAM."=$timeout"); // each N secs we are trying to check transaction status
						return;
					} else {
						$payment->setStatus(Zend_Payment_Session_Interface::STATUS_DROPPED);
						$payment->addStatusData("error, There was no status response from payment gateway");
						$payment->addResponseData("error, Transaction was dropped after ".self::MAX_WAIT_FOR_STATUS_SECS
							." secs of waiting for status");
						Zend_Payment_Registry::getInstance()->getLoader()->save($payment);
					}
				} 
				
				if ($payment->getStatus() != Zend_Payment_Session_Interface::STATUS_DONE) {
					// payment gateway redirected us to success action, but status indicates something else?
					throw new Zend_Payment_Exception("Your payment wasn't correctly acknowledged by our system.<br/>".
						"Please contact with administrator and give him following information: paymentId=$paymentId, status=".
						$payment->getStatus().".");
				}
				
				$this->_redirect($payment->getSuccessUrl());
				return;
			}
		}
		
		$this->disableView();
		echo "Cannot find your payment session.";
	}

	/**
	 * Unsuccessful payment screen.
	 */
	public function failedAction() {
		$paymentId = $this->findPaymentId();
		
		if ($paymentId) {
			$payment = Zend_Payment_Registry::getInstance()->getLoader()->load($paymentId);
			
			if ($payment) {
				if ($payment->getStatus() !== Zend_Payment_Session_Interface::STATUS_CANCELLED) {
					$payment->setStatus(Zend_Payment_Session_Interface::STATUS_CANCELLED);
					$payment->addStatusData("error, There was no status response from payment gateway");
					$payment->addResponseData("error, Transaction was cancelled by payment failed action");
					Zend_Payment_Registry::getInstance()->getLoader()->save($payment);
				}
				
				$this->_redirect($payment->getErrorUrl());
				return;
			}
		}
		
		$this->disableView();
		echo "Cannot find this payment session.";
	}
	
	protected function getRequestData() {
		$d = new Zend_Date();
		$d = "[request time=".$d->toString()."]: ";
		foreach ($this->getRequest()->getParams() as $key=>$value)
			$d .= "$key=$value; ";
		return $d;
	}
	
	/**
	 * Tries to find paymentId from request. 
	 */
	protected function findPaymentId() {
		// standard method
		$paymentId = $this->_getParam(Zend_Payment_Gateway_Base::PAYMENT_ID_PARAM);
		// if standard method fails, try to find payment id using methods from registered payment method objects
		if (!$paymentId)
			$paymentId = Zend_Payment_Registry::getInstance()->findPaymentId($this->getRequest());
		return $paymentId;
	}

    protected function disableLayout() {
    	if ($this->_helper->hasHelper('layout'))
    		$this->_helper->layout->disableLayout();
    	return $this;
    }

    protected function disableView() {
		$this->getFrontController()->setParam('noViewRenderer', true);
    	return $this;
    }
    
}