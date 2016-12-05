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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

/*
 * Plugin for mystrom eco power lan device.
 */
class mystrom extends eqLogic
{
    private static $_eqLogics = null;

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
        $logger = log::getLogger('mystrom');

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
     * Do the authentification of the user.
     * Use the configuration userId and password and store
     * the authentification token into the configuration key authToken.
     * @return boolean indicating the success of the authentification.
     */
    public function doAuthentification()
    {
        $logger = log::getLogger('mystrom');

        log::add('mystrom', 'info', 'Authentification');
        $user = config::byKey('userId', 'mystrom');
        $password = config::byKey('password', 'mystrom');

        $authUrl = mystrom::getMystromUrl() . '/auth?email=' . $user
                . '&password=' . $password;

        $json = file_get_contents($authUrl);
        log::add('mystrom', 'debug', $json);

        $jsonObj = json_decode($json);
        if ($jsonObj->status == 'ok') {
            config::save('authToken', $jsonObj->authToken, 'mystrom');
            log::add('mystrom', 'debug', "Clé d'authentification sauvée: " . $jsonObj->authToken);
            return true;
        } else {
            log::add('mystrom', 'warning', "Erreur d'authentification: " . $jsonObj->error);
            return false;
        }
    }

    /**
     * Load all devices from mystrom api, create devices into jeedom if
     * not existing or update names if already exist into jeedom database.
     * @return A string empty if success otherwhise an error message.
     */
    public function syncMyStrom()
    {
        $logger = log::getLogger('mystrom');
        if (mystrom::doAuthentification()) {
            log::add('mystrom', 'info', 'Recherche des équipements mystrom');
            $authToken = config::byKey('authToken', 'mystrom');
            $devicesUrl = mystrom::getMystromUrl() . '/devices?authToken=' . $authToken;

            $json = file_get_contents($devicesUrl);
            log::add('mystrom', 'debug', $json);

            $jsonObj = json_decode($json);
            if ($jsonObj->status == 'ok') {
                foreach ($jsonObj->devices as $device) {
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
                log::add('mystrom', 'error', "Erreur de recherche des équipements: " . $jsonObj->error);
                return "Erreur de recherche des équipements voir les logs";
            }
        } else {
            throw new Exception("Erreur d'authentification voir les logs");
        }
    }

    /**
     * Set state on or off of a device, if the device is the master,
     * restart it.
     * @param $eqLogic The jeedom device object to change status.
     * @param $isOn Boolean indicating to set on or off.
     * @param $deviceId The mystrom device identifier.
     */
    public function setState($eqLogic, $isOn, $deviceId)
    {
        $logger = log::getLogger('mystrom');
        $authToken = config::byKey('authToken', 'mystrom');
        $stateUrl = mystrom::getMystromUrl() . '/device/switch?authToken=' . $authToken
                  . '&id=' . $deviceId . '&on=' . (($isOn) ? 'true' : 'false');
        $restartUrl = mystrom::getMystromUrl() . '/device/restart?authToken=' . $authToken
                  . '&id=' . $deviceId;

        $url = '';

        if ($eqLogic->getConfiguration('mystromType') == 'mst') {
            $url = $restartUrl;
        } else {
            $url = $stateUrl;
        }

        $json = file_get_contents($url);
        log::add('mystrom', 'debug', $url);

        $jsonObj = json_decode($json);

        if ($jsonObj->status != 'ok') {
            log::add('mystrom', 'error', $json);
        }
    }

    /**
     * Refresh data as state, consommation, ... for all devices.
     */
    public function pull($_eqLogic_id = null)
    {
        $logger = log::getLogger('mystrom');

        if (mystrom::$_eqLogics == null) {
            mystrom::$_eqLogics = mystrom::byType('mystrom');
        }

        $authToken = config::byKey('authToken', 'mystrom');
        $devicesUrl = mystrom::getMystromUrl() . '/devices?report=true&authToken=' . $authToken;

        $json = file_get_contents($devicesUrl);
        log::add('mystrom', 'debug', $json);

        $jsonObj = json_decode($json);
        $foundMystromDevice = null;

        if ($jsonObj->status != 'ok') {
            log::add('mystrom', 'error', $jsonObj->error);
            return;
        }

        foreach (mystrom::$_eqLogics as $eqLogic) {
          $foundMystromDevice = null;
          $changed = false;

          foreach ($jsonObj->devices as $device) {
              if ($device->id == $eqLogic->getLogicalId()) {
                  log::add('mystrom', 'debug', "Equipement trouvé avec id " . $device->id);
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
          $changed = $eqLogic->checkAndUpdateCmd('dailyConso', $foundMystromDevice->energyReport->daylyConsumption) || $changed;
          $changed = $eqLogic->checkAndUpdateCmd('monthlyConso', $foundMystromDevice->energyReport->monthlyConsumption) || $changed;

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

        $commandOk = false;
        $eqLogic = $this->getEqLogic();
        $mystromId = $eqLogic->getLogicalId();
        $state = '';

        if ($this->getLogicalId() == 'on') {
            $commandOk = true;
            $state = 'on';
            $stateBinary = '1';
            mystrom::setState($eqLogic, true, $mystromId);
        }

        if ($this->getLogicalId() == 'off') {
            $commandOk = true;
            $state = 'off';
            $stateBinary = '0';
            mystrom::setState($eqLogic, false, $mystromId);
        }

        if ($this->getLogicalId() == 'restart') {
            $commandOk = true;
            $state = 'off';
            $stateBinary = '0';
            mystrom::setState($eqLogic, false, $mystromId);
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
