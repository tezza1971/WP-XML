<?php
/**
* AppDoc.php
*
* This script provides the class definition for AppDoc. AppDoc
* creates the framework for XAO to be used in framwork mode. AppDoc inherits
* DomDoc and AppDoc is intended to be inherited by the end user 
* - to provide the
* user with an "Application Object" that is customised by them. See the doc 
* comments directly preceeding the class declaration for more information.
* It is advisable to consult the tutorials shipped with XAO to obtain an
* understanding of how this class is to be used.
*
* @author       Terence Kearns
* @version      1.0 alpha
* @copyright    Terence Kearns 2003
* @license      LGPL (see http://www.gnu.org/licenses/lgpl.txt )
* @link         http://xao-php.sourceforge.net
* @package      XAO
* @see          class DomDoc
*/

/**
* XML Application Object
*
* AppDoc is the "Application Object" part of the XAO acronym. It represents the
* "Application Document" in the context of XML being a document. So the contents
* delivered to the user by the "Application" is contentained in the "Document"
* held as a DOM XML document by AppDoc::objDoc. the objDoc property is inherited
* from the DomDoc class. The AppDoc class provides extra functionality on 
* DomDoc that is specifically needed for typical "framework mode" usage of XAO. 
* Usage of this class assumes that the user will be employing XAO in framework 
* mode. In short, this is the framework class for XAO. It is advisable to 
* consult the tutorials shipped with XAO to obtain an understanding of how this 
* class is intended to be used.
*
* @package      XAO
*/
class   Xao_AppDoc 
extends Xao_DomDoc 
{
    
    /**
    * Storage slot for alternative payload
    *
    * If this variable is populated, then this is all that is sent to the UA
    * via the AppDoc::Send method. If it is left empty, then the AppDoc::Send
    * method will send the serialised content of DomDoc::objDoc
    * This method is provided as a method of short-circuiting the default
    * behavior of AppDocc::Send - thereby allowing AppDoc::Send to be the 
    * single/only way for which the payload is transmitted. This is very 
    * important because it allows XAO framework to sentralise control script 
    * completion and payload transmission.
    * An example of where an alternate payload is needed is an XSLT 
    * transformation result. Other instances, where the well-formedness of a 
    * payload cannot be garenteed, will also require the use of this string
    * variable.
    * 
    * @var      string  
    */
    public $strAltPayload = null;


    /**
    * Debug option
    *
    * Designed to be used during development, this object will cause error
    * output to be more verbose unser certain conditions. It may also be used
    * by the developer to output diagnostic information at run-time. It is kept 
    * off by default in case it proposes any security issues.
    * 
    * @var      boolean  
    */
    public $strForceContentType;

    /**
    * Whether or not the document has been transformed.
    *
    * If the transformation has not occured, then the http content-type header
    * is set to text/xml if there is no specific content-type enforced.
    * 
    * @var      boolean  
    */
    public $blnTransformed = false;

    /**
    * Current stylesheet URI
    * 
    * This represents the current stylesheet that is used by this DomDoc.
    * If a user overrides the $uriStyleSheet member variable with a populated
    * version, this->ndBuildXslPi() is called with it in the contructor.
    *
    * @var      uri  
    */
    private $uriStyleSheet;

    /**
    * Stylesheet processing instruction node
    * 
    * This is the node object representing the stylesheet PI. It is set using 
    * the ndBuildXslPi() method which only matains one PI node for the 
    * stylesheet. If a user overrides the $uriStyleSheet member variable with 
    * a populated version, this ndBuildXslPi() is called with it in the 
    * contructor.
    *
    * @var      node  
    */
    public $ndStylePi;

    /**
    * Which XSLT processor to use
    * 
    * This option allows the user to choose which implemented XSLT processor
    * to employ by default (which xslt driver to use).
    *
    * @var      string  
    */
    public $strXsltProcessor = XAO_DRIVER_XSLT_TYPE_LIBXSL;
    
    /**
    * Stylesheet native PHP DOM XML object
    * 
    * This variable is populated if the specified stylsheet is successfully
    * opened as an XML document.
    *
    * @var      object
    */
    protected $objTransformer;
    
    /**
    * AppDoc constructor
    *
    * This method runs the parent constructor and sets up the xao namespace.
    * There is no way to detect if a namespace declaration exists 
    * (to prevent duplicates). At the moment, one is inserted regardless!!!
    * This is absolutely neccesary due to their usage by exceptions.
    * WARNING::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    * THIS  MEANS THAT YOU CANNOT IMPORT EXISTING XML FILES WHICH HAVE A
    * THE NAMESPACE xmlns:xao ALREADY DECLARED IN THE ROOT ELEMENT.
    * The current DOMXML extension allows multiple attributes of the same name 
    * to be inserted (not good). It's namespace functions don't allow a 
    * [non-default] namespace declaration to be inserted without changing the 
    * prefix of the tag in context - so we're forced to use the dubious
    * "set_attribute()" method instead.
    *
    * @param    mixed   starting data
    * @param    integer how to use the staring data
    * @return   void
    */
    public function __construct($mxdStarter,$intUse = XAO_DOC_NEW) {
        parent::__construct($mxdStarter,$intUse);
                                        // automatically inject the XAO 
                                        // namespace into the document root
        $this->PutXaoNs($this->ndRoot);
    }
    
    /**
    * Insert stylesheet processing instruction
    *
    * This processing instruction is used by the transform() method if a
    * stylesheet URI is not specifically provided to it. This method will
    * automatically be called in the constructor if the user overrides the 
    * $this->uriStyleSheet member attribute. It may, however, be called at any
    * time by the user. Only one xsl stylsheet PI is maintained in the 
    * document. If it was already set at the time of the call to this method, 
    * then the new stylsheet URI will _replace_ the one in the existing PI.
    *
    * @param    uri     path to XSL stylesheet
    * @param    boolean Whether or not to check(parse) the file.
    * @return   bool    success
    */
    public function ndBuildXslPi($uriStyleSheet,$blnCheck = true) {
        $this->_TestForConstuctor();
        $uriStyleSheet = str_replace("\\","/",$uriStyleSheet);
        if($blnCheck) {
            if(!file_exists($uriStyleSheet)) {
                $this->XaoThrow(
                    "ndBuildXslPi: The stylsheet you specified: "
                    .$uriStyleSheet." does not exist. Set local file "
                    ."checking (parsing) to false in the second argument of "
                    ."DomDoc::ndBuildXslPi() if the file exists remotely or "
                    ."you want to override checking.",
                    debug_backtrace(),
                    null,
                    true
                );
                return false;
            }
        }
        $this->uriStyleSheet = $uriStyleSheet;
        $strPiCont = 'type="text/xsl" href="'.$this->uriStyleSheet.'"';
        $strPiTarget = 'xml-stylesheet';
                                        // test for existing identical PI
                                        // declaration. return that if exists.
        $arrNdPis = $this->arrNdGetPis($strPiTarget,$strPiCont);
        if(count($arrNdPis)) return $arrNdPis[0];
        
        $piStyle = $this->objDoc->createProcessingInstruction(
            $strPiTarget, $strPiCont
        );

        if(!is_object($piStyle)) {
            $this->XaoThrow(
                "ndBuildXslPi: Unable to create processing instruction using "
                ."target of ".$strPiTarget." and content of ".$strPiCont,
                debug_backtrace(),
                null,
                true
            );
            return false;
        }
        else{
                                        // if one exists, replace it
            if(is_object($this->ndStylePi)) {
                $this->objDoc->replaceChild($piStyle,$this->ndStylePi);
            }
                                        // otherwise create it
            else {
                $this->ndStylePi = $this->objDoc->insertBefore(
                    $piStyle, $this->ndRoot
                );
            }
        }
        if(is_object($this->ndStylePi)) {
            return $this->ndStylePi;
        }
        else {
            return false;
        }
    }
    
    /**
    * Get rid of a previously specified stylesheet
    *
    * See doco on ndBuildXslPi() for details on setting this processing 
    * instruction. Theis function checks to see if the style PI has been set 
    * and unlinks (deletes) it if it has been set.
    *
    * @return  void
    */
    public function DropStylePi() {
        if(is_object($this->ndStylePi)) {
            $this->objDoc->removeChild($this->ndStylePi);
        }
    }
    
    protected function objGetTransformer() {
        if(!is_object($this->objTransformer)) {
            $this->SetXsltProcessor();
        }
        return $this->objTransformer;
    }
    
    public function SetXsltProcessor($strXsltType = null,$uriStyleSheet = null) {
        if(!$uriStyleSheet) $uriStyleSheet = $this->uriStyleSheet;
        else $this->ndBuildXslPi($uriStyleSheet);
        if(!$this->uriStyleSheet) {
            $this->XaoThrow(
                "You can't transform anything until you have run
                 objAppDoc->ndBuildXslPi() with the stylesheet specified.",
                debug_backtrace(), null, true
            );
            return;
        }
        if(!$strXsltType) $strXsltType = $this->strXsltProcessor;
        $strClass = "Xao_Drivers_Xslt_".$strXsltType;
        $this->objTransformer = new $strClass(
            $this->objDoc,$this->uriStyleSheet
        );
        $this->intCountErrors($this->objTransformer,true);
    }

    /**
    * Prepares $this->strAltPayload with XSLT transformation result data
    *
    * This function is usually called just prior to $this->Send()
    * It is used when XSLT tranformations are required. It short-circuits the
    * behaviour of $this->Send by populating $this->strAltPayload the results
    * of the transformation. Note that this method requires a cirtain amount of
    * preparation work by the user - ie. a stylsheet must be set using
    * $this->ndBuildXslPi()
    *
    * @return   void
    */
    public function Transform($arrCacheParams = null) {
        if(!$arrCacheParams) $arrCacheParams = array();
        $this->_TestForConstuctor();
        if($this->blnDebug) {
                                        // check for a URL directive
            if(
                array_key_exists("xao:XSL",$_GET) 
                && file_exists($this->uriStyleSheet)
            ) {
                header("Content-Type: text/xml;");
                die(Xao_Util_FS::strGetFileData($this->uriStyleSheet));
                return;
            }
        }
        $objXt = $this->objGetTransformer();
        if(is_array($arrCacheParams) && count($arrCacheParams)) 
            $objXt->arrCacheParams = $arrCacheParams;
                                    // perform the actual transformation and
                                    // pass the results to 
                                    // $this->strAltPayload
        $objXt->Transform();
        if(!$this->intCountErrors($objXt,true)) {
            if($this->strAltPayload = $objXt->strGetXsltResult()) {
                $this->blnTransformed = true;
            }
            else {
                $this->intCountErrors($objXt,true);
                $this->XaoThrow(
                    "The results of the XSL transformation returned empty",
                    debug_backtrace(),null,true
                );
            }
        }
        else {
            Xao_Root::DEBUG($objXt->arrErrors);
        }
    }
    

    /**
    * Send the serialised XML content of this object to the client
    *
    * This function will emit the contents of this XML document as a string to
    * the user-agent. It checks to see if the option was set to bind the error
    * data [built up from usage of $this->XaoThrow()] to the content. It also
    * signals the user agent to expect text in XML format.
    * When XAO is used as a framework, then some sort of send
    *
    * @param    uri     optionally write the output to this file
    * @access   public
    */
    function Send($uriDestination = null) {
        $this->_TestForConstuctor();

                                        // If this object is running in debug
                                        // mode, then special XAO URL directives
                                        // can be acted on.
        if($this->blnDebug) {
                                        // this debug directive causes a source
                                        // dump of the XML content regardless of
                                        // any alternate payload.
            if(array_key_exists("xao:XML",$_GET)) {
                if($this->ndStylePi && is_object($this->ndStylePi))
                    $this->objDoc->removeChild($this->ndStylePi);
                if($_GET["xao:XML"] == "plain") {
                    header("Content-Type: text/plain");
                }
                else {
                    header("Content-Type: text/xml");
                }
                die($this->xmlGetDoc());
            }
                                        // Send the XML as plain text for
                                        // readability
            elseif(array_key_exists("xao:Text",$_GET)) {
                $this->strForceContentType = "text/plain";
            }
                                        // Send the stylesheet file contents as
                                        // XML if the stylesheet is a referenced
                                        // file.
            elseif(array_key_exists("xao:XSL",$_GET)) {
                if(file_exists($this->uriStyleSheet)) {
                    header("Content-Type: text/xml");
                    die(file_get_contents($this->uriStyleSheet));
                }
                else {
                    header("Content-Type: text/plain");
                    die("Cannot locate a stylesheet to display.");
                }
            }
        }
        
        if($this->strAltPayload === null) 
            $this->strAltPayload = $this->xmlGetDoc();
        
        if(strlen($this->strForceContentType)) {
            header(
                "Content-Type: ".$this->strForceContentType
            );
        }
        elseif(substr($this->strAltPayload,1,4) == "?xml") {
            header("Content-Type: text/xml;");
        }
        else {
            header("Content-Type: text/html;");
        }
        if($uriDestination) 
            $this->CommitToFile($uriDestination,$this->strAltPayload);
        
        echo $this->strAltPayload;
    }
    
    /**
     * This method can be used to stipulate a HTTP content type. Once set, it
     * will override any of the ones that this->Send() would calculate.
     * 
     * @param    string    HTTP content type
     * @reutrn     void
     */
    function SetContentType($str) {
        $this->strForceContentType = $str;
    }
    
    /**
     * This is a convenience method used to send the user to a different URL
     * 
     * @param    string    URL location of destination
     * @return    void
     */
    function Redirect($uri) {
        header("Location: ".$uri);
        die();
    }
    
    /**
    * Return data that would be destined for the client (in current state)
    *
    * This function is specific to AppDoc and not DomDoc because of the
    * strAltPayload member attribute. This function can be used by the 
    * RpcController class if the user bases their request class on AppDoc. For
    * instnace, they may want to take advantage of AppDoc's transform
    * capabilities to convernt proprietary content into another format such
    * as RSS.
    *
    * @return   string
    * @access   public
    */
    function strGetPayload() {
        if(strlen($this->strAltPayload)) return $this->strAltPayload;
        return $this->xmlGetDoc();
    }
    
    /**
    * Wrapper for parent::XaoThrow() function adding ability to abort script
    *
    * This function basically calls the parent function of the same name but
    * also allows the caller to optionally abort the script if the last
    * argument is set to true.
    *
    * @param    string Main error message
    * @param    array A hash of name/vals which will be attributes in the 
    *           exception tag
    * @access   public
    */
    function XaoThrow(
        $strErrMsg,
        $arrBt = null,
        $arrAttribs = null,
        $blnDie = false
    ) {
        if($blnDie === true) {
            header("Content-Type: text/plain;");
            print(
                "AppDoc::XaoThrow() received a fourth param - request to die. ".
                "Terminating script. error below:\n\n".$strErrMsg."\n\n"
            );
            if(substr($this->strDebugData,1,4) != "?xml") 
                print($this->strDebugData."\n\n");
            if(!$arrBt) $arrBt = debug_backtrace();
            if(count($this->arrErrors)) var_dump($this->arrErrors);
            $this->_print_bt($arrBt);
            die();
        }
        if(!is_array($arrBt)) $arrBt = debug_backtrace();
        parent::XaoThrow($strErrMsg,$arrBt,$arrAttribs,$blnDie);
    }

} // END CLASS
