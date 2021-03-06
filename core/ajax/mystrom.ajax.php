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

ini_set('display_errors', 1);
//ini_set('display_warnings', 1);

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__) . '/../class/mystrom.class.php';

    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    log::add('mystrom', 'debug', 'Ajax call mystrom' . init('action'));                
    
    ajax::init();
    if (init('action') == 'syncMyStrom') {
        $mystrom = new mystrom();
        $result = $mystrom->syncMystrom();        
        if($result == ''){
            ajax::success();
        } else {
            ajax::error($result);
        }
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    log::add('mystrom', 'debug', 'Ajax syncMyStrom 3');                
    ajax::error(displayExeption($e), $e->getCode());
}
?>
