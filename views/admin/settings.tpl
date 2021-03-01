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

{if $error !== null}
    {$error|unescape: 'html'}
{/if}
{if not $isAuthorized}
    <div class="panel" id="fieldset_0">
        <div class="panel-heading">
            <i class="icon-envelope"></i> {l s='Onpay settings' mod='onpay'}
        </div>
       <a href="{$authorizationUrl}" class="btn btn-default">{l s='Login with Onpay' mod='onpay'}</a>
    </div>
    {else}
    {$form|unescape: 'html' }
    <a id="onpayRefresh" data-link="{$smarty.server.REQUEST_URI}&refresh=true" class="btn btn-default">{l s='Refresh' mod='onpay'}</a>
    <a id="onpayLogout" data-link="{$smarty.server.REQUEST_URI}&detach=true" class="btn btn-default">{l s='Log out from OnPay' mod='onpay'}</a>
    <br/>
    <br/>
{/if}
