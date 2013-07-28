<?php
/**
 * This is an abstract class which overrides some of the basic database class
 * methods using the PDO way of doing everything. Product-specific RDBMS
 * implementations need to inherit this class mainly to set up the database
 * connection.
 */
abstract
class       Xao_Drivers_Rdbms_PdoA
extends     Xao_Drivers_Rdbms_XaoDbA
{
    /**
     * The PDO data object used to connect to the database. Before the
     * instantiated PDO object is assigned to this member variable, a connection
     * to the database is established in the child class (database-specific).
     * It's public, and should be accessed directly if more complex features
     * (like curosr management) are required.)'
     * 
     * @param   object
     */
    public $objPdo;
    
    /**
     * must use this method to establish a connected PDO object to store locally
     * in $this->objPdo
     */
    abstract function __construct();
    
    /**
     * This function used to run SQL commands that don't return data.
     * 
     * @param   string  SQL query.
     * @param   array   an associative array with values to replace the named
     *                  variables in the accompanying SQL string. The array
     *                  index names need to begin with the colon character.
     */
    public function NonQuery(
        $strSql,
        $blnWarnNoChanges = false, 
        $arrSubstitutions = array()
    ) {
        if(count($this->arrErrors)) return;
        $intRows = 0;
        try {
            if(count($arrSubstitutions)) {
                $objStmt = $this->objPdo->prepare($strSql);
                if($objStmt->execute($arrSubstitutions))
                    $intRows = $objStmt->rowCount();
                $stmt = null;
            }
            else {
                $intRows = $this->objPdo->exec($strSql);
            }
        }
        catch (PDOException $e) {
            $this->XaoThrowE($e);
        }
        if($blnWarnNoChanges && !$intRows) {
            $this->XaoThrow("No records were modified by ".$strSql);
        }
    }
    
    /**
     * General query function to return tabular result sets.
     * 
     * The returned array consists of an array of associative arrays. Each
     * associative array is single record. The array keys being the column name
     * and the array values being the column values. This format is important to
     * the XAO framework for converting database results to XML.
     * 
     * @param   string  User SQL query.
     * @param   array   Substitutions for SQL named variables
     * @param   array   PDO driver options array
     * 
     * @return  array   2D array containing result records
     */
    public function arrQuery(
        $strSql, 
        $arrSubs = array(),
        $arrDbOptions = array()
    ) {
        if(count($this->arrErrors)) return;
        try {
            $objStmt = $this->objPdo->prepare($strSql, $arrDbOptions);
            $objStmt->execute($arrSubs);
            // Xao_Root::DEBUG($objStmt);
            return $objStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(PDOException $e) {
            $this->XaoThrowE($e);            
        }
    }
    
    /**
     * Same as arrQuery() but results are grouped by values of a column
     * 
     * @param   string  User SQL query.
     * @param   string  Name of the column to group the data by
     * @param   array   Substitutions for SQL named variables
     * @param   array   PDO driver options array
     * 
     * @return  array   2D array containing result records
     */
    public function arrQueryGrouped(
        $strSql, 
        $strGrpColName,
        $arrSubs = array(),
        $arrDbOptions = array()
    ) {
        if(count($this->arrErrors)) return;
        try {
            $objStmt = $this->objPdo->prepare($strSql, $arrDbOptions);
            $objStmt->execute($arrSubs);
            return $objStmt->fetchAll(
                PDO::FETCH_COLUMN | PDO::FETCH_GROUP, 
                $strGrpColName
            );
        }
        catch(PDOException $e) {
            $this->XaoThrow($e);            
        }
    }
        
    /**
     * Returns the first column of all result records in a single array.
     * 
     * This implementation takes advantage of PDOs PDO::FETCH_COLUMN
     * 
     * @param   string  User SQL Query
     * @param   integer The (zero-based) index of the column required
     * @param   array   Substitutions for SQL named variables
     * @param   array   PDO driver options array
     * @param   bool    Specify whether only unique results should be returned
     * 
     * @return   array   Single dimension array having list of records values
     */
    public function arrQueryStream(
        $strSql,
        $intColIdx = 0,
        $arrSubs = array(),
        $arrDbOptions = array(),
        $blnUnique = false
    ) {
        if(count($this->arrErrors)) return;
        try {
            $objStmt = $this->objPdo->prepare($strSql, $arrDbOptions);
            $objStmt->execute($arrSubs);
            $intStyle = PDO::FETCH_COLUMN;
            if($blnUnique) $intStyle = PDO::FETCH_COLUMN | FETCH_UNIQUE;
            return $objStmt->fetchAll($intStyle,$intColIdx);
        }
        catch(PDOException $e) {
            $this->XaoThrow($e);            
        }
    }
    
    /**
     * Like arrQueryStream() but returns only UNIQUE values
     * 
     * This is a convenience method which relays the call to arrQueryStream()
     * with it's behavior modified to only return unique values.
     * 
     * @param   string  User SQL Query
     * @param   integer The (zero-based) index of the column required
     * @param   array   Substitutions for SQL named variables
     * @param   array   PDO driver options array
     * 
     * @return   array   Single dimension array having list of records values
     */
    public function arrQueryStreamUnique(
        $strSql,
        $intColIdx = 0,
        $arrSubs = array(),
        $arrDbOptions = array()
    ) {
        return $this->arrQueryStream(
            $strSql,$intColIdx,$arrSubs,$arrDbOptions,true
        );
    }

    /**
     * Handy when only the first column of the first record is required.
     * 
     * @param   string  User SQL Query
     * @param   integer The (zero-based) index of the column required
     * @param   array   Substitutions for SQL named variables
     * @param   array   PDO driver options array
     * 
     * @return  mixed   Whatever value that was in the RDBMS field.
     */
    public function mxdGetOne(
        $strSql,
        $intColIdx = 0,
        $arrSubs = array(),
        $arrDbOptions = array()
    ) {
        if(count($this->arrErrors)) return;
        try {
            $objStmt = $this->objPdo->prepare($strSql, $arrDbOptions);
            $objStmt->execute($arrSubs);
            return $objStmt->fetchColumn($intColIdx);
        }
        catch(PDOException $e) {
            $this->XaoThrow($e);            
        }
    }

}