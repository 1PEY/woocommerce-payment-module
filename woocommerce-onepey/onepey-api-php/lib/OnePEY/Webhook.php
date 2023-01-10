<?php
namespace OnePEY;

class Webhook extends Response {
	
  protected $requiredParams = array('responseCode', 'reasonCode' , 'transactionID', 'orderID', 'pSign');
  
  public function __construct() {
  	
  	$httpParams = count($this->requiredParams) == count(array_intersect($this->requiredParams, array_keys($_POST))) ? $_POST : null;
  	if ($httpParams == null)
  		$httpParams = count($this->requiredParams) == count(array_intersect($this->requiredParams, array_keys($_GET))) ? $_GET : null;

  	parent::__construct(json_encode($httpParams));
  }
  
  
  public function assignResponse($response){
  	if (is_array($response))
  		$this->__setResponseArray($response);
  	else if (is_object($response))
  		$this->__setResponse($response);
  	else 
  		return;
  }
  
  private function __setResponseArray($arr){
  	$this->_responseArray = $arr;
  	$this->_response = json_decode(json_encode($arr));
  }
  
  
  private function __setResponse($obj){
  	$this->_response = $obj;
  	$this->_responseArray = json_decode(json_encode($obj));
  }
  
  
  
  public function isAuthorized() {
  	
  	if ($this->_responseArray == null)
  		return false;
  	
  	$paramsPSignCheckArr = array_merge(array(), $this->_responseArray);
  	
  	if (isset($paramsPSignCheckArr['pSign']) && trim($paramsPSignCheckArr['pSign']) != '' 
  			&& strlen($paramsPSignCheckArr['pSign']) >= 40){
	  	
	  	$remotePSign = $paramsPSignCheckArr['pSign'];
  		unset($paramsPSignCheckArr['pSign']);
	  	$localPSign = hash(Settings::$pSignAlgorithm, implode('', array_merge(array(Settings::$passCode), array_values($paramsPSignCheckArr))));
	  	
	  	return $remotePSign == $localPSign;
  	}
  	return false;
  }
  
  
  public function getUid() {

  	if (isset($this->getResponse()->transactionID)) {
  		return $this->getResponse()->transactionID;
  	}else{
  		return false;
  	}
  }
  
  public function getTrackingId() {

  	if (isset($this->getResponse()->orderID)) {
  		return $this->getResponse()->orderID;
  	}else{
  		return false;
  	}
  }
  
}
?>