<?php
/**
 * PhpToJson.php
 * Created on 03/04/2007
 *
 * This class can take the standard XAO database result set and encode it into a
 * JSON string for usage in JavaScript etc. It features call-back functionaility
 * borrowed from Xao_Util_DbToXml allowing you to extend this class and add your own
 * extra methods to process the data before it is converted to JavaScript.
 */

/**
 * Take a standard XAO result set like the one returned by arrQuery() from the
 * database drivers, and pass it to this class's constructor. You can then
 * Encode() that data and jsonGetResult() which can then be dumped to produce a
 * JavaScript compliant 2-dimensional array. call such a php script using
 * [script language="JavaScript" src="jason_data.php" /] for example. 
 * $objProc = new PhpToJson($arrData); 
 * $objProc->blnUtf8Encode = true; 
 * $objProc->Encode();
 * echo $objProc- >jsonGetResult();
 */
class   Xao_Util_PhpToJson 
extends Xao_Root 
{
    protected $strVarName;
    
    /**
     * You can processes columns from the source dataset through call back
     * functions which you insert into your child class which inherits this one.
     * These callbacks are specified in the second constructor parameter and
     * take the form of array("colname"=>"function_name")
     * 
     * @var    array
     */
    protected $arrCallBacks = array();
    
    /**
     * This is a XAO compliant database resultset from arrQuery(). this is a
     * sequence array of associative arrays where the array keys correspond to
     * the database results column names. This member variable is assigned from
     * the contents of the constructor's first argument.
     * 
     * @var    array
     */
    protected $mxdData = null;
    
    /**
     * This string is built up by the Encode() function and is formatted to the
     * specification as per http://www.json.org It is what is returned by 
     * $this->jsonGetResult()
     * 
     * @var    string
     */
    public $jsonResult = "";
    
    /**
     * An internal flag used to determin if the data has already been encoded
     * successfully. If not, then $this->jsonGetResult() will throw a warning.
     * 
     * @var    bool
     */
    private $blnEncoded = false;
    
    /**
     * Whether or not to escape the content data of a field before passing it to
     * the callback functions. If set to false, it is the responsibility of the
     * callback function developer to escape any strings they may return.
     * 
     * @var    bool
     */
    public $blnEscapeCallBacks = true;
    
    /**
     * Whether or not to pass the content data through utf8_encode()
     * 
     * @var    bool
     */
    public $blnUtf8Encode = false;
    
    /**
     * The strings to be replaced when escaping string data. This is assigned in
     * the constructor.
     * 
     * @var    array
     */
    protected $arrSrch;
    
    /**
     * The strings to replace with when escaping string data. This is assigned
     * in the constructor and must be aligned with $this->arrSrch.
     * 
     * @var array
     */
    protected $arrRepl;

    /**
     * The constructor takes the source data and sets up the variables.
     * 
     * @param    array    A XAO compliant result set.
     * @param    array    An associative array of column to function mappings
     * @return    void
     */
    public function __construct($mxdData = null, $arrCallBacks = null) {
                                        // Initialise substitutions
        $this->arrSrch =  array(
            chr(34),chr(92),chr(47),chr(8),chr(12),chr(10),chr(13),chr(9)
        );
        $this->arrRepl = array(
            "\\\"", "\\\\", "\\/",  "\\b", "\\f",  "\\n",  "\\r",  "\\t"
        );
        if(is_array($arrCallBacks)) $this->SetCallbacks($arrCallBacks);
        if(is_array($mxdData)) $this->SetData($mxdData);
    }
    
    public function SetCallbacks($arrCallBacks) {
        if(!is_array($arrCallBacks)) {
            $this->XaoThrow(
                "DbToXml::__construct() expects an associative array of "
                ."column names vs call-back methods as it's second argument", 
                debug_backtrace()
            );
            return;
        }
        (is_array($arrCallBacks))
            ? $this->arrCallBacks = $arrCallBacks 
            : $this->arrCallBacks = array();
    }
    
    public function SetData($mxdData) {
        if(!is_array($mxdData)) {
            $this->XaoThrow(
                "DbToXml::__construct() expects an XAO compliant result-set"
                ." array", 
                debug_backtrace()
            );
            return;
        }
        $this->mxdData = $mxdData;
    }
    
    /**
     * This method is called after instantiation and any optional parameters
     * (member variables) are set up. It builds the json data to 
     * $this->jsonResult
     * 
     * @param    array    A list of column names not to be escaped
     * @return    void
     */
    public function Encode($arrNotEscaped = null) {
        if(!$this->mxdData) {
            $this->XaoThrow(
                "DbToJason->Encode(): Cannot encode until data is set. use " 
                ."DbToJason->SetData().",
                debug_backtrace()
            );
        }
        if(!is_array($this->arrCallBacks)) return;
                                        // begins the string but also resets 
                                        // the rsult
        $this->jsonResult = $this->_mxdEscape($this->mxdData);
        if(!count($this->arrErrors)) $this->blnEncoded = true;
    }
    
    /**
     * A general purpose data processor to JSONize all field data
     * 
     * @param    mixed    The data to be escaped
     * @param    string    The name of the column it comes from
     * @return     mixed     The escaped result
     */
    function _mxdEscape($val,$key = "") {
        $type = gettype($val);
        switch($type) {
            case "integer": return $val;
            case "double": return $val;
            case "NULL": return "null";
            case "boolean": 
                if($val) return "true"; 
                else return "false";
            case "string": 
                if($this->blnUtf8Encode) {
                    return "\""
                        .str_replace(
                            $this->arrSrch,$this->arrRepl,utf8_encode($val)
                        )
                        ."\"";
                }
                else {
                    return "\""
                        .str_replace(
                            $this->arrSrch,$this->arrRepl,$val
                        )
                        ."\"";
                }
            case "array":
                if($this->isAssoc($val)) return $this->jsonEncodeAssoc($val);
                else return $this->jsonEncodeArray($val);
            default:
                if(!count($this->arrErrors)) {
                    $this->XaoThrow(
                        "DbToJason::_mxdEscape(): unsupported type in column "
                        .$key.": ".$type,
                        debug_backtrace()
                    );
                }
                return "\"[PhpToJson:_mxdEscape(): unsupported type]".$type."\"";
        }
    }
    
    /**
     * This is called at the end when all the work is done
     * 
     * @return     string     JSON formatted serialised string
     */
    public function jsonGetResult() {
        if(!$this->blnEncoded) {
            $this->XaoThrow(
                "PhpToJson::jsonGetResult(): Data has not been encoded yet or "
                ."there are errors",
                debug_backtrace(),
                array("level"=>"warning")
            );
            return;
        }
        return $this->jsonResult;
    }
    
    /**
     * This generic XAO method needs to be overriden to be useful in a
     * JavaScript context. At the moment, the backtrace array is ignored
     * 
     * @param    string    the message to send to the user
     * @param    array    for compatiability with Xao_Root::XaoThrow()
     * @param    array    paramters array
     * @param    bool    whether or not to terminate PHP execution.
     */
    public function XaoThrow(
        $strMsg,$arrBt = null,$arrParams = null ,$blnDie = false
    ) {
        parent::XaoThrow($strMsg,$arrBt,$arrParams,$blnDie);
        $strOut = "XAO: PhpToJson: ";
        if(is_array($arrParams) && array_key_exists("level",$arrParams)) {
            $strOut .= $arrParams["level"].": ";
        }
        $strOut .= "\n\n".$strMsg;
        echo "alert(".$this->_mxdEscape($strOut).");\n";
        if($blnDie) die("// script terminated by PhpToJson::XaoThrow()!");
    }
    
    public function AssignTo($strJsVarName,$blnDeclare = false) {
        if(!$this->blnEncoded) {
            $this->XaoThrow(
                "PhpToJson::jsonGetResult(): Data has not been encoded yet or "
                ."there are errors",
                debug_backtrace(),
                array("level"=>"warning")
            );
            return;
        }
        $prefix = ($blnDeclare) ? "var " : "";
        $this->jsonResult = $prefix.$strJsVarName." = ".$this->jsonResult;
    }
    
    /**
     * This function is used for debugging. It dumps the JSON content to the
     * output.
     * 
     * @return void
     */
     
    public function Send() {
        header("Content-Type: text/javascript");
        echo $this->jsonGetResult();
        die();
    }
     
    /**
     * Create another dimension above the result array
     * Sometimes the requirement for JSON data is more structured than a list of
     * records. This function allows you to create a higher level structure to
     * wrap the result data. It can be called as many times as you like - each
     * time, I higher level array will wrap it, increasing the number of array
     * dimensions by one every time it's called. This function cannot be called
     * until an existing result has been encoded. note that all keys in the
     * wrapping array will have their call-back functions called if they are
     * mapped in $this->arrCallBacks.
     * 
     * @param    array    An associative array to wrap the existing encoded data
     * @param    string    The array key into which the result is substituted
     * @return void
     */
    public function Wrap($arrWrapper, $strMatchKey) {
        if(!is_array($arrWrapper)) {
            $this->XaoThrow(
                "PhpToJson::WrapResult(): First param must be an associative "
                ."array. ",
                debug_backtrace()
            );
        }
        if(!array_key_exists($strMatchKey,$arrWrapper)) {
            $this->XaoThrow(
                "PhpToJson::WrapResult(): Could not find matching key "
                .$strMatchKey,
                debug_backtrace()
            );
        }
        $arrWrapper[$strMatchKey] = $this->jsonGetResult();
        $this->jsonResult = $this->jsonEncodeAssoc(
            $arrWrapper,
            array($strMatchKey)
        );
    }
    
    /**
     * JSON encode an associative array.
     * 
     * This is a general-purpose function for encoding an associative array into
     * a JavaScript object definition string. It is called by the Encode()
     * function on each row in the data result set. It is also called by the
     * WrapResult() function which assigns an already encoded JSON string to a
     * value which is then not to be escaped. It is tagged for escape avoidance
     * by having it's array key name listed in the array in the second
     * parameter.
     * 
     * @param    array    An associative array
     * @param    array    A list of array keys not to be escaped
     */
    public function jsonEncodeAssoc($arrRow, $arrNotEscaped = null) {
        if(!is_array($arrNotEscaped)) $arrNotEscaped = array();
        $jsonResult = "";
        $n = 0;
        foreach($arrRow AS $key => $val) {
            $n++;
            $jsonResult .= $key.":";
            if(array_key_exists($key,$this->arrCallBacks)) {
                $fnc = $this->arrCallBacks[$key];
                if($this->blnEscapeCallBacks && !in_array($key,$arrNotEscaped)){
                    $jsonResult .= $this->$fnc(
                        $key, $this->_mxdEscape($val,$key)
                    );
                }
                else {
                    $jsonResult .= $this->$fnc(
                        $key, $val
                    );
                }
            }
            else {
                if(in_array($key,$arrNotEscaped)) {
                    $jsonResult .= $val;
                }
                else {
                    $jsonResult .= $this->_mxdEscape($val,$key);
                }
            }
            if($n < count($arrRow)) $jsonResult .= ",";
        }
        return "{".$jsonResult."}";
    }
    
    /**
     * A method for encoding simple arrays.
     * 
     * @param array $arrSimple
     * @return unknown_type
     */
    public function jsonEncodeArray($arrSimple) {
        $jsonResult = "";
        $n = 0;
        foreach($arrSimple AS $val) {
            $n++;
            $jsonResult .= $this->_mxdEscape($val);
            if($n < count($arrSimple)) $jsonResult .= ",";
        }
        return "[".$jsonResult."]";
    }
    
    public function isAssoc($arr){
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

