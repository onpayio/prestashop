{*
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
*
*}

<form method="post" action="{$form_action}" id="onpayForm">
    <input type="hidden" name="onpay_gatewayid" value="{$form_fields['onpay_gatewayid']}">
    <input type="hidden" name="onpay_currency" value="{$form_fields['onpay_currency']}">
    <input type="hidden" name="onpay_amount" value="{$form_fields['onpay_amount']}">
    <input type="hidden" name="onpay_reference" value="{$form_fields['onpay_reference']}">
    <input type="hidden" name="onpay_accepturl" value="{$form_fields['onpay_accepturl']}">
    <input type="hidden" name="onpay_type" value="{$form_fields['onpay_type']}">
    <input type="hidden" name="onpay_method" value="{$form_fields['onpay_method']}">
    <input type="hidden" name="onpay_declineurl" value="{$form_fields['onpay_declineurl']}">
    <input type="hidden" name="onpay_callbackurl" value="{$form_fields['onpay_callbackurl']}">
    <input type="hidden" name="onpay_testmode" value="{$form_fields['onpay_testmode']}">

    {if array_key_exists('onpay_design', $form_fields)}
        <input type="hidden" name="onpay_design" value="{$form_fields['onpay_design']}">
    {/if}

    {if array_key_exists('onpay_language', $form_fields)}
        <input type="hidden" name="onpay_language" value="{$form_fields['onpay_language']}">
    {/if}

    <input type="hidden" name="onpay_hmac_sha1" value="{$form_fields['onpay_hmac_sha1']}">
</form>
