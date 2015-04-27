<?php

/**
 * Base payment class used to interaction with UI and to delegate concrete
 * payment implementation to specialized classes.
 * 
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Project page: http://lifeinide.blogspot.com/2010/12/multi-payment-gateway-module-for-zend.html
 * 
 * @author l0co@wp.pl
 */
abstract class Zend_Payment_Gateway_Base {
	
	const PAYMENT_ID_PARAM = "pid";
	
	protected $config;
	protected $type;
	
	/**
	 * Current transaction session.
	 * @var Zend_Payment_Session_Interface
	 */
	protected $session;
	
	/**
	 * Create object instance with passed configuration.
	 * @param Zend_Config $config Payment gateway configuration 
	 */
	public function __construct($config) {
		$this->config = $config;
	}

	/**************************************************************************
	 * NEED TO BE OVERRIDEN 
	 **************************************************************************/
	
	/** 
	 * Returns form data to payment service as key=>value array. This data serves as
	 * <input type="hidden"> form content feed. SHOULD be overriden.
	 * 
	 * In your class you should ususally provide here amount, description etc.:
	 *  
	 * 	$data["THE_AMOUNT"] =  $this->session->getAmount();
	 *	$data["THE_DESCRIPTION"] = $this->session->getDescription();
	 *	$data["MY_DATA"] =   "my data"
	 *
	 * These fields, as those above, usually depends on payment gateway provider and have to be assembled
	 * manually in derived concretes class. All necessary values should are available in $this->session.
	 * 
	 * NOTE, that all constant properties passed in config "form.*" fields will be added to the form automatically.
	 **/
	public function getFormData() {
		$array = $this->config->form->toArray();
		if ($this->getStatusUrlParam())
			$array[$this->getStatusUrlParam()] = 	$this->buildUrl('status');
		if ($this->getSuccessUrlParam())
			$array[$this->getSuccessUrlParam()] = 	$this->buildUrl('success');
		if ($this->getFailedUrlParam())
			$array[$this->getFailedUrlParam()] = 	$this->buildUrl('failed');
		return $array;
	}
	
	/**
	 * Need to validate if payment is done properly. Appropriate payment session is already loaded
	 * into $this->session. If failed, shoud throw Zend_Payment_Exception with conformed message.
	 *  
	 * @param Zend_Controller_Request_Http $request Request carrying all data from payment gateway
	 * 		provider. Each available request field, http method etc. can be used for veryfing
	 */
	protected abstract function validate(Zend_Controller_Request_Http $request);
	
	/**
	 * Helper method allowing to find payment ID from request params if standard
	 * method fails.
	 * @param Zend_Controller_Request_Http $request Current request
	 * @return String payment id or null
	 */
	public function findPaymentId(Zend_Controller_Request_Http $request) {
		// default implementation to override
		return $request->getParam(self::PAYMENT_ID_PARAM);
	}
	
	/** Returns param name pointing to status URL (where transaction status is transmitted by payment server) **/
	protected abstract function getStatusUrlParam();

	/** Returns param name pointing to success URL (where payment server redirects after transaction done) **/
	protected abstract function getSuccessUrlParam();

	/** Returns param name pointing to success URL (where payment server redirects after transaction failed) **/
	protected abstract function getFailedUrlParam();

	/** Returns form action method to payment service. Can be overriden **/
	public function getFormMethod() {
		return "POST";
	}
	
	/**************************************************************************
	 * COMMON LOGIC 
	 **************************************************************************/
	
	/**
	 * Begins payment session. Session begins after this method call and should be then handled
	 * by Zend_Payment_Controller::beginAction()
	 * 
	 * @param float $amount Money to spend
	 * @param String $description Payment description
	 * @param String $success_url Url to redirect after successful payment (can be relative: "/mycontroler/myaction?myvar=myval") 
	 * @param String $error_url Url to redirect after unsuccessul payment (as above)
	 */
	public function beginSession($amount, $description, $success_url, $error_url) {
		$loader = Zend_Payment_Registry::getInstance()->getLoader();
		$payment = $loader->build($this->type, $amount, $description, $success_url, $error_url);
		$payment->save();

		$this->session = $payment;
		return $payment->id;
	}
	
	/**
	 * Restores previously began session
	 * @param $p Zend_Payment_Session_Interface|string session object or session id
	 * @return Zend_Payment_Session_Interface 
	 */
	public function restoreSession($p) {
		if ($p instanceof Zend_Payment_Session_Interface) {
			$this->session = $p;
		} else {
			$this->session = Zend_Payment_Registry::getInstance()->getLoader()->load($p);
		}
		
		return $this->session;
	}
	
	/**
	 * Performs payment validation using internal validate() method and marks payment
	 * session object accordingly.
	 * @param $request
	 */
	public function doValidation(Zend_Controller_Request_Http $request) {
		if (!$this->session)
			throw new Zend_Payment_Exception("Payment session must be loaded to perform verification.");
		if (!$this->session->getStatus() == Zend_Payment_Session_Interface::STATUS_NEW)
			throw new Zend_Payment_Exception("This payment is not appropriated for finalizing (status=" .
				$this->session->getStatus() . ").");

		try {
			$result = $this->validate($request);
			$this->session->setStatus(Zend_Payment_Session_Interface::STATUS_DONE);
			Zend_Payment_Registry::getInstance()->getLoader()->save($this->session);
		} catch (Zend_Payment_Exception $e) {
			$this->session->status = Zend_Payment_Session_Interface::STATUS_CANCELLED;
			Zend_Payment_Registry::getInstance()->getLoader()->save($this->session);
			throw $e;
		}
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getDisplayName() {
		return $this->config->name;
	}
	
	public function getSession() {
		return $this->session;
	}
	
	/** Returns form action to payment service. Can be overriden **/
	public function getFormAction() {
		return $this->config->action;
	}

	protected function getPaymentControllerName() {
		return $this->config->controller;
	}
	
	protected function buildUrl($action) {
		return "http://". Zend_Payment_Registry::getInstance()->serverpath . "/" .
			$this->getPaymentControllerName() . "/" . $action .
			"?" . self::PAYMENT_ID_PARAM . "=" . $this->session->getId();
	}
	
}