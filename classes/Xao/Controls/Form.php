<?php
/**
 * The Form class is designed provide a PHP API for generating UI forms. It's
 * basically a DOM document that will need to be transformed to produce, for eg.
 * HTML.
 */
class   Xao_Controls_Form 
extends Xao_DomDoc 
{
    /**
     * An array of all form element object instances 
     * 
     * @var    array
     */
    private $_arrElements = array();

    /**
     * Constructor begins the XML packet which will encapsulate the form
     * 
     * @param    string    The unique element identifier for this document
     * @param    string    The destination URL where the form will be sent
     * @param    string    This is an optional thing for HTML forms
     * @return  void    
     */
    public function __construct($strId, $uriAction, $strMethod = "post") {
        parent::__construct("XaoForm");
        $this->ndRoot->setAttribute("id",$strId);
        $this->ndRoot->setAttribute("action",$uriAction);
        $this->SetMethod($strMethod);
    }
    
    /**
     * A caption is an optional string which the UI designer may choose to
     * display.
     * 
     * @param    sting    Caption associated with this form
     * @return    void
     */
    public function SetCaption($strCaption) {
        $this->ndRoot->setAttribute("caption",$strCaption);
    }
 
    /**
     * Often used for HTML forms, this optional form property can be used to
     * determin how the form's contents will be submitted to the server.
     * 
     * @param    string    HTTP delivery method. Defaults to "post"
     * @return    void
     */
    public function SetMethod($strMethod) {
        $this->ndRoot->setAttribute("method",$strMethod);
    }
    
    /**
     * The HTML form element is often used to render forms. This element may
     * have a number of attributes not addressed by this class. You can easily
     * set them using this generic method. This is often used to insert inline
     * JavaScript events, for example "onsubmit". Hardcore javascript programers
     * don't need to use this to attach events, but this facility goes beyond
     * JavaScript usage.
     * 
     * @param    string    The HTML attribute name
     * @param    string    The attribute's value
     * @return    void
     */
    public function SetHtmlAtt($strName,$strValue) {
        $ndAtt = $this->ndAppendToRoot("HtmlAttribute",$strValue);
        $ndAtt->setAttribute("name",$strName);
    }

    /**
     * This method is a used to manipulate all elements of the form as a group.
     * 
     * This is achieved by nominating a call-back function to process each form
     * element and giving it an optional parameter(s). In addition, the list of
     * elements to be processed can be filtered by element type. An example
     * usage of this method can be seen in $this->SetTextFieldClasses().
     * 
     * @param    string    name of the callback method
     * @param    mixed    parameters for the call-back method
     * @param    array    An optional list of FormElement types that are to be
     *                     included in the application of the callback method. 
     *                     Otherwise all elements are subjected to it.
     * @return    void    
     */
    public function ApplyToElements(
        $strFunction,$mxdParams = null,$arrTypes = null
    ) {
        foreach($this->_arrElements AS $objElement) {
            if(
                is_array($arrTypes) 
                && !in_array($objElement->strGetType,$arrTypes)
            ) continue;
            $this->$strFunction($objElement,$mxdParams);
        }
    }
    
    /**
     * This method will apply a HTML class attribute to each text field using
     * the name supplied. This method is a good example of how the
     * ApplyToElements method can be employed.
     * 
     * @param    string    Value of HTML class attribute
     * @return    void
     */
    public function SetTextFieldClasses($strClassName) {
        $this->ApplyToElements(
            "SetFieldClass",$strClassName,FormElement::arrTextTypes
        );
    }
    
    /**
     * This method will apply a HTML class attribute to each button using the
     * name supplied. This method is a good example of how the ApplyToElements
     * method can be employed.
     * 
     * @param   string  Value of HTML class attribute
     * @return  void
     */
    public function SetButtonFieldClasses($strClassName) {
        $this->ApplyToElements(
            "SetFieldClass",$strClassName,FormElement::arrButtonTypes
        );
    }

    /**
     * This is a convenience function to set a HTML class attribute on a form
     * element. It is also an example of a callback element that is picked up by
     * $this->ApplyToElements when specified.
     * 
     * @param    object    FormElement object instance
     * @param    string    Value to be assigned to the class attribute
     * @return    void
     */
    protected function SetFieldClass($objElement,$strClassName) {
        $objElement->SetHtmlAtt("class",$strClassName);
    }
    
    /**
     * This is used to aggregate FormElement object instances to this Form.
     * 
     * There is no XML consuming done at this point since further manipulation
     * may occur to the aggregated form elements. However when the Finalise()
     * method on this class is run, at that point each form element's XML is
     * consumed.
     * 
     * @param    mixed    Either an instance of FormElement or an array of
     *                     instances
     * @return    void
     */
    public function AddElement($mxdElements) {
        if(is_object($mxdElements)) $this->_arrElements = array($mxdElements);
        if(!is_array($mxdElements)) {
            $this->XaoThrow(
                "Form::AddElement() requires either a FormElement object or an" 
                ." array of FormElement objects.",
                debug_backtrace()
            );
            return;
        }
        else {
            $this->_arrElements = $mxdElements;
        }
    }

    /**
     * This method MUST be executed once all the FormElements have been added
     * and all the manipulations are COMPLETE. It is needed to build the
     * complete XML tree for the Form instance.
     * 
     * @return void
     */
    public function Finalise() {
        foreach($this->_arrElements AS $objElement) {
            $this->ndConsumeDoc($objElement);
        }
    }

}
