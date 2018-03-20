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

        PaysonCheckout2::paysonAddLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *', 1, null, null, null, true);
        PaysonCheckout2::paysonAddLog('Call Type: ' . Tools::getValue('call'), 1, null, null, null, true);
        
        $cartId = (int) Tools::getValue('id_cart');
        if (!isset($cartId)|| $cartId < 1 || $cartId == null) {
            PaysonCheckout2::paysonAddLog('No cart ID.', 2, null, null, null, true);
            Tools::redirect('index.php');
        }

        require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
        $payson = new PaysonCheckout2();
        
        if (isset($this->context->cookie->paysonCheckoutId) && $this->context->cookie->paysonCheckoutId != null) {
            // Get checkout ID from cookie
            $checkoutId = $this->context->cookie->paysonCheckoutId;
            PaysonCheckout2::paysonAddLog('Got checkout ID: ' . $checkoutId . ' from cookie.', 1, null, null, null, true);
        } else {
            // Get checkout ID from query
            if (Tools::getIsset('checkout') && Tools::getValue('checkout') != null) {
                $checkoutId = Tools::getValue('checkout');
                PaysonCheckout2::paysonAddLog('Got checkout ID: ' . $checkoutId . ' from query.', 1, null, null, null, true);
            } else {
                // Get checkout ID from DB
                $checkoutId = $payson->getPaysonOrderEventId($cartId);
                if (isset($checkoutId) && $checkoutId != null) {
                    PaysonCheckout2::paysonAddLog('Got checkout ID: ' . $checkoutId . ' from DB.', 1, null, null, null, true);
                } else {
                    // Unable to get checkout ID
                    PaysonCheckout2::paysonAddLog('No checkout ID, redirect.', 2, null, null, null, true);
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

        PaysonCheckout2::paysonAddLog('Cart ID: ' . $cart->id, 1, null, null, null, true);
        PaysonCheckout2::paysonAddLog('Cart delivery cost: ' . $cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 1, null, null, null, true);
        PaysonCheckout2::paysonAddLog('Cart total: ' . $cart->getOrderTotal(true, Cart::BOTH), 1, null, null, null, true);
        PaysonCheckout2::paysonAddLog('Checkout ID: ' . $checkout->id, 1, null, null, null, true);
        PaysonCheckout2::paysonAddLog('Checkout total: ' . $checkout->payData->totalPriceIncludingTax, 1, null, null, null, true);
        PaysonCheckout2::paysonAddLog('Checkout Status: ' . $checkout->status, 1, null, null, null, true);

        $orderCreated = false;

        // For testing
        //$checkout->status = 'expired';

        switch ($checkout->status) {
            case 'created':
                Tools::redirect('order.php?step=3');
                break;
            case 'readyToShip':
                if ($cart->OrderExists() == false) {
                    // Create PS order
                    $orderCreated = $payson->createOrderPS($cart->id, $checkout);
                    PaysonCheckout2::paysonAddLog('New order ID: ' . $orderCreated, 1, null, null, null, true);
                } else {
                    PaysonCheckout2::paysonAddLog('Order already created.', 1, null, null, null, true);
                    Tools::redirect('index.php');
                }
                break;
            case 'readyToPay':
                Tools::redirect('order.php?step=3');
                break;
            case 'denied':
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                $this->context->cookie->__set('validation_error', $this->l('The payment was denied. Please try using a different payment method.'));
                Tools::redirect('order.php?step=3');
                break;
            case 'canceled':
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                $this->context->cookie->__set('validation_error', $this->l('This order has been canceled. Please try again.'));
                Tools::redirect('index.php');
                break;
            case 'expired':
                $this->context->cookie->__set('paysonCheckoutId', null);
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                $this->context->cookie->__set('validation_error', $this->l('This order has expired. Please try again.'));
                Tools::redirect('index.php');
                break;
            case 'shipped':
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                Tools::redirect('index.php');
                break;
            default:
                PaysonCheckout2::paysonAddLog('Unknown Checkout Status: ' . $checkout->status, 2, null, null, null, true);
                //$this->context->cookie->__set('validation_error', $this->l('Unknown order status.'));
                Tools::redirect('index.php');
        }

        // Delete checkout id cookie
        $this->context->cookie->__set('paysonCheckoutId', null);

        $order = new Order((int) $orderCreated);
        $this->context->cookie->__set('id_customer', $order->id_customer);
        
        $this->context->smarty->assign('snippet', $checkout->snippet);
        $this->context->smarty->assign('HOOK_ORDER_CONFIRMATION', Hook::exec('OrderConfirmation', array('objOrder' => $order)));

        $this->setTemplate('payment.tpl');
    }
}
