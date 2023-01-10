<?php
namespace OnePEY;

abstract class ResponseBase {

  protected $_response;
  protected $_responseArray;
  
  const APPROVED = 1;
  const DECLINED = 2;
  const FAILED = 3;
  const REDIRECT = 4;
  const CANCELLED = 5;
  const PENDING_APPROVAL = 6;
  const PENDING_REFUND = 7;
  const PENDING_PROCESSOR = 8;
  const AUTHORIZED = 10;
  const REFUNDED = 40;
  const PENDING= 80;

  public function __construct($message){
    $this->_response = json_decode($message);
    $this->_responseArray = json_decode($message, true);
  }
  public abstract function isSuccess();

  public function isError() {
    if (!is_object($this->getResponse()))
      return true;

    if (isset($this->getResponse()->responseCode) && $this->getResponse()->responseCode == self::FAILED)
      return true;

    if (isset($this->getResponse()->errorInfo))
      return true;

    return false;
  }

  public function isValid() {
    return !($this->_response === false || $this->_response == null);
  }

  public function getResponse() {
    return $this->_response;
  }

  public function getResponseArray() {
    return $this->_responseArray;
  }

}
?>
