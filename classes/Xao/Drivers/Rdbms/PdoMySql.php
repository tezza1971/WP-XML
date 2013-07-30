<?php
/**
 * this class implements the PDO version of MySql from mysql.com
 */

/**
 * Basic requirements checking before class definition is attempted.
 */
                                        // check for the existance of PDO
if(!class_exists("PDO",false)) die(
    "The server does not have any PDO (PHP Data Objects) drivers installed."
);
                                        // check for the mysql PDO driver
$mysql_support = false;
foreach(PDO::getAvailableDrivers() as $driver) 
    if($driver == "mysql") $mysql_support = true;
if(!$mysql_support) die(
    "The server has PDO support, but the MySql PDO driver is not installed."
);
                                        // begin main class definition
/**
 * This class implements the XAO database object for the PDO version of MySQL.
 * 
 * 
 */
class       Xao_Drivers_Rdbms_PdoMySql
extends     Xao_Drivers_Rdbms_PdoA
{
    /**
     * Constructor establishes connection to RDBMS server
     * 
     * @param   string  database server username
     * @param   string  database server password
     * @param   string  name of the database
     * @param   string  database server host name
     *                  (leave blank to default to localhost)
     * @param   string  port number if using TCP hostname 
     *                  (leave blank for default)
     * @param   bool    Whether or not the connection should be persistent
     * @param   bool    Whether or not the app is being used in debug mode
     */
    function __construct(
        $DBUser, $DBPass, $DBName = false, $DBHost = false, $DBPort = false,
        $blnPersistent = false, 
        $blnDebug = false
    ) {
        $this->SetDriverType(XAO_DRIVER_RDBMS_TYPE_PDO_MYSQL);
        $this->blnDebug = $blnDebug;
        $this->SetPersistence($blnPersistent);
                                        // much of the following code borrowed
                                        // from ref.pdo-mysql.connection.php
        $DBNameEq = empty($DBName) ? '' : ";dbname=$DBName";
                                        // default host hardwired
        if (empty($DBHost)) $DBHost = 'localhost';
                                        // autodetect unix socket usage
        If ($DBHost[0] === '/')
        {
            $Connection = "unix_socket=$DBHost";
        }
        else
        {
                                        // default port hardwired
            if (empty($DBPort)) $DBPort = 3306;
            $Connection = "host=$DBHost;port=$DBPort";
        }         
        $this->SetConnString($Connection);
        try {
            $this->objPdo = new PDO(
                "mysql:".$Connection.$DBNameEq,
                $DBUser,
                $DBPass
            );
        }
        catch(PDOException $e) {
            $this->XaoThrowE($e,array("connection"=>$Connection));
        }
    }
}
