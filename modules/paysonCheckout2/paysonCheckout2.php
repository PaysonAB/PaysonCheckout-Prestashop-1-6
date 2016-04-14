<?php

if (!isset($_SESSION)) {
    session_start();
}

//include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/payson/paysonapi.php');

class PaysonCheckout2 extends PaymentModule {

    private $_html = '';
    private $_postErrors = array();
    public $MODULE_VERSION;
    public $testMode;
    public $discount_applies;
    //public $paysonResponsR;
    private $checkoutId;

    public function __construct() {
        $this->name = 'paysonCheckout2';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0.4';
        $this->currencies = true;
        $this->author = 'Payson AB';
        $this->module_key = '94873fa691622bfefa41af2484650a2e';
        $this->currencies_mode = 'checkbox';
        $this->discount_applies = 0;

        $this->MODULE_VERSION = sprintf('payson_checkout2_prestashop|%s|%s', $this->version, _PS_VERSION_);
        $this->testMode = Configuration::get('PAYSONCHECKOUT2_MODE') == 'sandbox';

        parent::__construct();

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Paysoncheckout2.0');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
    }

    public function install() {
        include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/payson_api/def.payson.php');

        Db::getInstance()->execute($this->paysonCreateTransOrderEventsTableQuery($paysonDbTableOrderEembedded));

        $orderStates = Db::getInstance()->executeS("SELECT id_order_state FROM " . _DB_PREFIX_ . "order_state WHERE module_name='paysonCheckout2'");
        $paysonPaidId = '';

        if (!$orderStates) {
            $db = Db::getInstance();
            $db->insert("order_state", array(
                "invoice" => "1",
                "send_email" => "1",
                "module_name" => "paysonCheckout2",
                "color" => "Orange",
                "unremovable" => "1",
                "hidden" => "0",
                "logable" => "1",
                "delivery" => "0",
                "shipped" => "0",
                "paid" => "1",
                "deleted" => "0"));

            $paysonPaidId = $db->Insert_ID();

            $languages = $db->executeS("SELECT id_lang, iso_code FROM " . _DB_PREFIX_ . "lang WHERE iso_code IN('sv','en','fi')");

            foreach ($languages as $language) {
                switch ($language['iso_code']) {
                    case 'sv':


                        $db->insert('order_state_lang', array(
                            "id_order_state" => pSQL($paysonPaidId),
                            "id_lang" => pSQL($language['id_lang']),
                            "name" => "Betald med Payson Checkout 2.0",
                            "template" => "payment"
                        ));
                        break;

                    case 'en':

                        $db->insert('order_state_lang', array(
                            "id_order_state" => pSQL($paysonPaidId),
                            "id_lang" => pSQL($language['id_lang']),
                            "name" => "Paid with Payson Checkout 2.0",
                            "template" => "payment"
                        ));
                        break;

                    case 'fi':

                        $db->insert('order_state_lang', array(
                            "id_order_state" => pSQL($paysonPaidId),
                            "id_lang" => pSQL($language['id_lang']),
                            "name" => "Maksettu Payson Checkout 2.0",
                            "template" => "payment"
                        ));
                        break;
                }
            }

            // Add the payson logotype to the order status folder
            copy(_PS_MODULE_DIR_ . "paysonCheckout2/logo.gif", "../img/os/" . $paysonPaidId . ".gif");
        } else {
            foreach ($orderStates as $orderState) {
                $paysonPaidId = $orderState['id_order_state'];
                copy(_PS_MODULE_DIR_ . "paysonCheckout2/logo.gif", "../img/os/" . $paysonPaidId . ".gif");
            }
        }

        if (!parent::install()
                OR ! Configuration::updateValue("PAYSONCHECKOUT2_ORDER_STATE_PAID", $paysonPaidId)
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_MERCHANTID', '')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_APIKEY', '')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_SANDBOX_MERCHANTID', '4')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_SANDBOX_APIKEY', '2acab30d-fe50-426f-90d7-8c60a7eb31d4')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_MODE', 'sandbox')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_MODULE_VERSION', 'PAYSONCHECKOUT2-PRESTASHOP-' . $this->version)
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_RECEIPT', '0')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_LOGS', 'no')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_VERIFICATION', 'none')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_COLOR_SCHEME', 'gray')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH', '100')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE', '%')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT', '700')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE', 'px')
                OR ! Configuration::updateValue('PAYSONCHECKOUT2_REQUEST_PHONE', '0')
                OR ! $this->registerHook('payment')
                OR ! $this->registerHook('paymentReturn'))
            return false;
        return true;
    }

    public function uninstall() {

        return (parent::uninstall() AND
                Configuration::deleteByName('PAYSONCHECKOUT2_MERCHANTID') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_APIKEY') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_SANDBOX_MERCHANTID') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_SANDBOX_APIKEY') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_MODE') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_MODULE_VERSION') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_RECEIPT') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_LOGS') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_VERIFICATION') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_COLOR_SCHEME') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_REQUEST_PHONE') AND
                Configuration::deleteByName('paysonpay') AND
                Configuration::deleteByName('PAYSONCHECKOUT2_INVOICE_ENABLED'));
    }

    public function getContent() {
        $this->_html = '<h2>' . $this->l('Payson') . '</h2>';
        if (isset($_POST['submitPayson'])) {
            if (Configuration::get('PAYSONCHECKOUT2_MODE') != 'sandbox') {
                if (empty($_POST['APIKEY']))
                    $this->_postErrors[] = $this->l('Payson API-Key is required.');
                if (empty($_POST['merchantid']))
                    $this->_postErrors[] = $this->l('Payson Merchant Id is required.');
            }

            $mode = Tools::getValue('payson_mode');
            if ($mode == 'real' ? 'real' : 'sandbox')
                Configuration::updateValue('PAYSONCHECKOUT2_MODE', $mode);

            $verification = Tools::getValue('payson_verification');
            if ($verification == 'bankid' ? 'bankid' : 'none')
                Configuration::updateValue('PAYSONCHECKOUT2_VERIFICATION', $verification);

            $colorScheme = Tools::getValue('payson_color_scheme');
            Configuration::updateValue('PAYSONCHECKOUT2_COLOR_SCHEME', $colorScheme);

            $logPayson = Tools::getValue('payson_log');
            if ($logPayson == 'yes' ? 'yes' : 'no')
                Configuration::updateValue('PAYSONCHECKOUT2_LOGS', $logPayson);

            if (!sizeof($this->_postErrors)) {
                Configuration::updateValue('PAYSONCHECKOUT2_MERCHANTID', intval($_POST['merchantid']));
                Configuration::updateValue('PAYSONCHECKOUT2_APIKEY', strval($_POST['apikey']));
                Configuration::updateValue('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH', strval($_POST['iframeSizeWidth']));
                Configuration::updateValue('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE', strval($_POST['iframeSizeWidthType']));
                Configuration::updateValue('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT', strval($_POST['iframeSizeHeight']));
                Configuration::updateValue('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE', strval($_POST['iframeSizeHeightType']));

                if (!isset($_POST['enableReceipt']))
                    Configuration::updateValue('PAYSONCHECKOUT2_RECEIPT', '0');
                else
                    Configuration::updateValue('PAYSONCHECKOUT2_RECEIPT', strval($_POST['enableReceipt']));

                if (!isset($_POST['enableRequestPhone']))
                    Configuration::updateValue('PAYSONCHECKOUT2_REQUEST_PHONE', '0');
                else
                    Configuration::updateValue('PAYSONCHECKOUT2_REQUEST_PHONE', strval($_POST['enableRequestPhone']));

                $this->displayConf();
            } else
                $this->displayErrors();
        }

        $this->displayPayson();
        $this->displayFormSettings();
        return $this->_html;
    }

    public function displayConf() {
        $this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="' . $this->l('Confirmation') . '" />
			' . $this->l('Settings updated') . '
		</div>';
    }

    public function displayErrors() {
        $nbErrors = sizeof($this->_postErrors);
        $this->_html .= '
		<div class="alert error">
			<h3>' . ($nbErrors > 1 ? $this->l('There are') : $this->l('There is')) . ' ' . $nbErrors . ' ' . ($nbErrors > 1 ? $this->l('errors') : $this->l('error')) . '</h3>
			<ol>';
        foreach ($this->_postErrors AS $error)
            $this->_html .= '<li>' . $error . '</li>';
        $this->_html .= '
			</ol>
		</div>';
    }

    public function displayPayson() {
        global $cookie;
        include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/payson_api/def.payson.php');


        $this->_html .= '
		<img src="../modules/paysonCheckout2/payson.png" style="float:left; margin-right:15px;" /><br/>
		<b>' . $this->l('This module allows you to accept payments by Payson Checkout 2.0.') . '</b><br /><br />
		' . $this->l('You need to apply for and be cleared for payments by Payson before using this module.') . '
		<br /><br /><br />';
    }

    public function displayFormSettings() {

        $conf = Configuration::getMultiple(array(
                    'PAYSONCHECKOUT2_MERCHANTID',
                    'PAYSONCHECKOUT2_APIKEY',
                    'paysonpay',
                    'PAYSONCHECKOUT2_RECEIPT',
                    'PAYSONCHECKOUT2_REQUEST_PHONE',
                    'PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH',
                    'PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE',
                    'PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT',
                    'PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE'
        ));

        $payson_mode_text = 'Currently using ' . Configuration::get('PAYSONCHECKOUT2_MODE') . ' mode.';

        $merchantid = array_key_exists('merchantid', $_POST) ? $_POST['merchantid'] : (array_key_exists('PAYSONCHECKOUT2_MERCHANTID', $conf) ? $conf['PAYSONCHECKOUT2_MERCHANTID'] : '');
        $apikey = array_key_exists('apikey', $_POST) ? $_POST['apikey'] : (array_key_exists('PAYSONCHECKOUT2_APIKEY', $conf) ? $conf['PAYSONCHECKOUT2_APIKEY'] : '');
        $iframeSizeWidth = array_key_exists('iframeSizeWidth', $_POST) ? $_POST['iframeSizeWidth'] : (array_key_exists('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH', $conf) ? $conf['PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH'] : '');
        $iframeSizeWidthType = array_key_exists('iframeSizeWidthType', $_POST) ? $_POST['iframeSizeWidthType'] : (array_key_exists('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE', $conf) ? $conf['PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE'] : '');
        $iframeSizeHeight = array_key_exists('iframeSizeHeight', $_POST) ? $_POST['iframeSizeHeight'] : (array_key_exists('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT', $conf) ? $conf['PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT'] : '');
        $iframeSizeHeightType = array_key_exists('iframeSizeHeightType', $_POST) ? $_POST['iframeSizeHeightType'] : (array_key_exists('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE', $conf) ? $conf['PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE'] : '');

        $enableReceipt = array_key_exists('enableReceipt', $_POST) ? $_POST['enableReceipt'] : (array_key_exists('PAYSONCHECKOUT2_RECEIPT', $conf) ? $conf['PAYSONCHECKOUT2_RECEIPT'] : '0');
        $enableRequestPhone = array_key_exists('enableRequestPhone', $_POST) ? $_POST['enableRequestPhone'] : (array_key_exists('PAYSONCHECKOUT2_REQUEST_PHONE', $conf) ? $conf['PAYSONCHECKOUT2_REQUEST_PHONE'] : '0');
        $this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" style="clear: both;">
		<fieldset>
		    <legend><img src="../img/admin/contact.gif" />' . $this->l('Settings') . '</legend>
	
                    <div class="warn">
                        ' . $this->l('Module version: ') . $this->version . '
                    </div><br /><br />
                    
                    ' . $this->l('Select the mode (Real or Sandbox).') . '<br />
                    ' . $this->l('Mode:    ') . '
                    <select name="payson_mode">
                                    <option value="real"' . (Configuration::get('PAYSONCHECKOUT2_MODE') == 'real' ? ' selected="selected"' : '') . '>' . $this->l('Real') . '&nbsp;&nbsp;</option>
                                    <option value="sandbox"' . (Configuration::get('PAYSONCHECKOUT2_MODE') == 'sandbox' ? ' selected="selected"' : '') . '>' . $this->l('Sandbox') . '&nbsp;&nbsp;</option>
                    </select><br />
                    <strong>' . $this->l($payson_mode_text) . '</strong><br /><br />

                    ' . $this->l('Enter your merchant id for Payson Checkout 2.0') . '<br />
                    ' . $this->l('merchant id:    ') . '
                    <input type="text" size="45" name="merchantid" value="' . htmlentities($merchantid, ENT_COMPAT, 'UTF-8') . '" /><br /><br />

                    ' . $this->l('Enter your API-Key for Payson Checkout 2.0') . '<br />
                    ' . $this->l('API-Key:    ') . '
                    <input type="text" size="45" name="apikey" value="' . htmlentities($apikey, ENT_COMPAT, 'UTF-8') . '" /><br /><br />

                    ' . $this->l('Show Receipt Page:    ') .
                '<input type="checkbox" size="45" name="enableReceipt" value="1" ' . ($enableReceipt == "1" ? "checked=checked" : '') . '" /><br /><br />

                    ' . $this->l('Troubleshoot response from Payson Checkout 2.0.') . '<br />
                    ' . $this->l('Logg:    ') . '

                    <select name="payson_log">
                        <option value="yes"' . (Configuration::get('PAYSONCHECKOUT2_LOGS') == 'yes' ? ' selected="selected"' : '') . '>' . $this->l('Yes') . '&nbsp;&nbsp;</option>
                        <option value="no"' . (Configuration::get('PAYSONCHECKOUT2_LOGS') == 'no' ? ' selected="selected"' : '') . '>' . $this->l('No') . '&nbsp;&nbsp;</option>
                    </select><br /><br />
                   
                    ' . $this->l('Graphical user interface:') . '<br /><br />
                        
                    ' . $this->l('Can be used to add extra customer verification') . '<br />
                    ' . $this->l('Verification:    ') . ' 
                    <select name="payson_verification">
                        <option value="none"' . (Configuration::get('PAYSONCHECKOUT2_VERIFICATION') == 'none' ? ' selected="selected"' : '') . '>' . $this->l('None') . '&nbsp;&nbsp;</option>
                        <!--<option value="bankid"' . (Configuration::get('PAYSONCHECKOUT2_VERIFICATION') == 'bankid' ? ' selected="selected"' : '') . '>' . $this->l('Bankid') . '&nbsp;&nbsp;</option>-->
                    </select><br /><br />
                                    ' .
                $this->l('Enable request phone:') .
                ' <input type="checkbox" size="45" name="enableRequestPhone" value="1" ' . ($enableRequestPhone == "1" ? "checked=checked" : '') . '" /><br /><br />

                    ' . $this->l('Color scheme:    ') . '			
                    <select name="payson_color_scheme">
                        <option value="blue"' . (Configuration::get('PAYSONCHECKOUT2_COLOR_SCHEME') == 'blue' ? ' selected="selected"' : '') . '>' . $this->l('blue') . '&nbsp;&nbsp;</option>
                        <option value="white"' . (Configuration::get('PAYSONCHECKOUT2_COLOR_SCHEME') == 'white' ? ' selected="selected"' : '') . '>' . $this->l('white') . '&nbsp;&nbsp;</option>
                        <option value="gray"' . (Configuration::get('PAYSONCHECKOUT2_COLOR_SCHEME') == 'gray' ? ' selected="selected"' : '') . '>' . $this->l('gray') . '&nbsp;&nbsp;</option>
                    </select><br /><br />

                    ' . $this->l('Enter the width of iframe.') . '<br />
                    ' . $this->l('Size:    ') . '
                    <input type="text" size="5" name="iframeSizeWidth" value="' . htmlentities($iframeSizeWidth, ENT_COMPAT, 'UTF-8') . '" />
                    <select name="iframeSizeWidthType"">
                        <option value="%"' . (Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE') == '%' ? ' selected="selected"' : '') . '>' . $this->l('%') . '&nbsp;&nbsp;</option>
                        <option value="px"' . (Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE') == 'px' ? ' selected="selected"' : '') . '>' . $this->l('px') . '&nbsp;&nbsp;</option>
                    </select><br /><br />

                    ' . $this->l('Enter the Height of iframe.') . '<br />
                    ' . $this->l('Size:    ') . '
                    <input type="text" size="5" name="iframeSizeHeight" value="' . htmlentities($iframeSizeHeight, ENT_COMPAT, 'UTF-8') . '" />   
                        
                    <select name="iframeSizeHeightType"">
                        <option value="%"' . (Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE') == '%' ? ' selected="selected"' : '') . '>' . $this->l('%') . '&nbsp;&nbsp;</option>
                        <option value="px"' . (Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE') == 'px' ? ' selected="selected"' : '') . '>' . $this->l('px') . '&nbsp;&nbsp;</option>
                    </select><br /><br />
                    
                    ' . $this->l('You can find your logs in Admin | Advanced Parameter -> Logs.') . '<br /><br />
                    <center><input type="submit" name="submitPayson" value="' . $this->l('Update settings') . '" class="button" /></center>
		</fieldset>
		</form><br /><br />
		<fieldset class="width3">
			<legend><img src="../img/admin/warning.gif" />' . $this->l('Information') . '</legend>'
                . $this->l('Note that Payson only accept SEK and EUR.') . '<br />
		</fieldset>';
    }

    public function hookPayment($params) {
        global $smarty;
        if (!$this->active)
            return;
        if (!$this->_checkCurrency($params['cart']))
            return;

        return $this->display(__FILE__, 'paysonCheckout2.tpl');
    }

    public function hookPaymentReturn($params) {
        if (!$this->active)
            return;

        return $this->display(__FILE__, 'confirmation.tpl');
    }

    public function getL($key) {
        include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/payson_api/def.payson.php');

        $translations = array(
            'Your seller e-mail' => $this->l('Your seller e-mail'),
            'Your merchant id' => $this->l('Your merchant id'),
            'Your API-Key' => $this->l('Your API-Key'),
            'Custom message' => $this->l('Custom message'),
            'Update settings' => $this->l('Update settings'),
            'Information' => $this->l('Information'),
            'All PrestaShop currencies must be configured</b> inside Profile > Financial Information > Currency balances' => $this->l('All PrestaShop currencies must be configured</b> inside Profile > Financial Information > Currency balances'),
            'Note that Payson only accept SEK and EUR.' => $this->l('Note that Payson only accept SEK and EUR.'),
            'Payson' => $this->l('Payson'),
            'Accepts payments by Payson' => $this->l('Accepts payments by Payson'),
            'Are you sure you want to delete your details?' => $this->l('Are you sure you want to delete your details?'),
            'Payson business e-mail address is required.' => $this->l('Payson business e-mail address is required.'),
            'Payson business must be an e-mail address.' => $this->l('Payson business must be an e-mail address.'),
            'Payson Merchant Id is required.' => $this->l('Payson Merchant Id is required.'),
            'Payson API-Key is required.' => $this->l('Payson API-Key is required.'),
            'Payson Merchant Id is required.' => $this->l('Payson Merchant Id is required.'),
            'mc_gross' => $this->l('Payson key \'mc_gross\' not specified, can\'t control amount paid.'),
            'payment' => $this->l('Payment: '),
            'cart' => $this->l('Cart not found'),
            'order' => $this->l('Order has already been placed'),
            'transaction' => $this->l('Payson Transaction ID: '),
            'Payson error: (invalid customer)' => $this->l('Payson error: (invalid customer)'),
            'Payson error: (invalid address)' => $this->l('Payson error: (invalid address)'),
            'Your order is being send to Payson for payment. Please  wait' => $this->l('Din order behandlas av Payson, vänligen vänta')
        );
        return $translations[$key];
    }

    private function _checkCurrency($cart) {
        $currency_order = new Currency(intval($cart->id_currency));
        $currencies_module = $this->getCurrency();
        $currency_default = Configuration::get('PS_CURRENCY_DEFAULT');

        if (strtoupper($currency_order->iso_code) != 'SEK' && strtoupper($currency_order->iso_code) != 'EUR')
            return;

        if (is_array($currencies_module))
            foreach ($currencies_module AS $currency_module)
                if ($currency_order->id == $currency_module['id_currency'])
                    return true;
    }

    private function paysonCreateTransOrderEventsTableQuery($table_name) {
        return " CREATE TABLE IF NOT EXISTS " . _DB_PREFIX_ . $table_name . " (
	            `payson_embedded_id` int(11) auto_increment,
                `cart_id` int(15) NOT NULL,
				`order_id` int(15) DEFAULT NULL,
                `checkout_id` varchar(40) DEFAULT NULL,
                `purchase_id` varchar(50) DEFAULT NULL,
				`payment_status` varchar(20) DEFAULT NULL,
				`added` datetime DEFAULT NULL,
				`updated` datetime DEFAULT NULL,
				`sender_email` varchar(50) DEFAULT NULL,
				`currency_code` varchar(5) DEFAULT NULL,
				`tracking_id`  varchar(100) DEFAULT NULL,
				`type` varchar(50) DEFAULT NULL,
				`shippingAddress_name` varchar(50) DEFAULT NULL,
				`shippingAddress_lastname` varchar(50) DEFAULT NULL,
				`shippingAddress_street_address` varchar(60) DEFAULT NULL,
				`shippingAddress_postal_code` varchar(20) DEFAULT NULL,
				`shippingAddress_city` varchar(60) DEFAULT NULL,
				`shippingAddress_country` varchar(60) DEFAULT NULL,
				PRIMARY KEY  (`payson_embedded_id`)
	        ) ENGINE=MyISAM";
    }

    public function paysonApiError($error) {
        $error_code = '<html>
				<head>
                                    <script type="text/javascript"> 
                                        alert("' . $error . '");
                                        window.location="' . ('/index.php?controller=order') . '";
                                    </script>
				</head>
			</html>';
        echo $error_code;
        exit;
    }

    public function PaysonorderExists($purchaseid) {
        $result = (bool) Db::getInstance()->getValue('SELECT count(*) FROM `' . _DB_PREFIX_ . 'payson_embedded_order` WHERE `purchase_id` = ' . (int) $purchaseid);
        return $result;
    }

    public function cartExists($cartId) {
        $result = (bool) Db::getInstance()->getValue('SELECT count(*) FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_cart` = ' . (int) $cartId);
        return $result;
    }

    public function PaysonOrderEventsExists($cartId) {
        $result = Db::getInstance()->getValue('SELECT checkout_id FROM `' . _DB_PREFIX_ . 'payson_embedded_order` WHERE `cart_id` = ' . (int) $cartId);
        return $result;
    }

    public function getCheckoutIdPayson($cartId) {
        $result = Db::getInstance()->getRow('SELECT checkout_id FROM `' . _DB_PREFIX_ . 'payson_embedded_order` WHERE `cart_id` = ' . (int) $cartId . ' ORDER BY `added` DESC');
        if ($result['checkout_id'])
            return $result['checkout_id'];
        else
            return NULL;
    }

    public function getCartIdPayson($checkoutId) {
        $result = Db::getInstance()->getRow('SELECT cart_id FROM `' . _DB_PREFIX_ . 'payson_embedded_order` WHERE `checkout_id` = ' . $checkoutId . ' ORDER BY `added` DESC');
        if ($result['cart_id'])
            return $result['cart_id'];
        else
            return NULL;
    }

    /*
     * @return the object of PaysonApi
     * @disc check the current merchant_id and current api_key by multishop
     */

    public function getAPIInstanceMultiShop() {
        include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/paysonEmbedded/paysonapi.php');

        if ($this->testMode) {
            return new PaysonEmbedded\PaysonApi(trim(Configuration::get('PAYSONCHECKOUT2_SANDBOX_MERCHANTID')), trim(Configuration::get('PAYSONCHECKOUT2_SANDBOX_APIKEY')), TRUE);
        } else {
            return new PaysonEmbedded\PaysonApi(trim(Configuration::get('PAYSONCHECKOUT2_MERCHANTID')), trim(Configuration::get('PAYSONCHECKOUT2_APIKEY')), FALSE);
        }
    }

    public function getSnippetUrl($snippet) {
        $str = "url='";
        $url = explode($str, $snippet);
        $newStr = "'>";
        return explode($newStr, $url[1]);
    }

    private function returnCall($code) {
        $this->responseCode($code);
        exit();
    }

    private function responseCode($code) {
        return var_dump(http_response_code($code));
    }

    public function CreateOrder($cart_id, $checkouId, $ReturnCallUrl = Null) {
        include_once(dirname(__FILE__) . '/../../config/config.inc.php');
        include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/paysonEmbedded/paysonapi.php');

        if (Configuration::get('PAYSONCHECKOUT2_LOGS') == 'yes') {
            PrestaShopLogger::addLog($ReturnCallUrl, 1, NULL, NULL, NULL, true);
        }

        $cartIdTemp = $ReturnCallUrl == 'ipnCall' ? $this->getCartIdPayson($checkouId) : $cart_id;
        $cart = new Cart($cartIdTemp);
        $customer = new Customer($cart->id_customer);

        if (($cart->id_customer == 0 OR $cart->id_address_delivery == 0 OR $cart->id_address_invoice == 0 OR ! $this->active) && ($ReturnCallUrl != 'ipnCall'))
            Tools::redirect('index.php?controller=order&step=1');

        if ((!Validate::isLoadedObject($customer)) && ($ReturnCallUrl != 'ipnCall'))
            Tools::redirect('index.php?controller=order&step=1');

        $callPaysonApi = $this->getAPIInstanceMultiShop();

        if ((bool) $cart->OrderExists() != 1) {

            try {

                $checkout = $callPaysonApi->GetCheckout($this->getCheckoutIdPayson($cart->id));
                $currency = new Currency($cart->id_currency);

                $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
                switch ($checkout->status) {
                    case "created":           //by Cancel
                        Tools::redirect('index.php?controller=order&step=1');
                        break;
                    case "readyToShip":
                        //$checkout->order;
                        $comment = "Checkout ID: " . $checkout->id . "\n";
                        $comment .= "Payson status: " . $checkout->status . "\n";
                        $comment .= $this->l('Paid Cart Id:  ') . $cartIdTemp . "\n";
                        $this->testMode ? $comment .= $this->l('Payment mode:  ') . 'TEST MODE' : '';

                        $address = new Address(intval($cart->id_address_delivery));
                        $address->firstname = $checkout->customer->firstName;
                        $address->lastname = $checkout->customer->lastName;
                        $address->address1 = $checkout->customer->street;
                        $address->address2 = '';
                        $address->city = $checkout->customer->city;
                        $address->postcode = $checkout->customer->postalCode;
                        $address->country = $checkout->customer->countryCode;
                        $address->id_customer = $cart->id_customer;
                        $address->alias = "Payson account address";
                        $address->update();

                        if ($this->PaysonorderExists($checkout->id)) {
                            $this->validateOrder((int) $cart->id, Configuration::get("PAYSONCHECKOUT2_ORDER_STATE_PAID"), $total, $this->displayName, $comment . '<br />', array(), (int) $currency->id, false, $customer->secure_key);
                            $this->updatePaysonOrderEvents($checkout, $cart_id);
                        }
                        if ($checkout->id != Null AND $checkout->status == 'readyToShip') {

                            $embeddedUrl = $this->getSnippetUrl($checkout->snippet);
                            $ReturnCallUrl == 'ipnCall' ? $this->returnCall(200) : Tools::redirect(Context::getContext()->link->getModuleLink('paysoncheckout2', 'payment', array('checkoutId' => $checkout->id, 'width' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH'), 'width_type' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE'), 'height' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT'), 'height_type' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE'), 'snippetUrl' => $embeddedUrl[0])));
                        }
                        break;
                    case "readyToPay":
                        if ($checkout->id != Null) {
                            $embeddedUrl = $this->getSnippetUrl($checkout->snippet);
                            $ReturnCallUrl == 'ipnCall' ? $this->returnCall(200) : Tools::redirect(Context::getContext()->link->getModuleLink('paysoncheckout2', 'payment', array('checkoutId' => $checkout->id, 'width' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH'), 'width_type' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_WIDTH_TYPE'), 'height' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT'), 'height_type' => Configuration::get('PAYSONCHECKOUT2_IFRAME_SIZE_HEIGHT_TYPE'), 'snippetUrl' => $embeddedUrl[0])));
                        }
                        break;
                    case "denied":
                        $this->validateOrder((int) $cart->id, _PS_OS_CANCELED_, $checkout->order->totalPriceIncludingTax, $this->displayName, $comment . '<br />', array(), (int) $currency->id, false, $customer->secure_key);
                        $this->updatePaysonOrderEvents($checkout, $cart_id);
                        $this->paysonApiError($this->l('The payment was denied. Please try using a different payment method.'));
                        break;
                    case "canceled":
                        $this->updatePaysonOrderEvents($checkout, $cart_id);
                        $ReturnCallUrl == 'ipnCall' ? $this->returnCall(200) : Tools::redirect('index.php?controller=order&step=1');
                        break;
                    case "Expired":
                        $this->updatePaysonOrderEvents($checkout, $cart_id);
                        $ReturnCallUrl == 'ipnCall' ? $this->returnCall(200) : Tools::redirect('index.php?controller=order&step=1');
                        break;
                    default:
                        if (Configuration::get('PAYSONCHECKOUT2_LOGS') == 'yes') {
                            PrestaShopLogger::addLog('Status: ' . $checkout->status, 1, NULL, NULL, NULL, true);
                        }
                        $ReturnCallUrl == 'ipnCall' ? $this->returnCall(200) : $this->paysonApiError('Please try using a different payment method.');
                }
            } catch (Exception $e) {
                if (Configuration::get('PAYSONCHECKOUT2_LOGS') == 'yes') {
                    $message = '<Payson PrestaShop Checkout 2.0> ' . $e->getMessage();
                    PrestaShopLogger::addLog($message, 1, NULL, NULL, NULL, true);
                }

                $$this->paysonApiError('Please try using a different payment method.');
            }
        } else {
            if ($ReturnCallUrl == 'ipnCall') {
                $this->returnCall(200);
            }
            $order = Order::getOrderByCartId($cart->id);

            Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?id_cart=' . (int) $cart->id . '&id_module=' . $this->id . '&id_order=' . $this->currentOrder . '&key=' . $customer->secure_key);
        }
        if ($ReturnCallUrl == 'ipnCall') {
            $this->returnCall(200);
        }
    }

    /*
     * @return void
     * @param checkoutId
     * @param $currentCartId
     * @disc The function save the parameters in the database
     */

    public function createPaysonOrderEvents($checkoutId, $currentCartId = 0) {
        $result_add = Db::getInstance()->insert('payson_embedded_order', array(
            'cart_id' => (int) $currentCartId,
            'checkout_id' => $checkoutId,
            'purchase_id' => $checkoutId,
            'payment_status' => 'created',
            'added' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
                )
        );
    }

    /*
     * @return void
     * @param $paymentDetails
     * @param $currentCartId
     * @param $currentOrder
     * @disc The function update the parameters in the database
     */

    public function updatePaysonOrderEvents($paymentDetails, $currentCartId = 0, $currentOrder = 1) {
        $currentCartId = '';
        Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'payson_embedded_order` SET
            `order_id` = "' . $currentOrder . '",
            `payment_status` = "' . $paymentDetails->status . '",
            `updated` = NOW(),
            `sender_email` = "' . $paymentDetails->customer->email . '", 
            `currency_code` = "' . $paymentDetails->payData->currency . '",
            `tracking_id` = "",
            `type` = "embedded",
            `shippingAddress_name` = "' . $paymentDetails->customer->firstName . '",
            `shippingAddress_lastname` = "' . $paymentDetails->customer->lastName . '",
            `shippingAddress_street_address` = "' . $paymentDetails->customer->street . '",
            `shippingAddress_postal_code` = "' . $paymentDetails->customer->postalCode . '",
            `shippingAddress_city` = "' . $paymentDetails->customer->city . '",
            `shippingAddress_country` = "' . $paymentDetails->customer->countryCode . '"
            WHERE `checkout_id` = "' . $paymentDetails->id . '"'
        );
    }

    public function setCheckoutId($checkoutId) {
        $this->checkoutId = $checkoutId;
    }

    public function getCheckoutId() {
        return $this->checkoutId;
    }

    public function languagePayson($language) {
        switch (strtoupper($language)) {
            case "SE":
            case "SV":
                return "SV";
            case "FI":
                return "FI";
            default:
                return "EN";
        }
    }

}

//end class
?>