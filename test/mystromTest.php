<?php
use PHPUnit\Framework\TestCase;

$unitTest = true;

include_once('eqLogic.php');
include_once('cmd.php');
include_once('./core/class/myStromDevice.class.php');
include_once('./core/class/mystromApiResult.class.php');
include_once('./core/class/getAllDevicesResult.class.php');
include_once('./core/class/MyStromService.class.php');
include_once('./core/class/myStrom.class.php');

/**
* Test class for mystrom service class
*/
class mystromTest extends TestCase
{
    private function setJeedomDevices($target, $eqLogics)
    {
        $target->method('loadEqLogic')
        ->willReturn($eqLogics);
    }

    private function setMystromDevices(MyStromService $mystromService, $devices)
    {
        $result = new GetAllDevicesResult();
        $result->status = 'ok';
        $result->devices = $devices;

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

    public function testPullWhen1DeviceExistAt2SidesShouldRefreshJeedom()
    {
        $mystromService = $this->getMockBuilder(MyStromService::class)
        ->setMethods(['loadAllDevicesFromServer'])
        ->getMock();

        $target = $this->getMockBuilder(mystrom::class)
        ->setMethods(['logError', 'loadEqLogic', 'logDebug'])
        ->getMock();

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

        $this->setJeedomDevices($target, $eqLogics);
        $this->setMystromDevices($mystromService, $devices);

        $target->pull($mystromService);
    }
}
