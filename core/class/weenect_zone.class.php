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
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/weenect_base.class.php';

class weenect_zone extends weenect_base {
    const W_CMD_common = array(
        'type'=>array('name'=>'type','type'=>'info', 'subtype'=>'string'),
        'date_tracker'=>array('name'=>'dernière date','type'=>'info', 'subtype'=>'string'),
    );
}

class weenectZoneCmd extends cmd {
}
?>