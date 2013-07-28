<?php
/**
 * DomFactory.php
 * 
 * This script provides the class definition for DomFactory - a class which is
 * used to safely return DOM XML objects.
 *
 * @author       Terence Kearns
 * @version      0.2
 * @copyright    Terence Kearns 2003
 * @license      LGPL
 * @link         http://xao-php.sourceforge.net
 * @package      XAO
 */

/**
* Quick XML Parser and Debugger
*
* This main job of this class is to test for XML well-formedness. Secondly, it
* is to provide highly useful debugging output in the event that the input text
* fails the wellformedness test.
*
* @package      XAO
*/
class   Xao_NonUser_DomFactory 
extends Xao_Root 
{
    public $objDoc;
    
    public $intErrorLine;
    
    public $uriContextFile;
    
    public $strErrorMsg;
    
    public $strErrorMsgFull;
    
    function __construct($strTarget) {
        if(strstr($strTarget,"\n") === false) {
            if(file_exists($strTarget)) {
                $this->objDoc = $this->_objDomParseFile($strTarget);
                return;
            }
        }
        $this->objDoc = $this->_objDomParseData($strTarget);
    }
    
    function objGetObjDoc() {
        return $this->objDoc;
    }

    function _objDomParseFile($uriSrc) {
                                            // assume that the file does not exist
            $this->uriContextFile = null;
        if(file_exists($uriSrc)) {
                                            // populate $this->uriContextFile for
                                            // use later in _objDomParseData()
                $this->uriContextFile = $uriSrc;
                                            // try to keep all file access thread-
                                            // safe when obtaining the content.
                                            // This is the main reason we don't use
                                            // domxml_open_file() - it does not
                                            // participate in flock() co-operative
                                            // locking.
            $fp = fopen($uriSrc,"r")
                OR $this->XaoThrow(
                    "\nCould not open file (".$uriSrc.")",
                    debug_backtrace()
                );
            flock($fp,LOCK_SH)
                OR $this->XaoThrow(
                    "\nCould not get a shared lock on file (".$uriSrc.").",
                    debug_backtrace()
                );
                                            // sometimes filezise cannot get an 
                                            // above 0 read on the file.
                                            // so we try at least 10 times.
                for($i=0;$i<10;$i++) {
                    $intSize = filesize($uriSrc);
                    if($intSize) break;
                }
                if(!$intSize) die(
                    "Temporarily unable to read ".$uriSrc." or the file is " .
                    "empty! Pleast try again."
                );
                $strFileData = fread($fp,$intSize);
            flock($fp,LOCK_UN);
            fclose($fp);
                                        // We now have the content. Parse it.
                                        // And exit the fuction.
            return $this->_objDomParseData($strFileData);
        }
        else {
            $this->XaoThrow(
                "\nFile (".$uriSrc.") not found.",
                debug_backtrace()
            );
        }
        return false;
    }
    
    function _objDomParseData($strSrc) {
                                        // Attempt a new DOM object using 
                                        // supplied data.
        ob_start();
            $objDoc = DOMDocument::loadXML($strSrc);
        $strWarnings = ob_get_contents();
        ob_end_clean();
        if($strWarnings) {
            // print($strWarnings);
            if (preg_match("/line: (\\d+)/", $strWarnings, $arrMatches)) {
                $intLine = $arrMatches[1];
            } else {
                $intLine = null;
            }
            $objDebugData = new Xao_NonUser_TextDebugger($strSrc,$intLine);
            if(strlen($this->uriContextFile)) 
                $this->strDebugData="<h1>".$this->uriContextFile."</h1>";
            $objDebugData->intPadding = 0;
            $this->strDebugData .= $strWarnings
                .$objDebugData->strGetHtml()
                .Xao_Root::HTML_Stack_Dump(debug_backtrace());
            die($this->strDebugData);
            // $this->XaoThrow($strWarnings,debug_backtrace(),null,true);
        }
                                        // test for success. if parse fails, we 
                                        // are obligated to produce error 
                                        // information.
        if(!is_object($objDoc)) {
                                            
            $strFile = "";
            if(strlen($this->uriContextFile)) 
                $strFile = " in file ".$this->uriContextFile." ";
                                            // We only go to the bother of 
                                            // performing another [sax] parse if
                                            // we failed the initial DOM parse.
            if($this->blnSaxParse($strSrc)) {
                                            // DOM parse failed, SAX parse succeded.
                                            // We need DOM parsing to succeed.
                                            // Throw appropriate error.
                $this->XaoThrow(
                    "The XML data ".$strFile
                    ."was parsed by PHP's XML parser but not by "
                    ."PHP's DOM XML domxml_open_mem() method. No details of the "
                    ."error can be extracted from domxml_open_mem(). Sorry.",
                    debug_backtrace()
                );
            }
            else {
                                            // While we expected the SAX parse to
                                            // fail also, it did the job of
                                            // providing the error information that
                                            // we could not extract from DOM
                $this->XaoThrow(
                    $this->strErrorMsgFull."<br/>\n".$this->strDebugData,
                    debug_backtrace()
                );
            }
            return false;
        }
        return $objDoc;
    }
    
    function blnSaxParse($strData) {
        $xp = xml_parser_create();
        //xml_set_object($xp, $this);
        $xpRes = xml_parse($xp,$strData);
        if(!$xpRes) {
            $this->strErrorMsg = xml_error_string(xml_get_error_code($xp));
            $this->intErrorLine = xml_get_current_line_number($xp);
            
            $this->strErrorMsgFull = "The following parse error occured";
            if($this->intErrorLine !== false) {
                $this->strErrorMsgFull .= 
                    "<br/>\n on or near line ".$this->intErrorLine;
            }
            if(strlen($this->uriContextFile)) {
                $this->strErrorMsgFull .= 
                    "<br/>\n in the file ".$this->uriContextFile;
            }
            $this->strErrorMsgFull .= ":<br/>\n <i style=\"color: red;\">".$this->strErrorMsg."</i><br/>\n";
            
            if(is_int($this->intErrorLine)) {
                $objDebugData = new TextDebugger(
                    $strData,
                    $this->intErrorLine
                );
            }
            else {
                $objDebugData = new TextDebugger(
                    $strData
                );
            }
            $this->strDebugData = $this->strErrorMsgFull
                .$objDebugData->strGetHtml()
                .Xao_Root::HTML_Stack_Dump(debug_backtrace());
        }
        xml_parser_free($xp);
        return $xpRes;
    }

    function XaoThrow($strErrMsg,$arrBt = null,$arrErrAttribs = null,$strPayload = null) {
        if(!is_array($arrErrAttribs)) $arrErrAttribs = array();
        if($this->intErrorLine) $arrErrAttribs["line"] = $this->intErrorLine;
        parent::XaoThrow($strErrMsg,$arrBt,$arrErrAttribs,$strPayload);
    }
}
