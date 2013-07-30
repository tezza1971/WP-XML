<?php
/**
 * The sole purpose of this class is to unit test _EntBase
 * 
 * Since _EntBase is designed to be a parent class, maybe it's a good idea to
 * unit test it in such a context. _EntBase won't work as designed if 
 * instatiated directly. At the moment, it only supports postgres RDBMS type,
 * although, the SQL code is fairly generic.
 */
class _EntBaseTest extends _EntBase {
    /**
     * This entity only supports the following RDBMS types
     * 
     * @access  public
     * @var     array
     */
    var $arrSupportedDbTypes = array(
        XAO_DRIVER_RDBMS_TYPE_POSTGRES
    );
 
    function _EntBaseTest($objDb) {
        $this->_EntBase(
            $objDb,
            "EntBaseTest",
            "XAO_EntBaseTest",
            "XAO_EntBaseTestView"
        );
    }
}