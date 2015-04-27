<?php

/**
 * A PerfectMoney.com payment gateway. 
 * 
 * Default config:
 * 
 * payments.perfectmoney.name = "Perfect Money"
 * payments.perfectmoney.clazz = "Zend_Payment_Gateway_PerfectMoney"
 * payments.perfectmoney.action = "https://perfectmoney.com/api/step1.asp"
 * payments.perfectmoney.pass = "alternative pass md5 here"
 * payments.perfectmoney.form.PAYEE_ACCOUNT = "U1234567"
 * payments.perfectmoney.form.PAYEE_NAME = "my payee name"
 * payments.perfectmoney.form.PAYMENT_UNITS = "USD"
 * payments.perfectmoney.form.PAYMENT_URL_METHOD = "POST"
 * payments.perfectmoney.form.NOPAYMENT_URL_METHOD = "POST"
 * payments.perfectmoney.form.BAGGAGE_FIELDS = ""
 * payments.perfectmoney.form.PAYMENT_METHOD = "Pay Now!"
 * payments.perfectmoney.form.AVAILABLE_PAYMENT_METHODS = "all" 
 *  
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Project page: http://lifeinide.blogspot.com/2010/12/multi-payment-gateway-module-for-zend.html
 * 
 * @author l0co@wp.pl
 */
class Zend_Payment_Gateway_PerfectMoney extends Zend_Payment_Gateway_Base {
        
  public function getFormData() {
    $data = parent::getFormData();

    $data["PAYMENT_ID"] = $this->session->getId();
    $data["PAYMENT_AMOUNT"] = $this->session->getAmount();
    $data["SUGGESTED_MEMO"] = $this->session->getDescription();
    
    return $data;
  }

  public function findPaymentId(Zend_Controller_Request_Http $request) {
    return $request->getParam("PAYMENT_ID");
  }

  protected function validate(Zend_Controller_Request_Http $request) {
    // check if payment id is correct
    if ($request->getParam('PAYMENT_ID') != $this->session->getId())
      throw new Zend_Payment_Exception(sprintf(
        "Gateway transaction id (%s) is not equal with internal transaction id",
        $request->getParam('PAYMENT_ID')));

    // check if amount is correct
    if ($request->getParam('PAYMENT_AMOUNT') != $this->session->getAmount())
      throw new Zend_Payment_Exception(sprintf(
        "Gateway transaction amount (%s) is not equal with internal transaction amount",
        $request->getParam('PAYMENT_AMOUNT')));

    // build verification string and calculate hash
    $string =
      $request->getParam('PAYMENT_ID').':'.
      $request->getParam('PAYEE_ACCOUNT').':'.
      $request->getParam('PAYMENT_AMOUNT').':'.
      $request->getParam('PAYMENT_UNITS').':'.
      $request->getParam('PAYMENT_BATCH_NUM').':'.
      $request->getParam('PAYER_ACCOUNT').':'.
      $this->config->pass.':'.
      $request->getParam('TIMESTAMPGMT');
        
    $v2hash = $request->getParam('V2_HASH');
    $myhash = $hash=strtoupper(md5($string));

    if ($v2hash!=$myhash)
      throw new Zend_Payment_Exception(sprintf(
        "Invalid transaction hash (expected: %s, given: %s); possible hack attempt!",
        $myhash, $v2hash));
  }

  protected function getStatusUrlParam() {
    return "STATUS_URL";
  }

  protected function getSuccessUrlParam() {
    return "PAYMENT_URL";
  }

  protected function getFailedUrlParam() {
    return "NOPAYMENT_URL";
  }
        
}
