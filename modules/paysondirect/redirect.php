<?php

global $cookie;
include_once(dirname(__FILE__) . '/../../config/config.inc.php');
include_once(dirname(__FILE__) . '/../../init.php');
include_once(dirname(__FILE__) . '/paysondirect.php');
include_once(_PS_MODULE_DIR_ . 'paysondirect/paysonEmbedded/paysonapi.php');

$payson = new Paysondirect();

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

//print_r($currencies_module);exit;

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

//Configuration::updateValue('PS_BLOCK_CART_AJAX', 0);
use PaysonEmbedded\CurrencyCode as CurrencyCode;

$confirmationUri = "http://" . $url . "modules/paysondirect/validation.php?trackingId=" . $trackingId . "&id_cart=" . $cart->id;
$notificationUri = "http://" . $url . 'modules/paysondirect/ipn_payson.php?id_cart=' . $cart->id;
$termsUri        = "http://www.google.se/#q=terms";
//$checkoutUri     = "http://" . $url . "index.php?controller=order&step=1";
$checkoutUri = "http://" . $url . "modules/paysondirect/validation.php?trackingId=" . $trackingId . "&id_cart=" . $cart->id;


//print_r(trim(Configuration::get('PAYSON_MERCHANTID')));;exit;
//print_r(PAYSON_MERCHANTID);exit;
$callPaysonApi = $payson->getAPIInstanceMultiShop();

$paysonMerchant = new PaysonEmbedded\PaysonMerchant(($payson->testMode  ? trim(Configuration::get('PAYSON_SANDBOX_MERCHANTID')) : trim(Configuration::get('PAYSON_MERCHANTID'))), $checkoutUri, $confirmationUri, $notificationUri, $termsUri, NULL, $payson->MODULE_VERSION);
$paysonMerchant->setReference($cart->id);
//$paysonMerchant = new PaysonEmbedded\PaysonMerchant($callPaysonApi->getMerchantId(), $checkoutUri, $confirmationUri, $notificationUri, $termsUri);

$payData = new PaysonEmbedded\PayData();
$payData->setCurrencyCode($currency_module['iso_code']);
//$payData->setLocaleCode(Language::getIsoById($cookie->id_lang));

$orderItems = orderItemsList($cart, $payson);

$payData->setOrderItems($orderItems);

$callPaysonApi->setPaysonMerchant($paysonMerchant);
$callPaysonApi->setPayData($payData);

$callPaysonApi->setCustomer(new PaysonEmbedded\Customer(
        $customer->firstname, 
        $customer->lastname,
        $customer->email,
        $address->phone,
        //$customer->birthday,
        '4605092222',
        $address->city, 
        $address->country, 
        //$address->postcode, 
        '99999',
        $address->address1)
);

$callPaysonApi->setGui(new PaysonEmbedded\Gui($payson->languagePayson(Language::getIsoById($cookie->id_lang)), Configuration::get('PAYSON_COLOR_SCHEME'), Configuration::get('PAYSON_VERIFICATION'), (int) Configuration::get('PAYSON_REQUEST_PHONE')));
//Create a row in the table ps_payson_order_X of the database
PrestaShopLogger::addLog('message', 1, NULL, NULL, NULL, true);
$paysonEmbeddedStatus = '';
if ($payson->getCheckoutIdPayson($cart->id) != Null) {
    $callPaysonApi->doRequest('GET', $payson->getCheckoutIdPayson($cart->id));
    $paysonEmbeddedStatus = $callPaysonApi->getResponsObject()->status;
}
    
if ($payson->getCheckoutIdPayson($cart->id) != Null AND $paysonEmbeddedStatus == 'readyToShip') {
    //$payson->CreateOrder($cart->id, $payson->getCheckoutIdPayson($cart->id), 'checkoutCall');
    //$embeddedUrl = $this->getSnippetUrl($callPaysonApi->getResponsObject()->snippet);
    //Tools::redirect(Context::getContext()->link->getModuleLink('paysondirect', 'payment', array('checkoutId' => $callPaysonApi->getResponsObject()->id, 'width' => Configuration::get('PAYSON_IFRAME_SIZE_WIDTH'), 'width_type' => Configuration::get('PAYSON_IFRAME_SIZE_WIDTH_TYPE'), 'height' => Configuration::get('PAYSON_IFRAME_SIZE_HEIGHT'), 'height_type' => Configuration::get('PAYSON_IFRAME_SIZE_HEIGHT_TYPE'), 'snippetUrl' => $embeddedUrl[0])));
}
    
if ($payson->getCheckoutIdPayson($cart->id) != Null AND $paysonEmbeddedStatus == 'created') {
    //$callPaysonApi->doRequest("PUT", $this->getCheckoutIdPayson($this->session->data['order_id']));
    //update order to denied and update the database
    $callPaysonApi->doRequest("POST");
    if ($callPaysonApi->getCheckoutId() != null) {
        $payson->createPaysonOrderEvents($callPaysonApi->getCheckoutId(), $cart->id);
    }
}else{
    $callPaysonApi->doRequest("POST");
    if ($callPaysonApi->getCheckoutId() != null) {
        PrestaShopLogger::addLog('message', 1, NULL, NULL, NULL, true);
        $payson->createPaysonOrderEvents($callPaysonApi->getCheckoutId(), $cart->id);
    }
}
//echo '<pre>';print_r($callPaysonApi);echo '</pre>';exit;
if (count($callPaysonApi->getpaysonResponsErrors()) == 0) {
    //print_r($callPaysonApi->getCheckoutId());exit;
    $callPaysonApi->doRequest();
    $embeddedUrl = $payson->getSnippetUrl($callPaysonApi->getResponsObject()->snippet);

    Tools::redirect(Context::getContext()->link->getModuleLink('paysondirect', 'payment', array('checkoutId' => $callPaysonApi->getCheckoutId(), 'width' => Configuration::get('PAYSON_IFRAME_SIZE_WIDTH'), 'width_type' => Configuration::get('PAYSON_IFRAME_SIZE_WIDTH_TYPE'), 'height' => Configuration::get('PAYSON_IFRAME_SIZE_HEIGHT'), 'height_type' => Configuration::get('PAYSON_IFRAME_SIZE_HEIGHT_TYPE'), 'snippetUrl' => $embeddedUrl[0])));
} else {
    if (Configuration::get('PAYSON_LOGS') == 'yes') {
        foreach ($callPaysonApi->getpaysonResponsErrors() as $value) {
            $message = '<Payson Embedded> ErrorId: ' . $value->getErrorId() . '  -- Message: ' . $value->getMessage() . '  -- Parameter: ' . $value->getParameter();
            PrestaShopLogger::addLog($message, 1, NULL, NULL, NULL, true);
        }
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

function orderItemsList($cart, $payson) {
    include_once(_PS_MODULE_DIR_ . 'paysondirect/PaysonEmbedded/orderitem.php');

    $orderitemslist = array();



    foreach ($cart->getProducts() AS $cartProduct) {
        if (isset($cartProduct['quantity_discount_applies']) && $cartProduct['quantity_discount_applies'] == 1)
            $payson->discount_applies = 1;
        $my_taxrate = $cartProduct['rate'] / 100;
        $product_price = $cartProduct['price_wt'];
        //print_r('nada');
        $attributes_small = isset($cartProduct['attributes_small']) ? $cartProduct['attributes_small'] : '';

        $orderitemslist[] = new PaysonEmbedded\OrderItem(
                $cartProduct['name'] . '  ' . $attributes_small, number_format($product_price, 2, '.', ''), $cartProduct['cart_quantity'], number_format($my_taxrate, 3, '.', ''), $cartProduct['id_product']
        );
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
            $orderitemslist[] = new PaysonEmbedded\OrderItem(
                    isset($carrier->name) ? $carrier->name : 'shipping', number_format($total_shipping_wt, 2, '.', ''), 1, number_format($carriertax_rate, 2, '.', ''), 9998
            );
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
        //$objDiscount = new Discount(intval($cartDiscount['id_discount']));
        $discountTemp -= ($cartDiscount['value_real'] - (empty($cartDiscounts) ? 0 : $cartDiscounts[$i]['obj']->free_shipping ? $total_shipping_wt : 0));
        // $itemPriceTemp -= (Tools::ps_round($cartDiscount['value_real'], Configuration::get('PS_PRICE_DISPLAY_PRECISION')) - (empty($cartDiscounts) ? 0 : $cartDiscounts[$i]['obj']->free_shipping ? Tools::ps_round($total_shipping_wt, Configuration::get('PS_PRICE_DISPLAY_PRECISION')) : 0));
        $i++;
    }
    if (!empty($cartDiscounts)) {
        $orderItemTemp = new PaysonEmbedded\OrderItem($cartDiscount['name'], number_format($discountTemp, Configuration::get('PS_PRICE_DISPLAY_PRECISION'), '.', ''), 1, number_format($tax_rate_discount, 4, '.', ''));
        $orderItemTemp->setType('discount');
        $orderitemslist[] = $orderItemTemp;
    }

    if ($cart->gift) {
        $orderitemslist[] = new PaysonEmbedded\OrderItem('gift wrapping', number_format(Tools::convertPrice((float) $cart->getGiftWrappingPrice(false), Currency::getCurrencyInstance((int) $cart->id_currency)), Configuration::get('PS_PRICE_DISPLAY_PRECISION'), '.', ''), 1, number_format((((($cart->getOrderTotal(true, Cart::ONLY_WRAPPING) * 100) / $cart->getOrderTotal(false, Cart::ONLY_WRAPPING)) - 100) / 100), 2, '.', ''), 'wrapping');
    }
    //echo '<pre>';print_r($orderitemslist);echo '</pre>';exit;
    return $orderitemslist;
}

//ready, -----------------------------------------------------------------------
?>
