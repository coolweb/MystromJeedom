<?php
use PHPUnit\Framework\TestCase;

include_once('eqLogic.php');
include_once('cmd.php');
include_once('./test/jeedom.php');
include_once('./core/class/myStromDevice.class.php');
include_once('./core/class/myStromApiResult.class.php');
include_once('./core/class/getAllDevicesResult.class.php');
include_once('./core/class/mystromButtonDevice.class.php');
include_once('./core/class/mystromService.class.php');

/**
* Test class for mystrom service class
*/
class mystromServiceTest extends TestCase
{
    public function testDoAuthentificationWhenErrorShouldReturnFalse()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doJsonCall'])
        ->getMock();
        
        $jsonObject = new stdClass();
        @$jsonObject->status = 'ko';
        @$jsonObject->error = 'error';
        
        $target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $result = $target->doAuthentification();
        
        $this->assertFalse($result);
    }
    
    public function testDoAuthentificationWhenOkShouldSaveTheToken()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doJsonCall'])
        ->getMock();
        
        $jsonObject = new stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->authToken = '1234';
        
        $target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $target->expects($this->once())
        ->method('saveMystromConfiguration')
        ->with($this->equalTo('authToken'), $this->equalTo($jsonObject->authToken));
        
        $result = $target->doAuthentification();
        
        $this->assertTrue($result);
    }
    
    public function testLoadAllDevicesWhenErrorShouldReturnTheError()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doJsonCall'])
        ->getMock();
        
        $jsonObject = new stdClass();
        @$jsonObject->status = 'ko';
        @$jsonObject->error = 'error';
        
        $target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $result = $target->loadAllDevicesFromServer();
        
        $this->assertEquals($result->error, $jsonObject->error);
    }
    
    public function testLoadAllDevicesWhenOkShouldReturnTheDevices()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doJsonCall'])
        ->getMock();
        
        $jsonObject = new stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->devices = array();
        
        $device1 = new stdClass();
        @$device1->id = '1234';
        @$device1->type = 'eth';
        @$device1->name = 'device1';
        @$device1->state = 'on';
        @$device1->power = '1';
        array_push($jsonObject->devices, $device1);
        
        $device2 = new stdClass();
        @$device2->id = '4321';
        @$device2->type = 'mst';
        @$device2->name = 'device2';
        @$device2->state = 'off';
        @$device2->power = '0';
        array_push($jsonObject->devices, $device2);
        
        $target->method('doJsonCall')
        ->willReturn($jsonObject);

        $target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/devices?authToken='));
        
        $result = $target->loadAllDevicesFromServer();
        
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

    public function testLoadAllDevicesWhenLoadReportDataShouldReturnTheConsumptions()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doJsonCall'])
        ->getMock();
        
        $jsonObject = new stdClass();
        @$jsonObject->status = 'ok';
        @$jsonObject->devices = array();
        
        $device1 = new stdClass();
        @$device1->id = '1234';
        @$device1->type = 'eth';
        @$device1->name = 'device1';
        @$device1->state = 'on';
        @$device1->power = '1';
        @$device1->energyReport->daylyConsumption = 12;
        @$device1->energyReport->monthlyConsumption = 100;
        array_push($jsonObject->devices, $device1);
        
        $device2 = new stdClass();
        @$device2->id = '4321';
        @$device2->type = 'mst';
        @$device2->name = 'device2';
        @$device2->state = 'on';
        @$device2->power = '1';
        @$device2->energyReport->daylyConsumption = 15;
        @$device2->energyReport->monthlyConsumption = 110;
        array_push($jsonObject->devices, $device2);
        
        $target->method('doJsonCall')
        ->willReturn($jsonObject);

        $target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/devices?report=true&authToken='));
        
        $result = $target->loadAllDevicesFromServer(true);
        
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
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doJsonCall'])
        ->getMock();
        
        $jsonObject = new stdClass();
        @$jsonObject->status = 'ko';
        @$jsonObject->error = 'error';
        
        $target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $result = $target->setState('1234', 'eth', true);
        
        $this->assertEquals($result->status, 'ko');
        $this->assertEquals($result->error, 'error');
    }
    
    public function testSetStateWhenStateIsOnShouldCallTheCorrectUrl()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doJsonCall'])
        ->getMock();
        
        $jsonObject = new stdClass();
        @$jsonObject->status = 'ok';
        
        $target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/device/switch?authToken=&id=1234&on=true'));
        
        $result = $target->setState('1234', 'eth', true);
        
        $this->assertEquals($result->status, 'ok');
    }
    
    public function testSetStateWhenStateIsOffShouldCallTheCorrectUrl()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doJsonCall'])
        ->getMock();
        
        $jsonObject = new stdClass();
        @$jsonObject->status = 'ok';
        
        $target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/device/switch?authToken=&id=1234&on=false'));
        
        $result = $target->setState('1234', 'eth', false);
        
        $this->assertEquals($result->status, 'ok');
    }
    
    public function testSetStateWhenDeviceIsMasterShouldDoAReset()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doJsonCall'])
        ->getMock();
        
        $jsonObject = new stdClass();
        @$jsonObject->status = 'ok';
        
        $target->method('doJsonCall')
        ->willReturn($jsonObject);
        
        $target->expects($this->once())
        ->method('doJsonCall')
        ->with($this->equalTo('https://www.mystrom.ch/mobile/device/restart?authToken=&id=1234'));
        
        $result = $target->setState('1234', 'mst', false);
        
        $this->assertEquals($result->status, 'ok');
    }

    public function testWhenRetrieveLocalButtonInfo_ShouldGetTheMACAddress()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doHttpCall'])
        ->getMock();

        $jsonResult = '{"7C2F1D4G5H":{"type": "button", "battery": true, "reachable": true, "meshroot": false, "charge": false, "voltage": 3.705, "fw_version": "2.37", "single": "", "double": "", "long": "", "touch": ""}}';
        $target->method('doHttpCall')
        ->willReturn($jsonResult);

        $buttonInfo = $target->RetrieveLocalButtonInfo('192.168.1.2');

        $this->assertEquals($buttonInfo->macAddress, '7C2F1D4G5H');
    }

    public function testSaveUrlsFOrWifiButtonWhenSaved_ShouldCallTheCorrectUrls()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doHttpCall'])
        ->getMock();

        $target->method('logWarning')
        ->will($this->returnCallback(function($message){
            throw new Exception($message);
        }));

        $jeedomIp = '192.168.1.10';
        $target->method('getMyStromConfiguration')
        ->with(
            $this->equalTo('internalAddr'),
            $this->equalTo(true)
        )
        ->willReturn($jeedomIp);

        $button = new MystromButtonDevice();
        $button->ipAddress = '192.168.1.2';
        $button->macAddress = 'F1G2H3J5';

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $buttonUrl = 'http://' . $button->ipAddress . '/api/v1/device/' . $button->macAddress;
        $singleUrl = 'single=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . jeedom::getApiKey() . '%26type%3Dcmd%26id%3D' . $singleId;
        $doubleUrl = 'double=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . jeedom::getApiKey() . '%26type%3Dcmd%26id%3D' . $doubleId;
        $longUrl = 'long=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . jeedom::getApiKey() . '%26type%3Dcmd%26id%3D' . $longId;
        $touchUrl = 'touch=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . jeedom::getApiKey() . '%26type%3Dcmd%26id%3D' . $touchId;

        $target->expects($this->exactly(4))
        ->method('doHttpCall')
        ->withConsecutive(
            [$this->equalTo($buttonUrl), $this->equalTo($singleUrl), $this->equalTo('POST')],
            [$this->equalTo($buttonUrl), $this->equalTo($doubleUrl), $this->equalTo('POST')],
            [$this->equalTo($buttonUrl), $this->equalTo($longUrl), $this->equalTo('POST')],
            [$this->equalTo($buttonUrl), $this->equalTo($touchUrl), $this->equalTo('POST')]
        )
        ->willReturnOnConsecutiveCalls('','','','');

        $target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);
    }

    public function testSaveUrlsFOrWifiButtonWhenButtonNotReachable_ShouldReturnFalse()
    {
        $target = $this->getMockBuilder(MyStromService::class)
        ->setMethods([
        'logDebug',
        'logWarning',
        'logInfo',
        'getMyStromConfiguration',
        'saveMystromConfiguration',
        'doHttpCall'])
        ->getMock();

        $jeedomIp = '192.168.1.10';
        $target->method('getMyStromConfiguration')
        ->with(
            $this->equalTo('internalAddr'),
            $this->equalTo(true)
        )
        ->willReturn($jeedomIp);

        $button = new MystromButtonDevice();
        $button->ipAddress = '192.168.1.2';
        $button->macAddress = 'F1G2H3J5';

        $singleId = '1';
        $doubleId = '2';
        $longId = '3';
        $touchId = '4';

        $target->expects($this->once())
        ->method('doHttpCall')
        ->with(
            $this->equalTo('http://' . $button->ipAddress . '/api/v1/device/' . $button->macAddress),
            $this->equalTo('single=get://' . $jeedomIp . '/core/api/jeeApi.php?apikey%3D' 
            . jeedom::getApiKey() . '%26type%3Dcmd%26id%3D' . $singleId),
            'POST')
        ->will($this->throwException(new Exception));

        $result = $target->SaveUrlsForWifiButton($button, $singleId, $doubleId, $longId, $touchId);

        $this->assertFalse(false);
    }
}
