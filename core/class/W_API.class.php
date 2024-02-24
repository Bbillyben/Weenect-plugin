
<?php 
/* --------- Classe d'appel à l'API de weenect
* necessité d'un token retournée dans la phase d'authentification
* ajouter au header : Authorisation: JWT Token
*/

require_once __DIR__  . '/../../../../core/php/core.inc.php';

class W_API {
    // URLS :
    PRIVATE CONST LOGIN_URL = 'https://apiv4.weenect.com/v4/user/login'; // login 
    PRIVATE CONST TRACKER_POSITION_URL = 'https://apiv4.weenect.com/v4/mytracker-userspace/position'; // les position de tous les tracker
    PRIVATE CONST TRACKER_DATA_URL = 'https://apiv4.weenect.com/v4/mytracker-userspace'; // les information sur les tracker (date, zones, ...)

    /* --------- Récupération du Token par login du user
    * retourne : String Token
    * username : identifiant weenect
    * password : password weenect
    */
    public static function get_token($username, $password){
        log::add('weenect', 'debug', '║ ╟───  Request for new token');
        $data=array(
            'username'=>$username,
            'password'=>$password
        );
        $dataCmd=W_API::computeCMD(W_API::LOGIN_URL, null, json_encode($data), "POST");
        W_API::printData($dataCmd);
        if(W_API::test_status($dataCmd['status'])){
            $res=$dataCmd['result'];
            return $res['access_token'];
        }
        return Null;        
    }
    /* --------- Récupération des position des tracker
    * retourne : String Token
    * $token : token d'authentification
    */
    public static function get_tracker_position($token){
        log::add('weenect', 'debug', '║ ╟───  Request for trackers data');
        $dataCmd=W_API::computeCMD(W_API::TRACKER_POSITION_URL, $token);
        // W_API::printData($dataCmd);
        return $dataCmd;
    }
    /* --------- Récupération des données des tracker
    * notamment les noms, dates et zones attaché aux trackers
    * retourne : un array multidimensionnel avec les données
    * $token : token d'authentification
    */
    public static function get_account_datas($token){
        log::add('weenect', 'debug', '║ ╟───  Request account data');
        $dataCmd=W_API::computeCMD(W_API::TRACKER_DATA_URL, $token);
        // W_API::printData($dataCmd);
        return $dataCmd;
    }
    /* --------- Utilitaire d'execution des commande API
    * retourne un array avec status, header et result de la réponse
    * $cmd : la commande a executer (URL)
    * $token : token d'authentification
    * $dataPost -optionnel : les données POST a attacher à la requete
    * $method - optionnel : methode de la requete (par défaut GET)
    */
   public static function computeCMD($cmd,$token, $dataPost=Null, $method="GET"){
        $data = array();
        $data['status']=0;
        $headers = W_API::getBaseHeader($token);
        $headersA=[];
        // log::add('weenect', 'debug', '║ ║ ╟─── Commande :'.$cmd);
        // log::add('weenect', 'debug', '║ ║ ╟─── Headers requete :'.implode(" | ",$headers));
        // log::add('weenect', 'debug', '║ ║ ╟─── data :'.$dataPost);

        $ch = curl_init();
        W_API::configureBasecURL($ch, $cmd, $headers, $headersA, $method);
        if($dataPost)curl_setopt($ch, CURLOPT_POSTFIELDS, $dataPost);
        /* execution de la requete */
        $result = curl_exec($ch);
        // log::add('weenect', 'debug', '║ ║ ╟─ headers Answer :'.json_encode($headersA));
        
        
        /* gestion de l'erreur */
        if (curl_errno($ch)) {
            log::add('weenect', 'error', 'Error:'.curl_errno($ch)." / ".curl_error($ch));
            // log::add('weenect', 'debug', '║ ║ ╟─ Error:'.curl_errno($ch)." / ".curl_error($ch));
        }

        $data['status']=curl_getinfo($ch)['http_code'];
        $data['header']=$headersA;
        $data['result']=json_decode($result,true);

        return $data;

    }  
    /* --------- Utilitaire pour imprimer dans le log en debug un array 1 dimension
    * retourne un array avec status, header et result de la réponse
    * $data : array à logger
    */
    public static function printData($data){
        foreach($data as $k=>$v){
        log::add('weenect', 'debug', "║ ║ ╟─ $k : ".json_encode($v));
        }
    }
   /* --------- Utilitaire pour créer le header de base 
    * retourne un array avec status, header et result de la réponse
    * $token : le token
    */
   public static function getBaseHeader($token){
        $headers = array();
        $headers[] ="Content-Type:application/json";

        if($token<>''){
        $headers[] ='Authorization: JWT '.$token;
        }
        return $headers;
    }
    /* --------- Utilitaire pour configurer le cUrl de base  
    * $ch : curl de base 
    * $url : url de la commande API
    * $headers : header de la requete
    * $headersA : le header de la réponse (rempli par le retour de la requete)
    * $method : methode de l'appel (par défaut GET)
    */
    public static function configureBasecURL($ch, $url, $headers, &$headersA, $method='GET'){
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // this function is called by curl for each header received
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
        function($curl, $header) use (&$headersA)
        {
           $len = strlen($header);            
           $header = explode(':', $header, 2);
           if (count($header) < 2) // ignore invalid headers
              return $len;
  
           $headersA[strtolower(trim($header[0]))][] = trim($header[1]);
  
           return $len;
        }
        );
    }
    /* --------- Utilitaire tester le retour de la réponse  
    * $status_code : le code du status de la réponse
    * return True/False 
    */
    public static function test_status($status_code){
        log::add('weenect','debug','║ ╟══ test status :'.$status_code);
        switch ($status_code) {
            case 200:
                return True;
            case 401:
                log::add('weenect','debug','║ ╟══ #################### ERROR  :'.$status_code);
                return False;
                break;
            default:
                log::add('weenect','debug','║ ╟══ #################### Status code unknown  :'.$status_code);
                return False;
        }

    }
    /* --------- Utilitaire extraire une valeur d'un array  
    * $arr : l'array à fouiller
    * $val : la clé a trouver
    * retourne une valeur à partir d'un array si la clé exiiste sinon false 
    */
   public static function gvfa($arr, $val){
        if(is_array($arr) && array_key_exists($val,$arr)){
        return $arr[$val];
        }else{
        return false;
        }
    }
    /* --------- Utilitaire extraire une valeur d'un array, en profondeur
    * $arr : l'array à fouiller
    * $valArr : un array contenant la suite de clé à trouver
    * retourne une valeur à partir d'un array si la clé exiiste sinon false 
    */
   public static function gvfaKR($arr, $valArr){
    $pointeur=$arr;
    foreach($valArr as $val){
       if(is_array($pointeur) && array_key_exists($val,$pointeur)){
          $pointeur = $pointeur[$val];
       }else{
          return false;
       }

    }
    return $pointeur;
 }

    

}


?>