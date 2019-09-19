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
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

//        $this->context->smarty->assign([
//            'HOOK_LEFT_COLUMN' => $this->hookDisplayLeftColumn(),
//        ]);

        // If we're not redirecting the customer in postProcess on successful payment, we'll default to the declined template.
//        $this->setTemplate('payment_decline.tpl');
    }

    public function postProcess()
    {
        $paymentWindow = new \OnPay\API\PaymentWindow();
        $paymentWindow->setSecret(Configuration::get('ONPAY_SECRET'));

        // Validate that submitted HMAC matches submitted values
        if ($paymentWindow->validatePayment(Tools::getAllValues())) {
            // The data submitted was validated sucessfully
            // Now we'll determine if we're dealing with an accepted or declined transaction.
            if (false !== Tools::getValue('accept')) {
                // Transaction was accepted
                $cart = new CartCore(Tools::getValue('onpay_reference'));
                /** @var CustomerCore $customer */
                $customer = new Customer($cart->id_customer);
                Tools::redirect($this->context->link->getPageLink('order-confirmation', Configuration::get('PS_SSL_ENABLED'), null, [
                    'id_cart' => $cart->id,
                    'id_module' => (int)$this->module->id,
                    'key' => $customer->secure_key
                ]));
//                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . (int)$this->module->id . '&key=' . $customer->secure_key . '&status=' . $status);
            }
        }
    }

    public function hookDisplayLeftColumn() {
        // We don't want any left column hooks showing on this page.
        return false;
    }
}
