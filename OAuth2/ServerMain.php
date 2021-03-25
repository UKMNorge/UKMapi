<?php

namespace UKMNorge\OAuth2;

use \OAuth2\Server as BshafferServer;

require_once('UKM/vendor/autoload.php');
require_once('UKMconfig.inc.php');

use OAuth2\GrantType\AuthorizationCode;


class ServerMain {
    
    static $server;
    static $storage;
    
    public static function getServer() {
        if( null == static::$server ) {
            static::init();
        }
        return static::$server;
    }

    public static function getStorage() {
        if( null == static::$storage ) {
            static::init();
        }
        return static::$storage;
    }
    
    private static function init() {

        \OAuth2\Autoloader::register();

        // $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
        static::$storage = new UserPdo(array('dsn' => UKM_ID_DB, 'username' => UKM_ID_USERNAME, 'password' => UKM_ID_PASSWORD));

        // Pass a storage object or array of storage objects to the OAuth2 server class
        static::$server = new BshafferServer(static::$storage);

        // Add the "User Credentials" grant type (authentication of the users)
        // static::$server->addGrantType(new UserCredentials(static::$storage));


        // ************** To add another grand types remove comments ********************

        // Add the "Client Credentials" grant type (it is the simplest of the grant types)
        // $server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));

        // Add the "Authorization Code" grant type (this is where the oauth magic happens)
        static::$server->addGrantType(new AuthorizationCode(static::$storage));
        
    }
}

?>