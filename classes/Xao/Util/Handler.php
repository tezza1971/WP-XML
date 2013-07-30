<?php
abstract 
class   Xao_Util_Handler 
extends Xao_Root 
{
    
    public $objHost;
    public $objVld;
    public $arrReq;
    
    public function __construct($objHost,&$arrReq,$objVld = null) {
        if(!is_object($objHost)) {
            $this->XaoThrow(
                "Constructor for Handler requires a valid host object "
                ."reference.",
                debug_backtrace()
            );
            return;
        }
        $this->objHost = $objHost;

        if(!is_array($arrReq)) {
            $this->XaoThrow(
                "Constructor for Handler requires a valid array of requests. ",
                debug_backtrace()
            );
            return;
        }
        $this->arrReq = $arrReq;
        
        if(is_object($objVld)) {
            $this->objVld = $objVld;
            $this->objVld->ValidateInput($this->arrReq);
            $this->objHost->intCountErrors($this->objVld,true);
        }
    }
    
    public function arrGetValidationErrors() {
        if(is_object($this->objVld)) {
            return $this->objVld->arrValidationErrors;
        }
        return array();
    }
}
