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
  // tableaux des commandes
  const W_CMD_common = array(
      'type'=>array('name'=>'type','type'=>'info', 'subtype'=>'string'), 
      'date_tracker'=>array('name'=>'dernière date','type'=>'info', 'subtype'=>'string'),
      
      // metrics 
      'battery'=>array('name'=>'Battery','type'=>'info', 'subtype'=>'numeric'),
      'gsm'=>array('name'=>'GSM','type'=>'info', 'subtype'=>'numeric'),
      'signal_strength_percent'=>array('name'=>'Signal','type'=>'info', 'subtype'=>'numeric'),
      
      // position
      'latitude'=>array('name'=>'Latitude','type'=>'info', 'subtype'=>'numeric'),
      'longitude'=>array('name'=>'Longitude','type'=>'info', 'subtype'=>'numeric'),
      'radius'=>array('name'=>'Radius','type'=>'info', 'subtype'=>'numeric'),
      'coord'=>array('name'=>'Coordonnées','type'=>'info', 'subtype'=>'string'),
      
      // status
      'is_online'=>array('name'=>'Online','type'=>'info', 'subtype'=>'binary'),
      'valid_signal'=>array('name'=>'Valid Signal','type'=>'info', 'subtype'=>'binary'),  
      'is_in_deep_sleep'=>array('name'=>'Deepsleep','type'=>'info', 'subtype'=>'binary'),

      //action 
      'update'=>array('name'=>'Mettre à jour','type'=>'action', 'subtype'=>'other'),

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
    'related_zones'=>array('name'=>'Zones', 'info'=>'Zones attaché au trackers', 'hidden'=>true),
    'former_name'=>array('name'=>'former name', 'info'=>'ancien nom equipement', 'hidden'=>true),
  );

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
  public function get_api_data($api_cmd){
    log::add(__CLASS__, 'debug', '║ ╟─── Task for api data :'.$api_cmd);
    $token = config::byKey('token', __CLASS__);
    if(!$token){
      log::add(__CLASS__, 'debug', '║ ╟─── Token  Not Found / start token retrieve');
      $token = weenect::update_token();
      if(!$token)return False;
    }
    log::add(__CLASS__, 'debug', '║ ╟─── Token  :'.$token);
    $datas = W_API::$api_cmd($token);
    if(!W_API::test_status($datas['status'])){
      log::add(__CLASS__, 'debug', '║ ╟─── curl status error : '.$datas['status'].' => try update token');
      $token = weenect::update_token();
      if(!$token)return False;
      W_API::$api_cmd($token);
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
    $gen = weenect::update_general();
    $cronset = weenect::setUpdateCron();
    // return $pos && $gen;
    return array("next_due_date"=>$cronset);
  }


  /*  -----  lancement de la mise à jour des positions de tous les tracker.
  */
  public static function update_position(){
    $datas = weenect::get_api_data('get_tracker_position');
    log::add('weenect', 'debug', "║ ╠════════════════ position data : ".json_encode( $datas));
    if(!$datas)return false;
    foreach($datas['result'] as $tracker){
      weenect::update_tracker($tracker);
    }
    return True;
  }

  /*  -----  lancement de la mise à jour des positions de toutes les information et position des tracker.
  */
  public static function update_general(){
    $general = weenect::get_api_data('get_account_datas');
    foreach($general['result']['items'] as $tracker){
      log::add('weenect', 'debug', "║ ╠════════════════ update Configuration tracker : ".json_encode( $tracker));
      weenect::update_general_tracker($tracker, TRUE);
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
    $eqLogic->updateCMDfromArray($datas['position'][0]);
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
	{ // called by ajax in config
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
      foreach ($zones as $zone){
          $zCmd= $zone->getCmd(null, 'coord');
          $zPos = (is_object($zCmd)?$zCmd->execCmd():null);
          if(!$zPos)continue;

          $zRadCmd =  $zone->getCmd(null, 'distance');
          $in = $zone->getCmd(null, 'is_in');

          $zRad = (is_object($zRadCmd)?$zRadCmd->execCmd():0);
          $dist = self::distance($tPos, $zPos);
          $rad =  $tRad +$zRad;
          // log::add(__CLASS__, 'debug', 'dist calc - '.$zone->getName()." # pos :".$zPos." radius : ".$zRad );
          // log::add(__CLASS__, 'debug', 'dist = '.$dist);
          if($rad>= $dist){
            $zIdcmd =$this->getCmd(null, 'curr_zone_id');
            if(is_object($zIdcmd))$zIdcmd->event($zone->getLogicalId());
            $zNamecmd =$this->getCmd(null, 'curr_zone_name');
            if(is_object($zNamecmd))$zNamecmd->event($zone->getName());
            if(is_object($in))$in->event(1);
            
          }else{
            if(is_object($in))$in->event(0);
          }
      }
      $zIdcmd =$this->getCmd(null, 'curr_zone_id');
      if(is_object($zIdcmd))$zIdcmd->event(0);
      $zNamecmd =$this->getCmd(null, 'curr_zone_name');
      if(is_object($zNamecmd))$zNamecmd->event(null);
  }
}

class weenectCmd extends cmd {

  // Exécution d'une commande
  public function execute($_options = array()) {
    log::add('weenect','debug', "╔═══════════════════════ execute CMD : ".$this->getId()." | ".$this->getHumanName().", logical id : ".$this->getLogicalId() ."  options : ".print_r($_options));
    log::add('weenect','debug', '╠════ Eq logic '.$this->getEqLogic()->getHumanName());
    switch($this->getLogicalId()){
      case 'update':
        // demande la mise à jour de toutes les position des trackers.
        weenect::update_position();
        break;
      default:
        log::add('weenect','debug', '╠════ Default call');

   } 
   log::add('weenect','debug', "╚═════════════════════════════════════════ END execute CMD ");
  }

}
