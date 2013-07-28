<?php
class   Xao_Util_FS
extends Xao_Root 
{
    public static function FixSlashes(&$uri) {
        $uri = preg_replace("/\\\\/","/",$uri);
    }

    public static function EnsureDir($uriTarget,$uriBase = "") {
                                        // if directory is already there, then
                                        // exit returning true
        // $uriTarget = dirname($uriTarget); // don't do this. it truncates.
        if(is_dir($uriTarget)) return true;
        $arrTarget = explode("/",$uriTarget);
        foreach($arrTarget AS $idx => $dirName) {
            if(!$dirName) continue; // sometimes the first entry is blank
                                        // skip windows driver letters
            if($idx == 0 && preg_match("/\w\:/",$dirName)) continue;
            $prefix = "";
            for($i=0; $i<=$idx; $i++) {
                ($i)?$prefix .= "/".$arrTarget[$i]:$prefix .= $arrTarget[$i];
            }
            $uriPartialPath = $uriBase.$prefix;
            if(!is_dir($uriPartialPath)) {
                if(!mkdir($uriPartialPath)) die(
                    "Could not create directory under ".$uriPartialPath
                );
            }
        }
        return true;
    }
    
    public static function uriFindServerDirSlot(
        $uriBase,
        $strDirPrefix = "XAO_",
        $intMaxChildren = 8
    ) {
        $arrParams = array($uriBase,$strDirPrefix,$intMaxChildren);
        $uriResult = Xao_Util_FS::uriFindSlot($arrParams);
        if(!$uriResult) {
            $uriResult = Xao_Util_FS::uriPushNewDir($arrParams);
        }
        return $uriResult;
    }
    
    /* Find a directory where there is an empty slot for a FILE or DIRectory
     * 
     * Whether or not the slot needs to be for a file is determined by the
     * blnFile param. Otherwise it is assumed to be looking for a DIRectory
     * slot.
     * 
     * @param    An array of standard paramters (base dir, prefix, max items)
     * @param   Whether or not the slot search is for a file
     * @param   Whether or not to traverse sybling folders (for DIR search)
     */
    public static function uriFindSlot($arrParams,$blnFile = true,$blnCkSybling = false) {
                                        // init arrays to receive listings
        $arrDirs = array();
        $arrFiles = array();
                                        // polulating listings
        Xao_Util_FS::ReferDirListing(
            $arrParams[0],
            $arrDirs,
            $arrFiles,
            $arrParams[1]
        );
                                        // set search target
        ($blnFile) ? $arrTarget = $arrFiles : $arrTarget = $arrDirs;
                                        // found empty slot in base dir
        if(count($arrTarget) < $arrParams[2]) {
            return $arrParams[0];
        }
        else{
            if(!count($arrDirs)) {
                                        // this code is never reached if
                                        // $blnFile is set to false.
                                        // There are no file slots free and
                                        // no sub-directories to search either.
                return false;
            }
            else {
                                        // this value may never be modified but
                                        // it is checked later. Needs an init.
                $uriNextSybling = "";
                                        // are sybling checks turned on for 
                                        // DIRectory searches.
                if(!$blnFile && $blnCkSybling) {
                    $arrPathItems = explode("/",$arrParams[0]);
                    $uriCurrDirName = array_pop($arrPathItems);
                    $uriParentDirName = implode("/",$arrPathItems);
                    
                    $arrParDirs = array();
                    $arrParFiles = array();
                    Xao_Util_FS::ReferDirListing(
                        $uriParentDirName,
                        $arrParDirs,
                        $arrParFiles,
                        $arrParams[1]
                    );
                    
                    foreach($arrParDirs AS $intKey => $uriSybling) {
                        if($uriSybling == $uriCurrDirName) {
                            $i = ++$intKey;
                            if(array_key_exists($i,$arrParDirs)) {
                                $uriNextSybling = $uriParentDirName."/".$arrParDirs[$i];
                                $arrSybDirs = array();
                                $arrSybFiles = array();
                                Xao_Util_FS::ReferDirListing(
                                    $uriNextSybling,
                                    $arrSybDirs,
                                    $arrSybFiles,
                                    $arrParams[1]
                                );
                                if(count($arrSybDirs) < $arrParams[2]) {
                                    break;
                                }
                                else {
                                    $uriNextSybling = false;
                                }
                            }
                        }
                        if($uriSybling != $uriCurrDirName && !$uriNextSybling) {
                            $uriNextSybling = $uriParentDirName."/".$uriSybling;
                            $arrSybDirs = array();
                            $arrSybFiles = array();
                            Xao_Util_FS::ReferDirListing(
                                $uriNextSybling,
                                $arrSybDirs,
                                $arrSybFiles,
                                $arrParams[1]
                            );
                            if(count($arrSybDirs) < $arrParams[2]) {
                                break;
                            }
                            else {
                                $uriNextSybling = false;
                            }
                        }
                    }
                }
                
                if($uriNextSybling) {
                    $arrParams[0] = $uriNextSybling;
                    $uriResult = Xao_Util_FS::uriFindSlot(
                        $arrParams,
                        $blnFile,
                        $blnCkSybling
                    );
                    if(
                        !$uriResult = Xao_Util_FS::uriFindSlot(
                            $arrParams,
                            $blnFile,
                            $blnCkSybling
                        )
                    ) die(
                        "FS::uriFindSlot(): erorr using uriNextSybling"
                    );
                    return $uriResult;
                }
                else {
                                        // Sybling checking is turned on AFTER
                                        // the base dir has been searched. This
                                        // only applies to DIR searches.
                    if(!$blnFile) $blnCkSybling = true;
                                        // This var will be returned regardless
                                        // if it has been set or not.
                    $uriResult = false;
                    $uriOriginalBase = $arrParams[0];
                                        // keep searching through children and
                                        // return the result. Break off the
                                        // traversal if a useable result is 
                                        // found.
                    foreach($arrDirs AS $uriDir) {
                        $arrParams[0] = $uriOriginalBase."/".$uriDir;
                        if(
                            $uriResult = Xao_Util_FS::uriFindSlot(
                                $arrParams,
                                $blnFile,
                                $blnCkSybling
                            )
                        ) {
                            break;
                        }
                    }
                    return $uriResult;
                }
            }
        }
    }
    
    public static function uriPushNewDir($arrParams) {
        $uriResult = Xao_Util_FS::uriFindSlot($arrParams,false);
        if($uriResult) {
            $arrDirs = array();
            $arrFiles = array();
            Xao_Util_FS::ReferDirListing(
                $uriResult,
                $arrDirs,
                $arrFiles,
                $arrParams[1]
            );
            for($i = 0; in_array($arrParams[1].$i,$arrDirs); $i++);
            $uriNewDir = $uriResult."/".$arrParams[1].$i;
            // $uriNewDir = $uriResult."/".uniqid($arrParams[1]);
            if(!mkdir($uriNewDir)) die (
                "FS::PushNewDir(): Could not create directory $uriNewDir"
            );
            return $uriNewDir;
        }
        else {
            die("FS::PushNewDir(): Huston, we have a problem.");
        }
    }

    public static function ReferDirListing(
        $uriBase,
        &$arrSubDirs,
        &$arrSubFiles,
        $strDirPrefix = "XAO_"
    ) {
        $arrSubDirs = array();
        $arrSubFiles = array();
        if(!$resDir = opendir($uriBase))
            die("ReferDirListing: path param $uriBase does not exist".
                " as a directory. $uriBase".var_dump($uriBase));
        while(($uriItem = readdir($resDir)) !== false) {
            if(
                is_dir($uriBase."/".$uriItem) && 
                substr($uriItem,0,strlen($strDirPrefix)) == $strDirPrefix &&
                $uriItem != "." &&
                $uriItem != ".."
            ) {
                $arrSubDirs[] = $uriItem;
            }
            elseif(is_file($uriBase."/".$uriItem)) {
                $arrSubFiles[] = $uriItem;
            }
        }
        closedir($resDir);
    }

    public static function strGetExtension($uriFname) {
        if(!preg_match("/\./",(string)$uriFname)) return false;
        $arrParts = explode(".",$uriFname);
        return end($arrParts);
    }
    
    public static function strGetFileData($uri,$blnRemote = false) {
        if(!$blnRemote && !file_exists($uri)) {
            die(
                "Could not find ".$uri
                .Xao_Root::HTML_Stack_Dump(debug_backtrace())
            );
        }
        if($blnRemote && !strrpos($uri,"://")) {
            die(
                "Attempting to use a remote file without a protocol identifier."
                ." requested ".$uri.Xao_Root::HTML_Stack_Dump(debug_backtrace())
            );
        }
        $str = "";
        $fp = fopen($uri,"r");
        if(!is_resource($fp)) {
            die(
                "Could not create file resource for ".$uri
                .Xao_Root::HTML_Stack_Dump(debug_backtrace())
            );
        }
        if($blnRemote) {
            while(!feof($fp)) $str .= fread($fp,8192);
        }
        else {
            flock($fp,LOCK_SH);
            ($intSize = filesize($uri)) ? $str = fread($fp,$intSize) : $str="";
            flock($fp,LOCK_UN);
        }
        fclose($fp);
        return $str;
    }

    public static function PutFileData($uri,&$str,$blnAppend = false) {
        $blnAppend ? $mode = "a" : $mode = "w";
        $fp = fopen($uri,$mode);
        flock($fp,LOCK_EX);
        fwrite($fp,$str);
        flock($fp,LOCK_UN);
        fclose($fp);
    }

    public static function uriMkTempName($uriOriginal) {
        $arr = explode(".",$uriOriginal);
        if(count($arr) > 1) {
            $len = -(strlen(end($arr)) +1);
            $uriNew = substr($uriOriginal,0,$len)
                    . ".tmp"
                    . substr($uriOriginal,$len);
        } else {
            $uriNew = $uriOriginal.".tmp";
        }
        return $uriNew;
    }
    
    public static function strMkPostString(
        array $arrPost, 
        $blnKeysEncode = false
    ) {
        $strPostData = "";
        foreach($arrPost AS $key => $val) {
            if($blnKeysEncode) $key = urlencode($val);
            $strPostData .= $key."=".urlencode($val)."&";
        }
        return $strPostData;
    }
}
