<?php
/* Form.php
 * Created on 2/02/2006 by tezza
 *
 * Form.php is designed provide a PHP API for generating UI forms. It's
 * basically a DOM document that will need to be transformed to produce, for eg.
 * HTML.
 */

class   Xao_Util_Form 
extends Xao_DomDoc 
{

    protected $arrElements = array();

    function __construct($strId, $uriAction, $strMethod = "post") {
        $elRoot = "XaoForm";
        parent::__construct($elRoot);
        $this->ndRoot->setAttribute("id",$strId);
        $this->ndRoot->setAttribute("action",$uriAction);
        $this->SetMethod($strMethod);
    }
    
    function SetCaption($strCaption) {
        $this->ndRoot->setAttribute("caption",$strCaption);
    }
 
    function SetMethod($strMethod) {
        $this->ndRoot->setAttribute("method",$strMethod);
    }
    
    function SetHtmlAtt($strName,$strValue) {
        $ndAtt = $this->ndAppendToRoot("HtmlAttribute",$strValue);
        $ndAtt->setAttribute("name",$strName);
    }

    function ApplyToAllElements($objElement) {
        // TODO
    }

    function ConsumeElements() {
        foreach($this->arrElements AS $objElement) {
            $this->ApplyToAllElements($objElement);
            $this->ndConsumeDoc($objElement);
        }
    }

}

