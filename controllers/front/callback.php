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

class OnpayCallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $onpay = Module::getInstanceByName('onpay');
        $paymentWindow = new \OnPay\API\PaymentWindow();
        $paymentWindow->setSecret(Configuration::get(Onpay::SETTING_ONPAY_SECRET));

        /** @var ContextCore $context */
        $context = Context::getContext();

        if (!$paymentWindow->validatePayment(Tools::getAllValues())) {
            $this->jsonResponse('Invalid values', true, 400);
        }

        /** @var CartCore $cart */
        $cart = new Cart(Tools::getValue('onpay_reference')); // Since the reference initially sent to onpay is the cart ID, we can use the reference the other way around to get the cart
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

        // Check if order creation is already happening
        if ($orderId === false && $onpay->isCartLocked($cart->id)) {
            // Wait for order creation to end for 1s, and try to get order again.
            sleep(1);
            $orderId = OrderCore::getOrderByCartId($cart->id);
            if ($orderId === false) {
                // If still no order created, tell client to try again later
                $this->jsonResponse('Cart locked, try again later', true, 400);
            }
        }

        // Check that order is not yet created, or in process of creation.
        if ($orderId === false && !$onpay->isCartLocked($cart->id)) {
            // Lock cart while creating order
            $onpay->lockCart($cart->id);

            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
            $currency = $this->context->currency;

            $this->module->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                $total,
                'OnPay',
                null,
                [
                    'transaction_id' => Tools::getValue('onpay_uuid'),
                    'card_brand' => Tools::getValue('onpay_cardtype')
                ],
                (int)$currency->id,
                false,
                $customer->secure_key
            );

            // Unlock cart again
            $onpay->unlockCart($cart->id);
        } else {
            // Order is already created, set status to payment complete
            $order = new Order($orderId);
            if ($order->current_state !== Configuration::get('PS_OS_PAYMENT')) {
                $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));

                // For some reason Prestashop 'forgets' the transaction id given on order validation, so we'll set again.
                $payments = OrderPaymentCore::getByOrderId($orderId);
                if (!empty($payments)) {
                    $payment = $payments[0];
                    $payment->transaction_id = Tools::getValue('onpay_uuid');
                    $payment->update();
                }
            }
        }

        $this->jsonResponse('Order validated');
    }

    private function jsonResponse($message, $error = false, $responseCode = 200) {
        header('Content-Type: application/json', true, $responseCode);
        $response = [];
        if (!$error) {
            $response = ['success' => $message, 'error' => false];
        } else {
            $response = ['error' => $message];
        }
        die(Tools::jsonEncode($response));
    }
}
