
<?php 

require_once __DIR__  . '/../../../../core/php/core.inc.php';

class W_API {
    PRIVATE CONST LOGIN_URL = 'https://apiv4.weenect.com/v4/user/login';
    PRIVATE CONST TRACKER_POSITION_URL = 'https://apiv4.weenect.com/v4/mytracker-userspace/position';
    PRIVATE CONST TRACKER_DATA_URL = 'https://apiv4.weenect.com/v4/mytracker-userspace';

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

    public static function get_tracker_data($token){
        log::add('weenect', 'debug', '║ ╟───  Request for trackers data');
        $dataCmd=W_API::computeCMD(W_API::TRACKER_POSITION_URL, $token);
        W_API::printData($dataCmd);
        return $dataCmd;
    }

    // utilitaire
   // execution de la commande 
   // return un array avec status, header et result de la réponse
   public static function computeCMD($cmd,$token, $dataPost=Null, $method="GET"){
        $data = array();
        $data['status']=0;
        $headers = W_API::getBaseHeader($token);
        $headersA=[];
        log::add('weenect', 'debug', '║ ║ ╟─── Commande :'.$cmd);
        log::add('weenect', 'debug', '║ ║ ╟─── Headers requete :'.implode(" | ",$headers));
        log::add('weenect', 'debug', '║ ║ ╟─── data :'.$dataPost);

        $ch = curl_init();
        W_API::configureBasecURL($ch, $cmd, $headers, $headersA, $method);
        if($dataPost)curl_setopt($ch, CURLOPT_POSTFIELDS, $dataPost);
        /* execution de la requete */
        $result = curl_exec($ch);
        log::add('weenect', 'debug', '║ ║ ╟─ headers Answer :'.json_encode($headersA));
        
        
        /* gestion de l'erreur */
        if (curl_errno($ch)) {
            log::add('weenect', 'error', 'Error:'.curl_errno($ch)." / ".curl_error($ch));
            log::add('weenect', 'debug', '║ ║ ╟─ Error:'.curl_errno($ch)." / ".curl_error($ch));
        }

        $data['status']=curl_getinfo($ch)['http_code'];
        $data['header']=$headersA;
        $data['result']=json_decode($result,true);

        return $data;

    }  
 // permet d'imprimer dans le debug les résultats
 public static function printData($data){
        foreach($data as $k=>$v){
        log::add('weenect', 'debug', "║ ║ ╟─ $k : $v");
        }
    }  
   // pBuild base header
   public static function getBaseHeader($token){
        $headers = array();
        $headers[] ="Content-Type:application/json";

        if($token<>''){
        $headers[] ='Authorization: JWT '.$token;
        }
        return $headers;
    }
    // pour configurer les curl de base
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

    

}


?>