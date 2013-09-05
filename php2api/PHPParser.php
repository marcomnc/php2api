<?php

/**
 * Description of PHPParser
 *
 * @author marcoma
 */
class PHPParser {
    
    private $_class = null;
    private $_instanceClass = null;
    private $_file = null; 
    private $_instance = null;  
    
    private $_framework;
    
    public static $defParam = array("optional" => false,   
                                    "type" => "string", //Default
                                    "array"=> false);
    
    private function _createInstance() {
        if ($this->_instance == null) {
            switch ($this->_framework."") {
                case "CI":
                    if ($this->_instanceClass != "") {
                        $CI = & get_instance();
                        $CI->load->model($this->_instanceClass); 
                        $class = $this->_class;
                        $this->_instance = $CI->{$class};                    
                    }
                    break;
                
                default:
                    try {
                        if (!is_null($this->_file)) {
                            require_once $this->_file;
                        }
                        $this->_instance = new $this->_class;
                    } catch (Exception $e) { 
                        //throw new Exception;
                    }
                    break;
            }
        }
    }

    public function __construct($class = null, $file = null, $framework = null, $frameWorkClass = null) {
        $this->_class = $class;
        $this->_file = $file;
        $this->_framework = $framework;
        $this->_instanceClass = $frameWorkClass;
        $this->_createInstance();
    }
        
    /**
     * Lista dei parmetri di un methodo della classse
     * @param type $method
     * @return array|boolean array("optional" => false,   
                                    "type" => "string", //Default
                                    "array"=> false);
     */
    public function getMethodParams($method) {
        
        $returnParams = array();
        if ($this->_instance == null) {
            return false;
        }
        
        $ref = new ReflectionMethod ($this->_instance, $method);
        if (is_null($ref) || !$ref->isPublic() || !$ref->isUserDefined()) {
            return false;
        }
        
        $comment = $this->_getParamType($ref->getDocComment());  
   
        foreach ($ref->getParameters() as $param) {
            $returnParams[$param->name] = self::$defParam;
            if (isset($comment["params"]['$'.$param->name]) && $comment["params"]['$'.$param->name] != "") {
                $returnParams[$param->name]["type"] = str_replace("[]", "", $comment["params"]['$'.$param->name]);  
                if (strpos($comment["params"]['$'.$param->name], "[]") !== false) {
                    $returnParams[$param->name]["array"] = true;
                }
            }
        }
        
        $returnParams["return_value"] = self::$defParam;
        
        if(isset($comment["return"]) && $comment["return"] != "") {
            $returnParams["return_value"]["type"] = str_replace("[]", "", $comment["return"]);
            if (strpos($comment["return"],"[]") !== false) {
                    $returnParams["return_value"]["array"] = true;
                }
            
        }
        return $returnParams;
     }
    
    private function _getParamType($comment) {
        
        if (strpos($comment, "/*") === 0 && strripos($comment, "*/") === strlen($comment)-2) {
                $lines = preg_split("(\\n\\r|\\r\\n\\|\\r|\\n)", $comment);
                $description = "";
                $returntype = "";
                $params = array();
                while (next($lines)) {
                        $line = trim(current($lines));
                        $line = trim(substr($line, strpos($line, "* ")+2));
                        if (isset($line[0]) && $line[0] == "@") {
                                $parts = explode(" ", $line);
                                if ($parts[0] == "@return") {
                                        $returntype = $parts[1];
                                } elseif ($parts[0] == "@param") {
                                        $params[$parts[2]] = $parts[1];
                                } elseif ($parts[0] == "@var") {
                                        $params['type'] = $parts[1];
                                }
                        } else {
                                $description .= "\n".trim($line);
                        }
                }

                $comment = array("description"=>$description, "params"=>$params, "return"=>$returntype);
                return $comment;
        } else {
                return "";
        }
        
    }
    
    public function getPublicProperties() {
        
        $reflect = new ReflectionClass($this->_instance);

        foreach ($reflect->getProperties() as $property) {
            $comment = $this->_getParamType($property->getDocComment());  

            $returnParams[$property->name] = self::$defParam;
            if (isset($comment["params"]["type"]) && $comment["params"]["type"] != "") {
                $returnParams[$property->name]["type"] = str_replace("[]", "", $comment["params"]["type"]);  
                
                if (strpos($comment["params"]["type"], "[]") !== false) {
                    $returnParams[$property->name]["array"] = true;
                }
            }            
            if (is_null($property->getValue($this->_instance))) {
                $returnParams[$property->name]["optional"] = true;
            }
        }
        
       
        return $returnParams;
    }     
}

?>
