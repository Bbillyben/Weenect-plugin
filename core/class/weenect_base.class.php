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
  
    public static $__CUR_CLASS__ = "weenect";

    function __construct() {
        self::$__CUR_CLASS__='weenect'; //get_class($this);
        // parent::__construct();
    }
    public static function get_cmd_array(){
      return array();
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
        log::add(self::$__CUR_CLASS__, 'debug', "╟─ creation de la commande : '".__($setting['name'], __FILE__)."' - $logId  de type : ".$setting['type'].'|'.$setting['subtype']);
      }
      $wCMD->setType($setting['type']);
      $wCMD->setSubType($setting['subtype']);
      $wCMD->setEqLogic_id($this->getId());
      $wCMD->save();
   }
}
/**    ----- retourne la clé de l'array pour laquelle la valeur de $key == $logId
 * $logId : la valeur à trouver pour
 * $key : la clé
 * $_default : valeur par défaut retourné sir $logId non trouvé sur $key
 */
  public static function getCmdLogId($logId, $_default=false, $key='key',  $arr=false){
    if(!$arr)$arr=static::get_cmd_array();
    $id=false;//array_search($logId, array_column($arr, $key));
    foreach($arr as $k=>$v){
      if(array_key_exists($key, $v) && $v[$key]==$logId){
        $id = $k;
        break;
      }
    }
    if($id===false)return $_default;
    return $id;
  }
  /*    ----- fonction pour mettre à jour les valeurs à partir d'un array 
      * dont les clé sont les logicalId des commandes (cf les array de classe)
      * contenant la clé status => 200 Ok si on doit remplir les données
  */
  public function updateCMDfromArray($data){
    // log::add(self::$__CUR_CLASS__, 'debug', "║ ╟───────────── update commands :".json_encode($data));
    foreach($data as $logId => $val){
      // log::add(self::$__CUR_CLASS__, 'debug', "║ ╟─── commands :".$logId);
        $cmdlogId = self::getCmdLogId($logId, $logId);// array_search($logId, array_column(static::get_cmd_array(), 'key'));
        $wCMD = $this->getCmd(null, $cmdlogId);
        $isOk=true;
        if (is_object($wCMD)) {
          log::add(self::$__CUR_CLASS__, 'debug', "║ ║ ╟─ update commande $logId to $val");
          $isOk = $this->checkAndUpdateCmd($cmdlogId, $val) && $isOk;
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
    // log::add(self::$__CUR_CLASS__, 'debug', "║ ╟───────────── update coordinates ");
    $wCMD = $this->getCmd(null, "coord");
    if (!is_object($wCMD)){
      $this->createCMDFromArray([$_coorDef]);
    }
    $latCMD = $this->getCmd(null, "latitude");
    $longCMD = $this->getCmd(null, "longitude");
    if(is_object($latCMD) && is_object($longCMD)){
      $coord = $latCMD->execCmd() . "," . $longCMD->execCmd();
      $this->checkAndUpdateCmd( "coord", $coord);
    }
  }

  public function buildLocation() {
    $return = array(
      'id' => $this->getLogicalId(),
      'name' => array('value'=>$this->getName()),
      'type'=>  get_class($this)
    );
    $cmd = $this->getCmd(null, 'coord');
    $return['coord'] = static::buildCmd($cmd);

    $cmd = $this->getCmd(null, 'radius');
    $return['radius'] = static::buildCmd($cmd);
    
    return $return;
  }
  public static function buildCmd($cmd){
    if(is_object($cmd)){
      $return = array(
        'id'=>$cmd->getId(),
        'value'=> $cmd->execCmd(),
        'collectDate'=> $cmd->getCollectDate()
      );
    }else{
      $return = array(
        'id' =>null,
        'value' => null,
        'collectDate' => null
      );
    }
    return $return;
  }

}

