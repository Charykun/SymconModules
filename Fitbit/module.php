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
            $this->CreateStringProfile("Fitbit_Battery", "Battery", "", "");
            $this->CreateIntegerProfile("Fitbit_Steps", "Motion", "", "", "0", "", "1");
            $this->CreateFloatProfile("Fitbit_km", "Distance", "", " km", "0", "", "", "2");
            $this->CreateIntegerProfile("Fitbit_Floors", "Intensity", "", "", "0", "", "1");
            $this->CreateIntegerProfile("Fitbit_Calories", "Popcorn", "", " kcal", "0", "", "1");
            $this->CreateStringProfile("Fitbit_Sleep", "Cloud", "", "");
            $this->RegisterVariableString("RefreshToken", "RefreshToken");
            IPS_SetHidden($this->GetIDForIdent("RefreshToken"), true); 
            $this->RegisterVariableString("Username", "Username", "", 1);
            $this->RegisterVariableInteger("lastSyncTime", "lastSyncTime", "UnixTimestamp", 2);
            $this->RegisterVariableString("Battery", "Battery", "Fitbit_Battery", 3);          
            $this->RegisterVariableInteger("Steps", "Steps", "Fitbit_Steps", 4);
            $this->RegisterVariableFloat("Distances", "Distances", "Fitbit_km", 5);           
            $this->RegisterVariableInteger("Floors", "Floors", "Fitbit_Floors", 6);
            $this->RegisterVariableInteger("CaloriesOut", "Calories", "Fitbit_Calories", 7);
            $this->RegisterVariableString("totalMinutesAsleep", "Sleep", "Fitbit_Sleep", 8); 
            $this->RegisterVariableString("totalTimeInBed", "In Bed", "Fitbit_Sleep", 9); 
        }
        
        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();             
            
            $sid = $this->RegisterScript("Hook", "Hook", "<? //Do not delete or modify.\nFitbit_Update(".$this->InstanceID.", \"\");");
            IPS_SetHidden($sid, true); 
            $this->RegisterHook("/hook/fitbit", $sid);           
            if ( $this->ReadPropertyBoolean("Active") ) 
            { 
                //$this->SetStatus(102); 
                $this->Update();
                IPS_SetScriptTimer($sid, 60 * 15);
            }
            else 
            { 
                $this->SetStatus(104); 
                IPS_SetScriptTimer($sid, 0);
            }
        }
        
        /**
         * Fitbit_Update();
         * @param string $request
         * @return array
         */
        public function Update($request = "")
        {            
            /**
             * Workaround 
             */
            if (function_exists('hash_hmac') === false)
            {
                if ( file_exists("/usr/bin/php") === false )
                {
                    exec("apt-get install php5-cli -y");
                }
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
            
            exec("mkdir /usr/share/symcon/session/");
            
            $provider = new djchen\OAuth2\Client\Provider\Fitbit([
                "clientId"          => $this->ReadPropertyString("ClientId"),
                "clientSecret"      => $this->ReadPropertyString("ClientSecret"),
                "redirectUri"       => $this->ReadPropertyString("RedirectUri")
            ]);
                    
            $refreshToken = GetValueString($this->GetIDForIdent("RefreshToken")); 
            if ($refreshToken === "")
            {
                $this->SetStatus(201); 
                if ($_IPS['SENDER'] != "WebHook") 
                {
                    echo "Not Authorized! Please login with browser (http://Host:Port/hook/fitbit)";
                    return;
                }
                
                // start the session
                session_start();
                
                if (!isset($_GET['code']))
                {
                    // If we don't have an authorization code then get one
                    $authUrl = $provider->getAuthorizationUrl();
                    $_SESSION['oauth2state'] = $provider->getState();
                    header('Location: '.$authUrl);
                    exit;  
                }
                else
                {
                   $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
                    $this->SetValue($this->GetIDForIdent("RefreshToken"), $token->getRefreshToken());
                    header('Location: '.$this->ReadPropertyString("RedirectUri"));
                    exit; 
                }
            }
                       
            $grant = new League\OAuth2\Client\Grant\RefreshToken();
            try 
            {
                $token = $provider->getAccessToken($grant, ['refresh_token' => $refreshToken]);                
                $this->SetValue($this->GetIDForIdent("RefreshToken"), $token->getRefreshToken());                
            } catch (Exception $ex) 
            {
                $this->SetStatus(201);  
                $this->SetValue($this->GetIDForIdent("RefreshToken"),""); 
                echo "Not Authorized! Please login with browser (http://Host:Port/hook/fitbit)";
                return;
            }

            try 
            {
                $this->SetStatus(102); 
                $userDetails = $provider->getResourceOwner($token);
                if ($_IPS['SENDER'] === "WebHook") 
                {
                    echo "Hello " . $userDetails->getDisplayName() . "!" . PHP_EOL; 
                    echo "Login successful!";   
                }                
            } 
            catch (Exception $ex) 
            {
                $this->SetStatus(200);  
                echo "Login error!";                
                return;
            }  
            
            if ($request === "")
            {
                $this->SetValue($this->GetIDForIdent("Username"), $userDetails->getDisplayName());
                $request = $provider->getAuthenticatedRequest("GET", "https://api.fitbit.com/1/user/-/devices.json", $token);
                $response = $provider->getResponse($request);
                $this->SetValue($this->GetIDForIdent("Battery"), $response[0]["battery"]);
                $date = new \DateTime((string) $response[0]["lastSyncTime"]);
                $timestamp = $date->getTimestamp();
                $this->SetValue($this->GetIDForIdent("lastSyncTime"), $timestamp );
                $date = date("Y-m-d");
                $request = $provider->getAuthenticatedRequest("GET", "https://api.fitbit.com/1/user/-/activities/date/$date.json", $token);
                $response = $provider->getResponse($request);
                $this->SetValue($this->GetIDForIdent("Steps"), $response["summary"]["steps"]);
                $this->SetValue($this->GetIDForIdent("Distances"), $response["summary"]["distances"][0]["distance"]);
                $this->SetValue($this->GetIDForIdent("Floors"), $response["summary"]["floors"]);
                $this->SetValue($this->GetIDForIdent("CaloriesOut"), $response["summary"]["caloriesOut"]); 
                $request = $provider->getAuthenticatedRequest("GET", "https://api.fitbit.com/1/user/-/sleep/date/$date.json", $token);
                $response = $provider->getResponse($request);
                $this->SetValue($this->GetIDForIdent("totalMinutesAsleep"), date('H:i', mktime(0,$response["summary"]["totalMinutesAsleep"]))); 
                $this->SetValue($this->GetIDForIdent("totalTimeInBed"), date('H:i', mktime(0,$response["summary"]["totalTimeInBed"]))); 
            }
            else 
            {
                return $provider->getResponse($provider->getAuthenticatedRequest("GET", $request, $token));
            }
        }
        
        /**
         * Fitbit_GetDailyActivitySummary("yyyy-MM-dd");
         * @param string $date
         * @return array
         */
        public function GetDailyActivitySummary($date = "")
        {
            if ($date === "") { $date = date("Y-m-d"); }
            $response = $this->Update("https://api.fitbit.com/1/user/-/activities/date/$date.json");
            return $response;
        }
    }