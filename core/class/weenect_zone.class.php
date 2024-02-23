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
    const WZ_CMD_common = array(
        'number'=>array('name'=>'Num','type'=>'info', 'subtype'=>'string'),
        // 'tracker_id'=>array('name'=>'Tracker','type'=>'info', 'subtype'=>'string'),
        'address'=>array('name'=>'Adresse','type'=>'info', 'subtype'=>'string'),
        'latitude'=>array('name'=>'Latitude','type'=>'info', 'subtype'=>'numeric'),
        'longitude'=>array('name'=>'Longitude','type'=>'info', 'subtype'=>'numeric'),
        'distance'=>array('name'=>'Distance','type'=>'info', 'subtype'=>'numeric'),
        'coord'=>array('name'=>'Coordonnées','type'=>'info', 'subtype'=>'string'),
    );

    const WZ_CONF_common = array(
        'tracker_id'=>array('name'=>'Tracker','type'=>'info', 'subtype'=>'string'),
        'name'=>array('name'=>'Nom d\'origine','type'=>'info', 'subtype'=>'string'),
    );

    public static function update_zones($eqTracker, $_zones){
        log::add("weenect", 'debug', '║ ║ ╔════════════ Zone Update :'.json_encode($zids));
        // suppression des zones inutiles
        $zones = self::byTracker($eqTracker->getLogicalId());
        $zids = array_map(function($item) {return $item["id"];}, $_zones);
        log::add("weenect", 'debug', '║ ║ ╔════════════ Zone Update :'.json_encode($zids));
        foreach ($zones as $zone){
            if(!in_array($zone->getLogicalId(), $zids)){
                log::add("weenect", 'debug', '║ ║ ╟───────── removing Zone : '.$zone->getLogicalId().'/'.$zone->getHumanName());
                $zone->remove();
            }
        }

        // mise à jour des zones référencées via l'appel à l'API
        foreach($_zones as $zone){
            log::add("weenect", 'debug', '║ ║ ╟───────────────── Zone : '.json_encode($zone));
            self::update_single($eqTracker, $zone);
        }

        
        log::add("weenect", 'debug', '║ ║ ╚═════════════ end zone update');

    }
    public static function update_single($eqTracker, $zone){
        $zId = $zone['id'];
        $tId = $zone['tracker_id'];
        $eqLogic=eqLogic::byLogicalId($zId, "weenect_zone");
        if(!$eqLogic){
            $eqLogic=self::create_new_zones($eqTracker, $zId, $zone);
          }
          $eqLogic->updateFromTracker($eqTracker);
          $eqLogic->updateCMDfromArray($zone);
          $eqLogic->update_coordinate(self::WZ_CMD_common['coord']);
          $eqLogic->updateCONFfromArray($zone, self::WZ_CMD_common);
    }
    public static function create_new_zones($eqTracker, $zoneId, $zone) {
        log::add("weenect", 'debug', '║ ╟─── No Zone found, create a new one for id :'.$zoneId);
        $addTName=config::byKey('add-tracker-name', 'weenect');

        $zName = ($addTName?$eqTracker->getName()."-":"").$zone['name'];
        $eqLogic = new weenect_zone();
        $eqLogic->setName($zName);
        $eqLogic->setEqType_name('weenect_zone'); 
        $eqLogic->setLogicalId($zoneId);
        // $eqLogic->setObject_id($zoneId);
        $eqLogic->setIsEnable(1);
        $eqLogic->setConfiguration('tracker_id', $zone['tracker_id']);
        $eqLogic->save();
        return $eqLogic;
    }

    public static function byTracker($_tracker_id){
        log::add("weenect", "debug", "║ ╟─── ask for zone for tracker  :".$tracker_id);
        $values = array(
			'eqType_name' => __CLASS__,
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__, 'el') . '
		FROM eqLogic el
		LEFT JOIN object ob ON el.object_id=ob.id
		WHERE eqType_name=:eqType_name ';
        $sql .= ' AND json_value(el.configuration, "$.tracker_id")='.$_tracker_id;
		$sql .= ' ORDER BY ob.name,el.name';

        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
    }
    public static function all() {
		$values = array(
			'eqType_name' => __CLASS__,
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__, 'el') . '
		FROM eqLogic el
		LEFT JOIN object ob ON el.object_id=ob.id
		WHERE eqType_name=:eqType_name ';
		$sql .= ' ORDER BY ob.name,el.name';

        return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}


    /// synchro with tracker
    public function updateFromTracker($eqTracker, $_oldname = null){
        $addTName=config::byKey('add-tracker-name', 'weenect');
        $linkConf=config::byKey('link-tracker-conf', 'weenect');
       
        // ajout du nom du tracker en préfix de la zone si selectionné 
        $zName = $this->getName();
        $tName = $eqTracker->getName();
        // replace de l'ancier non
        if($addTName && $_oldname){
            $zName = preg_replace('/'.$_oldname.'/', $tName, $zName);
            $this->setName($zName);
        }
        if($addTName && !preg_match('/.*'.$tName.'.*/', $zName)){
            $zName = $tName."-".$zName;
            $this->setName($zName);
        }

        // synchro des configuration
        if($linkConf){
            $this->setObject_id($eqTracker->getObject_id('object_id'));
            //categories:
			foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
				$this->setCategory($key, $eqTracker->getCategory($key, '0'));
			}
        }
        $this->save();
    }
    public function postSave() {
        //     les commandes générales
        $this->createCMDFromArray(self::WZ_CMD_common);
        $this->update_coordinate(self::WZ_CMD_common['coord']);
      }
}

class weenect_zoneCmd extends cmd {
}
?>