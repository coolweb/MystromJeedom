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

use coolweb\mystrom\mystromDevice;
use coolweb\mystrom\MystromWifiBulb;
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

    private $mystromServerDevices = array();

    /**
     * The current linked eqLogic to the cmd
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $currentEqLogicInJeedom;

    private function launchExecuteEvent($eqLogicId, $cmdLogicalId, $cmdType, $cmdOptions = array())
    {
        $this->currentEqLogicId = $eqLogicId;

        foreach ($this->eqLogics as $eqLogic) {
            if ($eqLogic->logicalId == $this->currentEqLogicId) {
                $this->currentEqLogic = $eqLogic;
            }
        }
        
        $cmd = new Cmd();
        $cmd->logicalId = $cmdLogicalId;
        $cmd->type = $cmdType;
        $this->currentCmd = $cmd;

        $this->target->execute($cmdOptions);
    }

    /**
     * Add eq logic into jeedom.
     *
     * @param string $logicalId The identifier of eq logic into Jeedom
     * @param string $name The name of the device.
     * @param string $mystromType The code type of mytrom device
     * @param Array $cmds Array of commands attached to the eq logic.
     * @param boolean $isLocal
     * @return void
     */
    private function addEqLogicInJeedom($logicalId, $name, $mystromType, $cmds, $isLocal = false)
    {
        $eqLogic = new eqLogic();
        $eqLogic->logicalId = $logicalId;
        $eqLogic->mystromType = $mystromType;
        $eqLogic->name = $name;
        $eqLogic->cmds = $cmds;
        $eqLogic->isLocal = $isLocal;

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

        $this->mystromService->method("setBulbColor")
        ->will($this->returnCallBack(array($this, 'setBulbColor')));

        $this->mystromService->method("setState")
        ->will($this->returnCallBack(array($this, 'setState')));
    }

    public function setState($eqLogicId, $deviceType, $isOn, $isToggle)
    {
        $foundDevice = null;

        foreach ($this->mystromServerDevices as $mystromDevice) {
            if ($mystromDevice->id == $eqLogicId) {
                $foundDevice = $mystromDevice;
            }
        }

        if ($isToggle === true) {
            $foundDevice->state = $foundDevice->state == "on" ? "off" : "on";
        }
    }

    public function setBulbColor(MystromWifiBulb $bulbDevice, $color)
    {
        $foundDevice = null;

        foreach ($this->mystromServerDevices as $mystromDevice) {
            if ($mystromDevice->id == $bulbDevice->id) {
                $foundDevice = $mystromDevice;
            }
        }

        if ($foundDevice == null) {
            $foundDevice = $bulbDevice;
            array_push($this->mystromServerDevices, $bulbDevice);
        }

        $foundDevice->color = $color;
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
            if ($cmd->logicalId == $cmdName) {
                $cmd->value = $cmdValue;
                $cmdFound = true;
            }
        }

        if ($cmdFound == false) {
            $cmdToAdd = new Cmd();
            $cmdToAdd->logicalId = $cmdName;
            $cmdToAdd->value = $cmdValue;

            array_push($this->currentEqLogicInJeedom->cmds, $cmdToAdd);
        }
    }

    public function getEqLogicConfiguration($key)
    {
        foreach ($this->eqLogics as $eqLogic) {
            if ($eqLogic->getLogicalId() == $this->currentEqLogicId) {
                switch ($key) {
                    case 'mystromType':
                        return $eqLogic->mystromType;
                        
                    case 'macAddress':
                        return $eqLogic->macAddress;

                    case 'isLocal':
                        return $eqLogic->isLocal;
                    
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
        $this->mystromServerDevices = array();

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
            'getEqLogic',
            'getCmd'])
        ->getMock();
    }

    public function testWhenButtonTouchedItShouldSetTheTouchedInfoOn()
    {
        // Arrange
        $cmd = new Cmd();
        $cmd->type = "action";
        $cmd->logicalId = "isTouchedAction";

        $cmdTouched = new Cmd();
        $cmdTouched->type = "info";
        $cmdTouched->logicalId = "isTouched";

        $this->addEqLogicInJeedom("def", "test device", "wbp", array($cmd, $cmdTouched));
        $this->initTestData();

        // Act
        $this->launchExecuteEvent("def", $cmd->logicalId, $cmd->type);

        // Assert
        $this->assertEquals($cmdTouched->value, 1);
    }

    public function testWhenBulbChangeColorItShouldChangeColorOfBulb()
    {
        // Arrange
        $cmd = new Cmd();
        $cmd->type = "color";
        $cmd->logicalId = "color";
        $options = array("color" => "#00ff11");

        $cmdRgb = new Cmd();
        $cmdRgb->type = "string";
        $cmdRgb->logicalId = "colorRgb";

        $this->addEqLogicInJeedom("def", "test device", "wrb", array($cmd, $cmdRgb));
        $this->initTestData();

        // Act
        $this->launchExecuteEvent("def", $cmd->logicalId, $cmd->type, $options);

        // Assert
        $this->assertEquals($this->mystromServerDevices[0]->color, "#00ff11");
        $this->assertEquals($cmdRgb->value, "#00ff11");
    }

    /**
     * Test toggle cmd.
     *
     * @return void
     */
    public function testWhenToggleOffDeviceItShouldSetItOn()
    {
        // Arrange
        $cmd = new Cmd();
        $cmd->type = "action";
        $cmd->logicalId = "toggle";

        $cmdState = new Cmd();
        $cmdState->type = "info";
        $cmdState->logicalId = "state";
        $cmdState->value = "off";

        $cmdStateBinary = new Cmd();
        $cmdStateBinary->type = "info";
        $cmdStateBinary->logicalId = "stateBinary";
        $cmdStateBinary->value = "0";

        $deviceServer = new mystromDevice();
        $deviceServer->id = "def";
        $deviceServer->state = "off";
        array_push($this->mystromServerDevices, $deviceServer);

        $this->addEqLogicInJeedom("def", "test device", "wrb", array($cmd, $cmdState, $cmdStateBinary));
        $this->initTestData();

        // Act
        $this->launchExecuteEvent("def", $cmd->logicalId, $cmd->type, null);

        // Assert
        $this->assertEquals($cmdState->value, "on");
        $this->assertEquals($deviceServer->state, "on");
    }
}
