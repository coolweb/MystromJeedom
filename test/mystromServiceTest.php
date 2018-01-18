<?php
include_once('eqLogic.php');
include_once('cmd.php');
include_once('./test/jeedom.php');
include_once('./core/class/mystromBaseDevice.class.php');
include_once('./core/class/mystromDevice.class.php');
include_once('./core/class/mystromApiResult.class.php');
include_once('./core/class/getAllDevicesResult.class.php');
include_once('./core/class/mystromButtonDevice.class.php');
include_once('./core/class/mystromService.class.php');
include_once('./core/class/mystromWifiSwitchEurope.class.php');
include_once('./core/class/mystromWifiBulb.class.php');

use PHPUnit\Framework\TestCase;
use coolweb\mystrom\jeedomHelper;
use coolweb\mystrom\MyStromService;
use coolweb\mystrom\MyStromDevice;
use coolweb\mystrom\MystromButtonDevice;
use coolweb\mystrom\MystromApiResult;
use coolweb\mystrom\mystromWifiSwitchEurope;

/**
* Test class for mystrom service class
*/
class mystromServiceTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $jeedomHelper;

    /** @var \MystromService */
    private $target;

    /** @var string The token returned on login */
    private $currentLoginToken = "xdfge";

    private $myStromApiUrl = 'https://www.mystrom.ch/mobile';
    private $jeedomIpAddress = "192.168.1.10";

    private $isLoginOk = false;
    private $userIdInJeedom = null;
    private $passwordInJeedom = null;
    private $loginTokenInJeedom = null;

    private $isLoadingDevicesFromJeedomServerOk = false;

    private $loginUrl;
    private $loginObject;
    private $loadDevicesFromServerUrl;
    private $loadDevicesFromServerWithReportUrl;
    private $loadDevicesServerObject;

    private $mystromServerDevices = array();
    private $mystromLocalDevices = array();

    private $isSetStateOk = false;
    private $setStateObject;
    private $setStateUrl;
    private $setStateUrlRestartMaster;

    private $isErrorSavingLocalButton = false;
    private $isErrorSavingServerButton = false;
    
    private function setLoginIsOk($isOk)
    {
        $this->isLoginOk = $isOk;
    }

    private function setUserPasswordInJeedom($user, $password)
    {
        $this->userIdInJeedom = $user;
        $this->passwordInJeedom = $password;
    }

    private function initTestData()
    {
        $this->loginObject = new \stdClass();

        if ($this->isLoginOk == false) {
            @$this->loginObject->status = 'ko';
            @$this->loginObject->error = 'error';
        } else {
            @$this->loginObject->status = 'ok';
            @$this->loginObject->authToken = $this->currentLoginToken;
        }

        $this->loadDevicesServerObject = new \stdClass();
        
        if ($this->isLoadingDevicesFromJeedomServerOk == false) {
            @$this->loadDevicesServerObject->status = 'ko';
            @$this->loadDevicesServerObject->error = 'error';
        } else {
            @$this->loadDevicesServerObject->status = 'ok';
            @$this->loadDevicesServerObject->devices = $this->mystromServerDevices;
        }

        $this->loginUrl = $this->myStromApiUrl . "/auth?email=" . $this->userIdInJeedom
        . "&password=" . $this->passwordInJeedom;

        $this->loadDevicesFromServerUrl = $this->myStromApiUrl . "/devices?" .
        "authToken=" . $this->loginTokenInJeedom;

        $this->loadDevicesFromServerWithReportUrl = $this->myStromApiUrl . "/devices?report=true&" .
        "authToken=" . $this->loginTokenInJeedom;

        $this->setStateUrl = $this->myStromApiUrl . "/switch?" .
        "authToken=" . $this->loginTokenInJeedom;

        $this->setStateUrlRestartMaster = $this->myStromApiUrl . "/restart?" .
        "authToken=" . $this->loginTokenInJeedom;

        $this->setStateObject = new \stdClass();
        
        if ($this->isSetStateOk == false) {
            @$this->setStateObject->status = 'ko';
            @$this->setStateObject->error = 'error';
        }

        $this->target->method('doJsonCall')
        ->will($this->returnCallBack(array($this, 'getJson')));

        $this->target->method('doHttpCall')
        ->will($this->returnCallBack(array($this, 'doHttpCall')));

        $this->jeedomHelper
        ->method('savePluginConfiguration')
        ->will($this->returnCallBack(array($this, 'saveInJeedomConfiguration')));

        $this->jeedomHelper
        ->method('loadPluginConfiguration')
        ->will($this->returnCallBack(array($this, 'loadPluginConfiguration')));
    }

    public function addDeviceOnMystromServer($id, $type, $name, $state, $power, $temperature = null, $color = null)
    {
        $this->isLoadingDevicesFromJeedomServerOk = true;

        $device1 = new \stdClass();
        @$device1->id = $id;
        @$device1->type = $type;
        @$device1->name = $name;
        @$device1->state = $state;
        @$device1->power = $power;
        @$device1->bulbColor = $color;
        @$device1->wifiSwitchTemp = $temperature;
        $energyReport = new \stdClass();
        @$energyReport->daylyConsumption = 15;
        @$energyReport->monthlyConsumption = 110;
        @$device1->energyReport = $energyReport;

        array_push($this->mystromServerDevices, $device1);
    }

    public function addLocalButton($macAddress, $ipAddress){
        $button = new \stdClass();
        $button->macAddress = $macAddress;
        $button->ipAddress = $ipAddress;
        $button->single = "";
        $button->double = "";
        $button->long = "";
        $button->touch = "";

        array_push($this->mystromLocalDevices, $button);
    }

    public function addLocalBulb($macAddress, $ipAddress, $on, $power, $color)
    {
        $bulb = new \stdClass();
        $bulb->macAddress = $macAddress;
        $bulb->ipAddress = $ipAddress;
        $bulb->on = $on;
        $bulb->power = $power;
        $bulb->color = $color;

        array_push($this->mystromLocalDevices, $bulb);
    }

    public function loadPluginConfiguration($key)
    {
        if ($key == "authToken") {
            return $this->loginTokenInJeedom;
        }
        
        if ($key == "userId") {
            return $this->userIdInJeedom;
        }

        if ($key == "password") {
            return $this->passwordInJeedom;
        }

        if($key == "internalAddr") {
            return $this->jeedomIpAddress;
        }
    }

    public function saveInJeedomConfiguration($key, $value)
    {
        if ($key == 'authToken') {
            $this->loginTokenInJeedom = $value;
        }
    }

    public function doHttpCall($url, $data, $verb)
    {
        if($verb == "GET")
        {
            if(strpos($url, "/api/v1/device") != false)
            {
                $deviceIpAddressPos = strpos($url, "http://") + 7;
                $deviceIpAddressEndPos = strpos($url, "/api", $deviceIpAddressPos);
                $deviceIpAddress = substr($url, $deviceIpAddressPos, $deviceIpAddressEndPos - $deviceIpAddressPos);

                foreach ($this->mystromLocalDevices as $device) {
                    if($device->ipAddress == $deviceIpAddress)
                    {
                        $jsonData = "{\"" . $device->macAddress .
                            "\":" . json_encode($device) . "}";
                        return $jsonData;
                    }
                }
            }

            if(strpos($url, "/device/setSettings") != false)
            {
                if($this->isErrorSavingServerButton == true)
                {
                    $resultHttp = new MyStromApiResult();
                    $resultHttp->status = "ko";
                    $resultHttp->error = "settings.update.failed";

                    return json_encode($resultHttp);
                }

                $parts = parse_url($url);
                parse_str($parts["query"], $query);

                $deviceId = $query["id"];
                $singleUrl = $query["localSingleUrl"];
                $doubleUrl = $query["localDoubleUrl"];
                $longUrl = $query["localLongUrl"];

                if(key_exists("localTouchUrl", $query))
                {
                    $touchUrl = $query["localTouchUrl"];
                }

                foreach ($this->mystromServerDevices as $device) {
                    if($device->id == $deviceId)
                    {
                        $device->single = $singleUrl;
                        $device->double = $doubleUrl;
                        $device->long = $longUrl;

                        if(isset($touchUrl))
                        {
                            $device->touch = $touchUrl;
                        }
                    }
                }

                $resultHttp = new MyStromApiResult();
                $resultHttp->status = "ok";
                return json_encode($resultHttp);
            }
        }
        
        if($verb == "POST")
        {
            if($this->isErrorSavingLocalButton == true)
            {
                throw new \Exception("Error setup in mock");
            }

            if(strpos($url, "/api/v1/device") != false)
            {
                $deviceIpAddressPos = strpos($url, "http://") + 7;
                $deviceIpAddressEndPos = strpos($url, "/api", $deviceIpAddressPos);
                $deviceIpAddress = substr($url, $deviceIpAddressPos, $deviceIpAddressEndPos - $deviceIpAddressPos);

                foreach ($this->mystromLocalDevices as $device) {
                    if($device->ipAddress == $deviceIpAddress)
                    {
                        $equalPos = strpos($data, "=") + 1;
                        $actionName = substr($data, 0, $equalPos -1);
                        $actionValue = substr($data, $equalPos);

                        switch ($actionName) {
                            case "single":
                                $device->single = $data;
                                break;
                            
                            case "double":
                                $device->double = $data;
                                break;

                            case "long":
                                $device->long = $data;
                                break;

                            case "touch":
                                $device->touch = $data;
                                break;

                            default:
                                break;
                        }
                    }
                }

                return "";
            }            
        }
    }

    public function getJson($url)
    {
        if ($url == $this->loginUrl) {
            return $this->loginObject;
        }

        if ($url == $this->loadDevicesFromServerUrl) {
            return $this->loadDevicesServerObject;
        }

        if ($url == $this->loadDevicesFromServerWithReportUrl) {
            return $this->loadDevicesServerObject;
        }

        if(strpos($url, $this->setStateUrlRestartMaster) == 0){
            foreach ($this->mystromServerDevices as $device) {
                if($device->type != "mst"){
                    $device->state = "offline";
                }
            }
        }

        if(strpos($url, $this->setStateUrl) == 0){
            $parts = parse_url($url);
            parse_str($parts["query"], $query);

            $isColor = false;

            $on = false;
            if(strpos($url, "true") != false){
                $on = true;
            }

            if(key_exists("color", $query))
            {
                $isColor = true;
            }

            $deviceId = $query["id"];
            
            foreach ($this->mystromServerDevices as $device) {
                if($device->id == $deviceId){
                    if($isColor == false)
                    {
                        $device->state = $on == true ? "on" : "off";
                    } else {
                        $device->color = $query["color"];
                    }
                }
            }

            return $this->setStateObject;
        }

        if(strpos($url, $this->setStateUrlRestartMaster) == 0){
            return $this->setStateObject;
        }
    }

    protected function setUp()
    {
        $this->currentLoginToken = null;
        $this->isLoadingDevicesFromJeedomServerOk = false;
        $this->isLoginOk = false;
        $this->mystromServerDevices = array();
        $this->mystromLocalDevices = array();
        $this->isSetStateOk = false;
        $this->isErrorSavingLocalButton = false;
        $this->isErrorSavingServerButton = false;

        $this->jeedomHelper = $this->getMockBuilder(JeedomHelper::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'logError',
        'loadPluginConfiguration',
        'savePluginConfiguration',
        'getEqLogicByLogicalId',
        'createAndSaveEqLogic',
        'createCmd',
        'getJeedomApiKey'])
        ->getMock();

        $this->target = $this->getMockBuilder(MyStromService::class)
        ->enableOriginalConstructor()
        ->setConstructorArgs([$this->jeedomHelper])
        ->setMethods(["doJsonCall", "doHttpCall"])
        ->getMock();

        $this->jeedomHelper->method('getJeedomApiKey')
        ->willReturn('FFFFFF');
    }

    public function testDoAuthentificationWhenErrorShouldReturnFalse()
    {
        $this->initTestData();
        
        $result = $this->target->doAuthentification();
        
        $this->assertFalse($result);
    }
    
    public function testDoAuthentificationWhenOkShouldSaveTheToken()
    {
        $this->isLoginOk = true;
        $this->setUserPasswordInJeedom("userId", "ssss");
        $this->initTestData();

        $result = $this->target->doAuthentification();
        
        $this->assertTrue($result);
        $this->assertEquals($this->currentLoginToken, $this->loginTokenInJeedom);
    }
    
    public function testLoadAllDevicesWhenErrorShouldReturnTheError()
    {
        $this->initTestData();
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals($result->error, $this->loadDevicesServerObject->error);
    }
    
    public function testLoadAllDevicesWhenOkShouldReturnTheDevices()
    {
        $this->addDeviceOnMystromServer('1234', 'eth', 'device1', 'on', '1');
        $this->addDeviceOnMystromServer('4321', 'mst', 'device2', 'off', '0');
        $this->initTestData();
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 2);
        $this->assertEquals($result->devices[0]->id, $this->mystromServerDevices[0]->id);
        $this->assertEquals($result->devices[0]->type, $this->mystromServerDevices[0]->type);
        $this->assertEquals($result->devices[0]->name, $this->mystromServerDevices[0]->name);
        $this->assertEquals($result->devices[0]->state, $this->mystromServerDevices[0]->state);
        $this->assertEquals($result->devices[0]->power, $this->mystromServerDevices[0]->power);
        
        $this->assertEquals($result->devices[1]->id, $this->mystromServerDevices[1]->id);
        $this->assertEquals($result->devices[1]->type, $this->mystromServerDevices[1]->type);
        $this->assertEquals($result->devices[1]->name, $this->mystromServerDevices[1]->name);
        $this->assertEquals($result->devices[1]->power, $this->mystromServerDevices[1]->power);
    }

    public function testLoadAllDevicesWhenButtonPlusShouldReturnTheButtonClass()
    {
        $this->addDeviceOnMystromServer('1234', 'wbp', 'device1', 'on', '1');
        $this->initTestData();
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 1);
        $this->assertEquals($result->devices[0]->id, $this->mystromServerDevices[0]->id);
        $this->assertEquals($result->devices[0]->type, $this->mystromServerDevices[0]->type);
        $this->assertEquals($result->devices[0]->name, $this->mystromServerDevices[0]->name);
        $this->assertEquals($result->devices[0]->state, $this->mystromServerDevices[0]->state);
        $this->assertTrue($result->devices[0] instanceof MystromButtonDevice);
    }

    public function testLoadAllDevicesWhenButtonSimpleShouldReturnTheButtonClass()
    {
        $this->addDeviceOnMystromServer('1234', 'wbs', 'device1', 'on', '1');
        $this->initTestData();
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 1);
        $this->assertEquals($result->devices[0]->id, $this->mystromServerDevices[0]->id);
        $this->assertEquals($result->devices[0]->type, $this->mystromServerDevices[0]->type);
        $this->assertEquals($result->devices[0]->name, $this->mystromServerDevices[0]->name);
        $this->assertEquals($result->devices[0]->state, $this->mystromServerDevices[0]->state);
        $this->assertTrue($result->devices[0] instanceof MystromButtonDevice);
    }

    public function testLoadAllDevicesWhenWifiSwitchEuropeShouldReturnTheWifiSwithEuropeClass()
    {
        $this->addDeviceOnMystromServer('1234', 'wse', 'device1', 'on', '1', '21');
        $this->initTestData();

        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 1);
        $this->assertEquals($result->devices[0]->id, $this->mystromServerDevices[0]->id);
        $this->assertEquals($result->devices[0]->type, $this->mystromServerDevices[0]->type);
        $this->assertEquals($result->devices[0]->name, $this->mystromServerDevices[0]->name);
        $this->assertEquals($result->devices[0]->state, $this->mystromServerDevices[0]->state);
        $this->assertEquals($result->devices[0]->temperature, $this->mystromServerDevices[0]->wifiSwitchTemp);
        $this->assertTrue($result->devices[0] instanceof \coolweb\mystrom\MystromWifiSwitchEurope);
    }

    public function testLoadAllDevicesWhenWifiBulbShouldReturnTheWifiBulbClass()
    {
        $this->addDeviceOnMystromServer('1234', 'wrb', 'device1', 'on', '1', null, "124;100;100");
        $this->initTestData();
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 1);
        $this->assertEquals($result->devices[0]->id, $this->mystromServerDevices[0]->id);
        $this->assertEquals($result->devices[0]->type, $this->mystromServerDevices[0]->type);
        $this->assertEquals($result->devices[0]->name, $this->mystromServerDevices[0]->name);
        $this->assertEquals($result->devices[0]->state, $this->mystromServerDevices[0]->state);
        $this->assertEquals($result->devices[0]->color, "#00ff11");
        $this->assertTrue($result->devices[0] instanceof \coolweb\mystrom\MystromWifiBulb);
    }

    public function testWhenSetBulbColor()
    {
        // Arrange
        $this->addDeviceOnMystromServer("1234", "wrb", "device1", "on", "1", "21");
        $this->initTestData();

        // Act
        $wifiBulb = new \coolweb\mystrom\MystromWifiBulb();
        $wifiBulb->id = "1234";
        $this->target->setBulbColor($wifiBulb, "#00ff11");

        // Assert
        $this->assertEquals($this->mystromServerDevices[0]->color, "124;100;100");
    }

    public function testLoadAllDevicesWhenLoadReportDataShouldReturnTheConsumptions()
    {
        $this->addDeviceOnMystromServer('1234', 'eth', 'device1', 'on', '1');
        $this->initTestData();

        $result = $this->target->loadAllDevicesFromServer(true);
        
        $this->assertEquals(count($result->devices), 1);
        $this->assertEquals($result->devices[0]->id, $this->mystromServerDevices[0]->id);
        $this->assertEquals($result->devices[0]->type, $this->mystromServerDevices[0]->type);
        $this->assertEquals($result->devices[0]->name, $this->mystromServerDevices[0]->name);
        $this->assertEquals($result->devices[0]->daylyConsumption, $this->mystromServerDevices[0]->energyReport->daylyConsumption);
        $this->assertEquals($result->devices[0]->monthlyConsumption, $this->mystromServerDevices[0]->energyReport->monthlyConsumption);
    }
    
    public function testSetStateWhenErrorShouldReturnTheError()
    {
        $this->initTestData();
        
        $result = $this->target->setState('1234', 'eth', true);
        
        $this->assertEquals($result->status, 'ko');
        $this->assertEquals($result->error, 'error');
    }
    
    public function testSetStateWhenStateIsOnShouldSetStateOn()
    {
        $this->addDeviceOnMystromServer('1234', 'eth', 'device1', 'off', '1');
        $this->initTestData();

        $result = $this->target->setState('1234', 'eth', true);
        
        $this->assertEquals($this->mystromServerDevices[0]->state, 'on');
    }
    
    public function testSetStateWhenStateIsOffSetStateOff()
    {
        $this->addDeviceOnMystromServer('1234', 'eth', 'device1', 'on', '1');
        $this->initTestData();

        $result = $this->target->setState('1234', 'eth', false);
        
        $this->assertEquals($this->mystromServerDevices[0]->state, 'off');
    }
    
    public function testSetStateWhenDeviceIsMasterShouldDoAReset()
    {
        $this->addDeviceOnMystromServer('1234', 'eth', 'device1', 'on', '1');
        $this->addDeviceOnMystromServer('4444', 'mst', 'device1', 'on', '1');
        $this->initTestData();

        $result = $this->target->setState('4444', 'mst', false);
        
        $this->assertEquals($this->mystromServerDevices[0]->state, 'offline');
    }

    public function testWhenRetrieveLocalBulbInfo_ShouldReturnCorrectInfo()
    {
        $this->addLocalBulb("7C2F1D4G5H", "192.168.1.2", true, 2.5, "124;100;100");
        $this->initTestData();

        $bulbInfo = $this->target->RetrieveLocalRgbBulbInfo("192.168.1.2");

        $this->assertEquals($bulbInfo->macAddress, "7C2F1D4G5H");
        $this->assertEquals($bulbInfo->state, "on");
        $this->assertEquals($bulbInfo->power, "2.5");
        $this->assertEquals($bulbInfo->color, "#00ff11");
    }

    public function testWhenRetrieveLocalButtonInfo_ShouldGetTheMACAddress()
    {
        $this->addLocalButton("7C2F1D4G5H", "192.168.1.2");
        $this->initTestData();

        $buttonInfo = $this->target->RetrieveLocalButtonInfo("192.168.1.2");

        $this->assertEquals($buttonInfo->macAddress, "7C2F1D4G5H");
    }

    public function testSaveUrlsForLocalWifiButtonPlusWhenSaved_ShouldSaveUrls()
    {        
        $button = new MystromButtonDevice();
        $button->ipAddress = '192.168.1.2';
        $button->macAddress = 'F1G2H3J5';
        $button->isLocal= true;
        $this->addLocalButton($button->macAddress, $button->ipAddress);

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $singleUrl = 'single=get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey%3D'
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $singleId;
        $doubleUrl = 'double=get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey%3D'
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $doubleId;
        $longUrl = 'long=get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey%3D'
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $longId;
        $touchUrl = 'touch=get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey%3D'
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $touchId;
        $this->initTestData();

        $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);

        $this->assertEquals($this->mystromLocalDevices[0]->single, $singleUrl);
        $this->assertEquals($this->mystromLocalDevices[0]->double, $doubleUrl);
        $this->assertEquals($this->mystromLocalDevices[0]->long, $longUrl);
        $this->assertEquals($this->mystromLocalDevices[0]->touch, $touchUrl);
    }

    public function testSaveUrlsForLocalWifiButtonSimpleWhenSaved_ShouldSaveUrls()
    {
        $button = new MystromButtonDevice();
        $button->ipAddress = '192.168.1.2';
        $button->macAddress = 'F1G2H3J5';
        $button->isLocal= true;
        $button->type = 'wbs';

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';

        $singleUrl = 'single=get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey%3D'
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $singleId;
        $doubleUrl = 'double=get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey%3D'
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $doubleId;
        $longUrl = 'long=get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey%3D'
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $longId;
       
        $this->addLocalButton($button->macAddress, $button->ipAddress);
        $this->initTestData();
            
        $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId);

        $this->assertEquals($this->mystromLocalDevices[0]->single, $singleUrl);
        $this->assertEquals($this->mystromLocalDevices[0]->double, $doubleUrl);
        $this->assertEquals($this->mystromLocalDevices[0]->long, $longUrl);
        $this->assertEquals($this->mystromLocalDevices[0]->touch, "");
    }

    public function testSaveUrlsForLocalWifiButtonWhenButtonNotReachable_ShouldReturnFalse()
    {
        $button = new MystromButtonDevice();
        $button->ipAddress = '192.168.1.2';
        $button->macAddress = 'F1G2H3J5';
        $button->isLocal= true;

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $this->isErrorSavingLocalButton = true;
        $this->initTestData();

        $result = $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);

        $this->assertFalse(false);
    }

    public function testSaveUrlsForServerWifiButtonSimpleWhenSaved_ShouldCallTheCorrectUrls()
    {
        $button = new MystromButtonDevice();
        $button->id = '123456';
        $button->isLocal = false;
        $button->type = 'wbs';
        $this->addDeviceOnMystromServer($button->id, $button->type, $button->name, "on", "4");

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';

        $singleUrl = 'get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey='
            . 'FFFFFF' . '&type=cmd&id=' . $singleId;
        $doubleUrl = 'get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey='
            . 'FFFFFF' . '&type=cmd&id=' . $doubleId;
        $longUrl = 'get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey='
            . 'FFFFFF' . '&type=cmd&id=' . $longId;        

        $this->initTestData();

        $result = $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId);

        $this->assertTrue($result);
        $this->assertEquals($this->mystromServerDevices[0]->single, $singleUrl);
        $this->assertEquals($this->mystromServerDevices[0]->double, $doubleUrl);
        $this->assertEquals($this->mystromServerDevices[0]->long, $longUrl);
    }

    public function testSaveUrlsForServerWifiButtonPlusWhenSaved_ShouldCallTheCorrectUrls()
    {
        $button = new MystromButtonDevice();
        $button->id = '123456';
        $button->isLocal = false;
        $button->type = 'wbp';
        $this->addDeviceOnMystromServer($button->id, $button->type, $button->name, "on", "4");

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $singleUrl = 'get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey='
            . 'FFFFFF' . '&type=cmd&id=' . $singleId;
        $doubleUrl = 'get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey='
            . 'FFFFFF' . '&type=cmd&id=' . $doubleId;
        $longUrl = 'get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey='
            . 'FFFFFF' . '&type=cmd&id=' . $longId;        
        $touchUrl = 'get://' . $this->jeedomIpAddress . '/core/api/jeeApi.php?apikey='
            . 'FFFFFF' . '&type=cmd&id=' . $touchId;        

        $this->initTestData();

        $result = $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);

        $this->assertTrue($result);
        $this->assertEquals($this->mystromServerDevices[0]->single, $singleUrl);
        $this->assertEquals($this->mystromServerDevices[0]->double, $doubleUrl);
        $this->assertEquals($this->mystromServerDevices[0]->long, $longUrl);
        $this->assertEquals($this->mystromServerDevices[0]->touch, $touchUrl);
    }

    public function testSaveUrlsForServerWifiButtonWhenErrorFromServer_ShouldReturnFalse()
    {
        // Arrange
        $button = new MystromButtonDevice();
        $button->id = '123456';
        $button->isLocal = false;

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $this->isErrorSavingServerButton = true;
        $this->initTestData();

        // Act
        $result = $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);

        // Assert
        $this->assertFalse($result);
    }
}
