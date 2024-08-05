<?php
/**
 * MIT License
 *
 * Copyright (c) 2019 OnPay.io
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once __DIR__ . '/require.php';
require_once __DIR__ . '/classes/CurrencyHelper.php';
require_once __DIR__ . '/classes/TokenStorage.php';
require_once __DIR__ . '/classes/Release.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class onpay extends PaymentModule {
    const ONPAY_PLUGIN_VERSION = '1.0.19';
    const ONPAY_PLATFORM_STRING = 'prestashop17/' . self::ONPAY_PLUGIN_VERSION . '/' . _PS_VERSION_;
    const SETTING_ONPAY_GATEWAY_ID = 'ONPAY_GATEWAY_ID';
    const SETTING_ONPAY_SECRET = 'ONPAY_SECRET';
    const SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY = 'ONPAY_EXTRA_PAYMENTS_MOBILEPAY';
    const SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL = 'ONPAY_EXTRA_PAYMENTS_VIABILL';
    const SETTING_ONPAY_EXTRA_PAYMENTS_ANYDAY = 'ONPAY_EXTRA_PAYMENTS_ANYDAY_SPLIT';
    const SETTING_ONPAY_EXTRA_PAYMENTS_VIPPS = 'ONPAY_EXTRA_PAYMENTS_VIPPS';
    const SETTING_ONPAY_EXTRA_PAYMENTS_SWISH = 'ONPAY_EXTRA_PAYMENTS_SWISH';
    const SETTING_ONPAY_EXTRA_PAYMENTS_CARD = 'ONPAY_EXTRA_PAYMENTS_CARD';
    const SETTING_ONPAY_EXTRA_PAYMENTS_KLARNA = 'ONPAY_EXTRA_PAYMENTS_KLARNA';
    const SETTING_ONPAY_EXTRA_PAYMENTS_PAYPAL = 'ONPAY_EXTRA_PAYMENTS_PAYPAL';
    const SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY = 'ONPAY_EXTRA_PAYMENTS_APPLE_PAY';
    const SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY = 'ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY';
    const SETTING_ONPAY_PAYMENTWINDOW_DESIGN = 'ONPAY_PAYMENTWINDOW_DESIGN';
    const SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE = 'ONPAY_PAYMENTWINDOW_LANGUAGE';
    const SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO = 'ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO';
    const SETTING_ONPAY_TOKEN = 'ONPAY_TOKEN';
    const SETTING_ONPAY_TESTMODE = 'ONPAY_TESTMODE_ENABLED';
    const SETTING_ONPAY_CARDLOGOS = 'ONPAY_CARD_LOGOS';
    const SETTING_ONPAY_HOOK_VERSION = 'ONPAY_HOOK_VERSION';
    const SETTING_ONPAY_ORDERSTATUS_AWAIT = 'ONPAY_OS_AWAIT';
    const SETTING_ONPAY_AUTOCAPTURE = 'SETTING_ONPAY_AUTOCAPTURE';
    const SETTING_ONPAY_AUTOCAPTURE_STATUS = 'SETTING_ONPAY_AUTOCAPTURE_STATUS';
    const SETTING_ONPAY_LOCKEDCART_TABLE = 'onpay_locked_cart';
    const SETTING_ONPAY_LOCKEDCART_TABLE_CREATED = 'ONPAY_LOCKEDCART_CREATED';
    const SETTING_ONPAY_RELEASE_INFO = 'ONPAY_RELEASE_INFO';

    protected $htmlContent = '';

    /**
     * @var \OnPay\OnPayAPI $client
     */
    protected $client;

    /**
     * @var array $_postErrors
     */
    protected $_postErrors = [];

    /**
     * @var CurrencyHelper $currencyHelper
     */
    protected $currencyHelper;

    public function __construct() {
        $this->name = 'onpay';
        $this->tab = 'payments_gateways';
        $this->version = self::ONPAY_PLUGIN_VERSION;
        $this->ps_versions_compliancy = array('min' => '1.7.0.1', 'max' => _PS_VERSION_);
        $this->author = 'OnPay.io';
        $this->need_instance = 0;
        $this->controllers = array('payment', 'callback');
        $this->is_eu_compatible = 1;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('OnPay');
        $this->description = $this->l('Use OnPay.io for handling payments');
        $this->confirmUninstall = $this->l('Are you sure about uninstalling the OnPay.io module?');
        $this->currencyHelper = new CurrencyHelper();

        $this->registerHooks();
        $this->registerOrderState();
        $this->registerCartLockTable();
    }

    private function registerHooks() {
        $hookVersion = 6;
        $currentHookVersion = Configuration::get(self::SETTING_ONPAY_HOOK_VERSION, null, null, null, 0);

        if ($currentHookVersion >= $hookVersion) {
            return;
        }

        $hooks = [
            1 => [
                'paymentOptions',
                'adminOrder',
            ],
            2 => [
                'actionFrontControllerSetMedia',
            ],
            3 => [
                'displayAdminOrderMainBottom',
                'actionAdminControllerSetMedia',
            ],
            4 => [
                'actionOrderStatusUpdate',
            ],
            5 => [
                'dashboardZoneTwo',
            ],
            6 => [
                'displayBeforeBodyClosingTag',
            ],
        ];

        $positions = [
            'dashboardZoneTwo' => 1,
        ];

        $highestVersion = 0;
        foreach ($hooks as $version => $versionHooks) {
            if ($hookVersion >= $version) {
                foreach ($versionHooks as $hook) {
                    if (!$this->isRegisteredInHook($hook)) {
                        $this->registerHook($hook);
                        // Update position if requested
                        if (array_key_exists($hook, $positions)) {
                            $hookId = Hook::getIdByName($hook);
                            $this->updatePosition($hookId, false, $positions[$hook]);
                        }
                    }
                }
                $highestVersion = $hookVersion;
            }
        }

        Configuration::updateValue(self::SETTING_ONPAY_HOOK_VERSION, $highestVersion);
    }

    private function registerOrderState() {
        $awaitingStateName = 'Awaiting OnPay Payment';

        // If configuration key exists no need to register state
        if (Configuration::get(self::SETTING_ONPAY_ORDERSTATUS_AWAIT, null, null, null, 0) !== 0) {
            return;
        }

        // check if order state exist
        $state_exist = false;
        foreach (OrderState::getOrderStates((int)$this->context->language->id) as $state) {
            if (in_array($awaitingStateName, $state)) {
                $state_exist = true;
                break;
            }
        }
 
        // If the state does not exist, we create it.
        if (!$state_exist) {
            // Create new order state
            $orderState = new OrderState();
            $orderState->color = '#34209E'; // PS color for awaiting
            $orderState->send_email = false;
            $orderState->module_name = $this->name;
            $orderState->name = array();
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                $orderState->name[$language['id_lang']] = $awaitingStateName;
            }
            // Add state
            $orderState->add();
            // Save order state ID for later use
            Configuration::updateValue(self::SETTING_ONPAY_ORDERSTATUS_AWAIT, $orderState->id);
        }
    }

    private function unregisterOrderState() {
        if (Configuration::get(self::SETTING_ONPAY_ORDERSTATUS_AWAIT, null, null, null, 0) > 0) {
            $orderState = new OrderState(Configuration::get(self::SETTING_ONPAY_ORDERSTATUS_AWAIT));
            $orderState->delete();
        }
        return true;
    }
    
    /**
     * Create table used for locked carts
     */
    private function registerCartLockTable() {
        if (Configuration::get(self::SETTING_ONPAY_LOCKEDCART_TABLE_CREATED, null, null, null, false)) {
            return;
        }
        $tableName = _DB_PREFIX_ . self::SETTING_ONPAY_LOCKEDCART_TABLE;
        $db = Db::getInstance();
        $db->execute('CREATE TABLE `' . $tableName . '` (`id_cart` INT(10) UNSIGNED NOT NULL)') !== false;
        Configuration::updateValue(self::SETTING_ONPAY_LOCKEDCART_TABLE_CREATED, true);
    }

    /**
     * Drop table used for locked carts
     */
    private function dropCartLockTable() {
        $tableName = _DB_PREFIX_ . self::SETTING_ONPAY_LOCKEDCART_TABLE;
        $db = Db::getInstance();
        return $db->execute('DROP TABLE `' . $tableName . '`') !== false;
    }

    public function install() {
        if (
            !parent::install() ||
            !Configuration::updateValue($this::SETTING_ONPAY_HOOK_VERSION, 0) ||
            !Configuration::updateValue(self::SETTING_ONPAY_CARDLOGOS, json_encode(['mastercard', 'visa'])) // Set default values for card logos
        ) {
            return false;
        }
        return true;
    }

    public function uninstall() {
        if (
            parent::uninstall() == false ||
            !$this->unregisterOrderState() ||
            !$this->dropCartLockTable() ||
            !Configuration::deleteByName($this::SETTING_ONPAY_GATEWAY_ID) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_SECRET) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_ANYDAY) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_VIPPS) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_SWISH) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_CARD) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_KLARNA) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_PAYPAL) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_PAYMENTWINDOW_DESIGN) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_TOKEN) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_TESTMODE) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_CARDLOGOS) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_HOOK_VERSION) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_ORDERSTATUS_AWAIT) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_AUTOCAPTURE) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_AUTOCAPTURE_STATUS) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_LOCKEDCART_TABLE_CREATED)
        ) {
            return false;
        }
        return true;
    }


    /**
     * Administration page
     */
    public function getContent() {
        $this->htmlContent .= $this->renderReleaseInfo();

        if('true' === Tools::getValue('detach')) {
            $params = [];
            $params['token'] = Tools::getAdminTokenLite('AdminModules');
            $params['controller'] = 'AdminModules';
            $params['configure'] = 'onpay';
            $params['tab_module'] = 'payments_gateways';
            $params['module_name'] = 'onpay';
            $url = $this->generateUrl($params);
            Configuration::deleteByName(self::SETTING_ONPAY_TOKEN);
            return Tools::redirectLink($url);
        }

        $onpayApi = $this->getOnpayClient(true);
        if(false !== Tools::getValue('code') || 'true' === Tools::getValue('refresh')) {
            if (!$onpayApi->isAuthorized() && false !== Tools::getValue('code')) {
                $onpayApi->finishAuthorize(Tools::getValue('code'));
            }
            Configuration::updateValue(self::SETTING_ONPAY_GATEWAY_ID, $onpayApi->gateway()->getInformation()->gatewayId);
            Configuration::updateValue(self::SETTING_ONPAY_SECRET, $onpayApi->gateway()->getPaymentWindowIntegrationSettings()->secret);
        }

        if (Tools::isSubmit('btnSubmit'))
        {
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->htmlContent .= $this->displayError($err);
                }
            }
        }

        $error = null;
        try {
            $this->htmlContent .= $this->renderAdministrationForm();
        } catch (\OnPay\API\Exception\ApiException $exception) {
            // If we hit an ApiException, something bad happened with our token and we'll delete the token and show the auth-page again.
            Configuration::deleteByName(self::SETTING_ONPAY_TOKEN);
            $error = $this->displayError($this->l('Token from OnPay is either revoked from the OnPay gateway or is expired'));
        }

        $this->smarty->assign(array(
            'form' => $this->htmlContent,
            'isAuthorized' => $onpayApi->isAuthorized(),
            'authorizationUrl' => $onpayApi->authorize(),
            'error' => $error
        ));

        return $this->display(__FILE__, 'views/admin/settings.tpl');
    }


    /**
     * Hooks
     */

    /**
     * Hooks custom CSS to header in backoffice
     */
    public function hookActionAdminControllerSetMedia() {
        $this->context->controller->addCSS($this->_path.'/views/css/back.css');
        $this->context->controller->addJS($this->_path . '/views/js/back.js');
    }

    /**
     * Hooks CSS to header in frontend
     */
    public function hookActionFrontControllerSetMedia() {
        $this->context->controller->registerStylesheet($this->name . '-front_css', $this->_path.'views/css/front.css');

        // If either Apple Pay or Google Pay is enabled, register frontend script for managing these.
        if ($this->showGAPay()) {
            $this->context->controller->registerJavascript($this->name . '-script_jssdk', 'https://onpay.io/sdk/v1.js', ['server' => 'remote']);
            $this->context->controller->registerJavascript($this->name . '-script', $this->_path.'views/js/apple_google_pay.js');
        }
    }

    /**
     * Hooks JS and variables
     */
    public function hookDisplayBeforeBodyClosingTag() {
        $return = '';
        // If either Apple Pay or Google Pay is enabled, register frontend script for managing these.
        if ($this->showGAPay()) {
            $appleId = null;
            $googleId = null;
            $optionFinder = new PaymentOptionsFinder;
            $options = $optionFinder->present();

            if (array_key_exists('onpay', $options)) {
                foreach ($options['onpay'] as $option) {
                    if (array_key_exists('module_name', $option) && array_key_exists('id', $option)) {
                        if ($option['module_name'] === $this->name . '_applepay') {
                            $appleId = $option['id'];
                        }
                        if ($option['module_name'] === $this->name . '_googlepay') {
                            $googleId = $option['id'];
                        }
                    }
                }
            }

            // Prepare Google and Apple Pay entries, add variable for IDs and hide inputs
            $return = '<style type="text/css">';
            if (null !== $appleId) {
                $return .= '#' . $appleId . '-container {display:none}';
                $return .= '#' . $appleId . '-container.show {display:inherit}';
            }
            if (null !== $googleId) {
                $return .= '#' . $googleId . '-container {display:none}';
                $return .= '#' . $googleId . '-container.show {display:inherit}';
            }
            $return .= '</style>';
            $return .= '<script type="text/javascript">';
            if (null !== $appleId) {
                $return .= 'let appleId="' . $appleId . '";';
            }
            if (null !== $googleId) {
                $return .= 'let googleId="' . $googleId . '";';
            }
            $return .= '</script>';
        }
        return $return;
    }

    private function showGAPay() {
        if (
            $this->context->controller->getPageName() === 'checkout' &&
            (
                Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY) === '1' ||
                Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY) === '1'
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Generates the view when placing an order and card payment, viabill and mobilepay is shown as an option
     * @param array $params
     * @return mixed
     */
    public function hookPaymentOptions(array $params) {
        if($this->getOnpayClient()->isAuthorized()) {
            $order = $params['cart'];
            $currency = new Currency($order->id_currency);
            $currencyUtil = new \OnPay\API\Util\Currency($currency->iso_code);

            if (null === $this->currencyHelper->fromNumeric($currency->iso_code_num)) {
                // If we can't determine the currency, we wont show the payment method at all.
                return;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_CARD)) {
                $cardLogos = [];
                foreach (json_decode(Configuration::get(self::SETTING_ONPAY_CARDLOGOS), true) as $cardLogo) {
                    $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/' . $cardLogo . '.svg');
                }
                if (count($cardLogos) === 0) {
                    $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/generic.svg');
                }

                $cardOption = new PaymentOption();
                $cardOption->setModuleName($this->name)
                    ->setCallToActionText($this->l('Pay with credit card'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_CARD, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos($cardLogos));
                $payment_options[] = $cardOption;
            }

            // Not available in testmode
            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY) && !Configuration::get(self::SETTING_ONPAY_TESTMODE) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_APPLEPAY)) {
                $apOption = new PaymentOption();
                $apOption->setModuleName($this->name . '_applepay')
                    ->setCallToActionText($this->l('Pay using Apple Pay'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_APPLEPAY, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos([
                        $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/apple-pay.svg')
                    ]));
                $payment_options[] = $apOption;
            }

            // Not available in testmode
            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY) && !Configuration::get(self::SETTING_ONPAY_TESTMODE) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_GOOGLEPAY)) {
                $gpOption = new PaymentOption();
                $gpOption->setModuleName($this->name . '_googlepay')
                    ->setCallToActionText($this->l('Pay using Google Pay'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_GOOGLEPAY, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos([
                        $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/google-pay.svg')
                    ]));
                $payment_options[] = $gpOption;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_VIABILL)) {
                $vbOption = new PaymentOption();
                $vbOption->setModuleName($this->name)
                    ->setCallToActionText($this->l('Pay through ViaBill'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_VIABILL, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos([
                        $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/viabill.svg')
                    ]));
                $payment_options[] = $vbOption;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_ANYDAY) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_ANYDAY)) {
                $asOption = new PaymentOption();
                $asOption->setModuleName($this->name)
                    ->setCallToActionText($this->l('Pay through Anyday'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_ANYDAY, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos([
                        $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/anyday.svg')
                    ]));
                $payment_options[] = $asOption;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIPPS) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_VIPPS)) {
                $vipOption = new PaymentOption();
                $vipOption->setModuleName($this->name)
                    ->setCallToActionText($this->l('Pay through Vipps'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_VIPPS, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos([
                        $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/vipps.svg')
                    ]));
                $payment_options[] = $vipOption;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_SWISH) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_SWISH)) {
                $swiOption = new PaymentOption();
                $swiOption->setModuleName($this->name)
                    ->setCallToActionText($this->l('Pay through Swish'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_SWISH, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos([
                        $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/swish.svg')
                    ]));
                $payment_options[] = $swiOption;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_MOBILEPAY)) {
                $mpoOption = new PaymentOption();
                $mpoOption->setModuleName($this->name)
                    ->setCallToActionText($this->l('Pay through MobilePay'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_MOBILEPAY, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos([
                        $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/mobilepay.svg')
                    ]));
                $payment_options[] = $mpoOption;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_KLARNA) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_KLARNA)) {
                $swiOption = new PaymentOption();
                $swiOption->setModuleName($this->name)
                    ->setCallToActionText($this->l('Pay through Klarna'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_KLARNA, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos([
                        $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/klarna.svg')
                    ]));
                $payment_options[] = $swiOption;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_PAYPAL) && $currencyUtil->isPaymentMethodAvailable(\OnPay\API\PaymentWindow::METHOD_PAYPAL)) {
                $swiOption = new PaymentOption();
                $swiOption->setModuleName($this->name)
                    ->setCallToActionText($this->l('Pay through PayPal'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_PAYPAL, $currency)))
                    ->setAdditionalInformation($this->renderMethodLogos([
                        $cardLogos[] = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/paypal.svg')
                    ]));
                $payment_options[] = $swiOption;
            }

            return $payment_options;
        }
        return;
    }

    public function hookDisplayAdminOrderMainBottom($params) {
        return $this->handleAdminOrderHook('views/admin/order_details.tpl', $params);
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookActionOrderStatusUpdate($params) {
        $newStatus = $params['newOrderStatus'];
        $order = new Order($params['id_order']);
        
        // Check if auto capture is enabled, and that new status is the correct status.
        if (Configuration::get(self::SETTING_ONPAY_AUTOCAPTURE) && intval(Configuration::get(self::SETTING_ONPAY_AUTOCAPTURE_STATUS)) === $newStatus->id) {
            $payments = $order->getOrderPayments();
            $onPayAPI = $this->getOnpayClient();

            if (!$onPayAPI->isAuthorized()) {
                return;
            }

            // Loop over payments on order
            foreach ($payments as $payment) {
                // Check if order payment method is OnPay
                if ($payment->payment_method === 'OnPay' && null !== $payment->transaction_id && '' !== $payment->transaction_id) {
                    $transaction = $onPayAPI->transaction()->getTransaction($payment->transaction_id);
                    // If transaction has status active, and charged amount is less than the full amount, we'll capture the remaining amount on transaction
                    if ($transaction->status === 'active' && $transaction->charged < $transaction->amount) {
                        try {
                            $onPayAPI->transaction()->captureTransaction($payment->transaction_id);
                        } catch (\OnPay\API\Exception\ApiException $exception) {
                            // No need to do anything here
                        }
                    }
                }
            }
        }
    }

    /**
     * Actions on order page
     * @param $params
     * @return mixed
     */
    public function hookAdminOrder($params) {
        if (in_array('displayAdminOrderMainBottom', Hook::$executed_hooks)) {
            // If hook displayAdminOrderMainBottom is executed, no need to do anything here, since the displayAdminOrder hook is used for legacy.
            return;
        }

        return $this->handleAdminOrderHook('views/admin/order_details_legacy.tpl', $params);
    }

    private function handleAdminOrderHook($template, $params) {
        $order = new Order($params['id_order']);
        $payments = $order->getOrderPayments();

        $onPayAPI = $this->getOnpayClient();

        if (!$onPayAPI->isAuthorized()) {
            return;
        }

        if(Tools::isSubmit('onpayCapture')) {
            foreach ($payments as $payment) {
                try {
                    $onPayAPI->transaction()->captureTransaction($payment->transaction_id);
                    $this->context->controller->confirmations[] = $this->l("Captured transaction");
                } catch (\OnPay\API\Exception\ApiException $exception) {
                    $this->context->controller->errors[] = Tools::displayError($this->l('Could not capture payment'));
                }
            }
        }

        if(Tools::isSubmit('onpayCancel')) {
            foreach ($payments as $payment) {
                try {
                    $onPayAPI->transaction()->cancelTransaction($payment->transaction_id);
                    $this->context->controller->confirmations[] = $this->l("Cancelled transaction");
                } catch (\OnPay\API\Exception\ApiException $exception) {
                    $this->context->controller->errors[] = Tools::displayError($this->l('Could not cancel transaction'));
                }
            }
        }

        if(Tools::isSubmit('refund_value')) {
            foreach ($payments as $payment) {
                try {
                    $value = Tools::getValue('refund_value');
                    $currency = Tools::getValue('refund_currency');
                    $value = str_replace('.', ',', $value);
                    $amount = $this->currencyHelper->majorToMinor($value, $currency, ',');
                    $onPayAPI->transaction()->refundTransaction($payment->transaction_id, $amount);
                    $this->context->controller->confirmations[] = $this->l("Refunded transaction");
                } catch (\OnPay\API\Exception\ApiException $exception) {
                    $this->context->controller->errors[] = Tools::displayError($this->l('Could not refund transaction'));
                }
            }
        }

        if(Tools::isSubmit('onpayCapture_value')) {
            foreach ($payments as $payment) {
                try {
                    $value = Tools::getValue('onpayCapture_value');
                    $currency = Tools::getValue('onpayCapture_currency');
                    $value = str_replace('.', ',', $value);
                    $amount = $this->currencyHelper->majorToMinor($value, $currency, ',');
                    $onPayAPI->transaction()->captureTransaction($payment->transaction_id, $amount);
                    $this->context->controller->confirmations[] = $this->l("Captured transaction");
                } catch (\OnPay\API\Exception\ApiException $exception) {
                    $this->context->controller->errors[] = Tools::displayError($this->l('Could not capture transaction'));
                }
            }
        }

        $details = [];

        try {
            foreach ($payments as $payment) {
                if ($payment->payment_method === 'OnPay' && null !== $payment->transaction_id && '' !== $payment->transaction_id) {
                    $onpayInfo = $onPayAPI->transaction()->getTransaction($payment->transaction_id);
                    $amount  = $this->currencyHelper->minorToMajor($onpayInfo->amount, $onpayInfo->currencyCode, ',');
                    $chargable = $onpayInfo->amount - $onpayInfo->charged;
                    $chargable = $this->currencyHelper->minorToMajor($chargable, $onpayInfo->currencyCode, ',');
                    $refunded = $this->currencyHelper->minorToMajor($onpayInfo->refunded, $onpayInfo->currencyCode, ',');
                    $charged = $this->currencyHelper->minorToMajor($onpayInfo->charged, $onpayInfo->currencyCode, ',');
                    $currency = $this->currencyHelper->fromNumeric($onpayInfo->currencyCode);

                    $currencyCode = $onpayInfo->currencyCode;

                    array_walk($onpayInfo->history, function(\OnPay\API\Transaction\TransactionHistory $history) use($currencyCode) {
                        $amount = $history->amount;
                        $amount = $this->currencyHelper->minorToMajor($amount, $currencyCode, ',');
                        $history->amount = $amount;
                    });

                    $refundable = $onpayInfo->charged - $onpayInfo->refunded;
                    $refundable = $this->currencyHelper->minorToMajor($refundable, $onpayInfo->currencyCode,',');
                    $details[] = [
                        'details' => ['amount' => $amount, 'chargeable' => $chargable, 'refunded' => $refunded, 'charged' => $charged, 'refundable' => $refundable, 'currency' => $currency],
                        'payment' => $payment,
                        'onpay' => $onpayInfo,
                    ];
                }
            }
        } catch (\OnPay\API\Exception\ApiException $exception) {
            // If there was problems, we'll show the same as someone with an unauthed acc
            $this->smarty->assign(array(
                'paymentdetails' => $details,
                'url' => '',
                'isAuthorized' => false,
                'this_path' => $this->_path,
            ));
            return $this->display(__FILE__, $template);
        }

        $url = $_SERVER['REQUEST_URI'];
        $this->smarty->assign(array(
            'paymentdetails' => $details,
            'url' => $url,
            'isAuthorized' => $this->getOnpayClient()->isAuthorized(),
            'this_path' => $this->_path,
        ));
        return $this->display(__FILE__, $template);
    }

    public function hookDashboardZoneTwo() {
        return $this->renderReleaseInfo();
    }

    /**
     * Utilities
     */

    /**
     * Returns an instantiated OnPay API client
     *
     * @return \OnPay\OnPayAPI
     */
    private function getOnpayClient($prepareRedirectUri = false) {
        $tokenStorage = new TokenStorage();

        $params = [];
        // AdminToken cannot be generated on payment pages
        if($prepareRedirectUri) {
            $params['token'] = Tools::getAdminTokenLite('AdminModules');
            $params['controller'] = 'AdminModules';
            $params['configure'] = 'onpay';
            $params['tab_module'] = 'payments_gateways';
            $params['module_name'] = 'onpay';
        }

        $url = $this->generateUrl($params);
        $onPayAPI = new \OnPay\OnPayAPI($tokenStorage, [
            'client_id' => 'Onpay Prestashop',
            'redirect_uri' => $url,
            'platform' => self::ONPAY_PLATFORM_STRING,
        ]);
        return $onPayAPI;
    }

    /**
     * Generates payment window object for use on the payment page
     * @param $order
     * @param $payment
     * @param $currency
     * @return \OnPay\API\PaymentWindow
     */
    private function getPaymentWindow($order, $payment, $currency) {
        // We'll need to find out details about the currency, and format the order total amount accordingly
        $isoCurrency = $this->currencyHelper->fromNumeric($currency->iso_code_num);
        $orderTotal = number_format($order->getOrderTotal(), $isoCurrency->exp, '', '');

        $paymentWindow = new \OnPay\API\PaymentWindow();
        $paymentWindow->setGatewayId(Configuration::get(self::SETTING_ONPAY_GATEWAY_ID));
        $paymentWindow->setSecret(Configuration::get(self::SETTING_ONPAY_SECRET));
        $paymentWindow->setCurrency($isoCurrency->alpha3);
        $paymentWindow->setAmount($orderTotal);
        // Reference must be unique (eg. invoice number)
        $paymentWindow->setReference($order->id);
        $paymentWindow->setAcceptUrl($this->context->link->getModuleLink('onpay', 'payment', ['accept' => 1], Configuration::get('PS_SSL_ENABLED')));
        $paymentWindow->setDeclineUrl($this->context->link->getModuleLink('onpay', 'payment', [], Configuration::get('PS_SSL_ENABLED')));
        $paymentWindow->setType("payment");
        $paymentWindow->setCallbackUrl($this->context->link->getModuleLink('onpay', 'callback', [], Configuration::get('PS_SSL_ENABLED'), null));
        $paymentWindow->setWebsite(Tools::getHttpHost(true).__PS_BASE_URI__);
        $paymentWindow->setPlatform('prestashop17', $this->version, _PS_VERSION_);

        if(Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN)) {
            $paymentWindow->setDesign(Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN));
        }

        if (Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO)) {
            $paymentWindow->setLanguage($this->getPaymentWindowLanguageByPSLanguage($this->context->language->iso_code));
        } else if (Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE)) {
            $paymentWindow->setLanguage(Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE));
        }

        // Set payment method
        $paymentWindow->setMethod($payment);

        // Add additional info
        $customer = new Customer($order->id_customer);

        $invoice_address = new Address($order->id_address_invoice);
        $invoice_country = new Country($invoice_address->id_country);
        $invoice_state = new State($invoice_address->id_state);

        $delivery_address = new Address($order->id_address_invoice);
        $delivery_country = new Country($delivery_address->id_country);
        $delivery_state = new State($delivery_address->id_state);

        $paymentInfo = new \OnPay\API\PaymentWindow\PaymentInfo();

        $this->setPaymentInfoParameter($paymentInfo, 'AccountId', $customer->id);

        $dateCreated = strtotime($customer->date_add);
        if ($dateCreated) {
            $this->setPaymentInfoParameter($paymentInfo, 'AccountDateCreated', date('Y-m-d', $dateCreated));
        }

        $dateChange = strtotime($customer->date_upd);
        if ($dateChange) {
            $this->setPaymentInfoParameter($paymentInfo, 'AccountDateChange', date('Y-m-d', $dateChange));
        }

        $datePwChange = strtotime($customer->last_passwd_gen);
        if ($datePwChange) {
            $this->setPaymentInfoParameter($paymentInfo, 'AccountDatePasswordChange', date('Y-m-d', $datePwChange));
        }

        $dateShipFirst = strtotime($delivery_address->date_add);
        if ($dateShipFirst) {
            $this->setPaymentInfoParameter($paymentInfo, 'AccountShippingFirstUseDate', date('Y-m-d', $dateShipFirst));
        }

        if ($invoice_address->id === $delivery_address->id) {
            $this->setPaymentInfoParameter($paymentInfo, 'AccountShippingIdenticalName', 'Y');
            $this->setPaymentInfoParameter($paymentInfo, 'AddressIdenticalShipping', 'Y');
        }

        $this->setPaymentInfoParameter($paymentInfo, 'BillingAddressCity', $invoice_address->city);
        $this->setPaymentInfoParameter($paymentInfo, 'BillingAddressCountry', $invoice_country->iso_code);
        $this->setPaymentInfoParameter($paymentInfo, 'BillingAddressLine1', $invoice_address->address1);
        $this->setPaymentInfoParameter($paymentInfo, 'BillingAddressLine2', $invoice_address->address2);
        $this->setPaymentInfoParameter($paymentInfo, 'BillingAddressPostalCode', $invoice_address->postcode);
        $this->setPaymentInfoParameter($paymentInfo, 'BillingAddressState', $invoice_state->iso_code);

        $this->setPaymentInfoParameter($paymentInfo, 'ShippingAddressCity', $delivery_address->city);
        $this->setPaymentInfoParameter($paymentInfo, 'ShippingAddressCountry', $delivery_country->iso_code);
        $this->setPaymentInfoParameter($paymentInfo, 'ShippingAddressLine1', $delivery_address->address1);
        $this->setPaymentInfoParameter($paymentInfo, 'ShippingAddressLine2', $delivery_address->address2);
        $this->setPaymentInfoParameter($paymentInfo, 'ShippingAddressPostalCode', $delivery_address->postcode);
        $this->setPaymentInfoParameter($paymentInfo, 'ShippingAddressState', $delivery_state->iso_code);

        $this->setPaymentInfoParameter($paymentInfo, 'Name', $customer->firstname . ' ' . $customer->lastname);
        $this->setPaymentInfoParameter($paymentInfo, 'Email', $customer->email);
        $this->setPaymentInfoParameter($paymentInfo, 'PhoneHome',  [null, $invoice_address->phone]);
        $this->setPaymentInfoParameter($paymentInfo, 'PhoneMobile', [null, $invoice_address->phone_mobile]);
        $this->setPaymentInfoParameter($paymentInfo, 'DeliveryEmail', $customer->email);

        $paymentWindow->setInfo($paymentInfo);

        // Enable testmode
        if(Configuration::get(self::SETTING_ONPAY_TESTMODE)) {
            $paymentWindow->setTestMode(1);
        } else {
            $paymentWindow->setTestMode(0);
        }

        return $paymentWindow;
    }

    /**
     * Method used for setting a payment info parameter. The value is attempted set, if this fails we'll ignore the value and do nothing.
     * $value can be a single value or an array of values passed on as arguments.
     * Validation of value happens directly in the SDK.
     *
     * @param $paymentInfo
     * @param $parameter
     * @param $value
     */
    private function setPaymentInfoParameter($paymentInfo, $parameter, $value) {
        if ($paymentInfo instanceof \OnPay\API\PaymentWindow\PaymentInfo) {
            $method = 'set'.$parameter;
            if (method_exists($paymentInfo, $method)) {
                try {
                    if (is_array($value)) {
                        call_user_func_array([$paymentInfo, $method], $value);
                    } else {
                        call_user_func([$paymentInfo, $method], $value);
                    }
                } catch (\OnPay\API\Exception\InvalidFormatException $e) {
                    // No need to do anything. If the value fails, we'll simply ignore the value.
                }
            }
        }
    }

    private function renderPaymentWindowForm(\OnPay\API\PaymentWindow $paymentWindow) {
        $this->smarty->assign(array(
            'form_action' => $paymentWindow->getActionUrl(),
            'form_fields' => $paymentWindow->getFormFields()
        ));
        return $this->display(__FILE__, 'views/templates/front/payment.tpl');
    }

    private function renderMethodLogos($logos = []) {
        $this->smarty->assign(array(
            'logos' => $logos,
        ));
        return $this->display(__FILE__, 'views/templates/front/logos.tpl');
    }
    private function renderReleaseInfo() {
        $releaseInfo = $this->getLatestModuleRelease();
        if (version_compare($this->version, $releaseInfo->getLatestVersion(), '<')) {
            $this->smarty->assign(array(
                'release' => $releaseInfo,
                'this_path' => $this->_path,
            ));
            return $this->display(__FILE__, 'views/admin/release.tpl');
        }
        return '';
    }

    /**
     * Renders form for administration page
     *
     * @return mixed
     * @throws \OnPay\API\Exception\ApiException
     * @throws \OnPay\API\Exception\ConnectionException
     */
    private function renderAdministrationForm() {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('OnPay settings'),
                    'icon' => 'icon-envelope',
                    'type' => 'legend',
                    'name' => 'ONPAY_SETTINGS',
                ),
                'input' => array(
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Payment methods'),
                        'name' => 'ONPAY_EXTRA_PAYMENTS',
                        'required' => false,
                        'values'=>[
                            'query'=> [
                                [
                                    'id' => 'CARD',
                                    'name' => $this->l('Card'),
                                    'val' => true
                                ],
                                [
                                    'id' => 'MOBILEPAY',
                                    'name' => $this->l('MobilePay'),
                                    'val' => true
                                ],
                                [
                                    'id' => 'VIPPS',
                                    'name' => $this->l('Vipps'),
                                    'val' => true
                                ],
                                [
                                    'id' => 'SWISH',
                                    'name' => $this->l('Swish'),
                                    'val' => true
                                ],
                                [
                                    'id' => 'VIABILL',
                                    'name' => $this->l('ViaBill'),
                                    'val' => true
                                ],
                                [
                                    'id' => 'ANYDAY_SPLIT',
                                    'name' => $this->l('Anyday'),
                                    'val' => true
                                ],
                                [
                                    'id' => 'KLARNA',
                                    'name' => $this->l('Klarna'),
                                    'val' => true
                                ],
                                [
                                    'id' => 'PAYPAL',
                                    'name' => $this->l('PayPal'),
                                    'val' => true
                                ],
                                [
                                    'id' => 'APPLE_PAY',
                                    'name' => $this->l('Apple Pay'),
                                    'val' => true
                                ],
                                [
                                    'id' => 'GOOGLE_PAY',
                                    'name' => $this->l('Google Pay'),
                                    'val' => true
                                ]
                            ],
                            'id'=>'id',
                            'name'=>'name'
                        ]
                    ),

                    array(
                        'type' => 'legend',
                        'label' => '<br /><h3>' . $this->l('Payment window') . '</h3>',
                        'name' => 'ONPAY_PAYMENTWINDOW_SETTINGS',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Test Mode'),
                        'name' => self::SETTING_ONPAY_TESTMODE,
                        'required' => false,
                        'values'=>[
                            array(
                                'id' => 'ENABLED',
                                'value' => '1',
                                'label' => $this->l('On')
                            ),
                            array(
                                'id' => 'ENABLED',
                                'value' => false,
                                'label' => $this->l('Off')
                            )
                        ]
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Payment window design'),
                        'name' => self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN,
                        'options' => array(
                            'query' => $this->getPaymentWindowDesignOptions(),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Payment window language'),
                        'name' => self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE,
                        'options' => [
                            'query'=> $this->getPaymentWindowLanguageOptions(),
                            'id' => 'id_option',
                            'name' => 'name',
                        ]
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Automatic payment window language'),
                        'desc' => $this->l('Overrides language chosen above, and instead determines payment window language based on frontoffice language'),
                        'name' => self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO,
                        'required' => false,
                        'values'=>[
                            array(
                                'id' => 'ENABLED',
                                'value' => '1',
                            ),
                            array(
                                'id' => 'DISABLED',
                                'value' => false
                            )
                        ]
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Card logos'),
                        'desc' => $this->l('Card logos shown for the Card payment method'),
                        'name' => self::SETTING_ONPAY_CARDLOGOS,
                        'values' => array(
                            'query' => $this->getCardLogoOptions(),
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'expand' => array(
                            array('print_total' => count($this->getCardLogoOptions())),
                            'default' => 'show',
                            'show' => array('text' => $this->l('Show'), 'icon' => 'plus-sign-alt'),
                            'hide' => array('text' => $this->l('Hide'), 'icon' => 'minus-sign-alt')
                        ),
                    ),

                    array(
                        'type' => 'legend',
                        'label' => '<br /><h3>' . $this->l('Backoffice settings') . '</h3>',
                        'name' => 'ONPAY_BACKOFFICE_SETTINGS',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Automatic capture'),
                        'desc' => $this->l('Automatically capture remaining amounts on transactions, when orders are marked with status chosen below.'),
                        'name' => self::SETTING_ONPAY_AUTOCAPTURE,
                        'required' => false,
                        'values'=>[
                            array(
                                'id' => 'ENABLED',
                                'value' => '1',
                                'label' => $this->l('On')
                            ),
                            array(
                                'id' => 'ENABLED',
                                'value' => false,
                                'label' => $this->l('Off')
                            )
                        ]
                    ),
                    array(
                        'type' => 'select',
                        'lang' => true,
                        'label' => $this->l('Automatic capture status'),
                        'desc' => $this->l('Status that triggers automatic capture of transaction, if enabled above.'),
                        'name' => self::SETTING_ONPAY_AUTOCAPTURE_STATUS,
                        'options' => [
                            'query'=> $this->getOrderStatuses(),
                            'id' => 'id_option',
                            'name' => 'name',
                        ]
                    ),

                    array(
                        'type' => 'legend',
                        'label' => '<br /><h3>' . $this->l('Gateway information') . '</h3>',
                        'name' => 'ONPAY_GATEWAY_INFO',
                    ),
                    array(
                        'type' => 'text',
                        'readonly' => true,
                        'class' => 'fixed-width-xl',
                        'label' => $this->l('Gateway ID'),
                        'name' => self::SETTING_ONPAY_GATEWAY_ID,
                    ),
                    array(
                        'type' => 'text',
                        'readonly' => true,
                        'class' => 'fixed-width-xl',
                        'label' => $this->l('Window secret'),
                        'name' => self::SETTING_ONPAY_SECRET,
                    ),
                ),
                'submit' => array(
                    'type' => 'button',
                    'title' => $this->l('Save'),
                    'name' => 'ONPAY_PSETTINGS_SAVE',
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Handles posts to administration page
     */
    private function _postProcess() {
        if (Tools::isSubmit('btnSubmit'))
        {
            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_ANYDAY)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_ANYDAY, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_ANYDAY, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIPPS)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIPPS, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIPPS, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_SWISH)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_SWISH, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_SWISH, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_KLARNA)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_KLARNA, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_KLARNA, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_PAYPAL)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_PAYPAL, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_PAYPAL, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_TESTMODE)) {
                Configuration::updateValue(self::SETTING_ONPAY_TESTMODE, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_TESTMODE, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN) === 'ONPAY_DEFAULT_WINDOW') {
                Configuration::updateValue(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN, false);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN, Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN));
            }

            if(Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE) === 'ONPAY_PAYMENTWINDOW_LANGUAGE') {
                Configuration::updateValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE, false);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE, Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE));
            }

            if(Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO) === 'ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO') {
                Configuration::updateValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO, false);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO, Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO));
            }

            $cardLogos = [];
            foreach($this->getCardLogoOptions() as $cardLogo) {
                if (Tools::getValue(self::SETTING_ONPAY_CARDLOGOS . '_' . $cardLogo['id_option'])) {
                    $cardLogos[] = $cardLogo['id_option'];
                }
            }
            Configuration::updateValue(self::SETTING_ONPAY_CARDLOGOS, json_encode($cardLogos));

            if(Tools::getValue(self::SETTING_ONPAY_AUTOCAPTURE)) {
                Configuration::updateValue(self::SETTING_ONPAY_AUTOCAPTURE, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_AUTOCAPTURE, false);
            }

            if(Tools::getValue(self::SETTING_ONPAY_AUTOCAPTURE_STATUS) === 'ONPAY_AUTOCAPTURE_STATUS') {
                Configuration::updateValue(self::SETTING_ONPAY_AUTOCAPTURE_STATUS, false);
            } else {
                $value = Tools::getValue(self::SETTING_ONPAY_AUTOCAPTURE_STATUS);
                if ($value === '0') {
                    Configuration::updateValue(self::SETTING_ONPAY_AUTOCAPTURE_STATUS, null);
                } else {
                    Configuration::updateValue(self::SETTING_ONPAY_AUTOCAPTURE_STATUS, $value);
                }
            }
        }
        $this->htmlContent .= $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * Returns a list of config field values
     *
     * @return array
     */
    private function getConfigFieldsValues() {
        $values = array(
            self::SETTING_ONPAY_GATEWAY_ID => Tools::getValue(self::SETTING_ONPAY_GATEWAY_ID, Configuration::get(self::SETTING_ONPAY_GATEWAY_ID)),
            self::SETTING_ONPAY_SECRET => Tools::getValue(self::SETTING_ONPAY_SECRET, Configuration::get(self::SETTING_ONPAY_SECRET)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_ANYDAY => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_ANYDAY, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_ANYDAY)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_VIPPS => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIPPS, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIPPS)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_SWISH => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_SWISH, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_SWISH)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_KLARNA => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_KLARNA, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_KLARNA)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_PAYPAL => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_PAYPAL, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_PAYPAL)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_APPLE_PAY)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_GOOGLE_PAY)),
            self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN => Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN, Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN)),
            self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE => Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE, Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE)),
            self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO => Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO, Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE_AUTO)),
            self::SETTING_ONPAY_TESTMODE => Tools::getValue(self::SETTING_ONPAY_TESTMODE, Configuration::get(self::SETTING_ONPAY_TESTMODE)),
            self::SETTING_ONPAY_AUTOCAPTURE => Tools::getValue(self::SETTING_ONPAY_AUTOCAPTURE, Configuration::get(self::SETTING_ONPAY_AUTOCAPTURE)),
            self::SETTING_ONPAY_AUTOCAPTURE_STATUS => Tools::getValue(self::SETTING_ONPAY_AUTOCAPTURE_STATUS, Configuration::get(self::SETTING_ONPAY_AUTOCAPTURE_STATUS)),
        );

        foreach (json_decode(Configuration::get(self::SETTING_ONPAY_CARDLOGOS), true) as $cardLogo) {
            $values[self::SETTING_ONPAY_CARDLOGOS . '_' . $cardLogo] = 'on';
        }

        return $values;
    }

    /**
     * Whether cart ID is in locked carts table
     */
    public function isCartLocked($cartId) {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(self::SETTING_ONPAY_LOCKEDCART_TABLE, 'lc');
        $sql->where('lc.id_cart = ' . (int)$cartId);
        $sql->limit('1');
        $rows = Db::getInstance()->executeS($sql);
        return count($rows) > 0;
    }

    /**
     * Adds cart ID to locked carts table
     */
    public function lockCart($cartId) {
        $db = Db::getInstance();
        $db->insert(self::SETTING_ONPAY_LOCKEDCART_TABLE, [
            'id_cart' => (int)$cartId,
        ]);
    }

    /**
     * Removes cart ID from locked carts table
     */
    public function unlockCart($cartId) {
        $db = Db::getInstance();
        $db->delete(self::SETTING_ONPAY_LOCKEDCART_TABLE, 'id_cart = ' . (int)$cartId);
    }

    /**
     * Returns a prepared list of payment window design options.
     *
     * @return array
     * @throws \OnPay\API\Exception\ApiException
     * @throws \OnPay\API\Exception\ConnectionException
     */
    private function getPaymentWindowDesignOptions() {
        try {
            $this->getOnpayClient();
        } catch (InvalidArgumentException $exception) {
            return array();
        }

        if(!$this->getOnpayClient()->isAuthorized()) {
            return [];
        }

        $designs = $this->getOnpayClient()->gateway()->getPaymentWindowDesigns()->paymentWindowDesigns;
        $options = array_map(function(\OnPay\API\Gateway\SimplePaymentWindowDesign $design) {
            return [
                'name' => $design->name,
                'id' => $design->name,
            ];
        }, $designs);

        array_unshift($options, ['name' => $this->l('Default'), 'id' => 'ONPAY_DEFAULT_WINDOW']);
        $selectOptions = [];
        foreach ($options as $option) {
            $selectOptions[] = [
                'id_option' => $option['id'],
                'name' => $option['name']
            ];
        }

        return $selectOptions;
    }

    /**
     * Returns a prepared list of available payment window languages
     *
     * @return array
     */
    private function getPaymentWindowLanguageOptions() {
        return [
            [
                'name' => $this->l('English'),
                'id_option' => 'en',
            ],
            [
                'name' => $this->l('Danish'),
                'id_option' => 'da',
            ],
            [
                'name' => $this->l('Dutch'),
                'id_option' => 'nl',
            ],
            [
                'name' => $this->l('Faroese'),
                'id_option' => 'fo',
            ],
            [
                'name' => $this->l('French'),
                'id_option' => 'fr',
            ],
            [
                'name' => $this->l('German'),
                'id_option' => 'de',
            ],
            [
                'name' => $this->l('Italian'),
                'id_option' => 'it',
            ],
            [
                'name' => $this->l('Norwegian'),
                'id_option' => 'no',
            ],
            [
                'name' => $this->l('Polish'),
                'id_option' => 'pl',
            ],
            [
                'name' => $this->l('Spanish'),
                'id_option' => 'es',
            ],
            [
                'name' => $this->l('Swedish'),
                'id_option' => 'sv',
            ],
        ];
    }

    // Returns valid OnPay payment window language by Prestashop language iso
    private function getPaymentWindowLanguageByPSLanguage($languageIso) {
        $languageRelations = [
            'en' => 'en',
            'es' => 'es',
            'da' => 'da',
            'de' => 'de',
            'fo' => 'fo',
            'fr' => 'fr',
            'it' => 'it',
            'nl' => 'nl',
            'no' => 'no',
            'pl' => 'pl',
            'sv' => 'sv',

            'us' => 'en', // Incase of mixup
            'nb' => 'no', // Incase use of archaic language definition
            'nn' => 'no', // Incase use of archaic language definition
            'dk' => 'da', // Incase of mixup
            'kl' => 'da', // Incase of Kalaallisut/Greenlandic
        ];
        if (array_key_exists($languageIso, $languageRelations)) {
            return $languageRelations[$languageIso];
        }
        return 'en';
    }

    /**
     * Returns a list of all available statuses
     * 
     * @return array
     */
    private function getOrderStatuses() {
        $orderState = new OrderState();
        $statuses = [
            [
                'name' => '-',
                'id_option' => 0,
            ]
        ];
        foreach($orderState->getOrderStates($this->context->language->id) as $status) {
            $statuses[] = [
                'name' => $status['name'],
                'id_option' => $status['id_order_state'],
            ];
        }
        return $statuses;
    }

    /**
     * Returns a prepared list of card logos
     *
     * @return array
     */
    private function getCardLogoOptions() {
        return [
            [
                'name' => $this->l('American Express/AMEX'),
                'id_option' => 'american-express',
            ],
            [
                'name' => $this->l('Dankort'),
                'id_option' => 'dankort',
            ],
            [
                'name' => $this->l('Diners'),
                'id_option' => 'diners',
            ],
            [
                'name' => $this->l('Discover'),
                'id_option' => 'discover',
            ],
            [
                'name' => $this->l('Forbrugsforeningen'),
                'id_option' => 'forbrugsforeningen',
            ],
            [
                'name' => $this->l('JCB'),
                'id_option' => 'jcb',
            ],
            [
                'name' => $this->l('Mastercard/Maestro'),
                'id_option' => 'mastercard',
            ],
            [
                'name' => $this->l('UnionPay'),
                'id_option' => 'unionpay',
            ],
            [
                'name' => $this->l('Visa/VPay/Visa Electron '),
                'id_option' => 'visa',
            ],
        ];
    }

    /**
     * Generates URL for current page with params
     * @param $params
     * @return string
     */
    private function generateUrl($params) {
        if (Configuration::get('PS_SSL_ENABLED')) {
            $currentPage = 'https://';
        } else {
            $currentPage = 'http://';
        }
        $currentPage .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $baseUrl = explode('?', $currentPage, 2);
        $baseUrl = array_shift($baseUrl);
        $fullUrl = $baseUrl . '?' . http_build_query($params);
        return $fullUrl;
    }

    private function getLatestModuleRelease() {
        $release = new Release();

        // If get release info from config key
        $config = Configuration::get(self::SETTING_ONPAY_RELEASE_INFO);
        if (false !== $config) {
            $release = Release::fromString($config);
            // If release info is less than a day old, well simply return this info.
            if (time() - $release->getLastCheck() < 86400) {
                return $release;
            }
        }
        
        // Fetch release info from OnPay
        $releaseUri = 'https://api.onpay.io/plugin/release/prestashop';
        $response = $this->httpGet($releaseUri);

        // Set check timestamp
        $release->setLastCheck(time());

        // Examine if a response is present
        if (null !== $response) {
            $releaseResponse = json_decode($response, true);
            if (null !== $releaseResponse) {
                // If tag_name is present, set value as latest version
                if (array_key_exists('tag_name', $releaseResponse)) {
                    $release->setLatestVersion($releaseResponse['tag_name']);
                }
                // If asset with the name onpay.zip is present, set this as the latest download
                if (array_key_exists('assets', $releaseResponse) && is_array($releaseResponse['assets'])) {
                    foreach ($releaseResponse['assets'] as $asset) {
                        if (array_key_exists('name', $asset) && 'onpay.zip' === $asset['name'] && array_key_exists('browser_download_url', $asset)) {
                            $release->setLatestDownload($asset['browser_download_url']);
                        }
                    }
                }
            }
        }

        // Set contents of release to config key
        Configuration::updateValue(self::SETTING_ONPAY_RELEASE_INFO, $release->toString());

        return $release;
    }

    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, self::ONPAY_PLATFORM_STRING);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
