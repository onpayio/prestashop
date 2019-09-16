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
    <div class="panel">
    <div class="panel-heading">
        Onpay - {l s='Transaction details' mod='onpay'}
    </div>
    <div class="onpay-body">

    {foreach from=$paymentdetails item=payment }

        {if $payment['onpay']->acquirer eq 'test'}
            <div class="alert alert-warning" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                {l s='This is an test order' mod='onpay'}
            </div>
        {/if}
        <div class="row">
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6">
                        {if $payment['onpay']->status eq 'active' }
                            <form method="post" class="form-inline" id="onpayCaptureTransaction" action="{$url}"
                                  name="capture-cancel">
                                <div class="form-group form-group-lg">
                                    <input type="text" class="form-control input-lg" name="onpayCapture_value"
                                           value="{$payment['details']['chargeable']}">
                                    <input type="hidden" class="form-control" name="onpayCapture_currency"
                                           value="{$payment['onpay']->currencyCode}">
                                    <input type="submit" class="btn btn-info" value="Capture amount">
                                </div>
                            </form>
                        {/if}
                    </div>

                    <div class="col-md-6">
                        <div class="pull-right">
                            <form class="onpayCancel form-inline" method="post" action="{$url}" name="capture-cancel">
                                {if $payment['onpay']->charged > 0 and $payment['onpay']->refunded < $payment['onpay']->charged }
                                    <button type="button" class="btn btn-info onpayActionButton" data-toggle="modal"
                                            data-target="#onpayRefund">
                                        {l s='Refund' mod='onpay'}
                                    </button>
                                {/if}

                                {if $payment['onpay']->status eq 'active'}
                                    <input class="btn btn-danger" id="onpayCancel" type="button" name="onpayCancel"
                                           value="{if $payment['details']['charged'] gt 0} {l s='Finish transaction' mod='onpay'}  {else} {l s='Cancel transaction' mod='onpay'} {/if}">
                                {/if}
                            </form>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-md-6">
            </div>
        </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h2>{l s='Transaction details' mod='onpay'}</h2>
                <table class="table table-bordered onpayDetail table-condensed">
                    <tbody>
                    <tr>
                        <td><strong>{l s='Status' mod='onpay'}</strong></td>
                        <td>{$payment['onpay']->status}</td>
                    </tr>
                    <tr>
                        <td><strong>{l s='Card type' mod='onpay'}</strong></td>
                        <td>{$payment['onpay']->cardType}</td>
                    </tr>
                    <tr>
                        <td><strong>{l s='Transaction number' mod='onpay'}</strong></td>
                        <td>{$payment['onpay']->transactionNumber}</td>
                    </tr>
                    <tr>
                        <td><strong>{l s='IP' mod='onpay'}</strong></td>
                        <td>{$payment['onpay']->ip}</td>
                    </tr>
                    <tr>
                        <td><strong>{l s='Amount' mod='onpay'}</strong></td>
                        <td>{$payment['details']['amount']} {$currencyDetails->suffix}</td>
                    </tr>
                    <tr>
                        <td><strong>{l s='Charged' mod='onpay'}</strong></td>
                        <td>{$payment['details']['charged']} {$currencyDetails->suffix}</td>
                    </tr>
                    <tr>
                        <td><strong>{l s='Refunded' mod='onpay'}</strong></td>
                        <td>{$payment['details']['refunded']} {$currencyDetails->suffix}</td>
                    </tr>
                    </tbody>
                </table>

                <!-- Refund window -->
                <div class="modal fade" id="onpayRefund" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">{l s='Refund transaction' mod='onpay'}</h4>
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
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">{l s='Capture transaction' mod='onpay'}</h4>
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
                <br><br><br><br>

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
                            <td>{$history->amount} {$currencyDetails->suffix}</td>
                            <td>{$history->author}</td>
                            <td>{$history->ip}</td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>

            </div>
        </div>
        </div>


    {/foreach}
    </div>

{/if}
