<?php

namespace UKMNorge\Slack\API\Response\Plugin;

class FileManager {

    const NAMESPACE_ID = 'SlackPlugin\\';
    private static $registered_autoloader = false;

    private static $plugins_dir;

    public function __construct( String $plugins_dir ) {
        static::$plugins_dir = rtrim($plugins_dir,'/').'/';
        
        if( !static::$registered_autoloader ) {
            spl_autoload_register([static::class,'autoload']);
            static::$registered_autoloader = true;
        }
    }

    public static function autoload( String $class_name ) {
        if (strpos($class_name, 'SlackPlugin\\') !== false) {
            $file = static::$plugins_dir . 
                str_replace(
                    '\\',
                    DIRECTORY_SEPARATOR,
                    explode(static::NAMESPACE_ID, $class_name)[1]
                ) .'.php';

            #error_log('FileManager::autoload( '. $file .' )');

            if (file_exists($file)) {
                require_once( $file );
            }
        }
    }
    
    public function registerPluginFilters($responseHandler) {
        error_log('FileManager: registerPluginFilters( '. get_class($responseHandler) .')');
        foreach( scandir( static::$plugins_dir ) as $plugin_folder ) {
            // Don't go up
            if( in_array( $plugin_folder, ['.','..'])) {
                continue;
            }
            // Each plugin should have its own folder
            if( is_dir( static::$plugins_dir . $plugin_folder ) ) {
                $this->hookPlugin( static::$plugins_dir . $plugin_folder, $responseHandler );
            }
        }
    }
    
    public function hookPlugin( $plugin_folder, $responseHandler) {
        foreach( scandir( $plugin_folder ) as $file ) {
            // Only php files allowed here
            if( pathinfo($file)['extension'] != 'php' ) {
                continue;
            }
            $class = 'SlackPlugin\\'. basename($plugin_folder) .'\\' . str_replace('.php','',$file);
            error_log('-> plugin: '. $class .' @ '. $plugin_folder .'/'. $file);
            $responseHandler->addFilter( new $class() );
        }
    }
}