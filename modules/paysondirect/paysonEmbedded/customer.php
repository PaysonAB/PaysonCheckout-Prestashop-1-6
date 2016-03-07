<?php
namespace PaysonEmbedded{
     class Customer{
        /** @var string $city */
        private $city;
        /** @var string $country */
        private $country;
        /** @var int $identityNumber Date of birth YYMMDD (digits). */
        private $identityNumber;
        /** @var string $email */
        private $email;
        /** @var string $firstName */
        private $firstName;
        /** @var string $lastName */
        private $lastName;
        /** @var string $phone Phone number. */
        private $phone;
        /** @var string $postalCode Postal code. */
        private $postalCode;
        /** @var string $street Street address.*/
        private $street;
        /** @var string $type Type of customer ("business", "person" (default)).*/
        private $type;
        /** @var string $reference Customer reference used when purchase is made by a company..*/
        private $reference;

        public function __construct($firstName = Null, $lastName = Null,  $email = Null,  $phone = Null, $identityNumber = Null, $city = Null, $country = Null, $postalCode = Null, $street = Null, $reference = Null, $type = 'person'){
            $this->firstName = $firstName;
            $this->lastName = $lastName;
            $this->email = $email;
            $this->phone = $phone;
            $this->identityNumber = $identityNumber;
            $this->city = $city; 
            $this->country = $country;
            $this->postalCode = $postalCode;
            $this->street = $street;
            $this->reference = $reference;
            $this->type = $type;   
        }
        
        public function setCity($city){
            $this->city = $city;     
        }
        
        public function getCity(){
            return $this->city;
        }
        
        public function setCountry($country){
            $this->country = $country;
        }
        
        public function getCountry(){
            return $this->country;
        }
        
        public function setIdentityNumber($identityNumber){
            $this->identityNumber = $identityNumber;
        }

        public function getIdentityNumber(){
            return $this->identityNumber;
        }
        
        public function setEmail($mail){
            $this->email = $email;
        }
        
        public function getEmail(){
            return $this->email;
        }
        
        public function setFirstName($firstName){
            $this->firstName = $firstName;
        }
        
        public function getFirstName(){
            return $this->firstName;
        }
        
        public function setLastName($lastName){
            $this->lastName = $lastName;
        }
        
        public function getLastName(){
            return $this->lastName;
        }

        public function setPhone($phone){
            $this->phone = $phone;
        }
        
        public function getPhone(){
            return $this->phone;
        }
        
        public function setPostalCode($postalCode){
            $this->postalCode = $postalCode;
        }
        
        public function getPostalCode(){
            return $this->postalCode;
        }
        
        public function setStreet($street){
            $this->street = $street;
        }
        
        public function getStreet(){
            return $this->street;
        }
        
        public function setType($type){
            $this->type = $type;
        }
        
        public function gettype(){
            return $this->type;
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
        public function getCustomerObject(){
            return get_object_vars($this);   
        }
    }
}
