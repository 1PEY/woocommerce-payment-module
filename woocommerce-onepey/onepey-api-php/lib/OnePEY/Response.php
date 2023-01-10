<?php
namespace OnePEY;

class Response extends ResponseBase {

  public function isSuccess() {
    return in_array($this->getStatus(), array(ResponseBase::APPROVED, ResponseBase::AUTHORIZED));
  }

  public function isFailed() {
    return in_array($this->getStatus(), array(ResponseBase::FAILED));
  }

  public function isIncomplete() {
    return in_array($this->getStatus(), 
    		array(ResponseBase::PENDING, ResponseBase::PENDING_APPROVAL, ResponseBase::PENDING_PROCESSOR, ResponseBase::PENDING_REFUND));
  }

  public function isDeclined() {
    return in_array($this->getStatus(), array(ResponseBase::DECLINED));
  }

  public function isPending() {
    return in_array($this->getStatus(), 
    		array(ResponseBase::PENDING, ResponseBase::PENDING_APPROVAL, ResponseBase::PENDING_PROCESSOR, ResponseBase::PENDING_REFUND));
  }

  public function isTest() {
    return false;
  }

  public function getStatus() {
  	
    if (isset($this->getResponse()->responseCode))
      return $this->getResponse()->responseCode;
    else
      return null;
  }

  public function getUid() {
    if ($this->hasTransactionSection()) {
      return $this->getResponse()->transaction->transactionID;
    }else{
      return false;
    }
  }

  public function getRedirectUrl() {
    if (isset($this->getResponse()->redirectURL)) {
      return $this->getResponse()->redirectURL;
    }else{
      return false;
    }
  }

  public function getTrackingId() {
    if ($this->hasTransactionSection()) {
      return $this->getResponse()->transaction->orderID;
    }else{
      return false;
    }
  }

  public function getPaymentMethod() {
   return false;
  }

  public function hasTransactionSection() {
    return (is_object($this->getResponse()) && isset($this->getResponse()->transaction));
  }

  public function getMessage() {

    if (is_object($this->getResponse())) {

      if (isset($this->getResponse()->errorInfo))
        return $this->getResponse()->errorInfo;
      
      else if (isset($this->getResponse()->responseCode)){
      	
		switch($this->getResponse()->responseCode){

			case ResponseBase::APPROVED:
				return 'Approved';
			case ResponseBase::AUTHORIZED:
				return 'Authorized';
			case ResponseBase::CANCELLED:
				return 'Cancelled';
			case ResponseBase::DECLINED:
				return 'Declined';
			case ResponseBase::FAILED:
				return 'Failed';
			case ResponseBase::PENDING:
				return 'Pending';
			case ResponseBase::PENDING_APPROVAL:
				return 'Pending Approval';
			case ResponseBase::PENDING_PROCESSOR:
				return 'Pending Processor';
			case ResponseBase::PENDING_REFUND:
				return 'Pending Refund';
			case ResponseBase::REDIRECT:
				return 'Pending Customer Redirect';
			case ResponseBase::REFUNDED:
				return 'Refunded';
				
			default:
		        return '';
		}
      }
    }

    return '';

  }
}
?>
