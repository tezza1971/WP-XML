<?php
/**
 * This class is the base class of all cache classes.
 * 
 * Many of the methods below are abstract because the class is designed to 
 * specify a standard interface across all cache object types.
 */
abstract
class   Xao_Drivers_Cache_Cache 
extends Xao_Drivers_BaseDriver 
{
    /**
     * A list of all the profiles (cache names) in this cache repository
     * 
     * @var    array
     */
    protected $arrCacheProfiles;
    
    /**
     * This is a time-to-live value in seconds. It determins how old the cache 
     * item is allowed to be before it is regarded as stale and overwritten by
     * newly generated data.
     * 
     * @var    integer
     */
    protected $intTTL = null;

    /**
     * Set the default time-to-live value for the member variable
     * 
     * @param   integer    Time to live (in seconds) for this cache item.
     * @return  void
     */
    function SetDefaultTTL($intTTL) {
        $this->__not_implemented();
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
    protected function intGetCacheKey($strProfile,$arrParams) {
        $this->__not_implemented();
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
     * @return  integer Cache item ID
     */
    protected function intPutCache(
        $strProfile,
        $arrParams,
        $strCacheData,
        $strMime,
        $intExpires = null,
        $blnIsBase64 = false
    ) {
        $this->__not_implemented();
    }

    /**
     * Delete an item from the cache
     * 
     * @param    integer    Cache item ID
     * @return  void
     */
    protected function DropItemById($intCacheKey) {
        $this->__not_implemented();
    }

    /**
     * Retrieve an item from the cache
     * 
     * @param    integer    Cache item ID
     * @return    string    Cache data (payload)
     */
    protected function strGetCache($intCacheKey) {
        $this->__not_implemented();
    }

    /**
     * Get items from cache that match a single param name/value pair.
     * 
     * @param    string    The name of the cache profile
     * @param    string    The name of the parameter to seach for
     * @param    string    The value of the parameter to be matched
     * @return    array    A list of cache item IDs
     */
    protected function arrGetCacheItemsByParam($strProfile,$strParamName,$strParamValue) {
        $this->__not_implemented();
    }

    /**
     * Delete one cache object.
     * 
     * @return  void
     */
    protected function DropItemsByProfile($strProfile) {
        $this->__not_implemented();
    }

    /**
     * Delete the entire cache - every object in it.
     * 
     * @return    void
     */
    protected function DropEntireCache() {
        $this->__not_implemented();
    }
}
