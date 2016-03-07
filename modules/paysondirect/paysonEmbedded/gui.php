<?php
namespace PaysonEmbedded{
    class Gui{
        /** @var string $colorScheme Color scheme of the checkout snippet("white", "black", "blue" (default), "red"). */
        private $colorScheme;
        /** @var string $locale Used to change the language shown in the checkout snippet ("se", "fi", "en" (default)). */
        private $locale;
        /** @var string $verfication  Can be used to add extra customer verfication ("bankid", "none" (default)). */
        private $verfication;
        /** @var bool $requestPhone  Can be used to require the user to fill in his phone number. */
        private $requestPhone;
        
        public function __construct($locale = "sv", $colorScheme = "gray", $verfication = "none", $requestPhone = NULL){
            $this->colorScheme = $colorScheme;
            $this->locale = $locale; 
            $this->verfication = $verfication;
            $this->requestPhone = $requestPhone;
        }

        public function setColorScheme($colorScheme){
            $this->colorScheme = $colorScheme;
        }
        
        public function getColorScheme(){
            return $this->colorScheme;
        }
        
        public function setLocale($locale){
            $this->locale = $locale;
        }
        
        public function getLocale(){
            return $this->locale;
        }
        
        public function setVerfication($verification){
            $this->verification = $verification;
        }
        
        public function getVerfication(){
            return $this->verification;
        }
        
        public function setRequestPhone($requestPhone){
            $this->requestPhone = $requestPhone;
        }
        
        public function getRequestPhone(){
            return $this->requestPhone;
        }
        
        /**
        * Returns the object of this class
        * 
        * @return string
        * @uses get_object_vars Description
        */
        public function getGuiObject(){
            return get_object_vars($this);      
        }
    }
}

