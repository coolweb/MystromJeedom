<?php
/* This file is part of Plugin MyStrom for jeedom.
 *
 * Plugin MyStrom for jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Plugin MyStrom for jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Plugin MyStrom for jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
?>
<div id='div_mystromHealthAlert' style="display: none;"></div>
<table class="table table-condensed tablesorter" id="table_healthMystrom">
    <thead>
        <tr>
            <th>{{Nom}}</th>
            <th>{{ID}}</th>
            <th>{{Etat}}</th>
        </tr>
    </thead>
    <tbody>

    </tbody>
</table>
<?php include_file('core', 'mystrom.class', 'js', 'mystrom');?>
<?php include_file('desktop', 'health', 'js', 'mystrom');?>
