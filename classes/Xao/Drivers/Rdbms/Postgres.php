<?php
/**
 * this class implements postgresql from postgresql.org
 */
class       Xao_Drivers_Rdbms_Postgres 
extends     Xao_Drivers_Rdbms_DbA
implements  Xao_Drivers_Rdbms_AtomicI
{
    /**
     * Constructor establishes connection to RDBMS server
     * 
     * @param    string    Passed straight through to pg_connect()
     * @param    bool    Whether or not the connection should be persistent
     */
    function __construct(
        $strConnectionString,
        $blnPersistent = false, 
        $blnDebug = false
    ) {
        $this->blnDebug = $blnDebug;
        $this->strDriverType = XAO_DRIVER_RDBMS_TYPE_POSTGRES;
        $this->SetConnString($strConnectionString);
        $this->SetPersistence($blnPersistent);
        ob_start();
            if($this->blnGetPersistence()) {
                $this->_conn = pg_pconnect($this->strGetConnString());
            }
            else {
                $this->_conn = pg_connect($this->strGetConnString());
            }
            $strErr = ob_get_contents();
        ob_end_clean();
        $this->blnCheckConnection($strErr);
    }
    
    /**
     * Force a close to postgres.
     */
    function Disconnect() {
        if($this->_conn) pg_close($this->_conn);
    }
    
    /**
     * Override to use native postgres query execution and error checking
     * 
     * @param   string User SQL query.
     * 
     * @return  resource native postgres SQL execution result resource
     */
    function _resQuery($sql) {
        if($this->_conn) {
            try {
                $res = pg_query($this->_conn,$sql);
            }
            catch(Exception $e) {
                if($strError = pg_result_error($res)) {
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
                    $this->XaoThrowE($e);
                }
                return false;
            }
            return $res;
        }
        else {
            $this->XaoThrow(
                "Cannot execute SQL since a valid connection was not " .
                "esablished.",
                debug_backtrace()
            );
            return false;
        }
    }
    
    /**
     * Override to use more efficient pg_fetch_all()
     * 
     * @param   string User SQL query.
     * 
     * @return  array  2D array containing result recordset
     */
    function arrQuery($sql) {
        if($res = $this->_resQuery($sql)) {
            $arr = pg_fetch_all($res);
            if(is_array($arr)) return $arr;
        }
        return array();
    }
    
    /**
     * Override to use more efficient postgres functions
     * 
     * @param   string User SQL query.
     * 
     * @return  array  Contains list of values from first column of each record
     */
    function arrQueryStream($sql,$intColIdx = 0) {
        $arr = array();
        if($res = $this->_resQuery($sql)) {
            $arrTbl = pg_fetch_all($res);
            if(is_array($arrTbl)) {
                foreach($arrTbl AS $arrRow) $arr[] = $arrTbl[$intColIdx];
            }
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
