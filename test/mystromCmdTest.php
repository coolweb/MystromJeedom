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
* Test class for mystrom cmd class
*/
class mystromCmdTest extends TestCase
{
    private $mystromService;
    private $target;

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
        ->setMethods([])
        ->getMock();

        $this->target = $this->getMockBuilder(mystromCmd::class)
        ->setMethods(['getEqLogicLogicalId', 'getEqLogicConfiguration', 'getLogicalId', 'getType', 'checkAndUpdateCmd', 'logDebug'])
        ->getMock();
    }

    public function testWhenButtonTouched_ItShouldSetTheTouchedInfoOnThenOff()
    {
        $this->setCmdId('isTouchedAction');
        $this->setMystromType(null);
        $this->setCmdType('action');

        $this->target->expects($this->exactly(2))
        ->method('checkAndUpdateCmd')
        ->withConsecutive(
            [$this->equalTo('isTouched'), $this->equalTo(1)],
            [$this->equalTo('isTouched'), $this->equalTo(0)]);

        $this->target->execute(null, $this->mystromService);
    }
}