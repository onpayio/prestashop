<?php
/**
 * @author OnPay.io
 * @copyright 2024 OnPay.io
 * @license MIT
 *
 * MIT License
 *
 * Copyright (c) 2024 OnPay.io
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
if (!defined('_PS_VERSION_')) {
    exit;
}

use OnPay\API\PaymentWindow;

class OnpayCallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $onpay = Module::getInstanceByName('onpay');
        $paymentWindow = new PaymentWindow();
        $paymentWindow->setSecret(Configuration::get(Onpay::SETTING_ONPAY_SECRET));

        $onpayUuid = Tools::getValue('onpay_uuid');
        $onpayReference = Tools::getValue('onpay_reference');
        $onpayAmount = Tools::getValue('onpay_amount');
        $onpayCurrency = Tools::getValue('onpay_currency');
        $onpayMethod = Tools::getValue('onpay_method');
        $onpayCardType = Tools::getValue('onpay_cardtype');

        // Validate query parameters and check that onpay_number is present
        if (!$paymentWindow->validatePayment(Tools::getAllValues()) || false === $onpayUuid) {
            $this->jsonResponse('Invalid values', true, 400);
        }

        /** @var ContextCore $context */
        $context = Context::getContext();

        /** @var CartCore $cart */
        $cart = new Cart($onpayReference); // Since the reference initially sent to onpay is the cart ID, we can use the reference the other way around to get the cart
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->jsonResponse('Invalid cart', true, 500);
        }
        $context->cart = $cart;

        /** @var CustomerCore $customer */
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->jsonResponse('Invalid customer', true, 500);
        }
        $context->customer = $customer;

        // Set currency from callback
        $currencyId = Currency::getIdByNumericIsoCode($onpayCurrency);
        $currency = new Currency($currencyId);
        if (!Validate::isLoadedObject($currency)) {
            $this->jsonResponse('Invalid currency', true, 500);
        }
        $this->context->currency = $currency; // Set the context currency to cart currency

        // Check if module is enabled
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == $this->module->name) {
                $authorized = true;
            }
        }

        if (!$authorized) {
            $this->jsonResponse('Payment module unavailable', true, 403);
        }

        // Get orderId
        $orderId = OrderCore::getOrderByCartId($cart->id);

        // Check that order is not yet created, or in process of creation.
        if ($orderId === false && !$onpay->isCartLocked($cart->id)) {
            // Lock cart while creating order
            $onpay->lockCart($cart->id);

            // Extract paid amount in major units
            $currencyHelper = new CurrencyHelper();
            $amountPaid = $currencyHelper->minorToMajor((int) $onpayAmount, $currency->iso_code_num);

            $method = 'OnPay';
            if ('card' === $onpayMethod && false !== $onpayCardType) {
                // If card type is provided, append to method name
                $method .= ' - ' . ucfirst($onpayCardType);
            }

            $this->module->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                $amountPaid,
                $method,
                null,
                [
                    'transaction_id' => $onpayUuid,
                    'card_brand' => Tools::getValue('onpay_cardtype'),
                ],
                $currency->id,
                false,
                $customer->secure_key
            );

            // Unlock cart again
            $onpay->unlockCart($cart->id);
        }

        $this->jsonResponse('Order validated');
    }

    private function jsonResponse($message, $error = false, $responseCode = 200)
    {
        header('Content-Type: application/json', true, $responseCode);
        $response = [];
        if (!$error) {
            $response = ['success' => $message, 'error' => false];
        } else {
            $response = ['error' => $message];
        }
        echo json_encode($response);
        exit;
    }
}
