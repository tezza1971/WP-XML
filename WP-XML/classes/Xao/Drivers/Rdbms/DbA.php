<?php
/**
 * This class is the base class of all native datbase driver classes.
 * 
 * Many of the methods below are abstract because the class is designed to 
 * specify a standard interface across all cache object types.
 * This forms the basis of all RDBMS drivers. All drivers need to inherit it. It
 * provides only basic functionality and specifies some unimplemented methods.
 * the reason an interface class was not used is because the readability of this
 * class is greatly enhanced by keeping all standard methods and associated
 * documentation in one place. A number of convenience methods are also added
 * here to be inherited by all. 
 */
abstract
class       Xao_Drivers_Rdbms_DbA 
extends     Xao_Drivers_Rdbms_XaoDb
{
    /**
     * Every should use this member variable to hold the native connection
     * resource of their implemented RDBMS. This is defined here for convenience
     * and convention.
     * 
     * @var        resource
     */
    protected $_conn;
    
    public function blnCheckConnection($strErr = "",$blnThrow = true) {
        if(is_resource($this->_conn)) return true;
        if($blnThrow) {
            $strThrowErr = "No connection to the database has been made.";
                                        // Warning. DEBUG mode is a security 
                                        // issue. Sensitive info in conn string.
            if($this->blnDebug) {
                $strThrowErr .= "\nCONNECTION STRING: ".$this->strGetConnString();
                if(trim($strErr)) {
                    $strThrowErr .= "\nCONNECTION ERROR: ".trim($strErr);
                }
            }
            $this->XaoThrow($strThrowErr, debug_backtrace());
        }
        return false;
    }
    
    /**
     * All native database modules in PHP have a function to release the 
     * resource handle to their database. This should be implemented here in
     * conjunction with the $this->_conn property.
     */
    abstract public function Disconnect();

    /**
     * Central point of query execution.
     * 
     * Pretty much all user-SQL goes through this point. This method is used by
     * queries that return data (eg. arrQuery()) and also queries that do not 
     * (eg. NonQuery()).
     * 
     * @param   string  User SQL query.
     * 
     * @return  resource native SQL execution result resource
     */
    abstract protected function _resQuery($strSql);
    
    /**
     * This function used to run queries that don't return data.
     * 
     * @param    string   User SQL query.
     * @param    bool     Warn if no records were modified
     */
    public function NonQuery($strSql,$blnWarnNoChanges = false) {
        if(count($this->arrErrors)) return;
        $mxdRes = $this->_resQuery($strSql);
        if($blnWarnNoChanges) {
            if(
                (is_array($mxdRes) && !count($mxdRes)) || $mxdRes === 0
            ) {
                $this->XaoThrow("No records were modified by ".$strSql);
            }
        }
    }

}
