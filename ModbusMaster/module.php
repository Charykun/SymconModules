<?php
    require_once(__DIR__ . "/../Base.php"); 
    class ModbusMaster extends BaseModule
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
            $this->RegisterPropertyString("IPAddress", "192.168.1.1");
            $this->RegisterPropertyInteger("AI", 0);
            $this->RegisterPropertyInteger("DI", 0);
            $this->RegisterPropertyInteger("DO", 0);
        }
        
        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
            
            $this->RegisterEventCyclic("Event_Update", "Update", 0, 0, 0, 0, 1, 1, "ModbusMaster_Update(".$this->InstanceID.");");   
        }
        
        /**
         * ModbusMaster_Update();
         */
        public function Update()
        {            
            include_once(__DIR__ . "/lib/ModbusMaster.php");
            if ( $this->ReadPropertyBoolean("Active") )
            {
                if (IPS_SemaphoreEnter("ModbusMaster_Update", 500))
                {
                    $URL = "http://" . $this->ReadPropertyString("IPAddress");
                    $headers = @get_headers($URL);
                    if ( isset($headers) && count($headers) > 0 && ( strpos($headers[0], "200") === FALSE ) )
                    {
                        throw new Exception("IP unreachable!");
                    }
                    $modbus = new PHPModbusMaster($this->ReadPropertyString("IPAddress"), "TCP");
                    //for ($i = 0; $i < 100; $i++) 
                    //{                        
                        $Channels = $this->ReadPropertyInteger("AI");
                        if ( $Channels > 0 )
                        {                        
                            try 
                            {
                                // FC 4
                                $recData = $modbus->readMultipleInputRegisters(1, 0, $Channels);
                            }
                            catch (Exception $e) 
                            {
                                throw new Exception("Error: " . $modbus);
                            }
                        }
                        $Channels = $this->ReadPropertyInteger("DI");
                        if ( $Channels > 0 )
                        {                        
                            try 
                            {
                                // FC 2
                                $recData = $modbus->readInputDiscretes(1, 0, $Channels);
                            }
                            catch (Exception $e) 
                            {
                                throw new Exception("Error: " . $modbus);
                            }
                        }                        
                        $Channels = $this->ReadPropertyInteger("DO");
                        if ( $Channels > 0 )
                        {                        
                            try 
                            {
                                // FC 1
                                $recData = $modbus->readCoils(1, 0, $Channels);
                            }
                            catch (Exception $e) 
                            {
                                throw new Exception("Error: " . $modbus);
                            }
                        }                        
                        IPS_Sleep(100);
                    //}
                
                    IPS_SemaphoreLeave("ModbusMaster_Update");
                }
            }        
            else 
            {
                $this->UnregisterEvent("Event_Update");
            }
        }        
    }