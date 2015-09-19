<?php
    require_once(__DIR__ . "/../Base.php"); 
    class Fitbit extends BaseModule
    {
        /**
         * Log Message
         * @param string $Message
         */
        protected function Log($Message)
        {
            IPS_LogMessage(__CLASS__, $Message);
        }
        
        /**
         * Create
         */        
        public function Create()
        {
            //Never delete this line!
            parent::Create();
            
            $this->RegisterPropertyBoolean("Active", false);       
        }
        
        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();             
            
            if ( $this->ReadProperty("Active") ) { $this->SetStatus(102); } else { $this->SetStatus(104); }
        }
        
        /**
         * Fitbit_Update();
         */
        public function Update()
        {            
            
        }        
    }