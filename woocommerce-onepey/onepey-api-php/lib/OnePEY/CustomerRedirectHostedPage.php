<?php
namespace OnePEY;

class CustomerRedirectHostedPage extends AuthorizationOperation {
  
  protected $_transactionID;
  protected $_remoteRedirectUrl;
  
  public function __construct($redirect_url = null, $uid = null) {
  	if ($redirect_url != null)
  		$this->_remoteRedirectUrl = $redirect_url;
  	if ($uid != null)
  		$this->_transactionID = $uid;
  }
  
  public function setRemoteUrl($url){
  	$this->_remoteRedirectUrl = $url;
  }
  public function getRemoteUrl() {
  	return $this->_remoteRedirectUrl;
  }
  
  public function setUid($uid){
  	$this->_transactionID = $uid;
  }
  public function getUid() {
  	return $this->_transactionID;
  }
  
  protected function _buildRequestMessage() {
  	
    $request = array(
        'amount' => $this->money->getAmount(),
        'currency' => $this->money->getCurrency(),
        'orderID' => $this->getTrackingId(),
        'returnURL' => $this->getReturnUrl(),
        'notifyURL' => $this->getNotificationUrl(),
        'transactionID' => $this->getUid(),
    );

    Logger::getInstance()->write($request, Logger::DEBUG, get_class() . '::' . __FUNCTION__);

    return $request;
  }
  
  public function getRedirectParameters(){
  	
  	return $this->_buildRequestMessage();
  }
  
  public function getFullRedirectUrl(){
  	 
  	return $this->getRemoteUrl().'?'.http_build_query($this->submitGetParams());
  }

}
?>
