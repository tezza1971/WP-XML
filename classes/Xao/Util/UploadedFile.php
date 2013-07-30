<?php
class   Xao_Util_UploadedFile 
extends Xao_Root 
{
    public $arrFile = array();
    public $strExt;
    public $uriDest;
    
    public function __construct($strPostName) {
        global $_FILES;
        if(!is_array($_FILES)) return;
        if(!is_string($strPostName)) return;
        if(!$strPostName) {
            $this->XaoThrow(
                "UploadedFile: Empty Constructor Argument.",
                debug_backtrace()
            );
            return;
        }
        if(array_key_exists($strPostName, $_FILES)) {
            $this->arrFile = $_FILES[$strPostName];
        }
        else {
            $this->XaoThrow(
                "UploadedFile: Initialisation error: Could not find file data"
                ." refered to as \"$strPostName\"  by the HTML upload form.",
                debug_backtrace()
            );
            return;
        }
        $this->strExt = Xao_Util_FS::strGetExtension($this->arrFile["name"]);
    }
    
    public function strGetExtension() {
        return $this->strExt;
    }
    
    public function arrGetInfo() {
        // if(!is_array($this->arrFile) || !count($this->arrFile)) return;
        return $this->arrFile;
    }
    
    public function uriGetName() {
        return $this->arrFile["name"];
    }
    
    public function uriGetDest() {
        return $this->uriDest;
    }
    
    /**
     * Move the uploaded file to the specified location
     *
     * This function is made possible because the class already knows where the
     * source file resides on the server. This method behaves differently
     * depending on wheather the destintion is a directory path ending in a
     * forward slash or if there is no trailing slash. A missing slash may mean
     * the last component of the URI is a file name and the file will be
     * renamed accordingly during the move. Otherwise, if there is no trailing
     * slash but the directory exists, the the above rule is applied. 
     * Otheriwise if there is no trailing slash and the directory does not 
     * exist, the file is renamed to the trailing name in the path.
     *
     * @param   uri   Where the file is to moved to
     * @return  void
     */
    public function MoveTo(
        $uriDest,
        $strNewFileName = null,
        $blnOverwrite = false
    ) {
                                    // if there was an initialisatrion error,
                                    // the bug out. No need to spew more errors.
        Xao_Util_FS::FixSlashes($uriDest);
        if(!count($this->arrFile) || !array_key_exists("name",$this->arrFile)) {
            $this->XaoThrow(
                "UploadedFile->MoveTo(): No information in arrFile to act on.",
                debug_backtrace()
            );
            return;
        }
                                    // shortcut name. value is not changed.
        $uriFileName = $this->arrFile["name"];
                                    // set destination globally and do not
                                    // change (effectively constant)
        $this->uriDest = $uriDest;
                                    // does the desitnation exist as a file.
        if(is_file($this->uriDest) && file_exists($this->uriDest)) {
            if($blnOverwrite) {
                unlink($this->uriDest);
                if(
                    !move_uploaded_file(
                        $this->arrFile["tmp_name"],$this->uriDest
                    )
                ) {
                    $strPathInfo = "";
                    if($this->blnDebug) 
                        $strPathInfo = $this->arrFile["tmp_name"]
                            ." to ".$this->uriDest;
                    $this->XaoThrow(
                        "There was a problem moving the uploaded file "
                        ."from ".$this->arrFile["tmp_name"]." to ".$strPathInfo,
                        debug_backtrace()
                    );
                    return;
                }
            }
            else {
                $this->XaoThrow(
                    $this->uriDest." already exists. The uploaded file " .
                    "cannot be moved here.",
                    debug_backtrace()
                );
                return;
            }
        }
                                    // does the destination exist as a directory
        elseif(is_dir($this->uriDest) && file_exists($this->uriDest)) {
            $this->MoveTo(
                $this->uriDest."/".$this->arrFile["name"],
                $strNewFileName,
                $blnOverwrite
            );
        }
                                    // the destination does not exist
        else {
            $arrDest = explode("/",$this->uriDest);
            if($uriFileName == end($arrDest)) {
                $uriDirPath = dirname($this->uriDest)."/";
            }
                                    // trailing slash uses original name
            elseif(!$strNewFileName) {
                $uriDirPath = $this->uriDest;
            }
            else {
                $this->XaoThrow(
                    "If moving file to a different filename, you need to " .
                    "populate the second param of UploadedFile::MoveTo() " .
                    "with the new file name.",
                    debug_backtrace()
                );
            }
                                    // ensure destination directory exists
            if(FS::EnsureDir($uriDirPath)) {
                if($strNewFileName) {
                    $uriDest = $uriDirPath."/".$strNewFileName;
                }
                else {
                    $uriDest = $uriDirPath;
                }
                if(
                    !move_uploaded_file(
                        $this->arrFile["tmp_name"],
                        $uriDest
                    )
                ) {
                    $this->XaoThrow(
                        "There was a problem moving the uploaded file "
                        ."from ".$this->arrFile["tmp_name"]." to ".$uriDirPath,
                        debug_backtrace()
                    );
                    return;
                }
            }
        }
    }
} 
