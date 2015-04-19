<?php
    class SSHRemote extends IPSModule
    {
        public function __construct($InstanceID)
        {
            //Never delete this line!
            parent::__construct($InstanceID);
            
            //These lines are parsed on Symcon Startup or Instance creation
            //You can not use variables here. Just static values.
            $this->RegisterPropertyString("IPAddress", "192.168.1.1");
            $this->RegisterPropertyInteger("Port", 22);
            $this->RegisterPropertyString("MAC", "00:00:00:00:00:00");
            $this->RegisterPropertyString("Username", "");
            $this->RegisterPropertyString("Password", "");
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            
            //$this->RegisterScript("Script_Test", "Test", "<? //Do not delete or modify.\ninclude(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/SymconModules/SSHRemote/module.php\");\necho (new SSHRemote(".$this->InstanceID."))->exec(\"pwd\");");
            $this->RegisterScript("Script_Wakeup", "*** Wakeup ***", "<? //Do not delete or modify.\ninclude(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/SymconModules/SSHRemote/module.php\");\n(new SSHRemote(".$this->InstanceID."))->WakeOnLAN();");

            $this->RegisterVariableBoolean("IsOnline", "Online", "Switch");
            
            $this->RegisterEventCyclic("Event_Update", "Update", 0, 0, 0, 0, 2, 1, "include(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/SymconModules/SSHRemote/module.php\");\n(new SSHRemote(".$this->InstanceID."))->Update();");
        }
        
        /**
         * This function will be available automatically after the module is imported with the module control.
         * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
         *
         * XXX_XXXXX($id);
         *
        */
        
        private $ssh;
        
        public function Login($Output = 0)
        {
            include_once(__DIR__ . "/SSH2.php");
            include_once(__DIR__ . "/Crypt/RSA.php");            

            $User = $this->ReadPropertyString("Username");
            $Key =  $this->ReadPropertyString("Password");
            
            //$Key = new Crypt_RSA();
            //$Key->loadKey(file_get_contents('/home/andreas/.ssh/id_rsa'));
            
            $this->ssh = new Net_SSH2($this->ReadPropertyString("IPAddress"), $this->ReadPropertyInteger("Port"));
            $this->ssh->setTimeout(10);
            if ( $this->ssh->login($User, $Key) )
            {
                if ( $Output ) { echo "Login succeed with User: $User"; } else { return 1; }
            } 
            else
            {
                if ( $Output ) { echo "Login Failed!"; } else { return 0; }
            }            
        }
        
        public function exec($cmd)
        {
            if ( !$this->ssh )
            {
                $this->Login();
            } 
            return $this->ssh->exec($cmd);            
        }
        
        public function execsu($cmd)
        {
            if ( !$this->ssh )
            {
                $this->Login();
            }             
            $User = $this->ReadPropertyString("Username");            
            $this->ssh->read(":~$");
            $this->ssh->write("sudo ".$cmd."\n");
            $output = @$this->ssh->read("#[pP]assword[^:]*:|:~\$#", NET_SSH2_READ_REGEX);
            if ( preg_match("#[pP]assword[^:]*:#", $output) ) 
            {
                $this->ssh->write($this->ReadPropertyString("Password")."\n");
                $out = $this->ssh->read($User);
                return str_replace($User, "", $out);                
            }                                                 
        }
        
        public function WakeOnLAN($mac = "", $broadcast = "")
        {
            if ( $mac == "" ) 
            { 
                $mac = $this->ReadPropertyString("MAC");                
            }
            if ( $broadcast == "") 
            {
                $ip = explode(".", $this->ReadPropertyString("IPAddress"));
                $broadcast = "$ip[0].$ip[1].$ip[2].255";
            }
            $addr_byte = explode(":", $mac);
            $hw_addr = "";
            for ( $a=0; $a <6; $a++ ) { $hw_addr .= chr(hexdec($addr_byte[$a])); }    
            $msg = chr(255).chr(255).chr(255).chr(255).chr(255).chr(255);
            for ( $a = 1; $a <= 16; $a++ ) { $msg .= $hw_addr; }
            $s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ( $s ) 
            {
                // setting a broadcast option to socket:
                socket_set_option($s, 1, 6, TRUE);
                if ( socket_sendto($s, $msg, strlen($msg), 0, $broadcast, 7) ) 
                {
                    //echo "Magic Packet sent successfully!";
                    socket_close($s);
                    return TRUE;
                }
                else
                {
                    user_error("Error Magic Packet failed!", E_USER_ERROR);
                }
            }
            else
            {
                user_error("Error creating socket!", E_USER_ERROR);
                return FALSE;               
            }        
        }

        public function Update()
        {
            if ( $this->Login() )
            {
                if ( GetValue($this->GetIDForIdent("IsOnline")) != TRUE )
                {
                    SetValue($this->GetIDForIdent("IsOnline"), TRUE);
                }
            }
            else
            {
                if ( GetValue($this->GetIDForIdent("IsOnline")) != FALSE )
                {
                    SetValue($this->GetIDForIdent("IsOnline"), FALSE);
                }
            }
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
    }
?>