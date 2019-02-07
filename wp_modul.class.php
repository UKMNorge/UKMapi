<?php

require_once('UKM/flashbag.class.php');


/**
 * Modul for å bygge plugins over
 * 
 * Bygger på UKMRFIDwp::UKMModul som igjen bygger på UKMVideresending::UKMModul
 * Kun denne skal brukes fra nå
 */
abstract class UKMWPmodul {
    public static $view_data = null;
    public static $ajax_response = null;

    public static $flashbag = null;
    
    // ABSTRACT METHODS AND VARIABLES
    public static $action = null;
    abstract static function hook();
    abstract static function meny();
    
    /**
     * Initier modulen
     * 
     * @param string __DIR__
     */
    public static function init( $plugin_path ) {
        static::$flashbag = new UKMflash( basename( $plugin_path ) );
        static::$view_data = [];
        if( isset( $_GET['action'] ) ) {
            static::setAction( $_GET['action'] );
        }
        self::setPluginPath( $plugin_path );
    }
    
    /**
     * Get Flashbag 
     * Instance of UKMflashbag.class
     * 
     * @return UKMflashbag
     */
    public static function getFlash() {
        return static::$flashbag;
    }

    /**
     * Get Flashbag
     * ALIAS: static::getFlash()
     * 
     * @return UKMflashbag
     */
    public static function getFlashbag() {
        return static::getFlash();
    }

    /**
     * Get include path for plugin
     * 
     * @return string $path_to_plugin
     */
    public static function getPluginPath() {
        $child = get_called_class();
        return static::$path_plugin;
    }

    /**
     * Render admin-GUI
     */
    public static function renderAdmin() {
        //static::init();
        ## ACTION CONTROLLER
        static::require('controller/'. static::getAction() .'.controller.php');
        
        ## RENDER
        echo TWIG( strtolower(static::getAction()) .'.html.twig', static::getViewData() , static::getPath(), true);

        // Hvis modulen bruker TwigJS
        if( file_exists( static::getTwigJsPath() ) ) {
            require_once('UKM/inc/twig-js.inc.php');
            echo TWIGjs( static::getTwigJsPath() );
        }
        return;
    }

    /**
     * Sett plugin path for inkludering av filer fra riktig sted
     * Initieres fra static::init()
     */
    public static function setPluginPath( $dir ) {
        static::$path_plugin  = $dir .'/';
    }

    public static function getPath() {
        return static::$path_plugin;
    }

    public static function getTwigPath() {
        return static::getPath() .'twig/';;
    }

    public static function getTwigJsPath() {
        return static::getTwigPath() .'js/';
    }

    /**
     * Hent hvilken viewAction som er aktive
     *
     * @return string
    **/
    public static function getAction() {
        return static::$action;
    }
    
    /**
     * Set aktiv viewAction
     * 
     * @param string $action
     * @return void
     */
    public static function setAction( $action ) {
        static::$action = $action;
    }
        
    /**
     * Hent alle view-data
     *
     * @return array
    **/
    public static function getViewData() {
        static::$view_data['UKMmodul_messages'] = static::getFlashbag();
        return static::$view_data;
    }

    /**
     * Legg til viewdata
     * 
     * Tar i mot array med flere keys (ett parameter)
     * eller key, value (to parameter)
     *
     * @param [string|array] key eller [key => val]
     * @param [null|array] data hvis oppgitt key som string
     * @return void
    **/
    public static function addViewData( $key_or_array, $data=null ) {
        if( is_array( $key_or_array ) ) {
            static::$view_data = array_merge( static::$view_data, $key_or_array );
        } else {
            static::$view_data[ $key_or_array ] = $data;
        }
    }
    
    /**
     * Hent alle ajax response-data
     *
     * @return array
    **/
    public static function getResponseData() {
        return static::$ajax_response;
    }

    /**
     * Legg til ajax respons-data
     * 
     * Tar i mot array med flere keys (ett parameter)
     * eller key, value (to parameter)
     *
     * @param [string|array] key eller [key => val]
     * @param [null|array] data hvis oppgitt key som string
     * @return void
    **/
    public static function addResponseData( $key_or_array, $data=null ) {
        if( is_array( $key_or_array ) ) {
            static::$ajax_response = array_merge( static::$ajax_response, $key_or_array );
        } else {
            static::$ajax_response[ $key_or_array ] = $data;
        }
    }

    public function require( $file ) {
        if( strpos( $file, 'UKM/' ) === 0 ) {
            require_once( $file );
        } else {
            require_once( static::getPluginPath() . $file );
        }
    }
}