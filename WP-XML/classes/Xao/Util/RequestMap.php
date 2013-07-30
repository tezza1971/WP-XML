<?php
/**
* Utility to implement and manage request to handler mappings
*
* This class allows developers to declare request (event) handlers. A standard
* associative array of requests (ie. $_REQUEST) is searched for a declared
* request name and the value of that request name is matched to it's declared
* function. The developer may use an XML file to delcare a request map.
*
* @author       Terence Kearns
* @version      0.0
* @copyright    Terence Kearns 2003
* @license      LGPL
* @package      XAO
* @link         http://xao-php.sourceforge.net
*/
class   Xao_Util_RequestMap 
extends Xao_Root 
{
    /**
     * Main RequestMap array.
     * 
     * This array contains the definative structure required by the RequestMap
     * class to implement Request-to-Function mapping. This variable MUST be
     * established in the constructor. It is either copied from the first
     * parameter of the constructor if the parameter is an array, or built and
     * assigned by the _ParseMapFile() method if the constructor parameter is a
     * URL to RequestMap XML document. Here is how a requestmap variable may be
     * declared in PHP: var $arrRequestMap = array(    array(       "strReqName"
     * => "[[INSERT REQUEST SET NAME HERE]]",       "arrReqCallBacks" => array(
     * array("[[REQUEST NAME]]","[[HANDLER FUNCTION NAME]]"),       )    ), );
     * This structure affords a many-to-many relationship between request keys
     * and handler functions. See documentation on _ParseMapFile() for how to
     * format the XML alternative.
     * 
     * @var    array
     */
    public $arrReqMap;

    /**
     * Temporary Request set array used by _ParseMapFile()
     * 
     * This varaible is referred to in parse_OpenElement() and
     * parse_ClosedElement() when the XML parser is run. It is used as a
     * temporary stack to build each request set as declared in the XML file.
     * There is no need for the developer to have anything to do with it.
     * 
     * @var    array
     */
    private $_arrRequestSet;

    /**
     * Temporary cache file containing serialised $this->arrReqMap
     * 
     * In the case of an XML file beuing used to declare a RequestMap, this file
     * will be used to cache the result of the XML parsing/conversion to the
     * main RequestMap array. Once the array is extracted from the XML file, it
     * is serialized using the PHP serialize() function and written to this
     * file. Subsequent calls to the _ParseMapFile() method will shortcircuit
     * the XML parser and unserialize the contents of the cache file directly to
     * the main RequestMap array.
     * 
     * @var    string
     */
    public $uriCacheFile = "RequestMap.tmp";
    
    /**
     * XML parser resource handle
     * 
     * This class variable is used internally by the _ParseMapFile() function.
     *
     * @var    resource
     */
    private $_resParser;
    
    /**
     * RequestMap constructor establishes the main RequestMap array.
     * 
     * It is possible that the developer prefers to declare the request mappings
     * directly as an array in PHP code, most likely hardcoded in the base
     * application class. This is the most efficient method. However, most
     * developers will prefer the more user-friendly XML format which is also
     * more flexible as the file can be programatically created by PHP or other
     * software. If the constructor argument is a URI to the appropriate XML
     * file, then _ParseMapFile() is called which will set the main RequestMap
     * array class variable. See _ParseMapFile() for details.
     * 
     * @param   mixed    either a RequestMap array or a URI to RequestMap XML
     * @param   uri      specified location of the map cache file.
     * @return  void
     * @access  public
     */
    public function __construct($mxdReqMap,$uriCacheFile = null) {
        if($uriCacheFile) {
            $this->uriCacheFile = $uriCacheFile;
        }
        if(is_array($mxdReqMap)) {
            $this->arrReqMap = $mxdReqMap;
        }
        elseif(file_exists($mxdReqMap)) {
            $this->_ParseMapFile($mxdReqMap);
        }
        else {
            $this->XaoThrow(
                "Connot use supplied argument for Request Map: ".$mxdReqMap,
                debug_backtrace()
            );
        }
    }
    
    /**
     * Override error function to force program termination
     * 
     * If this class has errors, then we can't guarentee that the app won't
     * break in some (difficult to detect) functional way. So everything must
     * stop until it's all good to go.
     * 
     * @param   string   error message
     * @param   void     placeholder
     * @param   void     placeholder
     */
    public function XaoThrow($msg, $bt = null, $stub2 = null) {
        if(is_array($bt) && count($bt)) {
            $msg = $bt[0]["file"]."\n<br/>line ".$bt[0]["line"]."\n<br/>".$msg;
        }
        die("RequestMap::XaoThrow() has terminated script execution.<br />" .
                "<br />".$msg);
    }

    /**
     * Search the RequestMap array and call any requested handler functions.
     * 
     * This public method will be called at a point where processing of the
     * client requests should be handled. The method will use the main
     * RequestMap array to allocate declared client-requests to their
     * corresponding handler call-back functions. Said functions need to exist
     * on the object instance which is passed as the first argument to these
     * functions. It also requires hash-table of requests which uses the same
     * format as the built in PHP environemnt (superglobals) variable --
     * $_REQUEST, $_GET, $_POST, $_COOKIE
     * 
     * @param   object   instance of class where the request handler is defined
     * @param   array    hash table containing all client requests
     * @return  void
     * @access  public
     */
    public function ExecuteRequests($objHandler,$arrReq = null) {
        if(!is_array($this->arrReqMap)) return false;
        if(!is_object($objHandler)) return false;
        
        $blnAtLeastOneHit = false;
        foreach($this->arrReqMap as $arrReqSet) {
            $strReqName = $arrReqSet["strReqName"];
            if(!array_key_exists($strReqName,$objHandler->arrReq)) continue;
            foreach($arrReqSet["arrReqCallBacks"] as $arrEventToHandler) {
                $strReqValue = $arrEventToHandler[0];
                $fncHandler = "REQ_".$arrEventToHandler[1];
                if($strReqValue != $objHandler->arrReq[$strReqName]) continue;
                if(method_exists($objHandler,$fncHandler)) {
                    if(is_array($arrReq)) {
                        $objHandler->$fncHandler($arrReq);
                    }
                    else {
                        $objHandler->$fncHandler();
                        $bt = debug_backtrace();
                    }
                    $blnAtLeastOneHit = true;
                }
            }
        }
        return $blnAtLeastOneHit;
    }
    
    /**
     * Build the main RequestMap array from an XML file
     * 
     * This method should only be called by the constructor which will contain
     * the URI argument that this function requires. Note that this function
     * could incur a potentially expensive processing overhead if the XML
     * RequestMap file needed to be parsed with every page view. This function
     * attempts to bypass the XML parsing process by running the
     * GetCachedMapfile() method (see it's notes for details). For this to ever
     * work, this function also needs to popluate the cache with the serialised
     * main RequestMap array after it is successfully built from parsing the XML
     * file. See notes for class var $uriCacheFile. This method uses the
     * standard PHP built-in XML module. This is a SAX parser using "event"
     * based parsing which is the fastest. See PHP documentation on xml_parse()
     * for details. The actualy construction of the main RequestMap array is
     * done by the two functions parse_OpenElement() and parse_CloseElement
     * which are automatically called by the xml_parse() function as it reads
     * the XML document data. The XML data should be structured as follows:
     *   <xao:  RequestMap       xao:xmlns="http://xao-php.sourceforge.
     * net/schema/xao_1-0.xsd">
     *       <xao:      RequestSet ReqName="[[INSERT REQUEST SET NAME HERE]]">
     *           <xao:          Request               ReqValue="[[REQUEST
     * NAME]]"               Handler="[[HANDLER FUNCTION NAME]]" /> [[insert
     * more request/handler pairs here]]
     *       </xao:      RequestSet>       [[insert more request sets here]]
     *   </xao:  RequestMap>
     * 
     * @param   uri      Location to the RequestMap XML file.
     * @return  void
     * @access  private
     */
    private function _ParseMapFile($uriReqMap) {
        $this->GetCachedMapfile();
        if(is_array($this->arrReqMap)) return true;
        
        $xmlRequestMap = file_get_contents($uriReqMap);
        
        if(!$this->_resParser) {
            $this->_resParser = xml_parser_create();
            xml_parser_set_option($this->_resParser, XML_OPTION_CASE_FOLDING, 0);
            xml_set_object($this->_resParser, $this);
            xml_set_element_handler(
                $this->_resParser,
                "parse_OpenElement",
                "parse_CloseElement"
            );
        }
        
        if(!xml_parse($this->_resParser,$xmlRequestMap))
            $this->XaoThrow(
                "Request map located at ".$uriReqMap." is corrupt.",
                debug_backtrace()
            );
        
        if(is_array($this->arrReqMap)) {
            $fp = fopen($this->uriCacheFile,"w");
            fwrite($fp,serialize($this->arrReqMap));
            fclose($fp);
        }
    }
    
    public function GetCachedMapfile() {
        if(file_exists($this->uriCacheFile)) {
            $arr = unserialize(file_get_contents($this->uriCacheFile));
            if(is_array($arr)) {
                $this->arrReqMap = $arr;
                return true;
            }
            // either got a dirty read or file is corrupt
            unlink($this->uriCacheFile);
        }
        return false;
    }

    /**
     * Used by the XML parser
     */
    public function parse_OpenElement($resParser, $strElName, $arrAttribs) {
        $arrElName = explode(":",$strElName);
                                        // ignore elements that are not part of
                                        // the XAO namespace.
        if($arrElName[0] != $this->strXaoNamespacePrefix) return;
        $strElName = $arrElName[1];
        
        if($strElName == "RequestMap") $this->arrReqMap = array();

        if($strElName == "RequestSet" && isset($arrAttribs["ReqName"])) {
            $this->_arrRequestSet = array();
            $this->_arrRequestSet["strReqName"] = $arrAttribs["ReqName"];
            $this->_arrRequestSet["arrReqCallBacks"] = array();
        }
        
        if(
            $strElName == "Request" 
            && isset($arrAttribs["ReqValue"]) 
            && isset($arrAttribs["Handler"])
        ) {
            $this->_arrRequestSet["arrReqCallBacks"][] 
                = array($arrAttribs["ReqValue"],$arrAttribs["Handler"]);
        }

    }
    
    /**
     * Used by the XML parser
     */
    public function parse_CloseElement($resParser, $strElName) { // do nothing 
        $arrElName = explode(":",$strElName);
                                        // ignore elements that are not part of
                                        // the XAO namespace.
        if($arrElName[0] != $this->strXaoNamespacePrefix) return;
        $strElName = $arrElName[1];
        
        if($strElName == "RequestSet") {
            $this->arrReqMap[] = $this->_arrRequestSet;
        }
    }
    
}
