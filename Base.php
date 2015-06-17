<?php
    class BaseModule extends IPSModule
    { 
        public function __construct($InstanceID)
        {
            //Never delete this line!
            parent::__construct($InstanceID);
        }
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
        }
         
        /**
         * Set Value
         * @param integer $ID
         * @param type $Value
         */        
        protected function SetValue($ID, $Value)
        {
            if ( GetValue($ID) !== $Value ) { SetValue($ID, $Value); }
        }
        
        /**
         * Register Event Cyclic
         * @param string $Ident
         * @param string $Name
         * @param integer $DateType
         * @param integer $DateInterval
         * @param integer $DateDays
         * @param integer $DateDaysInterval
         * @param integer $TimeTyp
         * @param integer $TimeInterval
         * @param string $Content
         * @param integer $Position
         * @return integer ID
         */
        protected function RegisterEventCyclic($Ident, $Name, $DateType, $DateInterval, $DateDays, $DateDaysInterval, $TimeTyp, $TimeInterval, $Content = "<?\n\n//Autogenerated script\n\n?>", $Position = 0)
        {
            //search for already available events with proper ident
            $eid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
            
            //properly update eventID
            if ( $eid === false)
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
            IPS_SetHidden($eid, true);
            //IPS_SetReadOnly($eid, true);
            	
            IPS_SetEventCyclic($eid, $DateType, $DateInterval, $DateDays, $DateDaysInterval, $TimeTyp, $TimeInterval);      
            IPS_SetEventScript($eid, $Content);
            IPS_SetEventActive($eid, true);
            			
            return $eid;				
        }
        
        /**
         * Unregister Event
         * @param string $Ident
         */
        protected function UnregisterEvent($Ident)
        {
            //search for already available events with proper ident
            $eid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
            
            if(IPS_EventExists($eid)) 
            { 
                IPS_DeleteEvent($eid);
            }            
        }  
        
        /**
         * Register Variable By Parent
         * @param integer $ParentID
         * @param string $Ident
         * @param string $Name
         * @param integer $Type
         * @param string $Profile
         * @param integer $Position
         * @return integer ID
         * @throws Exception
         */
        protected function RegisterVariableByParent($ParentID, $Ident, $Name, $Type, $Profile = "", $Position = 0) 
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