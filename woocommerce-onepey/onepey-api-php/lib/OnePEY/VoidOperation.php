<?php
namespace OnePEY;

class VoidOperation extends ChildTransaction {
	
	protected function _buildRequestMessage() {
	
		$request = array(
				'transactionID' => $this->getParentUid(),
		);
		 
		Logger::getInstance()->write($request, Logger::DEBUG, get_class() . '::' . __FUNCTION__);
		 
		return $request;
	}
}
?>
