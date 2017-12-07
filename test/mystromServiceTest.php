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

    protected function setUp()
    {
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
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ko';
        @$jsonObject->error = 'error';
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $result = $this->target->doAuthentification();
        
        $this->assertFalse($result);
    }
    
    public function testDoAuthentificationWhenOkShouldSaveTheToken()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->authToken = '1234';
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $this->jeedomHelper->expects($this->once())
        ->method('savePluginConfiguration')
        ->with($this->equalTo('authToken'), $this->equalTo($jsonObject->authToken));
        
        $result = $this->target->doAuthentification();
        
        $this->assertTrue($result);
    }
    
    public function testLoadAllDevicesWhenErrorShouldReturnTheError()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ko';
        @$jsonObject->error = 'error';
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals($result->error, $jsonObject->error);
    }
    
    public function testLoadAllDevicesWhenOkShouldReturnTheDevices()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->devices = array();
        
        $device1 = new \stdClass();
        @$device1->id = '1234';
        @$device1->type = 'eth';
        @$device1->name = 'device1';
        @$device1->state = 'on';
        @$device1->power = '1';
        array_push($jsonObject->devices, $device1);
        
        $device2 = new \stdClass();
        @$device2->id = '4321';
        @$device2->type = 'mst';
        @$device2->name = 'device2';
        @$device2->state = 'off';
        @$device2->power = '0';
        array_push($jsonObject->devices, $device2);
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);

        $this->target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/devices?authToken='));
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 2);
        $this->assertEquals($result->devices[0]->id, $device1->id);
        $this->assertEquals($result->devices[0]->type, $device1->type);
        $this->assertEquals($result->devices[0]->name, $device1->name);
        $this->assertEquals($result->devices[0]->state, $device1->state);
        $this->assertEquals($result->devices[0]->power, $device1->power);
        
        $this->assertEquals($result->devices[1]->id, $device2->id);
        $this->assertEquals($result->devices[1]->type, $device2->type);
        $this->assertEquals($result->devices[1]->name, $device2->name);
        $this->assertEquals($result->devices[1]->power, $device2->power);
    }

    public function testLoadAllDevicesWhenButtonPlusShouldReturnTheButtonClass()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->devices = array();
        
        $device1 = new \stdClass();
        @$device1->id = '1234';
        @$device1->type = 'wbp';
        @$device1->name = 'device1';
        @$device1->state = 'offline';
        @$device1->power = '0';
        array_push($jsonObject->devices, $device1);
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);

        $this->target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/devices?authToken='));
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 1);
        $this->assertEquals($result->devices[0]->id, $device1->id);
        $this->assertEquals($result->devices[0]->type, $device1->type);
        $this->assertEquals($result->devices[0]->name, $device1->name);
        $this->assertEquals($result->devices[0]->state, $device1->state);
        $this->assertTrue($result->devices[0] instanceof MystromButtonDevice);
    }

    public function testLoadAllDevicesWhenButtonSimpleShouldReturnTheButtonClass()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->devices = array();
        
        $device1 = new \stdClass();
        @$device1->id = '1234';
        @$device1->type = 'wbs';
        @$device1->name = 'device1';
        @$device1->state = 'offline';
        @$device1->power = '0';
        array_push($jsonObject->devices, $device1);
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);

        $this->target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/devices?authToken='));
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 1);
        $this->assertEquals($result->devices[0]->id, $device1->id);
        $this->assertEquals($result->devices[0]->type, $device1->type);
        $this->assertEquals($result->devices[0]->name, $device1->name);
        $this->assertEquals($result->devices[0]->state, $device1->state);
        $this->assertTrue($result->devices[0] instanceof MystromButtonDevice);
    }

    public function testLoadAllDevicesWhenWifiSwitchEuropeShouldReturnTheWifiSwithEuropeClass()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->devices = array();
        
        $device1 = new \stdClass();
        @$device1->id = '1234';
        @$device1->type = 'wse';
        @$device1->name = 'device1';
        @$device1->state = 'offline';
        @$device1->power = '0';
        @$device1->wifiSwitchTemp = '21';
        array_push($jsonObject->devices, $device1);
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);

        $this->target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/devices?authToken='));
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 1);
        $this->assertEquals($result->devices[0]->id, $device1->id);
        $this->assertEquals($result->devices[0]->type, $device1->type);
        $this->assertEquals($result->devices[0]->name, $device1->name);
        $this->assertEquals($result->devices[0]->state, $device1->state);
        $this->assertEquals($result->devices[0]->temperature, $device1->wifiSwitchTemp);
        $this->assertTrue($result->devices[0] instanceof \coolweb\mystrom\MystromWifiSwitchEurope);
    }

    /**public function testLoadAllDevicesWhenWifiBulbShouldReturnTheWifiBulbClass()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->devices = array();
        
        $device1 = new \stdClass();
        @$device1->id = '1234';
        @$device1->type = 'wrb';
        @$device1->name = 'device1';
        @$device1->state = 'offline';
        @$device1->power = '0';
        array_push($jsonObject->devices, $device1);
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);

        $this->target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/devices?authToken='));
        
        $result = $this->target->loadAllDevicesFromServer();
        
        $this->assertEquals(count($result->devices), 1);
        $this->assertEquals($result->devices[0]->id, $device1->id);
        $this->assertEquals($result->devices[0]->type, $device1->type);
        $this->assertEquals($result->devices[0]->name, $device1->name);
        $this->assertEquals($result->devices[0]->state, $device1->state);
        $this->assertTrue($result->devices[0] instanceof \coolweb\mystrom\MystromWifiBulb);
    }*/

    /*public function testWhenSetBulbColor()
    {
        $wifiBulb = new \coolweb\mystrom\MystromWifiBulb();
        $this->target->setBulbColor($wifiBulb, "#00ff11");
    }*/

    public function testLoadAllDevicesWhenLoadReportDataShouldReturnTheConsumptions()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->devices = array();
        
        $device1 = new \stdClass();
        @$device1->id = '1234';
        @$device1->type = 'eth';
        @$device1->name = 'device1';
        @$device1->state = 'on';
        @$device1->power = '1';
        @$device1->energyReport->daylyConsumption = 12;
        @$device1->energyReport->monthlyConsumption = 100;
        array_push($jsonObject->devices, $device1);
        
        $device2 = new \stdClass();
        @$device2->id = '4321';
        @$device2->type = 'mst';
        @$device2->name = 'device2';
        @$device2->state = 'on';
        @$device2->power = '1';
        @$device2->energyReport->daylyConsumption = 15;
        @$device2->energyReport->monthlyConsumption = 110;
        array_push($jsonObject->devices, $device2);
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);

        $this->target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/devices?report=true&authToken='));
        
        $result = $this->target->loadAllDevicesFromServer(true);
        
        $this->assertEquals(count($result->devices), 2);
        $this->assertEquals($result->devices[0]->id, $device1->id);
        $this->assertEquals($result->devices[0]->type, $device1->type);
        $this->assertEquals($result->devices[0]->name, $device1->name);
        $this->assertEquals($result->devices[0]->daylyConsumption, $device1->energyReport->daylyConsumption);
        $this->assertEquals($result->devices[0]->monthlyConsumption, $device1->energyReport->monthlyConsumption);
        
        $this->assertEquals($result->devices[1]->id, $device2->id);
        $this->assertEquals($result->devices[1]->type, $device2->type);
        $this->assertEquals($result->devices[1]->name, $device2->name);
        $this->assertEquals($result->devices[1]->daylyConsumption, $device2->energyReport->daylyConsumption);
        $this->assertEquals($result->devices[1]->monthlyConsumption, $device2->energyReport->monthlyConsumption);
    }
    
    public function testSetStateWhenErrorShouldReturnTheError()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ko';
        @$jsonObject->error = 'error';
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $result = $this->target->setState('1234', 'eth', true);
        
        $this->assertEquals($result->status, 'ko');
        $this->assertEquals($result->error, 'error');
    }
    
    public function testSetStateWhenStateIsOnShouldCallTheCorrectUrl()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $this->target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/device/switch?authToken=&id=1234&on=true'));
        
        $result = $this->target->setState('1234', 'eth', true);
        
        $this->assertEquals($result->status, 'ok');
    }
    
    public function testSetStateWhenStateIsOffShouldCallTheCorrectUrl()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $this->target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/device/switch?authToken=&id=1234&on=false'));
        
        $result = $this->target->setState('1234', 'eth', false);
        
        $this->assertEquals($result->status, 'ok');
    }
    
    public function testSetStateWhenDeviceIsMasterShouldDoAReset()
    {
        $jsonObject = new \stdClass();
        @$jsonObject->status = 'ok';
        
        $this->target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $this->target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/device/restart?authToken=&id=1234'));
        
        $result = $this->target->setState('1234', 'mst', false);
        
        $this->assertEquals($result->status, 'ok');
    }

    public function testWhenRetrieveLocalButtonInfo_ShouldGetTheMACAddress()
    {
        $jsonResult = '{"7C2F1D4G5H":{"type": "button", "battery": true, "reachable": true, "meshroot": false, "charge": false, "voltage": 3.705, "fw_version": "2.37", "single": "", "double": "", "long": "", "touch": ""}}';
        $this->target->method('doHttpCall')
        ->willReturn($jsonResult);

        $buttonInfo = $this->target->RetrieveLocalButtonInfo('192.168.1.2');

        $this->assertEquals($buttonInfo->macAddress, '7C2F1D4G5H');
    }

    public function testSaveUrlsForLocalWifiButtonPlusWhenSaved_ShouldCallTheCorrectUrls()
    {
        $this->jeedomHelper->method('logWarning')
        ->will($this->returnCallback(function($message){
            throw new Exception($message);
        }));

        $jeedomIp = '192.168.1.10';
        $this->jeedomHelper->method("loadPluginConfiguration")
        ->with(
            $this->equalTo('internalAddr'),
            $this->equalTo(true)
        )
        ->willReturn($jeedomIp);

        $button = new MystromButtonDevice();
        $button->ipAddress = '192.168.1.2';
        $button->macAddress = 'F1G2H3J5';
        $button->isLocal= true;

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $buttonUrl = 'http://' . $button->ipAddress . '/api/v1/device/' . $button->macAddress;
        $singleUrl = 'single=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $singleId;
        $doubleUrl = 'double=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $doubleId;
        $longUrl = 'long=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $longId;
        $touchUrl = 'touch=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $touchId;

        $this->target->expects($this->exactly(4))
        ->method('doHttpCall')
        ->withConsecutive(
            [$this->equalTo($buttonUrl), $this->equalTo($singleUrl), $this->equalTo('POST')],
            [$this->equalTo($buttonUrl), $this->equalTo($doubleUrl), $this->equalTo('POST')],
            [$this->equalTo($buttonUrl), $this->equalTo($longUrl), $this->equalTo('POST')],
            [$this->equalTo($buttonUrl), $this->equalTo($touchUrl), $this->equalTo('POST')]
        )
        ->willReturnOnConsecutiveCalls('','','','');

        $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);
    }

    public function testSaveUrlsForLocalWifiButtonSimpleWhenSaved_ShouldCallTheCorrectUrls()
    {
        $this->jeedomHelper->method('logWarning')
        ->will($this->returnCallback(function($message){
            throw new Exception($message);
        }));

        $jeedomIp = '192.168.1.10';
        $this->jeedomHelper->method('loadPluginConfiguration')
        ->with(
            $this->equalTo('internalAddr'),
            $this->equalTo(true)
        )
        ->willReturn($jeedomIp);

        $button = new MystromButtonDevice();
        $button->ipAddress = '192.168.1.2';
        $button->macAddress = 'F1G2H3J5';
        $button->isLocal= true;
        $button->type = 'wbs';

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';

        $buttonUrl = 'http://' . $button->ipAddress . '/api/v1/device/' . $button->macAddress;
        $singleUrl = 'single=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $singleId;
        $doubleUrl = 'double=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $doubleId;
        $longUrl = 'long=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $longId;
       
        $this->target->expects($this->exactly(3))
        ->method('doHttpCall')
        ->withConsecutive(
            [$this->equalTo($buttonUrl), $this->equalTo($singleUrl), $this->equalTo('POST')],
            [$this->equalTo($buttonUrl), $this->equalTo($doubleUrl), $this->equalTo('POST')],
            [$this->equalTo($buttonUrl), $this->equalTo($longUrl), $this->equalTo('POST')]            
        )
        ->willReturnOnConsecutiveCalls('','','');

        $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId);
    }

    public function testSaveUrlsForLocalWifiButtonWhenButtonNotReachable_ShouldReturnFalse()
    {
        $jeedomIp = '192.168.1.10';
        $this->jeedomHelper->method('loadPluginConfiguration')
        ->with(
            $this->equalTo('internalAddr'),
            $this->equalTo(true)
        )
        ->willReturn($jeedomIp);

        $button = new MystromButtonDevice();
        $button->ipAddress = '192.168.1.2';
        $button->macAddress = 'F1G2H3J5';
        $button->isLocal= true;

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $this->target->expects($this->once())
        ->method('doHttpCall')
        ->with(
            $this->equalTo('http://' . $button->ipAddress . '/api/v1/device/' . $button->macAddress),
            $this->equalTo('single=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $singleId),
            'POST')
        ->will($this->throwException(new \Exception));

        $result = $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);

        $this->assertFalse(false);
    }

    public function testSaveUrlsForServerWifiButtonSimpleWhenSaved_ShouldCallTheCorrectUrls()
    {
        $this->jeedomHelper->method('logWarning')
        ->will($this->returnCallback(function($message){
            throw new \Exception($message);
        }));

        $jeedomIp = '192.168.1.10';
        $authToken = 'xyz';
        $this->jeedomHelper->method('loadPluginConfiguration')
        ->withConsecutive(
            [$this->equalTo('internalAddr'), $this->equalTo(true)],
            [$this->equalTo('authToken')]
        )
        ->willReturnOnConsecutiveCalls($jeedomIp, $authToken);

        $button = new MystromButtonDevice();
        $button->id = '123456';
        $button->isLocal = false;
        $button->type = 'wbs';

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';

        $serverUrl = 'https://www.mystrom.ch/mobile/device/setSettings?authToken=' . $authToken .
        '&id=' . $button->id;
        $singleUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $singleId;
        $doubleUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $doubleId;
        $longUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $longId;
        $urlToBeCalled = $serverUrl .
                        '&localSingleUrl=' . $singleUrl .
                        '&localDoubleUrl=' . $doubleUrl .
                        '&localLongUrl=' . $longUrl;
        
        $resultHttp = new MyStromApiResult();
        $resultHttp->status = 'ok';

        $this->target->expects($this->once())
        ->method('doHttpCall')
        ->with($this->equalTo($urlToBeCalled), null, $this->equalTo('GET'))
        ->willReturn(json_encode($resultHttp));

        $result = $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId);
        $this->assertTrue($result);
    }

    public function testSaveUrlsForServerWifiButtonPlusWhenSaved_ShouldCallTheCorrectUrls()
    {
        $this->jeedomHelper->method('logWarning')
        ->will($this->returnCallback(function($message){
            throw new Exception($message);
        }));

        $jeedomIp = '192.168.1.10';
        $authToken = 'xyz';
        $this->jeedomHelper->method('loadPluginConfiguration')
        ->withConsecutive(
            [$this->equalTo('internalAddr'), $this->equalTo(true)],
            [$this->equalTo('authToken')]
        )
        ->willReturnOnConsecutiveCalls($jeedomIp, $authToken);

        $button = new MystromButtonDevice();
        $button->id = '123456';
        $button->isLocal = false;

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $serverUrl = 'https://www.mystrom.ch/mobile/device/setSettings?authToken=' . $authToken .
        '&id=' . $button->id;
        $singleUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $singleId;
        $doubleUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $doubleId;
        $longUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $longId;
        $touchUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $touchId;
        $urlToBeCalled = $serverUrl .
                        '&localSingleUrl=' . $singleUrl .
                        '&localDoubleUrl=' . $doubleUrl .
                        '&localLongUrl=' . $longUrl .
                        '&localTouchUrl=' . $touchUrl;
        
        $resultHttp = new MyStromApiResult();
        $resultHttp->status = 'ok';

        $this->target->expects($this->once())
        ->method('doHttpCall')
        ->with($this->equalTo($urlToBeCalled), null, $this->equalTo('GET'))
        ->willReturn(json_encode($resultHttp));

        $result = $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);
        $this->assertTrue($result);
    }

    public function testSaveUrlsForServerWifiButtonWhenErrorFromServer_ShouldReturnFalse()
    {
        $jeedomIp = '192.168.1.10';
        $authToken = 'xyz';
        $this->jeedomHelper->method('loadPluginConfiguration')
        ->withConsecutive(
            [$this->equalTo('internalAddr'), $this->equalTo(true)],
            [$this->equalTo('authToken')]
        )
        ->willReturnOnConsecutiveCalls($jeedomIp, $authToken);

        $button = new MystromButtonDevice();
        $button->id = '123456';
        $button->isLocal = false;

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $serverUrl = 'https://www.mystrom.ch/mobile/device/setSettings?authToken=' . $authToken .
        '&id=' . $button->id;
        $singleUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $singleId;
        $doubleUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $doubleId;
        $longUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $longId;
        $touchUrl = 'get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . 'FFFFFF' . '%26type%3Dcmd%26id%3D' . $touchId;
        $urlToBeCalled = $serverUrl .
                        '&localSingleUrl=' . $singleUrl .
                        '&localDoubleUrl=' . $doubleUrl .
                        '&localLongUrl=' . $longUrl .
                        '&localTouchUrl=' . $touchUrl;

        $resultHttp = new MyStromApiResult();
        $resultHttp->status = 'ko';
        $resultHttp->error = 'settings.update.failed';

        $this->target->expects($this->once())
        ->method('doHttpCall')
        ->with($this->equalTo($urlToBeCalled), null, $this->equalTo('GET'))
        ->willReturn(json_encode($resultHttp));

        $result = $this->target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);
        $this->assertFalse($result);
    }
}
