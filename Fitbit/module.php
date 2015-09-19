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
         * Construct
         * @param integer $InstanceID
         */        
        public function __construct($InstanceID)
        {
            //Never delete this line!
            parent::__construct($InstanceID);
            
            //These lines are parsed on Symcon Startup or Instance creation
            //You can not use variables here. Just static values.
            $this->RegisterPropertyBoolean("Active", false);       
        }
        
        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();             
        }
        
        /**
         * Fitbit_Update();
         */
        public function Update()
        {            
            
        }        
    }