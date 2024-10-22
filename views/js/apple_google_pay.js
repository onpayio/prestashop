/**
 * MIT License
 *
 * Copyright (c) 2024 OnPay.io
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

jQuery(function($) {
    // Run initially
    initGAPay();

    function initGAPay() {
        // Run logic that disables Apple Pay and Google Pay for selection.
        disableGAPay();
        // Renable supported methods.
        renableSupportedGAPay();
    }

    function disableGAPay() {
        // Disable Apple Pay method for usage
        if (typeof appleId !== 'undefined') {
            $('#' + appleId + '-container').hide();
            $('input#' + appleId).attr('disabled', true).attr('checked', false).addClass('disabled');
        }
        

        // Disable Google Pay method for usage
        if (typeof googleId !== 'undefined') {
            $('#' + googleId + '-container').hide();
            $('input#' + googleId).attr('disabled', true).attr('checked', false).addClass('disabled');
        }
    }

    function renableSupportedGAPay() {
        if (typeof window['Promise'] === 'function') {
            // Check if Apple Pay is supported, and renable method if so.
            if (typeof appleId !== 'undefined') {
                let applePayAvailablePromise = OnPayIO.applePay.available();
                applePayAvailablePromise.then(function(result) {
                    if (result) {
                        $('#' + appleId + '-container').show().addClass('show');
                        $('input#' + appleId).attr('disabled', false).removeClass('disabled');
                    } else {
                        $('#' + appleId + '-container').remove();
                    }
                });
            }

            // Check if Google Pay is supported, and renable method if so.
            if (typeof googleId !== 'undefined') {
                let googlePayAvailablePromise = OnPayIO.googlePay.available();
                googlePayAvailablePromise.then(function(result) {
                    if (result) {
                        $('#' + googleId + '-container').show().addClass('show');
                        $('input#' + googleId).attr('disabled', false).removeClass('disabled');
                    } else {
                        $('#' + googleId + '-container').remove();
                    }
                });
            }
        }
    }
});