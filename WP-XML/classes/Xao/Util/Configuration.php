<?php
class   Xao_Util_Configuration 
extends Xao_Root 
{
    
    protected   $_arrParams;
    protected   $_objConfDoc;
    public      $uriConfDoc;
    public      $uriThisCache;
    protected   $_blnInit = false;
    
    /**
    * Process all nodes (domelements) due for processing.
    *
    * When the user has finished nominating all the nodes for processing using
    * either SetCustomTagName() or SetCustomTagQuery(), then this function can
    * be called. It's a good idea to make sure this is only called as many times
    * as it needs to be (once).
    * 
    * @param	uri	Where the configuration document is kept (it's name).
    * @param	uri	If and where the cache file should be kept (recomended).
    */
    public function __construct(
        $uriConfDoc,
        $uriThisCache = null
    ) {
        if(!$uriThisCache) 
            $uriThisCache = Xao_Util_FS::uriMkTempName($uriConfDoc);
        $this->uriConfDoc = $uriConfDoc;
        $this->uriThisCache = $uriThisCache;
        if(!file_exists($uriConfDoc)) {
            $this->XaoThrow(
                "The file (".$this->uriConfDoc.") does not exist",
                debug_backtrace()
            );
            return;
        }
        $this->Init();
    }
    
    public function Init() {
        if(count($this->arrErrors)) return;
                                        // If we can resume params from cache,
                                        // then bug out.
        if($this->_blnTryCache()) {
            $this->_blnInit = true;
            return;
        }
        $this->_objConfDoc = new Xao_DomDoc($this->uriConfDoc,XAO_DOC_READFILE);
        $this->_ReadParams();
        $this->_WriteCache();
        if(!is_array($this->_arrParams) || !count($this->_arrParams)) {
            $this->XaoThrow(
                "Failed to obtain parameters from ".$this->uriConfDoc
            );
            return;
        }
        $this->_blnInit = true;
    }
    
    public function blnCkInit() {
        if(!$this->_blnInit) {
            $this->XaoThrow(
                "The Init() method on the configuration class needs to be " .
                "called. If you know it has and you are still getting " .
                "this message, then initialisaton has failed."
            );
        }
        return $this->_blnInit;
    }
    
    public function mxdGetParam($uri) {
        if(!$this->blnCkInit()) return;
        if(count($this->arrErrors)) return;
        if(!is_array($this->_arrParams) || !count($this->_arrParams)) {
            $this->XaoThrow(
                "mxdGetParam(): Parameter stack is empty. This means your " .
                "config file has not been successfully parsed for " .
                "parameter extraction or your config file is empty. Param " .
                "request was '".$uri."'"
                ,debug_backtrace()
            );
            return;
        }
        $arrBranch = explode("/",$uri);
        $blnSet = false;
        $strTargetVar = '$this->_arrParams';
        $mxdTargetVal = false;
        foreach($arrBranch AS $strIndex) {
            $strTargetVar .= '["'.$strIndex.'"]';
        }
        eval('$blnSet = isset('.$strTargetVar.');');
        if($blnSet) {
            eval('$mxdTargetVal = '.$strTargetVar.";");
        }
        else {
            $this->XaoThrow(
                "Could not find value for ".$uri." in ".$strTargetVar,
                debug_backtrace()
            );
        }
        return $mxdTargetVal;
    }
    
    protected function _WriteCache() {
        if(count($this->arrErrors)) return;
        if(!is_array($this->_arrParams) || !count($this->_arrParams)) return;
        Xao_Util_FS::PutFileData(
            $this->uriThisCache,
            serialize($this->_arrParams)
        );
    }
    
    protected function _ReadParams() {
        if(count($this->arrErrors)) return;
        if(!is_object($this->_objConfDoc)) {
            $this->XaoThrow(
                "The DOM object for the config file is not instantiated. " .
                "Cannot read params.",
                debug_backtrace()
            );
        }
        $lstNodes = $this->_objConfDoc->objXPath("/configuration/params");
        if(!is_object($lstNodes) || !$lstNodes->length) {
            $this->intCountErrors($this->_objConfDoc,true);
            $this->XaoThrow(
                $this->uriConfDoc." is not a valid XAO configuration file. ",
                debug_backtrace()
            );
            return;
        }
        $this->_ReadParamTraverse($lstNodes->item(0),$this->_arrParams);
    }
    
    protected function _ReadParamTraverse($nd,&$arr) {
        if(count($this->arrErrors)) return;
        if(!is_object($nd)) {
            $this->XaoThrow("No valid node supplied.",debug_backtrace());
            return;
        }
        $arrNdChildren = $nd->childNodes;
        foreach($arrNdChildren AS $ndChild) {
            $strParamName = null;
            $strChildElName = $ndChild->nodeName;
            if(
                $ndChild->nodeType == XML_ELEMENT_NODE && 
                (
                    $strChildElName == "param" ||
                    $strChildElName == "params"
                )
            ) {
                $arrAttributes = $ndChild->attributes;
                foreach($arrAttributes AS $ndAtt) {
                    if($ndAtt->name == "name") {
                        $strParamName = trim($ndAtt->value);
                        break;
                    }
                }
            }
            
            if($strChildElName == "params") {
                if($strParamName) {
                    $arr[$strParamName] = array();
                    $this->_ReadParamTraverse($ndChild,$arr[$strParamName]);
                } else {
                    $arrProxy &= $arr[] = array();
                    $this->_ReadParamTraverse($ndChild,$arrProxy);
                }
            }
            elseif($strChildElName == "param") {
                                    // repeating param instances with the 
                                    // same name are turned into an array.
                if(is_array($arr) && array_key_exists($strParamName,$arr)) {
                    if(!is_array($arr[$strParamName])) {
                        $tmp = $arr[$strParamName];
                        $arr[$strParamName] = array();
                        $arr[$strParamName][] = $tmp;
                    }
                    $arr[$strParamName][] = $this->_ConfigContentParse(
                        trim($ndChild->get_content())
                    );
                }
                elseif($strParamName) {
                    $arr[$strParamName] = $this->_ConfigContentParse(
                        trim($ndChild->textContent)
                    );
                }
                else {
                    $arr[] = $this->_ConfigContentParse(
                        trim($ndChild->textContent)
                    );
                }
            }
        }
    }
    
    protected function _ConfigContentParse(&$strContent) {
        $arrMatches = array();
        $strPattern = "/\[\[(\w*)\]\]/";
        if(preg_match($strPattern,$strContent,$arrMatches)) {
            if(count($arrMatches) > 1 && defined($arrMatches[1])) {
                $strContent = str_replace(
                    $arrMatches[0],
                    constant($arrMatches[1]),
                    $strContent
                );
                                        // an endless loop could be caused if
                                        // the value of a matched constant
                                        // matches the pattern in $strPattern
                $strContent = $this->_ConfigContentParse($strContent);
            }
        }
        return $strContent;
    }
    
    protected function _blnTryCache() {
        if(
            file_exists($this->uriThisCache) && 
            is_file($this->uriThisCache) &&
            file_exists($this->uriConfDoc) && 
            is_file($this->uriConfDoc)
        ) {
            if(filemtime($this->uriConfDoc) > filemtime($this->uriThisCache)) 
                return false;
            $this->_arrParams = unserialize(
                Xao_Util_FS::strGetFileData($this->uriThisCache)
            );
            if(is_array($this->_arrParams)) return true;
            $this->XaoThrow(
                "There was a problem trying to unserialize config cache " .
                "located at ".$this->uriThisCache,
                debug_backtrace()
            );
        }
        return false;
    }

    public function XaoThrow($strMsg,$arrBt = null,$arrAttribs = null) {
        $strStackDump = "";
        if(is_array($arrBt)) $strStackDump = Xao_Root::HTML_Stack_Dump($arrBt);
        die(
            "Script terminated by object class XAO 'Configuration' class's XaoThrow method: "
            ."<br/><br/>".$strMsg
            ."<br/><br/>".$strStackDump
        );
    }
}
