<?php
    class OneWireLan extends IPSModule
    {
        public function __construct($InstanceID)
        {
            //Never delete this line!
            parent::__construct($InstanceID);
            
            //These lines are parsed on Symcon Startup or Instance creation
            //You can not use variables here. Just static values.
            $this->RegisterPropertyBoolean("Active", false);
            $this->RegisterPropertyString("IPAddress", "192.168.1.1");
            $this->RegisterPropertyInteger("Interval", 10);
            $this->CreateFloatProfile("mV", "", "", " mV", 0, 0, 0, 2);
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            
            $this->RegisterEventCyclic("Event_Update", "Update", 0, 0, 0, 0, 1, 1, "include(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/SymconModules/OneWireLan/module.php\");\n(new OneWireLan(".$this->InstanceID."))->Update();");              
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
            if ( $this->ReadPropertyBoolean("Active") )
            {
                IPS_SetEventCyclic($this->GetIDForIdent("Event_Update"), 0, 0, 0, 0, 1, $this->ReadPropertyInteger("Interval"));
                $URL = "http://" . $this->ReadPropertyString("IPAddress") . "/details.xml";
                $headers = @get_headers($URL);
                if ( isset($headers) && count($headers) > 0 && ( strpos($headers[0], "200") === FALSE ) )
                {
                    throw new Exception("Failed loading ... 1-Wire LAN unreachable!");
                }
                $xml = new SimpleXMLElement($URL, NULL, TRUE);
                $this->SetValue($this->RegisterVariableInteger("PollCount", "PollCount", "", -5), (int) $xml->PollCount);
                $this->SetValue($this->RegisterVariabeFloat("VoltagePower", "VoltagePower", "~Volt", -4), (float) $xml->VoltagePower);
                $this->SetValue($this->RegisterVariableInteger("DevicesConnectedChannel1", "DevicesConnectedChannel1", "", -3), (int) $xml->DevicesConnectedChannel1);                
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel1"), "DataErrorsChannel1", "DataErrorsChannel1", 1), (int) $xml->DataErrorsChannel1);
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel1"), "VoltageChannel1", "VoltageChannel1", 2, "~Volt"), (float) $xml->VoltageChannel1);
                $this->SetValue($this->RegisterVariableInteger("DevicesConnectedChannel2", "DevicesConnectedChannel2", "", -2), (int) $xml->DevicesConnectedChannel2);                
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel2"), "DataErrorsChannel2", "DataErrorsChannel2", 1), (int) $xml->DataErrorsChannel2);
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel2"), "VoltageChannel2", "VoltageChannel2", 2, "~Volt"), (float) $xml->VoltageChannel2);
                $this->SetValue($this->RegisterVariableInteger("DevicesConnectedChannel3", "DevicesConnectedChannel3", "", -1), (int) $xml->DevicesConnectedChannel3);                
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel3"), "DataErrorsChannel3", "DataErrorsChannel3", 1), (int) $xml->DataErrorsChannel3);  
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel3"), "VoltageChannel3", "VoltageChannel3", 2, "~Volt"), (float) $xml->VoltageChannel3);                
                //user_error("Active", E_USER_NOTICE);         
                foreach ($xml->owd_DS18B20 as $Sensor) 
                {
                    $VarIdent = "DS18B20_" . $Sensor->ROMId;
                    $this->SetValue($this->RegisterVariableInteger($VarIdent, $VarIdent), (int) $Sensor->Health);
                    $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent($VarIdent), $VarIdent . "_Temp", "Temperature", 2, "~Temperature"), (float) $Sensor->Temperature);  
                }
                foreach ($xml->owd_DS2438 as $Sensor) 
                {
                    $VarIdent = "DS2438_" . $Sensor->ROMId;
                    $this->SetValue($this->RegisterVariableInteger($VarIdent, $VarIdent), (int) $Sensor->Health);
                    $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent($VarIdent), $VarIdent . "_Temp", "Temperature", 2, "~Temperature"), (float) $Sensor->Temperature);  
                    $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent($VarIdent), $VarIdent . "_Vdd", "Vdd", 2, "~Volt"), (float) $Sensor->Vdd);  
                    $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent($VarIdent), $VarIdent . "_Vad", "Vad", 2, "~Volt"), (float) $Sensor->Vad);  
                    $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent($VarIdent), $VarIdent . "_Vsense", "Vsense", 2, "~mV"), (float) $Sensor->Vsense);  
                }
            }        
            else 
            {
                $this->UnregisterEvent("Event_Update");
            }
        }
        
        private function SetValue($ID, $Value)
        {
            if ( GetValue($ID) !== $Value ) { SetValue($ID, $Value); }
        }
        
        private function CreateFloatProfile($ProfileName, $Icon, $Präfix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
        {
            $Profile = IPS_GetVariableProfile($ProfileName);
            if ($Profile !== FALSE)
            {
                IPS_CreateVariableProfile($ProfileName, 2);
                IPS_SetVariableProfileIcon($ProfileName,  $Icon);
                IPS_SetVariableProfileText($ProfileName, $Präfix, $Suffix);
                IPS_SetVariableProfileValues($ProfileName, $MinValue, $MaxValue, $StepSize);
                IPS_SetVariableProfileDigits($ProfileName, $Digits);
            }
        }

        private function RegisterVariableByParent($ParentID, $Ident, $Name, $Type, $Profile = "", $Position = 0) 
        {
            if($Profile != "") 
            {
                //prefer system profiles
		if(IPS_VariableProfileExists("~".$Profile)) 
                {
                    $Profile = "~".$Profile;
		}
		if(!IPS_VariableProfileExists($Profile)) 
                {
                    throw new Exception("Profile with name ".$Profile." does not exist");
		}
            }
            //search for already available variables with proper ident
            $vid = @IPS_GetObjectIDByIdent($Ident, $ParentID);
            //properly update variableID
            if($vid === false) { $vid = 0; }
            //we have a variable with the proper ident. check if it fits
            if($vid > 0) 
            {
                //check if we really have a variable
                if(!IPS_VariableExists($vid)) { throw new Exception("Ident with name ".$Ident." is used for wrong object type"); } //bail out
		//check for type mismatch
		if(IPS_GetVariable($vid)["VariableType"] != $Type) 
                {
                    //mismatch detected. delete this one. we will create a new below
                    IPS_DeleteVariable($vid);
                    //this will ensure, that a new one is created
                    $vid = 0;
		}
            }
            //we need to create one
            if($vid == 0)
            {
                $vid = IPS_CreateVariable($Type);
		//configure it
		IPS_SetParent($vid, $ParentID);
		IPS_SetIdent($vid, $Ident);
		IPS_SetName($vid, $Name);
		IPS_SetPosition($vid, $Position);
		//IPS_SetReadOnly($vid, true);
            }
            //update variable profile. profiles may be changed in module development.
            //this update does not affect any custom profile choices
            IPS_SetVariableCustomProfile($vid, $Profile);
            return $vid;
	}
        
        private function RegisterEventCyclic($Ident, $Name, $DateType, $DateInterval, $DateDays, $DateDaysInterval, $TimeTyp, $TimeInterval, $Content = "<?\n\n//Autogenerated script\n\n?>", $Position = 0)
        {
            //search for already available events with proper ident
            $eid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
            
            //properly update eventID
            if ( $eid === FALSE )
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
            IPS_SetEventActive($eid, TRUE);
            			
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