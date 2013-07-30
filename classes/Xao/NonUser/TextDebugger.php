<?php
/**
 * Plain Text debugger
 * 
 * This class provides developer-friendly HTML output of strings for debugging.
 * Specifically, it highlights the specified line of the text and can display
 * the surrounding text using an nominal number of lines for padding. This
 * functionality can be used anywhere a parse failure accurs and there is a line
 * number involved.
 *
 * @author       Terence Kearns
 * @version      0.2
 * @copyright    Terence Kearns 2003
 * @license      LGPL
 * @package      XAO
 * @link         http://xao-php.sourceforge.net
 */
class Xao_NonUser_TextDebugger 
{
    public $strHtml;
    
    public $arrText;
    
    public $intLine;
    
    public $intPadding = 10;
    
    public $strHighlightStyle = 
        "color: red; font-weight: bold; background: yellow;";
    
    public $strTextStyle = 
        "color: black; font-weight: normal; background: #E0E0E0;";

    public $strBorderStyle = 
        "border: solid red 1px; padding: 10px; font-size: 8pt;";

    public function __construct($strText,$intLine = -1) {
            if($intLine == -1) $this->intPadding = 0;
            $this->arrText = @file($strText);
            if(!$this->arrText) {
                $this->arrText = explode("\n",$strText);
            }
            $this->intLine = $intLine;
    }
    
    public function strGetHtml() {
            
        if(!strlen($this->strHtml)) {
            $intPadding = $this->intPadding;
            $intHilightLine = $this->intLine;
                
            $strOut = "<pre style=\"".$this->strBorderStyle."\">Debug dump: ";
            
            if($intPadding < 0) 
                $intPadding *= -1; // deal with negative numbers
            if($intPadding != 0) 
                $strOut .= 
                    "printing ".$intPadding
                    ." lines either side of highlight.\n";
            
            $intCurrLine = 1;
            foreach($this->arrText AS $strLine) {
                if(
                    (
                        $intHilightLine > -1 
                        &&  ( 
                               ($intHilightLine - $intCurrLine) <= $intPadding
                            && ($intCurrLine - $intHilightLine) <= $intPadding
                        )
                    )
                    || $intPadding == 0
                ) {
                    if($intCurrLine == $intHilightLine) {
                        $strOut .= 
                            "<div style=\"".$this->strHighlightStyle."\">";
                    }
                    else {
                        $strOut .= "<div style=\"".$this->strTextStyle."\">";
                    }
                    $strOut .= 
                        sprintf(
                            "<b>%5d</b> %s",
                            $intCurrLine,
                            htmlentities($strLine)
                        );
                    $strOut .= "</div>";
                    if($intCurrLine == count($this->arrText)) {
                        $strOut .= "End of File!\n";
                    }
                }
                $intCurrLine++;
            }
            $strOut .= "</pre>\n";
            $this->strHtml = $strOut;
        }
            
        return $this->strHtml;
    }
}
