<?php

/**
 * Stub for eqLogic class
 */
class eqLogic
{
    public $logicalId = "";
    public $name = "";

    public function getLogicalId()
    {
        return $this->logicalId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function checkAndUpdateCmd($cmdName, $cmdValue)
    {
        return false;
    }
}
