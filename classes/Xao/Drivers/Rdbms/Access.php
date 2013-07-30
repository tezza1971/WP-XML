<?php
/**
 * MS Access implmenetation of XAO database driver.
 * 
 * This is an implementation of the XAO rdbms driver. In this case, it
 * implements the Microsoft Access database using PHPs ODBC functions. At this
 * stage, the class is extremely simple and only implements a few methods.
 */
class   Xao_Drivers_Rdbms_Access 
extends Xao_Drivers_Rdbms_DbA 
{
    /**
     * Constructor function
     * 
     * @param    string    The DSN string used in the ODBC connect function.
     * @param    string    The user name used in the ODBC connect function.
     * @param    string    The password used in the ODBC connect function.
     * @param    bool    Whether or not to use a persistent connection.
     */
    function __construct(
        $strConnectionString,
        $strUSer,
        $strPass,
        $blnPersistent = false
    ) {
        $this->strDriverType = XAO_DRIVER_RDBMS_TYPE_ACCESS;
        $this->SetConnString($strConnectionString);
        $this->SetPersistence($blnPersistent);
        ob_start();
            if($this->blnGetPersistence()) {
                $this->_conn = odbc_pconnect(
                    $this->strGetConnString(),
                    $strUSer,
                    $strPass
                );
            }
            else {
                $this->_conn = odbc_connect(
                    $this->strGetConnString(),
                    $strUSer,
                    $strPass
                );
            }
            $strErr = ob_get_contents();
        ob_end_clean();
        $this->blnCheckConnection($strErr);
    }
    
    /**
     * Overridden to implement odbc_exec()
     * 
     * @param    string    SQL statement
     */
    function _resQuery($strSql) {
        if($this->_conn) {
            ob_start();
            $res = odbc_exec($this->_conn,$strSql);
            $strErr = ob_get_contents();
            ob_end_clean();
            if($res === false) {
                                        // don't show details unless in debug
                if($strErr && $this->blnDebug) {
                    $this->XaoThrow(
                        "The database server reported a problem executing \n\n"
                        .$strSql."\n\n".$strErr,
                        debug_backtrace()
                    );
                }
                else {
                                        // leave out details to prevent SQL
                                        // injection attempts from being 
                                        // debugged
                    $this->XaoThrow(
                        "Some sort of error occured while talking to " .
                        "the database"
                    );
                }
            }
            else {
                return $res;
            }
        }
        else {
            $this->XaoThrow(
                "Cannot execute SQL since a valid connection was not " .
                "esablished.",
                debug_backtrace()
            );
        }
        return false;
    }
    
    /**
     * Overridden to implement odbc_fetch_array()
     * 
     * @param    string    SQL statement
     */
    function arrQuery($strSql) {
        $arr2d = array();
        if($res = $this->_resQuery($strSql)) {
            while($arr2d[] = odbc_fetch_array($res));
            array_pop($arr2d);
        }
        return $arr2d;
    }
}
