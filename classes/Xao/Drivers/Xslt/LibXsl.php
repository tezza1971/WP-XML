<?php
/**
 * This class implements the deault XSLT processor for PHP5
 * 
 * TODO - implement exception handling.
 */
class   Xao_Drivers_Xslt_LibXsl 
extends Xao_Drivers_Xslt_Xslt
{
    /**
     * In this case, the XSLT processor is a PHP5 object
     * 
     * @var    object
     */
    protected $objProc;
    
    /**
     * Whether or not to resolve external entities
     * 
     * @var    bool
     */
    protected $blnResolveEntities;
    
    /**
     * Set up the XSLT processor so it is ready to do a transform
     * 
     * @param    object    The source DOM object instance
     * @param    string    The location of the stylesheet
     * @return    void
     */
    public function __construct($objDoc,$uriStylSheet,$blnDieOnErros = true) {
        if(!extension_loaded('xsl')) {
            die("You need to have the XSL extension loaded in order to use"
            ." drivers/xslt/XsltLibXsl.php. On windows, go to add/remove "
            ."programs and click the CHANGE button next to PHP. On unix, "
            ."compile using --with-xsl[=DIR]. Don't forget to restart your "
            ."web server");
        }
        try {
            parent::__construct($objDoc,$uriStylSheet);
            $this->objProc = new XSLTProcessor();
            $this->objStyle = $this->objGetDomFactoryData($uriStylSheet);
            $this->objStyle->documentElement->setAttribute(
                "xml:base",dirname($uriStylSheet)
            );
            // die($this->objStyle->saveXML());
            ob_start();
            $err = error_reporting(E_ALL);
                                        // The XSLT processor will throw WARNING
                                        // errors if the stylesheet is not valid
                                        // (mostly). If PHP warning is not
                                        // enabled, 
                $this->objProc->importStyleSheet($this->objStyle);
                $strDebug = ob_get_contents();
            error_reporting($err);
            ob_end_clean();
            if($strDebug && $blnDieOnErros) die($strDebug);
        }
        catch(Exception $e) {
            $this->XaoThrowE($e);
        }
        catch(DOMException $e) {
            Xao_Root::DEBUG($e); // todo
        }
    }

    /**
     * This implementation's transform method
     * 
     * Most of the work is done in the constructor for this class. As you can
     * see, the transformation process is a one-liner. Our method ensures
     * maintaining the standard XAO behavior
     */
    public function Transform() {
        if(count($this->arrXslParams)) 
            $this->objProc->setParameter("",$this->arrXslParams);
        if($this->blnResolveEntities) {
            $this->objProc->resolveExternals = TRUE;
            $this->objProc->substituteEntities = TRUE;
        }
        $this->strXsltResult = $this->objProc->transformToXML($this->objSrc);
        $this->blnCompleted = true;
    }

    public function HandleEntities($bln) {
        $this->blnResolveEntities = (bool)$bln;
    }
}
