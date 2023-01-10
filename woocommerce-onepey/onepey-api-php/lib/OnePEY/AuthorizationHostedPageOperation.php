<?php
namespace OnePEY;

class AuthorizationHostedPageOperation extends AuthorizationOperation {
  

  protected function _buildRequestMessage() {
  	
    $request = array(
        'amount' => $this->money->getAmount(),
        'currency' => $this->money->getCurrency(),
        'orderID' => $this->getTrackingId(),
        'returnURL' => $this->getReturnUrl(),
        'notifyURL' => $this->getNotificationUrl(),
        'customerIP' => $this->customer->getIP(),
        //'customerForwardedIP' => $this->customer->getIP(),
        //'customerUserAgent' => 'n/a',
        //'customerAcceptLanguage' => $this->getLanguage(),
        'customerEmail' => $this->customer->getEmail(),
        'customerPhone' => $this->customer->getPhone(),
        'customerFirstName' => $this->customer->getFirstName(),
        'customerLastName' => $this->customer->getLastName(),
        'customerAddress1' => $this->customer->getAddress(),
        //'customerAddress2' => $this->customer->getAddress(),
        'customerCity' => $this->customer->getCity(),
        'customerZipCode' => $this->customer->getZip(),
        'customerStateProvince' => $this->customer->getState(),
        'customerCountry' => $this->customer->getCountry(),
    	//'saveCard' => $this->registerToken(),
        'description' => $this->getDescription(),
    );

    Logger::getInstance()->write($request, Logger::DEBUG, get_class() . '::' . __FUNCTION__);

    return $request;
  }

}
?>
