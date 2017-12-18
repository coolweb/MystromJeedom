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

    private $currentEqLogic;
    private $currentEqLogicId = "";
    private $currentCmd;
    private $eqLogics = [];
    private $cmds = [];

    /**
     * The current linked eqLogic to the cmd
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $currentEqLogicInJeedom;

    private function launchExecuteEvent($eqLogicId, $cmdLogicalId, $cmdType)
    {
        $this->currentEqLogicId = $eqLogicId;

        foreach ($this->eqLogics as $eqLogic) {
            if($eqLogic->logicalId == $this->currentEqLogicId)
            {
                $this->currentEqLogic = $eqLogic;
            }
        }
        
        $cmd = new Cmd();
        $cmd->logicalId = $cmdLogicalId;
        $cmd->type = $cmdType;
        $this->currentCmd = $cmd;

        $this->target->execute();
    }

    private function addEqLogicInJeedom($logicalId, $name, $mystromType, $cmds)
    {
        $eqLogic = new eqLogic();
        $eqLogic->logicalId = $logicalId;
        $eqLogic->mystromType = $mystromType;
        $eqLogic->name = $name;
        $eqLogic->cmds = $cmds;

        array_push($this->eqLogics, $eqLogic);
    }

    private function initTestData()
    {
        $this->target->method('getLogicalId')
        ->will($this->returnCallBack(array($this, 'getLogicalId')));

        $this->target->method('getEqLogicLogicalId')
        ->will($this->returnCallBack(array($this, 'getEqLogicLogicalId')));

        $this->target->method('getEqLogicConfiguration')
        ->will($this->returnCallBack(array($this, 'getEqLogicConfiguration')));

        $this->target
        ->method("getEqLogic")
        ->will($this->returnCallBack(array($this, 'getEqLogic')));

        $this->target->method("checkAndUpdateCmd")
        ->will($this->returnCallBack(array($this, 'checkAndUpdateCmd')));
    }

    public function getEqLogic()
    {
        return $this->currentEqLogic;
    }

    public function getEqLogicLogicalId()
    {
        return $this->currentEqLogicId;
    }

    public function getLogicalId()
    {
        return $this->currentCmd->logicalId;
    }

    public function checkAndUpdateCmd($cmdName, $cmdValue)
    {
        $cmdFound = false;

        foreach ($this->currentEqLogic->cmds as $cmd) {
            if($cmd->logicalId == $cmdName)
            {
                $cmd->value = $cmdValue;
                $cmdFound = true;
            }
        }

        if($cmdFound == false)
        {
            $cmdToAdd = new Cmd();
            $cmdToAdd->logicalId = $cmdName;
            $cmdToAdd->value = $cmdValue;

            array_push($this->currentEqLogicInJeedom->cmds, $cmdToAdd);
        }
    }

    public function getEqLogicConfiguration($key)
    {
        foreach ($this->eqLogics as $eqLogic) {
            if($eqLogic->getLogicalId() == $this->currentEqLogicId)
            {
                switch ($key) {
                    case 'mystromType':
                        return $eqLogic->mystromType;                        
                    
                    default:
                        return null;
                }
            }
        }
    }

    protected function setUp()
    {
        $this->currentCmdId = "";
        $this->currentEqLogicId = "";
        $this->eqLogics = [];
        $this->cmds = [];

        $this->mystromService = $this->getMockBuilder(MyStromService::class)
        ->disableOriginalConstructor()        
        ->getMock();

        $this->jeedomHelper = $this->getMockBuilder(JeedomHelper::class)
        ->getMock();            

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
    }

    public function testWhenButtonTouched_ItShouldSetTheTouchedInfoOn()
    {
        // Arrange
        $cmd = new Cmd();
        $cmd->type = "action";
        $cmd->logicalId = "isTouchedAction";

        $cmdTouched = new Cmd();
        $cmdTouched->type = "info";
        $cmdTouched->logicalId = "isTouched";

        $this->initTestData();

        // Act
        $this->addEqLogicInJeedom("def", "test device", "wbp", Array($cmd, $cmdTouched));
        $this->launchExecuteEvent("def", $cmd->logicalId, $cmd->type);

        // Assert
        $this->assertEquals($cmdTouched->value, 1);
    }
}