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

class weenect extends eqLogic {

  // définition des commandes
  const CMD_common = array(
      // => en configuration 'tracker_id'=>array('name'=>'Tracker Id','type'=>'info', 'subtype'=>'string'),
      'type'=>array('name'=>'Utilisateur dern commit','type'=>'info', 'subtype'=>'string'),
      'date_tracker'=>array('name'=>'Utilisateur dern commit','type'=>'info', 'subtype'=>'string'),
      
      // metrics 
      'battery'=>array('name'=>'Battery','type'=>'info', 'subtype'=>'numeric'),
      'gsm'=>array('name'=>'GSM','type'=>'info', 'subtype'=>'numeric'),
      'signal_strength_percent'=>array('name'=>'Signal','type'=>'info', 'subtype'=>'numeric'),
      
      // position
      'latitude'=>array('name'=>'Latitude','type'=>'info', 'subtype'=>'numeric'),
      'longitude'=>array('name'=>'Longitude','type'=>'info', 'subtype'=>'numeric'),
      'radius'=>array('name'=>'Radius','type'=>'info', 'subtype'=>'numeric'),
      
      // status
      'is_online'=>array('name'=>'Online','type'=>'info', 'subtype'=>'string'),
      'valid_signal'=>array('name'=>'Valid Signal','type'=>'info', 'subtype'=>'string'),  
      'is_in_deep_sleep'=>array('name'=>'Deepsleep','type'=>'info', 'subtype'=>'string')

  );


  // Update all eqLogic from data
  public static function update_all(){

    $token = config::byKey('token', __CLASS__);
    log::add(__CLASS__, 'debug', '║ ╔════════════════════ START Update Data');
    
    if(!$token){
      log::add(__CLASS__, 'debug', '║ ╟─── Token  Not Found / start token retrieve');
      $token = weenect::update_token();
      if(!$token)return False;
    }
    log::add(__CLASS__, 'debug', '║ ╟─── Token  :'.$token);
    $datas = W_API::get_tracker_position($token);
    if(!W_API::test_status($datas['status'])){
      log::add(__CLASS__, 'debug', '║ ╟─── curl status error : '.$datas['status'].' => try update token');
      $token = weenect::update_token();
      if(!$token)return False;
      $datas = W_API::get_tracker_position($token);
      if(!W_API::test_status($datas['status']))return False;
    }
    foreach($datas['result'] as $tracker){
      weenect::update_tracker($tracker);
    }
    

    return True;
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

  // mise à jour du tracker en fonction de son id
  // si auncun trouver en créé un nouveau
  public static function update_tracker($datas){
    $tId = $datas['tracker_id'];
    log::add('weenect', 'debug', "║ ╠════════════════ update tracker  : ".$tId);
    foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
      // $idTracker = $eqLogic->getCmd(null, 'tracker_id');
      $idTracker = $eqLogic->getConfiguration('tracker_id');
      if($idTracker==$tId){
        $eqLogic->updateCMDfromArray($datas['position']);
        log::add('weenect', 'debug', "║ ╠════════════════ End update tracker ");
        return true;
      }
    }
    log::add(__CLASS__, 'debug', '║ ╟─── No Tracker found, create a new one ');
    $eqLogic=weenect::create_new_tracker($idTracker);
  }


  public static function update_tracker($idTracker){
    log::add(__CLASS__, 'debug', "║ ╟─── create a new tracker $idTracker");
    $eqLogic = eqLogic::create();
    $eqLogic->setEqType(__CLASS__); 
    $eqLogic->setConfiguration('tracker_id', $idTracker);

    // Enregistrez le nouvel eqLogic
    $eqLogic->save();
    return $eqLogic;

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
    $this->createCMDFromArray(weenect::CMD_common);
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*    ----- fonction pour mettre à jour les valeurs à partir d'un array 
      * dont les clé sont les logicalId des commandes (cf les array de classe)
      * contenant la clé status => 200 Ok si on doit remplir les données
  */
  public function updateCMDfromArray($data){
    foreach($data as $logId => $val){
        if($logId=='status')continue;
        $wCMD = $this->getCmd(null, $logId);
        if (is_object($wCMD)) {
          log::add(__CLASS__, 'debug', "║ ║ ╟─ update commande $logId to $val");
          $wCMD->event($val);
          $wCMD->save();
        }
    }
  }
    
    
  /*    ----- fonction pour créer les commande à partir des array de définition de la classe 
     * dont les clé sont les logicalId des commandes
     * contenant les données name, type et subtype
 */
 public function createCMDFromArray($arrayCMD){
   foreach($arrayCMD as $logId => $setting){
      $wCMD = $this->getCmd(null, $logId);
      if (!is_object($wCMD)) {
        $wCMD = new weenectCmd();
        $wCMD->setLogicalId($logId);
        $wCMD->setIsVisible(1);
        $wCMD->setName(__($setting['name'], __FILE__));
        log::add(__CLASS__, 'debug', "╟─ creation de la commande : ".$setting['name']." - $logId  de type : ".$setting['type'].'|'.$setting['subtype']);
      }
      $wCMD->setType($setting['type']);
      $wCMD->setSubType($setting['subtype']);
      $wCMD->setEqLogic_id($this->getId());
      $wCMD->save();
    }
 }



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
  }

  /*     * **********************Getteur Setteur*************************** */
}
