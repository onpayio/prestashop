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

        $paymentWindow = new \OnPay\API\PaymentWindow();
        $paymentWindow->setSecret(Configuration::get('ONPAY_SECRET'));

        // Validate that submitted HMAC matches submitted values
        if ($paymentWindow->validatePayment(Tools::getAllValues())) {
            // The data submitted was validated sucessfully
            // Now we'll determine if we're dealing with an accepted or declined transaction.
            if (false !== Tools::getValue('accept')) {
                // Transaction was accepted
                $cart = new CartCore(Tools::getValue('onpay_reference'));
                // Get order
                $order = OrderCore::getByCartId($cart->id);
                /** @var CustomerCore $customer */
                $customer = new Customer($cart->id_customer);

                if (null === $order) {
                    // Order not yet validated, validate it as awaiting.
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
