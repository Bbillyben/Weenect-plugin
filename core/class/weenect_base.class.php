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

class weenect_base extends eqLogic {
  
    public static $__CUR_CLASS__ = "none";

    function __construct() {
        self::$__CUR_CLASS__='weenect'; //get_class($this);
        // parent::__construct();
    }

  /*    ----- fonction pour mettre à jour les valeurs à partir d'un array 
      * dont les clé sont les logicalId des commandes (cf les array de classe)
      * contenant la clé status => 200 Ok si on doit remplir les données
  */
  public function updateCMDfromArray($data){
    // log::add(self::$__CUR_CLASS__, 'debug', "║ ╟───────────── update commands :".json_encode($data));
    foreach($data as $logId => $val){
      // log::add(self::$__CUR_CLASS__, 'debug', "║ ╟─── commands :".$logId);
        if($logId=='status')continue;
        $wCMD = $this->getCmd(null, $logId);
        $isOk=true;
        if (is_object($wCMD)) {
          // log::add(self::$__CUR_CLASS__, 'debug', "║ ║ ╟─ update commande $logId to $val");
          $wCMD->event($val);
          $wCMD->save();
          // $isOk = $this->checkAndUpdateCmd($wCMD->getLogicalId(),$val) && $isOk;
        }
    }
    return $isOk;
  }
  public function updateCONFfromArray($data, $conf_array){
    // log::add(self::$__CUR_CLASS__, 'debug', "║ ╟───────────── update configuration :".json_encode($data));
    // log::add(self::$__CUR_CLASS__, 'debug', "║ ╟───────────── in  :".json_encode($conf_array));
    foreach($conf_array as $logId => $conf){
      // log::add(self::$__CUR_CLASS__, 'debug', "║ ║ ╟─ try find conf $logId (".array_key_exists($logId, $data).")");
      if(array_key_exists($logId, $data)){
        $conf_value = self::format_output($data[$logId], $conf['type']);
        // log::add(self::$__CUR_CLASS__, 'debug', "║ ║ ╟─ update configuration $logId to $conf_value");
        $this->setConfiguration($logId, $conf_value);
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
        $wCMD = new cmd();
        $wCMD->setLogicalId($logId);
        $wCMD->setIsVisible(1);
        $wCMD->setName(__($setting['name'], __FILE__));
        log::add(self::$__CUR_CLASS__, 'debug', "╟─ creation de la commande : ".$setting['name']." - $logId  de type : ".$setting['type'].'|'.$setting['subtype']);
      }
      $wCMD->setType($setting['type']);
      $wCMD->setSubType($setting['subtype']);
      $wCMD->setEqLogic_id($this->getId());
      $wCMD->save();
    }
 }


   // format output from API to be more readable

   public static function format_output($value, $type='string'){
    switch ($type) {
      case 'string':
        return strval($value);
      case 'date':
          return preg_replace('/^(\d{4}-\d{2}-\d{2}).*$/', '$1', $value);
      case 'time':
          return str_replace(array('T','Z'),array(' ',''),$value);
      default:
          return $value;
    }
  }

// shared by weenect and weenect zone
  public function update_coordinate($_coorDef){
    log::add(self::$__CUR_CLASS__, 'debug', "║ ╟───────────── update coordinates ");
    $wCMD = $this->getCmd(null, "coord");
    if (!is_object($wCMD)){
      $this->createCMDFromArray([$_coorDef]);
    }
    $latCMD = $this->getCmd(null, "latitude");
    $longCMD = $this->getCmd(null, "longitude");
    if(is_object($latCMD) && is_object($longCMD)){
      $coord = $latCMD->execCmd() . "," . $longCMD->execCmd();
      $wCMD->event($coord);
      $wCMD->save();
    }
  }

}

