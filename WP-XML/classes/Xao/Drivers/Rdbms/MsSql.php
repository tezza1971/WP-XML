<?php
/**
 * MS SQL specific driver class
 */
class       Xao_Drivers_Rdbms_MsSql 
extends     Xao_Drivers_Rdbms_DbA
implements  Xao_Drivers_Rdbms_AtomicI
{
    private $_dsn = array();
    /**
     * Constructor establishes connection to RDBMS server
     * 
     * @param    string    Passed straight through to pg_connect()
     * @param    bool    Whether or not the connection should be persistent
     */
    function __construct(
        $arrParams = null,
        $blnPersistent = false, 
        $blnDebug = false
    ) {
        if(!is_array($arrParams)) $arrParams = array();
        $this->blnDebug = $blnDebug;
        $this->strDriverType = XAO_DRIVER_RDBMS_TYPE_MSSQL;
        $this->SetConnString(implode(",",$arrParams));
        $this->SetPersistence($blnPersistent);
        ob_start();
            if($this->blnGetPersistence()) $fcnConnect = "mssql_pconnect";
            else $fcnConnect = "mssql_connect";
            if(
                array_key_exists("secret",$arrParams)
                && array_key_exists("user",$arrParams)
                && array_key_exists("server",$arrParams)
            ) {
                $this->_conn = $fcnConnect(
                    $arrParams["server"],
                    $arrParams["user"],
                    $arrParams["secret"]
                );
            }
            elseif (
                array_key_exists("server",$arrParams)
            ) {
                $this->_conn = $fcnConnect($arrParams["server"]);
            }
            else {
                $this->_conn = $fcnConnect();
            }
            if($this->_conn && array_key_exists("db",$arrParams)) {
                mssql_select_db($arrParams["db"],$this->_conn);
            }
            $strErr = ob_get_contents();
        ob_end_clean();
        $this->_dsn = $arrParams;
        if($strErr) $this->blnCheckConnection($strErr);
        else $this->blnCheckConnection(mssql_get_last_message());
    }
    
    /**
     * Force a close to postgres.
     */
    function Disconnect() {
        if($this->_conn) mssql_close($this->_conn);
    }
    
    /**
     * Override to use native postgres query execution and error checking
     * 
     * @param   string User SQL query.
     * 
     * @return  resource native postgres SQL execution result resource
     */
    function _resQuery($sql) {
        if(!$this->_conn) {
            $this->XaoThrow(
                "Cannot execute SQL since a valid connection was not " .
                "esablished.",
                debug_backtrace()
            );
            return false;
        }
        try {
            ob_start();
            if(array_key_exists("db",$this->_dsn)) {
                mssql_select_db($this->_dsn["db"],$this->_conn);
            }
            $res = mssql_query($sql,$this->_conn);
            $strErr = ob_get_contents();
            ob_end_clean();
            if($res === false) {
                $err = mssql_get_last_message($res);
                if($this->blnDebug) $err .= ": query: ".$sql;
                throw new Exception($err);
            }
            if($strErr) throw new Exception($strErr);
        }
        catch(Exception $e) {
            if(is_resource($res) && $strError = mssql_get_last_message($res)) {
                if($this->blnDebug) {
                    $this->XaoThrowE($e, "The database server reported a "
                        ."problem executing \n\n".$sql."\n\n".$strError
                    );
                }
                else {
                    $this->XaoThrowE($e,"The database reported an error."
                        ." (turn on debugging to see details)."
                    );
                }
            }
            else {
                $err = "unknown error ";
                if($this->blnDebug) $err .= "running ".$sql;
                $this->XaoThrowE($e,$err);
            }
            return false;
        }
        return $res;
    }
    
    /**
     * Override to use more efficient pg_fetch_all()
     * 
     * @param   string User SQL query.
     * 
     * @return  array  2D array containing result recordset
     */
    function arrQuery($sql) {
        try {
            if($res = $this->_resQuery($sql)) {
                $arr = array();
                while($arr[] = mssql_fetch_assoc($res));
                array_pop($arr);
                return $arr;
            }
        }
        catch(Exception $e) {
            $err = "unknown error ";
            if($this->blnDebug) $err .= "running ".$sql;
            $this->XaoThrowE($e,$err);
        }
        return array();
    }
    
    /**
     * Override to use more efficient postgres functions
     * 
     * @param   string     User SQL query.
     * @param    int        The index of the column to use
     * @return  array      Contains list of values from first column of each record
     */
    function arrQueryStream($sql,$intColIdx = 0) {
        $arr = array();
        try {
            if($res = $this->_resQuery($sql)) {
                while($arrRow = mssql_fetch_row($res)) {
                    if(is_array($arrRow) && $intColIdx < count($arrRow)) 
                        $arr[] = $arrRow[$intColIdx];
                }
            }
        }
        catch(Exception $e) {
            $this->XaoThrowE($e);
        }
        return $arr;
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
