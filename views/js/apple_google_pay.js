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