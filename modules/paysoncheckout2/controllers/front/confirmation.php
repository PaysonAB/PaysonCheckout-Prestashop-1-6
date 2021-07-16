<?php
/**
 * 2019 Payson AB
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    Payson AB <integration@payson.se>
 *  @copyright 2019 Payson AB
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class PaysonCheckout2ConfirmationModuleFrontController extends ModuleFrontController
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
    
    public function init()
    {
        parent::init();

        PaysonCheckout2::paysonAddLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *');
        PaysonCheckout2::paysonAddLog('Query: ' . print_r($_REQUEST, true));
        
        try {
            require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
            $payson = new PaysonCheckout2();
            
            // Delete old messages
            $this->context->cookie->__set('validation_error', null);
            
            $cartId = (int) Tools::getValue('id_cart');
            if (!isset($cartId)) {
                throw new Exception($this->module->l('Unable to show confirmation.', 'confirmation') . ' ' . $this->module->l('Missing cart ID.', 'confirmation'));
            }

            if (isset($this->context->cookie->paysonCheckoutId) && $this->context->cookie->paysonCheckoutId != null) {
                // Get checkout ID from cookie
                $checkoutId = $this->context->cookie->paysonCheckoutId;
                PaysonCheckout2::paysonAddLog('Got checkout ID: ' . $checkoutId . ' from cookie.');
            } else {
                // Get checkout ID from query
                if (Tools::getIsset('checkout') && Tools::getValue('checkout') != null) {
                    $checkoutId = Tools::getValue('checkout');
                    PaysonCheckout2::paysonAddLog('Got checkout ID: ' . $checkoutId . ' from query.');
                } else {
                    // Get checkout ID from DB
                    $checkoutId = $payson->getPaysonOrderEventId($cartId);
                    if (isset($checkoutId) && $checkoutId != null) {
                        PaysonCheckout2::paysonAddLog('Got checkout ID: ' . $checkoutId . ' from DB.');
                    } else {
                        // Unable to get checkout ID
                        throw new Exception($this->module->l('Unable to show confirmation.', 'confirmation') . ' ' . $this->module->l('Missing checkout ID.', 'confirmation'));
                    }
                }
            }

            $paysonApi = $payson->getPaysonApiInstance();
            $checkoutClient = new \Payson\Payments\CheckoutClient($paysonApi);
            $checkout = $checkoutClient->get(array('id' => $checkoutId));
            
            $cart = new Cart($cartId);

            PaysonCheckout2::paysonAddLog('Cart ID: ' . $cart->id);
            PaysonCheckout2::paysonAddLog('Checkout ID: ' . $checkout['id']);
            PaysonCheckout2::paysonAddLog('Checkout Status: ' . $checkout['status']);
            
            if (Configuration::get('PAYSONCHECKOUT2_STOCK_VALIDATION') == 1) {
                PaysonCheckout2::paysonAddLog('Checking stock.');
                if ($checkout['status'] == 'readyToShip' && !$cart->checkQuantities()) {
                    PaysonCheckout2::paysonAddLog('A product has run out of stock between checkout and confirmation.');
                    // Only cancel payment if there's no order
                    if ($cart->OrderExists() == false) {
                        // Cancel Payson payment
                        $checkout['status'] = 'canceled';
                        $checkoutClient->update($checkout);
                        Logger::addLog('Canceled Payson payment due to out of stock, cart: ' . $cartId . ', checkout: ' . $checkoutId, 3, null, null, null, true);
                    }
                    // Delete checkout id cookie, force a new checkout
                    $this->context->cookie->__set('paysonCheckoutId', null);
                    // Redirect to checkout
                    Tools::redirect('order.php?step=3');
                }									
            }
              
            $newOrderId = false;
            $redirect = false;

            // For testing
            //$checkout->status = 'canceled';

            switch ($checkout['status']) {
                case 'readyToShip':
                    if ($cart->OrderExists() == false) {
                        // Create PS order
                        $newOrderId = $payson->createOrderPS($cart->id, $checkout);

                        // Set order id
                        $ref = $newOrderId;
                        
                        if (Configuration::get('PAYSONCHECKOUT2_SELLER_REF') == 'order_ref') {
                            // Load order
                            $order = new Order($newOrderId);

                            // Set reference
                            $ref = $order->reference;
                        }
                        $checkout['merchant']['reference'] = $ref;
                        //$checkoutClient->update($checkout);
                        
                        PaysonCheckout2::paysonAddLog('New order ID: ' . $newOrderId);
                    } else {
                        PaysonCheckout2::paysonAddLog('Order already created.');
                        $redirect = 'index.php';
                    }
                    break;
                case 'created':
                case 'readyToPay':
                case 'denied':
                    //$redirect = 'order.php?step=3';
                    $redirect = 'index.php?fc=module&module=paysoncheckout2&controller=pconepage';
                    $this->context->cookie->__set('validation_error', $this->module->l('Payment status was', 'confirmation') . ' "' . $checkout['status'] . '".');
                    break;
                case 'canceled':
                case 'expired':
                case 'shipped':
                    throw new Exception($this->module->l('Unable to show confirmation.', 'confirmation') . ' ' . $this->module->l('Payment status was', 'confirmation') . ' "' . $checkout['status'] . '".');
                default:
                    $redirect = 'order.php?step=3';
                    $this->context->cookie->__set('validation_error', $this->module->l('Payment status was', 'confirmation') . ' "' . $checkout['status'] . '".');
            }

            // Delete checkout id cookie
            $this->context->cookie->__set('paysonCheckoutId', null);

            if ($redirect !== false) {
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                PaysonCheckout2::paysonAddLog('Checkout Status: ' . $checkout['status']);
                PaysonCheckout2::paysonAddLog('Unable to display confirmation, redirecting to: ' . $redirect);
                Tools::redirect($redirect);
            }

            $order = new Order((int) $newOrderId);
            $this->context->cookie->__set('id_customer', $order->id_customer);

            $this->context->smarty->assign('payson_checkout', $checkout['snippet']);
            $this->context->smarty->assign('HOOK_ORDER_CONFIRMATION', Hook::exec('OrderConfirmation', array('objOrder' => $order)));

            $customer = new Customer((int) ($order->id_customer));
            
            if ((isset($this->context->cookie->alreadyLoggedIn) && $this->context->cookie->alreadyLoggedIn == null)) {
                $customer->mylogout();
            }
            
            $this->displayConfirmation($cart, $customer, $order, $checkout);
        } catch (Exception $ex) {
            // Log error message
            PaysonCheckout2::paysonAddLog('Checkout error: ' . $ex->getMessage(), 2);

            // Delete checkout id cookie
            $this->context->cookie->__set('paysonCheckoutId', null);
            
            // Replace checkout snippet with error message
            $this->context->smarty->assign('payson_checkout', $ex->getMessage());

            // Show confirmation
            $this->setTemplate('payment.tpl');
        }
    }
    
    protected function displayConfirmation($cart, $customer, $order, $checkout)
    {
        if (Configuration::get('PAYSONCHECKOUT2_PS_CONFIRMATION_PAGE') == 1) {
            if (!$this->context->customer->isLogged(true)) {
                // If it's a customer that's not logged in or a guest we need to set some values to prevent log in or order history from appearing instead of order confirmation
                $customer->logged = 0;
                $this->context->customer = $customer;
                $this->context->cookie->id_customer = (int)$customer->id;
                $this->context->cookie->customer_lastname = $customer->lastname;
                $this->context->cookie->customer_firstname = $customer->firstname;
                $this->context->cookie->email = $customer->email;
            }
            
            // Set cookie for hookPaymentReturn to use
            $this->context->cookie->__set('psnCheckoutId', $checkout['id']);
            
            Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?id_cart=' . (int) $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $customer->secure_key);
        } else {
            PaysonCheckout2::paysonAddLog('Will use Payson confirmation page');
            // Use Payson order confirmation page
            $this->setTemplate('payment.tpl');
        }
    }
}
