<?php
use PHPUnit\Framework\TestCase;

include_once('eqLogic.php');
include_once('cmd.php');
include_once('./core/class/mystromBaseDevice.class.php');
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
    private $mystromService;
    private $target;

    private function setJeedomDevices($target, $eqLogics)
    {
        $target->method('loadEqLogic')
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
        ->setMethods(['loadAllDevicesFromServer'])
        ->getMock();

        $this->target = $this->getMockBuilder(mystrom::class)
        ->setMethods(['logError', 'loadEqLogic', 'logDebug', 'getLogicalId', 'setConfiguration'])
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

        $this->target->pull($this->mystromService);
    }

    public function testPullWhen1DeviceNotExistAtMystromServer_ItShouldLogError()
    {
        $eqLogic = $this->getMockBuilder(eqLogic::class)
        ->setMethods(['checkAndUpdateCmd', 'refreshWidget'])
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

        $this->target->expects($this->once())
        ->method('logError');

        $this->target->pull($this->mystromService);
    }

    public function testPullWhenErrorLoadingDevices_ItShouldLogAnError()
    {
        $this->setMystromDevices($this->mystromService, null, true);
        $this->target->expects($this->once())
        ->method('logError');

        $this->target->pull($this->mystromService);
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
