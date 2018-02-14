<?php
namespace coolweb\mystrom;

class JeedomHelper
{
    public function loadPluginConfiguration($key, $isCoreConfig = false)
    {
        return \config::byKey($key, $isCoreConfig == true ? "core" : "mystrom");
    }

    public function getJeedomApiKey()
    {
        return \jeedom::getApiKey();
    }
    
    /**
    * Save a configuration value for the plugin
    * @param string $key The name of the configuration to save
    * @param string $value The value of the configuration
    */
    public function savePluginConfiguration($key, $value)
    {
        \config::save($key, $value, "mystrom");
    }
    
    /**
    * Log a debug message
    * @param $message string The message to log
    */
    public function logDebug($message)
    {
        \log::add("mystrom", "debug", $message);
    }
    
    /**
    * Log an error message
    * @param string $message The message to log
    */
    public function logError($message)
    {
        \log::add("mystrom", "error", $message);
    }
    
    /**
    * Log an information message
    * @param $message string The message to log
    */
    public function logInfo($message)
    {
        \log::add("mystrom", "info", $message);
    }
    
    /**
    * Log a warning message
    * @param $message string The message to log
    */
    public function logWarning($message)
    {
        \log::add("mystrom", "warning", $message);
    }

    /**
     * Add a message to jeedom center message.
     *
     * @param string $message The message to add.
     * @return void
     */
    public function addMessage($message)
    {
        \message::add("mystrom", $message);
    }
    
    /**
    * Retrieve eqLogic by logical id
    * @param string $logicalId
    */
    public function getEqLogicByLogicalId($logicalId)
    {
        return \eqLogic::byLogicalId($logicalId, "mystrom");
    }

    /**
     * Load all eqLogics for honeywell plugin
     *
     * @return Array of eqLogic
     */
    public function loadEqLogic()
    {
        return \eqLogic::byType("mystrom");
    }
    
    /**
    * Create and save an eq logic into jeedom
    * @param string $logicalId The logical id of the eq logic
    * @param string $name The name of the eq logic
    * @param string[] $configurationKeyValue Key value pair of configuration values
    * @return eqLogic The created eqLogic
    */
    public function createAndSaveEqLogic($logicalId, $name, $configurationKeyValue)
    {
        $eqLogic = new \eqLogic();
        $eqLogic->setLogicalId($logicalId);
        $eqLogic->setEqType_name("mystrom");
        $eqLogic->setIsVisible(1);
        $eqLogic->setIsEnable(1);
        $eqLogic->setCategory("energy", 1);

        foreach ($configurationKeyValue as $key => $value) {
            $eqLogic->setConfiguration($key, $value);
        }

        $eqLogic->setName($name);
        $eqLogic->save();

        return $eqLogic;
    }

    /**
     * Create and save a cmd attach to aneq logic into jeedom
     *
     * @param eqLogic $eqLogic The eq logic to which to attach the command
     * @param string $cmdLogicalId The logical name id of the command into jeedom
     * @param string $cmdName The display name of the command
     * @param string $cmdType The type of the command info || action
     * @param string $cmdSubType The subtype of the command
     * @param bool $showOnDashboard Indicates if show command on dashboard
     * @return void
     */
    public function createCmd(
        $eqLogic,
        $cmdLogicalId,
        $cmdName,
        $cmdType,
        $cmdSubType,
        $showOnDashboard,
        $unite = "W"
    ) {
        $cmd = $eqLogic->getCmd(null, $cmdLogicalId);
        if (!is_object($cmd)) {
            $cmd = new \honeywellCmd();
            $cmd->setLogicalId($cmdLogicalId);
            $cmd->setName($cmdName);

            if ($cmdType == "info") {
                $cmd->setUnite($unite);
            }

            $cmd->setType($cmdType);
            $cmd->setSubType($cmdSubType);
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setDisplay("showOndashboard", $showOnDashboard == true ? "1" : "0");
            $cmd->save();
        }
    }

    public function clearCacheAndUpdateWidget($eqLogic)
    {
        $mc = \cache::byKey("mystromWidgetmobile" . $eqLogic->getId());
        $mc->remove();
        $mc = \cache::byKey("mystromWidgetdashboard" . $eqLogic->getId());
        $mc->remove();
        $eqLogic->toHtml("mobile");
        $eqLogic->toHtml("dashboard");

        $eqLogic->refreshWidget();
    }
}
