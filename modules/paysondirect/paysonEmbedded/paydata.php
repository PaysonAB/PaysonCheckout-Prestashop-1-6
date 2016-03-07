<?php
namespace PaysonEmbedded{
    require_once "orderitem.php";

    class PayData {
        /** @var string $localeCode Local code of the order ("SV", "EN", "FI", "DK", "NO")*/
        private $localeCode = NULL;
        /** @var string $currency Currency of the order ("sek", "eur"). */
        private $currency = NULL;
        /** @var array $items An array with objects of the order items*/
        public $items = array();
        /** @var int $billingFrequency Only used if the payment is a recurring payment. */
        private $billingFrequency = NULL;
        /** @var string $billingPeriod Can be used to create recurring payments("monthly", "weekly", "daily", "once" (default)). */
        private $billingPeriod = 'once';

        public function __construct() {

        }

        public function setCurrencyCode($currencyCode) {
            $this->currency = CurrencyCode::ConstantToString($currencyCode);
        }

        public function setLocaleCode($localeCode = "EN"){
            $this->localeCode = LocaleCode::ConstantToString($localeCode);
        }

        public function setItems($items) { 
            
            if(!($items instanceof OrderItem))
                throw new PaysonApiException("Parameter must be an object of class Item");

            $this->items[] = $items->getItem();
        }

        public function setOrderItems($items) {
            if (!is_array($items))
                throw new PaysonApiException("Parameter must be an array of OrderItems");

            foreach ($items as $item) {
                if ($item instanceof OrderItem){
                    $this->items[] = $item->getItem();       
                }else {
                  throw new PaysonApiException("Parameter must be an array of OrderItems");  
                }
            }
        }

        public function getItems(){
            return $this->items;
        }
        
        public function setBillingFrequency($billingFrequency){
            $this->billingFrequency = $billingFrequency;
        }
        
        public function getBillingFrequency(){
            return $this->billingFrequency;
        }
        
        public function setBillingPeriod($billingPeriod){
            $this->billingPeriod = $billingPeriod;
        }
        
        public function getBillingPeriod(){
            return $this->billingPeriod;
        }

        /**
        * Returns the object of this class
        * 
        * @return string
        * @uses get_object_vars Description
        */
        public function getPaydata(){  
            return get_object_vars($this);  
        }
    }
}