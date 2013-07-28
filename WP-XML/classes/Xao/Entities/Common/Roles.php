<?php
class   Xao_Entities_Common_Roles 
extends Xao_Entities_Common_Actors 
{

    public function __consrtruct(
        &$objDb,
        $strName = "roles",
        $strTable = "common.actor_roles",
        $strView = "common.v_roles"
    ) {
        parent::__consrtruct($objDb,$strName,$strTable,$strView);
    }
    
    public function intGetNewId() {
        $sql = "SELECT common.SEQ_ACTORS_ACTOR_ID.nextval FROM dual";
        $intNew = (int)$this->objDb->mxdGetOne($sql);
        $this->intCountErrors($this->objDb);
        return $intNew;
    }
    
    public function REQ_Add() {
        $this->Start();
        $this->Finish();
    }

    public function REQ_Edit() {
        $this->Start();
        $this->Finish();
    }

    public function REQ_Delete() {
        $this->Start();
        $this->Finish();
    }

    public function Start() {
        $this->objDb->BeginTransaction();
    }
    
    public function Finish() {
        if(count($this->arrErrors)) {
            $this->objDb->RollbackTransaction();
            return;
        }
        $this->objDb->CommitTransaction();
    }
}
