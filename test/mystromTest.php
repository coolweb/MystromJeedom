<?php

include_once('eqLogic.php');
include_once('cmd.php');
include_once('./core/class/mystromBaseDevice.class.php');
include_once('./core/class/mystromDevice.class.php');
include_once('./core/class/mystromWifiSwitchEurope.class.php');
include_once('./core/class/mystromApiResult.class.php');
include_once('./core/class/getAllDevicesResult.class.php');
include_once('./core/class/mystromService.class.php');
include_once('./core/class/mystrom.class.php');
include_once('./core/class/jeedomHelper.class.php');

use PHPUnit\Framework\TestCase;
use coolweb\mystrom\MyStromService;
use coolweb\mystrom\MyStromDevice;
use coolweb\mystrom\MyStromWifiSwitchEurope;
use coolweb\mystrom\GetAllDevicesResult;
use coolweb\mystrom\jeedomHelper;

/**
* Test class for mystrom service class
*/
class mystromTest extends TestCase
{
    private $mystromService;
    private $target;
    private $jeedomHelper;
    private $messages;
    
    private function setJeedomDevices($target, $eqLogics)
    {
        $this->target->method('loadEqLogic')
        ->willReturn($eqLogics);
    }

    private function setLogicalId($id)
    {
        $this->target->method('getLogicalId')
        ->willReturn($id);
    }

    private function setLocalBulbDevice(MyStromService $mystromService, $device)
    {
        $mystromService->method('RetrieveLocalRgbBulbInfo')
        ->willReturn($device);
    }

    private function setMystromDevices(MyStromService $mystromService, $devices, $error = false)
    {
        $result = new GetAllDevicesResult();

        if($error == false){
            $result->status = 'ok';
            $result->devices = $devices;
        } else {
            $result->status = 'ko';
            $result->error = 'error description';
            $result->devices = array();
        }

        $mystromService->method('loadAllDevicesFromServer')
        ->willReturn($result);
    }

    private function setCmdStateDeprecated($eqLogic, $deprecated = true)
    {
        $eqLogic->expects($this->at(0))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('state'))
        ->willReturn($deprecated);

        $eqLogic->expects($this->at(1))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('stateBinary'))
        ->willReturn($deprecated);

        $eqLogic->expects($this->at(2))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('conso'))
        ->willReturn($deprecated);

        $eqLogic->expects($this->at(3))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('dailyConso'))
        ->willReturn($deprecated);

        $eqLogic->expects($this->at(4))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('monthlyConso'))
        ->willReturn($deprecated);

        if ($deprecated === true) {
            $eqLogic->expects($this->once())
            ->method('refreshWidget');
        }
    }

    protected function setUp()
    {
        $this->messages = array();

        $this->mystromService = $this->getMockBuilder(MyStromService::class)
        ->disableOriginalConstructor()
        ->setMethods([
            'loadAllDevicesFromServer', 
            'RetrieveLocalRgbBulbInfo', 
            'retrieveLocalWifiSwitchDeviceInformation'])
        ->getMock();

        $this->jeedomHelper = $this->getMockBuilder(JeedomHelper::class)
        ->getMock();

        $this->jeedomHelper->method('addMessage')
        ->will($this->returnCallBack(array($this, 'addMessage')));

        $this->target = $this->getMockBuilder(mystrom::class)
        ->setConstructorArgs([$this->jeedomHelper, $this->mystromService])
        ->setMethods(['logError', 'loadEqLogic', 'logDebug', 'getLogicalId', 'setConfiguration', 'getConfiguration'])
        ->getMock();
    }

    public function addMessage($message)
    {
        array_push($this->messages, $message);
    }

    public function testPullWhen1DeviceExistAt2Sides_ItShouldRefreshJeedom()
    {
        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget'])
        ->getMock();

        $eqLogic->logicalId = '1234';
        $this->setCmdStateDeprecated($eqLogic);

        $eqLogics = array();
        array_push($eqLogics, $eqLogic);

        $device = new MyStromDevice();
        $device->id = '1234';

        $devices = array();
        array_push($devices, $device);

        $this->setJeedomDevices($this->target, $eqLogics);
        $this->setMystromDevices($this->mystromService, $devices);

        $this->target->pull();
    }

    public function testPullWhenDeviceOfflineBeforeAndNowItShouldAddMessageToCenterMessage()
    {
        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget'])
        ->getMock();

        $eqLogic->logicalId = '1234';
        $this->setCmdStateDeprecated($eqLogic, false);

        $eqLogics = array();
        array_push($eqLogics, $eqLogic);

        $device = new MyStromDevice();
        $device->id = '1234';
        $device->state = "offline";

        $devices = array();
        array_push($devices, $device);

        $this->setJeedomDevices($this->target, $eqLogics);
        $this->setMystromDevices($this->mystromService, $devices);

        $this->target->pull();

        $this->assertEquals(sizeof($this->messages), 1);
    }

    public function testPullWhenDeviceOfflineBeforeAndNowAndDeviceIsDisabledItShouldNotAddMessageToCenterMessage()
    {
        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget',])
        ->getMock();

        $eqLogic->isEnable = 0;

        $eqLogic->logicalId = '1234';

        $eqLogics = array();
        array_push($eqLogics, $eqLogic);

        $device = new MyStromDevice();
        $device->id = '1234';
        $device->state = "offline";

        $devices = array();
        array_push($devices, $device);

        $this->setJeedomDevices($this->target, $eqLogics);
        $this->setMystromDevices($this->mystromService, $devices);

        $this->target->pull();

        $this->assertEquals(sizeof($this->messages), 0);
    }

    public function testPullWhenButtonIsOfflineBeforeAndNowAndItShouldNotAddMessageToCenterMessage()
    {
        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget'])
        ->getMock();

        $eqLogic->logicalId = '1234';
        $eqLogic->mystromType = 'wbp';

        $eqLogics = array();
        array_push($eqLogics, $eqLogic);

        $device = new MyStromDevice();
        $device->id = '1234';
        $device->state = "offline";
        $device->type = "wbp";

        $devices = array();
        array_push($devices, $device);

        $this->setJeedomDevices($this->target, $eqLogics);
        $this->setMystromDevices($this->mystromService, $devices);

        $this->target->pull();

        $this->assertEquals(0, sizeof($this->messages));
    }

    public function testPullWhenBulbIsOfflineBeforeAndNowAndItShouldNotAddMessageToCenterMessage()
    {
        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget'])
        ->getMock();

        $eqLogic->logicalId = '1234';
        $eqLogic->mystromType = 'wrb';

        $eqLogics = array();
        array_push($eqLogics, $eqLogic);

        $device = new MyStromDevice();
        $device->id = '1234';
        $device->state = "offline";
        $device->type = "wrb";

        $devices = array();
        array_push($devices, $device);

        $this->setJeedomDevices($this->target, $eqLogics);
        $this->setMystromDevices($this->mystromService, $devices);

        $this->target->pull();

        $this->assertEquals(0, sizeof($this->messages));
    }

    public function testPullWhenWifiSwitchEurope_ItShouldUpdateTemperature()
    {
        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget'])
        ->getMock();

        $eqLogic->logicalId = '1234';
        $this->setCmdStateDeprecated($eqLogic);

        $eqLogics = array();
        array_push($eqLogics, $eqLogic);

        $device = new MystromWifiSwitchEurope();
        $device->id = '1234';
        $device->temperature = 21;
        $device->state = "on";
        $device->power = 120;
        $device->daylyConsumption = 1500;
        $device->monthlyConsumption = 2000;

        $devices = array();
        array_push($devices, $device);

        $this->setJeedomDevices($this->target, $eqLogics);
        $this->setMystromDevices($this->mystromService, $devices);

        $eqLogic->expects($this->exactly(6))
        ->method('checkAndUpdateCmd')
        ->withConsecutive(
            ["state", $device->state],
            ["stateBinary", "1"],
            ["conso", $device->power],
            ["dailyConso", $device->daylyConsumption],
            ["monthlyConso", $device->monthlyConsumption],
            ["temperature", $device->temperature]);

        $this->target->pull();
    }

    public function testPullWhen1DeviceNotExistAtMystromServer_ItShouldLogError()
    {
        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget', 'getName'])
        ->getMock();

        $eqLogic->logicalId = '1234';

        $eqLogics = array();
        array_push($eqLogics, $eqLogic);

        $device = new MyStromDevice();
        $device->id = '12';

        $devices = array();
        array_push($devices, $device);

        $this->setJeedomDevices($this->target, $eqLogics);
        $this->setMystromDevices($this->mystromService, $devices);

        $this->jeedomHelper->expects($this->once())
        ->method('logError');

        $this->target->pull();
    }

    public function testPullWhenErrorLoadingDevices_ItShouldLogAnError()
    {
        $this->setMystromDevices($this->mystromService, array(), true);

        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget', 'getName'])
        ->getMock();
        $eqLogic->logicalId = '1234';
        
        $eqLogics = array();
        array_push($eqLogics, $eqLogic);
        $this->setJeedomDevices($this->target, $eqLogics);
        
        $this->jeedomHelper->expects($this->once())
        ->method('logError');

        $this->target->pull();
    }

    public function testPullWhenLocalDevice_ItShouldSetDataIntoJeedom()
    {
        $bulbLocal = new \coolweb\mystrom\MystromWifiBulb();
        $bulbLocal->ipAddress = "192.168.1.2";
        $bulbLocal->state = "on";
        $bulbLocal->power = 2.5;
        $bulbLocal->color = "124;100;100";
        
        $this->setMystromDevices($this->mystromService, array());
        $this->setLocalBulbDevice($this->mystromService, $bulbLocal);

        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget', 'getName'])
        ->getMock();
        $eqLogic->logicalId = '1234';
        $eqLogic->isLocal = true;
        $eqLogic->ipAddress = "192.168.1.2";
        $eqLogic->mystromType = "wrb";
        
        $eqLogics = array();
        array_push($eqLogics, $eqLogic);
        $this->setJeedomDevices($this->target, $eqLogics);
        
        $eqLogic->expects($this->exactly(4))
        ->method('checkAndUpdateCmd')
        ->withConsecutive(
            ["state", $bulbLocal->state],
            ["stateBinary", "1"],
            ["conso", $bulbLocal->power],
            ["colorRgb", "124;100;100"]);

        $this->target->pull();        
    }

    public function testPullWhenLocalDeviceAndIsNotReachableItShouldSetDeviceOfflineIntoJeedom()
    {
        $this->setMystromDevices($this->mystromService, array());

        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget', 'getName'])
        ->getMock();
        $eqLogic->logicalId = '1234';
        $eqLogic->isLocal = true;
        $eqLogic->ipAddress = "192.168.1.2";
        $eqLogic->mystromType = "wse";
        
        $eqLogics = array();
        array_push($eqLogics, $eqLogic);
        $this->setJeedomDevices($this->target, $eqLogics);
        
        $eqLogic->expects($this->exactly(2))
        ->method('checkAndUpdateCmd')
        ->withConsecutive(
            ["state", "offline"],
            ["conso", "0"]
        );

        $this->target->pull();
    }

    public function testPreInsertWhenUserCreated_ItShouldSetIsLocal()
    {
        $this->setLogicalId(null);

        $this->target->expects($this->once())
        ->method('setConfiguration')
        ->with($this->equalTo('isLocal'), $this->equalTo(true));

        $this->target->preInsert();
    }

    public function testPreInsertWhenSystemCreated_ItShouldSetIsLocalToFalse()
    {
        $this->setLogicalId('1234');

        $this->target->expects($this->once())
        ->method('setConfiguration')
        ->with($this->equalTo('isLocal'), $this->equalTo(false));

        $this->target->preInsert();
    }
}
