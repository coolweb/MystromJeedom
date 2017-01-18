<?php

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
if (isset($unitTest) == false) {
    require_once dirname(__FILE__) . '/../php/mystrom.inc.php';
}

/*
 * Plugin for mystrom eco power lan device.
 */
class mystrom extends eqLogic
{
    private static $_eqLogics = null;

    /**
     * Logs an error message
     * @param $message string The message to log
     */
    public function logError(string $message)
    {
        log::add('mystrom', 'error', $message);
    }

    /**
     * Logs a debug message
     * @param $message string The message to log
     */
    public function logDebug(string $message)
    {
        log::add('mystrom', 'debug', $message);
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
        mystrom::pull();
    }

    public function preInsert()
    {
    }

    public function postInsert()
    {
    }

    public function preSave()
    {
    }

    /**
     * Function called by jeedom after the save of a device.
     */
    public function postSave()
    {
        log::add('mystrom', 'debug', "Ajout des commandes sur l'équipement");

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

    /**
     * Get all device from the user account.
     * @return An array with device objects from jeedom database.
     */
    public static function getAllDevices()
    {
        $logger = log::getLogger('mystrom');
        log::add('mystrom', 'debug', 'getAllDevices');

        $devices = array();
        $jeedomDevices = eqLogic::byType('mystrom');
        log::add('mystrom', 'debug', 'jeedom devices retrieved');

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
        log::add('mystrom', 'debug', 'syncMyStrom');
        $mystromService = new MyStromService();

        if ($mystromService->doAuthentification()) {
            log::add('mystrom', 'info', 'Recherche des équipements mystrom');
            $resultDevices = $mystromService->loadAllDevicesFromServer();
            
            if ($resultDevices->status == 'ok') {
                foreach ($resultDevices->devices as $device) {
                    $eqLogic = mystrom::byLogicalId($device->id, 'mystrom');
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

                log::add('mystrom', 'debug', "Ajout des équipements dans la base de données");

                return '';
            } else {
                log::add('mystrom', 'error', "Erreur de recherche des équipements: " . $resultDevices->error);
                return "Erreur de recherche des équipements voir les logs";
            }
        } else {
            throw new Exception("Erreur d'authentification voir les logs");
        }
    }

    /**
     * Refresh data as state, consommation, ... for all devices.
     */
    public function pull($mystromService = null)
    {
        if ($mystromService == null) {
            $mystromService = new MyStromService();
        }

        
        if (isset($this)) {
            mystrom::$_eqLogics = $this->loadEqLogic();
        } else {
            $mystromPlugin = new mystrom();
            mystrom::$_eqLogics = $mystromPlugin->loadEqLogic();
        }
        

        $resultDevices = $mystromService->loadAllDevicesFromServer(true);
        $foundMystromDevice = null;

        if ($resultDevices->status != 'ok') {
            log::add('mystrom', 'error', $resultDevices->error);
            return;
        }

        foreach (mystrom::$_eqLogics as $eqLogic) {
            $foundMystromDevice = null;
            $changed = false;

            foreach ($resultDevices->devices as $device) {
                if ($device->id == $eqLogic->getLogicalId()) {
                    if (isset($this) == false) {
                        mystrom::logDebug("Equipement trouvé avec id " . $device->id);
                    }

                    $foundMystromDevice = $device;
                }
            }

            if ($foundMystromDevice == null) {
                log::add('mystrom', 'error', "Impossible de trouver l'équipement mystrom id "
                . $eqLogic->getLogicalId());
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
        }
    }
}

/**
 * Command class for mystrom plugin.
 */
class mystromCmd extends cmd
{

    /**
     * Method called by jeedom when a command is executed on a device.
     */
    public function execute($_options = array())
    {
        if ($this->getType() == 'info') {
            return;
        }

        $mystromService = new MyStromService();
        $commandOk = false;
        $eqLogic = $this->getEqLogic();
        $mystromId = $eqLogic->getLogicalId();
        $deviceType = $eqLogic->getConfiguration('mystromType');
        $state = '';

        if ($this->getLogicalId() == 'on') {
            $commandOk = true;
            $state = 'on';
            $stateBinary = '1';
            $mystromService->setState($mystromId, $deviceType, true);
        }

        if ($this->getLogicalId() == 'off') {
            $commandOk = true;
            $state = 'off';
            $stateBinary = '0';
            $mystromService->setState($mystromId, $deviceType, false);
        }

        if ($this->getLogicalId() == 'restart') {
            $commandOk = true;
            $state = 'off';
            $stateBinary = '0';
            $mystromService->setState($mystromId, $deviceType, false);
        }

        if ($commandOk == false) {
            log::add('mystrom', 'error', "Commande non reconnue " . $this->getLogicalId());
        } else {
            $changed = $eqLogic->checkAndUpdateCmd('state', $state) || $changed;
            $changed = $eqLogic->checkAndUpdateCmd('stateBinary', $stateBinary) || $changed;

            $this->event($state);

            if ($changed) {
                $eqLogic->refreshWidget();
            }

            return $state;
        }
    }
}
