<?php
namespace PaysonEmbedded {
    class PaysonMerchant {
        /** @var int $merchantId */
        private $merchantId;
        /** @var url $checkoutUri URI to the merchants checkout page.*/
        private $checkoutUri = NULL;
        /** @var url $confirmationUri URI to the merchants confirmation page. */
        private $confirmationUri;
        /** @var url $notificationUri Notification URI which receives CPR-status updates. */
        private $notificationUri;
        /** @var url $verificationUri Validation URI which is called to verify an order before it can be paid. */
        private $verificationUri = NULL;
        /** @var url $termsUri URI som leder till säljarens villkor. */
        private $termsUri;
        /** @var string $reference Merchants own reference of the checkout.*/
        private $reference = NULL;
        /** @var int $partnerId Partners unique identifier */
        private $partnerId = NULL;
        /** @var string $integrationInfo Information about the integration. */
        private $integrationInfo = NULL;


        public function __construct($merchantId, $checkoutUri, $confirmationUri, $notificationUri, $termsUri, $partnerId = NULL, $integrationInfo = ' PaysonEmbedded|1.0|NONE') {
            $this->merchantId = $merchantId;
            $this->checkoutUri = $checkoutUri;
            $this->confirmationUri = $confirmationUri;
            $this->notificationUri = $notificationUri;
            $this->termsUri = $termsUri;
            $this->partnerId = $partnerId;
            $this->integrationInfo = $integrationInfo;
        }
        
        public function setIntegrationInfo($integrationInfo){
            $this->integrationInfo = $integrationInfo;
        }
        
        public function getIntegrationInfo(){
            return $this->integrationInfo;
        }
        
        public function setVerificationUri($verificationUri){
            $this->verificationUri = $verificationUri;
        }
        
        public function getVerificationUri(){
            return $this->verificationUri;
        }
        
        public function setReference($reference){
            $this->reference = $reference;
        }
        
        public function getReference(){
            return $this->reference;
        }

        /**
        * Returns the object of this class
        * 
        * @return string
        * @uses get_object_vars Description
        */
        public function getMerchantObject(){
            return get_object_vars($this);      
        }
    }
}
