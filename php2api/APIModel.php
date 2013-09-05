<?php

/**
 * Classi dati per l'interscambio
 * Se si utilizzano classi dati non presenti in questo file l'importanti è che siano 
 * accessibili dal server e da WSDLCreator
 *
 * @author marcoma
 */

class SimpleType {

    public static $list = array("integer", "string", "boolean", "ArrayOfString", "ArrayOfInt");
}

class stringDictionary {
    
    /**
     *
     * @var string
     */
    public $key = "";
    
    /**
     *
     * @var string
     */
    public $value;
    
}

class intDictionary {
    
    /**
     *
     * @var integer
     */
    public $key = 0;
    
    /**
     *
     * @var string
     */
    public $value;
    
}






?>
