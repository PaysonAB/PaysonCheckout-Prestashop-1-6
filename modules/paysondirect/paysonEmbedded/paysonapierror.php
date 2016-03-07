<?php
namespace PaysonEmbedded{
    class PaysonApiError {
        protected $errorId = NULL;
        protected $message = NULL;
        protected $parameter = NULL;

        public function __construct($errorId, $message, $parameter = null) {
            $this->errorId = $errorId;
            $this->message = $message;
            $this->parameter = $parameter;
        }

        public function getMessage() {
            return $this->message;
        }

        public function getParameter() {
            return $this->parameter;
        }

        public function getErrorId() {
            return $this->errorId;
        }

        public function __toString() {
            return " ErrorId: " . $this->getErrorId() .
                   " Message: " . $this->getMessage() .
                   " Parameter: " . $this->getParameter();
        }

    }
}