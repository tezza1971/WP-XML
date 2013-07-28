<?php
/**
 * This class implements XAO caching using the Oracle relational database. So
 * far this class seems to be functioning OK but has not really been tested.
 */
class   Xao_Drivers_Cache_CacheToOracle 
extends Xao_Drivers_Cache_Cache 
{
    /**
     * A reference to an object instance inheriting DbOracle
     * 
     * @var    object
     */
    private $objDb;

    /**
     * The name of the table which will store the cached data
     * 
     * @var    string    
     */
    private $strCacheTableName;

    /**
     * The name of the table containing cache params
     * 
     * @var string  
     */
    private $strCacheParamsTableName;

    /**
     * This value is used whenever a cache param has a null value. The field 
     * where this is stored in the database is not nullable.
     * 
     * @var string  
     */
    public $strNull = "'-- null_param_value --'";
    
    /**
     * Constructor function for this class.
     * 
     * @param   array   A structure containing cache profiles
     * @param   object  A reference to an XAO DbOracle instance
     */
    public function __construct($arrCacheProfiles,&$objDb) {
        $this->objDb = $objDb;
        $this->arrCacheProfiles = $arrCacheProfiles;
    }
    
    /**
     * This Time-To-Live setter is to establish a default
     * 
     * @param    int        number of seconds to set cache life expectancy
     * @return    void
     */
    public function SetDefaultTTL($intTTL) {
        $this->intTTL = (integer)$intTTL;
    }
    
    /**
     * A global function to delete ALL cached data. If caching is making a big
     * difference to performance, then running this function will incure a big
     * performance hit until cache data is built up again.
     * 
     * @return    void
     */
    public function DropEntireCache() {
        $sql = array();
        $sql[] = "DELETE FROM ".$this->strCacheTableName;
        $sql[] = "DELETE FROM ".$this->strCacheParamsTableName;
        $this->objDb->NonQuery($sql);
    }
    
    /**
     * Extract the ID number of a cache item based on it's parameters.
     * 
     * The sucessful return of a posative integer implies an affirmative cache 
     * hit. Generally an application will use this method to determine if it 
     * needs to build the potential cache item dynamically. The combination of 
     * the name value pairs in the cache parameters and the cache profile (name)
     * itself is necesarily a specification for uniqueness (primary key) and
     * uniquely selecting a cache record (item).
     * 
     * @param     string    The name of the cache profile
     * @param    array   The name/value pairs of the cache parameters
     * @return  integer    Cache item ID
     */
    public function intGetCacheKey($strProfile,$arrParams) {
        if(!array_key_exists($strProfile,$this->arrCacheProfiles)) {
            $this->XaoThrow(
                "The cache profile ".$strProfile." does not exist."
            );
            return;
        }
        $arrParamNames = $this->arrCacheProfiles[$strProfile];
        $sql = "SELECT cp.cache_key_id ";
        $strTableAlias = " FROM ".$this->strCacheParamsTableName." cp";
        $pfl = $strProfile;
        $this->objDb->PrepText($pfl);
        $strWhere = " WHERE cp.cache_param_name = 'CacheProfile' " .
            "AND cp.cache_param_value = $pfl ";
        $i = 0;
        foreach($arrParamNames as $strParamName) {
            if(!array_key_exists($strParamName,$arrParams)) {
                $this->XaoThrow(
                    "The cache param ".$strParamName." is required by the ".
                    $strProfile." cache profile."
                );
                return;
            }
            $strPrefix = "cp".$i++;
            $strTableAlias .=", ".$this->strCacheParamsTableName." ".$strPrefix;
            $strWhere .= " AND ".$strPrefix.".cache_key_id = cp.cache_key_id ";
            $pn = $strParamName;
            $this->objDb->PrepText($pn);
            $strWhere .= " AND ".$strPrefix.".cache_param_name = $pn ";
            $pv = trim($arrParams[$strParamName]);
            if(!strlen($pv)) $pv = $this->strNull;
            else $this->objDb->PrepText($pv);
            $strWhere .= " AND ".$strPrefix.".cache_param_value = $pv ";
        }
        $sql .= $strTableAlias.$strWhere;
        $intCacheKeyId = (integer)$this->objDb->mxdGetOne($sql);
        if(!$this->intCountErrors($this->objDb,true)) return $intCacheKeyId;
        return;
    }
    
    /**
     * Commits a cache item to the store and returns it's new ID number
     * 
     * If an item with the same set of criteria (name and params) is already in
     * the cache, it is replaced with the new cache data. Otherwise a new ID is
     * created and the data inserted.
     * 
     * @param    string    The name of the cache profile
     * @param    array    The name/value pairs of the cache parameters
     * @param     string    The data to be cached - must be text-based
     * @param    string    The mime-type (optional) (see base64 param)
     * @param    integer    Expiry unix timestamp
     * @param    bool    Whether or not the cache data is base64 encoded
     * 
     * @return  integer Cache item ID
     */
    public function intPutCache(
        $strProfile,
        $arrParams,
        $strCacheData,
        $strMime,
        $intExpires = null,
        $blnIsBase64 = false
    ) {
        $intTTL = (int)$this->intTTL;
        if((int)$intExpires) $intTTL = (int)$intExpires - time();
                                        // if expiry time was mis-calculated,
                                        // then don't set expiry time.
        if($intTTL < 0) $intTTL = 0;
        $intCacheKey = $this->intGetCacheKey($strProfile,$arrParams);
                                        // if there is no matching cache item,
                                        // then the number zero is returned.
        if(!is_integer($intCacheKey)) return;
        $strWhere = " cache_key_id = ";
                                        // prep cache item attributes
        if(is_null($intExpires) && !is_null($this->intTTL)) {
            $intExpires = time() + $this->intTTL;
        }
        ($blnIsBase64) ? $strIsBase64 = "'y'" : $strIsBase64 = "'n'";
        $strMimeType = $strMime;
        $this->objDb->PrepText($strMimeType);
        $strCacheData = trim($strCacheData);
        
        $intDataLen = strlen($strCacheData);
        if(!$intCacheKey && !$intDataLen) {
            return;
        }
        elseif($intCacheKey && !$intDataLen) {
            $this->DropCacheItem($intCacheKey);
            return;
        }
        elseif($intCacheKey) {
            $strWhere .= $intCacheKey;
            $sql = "UPDATE ".$this->strCacheTableName.
                " SET cache_is_base64 = ".$strIsBase64;
            $sql .= ", cache_created = SYSDATE ";
            $sql .= ", cache_mime_type = ".$strMimeType;
            if((int)$intTTL) {
                $sql .= ", cache_expires = SYSDATE + (".
                    (int)$intTTL."/86400) ";
            }
            else {
                $sql .= ", cache_expires = NULL";
            }
            $sql .= " WHERE ".$strWhere;
        }
        else {
            $sql = "SELECT SEQ_TEXT_CACHE_KEY_ID.nextval FROM dual";
            $intCacheKey = (integer)$this->objDb->mxdGetOne($sql);
            $strWhere .= $intCacheKey;
            
            $sql = "INSERT INTO ".$this->strCacheTableName.
                    " (cache_key_id,cache_content," .
                    "cache_is_base64,cache_mime_type";
            if((int)$intTTL) $sql .= ",cache_expires";
            $sql .= ") VALUES ($intCacheKey, EMPTY_CLOB()," .
                    "$strIsBase64,$strMimeType";
            if((int)$intTTL) $sql .= ",SYSDATE + (".
                (int)$intTTL."/86400) ";
            $sql .= ")";
            $arrSql = $this->_sqlMakeCacheParamsInsert(
                $strProfile,
                $arrParams,
                $intCacheKey
            );
            array_unshift($arrSql,$sql);
            $sql = $arrSql;
        }
        $this->objDb->NonQuery($sql);
        $res = $this->objDb->blnPutClob(
            $strCacheData,
            $this->strCacheTableName,
            "cache_content",
            $strWhere
        );
        if(!$this->intCountErrors($this->objDb,true)) return $intCacheKey;
        return;
    }
    
    /**
     * Helper function to build the insert sql for each cache parameter
     * 
     * @param    string    The name of the cache profile
     * @param    array    The name/value pairs of the cache parameters
     * @param    integer    Cache item ID
     * @return  sql
     */
    private function _sqlMakeCacheParamsInsert($strProfile,$arrParams,$intCacheKey) {
        if(!$intCacheKey) return;
        $arrSql = array();
        $pfl = $strProfile;
        $this->objDb->PrepText($pfl);
        $arrSql[] = "INSERT INTO ".$this->strCacheParamsTableName.
                " (cache_key_id,cache_param_name," .
                "cache_param_value) VALUES ($intCacheKey,'CacheProfile',$pfl)";
                                        // this could be improved to NOT use
                                        // multiple insert statements (comma-
                                        // separated brackets)
        foreach($arrParams as $strParamName => $strParamValue) {
            $this->objDb->PrepText($strParamName);
            if(!$strParamValue = trim($strParamValue)) {
                $strParamValue = $this->strNull;
            }
            else {
                $this->objDb->PrepText($strParamValue);
            }
            // to do : comma separated brackets for cache params
            $arrSql[] = "INSERT INTO ".$this->strCacheParamsTableName." " .
                "(cache_key_id, cache_param_name, cache_param_value) " .
                " VALUES " .
                "($intCacheKey, $strParamName, $strParamValue)";
        }
        return $arrSql;
    }
    
    /**
     * Delete an item from the cache
     * 
     * @param    integer    Cache item ID
     * @return  void
     */
    public function DropItemById($intCacheKey) {
        $intCacheKey = (integer)$intCacheKey;
        if(!$intCacheKey) return;
        $sql = array();
        $sql[] = "DELETE FROM ".$this->strCacheParamsTableName.
            " WHERE cache_key_id = $intCacheKey";
        $sql[] = "DELETE FROM ".$this->strCacheTableName.
            " WHERE cache_key_id = $intCacheKey";
        $this->objDb->NonQuery($sql);
        $this->intCountErrors($this->objDb,true);
    }
    
    /**
     * Retrieve an item from the cache
     * 
     * @param    integer    Cache item ID
     * @return    string    Cache data (payload)
     */
    public function strGetCache($intCacheKey) {
        $intCacheKey = (integer)$intCacheKey;
        $sql = "SELECT cache_content FROM ".$this->strCacheTableName.
            " WHERE cache_key_id = $intCacheKey AND (cache_expires IS NULL OR cache_expires > SYSDATE)";
        $strContent = $this->objDb->mxdGetOne($sql);
        $this->intCountErrors($this->objDb,true);
        if(!strlen(trim($strContent))) {
            $this->DropCacheItem($intCacheKey);
            return;
        }
        return $strContent;
    }
    
    /**
     * Get items from cache that match a single param name/value pair.
     * 
     * @param    string    The name of the cache profile
     * @param    string    The name of the parameter to seach for
     * @param    string    The value of the parameter to be matched
     * @return    array    A list of cache item IDs
     */
    public function arrGetCacheItemsByParam($strProfile,$strParamName,$strParamValue) {
        $arrKeys = array();
        $this->objDb->PrepText($strProfile);
        $this->objDb->PrepText($strParamName);
        $this->objDb->PrepText($strParamValue);
        $sql = "SELECT f.cache_key_id FROM ".
            $this->strCacheParamsTableName." f, ".
            $this->strCacheParamsTableName." p ".
            " WHERE f.cache_key_id = p.cache_key_id ".
            " AND f.cache_param_name = 'CacheProfile' ".
            " AND f.cache_param_value = $strProfile ".
            " AND p.cache_param_name = $strParamName ".
            " AND p.cache_param_value = $strParamValue ";
        $arrKeys = (integer)$this->objDb->arrQueryStream($sql);
        $this->intCountErrors($this->objDb,true);
        return $arrKeys;
    }
}
