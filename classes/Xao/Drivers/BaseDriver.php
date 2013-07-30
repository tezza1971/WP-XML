<?php
abstract
class   Xao_Drivers_BaseDriver 
extends Xao_Root 
{

    function __not_implemented() {
        $arrBT = debug_backtrace();
        $strName = get_class($this);
        die(
            "method <b>".$strName."->".$arrBT[1]["function"].
            "()</b> must be overriden by your driver class <b>".
            get_parent_class($this)."</b>"
        );
    }

}
