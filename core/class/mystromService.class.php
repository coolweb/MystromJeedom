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
     * @return The value of the configuration, null il not exists
     */
    public function getMyStromConfiguration($key)
    {
        return config::byKey($key, 'mystrom');
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
        log::add('mystrom', 'debug', $message);
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
     * @return GetAllDevicesResult The result of the call
     */
    public function loadAllDevicesFromServer()
    {
        $this->logInfo('Recherche des équipements mystrom');
        $authToken = $this->getMyStromConfiguration('authToken');
        $devicesUrl = $this->myStromApiUrl . '/devices?authToken=' . $authToken;

        $result = new GetAllDevicesResult();
        $jsonObj = $this->doJsonCall($devicesUrl);
        $result->status = $jsonObj->status;

        if($jsonObj->status == 'ok')
        {
            foreach ($jsonObj->devices as $device) {
                $mystromDevice = new MyStromDevice();
                $mystromDevice->id = $device->id;
                $mystromDevice->type = $device->type;
                $mystromDevice->name = $device->name;

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

        if($jsonObj->status !== 'ok')
        {
            $result->error = $jsonObj->error;
        }

        return $result;
    }
}
