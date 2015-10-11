<?php
    class OneWireLan extends IPSModule
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
            $this->RegisterPropertyInteger("DeviceType", 0);
            $this->RegisterPropertyString("IPAddress", "192.168.1.1");
            $this->RegisterPropertyInteger("Port", 4304);
            $this->RegisterPropertyInteger("Interval", 10);
            $this->CreateFloatProfile("mV", "", "", " mV", 0, 0, 0, 2);
        }

        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            
            if ( $this->ReadPropertyBoolean("Active") ) 
            { 
                $this->SetStatus(102);              
            } 
            else 
            { 
                $this->SetStatus(104); 
            }                        
        }
        
        public function Update()
        {
            $this->Log("Update ...");
            if ($this->ReadPropertyInteger("DeviceType") === 0)
            {               
                return $this->Update_0();
            }
            else
            {
                return $this->Update_1();
            }
        }
        
        private function Update_0()
        {
            $URL = "http://" . $this->ReadPropertyString("IPAddress") . "/details.xml";
            $headers = @get_headers($URL);
            if ( isset($headers) && count($headers) > 0 && ( strpos($headers[0], "200") === FALSE ) )
            {
                throw new Exception("Failed loading ... 1-Wire LAN unreachable!");
            }
            $xml = new SimpleXMLElement($URL, NULL, TRUE);
            $this->SetValue($this->RegisterVariableInteger("PollCount", "PollCount", "", -5), (int) $xml->PollCount);
            $this->SetValue($this->RegisterVariableFloat("VoltagePower", "VoltagePower", "~Volt", -4), (float) $xml->VoltagePower);
            $this->SetValue($this->RegisterVariableInteger("DevicesConnectedChannel1", "DevicesConnectedChannel1", "", -3), (int) $xml->DevicesConnectedChannel1);                
            $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel1"), "DataErrorsChannel1", "DataErrorsChannel1", 1), (int) $xml->DataErrorsChannel1);
            $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel1"), "VoltageChannel1", "VoltageChannel1", 2, "~Volt"), (float) $xml->VoltageChannel1);
            $this->SetValue($this->RegisterVariableInteger("DevicesConnectedChannel2", "DevicesConnectedChannel2", "", -2), (int) $xml->DevicesConnectedChannel2);                
            $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel2"), "DataErrorsChannel2", "DataErrorsChannel2", 1), (int) $xml->DataErrorsChannel2);
            $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel2"), "VoltageChannel2", "VoltageChannel2", 2, "~Volt"), (float) $xml->VoltageChannel2);
            $this->SetValue($this->RegisterVariableInteger("DevicesConnectedChannel3", "DevicesConnectedChannel3", "", -1), (int) $xml->DevicesConnectedChannel3);                
            $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel3"), "DataErrorsChannel3", "DataErrorsChannel3", 1), (int) $xml->DataErrorsChannel3);  
            $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel3"), "VoltageChannel3", "VoltageChannel3", 2, "~Volt"), (float) $xml->VoltageChannel3);                       
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
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent($VarIdent), $VarIdent . "_Vsense", "Vsense", 2, "mV"), (float) $Sensor->Vsense);  
            }            
        }
        
        private function Update_1()
        {
            require_once(__DIR__ . "/lib/ownet_php54fixed.php");
            //connect to owserver
            $ow=new OWNet("tcp://" . $this->ReadPropertyString("IPAddress") . ":" . $this->ReadPropertyInteger("Port"));
            if ($ow) 
            {
                $ow_dir=$ow->dir("/");
                if ($ow_dir) 
                {
                    $dirs=explode(",",$ow_dir['data_php']);
                    foreach ($dirs as $dev)
                    {
                        /* read standard device details */
                        $fam=$ow->read("$dev/family");
                        if (!$fam) continue;
                        $id=$ow->read("$dev/id");
                        $alias=$ow->get("$dev/alias");
                        $type=$ow->get("$dev/type");
                        $this->Log("FAM: $fam ID: $id ALIAS: $alias TYP: $type");
                        switch ($fam) 
                        {
                            case "28": case "10": case "22":
                                $temp=$ow->read("$dev/temperature",true);
                                if (strlen($temp)>0)
                                {
                                    $VarIdent = $type . "_" . $fam . "_" . $id . "_temp";
                                    $VarName = $type . " " . $fam . "." . $id . " " . $alias . " Temperature";
                                    $this->SetValue($this->RegisterVariableFloat($VarIdent, $VarName, "~Temperature"), (float) $temp);
                                }
                            break;
                            case "1D":
                                $counterA=$ow->read("$dev/counters.A",true);
                                if (strlen($counterA)>0)
                                {
                                    $VarIdent = $type . "_" . $fam . "_" . $id . "_countA";
                                    $VarName = $type . " " . $fam . "." . $id . " " . $alias . " Counters.A";
                                    $this->SetValue($this->RegisterVariableInteger($VarIdent, $VarName), (int) $counterA);
                                }    
                                $counterB=$ow->read("$dev/counters.B",true);
                                if (strlen($counterB)>0)
                                {
                                    $VarIdent = $type . "_" . $fam . "_" . $id . "_countB";
                                    $VarName = $type . " " . $fam . "." . $id . " " . $alias . " Counters.B";
                                    $this->SetValue($this->RegisterVariableInteger($VarIdent, $VarName), (int) $counterB);
                                }                               
                            break;
                            default:
                            break;
                        }
                    }
                }
            }
        }
        
        /**
         * SetValue
         * @param integer $ID
         * @param type $Value
         */
        private function SetValue($ID, $Value)
        {
            if ( GetValue($ID) !== $Value ) { SetValue($ID, $Value); }
        }
        
        /**
         * CreateFloatProfile
         * @param string $ProfileName
         * @param string $Icon
         * @param string $Präfix
         * @param string $Suffix
         * @param float $MinValue
         * @param float $MaxValue
         * @param integer $StepSize
         * @param integer $Digits
         */
        private function CreateFloatProfile($ProfileName, $Icon, $Präfix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
        {
            $Profile = IPS_VariableProfileExists($ProfileName);
            if ($Profile === FALSE)
            {
                IPS_CreateVariableProfile($ProfileName, 2);
                IPS_SetVariableProfileIcon($ProfileName,  $Icon);
                IPS_SetVariableProfileText($ProfileName, $Präfix, $Suffix);
                IPS_SetVariableProfileValues($ProfileName, $MinValue, $MaxValue, $StepSize);
                IPS_SetVariableProfileDigits($ProfileName, $Digits);
            }
        }
        
        /**
         * RegisterVariableByParent
         * @param integer $ParentID
         * @param string $Ident
         * @param string $Name
         * @param integer $Type
         * @param string $Profile
         * @param integer $Position
         * @return integer
         */
        private function RegisterVariableByParent($ParentID, $Ident, $Name, $Type, $Profile = "", $Position = 0) 
        {
            if($Profile !== "") 
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
            if($vid === 0)
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
    }