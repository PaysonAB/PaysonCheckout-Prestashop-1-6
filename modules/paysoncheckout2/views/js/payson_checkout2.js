/*
* 2018 Payson AB
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
*  @author    Payson AB <integration@payson.se>
*  @copyright 2018 Payson AB
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

$(document).ready(function() {
    function sendLockDown() {
        if ($('#paysonIframe').length) {
            document.getElementById('paysonIframe').contentWindow.postMessage('lock', '*');
            if ($('#paysonpaymentwindow').length) {
                // To prevent height flash when iframe reload
                $('#paysonpaymentwindow').height($('#paysonIframe').height());
            }
        }
    }

    function sendRelease() {
        if ($('#paysonIframe').length) {
            document.getElementById('paysonIframe').contentWindow.postMessage('release', '*');
        }
        setTimeout(function() {
            if ($('#paysonpaymentwindow').length) {
                $('#paysonpaymentwindow').height('auto');
            }
        }, 500);
    }

    setTimeout(function() {
        if ($('#paysonpaymentwindow').length) {
            $('#paysonpaymentwindow').height('auto');
        }
    }, 600);
    
    // Validate order on PaysonEmbeddedAddressChanged event
    function validateOrder(callData) {
        valReq = null;
        valReq = $.ajax({
            type: 'GET',
            url: validateurl,
            async: true,
            cache: false,
            data: callData,
            beforeSend: function()
            { 
                if (valReq !== null) {
                    valReq.abort();
                }
            },
            success: function(returnData)
            {
                if (returnData == 'reload') {
                    location.href = paymenturl;
                } else {
                    sendRelease();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                sendRelease();
            }
        });
    }
    
    document.addEventListener('PaysonEmbeddedAddressChanged', function() {
        sendLockDown();
        var callData = {validate_order: '1', id_cart: id_cart};
        validateOrder(callData);
    }, true);
});
