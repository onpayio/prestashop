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

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/CurrencyHelper.php';
require_once __DIR__ . '/classes/TokenStorage.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class onpay extends PaymentModule {
    const SETTING_ONPAY_GATEWAY_ID = 'ONPAY_GATEWAY_ID';
    const SETTING_ONPAY_SECRET = 'ONPAY_SECRET';
    const SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY = 'ONPAY_EXTRA_PAYMENTS_MOBILEPAY';
    const SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL = 'ONPAY_EXTRA_PAYMENTS_VIABILL';
    const SETTING_ONPAY_EXTRA_PAYMENTS_CARD = 'ONPAY_EXTRA_PAYMENTS_CARD';
    const SETTING_ONPAY_PAYMENTWINDOW_DESIGN = 'ONPAY_PAYMENTWINDOW_DESIGN';
    const SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE = 'ONPAY_PAYMENTWINDOW_LANGUAGE';
    const SETTING_ONPAY_TOKEN = 'ONPAY_TOKEN';
    const SETTING_ONPAY_TESTMODE = 'ONPAY_TESTMODE_ENABLED';

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
        $this->version = '1.0';
        $this->ps_versions_compliancy = array('min' => '1.7.6.1', 'max' => _PS_VERSION_);
        $this->author = 'OnPay.io';
        $this->need_instance = 0;
        $this->controllers = array('payment', 'callback');
        $this->is_eu_compatible = 1;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('OnPay', [], 'Modules.Onpay.Admin');
        $this->description = $this->trans('Use OnPay.io for handling payments', [], 'Modules.Onpay.Admin');
        $this->confirmUninstall = $this->trans('Are you sure about uninstalling the OnPay.io module?', [], 'Modules.Onpay.Admin');
        $this->currencyHelper = new CurrencyHelper();
    }

    public function install() {
        if (
            !parent::install() ||
            !$this->registerHook('paymentReturn') ||
            !$this->registerHook('paymentOptions') ||
            !$this->registerHook('adminOrder')
        ) {
            return false;
        }
        return true;
    }

    public function uninstall() {
        if (
            parent::uninstall() == false ||
            !Configuration::deleteByName($this::SETTING_ONPAY_GATEWAY_ID) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_SECRET) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_EXTRA_PAYMENTS_CARD) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_PAYMENTWINDOW_DESIGN) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_TOKEN) ||
            !Configuration::deleteByName($this::SETTING_ONPAY_TESTMODE)
        ) {
            return false;
        }
        return true;
    }


    /**
     * Administration page
     */
    public function getContent() {
        $this->hookHeader();

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
        if(false !== Tools::getValue('code') && !$onpayApi->isAuthorized()) {
            $onpayApi->finishAuthorize(Tools::getValue('code'));
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
     * Hooks custom CSS to header
     */
    public function hookHeader() {
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
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

            if (null === $this->currencyHelper->fromNumeric($currency->iso_code_num)) {
                // If we can't determine the currency, we wont show the payment method at all.
                return;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD)) {
                $cardOption = new PaymentOption();
                $cardOption->setModuleName($this->name)
                    ->setCallToActionText($this->trans('Pay with credit card', array(), 'Modules.Onpay.Shop'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_CARD, $currency)));
                $payment_options[] = $cardOption;
            }

            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL)) {
                $vbOption = new PaymentOption();
                $vbOption->setModuleName($this->name)
                    ->setCallToActionText($this->trans('Pay through ViaBill', array(), 'Modules.Onpay.Shop'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_VIABILL, $currency)));
                $payment_options[] = $vbOption;
            }

            // Mobilepay is not available in testmode
            if(Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY) && !Configuration::get(self::SETTING_ONPAY_TESTMODE)) {
                $mpoOption = new PaymentOption();
                $mpoOption->setModuleName($this->name)
                    ->setCallToActionText($this->trans('Pay through MobilePay', array(), 'Modules.Onpay.Shop'))
                    ->setForm($this->renderPaymentWindowForm($this->getPaymentWindow($order, \OnPay\API\PaymentWindow::METHOD_MOBILEPAY, $currency)));
                $payment_options[] = $mpoOption;
            }

            return $payment_options;
        }
        return;
    }

    /**
     * Actions on order page
     * @param $params
     * @return mixed
     */
    public function hookAdminOrder($params)
    {
        $this->hookHeader();
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
                $onpayInfo = $onPayAPI->transaction()->getTransaction($payment->transaction_id);
                $amount  = $this->currencyHelper->minorToMajor($onpayInfo->amount, $onpayInfo->currencyCode, ',');
                $chargable = $onpayInfo->amount - $onpayInfo->charged;
                $chargable = $this->currencyHelper->minorToMajor($chargable, $onpayInfo->currencyCode, ',');
                $refunded = $this->currencyHelper->minorToMajor($onpayInfo->refunded, $onpayInfo->currencyCode, ',');
                $charged = $this->currencyHelper->minorToMajor($onpayInfo->charged, $onpayInfo->currencyCode, ',');

                $currencyCode = $onpayInfo->currencyCode;

                array_walk($onpayInfo->history, function(\OnPay\API\Transaction\TransactionHistory $history) use($currencyCode) {
                    $amount = $history->amount;
                    $amount = $this->currencyHelper->minorToMajor($amount, $currencyCode, ',');
                    $history->amount = $amount;
                });

                $refundable = $onpayInfo->charged - $onpayInfo->refunded;
                $refundable = $this->currencyHelper->minorToMajor($refundable, $onpayInfo->currencyCode,',');
                $details[] = [
                    'details' => ['amount' => $amount, 'chargeable' => $chargable, 'refunded' => $refunded, 'charged' => $charged, 'refundable' => $refundable],
                    'payment' => $payment,
                    'onpay' => $onpayInfo,
                ];
            }
        } catch (\OnPay\API\Exception\ApiException $exception) {
            // If there was problems, we'll show the same as someone with an unauthed acc
            $this->smarty->assign(array(
                'paymentdetails' => $details,
                'url' => '',
                'isAuthorized' => false,
                'currencyDetails' => '',
                'this_path' => $this->_path,
            ));
            return $this->display(__FILE__, 'views/admin/order_details.tpl');
        }

        $url = $_SERVER['REQUEST_URI'];
        $this->smarty->assign(array(
            'paymentdetails' => $details,
            'url' => $url,
            'isAuthorized' => $this->getOnpayClient()->isAuthorized(),
            'currencyDetails' => new Currency($payments[0]->id_currency),
            'this_path' => $this->_path,
        ));
        return $this->display(__FILE__, 'views/admin/order_details.tpl');
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
        ]);
        return $onPayAPI;
    }

    /**
     * Generates payment window object for use on the payment page
     * @param $orderTotal
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
        $paymentPage = Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . '/module/' . $this->name . '/payment';
        $paymentWindow->setAcceptUrl($paymentPage . '?accept');
        $paymentWindow->setDeclineUrl($paymentPage);
        $paymentWindow->setType("payment");
        $paymentWindow->setCallbackUrl(Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . '/module/' . $this->name . '/callback');

        if(Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN)) {
            $paymentWindow->setDesign(Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN));
        }

        if(Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE)) {
            $paymentWindow->setLanguage(Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE));
        }

        // Set payment method
        $paymentWindow->setMethod($payment);

        // Enable testmode
        if(Configuration::get(self::SETTING_ONPAY_TESTMODE)) {
            $paymentWindow->setTestMode(1);
        } else {
            $paymentWindow->setTestMode(0);
        }

        return $paymentWindow;
    }

    private function renderPaymentWindowForm(\OnPay\API\PaymentWindow $paymentWindow) {
        $this->smarty->assign(array(
            'form_action' => $paymentWindow->getActionUrl(),
            'form_fields' => $paymentWindow->getFormFields(),
        ));
        return $this->display(__FILE__, 'views/templates/front/payment.tpl');
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
                    'icon' => 'icon-envelope'
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
                                    'id' => 'VIABILL',
                                    'name' => $this->l('ViaBill'),
                                    'val' => true
                                ]
                            ],
                            'id'=>'id',
                            'name'=>'name'
                        ]
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
                        'name' => 'ONPAY_PAYMENTWINDOW_DESIGN',
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
                        'name' => 'ONPAY_PAYMENTWINDOW_LANGUAGE',
                        'options' => [
                            'query'=> $this->getPaymentWindowLanguageOptions(),
                            'id' => 'id_option',
                            'name' => 'name',
                        ]
                    ),
                    array(
                        'type' => 'text',
                        'readonly' => true,
                        'class' => 'fixed-width-xl',
                        'label' => $this->l('Gateway ID'),
                        'name' => 'ONPAY_GATEWAY_ID',
                    ),
                    array(
                        'type' => 'text',
                        'readonly' => true,
                        'class' => 'fixed-width-xl',
                        'label' => $this->l('Window secret'),
                        'name' => 'ONPAY_SECRET',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
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

            if(Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD)) {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD, true);
            } else {
                Configuration::updateValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD, false);
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
        }
        $this->htmlContent .= $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * Returns a list of config field values
     *
     * @return array
     */
    private function getConfigFieldsValues() {
        return array(
            self::SETTING_ONPAY_GATEWAY_ID => Tools::getValue(self::SETTING_ONPAY_GATEWAY_ID, Configuration::get(self::SETTING_ONPAY_GATEWAY_ID)),
            self::SETTING_ONPAY_SECRET => Tools::getValue(self::SETTING_ONPAY_SECRET, Configuration::get(self::SETTING_ONPAY_SECRET)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_MOBILEPAY)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_VIABILL)),
            self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD => Tools::getValue(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD, Configuration::get(self::SETTING_ONPAY_EXTRA_PAYMENTS_CARD)),
            self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN => Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN, Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_DESIGN)),
            self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE => Tools::getValue(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE, Configuration::get(self::SETTING_ONPAY_PAYMENTWINDOW_LANGUAGE)),
            self::SETTING_ONPAY_TESTMODE => Tools::getValue(self::SETTING_ONPAY_TESTMODE, Configuration::get(self::SETTING_ONPAY_TESTMODE)),
        );
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
}
