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
    if ($('#opc_payment_methods').length) {
        // Hide all payment methods or messages about login
        $("#opc_payment_methods div").each(function() {
            $(this).hide();   
        });
        
        // Hide account info
        $('.page-heading.step-num:first, #opc_account, #opc_new_account').hide();
        
        // Add container for iframe
        $('#opc_payment_methods').append('<div style="height:536px;" id="paysonpaymentwindow"></div>');
        
        // Init checkout
        updateCheckout();
        
        // Watch for change in cart total and update checkout
        amount = $("#total_price").text();
        setInterval(function() {
            if(amount !== $("#total_price").text()) {
               sendLockDown();
               updateCheckout();
            } 
            amount = $("#total_price").text();
        }, 300);
    }

    function updateCheckout() {
        upReq = null;
        upReq = $.ajax({
            type: 'GET',
            url: pcourl,
            async: true,
            cache: false,
            data: {pco_update: '1'},
            beforeSend: function()
            { 
                if (upReq !== null) {
                    upReq.abort();
                }
            },
            success: function(returnData)
            {
                if (returnData == 'reload') {
                    location.href = orderOpcUrl;
                } else {
                    $("#paysonpaymentwindow").html(returnData);
                    sendRelease();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                //console.log(returnData);
                sendRelease();
            }
        });
    }

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
                    location.href = orderOpcUrl;
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
