<?php

require_once 'APIConfig.php';
require_once 'PHPParser.php';
require_once 'APISession.php';

/**
 * Server Soap per la gestione delle richieste
 *
 * @author marcoma
 */
class APIServer {
    
    private $_config = null;
    private $_wsdl = "";
    private $_encodicg = null;
    
    public static function mydbg ($mixed) {
    
        $f = fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . "log.txt", "a");
        fwrite($f, date(DATE_ATOM) ." DEBUG: " . print_r($mixed, true) . "\n");
        fclose($f);

    }
    
    public function __construct() {
        $this->_config = new APIConfig();
        $this->_wsdl = $this->_config->getConfig("protocol") . '://' . $_SERVER['HTTP_HOST'] . '/' . $this->_config->getConfig("url") . "?wsdl=1";        
        $this->_encodig = $this->_config->getConfig("encoding");
                
    }
    
    /**
     * Crea un server SOAP per eseguire le richieste in input
     * @param string indirizzo del WSDL. Se nullo viene cercato nei parametri
     * @param array $options Opzioni del SERVER (Vedi doc php)
     */
    public function createServer($wsdl = null, $options = null) {
        //Opzioni di default
        $options["cache_wsdl"] = false;
        $options["soap_version"] = SOAP_1_2;
        $options["encoding"] = (is_null($this->_encodicg))?"UTF-8":$this->_encodicg;

        
        $server = new SoapServer($this->_wsdl, $options);
        $server->setClass("APIHandle");
        $server->handle();        
        
    }
    
}


class APIHandle {
    
    private $_config = null;

    public function __construct() {
        $this->_config = new APIConfig();
                    
    }        
    
    function __call($name, $arguments) {
        APIServer::mydbg("Richiamo Funzione $name");
        APIServer::mydbg($arguments);
        
        $sessionId = "";
        if (isset($arguments[0]) && property_exists($arguments[0], "sessionId")) {
            $sessionId = $arguments[0]->sessionId;
        }

        if ($name != $this->_config->getConfig("methodLogin") && $sessionId == "") {
            
            return $this->_fault("5", "Invalid session passed");
        } elseif ($sessionId != "") {
            $apiSession = new APISession();
            if (!$apiSession->isValidSession($sessionId)) {
                return $this->_fault("10", "Session expired. Try to relogin");
            }
        }
      
        $classDef = preg_split("/ /", preg_replace  ("/[A-Z]/", " $0", $name, 2), 0,  PREG_SPLIT_NO_EMPTY);        

        $res = $this->_config->getResourceList($classDef[0], $classDef[1]);        
        
        // Per code Igniter
        $CI = & get_instance();
        $CI->load->model($res["fclass"]);

        
        $parser = new PHPParser($res["class"], $res["file"], $this->_config->getConfig("framework"), $res["fclass"]);

        $classDef[2] = array();
        foreach ($parser->getMethodParams(lcfirst($classDef[1])) as $name => $opt) {   
            if ($name != "return_value") {
                if (property_exists($arguments[0], $name)) {
                    $classDef[2][$name] = $arguments[0]->$name;
                } else {
                    $classDef[2][$name] = null;
                }
            } else {
                $return = $opt;
            }
        }
        
        $class = get_class($CI->$res["class"]);

        /*try {
            $return["value"] = call_user_func_array(array($class,lcfirst($classDef[1])), $classDef[2] ); 
            return array("result" => $return["value"]);

        } catch (Exception $e) {
            //@todo Controllo dei codici di errore
            return $this->_fault($e->getCode(), $e->getMessage());
        }*/
        APIServer::mydbg("Eseguo la funzione ");
        APIServer::mydbg($classDef);
	try {
    	    $return["value"] = call_user_func_array(array($class,lcfirst($classDef[1])), $classDef[2] );
            APIServer::mydbg("Return ");
            APIServer::mydbg($return);
	    return array("result" => $return["value"]);
        } catch (Exception $e) {
	    APIServer::mydbg("Error ");
            APIServer::mydbg($e);
            //@todo Controllo dei codici di errore
            return $this->_fault($e->getCode(), $e->getMessage());
        }

    }
    
    
    /**
     * Crea l'eccezzione da restituire
     * @param type $code
     * @param type $errorDescription
     * @return \SoapFault
     */
    private function _fault($code, $errorDescription = "Generic Error") {
        
        $faultCode = $this->_config->GetFaultCode();
        
        if (isset($faultCode[$code])) {
            $errorDescription = $faultCode[$code];
        }
        
        return new SoapFault("$code", $errorDescription);
        
    }
    
}

?>
