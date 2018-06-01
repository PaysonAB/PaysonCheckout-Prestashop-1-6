<?php
/**
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

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class PaysonCheckout2PcOnePageModuleFrontController extends ModuleFrontController
{

    public $display_column_left = false;
    public $display_column_right = false;
    public $ssl = false;
    
    public function __construct()
    {
        parent::__construct();

        if (Configuration::get('PS_SSL_ENABLED')) {
            $this->ssl = true;
        }
    }
    
    public function postProcess()
    {
        // Newsletter subscription
        if (Tools::getIsset('newsletter_sub')) {
            $val = Tools::getValue('newsletter_sub');
            $this->context->cookie->__set('newsletter_sub', $val);
            die('success');
        }
        
        // Newsletter markup
        if (Tools::getIsset('newsletter_markup')) {
            $markup = '
                    <div class="box newsletter-checkbox"><div class="checkbox"><div class="checker" id="uniform-newsletter">'
                    . '<span><input type="checkbox" name="newsletter_checkbox" id="newsletter_checkbox" value="1"></span>'
                    . '</div>'
                    . '<label for="newsletter_checkbox">' . $this->module->l('Subscribe to our newsletter.', 'pconepage') . '</label></div></div>';
            
            die($markup);
        }
    }
    
    public function initContent()
    {
        parent::initContent();
        PaysonCheckout2::paysonAddLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *');
        
        $errMess = false;
        try {
            // Class PaysonCheckout2
            require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
            $payson = new PaysonCheckout2();
            
            if (isset($this->context->cart) && $this->context->cart->nbProducts() > 0) {
                // Set default delivery option on cart if needed
                if (!$this->context->cart->getDeliveryOption(null, true)) {
                    $this->context->cart->setDeliveryOption($this->context->cart->getDeliveryOption());
                    $this->context->cart->save();
                    PaysonCheckout2::paysonAddLog('Added default delivery: ' . print_r($this->context->cart->getDeliveryOption(), true));
                }
                
                // Check if rules apply
                CartRule::autoRemoveFromCart($this->context);
                CartRule::autoAddToCart($this->context);
                
                // Get cart currency
                $cartCurrency = new Currency($this->context->cart->id_currency);
                PaysonCheckout2::paysonAddLog('Cart Currency: ' . $cartCurrency->iso_code);
                
                // Check cart currency
                if (!$payson->validPaysonCurrency($cartCurrency->iso_code)) {
                    $errMess = $this->module->l('Unsupported currency. Please use SEK or EUR.', 'pconepage');
                }
                
                // Check cart products stock levels
                $cartQuantities = $this->context->cart->checkQuantities(true);
                if ($cartQuantities !== true) {
                    $errMess = $this->module->l('An item', 'pconepage') . ' (' . $cartQuantities['name'] . ') ' . $this->module->l('in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.', 'pconepage');
                }
                
                // Check minimun order value
                $min_purchase = Tools::convertPrice((float) Configuration::get('PS_PURCHASE_MINIMUM'), $cartCurrency);
                if ($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) < $min_purchase) {
                    $errMess = $this->module->l('This order does not meet the requirement for minimum order value.', 'pconepage');
                }
                
                // Check customer and address
                if ($this->context->customer->isLogged() || $this->context->customer->is_guest) {
                    PaysonCheckout2::paysonAddLog($this->context->customer->is_guest == 1 ? 'Customer is: Guest' : 'Customer is: Logged in');
                    // Customer is logged in or has entered guest address information, we'll use this information
                    $customer = new Customer((int) ($this->context->cart->id_customer));
                    $address = new Address((int) ($this->context->cart->id_address_invoice));

                    if ($address->id_state) {
                        $state = new State((int) ($address->id_state));
                    }

                    if (!Validate::isLoadedObject($customer)) {
                        $errMess = $this->module->l('Unable to validate customer. Please try again.', 'pconepage');
                    }
                } else {
                    PaysonCheckout2::paysonAddLog('Customer is not Guest or Logged in');
                    // Create new customer and address
                    $address = new Address();
                    $customer = new Customer();
                }
                
                // Refresh cart summary
                $this->context->cart->getSummaryDetails();
                $this->assignSummaryInformations();
                
                // Get delivery options
                //$checkoutSession = $this->getCheckoutSession();
                //$delivery_options = $checkoutSession->getDeliveryOptions();
                //$delivery_options_finder_core = new DeliveryOptionsFinder($this->context, $this->getTranslator(), $this->objectPresenter, new PriceFormatter());
                //$delivery_option = $delivery_options_finder_core->getSelectedDeliveryOption();

                // Free shipping cart rule
                $free_shipping = false;
                foreach ($this->context->cart->getCartRules() as $rule) {
                    if ($rule['free_shipping']) {
                        $free_shipping = true;
                        break;
                    }
                }

                // Free shipping based on order total
                $configuration = Configuration::getMultiple(array('PS_SHIPPING_FREE_PRICE', 'PS_SHIPPING_FREE_WEIGHT'));
                if (isset($configuration['PS_SHIPPING_FREE_PRICE']) && $configuration['PS_SHIPPING_FREE_PRICE'] > 0) {
                    $free_fees_price = Tools::convertPrice((float) $configuration['PS_SHIPPING_FREE_PRICE'], Currency::getCurrencyInstance((int) $this->context->cart->id_currency));
                    $orderTotalwithDiscounts = $this->context->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, null, null, false);
                    $left_to_get_free_shipping = ($free_fees_price - $orderTotalwithDiscounts);
                    $this->context->smarty->assign('left_to_get_free_shipping', $left_to_get_free_shipping);
                }

                // Free shipping based on order weight
                if (isset($configuration['PS_SHIPPING_FREE_WEIGHT']) && $configuration['PS_SHIPPING_FREE_WEIGHT'] > 0) {
                    $free_fees_weight = $configuration['PS_SHIPPING_FREE_WEIGHT'];
                    $total_weight = $this->context->cart->getTotalWeight();
                    $left_to_get_free_shipping_weight = $free_fees_weight - $total_weight;
                    $this->context->smarty->assign('left_to_get_free_shipping_weight', $left_to_get_free_shipping_weight);
                }
                
                // Assign smarty tpl variables
                $this->context->smarty->assign(array(
                    'discounts' => $this->context->cart->getCartRules(),
                    'cart_is_empty' => false,
                    'gift' => $this->context->cart->gift,
                    'gift_message' => $this->context->cart->gift_message,
                    'giftAllowed' => (int) (Configuration::get('PS_GIFT_WRAPPING')),
                    'gift_wrapping_price' => Tools::convertPrice($this->context->cart->getGiftWrappingPrice(true), $cartCurrency),
                    'message' => Message::getMessageByCartId((int) ($this->context->cart->id)),
                    'id_cart' => $this->context->cart->id,
                    'controllername' => 'pconepage',
                    'free_shipping' => $free_shipping,
                    'id_lang' => $this->context->language->id,
                    'token_cart' => $this->context->cart->secure_key,
                    'id_address' => $this->context->cart->id_address_delivery,
                    //'delivery_options' => $delivery_options,
                    //'delivery_option' => $delivery_option,
                    'PAYSONCHECKOUT2_SHOW_TERMS' => (int) Configuration::get('PAYSONCHECKOUT2_SHOW_TERMS'),
                    'PAYSONCHECKOUT2_NEWSLETTER' => (int) Configuration::get('PAYSONCHECKOUT2_NEWSLETTER'),
                    'pcoUrl' => $this->context->link->getModuleLink('paysoncheckout2', 'pconepage', array(), true),
                    'validateUrl' => $this->context->link->getModuleLink('paysoncheckout2', 'validation', array(), true),
                    'paymentUrl' => $this->context->link->getModuleLink('paysoncheckout2', 'pconepage', array(), true),
                    'newsletter_optin_text' => $this->module->l('Sign up for our newsletter', 'pconepage'),
                ));

                // Check for error and exit if any
                if ($errMess !== false) {
                    throw new Exception($errMess);
                }
                
                // Initiate Payson API
                $paysonApi = $payson->getPaysonApiInstance();
                PaysonCheckout2::paysonAddLog('Payson API initiated. Agent ID: ' . $paysonApi->getMerchantId());

                $getCheckout = $this->getCheckout($payson, $paysonApi, $customer, $cartCurrency, $address);
                $checkout = $getCheckout['checkout'];
                $isNewCheckout = $getCheckout['newcheckout'];

                if (!$isNewCheckout) {
                    // Check if we need to create a new checkout if language or currency differs between cart and checkout
                    if (!$payson->checkCurrencyName($cartCurrency->iso_code, $checkout->payData->currency) || ($payson->languagePayson(Language::getIsoById($this->context->language->id)) !== $payson->languagePayson($checkout->gui->locale))) {
                        $this->context->cookie->__set('paysonCheckoutId', null);
                        $getCheckout = $this->getCheckout($payson, $paysonApi, $customer, $cartCurrency, $address);
                        $checkout = $getCheckout['checkout'];
                        $isNewCheckout = $getCheckout['newcheckout'];
                    } else {
                        if ($payson->canUpdate($checkout->status)) {
                            // Update checkout
                            $checkout = $paysonApi->UpdateCheckout($payson->updatePaysonCheckout($checkout, $customer, $this->context->cart, $payson, $address, $cartCurrency));
                        } else {
                            // E.g for expired checkouts
                            $this->context->cookie->__set('paysonCheckoutId', null);
                        }
                    }
                }
                
                // Assign some more smarty tpl variables
                $this->context->smarty->assign(array(
                    'pco_checkout_id' => $checkout->id,
                    'payson_checkout' => $checkout->snippet,
                ));

                // Reset message
                $errMess = '';
                $this->context->smarty->assign('payson_errors', null);
                // Check for validation/confirmation errors
                if (isset($this->context->cookie->validation_error) && $this->context->cookie->validation_error != null) {
                    //$errMess = $this->context->cookie->validation_error;
                    PaysonCheckout2::paysonAddLog('Validation or confirmation message: ' . $errMess);
                    //$this->context->smarty->assign('payson_errors', $errMess);
                    // Delete old messages
                    $this->context->cookie->__set('validation_error', null);
                }
                
                // If AJAX return snippet and any message
                if (Tools::getIsset('pco_update')) {
                    if ($errMess != '') {
                        $errMess = '<p class="warning">' . $errMess . '</p>';
                    }
                    die($errMess . $checkout->snippet);
                }
                
                // Show checkout
                $this->displayCheckout();
            } else {
                // No cart or empty cart
                throw new Exception($this->module->l('Your cart is empty.', 'pconepage'));
            }
        } catch (Exception $ex) {
            // Log error message
            PaysonCheckout2::paysonAddLog('Checkout error: ' . $ex->getMessage(), 2);

            // Replace checkout snippet with error message
            $this->context->smarty->assign('payson_checkout', $ex->getMessage());

            // If AJAX return error message
            if (Tools::getIsset('pco_update')) {
                die('<p class="warning">' . $ex->getMessage() . '</p>');
            }
            
            // Show checkout
            $this->displayCheckout();
        }
    }

    protected function getCheckout($payson, $paysonApi, $customer, $cartCurrency, $address)
    {
        // Get or create checkout
        $newCheckout = false;
        $checkoutId = $this->context->cookie->paysonCheckoutId;
        if ($checkoutId && $checkoutId != null) {
            // Get existing checkout
            $checkout = $paysonApi->GetCheckout($checkoutId);
            PaysonCheckout2::paysonAddLog('Got existing checkout with ID: ' . $checkout->id);
        } else {
            // Create a new checkout
            $checkout = $paysonApi->CreateGetCheckout($payson->createPaysonCheckout($customer, $this->context->cart, $payson, $cartCurrency, $this->context->language->id, $address));
            // Save checkout ID in cookie
            $this->context->cookie->__set('paysonCheckoutId', $checkout->id);
            // Save data in Payson order table
            $payson->createPaysonOrderEvent($checkout->id, $this->context->cart->id);
            PaysonCheckout2::paysonAddLog('Created new checkout with ID: ' . $checkout->id);
            $newCheckout = true;
        }
        
        return array('checkout' => $checkout, 'newcheckout' => $newCheckout);
    }
    
    protected function displayCheckout()
    {
        $this->setTemplate('payment.tpl');
    }
    
//    protected function getCheckoutSession()
//    {
//        $deliveryOptionsFinder = new DeliveryOptionsFinder($this->context, $this->getTranslator(), $this->objectPresenter, new PriceFormatter());
//
//        $session = new CheckoutSession($this->context, $deliveryOptionsFinder);
//
//        return $session;
//    }

    protected function validateDeliveryOption($delivery_option)
    {
        if (!is_array($delivery_option)) {
            return false;
        }

        foreach ($delivery_option as $option) {
            if (!preg_match('/(\d+,)?\d+/', $option)) {
                return false;
            }
        }

        return true;
    }

//    protected function updateMessage($messageContent, $cart)
//    {
//        if ($messageContent) {
//            if (!Validate::isMessage($messageContent)) {
//                return false;
//            } elseif ($oldMessage = Message::getMessageByCartId((int) ($cart->id))) {
//                $message = new Message((int) ($oldMessage['id_message']));
//                $message->message = $messageContent;
//                $message->update();
//            } else {
//                $message = new Message();
//                $message->message = $messageContent;
//                $message->id_cart = (int) ($cart->id);
//                $message->id_customer = (int) ($cart->id_customer);
//                $message->add();
//            }
//        } else {
//            if ($oldMessage = Message::getMessageByCartId((int) ($cart->id))) {
//                $message = new Message((int) ($oldMessage['id_message']));
//                $message->delete();
//            }
//        }
//
//        return true;
//    }

    protected function assignSummaryInformations()
    {
        $summary = $this->context->cart->getSummaryDetails();
        $customizedDatas = Product::getAllCustomizedDatas($this->context->cart->id);

        // override customization tax rate with real tax (tax rules)
        if ($customizedDatas) {
            foreach ($summary['products'] as &$productUpdate) {
                if (isset($productUpdate['id_product'])) {
                    $productId = (int) $productUpdate['id_product'];
                } else {
                    $productId = (int) $productUpdate['product_id'];
                }

                if (isset($productUpdate['id_product_attribute'])) {
                    $productAttributeId = (int) $productUpdate['id_product_attribute'];
                } else {
                    $productAttributeId = (int) $productUpdate['product_attribute_id'];
                }

                if (isset($customizedDatas[$productId][$productAttributeId])) {
                    $productUpdate['tax_rate'] = Tax::getProductTaxRate($productId, $this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
                }
            }
            Product::addCustomizationPrice($summary['products'], $customizedDatas);
        }

        $cart_product_context = Context::getContext()->cloneContext();
        foreach ($summary['products'] as $key => &$product) {
            // For older themes
            $product['quantity'] = $product['cart_quantity'];

            if ($cart_product_context->shop->id != $product['id_shop']) {
                $cart_product_context->shop = new Shop((int) $product['id_shop']);
            }
            $specific_price_output = null;
            $product['price_without_specific_price'] = Product::getPriceStatic($product['id_product'], !Product::getTaxCalculationMethod(), $product['id_product_attribute'], 2, null, false, false, 1, false, null, null, null, $specific_price_output, true, true, $cart_product_context);

            if (Product::getTaxCalculationMethod()) {
                $product['is_discounted'] = $product['price_without_specific_price'] != $product['price'];
            } else {
                $product['is_discounted'] = $product['price_without_specific_price'] != $product['price_wt'];
            }
        }

        // Get available cart rules and unset the cart rules already in the cart
        $available_cart_rules = CartRule::getCustomerCartRules($this->context->language->id, (isset($this->context->customer->id) ? $this->context->customer->id : 0), true, true, true, $this->context->cart);

        $cart_cart_rules = $this->context->cart->getCartRules();
        foreach ($available_cart_rules as $key => $available_cart_rule) {
            if (!$available_cart_rule['highlight'] || strpos($available_cart_rule['code'], 'BO_ORDER_') === 0) {
                unset($available_cart_rules[$key]);
                continue;
            }
            foreach ($cart_cart_rules as $cart_cart_rule) {
                if ($available_cart_rule['id_cart_rule'] == $cart_cart_rule['id_cart_rule']) {
                    unset($available_cart_rules[$key]);
                    continue 2;
                }
            }
        }

        $show_option_allow_separate_package = (!$this->context->cart->isAllProductsInStock(true) &&
                Configuration::get('PS_SHIP_WHEN_AVAILABLE'));

        $this->context->smarty->assign($summary);
        $this->context->smarty->assign(array(
            'token_cart' => Tools::getToken(false),
            'isVirtualCart' => $this->context->cart->isVirtualCart(),
            'productNumber' => $this->context->cart->nbProducts(),
            'voucherAllowed' => CartRule::isFeatureActive(),
            'shippingCost' => $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING),
            'shippingCostTaxExc' => $this->context->cart->getOrderTotal(false, Cart::ONLY_SHIPPING),
            'customizedDatas' => $customizedDatas,
            'CUSTOMIZE_FILE' => Product::CUSTOMIZE_FILE,
            'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
            'lastProductAdded' => $this->context->cart->getLastProduct(),
            'displayVouchers' => $available_cart_rules,
            'advanced_payment_api' => true,
            'currencySign' => $this->context->currency->sign,
            'currencyRate' => $this->context->currency->conversion_rate,
            'currencyFormat' => $this->context->currency->format,
            'currencyBlank' => $this->context->currency->blank,
            'show_option_allow_separate_package' => $show_option_allow_separate_package,
            'smallSize' => Image::getSize(ImageType::getFormatedName('small')),
        ));

        $this->context->smarty->assign(array(
            'HOOK_SHOPPING_CART' => Hook::exec('displayShoppingCartFooter', $summary),
            'HOOK_SHOPPING_CART_EXTRA' => Hook::exec('displayShoppingCart', $summary),
        ));
    }
}
