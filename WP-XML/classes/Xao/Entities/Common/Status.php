<?php
/**
 * Status Entity Class. This entity provides generalised status options for 
 * various other entities. Entities can deliniate their status collections by
 * way of the context field. A useful field is the ENTITY_TARGET_FIELD which 
 * shows the main field to which it's primary key is foreign keyed.
 */
class   Xao_Entities_Common_EntStatus 
extends Xao_Entities_EntBase 
{
    /**
     * This entity only supports the following RDBMS types
     * 
     * @access  public
     * @var     array
     */
    public $arrSupportedDbTypes = array(
        XAO_DRIVER_RDBMS_TYPE_POSTGRES,
        XAO_DRIVER_RDBMS_TYPE_ORACLE
    );
 
    public function EntStatus(
        &$objDb,
        $strName = "status",
        $strTable = "common.entity_status",
        $strView = null
    ) {
        $this->_EntBase($objDb,$strName,$strTable,$strView);
    }
}
