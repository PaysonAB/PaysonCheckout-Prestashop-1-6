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

class PaysonCheckout2ConfirmationModuleFrontController extends ModuleFrontController
{
    
    public $display_column_left = false;
    public $display_column_right = false;
    
    public function init()
    {
        parent::init();

        PaysonCheckout2::paysonAddLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *');
        PaysonCheckout2::paysonAddLog('Call Type: ' . Tools::getValue('call'));
        
        $cartId = (int) Tools::getValue('id_cart');
        if (!isset($cartId) || $cartId < 1 || $cartId == null) {
            PaysonCheckout2::paysonAddLog('No cart ID.', 2);
            Tools::redirect('index.php');
        }

        require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
        $payson = new PaysonCheckout2();
        
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
                    PaysonCheckout2::paysonAddLog('No checkout ID, redirect.', 2);
                    Tools::redirect('index.php');
                }
            }
        }

        $cart = new Cart($cartId);

        if (!$cart->checkQuantities()) {
            Tools::redirect('order.php?step=3');
        }

        $paysonApi = $payson->getPaysonApiInstance();
        
        $checkout = $paysonApi->GetCheckout($checkoutId);

        PaysonCheckout2::paysonAddLog('Cart ID: ' . $cart->id);
        PaysonCheckout2::paysonAddLog('Cart delivery cost: ' . $cart->getOrderTotal(true, Cart::ONLY_SHIPPING));
        PaysonCheckout2::paysonAddLog('Cart total: ' . $cart->getOrderTotal(true, Cart::BOTH));
        PaysonCheckout2::paysonAddLog('Checkout ID: ' . $checkout->id);
        PaysonCheckout2::paysonAddLog('Checkout total: ' . $checkout->payData->totalPriceIncludingTax);
        PaysonCheckout2::paysonAddLog('Checkout Status: ' . $checkout->status);

        $orderCreated = false;
        
        $redirect = false;

        // For testing
        //$checkout->status = 'denied';
        
        switch ($checkout->status) {
            case 'readyToShip':
                if ($cart->OrderExists() == false) {
                    // Create PS order
                    $orderCreated = $payson->createOrderPS($cart->id, $checkout);
                    PaysonCheckout2::paysonAddLog('New order ID: ' . $orderCreated);
                } else {
                    PaysonCheckout2::paysonAddLog('Order already created.');
                    $redirect = 'index.php';
                }
                break;
            case 'readyToPay':
            case 'denied':
            case 'created':
            case 'canceled':
            case 'expired':
            case 'shipped':
                $redirect = 'order.php?step=3';
                break;
            default:
                $redirect = 'order.php?step=3';
        }

        // Delete checkout id cookie
        $this->context->cookie->__set('paysonCheckoutId', null);
        
        if ($redirect !== false) {
            $this->context->cookie->__set('validation_error', $this->module->l('Payment status was',  'confirmation') . ' "' . $checkout->status . '". ' . $this->module->l('Please try again.', 'confirmation'));
            $payson->updatePaysonOrderEvent($checkout, $cartId);
            PaysonCheckout2::paysonAddLog('Unable to display confirmation, redirecting to: ' . $redirect);
            PaysonCheckout2::paysonAddLog('Checkout Status: ' . $checkout->status);
            Tools::redirect($redirect);
        }

        $order = new Order((int) $orderCreated);
        $this->context->cookie->__set('id_customer', $order->id_customer);
        
        $this->context->smarty->assign('snippet', $checkout->snippet);
        $this->context->smarty->assign('HOOK_ORDER_CONFIRMATION', Hook::exec('OrderConfirmation', array('objOrder' => $order)));

        $this->setTemplate('payment.tpl');
    }
}
