<?php
/**
 * Exceptions.php
 * 
 * This script provides exception-specific messaging for content based classes.
 * It is designed to be used in conjunction with DomDoc based classes. There is
 * no need for the developer to concern themselves with this class since DomDoc
 * uses it in DomDoc::XaoThrow(). However, it is possible to use this class from
 * anywhere - even outside XAO. It is well encapsulated and decoupled.
 *
 * @see      DomDoc::XaoThrow()
 */

/**
 * Utility for managing massages relating to exceptional conditions (errors)
 * 
 * This class is used to produce and manage error messages for exceptional
 * conditions as XML data in a DomDoc based object. It also stores debugging
 * data wich may optionally be displayed by an appropriate stylesheet. All data
 * is managed directly on the DOM tree (rather than maintaining a dedicated
 * stack) of the calling object to ensure it's availability without having to
 * notify a separate stack manager when the application needs the data. As such,
 * this class requires a stub (node) on the DOM tree represending the "errors"
 * element where it will mainatain error data.
 *
 * @author       Terence Kearns
 * @version      0.0
 * @copyright    Terence Kearns 2003
 * @license      LGPL
 * @package      XAO
 * @link         http://xao-php.sourceforge.net
 */
// TODO: sort out the Exception class so that it aligns with PHP5 architecture
class   Xao_NonUser_Exceptions 
extends Xao_Root 
{
    /**
     * A reference to the original DomDoc object containing the errors
     * 
     * @var        object
     */
    private $objDoc;
    
    /**
     * A reference to the node in which the errors are grafted
     * 
     * @var        object
     */
    private $ndErrors;
    
    /**
     * Whether or not to create a stack trace
     * 
     * @var        bool
     */
    public $blnCreateStackTrace = true;

    /**
     * XML Namespace Prefix
     * 
     * Element name to use for each exception.
     * 
     * @access   public
     * @var      string
     */
    private $strElName;
    
    /**
     * Hash list of metadata to provide supportive context for error messages
     * 
     * This is intended to be used by methods that override $this->XaoThrow().
     *
     * @var      array
     */
    public $arrErrAttribs = array();

    /**
     * XaoExceptions constructor
     * 
     * Simply obtain references to the calling document and keep them locally
     * (global to the class).
     * 
     * @param   dom ref     document where the error data is to be grafted.
     * @param   node ref    the stub onto which the error grafted
     * @param   string        the name of the element used to house exceptions
     * @return  void
     */
    public function __construct(
        $objDoc,
        $ndErrors,
        $strElName = "exception", 
        $idNamespace = ""
    ) {
        $this->objDoc             = $objDoc;
        $this->ndErrors           = $ndErrors;
        $this->strElName          = $strElName;
    }
    
    /**
     * Exceptional message setter method
     * 
     * @param   string     current message for current exceptional condition
     * @return  void
     */
    public function SetMessage($strMsg) {
        $strMsg = trim($strMsg);
        if(strlen($strMsg)) {
            $this->strError = $strMsg;
        }
        else {
            $this->strError = 
                "XaoExceptions::SetMessage - The error message was empty.";
        }
    }
    
    /**
     * Exceptional message setter method
     *
     * This method resets the current array of error message attributes to the
     * associative array passed in the first argument. The array is NOT
     * appended.
     * 
     * @param   array     copy associative array of error message attributes
     * @return  void
     */
    public function SetMsgAttribs($arrAttribs) {
        $this->arrErrAttribs = $arrAttribs;
    }
    
    /**
     * General purpose attribute setting utility
     * 
     * @param   string     name of the attribute to be set
     * @param   string     value of the attribute to be set
     * @return  void
     */
    public function SetMsgAttrib($strAttName,$strAttVal) {
        $strAttVal = trim($strAttVal);
        if(strlen($strAttVal)) $this->arrErrAttribs[$strAttName] = $strAttVal;
    }
    
    /**
     * Execution method for created the actual error data
     * 
     * @return  node    A reference to the new DOM node created.
     */
    public function ndCreateError($arrBt = null) {
                                        // create the document node for this 
                                        // error
        $elError = $this->objDoc->createElement($this->strElName);
        $ndError = $this->ndErrors->appendChild($elError);
        $elMsg   = $this->objDoc->createElement("msg");
        $ndMsg   = $ndError->appendChild($elMsg);
                                        // populate it with the main message        
        if(strlen($this->strError)) {
            $ndMsg->appendChild($this->objDoc->createTextNode($this->strError));
        }
        else {
            $strMsg = 
                "XaoExceptions::ndCreateError - No error message has been set.";
            $ndMsg->appendChild($this->objDoc->createTextNode($strMsg));
        }
                                        // set up any attributes
        foreach($this->arrErrAttribs AS $attName => $attVal) {
            if(is_numeric($attName)) $attName = "UnNamedNumericAttribute_"
                                              . $attName;
            if(strlen($attVal)) $ndError->setAttribute($attName,$attVal);
        }
                                        // include stack trace if required
        if($this->blnCreateStackTrace) 
            $this->_CreateStackTrace($ndError,$arrBt);
        
        return $ndError;
    }
    
    /**
     * Method for creating verbose stack trace data on DOM tree
     * 
     * @return  node    A reference to the error DOM node.
     * @return  void
     */
    private function _CreateStackTrace($ndErr,$arrBt = null) {
                                    // only produce elements if there is
                                    // valid content
        if(!is_array($arrBt) || !count($arrBt)) return;
        
        $elBt = $this->objDoc->createElement("stack");
        $ndBt = $ndErr->appendChild($elBt);
                                        // this will create a nested call
                                        // element for each function call stored 
                                        // in the backtrace array.
        try{
            foreach($arrBt AS $arrCall) {
                $elCall = $this->objDoc->createElement("call");
                if(!is_array($arrCall)) continue;
                foreach($arrCall AS $attCall => $valCall) {
                    if(is_array($valCall)) {
                        if(count($valCall)) {
                            $elArgs = $this->objDoc->createElement("args");
                            $ndArgs = $elCall->appendChild($elArgs);
                            foreach($valCall AS $attArg=>$valArg) {
                                if(!is_string($valArg)) {
                                    if(is_object($valArg)) 
                                        $valArg = get_class($valArg);
                                    if(is_array($valArg)) {// delve no further
                                        $tmp = "";
                                        foreach($valArg AS $va2) {
                                            if(is_array($va2)) {
                                                $tmp.="[".implode(",",$va2)."]";
                                            } else {
                                                $tmp.=$va2.",";
                                            }
                                        }
                                        $valArg = $tmp;
                                    }
                                        // all other data types SHOULD be able
                                        // to be coerced into strings
                                    $valArg = "[".$valArg."]";
                                }
                                        
                                
                                if(strlen(trim($valArg)) && $valArg != "[]") {
                                        // attempt to show content of argument
                                    $elItem = 
                                        $this->objDoc->createElement("item");
                                    
                                    if(strlen($valArg) > 256)
                                        $valArg = substr($valArg,0,255)."...";
                                    
                                    $elItem->appendChild(
                                        $this->objDoc->createTextNode($valArg)
                                    );
                                    $ndItem = $ndArgs->appendChild($elItem);
                                }
                                
                            } // end foreach on call arguments
                        }
                    }
                    else {
                                        // translate backtrace array keys/vals
                                        // to atribute name/vals for each CALL
                                        // element
                        if(is_object($valCall)) {
                            $valCall = get_class($valCall);
                        }
                        if(!is_string($attCall)) $attCall = "call".$attCall;
                        $elCall->setAttribute($attCall,$valCall);
                    }
                }
            } // end foreach on call attributes
        }
        catch(Exception $e) {
            die($e->getMessage());
        }
                                    // attache the CALL element to the
                                    // stack element.
        if(isset($ndCall)) {
                                // nest this element under the previous
                                // CALL element if one already exists
            $ndCall = $ndCall->appendChild($elCall);
        }
        else {
                                // create the start CALL element node.
            $ndCall = $ndBt->appendChild($elCall);
        }
    }
}