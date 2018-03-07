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
        $('#opc_payment_methods').append('<div id="paysonpaymentwindow"></div>');
        
        // Watch for change in cart total and update checkout
        amount = $("#total_price").text();
        setInterval(function() {
            if(amount !== $("#total_price").text()) {
               sendLockDown();
               updateCheckout();
            } 
            amount = $("#total_price").text();
        }, 500);
        
        // Init checkout
        updateCheckout();
    }

	upReq = null;
    function updateCheckout() {
        upReq =  $.ajax({
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
                    setTimeout(function() {
                        if ($('#paysonpaymentwindow').length) {
                            $('#paysonpaymentwindow').height('auto');
                        }
                    }, 800);
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
    }

    // Reset container height to auto for responsive
    setTimeout(function() {
        if ($('#paysonpaymentwindow').length) {
            $('#paysonpaymentwindow').height('auto');
        }
    }, 1000);
    
    // Validate order on PaysonEmbeddedAddressChanged event
	valReq = null;
    function validateOrder(callData) {
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
    
    // IE11 poly for custom event
    (function () {
        if ( typeof window.CustomEvent === "function" ) return false; //If not IE

        function CustomEvent ( event, params ) {
                params = params || { bubbles: false, cancelable: false, detail: undefined };
                var evt = document.createEvent( 'CustomEvent' );
                evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
                return evt;
        }
        CustomEvent.prototype = window.Event.prototype;
        window.CustomEvent = CustomEvent;
    })();
});
