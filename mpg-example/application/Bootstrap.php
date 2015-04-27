<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initGlobalConfig() {
        Zend_Registry::getInstance()->set('config', $config = new Zend_Config_Ini(
                APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV, null));
        return $config;
    }
	
    public function _initPaymentRegistry() {
		$r = Zend_Registry::getInstance();
		$r->set('payments', Zend_Payment_Registry::getInstance()->configure($r->config->payments));
	}

}

