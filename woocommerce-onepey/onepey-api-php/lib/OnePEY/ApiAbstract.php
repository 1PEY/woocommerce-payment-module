<?php
namespace OnePEY;

abstract class ApiAbstract {
  protected abstract function _buildRequestMessage();
  protected $_language;

  public function submit() {
    try {
      $response = $this->_remoteRequest();
    } catch (\Exception $e) {
      $msg = $e->getMessage();
      $response = '{ "errors":"' . $msg . '", "message":"' . $msg . '" }';
    }
    return new Response($response);
  }

  protected function _remoteRequest() {
    return GatewayTransport::submit( Settings::$merchantId, Settings::$passCode , $this->_endpoint(), $this->_buildRequestMessage() );
  }

  public function submitGetParams() {
    try {
      $response = $this->_remoteRequestPrepare();
    } catch (\Exception $e) {
      $msg = $e->getMessage();
      $response = '{ "errors":"' . $msg . '", "message":"' . $msg . '" }';
    }
    return $response;
  }
  
  protected function _remoteRequestPrepare() {
    return GatewayTransport::submitGetParams( Settings::$merchantId, Settings::$passCode , $this->_endpoint(), $this->_buildRequestMessage() );
  }

  protected function _endpoint() {
  		
  	switch($this->_getTransactionType()){
  		
  		case 'authorization':
		    return Settings::$gatewayBase . '/transaction/authorize' ;
  		case 'payment':
		    return Settings::$gatewayBase . '/transaction/execute' ;
  		case 'refund':
  		case 'credit':
		    return Settings::$gatewayBase . '/transaction/refund' ;
  		case 'capture':
		    return Settings::$gatewayBase . '/transaction/capture' ;
  		case 'void':
		    return Settings::$gatewayBase . '/transaction/void' ;
  		default:
		    return Settings::$gatewayBase . '/transaction/execute' ;
  	}
		    
  }

  protected function _getTransactionType() {
  	
    list($module,$klass) = explode('\\', get_class($this));
    $klass = str_replace('Operation', '', $klass);
    $klass = str_replace('HostedPage', '', $klass);
    $klass = strtolower($klass);
    return $klass;
  }
  
  public function setLanguage($language_code) {
    if (in_array($language_code, Language::getSupportedLanguages())) {
      $this->_language = $language_code;
    }else{
      $this->_language = Language::getDefaultLanguage();
    }
  }

  public function getLanguage() {
    return $this->_language;
  }
}
?>
