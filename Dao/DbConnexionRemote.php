<?php
declare(strict_types=1);

namespace App\Dao;

use mysqli;
use PDO;

/**
 * Description of DbConnexion
 *
 * @author XQJM798
 */
class DbConnexionRemote
{

    private static $objInstance;

    /**
     * Class Constructor - Create a new database connection if one doesn't exist
     * Set to private so no-one can create a new instance via ' = new DB();'
     */
    private function __construct() {}

    /**
     * Like the constructor, we make __clone private so nobody can clone the instance
     */
    private function __clone() {}

    /**
     * Returns DB instance or create initial connection
     * @param
     * @return $objInstance;
     */
    public static function getInstance(  ) {
        set_time_limit(0);

        echo $_ENV['DB_DSN_REMOTE'],$_ENV['DB_USER_REMOTE'], $_ENV['DB_PASS_REMOTE'];

        if(!self::$objInstance){
            self::$objInstance = new PDO($_ENV['DB_DSN_REMOTE'],$_ENV['DB_USER_REMOTE'], $_ENV['DB_PASS_REMOTE']);
            self::$objInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // self::$objInstance->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
        }

        return self::$objInstance;

    } # end method

    /**
     * Passes on any static calls to this class onto the singleton PDO instance
     * @param $chrMethod , $arrArguments
     * @param $arrArguments
     * @return mixed $mix
     */
    final public static function __callStatic( $chrMethod, $arrArguments ) {

        $objInstance = self::getInstance();

        return call_user_func_array([$objInstance, $chrMethod], $arrArguments);

    } # end method


    public static  function getConnectionRDB() {
        $result = new mysqli($_ENV['REMOTE_DB_SERVER'], $_ENV['REMOTE_DB_USER'], $_ENV['REMOTE_DB_PASSWD'], $_ENV['REMOTE_DB_NAME']);
	    if ($result->connect_errno)
            echo "Echec lors de la connexion à MySQL : (" . $result->connect_errno . ") " . $result->connect_error;
        else
        return $result;
}


}
?>
