<?php

/**
 * Gestione del file di configurazione
 *
 * @author marcoma
 */

class APIConfig {
    
    const CONFIG_FILE = "ConfigApi.xml";
    
    /**
     * Stringa di configurazione
     * @var type 
     */
    private $_config = array();
    
    /**
     * Array dei metodi
     * @var type 
     */
    private $_methods = array();

    public function __construct() {
        //$this->_config = new SimpleXmlElement(file_get_contents(self::CONFIG_FILE, true));
        $this->_config = new DOMDocument;
        $this->_config->preserveWhiteSpace = false;
        $this->_config->loadXML(file_get_contents(self::CONFIG_FILE,true));
        
    }
    
    public function getConfig($node = "") {
        if ($node == "") {
            return $this->_config;
        } else {
            foreach ($this->_config->getElementsByTagName($node) as $nodeElement) {
                return $nodeElement->nodeValue;
            }           
        }
    }
        
    
    public function getResourceList($uniqueResource = null, $uniqueMethod = null) {
        
        $methods = $this->_config->getElementsByTagName('methods');
        $resourceList = array();
        $return_methods = null;
        $path = $this->getConfig("classfolder");
        
        for ($i = 0; $i < $methods->length; $i ++) {
  
            $iMethod  = $methods->item($i);
            $iModel = $iMethod->parentNode;            

            if (is_null($uniqueResource) || $iModel->parentNode->nodeName == $uniqueResource) {                
                $resourceName = $iModel->parentNode->nodeName;
                $iAttribute = $iModel->attributes;
                $className = "";
                $fileName = "";
                $frameWorkClass = "";
                for ($ia = 0; $ia < $iAttribute->length; $ia++) {
                    
                    if (strtolower($iAttribute->item($ia)->name) == "class") {
                        $className  = $iAttribute->item($ia)->value;
                    }
                    
                    if (strtolower($iAttribute->item($ia)->name) == "file") {
                        $fileName  = $iAttribute->item($ia)->value;
                    }
                    
                    if (strtolower($iAttribute->item($ia)->name) == "frameworkclass") {
                        $frameWorkClass = $iAttribute->item($ia)->value;
                    }
                    
                }
                if ($className == "" || $fileName == "" || !file_exists($path . $fileName)) {
                    continue;
                }
                //Recuper i metodi per la classe
                for ($im = 0; $im < $iMethod->childNodes->length; $im++) {
                    $method = $iMethod->childNodes->item($im);
                    if (!is_null($uniqueMethod) && ucfirst($method->nodeName) != $uniqueMethod) {
                        continue;
                    }
                    $resourceCompleteName = ucfirst($resourceName) . ucfirst($method->nodeName);
                    $methodName = $method->nodeName;
                    $methodTitle = "";                    
                    for ($imd = 0; $imd < $method->childNodes->length; $imd++ ) {
                        
                        if ($method->childNodes->item($imd)->nodeName == "title") {
                            $methodTitle = $method->childNodes->item($imd)->nodeValue;
                        }
                        if ($method->childNodes->item($imd)->nodeName == "method") {
                            $methodName = $method->childNodes->item($imd)->nodeValue;
                        }                                                
                        
                        //Aggirono la struttura del metodo
                        $return_methods[$resourceCompleteName] = array("name"   => $resourceCompleteName,
                                                                       "class"  => $className,
                                                                       "file"   => $path . $fileName,
                                                                       "method" => $methodName,
                                                                       "title"  => $methodTitle,
                                                                       "fclass" => $frameWorkClass);
                    }
                    
                }               
            }
        }
        if ($uniqueMethod!= "" && $uniqueResource != "") {
            foreach ($return_methods as $ret) {
                return $ret;           
            } 
        } else {
            return $return_methods;
        }
    }        
   
    /**
     * Lista dei methodi di errore dal file di configurazione
     * @return array
     */
    public function GetFaultCode() {
            return array(); 
    }
}

?>
