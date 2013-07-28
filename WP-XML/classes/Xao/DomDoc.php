<?php
/**
 * DomDoc.php
 * 
 * This script provides the class definition for DomDoc. Since the DomDoc class
 * provides the basis for XAO, all the requirements checks for XAO are done
 * first up in this script. In general, however, all the code in XAO is object
 * oriented. For more information on the DomDoc class itself, see the doc
 * comment directly preceding the class declaration.
 *
 * @author       Terence Kearns
 * @version      1.0 alpha
 * @copyright    Terence Kearns 2003
 * @license      LGPL
 * @link         http://xao-php.sourceforge.net
 * @package      XAO
 */

/**
* General purpose DOM class
*
* This class provides three forms of functionality. 1) shortcut functions to 
* operations made tedious by the DOM API. 2) additional features not supported
* by the DOM API. 3) a thread-safe way of interacting with files associated 
* with the class' DOM document.
*
* @package      XAO
*/
class   Xao_DomDoc 
extends Xao_Root 
{

    /**
    * Singleton instance of Xao_NonUser_Exceptions object.
    *
    * This is only instantiated if $this->XaoThrow() is called. This object 
    * encapsulates error data management. It keeps all the error data on the
    * referenced DOM doc rather than a stack local to this class.
    * 
    * @var      object  
    */
    private $objErr;

    /**
    * Element containing the last(current) error message.
    *
    * This is populated by $this->XaoThrow() and is always appended to the root
    * node. in order for consumed DOM documents to have their errors displayed
    * the consume function of the context DomDoc needs to search for these and 
    * copy them to the root node of itself.
    * 
    * @var      node  
    */
    protected $ndErr;

    /**
    * An instance of the main DOM XML object.
    * 
    * The native PHP DOM XML object is kept here. Any PHP DOM methods may
    * be accessed directly from this object. For instance, 
    * $objMy->objDoc->getElementById(); The user can also pass this member
    * to functions requiring a native PHP DOM XML object. It is important to
    * note that the XAO API in no way limits the user's access to PHP's built-in
    * functions.
    *
    * @var      object  
    */
    public $objDoc;

    /**
    * Document root node
    * 
    * This object variable conains a reference to the root element node
    * of this DomDoc. It's a handy shortcut to $this->objDoc->document_root()
    * because it is used a lot.
    *
    * @var      node  
    */
    public $ndRoot;
        
    /**
    * How has the document been instantiated.
    * 
    * This attribute remembers the value of the mode constant that was 
    * used to instantiate this DomDoc object.
    *
    * @var      integer from constant  
    */
    public $intDocMode;
    
    /**
    * The name of each row element. Used by ndHashToXml
    *
    * @var      string  
    */
    protected $strRowEl               = "row";
    
    /**
    * Queue of element objects to be procssed
    * 
    * Users can use the SetCustomTagName() function to nominate elements by
    * name to be kept in this list. The method also requires the name of a 
    * valid function to do the processing.
    *
    * @var      integer from constant  
    */
    private $_arrCustomTagNames = array();
    
    /**
    * Queue of query result node objects to be procssed
    * 
    * Users can use the SetCustomTagQuery() function to find nodes to be kept 
    * in this list. The method also requires the name of a valid function to do 
    * the processing.
    *
    * @var      integer from constant  
    */
    private $_arrCustomTagQueries = array();

    /**
    * Constructor method 
    *
    * Create the objDoc instance property and associated ndRoot property based
    * on the user-selected mode of document creation. 
    *
    * @param    mixed   information required to create a DOM document
    * @param    int     constant specifying how the document is to be created
    * @return   void
    */
    public function __construct($mxdData,$intUse = XAO_DOC_NEW) {

        $this->intDocMode = $intUse;
                                    // for more info on each case block, see
                                    // comments in the constant definitions
                                    // at the top of this file.
        if($this->intDocMode == XAO_DOC_NEW) {
            if(!$this->blnTestSafeName($mxdData)) return;
            $this->objDoc = new DOMDocument("1.0");
            $elRoot = $this->objDoc->createElement($mxdData);
            $this->ndRoot = $this->objDoc->appendChild($elRoot);
        } 
        elseif($this->intDocMode == XAO_DOC_READFILE) {
            if(!file_exists($mxdData)) {
                $this->_AbortDocument("file (".$mxdData.") does not exist.");
            }
            else {
                $this->objDoc = $this->objGetDomFactoryData($mxdData);
            }
            
        }
        elseif($this->intDocMode == XAO_DOC_DATA) {
            $this->objDoc = $this->objGetDomFactoryData($mxdData);
        }
        elseif($this->intDocMode == XAO_DOC_REFERENCE) {
            if(is_object($mxdData) && is_a($mxdData,"DOMDocument")) {
                $this->objDoc = $mxdData;
            }
            else {
                $this->_AbortDocument(
                    "DomDoc::__construct expects a valid DOMDocument object"
                    ." instance when using XAO_DOC_REFERENCE"
                );
            }
        }
        else {
            $this->_AbortDocument(
                "The second argument to DomDoc constructor is invalid."
            );
        }
        $this->ndRoot = $this->objDoc->documentElement;
    }
    
    /**
     * Custom namespace (recommended). This must be called AFTER
     * the superclass constructor is called
     * (ie. the DOM document is established).
     *
     * @param    string    Namespace URI
     * @param    string    If not specified, the DEFAULT ns is assumed.
     *
     * @return   void
     */
    public function IntroduceNs($strNamespaceURI,$strPrefix = '') {
        if(!$strPrefix) $strPrefix = 'xmlns';
        else $strPrefix = 'xmlns:'.$strPrefix;
        // The custom namespace URI has to be
        // 'introduced' into the document using
        // the W3 uri, but susequent calling is
        // different. See example in manual
        $this->ndRoot->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            $strPrefix,
            $strNamespaceURI
        );
    }
    
    
    /**
     * Get processing instruction nodes of a cirtian name
     * 
     * You can optionally specify a string to match the content of the Pi in
     * which case the list is filtered to that.
     * 
     * @param    string    name of the PI
     * @param    string    string to match the PI content
     */
    public function arrNdGetPis($strTarget,$strContent = null) {
        $objChildren = $this->objDoc->childNodes;
        $arrNdTargets = array();
        if(!is_object($objChildren)) {
            $this->XaoThrow(
                "DomDoc->arrNdGetPis(): Could not obtain document children",
                debug_backtrace(),null,true
            );
            return;
        }
        foreach($objChildren as $node) {
            if(
                $strContent !== null 
                && $strContent != $node->nodeValue
            ) {
                continue;
            }
            if(
                $node->nodeType == XML_PI_NODE 
                && $node->nodeName == $strTarget
            ) {
                                        // do not assign by reference here.
                                        // there is a bug which will cause the
                                        // wrong node to be pointed to.
                $arrNdTargets[] = $node;
            } 
        }
        return $arrNdTargets;
    }
            
    /**
    * Abort document initialisation and instantiate an error document instead.
    * 
    * If something goes wrong in the initialisation process, the creation of a
    * document is aborted and a token error document is initialised instead.
    * Ordinarily, the $this->XaoThrow() method is used to raise errors, however 
    * if the initialisation process is not complete, then $this->XaoThrow() will 
    * not work. This function ensures that a document is always created and
    * then it calls the throw function.
    * 
    * @param   string   Error message to be contained in the error root element
    * @return  void
    */
    protected function _AbortDocument($strErrMsg) {
                                        // produce a basic documemnt so that we
                                        // have enough to throw an error.
        $this->objDoc = new DOMDocument("1.0");
        $ndRoot = $this->objDoc->createElement("abortedDoc");
        $this->ndRoot = $this->objDoc->appendChild($ndRoot);
        $this->XaoThrow(
            $strErrMsg,
            debug_backtrace()
        );
    }
    
    public function WarnToXml($msg,$arrAttribs = array()) {
        $ndWarn = $this->ndAppendToRoot("warning");
        $ndWarn->appendChild($this->objDoc->createTextNode($msg));
        if(is_array($arrAttribs) && count($arrAttribs)) {
            foreach($arrAttribs as $key => $val) {
                $ndWarn->setAttribute($key,$val);
            }
        }
    }
    
    
    /**
    * Base error logger
    * 
    * All DomDoc based objects should use this method to raise errors. The 
    * method will not stop execution. It will create elements on the DomDoc
    * tree containing all the error data available. It is up to the stylsheet
    * to extract and render error information through an appropriate template.
    * Users should note the ability to define a custom call-back function which
    * may be created as a method in the child object. To do this, populate
    * $this->strErrCallbackFunc with the name of your custom error method.
    * To find out more about how the exception elements are populated, check
    * out the documentation in the XaoExceptions class.
    * 
    * @param   string  Main error message for display
    * @param   array   A hash of attributes/values to include in error element
    * @return  void
    */
    public function XaoThrow(
        $strErrMsg, $arrBt = null, $arrAttribs = null, $arrPayload = null
    ) {
        if(!is_array($arrAttribs)) $arrAttribs = array();
        if(!is_array($arrPayload)) $arrPayload = array();
        parent::XaoThrow($strErrMsg,$arrBt,$arrAttribs,$arrPayload);
                                        // obtain singleton error object if it
                                        // does not already exist.
        if(!is_object($this->objErr)){
                                        // set up the error node to pass to the
                                        // XaoExceptions constructor. Ensure that
                                        // all the contents have a default
                                        // namespace in XAO
            $ndXaoExceptions = $this->ndAppendToRoot("exceptions");
                                        // NAMESPACES STILL NOT IMPLEMENTED YET!
            /* $ndXaoExceptions = $this->objDoc->createElementNS(
                $this->idXaoNamespace,
                "exceptions"
            );*/
            $this->objErr = 
                new Xao_NonUser_Exceptions($this->objDoc, $ndXaoExceptions, "exception");
        }
                                        // the XaoExceptions class is not much use
                                        // without populating this.
        $this->objErr->SetMessage($this->strError);
                                        // optional extras go here.
        $this->objErr->SetMsgAttribs($arrAttribs);
                                        // This is where all the action occurs
                                        // in the XaoExceptions class. See the Doc
                                        // comments in that class for details.
        $this->ndErr = $this->objErr->ndCreateError($arrBt);
        if(isset($this->ndRoot) && is_object($this->ndRoot)) {
            $arrNd = $this->ndRoot->getElementsByTagName("exceptions");
            if($arrNd->length && count($arrPayload)) {
                $ndXaoExceptions = $arrNd->item(0);
                $arrNd = $this->ndRoot->getElementsByTagName("Post");
                if(!$arrNd->length) {
                    $ndPost = $this->ndAppendToNode($ndXaoExceptions,"Post");
                    if(
                        !array_key_exists(
                            "validation_post_referer",
                            $arrPayload
                        )
                    ){
                        if(
                            array_key_exists(
                                "HTTP_REFERER",
                                $_SERVER
                            )
                        ) {
                            $arrPayload["validation_post_referer"] =
                                $_SERVER["HTTP_REFERER"];
                        } else {
                            $arrPayload["validation_post_referer"] = "";
                        }
                    }
                    $ndPost->setAttribute(
                        "referrer",$arrPayload["validation_post_referer"]
                    );
                    foreach($arrPayload AS $key => $val) {
                        if(!is_string($key)) continue;
                        if(substr($key, 0, 4) == "VLD_") continue;
                        $val = htmlentities($val);
                        $ndParam = $this->ndAppendToNode($ndPost,"Param",$val);
                        $ndParam->setAttribute("name",$key);
                    }
                }
            }
        }
    }
        
    /**
     * Serialise and return the entire document object as stand-alone XML.
     *This is used when the entire XML document is required in ASCII format.
     * 
     * @return  xml     document
     */
    public function xmlGetDoc() {
        $this->_TestForConstuctor();
        $this->objDoc->formatOutput = true;
        return $this->objDoc->saveXML();
    }

    /**
    * Serialise and return the entire document as an XML fragment.
    * 
    * This is used when an ASCII version of the XML document is required 
    * _without_ any XML declaration or processing instructions. Everything
    * below and including the root element is serialised.
    *
    * @return  xml     fragment
    */
    public function xmlGetFrag() {
        $this->_TestForConstuctor();
        return "\n\n".$this->objDoc->saveXML($this->ndRoot)."\n\n";
    }
    
    /**
    * Serialise and return an element node as an XML fragment.
    * 
    * This is used when an ASCII version of the XML document is required 
    * _without_ any XML declaration or processing instructions.
    *
    * @param   node
    * @return  xml     fragment
    */
    public function xmlGetNodeFrag($nd) {
        $this->_TestForConstuctor();
        if(!is_object($nd)) {
            $this->XaoThrow(
                "xmlGetNodeFrag: argument is not a node object.",
                debug_backtrace()
            );
            return;
        }
        if(get_class($nd) != "domelement") {
            $this->XaoThrow(
                "xmlGetNodeFrag: argument is not a valid domelement.",
                debug_backtrace()
            );
            return;
        }
        return "\n\n".$this->objDoc->saveXML($nd)."\n\n";
    }

    /**
    * Set the name for all the row tags.
    *
    * @param    string  Name to use for the root result tag
    * @return   void
    */
    public function SetRowTagName($strName) {
        if($this->blnTestSafeName($strName)) {
            $this->strRowEl = $strName;
        }
        else {
            $this->XaoThrow(
                "DbToXml: ".$strName." is not a valid name for an XML tag.",
                debug_backtrace()
            );
        }
    }
    
    public function ndHashToAttribs($ndStub,$arrAttribs) {
        foreach($arrAttribs AS $name => $value) {
            if($this->blnTestSafeName($name)) {
                $ndStub->setAttribute($name,$value);
            }
        }
    }
    
    public function ndArrayToXml(
        $ndStub,
        $arrRow,
        $strRowName = "items",
        $strItemNames = "item"
    ) {
        if(!is_array($arrRow)) return;
        $ndRow = $ndStub->appendChild(
            $this->objDoc->createElement($strRowName)
        );
        foreach($arrRow As $value) {
            $this->ndAppendToNode($ndRow,$strItemNames,$value);
        }
    }
    
    /**
    * Build a set of tags from a hash.
    *
    * @param    node    stub node
    * @param    hash     (associative array) of tag/values to build
    * @param    string    the name of the containing element node
    * @param    hash    associative array mapping field names to function names
    * @param    bool    whether or not to force all field tag names to lowercase
    * @return   node    a reference to the containing element node
    */
    public function ndHashToXml(
        $ndStub,
        $arrRow,
        $strRowName = null,
        $arrCallBacks = null,
        $blnCaseFold = false
    ) {
        if(!is_array($arrRow) || !count($arrRow)) return;
        if(!is_string(key($arrRow))) {
            $this->XaoThrow(
                "You tried to pass a linear array instead of a hash",
                debug_backtrace()
            );
            return;
        }
        if(!is_array($arrCallBacks)) $arrCallBacks = array();
        if(!$strRowName) $strRowName = $this->strRowEl;
        $ndRow = $ndStub->ownerDocument->createElement($strRowName);
        $ndRow = $ndStub->appendChild($ndRow);
                                    // in case this method is overriden
        if(method_exists($this,"RowConstructor")) $this->RowConstructor($ndRow);
                                    // iterate through the fields in the row
        foreach($arrRow AS $fieldName => $fieldVal) {
            if($blnCaseFold) $fieldName = strtolower($fieldName);
                                    // DON'T CREATE EMPTY TAGS!
            if(strlen($fieldVal) && !is_int($fieldName)) {
                                    // add an element for each non-empty field
                $ndField = $ndRow->appendChild(
                    $ndRow->ownerDocument->createElement($fieldName)
                );
                                    // CHECK FOR CALLBACKS
                if(array_key_exists($fieldName,$arrCallBacks)) {
                    $funcName = $arrCallBacks[$fieldName];
                    if(method_exists($this,$funcName)) {
                        $fieldVal = $this->$funcName($ndField,$fieldVal);
                    }
                    else {
                        if(!$this->blnThrewFuncXcptn) {
                            $this->blnThrewFuncXcptn = true;
                            $this->XaoThrow(
                                $funcName." specified in arrCallBacks is"
                                ." not a member function of "
                                .get_class($this),
                                debug_backtrace()
                            );
                        }
                    }
                }
                $ndField->appendChild($ndField->ownerDocument->createTextNode($fieldVal));
            }
        }
                                    // in case this method is overriden
        if(method_exists($this,"RowDestructor")) $this->RowDestructor($ndRow);
        return $ndRow;
    }
    
    public function Wrap($arrWrapper, $strMatchKey) {
        if(!array_key_exists($strMatchKey,$arrWrapper)) {
            $this->XaoThrow(
                "Cannot find an array key named ".$strMatchKey,
                debug_backtrace()
            );
        }
        $objDoc = new DOMDocument("1.0");
        $elRoot = $objDoc->createElement($this->ndRoot->tagName);
        $ndRoot = $objDoc->appendChild($elRoot);
        foreach($arrWrapper As $key => $value) {
            $nd = $ndRoot->appendChild($objDoc->createElement($key));
            if($strMatchKey == $key) {
                $ndList = $objDoc->importNode($this->ndRoot,true)->childNodes;
                for($i = 0; $i < $ndList->length; $i++) {
                    $nd->appendChild($ndList->item($i));
                }
            }
            else {
                $nd->appendChild($objDoc->createTextNode($value));
            }
        }
        $this->objDoc = $objDoc;
        $this->ndRoot = $ndRoot;
    }
        
    /**
    * mass storage serialisation
    *
    * This function will dump the ASCII version of this XML document [in it's
    * current state] to a specified file.
    *
    * @param    uri     path to destination file
    * @param    string  alternate payload
    * @return   void
    */
    public function CommitToFile($uriDestination,$strData = null) {
        $this->_TestForConstuctor();
        if(!file_exists($uriDestination)) {
            $this->XaoThrow(
                    "CommitToFile: ".$uriDestination." was not found.",
                    debug_backtrace()
                );
            return;
        }
        ob_start();
            $fp = fopen($uriDestination,"w+")
                or $this->XaoThrow(
                    "CommitToFile: could not open ".$uriDestination
                    ." for writing. Check permissions or read-only flags."
                    ,debug_backtrace()
                );
            flock($fp,LOCK_EX)
                or $this->XaoThrow(
                    "CommitToFile: Could not get an exclusive lock on "
                    .$uriDestination." for writing"
                    ,debug_backtrace()
                );
            if($strData === null) $strData = $this->xmlGetDoc();
            fwrite($fp,$strData)
                or $this->XaoThrow(
                    "CommitToFile: could write to ".$uriDestination
                    ,debug_backtrace()
                );
            flock($fp,LOCK_UN);
            fclose($fp);
        $strDebugData = ob_get_contents();
        ob_end_clean();
        if($strDebugData) $this->XaoThrow(
            "CommitToFile: PHP reported errors working with ".$uriDestination
            ,debug_backtrace()
        );
    }
    
   /**
    * fetch a single element node by name
    *
    * A convenience function for fetching a node reference to an element by
    * specifying only it's name.
    *
    * @param    uri     name of the element whose node is to be returned
    * @param    integer index of which node to return (0 for first)
    * @return   node
    */
    public function ndGetOneEl($strName,$intIdx=0) {
        $this->_TestForConstuctor();
        $arrNds = $this->objDoc->getElementsByTagName($strName);
        if($intIdx < $arrNds->length) return $arrNds->item($intIdx);
        return false;
    }

    /**
     * This is congruent to a singleton factory method in the context of DOM 
     * elements instead of OO object instances. In this case, a single child node of
     * a partiular tagName is ensured. If the tag does not exist, it is created. If 
     * it does exist, then it is found and returned. This method features the ability
     * to address nodes deep below the context node by specifying a dot-separated
     * list of elements to nominate the branches to search/ensure.
     * 
     * @param $strName      The name of the tag to find or create. Can be a dot
     *                      separated list if you wish to drill down.
     * @param $ndContext    The DOM Element under which to search for immediate 
     *                      children
     * @return DOMElement   The item shich is sought after
     */
    public function ndGetFirstChild($strName, $ndContext) {
        $arrEls = explode(".",$strName);
        $ndResult = null;
        $exists = false;
        foreach($arrEls AS $strName) {
            $exists = false;
            foreach($ndContext->childNodes AS $elChild) {
                if(
                    $elChild->nodeType == XML_ELEMENT_NODE 
                    && $elChild->tagName == $strName
                ) {
                    $ndResult = $elChild;
                    $exists = true;
                    break;
                }
            }
            if(!$exists) {
                $ndResult = $this->ndAppendToNode($ndContext,$strName);
            }
            $ndContext = $ndResult;
        }
        return $ndResult;
    }

   /**
    * quickly add a new element under the root element.
    *
    * This function is basically a shortcut for the common task of adding a new
    * element with some content under the root element of the document.
    *
    * @param    string  the name of the new element
    * @param    string  the content of the new element
    * @return   node    the newly added element node object
    */
    public function ndAppendToRoot($strElName,$strCont = "") {
        $this->_TestForConstuctor();
        if(!$this->blnTestSafeName($strElName)) {
            if(!is_string($strElName)) $strElName = "[object]";
            $this->XaoThrow(
                    "ndAppendToRoot: ".$strElName
                    ." Is not a valid element name.",
                    debug_backtrace()
                );
            $elNew = $this->objDoc->createElement("XAO_NODE_ERROR");
            $ndNew = $this->ndRoot->appendChild($elNew);
            return $ndNew;
        }
        $elNew = $this->objDoc->createElement($strElName);
        $ndNew = $this->ndRoot->appendChild($elNew);
        $ndNew->appendChild($this->objDoc->createTextNode($strCont));
        return $ndNew;
    }

    /**
    * quickly add a new element under an exising element node.
    *
    * This function is basically a shortcut for the common task of adding a new
    * element with some content under an existing node of the document.
    *
    * @param    node    a reference to the exisitng element node
    * @param    string  the name of the new element
    * @param    string  the content of the new element
    * @return   node    the newly added element node object
    */
    public function ndAppendToNode($ndStub,$strElName,$strCont = "") {
        $this->_TestForConstuctor();
        if(!$this->blnTestElementNode($ndStub)) {
            $this->XaoThrow(
                "ndAppendToNode: First argument is not a valid element node.",
                debug_backtrace()
            );
            return false;
        }
        if(!$this->blnTestSafeName($strElName)) {
            $this->XaoThrow(
                    "ndAppendToNode: ".$strElName
                    ." Is not a valid element name.",
                    debug_backtrace()
                );
            return false;
        }
        $elNew = $this->objDoc->createElement($strElName);
        $ndNew = $ndStub->appendChild($elNew);
        if($strCont) $ndNew->appendChild(
            $this->objDoc->createTextNode($strCont)
        );
        return $ndNew;
    }

    /**
    * Import a fragment from a foreign PHP DOM XML document
    *
    * This function will import a fragment from a foreign PHP DOM document
    * below the node specified in the first parameter. This function is 
    * especially used by the other Consume methods in this class.
    * At the moment it EXPLOITS the fact that node::replace_node() allows the
    * use of foreign DOM XML objects - this is not in the spec.
    * So this behaviour cannot be relied upon. It's worth noting that there
    * is an xinclude() function which looks like it might be the way to go but
    * documentation is vague http://www.xmlsoft.org/html/libxml-xinclude.html
    * http://www.php.net/manual/en/function.domdocument-xinclude.php
    * in any case, all maintenance for this functionality is centralised at this
    * one point in the XAO api. If neccesary, it may eploy different techniques
    * based on detecting which version of php/domxml is in use. Needless to say
    * that this function is PIVOTAL to the XAO framework concept which uses
    * aggregation to accumulate content through the CONSUME methods.
    *
    * @param    node    the node under which the fragment is to be grafted
    * @param    node    foreign node containing the fragment to be imported
    * @return   node    the newly added element node object
    */
    public function ndImportChildFrag($ndStub,$ndNew,$blnReplace = false) {
        $this->_TestForConstuctor();
        if(!$this->blnTestElementNode($ndStub)) return false;
        if(!$this->blnTestElementNode($ndNew)) return false;
                                        // make the foreign node local
        $ndNew = $this->objDoc->importNode($ndNew,true);
        if($blnReplace) {
            $ndStub->parentNode->replaceChild($ndNew,$ndStub);
        }
        else {
            $ndTmp = $ndStub->appendChild($ndNew);
        }
        return $ndNew;
    }

    /**
    * Import a foreign PHP DOM XML document and append it below $this->ndRoot
    *
    * This function will consume the contents of an entire DOM document and
    * retain it below the root node of this DomDoc.
    *
    * @param    DomDoc  a reference to an exising PHP DOM XML document
    * @param    node    an optional stub node to which the new data is grafted
    */
    public function ndConsumeDoc($objDoc,$ndStub = null,$blnReplace = false) {
        $this->_TestForConstuctor();
        if(!is_object($objDoc)) {
            $this->XaoThrow(
                "ndConsumeDoc: No DomDoc object given",
                debug_backtrace()
            );
        }
        elseif(!isset($objDoc->ndRoot)) {
            $this->XaoThrow(
                "ndConsumeDoc: No root node. First param must be an XAO "
                ."DomDoc, not just a basic PHP DOMXML object. Use the "
                ."DomFactory class if you need to convert an existing PHP "
                ."DOMXML object.",
                debug_backtrace()
            );
        }
        else {
            if(!$this->blnTestElementNode($ndStub)) $ndStub = $this->ndRoot;
            return $this->ndImportChildFrag($ndStub,$objDoc->ndRoot,$blnReplace);
        }
        return false;
    }

    /**
    * Import an XML document from a file and append it below $this->ndRoot
    *
    * This function will consume the contents of an entire XML document from a 
    * file and retain it below the root node of this DomDoc.
    *
    * @param    uri     the location of the XML file
    * @param    node    The ellement under which to append the file
    * @return   node    the node of what was the root element in the target    
    */
    public function ndConsumeFile($uri,$ndStub = null,$blnReplace = false) {
                                        // If there are any parse errors, then
                                        // they will be included in the object
                                        // returned by DomDoc. It's up to the
                                        // stylsheet to extract them.
        $objDoc = new Xao_DomDoc($uri,XAO_DOC_READFILE);
                                        // The new DomDoc is inevitably grafted
                                        // on to this DomDoc - errors and all.
        if(!$this->blnTestElementNode($ndStub)) $ndStub = $this->ndRoot;
        return $this->ndImportChildFrag($ndStub,$objDoc->ndRoot,$blnReplace);
    }
    
    /**
    * Import well-balenced XML data to append below $this->ndRoot
    *
    * This function will consume the contents of some XML data after wrapping
    * it in a root element whos name is specified in the second parameter. The
    * content is then retained under $this->ndRoot. It also adds an XML 
    * declaration in the process
    *
    * @param    xml     Miscellaneous XML data
    * @param    string  The name of the root element
    */
    public function ndConsumeFragData(
        $str, $strRoot, $ndStub = null, $blnReplace = false
    ) {
        $this->_TestForConstuctor();
                                        // this regex needs to be tested!
        if(!$this->blnTestSafeName($strRoot)) return;
                                        // wrap the fragment data in a basic
                                        // XML envelope
        $str = "<"."?xml version=\"1.0\"?".">\n<".$strRoot.">".$str;
        $str .= "</".$strRoot.">";
        $this->ndConsumeDocData($str,$ndStub,$blnReplace);
    }
    
    /**
    * Import well-balenced XML data to append below $this->ndRoot
    *
    * This function will consume the contents of an XML document.The
    * content is then retained under $this->ndRoot
    *
    * @param    xml     Miscellaneous XML data
    */
    public function ndConsumeDocData($str,$ndStub = null,$blnReplace = false) {
        $objDoc = new Xao_DomDoc($str,XAO_DOC_DATA);
        if(!$this->blnTestElementNode($ndStub)) $ndStub = $this->ndRoot;
        return $this->ndImportChildFrag($ndStub,$objDoc->ndRoot,$blnReplace);
    }

    /**
    * Test to see if the DomDoc constructor has been run
    *
    * This needs to be done for the sake of developers who can't figure out why
    * their script dies when inheriting from DomDoc. If $this->DomDoc is 
    * not executed somewhere before one of the other methods on this class is 
    * called, then most of them won't work - including $this->XaoThrow()!!!!!! 
    * This function is designed to check that and broadcast a dirty great
    * message announcing the fact. It's a bit of a hack but it's provided for
    * "extra" safety which should make life easier for the absent-minded
    * developer.
    *
    * @return   void
    */
    protected function _TestForConstuctor() {
                                            // The existance of $this->objDoc is
                                            // garenteed. Even if the constructor
                                            // fails to initialise one, then
                                            // $this->_AbortDocument should be 
                                            // called which provides a surrogate.
        if(!is_object($this->objDoc)) {
            $strThis = "DomDoc";
                                        // try to find out the names of classes
                                        // used to inherit DomDoc and use this
                                        // information to produce a [hopefully]
                                        // helpful warning.
            $strParent = get_parent_class($this);
            $strYoungest = get_class($this);
            $msg = "
                <h1>MASSAGE FOR THE PROGRAMMER: $strThis constructor not called!</h1>
                <p>You are trying to access methods on $strThis without running
                ".$strThis."->DomDoc()</p>
                <p>The immediate parent to $strThis is $strParent . You probably
                need to call ".$strThis."->DomDOc() in it's constructor. PHP
                does not automatically call the constructor of the superclass
                in a sub class's constructor.</p>
            ";
            if($strParent != $strYoungest) {
                $msg .= "
                    <p>If you already called ".$strThis."->DomDOc() from the
                    constructor in $strParent, then you probably didn't call the
                    constructor for $strParent in $strYoungest. Assuming that
                    $strYoungest is indeed a child of $strParent.</p>
                    <p>You're getting this ugly message because $strThis 
                    cannot handle exceptions nicely if it is not instantiated
                    properly.</p>
                    <p>Below is a debug_backtrace() which should help trace
                    where the problem (method call) originated from.</p>
                ";
            }
            $arr = debug_backtrace();
            echo $msg."<pre>";
            var_dump($arr);
            echo("</pre>");
            die("<h3>Script execution terminated.</h3>");
        }
    }
    
    
    /**
    * Turn an associative array into attributes
    *
    * The hash keys are used for the attribute names and the values are used
    * for the attribute values.
    *
    * @param    node
    * @param    array
    * @return    void
    */
    function Arr2Atts(&$ndEl,$arrAttribs) {
        if(!$this->blnTestElementNode($ndEl)) return false;
        foreach($arrAttribs AS $strName => $strValue) {
            try {
                $ndAttrib = $ndEl->setAttribute($strName,$strValue);
            }
            catch(Exception $e) {
                $this->XaoThrow(
                    "Arr2Atts: Could not set attribute using "
                    ."NAME(\"".$strName."\") and VALUE(\"".$strValue."\").",
                    debug_backtrace()
                );
            }
        }
    }
    
    /**
    * Use an XPath to nominate nodes for processing by a call-back function.
    *
    * This functionality is dubious when using namespaces. The experimental
    * nature of PHP's DOMXML extension makes it impossible to guarentee safe
    * usage.
    *
    * @param    string  XPath query
    * @param    string  name of user-defined callback function
    * @return   void
    */
    public function SetCustomTagQuery($strQuery,$fncName) {
        if(method_exists($this,$fncName)) {
            $this->_arrCustomTagQueries[] = array($strQuery,$fncName);
        }
        else {
            $this->XaoThrow(
                "SetCustomTag: Method ".$fncName." is undefined.",
                debug_backtrace()
            );
        }
    }
    
    /**
    * Have all elements of a specified name processed by a call-back function.
    *
    * This functionality is dubious when using namespaces. It still needs to be
    * tested with namespaces.
    *
    * @param    string  name of element to be globally matched
    * @param    string  name of user-defined callback method
    * @return   void
    */
    public function SetCustomTagName($elName,$fncName) {
        if(method_exists($this,$fncName)) {
            if($this->blnTestSafeName($elName)) {
                $this->_arrCustomTagNames[$elName] = $fncName;
            }
            else {
                $this->XaoThrow(
                    "SetCustomTag: ".$elName." is not a valid tag name",
                    debug_backtrace()
                );
            }
        }
        else {
            $this->XaoThrow(
                "SetCustomTag: Method ".$fncName." is undefined.",
                debug_backtrace()
            );
        }
    }
    
    /**
    * Process all nodes (domelements) due for processing.
    *
    * When the user has finished nominating all the nodes for processing using
    * either SetCustomTagName() or SetCustomTagQuery(), then this function can
    * be called. It's a good idea to make sure this is only called as many times
    * as it needs to be (once).
    *
    * @return   void
    */
    public function ProcessCustomTags() {
                                        // process all tag-name call-backs
        foreach($this->_arrCustomTagNames AS $elName => $fncName) {
            $objList = $this->objDoc->getElementsByTagName($elName);
            if(is_object($objList)) {
                foreach ($objList AS $nd) $this->$fncName($nd);
            }
            else {
                $this->XaoThrow(
                    "ProcessCustomTags: there was an error searching for "
                    .$elName." in the document.",
                    debug_backtrace()
                );
            }
        }
                                        // process all xpath query call-backs
        foreach($this->_arrCustomTagQueries AS $arrQryFunc) {
            $strQry  = $arrQryFunc[0];
            $fncName = $arrQryFunc[1];
            $objList = $this->arrNdXPath($strQry);
            if(is_object($objList)) {
                foreach($objList AS $nd) $this->$fncName($nd);
            }
            else {
                $this->XaoThrow(
                    "XPath query ".$strQry." did not work. Unfortunately, the "
                    ."underlying DOMXML function does not give up any error "
                    ."information to pass on. Sorry.",
                    debug_backtrace()
                );
            }
        }
    }
    
    /**
    * Return a list of nodes resulting from an XPath Query
    *
    * This function runs the XPath query and returns an array of nodes matching
    * the results. Unfortunately, xpath_eval() never divulges any error 
    * information. I assume that $objRes->nodeset holds a false value if the
    * query errored.
    *
    * @param  string The XPath query
    */
    public function objXPath($strExpr,$ndContext = null) {
        try {
            $objXPath = new DOMXPath($this->objDoc);
            if(is_object($ndContext)) {
                $objRes = $objXPath->query($strExpr,$ndContext);
            }
            else {
                $objRes = $objXPath->query($strExpr);
            }
        }
        catch(Exception $e) {
            $this->XaoThrow(
                "XPath query \"".$strExpr."\" returned errors:\n".
                $e->getMessage(),
                debug_backtrace()
            );
            return false;
        }
        return $objRes;
    }
    
    /**
    * Test if the supplied node is on object of type "domelement"
    *
    * This function is useful for testing variables that need to be accessed as
    * domelement objects..
    *
    * @param  node   The proposed DOM XML node object to test.
    */
    public function blnTestElementNode($ndEl,$blnThrow = false) {
        if(is_object($ndEl) && get_class($ndEl) == "DOMElement") return true;
        if($blnThrow) {
            $this->XaoThrow(
                "blnTestElementNode: Not a valid DOM element ",
                debug_backtrace()
            );
        }
        return false;
    }

    /**
    * unix timestamp date call-back mutator function
    *
    * This call-back function is placed here for convenience and can be used to
    * produce an element with a more convenient schema for representing a date
    * from a unix timestamp. It also provides a useful example of the call-back
    * capability of this class. What it does is add a set of attributes to the
    * element in context which each represent a conventional date component.
    *
    * @param    object  a reference to the current PHP DOM XML object instance
    * @param    node    a reference to the element node representing a field
    * @param    string  a copy of the text content that would normally be 
    *                   assigned to this field elemeent.
    * @return   void
    */
    protected function unixTsToReadable(&$ndField,$intTs) {
        $intTs = (integer)$intTs;
        if($intTs < 0) return;
        $ndField->setAttribute("unixTS",$intTs);
        $ndField->setAttribute("ODBCformat",date("Y-m-d H:i:s",$intTs));
        $ndField->setAttribute("year",date("Y",$intTs));
        $ndField->setAttribute("month",date("m",$intTs));
        $ndField->setAttribute("day",date("d",$intTs));
        $ndField->setAttribute("hour",date("H",$intTs));
        $ndField->setAttribute("min",date("i",$intTs));
    }

    /**
    * ODBC timestamp date call-back mutator function
    *
    * This call-back function is placed here for convenience and can be used to
    * produce create a unix timestamp from an ODBC compliant timestamp. This 
    * unix timestap is then sent to $this->unixTsToReadable where nice
    * attributes are added :)
    *
    * @param    object  a reference to the current PHP DOM XML object instance
    * @param    node    a reference to the element node representing a field
    * @param    string  a copy of the text content that would normally be 
    *                   assigned to this field elemeent.
    * @return   void
    */
    protected function odbcToReadable($ndField,$odbcTs) {
        if(trim($odbcTs) == "") return;
        if($unixTs = Xao_Util_DT::intIsoDateToUnix($odbcTs)) {
            if($unixTs != -1) $this->unixTsToReadable($ndField,$unixTs);
        }
        // return $odbcTs;
    }
    
    public function ToAtt(
        $ndField,$mxdVal,$strAttName = null,$blnToResult = false
    ) {
        if(!$strAttName) $strAttName = strtolower($ndField->nodeName);
        $ndTarget = $ndField->parentNode;
        if($blnToResult) $ndTarget = $ndTarget->parentNode;
        $ndTarget->setAttribute($strAttName,$mxdVal);
        $ndField->parentNode->removeChild($ndField);
    }

    protected function ToTextNode($ndField,$strData) {
        $ndRow = $ndField->parentNode;
        $ndRow->appendChild($this->objDoc->createTextNode($strData));
        $this->SuppressNode($ndField);
    }
    
    protected function ToCdata($ndField,$strData) {
        $ndField->appendChild($this->objDoc->createCDATASection($strData));
    }

    protected function ndReplaceContent($nd,$strContent) {
        $ndNew = $this->objDoc->createElement($nd->tagname());
        $ndNew = $nd->append_sibling($ndNew);
        $this->SuppressNode($nd);
        $ndNew->appendChild($this->objDoc->createTextNode($strContent));
        return $ndNew;
    }
    
    protected function SuppressNode($ndField,$mxdVal = null) {
        $ndRow = $ndField->parentNode;
        $ndRow->removeChild($ndField);
    }

    protected function _blnValidateEntity(&$objEnt,$strEntName) {
        if(!is_object($objEnt) || !is_a($objEnt,$strEntName)) {
            $this->XaoThrow(
                "Expecting a valid ".$strEntName." object instance."
                ,debug_backtrace()
            );
            return false;
        }
        return true;
    }
    
    public function Send() {
        header("Content-Type: text/xml;");
        echo $this->xmlGetDoc();
        die();
    }

} // END CLASS

