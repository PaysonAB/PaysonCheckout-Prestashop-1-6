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
    if (page_name === 'order-opc') {
        cartAmount = $("#total_price").text();
        // Detect completed  AJAX
        $(document).ajaxComplete(function(event, xhr, settings) {
             if ((settings.url.toLowerCase().indexOf('quick-order') >= 0) || (settings.url.toLowerCase().indexOf('order-opc') >= 0) || (cartAmount !== $("#total_price").text())) {
                hideTerms();
                addNewsletter();
                sendLockDown();
                updateCheckout({pco_update: '1'}, false, true);
                cartAmount = $("#total_price").text();
             }
        });

        // Hide all payment methods or messages about login
        $("#opc_payment_methods div").each(function() {
            $(this).hide();   
        });

        // Hide account info
        $('.page-heading.step-num:first, #opc_account, #opc_new_account').hide();


        // Add container for iframe
        $('#opc_payment_methods').append('<div style="height:536px;" id="paysonpaymentwindow"></div>');

        hideTerms();
        addNewsletter();

        function hideTerms() {
            if (!termsRequired()) {
                $('#cgv').addClass('cgv-disabled terms-box-hidden');
                if ($('#cgv').parent().parent().parent().parent().hasClass('box') && !$('#cgv').parent().parent().parent().parent().hasClass('order_carrier_content')) {
                    $('#cgv').parent().parent().parent().parent().addClass('terms-box-hidden');
                } else if ($('#cgv').parent().parent().parent().hasClass('checkbox')) {
                    $('#cgv').parent().parent().parent().addClass('terms-box-hidden');
                    if ($('#cgv').parent().parent().parent().prev('p').hasClass('carrier_title')) {
                        $('#cgv').parent().parent().parent().prev('p').addClass('terms-box-hidden');
                    }
                }
            } else {
                $('.terms-box-hidden').removeClass('terms-box-hidden');
                //$('#uniform-cgv').parent().parent().addClass('terms-box');
            }
        }

        function addNewsletter() {
            if (showNewsletter === 1 && $('.order_carrier_content').length && $('.newsletter-checkbox').length === 0) {
               $.ajax({
                    type: 'GET',
                    url: pcourl,
                    async: true,
                    cache: false,
                    data: {newsletter_markup: '1'},
                    success: function(returnData)
                    {
                        $('.order_carrier_content').append(returnData);
                        checkNewsletter();
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        //console.log(returnData);
                    }
                });
            }
        }

        // Precheck terms
        if (sessionStorage.conditions_to_approve_checkbox === 'true') {
            $('#cgv').prop('checked', true);
        }

        // Precheck newsletter
        function checkNewsletter() {
            if (sessionStorage.newsletter_checkbox === 'true') {
                $('#newsletter_checkbox').prop('checked', true);
                $('#newsletter_checkbox').parent().addClass('checked');
            }
        }

        // Newsletter action
        $('body').on('click', '#newsletter_checkbox', function () {
            sessionStorage.setItem('newsletter_checkbox', $(this).prop('checked'));
            if (sessionStorage.newsletter_checkbox === 'true') {
                $(this).parent().addClass('checked');
            } else {
                $(this).parent().removeClass('checked');
            }
            updateCheckout({newsletter_sub: $(this).prop('checked')}, false, false);
        });

        // Terms action
        $('body').on('click', '#cgv', function () {
            sessionStorage.setItem('conditions_to_approve_checkbox', $(this).prop('checked'));
        });

        // Payson address change
        document.addEventListener('PaysonEmbeddedAddressChanged', function() {
            sendLockDown();
            validateOrder({validate_order: '1', id_cart: id_cart});
        }, true);

        function updateCheckout(callData, updateCart, updateCheckout) {
            if ((termsRequired() && termsChecked()) || termsRequired() === false) {
                upReq = null;
                upReq = $.ajax({
                    type: 'GET',
                    url: pcourl,
                    async: true,
                    cache: false,
                    data: callData,
                    beforeSend: function()
                    { 
                        if (upReq !== null) {
                            upReq.abort();
                        }
                    },
                    success: function(returnData)
                    {
                        if (returnData === 'reload') {
                            location.href = orderOpcUrl;
                        } else if (updateCheckout === true) {
                            $("#paysonpaymentwindow").html(returnData);
                        }
                        sendRelease();
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        //console.log(returnData);
                        sendRelease();
                    }
                });
            } else {
                $("#paysonpaymentwindow").html('<p class="warning">' + acceptTermsMessage + '</p>');
            }    
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

        function termsChecked() {
            if ($('#cgv').prop('checked')) {
                return true;
            }

            return false;
        }

        function termsRequired() {
            if (showTerms === 1 && $('#cgv').length && $('.cgv-disabled').length === 0) {
                return true;
            }

            return false;
        }

        // Validate order
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

        // Init checkout
        updateCheckout({pco_update: '1'}, false, true);
    }
});
