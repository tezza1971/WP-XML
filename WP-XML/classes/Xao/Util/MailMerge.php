<?php
class   Xao_Util_MailMerge 
extends Xao_Root 
{
    protected $intLine;
    
    protected $arrTokenStack = array();
    
    private $regex = '/\{(\w+?)(:(.*?))?\}/m';

    public function strMerge(
        $arrMap,$strSubject,$strParentToken = null
    ) {
        if(
            count($arrMap) && 
            array_key_exists(0,$arrMap) && 
            is_array($arrMap[0])
        ) {
            $arrResults = array();
            foreach($arrMap AS $arrSubMap) {
                $arrResults[] = $this->strMerge(
                    $arrSubMap,$strSubject,$strParentToken
                );
            }
            return $arrResults;
        }
        return preg_replace(
            $this->regex."e",
            "\$this->strTokenReplacer('$1','$3',\$arrMap,\$strParentToken)",
            $strSubject
        );
    }
    
    private function strTokenReplacer(
        $strToken,
        $strParam,
        $arrMap,
        $strParentToken
    ) {
        if(!in_array($strParentToken,$this->arrTokenStack)) {
            $this->arrTokenStack[] = $strParentToken;
        } else {
            return "{recursive instance of ".$strToken."}";
        }
        
        $method = "MAP_".$strToken;
        if(method_exists($this,$method)) {
            $strReplacement = $this->$method($strParam);
        } elseif(array_key_exists($strToken,$arrMap)) {
            $strReplacement = $this->strMerge($arrMap,$arrMap[$strToken],$strToken);
        } else {
            $strReplacement = "{unmatched ".$strToken."}";
        }
        array_pop($this->arrTokenStack);
        return $strReplacement;
    }
    
    protected function MMThrow($msg,$strSubject = null,$intLine = null) {
        if(!$intLine) $intLine = $this->intLine;
        $this->XaoThrow(
            "MailMerge error: ".$msg,
            debug_backtrace(),
            array("type" => "MailMergeParseError", "ContentLine" => $intLine)
        );
    }
    
    protected function MAP_date($strParam) {
        $fmt = "c";
        if($strParam) $fmt = $strParam;
        return date($fmt);
    }
    
    /* 
    protected function MAP_PHP($strParam) {
        if(!$strParam) {
            $this->MMThrow("No PHP code was given for this PHP tag");
            return;
        }
        try {
            eval($strParam);
        }
        catch(Exception $e) {
            MMThrow(
                "Code for this PHP tag has caused an error: ".$e->getMessage()
            );
        }
    }
    */
}
