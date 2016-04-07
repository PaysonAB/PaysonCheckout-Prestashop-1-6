<?php

global $cookie;
include_once(dirname(__FILE__) . '/../../config/config.inc.php');
include_once(dirname(__FILE__) . '/../../init.php');
include_once(dirname(__FILE__) . '/paysonCheckout2.php');
include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/paysonEmbedded/paysonapi.php');

$payson = new PaysonCheckout2();

$cart = new Cart(intval($cookie->id_cart));

$address = new Address(intval($cart->id_address_invoice));

$state = NULL;

if ($address->id_state)
    $state = new State(intval($address->id_state));
$customer = new Customer(intval($cart->id_customer));


if (!Validate::isLoadedObject($address))
    die($payson->getL('Payson error: (invalid address)'));

if (!Validate::isLoadedObject($customer))
    die($payson->getL('Payson error: (invalid customer)'));


// check currency of payment
$currency_order = new Currency(intval($cart->id_currency));
$currencies_module = $payson->getCurrency();

if (is_array($currencies_module)) {
    foreach ($currencies_module AS $some_currency_module) {
        if ($currency_order->iso_code == $some_currency_module['iso_code']) {
            $currency_module = $some_currency_module;
        }
    }
} else {
    $currency_module = $currencies_module;
}

if ($currency_order->id != $currency_module['id_currency']) {
    $cookie->id_currency = $currency_module['id_currency'];
    $cart->id_currency = $currency_module['id_currency'];
    $cart->update();
}

$amount = floatval($cart->getOrderTotal(true, 3));

$url = Tools::getHttpHost(false, true) . __PS_BASE_URI__;

$trackingId = time();

use PaysonEmbedded\CurrencyCode as CurrencyCode;
//print_r("http://" . $url);exit;
$confirmationUri = "http://" . $url . "modules/paysonCheckout2/validation.php?trackingId=" . $trackingId . "&id_cart=" . $cart->id;
$notificationUri = "http://" . $url . 'modules/paysonCheckout2/ipn_payson.php?id_cart=' . $cart->id;
$termsUri = "http://" . $url . "index.php?id_cms=3&controller=cms&content_only=1";
//$checkoutUri     = "http://" . $url . "index.php?controller=order&step=1";
$checkoutUri = "http://" . $url . "modules/paysonCheckout2/validation.php?trackingId=" . $trackingId . "&id_cart=" . $cart->id;

$callPaysonApi = $payson->getAPIInstanceMultiShop();
$paysonMerchant = new PaysonEmbedded\Merchant($checkoutUri, $confirmationUri, $notificationUri, $termsUri, NULL, $payson->MODULE_VERSION);
$paysonMerchant->reference = $cart->id;
$payData = new PaysonEmbedded\PayData($currency_module['iso_code']);

orderItemsList($cart, $payson, $payData);

$gui = new PaysonEmbedded\Gui($payson->languagePayson(Language::getIsoById($cookie->id_lang)), Configuration::get('PAYSONCHECKOUT2_COLOR_SCHEME'), Configuration::get('PAYSONCHECKOUT2_VERIFICATION'), (int) Configuration::get('PAYSONCHECKOUT2_REQUEST_PHONE'));
$customer = new PaysonEmbedded\Customer($customer->firstname, $customer->lastname, $customer->email, $address->phone, "", $address->city, $address->country, $address->postcode, $address->address1);
$checkout = new PaysonEmbedded\Checkout($paysonMerchant, $payData, $gui, $customer);
$checkoutTempObj = NULL;
//echo '<pre>';print_r($checkout);'</pre>';exit;
try {
    $paysonEmbeddedStatus = '';
    if ($payson->getCheckoutIdPayson($cart->id) != Null) {
        $checkoutTempObj = $callPaysonApi->GetCheckout($payson->getCheckoutIdPayson($cart->id));
        //$callPaysonApi->doRequest('GET', $payson->getCheckoutIdPayson($cart->id));
        $paysonEmbeddedStatus = $checkoutTempObj->status;
    }
 
    if ($payson->getCheckoutIdPayson($cart->id) != Null AND $paysonEmbeddedStatus == 'created') {
        $checkoutIdTemp = $callPaysonApi->CreateCheckout($checkout);
        $checkoutTemp = $callPaysonApi->GetCheckout($checkoutIdTemp);
        $checkoutTempObj = $callPaysonApi->UpdateCheckout($checkoutTemp);
        
        if ($checkoutTempObj->id != null) {
            $payson->createPaysonOrderEvents($checkoutTempObj->id, $cart->id);
        }
    } else {
        $checkoutId = $callPaysonApi->CreateCheckout($checkout);
        $checkoutTempObj = $callPaysonApi->GetCheckout($checkoutId);

        if ($checkoutTempObj->id != null) {
            $payson->createPaysonOrderEvents($checkoutTempObj->id, $cart->id);
        }
    }

    $embeddedUrl = $payson->getSnippetUrl($checkoutTempObj->snippet);
    Tools::redirect(Context::getContext()->link->getModuleLink('paysonCheckout2', 'payment', array('checkoutId' => $checkoutTempObj->id, 'width' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH'), 'width_type' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE'), 'height' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT'), 'height_type' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE'), 'snippetUrl' => $embeddedUrl[0])));
} catch (Exception $e) {
    if (Configuration::get('PAYSONCHECKOUT2_LOGS') == 'yes') {
        $message = '<Payson PrestaShop Checkout 2.0> ' . $e->getMessage();
        PrestaShopLogger::addLog($message, 1, NULL, NULL, NULL, true);
    }
    $payson->paysonApiError('Please try using a different payment method.');
}


/*
 * @return void
 * @param array $paysonUrl, $productInfo, $shopInfo, $moduleVersionToTracking
 * @disc the function request and redirect Payson API Sandbox
 */

/*
 * @return product list
 * @param int $id_cart
 * @disc 
 */

function orderItemsList($cart, $payson, $payData) {
    include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/PaysonEmbedded/orderitem.php');

    $orderitemslist = array();



    foreach ($cart->getProducts() AS $cartProduct) {
        if (isset($cartProduct['quantity_discount_applies']) && $cartProduct['quantity_discount_applies'] == 1)
            $payson->discount_applies = 1;
        $my_taxrate = $cartProduct['rate'] / 100;
        $product_price = $cartProduct['price_wt'];

        $attributes_small = isset($cartProduct['attributes_small']) ? $cartProduct['attributes_small'] : '';

       $payData->AddOrderItem(new  PaysonEmbedded\OrderItem(
                $cartProduct['name'] . '  ' . $attributes_small, number_format($product_price, 2, '.', ''), $cartProduct['cart_quantity'], number_format($my_taxrate, 3, '.', ''), $cartProduct['id_product']
        ));
    }

    // check four discounts 
    $cartDiscounts = $cart->getDiscounts();

    /*
      $tax_rate = 0;
      $taxDiscount = Cart::getTaxesAverageUsed((int)($cart->id));
      if (isset($taxDiscount) AND $taxDiscount != 1)
      $tax_rate = $taxDiscount * 0.01;
     */



    $total_shipping_wt = floatval($cart->getTotalShippingCost());
    $total_shipping_wot = 0;
    $carrier = new Carrier($cart->id_carrier, $cart->id_lang);

    if ($total_shipping_wt > 0) {

        $carriertax = Tax::getCarrierTaxRate((int) $carrier->id, $cart->id_address_invoice);
        $carriertax_rate = $carriertax / 100;
        $forward_vat = 1 + $carriertax_rate;
        $total_shipping_wot = $total_shipping_wt / $forward_vat;

        if (!empty($cartDiscounts) and $cartDiscounts[0]['obj']->free_shipping) {
            //if (empty($cartDiscounts) and $cartDiscounts->free_shipping) {
            //if (empty($cartDiscounts))
        } else {
            $payData->AddOrderItem(new  PaysonEmbedded\OrderItem(
                    isset($carrier->name) ? $carrier->name : 'shipping', number_format($total_shipping_wt, 2, '.', ''), 1, number_format($carriertax_rate, 2, '.', ''), 'shipping', PaysonEmbedded\OrderItemType::SERVICE
            ));
        }
    }

    $tax_rate_discount = 0;
    $taxDiscount = Cart::getTaxesAverageUsed((int) ($cart->id));

    if (isset($taxDiscount) AND $taxDiscount != 1) {
        $tax_rate_discount = $taxDiscount * 0.01;
    }

    $discountTemp = 0;
    $i = 0;
    foreach ($cartDiscounts AS $cartDiscount) {
        $discountTemp -= ($cartDiscount['value_real'] - (empty($cartDiscounts) ? 0 : $cartDiscounts[$i]['obj']->free_shipping ? $total_shipping_wt : 0));
        $i++;
    }
    if (!empty($cartDiscounts)) {
        $payData->AddOrderItem(new  PaysonEmbedded\OrderItem($cartDiscount['name'], number_format($discountTemp, Configuration::get('PS_PRICE_DISPLAY_PRECISION'), '.', ''), 1, number_format($tax_rate_discount, 4, '.', ''), "discount", PaysonEmbedded\OrderItemType::DISCOUNT));
    }

    if ($cart->gift) {
       $wrappingTemp = number_format(Tools::convertPrice((float) $cart->getGiftWrappingPrice(false), Currency::getCurrencyInstance((int) $cart->id_currency)), Configuration::get('PS_PRICE_DISPLAY_PRECISION'), '.', '') * number_format((((($cart->getOrderTotal(true, Cart::ONLY_WRAPPING) * 100) / $cart->getOrderTotal(false, Cart::ONLY_WRAPPING))) / 100), 2, '.', '');
        $payData->AddOrderItem(new  PaysonEmbedded\OrderItem('gift wrapping', $wrappingTemp, 1, number_format((((($cart->getOrderTotal(true, Cart::ONLY_WRAPPING) * 100) / $cart->getOrderTotal(false, Cart::ONLY_WRAPPING)) - 100) / 100), 2, '.', ''), 'wrapping', PaysonEmbedded\OrderItemType::SERVICE));
    }
}

//ready, -----------------------------------------------------------------------
?>
