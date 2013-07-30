<?php
/*
 * functions.php
 *
 * This file is part of the wordpress convention. This file shouldn't grow too
 * large given the object oriented approach of the XAO library that is being
 * used at the core of this app.
 *
 * @author       Terence Kearns
 * @version      0.0 alpha
 * @copyright    Terence Kearns 2013
 * @license      GPL3 - http://www.gnu.org/licenses/gpl-3.0.html
 * @link         http://wp-xml.com
 * @package      WPX (Wordpress XML)
 */

/**
 * This function is called via spl_autoload_register() (PHP 5.1.2).
 * It takes care of locating the class files in the ./classes directory
 * on demand.
 *
 * @param string    $strClassName the spl_autoload_register() call passes
 *                  the name of the class to be loaded.
 */
function WpxAutoLoad($strClassName) {
                                        // binding an absolute path is safer
    $uriDefinition = dirname(__FILE__)."/classes/";
                                        // Interface class references don't
                                        // exist in the file name, so delete
                                        // this frim the tail.
    $strClassName = str_replace("_Interface","",$strClassName);
    $uriDefinition .= str_replace("_","/",$strClassName);
                                        // I won't test for file_exists() on
                                        // purpose. I want the resulting error
                                        // die("AUTOLOAD:".$uriDefinition);
    if(!file_exists($uriDefinition.".php")) return;
    include_once $uriDefinition.".php";
                                        // I should probably implement "throw
                                        // new ErrorException" at some stage.
    if(!in_array($strClassName,get_declared_classes())) {
        $arrBT = debug_backtrace();
        die("__autoload says: ".$uriDefinition.".php does not contain a class"
                ." definition for ".$strClassName."\n\n originally called by "
                .$arrBT[0]["file"]."\n\n on line ".$arrBT[0]["line"]);
    }
}
