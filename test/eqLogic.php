<?php

/**
 * Stub for eqLogic class
 */
class eqLogic
{
    public $logicalId = "";
    public $name = "";
    public $mystromType = "";
    public $cmds = [];
    public $isLocal = false;
    public $ipAddress = null;
    public $macAddress = "";
    public $isEnable = 1;

    public function getConfiguration($key)
    {
        if ($key == "isLocal") {
            return $this->isLocal;
        }

        if ($key == "ipAddress") {
            return $this->ipAddress;
        }

        if ($key == "mystromType") {
            return $this->mystromType;
        }

        return null;
    }

    public function getLogicalId()
    {
        return $this->logicalId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIsEnable()
    {
        return $this->isEnable;
    }

    public function checkAndUpdateCmd($cmdName, $cmdValue)
    {
        return false;
    }

    public function getCmd($type, $logicalId)
    {
        foreach ($this->cmds as $cmd) {
            if ($cmd->logicalId == $logicalId) {
                return $cmd;
            }
        }

        return null;
    }
}
