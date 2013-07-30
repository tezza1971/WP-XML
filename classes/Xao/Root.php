<?php
$XAO_PROXY_APP_DOC_GLOBAL_INSTANCE = null;
/** 
 * Xao_Root.php
 * 
 * This script provides base class for alll the classes in the XAO library. Some
 * of the methods need to be overriden. It serves to provide the minimum
 * features for any XAO class.
 */

define("XAO_DRIVER_XSLT_TYPE_LIBXSL","LibXsl");

define("XAO_DRIVER_RDBMS_TYPE_POSTGRES","postgres");
define("XAO_DRIVER_RDBMS_TYPE_ORACLE","oracle");
define("XAO_DRIVER_RDBMS_TYPE_MSSQL","mssql");
define("XAO_DRIVER_RDBMS_TYPE_ACCESS","access");
define("XAO_DRIVER_RDBMS_TYPE_MYSQL4","mysql4");
define("XAO_DRIVER_RDBMS_TYPE_SQLITE","sqlite");
define("XAO_DRIVER_RDBMS_TYPE_PDO_MYSQL","pdo-mysql");

/**
 * New dom document from from scratch
 * This constant represents a mode of the DomDoc which causes it to create a new
 * document on instatiation - using the starter as the name of the root element
 * for the new document.
 */
define("XAO_DOC_NEW",10);

/**
 * Dom document from local file for reading only.
 * This constant represents a mode of the DomDoc which causes it to use an
 * existing XML file as the basis of the DomDoc document on instatiation - using
 * the starter to determin the location of the local file. It treats the file as
 * read-only so none of the write methods will work. It uses a non-exclusive
 * read lock when opening the file.
 */
define("XAO_DOC_READFILE",20);

/**
 * Dom document from existing PHP DOM object instance.
 * This constant represents a mode of the DomDoc which causes it to use an
 * existing PHP DOM XML object instance for this DomDoc instance. This has the
 * effect of adding functionality from this class to an existing DOM object.
 */
define("XAO_DOC_REFERENCE",50);

/**
 * Dom document from existing XML data in a variable.
 * This constant represents a mode of the DomDoc which causes it to use existing
 * XML data as the basis for a new DomDoc object. Obviously the XML data needs
 * to be well-formed.
 */
define("XAO_DOC_DATA",60);

/**
* XAO REQUIREMENTS CHECKS
*
* XAO cannot work without first meeting some basic requirements. This script is
* used to test these requirements as it is the base class and is therefore 
* inevitably included before any XAO classes can be used.
*/
function XAO_CHECK_MINIMUM_REQUIREMENTS() {
    $arrPhpVer = explode(".",phpversion());
    if($arrPhpVer[0] != 5) die("Currently XAO only supports PHP version 5");
    if(!extension_loaded("xml"))
        die("XAO requires the XML extension to be loaded.");
}
XAO_CHECK_MINIMUM_REQUIREMENTS();


/** 
* Base class from which all XAO classes are inherited,
*
* This class is previded mainly for the purposes of centralisation so that any
* properties and methods required by all classes in the framework, can easily
* be added at this one central point. It provides crude error 
* handling capabilties which are extended for  content-based 
* classes.
*/
class Xao_Root {

    /**
     * General purpose flag to use for testing status of a process
     * 
     * @var    bool
     */
    private $_blnGoodToGo = false;
    
    /**
    * XAO XML Namespace Identifier
    *
    * This is used to separate XAO generated XML data from the user's XML data.
    * 
    * @param      string  
    */
    public $idXaoNamespace = "http://xao-php.sourceforge.net/schema/xao_1-0.xsd";
    
    /**
    * XAO XML Namespace Prefix
    *
    * This is an arbitary string whos default is set here. If the users wishes
    * to change it, they need to do so before the DomDoc constructor is called.
    *
    * @param      string  
    */
    public $strXaoNamespacePrefix = "xao";
    
    /**
    * Debug data used when $this->blnDebug option is set to true
    * 
    * This variable will contain extra diagnostic information as well as 
    * standard errors under certain circumstances. It is only outputted if
    * $this->blnDebug is set.
    *
    * @access   private
    * @param    string  
    */
    protected $strDebugData;

    /**
    * All error information is kept here
    *
    * This is usually populated by the class which overrides the $Throw()
    * method. It's worth checking out the way DomDoc overrides this, any class
    * that inherits DomDoc (and there are a lot of them) will obviously use it's
    * version.
    *
    * @access   public
    * @param      array  
    */
    protected $strError;
    
    public $arrErrors = array();
    protected $arrErrorAttribs = array();
    protected $arrErrorPayload = array();
    private $_arrErrorSubstitutionSearches = array();
    private $_arrErrorSubstitutionReplacements = array();
    private $_arrErrorSubstitutionAttribs = array();
    private $_arrBts = array();
    
    /**
    * Debug option
    *
    * Designed to be used during development, this object will cause error
    * output to be more verbose unser certain conditions. It may also be used
    * by the developer to output diagnostic information at run-time. It is kept 
    * off by default in case it proposes any security issues.
    * 
    * @param      boolean  
    */
    public $blnDebug = false;
    
    /**
    * Whether or not to terminate the script when an error is thrown
    *
    * Most of the time you will want to handle errors gracefully rather than
    * having your application terminate abruptly. If you want everthing to stop
    * when an error is thrown, then set this class attribute to true in your
    * application base class.
    *
    * @param      array  
    */
    public $blnDieOnThrow = false;
    
    /**
    * Whether or not the content of the error message in XaoThrow() is HTML
    *
    * Basically this translates to replacing newline characters with BR tags at
    * this stage. Other HTML features may be added.
    *
    * @param      boolean  
    */
    public $blnThrowOutputInHtml = false;

    /**
    * Name of user-defined callback function to handle all errors.
    *
    * The user must then define a member function using the name specified in 
    * this string variable. The function must implement the signature like so
    * function myHardErrorChucker($strMsg,$intLevel,$strCode) {...}
    * assuming they did $this->strErrCallbackFunc = "myHardErrorChucker";
    * in their child class constructor. Suggest checking out the source code for
    * $this->XaoThrow();
    * The idea behind this facility is that you do not need to override Throw()
    * in order to implement custom logging etc.
    * 
    * @param      string  
    */
    protected $fncErrCallbackFunc = "";
    
    /**
    * Paramters used to cache work done by the class.
    *
    * This is an associative array with three different types of potential
    * keys:
    * 1) "key" - the cache key
    * 2) "ttl" - time to live (seconds).
    * 3) "exp" - a unix timestamp when when the object expires
    * To use the cache, "key" is required. In addition, either "ttl" or "exp"
    * need to be populated. This array is passed to the contructor of the
    * CacheMan class for further action. see documentation in CacheMan for more
    * information.
    *
    * @param      array
    */
    public $arrCacheParams = array();
    
    /**
     * A general purpose flag used to indicate whether it's OK to go ahead and
     * execture any give code (usually a method).
     * 
     * @param     bool
     */
    protected $blnGoodToGo = true;
    
    /**
     * Generic/default error handler.
     * TODO: revise this method and all error handling under PHP5 Please note
     * that a call to this method (or it's overridden derivative) should ALWAYS
     * be supplied with a call to debug_backtrace() as the second param. This is
     * needed to provide an accurate stack trace leading all the way back to the
     * place of exception.
     *
     * @param    string  user-defined error message
     * @param    array   stack trace provided by debug_backtrace()
     * @param    array   hash list of metadata to provide supportive context
     * @return   void
     */
    public function XaoThrow(
        $strErrMsg, $arrBt = null, $arrAttribs = null, $arrPayload = null
    ) {
        if(!is_array($arrBt)) $arrBt = debug_backtrace();
        if(is_null($arrAttribs)) $arrAttribs = array();
        if(is_null($arrPayload)) $arrPayload = array();
        foreach($arrAttribs AS $key=>$val) {
            if(!is_string($key)) {
                $arr = array("YOU MUST USE AN ASSOCIATIVE ARRAY when " .
                    "populating the attributes argument (3rd) of the " .
                    "XaoThrow() method.",debug_backtrace());
                $this->DEBUG($arr);
            }
        }
                                        // clear any previous errors
        $this->strError = "";
        foreach($this->_arrErrorSubstitutionSearches AS $i => $strNeedle) {
            if(preg_match($strNeedle,$strErrMsg)) {
                $strErrMsg = $this->_arrErrorSubstitutionReplacements[$i];
                if(count($this->_arrErrorSubstitutionAttribs[$i])) {
                    $arrAttribs = $this->_arrErrorSubstitutionAttribs[$i];
                }
            }
        }
        $this->arrErrors[] = $strErrMsg;
        $this->arrErrorAttribs[] = $arrAttribs;
        $this->arrErrorPayload[] = $arrPayload;
        
        $this->_arrBts[] = $arrBt;

        if(
            array_key_exists("class",$arrAttribs) 
            && array_key_exists("function",$arrAttribs)
            && array_key_exists("line",$arrAttribs)
        ) {
            $this->strError .= 
                "In method "
                .$arrAttribs["class"]."::"
                .$arrAttribs["function"]."() on line "
                .$arrAttribs["line"]."\n\n";
        }
        
        $this->strError .= $strErrMsg;
                                        // call user-defined error function
        $fcn = (string)$this->fncErrCallbackFunc;
        if(strlen($fcn)) {
            if(method_exists($this,$fcn)) {
                $this->$fcn($strErrMsg,$arrBt,$arrAttribs,$arrPayload);
            }
        }
        
        if($this->blnThrowOutputInHtml) {
            $this->strError = "<pre class=\"XAO_error\">"
                .$this->strError."</pre>";
        }
        
        if($this->blnDieOnThrow) {
            print("blnDieOnThrow has been set to true. " .
                    "Script terminated by Xao_Root::XaoThrow(): error below: " .
                    "<br/><br/>".$this->strError);
            Xao_Root::DEBUG(debug_backtrace());
        }
    }
    
    /**
     * Conveniently route a PHP5 exception to XaoThrow
     * 
     * PHP5 Exceptions contain a rich data set that XaoThrow is capable of
     * using. This method provides a convenient single access-point to
     * implementing that without having to bloat the code just to pass a general
     * exception to XAO.
     * 
     * @param    object    PHP5 Exception object
     */
    public function XaoThrowE($exception,$arrExtra = null) {
        $arrAttribs = array(
                "line" => $exception->getLine(), 
                "code" => $exception->getCode(), 
                "file" => $exception->getFile()
            );
        if(is_array($arrExtra)) {
            $arrAttribs = array_merge($arrAttribs,$arrExtra);
        }
        if(is_string($arrExtra) && strlen($arrExtra)) {
            $arrAttribs["phperror"] = $exception->getMessage();
            $this->XaoThrow(
                $arrExtra,
                $exception->getTrace(),
                $arrAttribs
            );
        }
        else {
            $this->XaoThrow(
                $exception->getMessage(),
                $exception->getTrace(),
                $arrAttribs
            );
        }
    }
    
    /**
     * Originally designed as part of DomDoc, this is another quick way to bail
     * out of an error condition. This is overriden in DomDoc and bails out with
     * a DOM document. It is included in this here parent because
     * objGetDomFactoryData() uses it.
     * 
     * @param    string    bail out message
     */
    protected function _AbortDocument($strErrMsg) {
        die($strErrMsg);
    }
    
    /**
     * This factory method is used to return a DOMDocument reliably.  
     * 
     * The DomFactory class is used to return an instance of the DOMDocument
     * from an existing XML source (string or file). The reason a wrapper is
     * used is so that nice debugging uptions and error handling can be
     * implemented for parsing. See the DomFactory class for more details.
     * 
     * @param    mixed    URI to XML file or the XML data itself.
     * @return    object    parsed DOMDocument instance.
     */
    protected function objGetDomFactoryData($mxdData) {
        $objDomFactory = new Xao_NonUser_DomFactory($mxdData);
        if(strlen($objDomFactory->strErrorMsgFull)) {
            $this->_AbortDocument($objDomFactory->strErrorMsgFull);
            die($objDomFactory->strError);
        }
        else {
            $objDoc = $objDomFactory->objGetObjDoc();
            if(!is_object($objDoc)) {
                if($objDomFactory->strErrorMsg) {
                    $this->_AbortDocument($objDomFactory->strErrorMsg);
                }
                else {
                    $this->_AbortDocument(
                        "Unknown XML parse error in ".$mxdData
                    );
                }
            }
            else {
                return $objDoc;
            }
        }
    }

    /**
     * Drop the current errors buffer. Dunno why this is here really :/
     * 
     * @return    void
     */
    public function ClearErrors() {
        $this->arrErrors = array();
        $this->arrErrorAttribs = array();
        $this->arrErrorPayload = array();
        $this->_arrBts = array();
        $this->strError = "";
    }
    
    /**
     * Obtain the last error message
     * 
     * @return    string
     */
    public function strGetLastError() {
        $val = end($this->arrErrors);
        reset($this->arrErrors);
        return $val;
    }    
    
    /**
     * Check for errors on an arbitary object containing arrErrors
     * NEVER EVER CALL THIS METHOD ON A CLASS THAT OVERRIDES THE XAOTHROW METHOD
     * AND TERMINATES SCRIPT EXECUTION. For any functional classes you may use
     * by association, you can employ AppDoc's error throwing by calling this
     * method and passing it's instance. The main requirement is that it's error
     * stack is contained in a member variable by the name of arrErrors. If this
     * function itself throws an error it returns true. This is done in case
     * it's return value is checked for errors in an if statement. If it
     * executes successfully and returns an error stack count of 0, then this
     * will equate to false in an if statement. After all the errors are thrown,
     * the error stack is cleared on the referenced object. This method can also
     * be used simply to check for errors but not to throw them, this determined
     * by the second parameter. If you choose not to throw the errors, the error
     * stack is NOT cleared.
     * 
     * @param   object  Associated object instance (will be referenced)
     * @param   bool    whether or not to Throw the discovered errors
     * @return  integer The amount of errors found in the instance
     */
    public function intCountErrors($obj,$blnProcess = false) {
        if(!is_object($obj)) {
            $this->XaoThrow(
                "AppDoc::intCountErrors(): The first parameter to " .
                "intCountErrors needs to be an object instance.",
                debug_backtrace()
            );
                                        // true indicates there was an error
            return true;
        }
        if(!isset($obj->arrErrors) || !is_array($obj->arrErrors)) {
            $this->XaoThrow(
                get_class($obj)." does not have an arrErrors member variable ".
                "or if it does, then it is not an array.",
                debug_backtrace()
            );
                                        // true indicates there was an error
            return true;
        }
        
        $intErrors = count($obj->arrErrors);
        if($blnProcess && get_class($this) != get_class($obj)) {
                                        // Re-throw them all using the common
                                        // throw method.
            foreach($obj->arrErrors AS $i => $strError) {
                $this->XaoThrow(
                    $strError,
                    $obj->_arrBts[$i],
                    $obj->arrErrorAttribs[$i],
                    $obj->arrErrorPayload[$i]
                );
            }
                                        // clear the stack to prevent re-throws
            $obj->ClearErrors();
        }
        return $intErrors;
    }
    
    /**
     * Used for specifying replacement strings in error messages using preg
     * 
     * As errors are assigned in XaoThrow, they are checked using preg_match for
     * the $strString. If there is a match, the entire error message is replaced
     * with $strReplace. In addition, any attributes supplied here will also
     * be applied if a match is found.
     * 
     * @param    string    regular expression search string
     * @param    string    replacement message (overwrites original)
     * @param    array    optional attribute list (overwrites original)
     * @return    void
     */
    public function SetSubstituteErrMsg($strSearch, $strReplace, $arrAttribs = null) {
        if(!is_array($arrAttribs)) $arrAttribs = array();
        $this->_arrErrorSubstitutionSearches[] = $strSearch;
        $this->_arrErrorSubstitutionReplacements[] = $strReplace;
        $this->_arrErrorSubstitutionAttribs[] = $arrAttribs;
    }
    
    /**
     * This method is used to check if a string to see if it is sage to use as
     * an XML name - ie. as an attribute name or an element name.
     * 
     * @param    string    Value to be tested
     * @return    bool
     */
    public function blnTestSafeName($strSubject) {
        if(!is_string($strSubject)) {
            $this->XaoThrow(
                "Xao_Root::blnTestSafeName(): Attempt to pass a" .
                " non-string (".gettype($strSubject).") for XML name checking."
                ,debug_backtrace()
            );
            return false;
        }
                                        // check for line beginning with digit
                                        // or check for any non-word character
        if(preg_match("/(^\\d)|(\\W)/",$strSubject)) {
            $this->XaoThrow(
                "Xao_Root::blnTestSafeName(): ".$strSubject." is not a safe " .
                "name for use as an XML object"
                ,debug_backtrace()
            );
            return false;
        }
        return true;
    }
    
    /**
     * General purpose method used for dumping output for debugging
     * 
     * This method will terminate a script at the time it is called. It is used
     * to format the output for easy/convenient perousal.
     * 
     * @param   mixed   value to be debugged
     * @param   array   Call stack
     * @param   string  A user-friendly label to provide context
     * 
     * @return  void
     */
    public static function DEBUG($mxd,$blnBacktrace = false,$strCtx = "") {
        @header("Content-Type: text/plain");
        echo $strCtx."\n\n";
        var_dump($mxd);
        if($blnBacktrace) {
            echo("\n\nBACKTRACE:\n\n");
            $arrBt = debug_backtrace();
            Xao_Root::_print_bt($arrBt);
        }
        die();
    }
    
    /**
     * Pretty print facility for debug_backtrace() output.
     */
    protected static function _print_bt($arrBt) {
        foreach($arrBt AS $key => $val) {
            if($key == "type") continue;
            if($key == "file") echo "\n";
            if(is_array($val)) {
                Xao_Root::_print_bt($val);
            }
            else {
                if(is_object($val)) $val = get_class($val);
                echo $key."|".$val."\n";
            }
        }
    }
    
    /**
     * A convenince method for debugging DOM node content
     * 
     * @param   object    The PHP5 DOM document
     * @param    object    The PHP5 DOM document node
     * @param    array    the debug_backtrace() output
     * @return    void    
     */
    public static function DEBUG_NODE($doc,$nd,$blnBacktrace = false) {
        if(!is_object($doc)) die("DEBUG_NODE cannot debug first arg that is not a document object.");
        if(!is_object($nd)) die("DEBUG_NODE cannot debug second arg that is not a node object.");
        if(get_class($doc) != "DOMDocument") {
            echo "You did not supply a PHP5 DOM object as the first argument " .
                "to DEBUG_NODE.\n\nHere is what you supplied...\n\n";
            Xao_Root::DEBUG($doc);
        }
        if(get_class($nd) != "DOMElement") {
            echo "You did not supply a PHP5 DOM node object as the second " .
                "argument to DEBUG_NODE.\n\nHere is what you supplied...\n\n";
            Xao_Root::DEBUG($doc);
        }
        $xml = $doc->saveXML($nd);
        Xao_Root::DEBUG($xml,$blnBacktrace);
    }
    
    /**
     * This method is used to dump a 2d associative array as a HTML table. 
     * 
     * This is handy for debugging database results etc.
     * 
     * @param    array    The 2d data array
     * @return  void
     */
    public static function HTML_Table_Dump($arr) {
        if(!is_array($arr)) die("DEBUG_TABLE: expect a 2d array.");
        if(!count($arr)) die("DEBUG_TABLE: Array is empty");
        $row1 = $arr[0];
        if(!is_array($row1)) die("DEBUG_TABLE: No rows in array.");
        $cols = array();
        foreach($row1 as $colname => $colval) {
            $cols[] = $colname;
        }
        $html = "<table border=\"1\" cellpadding=\"4\" cellspacing=\"1\"><caption>DEBUG_TABLE</caption>\n<tr>\n";
        foreach($cols as $colname) {
            $html .= "<th>".$colname."</th>\n";
        }
        $html .= "\n</tr>";
        foreach($arr as $row) {
            $html .= "<tr>\n";
            foreach($row as $colval) {
                $html .= "<td>".$colval."</td>\n";
            }
            $html .= "</tr>\n";
        }
        $html .= "</table>\n";
        return $html;
    }
    
    /**
     * A pretty print facility for debug_backtrace() output
     * 
     * @param    array    debug_backtrace() output
     * @return    void
     */
    public static function HTML_Stack_Dump($arrBt) {
        $htmlDump = "<div>STACK DUMP:</div>";
        foreach($arrBt AS $arrCall) {
            $htmlDump .="\n<div style=\"border: 1px solid black;\">\n";
            foreach($arrCall AS $strLabel => $strVal) {
                if(
                    $strLabel == "args" || 
                    $strLabel == "type" || 
                    !is_string($strVal)
                ) continue;
                $htmlDump .= "<b>".$strLabel."</b>: ".$strVal."<br/>\n";
            }
            $htmlDump .="</div>\n";
        }
        return $htmlDump;
    }
    
    /**
     * Apply the XAO namespace to a DOM element object
     * 
     * @param    object    The DOM element
     * @return    void
     */
    public function PutXaoNs($ndTarget,$blnDefault = false) {
        // Xao_Root::DEBUG($ndTarget);
        if(is_object($ndTarget) && get_class($ndTarget) == "DOMElement") {
            $prefix = ":".$this->strXaoNamespacePrefix;
            if($blnDefault) $prefix = "";
            $ndTarget->setAttribute(
                "xmlns".$prefix,$this->idXaoNamespace
            );
            /*
            $ndTarget->addNamespace(
                $this->idXaoNamespace,
                $this->strXaoNamespacePrefix
            );
            */
        }
        else {
            $this->XaoThrow(
                "PutXaoNs(): argument is not a valid DOMElement object.",
                debug_backtrace()
            );
        }
    }
    
    /**
     * parses a string for parameters formatted in a particular way.
     * 
     * The string must contain parentheses in which the params are specified.
     * Each param consists of it's name, followed by it's value which is
     * contained in square brackets. ie. (foo[23r4q34]bar[werwe])
     * 
     * @param    string    The encoded expression
     * @return  array    A structured array containing the results
     */
    protected function arrParamParse($strExpr) {
        $arrResult = array();
        $arrResult["params"] = array();
        $arrResult["original"] = $strExpr;
        $arrResult["info"] = array("Could not find any params.");
        $arrMatches = array();
        if(
            $intParamCount = 
                preg_match_all(
                    "/\((\w+)\[(.*?)\]\)/",
                    $strExpr,
                    $arrMatches,
                    PREG_SET_ORDER
                )
        ) {
            $arrResult["info"] = 
                array("Found ".$intParamCount." param declarations.");
            foreach($arrMatches AS $arrToken) {
                if(array_key_exists($arrToken[1],$arrResult["params"])) {
                    $arrResult["info"][] = "Duplicate param declaration found".
                        " for ".$arrToken[1].". Using value from last ".
                        "declaration (overwriting).";
                }
                $arrResult["params"][$arrToken[1]] = $arrToken[2];
            }
        }
        return $arrResult;
    }

    /**
     * Try to trap any user-triggered errors for handling by DomDoc::throw
     * 
     * This function is a partial solution for general PHP error handling based
     * on PHP's own error management capabilities. Unfortunately, PHP will only
     * allow your error handling call-back function to process what it calls
     * "USER" errors - errors that are triggered using the trigger_error() or
     * user_error() functions. PHP does not let you manage PHP generated errors.
     * Furthermore, this function is only useful when people use XAO in
     * framework mode.
     *
     * @access   private
     */
    static function TrapPhpErrors($strAppDocInstanceVarName) {
        global $XAO_PROXY_APP_DOC_GLOBAL_INSTANCE;
                                        // import the application object 
                                        // instance.
        eval('global $'.$strAppDocInstanceVarName.';');
                                        // custom error handler cannot be set 
                                        // from within an object. It also needs
                                        // the handler to be a router to the
                                        // application object (if found in the
                                        // global scope).
        if(
            !isset($$strAppDocInstanceVarName) || 
            !is_object($$strAppDocInstanceVarName) ||
            !method_exists($$strAppDocInstanceVarName,"XaoThrow")
        ) {
            die(
                "Xao_Root::TrapPhpErrors(\"".$strAppDocInstanceVarName.
                "\"): AppDoc object by the name of ".$strAppDocInstanceVarName.
                " does not exist in the global scope."
            );
        }
                                        // I really wanted to pass this by
                                        // reference but simply could not get
                                        // it to show up when globaled from
                                        // _XaoErrorRouter(). So the value
                                        // is copied. It shouldn't work, but it
                                        // does. If you are uncomfortable with
                                        // this, then don't use 
                                        // Xao_Root::TrapPhpErrors()
        $XAO_PROXY_APP_DOC_GLOBAL_INSTANCE = $$strAppDocInstanceVarName;
        set_error_handler("_XaoErrorRouter");
        set_exception_handler("_XaoExceptionRouter");
    }
    
    public function SetGoodToGo($blnGood) {
        $this->_blnGoodToGo = (bool)$blnGood;
    }
    
    public function blnGoodToGo() {
        return $this->_blnGoodToGo;
    }
}

/**
 * Custom  error handler function
 *
 * Normally everything in XAO adheres strictly to being coded as object
 * oriented, however, since that's not how PHP was designed, allowances have to
 * be made. The following function is referred to by the Xao_Root::
 * TrapPhpErrors() method as specified by the set_error_handler
 * ("XaoErrorRouter") function. The call- back specified in set_error_handler()
 * is not able to exist inside a class definition if it is to work as intended
 * with PHPs custom error handling. It is only effective if the user
 * instantiates $objAppDoc as part of a conventional XAO methodology.
 *
 * @param    string  error code
 * @param    string  error message
 * @param    string  error location of context script
 * @param    integer line number of context script
 * @param    array   any arguments involved in an errored function
 * @access   private
 */
function _XaoErrorRouter(
    $strErrCode, 
    $strErrMsg, 
    $uriContext, 
    $intLine, 
    $mxdArgs
) {
    global $XAO_PROXY_APP_DOC_GLOBAL_INSTANCE;
    if(
        !isset($XAO_PROXY_APP_DOC_GLOBAL_INSTANCE) || 
        !is_object($XAO_PROXY_APP_DOC_GLOBAL_INSTANCE) ||
        !method_exists($XAO_PROXY_APP_DOC_GLOBAL_INSTANCE,"XaoThrow")
    ) {
        die(
            "_XaoErrorRouter(): This function should only be called " .
            "(statically) from Xao_Root::TrapPhpErrors()."
        );
    }
    $arrAttribs = array(
        "code"    => $strErrCode,
        "file"    => $uriContext,
        "line"    => $intLine,
        "phpArgs" => serialize($mxdArgs)
    );
                                        // ATM, error reports require an XML
                                        // aware user-agent.
    $XAO_PROXY_APP_DOC_GLOBAL_INSTANCE->XaoThrow(
        "PHP ERROR: ".$strErrMsg,
        debug_backtrace(),
        $arrAttribs
    );
}

/**
 * Same as _XaoErrorRouter() but for PHP5 exceptions
 * 
 * @param    object    PHP Exception instance
 * @return    void
 */
function _XaoExceptionRouter($e) {
    global $XAO_PROXY_APP_DOC_GLOBAL_INSTANCE;
    if(
        !isset($XAO_PROXY_APP_DOC_GLOBAL_INSTANCE) || 
        !is_object($XAO_PROXY_APP_DOC_GLOBAL_INSTANCE) ||
        !method_exists($XAO_PROXY_APP_DOC_GLOBAL_INSTANCE,"XaoThrow")
    ) {
        die(
            "_XaoExceptionRouter(): This function should only be called " .
            "(statically) from Xao_Root::TrapPhpErrors()."
        );
    }
    $arrAttribs = array(
        "code"    => $e->getCode(),
        "file"    => $e->getFile,
        "line"    => $e->getLine,
    );
                                        // ATM, error reports require an XML
                                        // aware user-agent.
    $XAO_PROXY_APP_DOC_GLOBAL_INSTANCE->XaoThrow(
        "PHP EXCEPTION: ".$e->getMessage(),
        $e->getTrace(),
        $arrAttribs
    );
}
