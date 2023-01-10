<?php

namespace OnePEY;

class Settings {
  public static $merchantId;
  public static $passCode;
  public static $pSignAlgorithm = 'sha1';
  public static $gatewayBase  = 'https://1pey.com/transaction/execute';
  public static $checkoutBase = 'https://1pey.com/transaction/customerDirect';
  public static $apiBase      = 'https://1pey.com';
}
?>
