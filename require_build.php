<?php
require_once __DIR__ . '/build/vendor/scoper-autoload.php';

/**
 * Here we'll map out classes used directly in OnPay woocommerce plugin or helper classes.
 * This way we'll keep class/namespace references the same in plugin code, regardless of whether plugin is built or not.
 */

/**
 * OnPay SDK classes
 */
class_alias('PrestashopOnpay\OnPay\OnPayAPI', 'OnPay\OnPayAPI');

class_alias('PrestashopOnpay\OnPay\TokenStorageInterface', 'OnPay\TokenStorageInterface');

class_alias('PrestashopOnpay\OnPay\API\GatewayService', 'OnPay\API\GatewayService');
class_alias('PrestashopOnpay\OnPay\API\PaymentWindow', 'OnPay\API\PaymentWindow');
class_alias('PrestashopOnpay\OnPay\API\SubscriptionService', 'OnPay\API\SubscriptionService');
class_alias('PrestashopOnpay\OnPay\API\TransactionService', 'OnPay\API\TransactionService');

class_alias('PrestashopOnpay\OnPay\API\Exception\ApiException', 'OnPay\API\Exception\ApiException');
class_alias('PrestashopOnpay\OnPay\API\Exception\ConnectionException', 'OnPay\API\Exception\ConnectionException');
class_alias('PrestashopOnpay\OnPay\API\Exception\TokenException', 'OnPay\API\Exception\TokenException');

class_alias('PrestashopOnpay\OnPay\API\Gateway\Information', 'OnPay\API\Gateway\Information');
class_alias('PrestashopOnpay\OnPay\API\Gateway\PaymentWindowDesignCollection', 'OnPay\API\Gateway\PaymentWindowDesignCollection');
class_alias('PrestashopOnpay\OnPay\API\Gateway\PaymentWindowIntegrationSettings', 'OnPay\API\Gateway\PaymentWindowIntegrationSettings');
class_alias('PrestashopOnpay\OnPay\API\Gateway\SimplePaymentWindowDesign', 'OnPay\API\Gateway\SimplePaymentWindowDesign');

class_alias('PrestashopOnpay\OnPay\API\Subscription\DetailedSubscription', 'OnPay\API\Subscription\DetailedSubscription');
class_alias('PrestashopOnpay\OnPay\API\Subscription\SimpleSubscription', 'OnPay\API\Subscription\SimpleSubscription');
class_alias('PrestashopOnpay\OnPay\API\Subscription\SubscriptionCollection', 'OnPay\API\Subscription\SubscriptionCollection');
class_alias('PrestashopOnpay\OnPay\API\Subscription\SubscriptionHistory', 'OnPay\API\Subscription\SubscriptionHistory');

class_alias('PrestashopOnpay\OnPay\API\Transaction\DetailedTransaction', 'OnPay\API\Transaction\DetailedTransaction');
class_alias('PrestashopOnpay\OnPay\API\Transaction\SimpleTransaction', 'OnPay\API\Transaction\SimpleTransaction');
class_alias('PrestashopOnpay\OnPay\API\Transaction\TransactionCollection', 'OnPay\API\Transaction\TransactionCollection');
class_alias('PrestashopOnpay\OnPay\API\Transaction\TransactionHistory', 'OnPay\API\Transaction\TransactionHistory');

class_alias('PrestashopOnpay\OnPay\API\Util\Converter', 'OnPay\API\Util\Converter');
class_alias('PrestashopOnpay\OnPay\API\Util\Link', 'OnPay\API\Util\Link');
class_alias('PrestashopOnpay\OnPay\API\Util\Pagination', 'OnPay\API\Util\Pagination');

/**
 * Other Classes
 */
class_alias('PrestashopOnpay\Alcohol\ISO4217', 'Alcohol\ISO4217');
