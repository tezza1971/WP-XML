<?php
/**
 * This class is a base class for general RDBMS entity objects. It is designed
 * to provide basic functions to get and set information to an underlying RDBMS
 * table. It is not to be used directly (hence the _ prefix). It is designed to
 * be extended by some sort of RDBMS entity class. This class has a member 
 * variable enumerating a list of RDBMS types that any given entity class may 
 * support. Posible types are defined in constants in _DbDriver.php. It would
 * be good practice to choose at least one type from these constants and add 
 * them to the list when constructing an entity class which inherits this class.
 * This base class will also use very generic SQL code (such as SELECT *) which
 * is generally not optimised for performance. Some methods might need to be
 * overriden to provide optimal performance if the RDBMS type is predictable.
 */
abstract
class   Xao_Entities_EntBase 
extends Xao_Root 
{
    /**
     * Used to store a list of obververs
     * 
     * @var        array
     */
    private $_arrObservers = array();
    
    /**
     * Reference to an instance of live database object on which to perform SQL
     * 
     * @var     object
     */
    public $objDb;
    
    /**
     * The default table or view name to use for basic SQL queries
     * 
     * @var     string
     */
    protected $_strView;
    
    /**
     * The default table to use for updates and inserts
     * 
     * @var     string
     */
    protected $_strTable;
    
    /**
     * Every entity will only support a limited list of database types.
     * See drivers/rdbms/_DbDriver.php for enumerated constants of known types.
     * 
     * @var     array
     * @see     Xao_Drivers_Rdbms_DbA
     */
    protected $arrSupportedDbTypes = array();
    
    /**
     * This var is optionally used to help optimise SELECT statements.
     * 
     * @var     array
     */
    protected $_arrViewColumns;
    
    /**
     * This var is optionally used to validate columns in INSERT/UPDATE 
     * statements.
     * 
     * @access  private
     * @var     array
     */
    protected $_arrTableColumns;

    /**
     * The entity name may be used for freindly messaging or whatever
     * 
     * @access  public
     * @var     string
     */
    public $strName;
    
    /**
     * This is only used if and when the entity is used as a request handler.
     * If this is the case, the user should send the request superglobal to 
     * the $this->SetRequest() method which will populate this variable. For
     * More information, check out RequestMap::ExecuteRequests().
     */
    public $arrReq = array();
    
    /**
     * Constructor method used to do basic entity initialisation.
     * 
     * @param   object  Live database instance
     * @param   string  Nominal user-friendly name of this entity
     * @param   string  Physical RDBMS table name for ins/upd/del
     * @param   string  table name or view on which to select
     */
    public function __construct(
        $objDb,
        $strName,
        $strTable   = null,
        $strView    = null
    ) {
        if(is_null($strTable)) $strTable = $strName;
        if(is_null($strView)) $strView = $strTable;
        if(!is_object($objDb) || !$objDb->blnCheckConnection("",false)) {
            $this->SetGoodToGo(false);
            $this->intCountErrors($objDb,true);
            $this->XaoThrow(
                "Cannot initialise entity. Need valid instance of live " .
                "database."
                , debug_backtrace()
            );
            return;
        }
        $this->objDb = $objDb;
        $this->blnDebug = $this->objDb->blnDebug;
        if(!in_array(
            $this->objDb->strGetDriverType(),
            $this->arrGetSupportedDbTypes()
        )) {
            $this->SetGoodToGo(false);
            $this->XaoThrow(
                "The RDBMS type you are attempting to use (".
                $this->objDb->strGetDriverType.") is not supported by this " .
                "entity. supported RDBMS types include: ".
                implode(",",$this->arrGetSupportedDbTypes())
                , debug_backtrace()
            );
            return;
        }
        $this->SetEntityName($strName);
        $this->SetDefaultTable($strTable);
        $this->SetDefaultView($strView);
        if(!count($this->arrErrors)) $this->SetGoodToGo(true);
    }
    
    /**
     * Add observers to the list
     */
    public function AddObserver(object $objObserver) {
        if(method_exists($objObserver,"On")) {
            $this->_arrObservers[] = $objObserver;
        }
    }
    
    private function Notify($strEvent,$arrPayload) {
        foreach($this->_arrObservers AS $objObserver) {
            $objObserver->On($strEvent,$arrPayload);
        }
    }
    
    /**
     * Accessor to fetch supported driver types
     */
    public function arrGetSupportedDbTypes() {
        return $this->arrSupportedDbTypes;
    }
    
    /**
     * This function is only really used if the entity is used as a request
     * handler via the RequestMap::ExecuteRequests() function. Usually this
     * method will be passed a superglobal before the entity object itself is
     * passed to the ExecuteRequests method.
     */
    public function SetRequest(&$arrReq) {
        if(is_array($arrReq)) $this->arrReq = $arrReq;
    }
    
    /**
     * Create this entity in the RDBMS if it does not already exist
     * 
     * It is envisioned that a developer may create a set of entity classes,
     * each with their own table creation scripts. The idea being that this
     * function can be called for each entity as part of a deployment/setup
     * strategy for the application. I imagine it would be as simple as cut/
     * pasting some SQL dump code (Data Definition Language (DDL)) into a 
     * string literal and running it through $this->objDb->NonQuery(). Obviously
     * the associated entity object (table) won't be the only thing in the DDL.
     * The developer would want to take care of dependant sequences, triggers,
     * rules, foreign keys or whatever - all inside this function.
     * There's nothing to define in this base class. Implementations are always
     * and overriden version of this function. If Build() is not overridden but
     * is called by an app, then the base function (this one) will throw an
     * error saying so.
     */
    public function Build() {
        $this->XaoThrow(
            "_EntBase::Build(): This entity does not implement it's " .
            "creation in the RDBMS."
            , debug_backtrace()
        );
    }
    
    /**
     * Retrieve a new sequence number to use as an ID
     * 
     * A developer may optionally override this method to get the next value on 
     * a sequence. This may be useful in applications when the creation of a new
     * entity record requires simultaneous creation of other entity records -
     * which all require the new (potential) ID of one. In other words, you can
     * get a new ID first, then insert it later - along with other entities that
     * reference it. The exact SQL code used will depend on the RDBMS type and
     * the design implementation. Some database designers do not use synthetic
     * identifiers.
     */
    public function intGetNewId() {
        $this->XaoThrow(
            "_EntBase::Build(): This entity does not implement retrieval of a ".
            "new numeric identifier as a descrite function. It may not be " .
            "applicable."
            , debug_backtrace()
        );
    }
    
    /**
     * Wrapper to keep debug modes in sync with database driver object.
     * 
     * Basically entity classes are tightly bound to the database, so it is 
     * important that the same debug level policies apply. Debug mode is mostly
     * used by exception routines. Generally more information (which may be
     * sensitive) is displayed with error messages.
     * 
     * @param   bool    Whether or not objects should be in debug mode.
     */
    public function SetDebug($bln) {
        $this->blnDebug = (bool)$bln;
        $this->objDb->blnDebug = (bool)$bln;
    }

    /**
     * Set the name of this entity
     * 
     * A developer may want to register a user-friendly name for the entity 
     * which doesn't necesarily correspond with the PHP class name. It also may
     * not correspond with the RDBMS object (table) name.
     * 
     * @param   string  name of the entity. For nominal usage. No rules apply.
     */
    public function SetEntityName($strName) {
        if(!is_string($strName) || !strlen(trim($strName))) {
            $this->SetGoodToGo(false);
            $this->XaoThrow(
                "Entity name needs to be a valid string."
                , debug_backtrace()
            );
            return;
        }
        $strName = trim($strName);
        if(strlen($strName) > 256) {
            $this->XaoThrow(
                "Entity name should be short. Specified name was: ".$strName
                , debug_backtrace()
            );
        }
        $this->strName = $strName;
    }
    
    /**
     * The defalt table is used to INSERT/UPDATE/DELETE data for a given entity.
     * 
     * @param   string  physical table name on which IUD ops can be performed.
     */
    public function SetDefaultTable($strName) {
        if(!is_string($strName) || !strlen(trim($strName))) {
            $this->SetGoodToGo(false);
            $this->XaoThrow(
                "Default database table name needs to be a valid string."
                , debug_backtrace()
            );
            return;
        }
        $this->_strTable = trim($strName);
    }
    
    /**
     * The default view is used by generic "get data" methods for entity data.
     * 
     * A view might involve more related tables and provide a richer record
     * set when it comes to a given entity. It may also be easier to filter (ie.
     * provide WHERE clauses for). Setting this is generally part of object
     * construction. If a value is not supplied to _EntBase constructor, then
     * _EntBase will set it to the current table name.
     * 
     * @param   string  name of table or view to SELECT data from.
     */
    public function SetDefaultView($strName) {
        if(!is_string($strName) || !strlen(trim($strName))) {
            $this->SetGoodToGo(false);
            $this->XaoThrow(
                "Default database view name needs to be a valid string."
                , debug_backtrace()
            );
            return;
        }
        $this->_strView = trim($strName);
    }

    /**
     * The is a generic method for returning a table object which is populated
     * with a record set from this entity instance.
     * 
     * @param   string  An optional where clause if all the data is not required
     * @return  object  A table object populated with SELECT results
     */
    public function tblGetRecords($strWhere = "") {
        if(!$this->blnGoodToGo()) return;
        $arrRecords = $this->arrGetRecords($strWhere);
        if(is_array($arrRecords)) {
            $tbl = new DbTable($arrRecords);
        }
        if($this->intCountErrors($tbl,true)) return false;
        return $tbl;
    }
    
    /**
     * Improve performance and/or filter columns returned.
     * 
     * Optional method to specify the named of collumns that SELECT is 
     * interested in. This can be used to filter which fields are returned, but
     * it can also be used to improve memory management/performance if the 
     * RDBMS query function knows roughly what to expect back from the RDBMS.
     * 
     * @param   array   List of column names.
     */
    public function SetViewColumns($arrCols) {
        if(!$this->blnGoodToGo()) return;
        if(!is_array($arrCols)) {
            $this->XaoThrow(
                "_EntBase->SetViewColumns(): This method expects an array" .
                " of strings representing column names to SELECT on. The" .
                " supplied argument was not an array."
            );
            return;
        }
        $this->_arrViewColumns = $arrCols;
    }
    
    /**
     * Convenience function to return a list of names used if available.
     * 
     * @return  array   A list of column names used int SELECTs.
     */
    public function arrGetViewColumns() {
        if(is_array($this->_arrViewColumns)) return $this->_arrViewColumns;
        $this->XaoThrow(
            "_EntBase->arrGetViewColumns(): A valid list of columns has not" .
            " been supplied using _EntBase->SetViewColumns()."
        );
    }

    /**
     * Specify a list of columns which apply to insert and update operations.
     * 
     * This may seem pretty pointless but it allows filtering of arrays which
     * are passed to the Insert() and Update() functions. This means you can
     * throw $_REQUEST at these functions. Only the array keys matching the
     * column names here will be used. There are two optional formats for the
     * array passed to this function. The simplest is
     * 
     * @param   array   list of strings, or list of arrays.
     * @param    bool    apply column list view as well
     */
    public function SetTableColumns($arrCols,$blnApplyToView = true) {
        if(!$this->blnGoodToGo()) return;
        if(!is_array($arrCols)) {
            $this->XaoThrow(
                "_EntBase->SetTableColumns(): This method expects an array" .
                " of strings representing column names to SELECT on. The" .
                " supplied argument was not an array."
            );
            return;
        }
        $this->_arrTableColumns = $arrCols;
        
        if($blnApplyToView) {
            $arrViewCols = $arrCols;
            if(is_array($arrCols[0])) {
                $arrViewCols = array();
                foreach($arrCols AS $arrCol) {
                    $arrViewCols[] = $arrCol[0];
                }
            }
            $this->SetViewColumns($arrViewCols);
        }
    }
    
    public function blnIsSqlInjectionSafe($strSub) {
        // TODO: This needs a lot of work
        $arrMatches = array();
        $strPattern = "/('\\s*(INSERT|UPDATE|DELETE)\\s*)/i";
        if(
            preg_match(
                $strPattern, 
                $strSub, 
                $arrMatches
            )
        ) {
            $strDetails = "";
            if($this->blnDebug) $strDetails .= " PATTERN: ".$strPattern;
            $this->XaoThrow(
                "An SQL Injection attack has been detected. The database " .
                "query has not been executed and your request has been ignored."
                .$strDetails
            );
            return false;
        }
        return true;
    }
    
    /**
     * Convience function to return a list of names used if available.
     * 
     * @return  array   A list of column names used int table INSERT/UPDATEs
     */
    public function arrGetTableColumns() {
        if(is_array($this->_arrTableColumns)) return $this->_arrTableColumns;
        $this->XaoThrow(
            "_EntBase->arrGetTableColumns(): A valid list of columns has not" .
            " been supplied using _EntBase->SetTableColumns()."
        );
    }

    /**
     * General purpose record accessor method
     * 
     * @parma    string    SQL where clause
     * @param    bool    whether or not to select distinct
     */
    public function arrGetRecords(
        $strWhere = "",
        $blnDistinct = false,
        $strOrderBy = ""
    ) {
        if(!$this->blnGoodToGo()) return;
                                        // build SQL query
        $strDistinct = "";
        if($blnDistinct) $strDistinct = " DISTINCT ";
        $strCols = "*";
        if(is_array($this->_arrViewColumns)) {
            $strCols = implode(",",$this->_arrViewColumns);
        }
        $sql = "SELECT ".$strDistinct.$strCols." FROM ".$this->_strView;
        if($strWhere) $sql .= " WHERE ".$strWhere;
        if($strOrderBy) $sql .= " ORDER BY ".$strOrderBy;
                                        // execute query and trap errors
        $arr = $this->objDb->arrQuery($sql);
        if(!$this->intCountErrors($this->objDb,true)) return $arr;
    }
    
    public function mxdGetOne($strField,$strWhere = "") {
        // $strField is vulnerable to injection but shouldn't be user-based
        $sql = "SELECT ".$strField." FROM ".$this->_strView;
        if($strWhere) $sql .= " WHERE ".$strWhere;
        $mxd = $this->objDb->mxdGetOne($sql);
        if(!$this->intCountErrors($this->objDb,true)) return $mxd;
        return false;
    }
    
    /**
     * Insert a record into the table represented by this object.
     * 
     * The supplied array needs to either an associative array where the array
     * keys are the column names and the array key values are the column values,
     * or, each column/value/type/[nullability] is passed as an array - making
     * the first argument to this function a 2D array. Lotta error checking
     * going on here. Please see API docs for the _ParseInput() method for more
     * detailed information.
     * 
     * @param   array   an associative array with data for each affected column.
     */
    public function Insert($arrNew) {
        if(!$this->blnGoodToGo()) return;
        $arrFields = null;
        $arrValues = null;
        if(!$this->_ParseInput($arrNew,$arrFields,$arrValues)) {
            $this->XaoThrow(
                "Inser(): Input parse error. See previous exceptions."
                ,debug_backtrace()
            );
            return false;
        }
        $sql = "INSERT INTO ".$this->_strTable." (".implode(",",$arrFields)
            .") VALUES (".implode(",",$arrValues).")";
        $this->objDb->NonQuery($sql,true);
        $this->intCountErrors($this->objDb,true);
        if(!count($this->arrErrors) && count($this->_arrObservers)) {
            $this->Notify("Insert",$arrNew);
        }
        if(!count($this->arrErrors)) return true;
        return false;
    }
    
    /**
     * The Update method requires a where clause to work. This is not a security
     * measure as much as an awareness thing to prevent accidental mass updates.
     * 
     * @param   array   an associative array with data for each affected column.
     * @param   string  the SQL where clause.
     */
    public function Update($arrNew,$strWhere) {
        if(!$this->blnGoodToGo()) return;
        $strWhere = trim($strWhere);
        if(!strlen($strWhere)) {
            $this->XaoThrow("_EntBase::Update(): The where clause is missing.");
            return;
        }
        $arrFields = null;
        $arrValues = null;
        if(!$this->_ParseInput($arrNew,$arrFields,$arrValues,false)) {
            $this->XaoThrow(
                "Inser(): Input parse error. See previous exceptions."
                ,debug_backtrace()
            );
            return;
        }
        $arrSets = array();
        foreach($arrFields AS $intIdx => $strField) {
            $arrSets[] = $strField." = ".$arrValues[$intIdx];
        }
        $sql = "UPDATE ".$this->_strTable." SET ".implode(",",$arrSets)
            ." WHERE ".$strWhere;
        $this->objDb->NonQuery($sql);
        $this->intCountErrors($this->objDb,true);
        if(!count($this->arrErrors) && count($this->_arrObservers)) 
            $this->Notify("Update",$this->arrGetRecords($strWhere));
        if(!count($this->arrErrors)) return true;
        return false;
    }
    
    /**
     * The Delete method requires a where clause to work. This is not a security
     * measure as much as an awareness thing to prevent accidental table 
     * deletion.
     * 
     * @param   string  the SQL where clause.
     */
    public function Delete($strWhere) {
        if(!$this->blnGoodToGo()) return;
        if(!strlen($strWhere)) {
            $this->XaoThrow("_EntBase::Delete(): The where clause is missing.");
            return;
        }
        $sql = "DELETE FROM ".$this->_strTable." WHERE ".$strWhere;
        $this->objDb->NonQuery($sql);
        if(!count($this->arrErrors) && count($this->_arrObservers)) 
            $this->Notify("Delete",$this->arrGetRecords($strWhere));
        $this->intCountErrors($this->objDb,true);
    }

    /**
     * This is an internal function used be Insert() and Update()
     * 
     * Basically it parses input which may be in one of two formats. An array
     * of field names and another of field values is returned via references
     * in the second and third arguments. The input format may simply be an
     * assotiative array where the array keys match the column names, or it may
     * be a 2 dimensionall array where each field is an array containing up to
     * four values with only the fourth one being optional. The values are:
     * 1) a string containing the name of the field
     * 2) a string or number containing the value of the field
     * 3) a string containing the data type of the field
     * 4) a boolean indicating if the field is nullable or not (default is true)
     * Note that the data type specifier must be available as a method in the
     * driver object prefixed with "Prep". These methods are designed to 
     * sanitise the first argument for inclusion into SQL statememtns and 
     * return the sanised value by reference. Therefore, the value is modified.
     * If only a simple associative array is passed as the first param to this
     * method, then all values are simply treated as text. If the underlying
     * table field is a different data type, they you're leaving it up to the 
     * descression of the RDBMS to automatically cast it for you.
     * Also note that if the SetTableColumns() function is used prior to this 
     * one, then this function will filter out fields not specified with
     * SetTableColumns()
     * 
     * @param   array   An associative array containing data for insert/update
     * @param   array   This recieves a list of the processed fields
     * @param   array   This recieves a list of values to be processed
     * @param   bool    Specified to check for NOT NULL input data
     */
    private function _ParseInput(
        &$arrNew,
        &$arrFields,
        &$arrValues,
        $blnRespectNotNulls = true
    ) {
        $arrFields = array();
        $arrValues = array();
                                        // cosmetic reference
        $arrColSpec = $this->_arrTableColumns;
                                        // begin some basic sanity checks.
        if(!is_array($arrNew)) {
            $this->Throw(
                "_EntBase::_ParseInput(): The Insert or Update method " .
                "expects an array."
                ,debug_backtrace()
            );
            return false;
        }
        if(!count($arrNew)) {
            $this->Throw(
                "_EntBase::_ParseInput(): Array is empty.",debug_backtrace()
            );
            return false;
        }
        
        if(!is_array($arrColSpec)) {
            $this->XaoThrow(
                "_EntBase::_ParseInput(): Method SetTableColumns() has " .
                "received an invalid column specification. It should at least" .
                " be an array of some sort."
                ,debug_backtrace()
            );
            return false;
        }
                                        // create a flat list of column names
        $arrColNames = array();
        foreach($arrColSpec AS $mxdColItem) {
                                        // simple mode
            if(is_string($mxdColItem)) {
                $arrColNames[] = $mxdColItem;
            }
                                        // complex mode
            elseif(is_array($mxdColItem)) {
                if(is_string($mxdColItem[0])) {
                    $arrColNames[] = $mxdColItem[0];
                }
                else {
                    $this->XaoThrow("_EntBase::_ParseInput(): Invalid column " .
                        "specification passed to SetTableColumns(). First " .
                        "value of a column specification should be a string."
                        , debug_backtrace());
                        return false;
                }
            }
            else{
                $this->XaoThrow("_EntBase::_ParseInput(): Invalid column " .
                    "specification passed to SetTableColumns(). Expecting " .
                    "either a string, or an array for each column."
                    , debug_backtrace());
                    return false;
            }
        }
                                        // begin parsing data array
        foreach($arrNew AS $field => $value) {
                                        // ignore items not in the column spec
            if(!in_array($field,$arrColNames)) continue;
            if(in_array($field,$arrColSpec)) {
                                        // DO THE THING
                $arrFields[] = $field;
                $this->objDb->PrepText($value);
                $arrValues[] = $value;
            }
            else {
                foreach($arrColSpec AS $key => $arrColItem) {
                    if(
                        !is_array($arrColItem) 
                        || !count($arrColItem) 
                        || $field != $arrColItem[0]
                    ) continue;
                    if(count($arrColItem) == 1) {
                                        // DO THE THING
                        $arrFields[] = $field;
                        $this->objDb->PrepText($value);
                        $arrValues[] = $value;
                        continue;
                    }
                                        // 0 - name (string)
                                        // 1 - type (string)
                                        // 2 - nullability (bool) (default true)
                                        // 3 - default value callback function
                    $strPrepMethod = "Prep".$arrColItem[1];
                    if(method_exists($this->objDb,$strPrepMethod)) {
                                        // DO THE THING
                        $arrFields[] = $arrColItem[0];
                        if(!array_key_exists(2,$arrColItem)) 
                            $arrColItem[2] = true;
                        if(
                            array_key_exists(3,$arrColItem) 
                            && is_string($arrColItem[3])
                            && method_exists($this,$arrColItem[3])
                        ) {
                            $value = $this->{$arrColItem[3]}($value);
                        }
                        $this->objDb->$strPrepMethod($value,$arrColItem[2]);
                        $arrValues[] = $value;
                    }
                    else {
                        $this->XaoThrow(
                            "_EntBase::_ParseInput(): Could not find method " .
                            "called ".$strPrepMethod."(). Invalid TYPE "
                            .$arrColItem[1]." specified in SetTableColumns " .
                            "for column ".$field
                            ,debug_backtrace()
                        );
                        return false;
                    }
                }
            }
        }
                                        // check for any compulsory fields 
                                        // missing from $arrNew
        $blnMissingFields = false;
        if(
            $blnRespectNotNulls
            && is_array($arrColSpec) 
            && count($arrColSpec) 
            && is_array($arrColSpec[0])
        ) {
            foreach($arrColSpec AS $arrRowSpec) {
                if(count($arrRowSpec) == 3) {
                    if(!$arrRowSpec[2] && !in_array($arrRowSpec[0],$arrFields)){
                        $this->XaoThrow(
                            "_ParseInput(): You appear to be missing data for ".
                            "the following COMPULSORY field: ".$arrRowSpec[0]
                            ,debug_backtrace()
                        );
                        $blnMissingFields = true;
                    }
                }
                else {
                    $this->XaoThrow(
                        "_ParseInput(): incomplete column spec assigned."
                        ,debug_backtrace()
                    );
                    return false;
                }
            }
        }
        if($blnMissingFields) return false;
        return true;
    }
    
    /**
     * Eache item in the array parameter is a comma-separated list of IDs. This
     * method takes an array of lists, collates them all into one vector, and
     * eliminated duplicates.
     */
    public function arrNormaliseAncestorLists($arrLists) {
        if(!is_array($arrLists)) {
            $this->XaoThrow(
                "_EntBase::arrNormaliseAncestorLists(): First argument " .
                "should be an ARRAY.",
                debug_backtrace()
            );
            return;
        }
        $arrAll = array();
        foreach($arrLists AS $strList) {
            $arrAll = array_merge($arrAll,explode(",",$strList));
        }
        $arrAll = array_unique($arrAll);
        if(count($arrAll) && $arrAll[0] == "") array_shift($arrAll);
        return $arrAll;
    }
}