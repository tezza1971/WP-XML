<?php
/**
 * InputValidator.php
 * 
 * This script contains all the infrastructure to support server-side form
 * validation. See class comments for details. Validation methods can be added
 * by extending this class (or the InputValidatorBasic child) and adding member
 * methods there.
 * 
 * @author       Terence Kearns
 * @version      1.0
 * @copyright    Terence Kearns 2006
 * @license      LGPL
 * @link         http://xao-php.sourceforge.net
 * @package      XAO
 */

/**
 * The InputValidator class provides a foundation framework for input validation
 * 
 * It may be used in isolation, or it may be passed as a second param to the
 * Handler constructor or sub-class there-of. This class is extended by
 * InputValidatorBasic which may also be extended by a user who would like to
 * add their own validation directives or override one of the default directive
 * handlers. See InputValidatorBasic class docs for more information on
 * how to add methods and naming conventions. 
 * All validation (execution) occurs in this classes ValidateInput() method. See
 * this method for tips on how the validation method parameters are passed. See
 * the XAO Handler class to see an example of how it can be called.
 */
class   Xao_Util_InputValidator 
extends Xao_Root 
{
    
    /** 
     * Array of validation errors
     * 
     * This log is for developers to use as they see fit. It may be tested in
     * a form handler to determine wheather handling processing should proceed
     * or not.
     * 
     * @var    array
     */
    public $arrValidationErrors = array();

    /** 
     * Directive prefix used in post vars (form element names)
     * 
     * HTML form elements beginning with this prefix (eg. "VLD_NotNull_name")
     * will be parsed (tokenised using the separator) for validation directives
     * and associated target form element names. Note that if you change this,
     * then your (sub) class will no longer support the built in directives
     * 
     * @var    string
     */
    private $strDirectivePrefix = "VLD";

    /** 
     * Directive separator character
     * 
     * The parser splits up the tokens using this character. It is important
     * that only one character is used in this string. It must be usable as the
     * first param in PHPs explode() function.
     * 
     * @var    string
     */
    private $strDirectiveSeparator = "_";
 
    /** 
     * The constructor is not used at this stage
     * 
     * It may be used to initialise validator settings prior to running the 
     * validation parser (this->ValidateInput) in future. For instance,
     * validation directives may be read from a file rather than the request
     * array.
     * 
     * @param    array    Optional argument to immediately validate
     * @return    void
     */
    public function __construct($arrReq = null) {
        if(is_array($arrReq)) $this->ValidateInput($arrReq);
    }
    
    /**
     * public setter method for $strDirectiveSeparator
     * 
     * @param    string    The delimiter to look for in the hidden form element.
     */
    public function SetSeparator($strSep) {
        $this->strDirectiveSeparator = $strSep;
    }
    
    /**
     * public setter method for $strDirectivePrefix
     * 
     * @param   string  The prefix to look for in the hidden form element.
     */
    public function SetPrefix($strPre) {
        $this->strDirectivePrefix = $strPre;
    }

    /** 
     * Check request array for validation directives and their target elements
     * 
     * Currently this function traverses a single hash (request array) to 
     * find both validation directives as well as target variables for said
     * directives. Once all the requirements for a found directive are met, it
     * will then call the (user-defined) call-back function, passing it a
     * standard set of params which support the validation processing performed
     * by said call-back function.
     * 
     * @param    array    An associative array of name/value pairs with data and
     *                     specifications     for validation
     * @return    void
     */
    public function ValidateInput(&$arrReq) {
        foreach($arrReq AS $strVldName => $strVldMsg) {
                                        // limiting the tokenisation to 3 
                                        // items means that separator chars
                                        // may be contained in target element
                                        // names.
            $arrTokens = explode($this->strDirectiveSeparator,$strVldName,3);
            if(
                count($arrTokens) < 3 || 
                $arrTokens[0] != $this->strDirectivePrefix
            ) continue;
                                        // currently does not allow for 
                                        // underscores in taget form field names
            $strTargetName = $arrTokens[2];
            if(!array_key_exists($strTargetName,$arrReq)) {
                $msg = "VALIDATOR PROCESSOR ERROR: ".$strVldName.
                " Specifies a form element which is missing from " .
                "the posted form: ".$strTargetName;
                $this->XaoThrow($msg,debug_backtrace());
                continue;
            }
            $strTargetValue = $arrReq[$strTargetName];
            $strVldDirective = $arrTokens[1];
            $strMethodName = $this->strDirectivePrefix.
                $this->strDirectiveSeparator.$strVldDirective;
            if(method_exists($this,$strMethodName)) {
                $this->$strMethodName(
                    $strVldName,
                    $strVldMsg,
                    $strTargetName,
                    $strTargetValue
                );
            }
            else {
                $msg = "VALIDATOR PROCESSOR ERROR: ".$strVldName." contains ".
                    "an unsupported form validation directive: ".
                    $strVldDirective;
                $this->XaoThrow($msg,debug_backtrace());
            }
        }
    }
    
    /** 
     * Custom exception raiser for form validation failures
     * 
     * This not only provides a standard "type" attribute for all validation
     * exceptions, it also produces side-effects like populating 
     * $this->arrValidationErrors[]. Bottom line, do not bypass this function
     * using XaoThrow(). You should always use it to throw validation failure
     * messages.
     * 
     * @param    string    validation failure message
     * @return    void    
     */
    protected function ThrowValidationFailure($msg) {
        if(!$msg || !is_string($msg)) return;
        $this->arrValidationErrors[] = $msg;
        $this->XaoThrow(
            $msg,
            debug_backtrace(),
            array("type" => "validation"),
            $_POST
        );
    }
    
    /**
     * Return a list of paramters extracted from the validation specification
     * 
     * @param    string    The string used as the value for the hidden VLD_ field
     * @return    array    A list of parameters used by the validating methods
     */
    protected function arrGetParams($strVldMsg) {
        $arrRes = $this->arrParamParse($strVldMsg);
        if(!is_array($arrRes) || !array_key_exists("params",$arrRes)) {
            $this->XaoThrow(
                "InputValidtor->arrParamParse(): did not return an expected".
                " value.",
                debug_backtrace()
            );
            return false;
        }
        if($this->blnDebug) {
            if(count($arrRes["info"]) > 1) {
                $this->XaoThrow(implode("<br/>\n",$arrRes["info"]));
            }
        }
        return $arrRes["params"];
    }
}
