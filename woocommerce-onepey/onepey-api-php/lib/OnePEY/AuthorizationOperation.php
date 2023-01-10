<?php
namespace OnePEY;

class AuthorizationOperation extends ApiAbstract {
  public $customer;
  public $card;
  public $money;
  protected $_description;
  protected $_tracking_id;
  protected $_notification_url;
  protected $_return_url;

  public function __construct() {
    $this->customer = new Customer();
    $this->money = new Money();
    $this->card = new Card();
    $this->_language = Language::getDefaultLanguage();
  }

  public function setDescription($description) {
    $this->_description = $description;
  }
  public function getDescription() {
    return $this->_description;
  }

  public function setTrackingId($tracking_id) {
    $this->_tracking_id = $tracking_id;
  }
  public function getTrackingId() {
    return $this->_tracking_id;
  }

  public function setNotificationUrl($notification_url) {
    $this->_notification_url = $notification_url;
  }
  public function getNotificationUrl() {
    return $this->_notification_url;
  }

  public function setReturnUrl($return_url) {
    $this->_return_url = $return_url;
  }
  public function getReturnUrl() {
    return $this->_return_url;
  }

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
        'cardNumber' => $this->card->getCardNumber(),
        'cardCVV2' => $this->card->getCardCvc(),
        'cardExpiryDate' => str_pad($this->card->getCardExpMonth(),2,'0',STR_PAD_LEFT).substr($this->card->getCardExpYear(),2),
        'cardHolderName' => $this->card->getCardHolder(),
    	//'saveCard' => $this->registerToken(),
        'description' => $this->getDescription(),
    );
    
    Logger::getInstance()->write($request, Logger::DEBUG, get_class() . '::' . __FUNCTION__);

    return $request;
  }

}
?>
