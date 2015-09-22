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
            $this->RegisterPropertyString("ClientId", ""); 
            $this->RegisterPropertyString("ClientSecret", ""); 
            $this->RegisterPropertyString("RedirectUri", "http://Host:Port/hook/fitbit");
            $this->RegisterPropertyString("RefreshToken", ""); 
        }
        
        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();             
            
            if ( $this->ReadPropertyBoolean("Active") ) { $this->SetStatus(102); } else { $this->SetStatus(104); }
            $sid = $this->RegisterScript("Hook", "Hook", "<? //Do not delete or modify.\nFitbit_Update(".$this->InstanceID.");");
            $this->RegisterHook("/hook/fitbit", $sid);
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
            if($_IPS['SENDER'] == "Execute") 
            {
		echo "This script cannot be used this way.";
                return;
            }
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
            function AutoLoader($className)
            {
                $file = str_replace('\\',DIRECTORY_SEPARATOR,$className);
                require_once __DIR__ . "/lib/" . $file . ".php";
            }
            spl_autoload_register("AutoLoader");
            require_once(__DIR__ . "/lib/GuzzleHttp/functions.php");
            require_once(__DIR__ . "/lib/GuzzleHttp/Promise/functions_include.php");
            require_once(__DIR__ . "/lib/GuzzleHttp/Psr7/functions_include.php");
            require_once(__DIR__ . "/lib/Fitbit.php");
            require_once(__DIR__ . "/lib/FitbitUser.php");
            
            
            $provider = new djchen\OAuth2\Client\Provider\Fitbit([
                "clientId"          => $this->ReadPropertyString("ClientId"),
                "clientSecret"      => $this->ReadPropertyString("ClientSecret"),
                "redirectUri"       => $this->ReadPropertyString("RedirectUri")
            ]);

            // start the session
            session_start();
            
            if (!isset($_GET['code'])) 
            {
                $_GET['code'] = $this->ReadPropertyString("RefreshToken");
            }
            if ($_GET['code'] === "") 
            {
                // If we don't have an authorization code then get one
                $authUrl = $provider->getAuthorizationUrl();
                $_SESSION['oauth2state'] = $provider->getState();
                header('Location: '.$authUrl);
                exit;
            }           
                     
            $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
            var_dump($token->getRefreshToken());
            /*

            */     
        }
    }