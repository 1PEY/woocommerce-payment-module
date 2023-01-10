<?php

// Tested on PHP 5.3

// This snippet (and some of the curl code) due to the Facebook SDK.
if (!function_exists('curl_init')) {
  throw new Exception('OnePEY needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('OnePEY needs the JSON PHP extension.');
}
if (!function_exists('mb_detect_encoding')) {
  throw new Exception('OnePEY needs the Multibyte String PHP extension.');
}

if (!class_exists('\OnePEY\Settings')) {
  require_once (__DIR__ . '/OnePEY/Settings.php');
  require_once (__DIR__ . '/OnePEY/Logger.php');
  require_once (__DIR__ . '/OnePEY/Language.php');
  require_once (__DIR__ . '/OnePEY/Customer.php');
  require_once (__DIR__ . '/OnePEY/Card.php');
  require_once (__DIR__ . '/OnePEY/Money.php');
  require_once (__DIR__ . '/OnePEY/ResponseBase.php');
  require_once (__DIR__ . '/OnePEY/Response.php');
  require_once (__DIR__ . '/OnePEY/ResponseCheckout.php');
  require_once (__DIR__ . '/OnePEY/ApiAbstract.php');
  require_once (__DIR__ . '/OnePEY/ChildTransaction.php');
  require_once (__DIR__ . '/OnePEY/GatewayTransport.php');
  require_once (__DIR__ . '/OnePEY/AuthorizationOperation.php');
  require_once (__DIR__ . '/OnePEY/AuthorizationHostedPageOperation.php');
  require_once (__DIR__ . '/OnePEY/PaymentOperation.php');
  require_once (__DIR__ . '/OnePEY/PaymentHostedPageOperation.php');
  require_once (__DIR__ . '/OnePEY/CustomerRedirectHostedPage.php');
  require_once (__DIR__ . '/OnePEY/CaptureOperation.php');
  require_once (__DIR__ . '/OnePEY/VoidOperation.php');
  require_once (__DIR__ . '/OnePEY/RefundOperation.php');
  require_once (__DIR__ . '/OnePEY/CreditOperation.php');
  require_once (__DIR__ . '/OnePEY/QueryByUid.php');
  require_once (__DIR__ . '/OnePEY/Webhook.php');
  require_once (__DIR__ . '/OnePEY/PaymentMethod/Base.php');
  require_once (__DIR__ . '/OnePEY/PaymentMethod/CreditCard.php');
}
?>
