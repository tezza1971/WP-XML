<?php
class   Xao_Util_DbToXmlTree 
extends Xao_Util_DbToXml 
{
    
    public $strParentCol;
    public $strKeyCol;
    public $intDepth = 0;
    public $intDepthLimit = 16;
    protected $_strChildrenEl = "children";
    public $blnThrewDepthException = false;
    public $mxdRootVal;
    protected $_arrTreeCallBacks = array();

    /**
     * This constructor passes through to it's parent DbToXml and also stores
     * some extra parameters locally for the specialised recursive encoding.
     * 
     * @param   object  An instance of a PEAR DB result object or a
     *                  associative  array.
     * @param   string  The name of the element to which the results r attached
     * @param   array   An associative array mapping result column names to
     *                  methods (call back functions) of the class.
     * @param   string  The name of the column in the result set that has the
     *                  child  value.
     * @param   string  The name of the column in the result set that has the
     *                  parent  value.
     * @param   mixed   The recursion is based on a values match between parent
     *                  and child. The recursion needs to start somewhere, so
     *                  this value can be specified for matching  where the 
     *                  recursion should commence. @see DbToXmlTree::Encode()
     * @param   object  a DOM node in the referenced document where the results
     * from   this calss will be appended.
     */
    public function __construct(
        $mxdResult,
        $strResultName  = "results",
        $strKeyCol,
        $strParentCol,
        $mxdRootVal = null,
        $arrCallBacks = array(),
        $ndStub = null
    ) {
        $this->SetCallBacks($arrCallBacks);
        $this->SetRootVal($mxdRootVal);
        $this->SetKeyCol($strKeyCol);
        $this->SetParentCol($strParentCol);
        $this->__construct(
            $mxdResult,
            $strResultName,
            $this->_arrTreeCallBacks,
            $ndStub
        );
    }
    
    public function SetRootVal($mxdRootVal) {
        $this->mxdRootVal = $mxdRootVal;
    }
    
    public function SetKeyCol($strKeyCol) {
        $this->strKeyCol = $strKeyCol;
    }

    public function SetParentCol($strParentCol) {
        $this->strParentCol = $strParentCol;
    }
    
    public function SetChildrenElName($elName) {
        if($this->_blnEncoded) {
            $this->XaoThrow(
                "DbToXml:SetChildrenElName() Cannot set name once Encoded.",
                debug_backtrace()
            );
            return;
        }
        if($this->blnTestSafeName($elName)) {
            $this->_strChildrenEl = $elName;
        }
        else {
            $this->XaoThrow(
                "DbToXml::SetChildrenElName() "
                .$elName." is not a valid name for an XML tag.",
                debug_backtrace()
            );
            return;
        }
    }

    public function Encode() {
        if(!is_object($this->objDoc)) $this->AttachTo();
        if(!count($this->arrResult)) return;
        if(!$this->blnDataIsGood()) return;
        if(!$this->blnHaveColNames()) return;
        foreach($this->arrResult AS $arrRow) {
            if($arrRow[$this->strKeyCol] == $this->mxdRootVal) {
                $this->_Recurse($this->ndStub,$arrRow);
            } 
        }
    }
    
    private function _Recurse($ndParentGroup, $arrCurrRow) {
        $blnWentIn = false;
        $ndRow = $this->ndHashToXml(
            $ndParentGroup,$arrCurrRow,$this->strRowEl,$this->_arrTreeCallBacks
        );
        foreach($this->arrResult AS $arrRow) {
            if($arrRow[$this->strParentCol] == $arrCurrRow[$this->strKeyCol]) {
                if($this->intDepth < $this->intDepthLimit) {
                    if(!$blnWentIn) {
                        $ndChildren = $ndRow->appendChild(
                            $this->objDoc->createElement($this->_strChildrenEl)
                        );
                    }
                    $blnWentIn = true;
                    $this->_Recurse($ndChildren,$arrRow);
                }
                elseif(!$this->blnThrewDepthException) {
                    $this->blnThrewDepthException = true;
                    $this->XaoThrow(
                        "Depth limit (".$this->intDepthLimit
                        .") for recursion has been reached. "
                        ."Check for circular references and then increase the "
                        ."intDepthLimit if required.",
                        debug_backtrace()
                    );
                }
            } 
        }
        if($blnWentIn) $this->intDepth++;
    }

    /**
    * Set the name for the children tag.
    *
    * @param    string  Name to use for the children tag
    * @return   void
    * @access   public
    */
    private function SetChildrenTagName($strName) {
        if($this->blnTestSafeName($strName)) {
            $this->_strChildrenEl = $strName;
        }
        else {
            $this->XaoThrow(
                "DbToXml: ".$strName." is not a valid name for an XML tag."
                ,$this->arrSetErrFnc(__FUNCTION__,__LINE__)
                ,debug_backtrace()
            );
        }
    }

    private function blnHaveColNames() {
        if(count($this->arrResult)) {
            if(!array_key_exists($this->strParentCol,$this->arrResult[0])) {
                $this->XaoThrow("Result set does note contain a column named "
                    .$this->strParentCol." (parent column name).",
                    debug_backtrace()
                );
                return false;
            }
            if(!array_key_exists($this->strKeyCol,$this->arrResult[0])) {
                $this->XaoThrow("Result set does note contain a column named "
                    .$this->strParentCol." (parent column name).",
                    debug_backtrace()
                );
                return false;
            }
        }
        return true;
    }
}

