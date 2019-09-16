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

{capture name=path}{l s='Payment failure' mod='onpay'}{/capture}
<h1 class="page-heading">{l s='The payment failed' mod='onpay'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<p class="alert alert-warning">{l s='The payment of the order failed, please try again.' mod='onpay'}</p>

<p class="cart_navigation clearfix">
    <a class="button-exclusive btn btn-default btn-lg" href="{$link->getPageLink('order&step=3', true)|escape:'html':'UTF-8'}" title="{l s='Go back to payment method' mod='onpay'}">
        <i class="icon-chevron-left"></i>{l s='Go back to payment method' mod='onpay'}
    </a>
</p>
