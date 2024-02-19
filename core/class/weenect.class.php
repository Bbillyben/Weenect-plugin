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
require_once __DIR__  . '/W_API.class.php';

class weenect extends weenect_base {
  const DEFAULT_CRON = "*/30 * * * *";
  // définition des commandes
  const W_CMD_common = array(
      // => en configuration 'tracker_id'=>array('name'=>'Tracker Id','type'=>'info', 'subtype'=>'string'),
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
      'refresh'=>array('name'=>'Refresh','type'=>'action', 'subtype'=>'other')

  );
  const W_CONF_common = array(
    'tracker_id'=>array('name'=>'Tracker id', 'info'=>'Id du tracker', 'type'=>'string'),
    'creation_date'=>array('name'=>'Date Creation', 'type'=>'date'),
    'expiration_date'=>array('name'=>'Date Expiration', 'type'=>'date' , 'info'=>'Date d\'expiration de l\'abonnement'),
    'warranty_end'=>array('name'=>'Date de Garantie', 'type'=>'date', 'info'=>'Date de fin de garantie du tracker'),
  );


  /* --------------------------------------------------------------------------------
  ------------------------------   Fonction de mise à jour --------------------------
  ---------------------------------------------------------------------------------- */
  // get data from appi where only token is required
  // handle the update of the token
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
  // mise à jour du token
  // enregistrement du nouveau token dans la config de l'équipement pour usage ultérieur
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
  // ==========================
  // Update all eqLogic from data
  // update both position and informations
  public static function update_all(){
    // $pos = weenect::update_position();
    $gen = weenect::update_general();
    $cronset = weenect::setUpdateCron();
    // return $pos && $gen;
    return array("next_due_date"=>$cronset);
  }


  //update position of trackers
  public static function update_position(){
    $datas = weenect::get_api_data('get_tracker_position');
    log::add('weenect', 'debug', "║ ╠════════════════ position data : ".json_encode( $datas));
    if(!$datas)return false;
    foreach($datas['result'] as $tracker){
      weenect::update_tracker($tracker);
    }
    return True;
  }

  // update datas other than positions
  public static function update_general(){
    $general = weenect::get_api_data('get_account_datas');
    foreach($general['result']['items'] as $tracker){
      log::add('weenect', 'debug', "║ ╠════════════════ update Configuration tracker : ".json_encode( $tracker));
      weenect::update_general_tracker($tracker, TRUE);
    }
    return True;
  }

  // mise à jour du tracker en fonction de son id
  // si auncun trouver en créé un nouveau
  public static function update_tracker($datas){
    $tId = $datas['tracker_id'];
    log::add('weenect', 'debug', "║ ╠════════════════ update tracker Position : ".$tId);
    $eqLogic=eqLogic::byLogicalId($tId, "weenect");
    if(!$eqLogic){
      log::add(__CLASS__, 'debug', '║ ╟─── No Tracker found, create a new one ');
      $eqLogic=weenect::create_new_tracker($tId);
    }    
    $eqLogic->updateCMDfromArray($datas['position'][0]);
    $eqLogic->update_coordinate();
    log::add('weenect', 'debug', "║ ╠════════════════ End update tracker ");
  }

  public static function update_general_tracker($datas, $update_position = False){
    $tId = $datas['id'];
    log::add('weenect', 'debug', "║ ╠════════════════ update General tracker  : ".$tId);
    $eqLogic=eqLogic::byLogicalId($tId, "weenect");
    if(!$eqLogic){
      log::add(__CLASS__, 'debug', '║ ╟─── No Tracker found, create a new one ');
      $eqLogic=weenect::create_new_tracker($tId);
    }
    $eqLogic->updateCONFfromArray($datas, weenect::W_CONF_common);
    if($update_position){
      $datas['tracker_id']=$tId;
      weenect::update_tracker($datas);
    }
    log::add('weenect', 'debug', "║ ╠════════════════ End update General tracker ");
  }

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
  /** Function setUpdateCron : called when by ajax on configuraiton save
	 * to update position of trackers  */
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
  /** Function getDueDateStr : called when by getDueDate (ajax on configuration save) to get string from due date of cron job */
	public static function getDueDateStr($freq)
	{
		$c = new Cron\CronExpression(checkAndFixCron($freq), new Cron\FieldFactory);
		$calculatedDate = array('prevDate' => '', 'nextDate' => '');
		$calculatedDate['prevDate'] = $c->getPreviousRunDate()->format('Y-m-d H:i:s');
		$calculatedDate['nextDate'] = $c->getNextRunDate()->format('Y-m-d H:i:s');
		return $calculatedDate;
	}

  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */
  // public static $_encryptConfigKey = array( 'token');

  /*     * ***********************Methode static*************************** */

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */
  
  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      return "les infos essentiel de mon plugin";
   }
   */

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    //     les commandes générales
    $this->createCMDFromArray(weenect::W_CMD_common);
    $this->update_coordinate();
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  public function update_coordinate(){
    log::add(__CLASS__, 'debug', "║ ╟───────────── update coordinates ");
    $wCMD = $this->getCmd(null, "coord");
    if (!is_object($wCMD)){
      $this->createCMDFromArray([weenect::W_CMD_common['coord']]);
    }
    $latCMD = $this->getCmd(null, "latitude");
    $longCMD = $this->getCmd(null, "longitude");
    if(is_object($latCMD) && is_object($longCMD)){
      $coord = $latCMD->execCmd() . "," . $longCMD->execCmd();
      $wCMD->event($coord);
      $wCMD->save();
    }
  }
//   /*    ----- fonction pour mettre à jour les valeurs à partir d'un array 
//       * dont les clé sont les logicalId des commandes (cf les array de classe)
//       * contenant la clé status => 200 Ok si on doit remplir les données
//   */
//   public function updateCMDfromArray($data){
//     log::add(__CLASS__, 'debug', "║ ╟───────────── update commands :".json_encode($data));
//     foreach($data as $logId => $val){
//       log::add(__CLASS__, 'debug', "║ ╟─── commands :".$logId);
//         if($logId=='status')continue;
//         $wCMD = $this->getCmd(null, $logId);
//         if (is_object($wCMD)) {
//           log::add(__CLASS__, 'debug', "║ ║ ╟─ update commande $logId to $val");
//           $wCMD->event($val);
//           $wCMD->save();
//         }
//     }
//   }
//   public function updateCONFfromArray($data, $conf_array){
//     log::add(__CLASS__, 'debug', "║ ╟───────────── update configuration :".json_encode($data));
//     log::add(__CLASS__, 'debug', "║ ╟───────────── in  :".json_encode($conf_array));
//     foreach($conf_array as $logId => $conf){
//       log::add(__CLASS__, 'debug', "║ ║ ╟─ try find conf $logId (".array_key_exists($logId, $data).")");
//       if(array_key_exists($logId, $data)){
//         $conf_value = weenect::format_output($data[$logId], $conf['type']);
//         log::add(__CLASS__, 'debug', "║ ║ ╟─ update configuration $logId to $conf_value");
//         $this->setConfiguration($logId, $conf_value);
//       }
//     }
//     $this->save();
//   }
//   /*    ----- fonction pour créer les commande à partir des array de définition de la classe 
//      * dont les clé sont les logicalId des commandes
//      * contenant les données name, type et subtype
//  */
//  public function createCMDFromArray($arrayCMD){
//    foreach($arrayCMD as $logId => $setting){
//       $wCMD = $this->getCmd(null, $logId);
//       if (!is_object($wCMD)) {
//         $wCMD = new weenectCmd();
//         $wCMD->setLogicalId($logId);
//         $wCMD->setIsVisible(1);
//         $wCMD->setName(__($setting['name'], __FILE__));
//         log::add(__CLASS__, 'debug', "╟─ creation de la commande : ".$setting['name']." - $logId  de type : ".$setting['type'].'|'.$setting['subtype']);
//       }
//       $wCMD->setType($setting['type']);
//       $wCMD->setSubType($setting['subtype']);
//       $wCMD->setEqLogic_id($this->getId());
//       $wCMD->save();
//     }
//  }



  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*     * **********************Getteur Setteur*************************** */
}

class weenectCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
    log::add('weenect','debug', "╔═══════════════════════ execute CMD : ".$this->getId()." | ".$this->getHumanName().", logical id : ".$this->getLogicalId() ."  options : ".print_r($_options));
    log::add('weenect','debug', '╠════ Eq logic '.$this->getEqLogic()->getHumanName());
    switch($this->getLogicalId()){
      case 'refresh':
         weenect::update_position();
        break;
      default:
        log::add('weenect','debug', '╠════ Default call');

   } 
   log::add('weenect','debug', "╚═════════════════════════════════════════ END execute CMD ");
  }

  /*     * **********************Getteur Setteur*************************** */
}
