<?php
use coolweb\mystrom\JeedomHelper;

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
if (file_exists(dirname(__FILE__) . '/../../../../core/php/core.inc.php')) {
    require_once dirname(__FILE__) . '/../php/mystrom.inc.php';
}

/*
* Plugin for mystrom eco power lan device.
*/
class mystrom extends eqLogic
{
    /** @var \coolweb\mystrom\JeedomHelper */
    private $_jeedomHelper;

    /** @var \coolweb\mystrom\MystromService */
    private $_mystromService;

    private static $_eqLogics = null;
    
    public function __construct($jeedomHelper, $mystromService)
    {
        if ($jeedomHelper == null) {
            $container = DI\ContainerBuilder::buildDevContainer();
            $this->_jeedomHelper = $container->get("\coolweb\mystrom\JeedomHelper");
        } else {
            $this->_jeedomHelper = $jeedomHelper;
        }

        if ($mystromService == null) {
            $this->_mystromService = $container->get("\coolweb\mystrom\MystromService");
        } else {
            $this->_mystromService = $mystromService;
        }
    }

    public function loadEqLogic()
    {
        return mystrom::byType('mystrom');
    }
    
    /*
    * Fonction exécutée automatiquement toutes les minutes par Jeedom
    */
    public static function cron()
    {
        $this->_jeedomHelper->logDebug("pull started");
        $this->pull();        
    }
    
    public function preInsert()
    {
        if ($this->getLogicalId() == null || $this->getLogicalId() == "") {
            // new device created by user
            $this->setConfiguration('isLocal', true);
        } else {
            $this->setConfiguration('isLocal', false);
        }
    }
    
    public function postInsert()
    {
    }
    
    public function preSave()
    {
        if ($this->getConfiguration('isLocal') == true) {
            // only mystrom button is supported for the moment
            if (!($this->getConfiguration('mystromType') == 'wbp' || $this->getConfiguration('mystromType') == 'wbs')) {
                throw new Exception('Vous ne pouvez créer que le type Wifi Bouton Plus ou Wifi Bouton', 1);
            }
            
            if ($this->getConfiguration('ipAddress') == null || $this->getConfiguration('ipAddress') == '') {
                throw new Exception('Veuillez introduire l\'adresse ip de l\'équipement', 1);
            }
        }
    }
    
    /**
    * Function called by jeedom after the save of a device.
    */
    public function postSave()
    {
        $this->_jeedomHelper->logDebug("Ajout des commandes sur l'équipement");
        $deviceType = $this->getConfiguration('mystromType');
        
        if ($deviceType == 'mst' || $deviceType == 'eth' || $deviceType == 'sw' || $deviceType == 'wsw') {
            $state = $this->getCmd(null, 'state');
            if (!is_object($state)) {
                $state = new mystromCmd();
                $state->setLogicalId('state');
                $state->setName(__('Etat', __FILE__));
                $state->setType('info');
                $state->setSubType('string');
                $state->setEqLogic_id($this->getId());
                $state->setDisplay('showOndashboard', '0');
                $state->save();
            }
            
            $stateBinary = $this->getCmd(null, 'stateBinary');
            if (!is_object($stateBinary)) {
                $stateBinary = new mystromCmd();
                $stateBinary->setLogicalId('stateBinary');
                $stateBinary->setName(__('EtatBinaire', __FILE__));
                $stateBinary->setIsVisible(false);
                $stateBinary->setType('info');
                $stateBinary->setSubType('binary');
                $stateBinary->setDisplay('generic_type', 'ENERGY_STATE');
                $stateBinary->setDisplay('showOndashboard', '0');
                $stateBinary->setEqLogic_id($this->getId());
                $stateBinary->setSubType('binary');
            }
            $stateBinary->save();
            
            $cmdid = $stateBinary->getId();
            
            if ($this->getConfiguration('mystromType') != 'mst') {
                $on = $this->getCmd(null, 'on');
                if (!is_object($on)) {
                    $on = new mystromCmd();
                    $on->setLogicalId('on');
                    $on->setName(__('On', __FILE__));
                    $on->setType('action');
                    $on->setSubType('other');
                    $on->setDisplay('generic_type', 'ENERGY_ON');
                    $on->setDisplay('showNameOndashboard', '0');
                    $on->setEqLogic_id($this->getId());
                    $on->setTemplate('dashboard', 'prise');
                    $on->setTemplate('mobile', 'prise');
                    $on->setValue($cmdid);
                    $on->save();
                }
                
                $off = $this->getCmd(null, 'off');
                if (!is_object($off)) {
                    $off = new mystromCmd();
                    $off->setLogicalId('off');
                    $off->setName(__('Off', __FILE__));
                    $off->setType('action');
                    $off->setSubType('other');
                    $off->setDisplay('generic_type', 'ENERGY_OFF');
                    $off->setDisplay('showNameOndashboard', '0');
                    $off->setEqLogic_id($this->getId());
                    $off->setTemplate('dashboard', 'prise');
                    $off->setTemplate('mobile', 'prise');
                    $off->setValue($cmdid);
                    $off->save();
                }
            } else {
                $restart = $this->getCmd(null, 'restart');
                if (!is_object($restart)) {
                    $restart = new mystromCmd();
                    $restart->setLogicalId('restart');
                    $restart->setName(__('Restart', __FILE__));
                    $restart->setType('action');
                    $restart->setSubType('other');
                    $restart->setEqLogic_id($this->getId());
                    $restart->setValue($cmdid);
                    $restart->save();
                }
            }
            
            $conso = $this->getCmd(null, 'conso');
            if (!is_object($conso)) {
                $conso = new mystromCmd();
                $conso->setLogicalId('conso');
                $conso->setName(__('Consommation', __FILE__));
                $conso->setType('info');
                $conso->setSubType('numeric');
                $conso->setTemplate('dashboard', 'line');
                $conso->setEqLogic_id($this->getId());
                $conso->setDisplay('showNameOndashboard', '0');
                $conso->setUnite('w');
                $conso->save();
            }
            
            $cmd = $this->getCmd(null, 'dailyConso');
            if (!is_object($cmd)) {
                $cmd = new mystromCmd();
                $cmd->setLogicalId('dailyConso');
                $cmd->setName(__('Consommation journalière', __FILE__));
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setDisplay('showNameOndashboard', '0');
                $cmd->setDisplay('showOndashboard', '0');
                $cmd->setUnite('Kw');
                $cmd->setIsHistorized(1);
                $cmd->save();
            }
            
            $cmd = $this->getCmd(null, 'monthlyConso');
            if (!is_object($cmd)) {
                $cmd = new mystromCmd();
                $cmd->setLogicalId('monthlyConso');
                $cmd->setName(__('Consommation mensuel', __FILE__));
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setEqLogic_id($this->getId());
                $cmd->setDisplay('showNameOndashboard', '0');
                $cmd->setDisplay('showOndashboard', '0');
                $cmd->setUnite('Kw');
                $cmd->setIsHistorized(1);
                $cmd->save();
            }
        } else {
            $isTouchedCmd = $this->getCmd(null, 'isTouched');
            $isTouchedActionCmd = $this->getCmd(null, 'isTouchedAction');
            
            if ($deviceType == 'wbs') {
                if (is_object($isTouchedCmd)) {
                    $isTouchedCmd->remove();
                }
                
                if (is_object($isTouchedActionCmd)) {
                    $isTouchedActionCmd->remove();
                }
            }
            
            if ($deviceType == 'wbp') {
                $isTouchedCmd = $this->getCmd(null, 'isTouched');
                if (!is_object($isTouchedCmd)) {
                    $isTouchedCmd = new mystromCmd();
                    $isTouchedCmd->setLogicalId('isTouched');
                    $isTouchedCmd->setName(__('Touché', __FILE__));
                    $isTouchedCmd->setType('info');
                    $isTouchedCmd->setSubType('binary');
                    $isTouchedCmd->setTemplate('dashboard', 'line');
                    $isTouchedCmd->setEqLogic_id($this->getId());
                    $isTouchedCmd->setDisplay('showNameOndashboard', '1');
                    $isTouchedCmd->save();
                }
                
                if (!is_object($isTouchedActionCmd)) {
                    $isTouchedActionCmd = new mystromCmd();
                    $isTouchedActionCmd->setLogicalId('isTouchedAction');
                    $isTouchedActionCmd->setName(__('Action Touché', __FILE__));
                    $isTouchedActionCmd->setType('action');
                    $isTouchedActionCmd->setSubType('other');
                    $isTouchedActionCmd->setEqLogic_id($this->getId());
                    $isTouchedActionCmd->setDisplay('showNameOndashboard', '0');
                    $isTouchedActionCmd->save();
                }
            }
            
            if ($deviceType == 'wbp' || $deviceType == 'wbs') {
                $isSingleCmd = $this->getCmd(null, 'isSingle');
                if (!is_object($isSingleCmd)) {
                    $isSingleCmd = new mystromCmd();
                    $isSingleCmd->setLogicalId('isSingle');
                    $isSingleCmd->setName(__('Appuyé 1 fois', __FILE__));
                    $isSingleCmd->setType('info');
                    $isSingleCmd->setSubType('binary');
                    $isSingleCmd->setTemplate('dashboard', 'line');
                    $isSingleCmd->setEqLogic_id($this->getId());
                    $isSingleCmd->setDisplay('showNameOndashboard', '1');
                    $isSingleCmd->save();
                }
                
                $isSingleActionCmd = $this->getCmd(null, 'isSingleAction');
                if (!is_object($isSingleActionCmd)) {
                    $isSingleActionCmd = new mystromCmd();
                    $isSingleActionCmd->setLogicalId('isSingleAction');
                    $isSingleActionCmd->setName(__('Action Appuyé 1 fois', __FILE__));
                    $isSingleActionCmd->setType('action');
                    $isSingleActionCmd->setSubType('other');
                    $isSingleActionCmd->setEqLogic_id($this->getId());
                    $isSingleActionCmd->setDisplay('showNameOndashboard', '0');
                    $isSingleActionCmd->save();
                }
                
                $isDoubleCmd = $this->getCmd(null, 'isDouble');
                if (!is_object($isDoubleCmd)) {
                    $isDoubleCmd = new mystromCmd();
                    $isDoubleCmd->setLogicalId('isDouble');
                    $isDoubleCmd->setName(__('Appuyé 2 fois', __FILE__));
                    $isDoubleCmd->setType('info');
                    $isDoubleCmd->setSubType('binary');
                    $isDoubleCmd->setTemplate('dashboard', 'line');
                    $isDoubleCmd->setEqLogic_id($this->getId());
                    $isDoubleCmd->setDisplay('showNameOndashboard', '1');
                    $isDoubleCmd->save();
                }
                
                $isDoubleActionCmd = $this->getCmd(null, 'isDoubleAction');
                if (!is_object($isDoubleActionCmd)) {
                    $isDoubleActionCmd = new mystromCmd();
                    $isDoubleActionCmd->setLogicalId('isDoubleAction');
                    $isDoubleActionCmd->setName(__('Action Appuyé 2 fois', __FILE__));
                    $isDoubleActionCmd->setType('action');
                    $isDoubleActionCmd->setSubType('other');
                    $isDoubleActionCmd->setEqLogic_id($this->getId());
                    $isDoubleActionCmd->setDisplay('showNameOndashboard', '0');
                    $isDoubleActionCmd->save();
                }
                
                $isLongPressedCmd = $this->getCmd(null, 'isLongPressed');
                if (!is_object($isLongPressedCmd)) {
                    $isLongPressedCmd = new mystromCmd();
                    $isLongPressedCmd->setLogicalId('isLongPressed');
                    $isLongPressedCmd->setName(__('Appuyé longtemps', __FILE__));
                    $isLongPressedCmd->setType('info');
                    $isLongPressedCmd->setSubType('binary');
                    $isLongPressedCmd->setTemplate('dashboard', 'line');
                    $isLongPressedCmd->setEqLogic_id($this->getId());
                    $isLongPressedCmd->setDisplay('showNameOndashboard', '1');
                    $isLongPressedCmd->save();
                }
                
                $isLongPressedActionCmd = $this->getCmd(null, 'isLongPressedAction');
                if (!is_object($isLongPressedActionCmd)) {
                    $isLongPressedActionCmd = new mystromCmd();
                    $isLongPressedActionCmd->setLogicalId('isLongPressedAction');
                    $isLongPressedActionCmd->setName(__('Action Appuyé longtemps', __FILE__));
                    $isLongPressedActionCmd->setType('action');
                    $isLongPressedActionCmd->setSubType('other');
                    $isLongPressedActionCmd->setEqLogic_id($this->getId());
                    $isLongPressedActionCmd->setDisplay('showNameOndashboard', '0');
                    $isLongPressedActionCmd->save();
                }
                
                // save url of cmd into the button
                if ($this->getConfiguration('isLocal') == true) {
                    $buttonIp = $this->getConfiguration('ipAddress');
                    
                    if (is_null($buttonIp) === false && $buttonIp != '') {
                        $button = $this->_mystromService->RetrieveLocalButtonInfo($buttonIp);
                        
                        if ($button === null) {
                            throw new Exception('Le bouton ne semble pas accessible, vérifiez l\'ip ou enlever les piles, remettez les et réessayez', 1);
                        }
                        
                        $button->type = $deviceType;
                        $button->isLocal = true;
                        $this->_mystromService->SaveUrlsForWifiButton(
                        $button,
                        $isSingleActionCmd->getId(),
                        $isDoubleActionCmd->getId(),
                        $isLongPressedActionCmd->getId(),
                        $isTouchedActionCmd->getId()
                        );
                    }
                } else {
                    $this->setConfiguration('ipAddress', null);
                    
                    $button = new MystromButtonDevice();
                    $button->id = $this->getLogicalId();
                    $button->isLocal = false;
                                        
                    $button->type = $deviceType;
                    $this->_mystromService->SaveUrlsForWifiButton(
                    $button,
                    $isSingleActionCmd->getId(),
                    $isDoubleActionCmd->getId(),
                    $isLongPressedActionCmd->getId(),
                    $isTouchedActionCmd->getId()
                    );
                }
            }
        }
    }
    
    public function preUpdate()
    {
    }
    
    public function postUpdate()
    {
    }
    
    public function preRemove()
    {
    }
    
    public function postRemove()
    {
    }
    
    /**
    * Returns the mystrom api url.
    * @return A string containing the url.
    */
    public function getMystromUrl()
    {
        return 'https://www.mystrom.ch/mobile';
    }
    
    public function getEqLogicByLogicalId($id)
    {
        return mystrom::byLogicalId($id, 'mystrom');
    }
    
    /**
    * Get all device from the user account.
    * @return An array with device objects from jeedom database.
    */
    public static function getAllDevices()
    {
        $this->_jeedomHelper->logDebug("getAllDevices");
        
        $devices = array();
        $jeedomDevices = eqLogic::byType('mystrom');
        $this->_jeedomHelper->logDebug("jeedom devices retrieved");
        
        foreach ($jeedomDevices as $eqLogic) {
            $device = new stdClass();
            @$device->logicalId = $eqLogic->getLogicalId();
            @$device->name = $eqLogic->getName();
            @$device->state = $eqLogic->getCmd(null, 'state')->execCmd();
            @$device->isEnable = $eqLogic->getIsEnable();
            $devices[] = $device;
        }
        
        return $devices;
    }
    
    /**
    * Load all devices from mystrom api, create devices into jeedom if
    * not existing or update names if already exist into jeedom database.
    * @return A string empty if success otherwhise an error message.
    */
    public function syncMyStrom()
    {
        $this->_jeedomHelper->logDebug("syncMyStrom");        
        
        if ($this->_mystromService->doAuthentification()) {
            $this->_jeedomHelper->logInfo("Recherche des équipements mystrom");
            $resultDevices = $this->_mystromService->loadAllDevicesFromServer();
            
            if (strcmp($resultDevices->status, 'ok') == 0) {
                foreach ($resultDevices->devices as $device) {
                    $eqLogic = $this->getEqLogicByLogicalId($device->id);
                    if (!is_object($eqLogic)) {
                        $eqLogic = new self();
                        $eqLogic->setLogicalId($device->id);
                        $eqLogic->setEqType_name('mystrom');
                        $eqLogic->setIsVisible(1);
                        $eqLogic->setIsEnable(1);
                        $eqLogic->setCategory('energy', 1);
                        $eqLogic->setConfiguration('mystromType', $device->type);
                    }
                    
                    $eqLogic->setName($device->name);
                    $eqLogic->save();
                }
                
                $this->_jeedomHelper->logDebug("Ajout des équipements dans la base de données");
                
                return '';
            } else {
                $this->_jeedomHelper->logError("Erreur de recherche des équipements: ' . $resultDevices->error");
                return 'Erreur de recherche des équipements voir les logs';
            }
        } else {
            throw new Exception('Erreur d\'authentification voir les logs');
        }
    }
    
    /**
    * Refresh data as state, consommation, ... for all devices.
    */
    public function pull()
    {                
        mystrom::$_eqLogics = $this->loadEqLogic();
        
        $resultDevices = $this->_mystromService->loadAllDevicesFromServer(true);
        $foundMystromDevice = null;
        
        if (strcmp($resultDevices->status, 'ok') == -1) {
            $this->_jeedomHelper->logError("Error retrieving devices status: ' . $resultDevices->error");
            return;
        }
        
        foreach (mystrom::$_eqLogics as $eqLogic) {
            $foundMystromDevice = null;
            $changed = false;
            $isLocal = $this->getConfiguration('isLocal');
            
            if ($isLocal != true) {
                foreach ($resultDevices->devices as $device) {
                    if ($device->id == $eqLogic->getLogicalId()) {
                        $this->_jeedomHelper->logDebug("Equipement trouvé avec id ' . $device->id . ' (' . $device->name . ')");
                        
                        $foundMystromDevice = $device;
                    }
                }
                
                if ($foundMystromDevice == null) {
                    $this->_jeedomHelper->logError("Impossible de trouver l\'équipement mystrom id "
                        . $eqLogic->getLogicalId() . " (" . $eqLogic->getName() . ")");
                    continue;
                }
                
                $changed = $eqLogic->checkAndUpdateCmd('state', $foundMystromDevice->state) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('stateBinary', (($foundMystromDevice->state == 'on') ? '1' : '0')) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('conso', $foundMystromDevice->power) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('dailyConso', $foundMystromDevice->daylyConsumption) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('monthlyConso', $foundMystromDevice->monthlyConsumption) || $changed;
                
                if ($changed) {
                    $eqLogic->refreshWidget();
                }
            } else {
                continue;
            }
        }
    }
}

/**
* Command class for mystrom plugin.
*/
class mystromCmd extends cmd
{
    /** @var \coolweb\mystrom\JeedomHelper */
    private $_jeedomHelper;

    public function __construct($jeedomHelper)
    {
        if ($jeedomHelper == null) {
            $container = DI\ContainerBuilder::buildDevContainer();
            $this->_jeedomHelper = $container->get("\coolweb\mystrom\JeedomHelper");
        } else {
            $this->_jeedomHelper = $jeedomHelper;
        }
    }

    public function getEqLogicLogicalId()
    {
        $eqLogic = $this->getEqLogic();
        $logicalId = $eqLogic->getLogicalId();
        
        return $logicalId;
    }
    
    public function getEqLogicConfiguration($configurationName)
    {
        $eqLogic = $this->getEqLogic();
        $configurationValue = $eqLogic->getConfiguration($configurationName);
        
        return $configurationValue;
    }
    
    public function checkAndUpdateCmd($cmdName, $cmdValue)
    {
        $eqLogic = $this->getEqLogic();
        $changed = $eqLogic->checkAndUpdateCmd($cmdName, $cmdValue);
        
        return $changed;
    }
    
    public function refreshWidget()
    {
        $eqLogic = $this->getEqLogic();
        $eqLogic->refreshWidget();
    }
    
    /**
    * Method called by jeedom when a command is executed on a device.
    */
    public function execute($_options = array())
    {
        if ($this->getType() == 'info') {
            return;
        }
                        
        $commandOk = false;
        $commandWifiButton = false;
        $changed = false;
        
        $mystromId = $this->getEqLogicLogicalId();
        $deviceType = $this->getEqLogicConfiguration('mystromType');
        $state = '';
        $cmdLogicalId = $this->getLogicalId();
        
        $this->_jeedomHelper->logDebug('Execute cmd ' . $cmdLogicalId);
        
        if ($cmdLogicalId == 'on') {
            $commandOk = true;
            $state = 'on';
            $stateBinary = '1';
            $this->_mystromService->setState($mystromId, $deviceType, true);
        }
        
        if ($cmdLogicalId == 'off') {
            $commandOk = true;
            $state = 'off';
            $stateBinary = '0';
            $this->_mystromService->setState($mystromId, $deviceType, false);
        }
        
        if ($cmdLogicalId == 'restart') {
            $commandOk = true;
            $state = 'off';
            $stateBinary = '0';
            $this->_mystromService->setState($mystromId, $deviceType, false);
        }
        
        if ($cmdLogicalId == 'isTouchedAction') {
            $commandOk = true;
            $commandWifiButton = true;
            
            $changed = $this->checkAndUpdateCmd('isTouched', 1) || $changed;
            $changed = $this->checkAndUpdateCmd('isTouched', 0) || $changed;
        }
        
        if ($cmdLogicalId == 'isSingleAction') {
            $commandOk = true;
            $commandWifiButton = true;
            
            $changed = $this->checkAndUpdateCmd('isSingle', 1) || $changed;
            $changed = $this->checkAndUpdateCmd('isSingle', 0) || $changed;
        }
        
        if ($cmdLogicalId == 'isDoubleAction') {
            $commandOk = true;
            $commandWifiButton = true;
            
            $changed = $this->checkAndUpdateCmd('isDouble', 1) || $changed;
            $changed = $this->checkAndUpdateCmd('isDouble', 0) || $changed;
        }
        
        if ($cmdLogicalId == 'isLongPressedAction') {
            $commandOk = true;
            $commandWifiButton = true;
            
            $changed = $this->checkAndUpdateCmd('isLongPressed', 1) || $changed;
            $changed = $this->checkAndUpdateCmd('isLongPressed', 0) || $changed;
        }
        
        if ($commandOk == false) {
            $this->_jeedomHelper->logError("Commande non reconnue " . $this->getLogicalId());
        } else {
            if ($commandWifiButton == false) {
                $changed = $this->checkAndUpdateCmd('state', $state) || $changed;
                $changed = $this->checkAndUpdateCmd('stateBinary', $stateBinary) || $changed;
                
                $this->event($state);
                
                if ($changed) {
                    $this->refreshWidget();
                }
                
                return $state;
            }
        }
    }
}
