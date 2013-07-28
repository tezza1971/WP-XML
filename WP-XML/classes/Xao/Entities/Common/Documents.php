<?php
/**
 * Context Entity Class. This entity provides basic access to getting and 
 * setting context records. The context entity itslef is used to partition other
 * entities in the database and is otherwise of no business signifigance.
 */
class   Xao_Entities_Commmon_Documents 
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
 
    public function __construct(
        &$objDb,
        $strName = "documents",
        $strTable = "common.docs",
        $strView = null
    ) {
        parent::__construct($objDb,$strName,$strTable,$strView);
    }
}
