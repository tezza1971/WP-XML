<?php
/**
 * FormElement.php Created on 2/02/2006 by tezza
 *
 * FormElement.php is designed be used in conjunction with Form.php. Both files
 * create instances of DomDoc classes. a FormElement is designed to be
 * "consumed" by a Form instance.
 */

/**
 * Form Element
 */
class   Xao_Util_FormElement 
extends Xao_DomDoc 
{
    
    public          $strType;
    public          $strName;
    public          $strCaption;
    public static   $arrTypes = array(
        "text","button","submit","password","textarea","hidden","radio",
        "checkbox","optionlist","reset"
    );
    
    public $arrCssProps = array();
    
    public function __construct(
        $strType,
        $strName,
        $strCaption = null
    ) {
        $this->strType = $strType;
        $this->strName = $strName;
        $this->strCaption = $strCaption;
        
        $elRoot = "Element";
        parent::__construct($elRoot);
        if(!in_array($strType,$this->arrTypes)) {
            $this->XaoThrow(
                "FormElement(): ".$strType." is not a TYPE that is suppported.",
                debug_backtrace()
            );
            return;
        }
        $elRoot = "FormElement";
        parent::__construct($elRoot);
        $this->ndRoot->setAttribute("type",$strType);
        $this->ndRoot->setAttribute("name",$strName);
        if($strCaption) $this->SetCaption($strCaption);
    }
    
    public function SetHtmlAtt($strName,$strValue) {
        $ndAtt = $this->ndAppendToRoot("HtmlAttribute",$strValue);
        $ndAtt->setAttribute("name",$strName);
    }
    
    public function AddVld($strType,$strMsg) {
        $elVld = new FormElement(
            "hidden",
            "VLD_".$strType."_".$this->strName
        );
        $elVld->DefaultValue($strMsg);
        $this->ndConsumeDoc($elVld);
    }
    
    public function DefaultValue($strVal) {
        $this->ndAppendToRoot("Default",$strVal);
    }
    
    public function SetCaption($strCaption) {
        $this->ndRoot->setAttribute("caption",$strCaption);
    }
    
    public function SetCssProperty($strName,$strValue) {
        $this->arrCssProps[$strName] = $strValue;
        $this->_ReApplyCss();
    }
    
    public function _ReApplyCss() {
        if(!count($this->arrCssProps)) return;
        $arrPairs = array();
        foreach($this->arrCssProps As $name => $val) {
            $arrPairs[] = $name.": ".$val;
        }
        $this->ndRoot->setAttribute("css",implode(";",$arrPairs));
    }
}
