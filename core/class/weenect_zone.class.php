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

/*
E eqLogic 'weenect_zone' pour référencer les zones affiliées à un tracker. 
=> retournée par l'api de weenect
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/weenect_base.class.php';

class weenect_zone extends weenect_base {
    const WZ_CMD_common = array(
        'number'=>array('name'=>'Num','type'=>'info', 'subtype'=>'string'),
        'address'=>array('name'=>'Adresse','type'=>'info', 'subtype'=>'string'),
        'latitude'=>array('name'=>'Latitude','type'=>'info', 'subtype'=>'numeric'),
        'longitude'=>array('name'=>'Longitude','type'=>'info', 'subtype'=>'numeric'),
        'distance'=>array('name'=>'Distance','type'=>'info', 'subtype'=>'numeric'),
        'coord'=>array('name'=>'Coordonnées','type'=>'info', 'subtype'=>'string'),
        'is_in'=>array('name'=>'Dans la Zone','type'=>'info', 'subtype'=>'binary'),
    );

    const WZ_CONF_common = array(
        'tracker_id'=>array('name'=>'Tracker','type'=>'info', 'subtype'=>'string'),
    );

    public static function update_zones($eqTracker, $_zones){
        log::add("weenect", 'debug', '║ ║ ╔════════════ Zone Update :'.json_encode($zids));
        // suppression des zones inutiles
        $zones = self::byTracker($eqTracker->getLogicalId());
        $zids = array_map(function($item) {return $item["id"];}, $_zones);
        foreach ($zones as $zone){
            if(!in_array($zone->getLogicalId(), $zids)){
                log::add("weenect", 'debug', '║ ║ ╟───────── removing Zone : '.$zone->getLogicalId().'/'.$zone->getHumanName());
                $zone->remove();
            }
        }
        // mise à jour des zones référencées via l'appel à l'API
        foreach($_zones as $zone){
            self::update_single($eqTracker, $zone);
        }
        log::add("weenect", 'debug', '║ ║ ╚═════════════ end zone update');

    }

    /*    ----- fonction de mise à jour d'un tracker unique 
    * $eqTracker : le eqLogic du tracker
    * $zone : le json de la zone, renvoyé par l'API
    */
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
    /*    ----- fonction de création d'une nouvelle zone (eqName : weenect_zone) 
    * $eqTracker : le eqLogic du tracker
    * $zoneId : id de la zone a créer
    * $zone : le json de la zone, renvoyé par l'API
    */
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
    /*    ----- selection des zones par l'idLogic du tracker (id de weenect)
    * cherche dans eqLogic, avec eqName = "weenect_zone" et dans le json de configuration le tracker_id
    * retourne un array avec les eqLogic des zones trouvées
    * $_tracker_id : id du tracker
    */
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
    /*    ----- selection de toutes les zones (weenect_zone)
    * cherche dans eqLogic, avec eqName = "weenect_zone" 
    * retourne un array avec les eqLogic des zones trouvées
    */
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
    /*    -----  zones sous format html par l'idLogic du tracker (id de weenect)
    * pour l'affichage dans l'equipement tracker corrspondant
    * retourne html avec les card par zone
    * $_tracker_id : id du tracker
    */
    public static function get_zone_html($trackerId){
        $zones = self::byTracker($trackerId);
        $plugin=plugin::byId('weenect');
        $html="";
        foreach($zones as $zone){
            if(!is_object($zone))continue;
            $opacity = ($zone->getIsEnable()) ? '' : 'disableCard';
            $html .= '<div class="eqLogicDisplayCardSecondary card ' . $opacity . '" data-eqLogic_id="' . $zone->getId() . '">';
            $html .= '<img class="card-img-top" src="'.$plugin->getPathImgIcon().'" alt="Card image cap">';
            $html .= '<div class="card-name">' . $zone->getHumanName(true, true) . '</div>';
            $html .= '<div class="icon_cont">';
            $in = $zone->getCmd(null, 'is_in');
            if(is_object($in)){
                $html .='<i class="fas fa-user '.($in->execCmd()?'tracker_in':'tracker_out').'" title="Tracker dans la zone"></i>';
            }
            $html .= ($zone->getIsVisible() == 1) ? '<i class="fas fa-eye" title="Equipement visible"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
            $html .= '</div>';
            $html .= '</div>';
        }
        return $html;
    }


    /*    -----  Synchronisation avec les configuration du Tracker
    * selon la configuration générale du plugin 
    * retourne html avec les card par zone
    * $eqTracker : eqLogic du tracker
    * $_oldname : optionnel, pour remplacement du nom dans le nom de la zone si configuration
    */
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
        // mise à jour de la commande 'coord' : long,lat
        $this->update_coordinate(self::WZ_CMD_common['coord']);
      }
}

class weenect_zoneCmd extends cmd {
}
?>