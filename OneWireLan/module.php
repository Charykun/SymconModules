<?php
    class OneWireLan extends IPSModule
    {
        public function __construct($InstanceID)
        {
            //Never delete this line!
            parent::__construct($InstanceID);
            
            //These lines are parsed on Symcon Startup or Instance creation
            //You can not use variables here. Just static values.
            $this->RegisterPropertyBoolean("Active", FALSE);
            $this->RegisterPropertyString("IPAddress", "192.168.1.1");
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            if ( isset($this->ReadPropertyBoolean("Active")) )
            {
                if ( $this->ReadPropertyBoolean("Active") )
                {
                    $this->RegisterEventCyclic("Event_Update", "Update", 0, 0, 0, 0, 1, 10, "include(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/SymconModules/OneWireLan/module.php\");\n(new OneWireLan(".$this->InstanceID."))->Update();");
                }
                else
                {
                    $this->UnregisterEvent("Event_Update");
                }
            }               
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
        
        private function RegisterEventCyclic($Ident, $Name, $DateType, $DateInterval, $DateDays, $DateDaysInterval, $TimeTyp, $TimeInterval, $Content = "<?\n\n//Autogenerated script\n\n?>", $Position = 0)
        {
            //search for already available events with proper ident
            $eid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
            
            //properly update eventID
            if ( $eid === false )
            {
                $eid = 0;
            }
            
            //we need to create one
            if ( $eid === 0 )
            {
                $eid = IPS_CreateEvent(1);
            }
            
            //configure it
            IPS_SetParent($eid, $this->InstanceID);
            IPS_SetIdent($eid, $Ident);
            IPS_SetName($eid, $Name);
            IPS_SetPosition($eid, $Position);
            IPS_SetHidden($eid, TRUE);
            //IPS_SetReadOnly($eid, true);
            	
            IPS_SetEventCyclic($eid, $DateType, $DateInterval, $DateDays, $DateDaysInterval, $TimeTyp, $TimeInterval);      
            IPS_SetEventScript($eid, $Content);
            IPS_SetEventActive($eid, true);
            			
            return $eid;				
        }
        
        private function UnregisterEvent($Ident)
        {
            //search for already available events with proper ident
            $eid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
            
            if(IPS_EventExists($eid)) 
            { 
                IPS_DeleteEvent($eid);
            }            
        }        
    }
?>