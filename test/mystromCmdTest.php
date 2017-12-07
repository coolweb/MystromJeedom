<?php
use PHPUnit\Framework\TestCase;

include_once('eqLogic.php');
include_once('cmd.php');
include_once('./core/class/mystromBaseDevice.class.php');
include_once('./core/class/mystromDevice.class.php');
include_once('./core/class/mystromApiResult.class.php');
include_once('./core/class/getAllDevicesResult.class.php');
include_once('./core/class/mystromService.class.php');
include_once('./core/class/mystrom.class.php');
include_once('./core/class/jeedomHelper.class.php');

use coolweb\mystrom\MyStromService;
use coolweb\mystrom\jeedomHelper;

/**
* Test class for mystrom cmd class
*/
class mystromCmdTest extends TestCase
{
    private $mystromService;

    /**
     * The testing target
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $target;
    private $jeedomHelper;

    /**
     * The current linked eqLogic to the cmd
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $currentEqLogic;

    private function setCmdId($id)
    {
        $this->target->method('getLogicalId')
        ->willReturn($id);
    }

    private function setEqLogicLogicaldId($id)
    {
        $this->target->method('getEqLogicLogicalId')
        ->willReturn($id);
    }

    private function setMystromType($mystromType)
    {
        $this->target->method('getEqLogicConfiguration')
        ->willReturn($mystromType);
    }

    private function setCmdType($cmdType)
    {
        $this->target->method('getType')
        ->willReturn($cmdType);
    }

    protected function setUp()
    {
        $this->mystromService = $this->getMockBuilder(MyStromService::class)
        ->disableOriginalConstructor()        
        ->getMock();

        $this->jeedomHelper = $this->getMockBuilder(JeedomHelper::class)
        ->getMock();
        
        $this->currentEqLogic = $this->getMockBuilder("eqLogic")
        ->getMock();

        $this->currentEqLogic
        ->expects($this->any())
        ->method("getName")
        ->willReturn("deviceName");

        $this->target = $this->getMockBuilder(mystromCmd::class)
        ->setConstructorArgs([$this->jeedomHelper, $this->mystromService])
        ->setMethods([
            'getEqLogicLogicalId', 
            'getEqLogicConfiguration', 
            'getLogicalId', 
            'getType', 
            'checkAndUpdateCmd', 
            'getEqLogic'])
        ->getMock();

        $this->target
        ->expects($this->any())
        ->method("getEqLogic")
        ->willReturn($this->currentEqLogic);
    }

    public function testWhenButtonTouched_ItShouldSetTheTouchedInfoOn()
    {
        $this->setCmdId('isTouchedAction');
        $this->setMystromType(null);
        $this->setCmdType('action');

        $this->target->expects($this->exactly(1))
        ->method('checkAndUpdateCmd')
        ->withConsecutive(
            [$this->equalTo('isTouched'), $this->equalTo(1)]);

        $this->target->execute(null, $this->mystromService);
    }
}