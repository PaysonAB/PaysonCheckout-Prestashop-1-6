$(document).ready(function(){
    if(document.getElementById('opc_payment_methods')) 
    {
        var amount = 0;
        
        document.getElementById("new_account_form").style.display = ("none");
        $getElementHPayment = document.getElementById("HOOK_PAYMENT");
        $getElementHPChildren = $getElementHPayment.children[0];
        $getElementHPCClass = $getElementHPChildren.className;

        if($getElementHPCClass === 'warning'){
            $("#HOOK_PAYMENT").each(function() {
                $("#HOOK_PAYMENT").hide();   
            });
            $("#opc_payment_methods").append("<div id=iframepayson></div>"); 

        }else{
            $("#opc_new_account").append("<div id=iframepayson></div>"); 
        }

        amount = $("#total_price").text();
        $intervalToShowPayson = 1;
        setInterval(function() {
            $isPayson = document.getElementById("paysonTracker");
            $paysonTrackerId = $isPayson.children[0].id;

            if(amount !== $("#total_price").text() || ($intervalToShowPayson === 1 && document.getElementById("payment_" + $paysonTrackerId).checked === true)){
                document.getElementById("new_account_form").style.display = ("none");
                document.getElementById("iframepayson").style.display = ("block");
                $(".confirm_button_div").each(function() {
                    $(".confirm_button_div").hide();   
                });

                sendLockDown();
                displaySnippet();

                $(".confirm_button_div").each(function() {
                    $(".confirm_button_div").hide();   
                });

                $("#offer_password").each(function() {
                    $("#offer_password").hide(); 

                });
               $intervalToShowPayson = 0;
               amount = $("#total_price").text();
            }
            if(document.getElementById("payment_" + $paysonTrackerId).checked === false){
                document.getElementById("iframepayson").style.display = ("none");
                document.getElementById("new_account_form").style.display = ("block");
                $(".confirm_button_div").each(function() {
                    $(".confirm_button_div").show();   
                });
                $intervalToShowPayson = 1;
            }
        }, 500);  
    displaySnippet();     

    }

    function displaySnippet() {
        $.ajax({
           url: window.location.origin  + '/modules/paysonCheckout2/redirect.php?type=checkPayson',
           success:function (data) {
            $("#iframepayson").html(data);
            if(document.getElementById('paysonIframe')) {
                sendRelease();
            }
           }
        });
    }

    document.addEventListener("PaysonEmbeddedAddressChanged",function(evt) {
        var address = evt.detail;
        updatCartAddress(address);
    });

    function updatCartAddress(address) {
        $.ajax({
           url: window.location.origin  + "/modules/paysonCheckout2/redirect.php?address_data="+JSON.stringify(address),
           success:function (data) {
            $("#iframepayson").html(data);
           }
        });
    }

    function sendLockDown() {
        var iframe = document.getElementById('paysonIframe');
        iframe.contentWindow.postMessage('lock', '*');
    }

    function sendRelease() {
        var iframe = document.getElementById('paysonIframe');
        iframe.contentWindow.postMessage('release', '*');
    }
});
        


