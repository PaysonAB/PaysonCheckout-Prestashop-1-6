<?php
namespace PaysonEmbedded{
    class OrderItem {
        /** @var float $discountRate Discount rate of the article (Decimal number (0.00-1.00)). */
        private $discountRate;
        /** @var float $creditedAmount Credited amount (Decimal number (with two decimals)). */
        private $creditedAmount;
        /** @var int $ean European Article Number (13 digits). */
        private $ean;
        /** @var url $imageUri An URI to an image of the article. */
        private $imageUri;
        /** @var string $name Name of the article. */
        private $name;
        /** @var float $unitPrice Unit price of the article (including tax). */
        private $unitPrice ;
        /** @var int $quantity  Quantity of the article. */
        private $quantity;
        /** @var float taxRate Tax rate of the article (0.00-1.00). */
        private $taxRate;
        /** @var string $reference Article reference, usually the article number. */
        private $reference;
        /** @var string $type Type of article ("Fee", "Physical" (default), "Service"). */
        private $type;
        /** @var url $uri URI to a the article page of the order item. */
        private $uri;
 
        /**
        * Constructs an OrderItem object
        * 
        * If any other value than description is provided all of them has to be entered
        * 
        * @param string $name Name of order item. Max 128 characters
        * @param float $unitPrice Unit price incl. VAT
        * @param int $quantity  Quantity of this item. Can have at most 2 decimal places
        * @param float $taxRate Tax value. Not actual percentage. For example, 25% has to be entered as 0.25
        * @param string $reference Sku of item
        */
        public function __construct($name, $unitPrice = null, $quantity = null, $taxRate = null, $reference = null, $type = 'physical') {
            $this->name = $name;
            $this->unitPrice = $unitPrice;
            $this->quantity = $quantity;
            $this->taxRate = $taxRate;
            $this->type = $type;
            $this->reference = $reference;
        }

        /**
        * Returns the object of this class
        * 
        * @return string
        * @uses get_object_vars Description
        */
        public function getItem(){
            return get_object_vars($this); 
        }

        /**
        * Returns the name of the order item
        * 
        * @return string
        */
        public function getName(){
            return $this->name;
        }
        
        public function setName($name){
            $this->name = $name;
        }

        public function setUnitPrice($unitPrice){
            $this->unitPrice = $unitPrice;
        }
        
        /**
        * Returns the price of the order item. . <strong>Note: </strong>Including vat
        * 
        * @return float
        */
        public function getUnitPrice(){
           return $this->unitPrice; 
        }

        public function setQuantity($quantity){
            $this->quantity = $quantity;
        }
        
        /**
        * Returns the quantity of this item
        * 
        * @return int
        */
        public function getQuantity(){
            return $this->quantity;
        }
        
        public function setTaxRate($taxRate){
            $this->TaxRate = $taxRate;
        }
        
        /** 
        * Returns the tax as a decimal value (0.00-1.00)
        * 
        * @return float Tax value
        */
        public function getTaxRate(){
            return $this->TaxRate;
        }
        
        public function setReference($reference){
            $this->reference = $reference;
        }
        
        /** 
        * Returns the article reference, usually the article number.
        * 
        * @return string
        */
        public function getReference(){
            $this->reference;
        }
        
        public function setType($type){
            $this->type = $type;
        }
        
        /**
        * Returns Type of article. of this item.
        * 
        * @return string
        */
        public function getType(){
            $this->type;
        }
        
        public function setDiscountRate($discountRate){
            $this->discountRate = $discountRate;
        }
        
        public function getDiscountRate(){
            return $this->discountRate;
        }
        
        public function setUri($uri){
            $this->uri = $uri;
        }
        
        /**
        * Returns the URI to a the article page of the order item.
        * 
        * @return uri
        */
        public function getUri(){
            $this->uri;
        }
        
        public function setCreditedAmount($creditedAmount){
            $this->creditedAmount = $creditedAmount;
        }
        
        /** 
        * Returns the Credited amount of the order item.
        * 
        * @return float
        */
        public function getCreditedAmount(){
            $this->creditedAmount;
        }
        
        public function setEan($ean){
            $this->ean = $ean;
        }
        
        /** 
        * Returns the European Article Number.
        * 
        * @return int
        */
        public function getEan(){
            $this->ean;
        }
        
        public function setImageUri($imageUri){
            $this->imageUri = $imageUri;
        }
        
        /**
        * Returns an URI to an image of the article.
        * 
        * @return uri
        */
        public function getImageUri(){
            $this->imageUri;
        }
    }
}
