<?php
/**
 * This class is the base class of all native XSLT driver classes.
 */
abstract
class   Xao_Drivers_Xslt_Xslt
extends Xao_Drivers_BaseDriver 
{

    /**
     * Cache parameters for the XSLT tranformation result
     * 
     * In the standard XAO framework, caching can be done at two stages. The
     * first is at the content generation stage which uses $arrDocCacheParams,
     * the second is at the transformation results stage which uses this array.
     * The array has the same requirements and is used in exactly the same way
     * as the $arrDocCacheParams array. Also see doc comments at Xao_Root::
     * arrCacheParams for more info. NOT IMPLEMENTED YET
     * 
     * @var      array
     */
    protected $arrXsltCacheParams = array();

    /**
     * DOM object instance of the source document
     * 
     * This has to be specified in the constructor.
     *
     * @var      object
     */
    protected $objSrc;
    
    /**
     * Whether or not to convert transform output from UTF8 to ISO-5589-1
     * 
     * @access  public
     * @var     bool
     */
    protected $blnUtf8ToWestern = false;
    
    /**
     * DOM instance of XSLT document
     * 
     * @var        object
     */
    protected $objStyle;
    
    /**
     * The stylesheet URI
     * 
     * @var        string
     */
    protected $uriStyleSheet;

    /**
     * List of XSL parameters to be passed to the XSLT processor
     * 
     * Usually these will be set by the application class which inherits DomDoc.
     * The transform methods of this class should use this associative array to
     * add these parameters to the processor. WARNING: RELYING ON THIS METHOD OF
     * SETTING XSL PARAMS WILL MAKE YOUR STYLSHEET INCOMPATIBLE WITH CLIENT-SIDE
     * TRANSFORMATION. IMPORTANT NOTE: YOUR PARAMS WILL NOT BE AVAILABLE IN YOUR
     * STYLSHEET IF YOU DO NOT DECLARE THEM (WITH EMPTY VALUES). THE PROCESSOR
     * WILL ONLY FEED PARAM VALUES TO THE STYLESHEET BY OVERRIDING THE VALUES OF
     * EXISTING ONES.
     *
     * @access   public
     * @var      array
     */
    public $arrXslParams = array();

    /**
     * XSLT processing result container
     * 
     * Regardless of which XSLT processor is used, the result is always stored
     * in this variable AS A STRING.
     *
     * @var      string
     */
    protected $strXsltResult;
    
    /**
     * Whether or not a transformation has completed successfully
     * 
     * @var        bool
     */
    public $blnCompleted = false;
    
    /**
     * The sonstructor function sets up all the required data
     * 
     * @param    object    An instance of the DOM document source XML
     * @param    string    The location of the stylesheet file to use
     * @return    void
     */
    public function __construct($objDoc,$uriStylSheet) {
                                        // sanity checks
        if(!is_object($objDoc) || !is_a($objDoc,"DOMDocument")) {
            if(is_object($objDoc)) $strType = get_class($objDoc);
            else $strType = gettype($objDoc);
            $this->XaoThrow(
                "The XSLT class needs a DOMDocument object instance. "
                .$strType." was passed.",
                debug_backtrace(),null,true
            );
            return;
        }
        if(!file_exists($uriStylSheet)) {
            $this->XaoThrow(
                "XSLT transformer class cannot find stylesheet ".$uriStylSheet,
                debug_backtrace()
            );
            return;
        }
        $this->objSrc = $objDoc;
    }

    /**
     * Add or update an XSL parameter for use when the Transformer is called
     * A local array of the parameters is populated for subsequent transferal to
     * the Transformer class when $this->Transform() is called.
     *
     * @param   string  Name of the XSL parameter or an ossicuative array
     * @param   string  String value of the parameter
     * @return  void
     */
    public function SetXslParam($mxdName,$strValue = null) {
                                        // check to see if we process in 
                                        // associative array mode
        if(!$strValue && is_array($mxdName)) {
            foreach($mxdName AS $name => $value) {
                if($this->blnTestSafeName($name)) {
                    $this->arrXslParams[$name] = $value;
                }
            }
            
        }
                                        // otherwise it's just a single param
        elseif(is_string($mxdName) && $mxdName && $strValue !== null) {
            if($this->blnTestSafeName($mxdName)) {
                $this->arrXslParams[$mxdName] = $strValue;
            }
        }
                                        // whoops!
        else {
            $this->XaoThrow(
                "_XsltDriver::SetXslParam() Invalid arguments.",
                debug_backtrace()
            );
        }
    }
    
    /**
     * Gets the text output of the transform if one has successfully completed.
     * 
     * @return     string    the results of the transform.
     */
    public function strGetXsltResult() {
                                        // We only return a result if processing
                                        // has completed
        if($this->blnCompleted) {
            return $this->strXsltResult;
        }
        else {
            $this->XaoThrow(
                "_XsltDriver::strGetXsltResult() A result cannot be returned"
                ." until a transformation has been completed",
                debug_backtrace()
            );
        }
    } 
    
    /**
     * To be overridden
     */
    public function Transform() {
        $this->__not_implemented();
    }
}
