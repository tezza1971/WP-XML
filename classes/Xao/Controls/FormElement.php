<?php
/**
 * The FormElement class is designed be used in conjunction with the Form class.
 * Both classes create instances of DomDoc classes. a FormElement is designed to
 * be "consumed" by a Form instance.
 */
class   Xao_Controls_FormElement 
extends Xao_DomDoc 
{
    /**
     * Specified in the constructor, the element types available are listed in
     * the $arrTypes member variable. They are often used in the HTML type
     * attribute
     * 
     * @var    string
     */
    protected $strType;
    
    /**
     * Specified in the constructor, the name of the element is often used in
     * the HTML attribute when rendered. Each element must have a name.
     * 
     * @var    string
     */
    protected $strName;
    
    /**
     * This is often used to display a label to the end user. If nothing is
     * supplied, then it is set to the name when the constructor runs.
     */
    protected $strCaption;
    
    /**
     * The default value of the form element is used to pre-populate fields for
     * the end user.
     * 
     * @var    string
     */
    protected $strDefault;
    
    /**
     * This array enumerates the allowed form types and is used to make sure any
     * types specified by the caller of this object are legal. It is up to the
     * renderer (ie. XSLT stylesheet) to decide how form element types are
     * handled.
     * 
     * @var    array
     */
    public static $arrTypes = array(
        "text","button","submit","password","textarea","hidden","radio",
        "checkbox","optionlist","reset","select","pageout","date","time",
        "repeater","file"
    );
    
    /**
     * This is an associative array containing the name/value pairs for all the
     * CSS properties that should apply to this element. Strictly speaking, this
     * shouldn't be used because it would generally be rendered as inline styles
     * - which are bad. Instead, the SetHtmlAtt() method should be used to set a
     * class attribute which is then used to attach style information via an
     * external CSS stylesheet.
     * 
     * @var    array
     */
    protected $arrCssProps = array();
    
    /**
     * Valid types of options. This is really saying what option types the XSLT
     * template will support.
     * 
     * @var    array
     */
    public static $arrOptionTypes = array("select","radio");
    
    /**
     * Valid types of text fields. This is used for specifying a HTML class
     * attribute so that we can style descretely
     * 
     * @var array
     */
    public static $arrTextTypes = array("text","textarea","file","password");

    /**
     * Valid types of button fields. This is used for specifying a HTML class
     * attribute so that we can style descretely
     * 
     * @var array
     */
    public static $arrButtonTypes = array("button","submit","reset");

    /**
     * All the minimal information for a form element should be specified in the
     * constructor.
     * 
     * @param    string    The form field type, restricted to $this->arrTypes
     * @param    string    Each form element should have a name
     * @param     string     catptions are often used as labels in HTML
     * @return     void
     */
    public function __construct($strType,$strName,$strCaption = null) {
        $this->strType = $strType;
        $this->strName = $strName;
        ($strCaption) 
            ? $this->strCaption = $strCaption 
            : $this->strCaption = $strName;
        
        parent::__construct("Element");
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
        if($strCaption) $this->SetCaption($this->strCaption);
        if(array_key_exists($strName,$_POST)) {
            $this->DefaultValue($_POST[$strName]);
        }
        elseif(array_key_exists(strtoupper($strName),$_POST)) {
            $this->DefaultValue($_POST[strtoupper($strName)]);
        }
    }
    
    /**
     * Used to retrieve the name of the element
     * 
     * @return string the name of the elemtn
     */
    function strGetName() {
        return $this->strName;
    }
    
    /**
     * This generic utility is used to do things such as add JavaScript events,
     * or any other HTML attribute that is not functionally addressed by the
     * FormElement class. Strictly speaking, this is just a way to cache desired
     * HTML attributes, it is really up to the XSLT stylesheet to render them.
     * 
     * @param    string    The name of the attribute (checked for safe names)
     * @param    string    The value of the attribute
     * @return     void
     */
    public function SetHtmlAtt($strName,$strValue) {
        $ndAtt = $this->ndAppendToRoot("HtmlAttribute",$strValue);
        $ndAtt->setAttribute("name",$strName);
    }
    
    /**
     * This is really a convenient way to specify those hidden HTML form
     * elements that specify how other elements in the page should be validated.
     * See the ../util/InputValidator.php class for more information. note that
     * more validation types may be supported by classes that inherit and add to
     * InputValidator
     * 
     * @param    string    A validation type understood by InputValidator
     * @param    string    The validation parameters understood by InputValidator
     * @retuen    void
     */
    public function AddVld($strType,$strMsg) {
        $elVld = new FormElement(
            "hidden",
            "VLD_".$strType."_".$this->strName
        );
        $elVld->DefaultValue($strMsg);
        $this->ndConsumeDoc($elVld);
    }
    
    /**
     * Basically a setter for $this->strDefault, it manages what to do if a
     * default value already exists. See comments on $this->strDefault
     * 
     * @param    string    The value that will be used by default
     * @param    bool    If an existing default value should be replaced.
     * @param    bool    Whether overriding should only apply with blanks
     */
    public function SetDefaultValue(
        $strVal,$blnForce = false, $blnReplaceOnlyBlanks = false
    ) {
        $objNodeList = $this->ndRoot->getElementsByTagName("Default");
                                        // see if i default was set
        if(!$blnForce && $objNodeList->length) {
            if($blnReplaceOnlyBlanks) {
                                        // bail if we already have content
                $ndFirst = $objNodeList->item(0);
                if($ndFirst->get_content()) return;
            }
            else {
                                        // bail if a default was set, regardless
                return;
            }
        }
        $this->strDefault = $strVal;
        if($objNodeList->length) {
            $ndFirst = $objNodeList->item(0);
            $ndFirst->appendChild($this->objDoc->createTextNode($strVal));
        }
        else {
            $this->ndAppendToRoot("Default",$strVal);
        }
    }
    
    /**
     * A setter for the Message element, this is generally used to specify a
     * message to the user describing or annotating this form element. The
     * implementation is up to the XSLT.
     * 
     * @param    string    the actually message content
     * @param    string    an optional URL to link to further info.
     */
    public function SetMessage($strMsg,$uriHref = null) {
        $ndMsg = $this->ndAppendToRoot("Message",$strMsg);
        if($uriHref) $ndMsg->setAttribute("href",$uriHref);
    }
    
    /**
     * A setter for the caption attribute on the form element, it is often used
     * to provide a label for the end user to see.
     * 
     * @param    string    The caption to use.
     * @return    void
     */
    public function SetCaption($strCaption) {
        $this->strCaption = $strCaption;
        $this->ndRoot->setAttribute("caption",$this->strCaption);
    }
    
    /**
     * This function sets an associative array containing the name/value pairs
     * for all the CSS properties that should apply to this element. Strictly
     * speaking, this shouldn't be used because it would generally be rendered
     * as inline styles - which are bad. Instead, the SetHtmlAtt() method should
     * be used to set a class attribute which is then used to attach style
     * information via an external CSS stylesheet. This would typically be
     * called multiple times for any given form element depending on how many
     * CSS properties are to be set.
     * 
     * @param    string    The CSS property name
     * @param    string    Said property's value
     * @return    void
     */
    public function SetCssProperty($strName,$strValue) {
        $this->arrCssProps[$strName] = $strValue;
        $this->_ReApplyCss();
    }
    
    /**
     * This method is used as a reliable way to set the CSS attribute with all
     * associated properties/values.
     * 
     * @return    void
     */
    private function _ReApplyCss() {
        if(!count($this->arrCssProps)) return;
        $arrPairs = array();
        foreach($this->arrCssProps As $name => $val) {
            $arrPairs[] = $name.": ".$val;
        }
        $this->ndRoot->setAttribute("css",implode(";",$arrPairs));
    }
    
    /**
     * A reliable way to create an element which presents a list of options to
     * the user. Each array item represents an option. An option is represented
     * by an array. The first item in the array is the name, the second is the
     * value, and the optional third is the value TRUE if the option is supposed
     * to be selected by default. You can also use the SetDefaultValue() method
     * BEFORE running this one to set a single default.
     * 
     * @param    array    A multi-dimensional array of name/value pairs
     * @return    void    
     */
    public function SetOptions($arrOptions) {
        if(!in_array($this->strType,$this->arrOptionTypes)) {
            $this->XaoThrow(
                "Options can only be added to elements of type ".
                implode(",",$this->arrOptionTypes).". This form element is of type".
                $this->strType
                ,debug_backtrace()
            );
            return;
        }
        if(!is_array($arrOptions) || !count($arrOptions)) return;
        if(!is_array($arrOptions[0]) || !count($arrOptions[0])) return;
        $blnHasDefault = $this->_blnOptionsHasDefault($arrOptions);
        foreach($arrOptions AS $arrOption) {
            $ndOpt = $this->ndAppendToRoot("Option",$arrOption[0]);
            $ndOpt->setAttribute("name",$arrOption[1]);
            if(
                !$blnHasDefault
                && $arrOption[0] == $this->strDefault
            ) {
                $arrOption[2] = true;
            }
            if(array_key_exists(2,$arrOption) && $arrOption[2]) {
                $ndOpt->setAttribute("default","true");
            }
        }
    }
    
    /**
     * A utility method to check if any default options exist at all.
     * 
     * @param    array    The option list to check
     * @return    bool    Whether or not a default exists
     */
    private function _blnOptionsHasDefault($arrOptions) {
        foreach ($arrOptions AS $arrOption) {
            if(array_key_exists(2,$arrOption) && $arrOption[2]) return true;
        }
        return false;
    }
}
