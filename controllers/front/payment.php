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

/**
 * @since 1.5.0
 */
class OnpayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();
        // If we're not redirecting the customer in postProcess on successful payment, we'll default to the declined template.
        $this->setTemplate('module:onpay/views/templates/front/payment_decline.tpl');
    }

    public function postProcess() {
        if (!Tools::getValue('onpay_hmac_sha1')) {
            return;
        }

        $onpay = Module::getInstanceByName('onpay');
        $paymentWindow = new \OnPay\API\PaymentWindow();
        $paymentWindow->setSecret(Configuration::get('ONPAY_SECRET'));

        // Validate that submitted HMAC matches submitted values
        if ($paymentWindow->validatePayment(Tools::getAllValues())) {
            // The data submitted was validated sucessfully
            // Now we'll determine if we're dealing with an accepted or declined transaction.
            if (false !== Tools::getValue('accept')) {
                // Transaction was accepted
                $cart = new Cart(Tools::getValue('onpay_reference'));

                // Get order
                $order = OrderCore::getByCartId($cart->id);

                /** @var CustomerCore $customer */
                $customer = new Customer($cart->id_customer);

                // Check that order is not yet created, or in process of creation.
                // If order is in process of creation, we simply don't care about setting a state.
                if (null === $order && !$onpay->isCartLocked($cart->id)) {
                    // Lock cart while creating order
                    $onpay->lockCart($cart->id);

                    $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
                    $currency = $this->context->currency;

                    $this->module->validateOrder(
                        $cart->id,
                        Configuration::get('ONPAY_OS_AWAIT'),
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
                }

                // Order is created, redirect to confirmation page
                Tools::redirect($this->context->link->getPageLink('order-confirmation', Configuration::get('PS_SSL_ENABLED'), null, [
                    'id_cart' => $cart->id,
                    'id_module' => (int)$this->module->id,
                    'key' => $customer->secure_key
                ]));
            }
        }
    }
}
