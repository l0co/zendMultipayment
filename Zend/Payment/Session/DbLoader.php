<?php

/**
 * Concrete session db loader.
 * 
 * You can use any database adapter to hold this table. 
 * Use following exemplary create table script (this one is for sqlite3):
 * 
 * CREATE TABLE payment_session (
 *    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
 *    type VARCHAR(64) NOT NULL,
 *    amount DECIMAL(10,2) NOT NULL,
 *    description TEXT,
 *    success_url VARCHAR(255) NOT NULL,
 *    error_url VARCHAR(255) NOT NULL,
 *    status INTEGER NOT NULL DEFAULT 0,
 *    status_data TEXT,
 *    response_data TEXT
 * );
 * 
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Project page: http://lifeinide.blogspot.com/2010/12/multi-payment-gateway-module-for-zend.html
 * 
 * @author l0co@wp.pl
 */
class Zend_Payment_Session_DbLoader extends Zend_Db_Table 
implements Zend_Payment_Session_LoaderInterface {
	
    protected $_name 		= "payment_session";
	protected $_primary 	= 'id';
    protected $_rowClass 	= 'Zend_Payment_Session_Db';
		
	public function load($id) {
		return $this->fetchRow("id=$id");
	}
	
	public function build($type, $amount, $description, $successUrl, $errorUrl) {
		$session = $this->createRow();
		$session->setType($type);
		$session->setAmount($amount);
		$session->setDescription($description);
		$session->setSuccessUrl($successUrl);
		$session->setErrorUrl($errorUrl);
		return $session;
	}
	
	public function save(Zend_Payment_Session_Interface $session) {
		$session->save();
	}
	
}