# 1PEY payment system API integration library

[![Build Status Master](https://travis-ci.org/1PEY/onepey-api-php.svg?branch=master)](https://travis-ci.org/1PEY/onepey-api-php)

## Requirements

PHP 5.5+

## Test Account

Please register your merchant test account at https://1pey.com/backoffice/register.html before starting the integration


### Test card numbers

Refer to the documentation https://1pey.com/backoffice/docs/api/testing.html#test-cards for valid test card numbers.

## Getting started

### Setup

Before using the library classes you must configure it.
You have to setup values of variables as follows:

  * `merchantId`
  * `passCode`
  * `pSignAlgorithm`
  * `gatewayBase`

You will receive the above data after registering your account.

```php
\OnePEY\Settings::$merchantId  = XXX;
\OnePEY\Settings::$passCode = 'XXXXXXXXXXXXXXX';
\OnePEY\Settings::$pSignAlgorithm = 'sha1'; //possible values see \OnePEY\PSignAlgorithm
\OnePEY\Settings::$gatewayBase = 'https://1pey.com';
```

### Hosted payment page

Simple usage looks like:

```php
require_once __DIR__ . 'PATH_TO_INSTALLED_LIBRARY/lib/OnePEY.php';
\OnePEY\Settings::$merchantId  = XXX;
\OnePEY\Settings::$passCode = 'XXXXXXXXXXXXXXX';

\OnePEY\Logger::getInstance()->setLogLevel(\OnePEY\Logger::INFO);

$transaction = new \OnePEY\PaymentHostedPageOperation;

$transaction->money->setAmount(5.00);
$transaction->money->setCurrency('USD');
$transaction->setDescription('test');
$transaction->setTrackingId('my_custom_variable');
$transaction->setLanguage('en');
$transaction->setNotificationUrl('http://www.example.com/notify');
$transaction->setReturnUrl('http://www.example.com/return');

$transaction->customer->setFirstName('John');
$transaction->customer->setLastName('Doe');
$transaction->customer->setCountry('GB');
$transaction->customer->setAddress('Demo str 12');
$transaction->customer->setCity('London');
$transaction->customer->setZip('ATE223');
$transaction->customer->setIp('127.0.0.1');
$transaction->customer->setEmail('john@example.com');
$transaction->customer->setPhone('+441234567890');

$response = $transaction->submit();

if ($response->isValid() && !empty($response->getRedirectUrl())) {

  $customerRedirect = new CustomerRedirectHostedPage($response->getRedirectUrl(), $response->getUid());
  $customerRedirect->money = $transaction->money;
  $customerRedirect->setTrackingId('my_custom_variable');
  $customerRedirect->setReturnUrl('http://www.example.com/return');
  $customerRedirect->setNotificationUrl('http://www.example.com/notify');

  header("Location: " . $customerRedirect->getFullRedirectUrl());
}
```
### Customer redirect back or IPN

Simple usage looks like:

```php
require_once __DIR__ . 'PATH_TO_INSTALLED_LIBRARY/lib/OnePEY.php';
\OnePEY\Settings::$merchantId  = XXX;
\OnePEY\Settings::$passCode = 'XXXXXXXXXXXXXXX';

\OnePEY\Logger::getInstance()->setLogLevel(\OnePEY\Logger::INFO);

$webhook = new \OnePEY\Webhook;

if (!$webhook->isAuthorized()){
  print("Forbidden / Not Authenticated redirect/notification.");
  return;
}

if (!$webhook->isValid() || empty($webhook->getUid())) {
  print("Error in redirect/notification parameters.");
  return;
}

if ($webhook->isSuccess()) {
  print("Status: " . $webhook->getStatus() . PHP_EOL);
  print("Transaction UID: " . $response->getUid() . PHP_EOL);
} elseif ($webhook->isFailed()) {
  print("Status: " . $webhook->getStatus() . PHP_EOL);
  print("Transaction UID: " . $response->getUid() . PHP_EOL);
  print("Reason: " . $webhook->getMessage() . PHP_EOL);
} else {
  print("Status: error" . PHP_EOL);
  print("Reason: " . $webhook->getMessage() . PHP_EOL);
}
```

### Payment request via direct API

Simple usage looks like:

```php
require_once __DIR__ . 'PATH_TO_INSTALLED_LIBRARY/lib/OnePEY.php';
\OnePEY\Settings::$merchantId  = XXX;
\OnePEY\Settings::$passCode = 'b8647b68898b084b';

\OnePEY\Logger::getInstance()->setLogLevel(\OnePEY\Logger::INFO);

$transaction = new \OnePEY\Payment;

$transaction->money->setAmount(5.00);
$transaction->money->setCurrency('USD');
$transaction->setDescription('test order');
$transaction->setTrackingId('my_custom_variable');

$transaction->card->setCardNumber('4200000000000000');
$transaction->card->setCardHolder('John Doe');
$transaction->card->setCardExpMonth(1);
$transaction->card->setCardExpYear(2030);
$transaction->card->setCardCvc('123');

$transaction->customer->setFirstName('John');
$transaction->customer->setLastName('Doe');
$transaction->customer->setCountry('GB');
$transaction->customer->setAddress('Demo str 12');
$transaction->customer->setCity('London');
$transaction->customer->setZip('ATE223');
$transaction->customer->setIp('127.0.0.1');
$transaction->customer->setEmail('john@example.com');
$transaction->customer->setPhone('+441234567890');

$response = $transaction->submit();

if ($response->isSuccess()) {
  print("Status: " . $response->getStatus() . PHP_EOL);
  print("Transaction UID: " . $response->getUid() . PHP_EOL);
} elseif ($response->isFailed()) {
  print("Status: " . $response->getStatus() . PHP_EOL);
  print("Transaction UID: " . $response->getUid() . PHP_EOL);
  print("Reason: " . $response->getMessage() . PHP_EOL);
} else {
  print("Status: error" . PHP_EOL);
  print("Reason: " . $response->getMessage() . PHP_EOL);
}
```

## Examples

See the [examples](examples) directory for integration examples of different
transactions.

## Documentation

Visit https://1pey.com/backoffice/docs/api/index.html for up-to-date documentation.

## Tests

To run tests

```bash
php -f ./test/OnePEY.php
```
