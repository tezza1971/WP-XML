<?php
/**
 * This is the 'base' class for all Rdbms classes whether they use native or PDO
 * connections.
 * 
 * This class is never used directly. In that sense, it is the equivilent of an
 * abstract class. However, there are concrete methods here, so it is not
 * abstract - it is a superclass. I could have used magic functions to automate
 * getters and setters, but that makes the code "mysterious" and lacking in
 * documentation.
 */
abstract
class       Xao_Drivers_Rdbms_XaoDbA
extends     Xao_Drivers_BaseDriver
{
    /**
     * This member variable records what type of database driver this is an
     * instance of. It is generally set by the driver implementation class that
     * inherits this class.
     * 
     * @access  private
     * @var     string
     */
    protected $strDriverType;
    
    /**
     * Wheather or not the driver object will be using persistent connections.
     * 
     * @access  private
     * @var     bool
     */
    protected $_blnPsnt = false;
    
    /**
     * The connection string used by the connect statement if applicable
     * 
     * @access  protected
     * @var     string
     */
    protected $_strConn;
    
    /**
     * This function used to run SQL commands that don't return data.
     * 
     * @param   User SQL query.
     * @return  Warn if no records were modified
     */
    abstract public function NonQuery($sql, $blnWarnNoChanges);

    /**
     * General query function to return tabular result sets.
     * 
     * The returned array consists of an array of associative arrays. Each
     * associative array is single record. The array keys being the column name
     * and the array values being the column values. This format is important to
     * the XAO framework for converting database results to XML.
     * 
     * @param   string  User SQL query.
     * 
     * @return  array   2D array containing result records
     */
    abstract public function arrQuery($sql);
        
    /**
     * Get a table object using a query that normally returns a resultset.
     * 
     * The returned table object is a traversable recordset with interator
     * methods and, potentialy, metadata. See DbTable for more info.
     * 
     * @param   string  User SQL query.
     * 
     * @return   object  DbTable object wrapping query result set 
     */
    public function tblQuery($sql) {
        if(count($this->arrErrors)) return;
        $arr = $this->arrQuery($sql);
        if(!is_array($arr)) return;
        if(!count($arr)) return $arr;
        $tbl = new DbTable($arr);
        if(!$this->intCountErrors($tbl,true)) return $tbl;
    }
    
    
    /**
     * Returns the first column of all result records in a single array.
     * 
     * While this method is implemented below, child object should inveriably
     * override it to implement a more efficient version using native function
     * calls.
     * 
     * @param   string  User SQL Query
     * @param   integer The (zero-based) index of the column required
     * 
     * @return   array   Single dimension array having list of records values
     */
    public function arrQueryStream($strSql,$intColIdx = 0) {
        if(count($this->arrErrors)) return;
        $arrRes = $this->arrQuery($strSql);
        if(!is_array($arrRes)) return;
        if(!count($arrRes)) return;
        if(!is_array($arrRes[0])) {
            $this->XaoThrow(
                "XaoDb::arrQueryStream() expected an array or hashes.",
                debug_backtrace()
            );
            return;        
        }
        if(!count($arrRes[0])) {
            $this->XaoThrow(
                "XaoDb::arrQueryStream() There are no columns.",
                debug_backtrace()
            );
            return;        
        }
        $arrStream = array();
        if($intColIdx) {
            if(!isset($arrRes[0][$intColIdx])) {
                $this->XaoThrow(
                    "Specified column index (".$intColIdx.") does not exist",
                    debug_backtrace()
                );
                return;
            }
            if(
                is_string($intColIdx) 
                && !array_key_exists($intColIdx,$arrRes[0])
            ) {
                $this->XaoThrow(
                    "Specified column name (".$intColIdx.") does not exist",
                    debug_backtrace()
                );
                return;
            }
            foreach($arrRes AS $arrRow) $arrStream[] = $arrRow[$intColIdx];
        }
        else {
            foreach($arrRes AS $arrRow) $arrStream[] = current($arrRow);
        }
        return $arrStream;
    }
    
    /**
     * Handy when only the first column of the first record is required.
     * 
     * @param   string  User SQL Query
     * 
     * @return  mixed   Whatever value that was in the RDBMS field.
     */
    public function mxdGetOne($strSql) {
        if(count($this->arrErrors)) return;
        $arrRes = $this->arrQuery($strSql);
        if(!is_array($arrRes)) return;
        if(!count($arrRes)) return;
        if(!is_array($arrRes[0])) return;
        if(!count($arrRes[0])) return;
        foreach($arrRes[0] AS $val) return $val;
    }

    
    /**
     * Used to sanitise user text-data for inclusion in SQL
     * 
     * This function will be deprecated when all dabase drivers are converted
     * to use PDO.
     * Most of the time there should be no reason to override this or
     * any other data sanitsiation methods. These methods are mostly called by
     * users to quickly create queries. SQL injection protection code should be
     * put into this method at some stage (to do). This method prepares raw text
     * with no considderation to it's length. Note, the string is passed "by
     * reference" and therefore has a side effect on the variable supplied in
     * the method call. This helps to reduce memory consumption/performance with
     * large strings.
     * 
     * @param   string  User data to be handled as text. Length unlimited.
     * @param   bool    Whether an error should be thrown if string is empty.
     * @param   bool    Whether or not to escape single quotes
     */
    function PrepText(&$str,$blnNullable = true,$blnEscapeSQL = true) {
        if($blnEscapeSQL) {
            if (get_magic_quotes_gpc()) $str = stripslashes($str);
                                        // The characters _ and % are escaped 
                                        // to prevent DOS attacks by users
                                        // injecting them into your LIKE operands.
                                        // The ; characters is escaped to prevent 
                                        // query stacking.
                                        // The - character is escaped to prevent 
                                        // parts of your SQL code being commented
                                        // out by someone trying to inject
                                        // replacement code.
            // $str = addcslashes($str,"_%;-"); // can't escape stuff twice
            switch($this->strDriverType) {
                case XAO_DRIVER_RDBMS_TYPE_POSTGRES:
                    $str = pg_escape_string($str);
                    break;
                case XAO_DRIVER_RDBMS_TYPE_ORACLE:
                    $str = str_replace("'","''",$str);
                    break;
                case XAO_DRIVER_RDBMS_TYPE_MSSQL:
                    $str = str_replace("'","''",$str);
                    break;
                case XAO_DRIVER_RDBMS_TYPE_ACCESS:
                    $str = str_replace("'","''",$str);
                    break;
                case XAO_DRIVER_RDBMS_TYPE_MYSQL4:
                    $str = mysql_escape_string($str);
                    break;
                case XAO_DRIVER_RDBMS_TYPE_SQLITE:
                    $str = sqlite_escape_string($str);
                    break;
                default:
                    $str = str_replace("'","''",$str);
            }
        }
        if($str) {
            $str = "'$str'";
        }
        else {
            if(!$blnNullable) {
                $this->XaoThrow(
                    "XaoDb::PrepText() Unhandled NOT NULL Exception. " .
                    "See stack trace for details.",
                    debug_backtrace()
                );
            }
            $str = "NULL";
        }
    }
   
    /**
     * Used to sanitise user data-data for inclusion in SQL
     * 
     * This function will be deprecated when all dabase drivers are converted
     * to use PDO. Passing by reference is deprecated anyway.
     * This method could use much more work/testing. Different RDBMSs supporting
     * different formats may need to override this method. or at least process
     * the results of parent:: PrepDate(). I hate parsing dates ARRGGHHH!
     * 
     * @param   string  User data to be handled as a date.
     * @param   string  An optional single-character delimiter to use
     * @param   bool    Whether an error should be thrown if date is empty.
     */
    function PrepDate(&$str,$blnNullable = true,$delim="/") {
        if(
            $this->strDriverType == XAO_DRIVER_RDBMS_TYPE_ORACLE 
            && $str == "SYSDATE"
        ) return;
        if(
            $this->strDriverType == XAO_DRIVER_RDBMS_TYPE_POSTGRES 
            && $str == "CURRENT_TIMESTAMP"
        ) return;
        
        if(strpos($str,$delim) && !strpos($str,":")) {
            if(!preg_match("/(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.](19|20)[0-9]{2}/", $str)) {
                $this->XaoThrow(
                    "The supplied date (".$str.") is not recognised as a " .
                    "valid date format. Try DD/MM/YYYY. The developer should " .
                    "considder using the form validation framework.",
                    debug_backtrace()
                );
                return;
            }
            $arr = explode($delim,$str);
            $arr = array_reverse($arr);
            if(strlen($arr[0]) == 2) {
                if($arr[0] > 50) $arr[0] = "19".$arr[0];
                else $arr[0] = "20".$arr[0];
            }
            elseif(strlen($arr[0]) == 4) {
                // do nothing
            }
            elseif(strpos($str,":")) {
                $this->XaoThrow(
                    "The PrepDate() method does not handle time data.",
                    debug_backtrace()
                );
                return;
            }
            else {
                $this->XaoThrow(
                    "The supplied date (".$str.") is not recognised as a " .
                    "valid date format. Try DD/MM/YYYY. The developer should " .
                    "considder using the form validation framework.",
                    debug_backtrace()
                );
                return;
            }
            $str = "'".sprintf("%04d-%02d-%02d", $arr[0], $arr[1], $arr[2])
                ." 00:00:00"."'";
        }
        // if(strlen(trim($str))) $str = "'$str'";
        elseif($str == "") {
            if(!$blnNullable) {
                $this->XaoThrow(
                    "XaoDb::PrepDate() Unhandled NOT NULL Exception. " .
                    "See stack trace for details.",
                    debug_backtrace()
                );
                return;
            }
            $str = "NULL";
        }
        else {
            $this->XaoThrow(
                "The supplied date (".$str
                .") is not recognised as a valid date format.  Try DD/MM/YYYY",
                debug_backtrace()
            );
        }
    }
    
    /**
     * Used to sanitise user integers for inclusion in SQL
     * 
     * This function will be deprecated when all dabase drivers are converted
     * to use PDO. Passing by reference is deprecated anyway.
     * 
     * @param   integer The user's integer to parse.
     * @param   bool    Whether an error should be thrown if integer is empty.
     */
    function PrepInt(&$int,$blnNullable = true) {
        if($int === "" || $int === false || $int === null) {
            $int = "NULL";
            if(!$blnNullable) {
                $this->XaoThrow(
                    "XaoDb::PrepInt() Unhandled NOT NULL Exception. " .
                    "See stack trace for details.",
                    debug_backtrace()
                );
            }
            return;
        }
        else {
            $int = (integer)$int;
        }
    }

    /**
     * Used to sanitise user boolean for inclusion in SQL
     * 
     * This function will be deprecated when all dabase drivers are converted
     * to use PDO. Passing by reference is deprecated anyway.
     * 
     * @param   mixed The user's integer to parse.
     * @param   bool    Whether an error should be thrown if integer is empty.
     */
    function PrepBool(&$bool,$blnNullable = true) {
        $bool = (integer)$bool;
    }

    /**
     * Basically, for slightly improved security, unless we're running in debug
     * mode, we're gonna keep silent on errors.
     */
    public function XaoThrow($msg,$arrMisc = array()) {
        if($this->blnDebug) 
            echo get_class($this)." RUNNING IN DEBUG MODE: ".$msg;
            Xao_Root::DEBUG($arrMisc,true,"Database error.");
    }
    public function XaoThrowE($e,$msg = "unknown error: ") {
        if($this->blnDebug) {
            echo get_class($this)." RUNNING IN DEBUG MODE: ".$msg;
            Xao_Root::DEBUG($e,true,"Database error.");
        }
    }
  
    /**
     * Sets the persistance status
     * 
     * @param   The connection persistance status
     */
    function SetPersistence($bln) {
        $this->_blnPsnt = $bln;
    }
    
    /**
     * Returns true or false depening on the persistance of the connection
     * 
     * @return  bool    The connection persistance status
     */
    function blnGetPersistence() {
        return $this->_blnPsnt;
    }

    /**
     * Sets the Connection string required by the driver's connection function.
     * This also should the database specific details for the particular 
     * connection.
     * 
     * @param   Connection string required by the driver implementation
     */
    function SetConnString($strConn) {
        $this->_strConn = $strConn;
    }
    
    /**
     * Gets the Connection string
     * 
     * @return  string  Connection string required by the driver implementation
     */
    function strGetConnString() {
        return $this->_strConn;
    }
    
    /**
     * Gets the driver type
     * 
     * @param   string  A string indicating which database system driver is for.
     */
    function strGetDriverType() {
        return $this->strDriverType;
    } 
    
    /**
     * Sets the driver type
     * 
     * @param driver type
     */
    function SetDriverType($str) {
        $this->strDriverType = $str;
    }

}