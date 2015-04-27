<?php

/**
 * A singleton instance of payment gateways registry.
 * 
 * You can configure payment registry from Zend_Config using configure() 
 * or manually register each gateway object instance using register().
 * 
 * For automatic registry configuration see example.application.ini with exemplary config. In short
 * it's requred to get following nodes:
 * 
 * serverpath	= "http://my.server.com"
 * controller 	= "payment controller name, extending Zend_Payment_Controller"
 * loader 		= "Zend_Payment_Session_LoaderInterface class name, maintaining session objects"
 * 
 * And for each gateway class you need to configure instance as follows:
 * 
 * mypayment.name 			= "payment display name"
 * mypayment.clazz 			= "Zend_Payment_Base derived class name"
 * mypayment.action 		= "http://payment/gateway/form/url"
 * mypayment.form.field1 	= "constant form field1 value"
 * mypayment.form.* 		= "other field..."
 * 
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Project page: http://lifeinide.blogspot.com/2010/12/multi-payment-gateway-module-for-zend.html
 * 
 * @author l0co@wp.pl
 */
class Zend_Payment_Registry {
	
	private static $instance;
	
	public $serverpath; // from config
	protected $controller; // from config
	protected $loaderClass; // from config
	protected $registry = array(); // all registered payment methods registered
	
	protected $loader = null;
	
	protected function __construct() {
	}
	
	/**
	 * @return Zend_Payment_Registry instance
	 */
	public static function getInstance() {
		if (!self::$instance) 
			self::$instance = new Zend_Payment_Registry();
		return self::$instance;
	}
	
	/**
	 * Configures payments registry from Zend_Config. For exemplary example.application.ini
	 * you need pass only $cfg->payments node.
	 */
	public function configure(Zend_Config $cfg) {
		$this->serverpath = $cfg->default->serverpath;
		$this->controller = $cfg->default->controller;
		$this->loaderClass = $cfg->default->loader;
		
		$default = $cfg->default;
		
		foreach ($cfg as $payment => $paymentcfg) {
			if ($payment != 'default') {
				// build payment config from default and specialized part
				$configObj = new Zend_Config(array(), true);
				$configObj->merge($paymentcfg)->merge($default);
				$configObj->setReadOnly();
				
				// create the payment object instance
				$paymentClass = $configObj->clazz;
				$paymentObj = new $paymentClass($configObj);
				
				// register configured payment into payments registry
				$this->register($payment, $paymentObj);
			}
		}
		
		return $this;
	}
	
	/**
	 * @return Zend_Payment_Session_LoaderInterface Session loader instance
	 */
	public function getLoader() {
		if (!$this->loader) {
			$class = $this->loaderClass;
			$this->loader = new $class();
		}
		
		return $this->loader;
	}
	
	/**
	 * Manual gateway registration
	 */
	public function register($type, Zend_Payment_Gateway_Base $instance) {
		$this->registry[$type] = $instance;
		$instance->setType($type);
	}
	
	/**
	 * @return Zend_Payment_Gateway_Base 
	 */
	public function getByType($type) {
		return $this->registry[$type];
	}
	
	public function getAll() {
		return $this->registry;
	}

	/**
	 * Lookups payment id from request using registered gateway lookup methods chain.
	 */
	public function findPaymentId(Zend_Controller_Request_Http $request) {
		foreach ($this->registry as $payment) {
			$ret = $payment->findPaymentId($request);
			if ($ret)
				return $ret;
		}
		
		return null;
	}
}

