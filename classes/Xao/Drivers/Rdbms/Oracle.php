<?php
class       Xao_Drivers_Rdbms_Oracle 
extends     Xao_Drivers_Rdbms_DbA
implements  Xao_Drivers_Rdbms_AtomicI
{
    /**
     * Once this is set by OCIParse() from the SQL code, it is used through-out
     * this class to run various things.
     * 
     * @var        resource
     */
    private $resStatement;
    
    /**
     * This is used internally by this class to store the memory limit as
     * calculated by _EstablishMemLimitBytes() for the purposes of the LOB
     * functions.
     * 
     * @var        int
     */
    private $intMemLimitBytes;
    
    /**
     * See http://au.php.net/manual/ro/function.oci-execute.php
     * 
     * @var        int
     */
    private $intExecMode = OCI_COMMIT_ON_SUCCESS;
    
    /**
     * Used to record that the developer last set the date format to.
     * 
     * @var        string
     */
    private $strDateFormat;

    /**
     * The constructor initialises the database connection and therefore
     * requires all the parameters which are specified via an associative array.
     * 
     * @param    array    See the code for what is expected in this array
     * @return    void
     */
    public function __construct($arrConnParams) {
        $this->strDriverType = XAO_DRIVER_RDBMS_TYPE_ORACLE;
        ob_start();
            $this->_conn = OCIPLogon(
                $arrConnParams["schema"],
                $arrConnParams["secret"],
                $arrConnParams["server"]
            );
            $strErr = ob_get_contents();
        ob_end_clean();
        $this->blnCheckConnection($strErr);
                                        // set the date format to something
                                        // sensible for each session.
        $this->SetDateFormat("YYYY-MM-DD HH24:MI:SS");
    }
    
    /**
     * Setter method to change Oracle's execution mode.
     * 
     * @param   int     Oracle constant OCI_COMMIT_ON_SUCCESS or OCI_DEFAULT
     */
    public function SetExecMode($intConst) {
        $this->intExecMode = $intConst;
    }
    
    /**
     * This handy method allows the user to specify how oracle returns dates.
     * See http://www.adp-gmbh.ch/ora/sql/datetime_format_elements.html or
     * http://www.oracle.com
     *     /technology/pub/articles/oracle_php_cookbook/fuecks_dates.html
     * 
     * @param    string    characters understood by oracle date format parser
     */
    public function SetDateFormat($strFormat) {
        if(!trim($strFormat) || count($this->arrErrors)) return;
        $this->strDateFormat = $strFormat;
        $sql = "ALTER SESSION SET NLS_DATE_FORMAT='".$this->strDateFormat."'";
        $this->NonQuery($sql);
    }
    
    /**
     * use PHP's memory_limit config var to determine how much we'll set aside
     * for oracle's large objects (LOBs).
     * 
     * @return    void
     */
    private function _EstablishMemLimitBytes() {
        $this->intMemLimitBytes = 0;
        $strMLimit = trim(get_cfg_var('memory_limit'));
        $arrMatches = array();
        $blnMatch = preg_match("/(\d)+([\w\D])+/",$strMLimit,$arrMatches);
        if($blnMatch && count($arrMatches) == 3) {
            $intLimit = $arrMatches[1];
                                        // push to upper-case and return only 
                                        // the first char.
            $strUnits = chr(ord(strtoupper($arrMatches[2])));
            if($strUnits == "M") {
                $this->intMemLimitBytes = $intLimit * 1024 * 1024;
            }
            elseif($strUnits == "K") {
                $this->intMemLimitBytes = $intLimit * 1024;
            }
            elseif($strUnits == "B") {
                $this->intMemLimitBytes = $intLimit;
            }
        }
    }

    /**
     * A wrapper function used all through the class to reliably create
     * statement resources on the $this->resStatement member variable and return
     * true or false upon success/failure.
     * 
     * @param    string    SQL code
     */
    private function _blnParseStatement($strSql) {
        if(count($this->arrErrors)) return;
        if(!$this->_conn) {
            $this->XaoThrow(
                "Cannot execute SQL since a valid connection was not "
                ."esablished.",
                debug_backtrace()
            );
            $this->resStatement = null;
            return;
        }
                                        // test if the SQL is valid
        $this->resStatement = OCIParse($this->_conn,$strSql);
        if(!$this->resStatement) {
            $this->_TestForErrors($this->_conn,"Database SQL parse error: ");
            $this->resStatement = null;
            return;
        }
        return true;
    }
    
    /**
     * This method is a single point of access to run all SQL statements (LOBs
     * excepted)
     * 
     * @param    string    SQL code
     */
    function _resQuery($strSql) {
        if(!$this->_blnParseStatement($strSql)) return;
                                        // try to run the SQL
        $res = OCIExecute($this->resStatement,$this->intExecMode);
        if(!$res) {
            $this->_TestForErrors($this->resStatement,"SQL Execution Error: ");
            return;
        }
        return $res;
    }
    
    /**
     * Typically used by IUD operations, this method is designed specifically
     * for SQL statements that do not return results, this method provides all
     * the low- level functionality to return an error managed, easy to use
     * service.
     * 
     * @param    mixed    an array of SQL statements or a single SQL statement.
     * @param   bool    whether or not to throw a warning exception if no rows
     *                     are     affected
     * @return     void
     */
    function NonQuery($mxdQueries,$blnWarnNoChanges = false) {
        if(is_array($mxdQueries)) {
            foreach($mxdQueries as $strSql) {
                if(!$this->_blnParseStatement($strSql)) return;
                if(!OCIExecute($this->resStatement,OCI_DEFAULT)) {
                    $this->_TestForErrors(
                        $this->resStatement,
                        "NonQuery SQL Execution Error: "
                    );
                    OCIFreeStatement($this->resStatement);
                    $this->resStatement = null;
                }
                if(
                    $blnWarnNoChanges 
                    && OCIRowCount($this->resStatement) === 0
                ) {
                    if($this->blnDebug) $strMore = " : \n\n".$strSql;
                    $this->XaoThrow(
                        "DbOracle::NonQuery(): No rows were created or" .
                        " changed with query".$strMore,
                        debug_backtrace()
                    );
                }
            }
            if(count($this->arrErrors)) {
                return;
            }
            else {
                $this->CommitTransaction();
                return true;
            }
        }
        return $this->_resQuery($mxdQueries);
    }
    
    /**
     * Designed specifically for SQL statements that do return results, this
     * method provides all the low-level functionality to provide an error
     * managed, easy to use service. All results are returned as an associative
     * array as is the standard for this method across all databases in the XAO
     * data access layer.
     * 
     * @param   string    a single SQL statement.
     * @return  void
     */
    function arrQuery($strSql) {
        if(!$this->_resQuery($strSql)) return;
        $arrRow = array();
        $arr2d = array();
        $intColCount = OCINumCols($this->resStatement);
        $arrColNames = array(); $arrColNames[0] = null;
        $arrColTypes = array(); $arrColTypes[0] = null;
        for($i = 1; $i <= $intColCount; $i++) {
            $arrColNames[$i] = OCIColumnName($this->resStatement, $i);
            $arrColTypes[$i] = OCIColumnType($this->resStatement, $i);
        }
                                        // If we don't have to check for CLOBs,
                                        // the processing time is cheaper.
        if(in_array("CLOB",$arrColTypes) || in_array("BLOB",$arrColTypes)) {
            $this->_DoLOBQuery2d(
                $arr2d,
                $intColCount,
                $arrColNames,
                $arrColTypes
            );
        }
        else {
            while (
                OCIFetchInto(
                    $this->resStatement, $arrRow, OCI_ASSOC + OCI_RETURN_NULLS
                )
            ) {
                $arr2d[] = $arrRow;
            }
        }
        OCIFreeStatement($this->resStatement);
        $this->resStatement = null;
        return $arr2d;
    }
    
    /**
     * This is a handy method for returning the results of a single column in a
     * 1-dimensional array.
     * 
     * @param    string    SQL code
     * @param    string    optional name of the column is more than one is present
     * @return    array    The one-dimensional array
     */
    function arrQueryStream($strSql,$strColName = "") {
        $arrRes = array();
        if(!$this->_resQuery($strSql)) return;
        if(
            !$this->resStatement
            || !OCIFetchStatement(
                $this->resStatement,$arrRes,0,-1,OCI_FETCHSTATEMENT_BY_COLUMN
            )
        ) return;
        if(!count($arrRes)) return;
        if($strColName) {
            $strColName = strtoupper($strColName);
            if(array_key_exists($strColName,$arrRes)) return $arrRes[$strColName];
            $this->XaoThrow(
                "Specified column name (".$strColName.") does not exist",
                debug_backtrace()
            );
            return;
        }
        else {
            return current($arrRes);
        }
    }
    
    function _DoLOBQuery2d(
        &$arr2d,
        &$intColCount,
        &$arrColNames,
        &$arrColTypes
    ) {
        if(!$this->resStatement) return;
        if(!is_integer($this->intMemLimitBytes)) 
            $this->_EstablishMemLimitBytes();
        $blnThrewOverflowExpn = false;
        $blnMemFncExists = function_exists("memory_get_usage");
        while (OCIFetch($this->resStatement)) {
            $arrRow = array();
            for($i = 1; $i <= $intColCount; $i++) {
                $tmp = OCIResult($this->resStatement, $i);
                $strType = $arrColTypes[$i];
                $strColName = $arrColNames[$i];
                if(!$strType) {
                    $arrRow[$strColName] = "OCIColumnType unavailable.";
                }
                elseif(is_string($tmp) || is_null($tmp)) {
                    $arrRow[$strColName] = $tmp;
                }
                elseif(
                    is_object($tmp) 
                    && ($strType == "CLOB" || $strType == "BLOB")
                ) {
                                        // none of the code inside the following
                                        // IF condition has been tested.
                    if($this->intMemLimitBytes && $blnMemFncExists) {
                                        // build in a 500K safety margin
                        $intMemAvail = $this->intMemLimitBytes 
                            - memory_get_usage() - 500000;
                        if($tmp->size() >= $intMemAvail) {
                            $arrRow[$strColName] = $strType." (".$tmp->size().") ".
                                "value too large for available memory (".
                                $intMemAvail."). Try increasing PHPs " .
                                "memory_limit (".$this->intMemLimitBytes.").";
                                        // only throw an exception once for
                                        // each query.
                            if(!$blnThrewOverflowExpn) {
                                $this->XaoThrow(
                                    "PHP's memory limit was " .
                                    "exceeded when attempting to retrieve a " .
                                    "[character] large object for the Oracle " .
                                    "database.",
                                    debug_backtrace()
                                );
                                $blnThrewOverflowExpn = true;
                            }
                                        // proceed to the next column
                            continue;
                        }
                    }
                    $arrRow[$strColName] = $tmp->load();
                }
                else {
                    $arrRow[$strColName] = "Unsupported column type: " .
                        "OCIColumnType: ".$strType;
                }
            }
            $arr2d[] = $arrRow;
        }
    }
    
    function blnPutClob(&$strData,$strTable,$strColumn,$strWhere) {
        $sql = "UPDATE $strTable SET $strColumn = EMPTY_CLOB() " .
            "WHERE $strWhere returning $strColumn into :lob";
        $res = $this->_blnParseStatement($sql);
        if(!$res) return;
        $lob = OCINewDescriptor($this->_conn, OCI_D_LOB);
        OCIBindByName($this->resStatement, ':lob', &$lob, -1, OCI_B_CLOB);
        $res = OCIExecute($this->resStatement,OCI_DEFAULT);
        if(!$res) {
            $this->_TestForErrors(
                $this->resStatement,
                "blnPutClob SQL Execution Error: "
            );
            return;
        }
        if(!$lob->save($strData)) {
            $this->XaoThrow(
                "Could not save [character] large object to ".$strTable,
                debug_backtrace()
            );
            return;
        }
        $this->CommitTransaction();
        $lob->free();
        OCIFreeStatement($this->resStatement);
        $this->resStatement = null;
        return true;
    }
    
    function blnPutBlob(&$strData,$strTable,$strColumn,$strWhere) {
        $sql = "UPDATE $strTable SET $strColumn = EMPTY_BLOB() " .
            "WHERE $strWhere returning $strColumn into :lob";
        $res = $this->_blnParseStatement($sql);
        if(!$res) return;
        $lob = OCINewDescriptor($this->_conn, OCI_D_LOB);
        OCIBindByName($this->resStatement, ':lob', &$lob, -1, OCI_B_BLOB);
        $res = OCIExecute($this->resStatement,OCI_DEFAULT);
        if(!$res) {
            $this->_TestForErrors(
                $this->resStatement,
                "blnPutBlob SQL Execution Error: "
            );
            return;
        }
        if(!$lob->save($strData)) {
            $this->XaoThrow(
                "Could not save [binary] large object to ".$strTable,
                debug_backtrace()
            );
            return;
        }
        $this->CommitTransaction();
        $lob->free();
        OCIFreeStatement($this->resStatement);
        $this->resStatement = null;
        return true;
    }

    function intGetSeqNextVal($strSeq) {
        if(count($this->arrErrors)) return;
        return (int)$this->mxdGetOne("SELECT ".$strSeq.".nextval FROM dual");
    }
    
    function BeginTransaction() {
                                        // finish off any unfinished work before
                                        // beginning a new batch.
        $this->CommitTransaction();
                                        // this means oracle won't commit 
                                        // statements automatically
        $this->intExecMode = OCI_DEFAULT;
    }
    
    function RollbackTransaction() {
                                        // restore to default behavior
        $this->intExecMode = OCI_COMMIT_ON_SUCCESS;
        ob_start();
            OCIRollback($this->_conn);
            $strErr = ob_get_contents();
        ob_end_clean();
        if(strlen($strErr)) $this->XaoThrow($strErr,debug_backtrace());
        $this->_TestForErrors($this->_conn,"Transaction Rollback error: ");
    }
    
    function CommitTransaction() {
                                        // restore to default behavior
        $this->intExecMode = OCI_COMMIT_ON_SUCCESS;
        ob_start();
            OCICommit($this->_conn);
            $strErr = ob_get_contents();
        ob_end_clean();
        if(strlen($strErr)) $this->XaoThrow($strErr,debug_backtrace());
        $this->_TestForErrors($this->_conn,"Transaction Commit error: ");
    }

    function _TestForErrors($res,$strPrefix = "Database Error: ") {
        if($arrErrors = OCIError($res)) {
            $this->_MarkOffset($arrErrors);
            if($this->blnDebug) {
                $strDebugMsg = "\nDEBUG DATA: \n\n";
                foreach($arrErrors AS $strCode => $strMsg) {
                    $strDebugMsg .= $strCode.": ".$strMsg."\n";
                }
                $this->XaoThrow($strPrefix.$strDebugMsg,debug_backtrace());
            }
            else {
                $this->XaoThrow(
                    $strPrefix." Please contact your helpdesk.",
                    debug_backtrace()
                );
            }
        }
    }

    function _MarkOffset(&$arrOraError) {
        if(
            array_key_exists("sqltext",$arrOraError) 
            && array_key_exists("offset",$arrOraError)
        ) {
            if($arrOraError["offset"]) {
                $arrOraError["sqltext"] = substr_replace(
                    $arrOraError["sqltext"],
                    "--ERROR--",
                    $arrOraError["offset"],
                    0
                );
            }
        }
    }
}
