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

{if $card}
<p class="payment_module onpay">
    <a href="javascript:$('#onpayForm').submit();" title="{l s='Pay by credit card' mod='onpay'}">
        <img src="{$this_path_bw}/views/img/dankort.svg" width="86" height="49"/>
        <img src="{$this_path_bw}/views/img/mastercard.svg" width="86" height="49"/>
        <img src="{$this_path_bw}/views/img/visa.svg" width="86" height="49"/>
        {l s='Pay by credit card' mod='onpay'}
    </a>
</p>
    <form method="post" action="{$actionUrl}" id="onpayForm">
        {foreach from=$card_fields key="key" item="item" }
            <input type="hidden" name="{$key}" value="{$item}">
        {/foreach}
    </form>

{/if}

{if $mobilepay }
<p class="payment_module onpay">
    <a href="javascript:$('#onpayMobilepayForm').submit();" title="{l s='Pay by Mobilepay' mod='onpay'}">
        <img src="{$this_path_bw}/views/img/mobilepay.svg" width="86" height="49"/>
        {l s='Pay by Mobilepay' mod='onpay'}
    </a>
</p>
    <form method="post" action="{$actionUrl}" id="onpayMobilepayForm">
        {foreach from=$mobilepay_fields key="key" item="item" }
            <input type="hidden" name="{$key}" value="{$item}">
        {/foreach}
    </form>
{/if}

{if $viabill }
<p class="payment_module onpay">
    <a href="javascript:$('#onpayViabillForm').submit();" title="{l s='Pay by Viabill' mod='onpay'}">
        <img src="{$this_path_bw}/views/img/viabill.svg" width="86" height="49"/>
        {l s='Pay by Viabill' mod='onpay'}
    </a>
    <form method="post" action="{$actionUrl}" id="onpayViabillForm">
        {foreach from=$mobilepay_fields key="key" item="item" }
            <input type="hidden" name="{$key}" value="{$item}">
        {/foreach}
    </form>
</p>
{/if}
