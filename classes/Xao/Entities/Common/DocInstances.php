<?php
/**
 * Keywords Entity Class. This class is used to maintain the central keywords
 * list for any given context. It also includes functionality for associating
 * with other entities.
 */
class   Xao_Entities_Common_DocInstances 
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
    
    public $strContext;
    public $arrDocInstColSpec;
    public $intLastId;
 
    public function __construct(
        &$objDb,
        $strName = "instances",
        $strTable = "common.doc_instances",
        $strView = null
    ) {
        parent::__construct($objDb,$strName,$strTable,$strView);
        $this->arrDocInstColSpec = array(
            array("doc_inst_id","Int",true),
            array("doc_id","Int",false),
            array("doc_inst_caption","Text",true),
            array("doc_inst_uri_key","Text",false),
            array("doc_inst_size_in_bytes","Int",false),
            array("doc_inst_mime_type","Text",false),
            array("doc_inst_tips","Text",true),
            array("doc_inst_created_by","Text",false),
            array("doc_inst_last_edited_by","Text",false),
            array("doc_inst_last_uploaded_by","Text",false),
            array("doc_inst_last_edited_ts","Date",true),
            array("doc_inst_last_uploaded_ts","Date",true),
        );
    }

    public function intGetNewId() {
        $sql = "SELECT common.SEQ_DOC_INSTANCE_ID.nextval FROM dual";
        $intNew = (int)$this->objDb->mxdGetOne($sql);
        $this->intCountErrors($this->objDb,true);
        $this->intLastId = $intNew;
        return $intNew;
    }
    
    public function Insert($arrData) {
        $this->SetTableColumns($this->arrDocInstColSpec);
        $this->SetDefaultTable("common.doc_instances");
        parent::Insert($arrData);
    }
    
}
