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
         * Create
         */        
        public function Create()
        {
            //Never delete this line!
            parent::Create();
            
            $this->RegisterPropertyBoolean("Active", false);       
        }
        
        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();             
            
            if ( $this->ReadPropertyBoolean("Active") ) { $this->SetStatus(102); } else { $this->SetStatus(104); }
        }
        
        /**
         * Some Const
         */
        const FITBIT_CLASSPATH = "/lib";
        
        /**
         * Fitbit_Update();
         */
        public function Update()
        {            
            /**
             * Workaround 
             */
            function hash($algo, $data, $raw_output = false)
            {
                shell_exec("touch /tmp/hash");shell_exec("touch /tmp/hash_data");
                file_put_contents("/tmp/hash_data", $data);
                shell_exec("/usr/bin/php -B \"file_put_contents('/tmp/hash', hash('$algo',file_get_contents('/tmp/hash_data'),$raw_output));\"");
                return (file_get_contents("/tmp/hash"));
            }
            function hash_hmac($algo, $data, $key, $raw_output = false)
            {
                shell_exec("touch /tmp/hash_hmac"); shell_exec("touch /tmp/hash_hmac_data"); shell_exec("touch /tmp/hash_hmac_key");
                file_put_contents("/tmp/hash_hmac_data", $data);
                file_put_contents("/tmp/hash_hmac_key", $key);
                shell_exec("/usr/bin/php -B \"file_put_contents('/tmp/hash_hmac', hash_hmac('$algo',file_get_contents('/tmp/hash_hmac_data'),file_get_contents('/tmp/hash_hmac_key'),$raw_output));\"");
                return (file_get_contents("/tmp/hash_hmac"));
            }
            
            /*
             * Requires
             */
            spl_autoload_register("AutoLoader");
            function AutoLoader($className)
            {
                $file = str_replace('\\',DIRECTORY_SEPARATOR,$className);
                require_once __DIR__ . "/lib/" . $file . ".php";
            }
            require_once(__DIR__ . "/lib/GuzzleHttp/functions.php");
            require_once(__DIR__ . "/lib/GuzzleHttp/Promise/functions_include.php");
            require_once(__DIR__ . "/lib/GuzzleHttp/Psr7/functions_include.php");
            require_once(__DIR__ . "/lib/Fitbit.php");
            require_once(__DIR__ . "/lib/FitbitUser.php");
            
            
            $provider = new djchen\OAuth2\Client\Provider\Fitbit([
                "clientId"          => "229W3G",
                "clientSecret"      => "e471aa7c8b19edc8c648b349be364199",
                "redirectUri"       => "http://siemensag.dyndns.tv:85/hook/fitbit"
            ]);

            // start the session
            session_start();
        }        
    }