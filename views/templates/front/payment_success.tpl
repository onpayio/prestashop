{*
* @author OnPay.io
* @copyright 2024 OnPay.io
* @license MIT
* 
* MIT License
*
* Copyright (c) 2021 OnPay.io
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
*}

{extends file='page.tpl'}
{block name='content'}
    {capture name=path}{l s='Payment success' mod='onpay'}{/capture}
    <section id="content-hook_order_confirmation" class="card">
        <div class="card-block">
            <div class="row">
                <div class="col-md-12">

                    {block name='order_confirmation_header'}
                      <h3 class="h1 card-title">
                        <i class="material-icons rtl-no-flip done">&#xE876;</i>{l s='Your order is confirmed' d='Shop.Theme.Checkout'}
                      </h3>
                    {/block}

                    <p>
                        <b>{l s='The payment was successful, and is awaiting processing.' mod='onpay'}</b><br/><br/>
                        {l s='An email has been sent to your mail address %email%.' d='Shop.Theme.Checkout' sprintf=['%email%' => $customer.email]}<br/><br/>
                        
                        {if $order.details.invoice_url}
                            {* [1][/1] is for a HTML tag. *}
                            {l
                                s='You can also [1]download your invoice[/1]'
                                d='Shop.Theme.Checkout'
                                sprintf=[
                                    '[1]' => "<a href='{$order.details.invoice_url}'>",
                                    '[/1]' => "</a>"
                                ]
                            }
                        {/if}
                    </p>

                    {block name='hook_order_confirmation'}
                        {$HOOK_ORDER_CONFIRMATION nofilter}
                    {/block}

                    <p class="cart_navigation clearfix">
                        <a class="button-exclusive btn btn-primary btn-lg" href="{$link->getPageLink('history', true)|escape:'html':'UTF-8'}" title="{l s='Go to orders' mod='onpay'}">
                            {l s='Go to orders' mod='onpay'}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </section>
{/block}
