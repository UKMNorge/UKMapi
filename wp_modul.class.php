<?php

require_once('UKM/flashbag.class.php');

/**
 * Modul for å bygge plugins over
 * 
 * Bygger på UKMRFIDwp::UKMModul som igjen bygger på UKMVideresending::UKMModul
 * Kun denne skal brukes fra nå
 */
class UKMWPmodul {
    public static $view_data = null;
    public static $ajax_response = null;
    public static $action = null;

    private static $path_plugin = null;
    private static $path_twig = null;
    private static $path_twigjs = null;

    private static $flashbag = null;
    
    /**
     * Initier modulen
     * 
     * @param string __DIR__
     */
    public static function init( $plugin_path ) {
        self::$flashbag = new UKMflash( basename( $plugin_path ) );
        self::$view_data = [];
        if( isset( $_GET['action'] ) ) {
            self::setAction( $_GET['action'] );
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
        return self::$flashbag;
    }

    /**
     * Get Flashbag
     * ALIAS: self::getFlash()
     * 
     * @return UKMflashbag
     */
    public static function getFlashbag() {
        return self::getFlash();
    }

    /**
     * Get include path for plugin
     * 
     * @return string $path_to_plugin
     */
    public static function getPluginPath() {
        return self::$path_plugin;
    }

    /**
     * Render admin-GUI
     */
    public static function renderAdmin() {
        self::init();
        ## ACTION CONTROLLER
        require_once('controller/'. self::getAction() .'.controller.php');
        
        ## RENDER
        echo TWIG( strtolower(self::getAction()) .'.html.twig', self::getViewData() , dirname(__FILE__), true);

        // Hvis modulen bruker TwigJS
        if( file_exists( self::$path_twigjs ) ) {
            require_once('UKM/inc/twig-js.inc.php');
            echo TWIGjs( dirname(__FILE__) );
        }
        return;
    }

    /**
     * Sett plugin path for inkludering av filer fra riktig sted
     * Initieres fra self::init()
     */
    public static function setPluginPath( $dir ) {
        self::$path_plugin  = $dir .'/';
        self::$path_twig    = self::$path_plugin .'twig/';
        self::$path_twigjs  = self::$path_twig .'js/';
    }

    /**
     * Hent hvilken viewAction som er aktive
     *
     * @return string
    **/
    public static function getAction() {
        return self::$action;
    }
    
    /**
     * Set aktiv viewAction
     * 
     * @param string $action
     * @return void
     */
    public static function setAction( $action ) {
        self::$action = $action;
    }
        
    /**
     * Hent alle view-data
     *
     * @return array
    **/
    public static function getViewData() {
        self::$view_data['UKMmodul_messages'] = self::getMessages();
        return self::$view_data;
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
            self::$view_data = array_merge( self::$view_data, $key_or_array );
        } else {
            self::$view_data[ $key_or_array ] = $data;
        }
    }
    
    /**
     * Hent alle ajax response-data
     *
     * @return array
    **/
    public static function getResponseData() {
        return self::$ajax_response;
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
            self::$ajax_response = array_merge( self::$ajax_response, $key_or_array );
        } else {
            self::$ajax_response[ $key_or_array ] = $data;
        }
    }

    public function require( $file ) {
        if( strpos( $file, 'UKM/' ) === 0 ) {
            require_once( $file );
        } else {
            require_once( self::getPluginPath() . $file );
        }
    }
}