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

{if $isAuthorized}
    {foreach from=$paymentdetails item=payment }
        <div class="card mt-2" id="view_order_payments_block">
            <div class="card-header">
                <h3 class="card-header-title">
                    <img src="{$this_path}/logo.png" height="18"/> Onpay - {l s='Transaction details' mod='onpay'}
                </h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        {if $payment['onpay']->acquirer eq 'test'}
                            <div class="alert alert-warning" role="alert">
                                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                                {l s='This is a test order' mod='onpay'}
                            </div>
                        {/if}

                        <h2>{l s='Transaction details' mod='onpay'}</h2>
                        <table class="table table-bordered onpayDetail table-condensed">
                            <tbody>
                            <tr>
                                <td><strong>{l s='Status' mod='onpay'}</strong></td>
                                <td>{$payment['onpay']->status}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Card type' mod='onpay'}</strong></td>
                                <td>
                                    {if $payment['onpay']->cardType != null}
                                        {$payment['onpay']->cardType}
                                    {else}
                                        {$payment['onpay']->acquirer}
                                    {/if}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Transaction number' mod='onpay'}</strong></td>
                                <td>{$payment['onpay']->transactionNumber}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Transaction ID' mod='onpay'}</strong></td>
                                <td>{$payment['onpay']->uuid}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='IP' mod='onpay'}</strong></td>
                                <td>{$payment['onpay']->ip}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Amount' mod='onpay'}</strong></td>
                                <td>{$payment['details']['amount']} {$payment['details']['currency']->alpha3}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Charged' mod='onpay'}</strong></td>
                                <td>{$payment['details']['charged']} {$payment['details']['currency']->alpha3}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Refunded' mod='onpay'}</strong></td>
                                <td>{$payment['details']['refunded']} {$payment['details']['currency']->alpha3}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h2>{l s='History' mod='onpay'}</h2>

                        <table class="table table-bordered onpayDetail">
                            <thead>
                            <th>{l s='Date & Time' mod='onpay'}</th>
                            <th>{l s='Action' mod='onpay'}</th>
                            <th>{l s='Amount' mod='onpay'}</th>
                            <th>{l s='User' mod='onpay'}</th>
                            <th>{l s='IP' mod='onpay'}</th>
                            </thead>
                            <tbody>
                            {foreach from=$payment['onpay']->history item=history}
                                <tr>
                                    <td>{$history->dateTime->format('Y-m-d H:i:s')}</td>
                                    <td>{$history->action}</td>
                                    <td>{$history->amount} {$payment['details']['currency']->alpha3}</td>
                                    <td>{$history->author}</td>
                                    <td>{$history->ip}</td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Refund window -->
                <div class="modal fade" id="onpayRefund" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{l s='Refund transaction' mod='onpay'}</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="post" id="onpayRefundTransaction" action="{$url}" name="capture-cancel">
                                    <div class="form-group">
                                        <label>{l s='Amount to refund' mod='onpay'}</label>
                                        <input type="text" class="form-control" name="refund_value"
                                               value="{$payment['details']['refundable']}">
                                        <input type="hidden" class="form-control" name="refund_currency"
                                               value="{$payment['onpay']->currencyCode}">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='onpay'}</button>
                                <a class="btn btn-info onpayActionButton"
                                   href="javascript:$('#onpayRefundTransaction').submit();">
                                    {l s='Refund' mod='onpay'}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Refund window -->

                <!-- Capture window -->
                <div class="modal fade" id="onpayCapture" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{l s='Capture transaction' mod='onpay'}</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="post" id="onpayCaptureTransaction" action="{$url}" name="capture-cancel">
                                    <div class="form-group">
                                        <label>{l s='Amount to capture' mod='onpay'}</label>
                                        <input type="text" class="form-control" name="onpayCapture_value"
                                               value="{$payment['details']['chargeable']}">
                                        <input type="hidden" class="form-control" name="onpayCapture_currency"
                                               value="{$payment['onpay']->currencyCode}">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='onpay'}</button>
                                <a class="btn btn-info onpayActionButton"
                                   href="javascript:$('#onpayCaptureTransaction').submit();">
                                    {l s='Capture' mod='onpay'}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Capture window -->
            </div>

            <div class="card-footer clearfix">
                    <div class="float-right">
                        <div class="form-inline">
                            {if $payment['onpay']->status eq 'active' }
                                <form method="post" class="mr-2" id="onpayCaptureTransaction" action="{$url}" name="capture-cancel">
                                    <div class="input-group">
                                        <input type="text" class="form-control input-lg" name="onpayCapture_value"
                                                value="{$payment['details']['chargeable']}">

                                        <input type="hidden" class="form-control" name="onpayCapture_currency"
                                                value="{$payment['onpay']->currencyCode}">

                                        <div class="input-group-append">
                                            <input type="submit" class="btn btn-info" value="Capture amount">
                                        </div>
                                    </div>
                                </form>
                            {/if}
     
                            <form class="onpayCancel" method="post" action="{$url}" name="capture-cancel">
                                {if $payment['onpay']->charged > 0 and $payment['onpay']->refunded < $payment['onpay']->charged }
                                    <button type="button" class="btn btn-info onpayActionButton mr-1" data-toggle="modal" data-target="#onpayRefund">
                                        {l s='Refund' mod='onpay'}
                                    </button>
                                {/if}

                                {if $payment['onpay']->status eq 'active'}
                                    <input class="btn btn-danger" id="onpayCancel" type="button" name="onpayCancel" value="{if $payment['details']['charged'] gt 0} {l s='Finish transaction' mod='onpay'}  {else} {l s='Cancel transaction' mod='onpay'} {/if}">
                                {/if}
                            </form>
                        </div>
                    </div>

            </div>
            
        </div>  
    {/foreach}
{/if}
