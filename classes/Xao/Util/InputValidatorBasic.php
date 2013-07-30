<?php
/**
 * InputValidatorBasic.php
 * 
 * This script is a single point of access to maintain all the XAO validation
 * functionality.
 * 
 * @author       Terence Kearns
 * @version      1.0
 * @copyright    Terence Kearns 2007
 * @license      LGPL
 * @link         http://xao-php.sourceforge.net
 * @package      XAO
 */

/**
 * Container class to maintain generic validation functions supported by XAO.
 * The naming convention for a directive handling call-back function is simple.
 * Assuming the default prefix and separator, the method 
 *     $this->VLD_MyCurrency()
 *     would handle information specified by a form element named like
 *     VLD_MyCurrency_Cost 
 *     where the value of a form variable called "Cost"
 *     should be validated by said call-back. 
 * Note that you may call any particular directive multiple times using a different target field name every
 * time. However having multiple directives of the same type acting on the same
 * target field is not supported and would not make sense. You can also specify
 * the same target element multiple times by using different directive types.
 * This will cause the same value to be checked by multiple call-back functions.
 * If you do this, then the order of functions called will resemble the order in
 * which the directives appear in the form (order of the $_POST vars array) -
 * assuming you are processing $_POST. See the provided VLD_NotNull method for a
 * working example which shows you which directives you are required to support.
 */
class   Xao_Util_InputValidatorBasic 
extends Xao_Util_InputValidator 
{
    /**
     * Wrapper method for the parent constructor
     * 
     * @param    array    optional input array for immediate validation
     */
    public function __construct($arrReq = null) {
        parent::__construct($arrReq);
    }
    
    /** 
     * NotNull directive handler
     * 
     * This built in directive support is provided for your convenience. If you
     * are not happy with the behavior, then you should subclass this class and
     * override it with your own replacement method. At the very least, it
     * provides you with a template which you can copy/paste. Note the four
     * params your directive validator is required to support.
     * 
     * @param    string Full name of the validation directive used
     * @param    string The value data supplied with the directive element
     * @param    string The name of the form field to validate
     * @param    string The content to validate
     * @return     void
     */
    protected function VLD_NotNull(
        $strVldName,
        $strVldMsg,
        $strTargetName,
        $strTargetValue
    ) {
        if(trim($strTargetValue) == "") {
            if(!$strVldMsg) $strVldMsg = $strTargetName;
            $this->ThrowValidationFailure(
                "Empty form field not allowed for ".$strVldMsg
            );
        }
    }
    
    /** 
     * Date directive handler
     * 
     * This built in directive support is provided for your convenience. If you
     * are not happy with the behavior, then you should subclass this class and
     * override it with your own replacement method.
     * 
     * @param   string Full name of the validation directive used
     * @param   string The value data supplied with the directive element
     * @param   string The name of the form field to validate
     * @param   string The content to validate
     * @return    void
     */
    protected function VLD_Date(
        $strVldName,
        $strVldMsg,
        $strTargetName,
        $strTargetValue
    ) {
        $this->XaoThrow(
            $strVldName.": Date validation not implemented yet.",
            debug_backtrace(),
            array("type" => "warning")
        );
    }

    /** 
     * Time directive handler
     * 
     * This built in directive support is provided for your convenience. If you
     * are not happy with the behavior, then you should subclass this class and
     * override it with your own replacement method.
     * 
     * @param   string Full name of the validation directive used
     * @param   string The value data supplied with the directive element
     * @param   string The name of the form field to validate
     * @param   string The content to validate
     * @return    void
     */
    protected function VLD_Time(
        $strVldName,
        $strVldMsg,
        $strTargetName,
        $strTargetValue
    ) {
        $this->XaoThrow(
            $strVldName.": Time validation not implemented yet.",
            debug_backtrace(),
            array("type" => "warning")
        );
    }

    /** 
     * Text Length checker
     * 
     * This validator checks for minimum and/or maximum lengths of text.
     * Checking is based on the existance of the 'min' or 'max' params.
     * 
     * @param   Full name of the validation directive used
     * @param   The value data supplied with the directive element
     * @param   The name of the form field to validate
     * @param   The content to validate
     * @return    void
     */
    protected function VLD_TextLength(
        $strVldName,
        $strVldMsg,
        $strTargetName,
        $strTargetValue
    ) {
        if(!is_array($arrParams = $this->arrGetParams($strVldMsg))) return;
        $msg = ""; 
        if(array_key_exists("msg",$arrParams)) $msg = $arrParams["msg"];
        if(
            !array_key_exists("max",$arrParams) 
            && !!array_key_exists("min",$arrParams)
        ) {
            $this->XaoThrow(
                "VLD_TextLength: expecting params 'max' and/or 'min'."
            );
        }
        if(array_key_exists("max",$arrParams)) {
            $intMax = (integer)$arrParams["max"];
            if(strlen($strTargetValue) > $intMax) {
                
                $this->ThrowValidationFailure(
                    "Form field (".$strTargetName.") exceeds maximum length ".
                    "of ".$intMax.": ".$msg
                );
                return;
            }
        }
        if(array_key_exists("min",$arrParams)) {
            $intMin = (integer)$arrParams["min"];
            if(strlen(trim($strTargetValue)) < $intMin) {
                $this->ThrowValidationFailure(
                    "Form field (".$strTargetName.") does not meet minimum".
                    " length of ".$intMin.": ".$msg
                );
                return;
            }
        }
    }
}
