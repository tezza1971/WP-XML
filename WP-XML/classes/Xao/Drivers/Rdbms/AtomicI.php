<?php
/**
 * Interface for transaction capable database driver classes
 */
interface Xao_Drivers_Rdbms_AtomicI
{

    /**
     * Queries following this call are bundled in a transaction
     * 
     * Obviously, this method will only be overwritten if the RDBMS for the 
     * particular dirver supports transactions. Just pass RDBMS errors through
     * to handle issues like if nesting transactions is allowed etc.
     * 
     * @return    void
     */
    function BeginTransaction();

    /**
     * Queries since last BeginTransaction() call are cancelled
     * 
     * @return  void
     */
    function RollbackTransaction();

    /**
     * Queries since last BeginTransaction() committed and tansaction is closed.
     * 
     * @return  void
     */
    function CommitTransaction();
}
