<?php
class   Xao_Util_DT 
extends Xao_Root 
{
    public static function intIsoDateToUnix($strIsoDate) {
        if(!$strIsoDate) return;
        $arrTs = explode(" ",(string)$strIsoDate);
        if(!isset($arrTs[1])) {
            var_dump(debug_backtrace());
            die("Invalid ISO Date format. ".(string)$strIsoDate);
            return;
        }
        $arrDate = explode("-",$arrTs[0]);
        $arrTime = explode(":",$arrTs[1]);
        $year   = $arrDate[0];
        $month  = $arrDate[1];
        $day    = $arrDate[2];
        $hour   = $arrTime[0];
        $min    = $arrTime[1];
        if(
            is_array($_ENV) 
            && array_key_exists("windir",$_ENV) 
            && stristr($_ENV["windir"],"C:\\")
        ) {
            if($year < 1970 || $year > 2038) {
                var_dump(debug_backtrace());
                die(
                    "Sorry: Windows mktime() cannot handle a date year " .
                    "outside of 1970 to 2048. Attempted date was ".$strIsoDate
                );
            }
        }
        return mktime($hour,$min,0,$month,$day,$year);
    }
    
    public static function strUnixToIso($intTime) {
        if(!$intTime = (integer)$intTime) return;
        return date("Y-m-d H:i:s",$intTime);
    }
}
