<?php

/**
 * Concrete payment session representation, holding data in db table.
 * See Zend_Payment_Session_DbLoader for table structure.
 * 
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Project page: http://lifeinide.blogspot.com/2010/12/multi-payment-gateway-module-for-zend.html
 * 
 * @author l0co@wp.pl
 */
class Zend_Payment_Session_Db extends Zend_Db_Table_Row 
implements Zend_Payment_Session_Interface {
	
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		return $this->id = $id;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getAmount() {
		return $this->amount;
	}
	
	public function setAmount($amount) {
		$this->amount = $amount;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function setDescription($description) {
		$this->description = $description;
	}
	
	public function getSuccessUrl() {
		return $this->success_url;
	}
	
	public function setSuccessUrl($successUrl) {
		$this->success_url = $successUrl;
	}
	
	public function getErrorUrl() {
		return $this->error_url;
	}
	
	public function setErrorUrl($errorUrl) {
		$this->error_url = $errorUrl;
	}
	
	public function getStatus() {
		return $this->status;
	}
	
	public function setStatus($status) {
		$this->status = $status;
	}
	
	public function getStatusData() {
		return $this->status_data;
	}
	
	public function addStatusData($statusData) {
		$this->status_data = $this->status_data . 
			(empty($this->status_data) ? "" : "\n") . $statusData;
	}

	public function getResponseData() {
		return $this->response_data;
	}
	
	public function addResponseData($responseData) {
		$this->response_data = $this->response_data . 
			(empty($this->response_data) ? "" : "\n") . $responseData;
	}

}