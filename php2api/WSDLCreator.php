<?php

require_once("XMLCreator.php");
require_once("PHPParser.php");
require_once("APIConfig.php");
require_once("APIModel.php");

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *  
 *
 * @category    
 * @package        
 * @copyright   Copyright (c) 2013 Mps Sistemi (http://www.mps-sistemi.it)
 * @author      MPS Sistemi S.a.s - Marco Mancinelli <marco.mancinelli@mps-sistemi.it>
 *
 */
 

class WSDLCreator {
    
    /**
     * Object for XMLCreator
     *
     * @var XMLCreator
     */
    private $XMLCreator;

    /**
     * Array with internal variable types
     *
     * @var array
     */
    private $xsd = array("string"=>"string", "bool"=>"boolean", "boolean"=>"boolean",
                                             "int"=>"integer", "integer"=>"integer", "double"=>"double", "float"=>"float", "number"=>"float",
                                             "datetime"=>"datetime",
                                             "resource"=>"anyType", "mixed"=>"anyType", "unknown"=>"anyType", "unknown_type"=>"anyType", "anytype"=>"anyType"
                                             );

    /**
     * The WSDL
     *
     * @var string
     */
    private $WSDL;

    /**
     * The WSDL in XMLCreator object
     *
     * @var XMLCreator
     */
    private $WSDLXML;
   
    /**
     * The name of the WSDL
     *
     * @var stirng
     */
    private $name;

    /**
     * The URL of the WSDL
     *
     * @var stirng
     */
    private $url;
    
    /**
     * Array of messages
     *
     * @var array
     */
    private $_messages = array();
    
    /**
     * Array of messages
     *
     * @var array
     */
    private $_operation = array();
    
    /**
     * Array of messages
     *
     * @var array
     */
    private $_binding = array();
    
    /**
     * Array of messages
     *
     * @var array
     */
    private $_complexType = array();
    
    /**
     *
     * @var object Configurazione
     */
    private $_config;      
    
    /*
     * Elenco dell classi complesse instanzite (per non crearle 2 volte)
     */
    private $_complexDataType = array();


    /**
     * Constructor
     *
     * @param string $name
     * @param string $url
     */
    public function __construct() {

            $this->_config = new APIConfig();
            
            $this->name = $this->_config->getConfig("targetNamespace");
            $this->url = $this->_config->getConfig("url");

            $this->WSDLXML = new XMLCreator("definitions");
            $this->WSDLXML->setAttribute("xmlns:typens", "urn:".$this->name);
            $this->WSDLXML->setAttribute("xmlns:xsd", "http://www.w3.org/2001/XMLSchema");
            $this->WSDLXML->setAttribute("xmlns:soap", "http://schemas.xmlsoap.org/wsdl/soap/");
            $this->WSDLXML->setAttribute("xmlns:soap12", "http://schemas.xmlsoap.org/wsdl/soap12/");
            $this->WSDLXML->setAttribute("xmlns:soapenc", "http://schemas.xmlsoap.org/soap/encoding/");
            $this->WSDLXML->setAttribute("xmlns:wsdl", "http://schemas.xmlsoap.org/wsdl/");
            $this->WSDLXML->setAttribute("xmlns", "http://schemas.xmlsoap.org/wsdl/");
            $this->WSDLXML->setAttribute("xmlns:http", "http://schemas.xmlsoap.org/http/");
            $this->WSDLXML->setAttribute("name", $this->name);
            $this->WSDLXML->setAttribute("targetNamespace", "urn:".$this->name);
            
            //$this->_createSecurityObject();
            
    }
    
    private function _createMessageByDirection($opName, $methodName, $inOut = false) {
        $direction = "Request";
        if ($inOut == true) {
            $direction = "Response";
        }
        $message = new XMLCreator("wsdl:message");
        $message->setAttribute("name", $opName . $direction);
        $messagePart = new XMLCreator("wsdl:part");
        $messagePart->setAttribute("name", "parameters");
        $messagePart->setAttribute("element", "typens:" . ucfirst($methodName) . $direction . "Params");
        $message->addChild($messagePart);
        return $message;
    }
    
    private function _createMessage($opName, $methodName) {
        $this->_messages[] = $this->_createMessageByDirection($opName, $methodName);
        $this->_messages[] = $this->_createMessageByDirection($opName, $methodName, true);
    }
    
    private function _createOperation($opName, $opTitle = "") {
        
        $operation = new XMLCreator("wsdl:operation");
        $operation->setAttribute("name", $opName);
        if ($opTitle != "") {
            $operationTitle = new XMLCreator("wsdl:documentation");
            $operationTitle->setData($opTitle);
            $operation->addChild($operationTitle);            
        }
        
        $operationInput = new XMLCreator("wsdl:input");
        $operationInput->setAttribute("message", "typens:" . $opName . "Request");
        $operationOutput = new XMLCreator("wsdl:output");
        $operationOutput->setAttribute("message", "typens:" . $opName . "Response");
        
        $operation->addChild($operationInput);
        $operation->addChild($operationOutput);
        
        $this->_operation[] =  $operation;
        
    }
    
    private function _createBinding($opName) {
        
        $binding = new XMLCreator("wsdl:operation ");
        $binding->setAttribute("name", $opName);
        
        $bindingSoap = new XMLCreator("soap:operation");
        $bindingSoap->setAttribute("soapAction", "");
        $binding->addChild($bindingSoap);
        
        $bindingInput = new XMLCreator("wsdl:input");
        $bindingSoap = new XMLCreator("soap:body");
        $bindingSoap->setAttribute("use", "literal");
        $bindingInput->addChild($bindingSoap);
        
        $bindingOutput = new XMLCreator("wsdl:output");
        $bindingOutput->addChild($bindingSoap);
        
        $binding->addChild($bindingInput);
        $binding->addChild($bindingOutput);
                       
        $this->_binding[] = $binding;
        
    } 
    
    private function _createTypeEn($res) {
        
        $parser = new PHPParser($res["class"], $res["file"], $this->_config->getConfig("framework"), $res["fclass"]);
        $params = $parser->getMethodParams($res["method"]);
        
        //parametri di input
        $element = new XMLCreator("xsd:element");
        $element->setAttribute("name", ucfirst($res["method"]) . "RequestParams");

        $complexType = new XMLCreator("xsd:complexType");
        $sequence = new XMLCreator("xsd:sequence");
        
        if (strtolower($res["method"]) != "login" && strtolower($res["method"]) != "logout") {
            $simpleElement = new XMLCreator("xsd:element");
            $simpleElement->setAttribute("minOccurs", "1");
            $simpleElement->setAttribute("maxOccurs", "1");
            $simpleElement->setAttribute("name", "sessionId");
            $simpleElement->setAttribute("type", "xsd:string");                        
            $sequence->addChild($simpleElement);
        }
        if ($params !== false) {
            foreach ($params as $name => $param) {                                

                if ($name != "return_value") {
                    
                    $simpleElement = new XMLCreator("xsd:element");
                    $simpleElement->setAttribute("minOccurs", ($param["array"])?"0":"1");
                    $simpleElement->setAttribute("maxOccurs", ($param["array"])?"unbounded":"1");
                    $simpleElement->setAttribute("name", $name);

                    if (in_array($param["type"], SimpleType::$list)) {
                        $simpleElement->setAttribute("type", "xsd:" . $param["type"]);                                    
                    } else {
                        $this->_createComplexDataType($param["type"]);
                        $simpleElement->setAttribute("type", "typens:" . $param["type"] . "Entity");   
                    }
                    $sequence->addChild($simpleElement);
                }
            }

            $complexType->addChild($sequence);
        }
        
        $element->addChild($complexType);
        
        $this->_complexType[] = $element;
        
        //parametri di output
        $element = new XMLCreator("xsd:element");
        $element->setAttribute("name", ucfirst($res["method"]) . "ResponseParams");

        $complexType = new XMLCreator("xsd:complexType");
        $sequence = new XMLCreator("xsd:sequence");
        $simpleElement = new XMLCreator("xsd:element");
        
        if ($params !== false) {
            $param = $params["return_value"];             
        } else {
            $param = PHPParser::$defParam;
        }

        $simpleElement->setAttribute("minOccurs", ($param["array"])?"0":"1");
        $simpleElement->setAttribute("maxOccurs", ($param["array"])?"unbounded":"1");
        $simpleElement->setAttribute("name", "result");
        if (in_array($param["type"], SimpleType::$list)) {
            $simpleElement->setAttribute("type", "xsd:" . $param["type"]);                                    
        } else {
            $this->_createComplexDataType($param["type"]);            
            $simpleElement->setAttribute("type", "typens:" . $param["type"] . "Entity");   
        }           
        $sequence->addChild($simpleElement);
        $complexType->addChild($sequence);
        $element->addChild($complexType);
        
        $this->_complexType[] = $element;     
    }
    
        
    
    private function _createComplexDataType($type) {
        
        if (!array_key_exists($type, $this->_complexDataType)) {
            $parser = new PHPParser($type);        

            $complexType = new XMLCreator("xsd:complexType");
            $complexType->setAttribute("name", $type ."Entity");
            $sequence = new XMLCreator("xsd:sequence");

            foreach ($parser->getPublicProperties() as $name => $param) {

                $element = new XMLCreator("xsd:element");
                $element->setAttribute("name", $name);
                //PEr ora gestisco solo un livello di complessità!!!
                $element->setAttribute("type", "xsd:" . $param["type"]);
                if ($param["array"] === true) {
                    $element->setAttribute("minOccurs", "0");
                    $element->setAttribute("maxOccurs", "unbounded");
                }
                if ($param["optional"] === true ) {
                    $element->setAttribute("nillable","true");
                }
                $sequence->addChild($element);

            }

            $complexType->addChild($sequence);

            $this->_complexDataType[$type] = $complexType;
        }
                    
    }
    
//    private function __createComplexDataTypeArray($type) {
//        
//        if (!array_key_exists($type, $this->_complexDataType)) {
//            $complexType = new XMLCreator("xsd:complexType");
//        }
//        
///*    <xsd:complexType name="ArrayOfApiMethods">
//        <xsd:sequence>
//          <xsd:element minOccurs="0" maxOccurs="unbounded" name="complexObjectArray" type="typens:apiMethodEntity" />
//        </xsd:sequence>
//      </xsd:complexType> */
//
//        
//    }
      
    public function createWSDL() {
        
        foreach ($this->_config->getResourceList() as $name => $resource) {
                 
            //Creo il messaggio
            $this->_createMessage($name, $resource["method"]);
            
            //Creo il Type
            $this->_createTypeEn($resource);
            
            //Creo l'operation
            $this->_createOperation($name, $resource["title"]);
            
            //Creao il Binding 
            $this->_createBinding($name);
        }
        
        //Aggiungo i type
        $types = new XMLCreator("wsdl:types");
        $schema = new XMLCreator("xsd:schema");
        $schema->setAttribute("targetNamespace", "urn:".$this->name);
        
        foreach ($this->_complexDataType as $ctd) {
            $schema->addChild($ctd);
        }
        
        foreach ($this->_complexType as $ct) {
            $schema->addChild($ct);
        }                
        
        $types->addChild($schema);
        
        $this->WSDLXML->addChild($types);
        
        //Aggiungo i messaggi
        foreach ($this->_messages as $mess) {
            $this->WSDLXML->addChild($mess);
        }
        
        //Aggiungo Port type ed operation
        $portType = new XMLCreator("wsdl:portType");
        $portType->setAttribute("name", $this->_config->getConfig("entry_point"));
        
        foreach ($this->_operation as $op) {
            $portType->addChild($op);
        }
        
         $this->WSDLXML->addChild($portType);
        
        $binding = new XMLCreator("wsdl:binding");
        $binding->setAttribute("name", $this->_config->getConfig("binding_name"));
        $binding->setAttribute("type", "typens:" . $this->_config->getConfig("entry_point"));
        $transport = new XMLCreator("soap:binding");
        $transport->setAttribute("transport", "http://schemas.xmlsoap.org/soap/http");
        $binding->addChild($transport);
        
        foreach ($this->_binding as $bin) {
            $binding->addChild($bin);
        }       
        
        $this->WSDLXML->addChild($binding);
        
        $service = new XMLCreator("wsdl:service");
        $service->setAttribute("name", $this->_config->getConfig("service_name"));
        $portWSDL = new XMLCreator("wsdl:port");
        $portWSDL->setAttribute("name", $this->_config->getConfig("port_name"));
        $portWSDL->setAttribute("binding", "typens:" . $this->_config->getConfig("binding_name"));
        $address = new XMLCreator("soap:address");
        $address->setAttribute("location", $this->_config->getConfig("protocol") . '://' . $_SERVER['HTTP_HOST'] . '/' . $this->_config->getConfig("url"));
        
        $portWSDL->addChild($address);
        $service->addChild($portWSDL);
        
        $this->WSDLXML->addChild($service);        
        
        $this->WSDL = $this->WSDLXML->getXML();
        return $this->WSDL;
    }

    /**
     * Get the WSDL
     *
     * @return string
     */
    public function getWSDL () {
            return $this->WSDL;
    }

    /**
     * Print the WSDL
     *
     * @param bool $headers
     */
    public function printWSDL ($headers = false) {
            if ($headers === true) {
                    header("Content-Type: application/xml");
                    print $this->WSDL;
                    exit;
            } else {
                    print $this->WSDL;
            }
    }


    /**
     * Save the WSDL to a file
     *
     * @param string $targetFile
     * @param boolean $overwrite
     */
    public function saveWSDL ($targetFile, $overwrite = true) {

            if (file_exists($targetFile) && $overwrite == false) {
                    $this->downloadWSDL();
            } elseif ($targetFile) {
                    $fh = fopen($targetFile, "w+");
                    fwrite($fh, $this->getWSDL());
                    fclose($fh);
            }
    }	

    /**
     * Download the WSDL
     *
     */
    public function downloadWSDL () {
            session_cache_limiter();
            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=".$this->name.".wsdl");
            header("Accept-Ranges: bytes");
            header("Content-Length: " . strlen($this->WSDL));
            $this->printWSDL();
            die();
    }
    
    /*
     * Deprecato
     */
    public function _createSecurityObject () {
        
        $this->_messages[] = $this->_createMessage("AuthLogin", "login") ;
        $this->_messages[] = $this->_createMessage("AuthLogout", "logout") ;
        
        $this->_operation[] = $this->_createOperation("AuthLogin", "Autenticazione per ottenere la chiave (SessionID)");
        $this->_operation[] = $this->_createOperation("AuthLogout");
        
        $this->_binding[] = $this->_createBinding("AuthLogin");
        $this->_binding[] = $this->_createBinding("AuthLogout");
        
        //simulo l'array delle risorse per il calcolo dei type
        $array = array("AuthLogin" => array("name"   => "AuthLogin",
                                            "class"  => "Auth",
                                            "file"   => $this->_config->getConfig("classfolder") . "api/Auth.php",
                                            "method" => "login",
                                            "title"  => "",
                                            "fclass" => "api/Auth"),
                       "AuthLogout"=> array("name"   => "AuthLogout",
                                            "class"  => "Auth",
                                            "file"   => $this->_config->getConfig("classfolder") . "api/Auth.php",
                                            "method" => "logout",
                                            "title"  => "",
                                            "fclass" => "api/Auth"),);
        
        foreach ($array as $a) {
            $this->_createTypeEn($a);
        }
        
            
    }
    
    
}


?>