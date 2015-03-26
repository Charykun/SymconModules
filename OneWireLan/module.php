<?
    class OneWireLan extends IPSModule
    {
        public function __construct($InstanceID)
        {
            //Never delete this line!
            parent::__construct($InstanceID);
            
            //These lines are parsed on Symcon Startup or Instance creation
            //You can not use variables here. Just static values.
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
        }
        
        /**
         * This function will be available automatically after the module is imported with the module control.
         * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
         *
         * XXX_XXXXX($id);
         *
        */
        public function Update()
        {
            
        }
    }
?>
