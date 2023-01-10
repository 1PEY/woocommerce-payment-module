<?php
namespace OnePEY;

class RefundOperation extends ChildTransaction {

  protected function _buildRequestMessage() {
    
  	$request = array(
  			'refundAmount' => $this->money->getAmount(),
  			'transactionID' => $this->getParentUid(),
  	);
  	
  	Logger::getInstance()->write($request, Logger::DEBUG, get_class() . '::' . __FUNCTION__);
  	
  	return $request;
  }
}
?>
