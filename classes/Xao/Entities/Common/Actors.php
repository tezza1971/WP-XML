<?php
/**
 * Context Entity Class. This entity provides basic access to getting and 
 * setting context records. The context entity itslef is used to partition other
 * entities in the database and is otherwise of no business signifigance.
 */
class   Xao_Entities_Common_Actors 
extends Xao_Entities_EntBase 
{
    /**
     * This entity only supports the following RDBMS types
     * 
     * @var     array
     */
    public $arrSupportedDbTypes = array(
        XAO_DRIVER_RDBMS_TYPE_POSTGRES,
        XAO_DRIVER_RDBMS_TYPE_ORACLE
    );
 
    public function __construct(
        &$objDb,
        $strName = "actors",
        $strTable = "common.actors",
        $strView = "common.v_actors"
    ) {
        parent::__construct($objDb,$strName,$strTable,$strView);
    }
    
    public function arrGetActorAncestors($intActorId) {
        if(!$intActorId) {
            $this->XaoThrow(
                "strGetAnsestorStream(): a valid actor ID is required in the " .
                "first method to this argument. ",
                debug_backtrace()
            );
            return;
        }
        $sql = "SELECT actor_ancestors FROM common.v_actor_ancestors " .
               "WHERE actor_id = ".$intActorId;
        $arrStream = $this->objDb->arrQueryStream($sql);
        $intErrCount = $this->intCountErrors($this->objDb,true);
        if($intErrCount) {
            $this->XaoThrow(
                "Error while trying to retrieve ancestors.",debug_backtrace()
            );
            return;
        }
        return $this->arrNormaliseAncestorLists($arrStream);
    }
}
