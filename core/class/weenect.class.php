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

/* classe principale du plugin Weenect
représente les données directe du tracker
récupérées par les appels API
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/weenect_base.class.php';
require_once __DIR__  . '/weenect_zone.class.php';
require_once __DIR__  . '/W_API.class.php';

class weenect extends weenect_base {
  const DEFAULT_CRON = "*/30 * * * *";// CRON par défaut pour les appels aux update de position - configurable dans le plugin
  const DEFAULT_TRACKER_COLOR = "pink";
  const DEFAULT_ZONE_COLOR = "green";
  // tableaux des commandes
  const W_CMD_common = array(
      'type'=>array('name'=>'type','type'=>'info', 'subtype'=>'string'), 
      'date_tracker'=>array('name'=>'dernière date','type'=>'info', 'subtype'=>'string'),
      
      // metrics 
      'battery'=>array('name'=>'Battery','type'=>'info', 'subtype'=>'numeric'),
      'gsm'=>array('name'=>'GSM','type'=>'info', 'subtype'=>'numeric'),
      'signal_strength_percent'=>array('name'=>'Signal','type'=>'info', 'subtype'=>'numeric'),
      'satellites'=>array('name'=>'Satellites','type'=>'info', 'subtype'=>'numeric'),
      
      // position
      'latitude'=>array('name'=>'Latitude','type'=>'info', 'subtype'=>'numeric'),
      'longitude'=>array('name'=>'Longitude','type'=>'info', 'subtype'=>'numeric'),
      'radius'=>array('name'=>'Precision','type'=>'info', 'subtype'=>'numeric'),
      'coord'=>array('name'=>'Coordonnées','type'=>'info', 'subtype'=>'string'),
      
      // status
      'is_online'=>array('name'=>'Online','type'=>'info', 'subtype'=>'binary'),
      'valid_signal'=>array('name'=>'Valid Signal','type'=>'info', 'subtype'=>'binary'),  
      'is_in_deep_sleep'=>array('name'=>'Deepsleep','type'=>'info', 'subtype'=>'binary'),
      'off_reason'=>array('name'=>'Raison Hors Tension','type'=>'info', 'subtype'=>'string'),
      'left_call'=>array('name'=>'Temps Appel Restant','type'=>'info', 'subtype'=>'numeric'),


      // update freq
      'last_freq_mode'=>array('name'=>'Frequence Mise à Jour', 'type'=>'info', 'subtype'=>'string'),
      'set_freq_mode'=>array('name'=>'Set Frequence Mise à Jour', 'type'=>'action', 'subtype'=>'select',
            'configuration'=> array(
              'infoName'=>'#last_freq_mode#', 
              'value'=> '#value#',
              'listValue'=>'30S|30S;1M|1M;2M|2M;3M|3M;5M|5M;10M|10M')
            ),

      //action 
      'refresh'=>array('name'=>'Refresh','type'=>'action', 'subtype'=>'other'),
      'ask_refresh'=>array('name'=>'Demande mise à jour','type'=>'action', 'subtype'=>'other'),
      'make_vibrate'=>array('name'=>'Vibration','type'=>'action', 'subtype'=>'other'),
      'make_ring'=>array('name'=>'Sonnerie','type'=>'action', 'subtype'=>'other'),
      'make_superlive'=>array('name'=>'Superlive','type'=>'action', 'subtype'=>'other'),


      //date 
      // 'creation_date'=>array('name'=>'Date Creation', 'type'=>'info', 'subtype'=>'string'),
      'expiration_date'=>array('name'=>'Date Expiration', 'type'=>'info' , 'subtype'=>'string'),
      // 'warranty_end'=>array('name'=>'Date de Garantie', 'type'=>'info', 'subtype'=>'string'),

      // zone en cours
      'curr_zone_id'=>array('name'=>'Id Zone Courante','type'=>'info', 'subtype'=>'string'),
      'curr_zone_name'=>array('name'=>'Nom Zone Courante','type'=>'info', 'subtype'=>'string'),

  );
  //tableau des configuration
  const W_CONF_common = array(
    'tracker_id'=>array('name'=>'Tracker id', 'info'=>'Id du tracker', 'type'=>'string'),
    'creation_date'=>array('name'=>'Date Creation', 'type'=>'date'),
    'warranty_end'=>array('name'=>'Date de Garantie', 'type'=>'date', 'info'=>'Date de fin de garantie du tracker'),
    'imei'=>array('name'=>'IMEI', 'type'=>'string'),
    'sim'=>array('name'=>'SIM', 'type'=>'string'),
    'type'=>array('name'=>'Type', 'type'=>'string'),
    'firmware'=>array('name'=>'Firmware', 'type'=>'string'),
    'related_zones'=>array('name'=>'Zones', 'info'=>'Zones attaché au trackers', 'hidden'=>true),
    'former_name'=>array('name'=>'former name', 'info'=>'ancien nom equipement', 'hidden'=>true),
  );

  public static function get_cmd_array(){
      return self::W_CMD_common;
  }
  /*  -----  utilisataire de calcul de distance entre deux points
  * selon la méthode de calcul Haversine http://villemin.gerard.free.fr/aGeograp/Distance.htm
  * retourne une distance en mètre
  * $_a : String : premier point 'latitude,longitude'
  * $_b : String : second point 'latitude,longitude'
  */
  public static function distance($_a, $_b) {
      $a = explode(',', $_a);
      $b = explode(',', $_b);
      $earth_radius = 6378.137;
      $rlo1 = deg2rad($a[1]);
      $rla1 = deg2rad($a[0]);
      $rlo2 = deg2rad($b[1]);
      $rla2 = deg2rad($b[0]);
      $dlo = ($rlo2 - $rlo1) / 2;
      $dla = ($rla2 - $rla1) / 2;
      $a = (sin($dla) * sin($dla)) + cos($rla1) * cos($rla2) * (sin($dlo) * sin($dlo));
      $d = 2 * atan2(sqrt($a), sqrt(1 - $a));
      return round(($earth_radius * $d)*1000, 2);
  }
  /* --------------------------------------------------------------------------------
  ------------------------------   Fonction de mise à jour --------------------------
  ---------------------------------------------------------------------------------- */
  /*  -----  utilisataire pour les appels aux commande de l'API
  * permet de gérer le renouvellement du Token si une réponse revenait négative
  * selon la méthode de calcul Haversine http://villemin.gerard.free.fr/aGeograp/Distance.htm
  * retourne une distance en mètre
  * $api_cmd : la command API , fonction static de la class W_API, qui ne demande que le toke
  * retourne le résultat de la commande, array [status, header, result]
  */
  public static function get_api_data($api_cmd){
    if (!method_exists('W_API', $api_cmd)) {
      log::add(__CLASS__, 'error', 'Weenect API commande not found :'.$api_cmd);
      return;
    }

    log::add(__CLASS__, 'debug', '║ ╟─── Task for api data :'.$api_cmd);
    $token = config::byKey('token', __CLASS__);
    if(!$token){
      log::add(__CLASS__, 'debug', '║ ╟─── Token  Not Found / start token retrieve');
      $token = weenect::update_token();
      if(!$token)return False;
    }
    log::add(__CLASS__, 'debug', '║ ╟─── Token  :'.$token);
    $args = array_slice(func_get_args(), 1);
    $merged_args = array_merge(array($token), $args);
    log::add(__CLASS__, 'debug', '║ ╟─── merged_args :'.json_encode($merged_args));
    $datas = call_user_func_array(array('W_API', $api_cmd), $merged_args);
    if(!W_API::test_status($datas['status'])){
      log::add(__CLASS__, 'debug', '║ ╟─── curl status error : '.$datas['status'].' => try update token');
      $token = weenect::update_token();
      if(!$token)return False;
      $datas = call_user_func_array(array('W_API', $api_cmd), $merged_args);
      if(!W_API::test_status($datas['status']))return False;
    }
    return $datas;
  }
  /*  -----  utilisataire pour renouveller le toker
  * mise à jour de la configuration du plugin avec le toke,n
  */
  public static function update_token(){
    log::add(__CLASS__, 'debug', '║ ╟─── renew token ....');
    $uname =  config::byKey('username', __CLASS__);
    $pass =  config::byKey('password', __CLASS__);
    log::add(__CLASS__, 'debug', '║ ╟─── username :'.$uname);
    log::add(__CLASS__, 'debug', '║ ╟─── password :'.$pass);
    $token = W_API::get_token($uname, $pass);
    if(!$token){
      log::add(__CLASS__, 'error', '## Unable to update token ##');
    }
    log::add(__CLASS__, 'debug', '║ ╟─── Save token in config :'.$token);
    config::save('token',$token, __CLASS__);
    return $token;
  }
  /*  -----  lancement de la mise à jour de toutes les informations, data et position.
  * permet de gérer le renouvellement du Token si une réponse revenait négative
  */
  public static function update_all(){
    // $pos = weenect::update_position();
    $gen = weenect::update_general(TRUE);
    $cronset = weenect::setUpdateCron();
    // return $pos && $gen;
    return array("next_due_date"=>$cronset);
  }


  /*  -----  lancement de la mise à jour des positions de tous les tracker.
  */
  public static function update_position(){
    log::add('weenect', 'debug', "║ ╠════════════════ Update All position data ");
    foreach(eqLogic::byType("weenect") as $tracker){
      $tracker->update_tracker_position();
    }
  }

  /*  -----  lancement de la mise à jour des positions de toutes les information et position des tracker.
  * $_updatePos : si on souhaite mettre à jours les positions en même temps que les infos
  */
  public static function update_general($_updatePos = TRUE){
    $general = weenect::get_api_data('get_account_datas');
    foreach($general['result']['items'] as $tracker){
      log::add('weenect', 'debug', "║ ╠════════════════ update Configuration tracker : ".json_encode( $tracker));
      weenect::update_general_tracker($tracker, $_updatePos);
    }
    return True;
  }

  /*  ----- Mis à jours des positions d'un tracker particulier
  * création d'un nouveau tracker si son id (weenect) est inconnnu
  * $datas : array des données du tracker.
  */
  public static function update_tracker($datas){
    $tId = $datas['tracker_id'];
    log::add('weenect', 'debug', "║ ╠════════════════ update tracker Position : ".$tId);
    $eqLogic=eqLogic::byLogicalId($tId, "weenect");
    if(!$eqLogic){
      log::add(__CLASS__, 'debug', '║ ╟─── No Tracker found, create a new one ');
      $eqLogic=weenect::create_new_tracker($tId);
    }    
    $eqLogic->updateCMDfromArray($datas);// for general informations
    $eqLogic->updateCMDfromArray($datas['position'][0]);// for position
    $eqLogic->update_coordinate(self::W_CMD_common['coord']);
    $eqLogic->updateCurrentZone();
    log::add('weenect', 'debug', "║ ╠════════════════ End update tracker ");
  }
  /*  ----- Mis à jours des données générales d'un tracker, voire des positions également
  * création d'un nouveau tracker si son id (weenect) est inconnnu
  * $datas : array des données du tracker.
  * $update_position : mis à jour des position également (retournée dans le même appel à l'api...)
  */
  public static function update_general_tracker($datas, $update_position = False){
    $tId = $datas['id'];
    log::add('weenect', 'debug', "║ ╠════════════════ update General tracker  : ".$tId);
    $eqLogic=eqLogic::byLogicalId($tId, "weenect");
    if(!$eqLogic){
      log::add(__CLASS__, 'debug', '║ ╟─── No Tracker found, create a new one ');
      $eqLogic=weenect::create_new_tracker($tId);
    }
    $eqLogic->updateCONFfromArray($datas, weenect::W_CONF_common);
    // update call time 
    $total_time = $datas['call_max_threshold'];
    $used_time = $datas['call_usage'];
    $left_time = $total_time - $used_time;
    $eqLogic->checkAndUpdateCmd('left_call', $left_time);


    log::add(__CLASS__, 'debug', '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> Time LEFT calculation :'.$total_time." - ".$used_time." = ".$left_time);
    // zone update

    if(array_key_exists("zones", $datas)){
      $ids = array_map(function($item) {return $item["id"];}, $datas['zones']);
      $isSet = $eqLogic->setConfiguration('related_zones', json_encode($ids));
      $eqLogic->save();
      weenect_zone::update_zones($eqLogic, $datas['zones']);
    }

    // position update
    if($update_position){
      // log::add('weenect', 'debug', "position update datas :".json_encode($datas));
      $datas['tracker_id']=$tId;
      weenect::update_tracker($datas);
    }
    $eqLogic->updateCurrentZone();
    log::add('weenect', 'debug', "║ ╠════════════════ End update General tracker ");
  }

  /*  ----- Création d'un nouveau tracker
  * récupère le nom via un appel API
  * création d'un nouveau tracker si son id (weenect) est inconnnu
  * $idTracker : id weenect du tracker, qui sera l'idlogic de l'eqlogic
  */
  public static function create_new_tracker($idTracker){
    log::add(__CLASS__, 'debug', "║ ╟─── create a new tracker $idTracker");
    
    // récupération des données pour le nom du tracker
    $data = weenect::get_api_data('get_account_datas');

    foreach($data['result']['items'] as $tracker){
      $id = W_API::gvfa($tracker, "id");
      if($id && $id == $idTracker){
        $name = W_API::gvfa($tracker, "name");
        break;
      
      }
    }
    if($name==undefined || !$name){
      $name = "Tracker ".uniqid();
    }
    log::add(__CLASS__, 'debug', "║ ╟─── tracker name : $name");
    $eqLogic = new weenect();
    $eqLogic->setName($name);
    $eqLogic->setEqType_name('weenect'); 
    $eqLogic->setLogicalId($idTracker);
    $eqLogic->setIsEnable(1);
    $eqLogic->setConfiguration('tracker_id', $idTracker);
    // Enregistrez le nouvel eqLogic
    $eqLogic->save();
    return $eqLogic;
  }



  /* -----------------------------------------------------------------------
  ------------------------------   Fonction CRON  --------------------------
  ------------------------------------------------------------------------- */
  /**  ----- Mis à jour/en place des appel cron.
  * ajoute/modifie une entrée dans le moteur des taches de jeedom
  * selon la configuration du plugin
  * lancé lors de l'appel ajax au save de la configuration.
  * retourne une string avec les information de date des dernier et prochain appel
  */

  public static function setUpdateCron()
	{ 
    
    // called by ajax in config
		log::add(__CLASS__, 'debug', "║  ╠════════════════ update cron called");

		// get frequency from config
		$freq = config::byKey('freq', __CLASS__);
		if ($freq == 'prog') $freq = config::byKey('autorefresh', __CLASS__);

		if ($freq == '' || is_null($freq)) { // set default if not set
			log::add(__CLASS__, 'debug', "║ ╟─── ".__('Aucun Cron Défini pour la mise à jour, passage au défaut :', __FILE__) . self::DEFAULT_CRON);
			$freq = self::DEFAULT_CRON;
		}
		log::add(__CLASS__, 'debug', "Add cron to freq : $freq ");
		// update cron
		$cron = cron::byClassAndFunction(__CLASS__, 'update_position');
    if($freq == 'manual'){
      if(is_object($cron)){
        log::add(__CLASS__, 'debug', "║ ╟───  remove current cron");
        $cron->remove();
      }
      return Null;
    }

		if (!is_object($cron)) {
			$cron = new cron();
			$cron->setClass(__CLASS__);
			$cron->setFunction('update_position');
		}
		$cron->setEnable(1);
		$cron->setDeamon(0);
		$cron->setSchedule(checkAndFixCron($freq));
		$cron->save();
    return self::getDueDateStr($freq);

	}

  /**  ----- Mis à des information générales.
  * toutes les 15 minutes
  */
  public static function cronDaily() {
    self::update_general(FALSE);
  }
  /**  ----- utilitaire pour construire un array avec les dates du prochain lancement du cron.
  * $freq : définition du cron
  * retourne une string avec les information de date des dernier et prochain appel
  */
  public static function getDueDateStr($freq)
	{
		$c = new Cron\CronExpression(checkAndFixCron($freq), new Cron\FieldFactory);
		$calculatedDate = array('prevDate' => '', 'nextDate' => '');
		$calculatedDate['prevDate'] = $c->getPreviousRunDate()->format('Y-m-d H:i:s');
		$calculatedDate['nextDate'] = $c->getNextRunDate()->format('Y-m-d H:i:s');
		return $calculatedDate;
	}



  /* --------------------------------------------------------------------------------------
     -----------------------------------  Methodes d'instance -----------------------------
     -------------------------------------------------------------------------------------- */
  
     // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
    //synchro des zones
    $odlName= $this->getConfiguration('former_name');// ancien nom de l'équipement
    $zones = weenect_zone::byTracker($this->getLogicalId());
    foreach ($zones as $zone){
      $zone->updateFromTracker($this, $odlName);
    }
    $this->setConfiguration('former_name', $this->getName());// référencement du nouveau nom
    
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    //     les commandes générales
    $this->createCMDFromArray(weenect::W_CMD_common);
    // lien slider set freq
    $valCmd = $this->getCmd(null, 'last_freq_mode');
    $slCmd = $this->getCmd(null, 'set_freq_mode');
    if(is_object($slCmd)){
      log::add(__CLASS__, 'debug', 'slide value before :'.$slCmd->getValue());
      log::add(__CLASS__, 'debug', 'Set Slider :'.$slCmd->getHumanName()." => ".$valCmd->getId());
      $slCmd->setValue($valCmd->getId());
      // $slCmd->setConfiguration('infoId', $valCmd->getId());
      $slCmd->save();
      log::add(__CLASS__, 'debug', 'slide value :'.$slCmd->getValue());
    }
    // mise à jour de la commande coord : String latitude,longitude
    $this->update_coordinate(self::W_CMD_common['coord']);
    // mise à jour de la position du tracker dans une zone
    $this->updateCurrentZone();    
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
    // Suppresion des zones liées au tracker, p^par idLogic du tracker
    log::add(__CLASS__, "debug", "║  ╠════════════════ preRemove equipement, remove  zones at first");
    $eqLogics = weenect_zone::byTracker($this->getLogicalId());
    foreach ($eqLogics as $eqLogic){
      log::add(__CLASS__, "debug", "║ ╟─── remove zone :".$eqLogic->getId()." / ".$eqLogic->getHumanName());
      $eqLogic->remove();
    }
  }

  public function update_tracker_position(){
    log::add('weenect', 'debug', "║ ╠════════════════ Update Single Tracker position data : ".$this->getName());
    $datas = weenect::get_api_data('get_tracker_position', $this->getLogicalId());
    $tracker_data = array(
      'tracker_id'=>$this->getLogicalId(), 
      "position"=>$datas['result']
    );
    log::add('weenect', 'debug', "║ ╠════════════════ position data : ".print_r($tracker_data, true));
    weenect::update_tracker($tracker_data);
  }
  /**  ----- mise à jour de la zone occupé par le tracker
  * met à jour les commande 'curr_zone_id' (idLogic de la zone en cours) et 'curr_zone_name' (nom de la zone en cours)
  * met à 0 les deux commande si aucune zone n'est occupé
  * si dist tracker<->zone <= radius Tracker + distance Zone
  * met à jour la commande 'is_in' de chacune des zone
  */
  public function updateCurrentZone(){
      log::add(__CLASS__, 'debug', '║  ╠════════════════ updateCurrentZone' );
      // calcul des distance pour zone 
      $cmd = $this->getCmd(null, 'coord');
      $tPos = (is_object($cmd)?$cmd->execCmd():null);
      if(!$tPos){
        log::add(__CLASS__, 'debug', 'Error : current position not set');
      }
      $tRadCmd =  $this->getCmd(null, 'radius');
      $tRad = (is_object($tRadCmd)?$tRadCmd->execCmd():0);

      $zones = weenect_zone::byTracker($this->getLogicalId());
      // log::add(__CLASS__, 'debug', 'tracker calc - # pos :'.$tPos." radius : ".$tRad );
      $found =false;
      foreach ($zones as $zone){
          $zCmd= $zone->getCmd(null, 'coord');
          $zPos = (is_object($zCmd)?$zCmd->execCmd():null);
          if(!$zPos)continue;

          $zRadCmd =  $zone->getCmd(null, 'radius');
          $in = $zone->getCmd(null, 'is_in');

          $zRad = (is_object($zRadCmd)?$zRadCmd->execCmd():0);
          $dist = self::distance($tPos, $zPos);
          $rad =  $tRad + $zRad;
          // log::add(__CLASS__, 'debug', 'dist calc - '.$zone->getName()." # pos :".$zPos." radius : ".$rad );
          // log::add(__CLASS__, 'debug', 'dist = '.$dist);
          if($rad >= $dist){
            
            $this->checkAndUpdateCmd('curr_zone_id', $zone->getLogicalId());
            $this->checkAndUpdateCmd('curr_zone_name', $this->get_clean_zonename($zone->getName()));
            $zone->checkAndUpdateCmd('is_in', 1);
            $found = true;
          }else{
            $zone->checkAndUpdateCmd('is_in', 0);
          }
      }
      if(!$found){
        $this->checkAndUpdateCmd('curr_zone_id',0);
        $this->checkAndUpdateCmd('curr_zone_name', 0);
      }

  }
  public function get_clean_zonename($zName){
    return preg_replace('/'.$this->getName().'-/',"",$zName);
  }
    /* ------------------------------------------------------------------------
     -----------------------------------  ACTIONS -----------------------------
     -------------------------------------------------------------------------- */
    public function send_action($action_type){
      $eqId = $this->getLogicalId();
      $return = weenect::get_api_data('launch_command', $eqId, $action_type);
    }

    public function updateFhz($fhz){
      log::add(__CLASS__, 'debug', "Ask for FHz Update :".$fhz);
      $eqId = $this->getLogicalId();
      $return = weenect::get_api_data('launch_command', $eqId, 'set_freq_mode', array('mode'=>$fhz));
      weenect::update_all();

    }

    /* --------------------------------------------------------------------------------------
     ------------------------------------  widget personnalisé ------------------------------
     -------------------------------------------------------------------------------------- */
  
    public static function getMapLayers(){
      return array(
        'CartoDB.DarkMatter'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
        'CartoDB.DarkMatterNoLabels'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
        'CartoDB.Positron'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
        'CartoDB.PositronNoLabels'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
        'CartoDB.Voyager'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
        'CartoDB.VoyagerLabelsUnder'=>array('url'=>'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
        'CartoDB.VoyagerNoLabels'=>array('maxZoom'=>19,'url'=>'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png','subdomains'=>'abcd','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>'),
        //'Esri.DeLorme'=>array('minZoom'=>1,'maxZoom'=>11,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/Specialty/DeLorme_World_Base_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Copyright: &copy;2012 DeLorme'),
        'Esri.NatGeoWorldMap'=>array('maxZoom'=>16,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; National Geographic, Esri, DeLorme, NAVTEQ, UNEP-WCMC, USGS, NASA, ESA, METI, NRCAN, GEBCO, NOAA, iPC'),
        //'Esri.OceanBasemap'=>array('maxZoom'=>13,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Sources: GEBCO, NOAA, CHS, OSU, UNH, CSUMB, National Geographic, DeLorme, NAVTEQ, and Esri'),
        'Esri.WorldGrayCanvas'=>array('maxZoom'=>16,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ'),
        'Esri.WorldImagery'=>array('url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'),
        //'Esri.WorldPhysical'=>array('maxZoom'=>8,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Physical_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: US National Park Service'),
        // 'Esri.WorldShadedRelief'=>array('maxZoom'=>13,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: Esri'),
        'Esri.WorldStreetMap'=>array('url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012'),
        'Esri.WorldTerrain'=>array('maxZoom'=>13,'url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Source: USGS, Esri, TANA, DeLorme, and NPS'),
        'Esri.WorldTopoMap'=>array('url'=>'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}','attribution'=>'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community'),
        'OpenStreetMap.BZH'=>array('maxZoom'=>19,'url'=>'https://tile.openstreetmap.bzh/br/{z}/{x}/{y}.png','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors, Tiles courtesy of <a href=\"http://www.openstreetmap.bzh/\" target=\"_blank\">Breton OpenStreetMap Team</a>'),
        'OpenStreetMap.DE'=>array('maxZoom'=>18,'url'=>'https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
        'OpenStreetMap.France'=>array('maxZoom'=>20,'url'=>'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png','attribution'=>'&copy; Openstreetmap France | &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
        'OpenStreetMap.HOT'=>array('maxZoom'=>19,'url'=>'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors, Tiles style by <a href=\"https://www.hotosm.org/\" target=\"_blank\">Humanitarian OpenStreetMap Team</a> hosted by <a href=\"https://openstreetmap.fr/\" target=\"_blank\">OpenStreetMap France</a>'),
        'OpenStreetMap.Mapnik'=>array('maxZoom'=>19,'url'=>'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png','attribution'=>'&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
        'OpenTopoMap'=>array('maxZoom'=>17,'url'=>'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png','attribution'=>'Map data: &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors, <a href=\"http://viewfinderpanoramas.org\">SRTM</a> | Map style: &copy; <a href=\"https://opentopomap.org\">OpenTopoMap</a> (<a href=\"https://creativecommons.org/licenses/by-sa/3.0/\">CC-BY-SA</a>)'),
        //'Stamen.Terrain'=>array('minZoom'=>0,'maxZoom'=>18,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
        //'Stamen.TerrainBackground'=>array('minZoom'=>0,'maxZoom'=>18,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/terrain-background/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
        // 'Stamen.Toner'=>array('minZoom'=>0,'maxZoom'=>20,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/toner/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
        // 'Stamen.TonerBackground'=>array('minZoom'=>0,'maxZoom'=>20,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/toner-background/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
        // 'Stamen.TonerLite'=>array('minZoom'=>0,'maxZoom'=>20,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}{r}.{ext}','subdomains'=>'abcd','ext'=>'png','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
        // 'Stamen.Watercolor'=>array('minZoom'=>1,'maxZoom'=>16,'url'=>'https://stamen-tiles-{s}.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.{ext}','subdomains'=>'abcd','ext'=>'jpg','attribution'=>'Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'),
        // 'Wikimedia'=>array('minZoom'=>1,'maxZoom'=>19,'url'=>'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png','attribution'=>'<a href=\"https://wikimediafoundation.org/wiki/Maps_Terms_of_Use\">Wikimedia</a')
      );
  }

  public function toHtml($_version = 'dashboard') {
    $replace = $this->preToHtml($_version, array(), true);
    if (!is_array($replace)) {
        return $replace;
    }
    
    log::add(__CLASS__, 'debug', '-----------------------  to html ------------------------------');
    $version = jeedom::versionAlias($_version);
    $replace['#version#'] = $_version;
    $replace['#logicalId#'] = $this->getLogicalId();
    $refresh = $this->getCmd(null, 'refresh');
    if (is_object($refresh)) {
        $replace['#refresh_id#'] = $refresh->getId();
    }
    $cmd= $this->getCmd(null, 'make_vibrate');
    if (is_object($cmd)) {
        $replace['#vibrate_id#'] = $cmd->getId();
    }
    $cmd= $this->getCmd(null, 'make_ring');
    if (is_object($cmd)) {
        $replace['#ring_id#'] = $cmd->getId();
    }
    $cmd= $this->getCmd(null, 'ask_refresh');
    if (is_object($cmd)) {
        $replace['#ask_refresh#'] = $cmd->getId();
    }
    


    $data = array();
    // les fond de carte 
    $mapsBG = self::getMapLayers();
    if(array_key_exists(config::byKey('light-theme', 'weenect', 'OpenStreetMap.Mapnik'), $mapsBG)){
        $data['light-theme'] = $mapsBG[config::byKey('light-theme', 'weenect', 'OpenStreetMap.Mapnik')];
    }else{
        $data['light-theme'] =$mapsBG['OpenStreetMap.Mapnik'];
    }
    if(array_key_exists(config::byKey('dark-theme', 'weenect', 'OpenStreetMap.Mapnik'), $mapsBG)){
        $data['dark-theme'] = $mapsBG[config::byKey('dark-theme', 'weenect', 'OpenStreetMap.Mapnik')];
    }else{
        $data['dark-theme'] = $mapsBG['OpenStreetMap.Mapnik'];
    }

    $data['control-zoom'] = ($version == 'dashboard');
    $data['control-attributions'] = ($version == 'dashboard');


    $replace['#height-map#'] = ($version == 'dashboard') ? intval($replace['#height#']) - 70 : 250;
    $replace['#tracker_id#'] = $this->getLogicalId();
    // tracker info
    $cmd =$this->getCmd(null, 'coord');
    if(is_object($cmd))$replace['#coordonate#'] = "<span id='coord' class='cmd weenect-coord ' data-cmd_id='".$cmd->getId()."'>".$cmd->execCmd()."</span>";
    $data['tracker']=$this->buildLocation();
    $data['tracker']['color']=config::byKey('tracker-color', __CLASS__)?:self::DEFAULT_TRACKER_COLOR;
    $data['tracker']['heatmap']=false;
    
    $cmd  =$this->getCmd(null, 'curr_zone_name');
    $zName= is_object($cmd) ? $cmd->execCmd():0;
    $zName = preg_replace('/'.$this->getName().'-/',"",$zName);
    if(! $zName || $zName==0) $zName="-";
    $replace['#current_zone#'] ="<span id='current_zone' class='cmd weenect-current' data-cmd_id='".( is_object($cmd) ?$cmd->getId():0)."'>". $zName."</span>";
    $data['tracker']['current_zone']=static::buildCmd($cmd);
    
    $cmd=$this->getCmd(null, 'date_tracker');
    if(is_object($cmd))$replace['#last_seen#']  =  "<span id='date_tracker' class='cmd weenect-horodatage' data-cmd_id='".$cmd->getId()."'>".$cmd->execCmd()."</span>";
    $data['tracker']['last_seen']=static::buildCmd($cmd);
    
    $cmd  =$this->getCmd(null, 'battery');
    if(is_object($cmd)) $replace['#tracker_battery#']  =  "<span class='cmd weenect-battery-icon' data-cmd_id='".$cmd->getId()."'><i class='fas fa-battery-half'></i></span><span id='battery' class='cmd weenect-battery' data-cmd_id='".$cmd->getId()."'>".$cmd->execCmd()."</span><span class='cmd weenect-battery'>%</span>";
    $data['tracker']['battery']=static::buildCmd($cmd);
   
   
    $cmd  =$this->getCmd(null, 'radius');
    if(is_object($cmd))$replace['#accuracy#'] = "<span id='radius' class='cmd weenect-precision' data-cmd_id='".$cmd->getId()."'> Precision :".$cmd->execCmd()." m</span>";
    $data['tracker']['radius']=static::buildCmd($cmd);

    $cmd  =$this->getCmd(null, 'satellites');
    if(is_object($cmd))$replace['#satellites#'] = "<span class='cmd weenect-satellites-icon' data-cmd_id='".$cmd->getId()."'><i class='fas fa-battery-half'></i></span> <span id='satellites' class='cmd weenect-satellites' data-cmd_id='".$cmd->getId()."'> ".$cmd->execCmd()."</span>";
    $data['tracker']['satellites']=static::buildCmd($cmd);

    // history du tracker
    if($this->getConfiguration("show_history")){
      $data['tracker']['history']=config::byKey('history_duration', __CLASS__);
    }
    
    // for zone
    $data['zones']=array();
    $zones = weenect_zone::byTracker($this->getLogicalId());
    $zoneColor = config::byKey('zone-color', __CLASS__)?:self::DEFAULT_ZONE_COLOR;
    foreach($zones as $z){
      $zId = $z->getLogicalId();
      $data['zones'][$zId]=$z->buildLocation();
      $data['zones'][$zId]['color']=$zoneColor;
      $data['zones'][$zId]['name']=preg_replace('/'.$this->getName().'-/',"",$data['zones'][$zId]['name']);
      $cmd = $z->getCmd(null, "is_in");
      $data['zones'][$zId]['is_in']=static::buildCmd($cmd);

    }
    $showPin = ($version == 'dashboard') ? config::byKey('show-pin_dash', __CLASS__):config::byKey('show-pin_mob', __CLASS__);
    $showZname = ($version == 'dashboard') ? config::byKey('show-zname_dash', __CLASS__):config::byKey('show-zname_mob', __CLASS__);
    
    //options 
    $data['options']=array(
      'pin' => $showPin,
      'zone_name' => $showZname,
      'dynamic_color' => config::byKey('dynamic_color', __CLASS__)
    );

    if($_version=='mobile'){
      $replace['#class#'] ="allowResize col2";
    }

    $replace['#json#'] = str_replace("'", "\'", json_encode($data));
    // renvoi du template
    $tempFile = getTemplate('core', $version, 'weenect_tile', 'weenect');
    $html = $this->postToHtml($_version, template_replace($replace,$tempFile));
    $html = translate::exec($html, 'plugins/weenect/core/template/' . $version . '/weenect_tile.html');
  
    return $html;
  }

  


//  ======================= Class weenect END
}

class weenectCmd extends cmd {

  public function preSave(){
      if($this->getLogicalId()=='coord'){
        //Gestion de l'historisation si l'équipement a coché "affciher l'historique" en configuration
        $show_hist = $this->getEqLogic()->getConfiguration("show_history");
        $vmd_isHis = $this->getIsHistorized();
        if($show_hist && !$vmd_isHis){
          log::add("weenect", "debug", "║ ╟─── Ask to show history on widget but coord cmd not historized => activate history (1 month by default if none)");
          $this->setIsHistorized(True);
          if( $this->getConfiguration("historyPurge")=="")$this->setConfiguration("historyPurge", "-1 month");
        }
      }
  }
  // Exécution d'une commande
  public function execute($_options = array()) {
    log::add('weenect','debug', "╔═══════════════════════ execute CMD : ".$this->getId()." | ".$this->getHumanName().", logical id : ".$this->getLogicalId() ."  options : ".json_encode($_options));
    log::add('weenect','debug', '╠════ Eq logic '.$this->getEqLogic()->getHumanName());
    switch($this->getLogicalId()){
      case 'update':
      case 'refresh':
        // demande la mise à jour de toutes les position des trackers.
        // weenect::update_position();
        $this->getEqLogic()->update_tracker_position();
        break;
      case 'ask_refresh':
      case 'make_vibrate':
      case 'make_ring':
        $this->getEqLogic()->send_action($this->getLogicalId());
        break;
      case 'set_freq_mode':
          $fzCmd = $this->getEqLogic()->updateFhz($_options['select']);
          break;
      default:
        log::add('weenect','debug', '╠════ Default call');

   } 
   log::add('weenect','debug', "╚═════════════════════════════════════════ END execute CMD ");
  }

}
