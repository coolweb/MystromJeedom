<?php
namespace coolweb\mystrom;

/**
* Mystrom service class
*/
class MyStromService
{
    private $myStromApiUrl = 'https://www.mystrom.ch/mobile';

    /** @var \coolweb\mystrom\JeedomHelper */
    private $jeedomHelper;

    public function __construct(\coolweb\mystrom\JeedomHelper $jeedomHelper)
    {
        $this->jeedomHelper = $jeedomHelper;
    }

    /**
    * Do a request and get json result.
    * @param $requestUrl string The request url to call
    * @return Object A json object.
    */
    public function doJsonCall($requestUrl)
    {
        $this->jeedomHelper->logDebug("Do http call " . $requestUrl);
        
        $json = file_get_contents($requestUrl);
        $this->jeedomHelper->logDebug("Result: " . $json);
        $jsonObj = json_decode($json);
        
        return $jsonObj;
    }
    
    public function doHttpCall($url, $data, $method = "POST")
    {
        $this->jeedomHelper->logDebug("Do http call url: " . $url);
        
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
            "cache-control: no-cache"
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
            "cache-control: no-cache",
            ),
            ));
            
            $result = curl_exec($curl);
            $err = curl_error($curl);
            
            curl_close($curl);
        }
        
        if ($err) {
            $this->jeedomHelper->logDebug("cURL Error #:" . $err);
            $result = false;
        }
        
        if ($result === false) {
            $this->jeedomHelper->logDebug("Error");
        }
        
        $this->jeedomHelper->logDebug("Result: " . print_r($result, true));
        $this->jeedomHelper->logDebug("Http code: " .print_r($http_response_header, true));
        
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
        $this->jeedomHelper->logInfo("Authentification");
        $user = $this->jeedomHelper->loadPluginConfiguration("userId");
        $password = $this->jeedomHelper->loadPluginConfiguration("password");
        
        $authUrl = $this->myStromApiUrl . "/auth?email=" . $user
        . "&password=" . $password;
        
        $jsonObj = $this->doJsonCall($authUrl);
        if ($jsonObj->status == "ok") {
            $this->jeedomHelper->savePluginConfiguration("authToken", $jsonObj->authToken);
            $this->jeedomHelper->logDebug("Clé d'authentification sauvée: " . $jsonObj->authToken);
            return true;
        } else {
            $this->jeedomHelper->logWarning("Erreur d'authentification: " . $jsonObj->error);
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
        $this->jeedomHelper->logInfo("Recherche des équipements mystrom");
        $authToken = $this->jeedomHelper->loadPluginConfiguration("authToken");
        $devicesUrl = $this->myStromApiUrl . "/devices?";
        
        if ($withReportData) {
            $devicesUrl = $devicesUrl . "report=true&";
        }
        
        $devicesUrl = $devicesUrl . "authToken=" . $authToken;
        
        $result = new GetAllDevicesResult();
        $jsonObj = $this->doJsonCall($devicesUrl);
        $result->status = $jsonObj->status;
        
        if (strcmp($jsonObj->status, "ok") == 0) {
            foreach ($jsonObj->devices as $device) {
                $mystromDevice = null;
                
                switch ($device->type) {
                    case "sw":
                    case "eth":
                    case "mst":
                    case "wsw":
                        $mystromDevice = new MyStromDevice();
                        $mystromDevice->power = $device->power;
                        
                        if ($withReportData) {
                            $mystromDevice->daylyConsumption = $device->energyReport->daylyConsumption;
                            $mystromDevice->monthlyConsumption = $device->energyReport->monthlyConsumption;
                        }
                        break;

                    case "wse":
                        $mystromDevice = new MystromWifiSwitchEurope();
                        $mystromDevice->power = $device->power;
                        $mystromDevice->temperature = $device->wifiSwitchTemp;

                        if ($withReportData) {
                            $mystromDevice->daylyConsumption = $device->energyReport->daylyConsumption;
                            $mystromDevice->monthlyConsumption = $device->energyReport->monthlyConsumption;
                        }
                        break;
                            
                    case "wbp":
                    case "wbs":
                        $mystromDevice = new MystromButtonDevice();
                        break;
                            
                    case "wrb":
                        $mystromDevice = new MystromWifiBulb();
                        $mystromDevice->power = $device->power;
                        
                        if ($withReportData) {
                            $mystromDevice->daylyConsumption = $device->energyReport->daylyConsumption;
                            $mystromDevice->monthlyConsumption = $device->energyReport->monthlyConsumption;
                        }

                        $hsv = explode(";", $device->bulbColor);
                        $rgb = explode(";", $this->hsvToRgb($hsv[0], $hsv[1], $hsv[2]));
                        $mystromDevice->color = "#" . \sprintf("%'.02s", \dechex($rgb[0]))
                        . \sprintf("%'.02s", \dechex($rgb[1]))
                        . \sprintf("%'.02s", \dechex($rgb[2]));

                        break;

                    default:
                        $this->jeedomHelper->logWarning("Unsupported device type: " . $device->type);
                        break;
                    }
                    
                if ($mystromDevice != null) {
                    $mystromDevice->id = $device->id;
                    $mystromDevice->type = $device->type;
                    $mystromDevice->name = $device->name;
                    $mystromDevice->state = $device->state;
                        
                    array_push($result->devices, $mystromDevice);
                }
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
        $authToken = $this->jeedomHelper->loadPluginConfiguration("authToken");
        $stateUrl = $this->myStromApiUrl . "/device/switch?authToken=" . $authToken
            . "&id=" . $deviceId . "&on=" . (($isOn) ? "true" : "false");
        $restartUrl = $this->myStromApiUrl . "/device/restart?authToken=" . $authToken
            . "&id=" . $deviceId;
            
        $url = "";
            
        if ($deviceType == "mst") {
            $url = $restartUrl;
        } else {
            $url = $stateUrl;
        }
            
        $jsonObj = $this->doJsonCall($url);
            
        $result = new MyStromApiResult();
        $result->status = $jsonObj->status;
            
        if ($jsonObj->status !== "ok") {
            $result->error = $jsonObj->error;
        }
            
        return $result;
    }

    /**
     * Set state for local mystrom device.
     *
     * @param [string] $ipAddress The ip address of the device.
     * @param [string] $macAddress The mac address of the device.
     * @param [bool] $isOn Indicates to switch on or off.
     * @return void
     */
    public function setStateLocalDevice($ipAddress, $macAddress, $isOn)
    {
        $stateUrl = "http://" . $ipAddress . "/api/v1/device/" . $macAddress;
        $dataAction = new \stdClass();
        @$dataAction->action = ($isOn == true) ? "on" : "off";
        $data = array($macAddress => $dataAction);
        $dataJson = json_encode($data);

        $result = $jsonObj = $this->doHttpCall($stateUrl, $dataJson);
            
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
            $this->jeedomHelper->logDebug("Retrieve info of wifi button " . $ipAddress);
            $url = "http://" . $ipAddress . "/api/v1/device";
            $result = $this->doHttpCall($url, null, "GET");
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
            $this->jeedomHelper->logWarning("RetrieveLocalButtonInfo - " . $e);
            return null;
        }
    }

    /**
     * Retrieve information of local rgb bulb
     *
     * @param [string] $ipAddress
     * @return MyStromWifiBulb The bulb if found otherwise null.
     */
    public function RetrieveLocalRgbBulbInfo($ipAddress)
    {
        try {
            $this->jeedomHelper->logDebug("Retrieve info of wifi bulb " . $ipAddress);
            $url = "http://" . $ipAddress . "/api/v1/device";
            $result = $this->doHttpCall($url, null, "GET");
            if ($result === false) {
                return null;
            }
                
            $jsonObj = json_decode($result);
            $macAddress = key($properties = get_object_vars($jsonObj));
                
            $mystromBulb = new MystromWifiBulb();
            $mystromBulb->macAddress = $macAddress;
            $mystromBulb->ipAddress = $ipAddress;
            $mystromBulb->state = $jsonObj->$macAddress->on == true ? "on" : "off";
            $mystromBulb->power = $jsonObj->$macAddress->power;
            $mystromBulb->color = $jsonObj->$macAddress->color;

            $hsv = explode(";", $mystromBulb->color);
            $rgb = explode(";", $this->hsvToRgb($hsv[0], $hsv[1], $hsv[2]));
            $mystromBulb->color = "#" . \sprintf("%'.02s", \dechex($rgb[0]))
            . \sprintf("%'.02s", \dechex($rgb[1]))
            . \sprintf("%'.02s", \dechex($rgb[2]));

            return $mystromBulb;
        } catch (Exception $e) {
            $this->jeedomHelper->logWarning("RetrieveLocalRgbBulbInfo - " . $e);
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
    public function SaveUrlsForWifiButton($wifiButton, $cmdIdSingle, $cmdIdDouble, $cmdIdLong, $cmdIdTouched = -1)
    {
        $jeedomIp = $this->jeedomHelper->loadPluginConfiguration("internalAddr", true);
        $apiKey = $this->jeedomHelper->getJeedomApiKey();
        $url = "get://" . $jeedomIp . "/core/api/jeeApi.php?apikey%3D" . $apiKey .
            "%26type%3Dcmd%26id%3D";
            
        $singleUrl = $url . $cmdIdSingle;
        $doubleUrl = $url . $cmdIdDouble;
        $longUrl = $url . $cmdIdLong;
        $touchedUrl = $url . $cmdIdTouched;
            
        if ($wifiButton->isLocal == true) {
            $this->jeedomHelper->logDebug("SaveUrlsForWifiButton - " . $wifiButton->ipAddress);
                
            $buttonApiUrl = "http://" . $wifiButton->ipAddress . "/api/v1/device/" .
                $wifiButton->macAddress;
                
            try {
                $result = $this->doHttpCall($buttonApiUrl, "single=" . $singleUrl, "POST");
                if ($result === false) {
                    return $result;
                }

                $result = $this->doHttpCall($buttonApiUrl, "double=" . $doubleUrl, "POST");
                if ($result === false) {
                    return $result;
                }

                $result = $this->doHttpCall($buttonApiUrl, "long=" . $longUrl, "POST");
                if ($result === false) {
                    return $result;
                }
                    
                if ($cmdIdTouched != -1) {
                    $result = $this->doHttpCall($buttonApiUrl, "touch=" . $touchedUrl, "POST");
                    if ($result === false) {
                        return $result;
                    }
                }

                return true;
            } catch (\Exception $e) {
                $this->jeedomHelper->logWarning("SaveUrlsForWifiButton - " . $e);
                return false;
            }
        } else {
            $authToken = $this->jeedomHelper->loadPluginConfiguration("authToken");
            $this->jeedomHelper->logDebug("SaveUrlsForWifiButton - " . $wifiButton->id);
                
            $url = $this->myStromApiUrl . "/device/setSettings?" . "authToken=" . $authToken .
                "&id=" . $wifiButton->id .
                "&localSingleUrl=" . $singleUrl .
                "&localDoubleUrl=" . $doubleUrl .
                "&localLongUrl=" . $longUrl;
                
            if ($cmdIdTouched != -1) {
                $url = $url . "&localTouchUrl=" . $touchedUrl;
            }
                
            $result = $this->doHttpCall($url, null, "GET");
            $resultApi = json_decode($result);
                
            if ($resultApi->status === "ok") {
                $this->jeedomHelper->logInfo("Url saved for wifi button id:" .
                        $wifiButton->id . " value: " . $resultApi->value);
                return true;
            } else {
                $this->jeedomHelper->logWarning("Unable to save url for wifi button id:" .
                        $wifiButton->id . " error: " . $resultApi->error);
                    
                return false;
            }
        }
    }

    /**
     * Change color of a bulb
     *
     * @param MystromWifiBulb $bulbDevice The bulb to change color
     * @param string $color The hexa value of the color
     * @return MyStromApiResult Api result
     */
    public function setBulbColor(MystromWifiBulb $bulbDevice, $color)
    {
        $result = new MyStromApiResult();
        
        list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
        
        $hsv = $this->RGBtoHSV($r, $g, $b);
        $hsvQueryParam = $hsv[0] . ";" . $hsv[1] . ";" . $hsv[2];

        if($bulbDevice->isLocal == true)
        {
            $url = "http://" . $bulbDevice->ipAddress . "/api/v1/device";
            $dataColor = new \stdClass();
            @$dataColor->color = $hsvQueryParam;
            $data = array($bulbDevice->macAddress => $dataColor);
            $dataJson = json_encode($data);

            $resultHttp = $this->doHttpCall($url, $dataJson, "POST");
            if ($resultHttp === false) {
                $result->status = "ko";
                $result->error = "Error setting color of local bulb " . $bulbDevice->ipAddress;
            } else {
                $result->status = "ok";
            }
        } else {
            $authToken = $this->jeedomHelper->loadPluginConfiguration("authToken");
            $colorUrl = $this->myStromApiUrl . "/device/switch?authToken=" . $authToken
                . "&id=" . $bulbDevice->id . "&color=" . $hsvQueryParam . "&ramp=1000";

            $jsonObj = $this->doJsonCall($colorUrl);
                
            $result->status = $jsonObj->status;
                
            if ($jsonObj->status !== "ok") {
                $result->error = $jsonObj->error;
            }
        }   
        return $result;
    }

    private function RGBtoHSV($R, $G, $B)   // RGB values:    0-255, 0-255, 0-255
    {                                       // HSV values:    0-360, 0-100, 0-100
        // Convert the RGB byte-values to percentages
        $R = ($R / 255);
        $G = ($G / 255);
        $B = ($B / 255);
    
        // Calculate a few basic values, the maximum value of R,G,B, the
        //   minimum value, and the difference of the two (chroma).
        $maxRGB = max($R, $G, $B);
        $minRGB = min($R, $G, $B);
        $chroma = $maxRGB - $minRGB;
    
        // Value (also called Brightness) is the easiest component to calculate,
        //   and is simply the highest value among the R,G,B components.
        // We multiply by 100 to turn the decimal into a readable percent value.
        $computedV = 100 * $maxRGB;
    
        // Special case if hueless (equal parts RGB make black, white, or grays)
        // Note that Hue is technically undefined when chroma is zero, as
        //   attempting to calculate it would cause division by zero (see
        //   below), so most applications simply substitute a Hue of zero.
        // Saturation will always be zero in this case, see below for details.
        if ($chroma == 0) {
            return array(0, 0, $computedV);
        }
    
        // Saturation is also simple to compute, and is simply the chroma
        //   over the Value (or Brightness)
        // Again, multiplied by 100 to get a percentage.
        $computedS = 100 * ($chroma / $maxRGB);
    
        // Calculate Hue component
        // Hue is calculated on the "chromacity plane", which is represented
        //   as a 2D hexagon, divided into six 60-degree sectors. We calculate
        //   the bisecting angle as a value 0 <= x < 6, that represents which
        //   portion of which sector the line falls on.
        if ($R == $minRGB) {
            $h = 3 - (($G - $B) / $chroma);
        } elseif ($B == $minRGB) {
            $h = 1 - (($R - $G) / $chroma);
        } else { // $G == $minRGB
            $h = 5 - (($B - $R) / $chroma);
        }
    
        // After we have the sector position, we multiply it by the size of
        //   each sector's arc (60 degrees) to obtain the angle in degrees.
        $computedH = 60 * $h;
    
        return array(round($computedH), round($computedS), round($computedV));
    }

    private function hsvToRgb($hue, $sat, $val, $array = false, $format = '%d;%d;%d')
    {
        if ($hue < 0) {
            $hue = 0;
        }
        if ($hue > 360) {
            $hue = 360;
        }
        if ($sat < 0) {
            $sat = 0;
        }
        if ($sat > 100) {
            $sat = 100;
        }
        if ($val < 0) {
            $val = 0;
        }
        if ($val > 100) {
            $val = 100;
        }
     
        $dS = $sat/100.0;
        $dV = $val/100.0;
        $dC = $dV*$dS;
        $dH = $hue/60.0;
        $dT = $dH;
     
        while ($dT >= 2.0) {
            $dT -= 2.0;
        }
        $dX = $dC*(1-abs($dT-1));
     
        switch (floor($dH)) {
          case 0:
            $dR = $dC; $dG = $dX; $dB = 0.0; break;
          case 1:
            $dR = $dX; $dG = $dC; $dB = 0.0; break;
          case 2:
            $dR = 0.0; $dG = $dC; $dB = $dX; break;
          case 3:
            $dR = 0.0; $dG = $dX; $dB = $dC; break;
          case 4:
            $dR = $dX; $dG = 0.0; $dB = $dC; break;
          case 5:
            $dR = $dC; $dG = 0.0; $dB = $dX; break;
          default:
            $dR = 0.0; $dG = 0.0; $dB = 0.0; break;
        }
     
        $dM  = $dV - $dC;
        $dR += $dM;
        $dG += $dM;
        $dB += $dM;
        $dR *= 255;
        $dG *= 255;
        $dB *= 255;
        $rgb = ($array) ? array('r'=>round($dR), 'b'=>round($dG), 'g'=>round($dB)) : sprintf($format, round($dR), round($dG), round($dB));
     
        return $rgb;
    }
}
