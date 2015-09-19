<?php
    require_once(__DIR__ . "/../Base.php"); 
    class Telegram extends BaseModule
    {     
        /**
         * Telegram Client
         * @var TelegramClient 
         */
        private $client;
        private $ids;

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
            //These lines are parsed on Symcon Startup or Instance creation
            //You can not use variables here. Just static values.
            $this->RegisterPropertyBoolean("Active", false);
            $this->client = $this->Telegram_Create_Client();
        }
        
        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            
            if ( $this->ReadPropertyBoolean("Active") ) { $this->SetStatus(102); } else { $this->SetStatus(104); }
            $this->RegisterScript("Script_Bot", "*** Auto Answer ***", "<?\n\$pattern = array(\n'(Uhrzeit)' => 'Es ist '. date('H:i', time()) . ' Uhr.',\n'(Temp)' => 'Die Aussentemperatur betraegt ' . (string)GetValue(35891) . ' C',\n);");                        
            //$this->RegisterEventCyclic("Event_Update", "Update", 0, 0, 0, 0, 1, 15, "Tg_Update(".$this->InstanceID.");");
        }
        
        /**
         * Tg_Update()
         */   
        public function Update()
        {           
            if ( $this->ReadPropertyBoolean("Active") )
            {    
                $this->client->start();
                if ( $this->client->getProcess()->isStarted() )
                {
                    //$this->Log('Started');                    
                    $this->client->getDialogList();
                    foreach ( $this->client->getContactList() as $Contact )
                    {
                        //$this->Log($Contact->first_name);   
                        if ( $Contact->first_name !== 'User') 
                        { 
                            $Ident = $Contact->first_name . ( ( isset( $Contact->last_name ) ) ? '_' . $Contact->last_name : '' ); 
                            $Name = $Contact->first_name . ( ( isset( $Contact->last_name ) ) ? ' ' . $Contact->last_name : '' ); 
                            $CInfo = $this->client->getContactInfo($Ident);
                            if ( $CInfo === NULL )
                            {                        
                                $this->SetValue($this->RegisterVariableInteger($Ident, $Name, 'Tg_Status'), 0);
                            }
                            else
                            {
                                if ( isset($CInfo->status) and $CInfo->status === 'offline' )
                                {
                                    $this->SetValue($this->RegisterVariableInteger($Ident, $Name, 'Tg_Status'), 1);
                                    $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent($Ident), 'lastonline', 'last Online', 1, '~UnixTimestamp'), (int) strtotime($CInfo->date . ' ' . $CInfo->time));
                                }
                                else 
                                {
                                    $this->SetValue($this->RegisterVariableInteger($Ident, $Name, 'Tg_Status'), 2);
                                }
                                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent($Ident), 'phone', 'Phone', 3), (string) $CInfo->phone);                            
                            }      
                            $lastID = $this->RegisterVariableByParent($this->GetIDForIdent($Ident), 'lastmsg', 'last Message', 3);
                            foreach ( $this->client->getHistory($Ident, 1) as $History )
                            {
                                if ( $History->direction === 'outgoing' )
                                {
                                    $this->SetValue($lastID, (string) '[' . $History->date . '] ' . $History->text);
                                    if ( $Name !== 'Telegram' )
                                    {                           
                                        //$this->Log($History->text);
                                        include(__DIR__ . "/../../../scripts/" . $this->GetIDForIdent('Script_Bot') . '.ips.php');                                        
                                        foreach ($pattern as $i => $value)
                                        {
                                            if (preg_match($i, $History->text))
                                            {
                                                $msg = $value;
                                                break;
                                            }
                                            else
                                            {
                                                $msg = '?';
                                            } 
                                        }                                                                                                  
                                        $this->SendMessage($Name, $msg);
                                    }
                                }
                            }
                            unset($Ident);
                            unset($Name);
                            unset($CInfo);
                            unset($lastID);
                        }    
                    }                    
                }
                else
                {
                    throw new Exception("Telegram Client can't started!");
                }
                $this->client->stop();                
            }
            else
            {
                //$this->UnregisterEvent("Event_Update");
                $this->client->stop();
            }
            //$this->Log('Stopped'); 
        }    
        
        /**
         * Send Message to User from Contactlist:
         * Tg_SendMessage('User', 'Message');
         * @param string $User
         * @param string $Message
         */
        public function SendMessage($User, $Message)
        {
            if ( is_string($User) and $User !== '' and is_string($Message) and $Message !== '' )
            {
                $User = str_replace(' ', '_', $User);
                $this->client->start();
                if ( $this->client->getProcess()->isStarted() )
                {
                    $this->client->getContactList();
                    $this->client->sendMessage(utf8_decode($User), utf8_decode($Message));
                }
                else
                {
                    throw new Exception("Telegram Client can't started!");
                }            
            }
            else
            {
                throw new Exception("Wrong or Empty Parameter!");
            }
            
        }
        
        /**
         * Add Contact to Contactlist:
         * Tg_AddContact('436760000000', 'Max', 'Mustermann');
         * @param string $Phone
         * @param string $First_Name
         * @param string $Last_Name
         */
        public function AddContact($Phone, $First_Name, $Last_Name)
        {
            if ( is_string($Phone) and $Phone !== '' and is_string($First_Name) and $First_Name !== '' and is_string($Last_Name) and $Last_Name !== '' )
            {
                $this->client->start();
                if ( $this->client->getProcess()->isStarted() )
                {
                    $this->client->getContactList();
                    $this->client->addContact($Phone, utf8_decode($First_Name), utf8_decode($Last_Name));
                }
                else
                {
                    throw new Exception("Telegram Client can't started!");
                }
            }
            else
            {
                throw new Exception("Wrong or Empty Parameter!");
            }
        }    
        
        public function DelContact($User)
        {
            if ( is_string($User) and $User !== '' )
            {
                $User = str_replace(' ', '_', $User);
                if ( @$this->GetIDForIdent($User) === FALSE )
                {
                    throw new Exception("Contact not found!");
                }
                $this->client->start();
                if ( $this->client->getProcess()->isStarted() )
                {
                    $this->client->getContactList();
                    $this->client->delContact($User) ;
                    //$this->UnregisterVariable($User);
                    IPS_DeleteVariable(IPS_GetObjectIDByIdent('lastmsg', $this->GetIDForIdent($User)));
                    IPS_DeleteVariable(IPS_GetObjectIDByIdent('lastonline', $this->GetIDForIdent($User)));
                    IPS_DeleteVariable(IPS_GetObjectIDByIdent('phone', $this->GetIDForIdent($User)));
                    IPS_DeleteVariable($this->GetIDForIdent($User));
                }
                else
                {
                    throw new Exception("Telegram Client can't started!");
                }
            }
            else
            {
                throw new Exception("Wrong or Empty Parameter!");
            }            
        }
            
        /**
         * Define include path for classes and some defaults for system dependent variables.
         */
        const TELEGRAM_CLASSPATH = "/lib";
        const TELEGRAM_HOMEPATH  = '/bin';         
        const TELEGRAM_COMMAND   = '/bin/telegram-cli -W -U root';
        const TELEGRAM_KEYFILE   = '/bin/tg-server.pub';
        const TELEGRAM_CONFIG    = '/bin/telegram.conf';  
        // Log level (0 = Debug, 1 = Info, 2 = Notice, 3 = Warning, 4 = Error)
        const TELEGRAM_LOGLEVEL  = 2;
        const TELEGRAM_LOGFILE   = '/tmp/telegram.log';    
        
        /**
         * Create telegram client.
         */
        private function Telegram_Create_Client($params = []) 
        {
            foreach (glob(__DIR__ . self::TELEGRAM_CLASSPATH . '/*.php') as $class) 
            {
                require_once $class;
            }
            $params += array(
                'command' => __DIR__ . self::TELEGRAM_COMMAND,
                'keyfile' => __DIR__ . self::TELEGRAM_KEYFILE,
                'configfile' => __DIR__ . self::TELEGRAM_CONFIG,
                'homepath' => __DIR__ . self::TELEGRAM_HOMEPATH,
                'log_level' => self::TELEGRAM_LOGLEVEL,
                'log_file' => self::TELEGRAM_LOGFILE,
            );            
            $logger = new TelegramLogger($params);
            $process = new TelegramProcess($params, $logger);
            return new TelegramClient($process, $logger);
        }           
    }