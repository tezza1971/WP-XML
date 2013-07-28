<?php
/**
 * Keywords Entity Class. This class is used to maintain the central keywords
 * list for any given context. It also includes functionality for associating
 * with other entities.
 */
class   Xao_Entities_Common_Keywords 
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
    
    public $intLastId;
 
    public function EntKeywords($objDb,$strContext) {
        parent::__construct($objDb,"keywords","common.entity_keywords");
        $this->strContext = $strContext;
        $arrColSpec = array(
            array("key_id","Int",false),
            array("key_caption","Text",false),
            array("key_context","Text",false)
        );
        $this->SetTableColumns($arrColSpec);
        $arrColSpec = array("key_id","key_caption");
        $this->SetViewColumns($arrColSpec);
    }

    public function intGetNewId() {
        $sql = "SELECT common.SEQ_ENTITY_KEYWORD_ID.nextval FROM dual";
        $intNew = (int)$this->objDb->mxdGetOne($sql);
        $this->intCountErrors($this->objDb,true);
        $this->intLastId = $intNew;
        return $intNew;
    }
    
    public function Insert($intFkeyId,$strAssocTable,$strAssocCol,$strKeyword) {
        if(!$strKeyword) return;
        if(!$intFkeyId) return;
        $strKeyword = str_replace("-","_",$strKeyword);
        if(preg_match("/\\W/",$strKeyword)) {
            $this->XaoThrow(
                "Keywords must consist of whole words and cannot contain " .
                "special characters. You tried to enter ".$strKeyword,
                debug_backtrace(),
                array("type" => "warning")
            );
            return;
        }
        $strKeyword = str_replace("_","-",$strKeyword);
        $arrTest = $this->arrGetRecords("key_caption = '".$strKeyword."'");
        $this->intCountErrors($this->objDb,true);
        if(is_array($arrTest) && count($arrTest)) {
            $intKeyId = (int)$arrTest[0]["KEY_ID"];
            $sql = "SELECT key_id FROM ".$strAssocTable." WHERE ".
                $strAssocCol." = ".$intFkeyId." AND key_id = ".$intKeyId;
            $arrTest = $this->objDb->arrQuery($sql);
            $this->intCountErrors($this->objDb,true);
            if(is_array($arrTest) && count($arrTest)) return;
        }
        else {
            $intKeyId = $this->intGetNewId();
            parent::Insert(
                array(
                    "key_id" => $intKeyId, 
                    "key_caption" => $strKeyword, 
                    "key_context" => $this->strContext
                )
            );
            //if($this->intCountErrors($this->objDb,true))
                //Xao_Root::DEBUG($arrTest);
            
        }
        $sql = "INSERT INTO ".$strAssocTable." (key_id,".$strAssocCol.
            ") VALUES (".$intKeyId.",".$intFkeyId.")";
        $this->objDb->NonQuery($sql);
        $this->intCountErrors($this->objDb,true);
    }
    
}
