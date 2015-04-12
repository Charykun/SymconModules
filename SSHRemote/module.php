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
            $this->RegisterPropertyString("Username", "");
            $this->RegisterPropertyString("Password", "");
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            
            $sid = $this->RegisterScript("SSHRemote_Test", "Test", "<? //Do not delete or modify.\ninclude(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/SymconModules/SSHRemote/module.php\");\necho (new SSHRemote(".$this->InstanceID."))->exec('pwd');");

        }
        
        /**
         * This function will be available automatically after the module is imported with the module control.
         * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
         *
         * XXX_XXXXX($id);
         *
        */
        
        private $ssh;
        
        public function Login($output = FALSE)
        {
            include_once('SSH2.php');
            include_once('Crypt/RSA.php');            

            $User = $this->ReadPropertyString("Username");
            $Key =  $this->ReadPropertyString("Password");
            
            //$Key = new Crypt_RSA();
            //$Key->loadKey(file_get_contents('/home/andreas/.ssh/id_rsa'));
            
            $this->ssh = new Net_SSH2($this->ReadPropertyString("IPAddress"), $this->ReadPropertyInteger("Port"));
            $this->ssh->setTimeout(10);
            if ( !$this->ssh->login($User, $Key) ) 
            {
                exit('Login Failed');
            }
            
            if($output)
            {
                echo 'Login succeed with User: '. $User;
            } 
            else 
            {
                return 'Login succeed with User: '. $User . "\n";               
            }
        }
        
        public function exec($cmd)
        {
            if(!$this->ssh)
            {
                $this->Login();
            } 
            return $this->ssh->exec($cmd);            
        }
        
        public function execsu($cmd)
        {
            if(!$this->ssh)
            {
                $this->Login();
            }             
            $User = $this->ReadPropertyString("Username");            
            $this->ssh->read(':~$');
            $this->ssh->write("sudo ".$cmd."\n");
            $output = @$this->ssh->read('#[pP]assword[^:]*:|:~\$#', NET_SSH2_READ_REGEX);
            if (preg_match('#[pP]assword[^:]*:#', $output)) {
                $this->ssh->write($this->ReadPropertyString("Password")."\n");
                $out = $this->ssh->read($User);
                return str_replace($User, '', $out);                
            }                                                 
        }
        
        public function WakeOnLAN($mac, $broadcast = '192.168.1.255')
        {
            $addr_byte = explode(':', $mac);
            $hw_addr = '';
            for ($a=0; $a <6; $a++) 
            {
                $hw_addr .= chr(hexdec($addr_byte[$a]));
            }    
            $msg = chr(255).chr(255).chr(255).chr(255).chr(255).chr(255);
            for ($a = 1; $a <= 16; $a++) 
            { 
                $msg .= $hw_addr; 
            }
            $s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ($s) 
            {
                // setting a broadcast option to socket:
                socket_set_option($s, 1, 6, TRUE);
                if(socket_sendto($s, $msg, strlen($msg), 0, $broadcast, 7)) 
                {
                    //echo "Magic Packet sent successfully!";
                    socket_close($s);
                    return TRUE;
                }
                else 
                {
                    echo 'Magic packet failed!';
                    return FALSE;
                }   
            }
            else
            {
                echo 'Error creating socket!';
                return FALSE;               
            }        
        }
    }
?>