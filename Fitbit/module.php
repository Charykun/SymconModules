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
            
            if ( $this->ReadPropertyBoolean("Active") ) { $this->SetStatus(102); } else { $this->SetStatus(104); }
        }
        
        /**
         * Fitbit_Update();
         */
        public function Update()
        {            
            include_once(__DIR__ . "/lib/fitbitphp.php");
            $fitbit = new FitBitPHP("92d2d787ee41469ac638998cd92836d3", "e471aa7c8b19edc8c648b349be364199");
            return $fitbit;
        }        
    }