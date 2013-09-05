<?php

require_once 'APIConfig.php';

class APISession {
    
    private $_CI = null;
    
    public function __construct() {
        $this->_CI = & get_instance();        
        $this->_CI->load->library("authenticate", true);        
    }
    
    /**
     * Attenzione su trekking in questo momento l'expired è gestito dai cookie
     * 
     * @param type $sessionId
     * @return boolean
     */
    public function isValidSession($sessionId) {
        
        if ($this->_CI->authenticate->isLogged($sessionId)) {
            if ($this->_CI->authenticate->service) {
                return true;
            }
        }
        return false;
    }
    
}

/**
 * Session manager genrico su file
 *
 * @author marcoma
 */
//class APISession {
//    
//    private static $_config = null;
//    private static $_sessionPath = null;
//
//    public function __construct() {
//        self::$_config = new APIConfig();
//        self::$_config->getConfig("sessionFoler");
//        
//    }
//    
//    static function isValidSession($sessionId) {
//        $sessionFile = self::$_sessionPath . "$sessionId.mss";
//        
//        $ret = false;
//        
//        if (file_exists($sessionFile)) {
//            
//            $sessionInfo = unserialize(file_get_contents($sessionFile));
//            
//            $now = new DateTime("now");
//            $diff = $sessionInfo["date"]->diff($now);
//            if ($diff->i < 30  && $sessionInfo["ip"] ==  $_SERVER['REMOTE_ADDR']) {
//                $sessionInfo["date"] = $now;
//                
//                $ret = true;
//                file_put_contents($sessionFile, $sessionInfo);
//            }
//            
//        }
//        return $ret;
//    }
//    
//    static function createSession($sessionId) {
//        
//        $sessionFile = self::$_sessionPath . "$sessionId.mss";
//        $sessionInfo = array("date" => new Date("now"),
//                             "ip"   => $_SERVER["REMOTE_ADDR"]);
//        
//        file_put_contents($sessionFile, $sessionInfo);
//        
//    }
//    
//    /**
//     * @todo Cancello i file di sessione
//     * @param type $sessionId
//     */
//    static function fulshSession($sessionId = null) {
//        
//    }
//    
//}

?>
