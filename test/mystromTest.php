<?php

include_once('eqLogic.php');
include_once('cmd.php');
include_once('./core/class/mystromBaseDevice.class.php');
include_once('./core/class/mystromDevice.class.php');
include_once('./core/class/mystromWifiSwitchEurope.class.php');
include_once('./core/class/mystromApiResult.class.php');
include_once('./core/class/getAllDevicesResult.class.php');
include_once('./core/class/myStromService.class.php');
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

    private function setMystromDevices(MyStromService $mystromService, $devices, $error = false)
    {
        $result = new GetAllDevicesResult();

        if($error == false){
            $result->status = 'ok';
            $result->devices = $devices;
        } else {
            $result->status = 'ko';
            $result->error = 'error description';
        }

        $mystromService->method('loadAllDevicesFromServer')
        ->willReturn($result);
    }

    private function setCmdStateDeprecated($eqLogic)
    {
        $eqLogic->expects($this->at(0))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('state'))
        ->willReturn(true);

        $eqLogic->expects($this->at(1))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('stateBinary'))
        ->willReturn(true);

        $eqLogic->expects($this->at(2))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('conso'))
        ->willReturn(true);

        $eqLogic->expects($this->at(3))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('dailyConso'))
        ->willReturn(true);

        $eqLogic->expects($this->at(4))
        ->method('checkAndUpdateCmd')
        ->with($this->equalTo('monthlyConso'))
        ->willReturn(true);

        $eqLogic->expects($this->once())
        ->method('refreshWidget');
    }

    protected function setUp()
    {
        $this->mystromService = $this->getMockBuilder(MyStromService::class)
        ->disableOriginalConstructor()
        ->setMethods(['loadAllDevicesFromServer'])
        ->getMock();

        $this->jeedomHelper = $this->getMockBuilder(JeedomHelper::class)
        ->getMock();

        $this->target = $this->getMockBuilder(mystrom::class)
        ->setConstructorArgs([$this->jeedomHelper, $this->mystromService])
        ->setMethods(['logError', 'loadEqLogic', 'logDebug', 'getLogicalId', 'setConfiguration', 'getConfiguration'])
        ->getMock();
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
        $this->setMystromDevices($this->mystromService, null, true);
        $this->jeedomHelper->expects($this->once())
        ->method('logError');

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
