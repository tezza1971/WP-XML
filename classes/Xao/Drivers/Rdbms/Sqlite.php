<?php
/**
 * SQL Lite (SqLite) is PHP5's built-in serverless database method (like MS
 * Access). This class implements it for Xao Rdbms.
 */
class       Xao_Drivers_Rdbms_Sqlite 
extends     Xao_Drivers_Rdbms_DbA
implements  Xao_Drivers_Rdbms_AtomicI
{
    private $blnBufferQueries = false;
    
    /**
     * Constructor function
     * 
     * @param    string    The path to the data file
     */
    function __construct($uriData) {
        $this->strDriverType = XAO_DRIVER_RDBMS_TYPE_SQLITE;
        $this->SetConnString($uriData);
        $this->SetPersistence(false);
        ob_start();
            $strErr = "";
            $this->_conn = sqlite_open($uriData,0666,$strErr);
            if(!$strErr) $strErr = ob_get_contents();
        ob_end_clean();
        $this->blnCheckConnection($strErr);
    }
    
    function UseBuffering($blnBuffer) {
        $this->blnBufferQueries = (bool)$blnBuffer;
    }
    
    /**
     * Overridden to implement odbc_exec()
     * 
     * @param    string    SQL statement
     */
    function _resQuery($strSql) {
        if(!$this->_conn) {
            $this->XaoThrow(
                "Cannot execute SQL since a valid connection was not " .
                "esablished.",
                debug_backtrace()
            );
            return false;
        }
        $strErr = null;
        ob_start();
            if($this->blnBufferQueries) {
                $res = sqlite_query(
                    $this->_conn,$strSql,SQLITE_ASSOC
                );
            }
            else {
                $res = sqlite_unbuffered_query(
                    $this->_conn,$strSql,SQLITE_ASSOC
                );
            }
            if($res === false) {
                $strErr = sqlite_error_string(
                    sqlite_last_error($this->_conn)
                );
            }
            if(!$strErr) $strErr = ob_get_contents();
        ob_end_clean();
        if(!$strErr) return $res;
                                    // don't show details unless in debug
        if($this->blnDebug) {
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
        return false;
    }
    
    function NonQuery($strSql,$blnWarnNoChanges = false) {
        if(!$this->_conn) {
            $this->XaoThrow(
                "Cannot execute SQL since a valid connection was not " .
                "esablished.",
                debug_backtrace()
            );
            return false;
        }
        $strErr = null;
        ob_start();
            $blnSuccess = sqlite_exec($this->_conn,$strSql);
            if(!$blnSuccess) {
                $strErr = sqlite_error_string(
                    sqlite_last_error($this->_conn)
                );
            }
            if(!$strErr) $strErr = ob_get_contents();
        ob_end_clean();
        if($strErr) {
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
            return false;
        }
        elseif($blnWarnNoChanges && sqlite_changes($this->_conn) === 0) {
            $strMore = ".";
            if($this->blnDebug) $strMore = " : \n\n".$strSql;
            $this->XaoThrow(
                "DbSqlite::NonQuery(): No rows were created or changed with" .
                " query".$strMore,
                debug_backtrace()
            );
            return false;
        }
        return true;
    }
    
    /**
     * Overridden to implement sqlite_fetch_all()
     * 
     * @param    string    SQL statement
     */
    function arrQuery($strSql) {
        if($res = $this->_resQuery($strSql)) {
            return sqlite_fetch_all($res,SQLITE_ASSOC);
        }
        return array();
    }

    public function BeginTransaction() {
        $this->NonQuery("BEGIN TRANSACTION");
    }

    public function RollbackTransaction() {
        $this->NonQuery("ROLLBACK TRANSACTION");
    }
    
    public function CommitTransaction() {
        $this->NonQuery("COMMIT TRANSACTION");
    }
}
