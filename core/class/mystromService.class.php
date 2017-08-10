<?php

/**
* Mystrom service class
*/
class MyStromService
{
    private $myStromApiUrl = 'https://www.mystrom.ch/mobile';
    
    /**
    * Get a configuration value of the plugin.
    * @param $key string The name of the configuration to retrieve
    * @param $isCoreConfig Optional (Default is false) Indicate if retrieve configuration in core jeedom or
    *        mystrom plugin.
    * @return The value of the configuration, null il not exists
    */
    public function getMyStromConfiguration($key, $isCoreConfig = false)
    {
        return config::byKey($key, $isCoreConfig == true ? 'core' : 'mystrom');
    }
    
    /**
    * Save a configuration key
    * @param $key string The key of the configuration to save
    * @param $value string The configuration value to save
    */
    public function saveMystromConfiguration($key, $value)
    {
        config::save($key, $value, 'mystrom');
    }
    
    /**
    * Log a debug message
    * @param $message string The message to log
    */
    public function logDebug($message)
    {
        log::add('mystrom', 'debug', 'MystromService: ' . $message);
    }
    
    /**
    * Log an information message
    * @param $message string The message to log
    */
    public function logInfo($message)
    {
        log::add('mystrom', 'info', $message);
    }
    
    /**
    * Log a warning message
    * @param $message string The message to log
    */
    public function logWarning($message)
    {
        log::add('mystrom', 'warning', $message);
    }
    
    /**
    * Do a request and get json result.
    * @param $requestUrl string The request url to call
    * @return Object A json object.
    */
    public function doJsonCall($requestUrl)
    {
        $this->logDebug('Do http call ' . $requestUrl);
        
        $json = file_get_contents($requestUrl);
        $this->logDebug('Result: ' . $json);
        $jsonObj = json_decode($json);
        
        return $jsonObj;
    }

    public function doHttpCall($url, $data, $method = 'POST')
    {
        $this->logDebug('Do http call url: ' . $url);
        
        if ($method == "POST") {
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
            'cache-control: no-cache'
            ),
            ));

            $result = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
        } else {
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL =>$url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
            'cache-control: no-cache',
            ),
            ));

            $result = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
        }

        if ($err) {
            $this->logDebug('cURL Error #:' . $err);
            return false;
        }

        if ($result === false) {
            $this->logDebug('Error');
        }

        $this->logDebug('Result: ' . print_r($result, true));
        $this->logDebug('Http code: ' .print_r($http_response_header, true));

        return $result;
    }
    
    /**
    * Do the authentification of the user.
    * Use the configuration userId and password and store
    * the authentification token into the configuration key authToken.
    * @return boolean indicating the success of the authentification.
    */
    public function doAuthentification()
    {
        $this->logInfo('Authentification');
        $user = $this->getMyStromConfiguration('userId');
        $password = $this->getMyStromConfiguration('password');
        
        $authUrl = $this->myStromApiUrl . '/auth?email=' . $user
        . '&password=' . $password;
        
        $jsonObj = $this->doJsonCall($authUrl);
        if ($jsonObj->status == 'ok') {
            $this->saveMystromConfiguration('authToken', $jsonObj->authToken);
            $this->logDebug("Clé d'authentification sauvée: " . $jsonObj->authToken);
            return true;
        } else {
            $this->logWarning("Erreur d'authentification: " . $jsonObj->error);
            return false;
        }
    }
    
    /**
    * Load all devices from mystrom server
    * @param boolean $withReportData Indicates to load report data with the devices, default is false
    * @return GetAllDevicesResult The result of the call
    */
    public function loadAllDevicesFromServer($withReportData = false)
    {
        $this->logInfo('Recherche des équipements mystrom');
        $authToken = $this->getMyStromConfiguration('authToken');
        $devicesUrl = $this->myStromApiUrl . '/devices?';
        
        if ($withReportData) {
            $devicesUrl = $devicesUrl . 'report=true&';
        }

        $devicesUrl = $devicesUrl . 'authToken=' . $authToken;
        
        $result = new GetAllDevicesResult();
        $jsonObj = $this->doJsonCall($devicesUrl);
        $result->status = $jsonObj->status;
        
        if (strcmp($jsonObj->status, 'ok') == 0) {
            foreach ($jsonObj->devices as $device) {
                $mystromDevice = new MyStromDevice();
                $mystromDevice->id = $device->id;
                $mystromDevice->type = $device->type;
                $mystromDevice->name = $device->name;
                $mystromDevice->state = $device->state;
                $mystromDevice->power = $device->power;

                if ($withReportData) {
                    $mystromDevice->daylyConsumption = $device->energyReport->daylyConsumption;
                    $mystromDevice->monthlyConsumption = $device->energyReport->monthlyConsumption;
                }

                array_push($result->devices, $mystromDevice);
            }
        } else {
            $result->error = $jsonObj->error;
        }
        
        return $result;
    }
    
    /**
    * Set the state on or off of a device, if the device type is the master, reset it.
    * @param $deviceId string The id of the device to change the state
    * @param $deviceType string The type of the device
    * @param $isOn boolean Indicating if the state should be on or off
    * @return MyStromApiResult The result of the call
    */
    public function setState($deviceId, $deviceType, $isOn)
    {
        $authToken = $this->getMyStromConfiguration('authToken');
        $stateUrl = $this->myStromApiUrl . '/device/switch?authToken=' . $authToken
        . '&id=' . $deviceId . '&on=' . (($isOn) ? 'true' : 'false');
        $restartUrl = $this->myStromApiUrl . '/device/restart?authToken=' . $authToken
        . '&id=' . $deviceId;
        
        $url = '';
        
        if ($deviceType == 'mst') {
            $url = $restartUrl;
        } else {
            $url = $stateUrl;
        }
        
        $jsonObj = $this->doJsonCall($url);
        
        $result = new MyStromApiResult();
        $result->status = $jsonObj->status;
        
        if ($jsonObj->status !== 'ok') {
            $result->error = $jsonObj->error;
        }
        
        return $result;
    }

    /**
     * Retrieve the information for a local wifi button.
     * @param ipAddress The ip address of the button.
     * @return {MystromButtonDevice} The button class if found otherwise null.
     */
    public function RetrieveLocalButtonInfo($ipAddress)
    {
        try {
            $this->logDebug('Retrieve info of wifi button ' . $ipAddress);
            $url = 'http://' . $ipAddress . '/api/v1/device';
            $result = $this->doHttpCall($url, null, 'GET');
            if ($result === false) {
                return null;
            }

            $jsonObj = json_decode($result);
            $macAddress = key($properties = get_object_vars($jsonObj));

        
            $mystromButton = new MystromButtonDevice();
            $mystromButton->macAddress = $macAddress;
            $mystromButton->ipAddress = $ipAddress;
            $mystromButton->doubleUrl = $jsonObj->$macAddress->double;
            $mystromButton->singleUrl = $jsonObj->$macAddress->single;
            $mystromButton->longUrl = $jsonObj->$macAddress->long;
            $mystromButton->touchedUrl = $jsonObj->$macAddress->touch;

            return $mystromButton;
        } catch (Exception $e) {
            $this->logWarning('RetrieveLocalButtonInfo - ' . $e);
            return null;
        }
    }

    /**
     *  Save urls of jeedom cmd action into the wifibutton
     *  @param $wifiButton {MystromButtonDevice} The button into which tosavethe urls
     *  @param $cmdIdSingle {string} The id of the action command
     *  @param $cmdIdDouble {string} The id of the action command
     *  @param $cmdIdLong {string} The id of the action command
     *  @param $cmdIdTouched {string} The id of the action command
     */
    public function SaveUrlsForWifiButton($wifiButton, $cmdIdSingle, $cmdIdDouble, $cmdIdLong, $cmdIdTouched)
    {
        $this->logDebug('SaveUrlsForWifiButton - ' . $wifiButton->ipAddress);
        
        $jeedomIp = $this->getMyStromConfiguration('internalAddr', true);
        $apiKey = jeedom::getApiKey();
        $url = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' . $apiKey .
            '%26type%3Dcmd%26id%3D';
        
        $buttonApiUrl = 'http://' . $wifiButton->ipAddress . '/api/v1/device/' .
            $wifiButton->macAddress;
        
        try {
            $this->doHttpCall($buttonApiUrl, 'single=' . $url . $cmdIdSingle, 'POST');
            $this->doHttpCall($buttonApiUrl, 'double=' . $url . $cmdIdDouble, 'POST');
            $this->doHttpCall($buttonApiUrl, 'long=' . $url . $cmdIdLong, 'POST');
            $this->doHttpCall($buttonApiUrl, 'touch=' . $url . $cmdIdTouched, 'POST');
        } catch (Exception $e) {
            $this->logWarning('SaveUrlsForWifiButton - ' . $e);
            return false;
        }
    }
}
