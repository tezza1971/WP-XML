<?php
/**
 * Definition factory for facade classes
 */
class Xao_Util_FacadeFactory extends Xao_Root {
    
    var $strDriverName;
    var $uriDriverDef;
    var $strNewClassName;
    var $uriNewClassDir;
    var $arrPublicMethods = array();

    /**
     * FacadeFactory constructor
     * 
     * The constructor does all the work of defining a facade class. The user
     * has to supply everything neccesary to make this happen - including the
     * name of the new class which is defined.
     * 
     * @param    object    An instance of the class over which the facade hangs
     * @param    string    The name of the class over which the facase hangs
     * @param    uri        The URI of the file which contains the class declaration
     * @param    string    The name of the new facade class to be defined
     * @param    uri        The location of the facade class definition file
     */
    function __construct(
        $objDriver,
        $strDriverName,
        $uriDriverDef,
        $strNewClassName,
        $uriNewClassDir = null
    ) {
        $this->uriDriverDef = $uriDriverDef;
        $this->strNewClassName = $strNewClassName;
        $this->uriNewClassDir = $uriNewClassDir;
        if(!class_exists($strDriverName)) {
            $this->XaoThrow(
                "ClassProxy expects a name of a declared class in it's " .
                "constructor. ".$strDriverName." is not a declared class.",
                debug_backtrace()
            );
            return;
        }
        $this->strDriverName = $strDriverName;
        if(!file_exists($this->uriDriverDef)) {
            $this->XaoThrow(
                "FacadeFactory::FacadeFactory() No file was found at ".
                $this->uriDriverDef,
                debug_backtrace()
            );
            return;
        }
        else {
                                        // slower alternate method for case 
                                        // sensitivity
            $arrMethods = $this->_arrGetClassMethods();
        }
        foreach($arrMethods AS $strMethod) {
            if(
                                        // exclude private methods
                !preg_match("/^_/",$strMethod)
                                        // exclude constructor
                && $strMethod != $this->strDriverName
                && $strMethod != "__construct"
                                        // this filter is important since
                                        // $this->_arrGetClassMethods may pick
                                        // up 'extra' function names.
                && method_exists($objDriver,$strMethod)
            ) {
                $this->arrPublicMethods[] = $strMethod;
            }
        }
        $this->_DefineClass();
    }
    
    function _DefineClass() {
        if(count($this->arrErrors)) return;
        if(!count($this->arrPublicMethods)) {
            $this->XaoThrow(
                "No public methods were found on $strDriverName",
                debug_backtrace()
            );
            return;
        }
                                        // this expression should probably be 
                                        // more restrictive (ie. no leading 
                                        // numbers)
        if(!ereg('[_A-Za-z0-9]+', $this->strNewClassName)) {
            $this->XaoThrow(
                "Supplied new class name (".$this->strNewClassName.
                ") cannot be used as an identifier in PHP.",
                debug_backtrace()
            );
            return;
        }
        if(!class_exists($this->strNewClassName)) {
            $src = 'class '.$this->strNewClassName.' extends Xao_Root { ';
            $src .= 'var $objDriver; ';
            $src .= 'function '.$this->strNewClassName.'($objDriver) { ';
            $src .= '$this->objDriver = $objDriver; ';
            $src .= ' }';
            foreach($this->arrPublicMethods as $strMethod) {
                $srcBod = '$args = func_get_args();';
                $srcBod .= '$res =& call_user_func_array(';
                $srcBod .= 'array($this->objDriver, "'.$strMethod.'")';
                $srcBod .= ',$args);';
                $srcBod .= '$this->intCountErrors($this->objDriver,true);';
                $srcBod .= 'return $res;';
                $src .= ' function '.$strMethod.'() {'.$srcBod.'}';
            }
            $src .= "}";
            if($this->uriNewClassDir && !is_dir($this->uriNewClassDir)) {
                $this->XaoThrow(
                    "Could not find supplied directory (".$this->uriNewClassDir.
                    ") in which to put new Facade class ".
                    $this->strNewClassName,
                    debug_backtrace()
                );
                return;
            }
            elseif($this->uriNewClassDir) {
                $uriNewClassDef = $this->uriNewClassDir."/".
                    $this->strNewClassName.".php";
                $this->_DefineWithFile($uriNewClassDef,&$src);
            }
            else {
                eval($src);
            }
        }
    }

    /**
     * Read the lines in the class definition script and extract method names
     * 
     * Thanks to kabatak user contributed notes to 
     * http://www.php.net/get_class_methods for the following (modified) code.
     */
    function _arrGetClassMethods() {
        $arr = file($this->uriDriverDef);
        $arrMethods = array();
        $regs = array();
        foreach ($arr as $line)
        {
            if(preg_match("/function ([_A-Za-z0-9]+)/", $line, $regs))
                $arrMethods[] = $regs[1];
        }
        return $arrMethods;
    }
    
    function _DefineWithFile($uriNewClassDef,&$src) {
        if(!trim($uriNewClassDef)) {
            $this->XaoThrow(
                "Supplied file argument was empty.",debug_backtrace()
            );
            return;
        }
        if(file_exists($uriNewClassDef)) {
            if(filemtime($uriNewClassDef) > filemtime($this->uriDriverDef)) {
                $this->_DoInclude($uriNewClassDef);
                return;
            }
        }
        if(!touch($uriNewClassDef)) {
            $this->XaoThrow(
                "Could not create the new Facade class in the " .
                "directory that was specified as ".
                $this->uriNewClassDir.". Suggest checking CREATE " .
                "permissions on that dir. Attempted new file was ".
                $uriNewClassDef,
                debug_backtrace()
            );
            return;
        }
        $srcFile = "<"."?php ".preg_replace("/(\{|\}|\;)/m","\$1\n", $src).
            " ?".">";
        Xao_Util_FS::PutFileData($uriNewClassDef,$srcFile);
        $this->_DoInclude($uriNewClassDef);
    }
    
    function _DoInclude($uriNewClassDef) {
        if(!file_exists($uriNewClassDef)) {
            $this->XaoThrow(
                "Could not find requested file for inclusion ".$uriNewClassDef,
                debug_backtrace()
            );
            return;
        }
        include_once $uriNewClassDef;
        if(!class_exists($this->strNewClassName)) {
            $this->XaoThrow(
                "Tried including file ".$uriNewClassDef.
                " created earlier by FacadeFactory but still no " .
                "class definition for ".$this->strNewClassName,
                debug_backtrace()
            );
            return;
        }
    }
}
