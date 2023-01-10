<?php
namespace OnePEY;

class QueryByUid extends ApiAbstract {
  protected $_uid;

  protected function _endpoint() {
    return Settings::$gatewayBase . '/transaction/status';
  }
  public function setUid($uid) {
    $this->_uid = $uid;
  }
  public function getUid() {
    return $this->_uid;
  }
  protected function _buildRequestMessage() {
   
  	$request = array(
  			'transactionID' => $this->getUid(),
  	);
  	 
  	Logger::getInstance()->write($request, Logger::DEBUG, get_class() . '::' . __FUNCTION__);
  	 
  	return $request;
  }
}
?>
