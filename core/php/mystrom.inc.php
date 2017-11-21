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

require_once dirname(__FILE__) . '/../class/jeedomHelper.class.php';
require_once dirname(__FILE__) . '/../class/mystromApiResult.class.php';
require_once dirname(__FILE__) . '/../class/getAllDevicesResult.class.php';
require_once dirname(__FILE__) . '/../class/mystromBaseDevice.class.php';
require_once dirname(__FILE__) . '/../class/mystromButtonDevice.class.php';
require_once dirname(__FILE__) . '/../class/mystromDevice.class.php';
require_once dirname(__FILE__) . '/../class/mystromService.class.php';

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
/*
 * Non obligatoire mais peut être utilisé si vous voulez charger en même temps que votre
 * plugin des librairies externes (ne pas oublier d'adapter plugin_info/info.xml).
 * 
 * 
 */
?>
